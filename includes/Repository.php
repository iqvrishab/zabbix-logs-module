<?php
declare(strict_types = 1);

namespace Modules\LogManager\Includes;

class Repository {

    /**
     * Escape a value for safe use in SQL strings.
     */
    private static function esc(string $value): string {
        return zbx_dbstr($value);
    }

    /**
     * Get Log Sources with optional Zabbix host mapping.
     * Uses explicit GROUP BY to avoid ONLY_FULL_GROUP_BY errors.
     */
    public static function getLogSources(array $filters = []): array {
        $sql = 'SELECT s.source_id, s.hostname, s.ip_address, s.vendor,
                       s.first_seen, s.last_seen, s.enabled,
                       MAX(h.hostid) AS hostid,
                       MAX(h.name)   AS zabbix_host_name,
                       MAX(h.status) AS zabbix_host_status
                FROM log_sources s
                LEFT JOIN interface i ON i.ip = s.ip_address
                LEFT JOIN hosts h ON h.hostid = i.hostid AND h.status IN (0,1)
                WHERE 1=1';

        if (isset($filters['enabled'])) {
            $sql .= ' AND s.enabled = ' . (int) $filters['enabled'];
        }

        $sql .= ' GROUP BY s.source_id, s.hostname, s.ip_address, s.vendor,
                            s.first_seen, s.last_seen, s.enabled
                  ORDER BY s.last_seen DESC';

        $result = DBselect($sql);
        $sources = [];
        while ($row = DBfetch($result)) {
            $sources[] = $row;
        }
        return $sources;
    }

    /**
     * Enable or disable a log source.
     */
    public static function setSourceStatus(int $source_id, int $status): bool {
        return (bool) DBexecute(
            'UPDATE log_sources SET enabled = ' . $status .
            ' WHERE source_id = ' . $source_id
        );
    }

    /**
     * Delete a log source and all associated logs.
     */
    public static function deleteSource(int $source_id): bool {
        DBexecute('DELETE FROM log_alert_history lah
                   WHERE lah.log_id IN (
                       SELECT nl.log_id FROM network_logs nl WHERE nl.source_id = ' . $source_id . ')');
        DBexecute('DELETE FROM network_logs WHERE source_id = ' . $source_id);
        return (bool) DBexecute('DELETE FROM log_sources WHERE source_id = ' . $source_id);
    }

    /**
     * Build the WHERE clause for network_logs from filter array.
     */
    private static function buildLogWhere(array $filters): string {
        $where = ' WHERE 1=1';

        if (!empty($filters['source_id'])) {
            $where .= ' AND l.source_id = ' . (int) $filters['source_id'];
        }
        if (!empty($filters['hostname'])) {
            $where .= " AND l.hostname LIKE '%" . self::esc($filters['hostname']) . "%'";
        }
        if (!empty($filters['source_ip'])) {
            $where .= " AND l.source_ip LIKE '%" . self::esc($filters['source_ip']) . "%'";
        }
        if (isset($filters['severity']) && $filters['severity'] !== '') {
            $where .= ' AND l.severity = ' . (int) $filters['severity'];
        }
        if (isset($filters['facility']) && $filters['facility'] !== '') {
            $where .= ' AND l.facility = ' . (int) $filters['facility'];
        }
        if (!empty($filters['keyword'])) {
            $kw = self::esc($filters['keyword']);
            $where .= " AND l.message LIKE '%" . $kw . "%'";
        }
        if (!empty($filters['time_from'])) {
            $where .= " AND l.received_at >= '" . self::esc($filters['time_from']) . "'";
        }
        if (!empty($filters['time_to'])) {
            $where .= " AND l.received_at <= '" . self::esc($filters['time_to']) . "'";
        }

        return $where;
    }

    /**
     * Get network logs with filtering and pagination.
     * NOTE: DBselect($sql, $limit, $offset) handles paging natively in Zabbix.
     */
    public static function getLogs(array $filters = [], int $limit = 100, int $offset = 0): array {
        $sql = 'SELECT l.log_id, l.source_id, l.received_at, l.severity, l.facility,
                       l.hostname, l.source_ip, l.message, l.raw_message,
                       s.vendor
                FROM network_logs l
                LEFT JOIN log_sources s ON s.source_id = l.source_id'
             . self::buildLogWhere($filters)
             . ' ORDER BY l.received_at DESC';

        $result = ($limit > 0)
            ? DBselect($sql, $limit, $offset)
            : DBselect($sql);

        $logs = [];
        while ($row = DBfetch($result)) {
            $logs[] = $row;
        }
        return $logs;
    }

    /**
     * Count network logs matching the given filters.
     */
    public static function getLogsCount(array $filters = []): int {
        // Replace aliases with a simple count query
        $sql = 'SELECT COUNT(*) AS cnt FROM network_logs l'
             . self::buildLogWhere($filters);

        $row = DBfetch(DBselect($sql));
        return $row ? (int) $row['cnt'] : 0;
    }

    /**
     * Get dashboard KPI statistics.
     */
    public static function getDashboardStats(): array {
        $today       = date('Y-m-d 00:00:00');
        $ten_min_ago = date('Y-m-d H:i:s', time() - 600);

        $row_total = DBfetch(DBselect(
            "SELECT COUNT(*) AS cnt FROM network_logs WHERE received_at >= '$today'"
        ));
        $total_today = $row_total ? (int) $row_total['cnt'] : 0;

        $severities = array_fill(0, 8, 0);
        $res_sev    = DBselect(
            "SELECT severity, COUNT(*) AS cnt FROM network_logs
             WHERE received_at >= '$today'
             GROUP BY severity"
        );
        while ($row = DBfetch($res_sev)) {
            $sev = (int) $row['severity'];
            if ($sev >= 0 && $sev <= 7) {
                $severities[$sev] = (int) $row['cnt'];
            }
        }

        $row_dev = DBfetch(DBselect(
            'SELECT COUNT(*) AS cnt FROM log_sources WHERE enabled = 1'
        ));
        $devices_count = $row_dev ? (int) $row_dev['cnt'] : 0;

        $row_rate    = DBfetch(DBselect(
            "SELECT COUNT(*) AS cnt FROM network_logs WHERE received_at >= '$ten_min_ago'"
        ));
        $rate_10min  = $row_rate ? (int) $row_rate['cnt'] : 0;
        $lps         = round($rate_10min / 600, 2);

        return [
            'total_today'     => $total_today,
            'severities'      => $severities,
            'devices_count'   => $devices_count,
            'logs_per_second' => $lps,
        ];
    }

    /**
     * Top N devices by log count today.
     */
    public static function getTopDevices(int $limit = 10): array {
        $today = date('Y-m-d 00:00:00');
        $sql   = "SELECT hostname, source_ip, COUNT(*) AS cnt
                  FROM network_logs
                  WHERE received_at >= '$today'
                  GROUP BY hostname, source_ip
                  ORDER BY cnt DESC";

        $result  = DBselect($sql, $limit);
        $devices = [];
        while ($row = DBfetch($result)) {
            $devices[] = $row;
        }
        return $devices;
    }

    /**
     * Get all alert rules.
     */
    public static function getAlertRules(): array {
        $result = DBselect(
            'SELECT rule_id, name, regex_pattern, severity, enabled
             FROM log_alert_rules
             ORDER BY rule_id DESC'
        );
        $rules = [];
        while ($row = DBfetch($result)) {
            $rules[] = $row;
        }
        return $rules;
    }

    /**
     * Insert or update an alert rule.
     */
    public static function saveAlertRule(array $data): bool {
        $name    = self::esc($data['name']);
        $pattern = self::esc($data['regex_pattern']);
        $sev     = (int) $data['severity'];
        $enabled = (int) $data['enabled'];

        if (!empty($data['rule_id'])) {
            return (bool) DBexecute(
                "UPDATE log_alert_rules
                 SET name = '$name', regex_pattern = '$pattern',
                     severity = $sev, enabled = $enabled
                 WHERE rule_id = " . (int) $data['rule_id']
            );
        }

        return (bool) DBexecute(
            "INSERT INTO log_alert_rules (name, regex_pattern, severity, enabled)
             VALUES ('$name', '$pattern', $sev, $enabled)"
        );
    }

    /**
     * Delete an alert rule and its history.
     */
    public static function deleteAlertRule(int $rule_id): bool {
        DBexecute('DELETE FROM log_alert_history WHERE rule_id = ' . $rule_id);
        return (bool) DBexecute('DELETE FROM log_alert_rules WHERE rule_id = ' . $rule_id);
    }

    /**
     * Get recent alert history with join details.
     */
    public static function getAlertHistory(int $limit = 50): array {
        $sql = 'SELECT h.alert_id, h.matched_at,
                       r.name AS rule_name, r.severity AS rule_severity,
                       l.message, l.hostname, l.source_ip
                FROM log_alert_history h
                JOIN log_alert_rules r ON r.rule_id = h.rule_id
                JOIN network_logs l    ON l.log_id  = h.log_id
                ORDER BY h.matched_at DESC';

        $result  = DBselect($sql, $limit);
        $history = [];
        while ($row = DBfetch($result)) {
            $history[] = $row;
        }
        return $history;
    }

    /**
     * Get configured retention period in days.
     */
    public static function getRetentionDays(): int {
        $row = DBfetch(DBselect('SELECT retention_days FROM log_retention LIMIT 1'));
        return $row ? (int) $row['retention_days'] : 30;
    }

    /**
     * Update the retention period.
     */
    public static function updateRetentionDays(int $days): bool {
        DBexecute('DELETE FROM log_retention');
        return (bool) DBexecute(
            'INSERT INTO log_retention (retention_days) VALUES (' . $days . ')'
        );
    }

    /**
     * Daily log counts for the last 7 days.
     */
    public static function getDailyTrend(): array {
        $days = [];
        for ($i = 6; $i >= 0; $i--) {
            $days[date('Y-m-d', strtotime("-{$i} days"))] = 0;
        }

        $min_date = date('Y-m-d 00:00:00', strtotime('-6 days'));
        $result   = DBselect(
            "SELECT DATE(received_at) AS log_date, COUNT(*) AS cnt
             FROM network_logs
             WHERE received_at >= '$min_date'
             GROUP BY log_date"
        );
        while ($row = DBfetch($result)) {
            if (array_key_exists($row['log_date'], $days)) {
                $days[$row['log_date']] = (int) $row['cnt'];
            }
        }
        return $days;
    }

    /**
     * Hourly log counts for the last 24 hours.
     */
    public static function getHourlyTrend(): array {
        $hours = [];
        for ($i = 23; $i >= 0; $i--) {
            $key         = date('Y-m-d H:00:00', strtotime("-{$i} hours"));
            $hours[$key] = 0;
        }

        $min_date = date('Y-m-d H:00:00', strtotime('-23 hours'));
        $result   = DBselect(
            "SELECT DATE_FORMAT(received_at, '%Y-%m-%d %H:00:00') AS log_hour, COUNT(*) AS cnt
             FROM network_logs
             WHERE received_at >= '$min_date'
             GROUP BY log_hour"
        );
        while ($row = DBfetch($result)) {
            if (array_key_exists($row['log_hour'], $hours)) {
                $hours[$row['log_hour']] = (int) $row['cnt'];
            }
        }

        // Re-key to "HH:00" labels
        $out = [];
        foreach ($hours as $ts => $cnt) {
            $out[date('H:00', strtotime($ts))] = $cnt;
        }
        return $out;
    }

    /**
     * Log counts grouped by facility.
     */
    public static function getLogsByFacility(): array {
        $result     = DBselect(
            'SELECT facility, COUNT(*) AS cnt
             FROM network_logs
             GROUP BY facility
             ORDER BY cnt DESC'
        );
        $facilities = [];
        while ($row = DBfetch($result)) {
            $facilities[(int) $row['facility']] = (int) $row['cnt'];
        }
        return $facilities;
    }
}

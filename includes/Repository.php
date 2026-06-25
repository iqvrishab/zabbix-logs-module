<?php

namespace Modules\LogManager\Includes;

use DB;

class Repository {

    /**
     * Get database driver.
     */
    public static function getDbDriver(): string {
        global $DB;
        return isset($DB['TYPE']) ? strtolower($DB['TYPE']) : 'mysql';
    }

    /**
     * Safely escape input string using standard Zabbix DB method.
     */
    public static function escape(string $value): string {
        return db2Db($value); // db2Db is Zabbix helper to escape strings, or we can use DB::escape() if available
    }

    /**
     * Get Log Sources with optional mapping to Zabbix Hosts.
     */
    public static function getLogSources(array $filters = []): array {
        $sql = 'SELECT s.*, h.hostid, h.name AS zabbix_host_name, h.status AS zabbix_host_status
                FROM log_sources s
                LEFT JOIN interface i ON s.ip_address = i.ip
                LEFT JOIN hosts h ON i.hostid = h.hostid AND h.status IN (0,1)
                WHERE 1=1';

        if (isset($filters['enabled'])) {
            $sql .= ' AND s.enabled = ' . (int)$filters['enabled'];
        }

        $sql .= ' GROUP BY s.source_id ORDER BY s.last_seen DESC';
        
        $db_result = DBselect($sql);
        $sources = [];
        while ($row = DBfetch($db_result)) {
            $sources[] = $row;
        }
        return $sources;
    }

    /**
     * Enable/Disable a Log Source.
     */
    public static function setSourceStatus(int $source_id, int $status): bool {
        return DBexecute('UPDATE log_sources SET enabled = ' . $status . ' WHERE source_id = ' . $source_id);
    }

    /**
     * Delete a Log Source.
     */
    public static function deleteSource(int $source_id): bool {
        DBexecute('DELETE FROM network_logs WHERE source_id = ' . $source_id);
        return DBexecute('DELETE FROM log_sources WHERE source_id = ' . $source_id);
    }

    /**
     * Get Network Logs with advanced filtering, full text search, and pagination.
     */
    public static function getLogs(array $filters = [], int $limit = 100, int $offset = 0): array {
        $sql = 'SELECT l.*, s.vendor 
                FROM network_logs l
                LEFT JOIN log_sources s ON l.source_id = s.source_id
                WHERE 1=1';

        if (!empty($filters['source_id'])) {
            $sql .= ' AND l.source_id = ' . (int)$filters['source_id'];
        }
        if (!empty($filters['hostname'])) {
            $sql .= " AND l.hostname LIKE '%" . db2Db($filters['hostname']) . "%'";
        }
        if (!empty($filters['source_ip'])) {
            $sql .= " AND l.source_ip LIKE '%" . db2Db($filters['source_ip']) . "%'";
        }
        if (isset($filters['severity']) && $filters['severity'] !== '') {
            $sql .= ' AND l.severity = ' . (int)$filters['severity'];
        }
        if (isset($filters['facility']) && $filters['facility'] !== '') {
            $sql .= ' AND l.facility = ' . (int)$filters['facility'];
        }
        if (!empty($filters['keyword'])) {
            $keyword = db2Db($filters['keyword']);
            // Try fulltext search if matched, else fallback to LIKE
            $sql .= " AND (MATCH(l.message) AGAINST ('" . $keyword . "' IN BOOLEAN MODE) OR l.message LIKE '%" . $keyword . "%')";
        }
        if (!empty($filters['time_from'])) {
            $sql .= " AND l.received_at >= '" . db2Db($filters['time_from']) . "'";
        }
        if (!empty($filters['time_to'])) {
            $sql .= " AND l.received_at <= '" . db2Db($filters['time_to']) . "'";
        }

        $sql .= ' ORDER BY l.received_at DESC';

        if ($limit > 0) {
            $sql = DBselect($sql, $limit, $offset);
        } else {
            $sql = DBselect($sql);
        }

        $logs = [];
        $db_result = DBselect($sql);
        while ($row = DBfetch($db_result)) {
            $logs[] = $row;
        }
        return $logs;
    }

    /**
     * Count logs based on filters.
     */
    public static function getLogsCount(array $filters = []): int {
        $sql = 'SELECT COUNT(*) as cnt FROM network_logs l WHERE 1=1';

        if (!empty($filters['source_id'])) {
            $sql .= ' AND l.source_id = ' . (int)$filters['source_id'];
        }
        if (!empty($filters['hostname'])) {
            $sql .= " AND l.hostname LIKE '%" . db2Db($filters['hostname']) . "%'";
        }
        if (!empty($filters['source_ip'])) {
            $sql .= " AND l.source_ip LIKE '%" . db2Db($filters['source_ip']) . "%'";
        }
        if (isset($filters['severity']) && $filters['severity'] !== '') {
            $sql .= ' AND l.severity = ' . (int)$filters['severity'];
        }
        if (isset($filters['facility']) && $filters['facility'] !== '') {
            $sql .= ' AND l.facility = ' . (int)$filters['facility'];
        }
        if (!empty($filters['keyword'])) {
            $keyword = db2Db($filters['keyword']);
            $sql .= " AND (MATCH(l.message) AGAINST ('" . $keyword . "' IN BOOLEAN MODE) OR l.message LIKE '%" . $keyword . "%')";
        }
        if (!empty($filters['time_from'])) {
            $sql .= " AND l.received_at >= '" . db2Db($filters['time_from']) . "'";
        }
        if (!empty($filters['time_to'])) {
            $sql .= " AND l.received_at <= '" . db2Db($filters['time_to']) . "'";
        }

        $row = DBfetch(DBselect($sql));
        return $row ? (int)$row['cnt'] : 0;
    }

    /**
     * Get statistics for Dashboard overview.
     */
    public static function getDashboardStats(): array {
        $today = date('Y-m-d 00:00:00');
        
        // Total logs today
        $row_total = DBfetch(DBselect("SELECT COUNT(*) as cnt FROM network_logs WHERE received_at >= '$today'"));
        $total_today = $row_total ? (int)$row_total['cnt'] : 0;

        // Count by severity today
        $severities = [0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 7 => 0];
        $res_sev = DBselect("SELECT severity, COUNT(*) as cnt FROM network_logs WHERE received_at >= '$today' GROUP BY severity");
        while ($row = DBfetch($res_sev)) {
            $severities[(int)$row['severity']] = (int)$row['cnt'];
        }

        // Active Devices Count
        $row_dev = DBfetch(DBselect("SELECT COUNT(*) as cnt FROM log_sources WHERE enabled = 1"));
        $devices_count = $row_dev ? (int)$row_dev['cnt'] : 0;

        // Logs per second/minute (average in last 10 minutes)
        $ten_mins_ago = date('Y-m-d H:i:s', time() - 600);
        $row_rate = DBfetch(DBselect("SELECT COUNT(*) as cnt FROM network_logs WHERE received_at >= '$ten_mins_ago'"));
        $rate_ten_min = $row_rate ? (int)$row_rate['cnt'] : 0;
        $lps = round($rate_ten_min / 600, 2);

        return [
            'total_today' => $total_today,
            'severities' => $severities,
            'devices_count' => $devices_count,
            'logs_per_second' => $lps
        ];
    }

    /**
     * Get Top Devices sending logs today.
     */
    public static function getTopDevices(int $limit = 5): array {
        $today = date('Y-m-d 00:00:00');
        $sql = "SELECT hostname, source_ip, COUNT(*) as cnt 
                FROM network_logs 
                WHERE received_at >= '$today' 
                GROUP BY hostname, source_ip 
                ORDER BY cnt DESC 
                LIMIT $limit";
        
        $db_result = DBselect($sql);
        $devices = [];
        while ($row = DBfetch($db_result)) {
            $devices[] = $row;
        }
        return $devices;
    }

    /**
     * Get Alert Rules.
     */
    public static function getAlertRules(): array {
        $db_result = DBselect('SELECT * FROM log_alert_rules ORDER BY rule_id DESC');
        $rules = [];
        while ($row = DBfetch($db_result)) {
            $rules[] = $row;
        }
        return $rules;
    }

    /**
     * Save Alert Rule.
     */
    public static function saveAlertRule(array $data): bool {
        if (!empty($data['rule_id'])) {
            return DBexecute("UPDATE log_alert_rules SET 
                name = '" . db2Db($data['name']) . "', 
                regex_pattern = '" . db2Db($data['regex_pattern']) . "', 
                severity = " . (int)$data['severity'] . ", 
                enabled = " . (int)$data['enabled'] . " 
                WHERE rule_id = " . (int)$data['rule_id']);
        } else {
            return DBexecute("INSERT INTO log_alert_rules (name, regex_pattern, severity, enabled) VALUES (
                '" . db2Db($data['name']) . "', 
                '" . db2Db($data['regex_pattern']) . "', 
                " . (int)$data['severity'] . ", 
                " . (int)$data['enabled'] . ")");
        }
    }

    /**
     * Delete Alert Rule.
     */
    public static function deleteAlertRule(int $rule_id): bool {
        return DBexecute('DELETE FROM log_alert_rules WHERE rule_id = ' . $rule_id);
    }

    /**
     * Get Alert History.
     */
    public static function getAlertHistory(int $limit = 50): array {
        $sql = 'SELECT h.*, r.name as rule_name, r.severity as rule_severity, l.message, l.hostname, l.source_ip
                FROM log_alert_history h
                JOIN log_alert_rules r ON h.rule_id = r.rule_id
                JOIN network_logs l ON h.log_id = l.log_id
                ORDER BY h.matched_at DESC';
        
        $db_result = DBselect($sql, $limit);
        $history = [];
        while ($row = DBfetch($db_result)) {
            $history[] = $row;
        }
        return $history;
    }

    /**
     * Get Retention Settings.
     */
    public static function getRetentionDays(): int {
        $row = DBfetch(DBselect('SELECT retention_days FROM log_retention LIMIT 1'));
        return $row ? (int)$row['retention_days'] : 30;
    }

    /**
     * Update Retention Settings.
     */
    public static function updateRetentionDays(int $days): bool {
        DBexecute('DELETE FROM log_retention');
        return DBexecute('INSERT INTO log_retention (retention_days) VALUES (' . $days . ')');
    }

    /**
     * Get daily trend statistics (number of logs per day for last 7 days).
     */
    public static function getDailyTrend(): array {
        $days = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i days"));
            $days[$d] = 0;
        }

        $min_date = date('Y-m-d 00:00:00', strtotime('-6 days'));
        $sql = "SELECT DATE(received_at) as log_date, COUNT(*) as cnt 
                FROM network_logs 
                WHERE received_at >= '$min_date' 
                GROUP BY log_date";
        
        $db_result = DBselect($sql);
        while ($row = DBfetch($db_result)) {
            $days[$row['log_date']] = (int)$row['cnt'];
        }
        return $days;
    }

    /**
     * Get hourly trend stats for the last 24 hours.
     */
    public static function getHourlyTrend(): array {
        $hours = [];
        for ($i = 23; $i >= 0; $i--) {
            $h = date('H:i', strtotime("-$i hours"));
            // Group by hour block
            $hours[date('Y-m-d H:00:00', strtotime("-$i hours"))] = 0;
        }

        $min_date = date('Y-m-d H:00:00', strtotime('-23 hours'));
        $sql = "SELECT DATE_FORMAT(received_at, '%Y-%m-%d %H:00:00') as log_hour, COUNT(*) as cnt 
                FROM network_logs 
                WHERE received_at >= '$min_date' 
                GROUP BY log_hour";
        
        $db_result = DBselect($sql);
        while ($row = DBfetch($db_result)) {
            if (isset($hours[$row['log_hour']])) {
                $hours[$row['log_hour']] = (int)$row['cnt'];
            }
        }
        
        // Re-key to readable hour names (e.g. "14:00")
        $result = [];
        foreach ($hours as $ts => $cnt) {
            $result[date('H:00', strtotime($ts))] = $cnt;
        }
        return $result;
    }

    /**
     * Get logs count grouped by facility.
     */
    public static function getLogsByFacility(): array {
        $sql = "SELECT facility, COUNT(*) as cnt 
                FROM network_logs 
                GROUP BY facility 
                ORDER BY cnt DESC";
        $db_result = DBselect($sql);
        $facilities = [];
        while ($row = DBfetch($db_result)) {
            $facilities[(int)$row['facility']] = (int)$row['cnt'];
        }
        return $facilities;
    }
}

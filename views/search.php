<?php
/**
 * @var CView $this
 * @var array $data
 */

$severity_names = [
    '' => _('All Severities'),
    0 => _('0 - Emergency'),
    1 => _('1 - Alert'),
    2 => _('2 - Critical'),
    3 => _('3 - Error'),
    4 => _('4 - Warning'),
    5 => _('5 - Notice'),
    6 => _('6 - Informational'),
    7 => _('7 - Debug')
];

$facility_names = [
    '' => _('All Facilities'),
    0 => _('0 - kern'),
    1 => _('1 - user'),
    2 => _('2 - mail'),
    3 => _('3 - daemon'),
    4 => _('4 - auth'),
    5 => _('5 - syslog'),
    6 => _('6 - lpr'),
    7 => _('7 - news'),
    8 => _('8 - uucp'),
    9 => _('9 - cron'),
    10 => _('10 - authpriv'),
    11 => _('11 - ftp'),
    12 => _('12 - ntp'),
    13 => _('13 - security/audit'),
    14 => _('14 - console'),
    15 => _('15 - clock'),
    16 => _('16 - local0'),
    17 => _('17 - local1'),
    18 => _('18 - local2'),
    19 => _('19 - local3'),
    20 => _('20 - local4'),
    21 => _('21 - local5'),
    22 => _('22 - local6'),
    23 => _('23 - local7')
];

$severity_classes = [
    0 => 'sev-emergency',
    1 => 'sev-alert',
    2 => 'sev-critical',
    3 => 'sev-error',
    4 => 'sev-warning',
    5 => 'sev-notice',
    6 => 'sev-info',
    7 => 'sev-debug'
];
?>
<link rel="stylesheet" href="modules/zabbix-log-manager/assets/css/logmanager.css">

<div class="logmanager-wrapper">
    <!-- Header & Navigation -->
    <div class="logmanager-header">
        <h1><?= _('Log Manager') ?></h1>
        <ul class="logmanager-tabs">
            <li><a href="zabbix.php?action=logmanager.overview"><?= _('Overview') ?></a></li>
            <li><a href="zabbix.php?action=logmanager.livelogs"><?= _('Live Logs') ?></a></li>
            <li><a href="zabbix.php?action=logmanager.search" class="active"><?= _('Search') ?></a></li>
            <li><a href="zabbix.php?action=logmanager.devices"><?= _('Devices') ?></a></li>
            <li><a href="zabbix.php?action=logmanager.alerts"><?= _('Alerts') ?></a></li>
            <li><a href="zabbix.php?action=logmanager.statistics"><?= _('Statistics') ?></a></li>
            <li><a href="zabbix.php?action=logmanager.settings"><?= _('Settings') ?></a></li>
        </ul>
    </div>

    <!-- Search / Filter Form -->
    <div class="widget-panel">
        <div class="widget-header">
            <h2><?= _('Search & Filter Logs') ?></h2>
        </div>
        <div class="widget-body">
            <form action="zabbix.php" method="get" class="search-form">
                <input type="hidden" name="action" value="logmanager.search">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="time_from"><?= _('Time From') ?></label>
                        <input type="text" id="time_from" name="time_from" value="<?= htmlspecialchars($data['filters']['time_from']) ?>" placeholder="YYYY-MM-DD HH:MM:SS">
                    </div>
                    <div class="form-group">
                        <label for="time_to"><?= _('Time To') ?></label>
                        <input type="text" id="time_to" name="time_to" value="<?= htmlspecialchars($data['filters']['time_to']) ?>" placeholder="YYYY-MM-DD HH:MM:SS">
                    </div>
                    <div class="form-group">
                        <label for="hostname"><?= _('Hostname') ?></label>
                        <input type="text" id="hostname" name="hostname" value="<?= htmlspecialchars($data['filters']['hostname']) ?>" placeholder="e.g. Cisco-Core">
                    </div>
                    <div class="form-group">
                        <label for="source_ip"><?= _('Source IP') ?></label>
                        <input type="text" id="source_ip" name="source_ip" value="<?= htmlspecialchars($data['filters']['source_ip']) ?>" placeholder="e.g. 192.168.1.1">
                    </div>
                    <div class="form-group">
                        <label for="severity"><?= _('Severity') ?></label>
                        <select id="severity" name="severity">
                            <?php foreach ($severity_names as $val => $name): ?>
                                <option value="<?= $val ?>" <?= $data['filters']['severity'] === (string)$val ? 'selected' : '' ?>><?= htmlspecialchars($name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="facility"><?= _('Facility') ?></label>
                        <select id="facility" name="facility">
                            <?php foreach ($facility_names as $val => $name): ?>
                                <option value="<?= $val ?>" <?= $data['filters']['facility'] === (string)$val ? 'selected' : '' ?>><?= htmlspecialchars($name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group full-width" style="margin-top: 10px;">
                    <label for="keyword"><?= _('Full Text Search / Keyword') ?></label>
                    <input type="text" id="keyword" name="keyword" value="<?= htmlspecialchars($data['filters']['keyword']) ?>" placeholder="Search logs using keyword or text...">
                </div>

                <div class="form-buttons" style="margin-top: 15px;">
                    <button type="submit" class="btn-primary"><?= _('Search') ?></button>
                    <a href="zabbix.php?action=logmanager.search" class="btn-alt"><?= _('Reset') ?></a>
                    
                    <div style="float: right;">
                        <button type="submit" name="export" value="csv" class="btn-alt"><?= _('Export CSV') ?></button>
                        <button type="submit" name="export" value="json" class="btn-alt"><?= _('Export JSON') ?></button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Results Table -->
    <div class="widget-panel" style="margin-top: 20px;">
        <div class="widget-header flex-header">
            <h2><?= _('Search Results') ?></h2>
            <span>Total Matches: <?= number_format($data['total_count']) ?></span>
        </div>
        <div class="widget-body no-padding">
            <table class="list-table log-table">
                <thead>
                    <tr>
                        <th style="width: 15%"><?= _('Time') ?></th>
                        <th style="width: 15%"><?= _('Hostname') ?></th>
                        <th style="width: 12%"><?= _('Source IP') ?></th>
                        <th style="width: 10%"><?= _('Severity') ?></th>
                        <th style="width: 10%"><?= _('Facility') ?></th>
                        <th style="width: 38%"><?= _('Message') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data['logs'])): ?>
                        <tr>
                            <td colspan="6" class="text-center no-data"><?= _('No matching logs found.') ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($data['logs'] as $log): ?>
                            <tr>
                                <td><?= htmlspecialchars($log['received_at']) ?></td>
                                <td><strong><?= htmlspecialchars($log['hostname']) ?></strong></td>
                                <td><?= htmlspecialchars($log['source_ip']) ?></td>
                                <td>
                                    <span class="tag <?= $severity_classes[$log['severity']] ?>">
                                        <?= (int)$log['severity'] ?> - <?= htmlspecialchars(isset($severity_names[$log['severity']]) ? $severity_names[$log['severity']] : 'Unknown') ?>
                                    </span>
                                </td>
                                <td><?= (int)$log['facility'] ?> - <?= htmlspecialchars(isset($facility_names[$log['facility']]) ? $facility_names[$log['facility']] : 'Unknown') ?></td>
                                <td class="log-message-cell"><?= htmlspecialchars($log['message']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($data['total_count'] > $data['limit']): ?>
                <div class="pagination">
                    <?php
                    $total_pages = ceil($data['total_count'] / $data['limit']);
                    $current_page = $data['page'];
                    
                    $start_page = max(1, $current_page - 2);
                    $end_page = min($total_pages, $current_page + 2);
                    
                    // Build query string
                    $q_params = $data['filters'];
                    $q_params['action'] = 'logmanager.search';
                    
                    if ($current_page > 1) {
                        $q_params['page'] = $current_page - 1;
                        echo '<a href="zabbix.php?' . http_build_query($q_params) . '" class="btn-alt">&laquo; Previous</a>';
                    }
                    
                    for ($p = $start_page; $p <= $end_page; $p++) {
                        $q_params['page'] = $p;
                        $active_class = ($p === $current_page) ? 'active' : '';
                        echo '<a href="zabbix.php?' . http_build_query($q_params) . '" class="btn-alt ' . $active_class . '">' . $p . '</a>';
                    }
                    
                    if ($current_page < $total_pages) {
                        $q_params['page'] = $current_page + 1;
                        echo '<a href="zabbix.php?' . http_build_query($q_params) . '" class="btn-alt">Next &raquo;</a>';
                    }
                    ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

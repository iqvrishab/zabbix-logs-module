<?php
/**
 * @var CView $this
 * @var array $data
 */

$severity_names = [
    0 => _('Emergency'),
    1 => _('Alert'),
    2 => _('Critical'),
    3 => _('Error'),
    4 => _('Warning'),
    5 => _('Notice'),
    6 => _('Informational'),
    7 => _('Debug')
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
<script src="modules/zabbix-log-manager/assets/js/Chart.min.js"></script>

<div class="logmanager-wrapper">
    <!-- Header & Navigation -->
    <div class="logmanager-header">
        <h1><?= _('Log Manager') ?></h1>
        <ul class="logmanager-tabs">
            <li><a href="zabbix.php?action=logmanager.overview" class="active"><?= _('Overview') ?></a></li>
            <li><a href="zabbix.php?action=logmanager.livelogs"><?= _('Live Logs') ?></a></li>
            <li><a href="zabbix.php?action=logmanager.search"><?= _('Search') ?></a></li>
            <li><a href="zabbix.php?action=logmanager.devices"><?= _('Devices') ?></a></li>
            <li><a href="zabbix.php?action=logmanager.alerts"><?= _('Alerts') ?></a></li>
            <li><a href="zabbix.php?action=logmanager.statistics"><?= _('Statistics') ?></a></li>
            <li><a href="zabbix.php?action=logmanager.settings"><?= _('Settings') ?></a></li>
        </ul>
    </div>

    <!-- Cards Row -->
    <div class="logmanager-grid logmanager-cards">
        <div class="card bg-total">
            <div class="card-info">
                <h3><?= _('Total Logs Today') ?></h3>
                <div class="value"><?= number_format($data['stats']['total_today']) ?></div>
            </div>
            <div class="card-icon"><i class="icon-logs"></i></div>
        </div>

        <div class="card bg-critical">
            <div class="card-info">
                <h3><?= _('Critical Logs') ?></h3>
                <div class="value"><?= number_format($data['stats']['severities'][0] + $data['stats']['severities'][1] + $data['stats']['severities'][2] + $data['stats']['severities'][3]) ?></div>
            </div>
            <div class="card-icon"><i class="icon-critical"></i></div>
        </div>

        <div class="card bg-warning">
            <div class="card-info">
                <h3><?= _('Warning Logs') ?></h3>
                <div class="value"><?= number_format($data['stats']['severities'][4]) ?></div>
            </div>
            <div class="card-icon"><i class="icon-warning"></i></div>
        </div>

        <div class="card bg-info">
            <div class="card-info">
                <h3><?= _('Info & Notice') ?></h3>
                <div class="value"><?= number_format($data['stats']['severities'][5] + $data['stats']['severities'][6]) ?></div>
            </div>
            <div class="card-icon"><i class="icon-info"></i></div>
        </div>

        <div class="card bg-devices">
            <div class="card-info">
                <h3><?= _('Active Devices') ?></h3>
                <div class="value"><?= number_format($data['stats']['devices_count']) ?></div>
            </div>
            <div class="card-icon"><i class="icon-devices"></i></div>
        </div>

        <div class="card bg-rate">
            <div class="card-info">
                <h3><?= _('Logs / Sec') ?></h3>
                <div class="value"><?= $data['stats']['logs_per_second'] ?></div>
            </div>
            <div class="card-icon"><i class="icon-rate"></i></div>
        </div>
    </div>

    <!-- Content Sections -->
    <div class="logmanager-grid logmanager-two-columns">
        <!-- Top Devices Widget -->
        <div class="widget-panel">
            <div class="widget-header">
                <h2><?= _('Top Devices (Logs Today)') ?></h2>
            </div>
            <div class="widget-body">
                <?php if (empty($data['top_devices'])): ?>
                    <p class="no-data"><?= _('No logs received today.') ?></p>
                <?php else: ?>
                    <table class="list-table">
                        <thead>
                            <tr>
                                <th><?= _('Device / Hostname') ?></th>
                                <th><?= _('IP Address') ?></th>
                                <th class="text-right"><?= _('Count') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['top_devices'] as $device): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($device['hostname']) ?></strong></td>
                                    <td><?= htmlspecialchars($device['source_ip']) ?></td>
                                    <td class="text-right"><?= number_format($device['cnt']) ?></td>
                                </tr>
                            <?php endindex; // Wait, standard php foreach syntax is endforeach; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Critical Alerts Widget -->
        <div class="widget-panel">
            <div class="widget-header">
                <h2><?= _('Recent Alerts') ?></h2>
            </div>
            <div class="widget-body">
                <?php if (empty($data['recent_alerts'])): ?>
                    <p class="no-data"><?= _('No recent alerts matching regex rules.') ?></p>
                <?php else: ?>
                    <table class="list-table">
                        <thead>
                            <tr>
                                <th><?= _('Rule') ?></th>
                                <th><?= _('Severity') ?></th>
                                <th><?= _('Device') ?></th>
                                <th><?= _('Time') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['recent_alerts'] as $alert): ?>
                                <tr>
                                    <td><?= htmlspecialchars($alert['rule_name']) ?></td>
                                    <td>
                                        <span class="tag <?= $severity_classes[$alert['rule_severity']] ?>">
                                            <?= htmlspecialchars($severity_names[$alert['rule_severity']]) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($alert['hostname']) ?> (<?= htmlspecialchars($alert['source_ip']) ?>)</td>
                                    <td><?= htmlspecialchars($alert['matched_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

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

$retention_options = [
    7 => _('7 Days'),
    30 => _('30 Days'),
    90 => _('90 Days'),
    180 => _('180 Days'),
    365 => _('365 Days')
];

?>
<link rel="stylesheet" href="modules/zabbix-log-manager/assets/css/logmanager.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="logmanager-wrapper">
    <!-- Header & Navigation -->
    <div class="logmanager-header">
        <h1><?= _('Log Manager') ?></h1>
        <ul class="logmanager-tabs">
            <li><a href="zabbix.php?action=logmanager.view&tab=overview" class="<?= $data['tab'] === 'overview' ? 'active' : '' ?>"><?= _('Overview') ?></a></li>
            <li><a href="zabbix.php?action=logmanager.view&tab=livelogs" class="<?= $data['tab'] === 'livelogs' ? 'active' : '' ?>"><?= _('Live Logs') ?></a></li>
            <li><a href="zabbix.php?action=logmanager.view&tab=search" class="<?= $data['tab'] === 'search' ? 'active' : '' ?>"><?= _('Search') ?></a></li>
            <li><a href="zabbix.php?action=logmanager.view&tab=devices" class="<?= $data['tab'] === 'devices' ? 'active' : '' ?>"><?= _('Devices') ?></a></li>
            <li><a href="zabbix.php?action=logmanager.view&tab=alerts" class="<?= $data['tab'] === 'alerts' ? 'active' : '' ?>"><?= _('Alerts') ?></a></li>
            <li><a href="zabbix.php?action=logmanager.view&tab=statistics" class="<?= $data['tab'] === 'statistics' ? 'active' : '' ?>"><?= _('Statistics') ?></a></li>
            <li><a href="zabbix.php?action=logmanager.view&tab=settings" class="<?= $data['tab'] === 'settings' ? 'active' : '' ?>"><?= _('Settings') ?></a></li>
        </ul>
    </div>

    <!-- Messages Block -->
    <?php if (!empty($data['messages'])): ?>
        <?php foreach ($data['messages'] as $msg): ?>
            <div class="<?= $msg['type'] === 'error' ? 'error-msg-box' : 'success-msg-box' ?>">
                <?= htmlspecialchars($msg['text']) ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- ── TAB CONTENT ── -->

    <?php if ($data['tab'] === 'overview'): ?>
        <!-- Cards Row -->
        <div class="logmanager-grid logmanager-cards">
            <div class="card bg-total">
                <div class="card-info">
                    <h3><?= _('Total Logs Today') ?></h3>
                    <div class="value"><?= number_format($data['stats']['total_today']) ?></div>
                </div>
            </div>
            <div class="card bg-critical">
                <div class="card-info">
                    <h3><?= _('Critical Logs') ?></h3>
                    <div class="value"><?= number_format($data['stats']['severities'][0] + $data['stats']['severities'][1] + $data['stats']['severities'][2] + $data['stats']['severities'][3]) ?></div>
                </div>
            </div>
            <div class="card bg-warning">
                <div class="card-info">
                    <h3><?= _('Warning Logs') ?></h3>
                    <div class="value"><?= number_format($data['stats']['severities'][4]) ?></div>
                </div>
            </div>
            <div class="card bg-info">
                <div class="card-info">
                    <h3><?= _('Info & Notice') ?></h3>
                    <div class="value"><?= number_format($data['stats']['severities'][5] + $data['stats']['severities'][6]) ?></div>
                </div>
            </div>
            <div class="card bg-devices">
                <div class="card-info">
                    <h3><?= _('Active Devices') ?></h3>
                    <div class="value"><?= number_format($data['stats']['devices_count']) ?></div>
                </div>
            </div>
            <div class="card bg-rate">
                <div class="card-info">
                    <h3><?= _('Logs / Sec') ?></h3>
                    <div class="value"><?= $data['stats']['logs_per_second'] ?></div>
                </div>
            </div>
        </div>

        <div class="logmanager-grid logmanager-two-columns">
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
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

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
                                                <?= htmlspecialchars(str_replace('All ', '', $severity_names[$alert['rule_severity']])) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($alert['hostname']) ?></td>
                                        <td><?= htmlspecialchars($alert['matched_at']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    <?php elseif ($data['tab'] === 'livelogs'): ?>
        <div class="widget-panel">
            <div class="widget-header flex-header">
                <h2><?= _('Live Tail Stream') ?></h2>
                <div class="header-controls">
                    <button type="button" id="btn-pause-resume" class="btn-alt"><?= _('Pause') ?></button>
                    <button type="button" id="btn-clear-logs" class="btn-alt"><?= _('Clear View') ?></button>
                    <span class="status-indicator online" id="stream-status"><?= _('Streaming') ?></span>
                </div>
            </div>
            <div class="widget-body no-padding scrollable-table-container">
                <table class="list-table log-table" id="live-logs-table">
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
                    <tbody id="live-logs-tbody">
                        <tr id="no-logs-row">
                            <td colspan="6" class="text-center no-data"><?= _('Waiting for logs...') ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <script src="modules/zabbix-log-manager/assets/js/logmanager.js"></script>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                initLiveLogs();
            });
        </script>

    <?php elseif ($data['tab'] === 'search'): ?>
        <div class="widget-panel">
            <div class="widget-header">
                <h2><?= _('Search & Filter Logs') ?></h2>
            </div>
            <div class="widget-body">
                <form action="zabbix.php" method="get" class="search-form">
                    <input type="hidden" name="action" value="logmanager.view">
                    <input type="hidden" name="tab" value="search">
                    
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
                        <a href="zabbix.php?action=logmanager.view&tab=search" class="btn-alt"><?= _('Reset') ?></a>
                        
                        <div style="float: right;">
                            <button type="submit" name="export" value="csv" class="btn-alt"><?= _('Export CSV') ?></button>
                            <button type="submit" name="export" value="json" class="btn-alt"><?= _('Export JSON') ?></button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

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
                                            <?= (int)$log['severity'] ?> - <?= htmlspecialchars(str_replace('All ', '', $severity_names[$log['severity']])) ?>
                                        </span>
                                    </td>
                                    <td><?= (int)$log['facility'] ?> - <?= htmlspecialchars(str_replace('All ', '', $facility_names[$log['facility']])) ?></td>
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
                        
                        $q_params = $data['filters'];
                        $q_params['action'] = 'logmanager.view';
                        $q_params['tab'] = 'search';
                        
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

    <?php elseif ($data['tab'] === 'devices'): ?>
        <div class="widget-panel">
            <div class="widget-header">
                <h2><?= _('Log Sources & Device Mapping') ?></h2>
            </div>
            <div class="widget-body no-padding">
                <table class="list-table">
                    <thead>
                        <tr>
                            <th><?= _('Hostname') ?></th>
                            <th><?= _('IP Address') ?></th>
                            <th><?= _('Vendor') ?></th>
                            <th><?= _('First Seen') ?></th>
                            <th><?= _('Last Seen') ?></th>
                            <th><?= _('Zabbix Host Integration') ?></th>
                            <th><?= _('Status') ?></th>
                            <th><?= _('Actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($data['sources'])): ?>
                            <tr>
                                <td colspan="8" class="text-center no-data"><?= _('No devices found.') ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($data['sources'] as $source): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($source['hostname']) ?></strong></td>
                                    <td><?= htmlspecialchars($source['ip_address']) ?></td>
                                    <td>
                                        <span class="tag vendor-tag vendor-<?= strtolower(htmlspecialchars($source['vendor'])) ?>">
                                            <?= htmlspecialchars($source['vendor']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($source['first_seen']) ?></td>
                                    <td><?= htmlspecialchars($source['last_seen']) ?></td>
                                    <td>
                                        <?php if (!empty($source['hostid'])): ?>
                                            <a href="zabbix.php?action=host.edit&hostid=<?= (int)$source['hostid'] ?>" class="zabbix-link">
                                                <span class="status-indicator online"></span>
                                                <?= htmlspecialchars($source['zabbix_host_name']) ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="status-indicator offline"></span>
                                            <span class="no-mapping"><?= _('Unmapped') ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ((int)$source['enabled'] === 1): ?>
                                            <span class="status-indicator online"><?= _('Active') ?></span>
                                        <?php else: ?>
                                            <span class="status-indicator offline"><?= _('Disabled') ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="zabbix.php?action=logmanager.view&tab=livelogs&device_id=<?= (int)$source['source_id'] ?>" class="btn-alt btn-action-small">
                                            <?= _('Live') ?>
                                        </a>
                                        <a href="zabbix.php?action=logmanager.view&tab=search&hostname=<?= urlencode($source['hostname']) ?>&source_ip=<?= urlencode($source['ip_address']) ?>" class="btn-alt btn-action-small">
                                            <?= _('Search') ?>
                                        </a>
                                        <?php if ((int)$source['enabled'] === 1): ?>
                                            <a href="zabbix.php?action=logmanager.view&tab=devices&task=toggle_source&source_id=<?= (int)$source['source_id'] ?>&status=0" class="btn-alt btn-action-small btn-disable-action">
                                                <?= _('Disable') ?>
                                            </a>
                                        <?php else: ?>
                                            <a href="zabbix.php?action=logmanager.view&tab=devices&task=toggle_source&source_id=<?= (int)$source['source_id'] ?>&status=1" class="btn-alt btn-action-small btn-enable-action">
                                                <?= _('Enable') ?>
                                            </a>
                                        <?php endif; ?>
                                        <a href="zabbix.php?action=logmanager.view&tab=devices&task=delete_source&source_id=<?= (int)$source['source_id'] ?>" 
                                           class="btn-alt btn-action-small btn-delete-action" 
                                           onclick="return confirm('<?= _('Are you sure you want to delete this log source and all its logs?') ?>');">
                                            <?= _('Delete') ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php elseif ($data['tab'] === 'alerts'): ?>
        <div class="logmanager-grid logmanager-two-columns">
            <div class="widget-panel">
                <div class="widget-header flex-header">
                    <h2><?= _('Regex Alert Rules') ?></h2>
                    <button type="button" class="btn-primary" onclick="showRuleForm()"><?= _('New Rule') ?></button>
                </div>
                
                <div id="rule-form-container" class="rule-form-panel" style="display: none;">
                    <h3 id="form-title"><?= _('Create Alert Rule') ?></h3>
                    <form action="zabbix.php?action=logmanager.view&tab=alerts" method="post" class="alert-rule-form">
                        <input type="hidden" name="task" value="save_rule">
                        <input type="hidden" id="rule_id" name="rule_id" value="">
                        
                        <div class="form-group">
                            <label for="name"><?= _('Rule Name') ?></label>
                            <input type="text" id="name" name="name" required placeholder="e.g. BGP Session Down">
                        </div>
                        <div class="form-group">
                            <label for="regex_pattern"><?= _('Regex Pattern') ?></label>
                            <input type="text" id="regex_pattern" name="regex_pattern" required placeholder="e.g. BGP.*neighbor.*lost">
                        </div>
                        <div class="form-group">
                            <label for="severity"><?= _('Alert Severity') ?></label>
                            <select id="severity" name="severity">
                                <?php foreach ($severity_names as $val => $name): ?>
                                    <?php if ($val !== ''): ?>
                                        <option value="<?= $val ?>"><?= htmlspecialchars($name) ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group inline-group">
                            <label for="enabled"><?= _('Enabled') ?></label>
                            <input type="checkbox" id="enabled" name="enabled" value="1" checked>
                        </div>
                        
                        <div class="form-buttons">
                            <button type="submit" class="btn-primary"><?= _('Save') ?></button>
                            <button type="button" class="btn-alt" onclick="hideRuleForm()"><?= _('Cancel') ?></button>
                        </div>
                    </form>
                </div>

                <div class="widget-body no-padding" style="margin-top: 10px;">
                    <table class="list-table">
                        <thead>
                            <tr>
                                <th><?= _('Rule Name') ?></th>
                                <th><?= _('Regex Pattern') ?></th>
                                <th><?= _('Severity') ?></th>
                                <th><?= _('Status') ?></th>
                                <th><?= _('Actions') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($data['rules'])): ?>
                                <tr>
                                    <td colspan="5" class="text-center no-data"><?= _('No alert rules defined.') ?></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($data['rules'] as $rule): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($rule['name']) ?></strong></td>
                                        <td><code><?= htmlspecialchars($rule['regex_pattern']) ?></code></td>
                                        <td>
                                            <span class="tag <?= $severity_classes[$rule['severity']] ?>">
                                                <?= htmlspecialchars($severity_names[$rule['severity']]) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ((int)$rule['enabled'] === 1): ?>
                                                <span class="status-indicator online"><?= _('Enabled') ?></span>
                                            <?php else: ?>
                                                <span class="status-indicator offline"><?= _('Disabled') ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn-alt btn-action-small" onclick="editRule(<?= htmlspecialchars(json_encode($rule)) ?>)"><?= _('Edit') ?></button>
                                            <a href="zabbix.php?action=logmanager.view&tab=alerts&task=delete_rule&rule_id=<?= (int)$rule['rule_id'] ?>" 
                                               class="btn-alt btn-action-small btn-delete-action" 
                                               onclick="return confirm('<?= _('Delete this rule?') ?>');"><?= _('Delete') ?></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="widget-panel">
                <div class="widget-header">
                    <h2><?= _('Alert Match History') ?></h2>
                </div>
                <div class="widget-body no-padding">
                    <table class="list-table">
                        <thead>
                            <tr>
                                <th><?= _('Rule') ?></th>
                                <th><?= _('Matched Log') ?></th>
                                <th><?= _('Time') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($data['history'])): ?>
                                <tr>
                                    <td colspan="3" class="text-center no-data"><?= _('No alert match history found.') ?></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($data['history'] as $hist): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($hist['rule_name']) ?></strong></td>
                                        <td>
                                            <div class="alert-log-details">
                                                <span class="alert-log-host">[<?= htmlspecialchars($hist['hostname']) ?>]</span>
                                                <span class="alert-log-message"><?= htmlspecialchars($hist['message']) ?></span>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($hist['matched_at']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <script>
        function showRuleForm() {
            document.getElementById('rule-form-container').style.display = 'block';
            document.getElementById('form-title').innerText = '<?= _("Create Alert Rule") ?>';
            document.getElementById('rule_id').value = '';
            document.getElementById('name').value = '';
            document.getElementById('regex_pattern').value = '';
            document.getElementById('severity').value = '3';
            document.getElementById('enabled').checked = true;
        }
        function hideRuleForm() {
            document.getElementById('rule-form-container').style.display = 'none';
        }
        function editRule(rule) {
            document.getElementById('rule-form-container').style.display = 'block';
            document.getElementById('form-title').innerText = '<?= _("Edit Alert Rule") ?>';
            document.getElementById('rule_id').value = rule.rule_id;
            document.getElementById('name').value = rule.name;
            document.getElementById('regex_pattern').value = rule.regex_pattern;
            document.getElementById('severity').value = rule.severity;
            document.getElementById('enabled').checked = (parseInt(rule.enabled) === 1);
        }
        </script>

    <?php elseif ($data['tab'] === 'statistics'): ?>
        <?php
        // Prepare statistics variables for Chart.js
        $sev_labels = [];
        $sev_counts = [];
        $sev_colors = ['#d13b3b','#e94b4b','#f24f2f','#ff7800','#ffb300','#2baf2b','#00a3ff','#9e9e9e'];
        $sev_bg = [];
        foreach ($data['severity_stats'] as $sev => $cnt) {
            $sev_labels[] = isset($severity_names[$sev]) ? str_replace('0 - ', '', str_replace('1 - ', '', str_replace('2 - ', '', str_replace('3 - ', '', str_replace('4 - ', '', str_replace('5 - ', '', str_replace('6 - ', '', str_replace('7 - ', '', $severity_names[$sev])))))))) : "Severity $sev";
            $sev_counts[] = $cnt;
            $sev_bg[] = isset($sev_colors[$sev]) ? $sev_colors[$sev] : '#555';
        }
        $dev_labels = [];
        $dev_counts = [];
        foreach ($data['top_devices'] as $device) {
            $dev_labels[] = $device['hostname'];
            $dev_counts[] = $device['cnt'];
        }
        $daily_labels = array_keys($data['daily_trend']);
        $daily_counts = array_values($data['daily_trend']);
        $hourly_labels = array_keys($data['hourly_trend']);
        $hourly_counts = array_values($data['hourly_trend']);
        ?>
        <div class="logmanager-grid logmanager-two-columns">
            <div class="widget-panel">
                <div class="widget-header">
                    <h2><?= _('Logs by Severity') ?></h2>
                </div>
                <div class="widget-body chart-container">
                    <canvas id="chart-severity"></canvas>
                </div>
            </div>
            <div class="widget-panel">
                <div class="widget-header">
                    <h2><?= _('Logs by Device') ?></h2>
                </div>
                <div class="widget-body chart-container">
                    <canvas id="chart-devices"></canvas>
                </div>
            </div>
            <div class="widget-panel">
                <div class="widget-header">
                    <h2><?= _('Logs by Hour (Last 24 Hours)') ?></h2>
                </div>
                <div class="widget-body chart-container">
                    <canvas id="chart-hourly"></canvas>
                </div>
            </div>
            <div class="widget-panel">
                <div class="widget-header">
                    <h2><?= _('Logs by Day (Last 7 Days)') ?></h2>
                </div>
                <div class="widget-body chart-container">
                    <canvas id="chart-daily"></canvas>
                </div>
            </div>
        </div>
        <script>
        document.addEventListener("DOMContentLoaded", function() {
            new Chart(document.getElementById('chart-severity'), {
                type: 'doughnut',
                data: {
                    labels: <?= json_encode($sev_labels) ?>,
                    datasets: [{
                        data: <?= json_encode($sev_counts) ?>,
                        backgroundColor: <?= json_encode($sev_bg) ?>,
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'right', labels: { color: '#ccc' } }
                    }
                }
            });

            new Chart(document.getElementById('chart-devices'), {
                type: 'bar',
                data: {
                    labels: <?= json_encode($dev_labels) ?>,
                    datasets: [{
                        label: 'Log Count',
                        data:  <?= json_encode($dev_counts) ?>,
                        backgroundColor: '#00a3ff',
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { ticks: { color: '#ccc' } },
                        y: { ticks: { color: '#ccc' } }
                    }
                }
            });

            new Chart(document.getElementById('chart-hourly'), {
                type: 'line',
                data: {
                    labels: <?= json_encode($hourly_labels) ?>,
                    datasets: [{
                        label: 'Log Count',
                        data: <?= json_encode($hourly_counts) ?>,
                        borderColor: '#2baf2b',
                        backgroundColor: 'rgba(43, 175, 43, 0.1)',
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { ticks: { color: '#ccc' } },
                        y: { ticks: { color: '#ccc' } }
                    }
                }
            });

            new Chart(document.getElementById('chart-daily'), {
                type: 'line',
                data: {
                    labels: <?= json_encode($daily_labels) ?>,
                    datasets: [{
                        label: 'Log Count',
                        data: <?= json_encode($daily_counts) ?>,
                        borderColor: '#ff7800',
                        backgroundColor: 'rgba(255, 120, 0, 0.1)',
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { ticks: { color: '#ccc' } },
                        y: { ticks: { color: '#ccc' } }
                    }
                }
            });
        });
        </script>

    <?php elseif ($data['tab'] === 'settings'): ?>
        <div class="logmanager-grid logmanager-two-columns">
            <div class="widget-panel">
                <div class="widget-header">
                    <h2><?= _('Log Retention Policy') ?></h2>
                </div>
                <div class="widget-body">
                    <form action="zabbix.php?action=logmanager.view&tab=settings" method="post" class="settings-form">
                        <input type="hidden" name="task" value="save_settings">

                        <div class="form-group">
                            <label for="retention_days"><?= _('Keep Logs For') ?></label>
                            <select id="retention_days" name="retention_days" class="form-control-large">
                                <?php foreach ($retention_options as $days => $label): ?>
                                    <option value="<?= $days ?>" <?= (int)$data['retention_days'] === $days ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-help"><?= _('Logs older than the selected period will be automatically deleted by the cleanup script.') ?></small>
                        </div>

                        <div class="form-buttons" style="margin-top: 20px;">
                            <button type="submit" class="btn-primary"><?= _('Save Policy') ?></button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="widget-panel">
                <div class="widget-header">
                    <h2><?= _('Syslog Receiver Daemon Info') ?></h2>
                </div>
                <div class="widget-body receiver-info-panel">
                    <table class="receiver-table">
                        <tr>
                            <th><?= _('Service Status') ?></th>
                            <td><span class="status-indicator online"></span> Active / Listening</td>
                        </tr>
                        <tr>
                            <th><?= _('UDP Port') ?></th>
                            <td><code>514</code></td>
                        </tr>
                        <tr>
                            <th><?= _('TCP Port') ?></th>
                            <td><code>514</code></td>
                        </tr>
                        <tr>
                            <th><?= _('Receiver Script') ?></th>
                            <td><code>modules/zabbix-log-manager/scripts/syslog_receiver.py</code></td>
                        </tr>
                        <tr>
                            <th><?= _('Cleanup Task') ?></th>
                            <td><code>modules/zabbix-log-manager/scripts/log_cleanup.py</code></td>
                        </tr>
                    </table>
                    <div class="note-box" style="margin-top: 15px;">
                        <strong>Note:</strong> Make sure the Python daemon is running in the background as a systemd service or inside your Docker container. Use the cleanup script in a cron job for automatic execution.
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

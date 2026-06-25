<?php
/**
 * @var CView $this
 * @var array $data
 */
?>
<link rel="stylesheet" href="modules/zabbix-log-manager/assets/css/logmanager.css">

<div class="logmanager-wrapper">
    <!-- Header & Navigation -->
    <div class="logmanager-header">
        <h1><?= _('Log Manager') ?></h1>
        <ul class="logmanager-tabs">
            <li><a href="zabbix.php?action=logmanager.overview"><?= _('Overview') ?></a></li>
            <li><a href="zabbix.php?action=logmanager.livelogs" class="active"><?= _('Live Logs') ?></a></li>
            <li><a href="zabbix.php?action=logmanager.search"><?= _('Search') ?></a></li>
            <li><a href="zabbix.php?action=logmanager.devices"><?= _('Devices') ?></a></li>
            <li><a href="zabbix.php?action=logmanager.alerts"><?= _('Alerts') ?></a></li>
            <li><a href="zabbix.php?action=logmanager.statistics"><?= _('Statistics') ?></a></li>
            <li><a href="zabbix.php?action=logmanager.settings"><?= _('Settings') ?></a></li>
        </ul>
    </div>

    <!-- Live Logs Panel -->
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
</div>

<script src="modules/zabbix-log-manager/assets/js/logmanager.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        initLiveLogs("<?= htmlspecialchars($data['device_id']) ?>");
    });
</script>

<?php
/**
 * @var CView $this
 * @var array $data
 */

$retention_options = [
    7 => _('7 Days'),
    30 => _('30 Days'),
    90 => _('90 Days'),
    180 => _('180 Days'),
    365 => _('365 Days')
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
            <li><a href="zabbix.php?action=logmanager.search"><?= _('Search') ?></a></li>
            <li><a href="zabbix.php?action=logmanager.devices"><?= _('Devices') ?></a></li>
            <li><a href="zabbix.php?action=logmanager.alerts"><?= _('Alerts') ?></a></li>
            <li><a href="zabbix.php?action=logmanager.statistics"><?= _('Statistics') ?></a></li>
            <li><a href="zabbix.php?action=logmanager.settings" class="active"><?= _('Settings') ?></a></li>
        </ul>
    </div>

    <?php if (!empty($data['success_msg'])): ?>
        <div class="success-msg-box"><?= htmlspecialchars($data['success_msg']) ?></div>
    <?php endif; ?>

    <div class="logmanager-grid logmanager-two-columns">
        <!-- Retention Settings -->
        <div class="widget-panel">
            <div class="widget-header">
                <h2><?= _('Log Retention Policy') ?></h2>
            </div>
            <div class="widget-body">
                <form action="zabbix.php" method="post" class="settings-form">
                    <input type="hidden" name="action" value="logmanager.settings">
                    <input type="hidden" name="subaction" value="save">

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

        <!-- Syslog Receiver Daemon Status / Config Info -->
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
</div>

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
            <li><a href="zabbix.php?action=logmanager.livelogs"><?= _('Live Logs') ?></a></li>
            <li><a href="zabbix.php?action=logmanager.search"><?= _('Search') ?></a></li>
            <li><a href="zabbix.php?action=logmanager.devices" class="active"><?= _('Devices') ?></a></li>
            <li><a href="zabbix.php?action=logmanager.alerts"><?= _('Alerts') ?></a></li>
            <li><a href="zabbix.php?action=logmanager.statistics"><?= _('Statistics') ?></a></li>
            <li><a href="zabbix.php?action=logmanager.settings"><?= _('Settings') ?></a></li>
        </ul>
    </div>

    <!-- Devices Panel -->
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
                                    <!-- View Live logs for this specific source -->
                                    <a href="zabbix.php?action=logmanager.livelogs&device_id=<?= (int)$source['source_id'] ?>" class="btn-alt btn-action-small" title="<?= _('View Live Logs') ?>">
                                        <?= _('Live') ?>
                                    </a>
                                    
                                    <!-- Search logs for this specific source -->
                                    <a href="zabbix.php?action=logmanager.search&hostname=<?= urlencode($source['hostname']) ?>&source_ip=<?= urlencode($source['ip_address']) ?>" class="btn-alt btn-action-small" title="<?= _('Search logs') ?>">
                                        <?= _('Search') ?>
                                    </a>

                                    <!-- Enable / Disable -->
                                    <?php if ((int)$source['enabled'] === 1): ?>
                                        <a href="zabbix.php?action=logmanager.devices&action=toggle&source_id=<?= (int)$source['source_id'] ?>&status=0" class="btn-alt btn-action-small btn-disable-action">
                                            <?= _('Disable') ?>
                                        </a>
                                    <?php else: ?>
                                        <a href="zabbix.php?action=logmanager.devices&action=toggle&source_id=<?= (int)$source['source_id'] ?>&status=1" class="btn-alt btn-action-small btn-enable-action">
                                            <?= _('Enable') ?>
                                        </a>
                                    <?php endif; ?>

                                    <!-- Delete -->
                                    <a href="zabbix.php?action=logmanager.devices&action=delete&source_id=<?= (int)$source['source_id'] ?>" 
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
</div>

<?php
/**
 * @var CView $this
 * @var array $data
 */

$severities = [
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
            <li><a href="zabbix.php?action=logmanager.alerts" class="active"><?= _('Alerts') ?></a></li>
            <li><a href="zabbix.php?action=logmanager.statistics"><?= _('Statistics') ?></a></li>
            <li><a href="zabbix.php?action=logmanager.settings"><?= _('Settings') ?></a></li>
        </ul>
    </div>

    <?php if (!empty($data['error'])): ?>
        <div class="error-msg-box"><?= htmlspecialchars($data['error']) ?></div>
    <?php endif; ?>

    <div class="logmanager-grid logmanager-two-columns">
        <!-- Rules List & Create/Edit Form -->
        <div class="widget-panel">
            <div class="widget-header flex-header">
                <h2><?= _('Regex Alert Rules') ?></h2>
                <button type="button" class="btn-primary" onclick="showRuleForm()"><?= _('New Rule') ?></button>
            </div>
            
            <!-- Hidden Create/Edit Rule Form -->
            <div id="rule-form-container" class="rule-form-panel" style="display: none;">
                <h3 id="form-title"><?= _('Create Alert Rule') ?></h3>
                <form action="zabbix.php" method="post" class="alert-rule-form">
                    <input type="hidden" name="action" value="logmanager.alerts">
                    <input type="hidden" name="subaction" value="save">
                    <input type="hidden" id="rule_id" name="rule_id" value="">
                    
                    <div class="form-group">
                        <label for="name"><?= _('Rule Name') ?></label>
                        <input type="text" id="name" name="name" required placeholder="e.g. BGP Session Down">
                    </div>
                    <div class="form-group">
                        <label for="regex_pattern"><?= _('Regex Pattern') ?></label>
                        <input type="text" id="regex_pattern" name="regex_pattern" required placeholder="e.g. BGP.*neighbor.*lost">
                        <small class="form-help"><?= _('Standard regular expression. Case-insensitive matching is used.') ?></small>
                    </div>
                    <div class="form-group">
                        <label for="severity"><?= _('Alert Severity') ?></label>
                        <select id="severity" name="severity">
                            <?php foreach ($severities as $val => $name): ?>
                                <option value="<?= $val ?>"><?= htmlspecialchars($name) ?></option>
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
                                            <?= htmlspecialchars($severities[$rule['severity']]) ?>
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
                                        <a href="zabbix.php?action=logmanager.alerts&subaction=delete&rule_id=<?= (int)$rule['rule_id'] ?>" 
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

        <!-- Alert History -->
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
                                    <td>
                                        <strong title="Severity: <?= htmlspecialchars($severities[$hist['rule_severity']]) ?>">
                                            <?= htmlspecialchars($hist['rule_name']) ?>
                                        </strong>
                                    </td>
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

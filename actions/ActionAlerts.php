<?php

namespace Modules\LogManager\Actions;

use Core\CAction;
use CControllerResponseData;
use Modules\LogManager\Includes\Repository;

class ActionAlerts extends CAction {

    protected function init(): void {
        $this->disableSidValidation();
    }

    protected function checkInput(): bool {
        $fields = [
            'subaction' => 'in save,delete',
            'rule_id' => 'db log_alert_rules.rule_id',
            'name' => 'string',
            'regex_pattern' => 'string',
            'severity' => 'int32',
            'enabled' => 'in 0,1'
        ];
        return $this->validateInput($fields);
    }

    protected function checkPermissions(): bool {
        return $this->getUserType() >= USER_TYPE_ZABBIX_USER;
    }

    protected function doAction(): void {
        $error = '';
        if ($this->hasInput('subaction')) {
            if ($this->getInput('subaction') === 'save') {
                $data = [
                    'rule_id' => $this->getInput('rule_id', ''),
                    'name' => $this->getInput('name', ''),
                    'regex_pattern' => $this->getInput('regex_pattern', ''),
                    'severity' => $this->getInput('severity', 3),
                    'enabled' => $this->getInput('enabled', 1)
                ];
                
                if (empty($data['name']) || empty($data['regex_pattern'])) {
                    $error = _('Rule name and Regex pattern cannot be empty.');
                } else {
                    // Try parsing regex pattern in PHP to validate it
                    if (@preg_match('/' . $data['regex_pattern'] . '/', '') === false) {
                        $error = _('Invalid regular expression pattern.');
                    } else {
                        Repository::saveAlertRule($data);
                        header('Location: zabbix.php?action=logmanager.alerts');
                        exit;
                    }
                }
            } elseif ($this->getInput('subaction') === 'delete') {
                $rule_id = (int)$this->getInput('rule_id');
                Repository::deleteAlertRule($rule_id);
                header('Location: zabbix.php?action=logmanager.alerts');
                exit;
            }
        }

        $rules = Repository::getAlertRules();
        $history = Repository::getAlertHistory(50);

        $data = [
            'active_tab' => 'alerts',
            'rules' => $rules,
            'history' => $history,
            'error' => $error
        ];

        $response = new CControllerResponseData($data);
        $response->setTitle(_('Log Manager - Alerts'));
        $this->setResponse($response);
    }
}

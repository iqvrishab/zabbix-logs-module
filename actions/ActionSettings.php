<?php

namespace Modules\LogManager\Actions;

use Core\CAction;
use CControllerResponseData;
use Modules\LogManager\Includes\Repository;

class ActionSettings extends CAction {

    protected function init(): void {
        $this->disableSidValidation();
    }

    protected function checkInput(): bool {
        $fields = [
            'subaction' => 'in save',
            'retention_days' => 'ge 1'
        ];
        return $this->validateInput($fields);
    }

    protected function checkPermissions(): bool {
        return $this->getUserType() >= USER_TYPE_ZABBIX_USER;
    }

    protected function doAction(): void {
        $success_msg = '';
        if ($this->hasInput('subaction') && $this->getInput('subaction') === 'save') {
            $retention_days = (int)$this->getInput('retention_days', 30);
            Repository::updateRetentionDays($retention_days);
            $success_msg = _('Retention settings saved successfully.');
        }

        $retention_days = Repository::getRetentionDays();

        $data = [
            'active_tab' => 'settings',
            'retention_days' => $retention_days,
            'success_msg' => $success_msg
        ];

        $response = new CControllerResponseData($data);
        $response->setTitle(_('Log Manager - Settings'));
        $this->setResponse($response);
    }
}

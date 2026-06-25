<?php

namespace Modules\LogManager\Actions;

use Core\CAction;
use CControllerResponseData;
use Modules\LogManager\Includes\Repository;

class ActionOverview extends CAction {

    protected function init(): void {
        $this->disableSidValidation();
    }

    protected function checkInput(): bool {
        return true;
    }

    protected function checkPermissions(): bool {
        return $this->getUserType() >= USER_TYPE_ZABBIX_USER;
    }

    protected function doAction(): void {
        $data = [
            'stats' => Repository::getDashboardStats(),
            'top_devices' => Repository::getTopDevices(5),
            'recent_alerts' => Repository::getAlertHistory(10),
            'active_tab' => 'overview'
        ];

        $response = new CControllerResponseData($data);
        $response->setTitle(_('Log Manager - Overview'));
        $this->setResponse($response);
    }
}

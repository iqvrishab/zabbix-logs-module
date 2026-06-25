<?php

namespace Modules\LogManager\Actions;

use Core\CAction;
use CControllerResponseData;
use Modules\LogManager\Includes\Repository;

class ActionLiveLogs extends CAction {

    protected function init(): void {
        $this->disableSidValidation();
    }

    protected function checkInput(): bool {
        $fields = [
            'ajax' => 'in 1',
            'last_log_id' => 'db network_logs.log_id',
            'device_id' => 'int32'
        ];
        return $this->validateInput($fields);
    }

    protected function checkPermissions(): bool {
        return $this->getUserType() >= USER_TYPE_ZABBIX_USER;
    }

    protected function doAction(): void {
        if ($this->hasInput('ajax')) {
            $filters = [];
            if ($this->hasInput('device_id')) {
                $filters['source_id'] = $this->getInput('device_id');
            }
            
            // Get the logs
            $logs = Repository::getLogs($filters, 50, 0);
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'logs' => $logs]);
            exit;
        }

        $data = [
            'active_tab' => 'livelogs',
            'device_id' => $this->getInput('device_id', '')
        ];

        $response = new CControllerResponseData($data);
        $response->setTitle(_('Log Manager - Live Logs'));
        $this->setResponse($response);
    }
}

<?php

namespace Modules\LogManager\Actions;

use Core\CAction;
use CControllerResponseData;
use Modules\LogManager\Includes\Repository;

class ActionDevices extends CAction {

    protected function init(): void {
        $this->disableSidValidation();
    }

    protected function checkInput(): bool {
        $fields = [
            'action' => 'in toggle,delete',
            'source_id' => 'db log_sources.source_id',
            'status' => 'in 0,1'
        ];
        return $this->validateInput($fields);
    }

    protected function checkPermissions(): bool {
        return $this->getUserType() >= USER_TYPE_ZABBIX_USER;
    }

    protected function doAction(): void {
        if ($this->hasInput('action')) {
            $source_id = (int)$this->getInput('source_id');
            if ($this->getInput('action') === 'toggle') {
                $status = (int)$this->getInput('status');
                Repository::setSourceStatus($source_id, $status);
            } elseif ($this->getInput('action') === 'delete') {
                Repository::deleteSource($source_id);
            }
            
            // Redirect back to devices tab
            header('Location: zabbix.php?action=logmanager.devices');
            exit;
        }

        $sources = Repository::getLogSources();
        
        $data = [
            'active_tab' => 'devices',
            'sources' => $sources
        ];

        $response = new CControllerResponseData($data);
        $response->setTitle(_('Log Manager - Devices'));
        $this->setResponse($response);
    }
}

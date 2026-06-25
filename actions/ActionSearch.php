<?php

namespace Modules\LogManager\Actions;

use Core\CAction;
use CControllerResponseData;
use Modules\LogManager\Includes\Repository;

class ActionSearch extends CAction {

    protected function init(): void {
        $this->disableSidValidation();
    }

    protected function checkInput(): bool {
        $fields = [
            'hostname' => 'string',
            'source_ip' => 'string',
            'severity' => 'string',
            'facility' => 'string',
            'keyword' => 'string',
            'time_from' => 'string',
            'time_to' => 'string',
            'export' => 'in csv,json',
            'page' => 'int32'
        ];
        return $this->validateInput($fields);
    }

    protected function checkPermissions(): bool {
        return $this->getUserType() >= USER_TYPE_ZABBIX_USER;
    }

    protected function doAction(): void {
        $filters = [
            'hostname' => $this->getInput('hostname', ''),
            'source_ip' => $this->getInput('source_ip', ''),
            'severity' => $this->getInput('severity', ''),
            'facility' => $this->getInput('facility', ''),
            'keyword' => $this->getInput('keyword', ''),
            'time_from' => $this->getInput('time_from', ''),
            'time_to' => $this->getInput('time_to', '')
        ];

        // Handle export first
        if ($this->hasInput('export')) {
            $logs = Repository::getLogs($filters, 5000, 0); // Export limit 5000 logs
            
            if ($this->getInput('export') === 'csv') {
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="network_logs_' . date('Ymd_His') . '.csv"');
                $output = fopen('php://output', 'w');
                fputcsv($output, ['Log ID', 'Time', 'Severity', 'Facility', 'Hostname', 'IP', 'Message']);
                foreach ($logs as $log) {
                    fputcsv($output, [
                        $log['log_id'],
                        $log['received_at'],
                        $log['severity'],
                        $log['facility'],
                        $log['hostname'],
                        $log['source_ip'],
                        $log['message']
                    ]);
                }
                fclose($output);
                exit;
            } else {
                header('Content-Type: application/json');
                header('Content-Disposition: attachment; filename="network_logs_' . date('Ymd_His') . '.json"');
                echo json_encode($logs, JSON_PRETTY_PRINT);
                exit;
            }
        }

        // Paging details
        $page = max(1, $this->getInput('page', 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;
        
        $logs = Repository::getLogs($filters, $limit, $offset);
        $total_count = Repository::getLogsCount($filters);
        
        $data = [
            'active_tab' => 'search',
            'filters' => $filters,
            'logs' => $logs,
            'page' => $page,
            'total_count' => $total_count,
            'limit' => $limit
        ];

        $response = new CControllerResponseData($data);
        $response->setTitle(_('Log Manager - Search'));
        $this->setResponse($response);
    }
}

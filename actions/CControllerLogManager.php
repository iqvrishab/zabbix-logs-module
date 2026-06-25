<?php
declare(strict_types = 1);

use Modules\LogManager\Includes\Repository;

require_once __DIR__ . '/../includes/Repository.php';

class CControllerLogManager extends CController {

	private Repository $repo;
	private array      $messages = [];

	protected function init(): void {
		$this->disableCsrfValidation();
		$this->repo = new Repository();
	}

	protected function checkInput(): bool {
		return $this->validateInput([
			'tab'            => 'string',
			'task'           => 'string',
			'source_id'      => 'int32',
			'rule_id'        => 'int32',
			'status'         => 'int32',
			'name'           => 'string',
			'regex_pattern'  => 'string',
			'severity'       => 'string',
			'facility'       => 'string',
			'keyword'        => 'string',
			'time_from'      => 'string',
			'time_to'        => 'string',
			'export'         => 'in csv,json',
			'ajax'           => 'in 1',
			'page'           => 'int32',
			'retention_days' => 'int32',
		]);
	}

	protected function checkPermissions(): bool {
		return true;
	}

	protected function doAction(): void {
		$tab  = (string)$this->getInput('tab', 'overview');
		$task = (string)$this->getInput('task', '');

		if ($task !== '') {
			try {
				$redirect = $this->handleTask($task, $tab);
				if ($redirect) {
					$this->redirect($tab);
					return;
				}
			} catch (\Throwable $e) {
				$this->messages[] = ['type' => 'error', 'text' => $e->getMessage()];
			}
		}

		// Handle AJAX request for Live Logs
		if ($this->hasInput('ajax')) {
			$filters = [];
			if ($this->hasInput('source_id')) {
				$filters['source_id'] = $this->getInput('source_id');
			}
			$logs = $this->repo->getLogs($filters, 50, 0);
			header('Content-Type: application/json');
			echo json_encode(['success' => true, 'logs' => $logs]);
			exit;
		}

		// Gather search filters
		$filters = [
			'hostname'  => $this->getInput('hostname', ''),
			'source_ip' => $this->getInput('source_ip', ''),
			'severity'  => $this->getInput('severity', ''),
			'facility'  => $this->getInput('facility', ''),
			'keyword'   => $this->getInput('keyword', ''),
			'time_from' => $this->getInput('time_from', ''),
			'time_to'   => $this->getInput('time_to', '')
		];

		// Handle search exports
		if ($this->hasInput('export')) {
			$logs = $this->repo->getLogs($filters, 5000, 0);
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

		// Prepare view data
		$page = max(1, $this->getInput('page', 1));
		$limit = 50;
		$offset = ($page - 1) * $limit;

		$data = [
			'tab'            => $tab,
			'messages'       => $this->messages,
			'stats'          => $this->repo->getDashboardStats(),
			'top_devices'    => $this->repo->getTopDevices(10),
			'recent_alerts'  => $this->repo->getAlertHistory(10),
			'sources'        => $this->repo->getLogSources(),
			'rules'          => $this->repo->getAlertRules(),
			'history'        => $this->repo->getAlertHistory(50),
			'retention_days' => $this->repo->getRetentionDays(),
			'filters'        => $filters,
			'logs'           => ($tab === 'search') ? $this->repo->getLogs($filters, $limit, $offset) : [],
			'total_count'    => ($tab === 'search') ? $this->repo->getLogsCount($filters) : 0,
			'page'           => $page,
			'limit'          => $limit,
			
			// Statistics metrics
			'severity_stats' => $this->repo->getDashboardStats()['severities'],
			'facility_stats' => $this->repo->getLogsByFacility(),
			'daily_trend'    => $this->repo->getDailyTrend(),
			'hourly_trend'   => $this->repo->getHourlyTrend()
		];

		$response = new CControllerResponseData($data);
		$response->setTitle('Log Manager');
		$this->setResponse($response);
	}

	private function handleTask(string $task, string $tab): bool {
		switch ($task) {
			case 'toggle_source':
				$this->repo->setSourceStatus((int)$this->getInput('source_id'), (int)$this->getInput('status'));
				$this->messages[] = ['type' => 'success', 'text' => 'Source status updated.'];
				return true;

			case 'delete_source':
				$this->repo->deleteSource((int)$this->getInput('source_id'));
				$this->messages[] = ['type' => 'success', 'text' => 'Source deleted.'];
				return true;

			case 'save_rule':
				$data = [
					'rule_id'       => $this->getInput('rule_id', ''),
					'name'          => $this->getInput('name', ''),
					'regex_pattern' => $this->getInput('regex_pattern', ''),
					'severity'      => $this->getInput('severity', 3),
					'enabled'       => $this->getInput('enabled', 1)
				];
				if (empty($data['name']) || empty($data['regex_pattern'])) {
					throw new \RuntimeException('Rule name and Regex pattern cannot be empty.');
				}
				if (@preg_match('/' . $data['regex_pattern'] . '/', '') === false) {
					throw new \RuntimeException('Invalid regular expression pattern.');
				}
				$this->repo->saveAlertRule($data);
				$this->messages[] = ['type' => 'success', 'text' => 'Alert rule saved.'];
				return true;

			case 'delete_rule':
				$this->repo->deleteAlertRule((int)$this->getInput('rule_id'));
				$this->messages[] = ['type' => 'success', 'text' => 'Alert rule deleted.'];
				return true;

			case 'save_settings':
				$days = (int)$this->getInput('retention_days', 30);
				$this->repo->updateRetentionDays($days);
				$this->messages[] = ['type' => 'success', 'text' => 'Retention policy updated successfully.'];
				return true;
		}
		return false;
	}

	private function redirect(string $tab): void {
		header('Location: zabbix.php?action=logmanager.view&tab=' . $tab);
		exit;
	}
}

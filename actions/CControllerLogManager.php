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
			'hostname'       => 'string',
			'source_ip'      => 'string',
			'keyword'        => 'string',
			'time_from'      => 'string',
			'time_to'        => 'string',
			'export'         => 'in csv,json',
			'ajax'           => 'in 1',
			'page'           => 'int32',
			'retention_days' => 'int32',
			'enabled'        => 'string',
		]);
	}

	protected function checkPermissions(): bool {
		return true;
	}

	protected function doAction(): void {
		$tab  = (string) $this->getInput('tab', 'overview');
		$task = (string) $this->getInput('task', '');

		// Handle AJAX live logs request
		if ($this->hasInput('ajax')) {
			$filters = [];
			if ($this->hasInput('source_id')) {
				$filters['source_id'] = (int) $this->getInput('source_id');
			}
			$logs = $this->repo->getLogs($filters, 50, 0);
			header('Content-Type: application/json');
			echo json_encode(['success' => true, 'logs' => $logs]);
			exit;
		}

		// Handle tasks with redirect
		if ($task !== '') {
			try {
				if ($this->handleTask($task, $tab)) {
					$this->redirect($tab);
					return;
				}
			} catch (\Throwable $e) {
				$this->messages[] = ['type' => 'error', 'text' => $e->getMessage()];
			}
		}

		// Handle export requests
		if ($this->hasInput('export')) {
			$this->handleExport();
			return;
		}

		// Search filters
		$filters = [
			'hostname'  => (string) $this->getInput('hostname',  ''),
			'source_ip' => (string) $this->getInput('source_ip', ''),
			'severity'  => (string) $this->getInput('severity',  ''),
			'facility'  => (string) $this->getInput('facility',  ''),
			'keyword'   => (string) $this->getInput('keyword',   ''),
			'time_from' => (string) $this->getInput('time_from', ''),
			'time_to'   => (string) $this->getInput('time_to',   ''),
		];

		$page   = max(1, (int) $this->getInput('page', 1));
		$limit  = 50;
		$offset = ($page - 1) * $limit;

		$stats = $this->repo->getDashboardStats();

		$data = [
			'tab'           => $tab,
			'messages'      => $this->messages,
			'stats'         => $stats,
			'top_devices'   => $this->repo->getTopDevices(10),
			'recent_alerts' => $this->repo->getAlertHistory(10),
			'sources'       => $this->repo->getLogSources(),
			'rules'         => $this->repo->getAlertRules(),
			'history'       => $this->repo->getAlertHistory(50),
			'retention_days'=> $this->repo->getRetentionDays(),
			'filters'       => $filters,
			'logs'          => ($tab === 'search')
				? $this->repo->getLogs($filters, $limit, $offset)
				: [],
			'total_count'   => ($tab === 'search')
				? $this->repo->getLogsCount($filters)
				: 0,
			'page'          => $page,
			'limit'         => $limit,
			'severity_stats'=> $stats['severities'],
			'facility_stats'=> $this->repo->getLogsByFacility(),
			'daily_trend'   => $this->repo->getDailyTrend(),
			'hourly_trend'  => $this->repo->getHourlyTrend(),
		];

		$response = new CControllerResponseData($data);
		$response->setTitle('Log Manager');
		$this->setResponse($response);
	}

	// ── Task dispatcher ─────────────────────────────────────────────────

	private function handleTask(string $task, string $tab): bool {
		switch ($task) {
			case 'toggle_source':
				$this->repo->setSourceStatus(
					(int) $this->getInput('source_id'),
					(int) $this->getInput('status', 1)
				);
				$this->messages[] = ['type' => 'success', 'text' => 'Source status updated.'];
				return true;

			case 'delete_source':
				$this->repo->deleteSource((int) $this->getInput('source_id'));
				$this->messages[] = ['type' => 'success', 'text' => 'Source deleted.'];
				return true;

			case 'save_rule':
				$name    = trim((string) $this->getInput('name', ''));
				$pattern = trim((string) $this->getInput('regex_pattern', ''));
				if ($name === '' || $pattern === '') {
					throw new \RuntimeException('Rule name and Regex pattern are required.');
				}
				if (@preg_match('/' . $pattern . '/', '') === false) {
					throw new \RuntimeException('Invalid regular expression pattern.');
				}
				$this->repo->saveAlertRule([
					'rule_id'       => $this->getInput('rule_id', ''),
					'name'          => $name,
					'regex_pattern' => $pattern,
					'severity'      => (int) $this->getInput('severity', 3),
					'enabled'       => $this->hasInput('enabled') ? 1 : 0,
				]);
				$this->messages[] = ['type' => 'success', 'text' => 'Alert rule saved.'];
				return true;

			case 'delete_rule':
				$this->repo->deleteAlertRule((int) $this->getInput('rule_id'));
				$this->messages[] = ['type' => 'success', 'text' => 'Alert rule deleted.'];
				return true;

			case 'save_settings':
				$this->repo->updateRetentionDays(max(1, (int) $this->getInput('retention_days', 30)));
				$this->messages[] = ['type' => 'success', 'text' => 'Retention policy saved.'];
				return true;
		}
		return false;
	}

	// ── Export handler ───────────────────────────────────────────────────

	private function handleExport(): void {
		$filters = [
			'hostname'  => (string) $this->getInput('hostname',  ''),
			'source_ip' => (string) $this->getInput('source_ip', ''),
			'severity'  => (string) $this->getInput('severity',  ''),
			'facility'  => (string) $this->getInput('facility',  ''),
			'keyword'   => (string) $this->getInput('keyword',   ''),
			'time_from' => (string) $this->getInput('time_from', ''),
			'time_to'   => (string) $this->getInput('time_to',   ''),
		];
		$logs = $this->repo->getLogs($filters, 5000, 0);
		$ts   = date('Ymd_His');

		if ($this->getInput('export') === 'csv') {
			header('Content-Type: text/csv');
			header("Content-Disposition: attachment; filename=\"network_logs_{$ts}.csv\"");
			$fp = fopen('php://output', 'w');
			fputcsv($fp, ['Log ID', 'Time', 'Severity', 'Facility', 'Hostname', 'IP', 'Message']);
			foreach ($logs as $log) {
				fputcsv($fp, [
					$log['log_id'], $log['received_at'], $log['severity'],
					$log['facility'], $log['hostname'], $log['source_ip'], $log['message'],
				]);
			}
			fclose($fp);
		} else {
			header('Content-Type: application/json');
			header("Content-Disposition: attachment; filename=\"network_logs_{$ts}.json\"");
			echo json_encode($logs, JSON_PRETTY_PRINT);
		}
		exit;
	}

	private function redirect(string $tab): void {
		header('Location: zabbix.php?action=logmanager.view&tab=' . urlencode($tab));
		exit;
	}
}

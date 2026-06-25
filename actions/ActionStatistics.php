<?php

namespace Modules\LogManager\Actions;

use Core\CAction;
use CControllerResponseData;
use Modules\LogManager\Includes\Repository;

class ActionStatistics extends CAction {

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
        $severity_stats = Repository::getDashboardStats()['severities'];
        $top_devices = Repository::getTopDevices(10);
        $facility_stats = Repository::getLogsByFacility();
        $daily_trend = Repository::getDailyTrend();
        $hourly_trend = Repository::getHourlyTrend();

        $data = [
            'active_tab' => 'statistics',
            'severity_stats' => $severity_stats,
            'top_devices' => $top_devices,
            'facility_stats' => $facility_stats,
            'daily_trend' => $daily_trend,
            'hourly_trend' => $hourly_trend
        ];

        $response = new CControllerResponseData($data);
        $response->setTitle(_('Log Manager - Statistics'));
        $this->setResponse($response);
    }
}

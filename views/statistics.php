<?php
/**
 * @var CView $this
 * @var array $data
 */

$severity_names = [
    0 => 'Emergency',
    1 => 'Alert',
    2 => 'Critical',
    3 => 'Error',
    4 => 'Warning',
    5 => 'Notice',
    6 => 'Informational',
    7 => 'Debug'
];

$severity_colors = [
    0 => '#d13b3b', # Emergency - Red
    1 => '#e94b4b', # Alert - Red
    2 => '#f24f2f', # Critical - Orange-Red
    3 => '#ff7800', # Error - Orange
    4 => '#ffb300', # Warning - Yellow
    5 => '#2baf2b', # Notice - Green
    6 => '#00a3ff', # Info - Blue
    7 => '#9e9e9e'  # Debug - Grey
];

// Process severity data
$severity_labels = [];
$severity_counts = [];
$severity_bg = [];
foreach ($data['severity_stats'] as $sev => $cnt) {
    $severity_labels[] = isset($severity_names[$sev]) ? $severity_names[$sev] : "Severity $sev";
    $severity_counts[] = $cnt;
    $severity_bg[] = isset($severity_colors[$sev]) ? $severity_colors[$sev] : '#555';
}

// Process device data
$device_labels = [];
$device_counts = [];
foreach ($data['top_devices'] as $device) {
    $device_labels[] = $device['hostname'];
    $device_counts[] = $device['cnt'];
}

// Process daily trend
$daily_labels = array_keys($data['daily_trend']);
$daily_counts = array_values($data['daily_trend']);

// Process hourly trend
$hourly_labels = array_keys($data['hourly_trend']);
$hourly_counts = array_values($data['hourly_trend']);
?>
<link rel="stylesheet" href="modules/zabbix-log-manager/assets/css/logmanager.css">
<!-- Load Chart.js from CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="logmanager-wrapper">
    <!-- Header & Navigation -->
    <div class="logmanager-header">
        <h1><?= _('Log Manager') ?></h1>
        <ul class="logmanager-tabs">
            <li><a href="zabbix.php?action=logmanager.overview"><?= _('Overview') ?></a></li>
            <li><a href="zabbix.php?action=logmanager.livelogs"><?= _('Live Logs') ?></a></li>
            <li><a href="zabbix.php?action=logmanager.search"><?= _('Search') ?></a></li>
            <li><a href="zabbix.php?action=logmanager.devices"><?= _('Devices') ?></a></li>
            <li><a href="zabbix.php?action=logmanager.alerts"><?= _('Alerts') ?></a></li>
            <li><a href="zabbix.php?action=logmanager.statistics" class="active"><?= _('Statistics') ?></a></li>
            <li><a href="zabbix.php?action=logmanager.settings"><?= _('Settings') ?></a></li>
        </ul>
    </div>

    <!-- Charts Grid -->
    <div class="logmanager-grid logmanager-two-columns">
        <!-- Severity Distribution -->
        <div class="widget-panel">
            <div class="widget-header">
                <h2><?= _('Logs by Severity') ?></h2>
            </div>
            <div class="widget-body chart-container">
                <canvas id="chart-severity"></canvas>
            </div>
        </div>

        <!-- Top Devices -->
        <div class="widget-panel">
            <div class="widget-header">
                <h2><?= _('Logs by Device') ?></h2>
            </div>
            <div class="widget-body chart-container">
                <canvas id="chart-devices"></canvas>
            </div>
        </div>

        <!-- Hourly Trend -->
        <div class="widget-panel">
            <div class="widget-header">
                <h2><?= _('Logs by Hour (Last 24 Hours)') ?></h2>
            </div>
            <div class="widget-body chart-container">
                <canvas id="chart-hourly"></canvas>
            </div>
        </div>

        <!-- Daily Trend -->
        <div class="widget-panel">
            <div class="widget-header">
                <h2><?= _('Logs by Day (Last 7 Days)') ?></h2>
            </div>
            <div class="widget-body chart-container">
                <canvas id="chart-daily"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // 1. Severity Chart
    new Chart(document.getElementById('chart-severity'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($severity_labels) ?>,
            datasets: [{
                data: <?= json_encode($severity_counts) ?>,
                backgroundColor: <?= json_encode($severity_bg) ?>,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: { color: '#ccc' }
                }
            }
        }
    });

    // 2. Devices Chart
    new Chart(document.getElementById('chart-devices'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($device_labels) ?>,
            datasets: [{
                label: 'Log Count',
                data:  <?= json_encode($device_counts) ?>,
                backgroundColor: '#00a3ff',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: { ticks: { color: '#ccc' } },
                y: { ticks: { color: '#ccc' } }
            }
        }
    });

    // 3. Hourly Chart
    new Chart(document.getElementById('chart-hourly'), {
        type: 'line',
        data: {
            labels: <?= json_encode($hourly_labels) ?>,
            datasets: [{
                label: 'Log Count',
                data: <?= json_encode($hourly_counts) ?>,
                borderColor: '#2baf2b',
                backgroundColor: 'rgba(43, 175, 43, 0.1)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: { ticks: { color: '#ccc' } },
                y: { ticks: { color: '#ccc' } }
            }
        }
    });

    // 4. Daily Chart
    new Chart(document.getElementById('chart-daily'), {
        type: 'line',
        data: {
            labels: <?= json_encode($daily_labels) ?>,
            datasets: [{
                label: 'Log Count',
                data: <?= json_encode($daily_counts) ?>,
                borderColor: '#ff7800',
                backgroundColor: 'rgba(255, 120, 0, 0.1)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: { ticks: { color: '#ccc' } },
                y: { ticks: { color: '#ccc' } }
            }
        }
    });
});
</script>

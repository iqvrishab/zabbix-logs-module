# Log Manager Module for Zabbix 7.4

A centralized syslog management solution embedded natively inside Zabbix, designed for MSP and NOC environments. Highly comparable to Graylog but built directly inside Zabbix for absolute correlation.

## Key Features

- **Dynamic Overview Dashboard**: Real-time stats (Total Logs Today, Critical Logs, Warnings, Active Devices, Rate of Logs per second), top active devices list, and recent alert match history.
- **Live Logs Streaming**: Dynamic 2-second auto-refresh live tail stream with infinite updates, Pause/Resume toggle, and custom colored severity tag rendering.
- **Advanced Full-Text Search**: Filter logs by Hostname, Source IP, Severity, Facility, Date Range, and Keyword string using full-text search index matching. Includes instant export to CSV or JSON format.
- **Zabbix & Config Manager integration**: Matches syslog hostname and IP address against Zabbix hosts & active network interfaces. Direct mapping indicators show if the sending device has a corresponding host defined inside Zabbix.
- **Regex Alert Rules**: Configurable real-time rule engine that monitors log content matching patterns like `Interface.*down`, `Authentication failed`, etc., and records alerts inside the local database.
- **Statistics Dashboard**: Rich Chart.js visualization of log distribution by severity levels, devices, hours, and daily trends.
- **Automated Retention**: Configurable log retention setting (7, 30, 90, 180, or 365 days) and automated python deletion daemon script.

## Directory Structure

```
modules/zabbix-log-manager/
├── manifest.json
├── Module.php
├── actions/
│   ├── ActionOverview.php
│   ├── ActionLiveLogs.php
│   ├── ActionSearch.php
│   ├── ActionDevices.php
│   ├── ActionAlerts.php
│   ├── ActionStatistics.php
│   └── ActionSettings.php
├── includes/
│   └── Repository.php
├── views/
│   ├── overview.php
│   ├── livelogs.php
│   ├── search.php
│   ├── devices.php
│   ├── alerts.php
│   ├── statistics.php
│   └── settings.php
├── assets/
│   ├── css/
│   │   └── logmanager.css
│   └── js/
│       └── logmanager.js
├── scripts/
│   ├── syslog_receiver.py
│   └── log_cleanup.py
└── sql/
    ├── schema.sql
    └── migration.sql
```

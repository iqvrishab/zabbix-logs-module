# Installation & Deployment Guide

Follow these steps to deploy and integrate the **Log Manager** module inside your Zabbix 7.4 environment.

## Phase 1: Database Setup
Execute the SQL schema migration against your Zabbix MySQL database instance:

```bash
# Copy schema.sql to your MySQL container or server and run:
mysql -u zabbix -p zabbix < sql/schema.sql
```

## Phase 2: Copying the Module
Copy the entire `zabbix-log-manager` directory to the Zabbix Web Frontend modules folder (usually located at `/usr/share/zabbix/modules/` or `/var/www/html/modules/` in Docker containers).

```bash
# Example Docker command:
docker cp modules/zabbix-log-manager <zabbix-web-container-id>:/usr/share/zabbix/modules/
```

After copying, log in to Zabbix as an Administrator, navigate to **Administration -> General -> Modules**, and click **Scan**. Find **Log Manager** and click **Enable**.

## Phase 3: Setup Python Daemon
The Python syslog receiver needs to run in the background (as a Docker service, systemd service, or background process).

### Install Dependencies
```bash
pip3 install --break-system-packages mysql-connector-python
```

### Start the Syslog Receiver Daemon
Make sure port `514` UDP/TCP is free and bindable on your server/container. Run the script:

```bash
# Make executable
chmod +x scripts/syslog_receiver.py

# Run in background
python3 scripts/syslog_receiver.py &
```

> [!NOTE]
> If running inside a Docker setup, you can mount this script inside your Zabbix container or run it as a separate container with DB host environment variables:
> `DB_HOST=mysql-server DB_USER=zabbix DB_PASSWORD=zabbix DB_DATABASE=zabbix python3 syslog_receiver.py`

## Phase 4: Configure Log Retention Cleanup
Add the `log_cleanup.py` script to your system cron to automatically purge expired logs:

```bash
# Edit cron entries
crontab -e

# Add a cron job to run every day at midnight:
0 0 * * * /usr/bin/python3 /usr/share/zabbix/modules/zabbix-log-manager/scripts/log_cleanup.py
```

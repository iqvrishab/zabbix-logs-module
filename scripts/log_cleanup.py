#!/usr/bin/env python3
import os
import sys
import re
import mysql.connector

# Default configurations
DB_HOST = os.environ.get('DB_HOST', 'localhost')
DB_PORT = int(os.environ.get('DB_PORT', 3306))
DB_USER = os.environ.get('DB_USER', 'zabbix')
DB_PASS = os.environ.get('DB_PASSWORD', 'zabbix')
DB_NAME = os.environ.get('DB_DATABASE', 'zabbix')

# Try reading Zabbix config
ZABBIX_CONF_PATHS = [
    '/etc/zabbix/web/zabbix.conf.php',
    '/usr/share/zabbix/conf/zabbix.conf.php',
    '/var/www/html/conf/zabbix.conf.php',
    'C:\\zabbix\\zabbix.conf.php'
]

for p in ZABBIX_CONF_PATHS:
    if os.path.exists(p):
        try:
            with open(p, 'r') as f:
                content = f.read()
                db_host_m = re.search(r'\$DB\[[\'"]SERVER[\'"]\]\s*=\s*[\'"](.*?)[\'"]', content)
                db_port_m = re.search(r'\$DB\[[\'"]PORT[\'"]\]\s*=\s*[\'"](.*?)[\'"]', content)
                db_user_m = re.search(r'\$DB\[[\'"]USER[\'"]\]\s*=\s*[\'"](.*?)[\'"]', content)
                db_pass_m = re.search(r'\$DB\[[\'"]PASSWORD[\'"]\]\s*=\s*[\'"](.*?)[\'"]', content)
                db_name_m = re.search(r'\$DB\[[\'"]DATABASE[\'"]\]\s*=\s*[\'"](.*?)[\'"]', content)
                
                if db_host_m: DB_HOST = db_host_m.group(1)
                if db_port_m and db_port_m.group(1): DB_PORT = int(db_port_m.group(1))
                if db_user_m: DB_USER = db_user_m.group(1)
                if db_pass_m: DB_PASS = db_pass_m.group(1)
                if db_name_m: DB_NAME = db_name_m.group(1)
                break
        except Exception as e:
            print(f"Error reading Zabbix config from {p}: {e}", file=sys.stderr)

def main():
    try:
        conn = mysql.connector.connect(
            host=DB_HOST,
            port=DB_PORT,
            user=DB_USER,
            password=DB_PASS,
            database=DB_NAME
        )
        cursor = conn.cursor()
        
        # Get retention days
        cursor.execute("SELECT retention_days FROM log_retention LIMIT 1")
        row = cursor.fetchone()
        retention_days = row[0] if row else 30
        
        print(f"Starting log cleanup. Retention period is set to {retention_days} days.")
        
        # Perform deletion
        query = """
            DELETE FROM network_logs 
            WHERE received_at < DATE_SUB(NOW(), INTERVAL %s DAY)
        """
        cursor.execute(query, (retention_days,))
        deleted_count = cursor.rowcount
        conn.commit()
        
        print(f"Successfully cleaned up {deleted_count} logs older than {retention_days} days.")
        
        cursor.close()
        conn.close()
    except Exception as e:
        print(f"Error executing log cleanup: {e}", file=sys.stderr)
        sys.exit(1)

if __name__ == '__main__':
    main()

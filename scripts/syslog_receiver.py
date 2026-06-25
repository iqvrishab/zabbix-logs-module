#!/usr/bin/env python3
import os
import sys
import re
import socket
import threading
import json
from datetime import datetime
import mysql.connector
from mysql.connector import pooling

# Default configurations
DB_HOST = os.environ.get('DB_HOST', 'localhost')
DB_PORT = int(os.environ.get('DB_PORT', 3306))
DB_USER = os.environ.get('DB_USER', 'zabbix')
DB_PASS = os.environ.get('DB_PASSWORD', 'zabbix')
DB_NAME = os.environ.get('DB_DATABASE', 'zabbix')

# Try reading Zabbix frontend config file if exists
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
                # Simple extraction of PHP variables
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

# DB connection pool
try:
    db_pool = pooling.MySQLConnectionPool(
        pool_name="syslog_pool",
        pool_size=10,
        host=DB_HOST,
        port=DB_PORT,
        user=DB_USER,
        password=DB_PASS,
        database=DB_NAME,
        autocommit=True
    )
except Exception as e:
    print(f"CRITICAL: Failed to create database pool connecting to {DB_HOST}: {e}", file=sys.stderr)
    sys.exit(1)

# Regex patterns for syslog parsing
# RFC3164: <PRI>timestamp hostname message
RFC3164_PATTERN = re.compile(
    r'^<(?P<pri>\d+)>(?P<timestamp>[A-Z][a-z]{2}\s+\d+\s+\d{2}:\d{2}:\d{2})\s+(?P<hostname>\S+)\s+(?P<message>.*)$'
)

# RFC5424: <PRI>VERSION TIMESTAMP HOSTNAME APP-NAME PROCID MSGID STRUCTURED-DATA MSG
RFC5424_PATTERN = re.compile(
    r'^<(?P<pri>\d+)>\d\s+(?P<timestamp>\S+)\s+(?P<hostname>\S+)\s+(?P<appname>\S+)\s+(?P<procid>\S+)\s+(?P<msgid>\S+)\s+(?P<msg>.*)$'
)

# Rules cache
rules_cache = []
rules_lock = threading.Lock()

def load_rules():
    global rules_cache
    try:
        conn = db_pool.get_connection()
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT rule_id, name, regex_pattern, severity, enabled FROM log_alert_rules WHERE enabled = 1")
        with rules_lock:
            rules_cache = cursor.fetchall()
        cursor.close()
        conn.close()
    except Exception as e:
        print(f"Error loading alert rules: {e}", file=sys.stderr)

# Periodically reload rules (every 60 seconds)
def rules_loader_loop():
    while True:
        load_rules()
        threading.Event().wait(60.0)

# Resolve vendor from message or hostname
def detect_vendor(message, hostname):
    msg_lower = message.lower()
    host_lower = hostname.lower()
    
    if "fortigate" in msg_lower or "devname=fg" in msg_lower:
        return "Fortigate"
    elif "cisco" in msg_lower or "ios" in host_lower:
        return "Cisco"
    elif "mikrotik" in msg_lower or "routeros" in msg_lower:
        return "Mikrotik"
    elif "juniper" in msg_lower:
        return "Juniper"
    elif "aruba" in msg_lower:
        return "Aruba"
    elif "linux" in msg_lower:
        return "Linux"
    elif "windows" in msg_lower or "microsoft" in msg_lower:
        return "Windows"
    return "Unknown"

# Get or create source ID
def get_source_id(conn, hostname, ip_address, message):
    cursor = conn.cursor()
    # Check if exists
    cursor.execute(
        "SELECT source_id, enabled FROM log_sources WHERE ip_address = %s AND hostname = %s",
        (ip_address, hostname)
    )
    res = cursor.fetchone()
    if res:
        source_id, enabled = res
        if enabled:
            # Update last seen
            cursor.execute(
                "UPDATE log_sources SET last_seen = NOW() WHERE source_id = %s",
                (source_id,)
            )
        cursor.close()
        return source_id if enabled else None
    
    # Insert new source
    vendor = detect_vendor(message, hostname)
    try:
        cursor.execute(
            "INSERT INTO log_sources (hostname, ip_address, vendor, first_seen, last_seen, enabled) VALUES (%s, %s, %s, NOW(), NOW(), 1)",
            (hostname, ip_address, vendor)
        )
        source_id = cursor.lastrowid
        cursor.close()
        return source_id
    except mysql.connector.Error as err:
        # Handle concurrent inserts gracefully
        cursor.execute(
            "SELECT source_id FROM log_sources WHERE ip_address = %s AND hostname = %s",
            (ip_address, hostname)
        )
        res = cursor.fetchone()
        cursor.close()
        if res:
            return res[0]
        return None

# Match alert rules and insert into history
def check_alerts(conn, log_id, message):
    with rules_lock:
        rules = list(rules_cache)
        
    cursor = conn.cursor()
    for rule in rules:
        try:
            if re.search(rule['regex_pattern'], message, re.IGNORECASE):
                # Match found! Insert log_alert_history
                cursor.execute(
                    "INSERT INTO log_alert_history (rule_id, log_id, matched_at) VALUES (%s, %s, NOW())",
                    (rule['rule_id'], log_id)
                )
        except Exception as e:
            print(f"Error processing regex rule {rule['rule_id']}: {e}", file=sys.stderr)
    cursor.close()

# Parse log line
def parse_syslog(data_str, source_ip):
    data_str = data_str.strip()
    
    # Defaults
    pri = 13  # facility user, severity notice
    timestamp = datetime.now()
    hostname = source_ip
    message = data_str
    
    # Try RFC5424
    m5424 = RFC5424_PATTERN.match(data_str)
    if m5424:
        gd = m5424.groupdict()
        pri = int(gd['pri'])
        hostname = gd['hostname']
        # Try parsing timestamp
        try:
            # Strip timezone offset colon for older python compatibility
            ts_str = gd['timestamp']
            if ts_str.endswith('Z'):
                ts_str = ts_str[:-1] + '+00:00'
            timestamp = datetime.fromisoformat(ts_str)
        except Exception:
            timestamp = datetime.now()
        # Message in RFC5424 contains msg
        message = gd['msg'].strip()
        # Structured data or extra prefix cleanup
        if message.startswith('- '):
            message = message[2:]
    else:
        # Try RFC3164
        m3164 = RFC3164_PATTERN.match(data_str)
        if m3164:
            gd = m3164.groupdict()
            pri = int(gd['pri'])
            hostname = gd['hostname']
            # Parse month day time
            try:
                current_year = datetime.now().year
                ts_str = f"{gd['timestamp']} {current_year}"
                timestamp = datetime.strptime(ts_str, "%b %d %H:%M:%S %Y")
            except Exception:
                timestamp = datetime.now()
            message = gd['message']
            
    severity = pri & 7
    facility = pri >> 3
    
    return {
        'severity': severity,
        'facility': facility,
        'hostname': hostname,
        'timestamp': timestamp,
        'message': message,
        'raw_message': data_str
    }

# Process received message
def process_message(data_str, source_ip):
    try:
        parsed = parse_syslog(data_str, source_ip)
        
        conn = db_pool.get_connection()
        source_id = get_source_id(conn, parsed['hostname'], source_ip, parsed['message'])
        
        if source_id is None:
            # Source disabled or ignored
            conn.close()
            return
            
        cursor = conn.cursor()
        cursor.execute(
            "INSERT INTO network_logs (source_id, received_at, severity, facility, hostname, source_ip, message, raw_message) VALUES (%s, %s, %s, %s, %s, %s, %s, %s)",
            (
                source_id,
                parsed['timestamp'].strftime('%Y-%m-%d %H:%M:%S.%f')[:-3],
                parsed['severity'],
                parsed['facility'],
                parsed['hostname'],
                source_ip,
                parsed['message'],
                parsed['raw_message']
            )
        )
        log_id = cursor.lastrowid
        conn.commit()
        cursor.close()
        
        # Check alert matches
        check_alerts(conn, log_id, parsed['message'])
        conn.close()
    except Exception as e:
        print(f"Error processing syslog message from {source_ip}: {e}", file=sys.stderr)

# UDP Listener
def udp_listener():
    sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    try:
        sock.bind(('0.0.0.0', 514))
    except Exception as e:
        print(f"CRITICAL: Failed to bind UDP port 514: {e}", file=sys.stderr)
        return
        
    print("UDP Syslog receiver listening on port 514...")
    while True:
        try:
            data, addr = sock.recvfrom(65535)
            data_str = data.decode('utf-8', errors='ignore')
            threading.Thread(target=process_message, args=(data_str, addr[0]), daemon=True).start()
        except Exception as e:
            print(f"UDP socket error: {e}", file=sys.stderr)

# TCP Client Handler
def handle_tcp_client(client_sock, client_addr):
    client_sock.settimeout(10.0)
    buf = ""
    while True:
        try:
            data = client_sock.recv(4096)
            if not data:
                break
            buf += data.decode('utf-8', errors='ignore')
            while "\n" in buf:
                line, buf = buf.split("\n", 1)
                if line.strip():
                    process_message(line, client_addr[0])
        except socket.timeout:
            break
        except Exception as e:
            print(f"Error handling TCP client {client_addr}: {e}", file=sys.stderr)
            break
    client_sock.close()

# TCP Listener
def tcp_listener():
    sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    sock.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
    try:
        sock.bind(('0.0.0.0', 514))
        sock.listen(100)
    except Exception as e:
        print(f"CRITICAL: Failed to bind TCP port 514: {e}", file=sys.stderr)
        return
        
    print("TCP Syslog receiver listening on port 514...")
    while True:
        try:
            client_sock, client_addr = sock.accept()
            threading.Thread(target=handle_tcp_client, args=(client_sock, client_addr), daemon=True).start()
        except Exception as e:
            print(f"TCP accept error: {e}", file=sys.stderr)

def main():
    load_rules()
    
    # Start rule updater thread
    threading.Thread(target=rules_loader_loop, daemon=True).start()
    
    # Start listeners
    udp_t = threading.Thread(target=udp_listener, daemon=True)
    tcp_t = threading.Thread(target=tcp_listener, daemon=True)
    
    udp_t.start()
    tcp_t.start()
    
    # Block main thread
    udp_t.join()
    tcp_t.join()

if __name__ == '__main__':
    main()

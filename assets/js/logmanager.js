/**
 * Log Manager Frontend JavaScript Logic
 */

let liveInterval = null;
let isPaused = false;
let deviceIdFilter = "";

const severityClasses = {
    0: 'sev-emergency',
    1: 'sev-alert',
    2: 'sev-critical',
    3: 'sev-error',
    4: 'sev-warning',
    5: 'sev-notice',
    6: 'sev-info',
    7: 'sev-debug'
};

const severityNames = {
    0: 'Emergency',
    1: 'Alert',
    2: 'Critical',
    3: 'Error',
    4: 'Warning',
    5: 'Notice',
    6: 'Informational',
    7: 'Debug'
};

const facilityNames = {
    0: 'kern', 1: 'user', 2: 'mail', 3: 'daemon', 4: 'auth', 5: 'syslog',
    6: 'lpr', 7: 'news', 8: 'uucp', 9: 'cron', 10: 'authpriv', 11: 'ftp',
    12: 'ntp', 13: 'security/audit', 14: 'console', 15: 'clock', 16: 'local0',
    17: 'local1', 18: 'local2', 19: 'local3', 20: 'local4', 21: 'local5',
    22: 'local6', 23: 'local7'
};

/**
 * Initialize Live Logs tail streaming
 */
function initLiveLogs(deviceId = "") {
    deviceIdFilter = deviceId;
    
    // Bind controls
    const btnPauseResume = document.getElementById("btn-pause-resume");
    const btnClear = document.getElementById("btn-clear-logs");
    const statusIndicator = document.getElementById("stream-status");

    if (btnPauseResume) {
        btnPauseResume.addEventListener("click", function() {
            isPaused = !isPaused;
            if (isPaused) {
                btnPauseResume.innerText = "Resume";
                statusIndicator.innerText = "Paused";
                statusIndicator.classList.remove("online");
                statusIndicator.classList.add("offline");
            } else {
                btnPauseResume.innerText = "Pause";
                statusIndicator.innerText = "Streaming";
                statusIndicator.classList.remove("offline");
                statusIndicator.classList.add("online");
                fetchLogs(); // Trigger immediate update
            }
        });
    }

    if (btnClear) {
        btnClear.addEventListener("click", function() {
            const tbody = document.getElementById("live-logs-tbody");
            tbody.innerHTML = `
                <tr id="no-logs-row">
                    <td colspan="6" class="text-center no-data">Cleared. Waiting for new logs...</td>
                </tr>
            `;
        });
    }

    // Initial fetch
    fetchLogs();
    
    // Set 2 seconds interval
    liveInterval = setInterval(fetchLogs, 2000);
}

/**
 * Fetch logs using AJAX
 */
function fetchLogs() {
    if (isPaused) return;

    let url = "zabbix.php?action=logmanager.livelogs&ajax=1";
    if (deviceIdFilter) {
        url += "&device_id=" + encodeURIComponent(deviceIdFilter);
    }

    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.logs) {
                updateLogsTable(data.logs);
            }
        })
        .catch(err => console.error("Error fetching live logs:", err));
}

/**
 * Prepend or update records in live logs table
 */
function updateLogsTable(logs) {
    const tbody = document.getElementById("live-logs-tbody");
    if (!tbody) return;

    // If no logs, skip
    if (logs.length === 0) return;

    // Remove empty row if exists
    const noLogsRow = document.getElementById("no-logs-row");
    if (noLogsRow) {
        noLogsRow.remove();
    }

    // Build new rows
    let newRowsHtml = "";
    logs.forEach(log => {
        const sevClass = severityClasses[log.severity] || "sev-debug";
        const sevName = severityNames[log.severity] || ("Severity " + log.severity);
        const facName = facilityNames[log.facility] || ("Facility " + log.facility);
        
        newRowsHtml += `
            <tr data-log-id="${log.log_id}">
                <td>${escapeHtml(log.received_at)}</td>
                <td><strong>${escapeHtml(log.hostname)}</strong></td>
                <td>${escapeHtml(log.source_ip)}</td>
                <td>
                    <span class="tag ${sevClass}">
                        ${log.severity} - ${escapeHtml(sevName)}
                    </span>
                </td>
                <td>${log.facility} - ${escapeHtml(facName)}</td>
                <td class="log-message-cell">${escapeHtml(log.message)}</td>
            </tr>
        `;
    });

    // Replace table content to avoid duplicating and showing old records indefinitely (max 100 entries)
    tbody.innerHTML = newRowsHtml;
}

/**
 * Simple HTML Escaper
 */
function escapeHtml(str) {
    if (!str) return '';
    return str
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

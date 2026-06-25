<?php
declare(strict_types = 1);

// ─── helpers ────────────────────────────────────────────────────────────────
$h   = static fn($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$url = static fn(array $p = []): string =>
    'zabbix.php?' . http_build_query(array_merge(['action' => 'logmanager.view'], $p));

$tab = $data['tab'] ?? 'overview';

// ─── lookups ────────────────────────────────────────────────────────────────
$sevLabel = [
    0 => 'Emergency', 1 => 'Alert',    2 => 'Critical', 3 => 'Error',
    4 => 'Warning',   5 => 'Notice',   6 => 'Info',     7 => 'Debug',
];
$sevColor = [
    0 => '#dc2626', 1 => '#ea580c', 2 => '#d97706', 3 => '#ca8a04',
    4 => '#65a30d', 5 => '#0891b2', 6 => '#2563eb', 7 => '#7c3aed',
];
$facLabel = [
    0=>'kern',1=>'user',2=>'mail',3=>'daemon',4=>'auth',5=>'syslog',
    6=>'lpr',7=>'news',8=>'uucp',9=>'cron',10=>'authpriv',11=>'ftp',
    12=>'ntp',13=>'audit',14=>'console',15=>'clock',
    16=>'local0',17=>'local1',18=>'local2',19=>'local3',
    20=>'local4',21=>'local5',22=>'local6',23=>'local7',
];
$tabs = [
    'overview'   => ['icon' => '📊', 'label' => 'Overview'],
    'livelogs'   => ['icon' => '📡', 'label' => 'Live Logs'],
    'search'     => ['icon' => '🔍', 'label' => 'Search'],
    'devices'    => ['icon' => '🖥️',  'label' => 'Devices'],
    'alerts'     => ['icon' => '🚨', 'label' => 'Alerts'],
    'statistics' => ['icon' => '📈', 'label' => 'Statistics'],
    'settings'   => ['icon' => '⚙️',  'label' => 'Settings'],
];
$filters         = $data['filters']        ?? [];
$retentionOptions = [7=>'7 Days',30=>'30 Days',90=>'90 Days',180=>'180 Days',365=>'365 Days'];
?>
<div class="lm-app">
<style>
/* ════════════════════════════════════════════════════════════════════
   Log Manager — Embedded Stylesheet
   ════════════════════════════════════════════════════════════════════ */
:root{
    --lm-bg:#0f1117;--lm-surface:#1a1d26;--lm-surface2:#21253a;
    --lm-border:#2a2f45;--lm-accent:#3b82f6;--lm-accent2:#6366f1;
    --lm-text:#e2e8f0;--lm-muted:#94a3b8;--lm-danger:#ef4444;
    --lm-warn:#f59e0b;--lm-ok:#22c55e;--lm-radius:8px;
    --lm-font:'Inter',system-ui,sans-serif;
}
.lm-app *{box-sizing:border-box;font-family:var(--lm-font);}
.lm-app{background:var(--lm-bg);color:var(--lm-text);min-height:100vh;padding:16px;}

/* ── Nav ── */
.lm-nav{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:20px;
    background:var(--lm-surface);padding:8px;border-radius:10px;
    border:1px solid var(--lm-border);}
.lm-nav a{display:flex;align-items:center;gap:6px;padding:8px 14px;
    border-radius:6px;text-decoration:none;color:var(--lm-muted);
    font-size:13px;font-weight:500;transition:all .15s;}
.lm-nav a:hover{background:var(--lm-surface2);color:var(--lm-text);}
.lm-nav a.active{background:linear-gradient(135deg,var(--lm-accent),var(--lm-accent2));
    color:#fff;box-shadow:0 2px 12px rgba(99,102,241,.35);}
.lm-nav-icon{font-size:15px;}

/* ── Alerts/Messages ── */
.lm-msg{padding:12px 16px;border-radius:6px;margin-bottom:12px;font-size:13px;font-weight:500;}
.lm-msg.success{background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.3);color:#86efac;}
.lm-msg.error{background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.3);color:#fca5a5;}

/* ── KPI Cards ── */
.lm-kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:20px;}
.lm-kpi{background:var(--lm-surface);border:1px solid var(--lm-border);border-radius:var(--lm-radius);
    padding:16px;position:relative;overflow:hidden;}
.lm-kpi::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;}
.lm-kpi.blue::before{background:linear-gradient(90deg,#3b82f6,#60a5fa);}
.lm-kpi.red::before{background:linear-gradient(90deg,#ef4444,#f87171);}
.lm-kpi.amber::before{background:linear-gradient(90deg,#f59e0b,#fbbf24);}
.lm-kpi.green::before{background:linear-gradient(90deg,#22c55e,#4ade80);}
.lm-kpi.purple::before{background:linear-gradient(90deg,#8b5cf6,#a78bfa);}
.lm-kpi.teal::before{background:linear-gradient(90deg,#14b8a6,#2dd4bf);}
.lm-kpi-icon{font-size:22px;margin-bottom:6px;}
.lm-kpi-label{font-size:11px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:var(--lm-muted);margin-bottom:4px;}
.lm-kpi-value{font-size:26px;font-weight:700;color:var(--lm-text);line-height:1;}

/* ── 2-col grid ── */
.lm-grid2{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;}
@media(max-width:900px){.lm-grid2{grid-template-columns:1fr;}}

/* ── Panel ── */
.lm-panel{background:var(--lm-surface);border:1px solid var(--lm-border);border-radius:var(--lm-radius);}
.lm-panel-head{display:flex;align-items:center;justify-content:space-between;
    padding:14px 18px;border-bottom:1px solid var(--lm-border);}
.lm-panel-head h2{margin:0;font-size:14px;font-weight:600;color:var(--lm-text);}
.lm-panel-body{padding:16px;}
.lm-panel-body.no-pad{padding:0;}

/* ── Table ── */
.lm-table{width:100%;border-collapse:collapse;font-size:13px;}
.lm-table thead th{padding:10px 14px;text-align:left;color:var(--lm-muted);
    font-size:11px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;
    border-bottom:1px solid var(--lm-border);background:var(--lm-surface2);}
.lm-table tbody tr{border-bottom:1px solid rgba(255,255,255,.04);transition:background .1s;}
.lm-table tbody tr:hover{background:rgba(255,255,255,.03);}
.lm-table tbody td{padding:10px 14px;vertical-align:middle;color:var(--lm-text);}
.lm-table .no-data td{color:var(--lm-muted);font-style:italic;text-align:center;padding:24px;}

/* ── Tags / Badges ── */
.lm-tag{display:inline-block;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:600;color:#fff;white-space:nowrap;}
.sev-0{background:#dc2626;} .sev-1{background:#ea580c;} .sev-2{background:#d97706;}
.sev-3{background:#ca8a04;} .sev-4{background:#65a30d;} .sev-5{background:#0891b2;}
.sev-6{background:#2563eb;} .sev-7{background:#7c3aed;}
.lm-dot{display:inline-block;width:8px;height:8px;border-radius:50%;margin-right:5px;}
.lm-dot.on{background:var(--lm-ok);}
.lm-dot.off{background:var(--lm-danger);}

/* ── Buttons ── */
.lm-btn{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:5px;
    font-size:13px;font-weight:500;cursor:pointer;text-decoration:none;border:1px solid transparent;transition:all .15s;}
.lm-btn-primary{background:linear-gradient(135deg,var(--lm-accent),var(--lm-accent2));color:#fff;border:none;box-shadow:0 2px 8px rgba(99,102,241,.3);}
.lm-btn-primary:hover{opacity:.88;}
.lm-btn-ghost{background:var(--lm-surface2);color:var(--lm-muted);border-color:var(--lm-border);}
.lm-btn-ghost:hover{color:var(--lm-text);background:rgba(255,255,255,.06);}
.lm-btn-danger{background:rgba(239,68,68,.12);color:#f87171;border-color:rgba(239,68,68,.25);}
.lm-btn-danger:hover{background:rgba(239,68,68,.22);}
.lm-btn-sm{padding:4px 10px;font-size:11px;}

/* ── Form ── */
.lm-form-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:12px;}
.lm-fg{display:flex;flex-direction:column;gap:4px;}
.lm-fg label{font-size:11px;font-weight:600;color:var(--lm-muted);letter-spacing:.05em;text-transform:uppercase;}
.lm-fg input,.lm-fg select,.lm-fg textarea{
    background:var(--lm-surface2);border:1px solid var(--lm-border);
    border-radius:5px;padding:8px 10px;color:var(--lm-text);font-size:13px;outline:none;
    transition:border .15s;}
.lm-fg input:focus,.lm-fg select:focus,.lm-fg textarea:focus{border-color:var(--lm-accent);}
.lm-fg input::placeholder{color:var(--lm-muted);}
.lm-fg option{background:var(--lm-surface);}
.lm-form-actions{display:flex;gap:8px;margin-top:14px;flex-wrap:wrap;align-items:center;}
.lm-help{font-size:11px;color:var(--lm-muted);margin-top:2px;}

/* ── Chart container ── */
.lm-chart-box{height:220px;position:relative;padding:4px 0;}

/* ── Live Log table ── */
.lm-scroll{max-height:520px;overflow-y:auto;}
.lm-scroll::-webkit-scrollbar{width:4px;}
.lm-scroll::-webkit-scrollbar-track{background:var(--lm-surface);}
.lm-scroll::-webkit-scrollbar-thumb{background:var(--lm-border);border-radius:2px;}
.lm-log-msg{font-family:'Courier New',monospace;font-size:12px;word-break:break-all;}
.lm-stream-ctrl{display:flex;align-items:center;gap:10px;}
.lm-status-pill{padding:4px 10px;border-radius:999px;font-size:11px;font-weight:600;}
.lm-status-pill.live{background:rgba(34,197,94,.15);color:#4ade80;}
.lm-status-pill.paused{background:rgba(239,68,68,.15);color:#f87171;}

/* ── Alert rule form slide ── */
.lm-rule-form{background:var(--lm-surface2);border:1px solid var(--lm-border);
    border-radius:var(--lm-radius);padding:16px;margin-bottom:16px;}
.lm-rule-form h3{margin:0 0 14px;font-size:14px;font-weight:600;}

/* ── Settings info table ── */
.lm-info-table{width:100%;border-collapse:collapse;font-size:13px;}
.lm-info-table td{padding:9px 12px;border-bottom:1px solid var(--lm-border);}
.lm-info-table td:first-child{color:var(--lm-muted);font-weight:600;width:35%;}
.lm-info-table code{background:var(--lm-surface2);padding:2px 6px;border-radius:3px;font-size:12px;}

/* ── Pagination ── */
.lm-pager{display:flex;justify-content:center;gap:4px;padding:14px;}
.lm-pager a{padding:5px 11px;border-radius:5px;font-size:12px;}
.lm-pager a.active{background:var(--lm-accent);color:#fff;}

/* ── Alert history snippet ── */
.lm-ah-host{font-size:11px;color:var(--lm-muted);font-weight:600;}
.lm-ah-msg{font-size:12px;font-family:monospace;word-break:break-all;}

/* ── Vendor chip ── */
.lm-vendor{display:inline-block;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:600;}
.lm-vendor{background:#334155;color:#94a3b8;}

/* ── Note box ── */
.lm-note{background:rgba(59,130,246,.08);border:1px dashed rgba(59,130,246,.25);
    border-radius:6px;padding:12px;font-size:12px;color:var(--lm-muted);margin-top:12px;}

/* ── Checkbox inline ── */
.lm-check{display:flex;align-items:center;gap:8px;font-size:13px;}
.lm-check input[type=checkbox]{width:16px;height:16px;accent-color:var(--lm-accent);}
</style>

<!-- ── Navigation ───────────────────────────────────────────────────────── -->
<nav class="lm-nav">
    <?php foreach ($tabs as $key => $t): ?>
        <a href="<?= $url(['tab' => $key]) ?>" class="<?= $tab === $key ? 'active' : '' ?>">
            <span class="lm-nav-icon"><?= $t['icon'] ?></span>
            <?= $h($t['label']) ?>
        </a>
    <?php endforeach ?>
</nav>

<!-- ── Flash messages ───────────────────────────────────────────────────── -->
<?php foreach ($data['messages'] as $msg): ?>
    <div class="lm-msg <?= $h($msg['type']) ?>">
        <?= $msg['type'] === 'error' ? '⚠️' : '✅' ?> <?= $h($msg['text']) ?>
    </div>
<?php endforeach ?>

<?php /* ═══════════════════════════════════ OVERVIEW ═══════════════════════════════════ */ ?>
<?php if ($tab === 'overview'): ?>

<div class="lm-kpi-grid">
    <?php
    $stats = $data['stats'];
    $crit  = $stats['severities'][0] + $stats['severities'][1]
           + $stats['severities'][2] + $stats['severities'][3];
    $warn  = $stats['severities'][4];
    $info  = $stats['severities'][5] + $stats['severities'][6];
    $kpis  = [
        ['blue',   '📋', 'Total Logs Today', number_format($stats['total_today'])],
        ['red',    '🔴', 'Critical / Error',  number_format($crit)],
        ['amber',  '⚠️',  'Warning',           number_format($warn)],
        ['green',  '💬', 'Info / Notice',     number_format($info)],
        ['purple', '🖥️',  'Active Devices',    number_format($stats['devices_count'])],
        ['teal',   '⚡', 'Logs / Second',     $stats['logs_per_second']],
    ];
    foreach ($kpis as [$cls, $icon, $label, $val]): ?>
    <div class="lm-kpi <?= $cls ?>">
        <div class="lm-kpi-icon"><?= $icon ?></div>
        <div class="lm-kpi-label"><?= $h($label) ?></div>
        <div class="lm-kpi-value"><?= $h((string)$val) ?></div>
    </div>
    <?php endforeach ?>
</div>

<div class="lm-grid2">
    <!-- Top Devices -->
    <div class="lm-panel">
        <div class="lm-panel-head"><h2>🏆 Top Devices Today</h2></div>
        <div class="lm-panel-body no-pad">
            <table class="lm-table">
                <thead><tr>
                    <th>Hostname</th><th>IP Address</th><th>Log Count</th>
                </tr></thead>
                <tbody>
                <?php if (empty($data['top_devices'])): ?>
                    <tr class="no-data"><td colspan="3">No logs received today.</td></tr>
                <?php else: ?>
                    <?php foreach ($data['top_devices'] as $d): ?>
                    <tr>
                        <td><strong><?= $h($d['hostname']) ?></strong></td>
                        <td><code><?= $h($d['source_ip']) ?></code></td>
                        <td><?= number_format($d['cnt']) ?></td>
                    </tr>
                    <?php endforeach ?>
                <?php endif ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Alerts -->
    <div class="lm-panel">
        <div class="lm-panel-head"><h2>🚨 Recent Alerts</h2></div>
        <div class="lm-panel-body no-pad">
            <table class="lm-table">
                <thead><tr>
                    <th>Rule</th><th>Severity</th><th>Host</th><th>Time</th>
                </tr></thead>
                <tbody>
                <?php if (empty($data['recent_alerts'])): ?>
                    <tr class="no-data"><td colspan="4">No recent alert matches.</td></tr>
                <?php else: ?>
                    <?php foreach ($data['recent_alerts'] as $a): ?>
                    <tr>
                        <td><?= $h($a['rule_name']) ?></td>
                        <td>
                            <span class="lm-tag sev-<?= (int)$a['rule_severity'] ?>">
                                <?= $h($sevLabel[$a['rule_severity']] ?? 'Unknown') ?>
                            </span>
                        </td>
                        <td><?= $h($a['hostname']) ?></td>
                        <td style="font-size:11px;color:var(--lm-muted)"><?= $h($a['matched_at']) ?></td>
                    </tr>
                    <?php endforeach ?>
                <?php endif ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php /* ═══════════════════════════════════ LIVE LOGS ═══════════════════════════════════ */ ?>
<?php elseif ($tab === 'livelogs'): ?>

<div class="lm-panel">
    <div class="lm-panel-head">
        <h2>📡 Live Log Tail</h2>
        <div class="lm-stream-ctrl">
            <button id="btn-pause" class="lm-btn lm-btn-ghost lm-btn-sm">⏸ Pause</button>
            <button id="btn-clear" class="lm-btn lm-btn-ghost lm-btn-sm">🗑 Clear</button>
            <span class="lm-status-pill live" id="stream-badge">● Live</span>
        </div>
    </div>
    <div class="lm-panel-body no-pad">
        <div class="lm-scroll">
            <table class="lm-table" id="live-tbl">
                <thead><tr>
                    <th style="width:14%">Time</th>
                    <th style="width:13%">Hostname</th>
                    <th style="width:11%">Source IP</th>
                    <th style="width:10%">Severity</th>
                    <th style="width:9%">Facility</th>
                    <th>Message</th>
                </tr></thead>
                <tbody id="live-tbody">
                    <tr class="no-data"><td colspan="6">Waiting for incoming logs…</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
(function(){
const sevLabel = <?= json_encode($sevLabel) ?>;
const facLabel = <?= json_encode($facLabel) ?>;
const tbody  = document.getElementById('live-tbody');
const badge  = document.getElementById('stream-badge');
let paused   = false;

function esc(s){ const d=document.createElement('div'); d.textContent=s||''; return d.innerHTML; }

function sevClass(s){ const m=['sev-0','sev-1','sev-2','sev-3','sev-4','sev-5','sev-6','sev-7']; return m[s]||'sev-7'; }

function render(logs){
    if(!logs.length) return;
    let html = '';
    logs.forEach(l=>{
        html += `<tr>
            <td style="font-size:11px;color:var(--lm-muted)">${esc(l.received_at)}</td>
            <td><strong>${esc(l.hostname)}</strong></td>
            <td><code style="font-size:11px">${esc(l.source_ip)}</code></td>
            <td><span class="lm-tag ${sevClass(l.severity)}">${esc(sevLabel[l.severity]||l.severity)}</span></td>
            <td style="font-size:11px;color:var(--lm-muted)">${esc(facLabel[l.facility]||l.facility)}</td>
            <td class="lm-log-msg">${esc(l.message)}</td>
        </tr>`;
    });
    tbody.innerHTML = html;
}

function fetch_logs(){
    if(paused) return;
    fetch('<?= $url(['ajax' => 1]) ?>')
        .then(r=>r.json()).then(d=>{ if(d.success) render(d.logs); })
        .catch(()=>{});
}

document.getElementById('btn-pause').addEventListener('click', function(){
    paused = !paused;
    this.textContent = paused ? '▶ Resume' : '⏸ Pause';
    badge.textContent = paused ? '⏸ Paused' : '● Live';
    badge.className = 'lm-status-pill ' + (paused ? 'paused' : 'live');
});
document.getElementById('btn-clear').addEventListener('click', ()=>{
    tbody.innerHTML = '<tr class="no-data"><td colspan="6">Cleared.</td></tr>';
});

fetch_logs();
setInterval(fetch_logs, 2000);
})();
</script>

<?php /* ═══════════════════════════════════ SEARCH ═══════════════════════════════════ */ ?>
<?php elseif ($tab === 'search'): ?>

<div class="lm-panel" style="margin-bottom:16px">
    <div class="lm-panel-head"><h2>🔍 Search & Filter</h2></div>
    <div class="lm-panel-body">
        <form action="zabbix.php" method="get">
            <input type="hidden" name="action" value="logmanager.view">
            <input type="hidden" name="tab"    value="search">
            <div class="lm-form-grid">
                <?php
                $sf = [
                    ['time_from','Time From','YYYY-MM-DD HH:MM:SS'],
                    ['time_to',  'Time To',  'YYYY-MM-DD HH:MM:SS'],
                    ['hostname', 'Hostname',  'e.g. Core-SW'],
                    ['source_ip','Source IP', 'e.g. 192.168.1.1'],
                ];
                foreach ($sf as [$name,$label,$ph]): ?>
                <div class="lm-fg">
                    <label><?= $label ?></label>
                    <input type="text" name="<?= $name ?>" value="<?= $h($filters[$name] ?? '') ?>" placeholder="<?= $ph ?>">
                </div>
                <?php endforeach ?>
                <div class="lm-fg">
                    <label>Severity</label>
                    <select name="severity">
                        <option value="">All Severities</option>
                        <?php foreach ($sevLabel as $v=>$lbl): ?>
                        <option value="<?= $v ?>" <?= ($filters['severity']??'') === (string)$v ? 'selected' : '' ?>>
                            <?= $v ?> — <?= $lbl ?>
                        </option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="lm-fg">
                    <label>Facility</label>
                    <select name="facility">
                        <option value="">All Facilities</option>
                        <?php foreach ($facLabel as $v=>$lbl): ?>
                        <option value="<?= $v ?>" <?= ($filters['facility']??'') === (string)$v ? 'selected' : '' ?>>
                            <?= $v ?> — <?= $lbl ?>
                        </option>
                        <?php endforeach ?>
                    </select>
                </div>
            </div>
            <div class="lm-fg" style="margin-top:10px">
                <label>Keyword / Full-Text Search</label>
                <input type="text" name="keyword" value="<?= $h($filters['keyword'] ?? '') ?>" placeholder="Search in log messages…">
            </div>
            <div class="lm-form-actions">
                <button type="submit" class="lm-btn lm-btn-primary">🔍 Search</button>
                <a href="<?= $url(['tab' => 'search']) ?>" class="lm-btn lm-btn-ghost">↺ Reset</a>
                <button type="submit" name="export" value="csv"  class="lm-btn lm-btn-ghost" style="margin-left:auto">⬇ CSV</button>
                <button type="submit" name="export" value="json" class="lm-btn lm-btn-ghost">⬇ JSON</button>
            </div>
        </form>
    </div>
</div>

<div class="lm-panel">
    <div class="lm-panel-head">
        <h2>Search Results</h2>
        <span style="font-size:12px;color:var(--lm-muted)"><?= number_format($data['total_count']) ?> records</span>
    </div>
    <div class="lm-panel-body no-pad">
        <div class="lm-scroll">
        <table class="lm-table">
            <thead><tr>
                <th style="width:14%">Time</th>
                <th style="width:13%">Hostname</th>
                <th style="width:11%">Source IP</th>
                <th style="width:10%">Severity</th>
                <th style="width:9%">Facility</th>
                <th>Message</th>
            </tr></thead>
            <tbody>
            <?php if (empty($data['logs'])): ?>
                <tr class="no-data"><td colspan="6">No logs matched your filters.</td></tr>
            <?php else: ?>
                <?php foreach ($data['logs'] as $log): ?>
                <tr>
                    <td style="font-size:11px;color:var(--lm-muted)"><?= $h($log['received_at']) ?></td>
                    <td><strong><?= $h($log['hostname']) ?></strong></td>
                    <td><code style="font-size:11px"><?= $h($log['source_ip']) ?></code></td>
                    <td>
                        <span class="lm-tag sev-<?= (int)$log['severity'] ?>">
                            <?= $h($sevLabel[$log['severity']] ?? (string)$log['severity']) ?>
                        </span>
                    </td>
                    <td style="font-size:11px;color:var(--lm-muted)">
                        <?= $h($facLabel[$log['facility']] ?? (string)$log['facility']) ?>
                    </td>
                    <td class="lm-log-msg"><?= $h($log['message']) ?></td>
                </tr>
                <?php endforeach ?>
            <?php endif ?>
            </tbody>
        </table>
        </div>
        <?php if ($data['total_count'] > $data['limit']): ?>
        <div class="lm-pager">
            <?php
            $total_pages = (int) ceil($data['total_count'] / $data['limit']);
            $cur         = $data['page'];
            $base        = array_merge($filters, ['action'=>'logmanager.view','tab'=>'search']);
            if ($cur > 1):
                $base['page'] = $cur - 1;
                echo '<a href="zabbix.php?' . http_build_query($base) . '" class="lm-btn lm-btn-ghost lm-btn-sm">‹</a>';
            endif;
            for ($p = max(1,$cur-2); $p <= min($total_pages,$cur+2); $p++):
                $base['page'] = $p;
                echo '<a href="zabbix.php?' . http_build_query($base) . '" class="lm-btn lm-btn-ghost lm-btn-sm' . ($p===$cur?' active':'') . '">' . $p . '</a>';
            endfor;
            if ($cur < $total_pages):
                $base['page'] = $cur + 1;
                echo '<a href="zabbix.php?' . http_build_query($base) . '" class="lm-btn lm-btn-ghost lm-btn-sm">›</a>';
            endif;
            ?>
        </div>
        <?php endif ?>
    </div>
</div>

<?php /* ═══════════════════════════════════ DEVICES ═══════════════════════════════════ */ ?>
<?php elseif ($tab === 'devices'): ?>

<div class="lm-panel">
    <div class="lm-panel-head"><h2>🖥️ Log Sources &amp; Device Mapping</h2></div>
    <div class="lm-panel-body no-pad">
        <table class="lm-table">
            <thead><tr>
                <th>Hostname</th><th>IP Address</th><th>Vendor</th>
                <th>First Seen</th><th>Last Seen</th><th>Zabbix Host</th>
                <th>Status</th><th>Actions</th>
            </tr></thead>
            <tbody>
            <?php if (empty($data['sources'])): ?>
                <tr class="no-data"><td colspan="8">No devices discovered yet. Check that the syslog daemon is running.</td></tr>
            <?php else: ?>
                <?php foreach ($data['sources'] as $s): ?>
                <tr>
                    <td><strong><?= $h($s['hostname']) ?></strong></td>
                    <td><code style="font-size:11px"><?= $h($s['ip_address']) ?></code></td>
                    <td><span class="lm-vendor"><?= $h($s['vendor']) ?></span></td>
                    <td style="font-size:11px;color:var(--lm-muted)"><?= $h($s['first_seen']) ?></td>
                    <td style="font-size:11px;color:var(--lm-muted)"><?= $h($s['last_seen']) ?></td>
                    <td>
                        <?php if (!empty($s['hostid'])): ?>
                            <a href="zabbix.php?action=host.edit&amp;hostid=<?= (int)$s['hostid'] ?>" style="color:var(--lm-accent);text-decoration:none;font-size:12px">
                                <span class="lm-dot on"></span><?= $h($s['zabbix_host_name']) ?>
                            </a>
                        <?php else: ?>
                            <span class="lm-dot off"></span><span style="color:var(--lm-muted);font-size:12px">Unmapped</span>
                        <?php endif ?>
                    </td>
                    <td>
                        <?php if ((int)$s['enabled'] === 1): ?>
                            <span style="color:var(--lm-ok);font-size:12px;font-weight:600">● Active</span>
                        <?php else: ?>
                            <span style="color:var(--lm-danger);font-size:12px;font-weight:600">● Disabled</span>
                        <?php endif ?>
                    </td>
                    <td style="display:flex;gap:4px;flex-wrap:wrap">
                        <a href="<?= $url(['tab'=>'livelogs','source_id'=>(int)$s['source_id']]) ?>" class="lm-btn lm-btn-ghost lm-btn-sm">Live</a>
                        <a href="<?= $url(['tab'=>'search','hostname'=>$s['hostname'],'source_ip'=>$s['ip_address']]) ?>" class="lm-btn lm-btn-ghost lm-btn-sm">Search</a>
                        <?php if ((int)$s['enabled'] === 1): ?>
                        <a href="<?= $url(['tab'=>'devices','task'=>'toggle_source','source_id'=>(int)$s['source_id'],'status'=>0]) ?>" class="lm-btn lm-btn-ghost lm-btn-sm">Disable</a>
                        <?php else: ?>
                        <a href="<?= $url(['tab'=>'devices','task'=>'toggle_source','source_id'=>(int)$s['source_id'],'status'=>1]) ?>" class="lm-btn lm-btn-ghost lm-btn-sm">Enable</a>
                        <?php endif ?>
                        <a href="<?= $url(['tab'=>'devices','task'=>'delete_source','source_id'=>(int)$s['source_id']]) ?>" class="lm-btn lm-btn-danger lm-btn-sm" onclick="return confirm('Delete this source and ALL its logs?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach ?>
            <?php endif ?>
            </tbody>
        </table>
    </div>
</div>

<?php /* ═══════════════════════════════════ ALERTS ═══════════════════════════════════ */ ?>
<?php elseif ($tab === 'alerts'): ?>

<div class="lm-grid2">
    <!-- Rules Panel -->
    <div class="lm-panel">
        <div class="lm-panel-head">
            <h2>🚨 Alert Rules</h2>
            <button class="lm-btn lm-btn-primary lm-btn-sm" onclick="document.getElementById('rule-form').style.display='block'">+ New Rule</button>
        </div>
        <div class="lm-panel-body">
            <!-- Create / Edit form -->
            <div id="rule-form" class="lm-rule-form" style="display:none">
                <h3 id="form-title">Create Alert Rule</h3>
                <form action="<?= $url(['tab'=>'alerts','task'=>'save_rule']) ?>" method="post">
                    <input type="hidden" id="fld-rule_id" name="rule_id" value="">
                    <div class="lm-form-grid" style="grid-template-columns:1fr">
                        <div class="lm-fg">
                            <label>Rule Name</label>
                            <input type="text" id="fld-name" name="name" placeholder="e.g. BGP Session Down" required>
                        </div>
                        <div class="lm-fg">
                            <label>Regex Pattern</label>
                            <input type="text" id="fld-regex" name="regex_pattern" placeholder="e.g. BGP.*neighbor.*lost" required>
                            <span class="lm-help">Case-insensitive regex matched against log messages.</span>
                        </div>
                        <div class="lm-fg">
                            <label>Severity</label>
                            <select id="fld-severity" name="severity">
                                <?php foreach ($sevLabel as $v=>$lbl): ?>
                                <option value="<?= $v ?>"><?= $v ?> — <?= $lbl ?></option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="lm-check">
                            <input type="checkbox" id="fld-enabled" name="enabled" value="1" checked>
                            <label for="fld-enabled">Enabled</label>
                        </div>
                    </div>
                    <div class="lm-form-actions">
                        <button type="submit" class="lm-btn lm-btn-primary">💾 Save Rule</button>
                        <button type="button" class="lm-btn lm-btn-ghost" onclick="document.getElementById('rule-form').style.display='none'">Cancel</button>
                    </div>
                </form>
            </div>

            <!-- Rules list -->
            <table class="lm-table">
                <thead><tr>
                    <th>Name</th><th>Pattern</th><th>Severity</th><th>Status</th><th>Actions</th>
                </tr></thead>
                <tbody>
                <?php if (empty($data['rules'])): ?>
                    <tr class="no-data"><td colspan="5">No alert rules defined.</td></tr>
                <?php else: ?>
                    <?php foreach ($data['rules'] as $r): ?>
                    <tr>
                        <td><strong><?= $h($r['name']) ?></strong></td>
                        <td><code style="font-size:11px"><?= $h($r['regex_pattern']) ?></code></td>
                        <td><span class="lm-tag sev-<?= (int)$r['severity'] ?>"><?= $h($sevLabel[$r['severity']] ?? '') ?></span></td>
                        <td>
                            <?php if ((int)$r['enabled']): ?>
                                <span style="color:var(--lm-ok);font-size:12px;font-weight:600">● On</span>
                            <?php else: ?>
                                <span style="color:var(--lm-danger);font-size:12px;font-weight:600">● Off</span>
                            <?php endif ?>
                        </td>
                        <td style="display:flex;gap:4px">
                            <button class="lm-btn lm-btn-ghost lm-btn-sm" onclick='editRule(<?= json_encode($r) ?>)'>Edit</button>
                            <a href="<?= $url(['tab'=>'alerts','task'=>'delete_rule','rule_id'=>(int)$r['rule_id']]) ?>" class="lm-btn lm-btn-danger lm-btn-sm" onclick="return confirm('Delete this rule?')">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach ?>
                <?php endif ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Alert History -->
    <div class="lm-panel">
        <div class="lm-panel-head"><h2>📜 Alert History</h2></div>
        <div class="lm-panel-body no-pad">
            <table class="lm-table">
                <thead><tr>
                    <th>Rule</th><th>Severity</th><th>Matched Log</th><th>Time</th>
                </tr></thead>
                <tbody>
                <?php if (empty($data['history'])): ?>
                    <tr class="no-data"><td colspan="4">No alert matches recorded yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($data['history'] as $a): ?>
                    <tr>
                        <td><strong><?= $h($a['rule_name']) ?></strong></td>
                        <td><span class="lm-tag sev-<?= (int)$a['rule_severity'] ?>"><?= $h($sevLabel[$a['rule_severity']] ?? '') ?></span></td>
                        <td>
                            <div class="lm-ah-host">[<?= $h($a['hostname']) ?> / <?= $h($a['source_ip']) ?>]</div>
                            <div class="lm-ah-msg"><?= $h(mb_strimwidth($a['message'], 0, 120, '…')) ?></div>
                        </td>
                        <td style="font-size:11px;color:var(--lm-muted);white-space:nowrap"><?= $h($a['matched_at']) ?></td>
                    </tr>
                    <?php endforeach ?>
                <?php endif ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function editRule(r){
    document.getElementById('rule-form').style.display = 'block';
    document.getElementById('form-title').textContent   = 'Edit Alert Rule';
    document.getElementById('fld-rule_id').value  = r.rule_id;
    document.getElementById('fld-name').value     = r.name;
    document.getElementById('fld-regex').value    = r.regex_pattern;
    document.getElementById('fld-severity').value = r.severity;
    document.getElementById('fld-enabled').checked = parseInt(r.enabled) === 1;
}
</script>

<?php /* ═══════════════════════════════════ STATISTICS ═══════════════════════════════════ */ ?>
<?php elseif ($tab === 'statistics'): ?>

<?php
$sevColors = array_values($sevColor);
$sevLabels = array_values($sevLabel);
$sevCounts = array_values($data['severity_stats'] ?? array_fill(0,8,0));

$devLabels = array_column($data['top_devices'], 'hostname');
$devCounts = array_column($data['top_devices'], 'cnt');

$dailyLabels = array_keys($data['daily_trend']);
$dailyCounts = array_values($data['daily_trend']);
$hourlyLabels = array_keys($data['hourly_trend']);
$hourlyCounts = array_values($data['hourly_trend']);
?>

<div class="lm-grid2">
    <div class="lm-panel">
        <div class="lm-panel-head"><h2>📊 Logs by Severity</h2></div>
        <div class="lm-panel-body"><div class="lm-chart-box"><canvas id="ch-sev"></canvas></div></div>
    </div>
    <div class="lm-panel">
        <div class="lm-panel-head"><h2>🖥️ Logs by Device</h2></div>
        <div class="lm-panel-body"><div class="lm-chart-box"><canvas id="ch-dev"></canvas></div></div>
    </div>
    <div class="lm-panel">
        <div class="lm-panel-head"><h2>⏱ Hourly Trend (Last 24 h)</h2></div>
        <div class="lm-panel-body"><div class="lm-chart-box"><canvas id="ch-hour"></canvas></div></div>
    </div>
    <div class="lm-panel">
        <div class="lm-panel-head"><h2>📅 Daily Trend (Last 7 Days)</h2></div>
        <div class="lm-panel-body"><div class="lm-chart-box"><canvas id="ch-day"></canvas></div></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
(function(){
const cf = { responsive:true, maintainAspectRatio:false };
const tick = { color:'#94a3b8' };
const gridC = 'rgba(255,255,255,.06)';

new Chart(document.getElementById('ch-sev'),{
    type:'doughnut',
    data:{ labels:<?=json_encode($sevLabels)?>, datasets:[{ data:<?=json_encode($sevCounts)?>, backgroundColor:<?=json_encode($sevColors)?>, borderWidth:0 }] },
    options:{ ...cf, plugins:{ legend:{ position:'right', labels:{ color:'#94a3b8', boxWidth:12 } } } }
});

new Chart(document.getElementById('ch-dev'),{
    type:'bar',
    data:{ labels:<?=json_encode($devLabels)?>, datasets:[{ label:'Logs', data:<?=json_encode($devCounts)?>, backgroundColor:'#3b82f6', borderRadius:4 }] },
    options:{ ...cf, plugins:{legend:{display:false}}, scales:{ x:{ticks:tick,grid:{color:gridC}}, y:{ticks:tick,grid:{color:gridC}} } }
});

new Chart(document.getElementById('ch-hour'),{
    type:'line',
    data:{ labels:<?=json_encode($hourlyLabels)?>, datasets:[{ label:'Logs/hr', data:<?=json_encode($hourlyCounts)?>, borderColor:'#22c55e', backgroundColor:'rgba(34,197,94,.1)', fill:true, tension:.35, pointRadius:2 }] },
    options:{ ...cf, plugins:{legend:{display:false}}, scales:{ x:{ticks:tick,grid:{color:gridC}}, y:{ticks:tick,grid:{color:gridC}} } }
});

new Chart(document.getElementById('ch-day'),{
    type:'line',
    data:{ labels:<?=json_encode($dailyLabels)?>, datasets:[{ label:'Logs/day', data:<?=json_encode($dailyCounts)?>, borderColor:'#f59e0b', backgroundColor:'rgba(245,158,11,.1)', fill:true, tension:.35, pointRadius:3 }] },
    options:{ ...cf, plugins:{legend:{display:false}}, scales:{ x:{ticks:tick,grid:{color:gridC}}, y:{ticks:tick,grid:{color:gridC}} } }
});
})();
</script>

<?php /* ═══════════════════════════════════ SETTINGS ═══════════════════════════════════ */ ?>
<?php elseif ($tab === 'settings'): ?>

<div class="lm-grid2">
    <div class="lm-panel">
        <div class="lm-panel-head"><h2>⚙️ Log Retention Policy</h2></div>
        <div class="lm-panel-body">
            <form action="<?= $url(['tab'=>'settings','task'=>'save_settings']) ?>" method="post">
                <div class="lm-fg">
                    <label>Retention Period</label>
                    <select name="retention_days">
                        <?php foreach ($retentionOptions as $days=>$label): ?>
                        <option value="<?= $days ?>" <?= (int)$data['retention_days'] === $days ? 'selected' : '' ?>>
                            <?= $label ?>
                        </option>
                        <?php endforeach ?>
                    </select>
                    <span class="lm-help">Logs older than this period are removed by the cleanup script.</span>
                </div>
                <div class="lm-form-actions" style="margin-top:16px">
                    <button type="submit" class="lm-btn lm-btn-primary">💾 Save Policy</button>
                </div>
            </form>
        </div>
    </div>

    <div class="lm-panel">
        <div class="lm-panel-head"><h2>📋 Syslog Daemon Info</h2></div>
        <div class="lm-panel-body">
            <table class="lm-info-table">
                <tr><td>UDP Port</td><td><code>514</code></td></tr>
                <tr><td>TCP Port</td><td><code>514</code></td></tr>
                <tr><td>Receiver</td><td><code>scripts/syslog_receiver.py</code></td></tr>
                <tr><td>Cleanup</td><td><code>scripts/log_cleanup.py</code></td></tr>
                <tr><td>Retention</td><td><strong><?= (int)$data['retention_days'] ?> days</strong></td></tr>
            </table>
            <div class="lm-note">
                <strong>💡 Tip:</strong> Run the receiver as a systemd service or Docker container.<br>
                Schedule cleanup via cron: <code>0 0 * * * python3 /path/to/log_cleanup.py</code>
            </div>
        </div>
    </div>
</div>

<?php endif ?>
</div>

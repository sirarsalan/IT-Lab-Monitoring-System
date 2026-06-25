<?php
// ============================================================
// changes.php — Inventory Change Tracker
// index.php style — header.php / footer.php included
// ============================================================

include 'auth.php';
include 'db.php';

/* ===== ACKNOWLEDGE ACTIONS (AJAX) ===== */
if (isset($_POST['ack_id'])) {
    $aid = (int)$_POST['ack_id'];
    $by  = $conn->real_escape_string($_SESSION['username'] ?? 'admin');
    $conn->query("UPDATE inventory_changes SET acknowledged=1, ack_by='$by', ack_at=NOW() WHERE id=$aid");
    echo json_encode(['ok' => true]);
    exit;
}
if (isset($_POST['ack_all'])) {
    $by = $conn->real_escape_string($_SESSION['username'] ?? 'admin');
    $conn->query("UPDATE inventory_changes SET acknowledged=1, ack_by='$by', ack_at=NOW() WHERE acknowledged=0");
    echo json_encode(['ok' => true]);
    exit;
}

/* ===== FILTERS ===== */
$filter_type   = $_GET['type']   ?? '';
$filter_pc     = $_GET['pc']     ?? '';
$filter_status = $_GET['status'] ?? '';
$page          = max(1, (int)($_GET['page'] ?? 1));
$per_page      = 20;
$offset        = ($page - 1) * $per_page;

$where = "WHERE 1=1";
if ($filter_type)                $where .= " AND change_type='".$conn->real_escape_string($filter_type)."'";
if ($filter_pc)                  $where .= " AND pc_name LIKE '%".$conn->real_escape_string($filter_pc)."%'";
if ($filter_status === 'unread') $where .= " AND acknowledged=0";
if ($filter_status === 'read')   $where .= " AND acknowledged=1";

/* ===== COUNTS ===== */
$total_count  = $conn->query("SELECT COUNT(*) AS c FROM inventory_changes $where")->fetch_assoc()['c'] ?? 0;
$total_pages  = max(1, ceil($total_count / $per_page));
$unread_count = $conn->query("SELECT COUNT(*) AS c FROM inventory_changes WHERE acknowledged=0")->fetch_assoc()['c'] ?? 0;
$today_count  = $conn->query("SELECT COUNT(*) AS c FROM inventory_changes WHERE DATE(detected_at)=CURDATE()")->fetch_assoc()['c'] ?? 0;
$week_count   = $conn->query("SELECT COUNT(*) AS c FROM inventory_changes WHERE detected_at >= NOW() - INTERVAL 7 DAY")->fetch_assoc()['c'] ?? 0;

/* ===== TYPE BREAKDOWN ===== */
$type_res = $conn->query("SELECT change_type, COUNT(*) AS c FROM inventory_changes GROUP BY change_type ORDER BY c DESC");
$type_map = [];
while ($tr = $type_res->fetch_assoc()) $type_map[$tr['change_type']] = $tr['c'];

/* ===== ROWS ===== */
$rows_res = $conn->query("SELECT * FROM inventory_changes $where ORDER BY detected_at DESC LIMIT $per_page OFFSET $offset");
$rows = [];
while ($row = $rows_res->fetch_assoc()) $rows[] = $row;

/* ===== TOP CHANGED PCs ===== */
$top_res = $conn->query("SELECT pc_name, COUNT(*) AS c FROM inventory_changes GROUP BY pc_name ORDER BY c DESC LIMIT 6");
$top_pcs = [];
while ($tp = $top_res->fetch_assoc()) $top_pcs[] = $tp;

/* ===== HELPER FUNCTIONS ===== */
function typeLabel($t) {
    $map = [
        'ram'         => ['RAM',          'ti-database',      '#6366f1', 'blue'],
        'ip'          => ['IP Address',   'ti-network',       '#0d9488', 'teal'],
        'cpu'         => ['CPU',          'ti-cpu',           '#d97706', 'amber'],
        'os'          => ['OS',           'ti-brand-windows', '#059669', 'green'],
        'disk'        => ['Disk',         'ti-device-floppy', '#7c3aed', 'purple'],
        'location'    => ['Location',     'ti-map-pin',       '#2563eb', 'blue'],
        'arch'        => ['Architecture', 'ti-settings',      '#9ca3af', 'gray'],
        'mb_serial'   => ['MB Serial',    'ti-circuit-board', '#dc2626', 'red'],
        'logged_user' => ['Logged User',  'ti-user',          '#6b7280', 'gray'],
    ];
    return $map[$t] ?? [$t, 'ti-alert-circle', '#6b7280', 'gray'];
}

function timeDiff($dt) {
    $diff = time() - strtotime($dt);
    if ($diff < 60)    return $diff . 's ago';
    if ($diff < 3600)  return floor($diff/60)   . 'm ago';
    if ($diff < 86400) return floor($diff/3600) . 'h ago';
    return floor($diff/86400) . 'd ago';
}

$types_all = ['ram','ip','cpu','os','disk','location','arch','mb_serial','logged_user'];
$max_top   = $top_pcs ? max(array_column($top_pcs,'c')) : 1;

// Force header.php to highlight correct sidebar item
$_GET['view'] = 'logs'; // closest nav item — or add 'changes' to header if needed
include 'header.php';
?>

<!-- ===== SUCCESS BANNER ===== -->
<?php if (isset($_GET['scan_done'])): ?>
<div style="background:#d1fae5;border:.5px solid #6ee7b7;color:#065f46;padding:10px 16px;border-radius:10px;margin-bottom:14px;font-size:12px;display:flex;align-items:center;gap:8px;">
    <i class="ti ti-circle-check" style="font-size:16px"></i>
    Scan complete — <?= (int)$_GET['scanned'] ?> PCs checked, <?= (int)$_GET['changes'] ?> new changes detected.
</div>
<?php endif; ?>

<!-- ===== STAT CARDS ===== -->
<div class="stats-row" style="grid-template-columns:repeat(4,1fr)">
    <div class="stat-card">
        <div class="stat-accent red"></div>
        <div class="stat-icon red"><i class="ti ti-bell"></i></div>
        <div class="stat-label">Unread Alerts</div>
        <div class="stat-value" style="color:#dc2626" id="unread-count"><?= $unread_count ?></div>
        <div class="stat-sub">requires attention</div>
    </div>
    <div class="stat-card">
        <div class="stat-accent amber"></div>
        <div class="stat-icon amber"><i class="ti ti-calendar-today"></i></div>
        <div class="stat-label">Today</div>
        <div class="stat-value" style="color:#d97706"><?= $today_count ?></div>
        <div class="stat-sub">changes detected</div>
    </div>
    <div class="stat-card">
        <div class="stat-accent blue"></div>
        <div class="stat-icon blue"><i class="ti ti-calendar-week"></i></div>
        <div class="stat-label">This Week</div>
        <div class="stat-value" style="color:#6366f1"><?= $week_count ?></div>
        <div class="stat-sub">last 7 days</div>
    </div>
    <div class="stat-card">
        <div class="stat-accent green"></div>
        <div class="stat-icon green"><i class="ti ti-clipboard-list"></i></div>
        <div class="stat-label">Total Logged</div>
        <div class="stat-value" style="color:#059669"><?= $total_count ?></div>
        <div class="stat-sub">all time</div>
    </div>
</div>

<!-- ===== TWO COL: Type Breakdown + Top PCs ===== -->
<div class="two-col" style="margin-bottom:14px">

    <!-- Change Types -->
    <div class="panel">
        <div class="panel-head">
            <div class="panel-title"><i class="ti ti-chart-bar"></i>Change Types Breakdown</div>
        </div>
        <div style="padding:14px 16px">
            <?php foreach ($types_all as $t):
                [$lbl, $icon, $col, $clr] = typeLabel($t);
                $cnt = $type_map[$t] ?? 0;
                $pct = $total_count ? round($cnt / $total_count * 100) : 0;
            ?>
            <div style="margin-bottom:10px">
                <div style="display:flex;justify-content:space-between;font-size:11px;margin-bottom:4px;align-items:center">
                    <span style="display:flex;align-items:center;gap:5px;color:var(--color-text-primary)">
                        <i class="ti <?= $icon ?>" style="color:<?= $col ?>;font-size:13px"></i><?= $lbl ?>
                    </span>
                    <a href="changes.php?type=<?= $t ?>" style="color:#6366f1;font-size:11px;text-decoration:none;font-weight:500"><?= $cnt ?> ›</a>
                </div>
                <div style="height:6px;background:#f1f5f9;border-radius:99px;overflow:hidden">
                    <div style="height:100%;border-radius:99px;width:<?= max($pct,1) ?>%;background:<?= $col ?>;transition:width .4s"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Top Changed PCs -->
    <div class="panel">
        <div class="panel-head">
            <div class="panel-title"><i class="ti ti-device-desktop"></i>Most Changed PCs</div>
        </div>
        <div style="padding:14px 16px">
            <?php if (empty($top_pcs)): ?>
            <div style="text-align:center;padding:24px;color:var(--color-text-tertiary);font-size:12px">
                <i class="ti ti-inbox" style="font-size:24px;display:block;margin-bottom:6px"></i>
                No changes logged yet
            </div>
            <?php else: ?>
            <?php foreach ($top_pcs as $tp):
                $pct = round($tp['c'] / $max_top * 100);
            ?>
            <div style="margin-bottom:10px">
                <div style="display:flex;justify-content:space-between;font-size:11px;margin-bottom:4px">
                    <a href="changes.php?pc=<?= urlencode($tp['pc_name']) ?>"
                       style="color:var(--color-text-primary);text-decoration:none;font-weight:500">
                        <?= htmlspecialchars($tp['pc_name']) ?>
                    </a>
                    <span style="color:var(--color-text-tertiary)"><?= $tp['c'] ?> changes</span>
                </div>
                <div style="height:6px;background:#f1f5f9;border-radius:99px;overflow:hidden">
                    <div style="height:100%;border-radius:99px;width:<?= $pct ?>%;background:linear-gradient(90deg,#6366f1,#8b5cf6)"></div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- ===== MAIN PANEL: Filter + Table ===== -->
<div class="panel">
    <div class="panel-head">
        <div class="panel-title">
            <i class="ti ti-bell"></i>
            Inventory Change Log
            <?php if ($unread_count > 0): ?>
            <span class="panel-badge" style="background:#fee2e2;color:#dc2626"><?= $unread_count ?> unread</span>
            <?php endif; ?>
            <span class="panel-badge"><?= $total_count ?> total</span>
        </div>
        <div style="display:flex;align-items:center;gap:8px">
            <?php if ($unread_count > 0): ?>
            <button class="filter-btn" style="background:#059669" onclick="ackAll()">
                <i class="ti ti-checks" style="font-size:13px"></i>Mark All Read
            </button>
            <?php endif; ?>
            <a href="sync_pc_status.php?run_scan=1" class="filter-btn">
                <i class="ti ti-refresh" style="font-size:13px"></i>Run Scan
            </a>
            <div class="pager">
                <span class="pg-info">Page <?= $page ?>/<?= $total_pages ?></span>
                <?php
                $prev_params = array_merge($_GET, ['page' => $page-1]);
                $next_params = array_merge($_GET, ['page' => $page+1]);
                unset($prev_params['view'], $next_params['view']);
                ?>
                <a href="changes.php?<?= http_build_query($prev_params) ?>"
                   class="pg-btn" <?= $page<=1?'style="pointer-events:none;opacity:.35"':'' ?>>‹</a>
                <a href="changes.php?<?= http_build_query($next_params) ?>"
                   class="pg-btn" <?= $page>=$total_pages?'style="pointer-events:none;opacity:.35"':'' ?>>›</a>
            </div>
        </div>
    </div>

    <!-- FILTER ROW -->
    <form method="GET">
        <div class="filter-row">
            <input name="pc" placeholder="Filter PC name..." value="<?= htmlspecialchars($filter_pc) ?>">

            <select name="type">
                <option value="">All Types</option>
                <?php foreach ($types_all as $t):
                    [$lbl] = typeLabel($t);
                ?>
                <option value="<?= $t ?>" <?= $filter_type===$t?'selected':'' ?>><?= $lbl ?></option>
                <?php endforeach; ?>
            </select>

            <select name="status">
                <option value=""       <?= $filter_status===''      ?'selected':'' ?>>All Status</option>
                <option value="unread" <?= $filter_status==='unread'?'selected':'' ?>>Unread</option>
                <option value="read"   <?= $filter_status==='read'  ?'selected':'' ?>>Read</option>
            </select>

            <button type="submit" class="filter-btn"><i class="ti ti-search" style="font-size:13px"></i>Search</button>
            <a href="changes.php" class="filter-btn" style="background:var(--color-background-secondary);color:var(--color-text-secondary);border:.5px solid var(--color-border-tertiary)">Reset</a>
        </div>
    </form>

    <!-- TABLE -->
    <table class="tbl">
        <thead>
            <tr>
                <th style="width:12px"></th>
                <th>PC Name</th>
                <th>IP Address</th>
                <th>Change Type</th>
                <th>Field</th>
                <th>Old Value</th>
                <th>New Value</th>
                <th>Detected</th>
                <th>Status</th>
                <th style="width:36px"></th>
            </tr>
        </thead>
        <tbody>

        <?php if (empty($rows)): ?>
        <tr>
            <td colspan="10" style="text-align:center;padding:32px;color:var(--color-text-tertiary)">
                <i class="ti ti-mood-smile" style="font-size:28px;display:block;margin-bottom:8px;color:#6366f1"></i>
                No changes found — inventory is stable!
            </td>
        </tr>
        <?php endif; ?>

        <?php foreach ($rows as $r):
            [$lbl, $icon, $col, $clr] = typeLabel($r['change_type']);
            $isUnread = !$r['acknowledged'];
        ?>
        <tr id="row-<?= $r['id'] ?>" style="<?= $isUnread ? 'background:#fffbeb !important' : '' ?>">

            <!-- Status Dot -->
            <td style="text-align:center">
                <span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:<?= $isUnread ? '#ef4444' : '#d1d5db' ?>"></span>
            </td>

            <!-- PC Name -->
            <td>
                <span style="font-weight:500;font-size:12px"><?= htmlspecialchars($r['pc_name']) ?></span>
                <?php if ($r['motherboard_serial']): ?>
                <br><span class="mono" style="font-size:9px;color:var(--color-text-tertiary)"><?= htmlspecialchars(substr($r['motherboard_serial'],0,18)) ?></span>
                <?php endif; ?>
            </td>

            <!-- IP -->
            <td class="mono"><?= htmlspecialchars($r['ip_address']) ?: '—' ?></td>

            <!-- Change Type -->
            <td>
                <span style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:500;color:<?= $col ?>">
                    <i class="ti <?= $icon ?>" style="font-size:14px"></i><?= $lbl ?>
                </span>
            </td>

            <!-- Field -->
            <td style="color:var(--color-text-secondary);font-size:11px"><?= htmlspecialchars($r['field_name']) ?></td>

            <!-- Old Value -->
            <td>
                <span class="pill p-disc" style="max-width:150px;display:inline-block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;vertical-align:middle"
                      title="<?= htmlspecialchars($r['old_value']) ?>">
                    <?= htmlspecialchars(mb_substr($r['old_value'], 0, 30)) ?>
                </span>
            </td>

            <!-- New Value -->
            <td>
                <span class="pill p-conn" style="max-width:150px;display:inline-block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;vertical-align:middle"
                      title="<?= htmlspecialchars($r['new_value']) ?>">
                    <?= htmlspecialchars(mb_substr($r['new_value'], 0, 30)) ?>
                </span>
            </td>

            <!-- Detected -->
            <td>
                <span style="font-size:11px;font-weight:500"><?= timeDiff($r['detected_at']) ?></span>
                <br><span class="mono" style="font-size:9px;color:var(--color-text-tertiary)"><?= date('d M Y H:i', strtotime($r['detected_at'])) ?></span>
            </td>

            <!-- Status -->
            <td>
                <?php if ($r['acknowledged']): ?>
                    <span class="pill p-conn">✓ Read</span>
                    <?php if ($r['ack_by']): ?>
                    <br><span style="font-size:9px;color:var(--color-text-tertiary)"><?= htmlspecialchars($r['ack_by']) ?></span>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="pill p-disc">● New</span>
                <?php endif; ?>
            </td>

            <!-- Ack Button -->
            <td>
                <button class="action-btn" <?= $r['acknowledged'] ? 'disabled style="opacity:.35;cursor:default"' : '' ?>
                    onclick="ackOne(<?= $r['id'] ?>)" title="Mark as read">
                    <i class="ti ti-check"></i>
                </button>
            </td>

        </tr>
        <?php endforeach; ?>

        </tbody>
    </table>
</div><!-- /panel -->

<!-- ===== TOAST ===== -->
<div id="toast" style="position:fixed;bottom:24px;right:24px;background:#1e1b4b;color:white;padding:12px 20px;border-radius:10px;font-size:12px;z-index:9999;transform:translateY(60px);opacity:0;transition:all .3s;box-shadow:0 8px 24px rgba(0,0,0,.25);display:flex;align-items:center;gap:8px"></div>

<script>
function showToast(msg) {
    const t = document.getElementById('toast');
    t.innerHTML = '<i class="ti ti-circle-check" style="font-size:16px;color:#6ee7b7"></i>' + msg;
    t.style.transform = 'translateY(0)';
    t.style.opacity   = '1';
    setTimeout(() => { t.style.transform='translateY(60px)'; t.style.opacity='0'; }, 2500);
}

function ackOne(id) {
    fetch('changes.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'ack_id=' + id
    })
    .then(r => r.json())
    .then(d => {
        if (!d.ok) return;
        const row = document.getElementById('row-' + id);
        if (row) {
            row.style.background = '';
            const dot  = row.querySelector('span[style*="7px"]');
            if (dot)  dot.style.background = '#d1d5db';
            const pill = row.querySelector('.p-disc');
            if (pill) { pill.className = 'pill p-conn'; pill.textContent = '✓ Read'; }
            const btn  = row.querySelector('.action-btn');
            if (btn)  { btn.disabled = true; btn.style.opacity='.35'; btn.style.cursor='default'; }
        }
        const badge = document.getElementById('unread-count');
        if (badge) badge.textContent = Math.max(0, parseInt(badge.textContent) - 1);
        showToast('Marked as read');
    });
}

function ackAll() {
    if (!confirm('Sab changes read mark karen?')) return;
    fetch('changes.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'ack_all=1'
    })
    .then(r => r.json())
    .then(d => {
        if (d.ok) {
            showToast('Sab read mark ho gaye');
            setTimeout(() => location.reload(), 900);
        }
    });
}
</script>

<?php include 'footer.php'; ?>

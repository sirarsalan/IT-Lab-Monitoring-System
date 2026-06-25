<?php
include 'auth.php';
include 'db.php';

$msg = '';
$msgType = '';

// ─── AUTO-SYNC users from users table ────────────────────────────────────────
$conn->query("
    INSERT IGNORE INTO print_quotas (user_id, username, quota_limit, pages_used)
    SELECT id, username, 50, 0 FROM users
    ON DUPLICATE KEY UPDATE username = VALUES(username)
");

// ─── SET / UPDATE QUOTA ───────────────────────────────────────────────────────
if (isset($_POST['save_quota'])) {
    $uid   = intval($_POST['user_id']);
    $limit = intval($_POST['quota_limit']);
    $notes = $conn->real_escape_string(trim($_POST['notes'] ?? ''));
    if ($uid && $limit > 0) {
        $conn->query("UPDATE print_quotas SET quota_limit='$limit', notes='$notes', last_updated=NOW() WHERE user_id='$uid'");
        $msg = 'Quota updated successfully!'; $msgType = 'success';
    }
}

// ─── RESET USER QUOTA ────────────────────────────────────────────────────────
if (isset($_POST['reset_quota'])) {
    $uid    = intval($_POST['reset_uid']);
    $reason = $conn->real_escape_string(trim($_POST['reset_reason'] ?? ''));
    $by     = $conn->real_escape_string($_SESSION['username'] ?? 'admin');

    // Get current usage before reset
    $cur = $conn->query("SELECT username, pages_used FROM print_quotas WHERE user_id='$uid'")->fetch_assoc();
    if ($cur) {
        $conn->query("INSERT INTO print_quota_resets (user_id, username, pages_reset, reset_by, reason)
            VALUES ('$uid', '".$conn->real_escape_string($cur['username'])."', '{$cur['pages_used']}', '$by', '$reason')");
        $conn->query("UPDATE print_quotas SET pages_used=0, last_reset=NOW(), last_updated=NOW() WHERE user_id='$uid'");
        $msg = "Quota reset for {$cur['username']}!"; $msgType = 'success';
    }
}

// ─── ADD PRINT LOG ───────────────────────────────────────────────────────────
if (isset($_POST['add_log'])) {
    $uid    = intval($_POST['log_user_id']);
    $pages  = intval($_POST['log_pages']);
    $printer= $conn->real_escape_string(trim($_POST['printer_name'] ?? ''));
    $pc     = $conn->real_escape_string(trim($_POST['pc_name'] ?? ''));
    $desc   = $conn->real_escape_string(trim($_POST['description'] ?? ''));
    $by     = $conn->real_escape_string($_SESSION['username'] ?? 'admin');

    if ($uid && $pages > 0) {
        // Get user info
        $urow = $conn->query("SELECT username, quota_limit, pages_used FROM print_quotas WHERE user_id='$uid'")->fetch_assoc();
        if ($urow) {
            $uname    = $conn->real_escape_string($urow['username']);
            $newTotal = $urow['pages_used'] + $pages;
            $limit    = $urow['quota_limit'];

            // Insert log
            $conn->query("INSERT INTO print_logs (user_id, username, pages, printer_name, pc_name, description, logged_by)
                VALUES ('$uid', '$uname', '$pages', '$printer', '$pc', '$desc', '$by')");

            // Update used count
            $conn->query("UPDATE print_quotas SET pages_used='$newTotal', last_updated=NOW() WHERE user_id='$uid'");

            if ($newTotal >= $limit) {
                $msg = "⚠️ {$urow['username']} ki limit KHATAM ho gayi! ($newTotal / $limit pages used)";
                $msgType = 'warning';
            } else {
                $remaining = $limit - $newTotal;
                $msg = "Print log added! {$urow['username']} ke paas sirf $remaining pages baqi hain.";
                $msgType = 'success';
            }
        }
    }
}

// ─── FETCH DATA ───────────────────────────────────────────────────────────────
$quotas = [];
$res = $conn->query("SELECT pq.*, u.role FROM print_quotas pq LEFT JOIN users u ON u.id=pq.user_id ORDER BY pq.username ASC");
if ($res) while ($r = $res->fetch_assoc()) $quotas[] = $r;

$logs = [];
$lres = $conn->query("SELECT * FROM print_logs ORDER BY id DESC LIMIT 100");
if ($lres) while ($r = $lres->fetch_assoc()) $logs[] = $r;

$resets = [];
$rres = $conn->query("SELECT * FROM print_quota_resets ORDER BY id DESC LIMIT 50");
if ($rres) while ($r = $rres->fetch_assoc()) $resets[] = $r;

// Stats
$totalUsers    = count($quotas);
$overLimit     = count(array_filter($quotas, function($q){ return $q['pages_used'] >= $q['quota_limit']; }));
$nearLimit     = count(array_filter($quotas, function($q){ return $q['pages_used'] >= $q['quota_limit']*0.8 && $q['pages_used'] < $q['quota_limit']; }));
$totalPrinted  = array_sum(array_column($quotas, 'pages_used'));

$_GET['view'] = 'print_quota';
include 'header.php';
?>

<style>
.quota-bar-wrap { background:#e2e8f0; border-radius:99px; height:8px; overflow:hidden; margin-top:4px; }
.quota-bar      { height:100%; border-radius:99px; transition:width .3s; }
.bar-ok   { background:linear-gradient(90deg,#10b981,#059669); }
.bar-warn { background:linear-gradient(90deg,#f59e0b,#d97706); }
.bar-over { background:linear-gradient(90deg,#ef4444,#dc2626); }
.alert-box { padding:10px 16px; border-radius:8px; font-size:12px; margin-bottom:14px; display:flex; align-items:center; gap:8px; }
.alert-success { background:#dcfce7; color:#15803d; border:.5px solid #86efac; }
.alert-warning { background:#fef9c3; color:#854d0e; border:.5px solid #fde047; }
</style>

<!-- STATS -->
<div class="stats-row">
  <div class="stat-card">
    <div class="stat-accent blue"></div>
    <div class="stat-icon blue"><i class="ti ti-users"></i></div>
    <div class="stat-label">Total Users</div>
    <div class="stat-value" style="color:#6366f1"><?= $totalUsers ?></div>
    <div class="stat-sub">with print quota</div>
  </div>
  <div class="stat-card">
    <div class="stat-accent red"></div>
    <div class="stat-icon red"><i class="ti ti-alert-triangle"></i></div>
    <div class="stat-label">Limit Khatam</div>
    <div class="stat-value" style="color:#dc2626"><?= $overLimit ?></div>
    <div class="stat-sub">quota exceeded</div>
  </div>
  <div class="stat-card">
    <div class="stat-accent amber"></div>
    <div class="stat-icon amber"><i class="ti ti-alert-circle"></i></div>
    <div class="stat-label">Near Limit</div>
    <div class="stat-value" style="color:#d97706"><?= $nearLimit ?></div>
    <div class="stat-sub">80%+ used</div>
  </div>
  <div class="stat-card">
    <div class="stat-accent teal"></div>
    <div class="stat-icon teal"><i class="ti ti-printer"></i></div>
    <div class="stat-label">Total Printed</div>
    <div class="stat-value" style="color:#0d9488"><?= $totalPrinted ?></div>
    <div class="stat-sub">pages this period</div>
  </div>
</div>

<?php if ($msg): ?>
<div class="alert-box alert-<?= $msgType ?>">
  <i class="ti ti-<?= $msgType==='success'?'circle-check':'alert-triangle' ?>"></i>
  <?= htmlspecialchars($msg) ?>
</div>
<?php endif; ?>

<!-- ADD PRINT LOG PANEL -->
<div class="panel" style="margin-bottom:16px">
  <div class="panel-head">
    <div class="panel-title"><i class="ti ti-printer" style="color:#6366f1"></i>Add Print Log <span class="panel-badge">IT Staff Entry</span></div>
  </div>
  <div style="padding:16px">
    <form method="POST">
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr 1fr;gap:10px;align-items:end">
        <div class="form-group" style="margin:0">
          <label>User *</label>
          <select name="log_user_id" required>
            <option value="">-- Select User --</option>
            <?php foreach($quotas as $q): 
              $pct = $q['quota_limit'] > 0 ? round($q['pages_used']/$q['quota_limit']*100) : 0;
              $over = $q['pages_used'] >= $q['quota_limit'];
            ?>
            <option value="<?= $q['user_id'] ?>" <?= $over?'style="color:#dc2626"':'' ?>>
              <?= htmlspecialchars($q['username']) ?> (<?= $q['pages_used'] ?>/<?= $q['quota_limit'] ?> pages<?= $over?' ⚠️':'' ?>)
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="margin:0">
          <label>Pages Printed *</label>
          <input type="number" name="log_pages" min="1" max="500" placeholder="e.g. 10" required>
        </div>
        <div class="form-group" style="margin:0">
          <label>Printer Name</label>
          <input type="text" name="printer_name" placeholder="e.g. HP LaserJet">
        </div>
        <div class="form-group" style="margin:0">
          <label>PC Name</label>
          <input type="text" name="pc_name" placeholder="e.g. LAB-PC-01">
        </div>
        <div class="form-group" style="margin:0">
          <label>&nbsp;</label>
          <button type="submit" name="add_log" class="filter-btn" style="width:100%;justify-content:center">
            <i class="ti ti-plus"></i>&nbsp;Add Log
          </button>
        </div>
      </div>
      <div style="margin-top:8px">
        <input type="text" name="description" placeholder="Description (optional)..." style="width:100%">
      </div>
    </form>
  </div>
</div>

<!-- QUOTA TABLE -->
<div class="panel" style="margin-bottom:16px">
  <div class="panel-head">
    <div class="panel-title"><i class="ti ti-adjustments"></i>User Print Quotas</div>
  </div>
  <table class="tbl">
    <thead>
      <tr>
        <th>Username</th>
        <th>Role</th>
        <th style="min-width:200px">Usage</th>
        <th>Pages Used</th>
        <th>Quota Limit</th>
        <th>Remaining</th>
        <th>Last Reset</th>
        <th>Notes</th>
        <th style="text-align:center">Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php if(empty($quotas)): ?>
      <tr><td colspan="9" style="text-align:center;padding:24px;color:#94a3b8">No users found</td></tr>
    <?php else: foreach($quotas as $q):
      $pct       = $q['quota_limit'] > 0 ? min(100, round($q['pages_used']/$q['quota_limit']*100)) : 0;
      $remaining = max(0, $q['quota_limit'] - $q['pages_used']);
      $barClass  = $pct >= 100 ? 'bar-over' : ($pct >= 80 ? 'bar-warn' : 'bar-ok');
      $isOver    = $q['pages_used'] >= $q['quota_limit'];
      $isNear    = !$isOver && $pct >= 80;
    ?>
    <tr <?= $isOver?'style="background:#fff5f5"':($isNear?'style="background:#fffbeb"':'') ?>>
      <td>
        <div style="display:flex;align-items:center;gap:7px">
          <div style="width:26px;height:26px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;color:#fff;font-size:10px;font-weight:600">
            <?= strtoupper(substr($q['username'],0,2)) ?>
          </div>
          <span style="font-weight:500"><?= htmlspecialchars($q['username']) ?></span>
          <?php if($isOver): ?><span style="font-size:10px;color:#dc2626;font-weight:600">⚠ LIMIT KHATAM</span><?php endif; ?>
          <?php if($isNear): ?><span style="font-size:10px;color:#d97706;font-weight:600">⚠ Near Limit</span><?php endif; ?>
        </div>
      </td>
      <td><span class="pill p-gray"><?= htmlspecialchars($q['role']??'user') ?></span></td>
      <td>
        <div style="font-size:10px;color:#64748b;margin-bottom:2px"><?= $pct ?>% used</div>
        <div class="quota-bar-wrap">
          <div class="quota-bar <?= $barClass ?>" style="width:<?= $pct ?>%"></div>
        </div>
      </td>
      <td style="font-weight:600;color:<?= $isOver?'#dc2626':($isNear?'#d97706':'#0f172a') ?>"><?= $q['pages_used'] ?></td>
      <td><?= $q['quota_limit'] ?></td>
      <td style="font-weight:500;color:<?= $remaining==0?'#dc2626':($remaining<=10?'#d97706':'#059669') ?>">
        <?= $remaining ?> pages
      </td>
      <td class="mono" style="font-size:10px"><?= $q['last_reset'] ? date('d M Y', strtotime($q['last_reset'])) : '—' ?></td>
      <td style="font-size:11px;color:#64748b"><?= htmlspecialchars($q['notes']??'—') ?></td>
      <td style="text-align:center;white-space:nowrap">
        <!-- Edit Quota -->
        <button class="action-btn" title="Set Quota"
          onclick="openSetQuota('<?= $q['user_id'] ?>','<?= htmlspecialchars($q['username']) ?>','<?= $q['quota_limit'] ?>','<?= htmlspecialchars($q['notes']??'') ?>')">
          <i class="ti ti-edit"></i>
        </button>
        <!-- Reset -->
        <button class="action-btn" title="Reset Quota"
          onclick="openReset('<?= $q['user_id'] ?>','<?= htmlspecialchars($q['username']) ?>','<?= $q['pages_used'] ?>')">
          <i class="ti ti-refresh"></i>
        </button>
      </td>
    </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<!-- PRINT LOG HISTORY -->
<div class="panel" style="margin-bottom:16px">
  <div class="panel-head">
    <div class="panel-title"><i class="ti ti-history"></i>Print Log History <span class="panel-badge"><?= count($logs) ?> records</span></div>
  </div>
  <table class="tbl">
    <thead>
      <tr><th>Date/Time</th><th>User</th><th>Pages</th><th>Printer</th><th>PC</th><th>Description</th><th>Logged By</th></tr>
    </thead>
    <tbody>
    <?php if(empty($logs)): ?>
      <tr><td colspan="7" style="text-align:center;padding:24px;color:#94a3b8">No logs yet</td></tr>
    <?php else: foreach($logs as $l): ?>
    <tr>
      <td class="mono" style="font-size:10px"><?= htmlspecialchars($l['logged_at']) ?></td>
      <td style="font-weight:500"><?= htmlspecialchars($l['username']) ?></td>
      <td><span class="pill p-blue"><?= $l['pages'] ?> pages</span></td>
      <td style="font-size:11px"><?= htmlspecialchars($l['printer_name']??'—') ?></td>
      <td class="mono" style="font-size:11px"><?= htmlspecialchars($l['pc_name']??'—') ?></td>
      <td style="font-size:11px;color:#64748b"><?= htmlspecialchars($l['description']??'—') ?></td>
      <td style="font-size:11px"><?= htmlspecialchars($l['logged_by']??'—') ?></td>
    </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<!-- RESET HISTORY -->
<?php if(!empty($resets)): ?>
<div class="panel">
  <div class="panel-head">
    <div class="panel-title"><i class="ti ti-refresh"></i>Reset History <span class="panel-badge"><?= count($resets) ?> resets</span></div>
  </div>
  <table class="tbl">
    <thead>
      <tr><th>Date</th><th>User</th><th>Pages Cleared</th><th>Reset By</th><th>Reason</th></tr>
    </thead>
    <tbody>
    <?php foreach($resets as $r): ?>
    <tr>
      <td class="mono" style="font-size:10px"><?= htmlspecialchars($r['reset_at']) ?></td>
      <td style="font-weight:500"><?= htmlspecialchars($r['username']) ?></td>
      <td><span class="pill p-amber"><?= $r['pages_reset'] ?> pages</span></td>
      <td><?= htmlspecialchars($r['reset_by']??'—') ?></td>
      <td style="font-size:11px;color:#64748b"><?= htmlspecialchars($r['reason']??'—') ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<!-- POPUP: Set Quota -->
<div id="popupSetQuota" class="popup" onclick="if(event.target===this)this.classList.remove('show')">
  <div class="popup-box" style="width:380px">
    <h3><i class="ti ti-adjustments"></i>&nbsp;Set Print Quota</h3>
    <form method="POST">
      <input type="hidden" name="user_id" id="sq_uid">
      <div class="form-group">
        <label>User</label>
        <input type="text" id="sq_uname" readonly style="background:#f1f5f9">
      </div>
      <div class="form-group">
        <label>Quota Limit (pages) *</label>
        <input type="number" name="quota_limit" id="sq_limit" min="1" max="99999" required placeholder="e.g. 50">
      </div>
      <div class="form-group">
        <label>Notes</label>
        <input type="text" name="notes" id="sq_notes" placeholder="Optional note...">
      </div>
      <div class="popup-actions">
        <button type="submit" name="save_quota" class="btn-save">Save Quota</button>
        <button type="button" class="btn-close" onclick="document.getElementById('popupSetQuota').classList.remove('show')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- POPUP: Reset Quota -->
<div id="popupReset" class="popup" onclick="if(event.target===this)this.classList.remove('show')">
  <div class="popup-box" style="width:380px">
    <h3 style="color:#d97706"><i class="ti ti-refresh" style="color:#d97706"></i>&nbsp;Reset Print Quota</h3>
    <p style="font-size:12px;color:#64748b;margin-bottom:14px">
      "<strong id="rst_uname"></strong>" ke <strong id="rst_pages"></strong> pages clear ho jayenge.
    </p>
    <form method="POST">
      <input type="hidden" name="reset_uid" id="rst_uid">
      <div class="form-group">
        <label>Reason (optional)</label>
        <input type="text" name="reset_reason" placeholder="e.g. Monthly reset, Special case...">
      </div>
      <div class="popup-actions">
        <button type="submit" name="reset_quota" class="btn-save" style="background:#d97706">Reset</button>
        <button type="button" class="btn-close" onclick="document.getElementById('popupReset').classList.remove('show')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
function openSetQuota(uid, uname, limit, notes) {
  document.getElementById('sq_uid').value   = uid;
  document.getElementById('sq_uname').value = uname;
  document.getElementById('sq_limit').value = limit;
  document.getElementById('sq_notes').value = notes;
  document.getElementById('popupSetQuota').classList.add('show');
}
function openReset(uid, uname, pages) {
  document.getElementById('rst_uid').value    = uid;
  document.getElementById('rst_uname').textContent = uname;
  document.getElementById('rst_pages').textContent = pages;
  document.getElementById('popupReset').classList.add('show');
}
</script>

<?php include 'footer.php'; ?>
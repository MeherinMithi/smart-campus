<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'staff') {
    header("Location: login.php"); exit;
}
require_once 'helpers.php';

// Handle resolve
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $all      = loadComplaints();
    $targetId = $_POST['complaint_id'] ?? '';
    $note     = trim($_POST['resolution_note'] ?? '');

    foreach ($all as &$c) {
        if ($c['id'] === $targetId && $c['assigned_to'] === $_SESSION['username']) {
            $c['status']          = 'Resolved';
            $c['resolution_note'] = $note;
            $c['resolved_at']     = date('Y-m-d H:i:s');
            break;
        }
    }
    unset($c);
    saveComplaints($all);
    header("Location: staff_dashboard.php"); exit;
}

$all    = loadComplaints();
$myWork = array_values(array_filter($all, fn($c) => $c['assigned_to'] === $_SESSION['username']));
usort($myWork, fn($a,$b) => strcmp($b['submitted_at'], $a['submitted_at']));

$total      = count($myWork);
$pending    = count(array_filter($myWork, fn($c) => $c['status'] === 'In Progress'));
$resolved   = count(array_filter($myWork, fn($c) => $c['status'] === 'Resolved'));

$filterStatus = $_GET['status'] ?? '';
$filtered = $filterStatus
    ? array_values(array_filter($myWork, fn($c) => $c['status'] === $filterStatus))
    : $myWork;

$notifs   = getNotifications($all, $_SESSION['username'], 'staff');
$initials = strtoupper(substr($_SESSION['fullname'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Staff Dashboard — Smart Campus</title>
  <link rel="stylesheet" href="../style.css"/>
</head>
<body>

  <header class="navbar">
    <div class="brand">Smart<span>Campus</span> <span style="font-size:12px;background:#2a7a5f;color:#fff;padding:2px 8px;border-radius:4px;font-family:'DM Sans',sans-serif;font-weight:700;margin-left:8px;">STAFF</span></div>
    <nav>
      <a href="staff_dashboard.php" class="active">My Tasks</a>
    </nav>
    <div class="user-badge">
      <div class="notif-wrap">
        <button class="notif-bell" onclick="toggleNotif()">
          🔔<?php if (count($notifs)): ?><span class="notif-count"><?= count($notifs) ?></span><?php endif; ?>
        </button>
        <div class="notif-dropdown" id="notifBox">
          <div class="notif-hd">🔔 Notifications</div>
          <?php if (empty($notifs)): ?>
            <div class="notif-empty">No new tasks assigned.</div>
          <?php else: foreach ($notifs as $n): ?>
            <div class="notif-item unread"><?= htmlspecialchars($n['msg']) ?><div class="notif-time"><?= htmlspecialchars($n['time']) ?></div></div>
          <?php endforeach; endif; ?>
        </div>
      </div>
      <div class="avatar"><?= $initials ?></div>
      <span><?= htmlspecialchars($_SESSION['fullname']) ?></span>
      <span class="role-tag">Staff</span>
      <a href="logout.php" class="logout-btn">Logout</a>
    </div>
  </header>

  <div class="page-wrapper">
    <div class="page-header">
      <h1>My Assigned Tasks</h1>
      <p>Complaints assigned to you for resolution. Prioritise High-priority issues first.</p>
    </div>

    <div class="stats-grid">
      <div class="stat-card accent-card">
        <span class="stat-label">Total Assigned</span>
        <span class="stat-value"><?= $total ?></span>
        <span class="stat-sub">All time</span>
      </div>
      <div class="stat-card warning-card">
        <span class="stat-label">Pending</span>
        <span class="stat-value"><?= $pending ?></span>
        <span class="stat-sub">Needs resolution</span>
      </div>
      <div class="stat-card success-card">
        <span class="stat-label">Resolved</span>
        <span class="stat-value"><?= $resolved ?></span>
        <span class="stat-sub">Completed</span>
      </div>
    </div>

    <div class="card">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--border);">
        <div class="card-title" style="margin:0;padding:0;border:none;">Complaint Tasks</div>
        <span style="font-size:13px;color:var(--text-muted);"><?= count($filtered) ?> task<?= count($filtered)!==1?'s':'' ?></span>
      </div>

      <form method="GET" action="staff_dashboard.php">
        <div class="filter-bar">
          <label>Show:</label>
          <select name="status" onchange="this.form.submit()">
            <option value="">All Tasks</option>
            <option value="In Progress" <?= $filterStatus==='In Progress'?'selected':'' ?>>Pending Only</option>
            <option value="Resolved"    <?= $filterStatus==='Resolved'?'selected':'' ?>>Resolved Only</option>
          </select>
          <?php if ($filterStatus): ?>
            <a href="staff_dashboard.php" class="btn btn-outline btn-sm">Clear</a>
          <?php endif; ?>
        </div>
      </form>

      <?php if (empty($filtered)): ?>
        <div class="empty-state">
          <div class="empty-icon">✅</div>
          <p><?= $filterStatus ? 'No tasks match this filter.' : 'No tasks have been assigned to you yet.' ?></p>
        </div>
      <?php else: ?>
        <?php foreach ($filtered as $c): ?>
          <?php $priClass = 'pri-' . strtolower($c['priority']); ?>
          <div class="task-card <?= $priClass ?>">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;">
              <div style="flex:1;">
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:6px;">
                  <span style="font-weight:bold;color:var(--primary);font-size:13px;"><?= htmlspecialchars($c['id']) ?></span>
                  <?= priorityBadge($c['priority']) ?>
                  <?= statusBadge($c['status']) ?>
                  <span style="font-size:11px;color:var(--text-muted);background:var(--surface-2);padding:2px 8px;border-radius:4px;"><?= htmlspecialchars($c['category']) ?></span>
                </div>
                <h3 style="font-size:15px;color:var(--text);margin-bottom:6px;font-weight:bold;"><?= htmlspecialchars($c['title']) ?></h3>
                <p style="font-size:13px;color:var(--text-muted);line-height:1.6;">
                  <?= htmlspecialchars(strlen($c['description']) > 160 ? substr($c['description'],0,160).'...' : $c['description']) ?>
                </p>
                <p style="font-size:12px;color:var(--text-muted);margin-top:8px;">
                  Reported by <strong><?= htmlspecialchars($c['student_name']) ?></strong> on <?= substr($c['submitted_at'],0,10) ?>
                  &nbsp;|&nbsp; Time taken: <span class="time-chip"><?= resolutionTime($c['submitted_at'], $c['resolved_at']) ?></span>
                </p>
                <?php if ($c['status']==='Resolved' && $c['resolution_note']): ?>
                  <div class="resolution-box" style="margin-top:10px;">
                    <div class="detail-label" style="color:#var(--success-bg);">✅ Your Resolution Note</div>
                    <p style="font-size:13px;color:#var(--success-bg);"><?= nl2br(htmlspecialchars($c['resolution_note'])) ?></p>
                    <?php if ($c['resolved_at']): ?>
                      <p style="font-size:12px;color:#var(--success-bg);margin-top:4px;">Resolved on: <?= htmlspecialchars($c['resolved_at']) ?></p>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
              </div>
              <?php if ($c['status'] === 'In Progress'): ?>
                <div style="flex-shrink:0;">
                  <button class="btn btn-success btn-sm"
                    onclick="openResolve('<?= htmlspecialchars($c['id'],ENT_QUOTES) ?>')">
                    Mark Resolved ✓
                  </button>
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Resolve Modal -->
  <div class="modal-overlay" id="resolveModal">
    <div class="modal">
      <div class="modal-hd">
        <h3>Mark as Resolved</h3>
        <button class="modal-close" onclick="closeModal('resolveModal')">×</button>
      </div>
      <form method="POST" action="staff_dashboard.php">
        <input type="hidden" name="complaint_id" id="resolveId">
        <div class="form-group">
          <label>Resolution Note *</label>
          <textarea name="resolution_note"
                    placeholder="Describe exactly what you did to fix this issue..."
                    required></textarea>
        </div>
        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:4px;">
          <button type="button" class="btn btn-outline" onclick="closeModal('resolveModal')">Cancel</button>
          <button type="submit" class="btn btn-success">Submit Resolution ✓</button>
        </div>
      </form>
    </div>
  </div>

  <script>
  function openResolve(id) {
    document.getElementById('resolveId').value = id;
    document.getElementById('resolveModal').classList.add('open');
  }
  function closeModal(id) { document.getElementById(id).classList.remove('open'); }
  document.querySelectorAll('.modal-overlay').forEach(function(el) {
    el.addEventListener('click', function(e) { if (e.target === el) el.classList.remove('open'); });
  });
  function toggleNotif() { document.getElementById('notifBox').classList.toggle('open'); }
  document.addEventListener('click', function(e) {
    var wrap = document.querySelector('.notif-wrap');
    if (wrap && !wrap.contains(e.target)) document.getElementById('notifBox').classList.remove('open');
  });
  </script>
</body>
</html>

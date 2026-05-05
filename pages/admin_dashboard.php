<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); exit;
}
require_once 'helpers.php';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $all      = loadComplaints();
    $targetId = $_POST['complaint_id'] ?? '';
    $action   = $_POST['action'] ?? '';

    foreach ($all as &$c) {
        if ($c['id'] !== $targetId) continue;
        if ($action === 'assign') {
            $c['assigned_to'] = trim($_POST['assigned_to'] ?? '');
            $c['status']      = 'In Progress';
        } elseif ($action === 'resolve') {
            $c['status']          = 'Resolved';
            $c['resolution_note'] = trim($_POST['resolution_note'] ?? '');
            $c['resolved_at']     = date('Y-m-d H:i:s');
        } elseif ($action === 'reopen') {
            $c['status']          = 'Submitted';
            $c['assigned_to']     = '';
            $c['resolution_note'] = '';
            $c['resolved_at']     = '';
        }
        break;
    }
    unset($c);
    saveComplaints($all);
    header("Location: admin_dashboard.php"); exit;
}

$all = loadComplaints();
usort($all, fn($a,$b) => strcmp($b['submitted_at'], $a['submitted_at']));

$total      = count($all);
$submitted  = count(array_filter($all, fn($c) => $c['status'] === 'Submitted'));
$inProgress = count(array_filter($all, fn($c) => $c['status'] === 'In Progress'));
$resolved   = count(array_filter($all, fn($c) => $c['status'] === 'Resolved'));

$filterStatus   = $_GET['status']   ?? '';
$filterCategory = $_GET['category'] ?? '';
$filtered = $all;
if ($filterStatus)   $filtered = array_values(array_filter($filtered, fn($c) => $c['status']   === $filterStatus));
if ($filterCategory) $filtered = array_values(array_filter($filtered, fn($c) => $c['category'] === $filterCategory));

$notifs   = getNotifications($all, $_SESSION['username'], 'admin');
$initials = strtoupper(substr($_SESSION['fullname'], 0, 1));

// Load staff list for assign dropdown
$users = json_decode(file_get_contents(__DIR__ . '/../data/users.json'), true) ?? [];
$staff = array_values(array_filter($users, fn($u) => $u['role'] === 'staff'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Dashboard — Smart Campus</title>
  <link rel="stylesheet" href="../style.css"/>
  <style>
    .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:200; align-items:center; justify-content:center; }
    .modal-overlay.open { display:flex; }
    .modal { background:var(--surface); border-radius:var(--radius-lg); padding:32px; width:100%; max-width:480px; box-shadow:var(--shadow-md); }
    .modal h3 { font-family:'Playfair Display',serif; font-size:18px; color:var(--primary); margin-bottom:18px; }
    .filter-bar { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:20px; align-items:center; }
    .filter-bar select { padding:8px 12px; border:1.5px solid var(--border); border-radius:var(--radius); font-family:'DM Sans',sans-serif; font-size:13px; color:var(--text); background:var(--surface); cursor:pointer; }
    .filter-bar label { font-size:13px; font-weight:600; color:var(--text-muted); }
    .action-btns { display:flex; gap:6px; flex-wrap:wrap; }
  </style>
</head>
<body>

  <header class="navbar">
    <div class="brand">Smart<span>Campus</span> <span style="font-size:12px;background:var(--accent);color:var(--primary);padding:2px 8px;border-radius:4px;font-family:'DM Sans',sans-serif;font-weight:700;margin-left:8px;">ADMIN</span></div>
    <nav>
      <a href="admin_dashboard.php" class="active">All Complaints</a>
    </nav>
    <div class="user-badge">
      <div class="notif-wrap">
        <button class="notif-bell" onclick="toggleNotif()">
          🔔<?php if (count($notifs)): ?><span class="notif-count"><?= count($notifs) ?></span><?php endif; ?>
        </button>
        <div class="notif-dropdown" id="notifBox">
          <div class="notif-hd">🔔 Notifications</div>
          <?php if (empty($notifs)): ?>
            <div class="notif-empty">No new notifications.</div>
          <?php else: foreach ($notifs as $n): ?>
            <div class="notif-item unread"><?= htmlspecialchars($n['msg']) ?><div class="notif-time"><?= htmlspecialchars($n['time']) ?></div></div>
          <?php endforeach; endif; ?>
        </div>
      </div>
      <div class="avatar"><?= $initials ?></div>
      <span><?= htmlspecialchars($_SESSION['fullname']) ?></span>
      <span class="role-tag">Admin</span>
      <a href="logout.php" class="logout-btn">Logout</a>
    </div>
  </header>

  <div class="page-wrapper">
    <div class="page-header">
      <h1>Admin Dashboard</h1>
      <p>Manage and assign all campus complaints from a single view.</p>
    </div>

    <div class="stats-grid">
      <div class="stat-card accent-card">
        <span class="stat-label">Total Complaints</span>
        <span class="stat-value"><?= $total ?></span>
        <span class="stat-sub">All time</span>
      </div>
      <div class="stat-card info-card">
        <span class="stat-label">Needs Assignment</span>
        <span class="stat-value"><?= $submitted ?></span>
        <span class="stat-sub">Awaiting action</span>
      </div>
      <div class="stat-card warning-card">
        <span class="stat-label">In Progress</span>
        <span class="stat-value"><?= $inProgress ?></span>
        <span class="stat-sub">Being resolved</span>
      </div>
      <div class="stat-card success-card">
        <span class="stat-label">Resolved</span>
        <span class="stat-value"><?= $resolved ?></span>
        <span class="stat-sub">Completed</span>
      </div>
    </div>

    <div class="card">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--border);">
        <div class="card-title" style="margin:0;padding:0;border:none;">All Complaints</div>
        <span style="font-size:13px;color:var(--text-muted);"><?= count($filtered) ?> shown</span>
      </div>

      <form method="GET" action="admin_dashboard.php">
        <div class="filter-bar">
          <label>Filter:</label>
          <select name="status" onchange="this.form.submit()">
            <option value="">All Statuses</option>
            <?php foreach (['Submitted','In Progress','Resolved'] as $s): ?>
              <option value="<?= $s ?>" <?= $filterStatus===$s?'selected':'' ?>><?= $s ?></option>
            <?php endforeach; ?>
          </select>
          <select name="category" onchange="this.form.submit()">
            <option value="">All Categories</option>
            <?php foreach (['Classroom','Hostel','Lab','Security','Cafeteria','Library'] as $cat): ?>
              <option value="<?= $cat ?>" <?= $filterCategory===$cat?'selected':'' ?>><?= $cat ?></option>
            <?php endforeach; ?>
          </select>
          <?php if ($filterStatus || $filterCategory): ?>
            <a href="admin_dashboard.php" class="btn btn-outline btn-sm">Clear</a>
          <?php endif; ?>
        </div>
      </form>

      <?php if (empty($filtered)): ?>
        <div class="empty-state"><div class="empty-icon">📭</div><p>No complaints match the selected filters.</p></div>
      <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>ID</th><th>Student</th><th>Title</th><th>Category</th>
                <th>Priority</th><th>Status</th><th>Submitted</th><th>Time</th><th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($filtered as $i => $c): ?>
              <tr class="clickable-row" onclick="toggleRow(<?= $i ?>)">
                <td style="font-weight:bold;color:var(--primary);"><?= htmlspecialchars($c['id']) ?></td>
                <td style="font-size:13px;"><?= htmlspecialchars($c['student_name']) ?></td>
                <td>
                  <div style="font-weight:bold;"><?= htmlspecialchars($c['title']) ?></div>
                  <?php if ($c['assigned_to']): ?>
                    <div style="font-size:12px;color:var(--text-muted);">→ <?= htmlspecialchars($c['assigned_to']) ?></div>
                  <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($c['category']) ?></td>
                <td><?= priorityBadge($c['priority']) ?></td>
                <td><?= statusBadge($c['status']) ?></td>
                <td style="font-size:13px;color:var(--text-muted);"><?= substr($c['submitted_at'],0,10) ?></td>
                <td><span class="time-chip"><?= resolutionTime($c['submitted_at'], $c['resolved_at']) ?></span></td>
                <td onclick="event.stopPropagation()">
                  <div style="display:flex;gap:5px;flex-wrap:wrap;">
                    <?php if ($c['status'] !== 'Resolved'): ?>
                      <button class="btn btn-primary btn-sm"
                        onclick="openAssign('<?= htmlspecialchars($c['id'],ENT_QUOTES) ?>')">Assign</button>
                    <?php endif; ?>
                    <?php if ($c['status'] === 'In Progress'): ?>
                      <button class="btn btn-success btn-sm"
                        onclick="openResolve('<?= htmlspecialchars($c['id'],ENT_QUOTES) ?>')">Resolve</button>
                    <?php endif; ?>
                    <?php if ($c['status'] === 'Resolved'): ?>
                      <form method="POST" onsubmit="return confirm('Reopen this complaint?')" style="display:inline;">
                        <input type="hidden" name="complaint_id" value="<?= htmlspecialchars($c['id']) ?>">
                        <input type="hidden" name="action" value="reopen">
                        <button type="submit" class="btn btn-outline btn-sm">Reopen</button>
                      </form>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
              <tr class="detail-row" id="detail-<?= $i ?>">
                <td colspan="9">
                  <div class="detail-inner">
                    <div class="detail-label">Description</div>
                    <p><?= nl2br(htmlspecialchars($c['description'])) ?></p>
                    <?php if ($c['status']==='Resolved' && $c['resolution_note']): ?>
                      <div class="resolution-box">
                        <div class="detail-label" style="color:var(--success);">✅ Resolution Note</div>
                        <p style="color:var(--success);"><?= nl2br(htmlspecialchars($c['resolution_note'])) ?></p>
                        <?php if ($c['resolved_at']): ?>
                          <p style="font-size:12px;color:var(--success);margin-top:4px;">Resolved: <?= htmlspecialchars($c['resolved_at']) ?></p>
                        <?php endif; ?>
                      </div>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Assign Modal -->
  <div class="modal-overlay" id="assignModal">
    <div class="modal">
      <div class="modal-hd">
        <h3>Assign Complaint</h3>
        <button class="modal-close" onclick="closeModal('assignModal')">×</button>
      </div>
      <form method="POST" action="admin_dashboard.php">
        <input type="hidden" name="action" value="assign">
        <input type="hidden" name="complaint_id" id="assignId">
        <div class="form-group">
          <label>Assign to Staff Member</label>
          <select name="assigned_to" required>
            <?php foreach ($staff as $s): ?>
              <option value="<?= htmlspecialchars($s['username']) ?>">
                <?= htmlspecialchars($s['fullname']) ?> (<?= htmlspecialchars($s['username']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:4px;">
          <button type="button" class="btn btn-outline" onclick="closeModal('assignModal')">Cancel</button>
          <button type="submit" class="btn btn-primary">Assign & Set In Progress →</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Resolve Modal -->
  <div class="modal-overlay" id="resolveModal">
    <div class="modal">
      <div class="modal-hd">
        <h3>Mark as Resolved</h3>
        <button class="modal-close" onclick="closeModal('resolveModal')">×</button>
      </div>
      <form method="POST" action="admin_dashboard.php">
        <input type="hidden" name="action" value="resolve">
        <input type="hidden" name="complaint_id" id="resolveId">
        <div class="form-group">
          <label>Resolution Note *</label>
          <textarea name="resolution_note" placeholder="Describe what was done to resolve this issue..." required></textarea>
        </div>
        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:4px;">
          <button type="button" class="btn btn-outline" onclick="closeModal('resolveModal')">Cancel</button>
          <button type="submit" class="btn btn-success">Mark Resolved ✓</button>
        </div>
      </form>
    </div>
  </div>

  <script>
  function toggleRow(i) {
    var row = document.getElementById('detail-'+i);
    row.classList.toggle('open');
  }
  function openAssign(id) {
    document.getElementById('assignId').value = id;
    document.getElementById('assignModal').classList.add('open');
  }
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

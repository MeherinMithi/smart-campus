<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php"); exit;
}
require_once 'helpers.php';

$all  = loadComplaints();
$mine = array_values(array_filter($all, fn($c) => $c['student_username'] === $_SESSION['username']));
usort($mine, fn($a,$b) => strcmp($b['submitted_at'], $a['submitted_at']));

$filterStatus   = $_GET['status']   ?? '';
$filterCategory = $_GET['category'] ?? '';
$filtered = $mine;
if ($filterStatus)   $filtered = array_values(array_filter($filtered, fn($c) => $c['status']   === $filterStatus));
if ($filterCategory) $filtered = array_values(array_filter($filtered, fn($c) => $c['category'] === $filterCategory));

$notifs   = getNotifications($all, $_SESSION['username'], 'student');
$initials = strtoupper(substr($_SESSION['fullname'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Complaints — Smart Campus</title>
  <link rel="stylesheet" href="../style.css"/>
</head>
<body>

  <header class="navbar">
    <div class="brand">Smart<span>Campus</span></div>
    <nav>
      <a href="student_dashboard.php">Dashboard</a>
      <a href="submit_complaint.php">Submit Complaint</a>
      <a href="my_complaints.php" class="active">My Complaints</a>
    </nav>
    <div class="user-badge">
      <div class="notif-wrap">
        <button class="notif-bell" onclick="toggleNotif()">
          🔔<?php if (count($notifs)): ?><span class="notif-count"><?= count($notifs) ?></span><?php endif; ?>
        </button>
        <div class="notif-dropdown" id="notifBox">
          <div class="notif-hd">🔔 Notifications</div>
          <?php if (empty($notifs)): ?>
            <div class="notif-empty">No notifications yet.</div>
          <?php else: foreach ($notifs as $n): ?>
            <div class="notif-item unread"><?= htmlspecialchars($n['msg']) ?><div class="notif-time"><?= htmlspecialchars($n['time']) ?></div></div>
          <?php endforeach; endif; ?>
        </div>
      </div>
      <div class="avatar"><?= $initials ?></div>
      <span><?= htmlspecialchars($_SESSION['fullname']) ?></span>
      <span class="role-tag">Student</span>
      <a href="logout.php" class="logout-btn">Logout</a>
    </div>
  </header>

  <div class="page-wrapper">
    <div class="page-header" style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:10px;">
      <div>
        <h1>My Complaints</h1>
        <p>Track all your submitted complaints and their current status.</p>
      </div>
      <a href="submit_complaint.php" class="btn btn-primary">+ New Complaint</a>
    </div>

    <div class="card">
      <!-- Filters -->
      <form method="GET" action="my_complaints.php">
        <div class="filter-bar">
          <label>Filter by:</label>
          <select name="status" onchange="this.form.submit()">
            <option value="">All Statuses</option>
            <?php foreach (['Submitted','In Progress','Resolved'] as $s): ?>
              <option value="<?= $s ?>" <?= $filterStatus===$s?'selected':'' ?>><?= $s ?></option>
            <?php endforeach; ?>
          </select>
          <select name="category" onchange="this.form.submit()">
            <option value="">All Categories</option>
            <?php foreach (['Classroom','Hostel','Lab','Security','Cafeteria','Library'] as $c): ?>
              <option value="<?= $c ?>" <?= $filterCategory===$c?'selected':'' ?>><?= $c ?></option>
            <?php endforeach; ?>
          </select>
          <?php if ($filterStatus || $filterCategory): ?>
            <a href="my_complaints.php" class="btn btn-outline btn-sm">Clear</a>
          <?php endif; ?>
          <span style="margin-left:auto;font-size:13px;color:#a01055;"><?= count($filtered) ?> complaint<?= count($filtered)!==1?'s':'' ?></span>
        </div>
      </form>

      <?php if (empty($filtered)): ?>
        <div class="empty-state">
          <div class="empty-icon">📭</div>
          <p><?= ($filterStatus||$filterCategory) ? 'No complaints match your filters.' : "You haven't submitted any complaints yet." ?></p>
          <a href="submit_complaint.php" class="btn btn-primary" style="margin-top:14px;display:inline-flex;">Submit a Complaint</a>
        </div>
      <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>ID</th><th>Title</th><th>Category</th><th>Priority</th>
                <th>Status</th><th>Submitted</th><th>Time to Resolve</th><th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($filtered as $i => $c): ?>
              <tr class="clickable-row" onclick="toggleRow(<?= $i ?>)">
                <td style="font-weight:bold;color:var(--primary);"><?= htmlspecialchars($c['id']) ?></td>
                <td style="font-weight:bold;"><?= htmlspecialchars($c['title']) ?></td>
                <td><?= htmlspecialchars($c['category']) ?></td>
                <td><?= priorityBadge($c['priority']) ?></td>
                <td><?= statusBadge($c['status']) ?></td>
                <td style="color:var(--text-muted);font-size:13px;"><?= substr($c['submitted_at'],0,10) ?></td>
                <td><span class="time-chip"><?= resolutionTime($c['submitted_at'], $c['resolved_at']) ?></span></td>
                <td style="color:var(--text-muted);font-size:18px;" id="arr-<?= $i ?>">▾</td>
              </tr>
              <tr class="detail-row" id="detail-<?= $i ?>">
                <td colspan="8">
                  <div class="detail-inner">
                    <div class="detail-label">Description</div>
                    <p><?= nl2br(htmlspecialchars($c['description'])) ?></p>
                    <?php if ($c['assigned_to']): ?>
                      <p style="margin-top:8px;font-size:13px;color:var(--text-muted);">🔧 Assigned to: <strong><?= htmlspecialchars($c['assigned_to']) ?></strong></p>
                    <?php endif; ?>
                    <?php if ($c['status']==='Resolved' && $c['resolution_note']): ?>
                      <div class="resolution-box">
                        <div class="detail-label" style="color:var(--success);">✅ Resolution Note</div>
                        <p style="color:var(--success);"><?= nl2br(htmlspecialchars($c['resolution_note'])) ?></p>
                        <?php if ($c['resolved_at']): ?>
                          <p style="font-size:12px;color:var(--success);margin-top:4px;">Resolved on: <?= htmlspecialchars($c['resolved_at']) ?></p>
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

  <script>
  function toggleRow(i) {
    var row = document.getElementById('detail-'+i);
    var arr = document.getElementById('arr-'+i);
    var open = row.classList.toggle('open');
    arr.textContent = open ? '▴' : '▾';
  }
  function toggleNotif() { document.getElementById('notifBox').classList.toggle('open'); }
  document.addEventListener('click', function(e) {
    var wrap = document.querySelector('.notif-wrap');
    if (wrap && !wrap.contains(e.target)) document.getElementById('notifBox').classList.remove('open');
  });
  </script>
</body>
</html>

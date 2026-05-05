<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php"); exit;
}
require_once 'helpers.php';

$all   = loadComplaints();
$mine  = array_values(array_filter($all, fn($c) => $c['student_username'] === $_SESSION['username']));
usort($mine, fn($a,$b) => strcmp($b['submitted_at'], $a['submitted_at']));

$total      = count($mine);
$submitted  = count(array_filter($mine, fn($c) => $c['status'] === 'Submitted'));
$inProgress = count(array_filter($mine, fn($c) => $c['status'] === 'In Progress'));
$resolved   = count(array_filter($mine, fn($c) => $c['status'] === 'Resolved'));
$recent     = array_slice($mine, 0, 5);

$notifs  = getNotifications($all, $_SESSION['username'], 'student');
$initials = strtoupper(substr($_SESSION['fullname'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard — Smart Campus</title>
  <link rel="stylesheet" href="../style.css"/>
</head>
<body>

<header class="navbar">
  <div class="brand">Smart<span>Campus</span></div>
  <nav>
    <a href="student_dashboard.php" class="active">Dashboard</a>
    <a href="submit_complaint.php">Submit Complaint</a>
    <a href="my_complaints.php">My Complaints</a>
  </nav>
  <div class="user-badge">
    <div class="notif-wrap">
      <button class="notif-bell" onclick="toggleNotif()" title="Notifications">
        🔔<?php if (count($notifs)): ?><span class="notif-count"><?= count($notifs) ?></span><?php endif; ?>
      </button>
      <div class="notif-dropdown" id="notifBox">
        <div class="notif-hd">🔔 Notifications</div>
        <?php if (empty($notifs)): ?>
          <div class="notif-empty">No notifications yet.</div>
        <?php else: foreach ($notifs as $n): ?>
          <div class="notif-item unread">
            <?= htmlspecialchars($n['msg']) ?>
            <div class="notif-time"><?= htmlspecialchars($n['time']) ?></div>
          </div>
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
  <div class="page-header">
    <h1>Welcome back, <?= htmlspecialchars(explode(' ', $_SESSION['fullname'])[0]) ?> 👋</h1>
    <p>Here's a summary of your submitted complaints and their current statuses.</p>
  </div>

  <div class="stats-grid">
    <div class="stat-card accent-card">
      <span class="stat-label">Total Submitted</span>
      <span class="stat-value"><?= $total ?></span>
      <span class="stat-sub">All time</span>
    </div>
    <div class="stat-card info-card">
      <span class="stat-label">Pending Review</span>
      <span class="stat-value"><?= $submitted ?></span>
      <span class="stat-sub">Awaiting admin</span>
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
      <div class="card-title" style="margin:0;padding:0;border:none;">Recent Complaints</div>
      <div style="display:flex;gap:8px;">
        <a href="my_complaints.php" class="btn btn-outline btn-sm">View All</a>
        <a href="submit_complaint.php" class="btn btn-primary btn-sm">+ New</a>
      </div>
    </div>

    <?php if (empty($recent)): ?>
      <div class="empty-state">
        <div class="empty-icon">📭</div>
        <p>You haven't submitted any complaints yet.</p>
        <a href="submit_complaint.php" class="btn btn-primary" style="margin-top:14px;display:inline-flex;">Submit Your First Complaint</a>
      </div>
    <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>ID</th><th>Title</th><th>Category</th><th>Priority</th><th>Status</th><th>Submitted</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recent as $c): ?>
            <tr>
              <td style="font-weight:bold;color:var(--primary);"><?= htmlspecialchars($c['id']) ?></td>
              <td><?= htmlspecialchars($c['title']) ?></td>
              <td><?= htmlspecialchars($c['category']) ?></td>
              <td><?= priorityBadge($c['priority']) ?></td>
              <td><?= statusBadge($c['status']) ?></td>
              <td style="color:var(--text-muted);font-size:13px;"><?= substr($c['submitted_at'],0,10) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
function toggleNotif() {
  document.getElementById('notifBox').classList.toggle('open');
}
document.addEventListener('click', function(e) {
  var wrap = document.querySelector('.notif-wrap');
  if (wrap && !wrap.contains(e.target)) document.getElementById('notifBox').classList.remove('open');
});
</script>
</body>
</html>

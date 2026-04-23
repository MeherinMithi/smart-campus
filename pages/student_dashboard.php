<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

// Load complaints for this student
$jsonPath  = __DIR__ . '/../data/complaints.json';
$jsonData  = file_get_contents($jsonPath);
$allComplaints = json_decode($jsonData, true) ?? [];

$myComplaints = array_filter($allComplaints, fn($c) => $c['student_username'] === $_SESSION['username']);

$total      = count($myComplaints);
$submitted  = count(array_filter($myComplaints, fn($c) => $c['status'] === 'Submitted'));
$inProgress = count(array_filter($myComplaints, fn($c) => $c['status'] === 'In Progress'));
$resolved   = count(array_filter($myComplaints, fn($c) => $c['status'] === 'Resolved'));

function statusBadge($status) {
    $map = ['Submitted' => 'submitted', 'In Progress' => 'inprogress', 'Resolved' => 'resolved'];
    $cls = $map[$status] ?? 'submitted';
    return "<span class='badge badge-{$cls}'>{$status}</span>";
}
function priorityBadge($priority) {
    $map = ['High' => 'high', 'Medium' => 'medium', 'Low' => 'low'];
    $cls = $map[$priority] ?? 'low';
    return "<span class='badge badge-{$cls}'>{$priority}</span>";
}

$initials = strtoupper(substr($_SESSION['fullname'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Dashboard — Smart Campus</title>
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
      <div class="avatar"><?= $initials ?></div>
      <span><?= htmlspecialchars($_SESSION['fullname']) ?></span>
      <a href="logout.php" class="logout-btn">Logout</a>
    </div>
  </header>

  <div class="page-wrapper">
    <div class="page-header">
      <h1>Welcome back, <?= htmlspecialchars(explode(' ', $_SESSION['fullname'])[0]) ?> 👋</h1>
      <p>Here's a summary of your submitted complaints and their current statuses.</p>
    </div>

    <!-- Stats -->
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

    <!-- Recent Complaints -->
    <div class="card">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;padding-bottom:14px;border-bottom:1px solid var(--border);">
        <h2 class="card-title" style="margin:0;padding:0;border:none;">Recent Complaints</h2>
        <div style="display:flex;gap:10px;">
          <a href="my_complaints.php" class="btn btn-outline btn-sm">View All</a>
          <a href="submit_complaint.php" class="btn btn-primary btn-sm">+ New Complaint</a>
        </div>
      </div>

      <?php
      $recent = array_slice(array_values($myComplaints), 0, 5);
      if (empty($recent)):
      ?>
        <div class="empty-state">
          <div class="empty-icon">📭</div>
          <p>You haven't submitted any complaints yet.</p>
          <a href="submit_complaint.php" class="btn btn-primary" style="margin-top:16px;display:inline-flex;">Submit Your First Complaint</a>
        </div>
      <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Category</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Submitted</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recent as $c): ?>
              <tr>
                <td style="font-weight:600;color:var(--primary);"><?= htmlspecialchars($c['id']) ?></td>
                <td><?= htmlspecialchars($c['title']) ?></td>
                <td><?= htmlspecialchars($c['category']) ?></td>
                <td><?= priorityBadge($c['priority']) ?></td>
                <td><?= statusBadge($c['status']) ?></td>
                <td style="color:var(--text-muted);font-size:13px;"><?= htmlspecialchars(substr($c['submitted_at'], 0, 10)) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

  </div>
</body>
</html>

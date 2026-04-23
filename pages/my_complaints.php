<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$jsonPath  = __DIR__ . '/../data/complaints.json';
$allComplaints = json_decode(file_get_contents($jsonPath), true) ?? [];
$myComplaints  = array_values(array_filter($allComplaints, fn($c) => $c['student_username'] === $_SESSION['username']));

// Sort by submitted_at descending
usort($myComplaints, fn($a, $b) => strcmp($b['submitted_at'], $a['submitted_at']));

// Filter
$filterStatus   = $_GET['status'] ?? '';
$filterCategory = $_GET['category'] ?? '';
$filtered = $myComplaints;
if ($filterStatus)   $filtered = array_values(array_filter($filtered, fn($c) => $c['status'] === $filterStatus));
if ($filterCategory) $filtered = array_values(array_filter($filtered, fn($c) => $c['category'] === $filterCategory));

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
  <title>My Complaints — Smart Campus</title>
  <link rel="stylesheet" href="../style.css"/>
  <style>
    .detail-panel {
      display: none;
      background: var(--surface-2);
      border-top: 1px solid var(--border);
    }
    .detail-panel.open { display: table-row; }
    .detail-inner { padding: 18px 20px; }
    .detail-inner p { font-size: 14px; line-height: 1.7; color: var(--text); }
    .detail-inner .label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: var(--text-muted); margin-bottom: 4px; }
    .detail-inner .resolution-box {
      background: var(--success-bg);
      border: 1px solid #a8d8c2;
      border-radius: var(--radius);
      padding: 12px 16px;
      margin-top: 12px;
    }
    .filter-bar { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:20px; align-items:center; }
    .filter-bar select {
      padding: 8px 12px;
      border: 1.5px solid var(--border);
      border-radius: var(--radius);
      font-family: 'DM Sans', sans-serif;
      font-size: 13px;
      color: var(--text);
      background: var(--surface);
      cursor: pointer;
    }
    .filter-bar label { font-size:13px; font-weight:600; color:var(--text-muted); }
    tr.clickable-row { cursor: pointer; }
    tr.clickable-row:hover td { background: var(--surface-2); }
  </style>
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
      <div class="avatar"><?= $initials ?></div>
      <span><?= htmlspecialchars($_SESSION['fullname']) ?></span>
      <a href="logout.php" class="logout-btn">Logout</a>
    </div>
  </header>

  <div class="page-wrapper">
    <div class="page-header" style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;">
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
              <option value="<?= $s ?>" <?= $filterStatus === $s ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
          </select>
          <select name="category" onchange="this.form.submit()">
            <option value="">All Categories</option>
            <?php foreach (['Classroom','Hostel','Lab','Security','Cafeteria','Library'] as $cat): ?>
              <option value="<?= $cat ?>" <?= $filterCategory === $cat ? 'selected' : '' ?>><?= $cat ?></option>
            <?php endforeach; ?>
          </select>
          <?php if ($filterStatus || $filterCategory): ?>
            <a href="my_complaints.php" class="btn btn-outline btn-sm">Clear Filters</a>
          <?php endif; ?>
          <span style="margin-left:auto;font-size:13px;color:var(--text-muted);"><?= count($filtered) ?> complaint<?= count($filtered) !== 1 ? 's' : '' ?></span>
        </div>
      </form>

      <?php if (empty($filtered)): ?>
        <div class="empty-state">
          <div class="empty-icon">📭</div>
          <p><?= ($filterStatus || $filterCategory) ? 'No complaints match your filters.' : "You haven't submitted any complaints yet." ?></p>
          <a href="submit_complaint.php" class="btn btn-primary" style="margin-top:16px;display:inline-flex;">Submit a Complaint</a>
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
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($filtered as $i => $c): ?>
              <tr class="clickable-row" onclick="toggleDetail('detail-<?= $i ?>')">
                <td style="font-weight:600;color:var(--primary);"><?= htmlspecialchars($c['id']) ?></td>
                <td style="font-weight:500;"><?= htmlspecialchars($c['title']) ?></td>
                <td><?= htmlspecialchars($c['category']) ?></td>
                <td><?= priorityBadge($c['priority']) ?></td>
                <td><?= statusBadge($c['status']) ?></td>
                <td style="color:var(--text-muted);font-size:13px;"><?= htmlspecialchars(substr($c['submitted_at'], 0, 10)) ?></td>
                <td style="text-align:right;font-size:18px;color:var(--text-muted);" id="arrow-<?= $i ?>">▾</td>
              </tr>
              <tr class="detail-panel" id="detail-<?= $i ?>">
                <td colspan="7">
                  <div class="detail-inner">
                    <div class="label">Description</div>
                    <p><?= nl2br(htmlspecialchars($c['description'])) ?></p>

                    <?php if ($c['assigned_to']): ?>
                      <p style="margin-top:10px;font-size:13px;color:var(--text-muted);">
                        🔧 Assigned to: <strong><?= htmlspecialchars($c['assigned_to']) ?></strong>
                      </p>
                    <?php endif; ?>

                    <?php if ($c['status'] === 'Resolved' && $c['resolution_note']): ?>
                      <div class="resolution-box">
                        <div class="label" style="color:var(--success);">Resolution Note</div>
                        <p style="color:var(--success);"><?= nl2br(htmlspecialchars($c['resolution_note'])) ?></p>
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
    function toggleDetail(id) {
      const panel = document.getElementById(id);
      const idx   = id.split('-')[1];
      const arrow = document.getElementById('arrow-' + idx);
      const isOpen = panel.classList.contains('open');
      panel.classList.toggle('open');
      arrow.textContent = isOpen ? '▾' : '▴';
    }
  </script>
</body>
</html>

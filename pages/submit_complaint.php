<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php"); exit;
}
require_once 'helpers.php';

$success = $error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category    = trim($_POST['category']    ?? '');
    $title       = trim($_POST['title']       ?? '');
    $description = trim($_POST['description'] ?? '');
    $priority    = trim($_POST['priority']    ?? '');

    $validCats  = ['Classroom','Hostel','Lab','Security','Cafeteria','Library'];
    $validPri   = ['High','Medium','Low'];

    if (!$category || !$title || !$description || !$priority)
        $error = "All fields are required.";
    elseif (!in_array($category, $validCats) || !in_array($priority, $validPri))
        $error = "Invalid category or priority.";
    elseif (strlen($title) < 5)
        $error = "Title must be at least 5 characters.";
    elseif (strlen($description) < 10)
        $error = "Description must be at least 10 characters.";
    else {
        $all   = loadComplaints();
        $newId = nextComplaintId($all);
        $all[] = [
            'id'               => $newId,
            'student_username' => $_SESSION['username'],
            'student_name'     => $_SESSION['fullname'],
            'category'         => $category,
            'title'            => $title,
            'description'      => $description,
            'priority'         => $priority,
            'status'           => 'Submitted',
            'submitted_at'     => date('Y-m-d H:i:s'),
            'assigned_to'      => '',
            'resolution_note'  => '',
            'resolved_at'      => '',
        ];
        saveComplaints($all);
        $success = "Complaint #{$newId} submitted successfully! Track it in My Complaints.";
    }
}

$initials = strtoupper(substr($_SESSION['fullname'], 0, 1));
$all      = loadComplaints();
$notifs   = getNotifications($all, $_SESSION['username'], 'student');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Submit Complaint — Smart Campus</title>
  <link rel="stylesheet" href="../style.css"/>
</head>
<body>

  <header class="navbar">
    <div class="brand">Smart<span>Campus</span></div>
    <nav>
      <a href="student_dashboard.php">Dashboard</a>
      <a href="submit_complaint.php" class="active">Submit Complaint</a>
      <a href="my_complaints.php">My Complaints</a>
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

  <div class="page-wrapper" style="max-width:700px;">
    <div class="page-header">
      <h1>Submit a Complaint</h1>
      <p>Fill in the details below and we'll get it reviewed as soon as possible.</p>
    </div>

    <?php if ($success): ?>
      <div class="alert alert-success">
        ✅ <?= htmlspecialchars($success) ?>
        <a href="my_complaints.php" style="margin-left:auto;font-weight:bold;color:var(--success);">View My Complaints →</a>
      </div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card">
      <div class="card-title">Complaint Details</div>
      <form method="POST" action="submit_complaint.php">
        <div class="form-row">
          <div class="form-group">
            <label for="category">Category *</label>
            <select id="category" name="category" required>
              <option value="" disabled <?= empty($_POST['category']) ? 'selected' : '' ?>>Select a category</option>
              <?php foreach (['Classroom','Hostel','Lab','Security','Cafeteria','Library'] as $cat): ?>
                <option value="<?= $cat ?>" <?= (($_POST['category'] ?? '') === $cat) ? 'selected' : '' ?>><?= $cat ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label for="priority">Priority *</label>
            <select id="priority" name="priority" required>
              <option value="" disabled <?= empty($_POST['priority']) ? 'selected' : '' ?>>Select priority</option>
              <option value="High"   <?= (($_POST['priority'] ?? '') === 'High')   ? 'selected' : '' ?>>🔴 High — Urgent</option>
              <option value="Medium" <?= (($_POST['priority'] ?? '') === 'Medium') ? 'selected' : '' ?>>🟡 Medium — Important</option>
              <option value="Low"    <?= (($_POST['priority'] ?? '') === 'Low')    ? 'selected' : '' ?>>🟢 Low — Minor</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label for="title">Complaint Title *</label>
          <input
            type="text"
            id="title"
            name="title"
            placeholder="e.g. Broken projector in Room 204"
            value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
            maxlength="120"
            required
          />
        </div>

        <div class="form-group">
          <label for="description">Description *</label>
          <textarea
            id="description"
            name="description"
            placeholder="Describe the problem in detail — include the exact location, how long it has been occurring, and how it affects you or other students."
            required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        </div>

        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:4px;">
          <a href="student_dashboard.php" class="btn btn-outline">Cancel</a>
          <button type="submit" class="btn btn-primary">Submit Complaint →</button>
        </div>
      </form>
    </div>

    <div class="card" style="margin-top:16px;background:var(--info-bg);border-color:#b8d4f8;">
      <div style="font-size:14px;font-weight:bold;color:var(--info);margin-bottom:8px;">💡 Tips for a useful complaint</div>
      <ul style="font-size:13px;color:var(--info);line-height:2;padding-left:18px;">
        <li>Include the exact location (room number, block, floor).</li>
        <li>Mention how long the issue has existed.</li>
        <li>Describe how it affects you or other students.</li>
        <li>Use <strong>High</strong> priority only for urgent safety or academic disruptions.</li>
      </ul>
    </div>
  </div>

  <script>
  function toggleNotif() { document.getElementById('notifBox').classList.toggle('open'); }
  document.addEventListener('click', function(e) {
    var wrap = document.querySelector('.notif-wrap');
    if (wrap && !wrap.contains(e.target)) document.getElementById('notifBox').classList.remove('open');
  });
  </script>
</body>
</html>

<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$success = "";
$error   = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category    = trim($_POST['category'] ?? '');
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priority    = trim($_POST['priority'] ?? '');

    $validCategories = ['Classroom','Hostel','Lab','Security','Cafeteria','Library'];
    $validPriorities = ['High','Medium','Low'];

    if (empty($category) || empty($title) || empty($description) || empty($priority)) {
        $error = "All fields are required.";
    } elseif (!in_array($category, $validCategories) || !in_array($priority, $validPriorities)) {
        $error = "Invalid category or priority selected.";
    } elseif (strlen($title) < 5) {
        $error = "Title must be at least 5 characters.";
    } elseif (strlen($description) < 10) {
        $error = "Description must be at least 10 characters.";
    } else {
        // Load existing complaints
        $jsonPath = __DIR__ . '/../data/complaints.json';
        $existing = json_decode(file_get_contents($jsonPath), true) ?? [];

        // Generate new ID
        $lastId = 0;
        foreach ($existing as $c) {
            $num = (int) ltrim($c['id'], 'CMP');
            if ($num > $lastId) $lastId = $num;
        }
        $newId = 'CMP' . str_pad($lastId + 1, 3, '0', STR_PAD_LEFT);

        $newComplaint = [
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
        ];

        $existing[] = $newComplaint;
        file_put_contents($jsonPath, json_encode($existing, JSON_PRETTY_PRINT));
        $success = "Complaint #{$newId} submitted successfully! You can track its status in My Complaints.";
    }
}

$initials = strtoupper(substr($_SESSION['fullname'], 0, 1));
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
      <div class="avatar"><?= $initials ?></div>
      <span><?= htmlspecialchars($_SESSION['fullname']) ?></span>
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
        <a href="my_complaints.php" style="margin-left:auto;font-weight:600;color:var(--success);">View Complaints →</a>
      </div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card">
      <h2 class="card-title">Complaint Details</h2>
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
              <option value="High"   <?= (($_POST['priority'] ?? '') === 'High')   ? 'selected' : '' ?>>🔴 High — Urgent, affecting many</option>
              <option value="Medium" <?= (($_POST['priority'] ?? '') === 'Medium') ? 'selected' : '' ?>>🟡 Medium — Important but not urgent</option>
              <option value="Low"    <?= (($_POST['priority'] ?? '') === 'Low')    ? 'selected' : '' ?>>🟢 Low — Minor inconvenience</option>
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
            placeholder="Describe the problem in detail — location, how long it's been occurring, how it affects you..."
            required
          ><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        </div>

        <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:4px;">
          <a href="student_dashboard.php" class="btn btn-outline">Cancel</a>
          <button type="submit" class="btn btn-primary">Submit Complaint →</button>
        </div>
      </form>
    </div>

    <!-- Tips -->
    <div class="card" style="margin-top:20px;background:var(--info-bg);border-color:#b8d4f8;">
      <h3 style="font-size:14px;font-weight:700;color:var(--info);margin-bottom:10px;">💡 Tips for a good complaint</h3>
      <ul style="font-size:13px;color:var(--info);line-height:2;padding-left:18px;">
        <li>Include the exact location (room number, block, floor).</li>
        <li>Mention how long the issue has existed.</li>
        <li>Describe how it affects students or daily operations.</li>
        <li>Choose <strong>High</strong> priority only for urgent safety or academic disruptions.</li>
      </ul>
    </div>
  </div>
</body>
</html>

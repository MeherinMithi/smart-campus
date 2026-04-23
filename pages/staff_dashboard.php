<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'staff') {
    header("Location: login.php");
    exit;
}

$jsonPath   = __DIR__ . '/../data/complaints.json';
$complaints = json_decode(file_get_contents($jsonPath), true) ?? [];

// Handle resolve action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $targetId = $_POST['complaint_id'] ?? '';
    $note     = trim($_POST['resolution_note'] ?? '');

    foreach ($complaints as &$c) {
        if ($c['id'] === $targetId && $c['assigned_to'] === $_SESSION['username']) {
            $c['status'] = 'Resolved';
            $c['resolution_note'] = $note;
            break;
        }
    }
    unset($c);
    file_put_contents($jsonPath, json_encode($complaints, JSON_PRETTY_PRINT));
    header("Location: staff_dashboard.php");
    exit;
}

// Filter to assigned complaints
$myWork = array_values(array_filter($complaints, fn($c) => $c['assigned_to'] === $_SESSION['username']));
usort($myWork, fn($a,$b) => strcmp($b['submitted_at'], $a['submitted_at']));

$total      = count($myWork);
$inProgress = count(array_filter($myWork, fn($c) => $c['status'] === 'In Progress'));
$resolved   = count(array_filter($myWork, fn($c) => $c['status'] === 'Resolved'));

// Filter
$filterStatus = $_GET['status'] ?? '';
$filtered = $filterStatus ? array_values(array_filter($myWork, fn($c) => $c['status'] === $filterStatus)) : $myWork;

function statusBadge($s) {
    $map = ['Submitted'=>'submitted','In Progress'=>'inprogress','Resolved'=>'resolved'];
    return "<span class='badge badge-".($map[$s]??'submitted')."'>{$s}</span>";
}
function priorityBadge($p) {
    $map = ['High'=>'high','Medium'=>'medium','Low'=>'low'];
    return "<span class='badge badge-".($map[$p]??'low')."'>{$p}</span>";
}

$initials = strtoupper(substr($_SESSION['fullname'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Staff Dashboard — Smart Campus</title>
  <link rel="stylesheet" href="../style.css"/>
  <style>
    .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:200; align-items:center; justify-content:center; }
    .modal-overlay.open { display:flex; }
    .modal { background:var(--surface); border-radius:var(--radius-lg); padding:32px; width:100%; max-width:460px; box-shadow:var(--shadow-md); }
    .modal h3 { font-family:'Playfair Display',serif; font-size:18px; color:var(--primary); margin-bottom:18px; }
    .filter-bar { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:20px; align-items:center; }
    .filter-bar select { padding:8px 12px; border:1.5px solid var(--border); border-radius:var(--radius); font-family:'DM Sans',sans-serif; font-size:13px; background:var(--surface); cursor:pointer; }
    .priority-high { border-left: 4px solid var(--danger); }
    .priority-medium { border-left: 4px solid var(--warning); }
    .priority-low { border-left: 4px solid var(--info); }
  </style>
</head>
<body>

  <header class="navbar">
    <div class="brand">Smart<span>Campus</span> <span style="font-size:12px;background:#2a7a5f;color:#fff;padding:2px 8px;border-radius:4px;font-family:'DM Sans',sans-serif;font-weight:700;margin-left:8px;">STAFF</span></div>
    <nav>
      <a href="staff_dashboard.php" class="active">My Tasks</a>
    </nav>
    <div class="user-badge">
      <div class="avatar"><?= $initials ?></div>
      <span><?= htmlspecialchars($_SESSION['fullname']) ?></span>
      <a href="logout.php" class="logout-btn">Logout</a>
    </div>
  </header>

  <div class="page-wrapper">
    <div class="page-header">
      <h1>My Assigned Tasks</h1>
      <p>Complaints assigned to you for resolution. Prioritize High-priority issues first.</p>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-card accent-card">
        <span class="stat-label">Total Assigned</span>
        <span class="stat-value"><?= $total ?></span>
        <span class="stat-sub">All time</span>
      </div>
      <div class="stat-card warning-card">
        <span class="stat-label">Pending</span>
        <span class="stat-value"><?= $inProgress ?></span>
        <span class="stat-sub">Needs resolution</span>
      </div>
      <div class="stat-card success-card">
        <span class="stat-label">Resolved</span>
        <span class="stat-value"><?= $resolved ?></span>
        <span class="stat-sub">Completed</span>
      </div>
    </div>

    <!-- Tasks -->
    <div class="card">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;padding-bottom:14px;border-bottom:1px solid var(--border);">
        <h2 class="card-title" style="margin:0;padding:0;border:none;">Complaint Tasks</h2>
      </div>

      <!-- Filter -->
      <form method="GET">
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
          <span style="margin-left:auto;font-size:13px;color:var(--text-muted);"><?= count($filtered) ?> task<?= count($filtered)!==1?'s':'' ?></span>
        </div>
      </form>

      <?php if (empty($filtered)): ?>
        <div class="empty-state">
          <div class="empty-icon">✅</div>
          <p><?= $filterStatus ? 'No tasks match this filter.' : 'No tasks have been assigned to you yet.' ?></p>
        </div>
      <?php else: ?>
        <div style="display:grid;gap:14px;">
          <?php foreach ($filtered as $i => $c):
            $priorityClass = 'priority-' . strtolower($c['priority']);
          ?>
          <div class="card <?= $priorityClass ?>" style="margin:0;padding:20px 22px;">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;">
              <div style="flex:1;">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;flex-wrap:wrap;">
                  <span style="font-weight:700;color:var(--primary);font-size:13px;"><?= htmlspecialchars($c['id']) ?></span>
                  <?= priorityBadge($c['priority']) ?>
                  <?= statusBadge($c['status']) ?>
                  <span style="font-size:12px;color:var(--text-muted);background:var(--surface-2);padding:2px 8px;border-radius:4px;"><?= htmlspecialchars($c['category']) ?></span>
                </div>
                <h3 style="font-size:16px;color:var(--text);margin-bottom:6px;font-weight:600;"><?= htmlspecialchars($c['title']) ?></h3>
                <p style="font-size:13px;color:var(--text-muted);line-height:1.6;"><?= htmlspecialchars(substr($c['description'], 0, 160)) ?><?= strlen($c['description']) > 160 ? '...' : '' ?></p>
                <p style="font-size:12px;color:var(--text-muted);margin-top:8px;">
                  Reported by <strong><?= htmlspecialchars($c['student_name']) ?></strong> on <?= htmlspecialchars(substr($c['submitted_at'],0,10)) ?>
                </p>

                <?php if ($c['status'] === 'Resolved' && $c['resolution_note']): ?>
                  <div style="background:var(--success-bg);border-radius:var(--radius);padding:10px 14px;margin-top:10px;">
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.7px;color:var(--success);margin-bottom:4px;">Your Resolution Note</div>
                    <p style="font-size:13px;color:var(--success);"><?= nl2br(htmlspecialchars($c['resolution_note'])) ?></p>
                  </div>
                <?php endif; ?>
              </div>
              <?php if ($c['status'] === 'In Progress'): ?>
              <div style="flex-shrink:0;">
                <button class="btn btn-success btn-sm" onclick="openResolve('<?= htmlspecialchars($c['id']) ?>')">Mark Resolved ✓</button>
              </div>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Resolve Modal -->
  <div class="modal-overlay" id="resolve-modal">
    <div class="modal">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <h3>Mark as Resolved</h3>
        <button onclick="closeModal('resolve-modal')" style="background:none;border:none;font-size:22px;cursor:pointer;color:var(--text-muted);">×</button>
      </div>
      <form method="POST">
        <input type="hidden" name="complaint_id" id="resolve-id">
        <div class="form-group">
          <label>Resolution Note *</label>
          <textarea name="resolution_note" placeholder="Describe exactly what you did to fix this issue..." required></textarea>
        </div>
        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:4px;">
          <button type="button" class="btn btn-outline" onclick="closeModal('resolve-modal')">Cancel</button>
          <button type="submit" class="btn btn-success">Submit Resolution ✓</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function openResolve(id) {
      document.getElementById('resolve-id').value = id;
      document.getElementById('resolve-modal').classList.add('open');
    }
    function closeModal(id) { document.getElementById(id).classList.remove('open'); }
    document.querySelectorAll('.modal-overlay').forEach(el => {
      el.addEventListener('click', e => { if (e.target === el) el.classList.remove('open'); });
    });
  </script>
</body>
</html>

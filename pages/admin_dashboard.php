<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$jsonPath = __DIR__ . '/../data/complaints.json';
$complaints = json_decode(file_get_contents($jsonPath), true) ?? [];
usort($complaints, fn($a,$b) => strcmp($b['submitted_at'], $a['submitted_at']));

// Handle assign & status change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $targetId  = $_POST['complaint_id'] ?? '';
    $action    = $_POST['action'] ?? '';

    foreach ($complaints as &$c) {
        if ($c['id'] !== $targetId) continue;

        if ($action === 'assign') {
            $c['assigned_to'] = trim($_POST['assigned_to'] ?? '');
            $c['status'] = 'In Progress';
        } elseif ($action === 'resolve') {
            $c['status'] = 'Resolved';
            $c['resolution_note'] = trim($_POST['resolution_note'] ?? '');
        } elseif ($action === 'reopen') {
            $c['status'] = 'Submitted';
            $c['assigned_to'] = '';
            $c['resolution_note'] = '';
        }
        break;
    }
    unset($c);
    file_put_contents($jsonPath, json_encode($complaints, JSON_PRETTY_PRINT));
    header("Location: admin_dashboard.php");
    exit;
}

$total      = count($complaints);
$submitted  = count(array_filter($complaints, fn($c) => $c['status'] === 'Submitted'));
$inProgress = count(array_filter($complaints, fn($c) => $c['status'] === 'In Progress'));
$resolved   = count(array_filter($complaints, fn($c) => $c['status'] === 'Resolved'));

// Filter
$filterStatus   = $_GET['status'] ?? '';
$filterCategory = $_GET['category'] ?? '';
$filtered = $complaints;
if ($filterStatus)   $filtered = array_values(array_filter($filtered, fn($c) => $c['status'] === $filterStatus));
if ($filterCategory) $filtered = array_values(array_filter($filtered, fn($c) => $c['category'] === $filterCategory));

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
      <div class="avatar"><?= $initials ?></div>
      <span><?= htmlspecialchars($_SESSION['fullname']) ?></span>
      <a href="logout.php" class="logout-btn">Logout</a>
    </div>
  </header>

  <div class="page-wrapper">
    <div class="page-header">
      <h1>Admin Dashboard</h1>
      <p>Manage and assign all campus complaints from a single view.</p>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-card accent-card">
        <span class="stat-label">Total Complaints</span>
        <span class="stat-value"><?= $total ?></span>
        <span class="stat-sub">All time</span>
      </div>
      <div class="stat-card info-card">
        <span class="stat-label">Pending Review</span>
        <span class="stat-value"><?= $submitted ?></span>
        <span class="stat-sub">Needs assignment</span>
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

    <!-- Complaints Table -->
    <div class="card">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;padding-bottom:14px;border-bottom:1px solid var(--border);">
        <h2 class="card-title" style="margin:0;padding:0;border:none;">All Complaints</h2>
        <span style="font-size:13px;color:var(--text-muted);"><?= count($filtered) ?> shown</span>
      </div>

      <!-- Filters -->
      <form method="GET">
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
                <th>ID</th>
                <th>Student</th>
                <th>Title</th>
                <th>Category</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Submitted</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($filtered as $i => $c): ?>
              <tr>
                <td style="font-weight:600;color:var(--primary);"><?= htmlspecialchars($c['id']) ?></td>
                <td style="font-size:13px;"><?= htmlspecialchars($c['student_name']) ?></td>
                <td>
                  <div style="font-weight:500;"><?= htmlspecialchars($c['title']) ?></div>
                  <?php if ($c['assigned_to']): ?>
                    <div style="font-size:12px;color:var(--text-muted);">→ <?= htmlspecialchars($c['assigned_to']) ?></div>
                  <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($c['category']) ?></td>
                <td><?= priorityBadge($c['priority']) ?></td>
                <td><?= statusBadge($c['status']) ?></td>
                <td style="color:var(--text-muted);font-size:13px;"><?= htmlspecialchars(substr($c['submitted_at'],0,10)) ?></td>
                <td>
                  <div class="action-btns">
                    <button class="btn btn-outline btn-sm" onclick="openDetail('<?= $i ?>')">View</button>
                    <?php if ($c['status'] !== 'Resolved'): ?>
                      <button class="btn btn-primary btn-sm" onclick="openAssign('<?= htmlspecialchars($c['id']) ?>', '<?= htmlspecialchars($c['assigned_to']) ?>')">Assign</button>
                    <?php endif; ?>
                    <?php if ($c['status'] === 'In Progress'): ?>
                      <button class="btn btn-success btn-sm" onclick="openResolve('<?= htmlspecialchars($c['id']) ?>')">Resolve</button>
                    <?php endif; ?>
                    <?php if ($c['status'] === 'Resolved'): ?>
                      <form method="POST" style="display:inline;" onsubmit="return confirm('Reopen this complaint?')">
                        <input type="hidden" name="complaint_id" value="<?= htmlspecialchars($c['id']) ?>">
                        <input type="hidden" name="action" value="reopen">
                        <button type="submit" class="btn btn-outline btn-sm">Reopen</button>
                      </form>
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

  <!-- Detail Modals (hidden) -->
  <?php foreach ($filtered as $i => $c): ?>
  <div class="modal-overlay" id="detail-modal-<?= $i ?>">
    <div class="modal">
      <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:16px;">
        <h3><?= htmlspecialchars($c['id']) ?>: <?= htmlspecialchars($c['title']) ?></h3>
        <button onclick="closeModal('detail-modal-<?= $i ?>')" style="background:none;border:none;font-size:22px;cursor:pointer;color:var(--text-muted);">×</button>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;font-size:13px;">
        <div><strong>Student:</strong> <?= htmlspecialchars($c['student_name']) ?></div>
        <div><strong>Category:</strong> <?= htmlspecialchars($c['category']) ?></div>
        <div><strong>Priority:</strong> <?= priorityBadge($c['priority']) ?></div>
        <div><strong>Status:</strong> <?= statusBadge($c['status']) ?></div>
        <div><strong>Submitted:</strong> <?= htmlspecialchars($c['submitted_at']) ?></div>
        <?php if ($c['assigned_to']): ?><div><strong>Assigned To:</strong> <?= htmlspecialchars($c['assigned_to']) ?></div><?php endif; ?>
      </div>
      <div style="font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:0.7px;color:var(--text-muted);margin-bottom:6px;">Description</div>
      <p style="font-size:14px;line-height:1.7;color:var(--text);"><?= nl2br(htmlspecialchars($c['description'])) ?></p>
      <?php if ($c['resolution_note']): ?>
        <div style="background:var(--success-bg);border-radius:var(--radius);padding:12px 16px;margin-top:14px;">
          <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.7px;color:var(--success);margin-bottom:4px;">Resolution Note</div>
          <p style="font-size:14px;color:var(--success);"><?= nl2br(htmlspecialchars($c['resolution_note'])) ?></p>
        </div>
      <?php endif; ?>
      <button class="btn btn-outline" style="margin-top:20px;width:100%;justify-content:center;" onclick="closeModal('detail-modal-<?= $i ?>')">Close</button>
    </div>
  </div>
  <?php endforeach; ?>

  <!-- Assign Modal -->
  <div class="modal-overlay" id="assign-modal">
    <div class="modal">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <h3>Assign Complaint</h3>
        <button onclick="closeModal('assign-modal')" style="background:none;border:none;font-size:22px;cursor:pointer;color:var(--text-muted);">×</button>
      </div>
      <form method="POST">
        <input type="hidden" name="action" value="assign">
        <input type="hidden" name="complaint_id" id="assign-id">
        <div class="form-group">
          <label>Assign to Staff Member</label>
          <select name="assigned_to" id="assign-staff" required>
            <option value="staff1">Rahim Mia (Maintenance Staff)</option>
          </select>
        </div>
        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:4px;">
          <button type="button" class="btn btn-outline" onclick="closeModal('assign-modal')">Cancel</button>
          <button type="submit" class="btn btn-primary">Assign & Set In Progress →</button>
        </div>
      </form>
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
        <input type="hidden" name="action" value="resolve">
        <input type="hidden" name="complaint_id" id="resolve-id">
        <div class="form-group">
          <label>Resolution Note</label>
          <textarea name="resolution_note" placeholder="Describe what was done to resolve this issue..." required></textarea>
        </div>
        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:4px;">
          <button type="button" class="btn btn-outline" onclick="closeModal('resolve-modal')">Cancel</button>
          <button type="submit" class="btn btn-success">Mark as Resolved ✓</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function openDetail(i) { document.getElementById('detail-modal-' + i).classList.add('open'); }
    function openAssign(id) {
      document.getElementById('assign-id').value = id;
      document.getElementById('assign-modal').classList.add('open');
    }
    function openResolve(id) {
      document.getElementById('resolve-id').value = id;
      document.getElementById('resolve-modal').classList.add('open');
    }
    function closeModal(id) { document.getElementById(id).classList.remove('open'); }
    // Close on backdrop click
    document.querySelectorAll('.modal-overlay').forEach(el => {
      el.addEventListener('click', e => { if (e.target === el) el.classList.remove('open'); });
    });
  </script>
</body>
</html>

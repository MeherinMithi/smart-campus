<?php
session_start();

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        // Load users from JSON
        $jsonPath = __DIR__ . '/../data/users.json';
        $jsonData = file_get_contents($jsonPath);
        $users    = json_decode($jsonData, true);

        $loginSuccess = false;
        foreach ($users as $user) {
            if ($user['username'] === $username && $user['password'] === $password) {
                // Set session
                $_SESSION['username'] = $user['username'];
                $_SESSION['fullname'] = $user['fullname'];
                $_SESSION['role']     = $user['role'];
                $loginSuccess = true;

                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header("Location: admin_dashboard.php");
                } elseif ($user['role'] === 'staff') {
                    header("Location: staff_dashboard.php");
                } else {
                    header("Location: student_dashboard.php");
                }
                exit;
            }
        }

        if (!$loginSuccess) {
            $error = "Invalid username or password. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login — Smart Campus</title>
  <link rel="stylesheet" href="../style.css" />
</head>
<body>
  <div class="login-page">
    <div class="login-box">
      <div class="brand-logo">
        <h1>Smart<span>Campus</span></h1>
        <p>Complaint &amp; Issue Tracking System</p>
      </div>

      <h2>Sign in to your account</h2>

      <?php if ($error): ?>
        <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="login.php">
        <div class="form-group">
          <label for="username">Username</label>
          <input
            type="text"
            id="username"
            name="username"
            placeholder="Enter your username"
            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
            required
          />
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input
            type="password"
            id="password"
            name="password"
            placeholder="Enter your password"
            required
          />
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:4px;">
          Login →
        </button>
      </form>

      <div style="margin-top:28px;padding-top:20px;border-top:1px solid var(--border);">
        <p style="font-size:12px;color:var(--text-muted);margin-bottom:10px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;">Demo Credentials</p>
        <div style="display:grid;gap:8px;font-size:13px;">
          <div style="background:var(--surface-2);border-radius:8px;padding:10px 12px;">
            🎓 <strong>Student:</strong> student1 / student123
          </div>
          <div style="background:var(--surface-2);border-radius:8px;padding:10px 12px;">
            🛡️ <strong>Admin:</strong> admin1 / admin123
          </div>
          <div style="background:var(--surface-2);border-radius:8px;padding:10px 12px;">
            🔧 <strong>Staff:</strong> staff1 / staff123
          </div>
        </div>
      </div>

      <p style="text-align:center;margin-top:20px;font-size:13px;color:var(--text-muted);">
        <a href="../index.html" style="color:var(--primary-light);">← Back to Home</a>
      </p>
    </div>
  </div>
</body>
</html>

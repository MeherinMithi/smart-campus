<?php
session_start();

// Get form data
$username = $_POST['username'];
$email    = $_POST['email'];
$password = $_POST['password'];

// Read JSON file
$jsonData = file_get_contents("users.json");
$users = json_decode($jsonData, true);

$loginSuccess = false;
$userRole = "";

// Check user
foreach ($users as $user) {
    if (
        $user['username'] === $username &&
        $user['email'] === $email &&
        $user['password'] === $password
    ) {
        $loginSuccess = true;
        $userRole = $user['role'];

        // ✅ Store data in session
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        break;
    }
}

// Redirect
if ($loginSuccess) {
    if ($userRole === "admin") {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: dashboard.php");
    }
} else {
    echo "<h2>❌ Login Failed</h2>";
    echo "<p>Invalid credentials. Please try again.</p>";
}
?>

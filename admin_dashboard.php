<?php
session_start();

// Security check
if (!isset($_SESSION['username'])) {
    header("Location: login.html");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #cf1773 0%, #f5336d 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .dashboard-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            width: 100%;
            max-width: 500px;
        }

        .dashboard-header {
            background: linear-gradient(135deg, #f5336d 0%, #ff6b9d 100%);
            padding: 50px 30px;
            text-align: center;
            color: white;
        }

        .dashboard-header h1 {
            font-size: 32px;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .dashboard-header p {
            font-size: 16px;
            opacity: 0.95;
        }

        .dashboard-content {
            padding: 40px 30px;
            text-align: center;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #f5336d 0%, #ff6b9d 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            font-size: 40px;
            color: white;
            box-shadow: 0 10px 30px rgba(245, 51, 109, 0.3);
        }

        .dashboard-content h2 {
            color: #333;
            font-size: 24px;
            margin-bottom: 15px;
        }

        .dashboard-content p {
            color: #666;
            font-size: 16px;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .logout-form {
            display: inline-block;
            width: 100%;
        }

        .logout-btn {
            width: 100%;
            max-width: 250px;
            padding: 14px 30px;
            background-color: #f5336d;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background-color: #d62959;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(245, 51, 109, 0.3);
        }

        .logout-btn:active {
            transform: translateY(0);
        }

        .info-cards {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 30px;
        }

        .info-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            border: 2px solid #e0e0e0;
        }

        .info-card h3 {
            color: #f5336d;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-card p {
            color: #333;
            font-size: 18px;
            font-weight: 600;
            margin: 0;
        }

        @media (max-width: 480px) {
            .dashboard-container {
                border-radius: 0;
            }
            
            .dashboard-content {
                padding: 30px 20px;
            }

            .dashboard-header h1 {
                font-size: 26px;
            }

            .info-cards {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>Welcome, <?php echo $_SESSION['username']; ?> 🎉</h1>
            <p>You are logged in successfully.</p>
        </div>
        
        <div class="dashboard-content">
            <div class="success-icon">
                ✓
            </div>
            
            <h2>Dashboard</h2>
            <p>Your session is active and you have full access to all features.</p>
            
            <div class="info-cards">
                <div class="info-card">
                    <h3>Status</h3>
                    <p>Active</p>
                </div>
                <div class="info-card">
                    <h3>Role</h3>
                    <p>Admin</p>
                </div>
            </div>
            
            <form action="logout.php" method="post" class="logout-form">
                <button type="submit" class="logout-btn">Logout</button>
            </form>
        </div>
    </div>
</body>
</html>
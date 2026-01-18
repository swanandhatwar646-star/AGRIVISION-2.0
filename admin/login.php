<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';

if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter username and password.';
    } else {
        $db = new Database();
        
        $stmt = $db->query("SELECT * FROM users WHERE mobile = ? AND role IN ('admin', 'analyst')");
        $admin = $db->fetch($stmt, [$username]);
        
        if ($admin && password_verify($password, $admin['password'])) {
            if ($admin['status'] == 'active') {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['name'];
                $_SESSION['admin_role'] = $admin['role'];
                
                header('Location: dashboard.php');
                exit();
            } else {
                $error = 'Your account is ' . $admin['status'] . '. Please contact support.';
            }
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - AGRIVISION</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="auth-container" style="background: linear-gradient(135deg, #1b5e20 0%, #2e7d32 100%);">
        <div class="auth-box">
            <div class="text-center mb-4">
                <i class="fas fa-shield-alt fa-3x" style="color: var(--primary-color);"></i>
                <h2 class="mt-3">AGRIVISION Admin</h2>
                <p>Secure Admin Access</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username (Mobile)</label>
                    <input type="tel" id="username" name="username" required 
                           placeholder="Enter admin username"
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required 
                           placeholder="Enter admin password">
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Login as Admin</button>
            </form>
            
            <div class="auth-footer">
                <p><a href="../index.php">Back to Home</a></p>
                <p><a href="../user/login.php">Farmer Login</a></p>
            </div>
        </div>
    </div>
</body>
</html>

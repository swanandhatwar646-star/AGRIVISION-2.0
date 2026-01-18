<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';

echo "<h2>Admin Login Debug</h2>";

$username = '9999999999';
$password = 'password';

$db = new Database();

echo "<h3>Step 1: Check if admin exists</h3>";
$stmt = $db->query("SELECT * FROM users WHERE mobile = ? AND role = 'admin'");
$admin = $db->fetch($stmt, [$username]);

if ($admin) {
    echo "✓ Admin found in database<br>";
    echo "ID: " . $admin['id'] . "<br>";
    echo "Name: " . htmlspecialchars($admin['name']) . "<br>";
    echo "Role: " . $admin['role'] . "<br>";
    echo "Status: " . $admin['status'] . "<br>";
    echo "Password Hash: " . substr($admin['password'], 0, 20) . "...<br>";
} else {
    echo "✗ Admin not found in database<br>";
    echo "<a href='../setup-admin.php'>Create Admin Account</a><br>";
    exit;
}

echo "<h3>Step 2: Verify password</h3>";
if (password_verify($password, $admin['password'])) {
    echo "✓ Password verified successfully<br>";
} else {
    echo "✗ Password verification failed<br>";
    echo "Trying to hash 'password': " . password_hash('password', PASSWORD_DEFAULT) . "<br>";
    exit;
}

echo "<h3>Step 3: Set session variables</h3>";
$_SESSION['admin_id'] = $admin['id'];
$_SESSION['admin_name'] = $admin['name'];
$_SESSION['admin_role'] = $admin['role'];

echo "✓ Session variables set<br>";
echo "Session ID: " . session_id() . "<br>";
echo "admin_id: " . $_SESSION['admin_id'] . "<br>";
echo "admin_name: " . $_SESSION['admin_name'] . "<br>";
echo "admin_role: " . $_SESSION['admin_role'] . "<br>";

echo "<h3>Step 4: Test redirect</h3>";
echo "<a href='dashboard.php'>Click here to go to Admin Dashboard</a><br>";

echo "<h3>Step 5: Session info</h3>";
echo "Session save path: " . session_save_path() . "<br>";
echo "Session name: " . session_name() . "<br>";
echo "Cookie parameters: " . print_r(session_get_cookie_params(), true) . "<br>";
?>

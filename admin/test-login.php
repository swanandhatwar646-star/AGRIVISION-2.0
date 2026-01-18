<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';

echo "<h2>Test Admin Login</h2>";

$db = new Database();

// Check if admin exists
echo "<h3>1. Check Admin Account</h3>";
$stmt = $db->query("SELECT id, name, mobile, role, status FROM users WHERE mobile = '9999999999'");
$admin = $db->fetch($stmt);

if ($admin) {
    echo "✓ Admin found<br>";
    echo "ID: {$admin['id']}<br>";
    echo "Name: {$admin['name']}<br>";
    echo "Mobile: {$admin['mobile']}<br>";
    echo "Role: {$admin['role']}<br>";
    echo "Status: {$admin['status']}<br>";
} else {
    echo "✗ Admin NOT found<br>";
    exit;
}

// Test password
echo "<h3>2. Test Password Verification</h3>";
$stmt = $db->query("SELECT password FROM users WHERE mobile = '9999999999'");
$user = $db->fetch($stmt);

$test_password = 'password';
if (password_verify($test_password, $user['password'])) {
    echo "✓ Password 'password' is CORRECT<br>";
} else {
    echo "✗ Password 'password' is INCORRECT<br>";
    echo "Stored hash: " . substr($user['password'], 0, 30) . "...<br>";
    echo "Test hash: " . substr(password_hash($test_password, PASSWORD_DEFAULT), 0, 30) . "...<br>";
}

// Simulate login
echo "<h3>3. Simulate Login</h3>";
$username = '9999999999';
$password = 'password';

$stmt = $db->query("SELECT * FROM users WHERE mobile = ? AND role IN ('admin', 'analyst')");
$admin_user = $db->fetch($stmt, [$username]);

if ($admin_user) {
    echo "✓ Query returned user<br>";
    if (password_verify($password, $admin_user['password'])) {
        echo "✓ Password verified<br>";
        if ($admin_user['status'] == 'active') {
            echo "✓ Account is active<br>";
            $_SESSION['admin_id'] = $admin_user['id'];
            $_SESSION['admin_name'] = $admin_user['name'];
            $_SESSION['admin_role'] = $admin_user['role'];
            echo "✓ Session set<br>";
            echo "Session ID: " . session_id() . "<br>";
            echo "<a href='dashboard.php'>Go to Dashboard</a>";
        } else {
            echo "✗ Account status: {$admin_user['status']}<br>";
        }
    } else {
        echo "✗ Password verification failed<br>";
    }
} else {
    echo "✗ Query returned no user<br>";
}
?>

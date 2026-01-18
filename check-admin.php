<?php
require_once 'config/config.php';
require_once 'config/database.php';

$db = new Database();

echo "<h2>Check Admin Accounts</h2>";

// Check all admin accounts
$stmt = $db->query("SELECT id, name, mobile, role, status FROM users WHERE role = 'admin'");
$admins = $db->fetchAll($stmt);

if (empty($admins)) {
    echo "No admin accounts found!<br>";
    echo "<a href='setup-admin.php'>Create Admin Account</a>";
} else {
    echo "<h3>Admin Accounts Found:</h3>";
    foreach ($admins as $admin) {
        echo "<hr>";
        echo "ID: {$admin['id']}<br>";
        echo "Name: {$admin['name']}<br>";
        echo "Mobile: {$admin['mobile']}<br>";
        echo "Role: {$admin['role']}<br>";
        echo "Status: {$admin['status']}<br>";
        
        // Test password
        $stmt2 = $db->query("SELECT password FROM users WHERE id = ?");
        $user = $db->fetch($stmt2, [$admin['id']]);
        
        echo "<br><strong>Test Password 'admin123':</strong><br>";
        if (password_verify('admin123', $user['password'])) {
            echo "✓ Password 'admin123' works<br>";
        } else {
            echo "✗ Password 'admin123' does NOT work<br>";
        }
        
        echo "<br><strong>Test Password 'password':</strong><br>";
        if (password_verify('password', $user['password'])) {
            echo "✓ Password 'password' works<br>";
        } else {
            echo "✗ Password 'password' does NOT work<br>";
        }
    }
}

echo "<hr>";
echo "<a href='admin/login.php'>Go to Admin Login</a>";
?>

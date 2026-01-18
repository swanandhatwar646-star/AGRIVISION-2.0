<?php
require_once 'config/config.php';
require_once 'config/database.php';

$db = new Database();

try {
    $stmt = $db->query("SELECT * FROM users WHERE mobile = '8888888888' AND role = 'admin'");
    $existing = $db->fetch($stmt);
    
    if ($existing) {
        echo "Admin account already exists!<br>";
        echo "Username: 8888888888<br>";
        echo "Password: admin123<br>";
        echo "<a href='admin/login.php'>Go to Admin Login</a>";
    } else {
        $hashed_password = password_hash('admin123', PASSWORD_DEFAULT);
        
        $stmt = $db->query("INSERT INTO users (name, mobile, password, role, status) VALUES (?, ?, ?, ?, ?)");
        $result = $db->execute($stmt, ['Admin', '8888888888', $hashed_password, 'admin', 'active']);
        
        if ($result) {
            echo "Admin account created successfully!<br>";
            echo "Username: 8888888888<br>";
            echo "Password: admin123<br>";
            echo "<a href='admin/login.php'>Go to Admin Login</a>";
        } else {
            echo "Failed to create admin account.";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

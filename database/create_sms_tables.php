<?php
// Create SMS alert tables
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'agrivision_db';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database successfully!\n";
    
    // Create SMS alerts table
    $sql1 = "CREATE TABLE IF NOT EXISTS sms_alerts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        alert_type VARCHAR(50) NOT NULL,
        title VARCHAR(200) NOT NULL,
        message TEXT NOT NULL,
        phone_number VARCHAR(20) NOT NULL,
        status ENUM('pending', 'sent', 'delivered', 'failed') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        sent_at TIMESTAMP NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    $pdo->exec($sql1);
    echo "✓ Created sms_alerts table successfully!\n";
    
    // Create SMS alert preferences table
    $sql2 = "CREATE TABLE IF NOT EXISTS sms_alert_preferences (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        alert_type VARCHAR(50) NOT NULL,
        enabled BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_alert (user_id, alert_type)
    )";
    
    $pdo->exec($sql2);
    echo "✓ Created sms_alert_preferences table successfully!\n";
    
    // Add phone_number and sms_enabled columns to users table
    $sql3 = "ALTER TABLE users ADD COLUMN IF NOT EXISTS phone_number VARCHAR(20)";
    $pdo->exec($sql3);
    echo "✓ Added phone_number column to users table successfully!\n";
    
    $sql4 = "ALTER TABLE users ADD COLUMN IF NOT EXISTS sms_enabled BOOLEAN DEFAULT FALSE";
    $pdo->exec($sql4);
    echo "✓ Added sms_enabled column to users table successfully!\n";
    
    // Insert sample SMS alerts
    $sample_alerts = [
        [
            'user_id' => 1,
            'alert_type' => 'weather',
            'title' => 'High Temperature Alert',
            'message' => 'Temperature in your field has exceeded 35°C. Please take necessary precautions for crop protection.',
            'phone_number' => '+919876543210',
            'status' => 'delivered',
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))
        ],
        [
            'user_id' => 1,
            'alert_type' => 'irrigation',
            'title' => 'Irrigation Reminder',
            'message' => 'Soil moisture in Zone A is below 40%. Please irrigate your field today.',
            'phone_number' => '+919876543210',
            'status' => 'delivered',
            'created_at' => date('Y-m-d H:i:s', strtotime('-5 hours'))
        ],
        [
            'user_id' => 1,
            'alert_type' => 'crop_health',
            'title' => 'Pest Alert',
            'message' => 'Pest activity detected in Zone B. Please inspect your crops and take appropriate action.',
            'phone_number' => '+919876543210',
            'status' => 'sent',
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
        ]
    ];
    
    foreach ($sample_alerts as $alert) {
        $stmt = $pdo->prepare("INSERT INTO sms_alerts (user_id, alert_type, title, message, phone_number, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$alert['user_id'], $alert['alert_type'], $alert['title'], $alert['message'], $alert['phone_number'], $alert['status'], $alert['created_at']]);
    }
    
    echo "✓ Inserted " . count($sample_alerts) . " sample SMS alerts successfully!\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>

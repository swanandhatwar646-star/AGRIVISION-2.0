<?php
// Add created_at column to fields table
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'agrivision_db';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database successfully!\n";
    
    // Add created_at column to fields table
    $sql = "ALTER TABLE fields ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
    $pdo->exec($sql);
    
    echo "✓ Added created_at column to fields table successfully!\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>

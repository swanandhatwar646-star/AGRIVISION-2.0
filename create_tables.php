<?php
// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'agrivision_db';

try {
    // Connect to MySQL without database
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Select the database
    $pdo->exec("USE $database");
    
    // Read and execute the SQL file
    $sql = file_get_contents('database/create_schemes_tables.sql');
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            echo "Executing: " . substr($statement, 0, 50) . "...\n";
            $pdo->exec($statement);
        }
    }
    
    echo "✅ Tables created successfully!\n";
    
    // Verify tables exist
    $stmt = $pdo->query("SHOW TABLES LIKE 'government_schemes'");
    if ($stmt->rowCount() > 0) {
        echo "✅ government_schemes table exists\n";
    }
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'scheme_applications'");
    if ($stmt->rowCount() > 0) {
        echo "✅ scheme_applications table exists\n";
    }
    
    // Count schemes
    $stmt = $pdo->query("SELECT COUNT(*) FROM government_schemes");
    $count = $stmt->fetchColumn();
    echo "✅ Inserted $count government schemes\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>

<?php
// Database update script
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'agrivision_db';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database successfully!\n";
    
    // Read and execute SQL file
    $sqlFile = 'c:/xampp/htdocs/AGRIVISION 4/AGRIVISION/database/update_tables.sql';
    $sql = file_get_contents($sqlFile);
    
    if ($sql) {
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                try {
                    $pdo->exec($statement);
                    echo "✓ Executed: " . substr($statement, 0, 50) . "...\n";
                } catch (PDOException $e) {
                    echo "✗ Error: " . $e->getMessage() . "\n";
                }
            }
        }
        
        echo "\nDatabase update completed!\n";
    } else {
        echo "Error reading SQL file!\n";
    }
    
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
?>

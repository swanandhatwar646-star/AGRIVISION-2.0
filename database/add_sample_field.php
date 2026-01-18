<?php
// Check and add sample data
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'agrivision_db';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database successfully!\n";
    
    // Check if fields exist
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM fields");
    $result = $stmt->fetch();
    echo "Fields count: " . $result['count'] . "\n";
    
    if ($result['count'] == 0) {
        // Insert sample field
        $stmt = $pdo->prepare("INSERT INTO fields (user_id, name, location, area, soil_type, crop_type, planting_date) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([1, 'Test Farm', 'Pune, Maharashtra', 10.5, 'Loamy Soil', 'Wheat', '2024-01-15']);
        $field_id = $pdo->lastInsertId();
        
        // Insert sample zones
        $zones = [
            ['Test Farm', 'Zone A', 45, 30, 25],
            ['Test Farm', 'Zone B', 60, 25, 35],
            ['Test Farm', 'Zone C', 55, 20, 40]
        ];
        
        foreach ($zones as $zone) {
            $stmt = $pdo->prepare("INSERT INTO field_zones (field_id, name, zone_name, healthy_percentage, stressed_percentage, deficient_percentage, water_requirement, pest_risk, nutrition_requirement) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$field_id, $zone[0], $zone[1], $zone[2], $zone[3], $zone[4], $zone[5], $zone[6]]);
        }
        
        echo "Inserted sample field and zones successfully!\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>

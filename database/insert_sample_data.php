<?php
// Sample data insertion script for testing
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'agrivision_db';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database successfully!\n";
    
    // Insert sample sensor readings for testing
    $sample_readings = [
        // Zone 1 data
        [
            'zone_id' => 1,
            'soil_moisture' => 65,
            'temperature' => 24,
            'humidity' => 70,
            'ph_level' => 6.5,
            'nutrient_level' => 75,
            'reading_time' => date('Y-m-d H:i:s', strtotime('-2 hours'))
        ],
        [
            'zone_id' => 1,
            'soil_moisture' => 62,
            'temperature' => 25,
            'humidity' => 68,
            'ph_level' => 6.8,
            'nutrient_level' => 72,
            'reading_time' => date('Y-m-d H:i:s', strtotime('-4 hours'))
        ],
        [
            'zone_id' => 1,
            'soil_moisture' => 58,
            'temperature' => 26,
            'humidity' => 65,
            'ph_level' => 7.0,
            'nutrient_level' => 70,
            'reading_time' => date('Y-m-d H:i:s', strtotime('-6 hours'))
        ],
        [
            'zone_id' => 1,
            'soil_moisture' => 60,
            'temperature' => 23,
            'humidity' => 72,
            'ph_level' => 6.2,
            'nutrient_level' => 78,
            'reading_time' => date('Y-m-d H:i:s', strtotime('-8 hours'))
        ],
        [
            'zone_id' => 1,
            'soil_moisture' => 55,
            'temperature' => 22,
            'humidity' => 75,
            'ph_level' => 6.5,
            'nutrient_level' => 73,
            'reading_time' => date('Y-m-d H:i:s', strtotime('-10 hours'))
        ],
        
        // Zone 2 data
        [
            'zone_id' => 2,
            'soil_moisture' => 70,
            'temperature' => 26,
            'humidity' => 65,
            'ph_level' => 7.2,
            'nutrient_level' => 68,
            'reading_time' => date('Y-m-d H:i:s', strtotime('-1 hour'))
        ],
        [
            'zone_id' => 2,
            'soil_moisture' => 68,
            'temperature' => 27,
            'humidity' => 62,
            'ph_level' => 7.0,
            'nutrient_level' => 65,
            'reading_time' => date('Y-m-d H:i:s', strtotime('-3 hours'))
        ],
        
        // Zone 3 data
        [
            'zone_id' => 3,
            'soil_moisture' => 45,
            'temperature' => 28,
            'humidity' => 80,
            'ph_level' => 5.8,
            'nutrient_level' => 82,
            'reading_time' => date('Y-m-d H:i:s', strtotime('-30 minutes'))
        ]
    ];
    
    // Insert sample readings
    foreach ($sample_readings as $reading) {
        $stmt = $pdo->prepare("INSERT INTO sensor_readings (zone_id, soil_moisture, temperature, humidity, ph_level, nutrient_level, reading_time) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$reading['zone_id'], $reading['soil_moisture'], $reading['temperature'], $reading['humidity'], $reading['ph_level'], $reading['nutrient_level'], $reading['reading_time']]);
    }
    
    echo "Inserted " . count($sample_readings) . " sample sensor readings successfully!\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>

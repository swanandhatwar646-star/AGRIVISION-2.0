<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$db = new Database();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $temperature = floatval($_POST['temperature'] ?? 25);
    $humidity = floatval($_POST['humidity'] ?? 60);
    $soil_moisture = floatval($_POST['soil_moisture'] ?? 50);
    
    if (empty($name)) {
        $error = 'Greenhouse name is required.';
    } else {
        $stmt = $db->query("INSERT INTO greenhouses (user_id, name, location, temperature, humidity, soil_moisture) VALUES (?, ?, ?, ?, ?, ?)");
        if ($db->execute($stmt, [$_SESSION['user_id'], $name, $location, $temperature, $humidity, $soil_moisture])) {
            $greenhouse_id = $db->lastInsertId();
            
            $alert_messages = [];
            if ($temperature > 35) {
                $alert_messages[] = ['alert_type' => 'High Temperature', 'message' => 'Temperature is above safe limit (35°C). Consider activating cooling system.', 'severity' => 'high'];
            } elseif ($temperature < 15) {
                $alert_messages[] = ['alert_type' => 'Low Temperature', 'message' => 'Temperature is below safe limit (15°C). Consider activating heating system.', 'severity' => 'medium'];
            }
            
            if ($humidity > 90) {
                $alert_messages[] = ['alert_type' => 'High Humidity', 'message' => 'Humidity is above safe limit (90%). This may promote fungal growth.', 'severity' => 'medium'];
            } elseif ($humidity < 30) {
                $alert_messages[] = ['alert_type' => 'Low Humidity', 'message' => 'Humidity is below safe limit (30%). Consider activating irrigation system.', 'severity' => 'medium'];
            }
            
            if ($soil_moisture < 20) {
                $alert_messages[] = ['alert_type' => 'Low Soil Moisture', 'message' => 'Soil moisture is critically low (below 20%). Immediate irrigation required.', 'severity' => 'high'];
            }
            
            foreach ($alert_messages as $alert) {
                $stmt = $db->query("INSERT INTO greenhouse_alerts (greenhouse_id, alert_type, message, severity) VALUES (?, ?, ?, ?)");
                $db->execute($stmt, [$greenhouse_id, $alert['alert_type'], $alert['message'], $alert['severity']]);
            }
            
            $success = 'Greenhouse added successfully! Redirecting...';
            header("refresh:2;url=my-greenhouse.php?greenhouse_id=$greenhouse_id");
        } else {
            $error = 'Failed to add greenhouse. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Greenhouse - AGRIVISION</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-leaf"></i> AGRIVISION</h3>
            </div>
            <nav class="sidebar-menu">
                <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="my-field.php"><i class="fas fa-seedling"></i> My Field</a>
                <a href="krishi-mandi.php"><i class="fas fa-store"></i> Krishi Mandi</a>
                <a href="my-greenhouse.php" class="active"><i class="fas fa-warehouse"></i> My Greenhouse</a>
                <a href="ai-support.php"><i class="fas fa-robot"></i> AI Support</a>
                <a href="appointments.php"><i class="fas fa-calendar"></i> Appointments</a>
                <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>
        
        <main class="main-content">
            <div class="top-bar">
                <div class="user-info">
                    <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?></div>
                    <div>
                        <h4><?php echo htmlspecialchars($_SESSION['user_name']); ?></h4>
                        <small>Add New Greenhouse</small>
                    </div>
                </div>
                <button class="theme-toggle" title="Toggle Theme">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
            
            <div class="card" style="max-width: 600px; margin: 0 auto;">
                <div class="card-header">
                    <h3><i class="fas fa-plus-circle"></i> Add New Greenhouse</h3>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="name">Greenhouse Name *</label>
                        <input type="text" id="name" name="name" required 
                               placeholder="Enter greenhouse name (e.g., Main Greenhouse, North Greenhouse)"
                               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" 
                               placeholder="Enter greenhouse location"
                               value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="temperature">Current Temperature (°C)</label>
                        <input type="number" id="temperature" name="temperature" step="0.1" min="0" max="50"
                               placeholder="Enter current temperature"
                               value="<?php echo htmlspecialchars($_POST['temperature'] ?? '25'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="humidity">Current Humidity (%)</label>
                        <input type="number" id="humidity" name="humidity" step="0.1" min="0" max="100"
                               placeholder="Enter current humidity"
                               value="<?php echo htmlspecialchars($_POST['humidity'] ?? '60'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="soil_moisture">Soil Moisture (%)</label>
                        <input type="number" id="soil_moisture" name="soil_moisture" step="0.1" min="0" max="100"
                               placeholder="Enter soil moisture level"
                               value="<?php echo htmlspecialchars($_POST['soil_moisture'] ?? '50'); ?>">
                    </div>
                    
                    <div style="background: var(--bg-light); padding: 15px; border-radius: var(--radius); margin-bottom: 20px;">
                        <h4 style="margin: 0 0 10px; font-size: 0.9rem;"><i class="fas fa-info-circle"></i> Safe Ranges</h4>
                        <p style="margin: 5px 0; font-size: 0.85rem;">• Temperature: 15°C - 35°C</p>
                        <p style="margin: 5px 0; font-size: 0.85rem;">• Humidity: 30% - 90%</p>
                        <p style="margin: 5px 0; font-size: 0.85rem;">• Soil Moisture: Above 20%</p>
                    </div>
                    
                    <div style="display: flex; gap: 10px; margin-top: 20px;">
                        <button type="submit" class="btn btn-primary" style="flex: 1;">Add Greenhouse</button>
                        <a href="my-greenhouse.php" class="btn btn-outline" style="flex: 1; text-align: center;">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>

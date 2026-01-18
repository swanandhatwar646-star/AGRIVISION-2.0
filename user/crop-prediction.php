<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$db = new Database();

$sunlight = $_POST['sunlight'] ?? '';
$water = $_POST['water'] ?? '';
$soil = $_POST['soil'] ?? '';

$suitable_crops = [];

if ($sunlight && $water && $soil) {
    $stmt = $db->query("SELECT * FROM crops WHERE sunlight_requirement LIKE ? AND water_requirement LIKE ? AND soil_type LIKE ?");
    $suitable_crops = $db->fetchAll($stmt, ["%$sunlight%", "%$water%", "%$soil%"]);
    
    if (empty($suitable_crops)) {
        $stmt = $db->query("SELECT * FROM crops WHERE soil_type LIKE ?");
        $suitable_crops = $db->fetchAll($stmt, ["%$soil%"]);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crop Prediction Results - AGRIVISION</title>
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
                <a href="krishi-mandi.php" class="active"><i class="fas fa-store"></i> Krishi Mandi</a>
                <a href="my-greenhouse.php"><i class="fas fa-warehouse"></i> My Greenhouse</a>
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
                        <small>Crop Prediction Results</small>
                    </div>
                </div>
                <button class="theme-toggle" title="Toggle Theme">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-seedling"></i> Recommended Crops</h3>
                    <a href="krishi-mandi.php?tab=prediction" class="btn btn-sm btn-outline">Back to Prediction</a>
                </div>
                
                <div style="background: var(--bg-light); padding: 20px; border-radius: var(--radius); margin-bottom: 30px;">
                    <h4>Your Input:</h4>
                    <p><strong>Sunlight:</strong> <?php echo ucfirst($sunlight); ?> Sun</p>
                    <p><strong>Water:</strong> <?php echo ucfirst($water); ?> Availability</p>
                    <p><strong>Soil:</strong> <?php echo ucfirst($soil); ?> Soil</p>
                </div>
                
                <?php if (!empty($suitable_crops)): ?>
                    <h4>Based on your conditions, we recommend these crops:</h4>
                    <div class="grid-3" style="margin-top: 20px;">
                        <?php foreach ($suitable_crops as $crop): ?>
                            <div class="stat-card" style="text-align: left;">
                                <div style="text-align: center; margin-bottom: 15px;">
                                    <i class="fas fa-seedling fa-3x" style="color: var(--primary-color);"></i>
                                </div>
                                <h4 style="color: var(--primary-color); text-align: center;"><?php echo htmlspecialchars($crop['name']); ?></h4>
                                <div style="margin-top: 15px; font-size: 0.9rem;">
                                    <p><strong>Sunlight:</strong> <?php echo htmlspecialchars($crop['sunlight_requirement']); ?></p>
                                    <p><strong>Water:</strong> <?php echo htmlspecialchars($crop['water_requirement']); ?></p>
                                    <p><strong>Soil:</strong> <?php echo htmlspecialchars($crop['soil_type']); ?></p>
                                    <p><strong>Season:</strong> <?php echo htmlspecialchars($crop['growing_season']); ?></p>
                                    <?php if ($crop['description']): ?>
                                        <p style="margin-top: 10px; color: var(--text-light);"><?php echo htmlspecialchars(substr($crop['description'], 0, 100)); ?>...</p>
                                    <?php endif; ?>
                                </div>
                                <div style="margin-top: 15px; text-align: center;">
                                    <span style="background: #4caf50; color: white; padding: 5px 15px; border-radius: 20px; font-size: 0.85rem;">
                                        <i class="fas fa-check"></i> Recommended
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px;">
                        <i class="fas fa-exclamation-triangle fa-3x" style="color: #ff9800; margin-bottom: 20px;"></i>
                        <h4>No exact matches found</h4>
                        <p style="color: var(--text-light); margin-top: 10px;">Try adjusting your criteria or consult with an agricultural expert.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>

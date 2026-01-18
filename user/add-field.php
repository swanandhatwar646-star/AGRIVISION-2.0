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
    $area = floatval($_POST['area'] ?? 0);
    $soil_type = $_POST['soil_type'] ?? '';
    $crop_type = $_POST['crop_type'] ?? '';
    $planting_date = $_POST['planting_date'] ?? '';
    
    if (empty($name) || empty($area)) {
        $error = 'Field name and area are required.';
    } else {
        $stmt = $db->query("INSERT INTO fields (user_id, name, location, area, soil_type, crop_type, planting_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($db->execute($stmt, [$_SESSION['user_id'], $name, $location, $area, $soil_type, $crop_type, $planting_date])) {
            $field_id = $db->lastInsertId();
            
            for ($i = 1; $i <= 3; $i++) {
                $stmt = $db->query("INSERT INTO field_zones (field_id, zone_name, healthy_percentage, stressed_percentage, deficient_percentage) VALUES (?, ?, ?, ?, ?)");
                $db->execute($stmt, [$field_id, "Zone $i", 70, 20, 10]);
            }
            
            $success = 'Field added successfully! Redirecting...';
            header("refresh:2;url=my-field.php?field_id=$field_id");
        } else {
            $error = 'Failed to add field. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Field - AGRIVISION</title>
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
                <a href="my-field.php" class="active"><i class="fas fa-seedling"></i> My Field</a>
                <a href="krishi-mandi.php"><i class="fas fa-store"></i> Krishi Mandi</a>
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
                        <small>Add New Field</small>
                    </div>
                </div>
                <button class="theme-toggle" title="Toggle Theme">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
            
            <div class="card" style="max-width: 600px; margin: 0 auto;">
                <div class="card-header">
                    <h3><i class="fas fa-plus-circle"></i> Add New Field</h3>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="name">Field Name *</label>
                        <input type="text" id="name" name="name" required 
                               placeholder="Enter field name (e.g., Main Field, North Field)"
                               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" 
                               placeholder="Enter field location"
                               value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="area">Area (in acres) *</label>
                        <input type="number" id="area" name="area" required step="0.01" min="0"
                               placeholder="Enter field area"
                               value="<?php echo htmlspecialchars($_POST['area'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="soil_type">Soil Type</label>
                        <select id="soil_type" name="soil_type">
                            <option value="">Select soil type</option>
                            <option value="clay" <?php echo (($_POST['soil_type'] ?? '') == 'clay') ? 'selected' : ''; ?>>Clay</option>
                            <option value="sandy" <?php echo (($_POST['soil_type'] ?? '') == 'sandy') ? 'selected' : ''; ?>>Sandy</option>
                            <option value="loamy" <?php echo (($_POST['soil_type'] ?? '') == 'loamy') ? 'selected' : ''; ?>>Loamy</option>
                            <option value="silt" <?php echo (($_POST['soil_type'] ?? '') == 'silt') ? 'selected' : ''; ?>>Silt</option>
                            <option value="peaty" <?php echo (($_POST['soil_type'] ?? '') == 'peaty') ? 'selected' : ''; ?>>Peaty</option>
                            <option value="chalky" <?php echo (($_POST['soil_type'] ?? '') == 'chalky') ? 'selected' : ''; ?>>Chalky</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="crop_type">Crop Type</label>
                        <input type="text" id="crop_type" name="crop_type" 
                               placeholder="Enter crop type (e.g., Wheat, Rice)"
                               value="<?php echo htmlspecialchars($_POST['crop_type'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="planting_date">Planting Date</label>
                        <input type="date" id="planting_date" name="planting_date" 
                               value="<?php echo htmlspecialchars($_POST['planting_date'] ?? ''); ?>">
                    </div>
                    
                    <div style="display: flex; gap: 10px; margin-top: 20px;">
                        <button type="submit" class="btn btn-primary" style="flex: 1;">Add Field</button>
                        <a href="my-field.php" class="btn btn-outline" style="flex: 1; text-align: center;">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>

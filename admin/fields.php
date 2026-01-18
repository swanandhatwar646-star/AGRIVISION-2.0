<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$db = new Database();

$stmt = $db->query("SELECT * FROM users WHERE id = ?");
$admin = $db->fetch($stmt, [$_SESSION['admin_id']]);

if (isset($_POST['add_crop'])) {
    $name = trim($_POST['name']);
    $sunlight = trim($_POST['sunlight_requirement']);
    $water = trim($_POST['water_requirement']);
    $soil = trim($_POST['soil_type']);
    $season = trim($_POST['growing_season']);
    $description = trim($_POST['description']);

    $stmt = $db->query("INSERT INTO crops (name, sunlight_requirement, water_requirement, soil_type, growing_season, description) VALUES (?, ?, ?, ?, ?, ?)");
    $db->execute($stmt, [$name, $sunlight, $water, $soil, $season, $description]);
    header('Location: fields.php');
    exit();
}

if (isset($_POST['edit_crop'])) {
    $id = intval($_POST['crop_id']);
    $name = trim($_POST['name']);
    $sunlight = trim($_POST['sunlight_requirement']);
    $water = trim($_POST['water_requirement']);
    $soil = trim($_POST['soil_type']);
    $season = trim($_POST['growing_season']);
    $description = trim($_POST['description']);

    $stmt = $db->query("UPDATE crops SET name = ?, sunlight_requirement = ?, water_requirement = ?, soil_type = ?, growing_season = ?, description = ? WHERE id = ?");
    $db->execute($stmt, [$name, $sunlight, $water, $soil, $season, $description, $id]);
    header('Location: fields.php');
    exit();
}

if (isset($_GET['delete_crop'])) {
    $id = intval($_GET['delete_crop']);
    $stmt = $db->query("DELETE FROM crops WHERE id = ?");
    $db->execute($stmt, [$id]);
    header('Location: fields.php');
    exit();
}

$edit_crop_id = isset($_GET['edit_crop']) ? intval($_GET['edit_crop']) : 0;
$edit_crop = null;

if ($edit_crop_id > 0) {
    $stmt = $db->query("SELECT * FROM crops WHERE id = ?");
    $edit_crop = $db->fetch($stmt, [$edit_crop_id]);
}

$stmt = $db->query("SELECT * FROM crops ORDER BY name");
$crops = $db->fetchAll($stmt);

$stmt = $db->query("SELECT f.*, u.name as farmer_name, u.mobile as farmer_mobile
                    FROM fields f
                    JOIN users u ON f.user_id = u.id
                    ORDER BY f.id DESC");
$fields = $db->fetchAll($stmt);

$stmt = $db->query("SELECT COUNT(*) as count FROM field_zones");
$total_zones = $db->fetch($stmt);

$selected_field_id = isset($_GET['field_id']) ? intval($_GET['field_id']) : 0;
$field_zones = [];

if ($selected_field_id > 0) {
    $stmt = $db->query("SELECT * FROM field_zones WHERE field_id = ?");
    $field_zones = $db->fetchAll($stmt, [$selected_field_id]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Fields & Crops - AGRIVISION Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-shield-alt"></i> AGRIVISION Admin</h3>
            </div>
            <nav class="sidebar-menu">
                <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="farmers.php"><i class="fas fa-users"></i> Farmers</a>
                <a href="fields.php" class="active"><i class="fas fa-map"></i> Fields & Crops</a>
                <a href="greenhouses.php"><i class="fas fa-warehouse"></i> Greenhouses</a>
                <a href="schemes.php"><i class="fas fa-hand-holding-usd"></i> Schemes</a>
                <a href="marketplace.php"><i class="fas fa-store"></i> Marketplace</a>
                <a href="appointments.php"><i class="fas fa-calendar"></i> Appointments</a>
                <a href="ai-queries.php"><i class="fas fa-robot"></i> AI Queries</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>
        
        <main class="main-content">
            <div class="top-bar">
                <div class="user-info">
                    <div class="user-avatar" style="background: #1b5e20;">
                        <?php echo strtoupper(substr($admin['name'], 0, 1)); ?>
                    </div>
                    <div>
                        <h4><?php echo htmlspecialchars($admin['name']); ?></h4>
                        <small><?php echo ucfirst($admin['role']); ?></small>
                    </div>
                </div>
                <button class="theme-toggle" title="Toggle Theme">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
            
            <div class="grid-3" style="margin-bottom: 30px;">
                <div class="stat-card">
                    <i class="fas fa-map"></i>
                    <h4><?php echo count($fields); ?></h4>
                    <p>Total Fields</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-layer-group"></i>
                    <h4><?php echo $total_zones['count']; ?></h4>
                    <p>Total Zones</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-seedling"></i>
                    <h4><?php echo count(array_filter($fields, fn($f) => !empty($f['crop_type']))); ?></h4>
                    <p>Active Crops</p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-seedling"></i> Manage Crops</h3>
                </div>

                <form method="POST" action="fields.php" style="padding: 20px;">
                    <input type="hidden" name="crop_id" value="<?php echo $edit_crop ? $edit_crop['id'] : ''; ?>">
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Crop Name</label>
                            <input type="text" name="name" required
                                   value="<?php echo $edit_crop ? htmlspecialchars($edit_crop['name']) : ''; ?>"
                                   style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Sunlight Requirement</label>
                            <input type="text" name="sunlight_requirement" required
                                   value="<?php echo $edit_crop ? htmlspecialchars($edit_crop['sunlight_requirement']) : ''; ?>"
                                   style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Water Requirement</label>
                            <input type="text" name="water_requirement" required
                                   value="<?php echo $edit_crop ? htmlspecialchars($edit_crop['water_requirement']) : ''; ?>"
                                   style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Soil Type</label>
                            <input type="text" name="soil_type" required
                                   value="<?php echo $edit_crop ? htmlspecialchars($edit_crop['soil_type']) : ''; ?>"
                                   style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Growing Season</label>
                            <input type="text" name="growing_season" required
                                   value="<?php echo $edit_crop ? htmlspecialchars($edit_crop['growing_season']) : ''; ?>"
                                   style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Description</label>
                            <input type="text" name="description"
                                   value="<?php echo $edit_crop ? htmlspecialchars($edit_crop['description']) : ''; ?>"
                                   style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        </div>
                    </div>
                    <div style="margin-top: 15px; display: flex; gap: 10px;">
                        <button type="submit" name="<?php echo $edit_crop ? 'edit_crop' : 'add_crop'; ?>"
                                style="padding: 10px 20px; background: var(--primary-color); color: white; border: none; border-radius: 5px; cursor: pointer;">
                            <i class="fas fa-<?php echo $edit_crop ? 'save' : 'plus'; ?>"></i>
                            <?php echo $edit_crop ? 'Update Crop' : 'Add Crop'; ?>
                        </button>
                        <?php if ($edit_crop): ?>
                            <a href="fields.php" style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer; text-decoration: none;">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        <?php endif; ?>
                    </div>
                </form>

                <div style="margin-top: 30px;">
                    <h4 style="margin-bottom: 15px;">All Crops</h4>
                    <?php if (empty($crops)): ?>
                        <p style="text-align: center; color: var(--text-light); padding: 40px;">
                            <i class="fas fa-seedling fa-3x" style="display: block; margin-bottom: 15px;"></i>
                            No crops found. Add a crop above.
                        </p>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="background: var(--bg-light); border-bottom: 2px solid #ddd;">
                                        <th style="padding: 12px; text-align: left;">Crop Name</th>
                                        <th style="padding: 12px; text-align: left;">Sunlight</th>
                                        <th style="padding: 12px; text-align: left;">Water</th>
                                        <th style="padding: 12px; text-align: left;">Soil</th>
                                        <th style="padding: 12px; text-align: left;">Season</th>
                                        <th style="padding: 12px; text-align: left;">Description</th>
                                        <th style="padding: 12px; text-align: center;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($crops as $crop): ?>
                                        <tr style="border-bottom: 1px solid #ddd;">
                                            <td style="padding: 12px; font-weight: 500; color: var(--primary-color);"><?php echo htmlspecialchars($crop['name']); ?></td>
                                            <td style="padding: 12px;"><?php echo htmlspecialchars($crop['sunlight_requirement']); ?></td>
                                            <td style="padding: 12px;"><?php echo htmlspecialchars($crop['water_requirement']); ?></td>
                                            <td style="padding: 12px;"><?php echo htmlspecialchars($crop['soil_type']); ?></td>
                                            <td style="padding: 12px;"><?php echo htmlspecialchars($crop['growing_season']); ?></td>
                                            <td style="padding: 12px;"><?php echo htmlspecialchars(substr($crop['description'] ?? '', 0, 50)); ?></td>
                                            <td style="padding: 12px; text-align: center;">
                                                <a href="fields.php?edit_crop=<?php echo $crop['id']; ?>"
                                                   style="color: #2196f3; text-decoration: none; margin-right: 10px;">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <a href="fields.php?delete_crop=<?php echo $crop['id']; ?>"
                                                   style="color: #f44336; text-decoration: none;"
                                                   onclick="return confirm('Are you sure you want to delete this crop?')">
                                                    <i class="fas fa-trash"></i> Delete
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card" style="margin-top: 30px;">
                <div class="card-header">
                    <h3><i class="fas fa-map"></i> All Fields</h3>
                </div>
                
                <?php if (empty($fields)): ?>
                    <p style="text-align: center; color: var(--text-light); padding: 40px;">
                        <i class="fas fa-map fa-3x" style="display: block; margin-bottom: 15px;"></i>
                        No fields found.
                    </p>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: var(--bg-light); border-bottom: 2px solid #ddd;">
                                    <th style="padding: 15px; text-align: left;">Field Name</th>
                                    <th style="padding: 15px; text-align: left;">Farmer</th>
                                    <th style="padding: 15px; text-align: left;">Location</th>
                                    <th style="padding: 15px; text-align: left;">Area</th>
                                    <th style="padding: 15px; text-align: left;">Crop Type</th>
                                    <th style="padding: 15px; text-align: left;">Soil Type</th>
                                    <th style="padding: 15px; text-align: left;">Zones</th>
                                    <th style="padding: 15px; text-align: left;">Created</th>
                                    <th style="padding: 15px; text-align: center;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fields as $field): ?>
                                    <tr style="border-bottom: 1px solid #ddd;">
                                        <td style="padding: 15px;">
                                            <strong><?php echo htmlspecialchars($field['name']); ?></strong>
                                        </td>
                                        <td style="padding: 15px;">
                                            <?php echo htmlspecialchars($field['farmer_name']); ?><br>
                                            <small style="color: var(--text-light);"><?php echo htmlspecialchars($field['farmer_mobile']); ?></small>
                                        </td>
                                        <td style="padding: 15px;"><?php echo htmlspecialchars($field['location'] ?: 'N/A'); ?></td>
                                        <td style="padding: 15px;"><?php echo $field['area']; ?> acres</td>
                                        <td style="padding: 15px;"><?php echo htmlspecialchars($field['crop_type'] ?: 'N/A'); ?></td>
                                        <td style="padding: 15px;"><?php echo htmlspecialchars($field['soil_type'] ?: 'N/A'); ?></td>
                                        <td style="padding: 15px;">
                                            <?php 
                                            $stmt = $db->query("SELECT COUNT(*) as count FROM field_zones WHERE field_id = ?");
                                            $zones = $db->fetch($stmt, [$field['id']]);
                                            echo $zones['count'];
                                            ?>
                                        </td>
                                        <td style="padding: 15px;"><?php echo date('M d, Y', strtotime($field['created_at'])); ?></td>
                                        <td style="padding: 15px; text-align: center;">
                                            <a href="fields.php?field_id=<?php echo $field['id']; ?>" 
                                               class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i> View Zones
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($selected_field_id > 0 && !empty($field_zones)): ?>
                <div class="card" style="margin-top: 20px;">
                    <div class="card-header">
                        <h3><i class="fas fa-th-large"></i> Field Zones</h3>
                        <a href="fields.php" class="btn btn-sm btn-outline">Close</a>
                    </div>
                    <div class="grid-3">
                        <?php foreach ($field_zones as $zone): ?>
                            <div class="stat-card">
                                <h4><?php echo htmlspecialchars($zone['zone_name']); ?></h4>
                                <div style="margin-top: 15px; font-size: 0.85rem;">
                                    <p><span style="color: #4caf50;">●</span> Healthy: <?php echo $zone['healthy_percentage']; ?>%</p>
                                    <p><span style="color: #ff9800;">●</span> Stressed: <?php echo $zone['stressed_percentage']; ?>%</p>
                                    <p><span style="color: #f44336;">●</span> Deficient: <?php echo $zone['deficient_percentage']; ?>%</p>
                                    <p><strong>Water:</strong> <?php echo $zone['water_requirement']; ?>%</p>
                                    <p><strong>Pest Risk:</strong> <?php echo ucfirst($zone['pest_risk']); ?></p>
                                    <p><strong>Nutrition:</strong> <?php echo $zone['nutrition_requirement']; ?>%</p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>

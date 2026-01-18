<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$db = new Database();

$stmt = $db->query("SELECT * FROM users WHERE id = ?");
$user = $db->fetch($stmt, [$_SESSION['user_id']]);

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'prediction';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($tab == 'pesticides' && $search) {
    $stmt = $db->query("SELECT * FROM pesticides WHERE name LIKE ? OR company LIKE ? ORDER BY name");
    $pesticides = $db->fetchAll($stmt, ["%$search%", "%$search%"]);
} else {
    $stmt = $db->query("SELECT * FROM pesticides ORDER BY name LIMIT 20");
    $pesticides = $db->fetchAll($stmt);
}

$stmt = $db->query("SELECT * FROM crops ORDER BY name");
$crops = $db->fetchAll($stmt);

$state = isset($_GET['state']) ? $_GET['state'] : '';
$district = isset($_GET['district']) ? $_GET['district'] : '';

$product_query = "SELECT p.*, s.company_name, s.state, s.district, s.region
                  FROM products p
                  JOIN suppliers s ON p.supplier_id = s.id
                  WHERE p.status = 'available'";

$params = [];
if ($state) {
    $product_query .= " AND s.state = ?";
    $params[] = $state;
}
if ($district) {
    $product_query .= " AND s.district = ?";
    $params[] = $district;
}

$product_query .= " ORDER BY p.created_at DESC LIMIT 20";

$stmt = $db->query($product_query);
$products = $db->fetchAll($stmt, $params);

$crop_price_query = "SELECT * FROM crop_prices WHERE 1=1";
$crop_params = [];
if ($state) {
    $crop_price_query .= " AND state = ?";
    $crop_params[] = $state;
}
if ($district) {
    $crop_price_query .= " AND district = ?";
    $crop_params[] = $district;
}
$crop_price_query .= " ORDER BY created_at DESC";

$stmt = $db->query($crop_price_query);
$crop_prices = $db->fetchAll($stmt, $crop_params);

$states = ['Maharashtra', 'Karnataka', 'Gujarat', 'Madhya Pradesh', 'Rajasthan', 'Uttar Pradesh', 'Punjab', 'Haryana', 'Tamil Nadu', 'Andhra Pradesh'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Krishi Mandi - AGRIVISION</title>
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
                <a href="dashboard.php"><i class="fas fa-home"></i> <?php echo t('dashboard'); ?></a>
                <a href="my-field.php"><i class="fas fa-seedling"></i> <?php echo t('my_field'); ?></a>
                <a href="krishi-mandi.php" class="active"><i class="fas fa-store"></i> <?php echo t('krishi_mandi'); ?></a>
                <a href="my-greenhouse.php"><i class="fas fa-warehouse"></i> <?php echo t('my_greenhouse'); ?></a>
                <a href="ai-support.php"><i class="fas fa-robot"></i> <?php echo t('ai_support'); ?></a>
                <a href="appointments.php"><i class="fas fa-calendar"></i> <?php echo t('appointments'); ?></a>
                <a href="profile.php"><i class="fas fa-user"></i> <?php echo t('profile'); ?></a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <?php echo t('logout'); ?></a>
            </nav>
        </aside>
        
        <main class="main-content">
            <div class="top-bar">
                <div class="user-info">
                    <div class="user-avatar"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></div>
                    <div>
                        <h4><?php echo htmlspecialchars($user['name']); ?></h4>
                        <small><?php echo t('krishi_mandi'); ?></small>
                    </div>
                </div>
                <button class="theme-toggle" title="Toggle Theme">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
            
            <div class="card">
                <div style="display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;">
                    <a href="krishi-mandi.php?tab=prediction" class="btn <?php echo $tab == 'prediction' ? 'btn-primary' : 'btn-outline'; ?>">
                        <i class="fas fa-seedling"></i> <?php echo t('crop_prediction'); ?>
                    </a>
                    <a href="krishi-mandi.php?tab=pesticides" class="btn <?php echo $tab == 'pesticides' ? 'btn-primary' : 'btn-outline'; ?>">
                        <i class="fas fa-spray-can"></i> <?php echo t('pesticide_info'); ?>
                    </a>
                    <a href="krishi-mandi.php?tab=marketplace" class="btn <?php echo $tab == 'marketplace' ? 'btn-primary' : 'btn-outline'; ?>">
                        <i class="fas fa-shopping-cart"></i> <?php echo t('marketplace'); ?>
                    </a>
                </div>
                
                <?php if ($tab == 'prediction'): ?>
                    <div class="card-header">
                        <h3><i class="fas fa-seedling"></i> <?php echo t('crop_prediction'); ?></h3>
                    </div>
                    <form method="POST" action="crop-prediction.php" style="max-width: 500px;">
                        <div class="form-group">
                            <label for="sunlight"><?php echo t('sunlight'); ?></label>
                            <select id="sunlight" name="sunlight" required>
                                <option value="">Select sunlight level</option>
                                <option value="full">Full Sun (6+ hours)</option>
                                <option value="partial">Partial Sun (3-6 hours)</option>
                                <option value="shade">Shade (< 3 hours)</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="water"><?php echo t('water_level'); ?></label>
                            <select id="water" name="water" required>
                                <option value="">Select water level</option>
                                <option value="high">High (Abundant water)</option>
                                <option value="medium">Medium (Moderate water)</option>
                                <option value="low">Low (Limited water)</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="soil"><?php echo t('soil_quality'); ?></label>
                            <select id="soil" name="soil" required>
                                <option value="">Select soil type</option>
                                <option value="clay">Clay Soil</option>
                                <option value="sandy">Sandy Soil</option>
                                <option value="loamy">Loamy Soil</option>
                                <option value="silt">Silt Soil</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary"><?php echo t('predict_crops'); ?></button>
                    </form>
                    
                    <div style="margin-top: 30px;">
                        <h4><?php echo t('suitable_crops'); ?></h4>
                        <div class="grid-3" style="margin-top: 20px;">
                            <?php foreach ($crops as $crop): ?>
                                <div class="stat-card" style="text-align: left;">
                                    <h4 style="color: var(--primary-color);"><?php echo htmlspecialchars($crop['name']); ?></h4>
                                    <div style="margin-top: 10px; font-size: 0.9rem;">
                                        <p><strong>Sunlight:</strong> <?php echo htmlspecialchars($crop['sunlight_requirement']); ?></p>
                                        <p><strong>Water:</strong> <?php echo htmlspecialchars($crop['water_requirement']); ?></p>
                                        <p><strong>Soil:</strong> <?php echo htmlspecialchars($crop['soil_type']); ?></p>
                                        <p><strong>Season:</strong> <?php echo htmlspecialchars($crop['growing_season']); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                
                <?php elseif ($tab == 'pesticides'): ?>
                    <div class="card-header">
                        <h3><i class="fas fa-spray-can"></i> <?php echo t('pesticide_info'); ?></h3>
                    </div>
                    
                    <form method="GET" action="" style="margin-bottom: 20px; display: flex; gap: 10px;">
                        <input type="hidden" name="tab" value="pesticides">
                        <input type="text" name="search" placeholder="Search pesticides by name or company..." 
                               value="<?php echo htmlspecialchars($search); ?>" style="flex: 1; padding: 12px; border: 2px solid #ddd; border-radius: var(--radius);">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> <?php echo t('search'); ?></button>
                    </form>
                    
                    <div class="grid-2">
                        <?php foreach ($pesticides as $pesticide): ?>
                            <div class="card">
                                <h4 style="color: var(--primary-color); margin-bottom: 10px;"><?php echo htmlspecialchars($pesticide['name']); ?></h4>
                                <div style="font-size: 0.9rem; color: var(--text-light);">
                                    <p><strong>Company:</strong> <?php echo htmlspecialchars($pesticide['company']); ?></p>
                                    <p><strong>Manufacturer:</strong> <?php echo htmlspecialchars($pesticide['manufacturer']); ?></p>
                                    <p><strong>Usage:</strong> <?php echo htmlspecialchars(substr($pesticide['usage'], 0, 150)); ?>...</p>
                                    <p><strong>Target Crops:</strong> <?php echo htmlspecialchars($pesticide['target_crops']); ?></p>
                                    <p><strong>Safety:</strong> <?php echo htmlspecialchars(substr($pesticide['safety_info'], 0, 100)); ?>...</p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                
                <?php elseif ($tab == 'marketplace'): ?>
                    <div class="card-header">
                        <h3><i class="fas fa-shopping-cart"></i> <?php echo t('marketplace'); ?></h3>
                    </div>
                    
                    <form method="GET" action="" style="margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
                        <input type="hidden" name="tab" value="marketplace">
                        <select name="state" style="padding: 12px; border: 2px solid #ddd; border-radius: var(--radius);">
                            <option value="">All States</option>
                            <?php foreach ($states as $s): ?>
                                <option value="<?php echo $s; ?>" <?php echo $state == $s ? 'selected' : ''; ?>><?php echo $s; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" name="district" placeholder="District"
                               value="<?php echo htmlspecialchars($district); ?>"
                               style="padding: 12px; border: 2px solid #ddd; border-radius: var(--radius);">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
                    </form>
                    
                    <div class="grid-3">
                        <?php foreach ($products as $product): ?>
                            <div class="card" style="margin-bottom: 0;">
                                <div style="text-align: center; padding: 20px; background: var(--bg-light); border-radius: var(--radius); margin-bottom: 15px;">
                                    <i class="fas fa-box fa-3x" style="color: var(--primary-color);"></i>
                                </div>
                                <h4 style="color: var(--primary-color); margin-bottom: 5px;"><?php echo htmlspecialchars($product['product_name']); ?></h4>
                                <p style="font-size: 0.85rem; color: var(--text-light); margin-bottom: 10px;">
                                    <?php echo htmlspecialchars($product['category']); ?>
                                </p>
                                <p style="font-size: 0.9rem; margin-bottom: 10px;"><?php echo htmlspecialchars(substr($product['description'], 0, 80)); ?>...</p>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px;">
                                    <h4 style="color: var(--secondary-color); margin: 0;">₹<?php echo number_format($product['price'], 2); ?></h4>
                                    <small style="color: var(--text-light);">Stock: <?php echo $product['stock_quantity']; ?></small>
                                </div>
                                <p style="font-size: 0.85rem; color: var(--text-light); margin-top: 10px;">
                                    <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($product['district'] . ', ' . $product['state']); ?>
                                </p>
                                <p style="font-size: 0.85rem; color: var(--text-light);">
                                    <i class="fas fa-building"></i> <?php echo htmlspecialchars($product['company_name']); ?>
                                </p>
                                <button class="btn btn-primary btn-block" style="margin-top: 15px;">Contact Supplier</button>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="card" style="margin-top: 30px;">
                        <div class="card-header">
                            <h3><i class="fas fa-rupee-sign"></i> Crop Prices</h3>
                        </div>

                        <?php if (empty($crop_prices)): ?>
                            <p style="text-align: center; color: var(--text-light); padding: 40px;">
                                <i class="fas fa-rupee-sign fa-3x" style="display: block; margin-bottom: 15px;"></i>
                                No crop prices found for selected filters.
                            </p>
                        <?php else: ?>
                            <div style="overflow-x: auto;">
                                <table style="width: 100%; border-collapse: collapse; min-width: 600px;">
                                    <thead>
                                        <tr style="background: var(--bg-light); text-align: left;">
                                            <th style="padding: 12px; border-bottom: 2px solid #ddd;">Crop Name</th>
                                            <th style="padding: 12px; border-bottom: 2px solid #ddd;">State</th>
                                            <th style="padding: 12px; border-bottom: 2px solid #ddd;">District</th>
                                            <th style="padding: 12px; border-bottom: 2px solid #ddd;">Region</th>
                                            <th style="padding: 12px; border-bottom: 2px solid #ddd;">Description</th>
                                            <th style="padding: 12px; border-bottom: 2px solid #ddd;">Price (₹)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($crop_prices as $crop): ?>
                                            <tr style="border-bottom: 1px solid #eee;">
                                                <td style="padding: 12px; font-weight: 500; color: var(--primary-color);">
                                                    <?php echo htmlspecialchars($crop['crop_name']); ?>
                                                </td>
                                                <td style="padding: 12px;"><?php echo htmlspecialchars($crop['state']); ?></td>
                                                <td style="padding: 12px;"><?php echo htmlspecialchars($crop['district']); ?></td>
                                                <td style="padding: 12px;"><?php echo htmlspecialchars($crop['region'] ?? '-'); ?></td>
                                                <td style="padding: 12px;"><?php echo htmlspecialchars(substr($crop['description'] ?? '', 0, 50)); ?></td>
                                                <td style="padding: 12px; font-weight: bold; color: var(--secondary-color); font-size: 1.1rem;">
                                                    ₹<?php echo number_format($crop['price'], 2); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <nav class="bottom-nav">
        <div class="container">
            <a href="dashboard.php">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="my-field.php">
                <i class="fas fa-seedling"></i>
                <span>My Field</span>
            </a>
            <a href="krishi-mandi.php" class="active">
                <i class="fas fa-store"></i>
                <span>Krishi Mandi</span>
            </a>
            <a href="profile.php">
                <i class="fas fa-user"></i>
                <span>Profile</span>
            </a>
        </div>
    </nav>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>

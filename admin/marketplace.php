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

if (isset($_GET['delete_product'])) {
    $product_id = intval($_GET['delete_product']);
    $stmt = $db->query("DELETE FROM products WHERE id = ?");
    $db->execute($stmt, [$product_id]);
    header('Location: marketplace.php');
    exit();
}

if (isset($_GET['toggle_product'])) {
    $product_id = intval($_GET['toggle_product']);
    $stmt = $db->query("UPDATE products SET status = CASE WHEN status = 'available' THEN 'unavailable' ELSE 'available' END WHERE id = ?");
    $db->execute($stmt, [$product_id]);
    header('Location: marketplace.php');
    exit();
}

if (isset($_POST['add_crop_price'])) {
    $crop_name = trim($_POST['crop_name']);
    $state = trim($_POST['state']);
    $district = trim($_POST['district']);
    $region = trim($_POST['region']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);

    $stmt = $db->query("INSERT INTO crop_prices (crop_name, state, district, region, description, price) VALUES (?, ?, ?, ?, ?, ?)");
    $db->execute($stmt, [$crop_name, $state, $district, $region, $description, $price]);
    header('Location: marketplace.php');
    exit();
}

if (isset($_GET['delete_crop_price'])) {
    $crop_price_id = intval($_GET['delete_crop_price']);
    $stmt = $db->query("DELETE FROM crop_prices WHERE id = ?");
    $db->execute($stmt, [$crop_price_id]);
    header('Location: marketplace.php');
    exit();
}

$stmt = $db->query("SELECT p.*, s.company_name, s.state, s.district, s.contact_number
                    FROM products p
                    JOIN suppliers s ON p.supplier_id = s.id
                    ORDER BY p.created_at DESC");
$products = $db->fetchAll($stmt);

$stmt = $db->query("SELECT * FROM crop_prices ORDER BY created_at DESC");
$crop_prices = $db->fetchAll($stmt);

$stmt = $db->query("SELECT COUNT(*) as count FROM suppliers");
$total_suppliers = $db->fetch($stmt);

$stmt = $db->query("SELECT COUNT(*) as count FROM products WHERE status = 'available'");
$available_products = $db->fetch($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Marketplace - AGRIVISION Admin</title>
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
                <a href="fields.php"><i class="fas fa-map"></i> Fields & Crops</a>
                <a href="greenhouses.php"><i class="fas fa-warehouse"></i> Greenhouses</a>
                <a href="schemes.php"><i class="fas fa-hand-holding-usd"></i> Schemes</a>
                <a href="marketplace.php" class="active"><i class="fas fa-store"></i> Marketplace</a>
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
                    <i class="fas fa-store"></i>
                    <h4><?php echo count($products); ?></h4>
                    <p>Total Products</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-check-circle" style="color: #4caf50;"></i>
                    <h4><?php echo $available_products['count']; ?></h4>
                    <p>Available</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-building"></i>
                    <h4><?php echo $total_suppliers['count']; ?></h4>
                    <p>Suppliers</p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-plus-circle"></i> Add Crop Price</h3>
                </div>
                <form method="POST" action="marketplace.php" style="padding: 20px;">
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Crop Name</label>
                            <input type="text" name="crop_name" required
                                   style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">State</label>
                            <input type="text" name="state" required
                                   style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">District</label>
                            <input type="text" name="district" required
                                   style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Region</label>
                            <input type="text" name="region"
                                   style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Price (₹)</label>
                            <input type="number" name="price" step="0.01" required
                                   style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Description</label>
                            <input type="text" name="description"
                                   style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        </div>
                    </div>
                    <button type="submit" name="add_crop_price"
                            style="margin-top: 15px; padding: 10px 20px; background: var(--primary-color); color: white; border: none; border-radius: 5px; cursor: pointer;">
                        <i class="fas fa-plus"></i> Add Crop Price
                    </button>
                </form>
            </div>

            <div class="card" style="margin-top: 30px;">
                <div class="card-header">
                    <h3><i class="fas fa-seedling"></i> Crop Prices</h3>
                </div>

                <?php if (empty($crop_prices)): ?>
                    <p style="text-align: center; color: var(--text-light); padding: 40px;">
                        <i class="fas fa-seedling fa-3x" style="display: block; margin-bottom: 15px;"></i>
                        No crop prices added yet.
                    </p>
                <?php else: ?>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: var(--bg-light); text-align: left;">
                                <th style="padding: 12px; border-bottom: 2px solid #ddd;">Crop Name</th>
                                <th style="padding: 12px; border-bottom: 2px solid #ddd;">State</th>
                                <th style="padding: 12px; border-bottom: 2px solid #ddd;">District</th>
                                <th style="padding: 12px; border-bottom: 2px solid #ddd;">Region</th>
                                <th style="padding: 12px; border-bottom: 2px solid #ddd;">Description</th>
                                <th style="padding: 12px; border-bottom: 2px solid #ddd;">Price</th>
                                <th style="padding: 12px; border-bottom: 2px solid #ddd;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($crop_prices as $crop): ?>
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding: 12px;"><?php echo htmlspecialchars($crop['crop_name']); ?></td>
                                    <td style="padding: 12px;"><?php echo htmlspecialchars($crop['state']); ?></td>
                                    <td style="padding: 12px;"><?php echo htmlspecialchars($crop['district']); ?></td>
                                    <td style="padding: 12px;"><?php echo htmlspecialchars($crop['region'] ?? '-'); ?></td>
                                    <td style="padding: 12px;"><?php echo htmlspecialchars(substr($crop['description'] ?? '', 0, 50)); ?></td>
                                    <td style="padding: 12px; font-weight: bold; color: var(--secondary-color);">₹<?php echo number_format($crop['price'], 2); ?></td>
                                    <td style="padding: 12px;">
                                        <a href="marketplace.php?delete_crop_price=<?php echo $crop['id']; ?>"
                                           style="color: #f44336; text-decoration: none;"
                                           onclick="return confirm('Are you sure you want to delete this crop price?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>

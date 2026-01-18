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

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$query = "SELECT * FROM users WHERE role = 'farmer'";
$params = [];

if ($search) {
    $query .= " AND (name LIKE ? OR mobile LIKE ? OR state LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%"];
}

$query .= " ORDER BY created_at DESC";

$stmt = $db->query($query);
$farmers = $db->fetchAll($stmt, $params);

if (isset($_GET['delete'])) {
    $user_id = intval($_GET['delete']);
    $stmt = $db->query("DELETE FROM users WHERE id = ? AND role = 'farmer'");
    $db->execute($stmt, [$user_id]);
    header('Location: farmers.php');
    exit();
}

if (isset($_GET['toggle_status'])) {
    $user_id = intval($_GET['toggle_status']);
    $stmt = $db->query("UPDATE users SET status = CASE WHEN status = 'active' THEN 'inactive' ELSE 'active' END WHERE id = ? AND role = 'farmer'");
    $db->execute($stmt, [$user_id]);
    header('Location: farmers.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Farmers - AGRIVISION Admin</title>
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
                <a href="farmers.php" class="active"><i class="fas fa-users"></i> Farmers</a>
                <a href="fields.php"><i class="fas fa-map"></i> Fields & Crops</a>
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
            
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-users"></i> Manage Farmers</h3>
                    <span class="btn btn-sm" style="background: var(--primary-color); color: white;">
                        Total: <?php echo count($farmers); ?>
                    </span>
                </div>
                
                <form method="GET" action="" style="margin-bottom: 20px; display: flex; gap: 10px;">
                    <input type="text" name="search" placeholder="Search by name, mobile, or state..." 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           style="flex: 1; padding: 12px; border: 2px solid #ddd; border-radius: var(--radius);">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                    <a href="farmers.php" class="btn btn-outline"><i class="fas fa-times"></i> Clear</a>
                </form>
                
                <?php if (empty($farmers)): ?>
                    <p style="text-align: center; color: var(--text-light); padding: 40px;">
                        <i class="fas fa-users fa-3x" style="display: block; margin-bottom: 15px;"></i>
                        No farmers found.
                    </p>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: var(--bg-light); border-bottom: 2px solid #ddd;">
                                    <th style="padding: 15px; text-align: left;">Name</th>
                                    <th style="padding: 15px; text-align: left;">Mobile</th>
                                    <th style="padding: 15px; text-align: left;">Location</th>
                                    <th style="padding: 15px; text-align: left;">Language</th>
                                    <th style="padding: 15px; text-align: left;">Status</th>
                                    <th style="padding: 15px; text-align: left;">Joined</th>
                                    <th style="padding: 15px; text-align: center;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($farmers as $farmer): ?>
                                    <tr style="border-bottom: 1px solid #ddd;">
                                        <td style="padding: 15px;">
                                            <strong><?php echo htmlspecialchars($farmer['name']); ?></strong>
                                        </td>
                                        <td style="padding: 15px;"><?php echo htmlspecialchars($farmer['mobile']); ?></td>
                                        <td style="padding: 15px;">
                                            <?php 
                                            $location = array_filter([$farmer['district'], $farmer['state']]);
                                            echo htmlspecialchars(implode(', ', $location) ?: 'N/A');
                                            ?>
                                        </td>
                                        <td style="padding: 15px;"><?php echo strtoupper($farmer['language'] ?: 'EN'); ?></td>
                                        <td style="padding: 15px;">
                                            <span class="btn btn-sm" style="
                                                <?php 
                                                echo match($farmer['status']) {
                                                    'active' => 'background: #4caf50; color: white;',
                                                    'inactive' => 'background: #9e9e9e; color: white;',
                                                    'pending' => 'background: #ff9800; color: white;',
                                                    default => 'background: #f44336; color: white;'
                                                };
                                                ?>">
                                                <?php echo ucfirst($farmer['status']); ?>
                                            </span>
                                        </td>
                                        <td style="padding: 15px;"><?php echo date('M d, Y', strtotime($farmer['created_at'])); ?></td>
                                        <td style="padding: 15px; text-align: center;">
                                            <a href="farmers.php?toggle_status=<?php echo $farmer['id']; ?>" 
                                               class="btn btn-sm" style="color: #ff9800; border-color: #ff9800;"
                                               title="Toggle Status">
                                                <i class="fas fa-toggle-on"></i>
                                            </a>
                                            <a href="farmers.php?delete=<?php echo $farmer['id']; ?>" 
                                               class="btn btn-sm" style="color: #f44336; border-color: #f44336;"
                                               onclick="return confirm('Are you sure you want to delete this farmer?')"
                                               title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>

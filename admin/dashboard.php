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

$stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'farmer'");
$total_farmers = $db->fetch($stmt);

$stmt = $db->query("SELECT COUNT(*) as count FROM fields");
$total_fields = $db->fetch($stmt);

$stmt = $db->query("SELECT COUNT(*) as count FROM greenhouses");
$total_greenhouses = $db->fetch($stmt);

$stmt = $db->query("SELECT COUNT(*) as count FROM appointments WHERE status = 'pending'");
$pending_appointments = $db->fetch($stmt);

$stmt = $db->query("SELECT COUNT(*) as count FROM ai_queries WHERE DATE(created_at) = CURDATE()");
$today_queries = $db->fetch($stmt);

$stmt = $db->query("SELECT * FROM users WHERE role = 'farmer' ORDER BY created_at DESC LIMIT 5");
$recent_farmers = $db->fetchAll($stmt);

$stmt = $db->query("SELECT * FROM appointments WHERE status = 'pending' ORDER BY created_at DESC LIMIT 5");
$recent_appointments = $db->fetchAll($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - AGRIVISION</title>
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
                <a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="farmers.php"><i class="fas fa-users"></i> Farmers</a>
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
            
            <div class="grid-4" style="margin-bottom: 30px;">
                <div class="stat-card">
                    <i class="fas fa-users"></i>
                    <h4><?php echo $total_farmers['count']; ?></h4>
                    <p>Total Farmers</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-map"></i>
                    <h4><?php echo $total_fields['count']; ?></h4>
                    <p>Total Fields</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-warehouse"></i>
                    <h4><?php echo $total_greenhouses['count']; ?></h4>
                    <p>Greenhouses</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-calendar-check"></i>
                    <h4><?php echo $pending_appointments['count']; ?></h4>
                    <p>Pending Appointments</p>
                </div>
            </div>
            
            <div class="grid-2" style="margin-bottom: 30px;">
                <div class="stat-card">
                    <i class="fas fa-robot" style="color: #9c27b0;"></i>
                    <h4><?php echo $today_queries['count']; ?></h4>
                    <p>Today's AI Queries</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-chart-line" style="color: #ff9800;"></i>
                    <h4>Active</h4>
                    <p>System Status</p>
                </div>
            </div>
            
            <div class="grid-2">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-user-plus"></i> Recent Farmers</h3>
                        <a href="farmers.php" class="btn btn-sm btn-outline">View All</a>
                    </div>
                    <?php if (empty($recent_farmers)): ?>
                        <p style="text-align: center; color: var(--text-light); padding: 20px;">No farmers registered yet.</p>
                    <?php else: ?>
                        <?php foreach ($recent_farmers as $farmer): ?>
                            <div style="padding: 15px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <h4 style="margin: 0;"><?php echo htmlspecialchars($farmer['name']); ?></h4>
                                    <small style="color: var(--text-light);"><?php echo htmlspecialchars($farmer['mobile']); ?></small>
                                </div>
                                <small style="color: var(--text-light);"><?php echo date('M d', strtotime($farmer['created_at'])); ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-calendar"></i> Pending Appointments</h3>
                        <a href="appointments.php" class="btn btn-sm btn-outline">View All</a>
                    </div>
                    <?php if (empty($recent_appointments)): ?>
                        <p style="text-align: center; color: var(--text-light); padding: 20px;">No pending appointments.</p>
                    <?php else: ?>
                        <?php foreach ($recent_appointments as $apt): ?>
                            <div style="padding: 15px; border-bottom: 1px solid #ddd;">
                                <div style="display: flex; justify-content: space-between; align-items: start;">
                                    <div>
                                        <h4 style="margin: 0;"><?php echo htmlspecialchars($apt['name']); ?></h4>
                                        <small style="color: var(--text-light);"><?php echo date('M d, Y', strtotime($apt['appointment_date'])); ?></small>
                                    </div>
                                    <span style="background: #ff9800; color: white; padding: 3px 10px; border-radius: 12px; font-size: 0.75rem;">Pending</span>
                                </div>
                                <p style="margin: 5px 0 0; font-size: 0.85rem; color: var(--text-light);">
                                    <?php echo htmlspecialchars(substr($apt['reason'], 0, 50)); ?>...
                                </p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>

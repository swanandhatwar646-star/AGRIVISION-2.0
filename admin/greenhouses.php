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

$stmt = $db->query("SELECT g.*, u.name as farmer_name, u.mobile as farmer_mobile 
                    FROM greenhouses g 
                    JOIN users u ON g.user_id = u.id 
                    ORDER BY g.id DESC");
$greenhouses = $db->fetchAll($stmt);

$selected_gh_id = isset($_GET['gh_id']) ? intval($_GET['gh_id']) : 0;
$gh_alerts = [];

if ($selected_gh_id > 0) {
    $stmt = $db->query("SELECT * FROM greenhouse_alerts WHERE greenhouse_id = ? ORDER BY created_at DESC LIMIT 20");
    $gh_alerts = $db->fetchAll($stmt, [$selected_gh_id]);
}

if (isset($_GET['resolve_alert'])) {
    $alert_id = intval($_GET['resolve_alert']);
    $gh_id = intval($_GET['gh_id']);
    $stmt = $db->query("UPDATE greenhouse_alerts SET is_resolved = TRUE WHERE id = ?");
    $db->execute($stmt, [$alert_id]);
    header("Location: greenhouses.php?gh_id=$gh_id");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Greenhouses - AGRIVISION Admin</title>
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
                <a href="greenhouses.php" class="active"><i class="fas fa-warehouse"></i> Greenhouses</a>
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
                    <i class="fas fa-warehouse"></i>
                    <h4><?php echo count($greenhouses); ?></h4>
                    <p>Total Greenhouses</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-thermometer-half" style="color: #f44336;"></i>
                    <h4>
                        <?php 
                        $avg_temp = 0;
                        if (!empty($greenhouses)) {
                            $total = array_sum(array_column($greenhouses, 'temperature'));
                            $avg_temp = round($total / count($greenhouses), 1);
                        }
                        echo $avg_temp;
                        ?>°C
                    </h4>
                    <p>Avg Temperature</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-tint" style="color: #2196f3;"></i>
                    <h4>
                        <?php 
                        $avg_humidity = 0;
                        if (!empty($greenhouses)) {
                            $total = array_sum(array_column($greenhouses, 'humidity'));
                            $avg_humidity = round($total / count($greenhouses), 1);
                        }
                        echo $avg_humidity;
                        ?>%
                    </h4>
                    <p>Avg Humidity</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-bell" style="color: #ff9800;"></i>
                    <h4>
                        <?php 
                        $stmt = $db->query("SELECT COUNT(*) as count FROM greenhouse_alerts WHERE is_resolved = FALSE");
                        $active_alerts = $db->fetch($stmt);
                        echo $active_alerts['count'];
                        ?>
                    </h4>
                    <p>Active Alerts</p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-warehouse"></i> All Greenhouses</h3>
                </div>
                
                <?php if (empty($greenhouses)): ?>
                    <p style="text-align: center; color: var(--text-light); padding: 40px;">
                        <i class="fas fa-warehouse fa-3x" style="display: block; margin-bottom: 15px;"></i>
                        No greenhouses found.
                    </p>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: var(--bg-light); border-bottom: 2px solid #ddd;">
                                    <th style="padding: 15px; text-align: left;">Name</th>
                                    <th style="padding: 15px; text-align: left;">Farmer</th>
                                    <th style="padding: 15px; text-align: left;">Location</th>
                                    <th style="padding: 15px; text-align: left;">Temperature</th>
                                    <th style="padding: 15px; text-align: left;">Humidity</th>
                                    <th style="padding: 15px; text-align: left;">Soil Moisture</th>
                                    <th style="padding: 15px; text-align: left;">Systems</th>
                                    <th style="padding: 15px; text-align: left;">Last Updated</th>
                                    <th style="padding: 15px; text-align: center;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($greenhouses as $gh): ?>
                                    <tr style="border-bottom: 1px solid #ddd;">
                                        <td style="padding: 15px;">
                                            <strong><?php echo htmlspecialchars($gh['name']); ?></strong>
                                        </td>
                                        <td style="padding: 15px;">
                                            <?php echo htmlspecialchars($gh['farmer_name']); ?><br>
                                            <small style="color: var(--text-light);"><?php echo htmlspecialchars($gh['farmer_mobile']); ?></small>
                                        </td>
                                        <td style="padding: 15px;"><?php echo htmlspecialchars($gh['location'] ?: 'N/A'); ?></td>
                                        <td style="padding: 15px;">
                                            <span style="color: <?php echo $gh['temperature'] > 35 ? '#f44336' : ($gh['temperature'] < 15 ? '#2196f3' : '#4caf50'); ?>;">
                                                <?php echo $gh['temperature']; ?>°C
                                            </span>
                                        </td>
                                        <td style="padding: 15px;"><?php echo $gh['humidity']; ?>%</td>
                                        <td style="padding: 15px;"><?php echo $gh['soil_moisture']; ?>%</td>
                                        <td style="padding: 15px; font-size: 0.85rem;">
                                            <span style="color: <?php echo $gh['irrigation_system'] == 'on' ? '#4caf50' : '#9e9e9e'; ?>;">
                                                <i class="fas fa-shower"></i> <?php echo strtoupper($gh['irrigation_system']); ?>
                                            </span><br>
                                            <span style="color: <?php echo $gh['ventilation_system'] == 'on' ? '#4caf50' : '#9e9e9e'; ?>;">
                                                <i class="fas fa-fan"></i> <?php echo strtoupper($gh['ventilation_system']); ?>
                                            </span><br>
                                            <span style="color: <?php echo $gh['cooling_system'] == 'on' ? '#4caf50' : '#9e9e9e'; ?>;">
                                                <i class="fas fa-snowflake"></i> <?php echo strtoupper($gh['cooling_system']); ?>
                                            </span>
                                        </td>
                                        <td style="padding: 15px;"><?php echo date('M d, H:i', strtotime($gh['last_updated'])); ?></td>
                                        <td style="padding: 15px; text-align: center;">
                                            <a href="greenhouses.php?gh_id=<?php echo $gh['id']; ?>" 
                                               class="btn btn-sm btn-primary">
                                                <i class="fas fa-bell"></i> Alerts
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($selected_gh_id > 0): ?>
                <div class="card" style="margin-top: 20px;">
                    <div class="card-header">
                        <h3><i class="fas fa-bell"></i> Greenhouse Alerts</h3>
                        <a href="greenhouses.php" class="btn btn-sm btn-outline">Close</a>
                    </div>
                    <?php if (empty($gh_alerts)): ?>
                        <p style="text-align: center; color: var(--text-light); padding: 30px;">
                            <i class="fas fa-check-circle fa-2x" style="color: #4caf50; display: block; margin-bottom: 10px;"></i>
                            No alerts for this greenhouse.
                        </p>
                    <?php else: ?>
                        <?php foreach ($gh_alerts as $alert): ?>
                            <div style="padding: 15px; margin-bottom: 15px; background: var(--bg-light); border-left: 4px solid <?php echo $alert['severity'] == 'high' ? '#f44336' : ($alert['severity'] == 'medium' ? '#ff9800' : '#ffeb3b'); ?>; border-radius: var(--radius);">
                                <div style="display: flex; justify-content: space-between; align-items: start;">
                                    <div>
                                        <h4 style="margin: 0 0 5px;"><?php echo htmlspecialchars($alert['alert_type']); ?></h4>
                                        <p style="margin: 0; color: var(--text-light);"><?php echo htmlspecialchars($alert['message']); ?></p>
                                        <small style="color: var(--text-light);"><?php echo date('M d, Y H:i', strtotime($alert['created_at'])); ?></small>
                                    </div>
                                    <?php if (!$alert['is_resolved']): ?>
                                        <a href="greenhouses.php?gh_id=<?php echo $selected_gh_id; ?>&resolve_alert=<?php echo $alert['id']; ?>" 
                                           class="btn btn-sm" style="color: #4caf50; border-color: #4caf50;">
                                            <i class="fas fa-check"></i> Resolve
                                        </a>
                                    <?php else: ?>
                                        <span class="btn btn-sm" style="background: #4caf50; color: white;">Resolved</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>

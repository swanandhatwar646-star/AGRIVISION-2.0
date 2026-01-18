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

if (isset($_GET['update_status'])) {
    $apt_id = intval($_GET['update_status']);
    $status = $_GET['status'] ?? 'pending';
    $stmt = $db->query("UPDATE appointments SET status = ? WHERE id = ?");
    $db->execute($stmt, [$status, $apt_id]);
    header('Location: appointments.php');
    exit();
}

if (isset($_GET['delete'])) {
    $apt_id = intval($_GET['delete']);
    $stmt = $db->query("DELETE FROM appointments WHERE id = ?");
    $db->execute($stmt, [$apt_id]);
    header('Location: appointments.php');
    exit();
}

$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

$query = "SELECT a.*, u.name as user_name, u.mobile as user_mobile 
          FROM appointments a 
          JOIN users u ON a.user_id = u.id";

$params = [];
if ($status_filter) {
    $query .= " WHERE a.status = ?";
    $params[] = $status_filter;
}

$query .= " ORDER BY a.appointment_date DESC";

$stmt = $db->query($query);
$appointments = $db->fetchAll($stmt, $params);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Appointments - AGRIVISION Admin</title>
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
                <a href="marketplace.php"><i class="fas fa-store"></i> Marketplace</a>
                <a href="appointments.php" class="active"><i class="fas fa-calendar"></i> Appointments</a>
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
                    <i class="fas fa-calendar"></i>
                    <h4><?php echo count($appointments); ?></h4>
                    <p>Total Appointments</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-clock" style="color: #ff9800;"></i>
                    <h4>
                        <?php 
                        $stmt = $db->query("SELECT COUNT(*) as count FROM appointments WHERE status = 'pending'");
                        $pending = $db->fetch($stmt);
                        echo $pending['count'];
                        ?>
                    </h4>
                    <p>Pending</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-check-circle" style="color: #4caf50;"></i>
                    <h4>
                        <?php 
                        $stmt = $db->query("SELECT COUNT(*) as count FROM appointments WHERE status = 'confirmed'");
                        $confirmed = $db->fetch($stmt);
                        echo $confirmed['count'];
                        ?>
                    </h4>
                    <p>Confirmed</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-check-double" style="color: #2196f3;"></i>
                    <h4>
                        <?php 
                        $stmt = $db->query("SELECT COUNT(*) as count FROM appointments WHERE status = 'completed'");
                        $completed = $db->fetch($stmt);
                        echo $completed['count'];
                        ?>
                    </h4>
                    <p>Completed</p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-calendar"></i> All Appointments</h3>
                    <div style="display: flex; gap: 10px;">
                        <a href="appointments.php" class="btn btn-sm <?php echo $status_filter == '' ? 'btn-primary' : 'btn-outline'; ?>">All</a>
                        <a href="appointments.php?status=pending" class="btn btn-sm <?php echo $status_filter == 'pending' ? 'btn-primary' : 'btn-outline'; ?>">Pending</a>
                        <a href="appointments.php?status=confirmed" class="btn btn-sm <?php echo $status_filter == 'confirmed' ? 'btn-primary' : 'btn-outline'; ?>">Confirmed</a>
                        <a href="appointments.php?status=completed" class="btn btn-sm <?php echo $status_filter == 'completed' ? 'btn-primary' : 'btn-outline'; ?>">Completed</a>
                    </div>
                </div>
                
                <?php if (empty($appointments)): ?>
                    <p style="text-align: center; color: var(--text-light); padding: 40px;">
                        <i class="fas fa-calendar fa-3x" style="display: block; margin-bottom: 15px;"></i>
                        No appointments found.
                    </p>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: var(--bg-light); border-bottom: 2px solid #ddd;">
                                    <th style="padding: 15px; text-align: left;">Name</th>
                                    <th style="padding: 15px; text-align: left;">User</th>
                                    <th style="padding: 15px; text-align: left;">Date</th>
                                    <th style="padding: 15px; text-align: left;">Contact</th>
                                    <th style="padding: 15px; text-align: left;">Reason</th>
                                    <th style="padding: 15px; text-align: left;">Status</th>
                                    <th style="padding: 15px; text-align: left;">Booked</th>
                                    <th style="padding: 15px; text-align: center;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($appointments as $apt): ?>
                                    <tr style="border-bottom: 1px solid #ddd;">
                                        <td style="padding: 15px;">
                                            <strong><?php echo htmlspecialchars($apt['name']); ?></strong>
                                        </td>
                                        <td style="padding: 15px;">
                                            <?php echo htmlspecialchars($apt['user_name']); ?><br>
                                            <small style="color: var(--text-light);"><?php echo htmlspecialchars($apt['user_mobile']); ?></small>
                                        </td>
                                        <td style="padding: 15px;"><?php echo date('M d, Y', strtotime($apt['appointment_date'])); ?></td>
                                        <td style="padding: 15px;"><?php echo htmlspecialchars($apt['contact_number']); ?></td>
                                        <td style="padding: 15px; max-width: 200px;">
                                            <?php echo htmlspecialchars(substr($apt['reason'], 0, 50)); ?>...
                                        </td>
                                        <td style="padding: 15px;">
                                            <span class="btn btn-sm" style="
                                                <?php 
                                                echo match($apt['status']) {
                                                    'pending' => 'background: #ff9800; color: white;',
                                                    'confirmed' => 'background: #4caf50; color: white;',
                                                    'completed' => 'background: #2196f3; color: white;',
                                                    'cancelled' => 'background: #f44336; color: white;',
                                                    default => 'background: #9e9e9e; color: white;'
                                                };
                                                ?>">
                                                <?php echo ucfirst($apt['status']); ?>
                                            </span>
                                        </td>
                                        <td style="padding: 15px;"><?php echo date('M d, H:i', strtotime($apt['created_at'])); ?></td>
                                        <td style="padding: 15px; text-align: center;">
                                            <div style="display: flex; gap: 5px; justify-content: center;">
                                                <?php if ($apt['status'] == 'pending'): ?>
                                                    <a href="appointments.php?update_status=<?php echo $apt['id']; ?>&status=confirmed" 
                                                       class="btn btn-sm" style="color: #4caf50; border-color: #4caf50;" title="Confirm">
                                                        <i class="fas fa-check"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($apt['status'] == 'confirmed'): ?>
                                                    <a href="appointments.php?update_status=<?php echo $apt['id']; ?>&status=completed" 
                                                       class="btn btn-sm" style="color: #2196f3; border-color: #2196f3;" title="Complete">
                                                        <i class="fas fa-check-double"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <a href="appointments.php?update_status=<?php echo $apt['id']; ?>&status=cancelled" 
                                                   class="btn btn-sm" style="color: #f44336; border-color: #f44336;" title="Cancel">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                                <a href="appointments.php?delete=<?php echo $apt['id']; ?>" 
                                                   class="btn btn-sm" style="color: #9e9e9e; border-color: #9e9e9e;"
                                                   onclick="return confirm('Are you sure you want to delete this appointment?')"
                                                   title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
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

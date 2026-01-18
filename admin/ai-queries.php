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
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';

$query = "SELECT aq.*, u.name as user_name, u.mobile as user_mobile 
          FROM ai_queries aq 
          JOIN users u ON aq.user_id = u.id";

$params = [];
$conditions = [];

if ($search) {
    $conditions[] = "(aq.query LIKE ? OR aq.response LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($type_filter) {
    $conditions[] = "aq.query_type = ?";
    $params[] = $type_filter;
}

if (!empty($conditions)) {
    $query .= " WHERE " . implode(' AND ', $conditions);
}

$query .= " ORDER BY aq.created_at DESC LIMIT 50";

$stmt = $db->query($query);
$queries = $db->fetchAll($stmt, $params);

$stmt = $db->query("SELECT COUNT(*) as count FROM ai_queries WHERE DATE(created_at) = CURDATE()");
$today_queries = $db->fetch($stmt);

$stmt = $db->query("SELECT query_type, COUNT(*) as count FROM ai_queries GROUP BY query_type ORDER BY count DESC");
$query_types = $db->fetchAll($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Queries - AGRIVISION Admin</title>
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
                <a href="appointments.php"><i class="fas fa-calendar"></i> Appointments</a>
                <a href="ai-queries.php" class="active"><i class="fas fa-robot"></i> AI Queries</a>
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
                    <i class="fas fa-robot"></i>
                    <h4><?php echo count($queries); ?></h4>
                    <p>Total Queries</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-clock" style="color: #ff9800;"></i>
                    <h4><?php echo $today_queries['count']; ?></h4>
                    <p>Today's Queries</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-chart-bar" style="color: #9c27b0;"></i>
                    <h4><?php echo count($query_types); ?></h4>
                    <p>Query Types</p>
                </div>
            </div>
            
            <div class="grid-2" style="margin-bottom: 30px;">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-pie"></i> Query Types</h3>
                    </div>
                    <?php if (empty($query_types)): ?>
                        <p style="text-align: center; color: var(--text-light); padding: 20px;">No query data yet.</p>
                    <?php else: ?>
                        <?php foreach ($query_types as $type): ?>
                            <div style="padding: 15px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between;">
                                <span><?php echo ucfirst($type['query_type'] ?: 'General'); ?></span>
                                <strong><?php echo $type['count']; ?></strong>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-filter"></i> Filter</h3>
                    </div>
                    <form method="GET" action="">
                        <div class="form-group">
                            <label for="search">Search</label>
                            <input type="text" id="search" name="search" placeholder="Search queries..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="form-group">
                            <label for="type">Query Type</label>
                            <select id="type" name="type">
                                <option value="">All Types</option>
                                <option value="crop_health" <?php echo $type_filter == 'crop_health' ? 'selected' : ''; ?>>Crop Health</option>
                                <option value="fertilizer" <?php echo $type_filter == 'fertilizer' ? 'selected' : ''; ?>>Fertilizer</option>
                                <option value="pest" <?php echo $type_filter == 'pest' ? 'selected' : ''; ?>>Pest</option>
                                <option value="weather" <?php echo $type_filter == 'weather' ? 'selected' : ''; ?>>Weather</option>
                                <option value="general" <?php echo $type_filter == 'general' ? 'selected' : ''; ?>>General</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">Apply Filter</button>
                        <a href="ai-queries.php" class="btn btn-outline btn-block" style="margin-top: 10px;">Clear</a>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-comments"></i> Recent AI Queries</h3>
                    <span class="btn btn-sm" style="background: var(--primary-color); color: white;">
                        Showing: <?php echo count($queries); ?>
                    </span>
                </div>
                
                <?php if (empty($queries)): ?>
                    <p style="text-align: center; color: var(--text-light); padding: 40px;">
                        <i class="fas fa-robot fa-3x" style="display: block; margin-bottom: 15px;"></i>
                        No AI queries found.
                    </p>
                <?php else: ?>
                    <?php foreach ($queries as $query): ?>
                        <div style="padding: 20px; border-bottom: 1px solid #ddd; margin-bottom: 15px;">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                                <div>
                                    <h4 style="margin: 0; color: var(--primary-color);">
                                        <?php echo htmlspecialchars($query['user_name']); ?>
                                    </h4>
                                    <small style="color: var(--text-light);"><?php echo htmlspecialchars($query['user_mobile']); ?></small>
                                </div>
                                <div style="text-align: right;">
                                    <span class="btn btn-sm" style="background: #9c27b0; color: white;">
                                        <?php echo ucfirst($query['query_type'] ?: 'General'); ?>
                                    </span>
                                    <br>
                                    <small style="color: var(--text-light);"><?php echo date('M d, H:i', strtotime($query['created_at'])); ?></small>
                                </div>
                            </div>
                            <div style="background: var(--bg-light); padding: 15px; border-radius: var(--radius); margin-bottom: 10px;">
                                <strong><i class="fas fa-question-circle"></i> Query:</strong>
                                <p style="margin: 5px 0;"><?php echo htmlspecialchars($query['query']); ?></p>
                            </div>
                            <div style="background: #e3f2fd; padding: 15px; border-radius: var(--radius);">
                                <strong><i class="fas fa-robot"></i> AI Response:</strong>
                                <p style="margin: 5px 0; white-space: pre-wrap;"><?php echo htmlspecialchars($query['response']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>

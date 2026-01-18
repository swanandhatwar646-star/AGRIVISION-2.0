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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $eligibility = trim($_POST['eligibility'] ?? '');
    $benefits = trim($_POST['benefits'] ?? '');
    $application_link = trim($_POST['application_link'] ?? '');
    $last_date = $_POST['last_date'] ?? '';
    
    if (empty($title)) {
        $error = 'Title is required.';
    } else {
        $stmt = $db->query("INSERT INTO government_schemes (title, description, eligibility, benefits, application_link, last_date) VALUES (?, ?, ?, ?, ?, ?)");
        if ($db->execute($stmt, [$title, $description, $eligibility, $benefits, $application_link, $last_date])) {
            $success = 'Scheme added successfully!';
        } else {
            $error = 'Failed to add scheme.';
        }
    }
}

if (isset($_GET['delete'])) {
    $scheme_id = intval($_GET['delete']);
    $stmt = $db->query("DELETE FROM government_schemes WHERE id = ?");
    $db->execute($stmt, [$scheme_id]);
    header('Location: schemes.php');
    exit();
}

if (isset($_GET['toggle_status'])) {
    $scheme_id = intval($_GET['toggle_status']);
    $stmt = $db->query("UPDATE government_schemes SET status = CASE WHEN status = 'active' THEN 'inactive' ELSE 'active' END WHERE id = ?");
    $db->execute($stmt, [$scheme_id]);
    header('Location: schemes.php');
    exit();
}

$stmt = $db->query("SELECT * FROM government_schemes ORDER BY created_at DESC");
$schemes = $db->fetchAll($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Schemes - AGRIVISION Admin</title>
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
                <a href="schemes.php" class="active"><i class="fas fa-hand-holding-usd"></i> Schemes</a>
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
            
            <div class="grid-2">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-plus-circle"></i> Add New Scheme</h3>
                    </div>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="title">Scheme Title *</label>
                            <input type="text" id="title" name="title" required 
                                   placeholder="Enter scheme title"
                                   value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="3" 
                                      placeholder="Enter scheme description"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="eligibility">Eligibility</label>
                            <textarea id="eligibility" name="eligibility" rows="2" 
                                      placeholder="Who is eligible for this scheme?"><?php echo htmlspecialchars($_POST['eligibility'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="benefits">Benefits</label>
                            <textarea id="benefits" name="benefits" rows="2" 
                                      placeholder="What are the benefits?"><?php echo htmlspecialchars($_POST['benefits'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="application_link">Application Link</label>
                            <input type="text" id="application_link" name="application_link" 
                                   placeholder="Enter application URL"
                                   value="<?php echo htmlspecialchars($_POST['application_link'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="last_date">Last Date</label>
                            <input type="date" id="last_date" name="last_date" 
                                   value="<?php echo htmlspecialchars($_POST['last_date'] ?? ''); ?>">
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-block">Add Scheme</button>
                    </form>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-list"></i> All Schemes</h3>
                        <span class="btn btn-sm" style="background: var(--primary-color); color: white;">
                            Total: <?php echo count($schemes); ?>
                        </span>
                    </div>
                    
                    <?php if (empty($schemes)): ?>
                        <p style="text-align: center; color: var(--text-light); padding: 40px;">
                            <i class="fas fa-hand-holding-usd fa-3x" style="display: block; margin-bottom: 15px;"></i>
                            No schemes found.
                        </p>
                    <?php else: ?>
                        <div style="max-height: 600px; overflow-y: auto;">
                            <?php foreach ($schemes as $scheme): ?>
                                <div style="padding: 15px; border-bottom: 1px solid #ddd;">
                                    <div style="display: flex; justify-content: space-between; align-items: start;">
                                        <div style="flex: 1;">
                                            <h4 style="margin: 0 0 5px; color: var(--primary-color);">
                                                <?php echo htmlspecialchars($scheme['title']); ?>
                                            </h4>
                                            <p style="margin: 5px 0; font-size: 0.9rem; color: var(--text-light);">
                                                <?php echo htmlspecialchars(substr($scheme['description'], 0, 100)); ?>...
                                            </p>
                                            <?php if ($scheme['last_date']): ?>
                                                <small style="color: var(--text-light);">
                                                    <i class="fas fa-calendar"></i> Last Date: <?php echo date('M d, Y', strtotime($scheme['last_date'])); ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                        <div style="margin-left: 15px; display: flex; flex-direction: column; gap: 5px;">
                                            <span class="btn btn-sm" style="
                                                <?php 
                                                echo $scheme['status'] == 'active' ? 'background: #4caf50; color: white;' : 'background: #9e9e9e; color: white;';
                                                ?>">
                                                <?php echo ucfirst($scheme['status']); ?>
                                            </span>
                                            <a href="schemes.php?toggle_status=<?php echo $scheme['id']; ?>" 
                                               class="btn btn-sm" style="color: #ff9800; border-color: #ff9800;">
                                                <i class="fas fa-toggle-on"></i>
                                            </a>
                                            <a href="schemes.php?delete=<?php echo $scheme['id']; ?>" 
                                               class="btn btn-sm" style="color: #f44336; border-color: #f44336;"
                                               onclick="return confirm('Are you sure you want to delete this scheme?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>

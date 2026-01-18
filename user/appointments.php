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

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $appointment_date = $_POST['appointment_date'] ?? '';
    $contact_number = trim($_POST['contact_number'] ?? '');
    $reason = trim($_POST['reason'] ?? '');
    
    if (empty($name) || empty($appointment_date) || empty($contact_number)) {
        $error = 'Name, appointment date, and contact number are required.';
    } elseif (strlen($contact_number) != 10 || !is_numeric($contact_number)) {
        $error = 'Please enter a valid 10-digit contact number.';
    } elseif (strtotime($appointment_date) < strtotime(date('Y-m-d'))) {
        $error = 'Appointment date cannot be in the past.';
    } else {
        $stmt = $db->query("INSERT INTO appointments (user_id, name, address, appointment_date, contact_number, reason) VALUES (?, ?, ?, ?, ?, ?)");
        if ($db->execute($stmt, [$_SESSION['user_id'], $name, $address, $appointment_date, $contact_number, $reason])) {
            $success = 'Appointment booked successfully! You will be contacted shortly.';
        } else {
            $error = 'Failed to book appointment. Please try again.';
        }
    }
}

$stmt = $db->query("SELECT * FROM appointments WHERE user_id = ? ORDER BY appointment_date DESC");
$appointments = $db->fetchAll($stmt, [$_SESSION['user_id']]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments - AGRIVISION</title>
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
                <a href="krishi-mandi.php"><i class="fas fa-store"></i> <?php echo t('krishi_mandi'); ?></a>
                <a href="my-greenhouse.php"><i class="fas fa-warehouse"></i> <?php echo t('my_greenhouse'); ?></a>
                <a href="ai-support.php"><i class="fas fa-robot"></i> <?php echo t('ai_support'); ?></a>
                <a href="appointments.php" class="active"><i class="fas fa-calendar"></i> <?php echo t('appointments'); ?></a>
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
                        <small><?php echo t('appointments'); ?></small>
                    </div>
                </div>
                <button class="theme-toggle" title="Toggle Theme">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
            
            <div class="grid-2">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-calendar-plus"></i> <?php echo t('book_appointment'); ?></h3>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="name"><?php echo t('name'); ?> *</label>
                            <input type="text" id="name" name="name" required 
                                   placeholder="Enter your full name"
                                   value="<?php echo htmlspecialchars($_POST['name'] ?? $user['name']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="address"><?php echo t('address'); ?></label>
                            <textarea id="address" name="address" rows="3" 
                                      placeholder="Enter your address"><?php echo htmlspecialchars($_POST['address'] ?? $user['address'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="appointment_date"><?php echo t('appointment_date'); ?> *</label>
                            <input type="date" id="appointment_date" name="appointment_date" required 
                                   min="<?php echo date('Y-m-d'); ?>"
                                   value="<?php echo htmlspecialchars($_POST['appointment_date'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="contact_number"><?php echo t('contact_number'); ?> *</label>
                            <input type="tel" id="contact_number" name="contact_number" required 
                                   placeholder="Enter 10-digit contact number"
                                   pattern="[0-9]{10}" maxlength="10"
                                   value="<?php echo htmlspecialchars($_POST['contact_number'] ?? $user['mobile'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="reason"><?php echo t('reason'); ?></label>
                            <textarea id="reason" name="reason" rows="3" 
                                      placeholder="Describe why you need an appointment (e.g., crop disease consultation, soil testing, field visit)"><?php echo htmlspecialchars($_POST['reason'] ?? ''); ?></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-block"><?php echo t('book_appointment'); ?></button>
                    </form>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-list"></i> <?php echo t('appointment_history'); ?></h3>
                    </div>
                    
                    <?php if (empty($appointments)): ?>
                        <p style="text-align: center; color: var(--text-light); padding: 30px;">
                            <i class="fas fa-calendar-times fa-2x" style="display: block; margin-bottom: 15px;"></i>
                            No appointments booked yet.
                        </p>
                    <?php else: ?>
                        <div style="max-height: 500px; overflow-y: auto;">
                            <?php foreach ($appointments as $apt): ?>
                                <div style="padding: 15px; border: 1px solid #ddd; border-radius: var(--radius); margin-bottom: 15px;">
                                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                                        <h4 style="margin: 0; color: var(--primary-color);">
                                            <?php echo htmlspecialchars($apt['name']); ?>
                                        </h4>
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
                                            <?php echo t($apt['status']); ?>
                                        </span>
                                    </div>
                                    
                                    <div style="font-size: 0.9rem; color: var(--text-light);">
                                        <p style="margin: 5px 0;">
                                            <i class="fas fa-calendar"></i> 
                                            <strong>Date:</strong> <?php echo date('M d, Y', strtotime($apt['appointment_date'])); ?>
                                        </p>
                                        <p style="margin: 5px 0;">
                                            <i class="fas fa-phone"></i> 
                                            <strong>Contact:</strong> <?php echo htmlspecialchars($apt['contact_number']); ?>
                                        </p>
                                        <?php if ($apt['address']): ?>
                                            <p style="margin: 5px 0;">
                                                <i class="fas fa-map-marker-alt"></i> 
                                                <strong>Address:</strong> <?php echo htmlspecialchars(substr($apt['address'], 0, 50)); ?>...
                                            </p>
                                        <?php endif; ?>
                                        <?php if ($apt['reason']): ?>
                                            <p style="margin: 5px 0;">
                                                <i class="fas fa-comment"></i> 
                                                <strong>Reason:</strong> <?php echo htmlspecialchars(substr($apt['reason'], 0, 80)); ?>...
                                            </p>
                                        <?php endif; ?>
                                        <p style="margin: 5px 0;">
                                            <i class="fas fa-clock"></i> 
                                            <strong>Booked:</strong> <?php echo date('M d, Y H:i', strtotime($apt['created_at'])); ?>
                                        </p>
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

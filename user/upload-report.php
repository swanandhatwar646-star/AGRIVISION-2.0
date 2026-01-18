<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$db = new Database();

$user_id = $_SESSION['user_id'];

$stmt = $db->query("SELECT * FROM users WHERE id = ?");
$user = $db->fetch($stmt, [$user_id]);

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $field_id = isset($_POST['field_id']) ? intval($_POST['field_id']) : null;
    $report_type = isset($_POST['report_type']) ? trim($_POST['report_type']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    
    if (empty($report_type)) {
        $allowed_types = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $file_type = $_FILES['report_file']['type'];
        $file_size = $_FILES['report_file']['size'];
        
        if (!in_array($file_type, $allowed_types)) {
            $error = t('invalid_file_type');
        } elseif ($file_size > 5242880) {
            $error = t('file_too_large');
        } else {
            $upload_dir = '../uploads/reports/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['report_file']['name'], PATHINFO_EXTENSION);
            $file_name = 'report_' . $user_id . '_' . time() . '.' . $file_extension;
            $file_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['report_file']['tmp_name'], $file_path)) {
                $stmt = $db->query("INSERT INTO farm_reports (user_id, field_id, report_type, file_path, description) VALUES (?, ?, ?, ?, ?)");
                $result = $db->execute($stmt, [$user_id, $field_id, $report_type, $file_name, $description]);
                
                if ($result) {
                    $success = t('upload_success');
                } else {
                    $error = t('error_occurred');
                }
            } else {
                $error = t('upload_failed');
            }
        }
    } else {
        $error = t('please_select_file');
    }
}

$stmt = $db->query("SELECT * FROM fields WHERE user_id = ?");
$fields = $db->fetchAll($stmt, [$user_id]);

$stmt = $db->query("SELECT fr.*, f.name as field_name FROM farm_reports fr LEFT JOIN fields f ON fr.field_id = f.id WHERE fr.user_id = ? ORDER BY fr.upload_date DESC");
$reports = $db->fetchAll($stmt, [$user_id]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('upload_report'); ?> - AGRIVISION</title>
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
                <a href="appointments.php"><i class="fas fa-calendar"></i> <?php echo t('appointments'); ?></a>
                <a href="schemes.php"><i class="fas fa-hand-holding-usd"></i> <?php echo t('schemes'); ?></a>
                <a href="upload-report.php" class="active"><i class="fas fa-file-upload"></i> <?php echo t('upload_report'); ?></a>
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
                        <small><?php echo t('upload_report'); ?></small>
                    </div>
                </div>
                <button class="theme-toggle" title="Toggle Theme">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-cloud-upload-alt"></i> <?php echo t('upload_new_report'); ?></h3>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" class="upload-form">
                        <div class="form-group">
                            <label><?php echo t('report_type'); ?></label>
                            <select name="report_type" class="form-control" required>
                                <option value=""><?php echo t('select_report_type'); ?></option>
                                <option value="soil_test"><?php echo t('soil_test_report'); ?></option>
                                <option value="crop_health"><?php echo t('crop_health_report'); ?></option>
                                <option value="weather"><?php echo t('weather_report'); ?></option>
                                <option value="pest_analysis"><?php echo t('pest_analysis_report'); ?></option>
                                <option value="other"><?php echo t('other'); ?></option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label><?php echo t('field'); ?></label>
                            <select name="field_id" class="form-control">
                                <option value=""><?php echo t('select_field'); ?></option>
                                <?php foreach ($fields as $field): ?>
                                    <option value="<?php echo $field['id']; ?>">
                                        <?php echo htmlspecialchars($field['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label><?php echo t('upload_file'); ?></label>
                            <input type="file" name="report_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
                            <small><?php echo t('file_types_allowed'); ?>: PDF, JPG, PNG, DOC, DOCX (Max 5MB)</small>
                        </div>
                        
                        <div class="form-group">
                            <label><?php echo t('description'); ?></label>
                            <textarea name="description" class="form-control" rows="4" placeholder="<?php echo t('enter_description'); ?>"></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload"></i> <?php echo t('upload_report'); ?>
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="card" style="margin-top: 20px;">
                <div class="card-header">
                    <h3><i class="fas fa-file-alt"></i> <?php echo t('uploaded_reports'); ?></h3>
                </div>
                <div class="card-body">
                    <?php if (empty($reports)): ?>
                        <div style="text-align: center; padding: 40px 20px;">
                            <i class="fas fa-folder-open fa-4x" style="color: #ccc; margin-bottom: 20px;"></i>
                            <p><?php echo t('no_reports_uploaded'); ?></p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th><?php echo t('report_type'); ?></th>
                                        <th><?php echo t('field'); ?></th>
                                        <th><?php echo t('description'); ?></th>
                                        <th><?php echo t('upload_date'); ?></th>
                                        <th><?php echo t('actions'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reports as $report): ?>
                                        <tr>
                                            <td>
                                                <i class="fas fa-file"></i> 
                                                <?php 
                                                $report_types = [
                                                    'soil_test' => t('soil_test_report'),
                                                    'crop_health' => t('crop_health_report'),
                                                    'weather' => t('weather_report'),
                                                    'pest_analysis' => t('pest_analysis_report'),
                                                    'other' => t('other')
                                                ];
                                                echo isset($report_types[$report['report_type']]) ? $report_types[$report['report_type']] : $report['report_type'];
                                                ?>
                                            </td>
                                            <td><?php echo $report['field_name'] ? htmlspecialchars($report['field_name']) : '-'; ?></td>
                                            <td><?php echo $report['description'] ? htmlspecialchars($report['description']) : '-'; ?></td>
                                            <td><?php echo date('M d, Y', strtotime($report['upload_date'])); ?></td>
                                            <td>
                                                <a href="../uploads/reports/<?php echo $report['file_path']; ?>" target="_blank" class="btn btn-sm btn-info" title="<?php echo t('view'); ?>">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="delete-report.php?id=<?php echo $report['id']; ?>" class="btn btn-sm btn-danger" title="<?php echo t('delete'); ?>" onclick="return confirm('<?php echo t('confirm_delete'); ?>');">
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
            </div>
        </main>
    </div>
    
    <script src="../assets/js/theme.js"></script>
</body>
</html>

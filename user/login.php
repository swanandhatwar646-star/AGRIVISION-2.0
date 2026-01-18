<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $mobile = trim($_POST['mobile'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($mobile) || empty($password)) {
        $error = t('fill_required_fields');
    } else {
        $db = new Database();
        
        $stmt = $db->query("SELECT * FROM users WHERE mobile = ? AND role = 'farmer'");
        $user = $db->fetch($stmt, [$mobile]);
        
        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] == 'active') {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_mobile'] = $user['mobile'];
                $_SESSION['user_language'] = $user['language'];
                
                if (empty($user['language'])) {
                    header('Location: language.php');
                } else {
                    header('Location: dashboard.php');
                }
                exit();
            } else {
                $error = 'Your account is ' . $user['status'] . '. Please contact support.';
            }
        } else {
            $error = t('invalid_credentials');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - AGRIVISION</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="text-center mb-4">
                <i class="fas fa-leaf fa-3x" style="color: var(--primary-color);"></i>
                <h2 class="mt-3">AGRIVISION</h2>
                <p><?php echo t('welcome'); ?>!</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="mobile"><?php echo t('mobile_number'); ?></label>
                    <input type="tel" id="mobile" name="mobile" required 
                           placeholder="Enter your mobile number"
                           pattern="[0-9]{10}" maxlength="10"
                           value="<?php echo htmlspecialchars($_POST['mobile'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="password"><?php echo t('password'); ?></label>
                    <input type="password" id="password" name="password" required 
                           placeholder="Enter your password">
                </div>
                
                <button type="submit" class="btn btn-primary btn-block"><?php echo t('login'); ?></button>
            </form>
            
            <div class="auth-footer">
                <p><?php echo t('dont_have_account'); ?> <a href="signup.php"><?php echo t('signup'); ?></a></p>
                <p><a href="../index.php"><?php echo t('home'); ?></a></p>
            </div>
        </div>
    </div>
</body>
</html>

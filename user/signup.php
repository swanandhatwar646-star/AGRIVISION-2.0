<?php
require_once '../config/config.php';
require_once '../config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($name) || empty($mobile) || empty($password)) {
        $error = t('fill_required_fields');
    } elseif (strlen($mobile) != 10 || !is_numeric($mobile)) {
        $error = 'Please enter a valid 10-digit mobile number.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        $db = new Database();
        
        $stmt = $db->query("SELECT id FROM users WHERE mobile = ?");
        $result = $db->fetch($stmt, [$mobile]);
        
        if ($result) {
            $error = 'Mobile number already registered.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $db->query("INSERT INTO users (name, mobile, password) VALUES (?, ?, ?)");
            if ($db->execute($stmt, [$name, $mobile, $hashed_password])) {
                $user_id = $db->lastInsertId();
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_mobile'] = $mobile;
                
                header('Location: language.php');
                exit();
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - AGRIVISION</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="text-center mb-4">
                <i class="fas fa-leaf fa-3x" style="color: var(--primary-color);"></i>
                <h2 class="mt-3">AGRIVISION</h2>
                <p><?php echo t('create_account'); ?></p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="name"><?php echo t('name'); ?></label>
                    <input type="text" id="name" name="name" required 
                           value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                           placeholder="Enter your full name">
                </div>
                
                <div class="form-group">
                    <label for="mobile"><?php echo t('mobile_number'); ?></label>
                    <input type="tel" id="mobile" name="mobile" required 
                           value="<?php echo htmlspecialchars($_POST['mobile'] ?? ''); ?>"
                           placeholder="Enter 10-digit mobile number"
                           pattern="[0-9]{10}" maxlength="10">
                </div>
                
                <div class="form-group">
                    <label for="password"><?php echo t('password'); ?></label>
                    <input type="password" id="password" name="password" required 
                           placeholder="Create a password (min 6 characters)"
                           minlength="6">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required 
                           placeholder="Confirm your password"
                           minlength="6">
                </div>
                
                <button type="submit" class="btn btn-primary btn-block"><?php echo t('signup'); ?></button>
            </form>
            
            <div class="auth-footer">
                <p><?php echo t('already_have_account'); ?> <a href="login.php"><?php echo t('login'); ?></a></p>
                <p><a href="../index.php"><?php echo t('home'); ?></a></p>
            </div>
        </div>
    </div>
    
    <script>
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirm = this.value;
            
            if (password !== confirm) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>

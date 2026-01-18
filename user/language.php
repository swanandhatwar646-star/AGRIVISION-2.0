<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$db = new Database();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $language = $_POST['language'] ?? 'en';
    
    $stmt = $db->query("UPDATE users SET language = ? WHERE id = ?");
    if ($db->execute($stmt, [$language, $_SESSION['user_id']])) {
        $_SESSION['user_language'] = $language;
        header('Location: dashboard.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Language - AGRIVISION</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="text-center mb-4">
                <i class="fas fa-globe fa-3x" style="color: var(--primary-color);"></i>
                <h2 class="mt-3"><?php echo t('select_language'); ?></h2>
                <p><?php echo t('choose_language'); ?></p>
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="language"><?php echo t('language'); ?></label>
                    <select id="language" name="language" required onchange="this.form.submit()">
                        <option value="">-- Select Language --</option>
                        <option value="en">English</option>
                        <option value="hi">हिंदी (Hindi)</option>
                        <option value="ta">தமிழ் (Tamil)</option>
                        <option value="te">తెలుగు (Telugu)</option>
                        <option value="kn">ಕನ್ನಡ (Kannada)</option>
                        <option value="mr">मराठी (Marathi)</option>
                        <option value="bn">বাংলা (Bengali)</option>
                        <option value="gu">ગુજરાતી (Gujarati)</option>
                        <option value="pa">ਪੰਜਾਬੀ (Punjabi)</option>
                        <option value="ml">മലയാളം (Malayalam)</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block"><?php echo t('continue'); ?></button>
            </form>
        </div>
    </div>
</body>
</html>

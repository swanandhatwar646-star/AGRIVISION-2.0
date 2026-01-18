<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: upload-report.php');
    exit();
}

$report_id = intval($_GET['id']);

$db = new Database();

$stmt = $db->query("SELECT * FROM farm_reports WHERE id = ? AND user_id = ?");
$report = $db->fetch($stmt, [$report_id, $user_id]);

if (!$report) {
    $_SESSION['error'] = t('error_occurred');
    header('Location: upload-report.php');
    exit();
}

$file_path = '../uploads/reports/' . $report['file_path'];

if (file_exists($file_path)) {
    unlink($file_path);
}

$stmt = $db->query("DELETE FROM farm_reports WHERE id = ? AND user_id = ?");
$result = $db->execute($stmt, [$report_id, $user_id]);

if ($result) {
    $_SESSION['success'] = t('delete_success');
} else {
    $_SESSION['error'] = t('error_occurred');
}

header('Location: upload-report.php');
exit();
?>

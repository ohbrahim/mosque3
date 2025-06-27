<?php
// إعدادات بسيطة وموثوقة
session_start();

// إعدادات قاعدة البيانات
define('DB_HOST', 'localhost');
define('DB_NAME', 'mosque_management');
define('DB_USER', 'root');
define('DB_PASS', '');

// إعدادات الموقع
define('SITE_URL', 'http://localhost/mosque2/');
define('UPLOAD_PATH', 'uploads/');

// اتصال قاعدة البيانات
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("خطأ في الاتصال: " . $e->getMessage());
}

// دوال أساسية
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../login.php');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        die('<div style="padding:20px;text-align:center;">
                <h3>ليس لديك صلاحية للوصول لهذه الصفحة</h3>
                <a href="index.php">العودة</a>
             </div>');
    }
}

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function formatArabicDate($date) {
    return date('Y-m-d H:i', strtotime($date));
}

function truncateText($text, $length = 100) {
    return mb_strlen($text) > $length ? mb_substr($text, 0, $length) . '...' : $text;
}
?>

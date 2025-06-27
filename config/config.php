<?php
/**
 * ملف التكوين الرئيسي الموحد والمحسن
 * تم دمج جميع ملفات التكوين في ملف واحد
 */

// بدء الجلسة
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// إعدادات التوقيت والترميز
date_default_timezone_set('Asia/Riyadh');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
header('Content-Type: text/html; charset=UTF-8');

// إعدادات الأمان للرؤوس
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// إعدادات قاعدة البيانات
if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'mosque_management');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_CHARSET', 'utf8mb4');
}

// إعدادات الموقع
if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'مسجد النور');
    define('SITE_URL', 'http://localhost/mosque2');
    define('ADMIN_URL', SITE_URL . '/admin/');
    define('UPLOAD_PATH', __DIR__ . '/../uploads/');
    define('ADMIN_EMAIL', 'admin@mosque.com');
    define('LANG', 'ar');
}

// إعدادات الأمان
if (!defined('SITE_KEY')) {
    define('SITE_KEY', 'mosque_secret_key_2024_change_this');
    define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
    define('SESSION_TIMEOUT', 3600); // ساعة واحدة
}

// إعدادات البريد الإلكتروني
if (!defined('SMTP_HOST')) {
    define('SMTP_HOST', 'smtp.gmail.com');
    define('SMTP_PORT', 587);
    define('SMTP_USERNAME', 'your_email@gmail.com');
    define('SMTP_PASSWORD', 'your_password');
}

// إعدادات التخزين المؤقت
if (!defined('CACHE_ENABLED')) {
    define('CACHE_ENABLED', true);
    define('CACHE_DURATION', 3600); // ساعة واحدة
    define('CACHE_PATH', __DIR__ . '/../cache/');
}

// تضمين الملفات الأساسية
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../includes/functions/functions.php';
require_once __DIR__ . '/../includes/auth/auth.php';
require_once __DIR__ . '/../includes/cache_system.php';
require_once __DIR__ . '/../includes/error_handler.php';
require_once __DIR__ . '/../includes/backup_system.php';

// إنشاء اتصال قاعدة البيانات المحسن
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    // إنشاء كائن قاعدة البيانات القديم للتوافق
    $db = new Database();
} catch (PDOException $e) {
    error_log('خطأ في الاتصال بقاعدة البيانات: ' . $e->getMessage());
    die('خطأ في الاتصال بقاعدة البيانات');
}

// الدوال الأساسية المحسنة
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && (
        $_SESSION['role'] === 'admin' || 
        $_SESSION['role'] === 'super_admin'
    );
}

function isModerator() {
    return isLoggedIn() && (
        $_SESSION['role'] === 'moderator' ||
        $_SESSION['role'] === 'admin' ||
        $_SESSION['role'] === 'super_admin'
    );
}

// دالة تنظيف البيانات المحسنة
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// دالة التحقق من صحة البريد الإلكتروني
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// دالة تشفير كلمات المرور
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// دالة التحقق من كلمات المرور
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// دالة إنشاء رمز CSRF
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// دالة التحقق من رمز CSRF
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// دالة التحقق من انتهاء الجلسة
function checkSessionTimeout() {
    if (isset($_SESSION['last_activity']) && 
        (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        return false;
    }
    $_SESSION['last_activity'] = time();
    return true;
}

// دالة تسجيل الأخطاء
function logError($message, $file = '', $line = '') {
    $logMessage = date('Y-m-d H:i:s') . " - Error: $message";
    if ($file) $logMessage .= " in $file";
    if ($line) $logMessage .= " on line $line";
    error_log($logMessage . PHP_EOL, 3, __DIR__ . '/../logs/error.log');
}

// إنشاء مجلدات مطلوبة إذا لم تكن موجودة
$requiredDirs = [
    __DIR__ . '/../uploads/',
    __DIR__ . '/../cache/',
    __DIR__ . '/../logs/'
];

foreach ($requiredDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// التحقق من انتهاء الجلسة
if (isLoggedIn()) {
    checkSessionTimeout();
}

?>
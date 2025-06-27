<?php
/**
 * دوال مساعدة عامة للموقع
 */

/**
 * التحقق من صحة البريد الإلكتروني
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * التحقق من قوة كلمة المرور
 */
function validatePassword($password) {
    // كلمة المرور يجب أن تكون 8 أحرف على الأقل
    if (strlen($password) < 8) {
        return false;
    }
    
    // يجب أن تحتوي على حرف كبير وصغير ورقم
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', $password)) {
        return false;
    }
    
    return true;
}

/**
 * إنشاء رمز عشوائي
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * تشفير كلمة المرور
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * التحقق من كلمة المرور
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * إعادة توجيه الصفحة
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * عرض رسالة خطأ
 */
function showError($message) {
    return '<div class="alert alert-danger">' . htmlspecialchars($message) . '</div>';
}

/**
 * عرض رسالة نجاح
 */
function showSuccess($message) {
    return '<div class="alert alert-success">' . htmlspecialchars($message) . '</div>';
}

/**
 * التحقق من تسجيل الدخول
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * التحقق من صلاحية المستخدم
 */
function hasPermission($role) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $userRole = $_SESSION['role'] ?? '';
    
    // المدير له جميع الصلاحيات
    if ($userRole === 'admin') {
        return true;
    }
    
    // التحقق من الصلاحية المطلوبة
    return $userRole === $role;
}

/**
 * الحصول على عنوان IP الحالي
 */
function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

/**
 * تسجيل نشاط المستخدم
 */
function logActivity($userId, $action, $description = '') {
    global $db;
    
    try {
        $db->insert('activity_log', [
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'ip_address' => getUserIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        // تسجيل الخطأ في ملف log
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

/**
 * تنسيق التاريخ بالعربية
 */
function formatArabicDate($date) {
    $months = [
        1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
        5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
        9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر'
    ];
    
    $timestamp = strtotime($date);
    $day = date('d', $timestamp);
    $month = $months[(int)date('m', $timestamp)];
    $year = date('Y', $timestamp);
    $time = date('H:i', $timestamp);
    
    return "$day $month $year - $time";
}

/**
 * اقتطاع النص
 */
function truncateText($text, $length = 100) {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    return substr($text, 0, $length) . '...';
}

/**
 * التحقق من البيئة المحلية
 */
function isLocalEnvironment() {
    $localHosts = ['localhost', '127.0.0.1', '::1'];
    $currentHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    return in_array($currentHost, $localHosts) || 
           strpos($currentHost, '.local') !== false ||
           strpos($currentHost, 'xampp') !== false ||
           strpos($currentHost, 'wamp') !== false;
}

/**
 * إنشاء CSRF Token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * التحقق من CSRF Token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * تنظيف HTML
 */
function cleanHTML($html) {
    // قائمة العناصر المسموحة
    $allowedTags = '<p><br><strong><em><u><h1><h2><h3><h4><h5><h6><ul><ol><li><a><img>';
    return strip_tags($html, $allowedTags);
}

/**
 * تحويل الروابط إلى HTML
 */
function makeClickableLinks($text) {
    return preg_replace(
        '/(https?:\/\/[^\s]+)/',
        '<a href="$1" target="_blank" rel="noopener">$1</a>',
        $text
    );
}
?>

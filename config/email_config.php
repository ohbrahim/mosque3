<?php
/**
 * إعدادات البريد الإلكتروني
 */

// إعدادات SMTP
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('SMTP_FROM_EMAIL', 'your-email@gmail.com');
define('SMTP_FROM_NAME', 'موقع المسجد');

// دالة لإرسال البريد الإلكتروني مع معالجة الأخطاء
function sendEmailSafely($to, $subject, $message, $fromEmail = null) {
    // التحقق من البيئة المحلية
    if (isLocalEnvironment()) {
        // في بيئة التطوير، نسجل الرسالة في ملف log بدلاً من إرسالها
        $logMessage = date('Y-m-d H:i:s') . " - Email to: $to, Subject: $subject\n";
        file_put_contents('logs/email_log.txt', $logMessage, FILE_APPEND | LOCK_EX);
        return true;
    }
    
    // في بيئة الإنتاج، نحاول إرسال البريد
    $headers = 'From: ' . ($fromEmail ?: SMTP_FROM_EMAIL) . "\r\n" .
               'Reply-To: ' . ($fromEmail ?: SMTP_FROM_EMAIL) . "\r\n" .
               'X-Mailer: PHP/' . phpversion() . "\r\n" .
               'Content-Type: text/plain; charset=UTF-8';
    
    return @mail($to, $subject, $message, $headers);
}

// دالة للتحقق من البيئة المحلية
function isLocalEnvironment() {
    $localHosts = ['localhost', '127.0.0.1', '::1'];
    $currentHost = $_SERVER['HTTP_HOST'] ?? '';
    
    return in_array($currentHost, $localHosts) || 
           strpos($currentHost, '.local') !== false ||
           strpos($currentHost, 'xampp') !== false ||
           strpos($currentHost, 'wamp') !== false;
}

// إنشاء مجلد logs إذا لم يكن موجوداً
if (!file_exists('logs')) {
    mkdir('logs', 0755, true);
}
?>

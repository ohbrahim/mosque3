<?php
/**
 * إعدادات البريد الإلكتروني
 */

// تفعيل أو إلغاء تفعيل البريد الإلكتروني
define('MAIL_ENABLED', false); // غير إلى true عند إعداد البريد

// إعدادات SMTP (للاستضافة المدفوعة)
define('SMTP_HOST', 'smtp.gmail.com'); // أو smtp الخاص بالاستضافة
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('SMTP_ENCRYPTION', 'tls');

// إعدادات البريد الافتراضية
define('MAIL_FROM_EMAIL', 'noreply@yoursite.com');
define('MAIL_FROM_NAME', SITE_NAME);

/**
 * دالة إرسال البريد المحسنة
 */
function sendEmail($to, $subject, $message, $isHTML = false) {
    if (!MAIL_ENABLED) {
        return false;
    }
    
    // إعداد الهيدر
    $headers = array();
    $headers[] = 'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM_EMAIL . '>';
    $headers[] = 'Reply-To: ' . MAIL_FROM_EMAIL;
    $headers[] = 'X-Mailer: PHP/' . phpversion();
    
    if ($isHTML) {
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
    } else {
        $headers[] = 'Content-Type: text/plain; charset=UTF-8';
    }
    
    // محاولة إرسال البريد
    try {
        return mail($to, $subject, $message, implode("\r\n", $headers));
    } catch (Exception $e) {
        error_log("Mail Error: " . $e->getMessage());
        return false;
    }
}

/**
 * دالة إرسال بريد التفعيل
 */
function sendVerificationEmail($email, $fullName, $token) {
    $verificationLink = "http://" . $_SERVER['HTTP_HOST'] . "/verify_email.php?token=" . $token;
    
    $subject = "تفعيل حساب " . SITE_NAME;
    $message = "مرحباً " . $fullName . ",\n\n";
    $message .= "شكراً لتسجيلك في " . SITE_NAME . "\n";
    $message .= "يرجى النقر على الرابط التالي لتفعيل حسابك:\n\n";
    $message .= $verificationLink . "\n\n";
    $message .= "بعد التفعيل، سيتم مراجعة حسابك من قبل الإدارة قبل تفعيله نهائياً.\n\n";
    $message .= "إذا لم تقم بالتسجيل، يرجى تجاهل هذا البريد.\n\n";
    $message .= "شكراً لك\n";
    $message .= "فريق " . SITE_NAME;
    
    return sendEmail($email, $subject, $message);
}

/**
 * دالة إرسال بريد استعادة كلمة المرور
 */
function sendPasswordResetEmail($email, $fullName, $token) {
    $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/reset_password.php?token=" . $token;
    
    $subject = "إعادة تعيين كلمة المرور - " . SITE_NAME;
    $message = "مرحباً " . $fullName . ",\n\n";
    $message .= "تم طلب إعادة تعيين كلمة المرور لحسابك في " . SITE_NAME . "\n\n";
    $message .= "يرجى النقر على الرابط التالي لإعادة تعيين كلمة المرور:\n\n";
    $message .= $resetLink . "\n\n";
    $message .= "هذا الرابط صالح لمدة ساعة واحدة فقط.\n\n";
    $message .= "إذا لم تطلب إعادة تعيين كلمة المرور، يرجى تجاهل هذا البريد.\n\n";
    $message .= "شكراً لك\n";
    $message .= "فريق " . SITE_NAME;
    
    return sendEmail($email, $subject, $message);
}

/**
 * دالة إرسال إشعار للإدارة
 */
function notifyAdminNewUser($userInfo) {
    global $db;
    
    $admins = $db->fetchAll("SELECT email, full_name FROM users WHERE role = 'admin' AND status = 'active'");
    
    foreach ($admins as $admin) {
        $subject = "عضو جديد في انتظار الموافقة - " . SITE_NAME;
        $message = "مرحباً " . $admin['full_name'] . ",\n\n";
        $message .= "عضو جديد قام بالتسجيل وتفعيل بريده الإلكتروني:\n\n";
        $message .= "الاسم: " . $userInfo['full_name'] . "\n";
        $message .= "اسم المستخدم: " . $userInfo['username'] . "\n";
        $message .= "البريد الإلكتروني: " . $userInfo['email'] . "\n";
        $message .= "تاريخ التسجيل: " . $userInfo['created_at'] . "\n\n";
        $message .= "يرجى مراجعة الحساب وتفعيله من لوحة التحكم:\n";
        $message .= "http://" . $_SERVER['HTTP_HOST'] . "/admin/user_approval.php\n\n";
        $message .= "شكراً لك\n";
        $message .= "نظام " . SITE_NAME;
        
        sendEmail($admin['email'], $subject, $message);
    }
}
?>
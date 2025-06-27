<?php
/**
 * وظائف إرسال البريد الإلكتروني
 */

/**
 * إرسال بريد إلكتروني
 */
function sendEmail($to, $subject, $message, $isHtml = false) {
    $siteName = getSetting($GLOBALS['db'], 'site_name', 'مسجد النور');
    $fromEmail = getSetting($GLOBALS['db'], 'contact_email', 'noreply@mosque.com');
    
    $headers = [];
    $headers[] = 'From: ' . $siteName . ' <' . $fromEmail . '>';
    $headers[] = 'Reply-To: ' . $fromEmail;
    $headers[] = 'X-Mailer: PHP/' . phpversion();
    
    if ($isHtml) {
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html; charset=UTF-8';
    } else {
        $headers[] = 'Content-type: text/plain; charset=UTF-8';
    }
    
    return mail($to, $subject, $message, implode("\r\n", $headers));
}

/**
 * إرسال بريد ترحيب للمستخدم الجديد
 */
function sendWelcomeEmail($userEmail, $userName) {
    $siteName = getSetting($GLOBALS['db'], 'site_name', 'مسجد النور');
    
    $subject = 'مرحباً بك في ' . $siteName;
    
    $message = "السلام عليكم ورحمة الله وبركاته\n\n";
    $message .= "أهلاً وسهلاً بك {$userName} في موقع {$siteName}\n\n";
    $message .= "نحن سعداء بانضمامك إلى مجتمعنا الإسلامي.\n\n";
    $message .= "يمكنك الآن:\n";
    $message .= "- تصفح المحتوى الإسلامي\n";
    $message .= "- قراءة الكتب من المكتبة الإلكترونية\n";
    $message .= "- المشاركة في التعليقات والنقاشات\n";
    $message .= "- التبرع للمشاريع الخيرية\n\n";
    $message .= "بارك الله فيك\n";
    $message .= "إدارة " . $siteName;
    
    return sendEmail($userEmail, $subject, $message);
}

/**
 * إرسال إشعار بتعليق جديد
 */
function sendCommentNotification($pageTitle, $commenterName, $commentContent) {
    $adminEmail = getSetting($GLOBALS['db'], 'contact_email', '');
    if (!$adminEmail) return false;
    
    $siteName = getSetting($GLOBALS['db'], 'site_name', 'مسجد النور');
    
    $subject = 'تعليق جديد على ' . $pageTitle;
    
    $message = "تم إضافة تعليق جديد على موقع {$siteName}\n\n";
    $message .= "الصفحة: {$pageTitle}\n";
    $message .= "المعلق: {$commenterName}\n\n";
    $message .= "التعليق:\n{$commentContent}\n\n";
    $message .= "يمكنك مراجعة التعليق من لوحة التحكم.";
    
    return sendEmail($adminEmail, $subject, $message);
}

/**
 * إرسال إشعار برسالة جديدة
 */
function sendContactNotification($senderName, $senderEmail, $subject, $messageContent) {
    $adminEmail = getSetting($GLOBALS['db'], 'contact_email', '');
    if (!$adminEmail) return false;
    
    $siteName = getSetting($GLOBALS['db'], 'site_name', 'مسجد النور');
    
    $emailSubject = 'رسالة جديدة من موقع ' . $siteName;
    
    $message = "رسالة جديدة من موقع {$siteName}\n\n";
    $message .= "المرسل: {$senderName}\n";
    $message .= "البريد الإلكتروني: {$senderEmail}\n";
    $message .= "الموضوع: {$subject}\n\n";
    $message .= "الرسالة:\n{$messageContent}";
    
    return sendEmail($adminEmail, $emailSubject, $message);
}

/**
 * إرسال إشعار بتبرع جديد
 */
function sendDonationNotification($donorName, $amount, $project) {
    $adminEmail = getSetting($GLOBALS['db'], 'contact_email', '');
    if (!$adminEmail) return false;
    
    $siteName = getSetting($GLOBALS['db'], 'site_name', 'مسجد النور');
    
    $subject = 'تبرع جديد - ' . $siteName;
    
    $message = "تم استلام تبرع جديد على موقع {$siteName}\n\n";
    $message .= "المتبرع: {$donorName}\n";
    $message .= "المبلغ: {$amount} ريال\n";
    $message .= "المشروع: {$project}\n\n";
    $message .= "يرجى متابعة إجراءات التبرع من لوحة التحكم.";
    
    return sendEmail($adminEmail, $subject, $message);
}

/**
 * إرسال تأكيد التبرع للمتبرع
 */
function sendDonationConfirmation($donorEmail, $donorName, $amount, $project) {
    $siteName = getSetting($GLOBALS['db'], 'site_name', 'مسجد النور');
    
    $subject = 'تأكيد التبرع - ' . $siteName;
    
    $message = "السلام عليكم ورحمة الله وبركاته\n\n";
    $message .= "الأخ الكريم {$donorName}\n\n";
    $message .= "تم استلام تبرعكم بمبلغ {$amount} ريال لمشروع {$project}\n\n";
    $message .= "جزاكم الله خيراً على هذا العمل الخيري\n";
    $message .= "نسأل الله أن يتقبل منكم ويبارك في أموالكم\n\n";
    $message .= "سنتواصل معكم قريباً لإتمام إجراءات التبرع\n\n";
    $message .= "بارك الله فيكم\n";
    $message .= "إدارة " . $siteName;
    
    return sendEmail($donorEmail, $subject, $message);
}
?>

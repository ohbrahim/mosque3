<?php
/**
 * إرسال بريد تفعيل الحساب
 */
function sendVerificationEmail($email, $fullName, $token) {
    $subject = 'تفعيل حساب ' . SITE_NAME;
    $verificationLink = SITE_URL . '/auth/login.php?action=verify&token=' . $token;
    
    $message = "
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: 'Arial', sans-serif; direction: rtl; text-align: right; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .button { display: inline-block; background: #667eea; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .footer { text-align: center; margin-top: 30px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>مرحباً بك في " . SITE_NAME . "</h2>
            </div>
            <div class='content'>
                <h3>أهلاً وسهلاً {$fullName}</h3>
                <p>شكراً لك على التسجيل في موقعنا. لإكمال عملية التسجيل، يرجى تفعيل حسابك بالنقر على الرابط أدناه:</p>
                
                <div style='text-align: center;'>
                    <a href='{$verificationLink}' class='button'>تفعيل الحساب</a>
                </div>
                
                <p>أو يمكنك نسخ الرابط التالي ولصقه في المتصفح:</p>
                <p style='background: #eee; padding: 10px; border-radius: 5px; word-break: break-all;'>{$verificationLink}</p>
                
                <p><strong>ملاحظة:</strong> بعد تفعيل البريد الإلكتروني، سيتم مراجعة طلبك من قبل إدارة المسجد وستصلك رسالة تأكيد عند الموافقة على طلبك.</p>
                
                <p>إذا لم تقم بإنشاء هذا الحساب، يرجى تجاهل هذه الرسالة.</p>
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " " . SITE_NAME . " - جميع الحقوق محفوظة</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($email, $subject, $message);
}

/**
 * إرسال بريد استعادة كلمة المرور
 */
function sendPasswordResetEmail($email, $fullName, $token) {
    $subject = 'استعادة كلمة المرور - ' . SITE_NAME;
    $resetLink = SITE_URL . '/auth/login.php?action=reset&token=' . $token;
    
    $message = "
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: 'Arial', sans-serif; direction: rtl; text-align: right; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .button { display: inline-block; background: #667eea; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .footer { text-align: center; margin-top: 30px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>استعادة كلمة المرور</h2>
            </div>
            <div class='content'>
                <h3>مرحباً {$fullName}</h3>
                <p>تلقينا طلباً لإعادة تعيين كلمة المرور الخاصة بحسابك في " . SITE_NAME . ".</p>
                
                <div style='text-align: center;'>
                    <a href='{$resetLink}' class='button'>إعادة تعيين كلمة المرور</a>
                </div>
                
                <p>أو يمكنك نسخ الرابط التالي ولصقه في المتصفح:</p>
                <p style='background: #eee; padding: 10px; border-radius: 5px; word-break: break-all;'>{$resetLink}</p>
                
                <div class='warning'>
                    <strong>تنبيه أمني:</strong>
                    <ul>
                        <li>هذا الرابط صالح لمدة ساعة واحدة فقط</li>
                        <li>إذا لم تطلب إعادة تعيين كلمة المرور، يرجى تجاهل هذه الرسالة</li>
                        <li>لا تشارك هذا الرابط مع أي شخص آخر</li>
                    </ul>
                </div>
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " " . SITE_NAME . " - جميع الحقوق محفوظة</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($email, $subject, $message);
}

/**
 * إشعار الإدارة بطلب تسجيل جديد
 */
function sendNewRegistrationNotification($adminEmail, $adminName, $requestId, $userData) {
    $subject = 'طلب تسجيل جديد - ' . SITE_NAME;
    $reviewLink = SITE_URL . '/admin/registration_requests.php?id=' . $requestId;
    
    $message = "
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: 'Arial', sans-serif; direction: rtl; text-align: right; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .button { display: inline-block; background: #667eea; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .info-box { background: white; border: 1px solid #ddd; padding: 20px; border-radius: 5px; margin: 20px 0; }
            .footer { text-align: center; margin-top: 30px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>طلب تسجيل جديد</h2>
            </div>
            <div class='content'>
                <h3>مرحباً {$adminName}</h3>
                <p>تم استلام طلب تسجيل جديد في " . SITE_NAME . " ويحتاج إلى مراجعتك.</p>
                
                <div class='info-box'>
                    <h4>بيانات المتقدم:</h4>
                    <p><strong>الاسم:</strong> {$userData['full_name']}</p>
                    <p><strong>اسم المستخدم:</strong> {$userData['username']}</p>
                    <p><strong>البريد الإلكتروني:</strong> {$userData['email']}</p>
                    <p><strong>رقم الهاتف:</strong> " . ($userData['phone'] ?? 'غير محدد') . "</p>
                    <p><strong>تاريخ الطلب:</strong> " . date('Y-m-d H:i:s') . "</p>
                </div>
                
                <div style='text-align: center;'>
                    <a href='{$reviewLink}' class='button'>مراجعة الطلب</a>
                </div>
                
                <p>يرجى مراجعة الطلب واتخاذ الإجراء المناسب (موافقة أو رفض).</p>
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " " . SITE_NAME . " - نظام إدارة المسجد</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($adminEmail, $subject, $message);
}

/**
 * دالة إرسال البريد الإلكتروني الأساسية مع دعم البيئة المحلية
 */
function sendEmail($to, $subject, $message) {
    // التحقق من البيئة المحلية
    if (isLocalEnvironment()) {
        return saveEmailLocally($to, $subject, $message);
    }
    
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . SITE_NAME . ' <noreply@' . parse_url(SITE_URL, PHP_URL_HOST) . '>',
        'Reply-To: noreply@' . parse_url(SITE_URL, PHP_URL_HOST),
        'X-Mailer: PHP/' . phpversion()
    ];
    
    return mail($to, $subject, $message, implode("\r\n", $headers));
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
           strpos($currentHost, 'wamp') !== false ||
           strpos($_SERVER['SERVER_NAME'] ?? '', 'localhost') !== false;
}

/**
 * حفظ البريد محلياً في بيئة التطوير
 */
function saveEmailLocally($to, $subject, $message) {
    // إنشاء مجلد emails إذا لم يكن موجوداً
    $emailDir = __DIR__ . '/../emails';
    if (!file_exists($emailDir)) {
        mkdir($emailDir, 0755, true);
    }
    
    // إنشاء ملف للبريد
    $emailId = uniqid('email_');
    $emailFile = $emailDir . '/' . $emailId . '.html';
    
    // محتوى البريد مع معلومات إضافية
    $emailContent = "
    <!DOCTYPE html>
    <html lang='ar' dir='rtl'>
    <head>
        <meta charset='UTF-8'>
        <title>{$subject}</title>
        <style>
            .email-info { background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #dee2e6; }
            .email-info h3 { color: #495057; margin-top: 0; }
            .email-content { border: 1px solid #dee2e6; border-radius: 5px; }
        </style>
    </head>
    <body>
        <div class='email-info'>
            <h3>معلومات البريد الإلكتروني</h3>
            <p><strong>إلى:</strong> {$to}</p>
            <p><strong>الموضوع:</strong> {$subject}</p>
            <p><strong>التاريخ:</strong> " . date('Y-m-d H:i:s') . "</p>
            <p><strong>ملاحظة:</strong> هذا البريد محفوظ محلياً في بيئة التطوير</p>
        </div>
        <div class='email-content'>
            {$message}
        </div>
    </body>
    </html>
    ";
    
    // حفظ البريد
    file_put_contents($emailFile, $emailContent);
    
    // حفظ فهرس البريد
    $emailIndex = $emailDir . '/index.json';
    $emails = [];
    if (file_exists($emailIndex)) {
        $emails = json_decode(file_get_contents($emailIndex), true) ?: [];
    }
    
    $emails[] = [
        'id' => $emailId,
        'to' => $to,
        'subject' => $subject,
        'date' => date('Y-m-d H:i:s'),
        'file' => $emailId . '.html'
    ];
    
    file_put_contents($emailIndex, json_encode($emails, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    return true;
}

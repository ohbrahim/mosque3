<?php
require_once 'config/config.php';

$message = '';
$error = '';

if (isset($_GET['token'])) {
    $token = sanitize($_GET['token']);
    
    $user = $db->fetchOne("SELECT * FROM users WHERE verification_token = ?", [$token]);
    
    if ($user) {
        if ($user['email_verified'] == 1) {
            $message = 'تم تفعيل بريدك الإلكتروني مسبقاً. حسابك في انتظار موافقة الإدارة.';
        } else {
            // تفعيل البريد الإلكتروني
            $db->update('users', [
                'email_verified' => 1,
                'verification_token' => null
            ], 'id = ?', [$user['id']]);
            
            $message = 'تم تفعيل بريدك الإلكتروني بنجاح! حسابك الآن في انتظار موافقة الإدارة.';
            
            // إشعار الإدارة بالعضو الجديد
            $admins = $db->fetchAll("SELECT email FROM users WHERE role = 'admin' AND status = 'active'");
            foreach ($admins as $admin) {
                $subject = "عضو جديد في انتظار الموافقة - " . SITE_NAME;
                $adminMessage = "عضو جديد قام بالتسجيل وتفعيل بريده الإلكتروني:\n\n";
                $adminMessage .= "الاسم: " . $user['full_name'] . "\n";
                $adminMessage .= "اسم المستخدم: " . $user['username'] . "\n";
                $adminMessage .= "البريد الإلكتروني: " . $user['email'] . "\n\n";
                $adminMessage .= "يرجى مراجعة الحساب وتفعيله من لوحة التحكم.";
                
                // إرسال البريد للإدارة
                // mail($admin['email'], $subject, $adminMessage);
            }
        }
    } else {
        $error = 'رمز التفعيل غير صحيح أو منتهي الصلاحية.';
    }
} else {
    $error = 'رمز التفعيل مفقود.';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تفعيل البريد الإلكتروني - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .verify-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            padding: 40px;
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="verify-card">
        <h2 class="mb-4"><?php echo SITE_NAME; ?></h2>
        
        <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle fa-3x mb-3"></i>
                <p><?php echo $message; ?></p>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-times-circle fa-3x mb-3"></i>
                <p><?php echo $error; ?></p>
            </div>
        <?php endif; ?>
        
        <div class="mt-4">
            <a href="login.php" class="btn btn-primary">تسجيل الدخول</a>
            <a href="index.php" class="btn btn-outline-secondary">الصفحة الرئيسية</a>
        </div>
    </div>
</body>
</html>
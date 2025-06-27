<?php
require_once 'config/config.php';

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = sanitize($_POST['token']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($password) || empty($confirm_password)) {
        $error = 'يرجى إدخال كلمة المرور وتأكيدها';
    } elseif ($password !== $confirm_password) {
        $error = 'كلمة المرور وتأكيد كلمة المرور غير متطابقتين';
    } elseif (strlen($password) < 6) {
        $error = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
    } else {
        $user = $db->fetchOne("SELECT * FROM users WHERE reset_token = ? AND reset_expires > NOW()", [$token]);
        
        if ($user) {
            // تحديث كلمة المرور
            $db->update('users', [
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'reset_token' => null,
                'reset_expires' => null
            ], 'id = ?', [$user['id']]);
            
            $success = 'تم تغيير كلمة المرور بنجاح. يمكنك الآن تسجيل الدخول.';
        } else {
            $error = 'رمز إعادة التعيين غير صحيح أو منتهي الصلاحية.';
        }
    }
}

// التحقق من صحة الرمز
if (!empty($token) && empty($success)) {
    $user = $db->fetchOne("SELECT * FROM users WHERE reset_token = ? AND reset_expires > NOW()", [$token]);
    if (!$user) {
        $error = 'رمز إعادة التعيين غير صحيح أو منتهي الصلاحية.';
        $token = '';
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعادة تعيين كلمة المرور - <?php echo SITE_NAME; ?></title>
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
        .reset-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }
        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
            border: 2px solid #f0f0f0;
            margin-bottom: 20px;
        }
        .btn-reset {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            width: 100%;
            color: white;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="reset-card">
        <div class="text-center mb-4">
            <h2><?php echo SITE_NAME; ?></h2>
            <p class="text-muted">إعادة تعيين كلمة المرور</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
            <div class="text-center">
                <a href="login.php" class="btn btn-primary">تسجيل الدخول</a>
            </div>
        <?php elseif (!empty($token)): ?>
            <form method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <div class="mb-3">
                    <label class="form-label">كلمة المرور الجديدة</label>
                    <input type="password" class="form-control" name="password" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">تأكيد كلمة المرور</label>
                    <input type="password" class="form-control" name="confirm_password" required>
                </div>
                <button type="submit" class="btn btn-reset">تغيير كلمة المرور</button>
            </form>
        <?php else: ?>
            <div class="alert alert-warning">
                رابط إعادة تعيين كلمة المرور غير صحيح أو منتهي الصلاحية.
            </div>
            <div class="text-center">
                <a href="login.php?action=forgot" class="btn btn-primary">طلب رابط جديد</a>
            </div>
        <?php endif; ?>
        
        <div class="text-center mt-3">
            <a href="login.php" class="text-decoration-none">العودة لتسجيل الدخول</a>
        </div>
    </div>
</body>
</html>
<?php
/**
 * إعادة تعيين كلمة مرور المدير
 */
require_once 'config/config.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if (empty($username) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'يرجى ملء جميع الحقول';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'كلمات المرور غير متطابقة';
    } elseif (strlen($newPassword) < 6) {
        $error = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
    } else {
        try {
            // التحقق من وجود المستخدم
            $user = $db->fetchOne("SELECT id FROM users WHERE username = ?", [$username]);
            
            if ($user) {
                // تحديث كلمة المرور
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $result = $db->update('users', [
                    'password' => $hashedPassword,
                    'updated_at' => date('Y-m-d H:i:s')
                ], 'username = ?', [$username]);
                
                if ($result) {
                    $message = 'تم تحديث كلمة المرور بنجاح! يمكنك الآن تسجيل الدخول.';
                } else {
                    $error = 'فشل في تحديث كلمة المرور';
                }
            } else {
                $error = 'اسم المستخدم غير موجود';
            }
        } catch (Exception $e) {
            $error = 'خطأ في قاعدة البيانات: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعادة تعيين كلمة المرور</title>
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
            max-width: 450px;
        }
        .reset-header {
            text-align: center;
            margin-bottom: 30px;
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
        <div class="reset-header">
            <h2>إعادة تعيين كلمة المرور</h2>
            <p class="text-muted">أدخل اسم المستخدم وكلمة المرور الجديدة</p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
            <div class="text-center">
                <a href="login.php" class="btn btn-primary">تسجيل الدخول</a>
            </div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-3">
                    <label for="username" class="form-label">اسم المستخدم</label>
                    <input type="text" class="form-control" id="username" name="username" 
                           value="<?php echo htmlspecialchars($_POST['username'] ?? 'admin'); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="new_password" class="form-label">كلمة المرور الجديدة</label>
                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">تأكيد كلمة المرور</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" class="btn btn-reset">تحديث كلمة المرور</button>
            </form>
        <?php endif; ?>
        
        <div class="text-center mt-3">
            <a href="index.php" class="text-decoration-none">العودة للموقع الرئيسي</a>
        </div>
    </div>
</body>
</html>

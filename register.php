<?php
/**
 * صفحة تسجيل مستخدم جديد
 */
require_once 'config/config.php';

$message = '';
$error = '';

// التحقق من تفعيل التسجيل
$registrationEnabled = getSetting($db, 'enable_registration', '1') === '1';

if (!$registrationEnabled) {
    $error = 'التسجيل غير مفعل حالياً';
}

// معالجة نموذج التسجيل
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $registrationEnabled) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'رمز الأمان غير صحيح';
    } else {
        $data = [
            'username' => sanitize($_POST['username']),
            'email' => sanitize($_POST['email']),
            'full_name' => sanitize($_POST['full_name']),
            'password' => $_POST['password'],
            'phone' => sanitize($_POST['phone']),
            'role' => 'member',
            'status' => 'active'
        ];
        
        // التحقق من البيانات
        if (empty($data['username']) || empty($data['email']) || empty($data['full_name']) || empty($data['password'])) {
            $error = 'جميع الحقول المطلوبة يجب ملؤها';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $error = 'البريد الإلكتروني غير صحيح';
        } elseif (strlen($data['password']) < 6) {
            $error = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
        } elseif ($data['password'] !== $_POST['confirm_password']) {
            $error = 'كلمة المرور وتأكيدها غير متطابقتان';
        } else {
            $result = $auth->createUser($data);
            if ($result['success']) {
                $message = 'تم إنشاء حسابك بنجاح. يمكنك الآن تسجيل الدخول.';
            } else {
                $error = $result['message'];
            }
        }
    }
}

$siteName = getSetting($db, 'site_name', 'مسجد النور');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل حساب جديد - <?php echo htmlspecialchars($siteName); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts - Arabic -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .register-card {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 500px;
        }
        
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="register-card">
                    <div class="text-center mb-4">
                        <h2 class="mb-3">تسجيل حساب جديد</h2>
                        <p class="text-muted">انضم إلى مجتمع <?php echo htmlspecialchars($siteName); ?></p>
                    </div>
                    
                    <?php if ($message): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo $message; ?>
                        </div>
                        <div class="text-center">
                            <a href="login.php" class="btn btn-primary">تسجيل الدخول</a>
                        </div>
                    <?php elseif ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($registrationEnabled && !$message): ?>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            
                            <div class="mb-3">
                                <label for="full_name" class="form-label">الاسم الكامل *</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" 
                                       value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">اسم المستخدم *</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">البريد الإلكتروني *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="phone" class="form-label">رقم الهاتف</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">كلمة المرور *</label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       minlength="6" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">تأكيد كلمة المرور *</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                       minlength="6" required>
                            </div>
                            
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-user-plus"></i> إنشاء الحساب
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                    
                    <div class="text-center">
                        <p class="mb-2">لديك حساب بالفعل؟ <a href="login.php">تسجيل الدخول</a></p>
                        <a href="index.php" class="text-muted">العودة للرئيسية</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // التحقق من تطابق كلمة المرور
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password && confirmPassword && password !== confirmPassword) {
                this.setCustomValidity('كلمة المرور غير متطابقة');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>

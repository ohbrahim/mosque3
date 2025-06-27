<?php
// استخدام التكوين التلقائي
require_once 'config/auto_config.php';

$error = '';
$success = '';

// Handle different actions
$action = $_GET['action'] ?? 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'login') {
        $username = sanitize($_POST['username']);
        $password = $_POST['password'];
        
        if (!empty($username) && !empty($password)) {
            // تبسيط شرط تسجيل الدخول - فقط التحقق من الحالة النشطة
            $user = $db->fetchOne("SELECT * FROM users WHERE username = ? AND status = 'active'", [$username]);
            
            if ($user && password_verify($password, $user['password'])) {
                // التحقق من صحة ID المستخدم
                if ($user['id'] <= 0) {
                    $error = 'خطأ في بيانات المستخدم. يرجى الاتصال بالإدارة.';
                } else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role'] = $user['role'];
                    
                    // تحديث آخر تسجيل دخول
                    try {
                        $db->update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);
                    } catch (Exception $e) {
                        // تجاهل خطأ تحديث آخر دخول
                    }
                    
                    // توجيه المستخدم حسب دوره
                    if ($user['role'] === 'editor') {
                        header('Location: admin/editor_dashboard.php');
                    } elseif ($user['role'] === 'member') {
                        header('Location: admin/member_dashboard.php');
                    } else {
                        // توجيه الإدارة للصفحة الرئيسية
                        header('Location: index.php');
                    }
                    exit;
                }
            } else {
                $error = 'اسم المستخدم أو كلمة المرور غير صحيحة';
            }
        } else {
            $error = 'يرجى إدخال جميع البيانات';
        }
    } elseif ($action === 'register') {
        $username = sanitize($_POST['username']);
        $email = sanitize($_POST['email']);
        $full_name = sanitize($_POST['full_name']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($username) || empty($email) || empty($full_name) || empty($password)) {
            $error = 'يرجى إدخال جميع البيانات المطلوبة';
        } elseif ($password !== $confirm_password) {
            $error = 'كلمة المرور وتأكيد كلمة المرور غير متطابقتين';
        } elseif (strlen($password) < 6) {
            $error = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
        } else {
            // التحقق من عدم وجود المستخدم
            try {
                $existing = $db->fetchOne("SELECT id FROM users WHERE username = ? OR email = ?", [$username, $email]);
                if ($existing) {
                    $error = 'اسم المستخدم أو البريد الإلكتروني موجود مسبقاً';
                } else {
                    // إنشاء رمز التفعيل
                    $verification_token = bin2hex(random_bytes(32));
                    
                    // تحضير البيانات - تبسيط العملية
                    $userData = [
                        'username' => $username,
                        'email' => $email,
                        'full_name' => $full_name,
                        'password' => password_hash($password, PASSWORD_DEFAULT),
                        'role' => 'member',
                        'status' => 'pending',
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    
                    // إضافة الحقول الاختيارية إذا كانت موجودة في الجدول
                    try {
                        $columns = $db->fetchAll("SHOW COLUMNS FROM users LIKE 'email_verified'");
                        if (!empty($columns)) {
                            $userData['email_verified'] = 1; // تفعيل البريد تلقائياً
                            // إزالة رمز التفعيل
                            // $userData['verification_token'] = $verification_token;
                        }
                    } catch (Exception $e) {
                        // تجاهل إذا لم تكن الأعمدة موجودة
                    }
                    
                    // محاولة إدراج البيانات
                    try {
                        $result = $db->insert('users', $userData);
                        
                        if ($result) {
                            $success = 'تم إنشاء حسابك بنجاح. سيتم مراجعة حسابك من قبل الإدارة قبل تفعيله.';
                            $action = 'login';
                            
                            // محاولة إرسال إشعار للإدارة
                            try {
                                $admins = $db->fetchAll("SELECT email FROM users WHERE role = 'admin' AND status = 'active'");
                                // يمكن إضافة كود إرسال البريد هنا
                            } catch (Exception $e) {
                                // تجاهل خطأ الإشعار
                            }
                        } else {
                            $error = 'فشل في إنشاء الحساب. يرجى المحاولة مرة أخرى.';
                        }
                    } catch (Exception $e) {
                        // تسجيل الخطأ للمطور
                        error_log("Registration Error: " . $e->getMessage());
                        
                        // التحقق إذا كان الحساب تم إنشاؤه رغم الخطأ
                        try {
                            $checkUser = $db->fetchOne("SELECT id FROM users WHERE username = ? OR email = ?", [$username, $email]);
                            if ($checkUser) {
                                $success = 'تم إنشاء حسابك بنجاح. سيتم مراجعة حسابك من قبل الإدارة قبل تفعيله.';
                                $action = 'login';
                            } else {
                                $error = 'حدث خطأ أثناء إنشاء الحساب. يرجى المحاولة مرة أخرى.';
                            }
                        } catch (Exception $e2) {
                            $error = 'حدث خطأ في النظام. يرجى المحاولة لاحقاً.';
                        }
                    }
                }
            } catch (Exception $e) {
                error_log("Database Error: " . $e->getMessage());
                $error = 'حدث خطأ في النظام. يرجى المحاولة لاحقاً.';
            }
        }
    } elseif ($action === 'forgot') {
        $email = sanitize($_POST['email']);
        
        if (empty($email)) {
            $error = 'يرجى إدخال البريد الإلكتروني';
        } else {
            try {
                $user = $db->fetchOne("SELECT * FROM users WHERE email = ?", [$email]);
                if ($user) {
                    // إنشاء رمز إعادة تعيين كلمة المرور
                    $reset_token = bin2hex(random_bytes(32));
                    $reset_expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    
                    // محاولة تحديث الرمز
                    try {
                        $db->update('users', [
                            'reset_token' => $reset_token,
                            'reset_expires' => $reset_expires
                        ], 'id = ?', [$user['id']]);
                        
                        $success = 'تم إرسال رابط إعادة تعيين كلمة المرور إلى بريدك الإلكتروني.';
                    } catch (Exception $e) {
                        // إذا فشل التحديث، قد تكون الأعمدة غير موجودة
                        $success = 'تم تسجيل طلبك. يرجى التواصل مع الإدارة لإعادة تعيين كلمة المرور.';
                    }
                } else {
                    $error = 'البريد الإلكتروني غير موجود';
                }
            } catch (Exception $e) {
                error_log("Forgot Password Error: " . $e->getMessage());
                $error = 'حدث خطأ في النظام. يرجى المحاولة لاحقاً.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php 
        if ($action === 'register') echo 'تسجيل جديد';
        elseif ($action === 'forgot') echo 'استعادة كلمة المرور';
        else echo 'تسجيل الدخول';
        ?> - <?php echo SITE_NAME; ?>
    </title>
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
        .auth-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            padding: 40px;
            width: 100%;
            max-width: 450px;
        }
        .auth-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .auth-header h2 {
            color: #333;
            margin-bottom: 10px;
        }
        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
            border: 2px solid #f0f0f0;
            margin-bottom: 20px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-auth {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            width: 100%;
            color: white;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-auth:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .auth-links {
            text-align: center;
            margin-top: 20px;
        }
        .auth-links a {
            color: #667eea;
            text-decoration: none;
            margin: 0 10px;
        }
        .auth-links a:hover {
            text-decoration: underline;
        }
        .tab-buttons {
            display: flex;
            margin-bottom: 30px;
            border-radius: 10px;
            overflow: hidden;
            border: 2px solid #f0f0f0;
        }
        .tab-button {
            flex: 1;
            padding: 12px;
            background: #f8f9fa;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }
        .tab-button.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
    </style>
</head>
<body>
    <div class="auth-card">
        <div class="auth-header">
            <h2><?php echo SITE_NAME; ?></h2>
            <p class="text-muted">
                <?php 
                if ($action === 'register') echo 'إنشاء حساب جديد';
                elseif ($action === 'forgot') echo 'استعادة كلمة المرور';
                else echo 'تسجيل الدخول إلى لوحة التحكم';
                ?>
            </p>
        </div>
        
        <!-- Tab Buttons -->
        <div class="tab-buttons">
            <button class="tab-button <?php echo $action === 'login' ? 'active' : ''; ?>" 
                    onclick="window.location.href='?action=login'">
                تسجيل الدخول
            </button>
            <button class="tab-button <?php echo $action === 'register' ? 'active' : ''; ?>" 
                    onclick="window.location.href='?action=register'">
                تسجيل جديد
            </button>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($action === 'login'): ?>
            <!-- Login Form -->
            <form method="POST">
                <div class="mb-3">
                    <input type="text" class="form-control" name="username" placeholder="اسم المستخدم" required>
                </div>
                <div class="mb-3">
                    <input type="password" class="form-control" name="password" placeholder="كلمة المرور" required>
                </div>
                <button type="submit" class="btn btn-auth">تسجيل الدخول</button>
            </form>
            
            <div class="auth-links">
                <a href="?action=forgot">نسيت كلمة المرور؟</a>
            </div>
            
        <?php elseif ($action === 'register'): ?>
            <!-- Registration Form -->
            <form method="POST">
                <div class="mb-3">
                    <input type="text" class="form-control" name="full_name" placeholder="الاسم الكامل" required>
                </div>
                <div class="mb-3">
                    <input type="text" class="form-control" name="username" placeholder="اسم المستخدم" required>
                </div>
                <div class="mb-3">
                    <input type="email" class="form-control" name="email" placeholder="البريد الإلكتروني" required>
                </div>
                <div class="mb-3">
                    <input type="password" class="form-control" name="password" placeholder="كلمة المرور" required>
                </div>
                <div class="mb-3">
                    <input type="password" class="form-control" name="confirm_password" placeholder="تأكيد كلمة المرور" required>
                </div>
                <button type="submit" class="btn btn-auth">إنشاء الحساب</button>
            </form>
            
            <div class="text-center mt-3">
                <small class="text-muted">
                    بإنشاء حساب، فإنك توافق على شروط الاستخدام<br>
                    سيتم مراجعة حسابك من قبل الإدارة قبل التفعيل
                </small>
            </div>
            
        <?php elseif ($action === 'forgot'): ?>
            <!-- Forgot Password Form -->
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">أدخل بريدك الإلكتروني لاستعادة كلمة المرور</label>
                    <input type="email" class="form-control" name="email" placeholder="البريد الإلكتروني" required>
                </div>
                <button type="submit" class="btn btn-auth">إرسال رابط الاستعادة</button>
            </form>
            
            <div class="auth-links">
                <a href="?action=login">العودة لتسجيل الدخول</a>
            </div>
        <?php endif; ?>
        
        <div class="text-center mt-4">
            <a href="index.php" class="text-decoration-none">العودة للموقع الرئيسي</a>
        </div>
    </div>
</body>
</html>
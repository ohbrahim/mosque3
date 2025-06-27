<?php
require_once '../config/config.php';
require_once '../includes/auth_functions.php';

// التحقق من Remember Me
checkRememberMe();

// إعادة توجيه إذا كان مسجل دخول بالفعل
if (isLoggedIn()) {
    if ($_SESSION['role'] === 'editor') {
        redirect('../admin/editor_dashboard.php');
    } else {
        redirect('../admin/index.php');
    }
}

$error = '';
$success = '';
$action = $_GET['action'] ?? 'login';

// معالجة تسجيل الدخول
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'login') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);
    
    if (!empty($username) && !empty($password)) {
        $result = loginUser($username, $password, $remember);
        
        if ($result['success']) {
            logActivity($_SESSION['user_id'], 'login', 'تسجيل دخول ناجح');
            
            // توجيه المستخدم حسب دوره
            if ($_SESSION['role'] === 'editor') {
                redirect('../admin/editor_dashboard.php');
            } else {
                redirect('../admin/index.php');
            }
        } else {
            $error = $result['message'];
            logActivity(null, 'login_failed', "محاولة دخول فاشلة: $username");
        }
    } else {
        $error = 'يرجى إدخال جميع البيانات';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>تسجيل الدخول</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            font: 14px sans-serif;
        }

        .wrapper {
            width: 360px;
            padding: 20px;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <h2>تسجيل الدخول</h2>
        <p>الرجاء إدخال بيانات الاعتماد الخاصة بك لتسجيل الدخول.</p>

        <?php
        if (!empty($error)) {
            echo '<div class="alert alert-danger">' . $error . '</div>';
        }
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . '?action=login'); ?>" method="post">
            <div class="form-group">
                <label>اسم المستخدم</label>
                <input type="text" name="username" class="form-control" value="">
            </div>
            <div class="form-group">
                <label>كلمة المرور</label>
                <input type="password" name="password" class="form-control">
            </div>
            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="rememberMe" name="remember">
                    <label class="form-check-label" for="rememberMe">تذكرني</label>
                </div>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="تسجيل الدخول">
            </div>
            <p>ليس لديك حساب؟ <a href="register.php">سجل الآن</a>.</p>
        </form>
    </div>
</body>

</html>

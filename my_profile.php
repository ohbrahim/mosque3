<?php
session_start();
require_once 'config/auto_config.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// جلب بيانات المستخدم
$user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$user_id]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // التحقق من البيانات الأساسية
    if (empty($full_name) || empty($email)) {
        $error = 'يرجى إدخال جميع البيانات المطلوبة';
    } else {
        $updateData = [
            'full_name' => $full_name,
            'email' => $email
        ];
        
        // تغيير كلمة المرور إذا تم إدخالها
        if (!empty($new_password)) {
            if (empty($current_password)) {
                $error = 'يرجى إدخال كلمة المرور الحالية';
            } elseif (!password_verify($current_password, $user['password'])) {
                $error = 'كلمة المرور الحالية غير صحيحة';
            } elseif ($new_password !== $confirm_password) {
                $error = 'كلمة المرور الجديدة وتأكيدها غير متطابقتين';
            } elseif (strlen($new_password) < 6) {
                $error = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
            } else {
                $updateData['password'] = password_hash($new_password, PASSWORD_DEFAULT);
            }
        }
        
        if (empty($error)) {
            try {
                $db->update('users', $updateData, 'id = ?', [$user_id]);
                $success = 'تم تحديث الملف الشخصي بنجاح';
                // تحديث بيانات المستخدم في الجلسة
                $_SESSION['full_name'] = $full_name;
                // إعادة جلب البيانات المحدثة
                $user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$user_id]);
            } catch (Exception $e) {
                $error = 'حدث خطأ أثناء التحديث: ' . $e->getMessage();
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
    <title>الملف الشخصي</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .profile-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2rem 0; }
        .profile-card { box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); border-radius: 10px; }
    </style>
</head>
<body>
    <div class="profile-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-0">الملف الشخصي</h1>
                    <p class="mb-0">مرحباً <?php echo htmlspecialchars($user['full_name']); ?></p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="index.php" class="btn btn-light">العودة للرئيسية</a>
                    <?php if ($_SESSION['role'] !== 'member'): ?>
                        <a href="admin/" class="btn btn-warning">لوحة التحكم</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8">
                <div class="card profile-card">
                    <div class="card-header">
                        <h5 class="mb-0">تحديث البيانات الشخصية</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">اسم المستخدم</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                                <small class="text-muted">لا يمكن تغيير اسم المستخدم</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">الاسم الكامل</label>
                                <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">البريد الإلكتروني</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            
                            <hr>
                            <h6>تغيير كلمة المرور (اختياري)</h6>
                            
                            <div class="mb-3">
                                <label class="form-label">كلمة المرور الحالية</label>
                                <input type="password" name="current_password" class="form-control">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">كلمة المرور الجديدة</label>
                                <input type="password" name="new_password" class="form-control">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">تأكيد كلمة المرور الجديدة</label>
                                <input type="password" name="confirm_password" class="form-control">
                            </div>
                            
                            <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card profile-card">
                    <div class="card-header">
                        <h5 class="mb-0">معلومات الحساب</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>الدور:</strong> 
                            <?php 
                            $roles = [
                                'admin' => 'مدير',
                                'moderator' => 'مشرف', 
                                'editor' => 'محرر',
                                'member' => 'عضو'
                            ];
                            echo $roles[$user['role']] ?? $user['role'];
                            ?>
                        </p>
                        <p><strong>الحالة:</strong> 
                            <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : 'warning'; ?>">
                                <?php echo $user['status'] === 'active' ? 'نشط' : 'معلق'; ?>
                            </span>
                        </p>
                        <p><strong>تاريخ التسجيل:</strong> <?php echo date('Y-m-d', strtotime($user['created_at'])); ?></p>
                        <?php if ($user['last_login']): ?>
                            <p><strong>آخر دخول:</strong> <?php echo date('Y-m-d H:i', strtotime($user['last_login'])); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card profile-card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0">روابط سريعة</h5>
                    </div>
                    <div class="card-body">
                        <a href="my_comments.php" class="btn btn-outline-primary btn-sm d-block mb-2">تعليقاتي</a>
                        <a href="my_stats.php" class="btn btn-outline-info btn-sm d-block mb-2">إحصائياتي</a>
                        <?php if ($_SESSION['role'] !== 'member'): ?>
                            <a href="admin/" class="btn btn-outline-success btn-sm d-block">لوحة التحكم</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

public function update($table, $data, $where, $params = []) {
    try {
        $set = [];
        $allParams = [];
        
        // إعداد البيانات للتحديث
        foreach ($data as $key => $value) {
            $set[] = "{$key} = ?";
            $allParams[] = $value;
        }
        
        // إضافة معاملات WHERE
        foreach ($params as $param) {
            $allParams[] = $param;
        }
        
        $sql = "UPDATE {$table} SET " . implode(', ', $set) . " WHERE {$where}";
        
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute($allParams);
        
        return $result;
    } catch (PDOException $e) {
        error_log("Database Update Error: " . $e->getMessage() . " SQL: " . $sql);
        return false;
    }
}
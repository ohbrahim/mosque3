<?php
/**
 * إنشاء مستخدم مدير افتراضي
 */
require_once '../config/config.php';

echo "<!DOCTYPE html>
<html lang='ar' dir='rtl'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>إنشاء مستخدم مدير</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap' rel='stylesheet'>
    <style>
        body { font-family: 'Cairo', sans-serif; background: #f8f9fa; }
        .container { max-width: 600px; margin-top: 50px; }
        .log-item { padding: 10px; margin: 5px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
    </style>
</head>
<body>
<div class='container'>
    <h1 class='text-center mb-4'>إنشاء مستخدم مدير افتراضي</h1>
    <div class='card'>
        <div class='card-body'>";

try {
    // التحقق من وجود مستخدم مدير
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
    $stmt->execute();
    $adminCount = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($adminCount['count'] == 0) {
        // إنشاء مستخدم مدير افتراضي
        $adminData = [
            'username' => 'admin',
            'email' => 'admin@mosque.com',
            'password' => password_hash('admin123', PASSWORD_DEFAULT),
            'full_name' => 'مدير النظام',
            'role' => 'admin',
            'status' => 'active'
        ];
        
        $stmt = $db->prepare("INSERT INTO users (username, email, password, full_name, role, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $result = $stmt->execute([
            $adminData['username'],
            $adminData['email'], 
            $adminData['password'],
            $adminData['full_name'],
            $adminData['role'],
            $adminData['status']
        ]);
        
        if ($result) {
            echo "<div class='log-item success'>✅ تم إنشاء مستخدم المدير بنجاح!</div>";
            echo "<div class='log-item info'>📧 اسم المستخدم: admin</div>";
            echo "<div class='log-item info'>🔑 كلمة المرور: admin123</div>";
            echo "<div class='log-item info'>📧 البريد الإلكتروني: admin@mosque.com</div>";
        } else {
            echo "<div class='log-item error'>❌ فشل في إنشاء المستخدم</div>";
        }
    } else {
        echo "<div class='log-item info'>ℹ️ يوجد مستخدم مدير مسبقاً</div>";
        
        // عرض بيانات المدير الموجود
        $stmt = $db->prepare("SELECT username, email, full_name FROM users WHERE role = 'admin' LIMIT 1");
        $stmt->execute();
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin) {
            echo "<div class='log-item info'>👤 اسم المستخدم: " . htmlspecialchars($admin['username']) . "</div>";
            echo "<div class='log-item info'>📧 البريد الإلكتروني: " . htmlspecialchars($admin['email']) . "</div>";
            echo "<div class='log-item info'>👨‍💼 الاسم الكامل: " . htmlspecialchars($admin['full_name']) . "</div>";
        }
    }
    
    echo "<div class='mt-4 text-center'>";
    echo "<a href='../login.php' class='btn btn-primary'>تسجيل الدخول</a> ";
    echo "<a href='../test_header_footer.php' class='btn btn-secondary'>اختبار النظام</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='log-item error'>❌ خطأ: " . $e->getMessage() . "</div>";
}

echo "        </div>
    </div>
</div>
</body>
</html>";
?>

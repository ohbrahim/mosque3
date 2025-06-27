<?php
/**
 * إصلاح مشكلة الترميز
 */

// إعدادات قاعدة البيانات
$host = 'localhost';
$username = 'root'; // ضع اسم المستخدم الخاص بك
$password = ''; // ضع كلمة المرور الخاصة بك
$database = 'ohbrah52_mosque'; // ضع اسم قاعدة البيانات الخاصة بك

echo "<!DOCTYPE html>
<html lang='ar' dir='rtl'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>إصلاح مشكلة الترميز</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap' rel='stylesheet'>
    <style>
        body { font-family: 'Cairo', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .fix-container { background: white; border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); margin: 50px auto; max-width: 800px; }
        .fix-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 20px 20px 0 0; }
        .fix-body { padding: 40px; }
        .step { background: #f8f9fa; border-radius: 10px; padding: 20px; margin-bottom: 20px; }
        .step-number { background: #667eea; color: white; border-radius: 50%; width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center; margin-left: 10px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='fix-container'>
            <div class='fix-header'>
                <h1>🔧 إصلاح مشكلة الترميز</h1>
                <p>إصلاح مشكلة عرض النصوص العربية</p>
            </div>
            <div class='fix-body'>";

try {
    // الاتصال بقاعدة البيانات
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // تعيين الترميز
    $pdo->exec("SET NAMES utf8");
    $pdo->exec("SET CHARACTER SET utf8");
    $pdo->exec("SET character_set_connection=utf8");
    
    echo "<div class='step'>
            <span class='step-number'>1</span>
            <strong>الاتصال بقاعدة البيانات:</strong> 
            <span class='text-success'>✅ نجح</span>
          </div>";
    
    // تحويل ترميز الجداول
    $tables = ['users', 'permissions', 'settings', 'pages', 'blocks', 'advertisements', 'comments', 'messages', 'polls', 'poll_options'];
    
    foreach ($tables as $table) {
        $pdo->exec("ALTER TABLE `$table` CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci");
    }
    
    echo "<div class='step'>
            <span class='step-number'>2</span>
            <strong>تحويل ترميز الجداول:</strong> 
            <span class='text-success'>✅ نجح</span>
          </div>";
    
    // حذف البيانات القديمة وإدراج بيانات جديدة
    $pdo->exec("DELETE FROM permissions");
    $pdo->exec("DELETE FROM settings");
    $pdo->exec("DELETE FROM blocks");
    $pdo->exec("DELETE FROM pages");
    
    // إدراج البيانات بترميز صحيح
    $permissions = [
        ['manage_users', 'إدارة المستخدمين', 'users'],
        ['manage_pages', 'إدارة الصفحات', 'pages'],
        ['manage_blocks', 'إدارة البلوكات', 'blocks'],
        ['manage_ads', 'إدارة الإعلانات', 'advertisements'],
        ['manage_comments', 'إدارة التعليقات', 'comments'],
        ['manage_messages', 'إدارة الرسائل', 'messages'],
        ['manage_polls', 'إدارة الاستطلاعات', 'polls'],
        ['view_stats', 'عرض الإحصائيات', 'statistics'],
        ['manage_settings', 'إدارة الإعدادات', 'settings']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO permissions (name, description, module) VALUES (?, ?, ?)");
    foreach ($permissions as $perm) {
        $stmt->execute($perm);
    }
    
    $settings = [
        ['site_name', 'مسجد النور', 'text', 'general', 'اسم الموقع'],
        ['site_description', 'موقع مسجد النور للتعليم القرآني', 'textarea', 'general', 'وصف الموقع'],
        ['site_logo', '', 'image', 'general', 'شعار الموقع'],
        ['contact_email', 'info@mosque.com', 'text', 'contact', 'بريد التواصل'],
        ['contact_phone', '+966123456789', 'text', 'contact', 'هاتف التواصل'],
        ['contact_address', 'الرياض، المملكة العربية السعودية', 'textarea', 'contact', 'عنوان المسجد'],
        ['prayer_city', 'Riyadh', 'text', 'prayer', 'مدينة أوقات الصلاة'],
        ['enable_comments', '1', 'boolean', 'features', 'تفعيل التعليقات'],
        ['enable_ratings', '1', 'boolean', 'features', 'تفعيل التقييمات'],
        ['items_per_page', '10', 'text', 'general', 'عدد العناصر في الصفحة']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, setting_type, category, description) VALUES (?, ?, ?, ?, ?)");
    foreach ($settings as $setting) {
        $stmt->execute($setting);
    }
    
    echo "<div class='step'>
            <span class='step-number'>3</span>
            <strong>إدراج البيانات الأساسية:</strong> 
            <span class='text-success'>✅ نجح</span>
          </div>";
    
    echo "<div class='alert alert-success mt-4'>
            <h4>🎉 تم إصلاح مشكلة الترميز بنجاح!</h4>
            <p>الآن يمكنك استخدام الموقع بشكل طبيعي مع عرض النصوص العربية بشكل صحيح.</p>
            <div class='mt-3'>
                <a href='../index.php' class='btn btn-primary me-2'>عرض الموقع</a>
                <a href='../admin/index.php' class='btn btn-success'>لوحة التحكم</a>
            </div>
            <div class='alert alert-info mt-3'>
                <strong>بيانات تسجيل الدخول:</strong><br>
                اسم المستخدم: <code>admin</code><br>
                كلمة المرور: <code>password</code>
            </div>
          </div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>
            <h4>❌ خطأ في الإصلاح</h4>
            <p>حدث خطأ: " . $e->getMessage() . "</p>
          </div>";
}

echo "        </div>
        </div>
    </div>
</body>
</html>";
?>

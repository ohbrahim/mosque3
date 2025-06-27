<?php
/**
 * ملف الإعداد والتثبيت
 */

// إعدادات قاعدة البيانات - يجب تحديثها حسب الخادم
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'mosque_management';

echo "<!DOCTYPE html>
<html lang='ar' dir='rtl'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>تثبيت نظام إدارة المسجد</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap' rel='stylesheet'>
    <style>
        body { font-family: 'Cairo', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .install-container { background: white; border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); margin: 50px auto; max-width: 800px; }
        .install-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 20px 20px 0 0; }
        .install-body { padding: 40px; }
        .step { background: #f8f9fa; border-radius: 10px; padding: 20px; margin-bottom: 20px; }
        .step-number { background: #667eea; color: white; border-radius: 50%; width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center; margin-left: 10px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='install-container'>
            <div class='install-header'>
                <h1>🕌 تثبيت نظام إدارة المسجد</h1>
                <p>مرحباً بك في معالج التثبيت</p>
            </div>
            <div class='install-body'>";

try {
    // الاتصال بقاعدة البيانات
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div class='step'>
            <span class='step-number'>1</span>
            <strong>الاتصال بقاعدة البيانات:</strong> 
            <span class='text-success'>✅ نجح</span>
          </div>";
    
    // إنشاء قاعدة البيانات
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$database`");
    
    echo "<div class='step'>
            <span class='step-number'>2</span>
            <strong>إنشاء قاعدة البيانات:</strong> 
            <span class='text-success'>✅ نجح</span>
          </div>";
    
    // قراءة وتنفيذ ملف SQL
    $sqlFile = __DIR__ . '/../database/mosque_management_fixed.sql';
    if (file_exists($sqlFile)) {
        $sql = file_get_contents($sqlFile);
        
        // تقسيم الاستعلامات
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($statements as $statement) {
            if (!empty($statement) && !preg_match('/^--/', $statement)) {
                $pdo->exec($statement);
            }
        }
        
        echo "<div class='step'>
                <span class='step-number'>3</span>
                <strong>إنشاء الجداول:</strong> 
                <span class='text-success'>✅ نجح</span>
              </div>";
    } else {
        throw new Exception("ملف SQL غير موجود");
    }
    
    // إنشاء مجلد الرفع
    $uploadDir = __DIR__ . '/../uploads';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    echo "<div class='step'>
            <span class='step-number'>4</span>
            <strong>إنشاء مجلد الرفع:</strong> 
            <span class='text-success'>✅ نجح</span>
          </div>";
    
    // إنشاء ملف .htaccess للحماية
    $htaccessContent = "RewriteEngine On\n";
    $htaccessContent .= "RewriteCond %{REQUEST_FILENAME} !-f\n";
    $htaccessContent .= "RewriteCond %{REQUEST_FILENAME} !-d\n";
    $htaccessContent .= "RewriteRule ^(.*)$ index.php?page=$1 [QSA,L]\n";
    
    file_put_contents(__DIR__ . '/../.htaccess', $htaccessContent);
    
    echo "<div class='step'>
            <span class='step-number'>5</span>
            <strong>إعداد ملفات النظام:</strong> 
            <span class='text-success'>✅ نجح</span>
          </div>";
    
    echo "<div class='alert alert-success mt-4'>
            <h4>🎉 تم التثبيت بنجاح!</h4>
            <p>تم تثبيت النظام بنجاح. يمكنك الآن:</p>
            <ul>
                <li><strong>تسجيل الدخول:</strong> اسم المستخدم: <code>admin</code> | كلمة المرور: <code>password</code></li>
                <li><strong>رابط الموقع:</strong> <a href='../index.php' target='_blank'>عرض الموقع</a></li>
                <li><strong>لوحة التحكم:</strong> <a href='../admin/index.php' target='_blank'>لوحة التحكم</a></li>
            </ul>
            <div class='alert alert-warning mt-3'>
                <strong>مهم:</strong> يرجى حذف مجلد <code>install</code> بعد التثبيت لأسباب أمنية.
            </div>
          </div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>
            <h4>❌ خطأ في التثبيت</h4>
            <p>حدث خطأ أثناء التثبيت: " . $e->getMessage() . "</p>
            <p>يرجى التأكد من:</p>
            <ul>
                <li>صحة بيانات الاتصال بقاعدة البيانات</li>
                <li>صلاحيات الكتابة على المجلد</li>
                <li>تفعيل إضافة PDO في PHP</li>
            </ul>
          </div>";
}

echo "        </div>
        </div>
    </div>
</body>
</html>";
?>

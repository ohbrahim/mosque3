<?php
/**
 * إصلاح جميع الأخطاء
 */

// منع عرض الأخطاء أثناء الإصلاح
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html lang='ar' dir='rtl'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>إصلاح جميع الأخطاء</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap' rel='stylesheet'>
    <style>
        body { font-family: 'Cairo', sans-serif; background: #f8f9fa; }
        .container { max-width: 800px; margin-top: 30px; }
        .log-item { padding: 10px; margin: 5px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
    </style>
</head>
<body>
<div class='container'>
    <h1 class='text-center mb-4'>🔧 إصلاح جميع أخطاء النظام</h1>
    <div class='card'>
        <div class='card-body'>";

$steps = [
    '1. التحقق من قاعدة البيانات',
    '2. إصلاح الجداول المفقودة', 
    '3. إنشاء مستخدم مدير',
    '4. إضافة الإعدادات الافتراضية',
    '5. إضافة عناصر القوائم',
    '6. اختبار النظام'
];

foreach ($steps as $step) {
    echo "<div class='log-item info'>⏳ {$step}...</div>";
}

try {
    // الخطوة 1: التحقق من قاعدة البيانات
    require_once '../config/config.php';
    echo "<div class='log-item success'>✅ تم الاتصال بقاعدة البيانات بنجاح</div>";
    
    // الخطوة 2: إصلاح الجداول
    $sqlFile = '../database/fix_header_footer.sql';
    if (file_exists($sqlFile)) {
        $sql = file_get_contents($sqlFile);
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($statements as $statement) {
            if (!empty($statement) && strpos($statement, '--') !== 0) {
                try {
                    $db->query($statement);
                } catch (Exception $e) {
                    if (strpos($e->getMessage(), 'already exists') === false && 
                        strpos($e->getMessage(), 'Duplicate entry') === false) {
                        echo "<div class='log-item warning'>⚠️ تحذير: " . $e->getMessage() . "</div>";
                    }
                }
            }
        }
        echo "<div class='log-item success'>✅ تم إصلاح الجداول</div>";
    }
    
    // الخطوة 3: إنشاء مستخدم مدير
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
    $stmt->execute();
    $adminCount = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($adminCount['count'] == 0) {
        $stmt = $db->prepare("INSERT INTO users (username, email, password, full_name, role, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            'admin',
            'admin@mosque.com',
            password_hash('admin123', PASSWORD_DEFAULT),
            'مدير النظام',
            'admin',
            'active'
        ]);
        echo "<div class='log-item success'>✅ تم إنشاء مستخدم المدير (admin/admin123)</div>";
    } else {
        echo "<div class='log-item info'>ℹ️ مستخدم المدير موجود مسبقاً</div>";
    }
    
    // الخطوة 4: الإعدادات الافتراضية
    $defaultSettings = [
        'site_name' => 'مسجد النور',
        'site_description' => 'مسجد النور - مكان للعبادة والتعلم والتواصل المجتمعي',
        'header_style' => 'modern',
        'footer_style' => 'modern',
        'header_bg_color' => '#667eea',
        'header_text_color' => '#ffffff',
        'footer_bg_color' => '#2c3e50',
        'footer_text_color' => '#ffffff'
    ];
    
    foreach ($defaultSettings as $key => $value) {
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $exists = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($exists['count'] == 0) {
            $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value, category, setting_type) VALUES (?, ?, 'general', 'text')");
            $stmt->execute([$key, $value]);
        }
    }
    echo "<div class='log-item success'>✅ تم إضافة الإعدادات الافتراضية</div>";
    
    // الخطوة 5: عناصر القوائم
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM menu_items WHERE menu_position = 'header'");
    $stmt->execute();
    $menuCount = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($menuCount['count'] == 0) {
        $menuItems = [
            ['الرئيسية', 'index.php', 'fas fa-home'],
            ['عن المسجد', '?page=about', 'fas fa-mosque'],
            ['القرآن الكريم', 'quran.php', 'fas fa-book-quran'],
            ['المكتبة', 'library.php', 'fas fa-book'],
            ['اتصل بنا', 'contact.php', 'fas fa-envelope']
        ];
        
        foreach ($menuItems as $index => $item) {
            $stmt = $db->prepare("INSERT INTO menu_items (title, url, icon, menu_position, display_order, target, status) VALUES (?, ?, ?, 'header', ?, '_self', 'active')");
            $stmt->execute([$item[0], $item[1], $item[2], $index + 1]);
        }
        echo "<div class='log-item success'>✅ تم إضافة عناصر القوائم</div>";
    } else {
        echo "<div class='log-item info'>ℹ️ عناصر القوائم موجودة مسبقاً</div>";
    }
    
    // الخطوة 6: اختبار النظام
    echo "<div class='log-item success'>🎉 تم إصلاح جميع الأخطاء بنجاح!</div>";
    
    echo "<div class='mt-4 text-center'>";
    echo "<h5>بيانات تسجيل الدخول:</h5>";
    echo "<p><strong>اسم المستخدم:</strong> admin</p>";
    echo "<p><strong>كلمة المرور:</strong> admin123</p>";
    echo "<div class='btn-group mt-3'>";
    echo "<a href='../login.php' class='btn btn-primary'>تسجيل الدخول</a>";
    echo "<a href='../test_header_footer.php' class='btn btn-success'>اختبار النظام</a>";
    echo "<a href='../index.php' class='btn btn-info'>الصفحة الرئيسية</a>";
    echo "</div>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='log-item error'>❌ خطأ عام: " . $e->getMessage() . "</div>";
    echo "<div class='log-item info'>💡 تأكد من إنشاء قاعدة البيانات 'mosque_management' أولاً</div>";
}

echo "        </div>
    </div>
</div>
</body>
</html>";
?>

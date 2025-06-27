<?php
/**
 * إصلاح صفحات لوحة التحكم
 */
require_once '../config/config.php';

// التحقق من تسجيل الدخول
requireLogin();
requirePermission('admin_access');

echo "<!DOCTYPE html>
<html lang='ar' dir='rtl'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>إصلاح لوحة التحكم</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap' rel='stylesheet'>
    <style>
        body { font-family: 'Cairo', sans-serif; background: #f8f9fa; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .card { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .alert { border-radius: 10px; }
        .btn { border-radius: 8px; }
    </style>
</head>
<body>
<div class='container'>
    <div class='card'>
        <div class='card-header bg-primary text-white'>
            <h3 class='mb-0'>إصلاح لوحة التحكم</h3>
        </div>
        <div class='card-body'>";

$errors = [];
$success = [];

// فحص الملفات المطلوبة
$requiredFiles = [
    'index.php' => 'الصفحة الرئيسية',
    'pages.php' => 'إدارة الصفحات',
    'blocks.php' => 'إدارة البلوكات',
    'users.php' => 'إدارة المستخدمين',
    'messages.php' => 'إدارة الرسائل',
    'comments.php' => 'إدارة التعليقات',
    'advertisements.php' => 'إدارة الإعلانات',
    'polls.php' => 'إدارة الاستطلاعات',
    'statistics.php' => 'الإحصائيات',
    'settings.php' => 'الإعدادات',
    'header_footer.php' => 'الهيدر والفوتر',
    'menu_manager.php' => 'إدارة القوائم'
];

echo "<h5>فحص ملفات لوحة التحكم:</h5>";

foreach ($requiredFiles as $file => $name) {
    if (file_exists($file)) {
        echo "<div class='alert alert-success'>✓ {$name} ({$file}) - موجود</div>";
        $success[] = $file;
    } else {
        echo "<div class='alert alert-danger'>✗ {$name} ({$file}) - مفقود</div>";
        $errors[] = $file;
    }
}

// فحص قاعدة البيانات
echo "<h5 class='mt-4'>فحص قاعدة البيانات:</h5>";

$requiredTables = [
    'users' => 'المستخدمين',
    'pages' => 'الصفحات',
    'blocks' => 'البلوكات',
    'messages' => 'الرسائل',
    'comments' => 'التعليقات',
    'advertisements' => 'الإعلانات',
    'polls' => 'الاستطلاعات',
    'settings' => 'الإعدادات',
    'menu_items' => 'عناصر القائمة',
    'header_footer_content' => 'محتوى الهيدر والفوتر'
];

foreach ($requiredTables as $table => $name) {
    try {
        $result = $db->fetchOne("SHOW TABLES LIKE '{$table}'");
        if ($result) {
            echo "<div class='alert alert-success'>✓ جدول {$name} ({$table}) - موجود</div>";
        } else {
            echo "<div class='alert alert-danger'>✗ جدول {$name} ({$table}) - مفقود</div>";
        }
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>✗ خطأ في فحص جدول {$name}: " . $e->getMessage() . "</div>";
    }
}

// فحص الصلاحيات
echo "<h5 class='mt-4'>فحص الصلاحيات:</h5>";

$permissions = [
    'admin_access' => 'الوصول للوحة التحكم',
    'manage_pages' => 'إدارة الصفحات',
    'manage_users' => 'إدارة المستخدمين',
    'manage_settings' => 'إدارة الإعدادات'
];

foreach ($permissions as $perm => $name) {
    if ($auth->hasPermission($perm)) {
        echo "<div class='alert alert-success'>✓ {$name} ({$perm}) - متاح</div>";
    } else {
        echo "<div class='alert alert-warning'>⚠️ {$name} ({$perm}) - غير متاح</div>";
    }
}

echo "<div class='mt-4'>
        <a href='index.php' class='btn btn-primary'>العودة للوحة التحكم</a>
        <a href='../index.php' class='btn btn-secondary'>عرض الموقع</a>
      </div>";

echo "</div></div></div></body></html>";
?>

<?php
/**
 * اختبار سريع لفحص المشاكل
 */

// تشغيل عرض الأخطاء
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html lang='ar' dir='rtl'>
<head>
    <meta charset='UTF-8'>
    <title>اختبار النظام</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .test { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { border-left: 5px solid #28a745; }
        .error { border-left: 5px solid #dc3545; }
        .warning { border-left: 5px solid #ffc107; }
    </style>
</head>
<body>";

echo "<h2>اختبار النظام</h2>";

// اختبار 1: تحميل config.php
echo "<div class='test'>";
try {
    require_once '../config/config.php';
    echo "<div class='success'>✓ تم تحميل config.php بنجاح</div>";
} catch (Exception $e) {
    echo "<div class='error'>✗ خطأ في تحميل config.php: " . $e->getMessage() . "</div>";
}
echo "</div>";

// اختبار 2: اتصال قاعدة البيانات
echo "<div class='test'>";
try {
    if (isset($db)) {
        $result = $db->fetchOne("SELECT 1 as test");
        echo "<div class='success'>✓ اتصال قاعدة البيانات يعمل</div>";
    } else {
        echo "<div class='error'>✗ متغير قاعدة البيانات غير موجود</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>✗ خطأ في قاعدة البيانات: " . $e->getMessage() . "</div>";
}
echo "</div>";

// اختبار 3: الدوال المطلوبة
echo "<div class='test'>";
$functions = ['sanitize', 'generateCSRFToken', 'verifyCSRFToken', 'requireLogin', 'requirePermission'];
foreach ($functions as $func) {
    if (function_exists($func)) {
        echo "<div class='success'>✓ دالة {$func} موجودة</div>";
    } else {
        echo "<div class='error'>✗ دالة {$func} مفقودة</div>";
    }
}
echo "</div>";

// اختبار 4: الجلسة
echo "<div class='test'>";
if (session_status() == PHP_SESSION_ACTIVE) {
    echo "<div class='success'>✓ الجلسة نشطة</div>";
    if (isset($_SESSION['user_id'])) {
        echo "<div class='success'>✓ المستخدم مسجل دخول: " . ($_SESSION['full_name'] ?? 'غير محدد') . "</div>";
    } else {
        echo "<div class='warning'>⚠️ المستخدم غير مسجل دخول</div>";
    }
} else {
    echo "<div class='error'>✗ الجلسة غير نشطة</div>";
}
echo "</div>";

// اختبار 5: الجداول المطلوبة
echo "<div class='test'>";
$tables = ['users', 'pages', 'blocks', 'messages', 'settings', 'polls', 'poll_options'];
foreach ($tables as $table) {
    try {
        $result = $db->fetchOne("SHOW TABLES LIKE '{$table}'");
        if ($result) {
            echo "<div class='success'>✓ جدول {$table} موجود</div>";
        } else {
            echo "<div class='error'>✗ جدول {$table} مفقود</div>";
        }
    } catch (Exception $e) {
        echo "<div class='error'>✗ خطأ في فحص جدول {$table}: " . $e->getMessage() . "</div>";
    }
}
echo "</div>";

echo "<div class='test'>
        <a href='index.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>العودة للوحة التحكم</a>
      </div>";

echo "</body></html>";
?>

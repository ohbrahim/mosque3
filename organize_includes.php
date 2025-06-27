<?php
/**
 * سكريبت تنظيم مجلد includes
 * ينشئ مجلدات فرعية وينقل الملفات حسب الوظيفة
 */

// إنشاء المجلدات الفرعية
$directories = [
    'auth' => 'ملفات المصادقة والتحقق',
    'blocks' => 'ملفات إدارة البلوكات',
    'comments' => 'ملفات نظام التعليقات',
    'email' => 'ملفات البريد الإلكتروني',
    'functions' => 'ملفات الدوال العامة',
    'ui' => 'ملفات واجهة المستخدم',
    'upload' => 'ملفات رفع الملفات'
];

$basePath = __DIR__ . '/includes/';

echo "<h2>تنظيم مجلد includes</h2>";

// إنشاء المجلدات
foreach ($directories as $dir => $description) {
    $dirPath = $basePath . $dir;
    if (!is_dir($dirPath)) {
        if (mkdir($dirPath, 0755, true)) {
            echo "<p>✅ تم إنشاء مجلد: $dir - $description</p>";
        } else {
            echo "<p>❌ فشل في إنشاء مجلد: $dir</p>";
        }
    } else {
        echo "<p>ℹ️ المجلد موجود بالفعل: $dir</p>";
    }
}

// تحديد الملفات المراد نقلها
$fileMapping = [
    'auth' => [
        'auth.php',
        'auth_enhanced.php', 
        'auth_functions.php'
    ],
    'blocks' => [
        'block_functions.php',
        'block_functions_final.php',
        'block_functions_fixed.php',
        'block_functions_simple.php',
        'block_functions_updated.php',
        'blocks_manager.php',
        'enhanced_blocks.php',
        'enhanced_blocks_v2.php',
        'auto_refresh_blocks.php'
    ],
    'comments' => [
        'comments_functions.php',
        'comments_system.php'
    ],
    'email' => [
        'email.php',
        'email_functions.php'
    ],
    'functions' => [
        'all_functions.php',
        'functions.php',
        'functions_complete.php',
        'functions_fixed.php',
        'functions_new.php',
        'helper_functions.php',
        'activity_logger.php'
    ],
    'ui' => [
        'header.php',
        'footer.php',
        'footer_fixed.php',
        'sidebar.php',
        'widgets.php'
    ],
    'upload' => [
        'upload.php',
        'upload_functions.php'
    ]
];

echo "<h3>نقل الملفات:</h3>";

// نقل الملفات
foreach ($fileMapping as $folder => $files) {
    echo "<h4>مجلد $folder:</h4>";
    foreach ($files as $file) {
        $sourcePath = $basePath . $file;
        $destPath = $basePath . $folder . '/' . $file;
        
        if (file_exists($sourcePath)) {
            if (rename($sourcePath, $destPath)) {
                echo "<p>✅ تم نقل: $file إلى $folder/</p>";
            } else {
                echo "<p>❌ فشل في نقل: $file</p>";
            }
        } else {
            echo "<p>⚠️ الملف غير موجود: $file</p>";
        }
    }
}

// حذف الملفات المكررة أو غير المطلوبة
$filesToDelete = [
    'config.php' // سيتم استخدام config.php الرئيسي
];

echo "<h3>حذف الملفات غير المطلوبة:</h3>";
foreach ($filesToDelete as $file) {
    $filePath = $basePath . $file;
    if (file_exists($filePath)) {
        if (unlink($filePath)) {
            echo "<p>✅ تم حذف: $file</p>";
        } else {
            echo "<p>❌ فشل في حذف: $file</p>";
        }
    }
}

echo "<h3>✅ تم الانتهاء من تنظيم مجلد includes</h3>";
echo "<p><a href='index.php'>العودة للصفحة الرئيسية</a></p>";
?>
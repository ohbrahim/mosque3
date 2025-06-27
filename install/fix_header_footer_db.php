<?php
/**
 * إصلاح قاعدة البيانات للهيدر والفوتر
 */
require_once '../config/config.php';

echo "<!DOCTYPE html>
<html lang='ar' dir='rtl'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>إصلاح قاعدة البيانات</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap' rel='stylesheet'>
    <style>
        body { font-family: 'Cairo', sans-serif; background: #f8f9fa; }
        .container { max-width: 800px; margin-top: 50px; }
        .log-item { padding: 10px; margin: 5px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
    </style>
</head>
<body>
<div class='container'>
    <h1 class='text-center mb-4'>إصلاح قاعدة البيانات للهيدر والفوتر</h1>
    <div class='card'>
        <div class='card-body'>";

try {
    // قراءة ملف SQL
    $sqlFile = '../database/fix_header_footer.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception('ملف SQL غير موجود');
    }
    
    $sql = file_get_contents($sqlFile);
    $statements = explode(';', $sql);
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        try {
            $db->query($statement);
            echo "<div class='log-item success'>✅ تم تنفيذ: " . substr($statement, 0, 100) . "...</div>";
            $successCount++;
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                echo "<div class='log-item info'>ℹ️ موجود مسبقاً: " . substr($statement, 0, 100) . "...</div>";
            } else {
                echo "<div class='log-item error'>❌ خطأ: " . $e->getMessage() . "</div>";
                $errorCount++;
            }
        }
    }
    
    echo "<div class='mt-4 p-3 bg-light rounded'>";
    echo "<h5>ملخص العملية:</h5>";
    echo "<p>✅ العمليات الناجحة: {$successCount}</p>";
    echo "<p>❌ الأخطاء: {$errorCount}</p>";
    
    if ($errorCount === 0) {
        echo "<div class='alert alert-success'>🎉 تم إصلاح قاعدة البيانات بنجاح!</div>";
        echo "<a href='../test_header_footer.php' class='btn btn-primary'>اختبار النظام</a> ";
        echo "<a href='../admin/header_footer.php' class='btn btn-secondary'>إدارة الهيدر والفوتر</a>";
    } else {
        echo "<div class='alert alert-warning'>⚠️ تم الإصلاح مع بعض التحذيرات</div>";
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='log-item error'>❌ خطأ عام: " . $e->getMessage() . "</div>";
}

echo "        </div>
    </div>
</div>
</body>
</html>";
?>

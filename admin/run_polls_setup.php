<?php
/**
 * إعداد جدول الاستطلاعات
 */
require_once '../config/config.php';

requireLogin();
requirePermission('admin_access');

echo "<!DOCTYPE html>
<html lang='ar' dir='rtl'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>إعداد جدول الاستطلاعات</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap' rel='stylesheet'>
    <style>
        body { font-family: 'Cairo', sans-serif; background: #f8f9fa; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .card { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
<div class='container'>
    <div class='card'>
        <div class='card-header bg-primary text-white'>
            <h3 class='mb-0'>إعداد جدول الاستطلاعات</h3>
        </div>
        <div class='card-body'>";

try {
    // قراءة ملف SQL
    $sql = file_get_contents('../database/add_polls_table.sql');
    
    // تقسيم الاستعلامات
    $queries = explode(';', $sql);
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            $db->query($query);
            echo "<div class='alert alert-success'>✓ تم تنفيذ: " . substr($query, 0, 50) . "...</div>";
        }
    }
    
    echo "<div class='alert alert-success'><strong>تم إنشاء جدول الاستطلاعات بنجاح! 🎉</strong></div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>خطأ: " . $e->getMessage() . "</div>";
}

echo "<div class='mt-4'>
        <a href='polls.php' class='btn btn-primary'>إدارة الاستطلاعات</a>
        <a href='index.php' class='btn btn-secondary'>العودة للوحة التحكم</a>
      </div>";

echo "</div></div></div></body></html>";
?>

<?php
/**
 * ملف التكوين التلقائي - يختار التكوين المناسب حسب البيئة
 */

// تحديد البيئة
if ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1') {
    // البيئة المحلية
    require_once __DIR__ . '/config.php';
} else {
    // الاستضافة المدفوعة
    require_once __DIR__ . '/config_production.php';
}
?>
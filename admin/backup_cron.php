<?php
/**
 * سكريبت النسخ الاحتياطية التلقائية
 * يستخدم مع cron jobs للنسخ المجدولة
 */

// منع الوصول المباشر من المتصفح
if (php_sapi_name() !== 'cli' && !defined('ALLOW_CRON_WEB_ACCESS')) {
    die('هذا السكريبت مخصص للتشغيل من سطر الأوامر فقط');
}

// تعيين مهلة زمنية أطول للنسخ الاحتياطية
set_time_limit(0);
ini_set('memory_limit', '512M');

// تضمين الملفات المطلوبة
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/backup_system.php';
require_once __DIR__ . '/../includes/error_handler.php';

/**
 * تسجيل رسالة مع الوقت
 */
function logMessage($message, $type = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$type] $message" . PHP_EOL;
    
    // طباعة في سطر الأوامر
    echo $logMessage;
    
    // تسجيل في ملف السجل
    $logFile = __DIR__ . '/../logs/backup_cron.log';
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

/**
 * تنفيذ النسخة الاحتياطية
 */
function runBackup() {
    try {
        logMessage('بدء عملية النسخ الاحتياطي التلقائي');
        
        // إنشاء كائن النسخ الاحتياطي
        global $pdo;
        $backupSystem = new BackupSystem($pdo);
        
        // تحديد نوع النسخة بناءً على اليوم
        $dayOfWeek = date('w'); // 0 = الأحد
        
        if ($dayOfWeek == 0) {
            // نسخة كاملة يوم الأحد
            logMessage('إنشاء نسخة احتياطية كاملة أسبوعية');
            $result = $backupSystem->createFullBackup('نسخة تلقائية كاملة - أسبوعية');
        } else {
            // نسخة تدريجية باقي الأيام
            logMessage('إنشاء نسخة احتياطية تدريجية يومية');
            $result = $backupSystem->createIncrementalBackup('نسخة تلقائية تدريجية - يومية');
        }
        
        if ($result['success']) {
            $sizeFormatted = formatFileSize($result['size']);
            logMessage("تم إنشاء النسخة الاحتياطية بنجاح: {$result['filename']} ({$sizeFormatted})", 'SUCCESS');
            
            // إرسال تقرير بالبريد الإلكتروني (اختياري)
            if (defined('BACKUP_EMAIL_REPORTS') && BACKUP_EMAIL_REPORTS && defined('ADMIN_EMAIL')) {
                sendBackupReport($result, true);
            }
            
        } else {
            logMessage('فشل في إنشاء النسخة الاحتياطية', 'ERROR');
        }
        
    } catch (Exception $e) {
        logMessage('خطأ في النسخ الاحتياطي: ' . $e->getMessage(), 'ERROR');
        
        // إرسال تنبيه بالخطأ
        if (defined('ADMIN_EMAIL')) {
            sendErrorAlert($e->getMessage());
        }
        
        return false;
    }
    
    return true;
}

/**
 * تنظيف النسخ القديمة
 */
function cleanupOldBackups() {
    try {
        logMessage('بدء تنظيف النسخ الاحتياطية القديمة');
        
        global $pdo;
        $backupSystem = new BackupSystem($pdo);
        
        // الحصول على قائمة النسخ
        $backups = $backupSystem->getBackupsList();
        $maxBackups = defined('MAX_BACKUPS') ? MAX_BACKUPS : 10;
        
        if (count($backups) > $maxBackups) {
            $backupsToDelete = array_slice($backups, $maxBackups);
            
            foreach ($backupsToDelete as $backup) {
                $backupSystem->deleteBackup($backup['filename']);
                logMessage("تم حذف النسخة القديمة: {$backup['filename']}");
            }
            
            logMessage('تم تنظيف النسخ القديمة بنجاح');
        } else {
            logMessage('لا توجد نسخ قديمة للحذف');
        }
        
    } catch (Exception $e) {
        logMessage('خطأ في تنظيف النسخ القديمة: ' . $e->getMessage(), 'ERROR');
    }
}

/**
 * فحص مساحة القرص
 */
function checkDiskSpace() {
    $backupDir = defined('BACKUP_PATH') ? BACKUP_PATH : __DIR__ . '/../backups/';
    $freeBytes = disk_free_space($backupDir);
    $totalBytes = disk_total_space($backupDir);
    
    if ($freeBytes && $totalBytes) {
        $freeGB = round($freeBytes / (1024 * 1024 * 1024), 2);
        $usedPercent = round((($totalBytes - $freeBytes) / $totalBytes) * 100, 2);
        
        logMessage("مساحة القرص المتاحة: {$freeGB} GB (مستخدم: {$usedPercent}%)");
        
        // تحذير إذا كانت المساحة المتاحة أقل من 1 GB
        if ($freeGB < 1) {
            logMessage('تحذير: مساحة القرص المتاحة أقل من 1 GB', 'WARNING');
            
            if (defined('ADMIN_EMAIL')) {
                sendDiskSpaceAlert($freeGB, $usedPercent);
            }
        }
    }
}

/**
 * إرسال تقرير النسخ الاحتياطي
 */
function sendBackupReport($result, $success) {
    if (!defined('ADMIN_EMAIL') || !function_exists('mail')) {
        return;
    }
    
    $subject = $success ? 'تقرير النسخ الاحتياطي - نجح' : 'تقرير النسخ الاحتياطي - فشل';
    
    $body = "تقرير النسخ الاحتياطي التلقائي\n\n";
    $body .= "التاريخ والوقت: " . date('Y-m-d H:i:s') . "\n";
    $body .= "الحالة: " . ($success ? 'نجح' : 'فشل') . "\n";
    
    if ($success && isset($result['filename'])) {
        $body .= "اسم الملف: {$result['filename']}\n";
        $body .= "حجم الملف: " . formatFileSize($result['size']) . "\n";
    }
    
    $body .= "\nهذا تقرير تلقائي من نظام النسخ الاحتياطي.";
    
    $headers = "From: backup@" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    @mail(ADMIN_EMAIL, $subject, $body, $headers);
}

/**
 * إرسال تنبيه خطأ
 */
function sendErrorAlert($errorMessage) {
    if (!defined('ADMIN_EMAIL') || !function_exists('mail')) {
        return;
    }
    
    $subject = 'خطأ في النسخ الاحتياطي التلقائي';
    
    $body = "حدث خطأ في النسخ الاحتياطي التلقائي:\n\n";
    $body .= "التاريخ والوقت: " . date('Y-m-d H:i:s') . "\n";
    $body .= "رسالة الخطأ: $errorMessage\n";
    $body .= "\nيرجى التحقق من النظام وإصلاح المشكلة.";
    
    $headers = "From: backup@" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    @mail(ADMIN_EMAIL, $subject, $body, $headers);
}

/**
 * إرسال تنبيه مساحة القرص
 */
function sendDiskSpaceAlert($freeGB, $usedPercent) {
    if (!defined('ADMIN_EMAIL') || !function_exists('mail')) {
        return;
    }
    
    $subject = 'تحذير: مساحة القرص منخفضة';
    
    $body = "تحذير من نظام النسخ الاحتياطي:\n\n";
    $body .= "مساحة القرص المتاحة منخفضة:\n";
    $body .= "المساحة المتاحة: {$freeGB} GB\n";
    $body .= "المساحة المستخدمة: {$usedPercent}%\n";
    $body .= "\nيرجى تحرير مساحة إضافية أو حذف النسخ الاحتياطية القديمة.";
    
    $headers = "From: backup@" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    @mail(ADMIN_EMAIL, $subject, $body, $headers);
}

/**
 * تنسيق حجم الملف
 */
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * تشغيل السكريبت الرئيسي
 */
function main() {
    logMessage('=== بدء تشغيل سكريبت النسخ الاحتياطي التلقائي ===');
    
    // فحص مساحة القرص
    checkDiskSpace();
    
    // تنفيذ النسخة الاحتياطية
    $success = runBackup();
    
    // تنظيف النسخ القديمة
    if ($success) {
        cleanupOldBackups();
    }
    
    logMessage('=== انتهاء تشغيل سكريبت النسخ الاحتياطي التلقائي ===');
    
    return $success;
}

// تشغيل السكريبت
if (php_sapi_name() === 'cli' || defined('ALLOW_CRON_WEB_ACCESS')) {
    $success = main();
    exit($success ? 0 : 1);
}

?>
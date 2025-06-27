<?php
/**
 * نظام النسخ الاحتياطي التلقائي لقاعدة البيانات
 * يوفر إنشاء واستعادة وإدارة النسخ الاحتياطية
 */

class BackupSystem {
    private $pdo;
    private $backupDir;
    private $maxBackups;
    private $dbHost;
    private $dbName;
    private $dbUser;
    private $dbPass;
    
    public function __construct($pdo, $backupDir = null) {
        $this->pdo = $pdo;
        $this->backupDir = $backupDir ?: (defined('BACKUP_PATH') ? BACKUP_PATH : __DIR__ . '/../backups/');
        $this->maxBackups = defined('MAX_BACKUPS') ? MAX_BACKUPS : 10;
        
        // الحصول على معلومات قاعدة البيانات من الاتصال
        $this->dbHost = defined('DB_HOST') ? DB_HOST : 'localhost';
        $this->dbName = defined('DB_NAME') ? DB_NAME : '';
        $this->dbUser = defined('DB_USER') ? DB_USER : '';
        $this->dbPass = defined('DB_PASS') ? DB_PASS : '';
        
        // إنشاء مجلد النسخ الاحتياطية إذا لم يكن موجوداً
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
        
        // إنشاء ملف .htaccess لحماية المجلد
        $htaccessFile = $this->backupDir . '.htaccess';
        if (!file_exists($htaccessFile)) {
            file_put_contents($htaccessFile, "Deny from all\n");
        }
    }
    
    /**
     * إنشاء نسخة احتياطية كاملة
     */
    public function createFullBackup($description = '') {
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $filename = "backup_full_{$timestamp}.sql";
            $filepath = $this->backupDir . $filename;
            
            // إنشاء النسخة الاحتياطية
            $this->createSQLDump($filepath);
            
            // ضغط الملف
            $compressedFile = $this->compressBackup($filepath);
            
            // حذف الملف غير المضغوط
            if ($compressedFile && file_exists($compressedFile)) {
                unlink($filepath);
                $filepath = $compressedFile;
                $filename = basename($compressedFile);
            }
            
            // تسجيل النسخة الاحتياطية في قاعدة البيانات
            $this->logBackup($filename, 'full', $description, filesize($filepath));
            
            // تنظيف النسخ القديمة
            $this->cleanOldBackups();
            
            log_info('تم إنشاء نسخة احتياطية كاملة: ' . $filename);
            
            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath,
                'size' => filesize($filepath)
            ];
            
        } catch (Exception $e) {
            log_error('فشل في إنشاء النسخة الاحتياطية: ' . $e->getMessage());
            throw new Exception('فشل في إنشاء النسخة الاحتياطية: ' . $e->getMessage());
        }
    }
    
    /**
     * إنشاء نسخة احتياطية تدريجية (البيانات المتغيرة فقط)
     */
    public function createIncrementalBackup($description = '') {
        try {
            $lastBackup = $this->getLastBackupTime();
            
            if (!$lastBackup) {
                // إذا لم توجد نسخة سابقة، إنشاء نسخة كاملة
                return $this->createFullBackup('نسخة كاملة أولى');
            }
            
            $timestamp = date('Y-m-d_H-i-s');
            $filename = "backup_incremental_{$timestamp}.sql";
            $filepath = $this->backupDir . $filename;
            
            // إنشاء النسخة التدريجية
            $this->createIncrementalSQLDump($filepath, $lastBackup);
            
            // ضغط الملف
            $compressedFile = $this->compressBackup($filepath);
            
            if ($compressedFile && file_exists($compressedFile)) {
                unlink($filepath);
                $filepath = $compressedFile;
                $filename = basename($compressedFile);
            }
            
            // تسجيل النسخة الاحتياطية
            $this->logBackup($filename, 'incremental', $description, filesize($filepath));
            
            log_info('تم إنشاء نسخة احتياطية تدريجية: ' . $filename);
            
            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath,
                'size' => filesize($filepath)
            ];
            
        } catch (Exception $e) {
            log_error('فشل في إنشاء النسخة الاحتياطية التدريجية: ' . $e->getMessage());
            throw new Exception('فشل في إنشاء النسخة الاحتياطية التدريجية: ' . $e->getMessage());
        }
    }
    
    /**
     * استعادة نسخة احتياطية
     */
    public function restoreBackup($filename) {
        try {
            $filepath = $this->backupDir . $filename;
            
            if (!file_exists($filepath)) {
                throw new Exception('ملف النسخة الاحتياطية غير موجود');
            }
            
            // إلغاء ضغط الملف إذا كان مضغوطاً
            $sqlFile = $this->decompressBackup($filepath);
            
            if (!$sqlFile) {
                $sqlFile = $filepath;
            }
            
            // قراءة وتنفيذ ملف SQL
            $sql = file_get_contents($sqlFile);
            
            if ($sql === false) {
                throw new Exception('فشل في قراءة ملف النسخة الاحتياطية');
            }
            
            // تقسيم الاستعلامات وتنفيذها
            $statements = $this->splitSQLStatements($sql);
            
            $this->pdo->beginTransaction();
            
            foreach ($statements as $statement) {
                if (trim($statement)) {
                    $this->pdo->exec($statement);
                }
            }
            
            $this->pdo->commit();
            
            // حذف الملف المؤقت إذا كان مختلفاً عن الأصلي
            if ($sqlFile !== $filepath && file_exists($sqlFile)) {
                unlink($sqlFile);
            }
            
            log_info('تم استعادة النسخة الاحتياطية: ' . $filename);
            
            return ['success' => true, 'message' => 'تم استعادة النسخة الاحتياطية بنجاح'];
            
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            
            log_error('فشل في استعادة النسخة الاحتياطية: ' . $e->getMessage());
            throw new Exception('فشل في استعادة النسخة الاحتياطية: ' . $e->getMessage());
        }
    }
    
    /**
     * الحصول على قائمة النسخ الاحتياطية
     */
    public function getBackupsList() {
        try {
            $stmt = $this->pdo->query("
                SELECT * FROM backup_log 
                ORDER BY created_at DESC
            ");
            
            $backups = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // إضافة معلومات إضافية لكل نسخة
            foreach ($backups as &$backup) {
                $filepath = $this->backupDir . $backup['filename'];
                $backup['exists'] = file_exists($filepath);
                $backup['size_formatted'] = $this->formatFileSize($backup['file_size']);
                $backup['age'] = $this->getTimeAgo($backup['created_at']);
            }
            
            return $backups;
            
        } catch (Exception $e) {
            log_error('فشل في الحصول على قائمة النسخ الاحتياطية: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * حذف نسخة احتياطية
     */
    public function deleteBackup($filename) {
        try {
            $filepath = $this->backupDir . $filename;
            
            // حذف الملف
            if (file_exists($filepath)) {
                unlink($filepath);
            }
            
            // حذف السجل من قاعدة البيانات
            $stmt = $this->pdo->prepare("DELETE FROM backup_log WHERE filename = ?");
            $stmt->execute([$filename]);
            
            log_info('تم حذف النسخة الاحتياطية: ' . $filename);
            
            return ['success' => true, 'message' => 'تم حذف النسخة الاحتياطية بنجاح'];
            
        } catch (Exception $e) {
            log_error('فشل في حذف النسخة الاحتياطية: ' . $e->getMessage());
            throw new Exception('فشل في حذف النسخة الاحتياطية: ' . $e->getMessage());
        }
    }
    
    /**
     * جدولة النسخ الاحتياطية التلقائية
     */
    public function scheduleAutoBackup() {
        try {
            // التحقق من آخر نسخة احتياطية
            $lastBackup = $this->getLastBackupTime();
            $now = time();
            
            // إعدادات الجدولة (يمكن تخصيصها)
            $fullBackupInterval = 7 * 24 * 3600; // أسبوع
            $incrementalBackupInterval = 24 * 3600; // يوم
            
            if (!$lastBackup || ($now - strtotime($lastBackup)) > $fullBackupInterval) {
                // إنشاء نسخة كاملة
                return $this->createFullBackup('نسخة تلقائية كاملة');
            } elseif (($now - strtotime($lastBackup)) > $incrementalBackupInterval) {
                // إنشاء نسخة تدريجية
                return $this->createIncrementalBackup('نسخة تلقائية تدريجية');
            }
            
            return ['success' => true, 'message' => 'لا حاجة لنسخة احتياطية في الوقت الحالي'];
            
        } catch (Exception $e) {
            log_error('فشل في الجدولة التلقائية للنسخ الاحتياطية: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * إنشاء ملف SQL dump
     */
    private function createSQLDump($filepath) {
        $output = "-- نسخة احتياطية لقاعدة البيانات\n";
        $output .= "-- تاريخ الإنشاء: " . date('Y-m-d H:i:s') . "\n";
        $output .= "-- قاعدة البيانات: {$this->dbName}\n\n";
        
        $output .= "SET FOREIGN_KEY_CHECKS=0;\n";
        $output .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
        $output .= "SET AUTOCOMMIT = 0;\n";
        $output .= "START TRANSACTION;\n\n";
        
        // الحصول على قائمة الجداول
        $tables = $this->pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($tables as $table) {
            $output .= $this->dumpTable($table);
        }
        
        $output .= "\nSET FOREIGN_KEY_CHECKS=1;\n";
        $output .= "COMMIT;\n";
        
        file_put_contents($filepath, $output);
    }
    
    /**
     * إنشاء نسخة تدريجية
     */
    private function createIncrementalSQLDump($filepath, $lastBackupTime) {
        $output = "-- نسخة احتياطية تدريجية\n";
        $output .= "-- تاريخ الإنشاء: " . date('Y-m-d H:i:s') . "\n";
        $output .= "-- آخر نسخة: {$lastBackupTime}\n\n";
        
        // جداول تحتوي على تواريخ تحديث
        $tablesWithTimestamp = ['blocks', 'users', 'comments', 'pages'];
        
        foreach ($tablesWithTimestamp as $table) {
            if ($this->tableExists($table)) {
                $output .= $this->dumpTableIncremental($table, $lastBackupTime);
            }
        }
        
        file_put_contents($filepath, $output);
    }
    
    /**
     * تفريغ جدول كامل
     */
    private function dumpTable($table) {
        $output = "\n-- \n-- بنية الجدول `{$table}`\n-- \n\n";
        
        // إنشاء الجدول
        $createTable = $this->pdo->query("SHOW CREATE TABLE `{$table}`")->fetch();
        $output .= "DROP TABLE IF EXISTS `{$table}`;\n";
        $output .= $createTable['Create Table'] . ";\n\n";
        
        // البيانات
        $output .= "-- \n-- تفريغ بيانات الجدول `{$table}`\n-- \n\n";
        
        $stmt = $this->pdo->query("SELECT * FROM `{$table}`");
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $output .= $this->generateInsertStatement($table, $row);
        }
        
        return $output;
    }
    
    /**
     * تفريغ جدول تدريجي
     */
    private function dumpTableIncremental($table, $lastBackupTime) {
        $output = "\n-- تحديثات الجدول `{$table}` منذ {$lastBackupTime}\n\n";
        
        $timestampColumn = $this->getTimestampColumn($table);
        
        if ($timestampColumn) {
            $stmt = $this->pdo->prepare("SELECT * FROM `{$table}` WHERE `{$timestampColumn}` > ?");
            $stmt->execute([$lastBackupTime]);
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $output .= $this->generateReplaceStatement($table, $row);
            }
        }
        
        return $output;
    }
    
    /**
     * إنشاء جملة INSERT
     */
    private function generateInsertStatement($table, $row) {
        $columns = array_keys($row);
        $values = array_values($row);
        
        $escapedColumns = array_map(function($col) { return "`{$col}`"; }, $columns);
        $escapedValues = array_map(function($val) {
            return $val === null ? 'NULL' : $this->pdo->quote($val);
        }, $values);
        
        return "INSERT INTO `{$table}` (" . implode(', ', $escapedColumns) . ") VALUES (" . implode(', ', $escapedValues) . ");\n";
    }
    
    /**
     * إنشاء جملة REPLACE
     */
    private function generateReplaceStatement($table, $row) {
        $columns = array_keys($row);
        $values = array_values($row);
        
        $escapedColumns = array_map(function($col) { return "`{$col}`"; }, $columns);
        $escapedValues = array_map(function($val) {
            return $val === null ? 'NULL' : $this->pdo->quote($val);
        }, $values);
        
        return "REPLACE INTO `{$table}` (" . implode(', ', $escapedColumns) . ") VALUES (" . implode(', ', $escapedValues) . ");\n";
    }
    
    /**
     * ضغط ملف النسخة الاحتياطية
     */
    private function compressBackup($filepath) {
        if (!extension_loaded('zlib')) {
            return false;
        }
        
        $compressedFile = $filepath . '.gz';
        
        $file = fopen($filepath, 'rb');
        $compressed = gzopen($compressedFile, 'wb9');
        
        if ($file && $compressed) {
            while (!feof($file)) {
                gzwrite($compressed, fread($file, 8192));
            }
            
            fclose($file);
            gzclose($compressed);
            
            return $compressedFile;
        }
        
        return false;
    }
    
    /**
     * إلغاء ضغط ملف النسخة الاحتياطية
     */
    private function decompressBackup($filepath) {
        if (pathinfo($filepath, PATHINFO_EXTENSION) !== 'gz') {
            return false;
        }
        
        $decompressedFile = substr($filepath, 0, -3);
        
        $compressed = gzopen($filepath, 'rb');
        $file = fopen($decompressedFile, 'wb');
        
        if ($compressed && $file) {
            while (!gzeof($compressed)) {
                fwrite($file, gzread($compressed, 8192));
            }
            
            gzclose($compressed);
            fclose($file);
            
            return $decompressedFile;
        }
        
        return false;
    }
    
    /**
     * تقسيم جمل SQL
     */
    private function splitSQLStatements($sql) {
        return array_filter(
            array_map('trim', explode(';', $sql)),
            function($statement) {
                return !empty($statement) && !preg_match('/^\s*--/', $statement);
            }
        );
    }
    
    /**
     * تسجيل النسخة الاحتياطية
     */
    private function logBackup($filename, $type, $description, $fileSize) {
        $stmt = $this->pdo->prepare("
            INSERT INTO backup_log (filename, backup_type, description, file_size, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([$filename, $type, $description, $fileSize]);
    }
    
    /**
     * الحصول على وقت آخر نسخة احتياطية
     */
    private function getLastBackupTime() {
        $stmt = $this->pdo->query("SELECT MAX(created_at) FROM backup_log");
        return $stmt->fetchColumn();
    }
    
    /**
     * تنظيف النسخ القديمة
     */
    private function cleanOldBackups() {
        $stmt = $this->pdo->prepare("
            SELECT filename FROM backup_log 
            ORDER BY created_at DESC 
            LIMIT ?, 1000
        ");
        
        $stmt->execute([$this->maxBackups]);
        $oldBackups = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($oldBackups as $filename) {
            $this->deleteBackup($filename);
        }
    }
    
    /**
     * التحقق من وجود جدول
     */
    private function tableExists($table) {
        $stmt = $this->pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * الحصول على عمود التاريخ في الجدول
     */
    private function getTimestampColumn($table) {
        $columns = ['updated_at', 'created_at', 'modified_at', 'timestamp'];
        
        foreach ($columns as $column) {
            $stmt = $this->pdo->prepare("SHOW COLUMNS FROM `{$table}` LIKE ?");
            $stmt->execute([$column]);
            
            if ($stmt->rowCount() > 0) {
                return $column;
            }
        }
        
        return null;
    }
    
    /**
     * تنسيق حجم الملف
     */
    private function formatFileSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * حساب الوقت المنقضي
     */
    private function getTimeAgo($datetime) {
        $time = time() - strtotime($datetime);
        
        if ($time < 60) return 'منذ لحظات';
        if ($time < 3600) return 'منذ ' . floor($time/60) . ' دقيقة';
        if ($time < 86400) return 'منذ ' . floor($time/3600) . ' ساعة';
        if ($time < 2592000) return 'منذ ' . floor($time/86400) . ' يوم';
        if ($time < 31536000) return 'منذ ' . floor($time/2592000) . ' شهر';
        
        return 'منذ ' . floor($time/31536000) . ' سنة';
    }
}

// إنشاء جدول سجل النسخ الاحتياطية إذا لم يكن موجوداً
function createBackupLogTable($pdo) {
    $sql = "
        CREATE TABLE IF NOT EXISTS backup_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            filename VARCHAR(255) NOT NULL,
            backup_type ENUM('full', 'incremental') NOT NULL,
            description TEXT,
            file_size BIGINT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_created_at (created_at),
            INDEX idx_backup_type (backup_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $pdo->exec($sql);
}

// تفعيل النظام
if (isset($pdo)) {
    createBackupLogTable($pdo);
    $GLOBALS['backupSystem'] = new BackupSystem($pdo);
}

/**
 * دوال مساعدة
 */
function create_backup($description = '') {
    return $GLOBALS['backupSystem']->createFullBackup($description);
}

function create_incremental_backup($description = '') {
    return $GLOBALS['backupSystem']->createIncrementalBackup($description);
}

function restore_backup($filename) {
    return $GLOBALS['backupSystem']->restoreBackup($filename);
}

function get_backups_list() {
    return $GLOBALS['backupSystem']->getBackupsList();
}

function schedule_auto_backup() {
    return $GLOBALS['backupSystem']->scheduleAutoBackup();
}

?>
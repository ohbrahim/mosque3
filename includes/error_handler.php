<?php
/**
 * نظام إدارة الأخطاء المحسن
 * يوفر تسجيل مفصل للأخطاء وإدارة محسنة للاستثناءات
 */

class ErrorHandler {
    private $logDir;
    private $maxLogSize;
    private $maxLogFiles;
    
    public function __construct($logDir = null) {
        $this->logDir = $logDir ?: (defined('LOG_PATH') ? LOG_PATH : __DIR__ . '/../logs/');
        $this->maxLogSize = 10 * 1024 * 1024; // 10MB
        $this->maxLogFiles = 5;
        
        // إنشاء مجلد السجلات إذا لم يكن موجوداً
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
        
        // تسجيل معالجات الأخطاء
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleFatalError']);
    }
    
    /**
     * معالج الأخطاء العادية
     */
    public function handleError($severity, $message, $file, $line) {
        // تجاهل الأخطاء المكبوتة بـ @
        if (!(error_reporting() & $severity)) {
            return false;
        }
        
        $errorType = $this->getErrorType($severity);
        $this->logError($errorType, $message, $file, $line);
        
        // إظهار الخطأ في بيئة التطوير فقط
        if (defined('DEBUG') && DEBUG) {
            echo "<div style='background: #ffebee; border: 1px solid #f44336; padding: 10px; margin: 10px; border-radius: 4px;'>";
            echo "<strong>$errorType:</strong> $message<br>";
            echo "<small>في الملف: $file على السطر: $line</small>";
            echo "</div>";
        }
        
        return true;
    }
    
    /**
     * معالج الاستثناءات
     */
    public function handleException($exception) {
        $this->logError(
            'EXCEPTION',
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );
        
        if (defined('DEBUG') && DEBUG) {
            echo "<div style='background: #ffebee; border: 1px solid #f44336; padding: 10px; margin: 10px; border-radius: 4px;'>";
            echo "<strong>استثناء غير معالج:</strong> " . $exception->getMessage() . "<br>";
            echo "<small>في الملف: " . $exception->getFile() . " على السطر: " . $exception->getLine() . "</small>";
            echo "<details><summary>تفاصيل التتبع</summary><pre>" . $exception->getTraceAsString() . "</pre></details>";
            echo "</div>";
        } else {
            echo "<div style='text-align: center; padding: 50px;'>";
            echo "<h2>عذراً، حدث خطأ غير متوقع</h2>";
            echo "<p>يرجى المحاولة مرة أخرى أو الاتصال بالدعم الفني</p>";
            echo "</div>";
        }
    }
    
    /**
     * معالج الأخطاء الفادحة
     */
    public function handleFatalError() {
        $error = error_get_last();
        
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $this->logError(
                'FATAL ERROR',
                $error['message'],
                $error['file'],
                $error['line']
            );
            
            if (!defined('DEBUG') || !DEBUG) {
                // إعادة توجيه لصفحة خطأ مخصصة
                if (!headers_sent()) {
                    header('HTTP/1.1 500 Internal Server Error');
                    header('Location: /error.php');
                }
            }
        }
    }
    
    /**
     * تسجيل الخطأ في ملف السجل
     */
    public function logError($type, $message, $file = '', $line = '', $trace = '') {
        $timestamp = date('Y-m-d H:i:s');
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $ip = $this->getClientIP();
        $url = $_SERVER['REQUEST_URI'] ?? 'Unknown';
        $userId = $_SESSION['user_id'] ?? 'Guest';
        
        $logEntry = "[$timestamp] $type: $message\n";
        $logEntry .= "File: $file\n";
        $logEntry .= "Line: $line\n";
        $logEntry .= "URL: $url\n";
        $logEntry .= "IP: $ip\n";
        $logEntry .= "User: $userId\n";
        $logEntry .= "User Agent: $userAgent\n";
        
        if ($trace) {
            $logEntry .= "Stack Trace:\n$trace\n";
        }
        
        $logEntry .= str_repeat('-', 80) . "\n";
        
        $logFile = $this->logDir . 'error_' . date('Y-m-d') . '.log';
        
        // تدوير ملفات السجل إذا كانت كبيرة
        $this->rotateLogFile($logFile);
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // إرسال تنبيه للمدير في حالة الأخطاء الحرجة
        if (in_array($type, ['FATAL ERROR', 'EXCEPTION']) && defined('ADMIN_EMAIL')) {
            $this->sendErrorAlert($type, $message, $file, $line);
        }
    }
    
    /**
     * تسجيل خطأ مخصص
     */
    public function logCustomError($message, $level = 'INFO', $context = []) {
        $contextStr = !empty($context) ? json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $this->logError($level, $message, '', '', $contextStr);
    }
    
    /**
     * الحصول على نوع الخطأ
     */
    private function getErrorType($severity) {
        switch ($severity) {
            case E_ERROR:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_USER_ERROR:
                return 'ERROR';
            case E_WARNING:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
            case E_USER_WARNING:
                return 'WARNING';
            case E_NOTICE:
            case E_USER_NOTICE:
                return 'NOTICE';
            case E_STRICT:
                return 'STRICT';
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
                return 'DEPRECATED';
            default:
                return 'UNKNOWN';
        }
    }
    
    /**
     * الحصول على عنوان IP الحقيقي للعميل
     */
    private function getClientIP() {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    }
    
    /**
     * تدوير ملفات السجل
     */
    private function rotateLogFile($logFile) {
        if (!file_exists($logFile) || filesize($logFile) < $this->maxLogSize) {
            return;
        }
        
        // نقل الملفات القديمة
        for ($i = $this->maxLogFiles - 1; $i > 0; $i--) {
            $oldFile = $logFile . '.' . $i;
            $newFile = $logFile . '.' . ($i + 1);
            
            if (file_exists($oldFile)) {
                if ($i == $this->maxLogFiles - 1) {
                    unlink($oldFile);
                } else {
                    rename($oldFile, $newFile);
                }
            }
        }
        
        // نقل الملف الحالي
        rename($logFile, $logFile . '.1');
    }
    
    /**
     * إرسال تنبيه بالبريد الإلكتروني للمدير
     */
    private function sendErrorAlert($type, $message, $file, $line) {
        if (!defined('ADMIN_EMAIL') || !function_exists('mail')) {
            return;
        }
        
        $subject = "تنبيه خطأ في الموقع - $type";
        $body = "حدث خطأ في الموقع:\n\n";
        $body .= "النوع: $type\n";
        $body .= "الرسالة: $message\n";
        $body .= "الملف: $file\n";
        $body .= "السطر: $line\n";
        $body .= "الوقت: " . date('Y-m-d H:i:s') . "\n";
        $body .= "الرابط: " . ($_SERVER['REQUEST_URI'] ?? 'Unknown') . "\n";
        
        $headers = "From: noreply@" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        @mail(ADMIN_EMAIL, $subject, $body, $headers);
    }
    
    /**
     * الحصول على إحصائيات الأخطاء
     */
    public function getErrorStats($days = 7) {
        $stats = [];
        
        for ($i = 0; $i < $days; $i++) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $logFile = $this->logDir . "error_$date.log";
            
            if (file_exists($logFile)) {
                $content = file_get_contents($logFile);
                $errorCount = substr_count($content, 'ERROR:');
                $warningCount = substr_count($content, 'WARNING:');
                $noticeCount = substr_count($content, 'NOTICE:');
                
                $stats[$date] = [
                    'errors' => $errorCount,
                    'warnings' => $warningCount,
                    'notices' => $noticeCount,
                    'total' => $errorCount + $warningCount + $noticeCount
                ];
            } else {
                $stats[$date] = [
                    'errors' => 0,
                    'warnings' => 0,
                    'notices' => 0,
                    'total' => 0
                ];
            }
        }
        
        return $stats;
    }
}

// تفعيل معالج الأخطاء
if (!isset($GLOBALS['errorHandler'])) {
    $GLOBALS['errorHandler'] = new ErrorHandler();
}

/**
 * دوال مساعدة لتسجيل الأخطاء
 */
function log_error($message, $context = []) {
    $GLOBALS['errorHandler']->logCustomError($message, 'ERROR', $context);
}

function log_warning($message, $context = []) {
    $GLOBALS['errorHandler']->logCustomError($message, 'WARNING', $context);
}

function log_info($message, $context = []) {
    $GLOBALS['errorHandler']->logCustomError($message, 'INFO', $context);
}

function log_debug($message, $context = []) {
    if (defined('DEBUG') && DEBUG) {
        $GLOBALS['errorHandler']->logCustomError($message, 'DEBUG', $context);
    }
}

?>
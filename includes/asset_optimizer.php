<?php
/**
 * نظام تحسين وضغط ملفات CSS و JavaScript
 * Asset Optimization and Compression System
 */

class AssetOptimizer {
    private $cacheDir;
    private $cssDir;
    private $jsDir;
    private $enableMinification;
    private $enableGzip;
    private $cacheExpiry;
    
    public function __construct($options = []) {
        $this->cacheDir = $options['cache_dir'] ?? __DIR__ . '/../cache/assets/';
        $this->cssDir = $options['css_dir'] ?? __DIR__ . '/../assets/css/';
        $this->jsDir = $options['js_dir'] ?? __DIR__ . '/../assets/js/';
        $this->enableMinification = $options['enable_minification'] ?? true;
        $this->enableGzip = $options['enable_gzip'] ?? true;
        $this->cacheExpiry = $options['cache_expiry'] ?? 3600; // ساعة واحدة
        
        $this->createDirectories();
    }
    
    /**
     * إنشاء المجلدات المطلوبة
     */
    private function createDirectories() {
        $dirs = [$this->cacheDir, $this->cssDir, $this->jsDir];
        
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
    
    /**
     * ضغط وتحسين ملفات CSS
     */
    public function optimizeCSS($files, $outputName = 'combined.css') {
        if (empty($files)) {
            return false;
        }
        
        $cacheKey = md5(serialize($files) . $outputName);
        $cacheFile = $this->cacheDir . $cacheKey . '.css';
        $gzipFile = $cacheFile . '.gz';
        
        // التحقق من وجود النسخة المحفوظة
        if ($this->isCacheValid($cacheFile, $files)) {
            return $this->getCacheUrl($cacheKey . '.css');
        }
        
        $combinedCSS = '';
        
        foreach ($files as $file) {
            $filePath = $this->cssDir . $file;
            
            if (file_exists($filePath)) {
                $css = file_get_contents($filePath);
                
                // معالجة المسارات النسبية
                $css = $this->fixCSSPaths($css, dirname($file));
                
                // إضافة تعليق بالملف المصدر
                $combinedCSS .= "/* Source: $file */\n";
                $combinedCSS .= $css . "\n\n";
            }
        }
        
        // ضغط CSS إذا كان مفعلاً
        if ($this->enableMinification) {
            $combinedCSS = $this->minifyCSS($combinedCSS);
        }
        
        // حفظ الملف المدمج
        file_put_contents($cacheFile, $combinedCSS);
        
        // إنشاء نسخة مضغوطة بـ Gzip
        if ($this->enableGzip && function_exists('gzencode')) {
            file_put_contents($gzipFile, gzencode($combinedCSS, 9));
        }
        
        return $this->getCacheUrl($cacheKey . '.css');
    }
    
    /**
     * ضغط وتحسين ملفات JavaScript
     */
    public function optimizeJS($files, $outputName = 'combined.js') {
        if (empty($files)) {
            return false;
        }
        
        $cacheKey = md5(serialize($files) . $outputName);
        $cacheFile = $this->cacheDir . $cacheKey . '.js';
        $gzipFile = $cacheFile . '.gz';
        
        // التحقق من وجود النسخة المحفوظة
        if ($this->isCacheValid($cacheFile, $files)) {
            return $this->getCacheUrl($cacheKey . '.js');
        }
        
        $combinedJS = '';
        
        foreach ($files as $file) {
            $filePath = $this->jsDir . $file;
            
            if (file_exists($filePath)) {
                $js = file_get_contents($filePath);
                
                // إضافة تعليق بالملف المصدر
                $combinedJS .= "/* Source: $file */\n";
                $combinedJS .= $js . "\n\n";
            }
        }
        
        // ضغط JavaScript إذا كان مفعلاً
        if ($this->enableMinification) {
            $combinedJS = $this->minifyJS($combinedJS);
        }
        
        // حفظ الملف المدمج
        file_put_contents($cacheFile, $combinedJS);
        
        // إنشاء نسخة مضغوطة بـ Gzip
        if ($this->enableGzip && function_exists('gzencode')) {
            file_put_contents($gzipFile, gzencode($combinedJS, 9));
        }
        
        return $this->getCacheUrl($cacheKey . '.js');
    }
    
    /**
     * ضغط CSS
     */
    private function minifyCSS($css) {
        // إزالة التعليقات
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // إزالة المسافات الزائدة
        $css = str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '    '], '', $css);
        
        // إزالة المسافات حول الرموز
        $css = str_replace(['; ', ' ;', ' {', '{ ', '} ', ' }', ': ', ' :', ', ', ' ,'], [';', ';', '{', '{', '}', '}', ':', ':', ',', ','], $css);
        
        // إزالة آخر فاصلة منقوطة قبل القوس المغلق
        $css = str_replace(';}', '}', $css);
        
        return trim($css);
    }
    
    /**
     * ضغط JavaScript (ضغط بسيط)
     */
    private function minifyJS($js) {
        // إزالة التعليقات أحادية السطر
        $js = preg_replace('/\/\/.*$/m', '', $js);
        
        // إزالة التعليقات متعددة الأسطر
        $js = preg_replace('/\/\*[\s\S]*?\*\//', '', $js);
        
        // إزالة المسافات الزائدة والأسطر الفارغة
        $js = preg_replace('/\s+/', ' ', $js);
        
        // إزالة المسافات حول الرموز
        $js = str_replace([' = ', ' + ', ' - ', ' * ', ' / ', ' == ', ' != ', ' === ', ' !== ', ' && ', ' || '], ['=', '+', '-', '*', '/', '==', '!=', '===', '!==', '&&', '||'], $js);
        
        return trim($js);
    }
    
    /**
     * إصلاح المسارات في CSS
     */
    private function fixCSSPaths($css, $relativePath) {
        // البحث عن url() في CSS
        $css = preg_replace_callback('/url\([\'"]?([^\'")]+)[\'"]?\)/', function($matches) use ($relativePath) {
            $url = $matches[1];
            
            // تجاهل المسارات المطلقة والـ data URLs
            if (strpos($url, 'http') === 0 || strpos($url, 'data:') === 0 || strpos($url, '/') === 0) {
                return $matches[0];
            }
            
            // إصلاح المسار النسبي
            $newPath = $relativePath ? $relativePath . '/' . $url : $url;
            return 'url(' . $newPath . ')';
        }, $css);
        
        return $css;
    }
    
    /**
     * التحقق من صحة الملف المحفوظ
     */
    private function isCacheValid($cacheFile, $sourceFiles) {
        if (!file_exists($cacheFile)) {
            return false;
        }
        
        $cacheTime = filemtime($cacheFile);
        
        // التحقق من انتهاء صلاحية الكاش
        if (time() - $cacheTime > $this->cacheExpiry) {
            return false;
        }
        
        // التحقق من تعديل الملفات المصدر
        foreach ($sourceFiles as $file) {
            $sourceFile = (strpos($file, '.css') !== false ? $this->cssDir : $this->jsDir) . $file;
            
            if (file_exists($sourceFile) && filemtime($sourceFile) > $cacheTime) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * الحصول على رابط الملف المحفوظ
     */
    private function getCacheUrl($filename) {
        $baseUrl = $_SERVER['REQUEST_SCHEME'] ?? 'http';
        $baseUrl .= '://' . $_SERVER['HTTP_HOST'];
        $baseUrl .= dirname($_SERVER['SCRIPT_NAME']);
        
        return rtrim($baseUrl, '/') . '/cache/assets/' . $filename;
    }
    
    /**
     * تنظيف الملفات المحفوظة القديمة
     */
    public function cleanCache($maxAge = 86400) { // 24 ساعة افتراضياً
        $files = glob($this->cacheDir . '*');
        $cleaned = 0;
        
        foreach ($files as $file) {
            if (is_file($file) && (time() - filemtime($file)) > $maxAge) {
                unlink($file);
                $cleaned++;
            }
        }
        
        return $cleaned;
    }
    
    /**
     * الحصول على إحصائيات الكاش
     */
    public function getCacheStats() {
        $files = glob($this->cacheDir . '*');
        $totalSize = 0;
        $fileCount = 0;
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $totalSize += filesize($file);
                $fileCount++;
            }
        }
        
        return [
            'file_count' => $fileCount,
            'total_size' => $totalSize,
            'total_size_formatted' => $this->formatFileSize($totalSize)
        ];
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
     * تقديم الملف مع ضغط Gzip إذا كان متاحاً
     */
    public function serveAsset($filename) {
        $filePath = $this->cacheDir . $filename;
        $gzipPath = $filePath . '.gz';
        
        // التحقق من دعم Gzip
        $acceptGzip = isset($_SERVER['HTTP_ACCEPT_ENCODING']) && 
                     strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false;
        
        if ($acceptGzip && file_exists($gzipPath) && $this->enableGzip) {
            header('Content-Encoding: gzip');
            $filePath = $gzipPath;
        }
        
        if (!file_exists($filePath)) {
            http_response_code(404);
            return false;
        }
        
        // تعيين headers المناسبة
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        
        switch ($extension) {
            case 'css':
                header('Content-Type: text/css; charset=utf-8');
                break;
            case 'js':
                header('Content-Type: application/javascript; charset=utf-8');
                break;
        }
        
        // Cache headers
        $etag = md5_file($filePath);
        header('ETag: "' . $etag . '"');
        header('Cache-Control: public, max-age=' . $this->cacheExpiry);
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $this->cacheExpiry) . ' GMT');
        
        // التحقق من If-None-Match
        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && 
            trim($_SERVER['HTTP_IF_NONE_MATCH'], '"') === $etag) {
            http_response_code(304);
            return true;
        }
        
        // إرسال الملف
        readfile($filePath);
        return true;
    }
}

/**
 * دالة مساعدة لتحسين CSS
 */
function optimizeCSS($files, $outputName = 'combined.css') {
    static $optimizer = null;
    
    if ($optimizer === null) {
        $optimizer = new AssetOptimizer();
    }
    
    return $optimizer->optimizeCSS($files, $outputName);
}

/**
 * دالة مساعدة لتحسين JavaScript
 */
function optimizeJS($files, $outputName = 'combined.js') {
    static $optimizer = null;
    
    if ($optimizer === null) {
        $optimizer = new AssetOptimizer();
    }
    
    return $optimizer->optimizeJS($files, $outputName);
}

/**
 * دالة مساعدة لتنظيف الكاش
 */
function cleanAssetCache($maxAge = 86400) {
    $optimizer = new AssetOptimizer();
    return $optimizer->cleanCache($maxAge);
}

/**
 * دالة مساعدة للحصول على إحصائيات الكاش
 */
function getAssetCacheStats() {
    $optimizer = new AssetOptimizer();
    return $optimizer->getCacheStats();
}

?>
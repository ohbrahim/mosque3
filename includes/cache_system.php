<?php
/**
 * نظام التخزين المؤقت المحسن
 * يحسن أداء الموقع عبر تخزين البيانات مؤقتاً
 */

class CacheSystem {
    private $cacheDir;
    private $defaultDuration;
    
    public function __construct($cacheDir = null, $defaultDuration = 3600) {
        $this->cacheDir = $cacheDir ?: (defined('CACHE_PATH') ? CACHE_PATH : __DIR__ . '/../cache/');
        $this->defaultDuration = $defaultDuration;
        
        // إنشاء مجلد التخزين المؤقت إذا لم يكن موجوداً
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * حفظ البيانات في التخزين المؤقت
     */
    public function set($key, $data, $duration = null) {
        if (!defined('CACHE_ENABLED') || !CACHE_ENABLED) {
            return false;
        }
        
        $duration = $duration ?: $this->defaultDuration;
        $filename = $this->getCacheFilename($key);
        
        $cacheData = [
            'data' => $data,
            'expires' => time() + $duration,
            'created' => time()
        ];
        
        return file_put_contents($filename, serialize($cacheData)) !== false;
    }
    
    /**
     * استرجاع البيانات من التخزين المؤقت
     */
    public function get($key) {
        if (!defined('CACHE_ENABLED') || !CACHE_ENABLED) {
            return null;
        }
        
        $filename = $this->getCacheFilename($key);
        
        if (!file_exists($filename)) {
            return null;
        }
        
        $cacheData = unserialize(file_get_contents($filename));
        
        if (!$cacheData || $cacheData['expires'] < time()) {
            $this->delete($key);
            return null;
        }
        
        return $cacheData['data'];
    }
    
    /**
     * حذف عنصر من التخزين المؤقت
     */
    public function delete($key) {
        $filename = $this->getCacheFilename($key);
        
        if (file_exists($filename)) {
            return unlink($filename);
        }
        
        return true;
    }
    
    /**
     * مسح جميع ملفات التخزين المؤقت
     */
    public function clear() {
        $files = glob($this->cacheDir . '*.cache');
        
        foreach ($files as $file) {
            unlink($file);
        }
        
        return true;
    }
    
    /**
     * مسح الملفات المنتهية الصلاحية
     */
    public function cleanup() {
        $files = glob($this->cacheDir . '*.cache');
        $cleaned = 0;
        
        foreach ($files as $file) {
            $cacheData = unserialize(file_get_contents($file));
            
            if (!$cacheData || $cacheData['expires'] < time()) {
                unlink($file);
                $cleaned++;
            }
        }
        
        return $cleaned;
    }
    
    /**
     * الحصول على إحصائيات التخزين المؤقت
     */
    public function getStats() {
        $files = glob($this->cacheDir . '*.cache');
        $totalSize = 0;
        $validFiles = 0;
        $expiredFiles = 0;
        
        foreach ($files as $file) {
            $totalSize += filesize($file);
            $cacheData = unserialize(file_get_contents($file));
            
            if ($cacheData && $cacheData['expires'] >= time()) {
                $validFiles++;
            } else {
                $expiredFiles++;
            }
        }
        
        return [
            'total_files' => count($files),
            'valid_files' => $validFiles,
            'expired_files' => $expiredFiles,
            'total_size' => $totalSize,
            'cache_dir' => $this->cacheDir
        ];
    }
    
    /**
     * تخزين مؤقت للبلوكات
     */
    public function cacheBlocks($position, $blocks) {
        return $this->set("blocks_$position", $blocks, 1800); // 30 دقيقة
    }
    
    /**
     * استرجاع البلوكات المخزنة مؤقتاً
     */
    public function getCachedBlocks($position) {
        return $this->get("blocks_$position");
    }
    
    /**
     * تخزين مؤقت للصفحات
     */
    public function cachePage($pageId, $content) {
        return $this->set("page_$pageId", $content, 3600); // ساعة واحدة
    }
    
    /**
     * استرجاع الصفحة المخزنة مؤقتاً
     */
    public function getCachedPage($pageId) {
        return $this->get("page_$pageId");
    }
    
    /**
     * تخزين مؤقت لنتائج قاعدة البيانات
     */
    public function cacheQuery($queryHash, $result) {
        return $this->set("query_$queryHash", $result, 900); // 15 دقيقة
    }
    
    /**
     * استرجاع نتائج الاستعلام المخزنة مؤقتاً
     */
    public function getCachedQuery($queryHash) {
        return $this->get("query_$queryHash");
    }
    
    /**
     * إنشاء hash للاستعلام
     */
    public function createQueryHash($sql, $params = []) {
        return md5($sql . serialize($params));
    }
    
    /**
     * الحصول على اسم ملف التخزين المؤقت
     */
    private function getCacheFilename($key) {
        return $this->cacheDir . md5($key) . '.cache';
    }
}

// إنشاء كائن التخزين المؤقت العام
if (!isset($GLOBALS['cache'])) {
    $GLOBALS['cache'] = new CacheSystem();
}

/**
 * دوال مساعدة للتخزين المؤقت
 */
function cache_set($key, $data, $duration = null) {
    return $GLOBALS['cache']->set($key, $data, $duration);
}

function cache_get($key) {
    return $GLOBALS['cache']->get($key);
}

function cache_delete($key) {
    return $GLOBALS['cache']->delete($key);
}

function cache_clear() {
    return $GLOBALS['cache']->clear();
}

function cache_cleanup() {
    return $GLOBALS['cache']->cleanup();
}

?>
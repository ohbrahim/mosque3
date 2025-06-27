<?php
/**
 * دوال مساعدة إضافية
 */

if (!function_exists('createSlug')) {
    function createSlug($text) {
        // تحويل النص إلى slug
        $slug = $text;
        
        // إزالة الأحرف الخاصة
        $slug = preg_replace('/[^\p{Arabic}\p{L}\p{N}\s\-_]/u', '', $slug);
        
        // استبدال المسافات بشرطات
        $slug = preg_replace('/\s+/', '-', trim($slug));
        
        // إزالة الشرطات المتتالية
        $slug = preg_replace('/-+/', '-', $slug);
        
        // إزالة الشرطات من البداية والنهاية
        $slug = trim($slug, '-');
        
        return $slug ?: 'page-' . time();
    }
}

if (!function_exists('generateSlug')) {
    function generateSlug($title) {
        return createSlug($title);
    }
}

if (!function_exists('updateSettingsCache')) {
    function updateSettingsCache($db) {
        try {
            // يمكن إضافة منطق تحديث الكاش هنا
            return true;
        } catch (Exception $e) {
            error_log("Error updating settings cache: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}

if (!function_exists('hasPermission')) {
    function hasPermission($permission) {
        if (!isLoggedIn()) {
            return false;
        }
        
        $role = $_SESSION['role'] ?? 'member';
        
        // صلاحيات المدير
        if ($role === 'admin') {
            return true;
        }
        
        // صلاحيات المشرف
        if ($role === 'moderator') {
            $moderatorPermissions = [
                'admin_access', 'manage_pages', 'manage_blocks', 
                'manage_comments', 'manage_polls', 'view_statistics'
            ];
            return in_array($permission, $moderatorPermissions);
        }
        
        // صلاحيات المحرر
        if ($role === 'editor') {
            $editorPermissions = [
                'admin_access', 'manage_own_pages', 'view_comments', 'view_statistics'
            ];
            return in_array($permission, $editorPermissions);
        }
        
        return false;
    }
}

if (!function_exists('formatArabicDate')) {
    function formatArabicDate($date) {
        if (!$date) return '';
        
        $timestamp = strtotime($date);
        $arabicMonths = [
            1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
            5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
            9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر'
        ];
        
        $day = date('d', $timestamp);
        $month = $arabicMonths[date('n', $timestamp)];
        $year = date('Y', $timestamp);
        $time = date('H:i', $timestamp);
        
        return convertToArabicNumbers("{$day} {$month} {$year} - {$time}");
    }
}

if (!function_exists('convertToArabicNumbers')) {
    function convertToArabicNumbers($string) {
        $western = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        
        return str_replace($western, $arabic, $string);
    }
}

if (!function_exists('truncateText')) {
    function truncateText($text, $length = 100) {
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        return mb_substr($text, 0, $length) . '...';
    }
}

if (!function_exists('canEditPage')) {
    function canEditPage($pageId) {
        if (!isLoggedIn()) {
            return false;
        }
        
        // المدير والمشرف يمكنهما تعديل أي صفحة
        if (in_array($_SESSION['role'], ['admin', 'moderator'])) {
            return true;
        }
        
        // المحرر يمكنه تعديل صفحاته فقط
        if ($_SESSION['role'] === 'editor') {
            global $db;
            try {
                $page = $db->fetchOne("SELECT created_by FROM pages WHERE id = ?", [$pageId]);
                return $page && $page['created_by'] == $_SESSION['user_id'];
            } catch (Exception $e) {
                return false;
            }
        }
        
        return false;
    }
}

if (!function_exists('logActivity')) {
    function logActivity($db, $action, $details = '', $userId = null) {
        try {
            $db->insert('activity_log', [
                'user_id' => $userId ?: ($_SESSION['user_id'] ?? null),
                'action' => $action,
                'details' => $details,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            error_log("Error logging activity: " . $e->getMessage());
        }
    }
}
?>

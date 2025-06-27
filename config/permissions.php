<?php
/**
 * نظام الصلاحيات المحسن مع تحديث صلاحيات المشرف
 */

// تعريف الأدوار والصلاحيات
$rolePermissions = [
    'admin' => [
        'view_admin_panel', 'manage_users', 'manage_pages', 'manage_comments', 
        'manage_messages', 'manage_blocks', 'manage_settings', 'manage_polls',
        'manage_advertisements', 'view_statistics', 'delete_pages', 'delete_comments',
        'delete_messages', 'delete_polls', 'delete_advertisements', 'edit_pages',
        'edit_comments', 'edit_messages', 'edit_polls', 'edit_advertisements'
    ],
    'moderator' => [
        'view_admin_panel', 'manage_pages', 'manage_comments', 'manage_messages',
        'manage_polls', 'manage_advertisements', 'view_statistics', 'add_pages',
        'add_comments', 'add_messages', 'edit_pages', 'edit_comments', 'edit_messages',
        'edit_polls', 'edit_advertisements', 'delete_comments'
    ],
    'editor' => [
        'view_admin_panel', 'add_pages', 'edit_pages', 'manage_pages'
    ],
    'member' => [
        'add_comments', 'edit_own_comments'
    ]
];

/**
 * التحقق من صلاحية المستخدم
 */
function hasPermission($permission, $userId = null) {
    global $rolePermissions;
    
    if (!isset($_SESSION['role'])) {
        return false;
    }
    
    $userRole = $_SESSION['role'];
    
    // المدير العام له جميع الصلاحيات
    if ($userRole === 'admin') {
        return true;
    }
    
    // التحقق من الصلاحية حسب الدور
    if (isset($rolePermissions[$userRole])) {
        return in_array($permission, $rolePermissions[$userRole]);
    }
    
    return false;
}

/**
 * التحقق من إمكانية الحذف - المشرف يمكنه حذف التعليقات فقط
 */
function canDelete($contentType, $contentId = null, $userId = null) {
    if (!isLoggedIn()) return false;
    
    $userRole = $_SESSION['role'];
    
    // الأدمن يمكنه حذف كل شيء
    if ($userRole === 'admin') {
        return true;
    }
    
    // المشرف يمكنه حذف التعليقات فقط
    if ($userRole === 'moderator' && $contentType === 'comments') {
        return true;
    }
    
    return false;
}

/**
 * التحقق من إمكانية تعديل المحتوى - إضافة صلاحيات للمحرر
 */
function canEdit($contentType, $contentId = null, $userId = null) {
    if (!isLoggedIn()) return false;
    
    $userRole = $_SESSION['role'];
    $currentUserId = $_SESSION['user_id'];
    
    // الأدمن يمكنه تعديل كل شيء
    if ($userRole === 'admin') {
        return true;
    }
    
    // المشرف يمكنه تعديل الصفحات والتعليقات والرسائل والاستطلاعات والإعلانات
    if ($userRole === 'moderator') {
        return in_array($contentType, ['pages', 'comments', 'messages', 'polls', 'advertisements']);
    }
    
    // المحرر يمكنه إضافة وتعديل الصفحات
    if ($userRole === 'editor' && $contentType === 'pages') {
        return true;
    }
    
    return false;
}

/**
 * التحقق من إمكانية الإضافة
 */
function canAdd($contentType) {
    if (!isset($_SESSION['role'])) {
        return false;
    }
    
    $userRole = $_SESSION['role'];
    
    switch ($contentType) {
        case 'pages':
            return in_array($userRole, ['admin', 'moderator', 'editor']);
        case 'comments':
            return in_array($userRole, ['admin', 'moderator', 'editor', 'member']);
        case 'messages':
            return in_array($userRole, ['admin', 'moderator']);
        case 'blocks':
        case 'advertisements':
            return in_array($userRole, ['admin']);
        case 'users':
            return $userRole === 'admin';
        default:
            return false;
    }
}

/**
 * التحقق من إمكانية عرض الإحصائيات
 */
function canViewStatistics() {
    if (!isset($_SESSION['role'])) {
        return false;
    }
    
    $userRole = $_SESSION['role'];
    return in_array($userRole, ['admin', 'moderator']);
}

/**
 * فلترة القوائم حسب الصلاحيات
 */
function filterMenuItems($menuItems) {
    $filteredItems = [];
    
    foreach ($menuItems as $item) {
        if (isset($item['permission']) && !hasPermission($item['permission'])) {
            continue;
        }
        $filteredItems[] = $item;
    }
    
    return $filteredItems;
}

/**
 * التحقق من حالة المستخدم وصلاحياته
 */
function requirePermission($permission) {
    if (!hasPermission($permission)) {
        die('ليس لديك صلاحية للوصول إلى هذه الصفحة');
    }
}

/**
 * التحقق من إمكانية الوصول للمحتوى
 */
function canAccess($contentType, $contentOwnerId = null) {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    $userRole = $_SESSION['role'];
    $userId = $_SESSION['user_id'];
    
    // المدير والمشرف يمكنهما الوصول لكل شيء
    if (in_array($userRole, ['admin', 'moderator'])) {
        return true;
    }
    
    // المحرر والعضو يمكنهما الوصول لمحتواهما فقط
    if ($contentType === 'profile' || $contentType === 'stats') {
        return $contentOwnerId == $userId;
    }
    
    return false;
}

/**
 * الحصول على قائمة الصفحات المسموحة حسب الدور
 */
function getAllowedPages() {
    if (!isset($_SESSION['role'])) {
        return [];
    }
    
    $userRole = $_SESSION['role'];
    $pages = [];
    
    // الصفحات المشتركة
    $pages[] = ['name' => 'الملف الشخصي', 'url' => 'profile.php', 'icon' => 'fas fa-user'];
    
    if ($userRole === 'admin') {
        $pages = array_merge($pages, [
            ['name' => 'لوحة التحكم', 'url' => 'index.php', 'icon' => 'fas fa-tachometer-alt'],
            ['name' => 'إدارة المستخدمين', 'url' => 'users.php', 'icon' => 'fas fa-users'],
            ['name' => 'إدارة الصفحات', 'url' => 'pages.php', 'icon' => 'fas fa-file-alt'],
            ['name' => 'إدارة البلوكات', 'url' => 'blocks.php', 'icon' => 'fas fa-th-large'],
            ['name' => 'إدارة الإعلانات', 'url' => 'advertisements.php', 'icon' => 'fas fa-bullhorn'],
            ['name' => 'التعليقات', 'url' => 'comments.php', 'icon' => 'fas fa-comments'],
            ['name' => 'الرسائل', 'url' => 'messages.php', 'icon' => 'fas fa-envelope'],
            ['name' => 'الاستطلاعات', 'url' => 'polls.php', 'icon' => 'fas fa-poll'],
            ['name' => 'الإحصائيات', 'url' => 'statistics.php', 'icon' => 'fas fa-chart-bar'],
            ['name' => 'الإعدادات', 'url' => 'settings.php', 'icon' => 'fas fa-cog']
        ]);
    } elseif ($userRole === 'moderator') {
        $pages = array_merge($pages, [
            ['name' => 'لوحة التحكم', 'url' => 'index.php', 'icon' => 'fas fa-tachometer-alt'],
            ['name' => 'إدارة الصفحات', 'url' => 'pages.php', 'icon' => 'fas fa-file-alt'],
            ['name' => 'التعليقات', 'url' => 'comments.php', 'icon' => 'fas fa-comments'],
            ['name' => 'الرسائل', 'url' => 'messages.php', 'icon' => 'fas fa-envelope'],
            ['name' => 'الإحصائيات', 'url' => 'statistics.php', 'icon' => 'fas fa-chart-bar']
        ]);
    } elseif ($userRole === 'editor') {
        $pages = array_merge($pages, [
            ['name' => 'لوحة التحكم', 'url' => 'editor_dashboard.php', 'icon' => 'fas fa-tachometer-alt'],
            ['name' => 'صفحاتي', 'url' => 'my_pages.php', 'icon' => 'fas fa-file-alt'],
            ['name' => 'إحصائياتي', 'url' => 'my_stats.php', 'icon' => 'fas fa-chart-line']
        ]);
    } elseif ($userRole === 'member') {
        $pages = array_merge($pages, [
            ['name' => 'لوحة التحكم', 'url' => 'member_dashboard.php', 'icon' => 'fas fa-tachometer-alt'],
            ['name' => 'إحصائياتي', 'url' => 'my_stats.php', 'icon' => 'fas fa-chart-line']
        ]);
    }
    
    return $pages;
}

/**
 * التحقق من إمكانية الوصول للصفحة
 */
function canAccessPage($page) {
    $allowedPages = getAllowedPages();
    foreach ($allowedPages as $allowedPage) {
        if (strpos($allowedPage['url'], $page) !== false) {
            return true;
        }
    }
    return false;
}
?>
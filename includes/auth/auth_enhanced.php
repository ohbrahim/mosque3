<?php
class AuthEnhanced {
    private $db;
    private $permissions = [
        'admin' => [
            'admin_access', 'manage_users', 'manage_pages', 'manage_blocks',
            'manage_comments', 'manage_messages', 'manage_polls', 'manage_settings',
            'view_statistics', 'delete_content', 'approve_users'
        ],
        'moderator' => [
            'admin_access', 'manage_pages', 'manage_blocks', 'manage_comments',
            'manage_messages', 'view_statistics'
            // لا يشمل delete_content
        ],
        'editor' => [
            'admin_access', 'manage_own_pages', 'manage_comments', 'view_profile',
            'view_own_statistics'
        ],
        'member' => [
            'view_profile', 'add_comments', 'view_own_statistics'
        ]
    ];
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function hasPermission($permission, $resourceOwnerId = null) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $userRole = $_SESSION['role'];
        $userId = $_SESSION['user_id'];
        
        // المدير له جميع الصلاحيات
        if ($userRole === 'admin') {
            return true;
        }
        
        // التحقق من الصلاحيات العامة
        if (isset($this->permissions[$userRole]) && 
            in_array($permission, $this->permissions[$userRole])) {
            
            // للمحرر: التحقق من ملكية المحتوى
            if ($userRole === 'editor' && $permission === 'manage_own_pages') {
                return $resourceOwnerId === null || $resourceOwnerId == $userId;
            }
            
            return true;
        }
        
        return false;
    }
    
    public function canDelete($resourceType, $resourceOwnerId = null) {
        $userRole = $_SESSION['role'] ?? null;
        
        // فقط المدير يمكنه الحذف
        return $userRole === 'admin';
    }
    
    public function canEdit($resourceType, $resourceOwnerId = null) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $userRole = $_SESSION['role'];
        $userId = $_SESSION['user_id'];
        
        switch ($userRole) {
            case 'admin':
                return true;
            case 'moderator':
                return in_array($resourceType, ['pages', 'comments', 'messages']);
            case 'editor':
                return $resourceType === 'pages' && 
                       ($resourceOwnerId === null || $resourceOwnerId == $userId);
            default:
                return false;
        }
    }
    
    /**
     * باقي الدوال الموجودة
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        try {
            return $this->db->fetchOne("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
        } catch (Exception $e) {
            return null;
        }
    }
    
    public function logout() {
        // حذف رمز التذكر
        if (isset($_SESSION['user_id'])) {
            $this->db->update('users', ['remember_token' => null], 'id = ?', [$_SESSION['user_id']]);
        }
        
        // حذف الكوكيز
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
        }
        
        session_destroy();
        header('Location: login_enhanced.php');
        exit;
    }
}
?>
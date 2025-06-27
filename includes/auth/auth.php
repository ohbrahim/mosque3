<?php
/**
 * نظام المصادقة والصلاحيات
 */

class Auth {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * تسجيل دخول المستخدم
     */
    public function login($username, $password) {
        try {
            $user = $this->db->fetchOne(
                "SELECT * FROM users WHERE username = ? AND status = 'active'", 
                [$username]
            );
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                
                // تحديث آخر تسجيل دخول
                $this->db->update('users', 
                    ['last_login' => date('Y-m-d H:i:s')], 
                    'id = ?', 
                    [$user['id']]
                );
                
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log('Login error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * تسجيل خروج المستخدم
     */
    public function logout() {
        session_destroy();
        header('Location: login.php');
        exit;
    }
    
    /**
     * التحقق من تسجيل الدخول
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * التحقق من الدور
     */
    public function hasRole($role) {
        return $this->isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === $role;
    }
    
    /**
     * التحقق من صلاحية معينة
     */
    public function hasPermission($permission) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        // المدير له جميع الصلاحيات
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
            return true;
        }
        
        // صلاحيات المحرر
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'editor') {
            $editorPermissions = [
                'admin_access', 'manage_pages', 'manage_blocks', 
                'manage_comments', 'view_statistics'
            ];
            return in_array($permission, $editorPermissions);
        }
        
        // صلاحيات الكاتب
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'author') {
            $authorPermissions = ['admin_access', 'manage_pages'];
            return in_array($permission, $authorPermissions);
        }
        
        return false;
    }
    
    /**
     * الحصول على بيانات المستخدم الحالي
     */
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
}
?>

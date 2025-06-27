<?php
/**
 * دوال تسجيل الأنشطة
 */

function logActivity($db, $action, $description = '', $userId = null) {
    try {
        $userId = $userId ?? ($_SESSION['user_id'] ?? null);
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $db->insert('activity_log', [
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        // تجاهل أخطاء تسجيل الأنشطة
        error_log("Activity log error: " . $e->getMessage());
    }
}

function getRecentActivities($db, $limit = 10, $userId = null) {
    try {
        $sql = "
            SELECT al.*, u.full_name, u.username 
            FROM activity_log al 
            LEFT JOIN users u ON al.user_id = u.id 
        ";
        
        $params = [];
        if ($userId) {
            $sql .= " WHERE al.user_id = ?";
            $params[] = $userId;
        }
        
        $sql .= " ORDER BY al.created_at DESC LIMIT ?";
        $params[] = $limit;
        
        return $db->fetchAll($sql, $params);
    } catch (Exception $e) {
        return [];
    }
}
?>

<?php
/**
 * ملف مساعد للحصول على بيانات بلوك محدد
 * يستخدم في واجهة إدارة البلوكات
 */

require_once '../config.php';
require_once '../includes/auth/auth.php';

// التحقق من صلاحيات الإدارة
$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasRole('admin')) {
    http_response_code(403);
    exit('غير مصرح');
}

// التحقق من وجود معرف البلوك
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    exit('معرف البلوك مطلوب');
}

$blockId = (int)$_GET['id'];

try {
    // الحصول على بيانات البلوك
    $stmt = $pdo->prepare("SELECT * FROM blocks WHERE id = ?");
    $stmt->execute([$blockId]);
    $block = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$block) {
        http_response_code(404);
        exit('البلوك غير موجود');
    }
    
    // تحويل التواريخ إلى تنسيق HTML datetime-local
    if ($block['schedule_start']) {
        $block['schedule_start'] = date('Y-m-d\TH:i', strtotime($block['schedule_start']));
    }
    
    if ($block['schedule_end']) {
        $block['schedule_end'] = date('Y-m-d\TH:i', strtotime($block['schedule_end']));
    }
    
    // إرجاع البيانات كـ JSON
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($block, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    exit('خطأ في الخادم: ' . $e->getMessage());
}

?>
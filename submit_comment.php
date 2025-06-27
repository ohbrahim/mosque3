<?php
require_once 'config/config.php';
require_once 'includes/all_functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // التحقق من رمز CSRF
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        die('رمز الأمان غير صحيح');
    }
    
    $pageId = (int)($_POST['page_id'] ?? 0);
    $content = $_POST['content'] ?? '';
    $returnUrl = $_POST['return_url'] ?? 'index.php';
    
    if (empty($pageId) || empty($content)) {
        die('بيانات غير كاملة');
    }
    
    // التحقق من وجود الصفحة
    $page = $db->fetchOne("SELECT id FROM pages WHERE id = ?", [$pageId]);
    if (!$page) {
        die('الصفحة غير موجودة');
    }
    
    // إضافة التعليق
    if (isLoggedIn()) {
        // مستخدم مسجل
        $result = addComment($db, $pageId, $content, null, null, $_SESSION['user_id']);
    } else {
        // زائر
        $authorName = $_POST['author_name'] ?? 'زائر';
        $authorEmail = $_POST['author_email'] ?? '';
        $result = addComment($db, $pageId, $content, $authorName, $authorEmail);
    }
    
    if ($result) {
        // التحقق من إعداد الموافقة التلقائية
        $autoApprove = getSetting($db, 'auto_approve_comments', '0');
        
        if ($autoApprove === '1') {
            // تم الموافقة تلقائياً
            header('Location: ' . $returnUrl . '?comment_status=approved');
        } else {
            // في انتظار الموافقة
            header('Location: ' . $returnUrl . '?comment_status=pending');
        }
    } else {
        // فشل في إضافة التعليق
        header('Location: ' . $returnUrl . '?comment_status=error');
    }
    
    exit;
}

// إعادة التوجيه إلى الصفحة الرئيسية إذا تم الوصول مباشرة
header('Location: index.php');
exit;
?>

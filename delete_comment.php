<?php
/**
 * حذف تعليق
 */
require_once 'config/config.php';
require_once 'includes/comments_system.php';

// التحقق من تسجيل الدخول
requireLogin();

$commentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($commentId) {
    // جلب بيانات التعليق
    $comment = $db->fetchOne("SELECT * FROM comments WHERE id = ?", [$commentId]);
    
    if ($comment) {
        // التحقق من الصلاحية
        $canDelete = false;
        
        if ($auth->hasPermission('manage_comments')) {
            $canDelete = true; // المدير يمكنه حذف أي تعليق
        } elseif ($comment['user_id'] == $_SESSION['user_id']) {
            $canDelete = true; // المستخدم يمكنه حذف تعليقه
        }
        
        if ($canDelete) {
            if (deleteComment($db, $commentId)) {
                $_SESSION['comment_message'] = 'تم حذف التعليق بنجاح';
            } else {
                $_SESSION['comment_error'] = 'فشل في حذف التعليق';
            }
        } else {
            $_SESSION['comment_error'] = 'ليس لديك صلاحية لحذف هذا التعليق';
        }
    } else {
        $_SESSION['comment_error'] = 'التعليق غير موجود';
    }
}

// إعادة التوجيه
$redirectUrl = $_SERVER['HTTP_REFERER'] ?? 'index.php';
header('Location: ' . $redirectUrl);
exit;
?>

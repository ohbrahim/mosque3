<?php
/**
 * دوال التعليقات المحسنة
 */

if (!function_exists('addComment')) {
    function addComment($db, $pageId, $content, $authorName = null, $authorEmail = null, $userId = null) {
        try {
            $data = [
                'page_id' => $pageId,
                'content' => sanitize($content),
                'status' => 'approved', // موافقة تلقائية للاختبار
                'created_at' => date('Y-m-d H:i:s'),
                'author_ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
            ];
            
            if ($userId) {
                $data['user_id'] = $userId;
                // جلب اسم المستخدم
                $user = $db->fetchOne("SELECT full_name, email FROM users WHERE id = ?", [$userId]);
                if ($user) {
                    $data['author_name'] = $user['full_name'];
                    $data['author_email'] = $user['email'];
                }
            } else {
                $data['author_name'] = $authorName;
                $data['author_email'] = $authorEmail;
            }
            
            return $db->insert('comments', $data);
        } catch (Exception $e) {
            error_log("Error adding comment: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('getPageComments')) {
    function getPageComments($db, $pageId) {
        try {
            return $db->fetchAll("
                SELECT c.*, u.full_name as user_name, u.avatar 
                FROM comments c 
                LEFT JOIN users u ON c.user_id = u.id 
                WHERE c.page_id = ? AND c.status = 'approved' 
                ORDER BY c.created_at DESC
            ", [$pageId]);
        } catch (Exception $e) {
            return [];
        }
    }
}

if (!function_exists('displayComments')) {
    function displayComments($db, $pageId) {
        $comments = getPageComments($db, $pageId);
        
        $html = '<div class="comments-section mt-5">';
        $html .= '<h4 class="mb-4">';
        $html .= '<i class="fas fa-comments"></i> ';
        $html .= 'التعليقات (' . convertToArabicNumbers(count($comments)) . ')';
        $html .= '</h4>';
        
        // نموذج إضافة تعليق
        $html .= '<div class="add-comment-form mb-4">';
        $html .= '<div class="card">';
        $html .= '<div class="card-body">';
        $html .= '<form method="POST" action="submit_comment.php">';
        $html .= '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">';
        $html .= '<input type="hidden" name="page_id" value="' . $pageId . '">';
        
        if (isLoggedIn()) {
            $html .= '<div class="mb-3">';
            $html .= '<label for="comment_content" class="form-label">أضف تعليقك</label>';
            $html .= '<textarea class="form-control" id="comment_content" name="content" rows="4" placeholder="شاركنا رأيك..." required></textarea>';
            $html .= '</div>';
            $html .= '<button type="submit" class="btn btn-primary">';
            $html .= '<i class="fas fa-paper-plane"></i> إرسال التعليق';
            $html .= '</button>';
        } else {
            $html .= '<div class="row">';
            $html .= '<div class="col-md-6 mb-3">';
            $html .= '<label for="author_name" class="form-label">الاسم</label>';
            $html .= '<input type="text" class="form-control" id="author_name" name="author_name" required>';
            $html .= '</div>';
            $html .= '<div class="col-md-6 mb-3">';
            $html .= '<label for="author_email" class="form-label">البريد الإلكتروني</label>';
            $html .= '<input type="email" class="form-control" id="author_email" name="author_email" required>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '<div class="mb-3">';
            $html .= '<label for="comment_content" class="form-label">التعليق</label>';
            $html .= '<textarea class="form-control" id="comment_content" name="content" rows="4" placeholder="شاركنا رأيك..." required></textarea>';
            $html .= '</div>';
            $html .= '<button type="submit" class="btn btn-primary">';
            $html .= '<i class="fas fa-paper-plane"></i> إرسال التعليق';
            $html .= '</button>';
        }
        
        $html .= '</form>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        // عرض التعليقات
        if (empty($comments)) {
            $html .= '<div class="text-center py-4">';
            $html .= '<i class="fas fa-comments fa-3x text-muted mb-3"></i>';
            $html .= '<p class="text-muted">لا توجد تعليقات بعد. كن أول من يعلق!</p>';
            $html .= '</div>';
        } else {
            foreach ($comments as $comment) {
                $html .= '<div class="comment-item card mb-3">';
                $html .= '<div class="card-body">';
                
                $html .= '<div class="d-flex align-items-start">';
                $html .= '<div class="comment-avatar me-3">';
                if ($comment['avatar']) {
                    $html .= '<img src="uploads/' . $comment['avatar'] . '" alt="صورة المعلق" class="rounded-circle" width="50" height="50">';
                } else {
                    $html .= '<div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">';
                    $html .= '<i class="fas fa-user"></i>';
                    $html .= '</div>';
                }
                $html .= '</div>';
                
                $html .= '<div class="flex-grow-1">';
                $html .= '<div class="comment-header mb-2">';
                $html .= '<h6 class="mb-1">' . htmlspecialchars($comment['user_name'] ?: $comment['author_name']) . '</h6>';
                $html .= '<small class="text-muted">' . formatArabicDate($comment['created_at']) . '</small>';
                $html .= '</div>';
                
                $html .= '<div class="comment-content">';
                $html .= '<p class="mb-0">' . nl2br(htmlspecialchars($comment['content'])) . '</p>';
                $html .= '</div>';
                $html .= '</div>';
                
                $html .= '</div>';
                $html .= '</div>';
                $html .= '</div>';
            }
        }
        
        $html .= '</div>';
        
        return $html;
    }
}
?>

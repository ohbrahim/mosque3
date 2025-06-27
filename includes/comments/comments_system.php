<?php
/**
 * نظام التعليقات
 */

/**
 * عرض التعليقات لصفحة معينة
 */
function displayComments($db, $pageId, $auth) {
    // التحقق من تفعيل التعليقات
    $commentsEnabled = getSetting($db, 'enable_comments', '1') === '1';
    
    if (!$commentsEnabled) {
        return '';
    }
    
    // جلب التعليقات المعتمدة
    $comments = $db->fetchAll("
        SELECT c.*, u.full_name, u.avatar 
        FROM comments c 
        LEFT JOIN users u ON c.user_id = u.id 
        WHERE c.page_id = ? AND c.status = 'approved' 
        ORDER BY c.created_at DESC
    ", [$pageId]);
    
    ob_start();
    ?>
    <div class="comments-section mt-5">
        <h4 class="mb-4">
            <i class="fas fa-comments"></i>
            التعليقات (<?php echo convertToArabicNumbers(count($comments)); ?>)
        </h4>
        
        <!-- نموذج إضافة تعليق -->
        <?php if ($auth->isLoggedIn()): ?>
            <div class="add-comment-form mb-4">
                <form method="POST" action="submit_comment.php">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="page_id" value="<?php echo $pageId; ?>">
                    
                    <div class="mb-3">
                        <label for="comment_content" class="form-label">أضف تعليقك</label>
                        <textarea class="form-control" id="comment_content" name="content" rows="4" 
                                  placeholder="شاركنا رأيك..." required></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> إرسال التعليق
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                يجب <a href="login.php">تسجيل الدخول</a> لإضافة تعليق
            </div>
        <?php endif; ?>
        
        <!-- عرض التعليقات -->
        <div class="comments-list">
            <?php if (empty($comments)): ?>
                <div class="text-center text-muted py-4">
                    <i class="fas fa-comments fa-3x mb-3"></i>
                    <p>لا توجد تعليقات بعد. كن أول من يعلق!</p>
                </div>
            <?php else: ?>
                <?php foreach ($comments as $comment): ?>
                    <div class="comment-item">
                        <div class="comment-header">
                            <div class="d-flex align-items-center">
                                <div class="comment-avatar">
                                    <?php if ($comment['avatar']): ?>
                                        <img src="<?php echo UPLOAD_PATH . $comment['avatar']; ?>" 
                                             alt="<?php echo htmlspecialchars($comment['full_name'] ?? $comment['author_name']); ?>">
                                    <?php else: ?>
                                        <i class="fas fa-user"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="comment-meta">
                                    <h6 class="comment-author">
                                        <?php echo htmlspecialchars($comment['full_name'] ?? $comment['author_name']); ?>
                                    </h6>
                                    <small class="comment-date text-muted">
                                        <i class="fas fa-clock"></i>
                                        <?php echo formatArabicDate($comment['created_at']); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="comment-content">
                            <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                        </div>
                        
                        <?php if ($auth->isLoggedIn()): ?>
                            <div class="comment-actions">
                                <button class="btn btn-sm btn-outline-primary" onclick="replyToComment(<?php echo $comment['id']; ?>)">
                                    <i class="fas fa-reply"></i> رد
                                </button>
                                
                                <?php if ($auth->hasPermission('manage_comments') || $comment['user_id'] == $_SESSION['user_id']): ?>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteComment(<?php echo $comment['id']; ?>)">
                                        <i class="fas fa-trash"></i> حذف
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <style>
    .comments-section {
        border-top: 2px solid #e9ecef;
        padding-top: 30px;
    }
    
    .add-comment-form {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
    }
    
    .comment-item {
        background: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .comment-header {
        margin-bottom: 15px;
    }
    
    .comment-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: #e9ecef;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-left: 15px;
        overflow: hidden;
    }
    
    .comment-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .comment-avatar i {
        color: #6c757d;
        font-size: 1.2rem;
    }
    
    .comment-author {
        margin-bottom: 5px;
        color: #2c3e50;
    }
    
    .comment-content {
        line-height: 1.6;
        color: #495057;
        margin-bottom: 15px;
    }
    
    .comment-actions {
        border-top: 1px solid #e9ecef;
        padding-top: 10px;
    }
    
    .comment-actions .btn {
        margin-left: 10px;
    }
    </style>
    
    <script>
    function replyToComment(commentId) {
        // يمكن تطوير نظام الردود لاحقاً
        alert('سيتم تطوير نظام الردود قريباً');
    }
    
    function deleteComment(commentId) {
        if (confirm('هل أنت متأكد من حذف هذا التعليق؟')) {
            window.location.href = `delete_comment.php?id=${commentId}`;
        }
    }
    </script>
    <?php
    
    return ob_get_clean();
}

/**
 * إضافة تعليق جديد
 */
function addComment($db, $pageId, $userId, $content, $authorName = null, $authorEmail = null) {
    $data = [
        'page_id' => $pageId,
        'user_id' => $userId,
        'content' => sanitize($content),
        'author_name' => $authorName ? sanitize($authorName) : null,
        'author_email' => $authorEmail ? sanitize($authorEmail) : null,
        'status' => 'pending', // يحتاج موافقة المدير
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    return $db->insert('comments', $data);
}

/**
 * الموافقة على تعليق
 */
function approveComment($db, $commentId) {
    return $db->update('comments', ['status' => 'approved'], 'id = ?', [$commentId]);
}

/**
 * رفض تعليق
 */
function rejectComment($db, $commentId) {
    return $db->update('comments', ['status' => 'rejected'], 'id = ?', [$commentId]);
}

/**
 * حذف تعليق
 */
function deleteComment($db, $commentId) {
    return $db->delete('comments', 'id = ?', [$commentId]);
}
?>

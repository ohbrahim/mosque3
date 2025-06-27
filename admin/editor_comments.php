<?php
require_once '../config/config.php';

// التحقق من تسجيل الدخول وصلاحيات المحرر
if (!isLoggedIn() || !hasPermission('manage_own_pages')) {
    header('Location: ../login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// جلب التعليقات على صفحات المحرر
try {
    $comments = $db->fetchAll("
        SELECT c.*, p.title as page_title, p.slug as page_slug, u.full_name 
        FROM comments c 
        LEFT JOIN pages p ON c.page_id = p.id 
        LEFT JOIN users u ON c.user_id = u.id 
        WHERE p.author_id = ? 
        ORDER BY c.created_at DESC
    ", [$userId]);
} catch (Exception $e) {
    $_SESSION['error'] = 'حدث خطأ أثناء جلب التعليقات: ' . $e->getMessage();
    $comments = [];
}

// تضمين ملف الهيدر
require_once 'header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0">التعليقات على صفحاتي</h2>
    </div>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <?php if (empty($comments)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">لا توجد تعليقات بعد</h4>
                    <p class="text-muted">لم يتم إضافة أي تعليقات على صفحاتك حتى الآن.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>الصفحة</th>
                                <th>المعلق</th>
                                <th>التعليق</th>
                                <th>الحالة</th>
                                <th>التاريخ</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($comments as $comment): ?>
                                <tr>
                                    <td><?php echo $comment['id']; ?></td>
                                    <td>
                                        <a href="../?page=<?php echo $comment['page_slug']; ?>" target="_blank">
                                            <?php echo htmlspecialchars($comment['page_title']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($comment['full_name'] ?: 'زائر'); ?></td>
                                    <td>
                                        <div class="comment-content">
                                            <?php echo htmlspecialchars(substr($comment['content'], 0, 100)); ?>
                                            <?php if (strlen($comment['content']) > 100): ?>...<?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $comment['status'] === 'approved' ? 'success' : 
                                                ($comment['status'] === 'pending' ? 'warning' : 
                                                ($comment['status'] === 'rejected' ? 'danger' : 'secondary')); 
                                        ?>">
                                            <?php 
                                                echo $comment['status'] === 'approved' ? 'معتمد' : 
                                                    ($comment['status'] === 'pending' ? 'في الانتظار' : 
                                                    ($comment['status'] === 'rejected' ? 'مرفوض' : 'سبام')); 
                                            ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($comment['created_at'])); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-info btn-sm" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#commentModal<?php echo $comment['id']; ?>">
                                            <i class="fas fa-eye"></i> عرض
                                        </button>
                                    </td>
                                </tr>
                                
                                <!-- Modal عرض التعليق -->
                                <div class="modal fade" id="commentModal<?php echo $comment['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">تفاصيل التعليق</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <strong>الصفحة:</strong> <?php echo htmlspecialchars($comment['page_title']); ?>
                                                </div>
                                                <div class="mb-3">
                                                    <strong>المعلق:</strong> <?php echo htmlspecialchars($comment['full_name'] ?: 'زائر'); ?>
                                                </div>
                                                <div class="mb-3">
                                                    <strong>التاريخ:</strong> <?php echo date('Y-m-d H:i:s', strtotime($comment['created_at'])); ?>
                                                </div>
                                                <div class="mb-3">
                                                    <strong>الحالة:</strong>
                                                    <span class="badge bg-<?php 
                                                        echo $comment['status'] === 'approved' ? 'success' : 
                                                            ($comment['status'] === 'pending' ? 'warning' : 
                                                            ($comment['status'] === 'rejected' ? 'danger' : 'secondary')); 
                                                    ?>">
                                                        <?php 
                                                            echo $comment['status'] === 'approved' ? 'معتمد' : 
                                                                ($comment['status'] === 'pending' ? 'في الانتظار' : 
                                                                ($comment['status'] === 'rejected' ? 'مرفوض' : 'سبام')); 
                                                        ?>
                                                    </span>
                                                </div>
                                                <div class="mb-3">
                                                    <strong>التعليق:</strong>
                                                    <div class="border p-3 mt-2 bg-light">
                                                        <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                                                <a href="../?page=<?php echo $comment['page_slug']; ?>#comment-<?php echo $comment['id']; ?>" 
                                                   target="_blank" class="btn btn-primary">
                                                    عرض في الموقع
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// تضمين ملف الفوتر
require_once 'footer.php';
?>

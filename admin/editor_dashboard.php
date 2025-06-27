<?php
require_once '../config/config.php';

// التحقق من تسجيل الدخول وصلاحيات المحرر
if (!isLoggedIn() || !hasPermission('manage_own_pages')) {
    header('Location: ../login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// جلب إحصائيات المحرر
try {
    $stats = [
        'my_pages' => $db->fetchOne("SELECT COUNT(*) as count FROM pages WHERE author_id = ?", [$userId])['count'] ?? 0,
        'my_comments' => $db->fetchOne("SELECT COUNT(*) as count FROM comments WHERE page_id IN (SELECT id FROM pages WHERE author_id = ?)", [$userId])['count'] ?? 0,
        'my_ratings' => $db->fetchOne("SELECT AVG(rating) as avg FROM ratings WHERE page_id IN (SELECT id FROM pages WHERE author_id = ?)", [$userId])['avg'] ?? 0,
        'total_views' => $db->fetchOne("SELECT SUM(views_count) as total FROM pages WHERE author_id = ?", [$userId])['total'] ?? 0
    ];
} catch (Exception $e) {
    $stats = ['my_pages' => 0, 'my_comments' => 0, 'my_ratings' => 0, 'total_views' => 0];
}

// جلب آخر الصفحات التي أنشأها المحرر
try {
    $recentPages = $db->fetchAll("
        SELECT * FROM pages 
        WHERE author_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5
    ", [$userId]);
} catch (Exception $e) {
    $recentPages = [];
}

// جلب آخر التعليقات على صفحات المحرر
try {
    $recentComments = $db->fetchAll("
        SELECT c.*, p.title as page_title, u.full_name 
        FROM comments c 
        LEFT JOIN pages p ON c.page_id = p.id 
        LEFT JOIN users u ON c.user_id = u.id 
        WHERE p.author_id = ? 
        ORDER BY c.created_at DESC 
        LIMIT 5
    ", [$userId]);
} catch (Exception $e) {
    $recentComments = [];
}

// تضمين ملف الهيدر
require_once 'header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-3">
            <div class="stats-card primary">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="count"><?php echo $stats['my_pages']; ?></div>
                        <div class="title">صفحاتي</div>
                    </div>
                    <div class="icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stats-card success">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="count"><?php echo $stats['my_comments']; ?></div>
                        <div class="title">التعليقات</div>
                    </div>
                    <div class="icon">
                        <i class="fas fa-comments"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stats-card warning">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="count"><?php echo number_format($stats['my_ratings'], 1); ?></div>
                        <div class="title">متوسط التقييم</div>
                    </div>
                    <div class="icon">
                        <i class="fas fa-star"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stats-card danger">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="count"><?php echo $stats['total_views']; ?></div>
                        <div class="title">إجمالي المشاهدات</div>
                    </div>
                    <div class="icon">
                        <i class="fas fa-eye"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">صفحاتي الأخيرة</h5>
                    <a href="editor_pages.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus me-1"></i> إضافة صفحة جديدة
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($recentPages)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">لا توجد صفحات بعد</h5>
                            <p class="text-muted">ابدأ بإنشاء أول صفحة لك</p>
                            <a href="editor_pages.php" class="btn btn-primary">إنشاء صفحة جديدة</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>العنوان</th>
                                        <th>الحالة</th>
                                        <th>المشاهدات</th>
                                        <th>تاريخ الإنشاء</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentPages as $page): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($page['title']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $page['status'] === 'published' ? 'success' : ($page['status'] === 'draft' ? 'warning' : 'secondary'); ?>">
                                                    <?php 
                                                        echo $page['status'] === 'published' ? 'منشور' : 
                                                            ($page['status'] === 'draft' ? 'مسودة' : 'معلق'); 
                                                    ?>
                                                </span>
                                            </td>
                                            <td><?php echo $page['views_count']; ?></td>
                                            <td><?php echo date('Y-m-d', strtotime($page['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="editor_pages.php?edit=<?php echo $page['id']; ?>" class="btn btn-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="../?page=<?php echo $page['slug']; ?>" target="_blank" class="btn btn-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">آخر التعليقات</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recentComments)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-comments fa-2x text-muted mb-2"></i>
                            <p class="text-muted">لا توجد تعليقات بعد</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recentComments as $comment): ?>
                            <div class="border-bottom pb-2 mb-2">
                                <h6 class="mb-1"><?php echo htmlspecialchars($comment['page_title']); ?></h6>
                                <p class="mb-1 small"><?php echo htmlspecialchars(substr($comment['content'], 0, 100)) . '...'; ?></p>
                                <small class="text-muted">
                                    بواسطة <?php echo htmlspecialchars($comment['full_name'] ?: 'زائر'); ?> - 
                                    <?php echo date('Y-m-d', strtotime($comment['created_at'])); ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">روابط سريعة</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="editor_pages.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-file-alt me-2"></i> إدارة صفحاتي
                        </a>
                        <a href="editor_comments.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-comments me-2"></i> متابعة التعليقات
                        </a>
                        <a href="profile.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-user me-2"></i> الملف الشخصي
                        </a>
                        <a href="../index.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-home me-2"></i> الموقع الرئيسي
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// تضمين ملف الفوتر
require_once 'footer.php';
?>

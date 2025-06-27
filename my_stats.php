<?php
session_start();
require_once 'config/auto_config.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// إحصائيات التعليقات
$comments_stats = $db->fetchOne("
    SELECT 
        COUNT(*) as total_comments,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_comments,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_comments,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_comments
    FROM comments WHERE user_id = ?
", [$user_id]);

// إحصائيات الصفحات (للمحررين والمشرفين والإدارة)
$pages_stats = null;
if (in_array($user_role, ['editor', 'moderator', 'admin'])) {
    $pages_stats = $db->fetchOne("
        SELECT 
            COUNT(*) as total_pages,
            SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published_pages,
            SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_pages
        FROM pages WHERE author_id = ?
    ", [$user_id]);
}

// آخر النشاطات
$recent_comments = $db->fetchAll("
    SELECT c.*, p.title as page_title 
    FROM comments c 
    LEFT JOIN pages p ON c.page_id = p.id 
    WHERE c.user_id = ? 
    ORDER BY c.created_at DESC 
    LIMIT 5
", [$user_id]);

$recent_pages = [];
if (in_array($user_role, ['editor', 'moderator', 'admin'])) {
    $recent_pages = $db->fetchAll("
        SELECT * FROM pages 
        WHERE author_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5
    ", [$user_id]);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إحصائياتي</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .stats-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2rem 0; }
        .stat-card { box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); border-radius: 10px; transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-2px); }
        .stat-icon { font-size: 2rem; }
    </style>
</head>
<body>
    <div class="stats-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-0">إحصائياتي</h1>
                    <p class="mb-0">نظرة عامة على نشاطك</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="my_profile.php" class="btn btn-light">الملف الشخصي</a>
                    <a href="index.php" class="btn btn-outline-light">الرئيسية</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <!-- إحصائيات التعليقات -->
        <div class="row mb-4">
            <div class="col-12">
                <h3 class="mb-3"><i class="fas fa-comments"></i> إحصائيات التعليقات</h3>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card text-center">
                    <div class="card-body">
                        <i class="fas fa-comment stat-icon text-primary mb-2"></i>
                        <h4 class="text-primary"><?php echo $comments_stats['total_comments']; ?></h4>
                        <p class="mb-0">إجمالي التعليقات</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card text-center">
                    <div class="card-body">
                        <i class="fas fa-check-circle stat-icon text-success mb-2"></i>
                        <h4 class="text-success"><?php echo $comments_stats['approved_comments']; ?></h4>
                        <p class="mb-0">تعليقات معتمدة</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card text-center">
                    <div class="card-body">
                        <i class="fas fa-clock stat-icon text-warning mb-2"></i>
                        <h4 class="text-warning"><?php echo $comments_stats['pending_comments']; ?></h4>
                        <p class="mb-0">في الانتظار</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card text-center">
                    <div class="card-body">
                        <i class="fas fa-times-circle stat-icon text-danger mb-2"></i>
                        <h4 class="text-danger"><?php echo $comments_stats['rejected_comments']; ?></h4>
                        <p class="mb-0">تعليقات مرفوضة</p>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($pages_stats): ?>
        <!-- إحصائيات الصفحات -->
        <div class="row mb-4">
            <div class="col-12">
                <h3 class="mb-3"><i class="fas fa-file-alt"></i> إحصائيات الصفحات</h3>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card stat-card text-center">
                    <div class="card-body">
                        <i class="fas fa-file stat-icon text-info mb-2"></i>
                        <h4 class="text-info"><?php echo $pages_stats['total_pages']; ?></h4>
                        <p class="mb-0">إجمالي الصفحات</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card stat-card text-center">
                    <div class="card-body">
                        <i class="fas fa-eye stat-icon text-success mb-2"></i>
                        <h4 class="text-success"><?php echo $pages_stats['published_pages']; ?></h4>
                        <p class="mb-0">صفحات منشورة</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card stat-card text-center">
                    <div class="card-body">
                        <i class="fas fa-edit stat-icon text-secondary mb-2"></i>
                        <h4 class="text-secondary"><?php echo $pages_stats['draft_pages']; ?></h4>
                        <p class="mb-0">مسودات</p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- آخر التعليقات -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-comments"></i> آخر التعليقات</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_comments)): ?>
                            <p class="text-muted">لا توجد تعليقات</p>
                        <?php else: ?>
                            <?php foreach ($recent_comments as $comment): ?>
                                <div class="border-bottom pb-2 mb-2">
                                    <small class="text-muted"><?php echo date('Y-m-d', strtotime($comment['created_at'])); ?></small>
                                    <p class="mb-1"><?php echo mb_substr(htmlspecialchars($comment['content']), 0, 100); ?>...</p>
                                    <small>على: <?php echo htmlspecialchars($comment['page_title'] ?? 'صفحة محذوفة'); ?></small>
                                </div>
                            <?php endforeach; ?>
                            <a href="my_comments.php" class="btn btn-sm btn-outline-primary">عرض جميع التعليقات</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if (!empty($recent_pages)): ?>
            <!-- آخر الصفحات -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-file-alt"></i> آخر الصفحات</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($recent_pages as $page): ?>
                            <div class="border-bottom pb-2 mb-2">
                                <small class="text-muted"><?php echo date('Y-m-d', strtotime($page['created_at'])); ?></small>
                                <p class="mb-1">
                                    <a href="read.php?slug=<?php echo urlencode($page['slug']); ?>" target="_blank">
                                        <?php echo htmlspecialchars($page['title']); ?>
                                    </a>
                                </p>
                                <span class="badge bg-<?php echo $page['status'] === 'published' ? 'success' : 'secondary'; ?>">
                                    <?php echo $page['status'] === 'published' ? 'منشور' : 'مسودة'; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                        <?php if (in_array($user_role, ['editor', 'moderator', 'admin'])): ?>
                            <a href="admin/pages.php" class="btn btn-sm btn-outline-primary">إدارة الصفحات</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
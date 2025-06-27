<?php
session_start();
require_once 'config/auto_config.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$page = $_GET['page'] ?? 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// جلب التعليقات
$comments = $db->fetchAll("
    SELECT c.*, p.title as page_title, p.slug as page_slug 
    FROM comments c 
    LEFT JOIN pages p ON c.page_id = p.id 
    WHERE c.user_id = ? 
    ORDER BY c.created_at DESC 
    LIMIT ? OFFSET ?
", [$user_id, $limit, $offset]);

// عدد التعليقات الإجمالي
$total_comments = $db->fetchOne("SELECT COUNT(*) as count FROM comments WHERE user_id = ?", [$user_id])['count'];
$total_pages = ceil($total_comments / $limit);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعليقاتي</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .comments-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2rem 0; }
        .comment-card { box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); border-radius: 8px; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="comments-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-0">تعليقاتي</h1>
                    <p class="mb-0">إجمالي التعليقات: <?php echo $total_comments; ?></p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="my_profile.php" class="btn btn-light">الملف الشخصي</a>
                    <a href="index.php" class="btn btn-outline-light">الرئيسية</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <?php if (empty($comments)): ?>
            <div class="alert alert-info text-center">
                <h5>لا توجد تعليقات بعد</h5>
                <p>لم تقم بكتابة أي تعليقات حتى الآن.</p>
                <a href="index.php" class="btn btn-primary">تصفح الصفحات</a>
            </div>
        <?php else: ?>
            <?php foreach ($comments as $comment): ?>
                <div class="card comment-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <strong>على صفحة: 
                                <?php if ($comment['page_title']): ?>
                                    <a href="read.php?slug=<?php echo urlencode($comment['page_slug']); ?>" target="_blank">
                                        <?php echo htmlspecialchars($comment['page_title']); ?>
                                    </a>
                                <?php else: ?>
                                    صفحة محذوفة
                                <?php endif; ?>
                            </strong>
                        </div>
                        <small class="text-muted"><?php echo date('Y-m-d H:i', strtotime($comment['created_at'])); ?></small>
                    </div>
                    <div class="card-body">
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                        <?php if ($comment['status'] === 'pending'): ?>
                            <span class="badge bg-warning mt-2">في انتظار الموافقة</span>
                        <?php elseif ($comment['status'] === 'approved'): ?>
                            <span class="badge bg-success mt-2">معتمد</span>
                        <?php else: ?>
                            <span class="badge bg-danger mt-2">مرفوض</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="تصفح التعليقات">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>">السابق</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>">التالي</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
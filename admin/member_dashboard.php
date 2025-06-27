<?php
/**
 * لوحة تحكم العضو
 */
require_once '../config/config.php';

requireLogin();

// التحقق من صلاحيات العضو
if ($_SESSION['role'] !== 'member') {
    header('Location: index.php');
    exit;
}

// إحصائيات العضو
$stats = [
    'my_comments' => 0,
    'total_pages' => 0,
    'my_profile_views' => 0
];

try {
    $stats['my_comments'] = $db->fetchOne("SELECT COUNT(*) as count FROM comments WHERE user_id = ?", [$_SESSION['user_id']])['count'];
    $stats['total_pages'] = $db->fetchOne("SELECT COUNT(*) as count FROM pages")['count'];
} catch (Exception $e) {
    // تجاهل الأخطاء
}

// آخر تعليقات العضو
try {
    $myComments = $db->fetchAll("
        SELECT c.id, c.comment_text, c.created_at, p.title as page_title
        FROM comments c
        LEFT JOIN pages p ON c.page_id = p.id
        WHERE c.user_id = ?
        ORDER BY c.created_at DESC
        LIMIT 5
    ", [$_SESSION['user_id']]);
} catch (Exception $e) {
    $myComments = [];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم العضو - <?php echo SITE_NAME; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #f8f9fa;
        }
        
        .sidebar {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            min-height: 100vh;
            color: white;
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            border-radius: 8px;
            margin: 2px 0;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        
        .main-content {
            padding: 30px;
        }
        
        .page-header {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }
        
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .content-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-2 p-0">
                <div class="sidebar">
                    <div class="p-4">
                        <h4 class="text-center mb-4">
                            <i class="fas fa-user"></i>
                            لوحة العضو
                        </h4>
                        
                        <nav class="nav flex-column">
                            <a class="nav-link active" href="member_dashboard.php">
                                <i class="fas fa-tachometer-alt"></i>
                                الرئيسية
                            </a>
                            <a class="nav-link" href="my_profile.php">
                                <i class="fas fa-user-circle"></i>
                                الملف الشخصي
                            </a>
                            <a class="nav-link" href="my_comments.php">
                                <i class="fas fa-comments"></i>
                                تعليقاتي
                            </a>
                            <a class="nav-link" href="my_stats.php">
                                <i class="fas fa-chart-line"></i>
                                إحصائياتي
                            </a>
                            <hr class="my-3">
                            <a class="nav-link" href="../index.php" target="_blank">
                                <i class="fas fa-external-link-alt"></i>
                                عرض الموقع
                            </a>
                            <a class="nav-link" href="../logout.php">
                                <i class="fas fa-sign-out-alt"></i>
                                تسجيل الخروج
                            </a>
                        </nav>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-10">
                <div class="main-content">
                    <!-- Page Header -->
                    <div class="page-header">
                        <div class="row align-items-center">
                            <div class="col">
                                <h2 class="mb-0">لوحة تحكم العضو</h2>
                                <p class="text-muted">مرحباً <?php echo htmlspecialchars($_SESSION['full_name']); ?>، مرحباً بك في لوحة العضو</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Stats -->
                    <div class="row">
                        <div class="col-md-4">
                            <div class="stat-card text-center">
                                <div class="stat-icon">
                                    <i class="fas fa-comments"></i>
                                </div>
                                <h3><?php echo convertToArabicNumbers($stats['my_comments']); ?></h3>
                                <p class="text-muted">تعليقاتي</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card text-center">
                                <div class="stat-icon">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <h3><?php echo convertToArabicNumbers($stats['total_pages']); ?></h3>
                                <p class="text-muted">إجمالي الصفحات</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card text-center">
                                <div class="stat-icon">
                                    <i class="fas fa-user-circle"></i>
                                </div>
                                <h3><?php echo convertToArabicNumbers($stats['my_profile_views']); ?></h3>
                                <p class="text-muted">مشاهدات الملف</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- My Comments -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="content-card">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h5 class="mb-0">آخر تعليقاتي</h5>
                                    <a href="my_comments.php" class="btn btn-sm btn-outline-secondary">عرض الكل</a>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>التعليق</th>
                                                <th>الصفحة</th>
                                                <th>التاريخ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($myComments as $comment): ?>
                                            <tr>
                                                <td><?php echo truncateText($comment['comment_text'], 50); ?></td>
                                                <td><?php echo htmlspecialchars($comment['page_title'] ?? '-'); ?></td>
                                                <td><?php echo formatArabicDate($comment['created_at']); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                            
                                            <?php if (empty($myComments)): ?>
                                            <tr>
                                                <td colspan="3" class="text-center">لا توجد تعليقات</td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="content-card">
                                <h5 class="mb-4">الإجراءات السريعة</h5>
                                <div class="row">
                                    <div class="col-md-3">
                                        <a href="my_profile.php" class="btn btn-outline-primary w-100 mb-3">
                                            <i class="fas fa-user-edit"></i><br>
                                            تحديث الملف الشخصي
                                        </a>
                                    </div>
                                    <div class="col-md-3">
                                        <a href="my_comments.php" class="btn btn-outline-success w-100 mb-3">
                                            <i class="fas fa-comments"></i><br>
                                            إدارة التعليقات
                                        </a>
                                    </div>
                                    <div class="col-md-3">
                                        <a href="../index.php" class="btn btn-outline-info w-100 mb-3" target="_blank">
                                            <i class="fas fa-globe"></i><br>
                                            زيارة الموقع
                                        </a>
                                    </div>
                                    <div class="col-md-3">
                                        <a href="my_stats.php" class="btn btn-outline-warning w-100 mb-3">
                                            <i class="fas fa-chart-bar"></i><br>
                                            عرض الإحصائيات
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
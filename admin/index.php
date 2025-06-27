<?php
require_once '../config/config.php';
requireLogin();

// التحقق من الصلاحيات
if (!isAdmin() && !isset($_SESSION['role'])) {
    die('ليس لديك صلاحية للوصول إلى لوحة التحكم');
}

// إحصائيات عامة
$stats = [
    'pages' => 0,
    'users' => 0,
    'comments' => 0,
    'messages' => 0,
    'visitors_today' => 0,
    'visitors_month' => 0
];

try {
    $stats['pages'] = $db->fetchOne("SELECT COUNT(*) as count FROM pages")['count'];
} catch (Exception $e) {
    // تجاهل الخطأ
}

try {
    $stats['users'] = $db->fetchOne("SELECT COUNT(*) as count FROM users")['count'];
} catch (Exception $e) {
    // تجاهل الخطأ
}

try {
    $stats['comments'] = $db->fetchOne("SELECT COUNT(*) as count FROM comments")['count'];
} catch (Exception $e) {
    // تجاهل الخطأ
}

try {
    $stats['messages'] = $db->fetchOne("SELECT COUNT(*) as count FROM messages")['count'];
} catch (Exception $e) {
    // تجاهل الخطأ
}

try {
    $stats['visitors_today'] = $db->fetchOne("SELECT COUNT(DISTINCT visitor_ip) as count FROM visitor_stats WHERE visit_date = CURDATE()")['count'];
} catch (Exception $e) {
    // تجاهل الخطأ
}

try {
    $stats['visitors_month'] = $db->fetchOne("SELECT COUNT(DISTINCT visitor_ip) as count FROM visitor_stats WHERE MONTH(visit_date) = MONTH(CURDATE()) AND YEAR(visit_date) = YEAR(CURDATE())")['count'];
} catch (Exception $e) {
    // تجاهل الخطأ
}

// آخر الصفحات المضافة
try {
    $latestPages = $db->fetchAll("SELECT id, title, created_at FROM pages ORDER BY created_at DESC LIMIT 5");
} catch (Exception $e) {
    $latestPages = [];
}

// آخر التعليقات
try {
    $latestComments = $db->fetchAll("
        SELECT c.id, c.comment_text, c.created_at, u.full_name, p.title as page_title
        FROM comments c
        LEFT JOIN users u ON c.user_id = u.id
        LEFT JOIN pages p ON c.page_id = p.id
        ORDER BY c.created_at DESC
        LIMIT 5
    ");
} catch (Exception $e) {
    $latestComments = [];
}

// آخر الرسائل
try {
    $latestMessages = $db->fetchAll("
        SELECT id, name, email, subject, created_at
        FROM messages
        ORDER BY created_at DESC
        LIMIT 5
    ");
} catch (Exception $e) {
    $latestMessages = [];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - مسجد النور</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts - Arabic -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #f8f9fa;
        }
        
        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        
        .table-hover tbody tr:hover {
            background-color: rgba(102, 126, 234, 0.05);
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
                            <i class="fas fa-mosque"></i>
                            لوحة التحكم
                        </h4>
                        
                        <nav class="nav flex-column">
                            <a class="nav-link active" href="index.php">
                                <i class="fas fa-tachometer-alt"></i>
                                الرئيسية
                            </a>
                            <a class="nav-link" href="pages.php">
                                <i class="fas fa-file-alt"></i>
                                إدارة الصفحات
                            </a>
                            <a class="nav-link" href="blocks.php">
                                <i class="fas fa-th-large"></i>
                                إدارة البلوكات
                            </a>
                            <a class="nav-link" href="advertisements.php">
                                <i class="fas fa-bullhorn"></i>
                                إدارة الإعلانات
                            </a>
                            <a class="nav-link" href="comments.php">
                                <i class="fas fa-comments"></i>
                                التعليقات
                            </a>
                            <a class="nav-link" href="messages.php">
                                <i class="fas fa-envelope"></i>
                                الرسائل
                            </a>
                            <a class="nav-link" href="polls.php">
                                <i class="fas fa-poll"></i>
                                الاستطلاعات
                            </a>
                            <a class="nav-link" href="users.php">
                                <i class="fas fa-users"></i>
                                المستخدمون
                            </a>
                            <a class="nav-link" href="statistics.php">
                                <i class="fas fa-chart-bar"></i>
                                الإحصائيات
                            </a>
                            <a class="nav-link" href="settings.php">
                                <i class="fas fa-cog"></i>
                                الإعدادات
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
                                <h2 class="mb-0">لوحة التحكم</h2>
                                <p class="text-muted">مرحباً <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'المدير'); ?>، مرحباً بك في لوحة التحكم</p>
                            </div>
                            <div class="col-auto">
                                <div class="btn-group">
                                    <a href="pages.php?action=add" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> إضافة صفحة
                                    </a>
                                    <a href="blocks.php?action=add" class="btn btn-outline-primary">
                                        <i class="fas fa-plus"></i> إضافة بلوك
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Stats -->
                    <div class="row">
                        <div class="col-md-3">
                            <div class="stat-card text-center">
                                <div class="stat-icon">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <h3><?php echo convertToArabicNumbers($stats['pages']); ?></h3>
                                <p class="text-muted">الصفحات</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card text-center">
                                <div class="stat-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <h3><?php echo convertToArabicNumbers($stats['users']); ?></h3>
                                <p class="text-muted">المستخدمون</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card text-center">
                                <div class="stat-icon">
                                    <i class="fas fa-comments"></i>
                                </div>
                                <h3><?php echo convertToArabicNumbers($stats['comments']); ?></h3>
                                <p class="text-muted">التعليقات</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card text-center">
                                <div class="stat-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <h3><?php echo convertToArabicNumbers($stats['messages']); ?></h3>
                                <p class="text-muted">الرسائل</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-3">
                            <div class="stat-card text-center">
                                <div class="stat-icon">
                                    <i class="fas fa-eye"></i>
                                </div>
                                <h3><?php echo convertToArabicNumbers($stats['visitors_today']); ?></h3>
                                <p class="text-muted">زوار اليوم</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card text-center">
                                <div class="stat-icon">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <h3><?php echo convertToArabicNumbers($stats['visitors_month']); ?></h3>
                                <p class="text-muted">زوار الشهر</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Latest Content -->
                    <div class="row">
                        <!-- Latest Pages -->
                        <div class="col-md-6">
                            <div class="content-card">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h5 class="mb-0">آخر الصفحات المضافة</h5>
                                    <a href="pages.php" class="btn btn-sm btn-outline-primary">عرض الكل</a>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>العنوان</th>
                                                <th>تاريخ الإضافة</th>
                                                <th>الإجراءات</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($latestPages as $page): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($page['title']); ?></td>
                                                <td><?php echo formatArabicDate($page['created_at']); ?></td>
                                                <td>
                                                    <a href="pages.php?action=edit&id=<?php echo $page['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                            
                                            <?php if (empty($latestPages)): ?>
                                            <tr>
                                                <td colspan="3" class="text-center">لا توجد صفحات</td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Latest Comments -->
                        <div class="col-md-6">
                            <div class="content-card">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h5 class="mb-0">آخر التعليقات</h5>
                                    <a href="comments.php" class="btn btn-sm btn-outline-primary">عرض الكل</a>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>التعليق</th>
                                                <th>الصفحة</th>
                                                <th>المستخدم</th>
                                                <th>التاريخ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($latestComments as $comment): ?>
                                            <tr>
                                                <td><?php echo truncateText($comment['comment_text'], 30); ?></td>
                                                <td><?php echo htmlspecialchars($comment['page_title'] ?? '-'); ?></td>
                                                <td><?php echo htmlspecialchars($comment['full_name'] ?? 'زائر'); ?></td>
                                                <td><?php echo formatArabicDate($comment['created_at']); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                            
                                            <?php if (empty($latestComments)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center">لا توجد تعليقات</td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Latest Messages -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="content-card">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h5 class="mb-0">آخر الرسائل</h5>
                                    <a href="messages.php" class="btn btn-sm btn-outline-primary">عرض الكل</a>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>الاسم</th>
                                                <th>البريد الإلكتروني</th>
                                                <th>الموضوع</th>
                                                <th>التاريخ</th>
                                                <th>الإجراءات</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($latestMessages as $message): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($message['name']); ?></td>
                                                <td><?php echo htmlspecialchars($message['email']); ?></td>
                                                <td><?php echo htmlspecialchars($message['subject']); ?></td>
                                                <td><?php echo formatArabicDate($message['created_at']); ?></td>
                                                <td>
                                                    <a href="messages.php?action=view&id=<?php echo $message['id']; ?>" class="btn btn-sm btn-outline-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                            
                                            <?php if (empty($latestMessages)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center">لا توجد رسائل</td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

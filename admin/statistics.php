<?php
/**
 * الإحصائيات والتقارير
 */
require_once '../config/config.php';

// التحقق من تسجيل الدخول والصلاحيات
requireLogin();
requirePermission('view_stats');

// جلب الإحصائيات
$stats = [];

// إحصائيات الزوار
$stats['visitors'] = [
    'today' => $db->fetchOne("SELECT COUNT(DISTINCT visitor_ip) as count FROM visitor_stats WHERE visit_date = CURDATE()")['count'],
    'yesterday' => $db->fetchOne("SELECT COUNT(DISTINCT visitor_ip) as count FROM visitor_stats WHERE visit_date = DATE_SUB(CURDATE(), INTERVAL 1 DAY)")['count'],
    'this_week' => $db->fetchOne("SELECT COUNT(DISTINCT visitor_ip) as count FROM visitor_stats WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")['count'],
    'this_month' => $db->fetchOne("SELECT COUNT(DISTINCT visitor_ip) as count FROM visitor_stats WHERE MONTH(visit_date) = MONTH(CURDATE()) AND YEAR(visit_date) = YEAR(CURDATE())")['count'],
    'total' => $db->fetchOne("SELECT COUNT(DISTINCT visitor_ip) as count FROM visitor_stats")['count']
];

// إحصائيات المحتوى
$stats['content'] = [
    'pages' => $db->fetchOne("SELECT COUNT(*) as count FROM pages")['count'],
    'published_pages' => $db->fetchOne("SELECT COUNT(*) as count FROM pages WHERE status = 'published'")['count'],
    'blocks' => $db->fetchOne("SELECT COUNT(*) as count FROM blocks")['count'],
    'active_blocks' => $db->fetchOne("SELECT COUNT(*) as count FROM blocks WHERE status = 'active'")['count'],
    'advertisements' => $db->fetchOne("SELECT COUNT(*) as count FROM advertisements")['count'],
    'active_ads' => $db->fetchOne("SELECT COUNT(*) as count FROM advertisements WHERE status = 'active'")['count']
];

// إحصائيات التفاعل
$stats['interaction'] = [
    'comments' => $db->fetchOne("SELECT COUNT(*) as count FROM comments")['count'],
    'approved_comments' => $db->fetchOne("SELECT COUNT(*) as count FROM comments WHERE status = 'approved'")['count'],
    'pending_comments' => $db->fetchOne("SELECT COUNT(*) as count FROM comments WHERE status = 'pending'")['count'],
    'messages' => $db->fetchOne("SELECT COUNT(*) as count FROM messages")['count'],
    'unread_messages' => $db->fetchOne("SELECT COUNT(*) as count FROM messages WHERE status = 'unread'")['count'],
    'polls' => $db->fetchOne("SELECT COUNT(*) as count FROM polls")['count'],
    'poll_votes' => $db->fetchOne("SELECT COUNT(*) as count FROM poll_votes")['count']
];

// إحصائيات المستخدمين
$stats['users'] = [
    'total' => $db->fetchOne("SELECT COUNT(*) as count FROM users")['count'],
    'active' => $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE status = 'active'")['count'],
    'admins' => $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE role = 'admin'")['count'],
    'moderators' => $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE role = 'moderator'")['count']
];

// أكثر الصفحات زيارة
$popularPages = $db->fetchAll("SELECT title, views_count FROM pages WHERE status = 'published' ORDER BY views_count DESC LIMIT 10");

// إحصائيات الزوار الشهرية (آخر 12 شهر)
$monthlyVisitors = $db->fetchAll("
    SELECT 
        DATE_FORMAT(visit_date, '%Y-%m') as month,
        COUNT(DISTINCT visitor_ip) as visitors,
        COUNT(*) as page_views
    FROM visitor_stats 
    WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(visit_date, '%Y-%m')
    ORDER BY month ASC
");

// إحصائيات الزوار اليومية (آخر 30 يوم)
$dailyVisitors = $db->fetchAll("
    SELECT 
        visit_date,
        COUNT(DISTINCT visitor_ip) as visitors,
        COUNT(*) as page_views
    FROM visitor_stats 
    WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY visit_date
    ORDER BY visit_date ASC
");

// أكثر البلدان زيارة
$topCountries = $db->fetchAll("
    SELECT 
        country,
        COUNT(DISTINCT visitor_ip) as visitors
    FROM visitor_stats 
    WHERE country != '' AND country IS NOT NULL
    GROUP BY country
    ORDER BY visitors DESC
    LIMIT 10
");

// إحصائيات المتصفحات
$browserStats = $db->fetchAll("
    SELECT 
        CASE 
            WHEN user_agent LIKE '%Chrome%' THEN 'Chrome'
            WHEN user_agent LIKE '%Firefox%' THEN 'Firefox'
            WHEN user_agent LIKE '%Safari%' AND user_agent NOT LIKE '%Chrome%' THEN 'Safari'
            WHEN user_agent LIKE '%Edge%' THEN 'Edge'
            WHEN user_agent LIKE '%Opera%' THEN 'Opera'
            ELSE 'أخرى'
        END as browser,
        COUNT(DISTINCT visitor_ip) as visitors
    FROM visitor_stats 
    WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY browser
    ORDER BY visitors DESC
");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الإحصائيات والتقارير - لوحة التحكم</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts - Arabic -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
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
        
        .content-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .stats-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .stats-icon {
            font-size: 3rem;
            opacity: 0.3;
            position: absolute;
            top: 20px;
            left: 20px;
        }
        
        .chart-container {
            position: relative;
            height: 400px;
            margin-bottom: 30px;
        }
        
        .progress-item {
            margin-bottom: 15px;
        }
        
        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
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
                            <a class="nav-link" href="index.php">
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
                            <a class="nav-link active" href="statistics.php">
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
                                <h2 class="mb-2">الإحصائيات والتقارير</h2>
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="index.php">لوحة التحكم</a></li>
                                        <li class="breadcrumb-item active">الإحصائيات</li>
                                    </ol>
                                </nav>
                            </div>
                            <div class="col-auto">
                                <button class="btn btn-primary" onclick="exportReport()">
                                    <i class="fas fa-download"></i> تصدير التقرير
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Visitor Statistics -->
                    <div class="content-card">
                        <h5 class="mb-4">إحصائيات الزوار</h5>
                        <div class="row">
                            <div class="col-md-2">
                                <div class="stats-card position-relative text-primary">
                                    <i class="fas fa-users stat-icon"></i>
                                    <div class="stats-number"><?php echo convertToArabicNumbers($stats['visitors']['today']); ?></div>
                                    <div class="stats-label">زوار اليوم</div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="stats-card position-relative text-info">
                                    <i class="fas fa-user-clock stat-icon"></i>
                                    <div class="stats-number"><?php echo convertToArabicNumbers($stats['visitors']['yesterday']); ?></div>
                                    <div class="stats-label">زوار أمس</div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="stats-card position-relative text-success">
                                    <i class="fas fa-calendar-week stat-icon"></i>
                                    <div class="stats-number"><?php echo convertToArabicNumbers($stats['visitors']['this_week']); ?></div>
                                    <div class="stats-label">هذا الأسبوع</div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="stats-card position-relative text-warning">
                                    <i class="fas fa-calendar-alt stat-icon"></i>
                                    <div class="stats-number"><?php echo convertToArabicNumbers($stats['visitors']['this_month']); ?></div>
                                    <div class="stats-label">هذا الشهر</div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="stats-card position-relative text-danger">
                                    <i class="fas fa-globe stat-icon"></i>
                                    <div class="stats-number"><?php echo convertToArabicNumbers($stats['visitors']['total']); ?></div>
                                    <div class="stats-label">إجمالي الزوار</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Charts Row -->
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="content-card">
                                <h5 class="mb-4">إحصائيات الزوار الشهرية</h5>
                                <div class="chart-container">
                                    <canvas id="monthlyChart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="content-card">
                                <h5 class="mb-4">أكثر البلدان زيارة</h5>
                                <?php foreach ($topCountries as $index => $country): ?>
                                    <div class="progress-item">
                                        <div class="progress-label">
                                            <span><?php echo htmlspecialchars($country['country']); ?></span>
                                            <span><?php echo convertToArabicNumbers($country['visitors']); ?></span>
                                        </div>
                                        <div class="progress">
                                            <div class="progress-bar" style="width: <?php echo ($country['visitors'] / $topCountries[0]['visitors']) * 100; ?>%"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Content Statistics -->
                    <div class="content-card">
                        <h5 class="mb-4">إحصائيات المحتوى</h5>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="stats-card position-relative text-primary">
                                    <i class="fas fa-file-alt stat-icon"></i>
                                    <div class="stats-number"><?php echo convertToArabicNumbers($stats['content']['pages']); ?></div>
                                    <div class="stats-label">إجمالي الصفحات</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card position-relative text-success">
                                    <i class="fas fa-eye stat-icon"></i>
                                    <div class="stats-number"><?php echo convertToArabicNumbers($stats['content']['published_pages']); ?></div>
                                    <div class="stats-label">الصفحات المنشورة</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card position-relative text-info">
                                    <i class="fas fa-th-large stat-icon"></i>
                                    <div class="stats-number"><?php echo convertToArabicNumbers($stats['content']['blocks']); ?></div>
                                    <div class="stats-label">إجمالي البلوكات</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card position-relative text-warning">
                                    <i class="fas fa-bullhorn stat-icon"></i>
                                    <div class="stats-number"><?php echo convertToArabicNumbers($stats['content']['advertisements']); ?></div>
                                    <div class="stats-label">الإعلانات</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Interaction and Popular Pages -->
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="content-card">
                                <h5 class="mb-4">إحصائيات التفاعل</h5>
                                <div class="row">
                                    <div class="col-6">
                                        <div class="stats-card text-primary">
                                            <div class="stats-number"><?php echo convertToArabicNumbers($stats['interaction']['comments']); ?></div>
                                            <div class="stats-label">التعليقات</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="stats-card text-success">
                                            <div class="stats-number"><?php echo convertToArabicNumbers($stats['interaction']['messages']); ?></div>
                                            <div class="stats-label">الرسائل</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="stats-card text-info">
                                            <div class="stats-number"><?php echo convertToArabicNumbers($stats['interaction']['polls']); ?></div>
                                            <div class="stats-label">الاستطلاعات</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="stats-card text-warning">
                                            <div class="stats-number"><?php echo convertToArabicNumbers($stats['interaction']['poll_votes']); ?></div>
                                            <div class="stats-label">أصوات الاستطلاعات</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="content-card">
                                <h5 class="mb-4">أكثر الصفحات زيارة</h5>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>الصفحة</th>
                                                <th>المشاهدات</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($popularPages as $page): ?>
                                            <tr>
                                                <td><?php echo truncateText($page['title'], 30); ?></td>
                                                <td>
                                                    <span class="badge bg-primary">
                                                        <?php echo convertToArabicNumbers($page['views_count']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Browser Statistics -->
                    <div class="content-card">
                        <h5 class="mb-4">إحصائيات المتصفحات</h5>
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="chart-container" style="height: 300px;">
                                    <canvas id="browserChart"></canvas>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <?php foreach ($browserStats as $browser): ?>
                                    <div class="progress-item">
                                        <div class="progress-label">
                                            <span><?php echo htmlspecialchars($browser['browser']); ?></span>
                                            <span><?php echo convertToArabicNumbers($browser['visitors']); ?></span>
                                        </div>
                                        <div class="progress">
                                            <div class="progress-bar" style="width: <?php echo ($browser['visitors'] / $browserStats[0]['visitors']) * 100; ?>%"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Monthly Visitors Chart
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyData = <?php echo json_encode($monthlyVisitors); ?>;
        
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: monthlyData.map(item => item.month),
                datasets: [{
                    label: 'الزوار',
                    data: monthlyData.map(item => item.visitors),
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'مشاهدات الصفحات',
                    data: monthlyData.map(item => item.page_views),
                    borderColor: '#764ba2',
                    backgroundColor: 'rgba(118, 75, 162, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        
        // Browser Statistics Chart
        const browserCtx = document.getElementById('browserChart').getContext('2d');
        const browserData = <?php echo json_encode($browserStats); ?>;
        
        new Chart(browserCtx, {
            type: 'doughnut',
            data: {
                labels: browserData.map(item => item.browser),
                datasets: [{
                    data: browserData.map(item => item.visitors),
                    backgroundColor: [
                        '#667eea',
                        '#764ba2',
                        '#f093fb',
                        '#f5576c',
                        '#4facfe',
                        '#00f2fe'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        
        // Export Report Function
        function exportReport() {
            // يمكن تطوير هذه الوظيفة لتصدير التقارير
            alert('سيتم تطوير وظيفة التصدير قريباً');
        }
    </script>
</body>
</html>

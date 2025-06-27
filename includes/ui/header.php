<?php
require_once 'includes/functions/all_functions.php';

// عرض البانر إذا كان مفعلاً
echo displayWelcomeBanner($db);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo getSetting($db, 'site_name', 'مسجد النور'); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts - Arabic -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: <?php echo $primaryColor; ?>;
            --secondary-color: <?php echo $secondaryColor; ?>;
        }
        
        body {
            font-family: 'Cairo', sans-serif;
            line-height: 1.6;
        }
        
        .navbar {
            background: #735C5E;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }
        
       .hero-section {
        position: relative;
    background: url('uploads/logo.jpg') no-repeat center center;
    background-size: cover;
    color: white;
    padding: 80px 0;
    text-align: center;
    overflow: hidden;
}

.hero-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
  
}

.hero-section .container {
    position: relative; /* ليكون النص فوق الطبقة الشفافة */
    z-index: 2;
}

.hero-section h1 {
    font-size: 3rem;
    font-weight: 700;
    margin-bottom: 20px;
    text-shadow: 0 2px 4px rgba(0,0,0,0.5); /* تحسين قراءة النص */
}

.hero-section p {
    font-size: 1.2rem;
    opacity: 0.9;
    text-shadow: 0 1px 3px rgba(0,0,0,0.5); /* تحسين قراءة النص */
    max-width: 800px;
    margin: 0 auto;
}
        
        .page-content {
            padding: 60px 0;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s;
            margin-bottom: 30px;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .card-img-top {
            border-radius: 15px 15px 0 0;
            height: 200px;
            object-fit: cover;
        }
        
        .sidebar-widget {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .sidebar-widget h5 {
            color: var(--primary-color);
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .footer {
            background: var(--primary-color);
            color: white;
            padding: 50px 0 30px;
        }
        
        .footer h5 {
            margin-bottom: 20px;
        }
        
        .footer a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer a:hover {
            color: white;
        }
        
        .ad-banner {
            margin: 20px 0;
            text-align: center;
        }
        
        .ad-banner img {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
        }
        
        .poll-widget {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .poll-option {
            margin-bottom: 15px;
        }
        
        .poll-result {
            margin-bottom: 15px;
        }
        
        .poll-progress {
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .poll-progress-bar {
            height: 100%;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            transition: width 0.3s;
        }
        
        .comments-section {
            margin-top: 50px;
            padding-top: 30px;
            border-top: 2px solid #e9ecef;
        }
        
        .comment-item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .rating-stars {
            color: #ffc107;
            margin: 10px 0;
        }
        
        .prayer-times-widget {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .prayer-time-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        
        .prayer-time-item:last-child {
            border-bottom: none;
        }
        
        .block-widget {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .block-widget h5 {
            color: var(--primary-color);
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        /* تنسيقات جديدة للإعلانات الهامة */
        .important-ad {
            border: 2px solid #ffc107;
            background: #fffdf0;
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 20px;
            position: relative;
        }
        
        .important-ad:before {
            content: 'هام';
            position: absolute;
            top: -12px;
            left: 15px;
            background: #ffc107;
            color: #000;
            padding: 2px 10px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 0.8rem;
        }
        
        .important-ad h6 {
            color: #b71c1c;
            font-weight: 700;
            margin-bottom: 10px;
            text-align: center;
        }
        
        .important-ad .ad-content {
            text-align: center;
        }
        
        /* تنسيقات جديدة لشريط الأخبار */
        .news-ticker {
            background: linear-gradient(135deg, #2c5530 0%, #1e3a1e 100%);
            color: white;
            padding: 12px 0;
            overflow: hidden;
            position: relative;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        
        .ticker-header {
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            background: #ffc107;
            color: #000;
            font-weight: bold;
            padding: 0 20px;
            display: flex;
            align-items: center;
            z-index: 2;
        }
        
        .ticker-content {
            padding-left: 120px;
            white-space: nowrap;
            animation: ticker 30s linear infinite;
        }
        
        .ticker-item {
            display: inline-block;
            margin-right: 40px;
            position: relative;
        }
        
        .ticker-item:after {
            content: '•';
            position: absolute;
            right: -25px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,0.5);
        }
        
        .ticker-item:last-child:after {
            display: none;
        }
        
        .ticker-item a {
            color: white;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .ticker-item a:hover {
            color: #ffc107;
        }
        
        @keyframes ticker {
            0% { transform: translateX(100%); }
            100% { transform: translateX(-100%); }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-mosque me-2"></i>
                <?php echo getSetting($db, 'site_name', 'مسجد الفجر'); ?>
           </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">الرئيسية</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="?page=about2">عن المسجد</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="archive.php">أرشيف الصفحات</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="?page=events">الفعاليات</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">اتصل بنا</a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i>
                                مرحباً، <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="my_profile.php"><i class="fas fa-user me-2"></i>الملف الشخصي</a></li>
                                <li><a class="dropdown-item" href="my_comments.php"><i class="fas fa-comments me-2"></i>تعليقاتي</a></li>
                                <li><a class="dropdown-item" href="my_stats.php"><i class="fas fa-chart-bar me-2"></i>إحصائياتي</a></li>
                                <?php if (hasPermission('admin_access')): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="admin/index.php"><i class="fas fa-tachometer-alt me-2"></i>لوحة التحكم</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>تسجيل الخروج</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php"><i class="fas fa-sign-in-alt me-1"></i> تسجيل الدخول</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php#register"><i class="fas fa-user-plus me-1"></i> تسجيل جديد</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
        
</div>
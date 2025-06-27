<?php
// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// تحديد الصفحة الحالية
$currentPage = basename($_SERVER['PHP_SELF']);

// تحديد عنوان الصفحة
$pageTitle = 'لوحة التحكم';
switch ($currentPage) {
    case 'index.php':
        $pageTitle = 'الرئيسية - لوحة التحكم';
        break;
    case 'pages.php':
        $pageTitle = 'إدارة الصفحات - لوحة التحكم';
        break;
    case 'blocks.php':
        $pageTitle = 'إدارة البلوكات - لوحة التحكم';
        break;
    case 'comments.php':
        $pageTitle = 'إدارة التعليقات - لوحة التحكم';
        break;
    case 'users.php':
        $pageTitle = 'إدارة المستخدمين - لوحة التحكم';
        break;
    case 'settings.php':
        $pageTitle = 'الإعدادات - لوحة التحكم';
        break;
    case 'polls.php':
        $pageTitle = 'الاستطلاعات - لوحة التحكم';
        break;
    case 'advertisements.php':
        $pageTitle = 'الإعلانات - لوحة التحكم';
        break;
    case 'statistics.php':
        $pageTitle = 'الإحصائيات - لوحة التحكم';
        break;
    case 'editor_dashboard.php':
        $pageTitle = 'لوحة تحكم المحرر';
        break;
    case 'editor_pages.php':
        $pageTitle = 'إدارة صفحاتي - لوحة المحرر';
        break;
}

// تحديد نوع المستخدم
$isAdmin = $_SESSION['role'] === 'admin';
$isModerator = $_SESSION['role'] === 'moderator';
$isEditor = $_SESSION['role'] === 'editor';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
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
            position: fixed;
            top: 0;
            bottom: 0;
            right: 0;
            z-index: 100;
            padding: 0;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
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
            margin-right: 250px;
            padding: 20px;
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
        }
        
        .stats-card {
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            color: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .stats-card.primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .stats-card.success {
            background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);
        }
        
        .stats-card.warning {
            background: linear-gradient(135deg, #FF9800 0%, #F57C00 100%);
        }
        
        .stats-card.danger {
            background: linear-gradient(135deg, #F44336 0%, #C62828 100%);
        }
        
        .stats-card .icon {
            font-size: 3rem;
            opacity: 0.5;
        }
        
        .stats-card .count {
            font-size: 2rem;
            font-weight: 700;
        }
        
        .stats-card .title {
            font-size: 1rem;
            opacity: 0.8;
        }
        
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }
        
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        
        .form-label {
            font-weight: 600;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }
        
        .dropdown-menu {
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-radius: 10px;
        }
        
        .dropdown-item {
            padding: 8px 20px;
            transition: all 0.2s;
        }
        
        .dropdown-item:hover {
            background-color: #f8f9fa;
        }
        
        .dropdown-item i {
            margin-left: 10px;
            color: #6c757d;
        }
        
        .navbar {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 15px;
        }
        
        .navbar-brand {
            font-weight: 700;
        }
        
        .user-dropdown img {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
            margin-left: 10px;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card-header {
            background-color: transparent;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            padding: 15px 20px;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .card-footer {
            background-color: transparent;
            border-top: 1px solid rgba(0,0,0,0.1);
            padding: 15px 20px;
        }
        
        .modal-content {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .modal-header {
            border-bottom: 1px solid rgba(0,0,0,0.1);
            padding: 15px 20px;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .modal-footer {
            border-top: 1px solid rgba(0,0,0,0.1);
            padding: 15px 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-2 sidebar">
                <div class="p-4">
                    <h4 class="text-center mb-4">
                        <i class="fas fa-mosque"></i>
                        <?php if ($isEditor): ?>
                            لوحة المحرر
                        <?php else: ?>
                            لوحة التحكم
                        <?php endif; ?>
                    </h4>
                    
                    <nav class="nav flex-column">
                        <?php if ($isEditor): ?>
                            <!-- قائمة المحرر -->
                            <a class="nav-link <?php echo $currentPage === 'editor_dashboard.php' ? 'active' : ''; ?>" href="editor_dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                الرئيسية
                            </a>
                            <a class="nav-link <?php echo $currentPage === 'editor_pages.php' ? 'active' : ''; ?>" href="editor_pages.php">
                                <i class="fas fa-file-alt me-2"></i>
                                صفحاتي
                            </a>
                            <a class="nav-link <?php echo $currentPage === 'editor_comments.php' ? 'active' : ''; ?>" href="editor_comments.php">
                                <i class="fas fa-comments me-2"></i>
                                التعليقات
                            </a>
                            <a class="nav-link <?php echo $currentPage === 'profile.php' ? 'active' : ''; ?>" href="profile.php">
                                <i class="fas fa-user me-2"></i>
                                الملف الشخصي
                            </a>
                        <?php else: ?>
                            <!-- قائمة المدير والمشرف -->
                            <a class="nav-link <?php echo $currentPage === 'index.php' ? 'active' : ''; ?>" href="index.php">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                الرئيسية
                            </a>
                            <a class="nav-link <?php echo $currentPage === 'pages.php' ? 'active' : ''; ?>" href="pages.php">
                                <i class="fas fa-file-alt me-2"></i>
                                إدارة الصفحات
                            </a>
                            <a class="nav-link <?php echo $currentPage === 'blocks.php' ? 'active' : ''; ?>" href="blocks.php">
                                <i class="fas fa-th-large me-2"></i>
                                إدارة البلوكات
                            </a>
                            <a class="nav-link <?php echo $currentPage === 'advertisements.php' ? 'active' : ''; ?>" href="advertisements.php">
                                <i class="fas fa-bullhorn me-2"></i>
                                إدارة الإعلانات
                            </a>
                            <a class="nav-link <?php echo $currentPage === 'comments.php' ? 'active' : ''; ?>" href="comments.php">
                                <i class="fas fa-comments me-2"></i>
                                التعليقات
                            </a>
                            <a class="nav-link <?php echo $currentPage === 'messages.php' ? 'active' : ''; ?>" href="messages.php">
                                <i class="fas fa-envelope me-2"></i>
                                الرسائل
                            </a>
                            <a class="nav-link <?php echo $currentPage === 'polls.php' ? 'active' : ''; ?>" href="polls.php">
                                <i class="fas fa-poll me-2"></i>
                                الاستطلاعات
                            </a>
                            <?php if ($isAdmin): ?>
                                <a class="nav-link <?php echo $currentPage === 'users.php' ? 'active' : ''; ?>" href="users.php">
                                    <i class="fas fa-users me-2"></i>
                                    المستخدمون
                                </a>
                            <?php endif; ?>
                            <a class="nav-link <?php echo $currentPage === 'statistics.php' ? 'active' : ''; ?>" href="statistics.php">
                                <i class="fas fa-chart-bar me-2"></i>
                                الإحصائيات
                            </a>
                            <a class="nav-link <?php echo $currentPage === 'settings.php' ? 'active' : ''; ?>" href="settings.php">
                                <i class="fas fa-cog me-2"></i>
                                الإعدادات
                            </a>
                        <?php endif; ?>
                        
                        <hr class="my-3">
                        <a class="nav-link" href="../index.php" target="_blank">
                            <i class="fas fa-external-link-alt me-2"></i>
                            عرض الموقع
                        </a>
                        <a class="nav-link" href="../logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>
                            تسجيل الخروج
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-10 main-content">
                <!-- Navbar -->
                <nav class="navbar">
                    <div class="container-fluid">
                        <span class="navbar-brand">
                            <?php echo $pageTitle; ?>
                        </span>
                        <div class="dropdown">
                            <a class="dropdown-toggle text-decoration-none text-dark user-dropdown" href="#" role="button" data-bs-toggle="dropdown">
                                <img src="../uploads/default-avatar.png" alt="User Avatar">
                                <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user"></i> الملف الشخصي</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a></li>
                            </ul>
                        </div>
                    </div>
                </nav>

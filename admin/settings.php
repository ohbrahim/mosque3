<?php
require_once '../config/config.php';
require_once '../includes/functions/all_functions.php';

// التحقق من تسجيل الدخول والصلاحيات
requireLogin();
requirePermission('manage_settings');

// معالجة الإجراءات
$action = isset($_GET['action']) ? $_GET['action'] : 'general';
$message = '';
$error = '';

// جلب مجموعات الإعدادات
$settingGroups = [
    'general' => 'الإعدادات العامة',
    'appearance' => 'المظهر',
    'content' => 'المحتوى',
    'التعليقات' => 'التعليقات',
    'البلوكات' => 'البلوكات',
    'social' => 'التواصل الاجتماعي',
    'advanced' => 'إعدادات متقدمة'
];

// معالجة حفظ الإعدادات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'رمز الأمان غير صحيح';
    } else {
        try {
            foreach ($_POST as $key => $value) {
                if ($key !== 'csrf_token' && $key !== 'action') {
                    $db->query("
                        UPDATE settings 
                        SET setting_value = ? 
                        WHERE setting_key = ?
                    ", [$value, $key]);
                }
            }
            $message = 'تم حفظ الإعدادات بنجاح';
        } catch (Exception $e) {
            $error = 'حدث خطأ أثناء حفظ الإعدادات: ' . $e->getMessage();
        }
    }
}

// جلب الإعدادات حسب المجموعة
$settings = [];
if ($action === 'general') {
    $settings = $db->fetchAll("SELECT * FROM settings WHERE setting_group = 'general' ORDER BY display_name");
} elseif ($action === 'appearance') {
    $settings = $db->fetchAll("SELECT * FROM settings WHERE setting_group = 'appearance' ORDER BY display_name");
} elseif ($action === 'content') {
    $settings = $db->fetchAll("SELECT * FROM settings WHERE setting_group = 'content' ORDER BY display_name");
} elseif ($action === 'comments') {
    $settings = $db->fetchAll("SELECT * FROM settings WHERE setting_group = 'التعليقات' ORDER BY display_name");
} elseif ($action === 'blocks') {
    $settings = $db->fetchAll("SELECT * FROM settings WHERE setting_group = 'البلوكات' ORDER BY display_name");
} elseif ($action === 'social') {
    $settings = $db->fetchAll("SELECT * FROM settings WHERE setting_group = 'social' ORDER BY display_name");
} elseif ($action === 'advanced') {
    $settings = $db->fetchAll("SELECT * FROM settings WHERE setting_group = 'advanced' ORDER BY display_name");
}

// إضافة إعدادات افتراضية إذا لم تكن موجودة
$defaultSettings = [
    // الإعدادات العامة
    ['site_title', 'موقع المسجد', 'text', 'general', 'عنوان الموقع', 'عنوان الموقع الرئيسي'],
    ['site_description', 'موقع مسجد النور الإسلامي', 'text', 'general', 'وصف الموقع', 'وصف قصير للموقع'],
    ['admin_email', 'admin@example.com', 'email', 'general', 'البريد الإلكتروني للمدير', 'البريد الإلكتروني الرئيسي للإدارة'],
    ['items_per_page', '10', 'number', 'general', 'عدد العناصر في الصفحة', 'عدد العناصر التي تظهر في كل صفحة'],
    
    // إعدادات المظهر
    ['theme', 'default', 'text', 'appearance', 'القالب', 'القالب المستخدم في الموقع'],
    ['primary_color', '#3490dc', 'color', 'appearance', 'اللون الرئيسي', 'اللون الرئيسي للموقع'],
    ['secondary_color', '#6574cd', 'color', 'appearance', 'اللون الثانوي', 'اللون الثانوي للموقع'],
    ['show_welcome_banner', '1', 'boolean', 'appearance', 'عرض شريط الترحيب', 'عرض شريط الترحيب في الصفحة الرئيسية'],
    ['welcome_banner_title', 'مرحباً بكم', 'text', 'appearance', 'عنوان شريط الترحيب', 'عنوان شريط الترحيب'],
    ['welcome_banner_subtitle', 'أهلاً وسهلاً', 'text', 'appearance', 'العنوان الفرعي لشريط الترحيب', 'العنوان الفرعي لشريط الترحيب'],
    ['welcome_banner_content', 'مرحباً بكم في موقع مسجد النور', 'textarea', 'appearance', 'محتوى شريط الترحيب', 'محتوى شريط الترحيب'],
    
    // إعدادات المحتوى
    ['show_author', '1', 'boolean', 'content', 'عرض اسم الكاتب', 'عرض اسم كاتب المقال'],
    ['show_date', '1', 'boolean', 'content', 'عرض تاريخ النشر', 'عرض تاريخ نشر المقال'],
    ['show_related', '1', 'boolean', 'content', 'عرض المقالات ذات الصلة', 'عرض المقالات ذات الصلة في نهاية المقال'],
    ['enable_ratings', '1', 'boolean', 'content', 'تفعيل التقييمات', 'السماح للمستخدمين بتقييم المحتوى'],
    
    // إعدادات التعليقات
    ['enable_comments', '1', 'boolean', 'التعليقات', 'تفعيل التعليقات', 'السماح بالتعليقات على المحتوى'],
    ['auto_approve_comments', '0', 'boolean', 'التعليقات', 'الموافقة التلقائية', 'الموافقة على التعليقات تلقائياً'],
    ['comments_per_page', '10', 'number', 'التعليقات', 'عدد التعليقات في الصفحة', 'عدد التعليقات التي تظهر في كل صفحة'],
    ['allow_guest_comments', '1', 'boolean', 'التعليقات', 'السماح بتعليقات الزوار', 'السماح للزوار بالتعليق بدون تسجيل دخول'],
    
    // إعدادات البلوكات
    ['allow_custom_html', '1', 'boolean', 'البلوكات', 'السماح بـ HTML مخصص', 'السماح باستخدام HTML مخصص في البلوكات'],
    ['allow_iframe', '1', 'boolean', 'البلوكات', 'السماح بـ iframe', 'السماح باستخدام iframe في البلوكات'],
    ['allow_marquee', '1', 'boolean', 'البلوكات', 'السماح بـ marquee', 'السماح باستخدام marquee في البلوكات'],
    
    // إعدادات التواصل الاجتماعي
    ['facebook_url', '', 'url', 'social', 'رابط فيسبوك', 'رابط صفحة الفيسبوك'],
    ['twitter_url', '', 'url', 'social', 'رابط تويتر', 'رابط حساب تويتر'],
    ['instagram_url', '', 'url', 'social', 'رابط انستغرام', 'رابط حساب انستغرام'],
    ['youtube_url', '', 'url', 'social', 'رابط يوتيوب', 'رابط قناة اليوتيوب'],
    
    // إعدادات متقدمة
    ['maintenance_mode', '0', 'boolean', 'advanced', 'وضع الصيانة', 'تفعيل وضع الصيانة للموقع'],
    ['maintenance_message', 'الموقع قيد الصيانة حالياً، يرجى العودة لاحقاً.', 'textarea', 'advanced', 'رسالة الصيانة', 'الرسالة التي تظهر في وضع الصيانة'],
    ['cache_enabled', '0', 'boolean', 'advanced', 'تفعيل التخزين المؤقت', 'تفعيل التخزين المؤقت لتحسين الأداء'],
    ['debug_mode', '0', 'boolean', 'advanced', 'وضع التصحيح', 'تفعيل وضع التصحيح لعرض الأخطاء']
];

// إضافة الإعدادات الافتراضية إذا لم تكن موجودة
foreach ($defaultSettings as $setting) {
    try {
        $exists = $db->fetchOne("SELECT COUNT(*) as count FROM settings WHERE setting_key = ?", [$setting[0]]);
        if ($exists['count'] == 0) {
            $db->query("
                INSERT INTO settings (setting_key, setting_value, setting_type, setting_group, display_name, description)
                VALUES (?, ?, ?, ?, ?, ?)
            ", $setting);
        }
    } catch (Exception $e) {
        // تجاهل الخطأ
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الإعدادات - لوحة التحكم</title>
    
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
        
        .content-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .settings-nav .nav-link {
            color: #495057;
            border-radius: 0;
            padding: 12px 20px;
            border-left: 3px solid transparent;
        }
        
        .settings-nav .nav-link.active {
            color: #3490dc;
            background-color: #f8f9fa;
            border-left: 3px solid #3490dc;
        }
        
        .form-label {
            font-weight: 600;
        }
        
        .setting-description {
            color: #6c757d;
            font-size: 0.875rem;
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
                            <a class="nav-link" href="comments.php">
                                <i class="fas fa-comments"></i>
                                التعليقات
                            </a>
                            <a class="nav-link active" href="settings.php">
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
                                <h2 class="mb-2">الإعدادات</h2>
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="index.php">لوحة التحكم</a></li>
                                        <li class="breadcrumb-item active">الإعدادات</li>
                                    </ol>
                                </nav>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Messages -->
                    <?php if ($message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Content -->
                    <div class="content-card">
                        <div class="row">
                            <!-- Settings Navigation -->
                            <div class="col-md-3 mb-4">
                                <div class="list-group settings-nav">
                                    <a href="?action=general" class="list-group-item list-group-item-action <?php echo $action === 'general' ? 'active' : ''; ?>">
                                        <i class="fas fa-cog me-2"></i> الإعدادات العامة
                                    </a>
                                    <a href="?action=appearance" class="list-group-item list-group-item-action <?php echo $action === 'appearance' ? 'active' : ''; ?>">
                                        <i class="fas fa-palette me-2"></i> المظهر
                                    </a>
                                    <a href="?action=content" class="list-group-item list-group-item-action <?php echo $action === 'content' ? 'active' : ''; ?>">
                                        <i class="fas fa-file-alt me-2"></i> المحتوى
                                    </a>
                                    <a href="?action=comments" class="list-group-item list-group-item-action <?php echo $action === 'comments' ? 'active' : ''; ?>">
                                        <i class="fas fa-comments me-2"></i> التعليقات
                                    </a>
                                    <a href="?action=blocks" class="list-group-item list-group-item-action <?php echo $action === 'blocks' ? 'active' : ''; ?>">
                                        <i class="fas fa-th-large me-2"></i> البلوكات
                                    </a>
                                    <a href="?action=social" class="list-group-item list-group-item-action <?php echo $action === 'social' ? 'active' : ''; ?>">
                                        <i class="fas fa-share-alt me-2"></i> التواصل الاجتماعي
                                    </a>
                                    <a href="?action=advanced" class="list-group-item list-group-item-action <?php echo $action === 'advanced' ? 'active' : ''; ?>">
                                        <i class="fas fa-tools me-2"></i> إعدادات متقدمة
                                    </a>
                                </div>
                            </div>
                            
                            <!-- Settings Form -->
                            <div class="col-md-9">
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <input type="hidden" name="action" value="<?php echo $action; ?>">
                                    
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0">
                                                <?php echo $settingGroups[$action] ?? 'الإعدادات'; ?>
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <?php if (empty($settings)): ?>
                                                <div class="text-center py-4">
                                                    <i class="fas fa-cog fa-3x text-muted mb-3"></i>
                                                    <p class="text-muted">لا توجد إعدادات في هذه المجموعة</p>
                                                </div>
                                            <?php else: ?>
                                                <?php foreach ($settings as $setting): ?>
                                                    <div class="mb-4">
                                                        <?php if ($setting['setting_type'] === 'boolean'): ?>
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox" id="<?php echo $setting['setting_key']; ?>" 
                                                                       name="<?php echo $setting['setting_key']; ?>" value="1" 
                                                                       <?php echo $setting['setting_value'] == '1' ? 'checked' : ''; ?>>
                                                                <label class="form-check-label" for="<?php echo $setting['setting_key']; ?>">
                                                                    <?php echo htmlspecialchars($setting['display_name']); ?>
                                                                </label>
                                                                <div class="setting-description">
                                                                    <?php echo htmlspecialchars($setting['description']); ?>
                                                                </div>
                                                            </div>
                                                        <?php elseif ($setting['setting_type'] === 'textarea'): ?>
                                                            <label for="<?php echo $setting['setting_key']; ?>" class="form-label">
                                                                <?php echo htmlspecialchars($setting['display_name']); ?>
                                                            </label>
                                                            <textarea class="form-control" id="<?php echo $setting['setting_key']; ?>" 
                                                                      name="<?php echo $setting['setting_key']; ?>" rows="3"><?php echo htmlspecialchars($setting['setting_value']); ?></textarea>
                                                            <div class="setting-description mt-1">
                                                                <?php echo htmlspecialchars($setting['description']); ?>
                                                            </div>
                                                        <?php elseif ($setting['setting_type'] === 'color'): ?>
                                                            <label for="<?php echo $setting['setting_key']; ?>" class="form-label">
                                                                <?php echo htmlspecialchars($setting['display_name']); ?>
                                                            </label>
                                                            <input type="color" class="form-control form-control-color" id="<?php echo $setting['setting_key']; ?>" 
                                                                   name="<?php echo $setting['setting_key']; ?>" value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                                                            <div class="setting-description mt-1">
                                                                <?php echo htmlspecialchars($setting['description']); ?>
                                                            </div>
                                                        <?php elseif ($setting['setting_type'] === 'number'): ?>
                                                            <label for="<?php echo $setting['setting_key']; ?>" class="form-label">
                                                                <?php echo htmlspecialchars($setting['display_name']); ?>
                                                            </label>
                                                            <input type="number" class="form-control" id="<?php echo $setting['setting_key']; ?>" 
                                                                   name="<?php echo $setting['setting_key']; ?>" value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                                                            <div class="setting-description mt-1">
                                                                <?php echo htmlspecialchars($setting['description']); ?>
                                                            </div>
                                                        <?php else: ?>
                                                            <label for="<?php echo $setting['setting_key']; ?>" class="form-label">
                                                                <?php echo htmlspecialchars($setting['display_name']); ?>
                                                            </label>
                                                            <input type="<?php echo $setting['setting_type']; ?>" class="form-control" id="<?php echo $setting['setting_key']; ?>" 
                                                                   name="<?php echo $setting['setting_key']; ?>" value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                                                            <div class="setting-description mt-1">
                                                                <?php echo htmlspecialchars($setting['description']); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="card-footer text-end">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save"></i> حفظ الإعدادات
                                            </button>
                                        </div>
                                    </div>
                                </form>
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

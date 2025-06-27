<?php
/**
 * صفحة الإعدادات المُصلحة
 */
require_once '../config/config_clean.php';

// التحقق من الصلاحيات
requireLogin();
requirePermission('manage_settings');

$message = '';
$error = '';

// معالجة حفظ الإعدادات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'رمز الأمان غير صحيح';
    } else {
        $settings = $_POST['settings'] ?? [];
        $saved = 0;
        
        foreach ($settings as $key => $value) {
            if (saveSetting($key, $value)) {
                $saved++;
            }
        }
        
        if ($saved > 0) {
            $message = "تم حفظ {$saved} إعداد بنجاح";
        } else {
            $error = 'فشل في حفظ الإعدادات';
        }
    }
}

// جلب الإعدادات الحالية
$currentSettings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    while ($row = $stmt->fetch()) {
        $currentSettings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    $error = 'خطأ في جلب الإعدادات: ' . $e->getMessage();
}

// الإعدادات الافتراضية
$defaultSettings = [
    'site_name' => 'موقع المسجد',
    'site_description' => 'موقع إدارة المسجد',
    'admin_email' => 'admin@mosque.com',
    'site_language' => 'ar',
    'timezone' => 'Asia/Riyadh',
    'maintenance_mode' => '0',
    'primary_color' => '#2c5530',
    'secondary_color' => '#f8f9fa',
    'prayer_city' => 'Riyadh',
    'prayer_country' => 'Saudi Arabia',
    'show_prayer_times' => '1',
    'show_weather' => '1',
    'facebook_url' => '',
    'twitter_url' => '',
    'instagram_url' => '',
    'youtube_url' => ''
];

// دمج الإعدادات
foreach ($defaultSettings as $key => $defaultValue) {
    if (!isset($currentSettings[$key])) {
        $currentSettings[$key] = $defaultValue;
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعدادات الموقع - لوحة التحكم</title>
    
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
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .content-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .section-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .setting-row {
            border-bottom: 1px solid #eee;
            padding: 15px 0;
        }
        
        .setting-row:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index_fixed.php">
                <i class="fas fa-mosque"></i>
                لوحة التحكم
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../index.php" target="_blank">
                    <i class="fas fa-external-link-alt"></i>
                    عرض الموقع
                </a>
                <a class="nav-link" href="../logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    تسجيل الخروج
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Page Header -->
        <div class="content-card">
            <h2 class="mb-2">إعدادات الموقع</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index_fixed.php">لوحة التحكم</a></li>
                    <li class="breadcrumb-item active">الإعدادات</li>
                </ol>
            </nav>
        </div>
        
        <!-- Messages -->
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i>
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Settings Form -->
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <!-- إعدادات عامة -->
            <div class="content-card">
                <div class="section-header">
                    <h4 class="mb-0">
                        <i class="fas fa-cog"></i>
                        إعدادات عامة
                    </h4>
                </div>
                
                <div class="setting-row">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">اسم الموقع</label>
                        </div>
                        <div class="col-md-9">
                            <input type="text" class="form-control" name="settings[site_name]" 
                                   value="<?php echo htmlspecialchars($currentSettings['site_name']); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="setting-row">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">وصف الموقع</label>
                        </div>
                        <div class="col-md-9">
                            <textarea class="form-control" name="settings[site_description]" rows="3"><?php echo htmlspecialchars($currentSettings['site_description']); ?></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="setting-row">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">بريد المدير</label>
                        </div>
                        <div class="col-md-9">
                            <input type="email" class="form-control" name="settings[admin_email]" 
                                   value="<?php echo htmlspecialchars($currentSettings['admin_email']); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="setting-row">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">وضع الصيانة</label>
                        </div>
                        <div class="col-md-9">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="settings[maintenance_mode]" 
                                       value="1" <?php echo $currentSettings['maintenance_mode'] ? 'checked' : ''; ?>>
                                <label class="form-check-label">تفعيل وضع الصيانة</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- إعدادات التصميم -->
            <div class="content-card">
                <div class="section-header">
                    <h4 class="mb-0">
                        <i class="fas fa-palette"></i>
                        إعدادات التصميم
                    </h4>
                </div>
                
                <div class="setting-row">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">اللون الأساسي</label>
                        </div>
                        <div class="col-md-9">
                            <input type="color" class="form-control form-control-color" name="settings[primary_color]" 
                                   value="<?php echo htmlspecialchars($currentSettings['primary_color']); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="setting-row">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">اللون الثانوي</label>
                        </div>
                        <div class="col-md-9">
                            <input type="color" class="form-control form-control-color" name="settings[secondary_color]" 
                                   value="<?php echo htmlspecialchars($currentSettings['secondary_color']); ?>">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- إعدادات أوقات الصلاة -->
            <div class="content-card">
                <div class="section-header">
                    <h4 class="mb-0">
                        <i class="fas fa-pray"></i>
                        إعدادات أوقات الصلاة
                    </h4>
                </div>
                
                <div class="setting-row">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">المدينة</label>
                        </div>
                        <div class="col-md-9">
                            <input type="text" class="form-control" name="settings[prayer_city]" 
                                   value="<?php echo htmlspecialchars($currentSettings['prayer_city']); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="setting-row">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">الدولة</label>
                        </div>
                        <div class="col-md-9">
                            <input type="text" class="form-control" name="settings[prayer_country]" 
                                   value="<?php echo htmlspecialchars($currentSettings['prayer_country']); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="setting-row">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">عرض أوقات الصلاة</label>
                        </div>
                        <div class="col-md-9">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="settings[show_prayer_times]" 
                                       value="1" <?php echo $currentSettings['show_prayer_times'] ? 'checked' : ''; ?>>
                                <label class="form-check-label">عرض أوقات الصلاة في الموقع</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- وسائل التواصل الاجتماعي -->
            <div class="content-card">
                <div class="section-header">
                    <h4 class="mb-0">
                        <i class="fas fa-share-alt"></i>
                        وسائل التواصل الاجتماعي
                    </h4>
                </div>
                
                <div class="setting-row">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">فيسبوك</label>
                        </div>
                        <div class="col-md-9">
                            <input type="url" class="form-control" name="settings[facebook_url]" 
                                   value="<?php echo htmlspecialchars($currentSettings['facebook_url']); ?>" 
                                   placeholder="https://facebook.com/yourpage">
                        </div>
                    </div>
                </div>
                
                <div class="setting-row">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">تويتر</label>
                        </div>
                        <div class="col-md-9">
                            <input type="url" class="form-control" name="settings[twitter_url]" 
                                   value="<?php echo htmlspecialchars($currentSettings['twitter_url']); ?>" 
                                   placeholder="https://twitter.com/yourpage">
                        </div>
                    </div>
                </div>
                
                <div class="setting-row">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">إنستغرام</label>
                        </div>
                        <div class="col-md-9">
                            <input type="url" class="form-control" name="settings[instagram_url]" 
                                   value="<?php echo htmlspecialchars($currentSettings['instagram_url']); ?>" 
                                   placeholder="https://instagram.com/yourpage">
                        </div>
                    </div>
                </div>
                
                <div class="setting-row">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">يوتيوب</label>
                        </div>
                        <div class="col-md-9">
                            <input type="url" class="form-control" name="settings[youtube_url]" 
                                   value="<?php echo htmlspecialchars($currentSettings['youtube_url']); ?>" 
                                   placeholder="https://youtube.com/yourchannel">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- زر الحفظ -->
            <div class="content-card">
                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save"></i>
                        حفظ جميع الإعدادات
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

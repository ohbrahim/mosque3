<?php
require_once '../config/config.php';

requireLogin();
requirePermission('manage_settings');

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'رمز الأمان غير صحيح';
    } else {
        $settings = [
            'show_welcome_banner' => isset($_POST['show_welcome_banner']) ? '1' : '0',
            'welcome_banner_title' => sanitize($_POST['welcome_banner_title']),
            'welcome_banner_subtitle' => sanitize($_POST['welcome_banner_subtitle']),
            'welcome_banner_content' => sanitize($_POST['welcome_banner_content']),
            'use_arabic_numbers' => isset($_POST['use_arabic_numbers']) ? '1' : '0'
        ];
        
        $success = true;
        foreach ($settings as $key => $value) {
            $existing = $db->fetchOne("SELECT id FROM settings WHERE setting_key = ?", [$key]);
            if ($existing) {
                if (!$db->update('settings', ['setting_value' => $value], 'setting_key = ?', [$key])) {
                    $success = false;
                }
            } else {
                if (!$db->insert('settings', [
                    'setting_key' => $key,
                    'setting_value' => $value,
                    'setting_type' => 'text',
                    'category' => 'display'
                ])) {
                    $success = false;
                }
            }
        }
        
        if ($success) {
            $message = 'تم حفظ الإعدادات بنجاح';
        } else {
            $error = 'فشل في حفظ بعض الإعدادات';
        }
    }
}

// جلب الإعدادات الحالية
$showWelcomeBanner = getSetting($db, 'show_welcome_banner', '0');
$welcomeBannerTitle = getSetting($db, 'welcome_banner_title', 'مرحباً بكم');
$welcomeBannerSubtitle = getSetting($db, 'welcome_banner_subtitle', 'أهلاً وسهلاً');
$welcomeBannerContent = getSetting($db, 'welcome_banner_content', 'مرحباً بكم في موقع مسجد النور');
$useArabicNumbers = getSetting($db, 'use_arabic_numbers', '1');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعدادات العرض - لوحة التحكم</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-display"></i> إعدادات العرض</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-success"><?php echo $message; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            
                            <div class="mb-4">
                                <h5>بانر الترحيب</h5>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="show_welcome_banner" name="show_welcome_banner" <?php echo $showWelcomeBanner ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="show_welcome_banner">
                                        عرض بانر الترحيب
                                    </label>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="welcome_banner_title" class="form-label">عنوان البانر</label>
                                    <input type="text" class="form-control" id="welcome_banner_title" name="welcome_banner_title" value="<?php echo htmlspecialchars($welcomeBannerTitle); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="welcome_banner_subtitle" class="form-label">العنوان الفرعي</label>
                                    <input type="text" class="form-control" id="welcome_banner_subtitle" name="welcome_banner_subtitle" value="<?php echo htmlspecialchars($welcomeBannerSubtitle); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="welcome_banner_content" class="form-label">محتوى البانر</label>
                                    <textarea class="form-control" id="welcome_banner_content" name="welcome_banner_content" rows="3"><?php echo htmlspecialchars($welcomeBannerContent); ?></textarea>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <h5>إعدادات الأرقام</h5>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="use_arabic_numbers" name="use_arabic_numbers" <?php echo $useArabicNumbers ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="use_arabic_numbers">
                                        استخدام الأرقام العربية (١٢٣) بدلاً من الهندية (123)
                                    </label>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="index.php" class="btn btn-secondary">العودة</a>
                                <button type="submit" class="btn btn-primary">حفظ الإعدادات</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

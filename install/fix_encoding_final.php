<?php
/**
 * إصلاح نهائي لمشكلة الترميز
 */

// إعدادات قاعدة البيانات
$host = 'localhost';
$username = 'root'; // ضع اسم المستخدم الخاص بك
$password = ''; // ضع كلمة المرور الخاصة بك
$database = 'ohbrah52_mosque'; // ضع اسم قاعدة البيانات الخاصة بك

echo "<!DOCTYPE html>
<html lang='ar' dir='rtl'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>إصلاح نهائي للترميز</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap' rel='stylesheet'>
    <style>
        body { font-family: 'Cairo', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .fix-container { background: white; border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); margin: 50px auto; max-width: 800px; }
        .fix-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 20px 20px 0 0; }
        .fix-body { padding: 40px; }
        .step { background: #f8f9fa; border-radius: 10px; padding: 20px; margin-bottom: 20px; }
        .step-number { background: #667eea; color: white; border-radius: 50%; width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center; margin-left: 10px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='fix-container'>
            <div class='fix-header'>
                <h1>🔧 إصلاح نهائي للترميز</h1>
                <p>حل مشكلة عرض النصوص العربية نهائياً</p>
            </div>
            <div class='fix-body'>";

try {
    // الاتصال بقاعدة البيانات
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password, array(
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ));
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div class='step'>
            <span class='step-number'>1</span>
            <strong>الاتصال بقاعدة البيانات:</strong> 
            <span class='text-success'>✅ نجح</span>
          </div>";
    
    // تحويل قاعدة البيانات إلى UTF8MB4
    $pdo->exec("ALTER DATABASE `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    echo "<div class='step'>
            <span class='step-number'>2</span>
            <strong>تحويل قاعدة البيانات:</strong> 
            <span class='text-success'>✅ نجح</span>
          </div>";
    
    // تحويل الجداول
    $tables = ['users', 'permissions', 'settings', 'pages', 'blocks', 'advertisements', 'comments', 'messages', 'polls', 'poll_options'];
    
    foreach ($tables as $table) {
        $pdo->exec("ALTER TABLE `$table` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }
    
    echo "<div class='step'>
            <span class='step-number'>3</span>
            <strong>تحويل الجداول:</strong> 
            <span class='text-success'>✅ نجح</span>
          </div>";
    
    // حذف البيانات القديمة وإدراج بيانات جديدة بترميز صحيح
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // حذف البيانات القديمة
    $pdo->exec("DELETE FROM permissions");
    $pdo->exec("DELETE FROM settings");
    $pdo->exec("DELETE FROM blocks");
    $pdo->exec("DELETE FROM pages");
    
    // إدراج الصلاحيات
    $permissions = [
        ['manage_users', 'إدارة المستخدمين', 'users'],
        ['manage_pages', 'إدارة الصفحات', 'pages'],
        ['manage_blocks', 'إدارة البلوكات', 'blocks'],
        ['manage_ads', 'إدارة الإعلانات', 'advertisements'],
        ['manage_comments', 'إدارة التعليقات', 'comments'],
        ['manage_messages', 'إدارة الرسائل', 'messages'],
        ['manage_polls', 'إدارة الاستطلاعات', 'polls'],
        ['view_stats', 'عرض الإحصائيات', 'statistics'],
        ['manage_settings', 'إدارة الإعدادات', 'settings']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO permissions (name, description, module) VALUES (?, ?, ?)");
    foreach ($permissions as $perm) {
        $stmt->execute($perm);
    }
    
    // إدراج الإعدادات
    $settings = [
        ['site_name', 'مسجد النور', 'text', 'general', 'اسم الموقع'],
        ['site_description', 'موقع مسجد النور للتعليم القرآني والدعوة الإسلامية', 'textarea', 'general', 'وصف الموقع'],
        ['site_logo', '', 'image', 'general', 'شعار الموقع'],
        ['contact_email', 'info@mosque.com', 'text', 'contact', 'بريد التواصل'],
        ['contact_phone', '+966123456789', 'text', 'contact', 'هاتف التواصل'],
        ['contact_address', 'الرياض، المملكة العربية السعودية', 'textarea', 'contact', 'عنوان المسجد'],
        ['prayer_city', 'Riyadh', 'text', 'prayer', 'مدينة أوقات الصلاة'],
        ['enable_comments', '1', 'boolean', 'features', 'تفعيل التعليقات'],
        ['enable_ratings', '1', 'boolean', 'features', 'تفعيل التقييمات'],
        ['items_per_page', '10', 'text', 'general', 'عدد العناصر في الصفحة']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, setting_type, category, description) VALUES (?, ?, ?, ?, ?)");
    foreach ($settings as $setting) {
        $stmt->execute($setting);
    }
    
    // إدراج البلوكات
    $blocks = [
        ['آية اليوم', '<div class="text-center"><h5 class="text-primary">قال الله تعالى:</h5><p class="lead">"وَمَن يَتَّقِ اللَّهَ يَجْعَل لَّهُ مَخْرَجًا"</p><small class="text-muted">سورة الطلاق - آية 2</small></div>', 'quran_verse', 'right', 1, 'active', 1],
        ['حديث اليوم', '<div class="text-center"><h5 class="text-success">قال رسول الله ﷺ:</h5><p>"من قرأ حرفاً من كتاب الله فله به حسنة، والحسنة بعشر أمثالها"</p><small class="text-muted">رواه الترمذي</small></div>', 'hadith', 'right', 2, 'active', 1],
        ['أخبار المسجد', '<h6>آخر الأخبار:</h6><ul class="list-unstyled"><li>• محاضرة يوم الجمعة بعد صلاة العصر</li><li>• دورة تحفيظ القرآن للأطفال</li><li>• برنامج الإفطار الجماعي</li></ul>', 'news', 'left', 1, 'active', 1]
    ];
    
    $stmt = $pdo->prepare("INSERT INTO blocks (title, content, block_type, position, display_order, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($blocks as $block) {
        $stmt->execute($block);
    }
    
    // إدراج الصفحات
    $pages = [
        ['عن المسجد', 'about', '<h2>مرحباً بكم في مسجد النور</h2><p>مسجد النور هو منارة للعلم والتعليم القرآني في قلب المدينة. نسعى لخدمة المجتمع المسلم وتقديم أفضل البرامج التعليمية والدعوية.</p><h3>رؤيتنا</h3><p>أن نكون مركزاً رائداً في التعليم القرآني وخدمة المجتمع المسلم.</p><h3>رسالتنا</h3><p>تقديم تعليم قرآني متميز وبرامج دعوية هادفة لجميع أفراد المجتمع.</p>', 'تعرف على مسجد النور ورؤيته ورسالته في خدمة المجتمع المسلم', 'published', 1, 1],
        ['البرامج والأنشطة', 'programs', '<h2>برامجنا وأنشطتنا</h2><h3>برامج التحفيظ</h3><ul><li>حلقات تحفيظ القرآن للأطفال</li><li>دورات التجويد للكبار</li><li>مسابقات قرآنية شهرية</li></ul><h3>البرامج التعليمية</h3><ul><li>دروس في الفقه والعقيدة</li><li>محاضرات أسبوعية</li><li>ورش تدريبية</li></ul><h3>الأنشطة الاجتماعية</h3><ul><li>إفطار جماعي في رمضان</li><li>زيارات للمرضى</li><li>مساعدة الأسر المحتاجة</li></ul>', 'تعرف على جميع البرامج والأنشطة التي يقدمها المسجد', 'published', 1, 1],
        ['اتصل بنا', 'contact', '<h2>تواصل معنا</h2><p>نحن سعداء لتواصلكم معنا في أي وقت. يمكنكم الوصول إلينا من خلال:</p><div class="row"><div class="col-md-6"><h4>معلومات التواصل</h4><p><strong>العنوان:</strong> الرياض، المملكة العربية السعودية</p><p><strong>الهاتف:</strong> +966123456789</p><p><strong>البريد الإلكتروني:</strong> info@mosque.com</p></div><div class="col-md-6"><h4>أوقات العمل</h4><p><strong>السبت - الخميس:</strong> 6:00 ص - 10:00 م</p><p><strong>الجمعة:</strong> 6:00 ص - 12:00 م، 2:00 م - 10:00 م</p></div></div>', 'معلومات التواصل مع إدارة المسجد', 'published', 1, 1]
    ];
    
    $stmt = $pdo->prepare("INSERT INTO pages (title, slug, content, excerpt, status, author_id, allow_comments) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($pages as $page) {
        $stmt->execute($page);
    }
    
    echo "<div class='step'>
            <span class='step-number'>4</span>
            <strong>إدراج البيانات بترميز صحيح:</strong> 
            <span class='text-success'>✅ نجح</span>
          </div>";
    
    echo "<div class='alert alert-success mt-4'>
            <h4>🎉 تم إصلاح مشكلة الترميز نهائياً!</h4>
            <p>الآن يجب أن تظهر النصوص العربية بشكل صحيح في الموقع.</p>
            <div class='mt-3'>
                <a href='../index.php' class='btn btn-primary me-2'>اختبار الموقع</a>
                <a href='../admin/index.php' class='btn btn-success'>اختبار لوحة التحكم</a>
            </div>
          </div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>
            <h4>❌ خطأ في الإصلاح</h4>
            <p>حدث خطأ: " . $e->getMessage() . "</p>
          </div>";
}

echo "        </div>
        </div>
    </div>
</body>
</html>";
?>

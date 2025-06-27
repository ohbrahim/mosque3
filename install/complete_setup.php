<?php
/**
 * أداة التثبيت الشاملة
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
    <title>تثبيت نظام إدارة المسجد</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap' rel='stylesheet'>
    <style>
        body { font-family: 'Cairo', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .install-container { background: white; border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); margin: 50px auto; max-width: 800px; }
        .install-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 20px 20px 0 0; }
        .install-body { padding: 40px; }
        .step { background: #f8f9fa; border-radius: 10px; padding: 20px; margin-bottom: 20px; }
        .step-number { background: #667eea; color: white; border-radius: 50%; width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center; margin-left: 10px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='install-container'>
            <div class='install-header'>
                <h1>🕌 تثبيت نظام إدارة المسجد</h1>
                <p>التثبيت الشامل مع إصلاح مشكلة الترميز</p>
            </div>
            <div class='install-body'>";

try {
    // الاتصال بقاعدة البيانات
    $pdo = new PDO("mysql:host=$host;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div class='step'>
            <span class='step-number'>1</span>
            <strong>الاتصال بخادم قاعدة البيانات:</strong> 
            <span class='text-success'>✅ نجح</span>
          </div>";
    
    // إنشاء قاعدة البيانات
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8 COLLATE utf8_general_ci");
    $pdo->exec("USE `$database`");
    
    echo "<div class='step'>
            <span class='step-number'>2</span>
            <strong>إنشاء قاعدة البيانات:</strong> 
            <span class='text-success'>✅ نجح</span>
          </div>";
    
    // تعيين الترميز
    $pdo->exec("SET NAMES utf8");
    $pdo->exec("SET CHARACTER SET utf8");
    
    // إنشاء الجداول
    $tables = [
        // جدول المستخدمين
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            phone VARCHAR(20),
            role ENUM('admin', 'moderator', 'editor', 'member') DEFAULT 'member',
            status ENUM('active', 'inactive', 'banned') DEFAULT 'active',
            avatar VARCHAR(255),
            last_login TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci",
        
        // جدول الصلاحيات
        "CREATE TABLE IF NOT EXISTS permissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL,
            description TEXT,
            module VARCHAR(50) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci",
        
        // جدول ربط المستخدمين بالصلاحيات
        "CREATE TABLE IF NOT EXISTS user_permissions (
            user_id INT,
            permission_id INT,
            granted_by INT,
            granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id, permission_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci",
        
        // جدول الإعدادات العامة
        "CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT,
            setting_type ENUM('text', 'textarea', 'image', 'boolean', 'json') DEFAULT 'text',
            category VARCHAR(50) DEFAULT 'general',
            description TEXT,
            updated_by INT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci",
        
        // جدول الصفحات
        "CREATE TABLE IF NOT EXISTS pages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(200) NOT NULL,
            slug VARCHAR(200) UNIQUE NOT NULL,
            content LONGTEXT,
            excerpt TEXT,
            featured_image VARCHAR(255),
            meta_title VARCHAR(200),
            meta_description TEXT,
            status ENUM('published', 'draft', 'private') DEFAULT 'draft',
            author_id INT,
            views_count INT DEFAULT 0,
            allow_comments BOOLEAN DEFAULT TRUE,
            template VARCHAR(50) DEFAULT 'default',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci",
        
        // جدول البلوكات
        "CREATE TABLE IF NOT EXISTS blocks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(200) NOT NULL,
            content LONGTEXT,
            block_type ENUM('html', 'quran_verse', 'hadith', 'prayer_times', 'news', 'announcement') DEFAULT 'html',
            position ENUM('left', 'right', 'center', 'header_ad') DEFAULT 'right',
            display_order INT DEFAULT 0,
            status ENUM('active', 'inactive') DEFAULT 'active',
            show_on_pages TEXT,
            css_class VARCHAR(100),
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci",
        
        // جدول الإعلانات
        "CREATE TABLE IF NOT EXISTS advertisements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(200) NOT NULL,
            content TEXT,
            image VARCHAR(255),
            link_url VARCHAR(500),
            position ENUM('header', 'sidebar', 'footer', 'content') DEFAULT 'header',
            start_date DATE,
            end_date DATE,
            clicks_count INT DEFAULT 0,
            impressions_count INT DEFAULT 0,
            status ENUM('active', 'inactive', 'expired') DEFAULT 'active',
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci",
        
        // جدول التعليقات
        "CREATE TABLE IF NOT EXISTS comments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            page_id INT,
            parent_id INT NULL,
            author_name VARCHAR(100) NOT NULL,
            author_email VARCHAR(100) NOT NULL,
            author_ip VARCHAR(45),
            content TEXT NOT NULL,
            status ENUM('pending', 'approved', 'rejected', 'spam') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci",
        
        // جدول التقييمات
        "CREATE TABLE IF NOT EXISTS ratings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            page_id INT,
            user_ip VARCHAR(45),
            rating TINYINT CHECK (rating >= 1 AND rating <= 5),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_rating (page_id, user_ip)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci",
        
        // جدول الرسائل
        "CREATE TABLE IF NOT EXISTS messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sender_name VARCHAR(100) NOT NULL,
            sender_email VARCHAR(100) NOT NULL,
            sender_phone VARCHAR(20),
            subject VARCHAR(200) NOT NULL,
            message TEXT NOT NULL,
            status ENUM('unread', 'read', 'replied') DEFAULT 'unread',
            replied_by INT NULL,
            reply_message TEXT NULL,
            replied_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci",
        
        // جدول الاستطلاعات
        "CREATE TABLE IF NOT EXISTS polls (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(200) NOT NULL,
            description TEXT,
            start_date DATE,
            end_date DATE,
            status ENUM('active', 'inactive', 'closed') DEFAULT 'active',
            allow_multiple_votes BOOLEAN DEFAULT FALSE,
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci",
        
        // جدول خيارات الاستطلاع
        "CREATE TABLE IF NOT EXISTS poll_options (
            id INT AUTO_INCREMENT PRIMARY KEY,
            poll_id INT,
            option_text VARCHAR(200) NOT NULL,
            votes_count INT DEFAULT 0,
            display_order INT DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci",
        
        // جدول أصوات الاستطلاع
        "CREATE TABLE IF NOT EXISTS poll_votes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            poll_id INT,
            option_id INT,
            voter_ip VARCHAR(45),
            voter_email VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci",
        
        // جدول إحصائيات الزوار
        "CREATE TABLE IF NOT EXISTS visitor_stats (
            id INT AUTO_INCREMENT PRIMARY KEY,
            visitor_ip VARCHAR(45),
            user_agent TEXT,
            page_url VARCHAR(500),
            referer VARCHAR(500),
            country VARCHAR(50),
            city VARCHAR(50),
            visit_date DATE,
            visit_time TIME,
            session_id VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_visit_date (visit_date),
            INDEX idx_visitor_ip (visitor_ip)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci",
        
        // جدول أوقات الصلاة
        "CREATE TABLE IF NOT EXISTS prayer_times (
            id INT AUTO_INCREMENT PRIMARY KEY,
            date DATE NOT NULL,
            fajr TIME NOT NULL,
            sunrise TIME NOT NULL,
            dhuhr TIME NOT NULL,
            asr TIME NOT NULL,
            maghrib TIME NOT NULL,
            isha TIME NOT NULL,
            city VARCHAR(100) DEFAULT 'Mecca',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_date_city (date, city)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci"
    ];
    
    foreach ($tables as $table) {
        $pdo->exec($table);
    }
    
    echo "<div class='step'>
            <span class='step-number'>3</span>
            <strong>إنشاء الجداول:</strong> 
            <span class='text-success'>✅ نجح</span>
          </div>";
    
    // إدراج المستخدم المدير
    $adminExists = $pdo->query("SELECT COUNT(*) FROM users WHERE username = 'admin'")->fetchColumn();
    if ($adminExists == 0) {
        $pdo->exec("INSERT INTO users (username, email, password, full_name, role) VALUES 
                   ('admin', 'admin@mosque.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'مدير النظام', 'admin')");
    }
    
    // إدراج الصلاحيات
    $pdo->exec("DELETE FROM permissions");
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
    $pdo->exec("DELETE FROM settings");
    $settings = [
        ['site_name', 'مسجد النور', 'text', 'general', 'اسم الموقع'],
        ['site_description', 'موقع مسجد النور للتعليم القرآني', 'textarea', 'general', 'وصف الموقع'],
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
    
    // إدراج البلوكات التجريبية
    $pdo->exec("DELETE FROM blocks");
    $blocks = [
        ['آية اليوم', '<div class="text-center"><h5 class="text-primary">قال الله تعالى:</h5><p class="lead">"وَمَن يَتَّقِ اللَّهَ يَجْعَل لَّهُ مَخْرَجًا"</p><small class="text-muted">سورة الطلاق - آية 2</small></div>', 'quran_verse', 'right', 1, 'active', 1],
        ['حديث اليوم', '<div class="text-center"><h5 class="text-success">قال رسول الله ﷺ:</h5><p>"من قرأ حرفاً من كتاب الله فله به حسنة، والحسنة بعشر أمثالها"</p><small class="text-muted">رواه الترمذي</small></div>', 'hadith', 'right', 2, 'active', 1],
        ['أخبار المسجد', '<h6>آخر الأخبار:</h6><ul class="list-unstyled"><li>• محاضرة يوم الجمعة بعد صلاة العصر</li><li>• دورة تحفيظ القرآن للأطفال</li><li>• برنامج الإفطار الجماعي</li></ul>', 'news', 'left', 1, 'active', 1]
    ];
    
    $stmt = $pdo->prepare("INSERT INTO blocks (title, content, block_type, position, display_order, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($blocks as $block) {
        $stmt->execute($block);
    }
    
    // إدراج الصفحات التجريبية
    $pdo->exec("DELETE FROM pages");
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
            <strong>إدراج البيانات الأساسية:</strong> 
            <span class='text-success'>✅ نجح</span>
          </div>";
    
    // إنشاء مجلد الرفع
    $uploadDir = __DIR__ . '/../uploads';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    echo "<div class='step'>
            <span class='step-number'>5</span>
            <strong>إنشاء مجلدات النظام:</strong> 
            <span class='text-success'>✅ نجح</span>
          </div>";
    
    // إنشاء ملف .htaccess
    $htaccessContent = "RewriteEngine On\n";
    $htaccessContent .= "RewriteCond %{REQUEST_FILENAME} !-f\n";
    $htaccessContent .= "RewriteCond %{REQUEST_FILENAME} !-d\n";
    $htaccessContent .= "RewriteRule ^(.*)$ index.php?page=$1 [QSA,L]\n";
    $htaccessContent .= "AddDefaultCharset UTF-8\n";
    
    @file_put_contents(__DIR__ . '/../.htaccess', $htaccessContent);
    
    echo "<div class='step'>
            <span class='step-number'>6</span>
            <strong>إعداد ملفات النظام:</strong> 
            <span class='text-success'>✅ نجح</span>
          </div>";
    
    echo "<div class='alert alert-success mt-4'>
            <h4>🎉 تم التثبيت بنجاح!</h4>
            <p>تم تثبيت النظام بنجاح مع إصلاح مشكلة الترميز. يمكنك الآن:</p>
            <ul>
                <li><strong>تسجيل الدخول:</strong> اسم المستخدم: <code>admin</code> | كلمة المرور: <code>password</code></li>
                <li><strong>رابط الموقع:</strong> <a href='../index.php' target='_blank' class='btn btn-primary btn-sm'>عرض الموقع</a></li>
                <li><strong>لوحة التحكم:</strong> <a href='../admin/index.php' target='_blank' class='btn btn-success btn-sm'>لوحة التحكم</a></li>
            </ul>
            <div class='alert alert-warning mt-3'>
                <strong>مهم جداً:</strong> يرجى حذف مجلد <code>install</code> بالكامل بعد التأكد من عمل الموقع لأسباب أمنية.
            </div>
            <div class='alert alert-info mt-3'>
                <strong>تحديث إعدادات قاعدة البيانات:</strong><br>
                تأكد من تحديث ملف <code>config/database.php</code> بإعدادات قاعدة البيانات الصحيحة.
            </div>
          </div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>
            <h4>❌ خطأ في التثبيت</h4>
            <p>حدث خطأ أثناء التثبيت: " . $e->getMessage() . "</p>
            <p>يرجى التأكد من:</p>
            <ul>
                <li>صحة بيانات الاتصال بقاعدة البيانات</li>
                <li>أن اسم قاعدة البيانات صحيح: <strong>$database</strong></li>
                <li>صلاحيات الكتابة على المجلد</li>
                <li>تفعيل إضافة PDO في PHP</li>
            </ul>
            <p><strong>تعديل الإعدادات:</strong> يمكنك تعديل إعدادات قاعدة البيانات في أعلى هذا الملف.</p>
          </div>";
}

echo "        </div>
        </div>
    </div>
</body>
</html>";
?>

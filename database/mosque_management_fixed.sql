-- قاعدة بيانات نظام إدارة المسجد أو المدرسة القرآنية
-- يجب إنشاء قاعدة البيانات أولاً من phpMyAdmin ثم تشغيل هذا الملف

-- جدول المستخدمين
CREATE TABLE users (
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
);

-- جدول الصلاحيات
CREATE TABLE permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    module VARCHAR(50) NOT NULL
);

-- جدول ربط المستخدمين بالصلاحيات
CREATE TABLE user_permissions (
    user_id INT,
    permission_id INT,
    granted_by INT,
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    FOREIGN KEY (granted_by) REFERENCES users(id),
    PRIMARY KEY (user_id, permission_id)
);

-- جدول الإعدادات العامة
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('text', 'textarea', 'image', 'boolean', 'json') DEFAULT 'text',
    category VARCHAR(50) DEFAULT 'general',
    description TEXT,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id)
);

-- جدول الصفحات
CREATE TABLE pages (
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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id)
);

-- جدول البلوكات
CREATE TABLE blocks (
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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- جدول الإعلانات
CREATE TABLE advertisements (
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- جدول التعليقات
CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_id INT,
    parent_id INT NULL,
    author_name VARCHAR(100) NOT NULL,
    author_email VARCHAR(100) NOT NULL,
    author_ip VARCHAR(45),
    content TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'spam') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE
);

-- جدول التقييمات
CREATE TABLE ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_id INT,
    user_ip VARCHAR(45),
    rating TINYINT CHECK (rating >= 1 AND rating <= 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
    UNIQUE KEY unique_rating (page_id, user_ip)
);

-- جدول الرسائل
CREATE TABLE messages (
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (replied_by) REFERENCES users(id)
);

-- جدول الاستطلاعات
CREATE TABLE polls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    start_date DATE,
    end_date DATE,
    status ENUM('active', 'inactive', 'closed') DEFAULT 'active',
    allow_multiple_votes BOOLEAN DEFAULT FALSE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- جدول خيارات الاستطلاع
CREATE TABLE poll_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    poll_id INT,
    option_text VARCHAR(200) NOT NULL,
    votes_count INT DEFAULT 0,
    display_order INT DEFAULT 0,
    FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE
);

-- جدول أصوات الاستطلاع
CREATE TABLE poll_votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    poll_id INT,
    option_id INT,
    voter_ip VARCHAR(45),
    voter_email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE,
    FOREIGN KEY (option_id) REFERENCES poll_options(id) ON DELETE CASCADE
);

-- جدول إحصائيات الزوار
CREATE TABLE visitor_stats (
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
    INDEX idx_visitor_ip (visitor_ip),
    INDEX idx_page_url (page_url)
);

-- جدول أوقات الصلاة
CREATE TABLE prayer_times (
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
);

-- إدراج البيانات الأساسية
INSERT INTO users (username, email, password, full_name, role) VALUES 
('admin', 'admin@mosque.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'مدير النظام', 'admin');

INSERT INTO permissions (name, description, module) VALUES 
('manage_users', 'إدارة المستخدمين', 'users'),
('manage_pages', 'إدارة الصفحات', 'pages'),
('manage_blocks', 'إدارة البلوكات', 'blocks'),
('manage_ads', 'إدارة الإعلانات', 'advertisements'),
('manage_comments', 'إدارة التعليقات', 'comments'),
('manage_messages', 'إدارة الرسائل', 'messages'),
('manage_polls', 'إدارة الاستطلاعات', 'polls'),
('view_stats', 'عرض الإحصائيات', 'statistics'),
('manage_settings', 'إدارة الإعدادات', 'settings');

INSERT INTO settings (setting_key, setting_value, setting_type, category, description) VALUES 
('site_name', 'مسجد النور', 'text', 'general', 'اسم الموقع'),
('site_description', 'موقع مسجد النور للتعليم القرآني', 'textarea', 'general', 'وصف الموقع'),
('site_logo', '', 'image', 'general', 'شعار الموقع'),
('contact_email', 'info@mosque.com', 'text', 'contact', 'بريد التواصل'),
('contact_phone', '+966123456789', 'text', 'contact', 'هاتف التواصل'),
('contact_address', 'الرياض، المملكة العربية السعودية', 'textarea', 'contact', 'عنوان المسجد'),
('prayer_city', 'Riyadh', 'text', 'prayer', 'مدينة أوقات الصلاة'),
('enable_comments', '1', 'boolean', 'features', 'تفعيل التعليقات'),
('enable_ratings', '1', 'boolean', 'features', 'تفعيل التقييمات'),
('items_per_page', '10', 'text', 'general', 'عدد العناصر في الصفحة');

-- إدراج بعض البلوكات التجريبية
INSERT INTO blocks (title, content, block_type, position, display_order, status, created_by) VALUES 
('آية اليوم', '<div class="text-center"><h5 class="text-primary">قال الله تعالى:</h5><p class="lead">"وَمَن يَتَّقِ اللَّهَ يَجْعَل لَّهُ مَخْرَجًا"</p><small class="text-muted">سورة الطلاق - آية 2</small></div>', 'quran_verse', 'right', 1, 'active', 1),
('حديث اليوم', '<div class="text-center"><h5 class="text-success">قال رسول الله ﷺ:</h5><p>"من قرأ حرفاً من كتاب الله فله به حسنة، والحسنة بعشر أمثالها"</p><small class="text-muted">رواه الترمذي</small></div>', 'hadith', 'right', 2, 'active', 1),
('أخبار المسجد', '<h6>آخر الأخبار:</h6><ul class="list-unstyled"><li>• محاضرة يوم الجمعة بعد صلاة العصر</li><li>• دورة تحفيظ القرآن للأطفال</li><li>• برنامج الإفطار الجماعي</li></ul>', 'news', 'left', 1, 'active', 1);

-- إدراج صفحات تجريبية
INSERT INTO pages (title, slug, content, excerpt, status, author_id, allow_comments) VALUES 
('عن المسجد', 'about', '<h2>مرحباً بكم في مسجد النور</h2><p>مسجد النور هو منارة للعلم والتعليم القرآني في قلب المدينة. نسعى لخدمة المجتمع المسلم وتقديم أفضل البرامج التعليمية والدعوية.</p><h3>رؤيتنا</h3><p>أن نكون مركزاً رائداً في التعليم القرآني وخدمة المجتمع المسلم.</p><h3>رسالتنا</h3><p>تقديم تعليم قرآني متميز وبرامج دعوية هادفة لجميع أفراد المجتمع.</p>', 'تعرف على مسجد النور ورؤيته ورسالته في خدمة المجتمع المسلم', 'published', 1, 1),
('البرامج والأنشطة', 'programs', '<h2>برامجنا وأنشطتنا</h2><h3>برامج التحفيظ</h3><ul><li>حلقات تحفيظ القرآن للأطفال</li><li>دورات التجويد للكبار</li><li>مسابقات قرآنية شهرية</li></ul><h3>البرامج التعليمية</h3><ul><li>دروس في الفقه والعقيدة</li><li>محاضرات أسبوعية</li><li>ورش تدريبية</li></ul><h3>الأنشطة الاجتماعية</h3><ul><li>إفطار جماعي في رمضان</li><li>زيارات للمرضى</li><li>مساعدة الأسر المحتاجة</li></ul>', 'تعرف على جميع البرامج والأنشطة التي يقدمها المسجد', 'published', 1, 1),
('اتصل بنا', 'contact', '<h2>تواصل معنا</h2><p>نحن سعداء لتواصلكم معنا في أي وقت. يمكنكم الوصول إلينا من خلال:</p><div class="row"><div class="col-md-6"><h4>معلومات التواصل</h4><p><strong>العنوان:</strong> الرياض، المملكة العربية السعودية</p><p><strong>الهاتف:</strong> +966123456789</p><p><strong>البريد الإلكتروني:</strong> info@mosque.com</p></div><div class="col-md-6"><h4>أوقات العمل</h4><p><strong>السبت - الخميس:</strong> 6:00 ص - 10:00 م</p><p><strong>الجمعة:</strong> 6:00 ص - 12:00 م، 2:00 م - 10:00 م</p></div></div>', 'معلومات التواصل مع إدارة المسجد', 'published', 1, 1);

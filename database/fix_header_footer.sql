-- إصلاح وتحديث جداول الهيدر والفوتر

-- التأكد من وجود جدول menu_items
CREATE TABLE IF NOT EXISTS `menu_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `url` varchar(500) NOT NULL,
  `icon` varchar(100) DEFAULT NULL,
  `menu_position` enum('header','footer','sidebar') NOT NULL DEFAULT 'header',
  `parent_id` int(11) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `target` enum('_self','_blank') DEFAULT '_self',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  KEY `menu_position` (`menu_position`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- التأكد من وجود جدول header_footer_content
CREATE TABLE IF NOT EXISTS `header_footer_content` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `section` enum('header','footer') NOT NULL,
  `content_type` enum('text','html','widget') NOT NULL DEFAULT 'text',
  `title` varchar(255) DEFAULT NULL,
  `content` text,
  `position` enum('left','center','right','full') DEFAULT 'left',
  `display_order` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `section` (`section`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إدراج عناصر القائمة الافتراضية للهيدر
INSERT IGNORE INTO `menu_items` (`id`, `title`, `url`, `icon`, `menu_position`, `parent_id`, `display_order`, `target`, `status`) VALUES
(1, 'الرئيسية', 'index.php', 'fas fa-home', 'header', NULL, 1, '_self', 'active'),
(2, 'عن المسجد', '?page=about', 'fas fa-mosque', 'header', NULL, 2, '_self', 'active'),
(3, 'الخدمات', '#', 'fas fa-hands-helping', 'header', NULL, 3, '_self', 'active'),
(4, 'القرآن الكريم', 'quran.php', 'fas fa-book-quran', 'header', NULL, 4, '_self', 'active'),
(5, 'المكتبة', 'library.php', 'fas fa-book', 'header', NULL, 5, '_self', 'active'),
(6, 'التبرعات', 'donations.php', 'fas fa-hand-holding-heart', 'header', NULL, 6, '_self', 'active'),
(7, 'اتصل بنا', 'contact.php', 'fas fa-envelope', 'header', NULL, 7, '_self', 'active');

-- إدراج عناصر فرعية للخدمات
INSERT IGNORE INTO `menu_items` (`id`, `title`, `url`, `icon`, `menu_position`, `parent_id`, `display_order`, `target`, `status`) VALUES
(8, 'أوقات الصلاة', '?page=prayer-times', 'fas fa-clock', 'header', 3, 1, '_self', 'active'),
(9, 'حاسبة الزكاة', '?page=zakat', 'fas fa-calculator', 'header', 3, 2, '_self', 'active'),
(10, 'الدروس والمحاضرات', '?page=lessons', 'fas fa-chalkboard-teacher', 'header', 3, 3, '_self', 'active'),
(11, 'الأنشطة', '?page=activities', 'fas fa-calendar-alt', 'header', 3, 4, '_self', 'active');

-- إدراج عناصر قائمة الفوتر
INSERT IGNORE INTO `menu_items` (`id`, `title`, `url`, `icon`, `menu_position`, `parent_id`, `display_order`, `target`, `status`) VALUES
(12, 'سياسة الخصوصية', '?page=privacy', '', 'footer', NULL, 1, '_self', 'active'),
(13, 'شروط الاستخدام', '?page=terms', '', 'footer', NULL, 2, '_self', 'active'),
(14, 'خريطة الموقع', '?page=sitemap', '', 'footer', NULL, 3, '_self', 'active'),
(15, 'الأسئلة الشائعة', '?page=faq', '', 'footer', NULL, 4, '_self', 'active');

-- إدراج إعدادات افتراضية
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`, `category`, `setting_type`, `description`) VALUES
('site_name', 'مسجد النور', 'general', 'text', 'اسم الموقع'),
('site_description', 'مسجد النور - مكان للعبادة والتعلم والتواصل المجتمعي', 'general', 'textarea', 'وصف الموقع'),
('contact_email', 'info@mosque-nour.com', 'contact', 'email', 'البريد الإلكتروني'),
('contact_phone', '+213 123 456 789', 'contact', 'text', 'رقم الهاتف'),
('contact_address', 'الجزائر العاصمة، الجزائر', 'contact', 'textarea', 'العنوان'),
('social_facebook', 'https://facebook.com/mosque-nour', 'social', 'url', 'رابط فيسبوك'),
('social_twitter', 'https://twitter.com/mosque_nour', 'social', 'url', 'رابط تويتر'),
('social_instagram', 'https://instagram.com/mosque_nour', 'social', 'url', 'رابط انستغرام'),
('social_youtube', 'https://youtube.com/mosque-nour', 'social', 'url', 'رابط يوتيوب'),
('header_style', 'modern', 'appearance', 'select', 'نمط الهيدر'),
('footer_style', 'modern', 'appearance', 'select', 'نمط الفوتر'),
('header_bg_color', '#667eea', 'appearance', 'color', 'لون خلفية الهيدر'),
('header_text_color', '#ffffff', 'appearance', 'color', 'لون نص الهيدر'),
('footer_bg_color', '#2c3e50', 'appearance', 'color', 'لون خلفية الفوتر'),
('footer_text_color', '#ffffff', 'appearance', 'color', 'لون نص الفوتر'),
('footer_copyright', 'جميع الحقوق محفوظة', 'general', 'text', 'نص حقوق الطبع'),
('show_social_links', '1', 'appearance', 'checkbox', 'عرض روابط التواصل الاجتماعي'),
('show_search_box', '1', 'appearance', 'checkbox', 'عرض مربع البحث'),
('header_fixed', '1', 'appearance', 'checkbox', 'هيدر ثابت'),
('header_transparent', '0', 'appearance', 'checkbox', 'هيدر شفاف');

-- إدراج محتوى تجريبي للفوتر
INSERT IGNORE INTO `header_footer_content` (`section`, `content_type`, `title`, `content`, `position`, `display_order`, `status`) VALUES
('footer', 'widget', 'معلومات التواصل', 'contact_info', 'left', 1, 'active'),
('footer', 'html', 'روابط مفيدة', '<ul class="list-unstyled"><li><a href="?page=about">عن المسجد</a></li><li><a href="?page=programs">البرامج</a></li><li><a href="?page=events">الفعاليات</a></li></ul>', 'center', 2, 'active'),
('footer', 'text', 'رسالة المسجد', 'نسعى لخدمة المجتمع المسلم وتقديم بيئة مناسبة للعبادة والتعلم', 'right', 3, 'active');

-- إضافة جداول الهيدر والفوتر وقائمة التنقل

-- جدول عناصر القائمة
CREATE TABLE IF NOT EXISTS `menu_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `url` varchar(500) NOT NULL,
  `target` enum('_self','_blank') DEFAULT '_self',
  `icon` varchar(100) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `menu_position` enum('header','footer','sidebar') DEFAULT 'header',
  `display_order` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  KEY `menu_position` (`menu_position`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إدراج عناصر القائمة الافتراضية
INSERT INTO `menu_items` (`title`, `url`, `target`, `icon`, `parent_id`, `menu_position`, `display_order`, `status`) VALUES
('الرئيسية', '/', '_self', 'fas fa-home', NULL, 'header', 1, 'active'),
('عن المسجد', '?page=about', '_self', 'fas fa-info-circle', NULL, 'header', 2, 'active'),
('البرامج والأنشطة', '#', '_self', 'fas fa-calendar', NULL, 'header', 3, 'active'),
('الدروس والمحاضرات', '?page=lessons', '_self', 'fas fa-book', 3, 'header', 1, 'active'),
('الدورات الشرعية', '?page=courses', '_self', 'fas fa-graduation-cap', 3, 'header', 2, 'active'),
('المسابقات', '?page=competitions', '_self', 'fas fa-trophy', 3, 'header', 3, 'active'),
('الخدمات', '#', '_self', 'fas fa-hands-helping', NULL, 'header', 4, 'active'),
('القرآن الكريم', 'quran.php', '_self', 'fas fa-book-quran', 7, 'header', 1, 'active'),
('المكتبة الإسلامية', 'library.php', '_self', 'fas fa-book-open', 7, 'header', 2, 'active'),
('حاسبة الزكاة', 'zakat.php', '_self', 'fas fa-calculator', 7, 'header', 3, 'active'),
('التبرعات', 'donations.php', '_self', 'fas fa-donate', 7, 'header', 4, 'active'),
('اتصل بنا', 'contact.php', '_self', 'fas fa-envelope', NULL, 'header', 5, 'active'),

-- عناصر الفوتر
('الرئيسية', '/', '_self', NULL, NULL, 'footer', 1, 'active'),
('عن المسجد', '?page=about', '_self', NULL, NULL, 'footer', 2, 'active'),
('سياسة الخصوصية', '?page=privacy', '_self', NULL, NULL, 'footer', 3, 'active'),
('شروط الاستخدام', '?page=terms', '_self', NULL, NULL, 'footer', 4, 'active'),
('اتصل بنا', 'contact.php', '_self', NULL, NULL, 'footer', 5, 'active');

-- جدول محتوى الهيدر والفوتر
CREATE TABLE IF NOT EXISTS `header_footer_content` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `section` enum('header','footer') NOT NULL,
  `content_type` enum('text','html','widget') NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `content` text,
  `position` enum('left','center','right','full') DEFAULT 'full',
  `display_order` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إدراج محتوى افتراضي للفوتر
INSERT INTO `header_footer_content` (`section`, `content_type`, `title`, `content`, `position`, `display_order`, `status`) VALUES
('footer', 'html', 'معلومات المسجد', '<h5>مسجد النور</h5><p>مسجد يخدم المجتمع المسلم ويقدم الخدمات الدينية والتعليمية</p>', 'left', 1, 'active'),
('footer', 'html', 'معلومات التواصل', '<h5>تواصل معنا</h5><p><i class="fas fa-phone"></i> +966123456789</p><p><i class="fas fa-envelope"></i> info@mosque.com</p><p><i class="fas fa-map-marker-alt"></i> الرياض، المملكة العربية السعودية</p>', 'center', 2, 'active'),
('footer', 'widget', 'أوقات الصلاة', 'prayer_times', 'right', 3, 'active');

-- إضافة إعدادات جديدة للهيدر والفوتر
INSERT INTO `settings` (`setting_key`, `setting_value`, `category`, `setting_type`, `description`) VALUES
('header_style', 'modern', 'design', 'select', 'نمط الهيدر'),
('header_fixed', '1', 'design', 'checkbox', 'هيدر ثابت'),
('header_transparent', '0', 'design', 'checkbox', 'هيدر شفاف'),
('show_search_box', '1', 'design', 'checkbox', 'عرض مربع البحث'),
('footer_style', 'modern', 'design', 'select', 'نمط الفوتر'),
('footer_copyright', 'جميع الحقوق محفوظة', 'general', 'text', 'نص حقوق الطبع'),
('show_social_links', '1', 'design', 'checkbox', 'عرض روابط التواصل الاجتماعي');

-- إصلاح جدول الإعدادات
ALTER TABLE `settings` 
ADD COLUMN IF NOT EXISTS `setting_group` varchar(50) DEFAULT 'عام' AFTER `setting_key`,
ADD COLUMN IF NOT EXISTS `display_order` int(11) DEFAULT 0 AFTER `setting_group`,
ADD COLUMN IF NOT EXISTS `setting_type` enum('text','textarea','number','email','url','color','select','boolean','file') DEFAULT 'text' AFTER `display_order`,
ADD COLUMN IF NOT EXISTS `setting_options` text DEFAULT NULL AFTER `setting_type`,
ADD COLUMN IF NOT EXISTS `display_name` varchar(100) DEFAULT NULL AFTER `setting_options`,
ADD COLUMN IF NOT EXISTS `description` text DEFAULT NULL AFTER `display_name`,
ADD COLUMN IF NOT EXISTS `is_required` tinyint(1) DEFAULT 0 AFTER `description`;

-- تحديث الإعدادات الموجودة
UPDATE `settings` SET 
    `setting_group` = 'عام',
    `display_name` = CASE 
        WHEN `setting_key` = 'site_name' THEN 'اسم الموقع'
        WHEN `setting_key` = 'site_description' THEN 'وصف الموقع'
        WHEN `setting_key` = 'site_keywords' THEN 'الكلمات المفتاحية'
        WHEN `setting_key` = 'admin_email' THEN 'بريد المدير'
        WHEN `setting_key` = 'site_logo' THEN 'شعار الموقع'
        WHEN `setting_key` = 'site_favicon' THEN 'أيقونة الموقع'
        WHEN `setting_key` = 'maintenance_mode' THEN 'وضع الصيانة'
        WHEN `setting_key` = 'auto_approve_comments' THEN 'الموافقة التلقائية على التعليقات'
        ELSE `setting_key`
    END,
    `setting_type` = CASE 
        WHEN `setting_key` IN ('site_name', 'admin_email') THEN 'text'
        WHEN `setting_key` IN ('site_description', 'site_keywords') THEN 'textarea'
        WHEN `setting_key` IN ('site_logo', 'site_favicon') THEN 'file'
        WHEN `setting_key` IN ('maintenance_mode', 'auto_approve_comments') THEN 'boolean'
        ELSE 'text'
    END,
    `is_required` = CASE 
        WHEN `setting_key` IN ('site_name', 'admin_email') THEN 1
        ELSE 0
    END
WHERE `display_name` IS NULL;

-- إضافة إعدادات إضافية
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`, `setting_group`, `display_name`, `setting_type`, `description`, `display_order`) VALUES
('site_theme_color', '#007bff', 'التصميم', 'لون الموقع الأساسي', 'color', 'اللون الأساسي المستخدم في تصميم الموقع', 1),
('posts_per_page', '10', 'عام', 'عدد المقالات في الصفحة', 'number', 'عدد المقالات التي تظهر في كل صفحة', 2),
('enable_comments', '1', 'التعليقات', 'تفعيل التعليقات', 'boolean', 'السماح بالتعليقات على الصفحات', 1),
('enable_ratings', '1', 'التقييمات', 'تفعيل التقييمات', 'boolean', 'السماح بتقييم الصفحات', 1),
('contact_phone', '', 'الاتصال', 'رقم الهاتف', 'text', 'رقم هاتف المسجد', 1),
('contact_address', '', 'الاتصال', 'العنوان', 'textarea', 'عنوان المسجد', 2),
('prayer_times_api', '', 'أوقات الصلاة', 'API أوقات الصلاة', 'url', 'رابط API لجلب أوقات الصلاة', 1),
('weather_api_key', '', 'الطقس', 'مفتاح API الطقس', 'text', 'مفتاح API لجلب بيانات الطقس', 1);

-- إصلاح جدول الاستطلاعات
ALTER TABLE `polls` 
ADD COLUMN IF NOT EXISTS `created_by` int(11) DEFAULT NULL AFTER `end_date`,
ADD COLUMN IF NOT EXISTS `updated_by` int(11) DEFAULT NULL AFTER `created_by`;

-- إصلاح جدول أصوات الاستطلاعات
ALTER TABLE `poll_votes` 
ADD COLUMN IF NOT EXISTS `created_at` datetime DEFAULT CURRENT_TIMESTAMP AFTER `user_ip`;

-- إصلاح جدول التعليقات
ALTER TABLE `comments` 
ADD COLUMN IF NOT EXISTS `ip_address` varchar(45) DEFAULT NULL AFTER `author_email`;

-- إصلاح جدول التقييمات
CREATE TABLE IF NOT EXISTS `ratings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_ip` varchar(45) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `page_id` (`page_id`),
  KEY `user_id` (`user_id`),
  UNIQUE KEY `unique_user_rating` (`page_id`, `user_id`),
  UNIQUE KEY `unique_ip_rating` (`page_id`, `user_ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إضافة جدول سجل الأنشطة
CREATE TABLE IF NOT EXISTS `activity_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

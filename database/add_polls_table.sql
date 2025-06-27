-- إنشاء جدول الاستطلاعات
CREATE TABLE IF NOT EXISTS `polls` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question` varchar(500) NOT NULL,
  `options` text NOT NULL,
  `votes` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_by` (`created_by`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إضافة بيانات تجريبية
INSERT INTO `polls` (`question`, `options`, `status`, `created_by`) VALUES
('ما رأيك في خدمات المسجد؟', '["ممتازة", "جيدة", "تحتاج تحسين", "ضعيفة"]', 'active', 1),
('أي الأنشطة تفضل أكثر؟', '["الدروس الدينية", "الأنشطة الثقافية", "الأنشطة الاجتماعية", "الأنشطة الرياضية"]', 'active', 1);

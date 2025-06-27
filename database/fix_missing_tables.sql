-- إصلاح جدول الاستطلاعات
ALTER TABLE polls 
ADD COLUMN IF NOT EXISTS allow_multiple_votes TINYINT(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS start_date DATE NULL,
ADD COLUMN IF NOT EXISTS end_date DATE NULL,
ADD COLUMN IF NOT EXISTS description TEXT NULL;

-- إنشاء جدول خيارات الاستطلاعات
CREATE TABLE IF NOT EXISTS poll_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    poll_id INT NOT NULL,
    option_text VARCHAR(255) NOT NULL,
    display_order INT DEFAULT 0,
    votes_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE
);

-- إنشاء جدول أصوات الاستطلاعات
CREATE TABLE IF NOT EXISTS poll_votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    poll_id INT NOT NULL,
    option_id INT NOT NULL,
    user_id INT NULL,
    voter_ip VARCHAR(45) NOT NULL,
    voted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE,
    FOREIGN KEY (option_id) REFERENCES poll_options(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- إضافة فهارس للأداء
CREATE INDEX IF NOT EXISTS idx_poll_votes_poll_id ON poll_votes(poll_id);
CREATE INDEX IF NOT EXISTS idx_poll_votes_option_id ON poll_votes(option_id);
CREATE INDEX IF NOT EXISTS idx_poll_options_poll_id ON poll_options(poll_id);

-- تحديث عدد الأصوات في خيارات الاستطلاعات
UPDATE poll_options po 
SET votes_count = (
    SELECT COUNT(*) 
    FROM poll_votes pv 
    WHERE pv.option_id = po.id
);

-- إضافة بيانات تجريبية للاستطلاعات إذا لم تكن موجودة
INSERT IGNORE INTO polls (id, title, description, status, created_by, created_at) VALUES
(1, 'ما رأيك في خدمات المسجد؟', 'نود معرفة رأيكم في الخدمات المقدمة في المسجد', 'active', 1, NOW()),
(2, 'أي الأنشطة تفضل أكثر؟', 'ساعدونا في اختيار الأنشطة المناسبة', 'active', 1, NOW());

-- إضافة خيارات للاستطلاع الأول
INSERT IGNORE INTO poll_options (poll_id, option_text, display_order) VALUES
(1, 'ممتازة', 1),
(1, 'جيدة', 2),
(1, 'تحتاج تحسين', 3),
(1, 'ضعيفة', 4);

-- إضافة خيارات للاستطلاع الثاني
INSERT IGNORE INTO poll_options (poll_id, option_text, display_order) VALUES
(2, 'الدروس الدينية', 1),
(2, 'الأنشطة الثقافية', 2),
(2, 'الأنشطة الاجتماعية', 3),
(2, 'الأنشطة الرياضية', 4);

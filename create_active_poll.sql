-- إنشاء استطلاع نشط جديد
-- تنفيذ هذا الملف في phpMyAdmin أو MySQL

-- تعطيل جميع الاستطلاعات الموجودة
UPDATE polls SET status = 'inactive';

-- إنشاء استطلاع جديد نشط
INSERT INTO polls (title, description, question, poll_type, status, created_at) 
VALUES (
    'استطلاع رأي الزوار',
    'نرحب بآرائكم وتقييمكم لموقع المسجد',
    'ما رأيك في موقع المسجد؟',
    'single_choice',
    'active',
    NOW()
);

-- الحصول على ID الاستطلاع الجديد (سيكون آخر ID تم إدراجه)
SET @poll_id = LAST_INSERT_ID();

-- إضافة خيارات الاستطلاع
INSERT INTO poll_options (poll_id, option_text, display_order) VALUES
(@poll_id, 'ممتاز', 1),
(@poll_id, 'جيد جداً', 2),
(@poll_id, 'جيد', 3),
(@poll_id, 'يحتاج إلى تحسين', 4);

-- التحقق من النتيجة
SELECT 'تم إنشاء الاستطلاع بنجاح' as result;
SELECT * FROM polls WHERE status = 'active';
SELECT * FROM poll_options WHERE poll_id = @poll_id;
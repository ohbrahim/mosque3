-- إضافة الأعمدة المفقودة في جدول pages
ALTER TABLE pages ADD COLUMN IF NOT EXISTS created_by INT NULL;
ALTER TABLE pages ADD COLUMN IF NOT EXISTS updated_by INT NULL;
ALTER TABLE pages ADD COLUMN IF NOT EXISTS views_count INT DEFAULT 0;

-- إضافة الأعمدة المفقودة في جدول settings
ALTER TABLE settings ADD COLUMN IF NOT EXISTS updated_by INT NULL;
ALTER TABLE settings ADD COLUMN IF NOT EXISTS created_at DATETIME NULL;
ALTER TABLE settings ADD COLUMN IF NOT EXISTS updated_at DATETIME NULL;

-- تحديث الإشارات المرجعية
ALTER TABLE pages ADD CONSTRAINT fk_pages_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE pages ADD CONSTRAINT fk_pages_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE settings ADD CONSTRAINT fk_settings_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL;

-- تحديث البيانات الموجودة
UPDATE pages SET created_by = 1 WHERE created_by IS NULL;
UPDATE settings SET updated_by = 1 WHERE updated_by IS NULL;

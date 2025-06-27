-- إصلاح جدول المستخدمين
-- أولاً، تحقق من وجود الأعمدة الجديدة وأضفها إذا لم تكن موجودة

-- إضافة الأعمدة الجديدة إذا لم تكن موجودة
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS email_verified TINYINT(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS verification_token VARCHAR(64) NULL,
ADD COLUMN IF NOT EXISTS reset_token VARCHAR(64) NULL,
ADD COLUMN IF NOT EXISTS reset_expires DATETIME NULL;

-- تحديث عمود الحالة ليشمل القيم الجديدة
ALTER TABLE users MODIFY COLUMN status ENUM('active', 'inactive', 'banned', 'pending') DEFAULT 'pending';

-- تحديث المستخدمين الحاليين
UPDATE users SET email_verified = 1 WHERE email_verified = 0 AND status = 'active';
UPDATE users SET status = 'active' WHERE status = 'banned' AND email_verified = 1;

-- إنشاء فهارس للأداء
CREATE INDEX IF NOT EXISTS idx_verification_token ON users(verification_token);
CREATE INDEX IF NOT EXISTS idx_reset_token ON users(reset_token);
CREATE INDEX IF NOT EXISTS idx_email_verified ON users(email_verified);
CREATE INDEX IF NOT EXISTS idx_status ON users(status);
CREATE INDEX IF NOT EXISTS idx_username ON users(username);
CREATE INDEX IF NOT EXISTS idx_email ON users(email);

-- تنظيف البيانات المكررة إذا وجدت
DELETE u1 FROM users u1
INNER JOIN users u2 
WHERE u1.id > u2.id 
AND u1.username = u2.username;

DELETE u1 FROM users u1
INNER JOIN users u2 
WHERE u1.id > u2.id 
AND u1.email = u2.email;
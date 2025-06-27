-- ملف إضافة الفهارس لتحسين أداء قاعدة البيانات
-- Database Performance Optimization Indexes

-- فهارس جدول المستخدمين (users)
CREATE INDEX IF NOT EXISTS idx_users_username ON users(username);
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);
CREATE INDEX IF NOT EXISTS idx_users_status ON users(status);
CREATE INDEX IF NOT EXISTS idx_users_created_at ON users(created_at);
CREATE INDEX IF NOT EXISTS idx_users_last_login ON users(last_login);

-- فهارس جدول البلوكات (blocks)
CREATE INDEX IF NOT EXISTS idx_blocks_type ON blocks(type);
CREATE INDEX IF NOT EXISTS idx_blocks_status ON blocks(status);
CREATE INDEX IF NOT EXISTS idx_blocks_position ON blocks(position);
CREATE INDEX IF NOT EXISTS idx_blocks_order_num ON blocks(order_num);
CREATE INDEX IF NOT EXISTS idx_blocks_created_at ON blocks(created_at);
CREATE INDEX IF NOT EXISTS idx_blocks_updated_at ON blocks(updated_at);
CREATE INDEX IF NOT EXISTS idx_blocks_type_status ON blocks(type, status);
CREATE INDEX IF NOT EXISTS idx_blocks_position_order ON blocks(position, order_num);

-- فهارس جدول التعليقات (comments)
CREATE INDEX IF NOT EXISTS idx_comments_block_id ON comments(block_id);
CREATE INDEX IF NOT EXISTS idx_comments_user_id ON comments(user_id);
CREATE INDEX IF NOT EXISTS idx_comments_status ON comments(status);
CREATE INDEX IF NOT EXISTS idx_comments_created_at ON comments(created_at);
CREATE INDEX IF NOT EXISTS idx_comments_parent_id ON comments(parent_id);
CREATE INDEX IF NOT EXISTS idx_comments_block_status ON comments(block_id, status);
CREATE INDEX IF NOT EXISTS idx_comments_user_created ON comments(user_id, created_at);

-- فهارس جدول الاستطلاعات (polls)
CREATE INDEX IF NOT EXISTS idx_polls_status ON polls(status);
CREATE INDEX IF NOT EXISTS idx_polls_created_at ON polls(created_at);
CREATE INDEX IF NOT EXISTS idx_polls_end_date ON polls(end_date);
CREATE INDEX IF NOT EXISTS idx_polls_status_end ON polls(status, end_date);

-- فهارس جدول خيارات الاستطلاع (poll_options)
CREATE INDEX IF NOT EXISTS idx_poll_options_poll_id ON poll_options(poll_id);
CREATE INDEX IF NOT EXISTS idx_poll_options_order_num ON poll_options(order_num);

-- فهارس جدول أصوات الاستطلاع (poll_votes)
CREATE INDEX IF NOT EXISTS idx_poll_votes_poll_id ON poll_votes(poll_id);
CREATE INDEX IF NOT EXISTS idx_poll_votes_option_id ON poll_votes(option_id);
CREATE INDEX IF NOT EXISTS idx_poll_votes_user_id ON poll_votes(user_id);
CREATE INDEX IF NOT EXISTS idx_poll_votes_ip_address ON poll_votes(ip_address);
CREATE INDEX IF NOT EXISTS idx_poll_votes_created_at ON poll_votes(created_at);
CREATE INDEX IF NOT EXISTS idx_poll_votes_poll_user ON poll_votes(poll_id, user_id);
CREATE INDEX IF NOT EXISTS idx_poll_votes_poll_ip ON poll_votes(poll_id, ip_address);

-- فهارس جدول الجلسات (sessions)
CREATE INDEX IF NOT EXISTS idx_sessions_user_id ON sessions(user_id);
CREATE INDEX IF NOT EXISTS idx_sessions_expires_at ON sessions(expires_at);
CREATE INDEX IF NOT EXISTS idx_sessions_created_at ON sessions(created_at);
CREATE INDEX IF NOT EXISTS idx_sessions_user_expires ON sessions(user_id, expires_at);

-- فهارس جدول سجل الأنشطة (activity_log)
CREATE INDEX IF NOT EXISTS idx_activity_log_user_id ON activity_log(user_id);
CREATE INDEX IF NOT EXISTS idx_activity_log_action ON activity_log(action);
CREATE INDEX IF NOT EXISTS idx_activity_log_table_name ON activity_log(table_name);
CREATE INDEX IF NOT EXISTS idx_activity_log_created_at ON activity_log(created_at);
CREATE INDEX IF NOT EXISTS idx_activity_log_user_action ON activity_log(user_id, action);
CREATE INDEX IF NOT EXISTS idx_activity_log_table_created ON activity_log(table_name, created_at);

-- فهارس جدول سجل الأخطاء (error_log)
CREATE INDEX IF NOT EXISTS idx_error_log_level ON error_log(level);
CREATE INDEX IF NOT EXISTS idx_error_log_created_at ON error_log(created_at);
CREATE INDEX IF NOT EXISTS idx_error_log_file ON error_log(file);
CREATE INDEX IF NOT EXISTS idx_error_log_level_created ON error_log(level, created_at);

-- فهارس جدول التخزين المؤقت (cache)
CREATE INDEX IF NOT EXISTS idx_cache_expires_at ON cache(expires_at);
CREATE INDEX IF NOT EXISTS idx_cache_created_at ON cache(created_at);
CREATE INDEX IF NOT EXISTS idx_cache_key_expires ON cache(cache_key, expires_at);

-- فهارس جدول النسخ الاحتياطية (backup_log)
CREATE INDEX IF NOT EXISTS idx_backup_log_type ON backup_log(type);
CREATE INDEX IF NOT EXISTS idx_backup_log_status ON backup_log(status);
CREATE INDEX IF NOT EXISTS idx_backup_log_created_at ON backup_log(created_at);
CREATE INDEX IF NOT EXISTS idx_backup_log_type_status ON backup_log(type, status);

-- فهارس جدول الإعدادات (settings)
CREATE INDEX IF NOT EXISTS idx_settings_category ON settings(category);
CREATE INDEX IF NOT EXISTS idx_settings_updated_at ON settings(updated_at);

-- فهارس جدول الصلاحيات (permissions)
CREATE INDEX IF NOT EXISTS idx_permissions_role ON permissions(role);
CREATE INDEX IF NOT EXISTS idx_permissions_resource ON permissions(resource);
CREATE INDEX IF NOT EXISTS idx_permissions_action ON permissions(action);
CREATE INDEX IF NOT EXISTS idx_permissions_role_resource ON permissions(role, resource);

-- فهارس جدول الملفات المرفوعة (uploads)
CREATE INDEX IF NOT EXISTS idx_uploads_user_id ON uploads(user_id);
CREATE INDEX IF NOT EXISTS idx_uploads_file_type ON uploads(file_type);
CREATE INDEX IF NOT EXISTS idx_uploads_created_at ON uploads(created_at);
CREATE INDEX IF NOT EXISTS idx_uploads_file_size ON uploads(file_size);
CREATE INDEX IF NOT EXISTS idx_uploads_user_type ON uploads(user_id, file_type);

-- فهارس مركبة لتحسين الاستعلامات المعقدة

-- فهرس للبحث في البلوكات النشطة حسب النوع والموقع
CREATE INDEX IF NOT EXISTS idx_blocks_active_search ON blocks(status, type, position, order_num) 
WHERE status = 'active';

-- فهرس للتعليقات المعتمدة حسب البلوك
CREATE INDEX IF NOT EXISTS idx_comments_approved_block ON comments(block_id, created_at) 
WHERE status = 'approved';

-- فهرس للاستطلاعات النشطة
CREATE INDEX IF NOT EXISTS idx_polls_active ON polls(created_at, end_date) 
WHERE status = 'active';

-- فهرس لسجل الأنشطة الحديثة
CREATE INDEX IF NOT EXISTS idx_activity_recent ON activity_log(created_at DESC, user_id, action);

-- فهرس للأخطاء الحرجة
CREATE INDEX IF NOT EXISTS idx_errors_critical ON error_log(created_at DESC, level) 
WHERE level IN ('error', 'critical');

-- فهرس للجلسات النشطة
CREATE INDEX IF NOT EXISTS idx_sessions_active ON sessions(user_id, expires_at) 
WHERE expires_at > NOW();

-- إحصائيات الجداول لتحسين الاستعلامات
ANALYZE TABLE users;
ANALYZE TABLE blocks;
ANALYZE TABLE comments;
ANALYZE TABLE polls;
ANALYZE TABLE poll_options;
ANALYZE TABLE poll_votes;
ANALYZE TABLE sessions;
ANALYZE TABLE activity_log;

-- تعليقات للمطورين
/*
ملاحظات مهمة حول الفهارس:

1. الفهارس المفردة (Single Column Indexes):
   - تحسن أداء البحث والفرز على عمود واحد
   - مفيدة للاستعلامات البسيطة

2. الفهارس المركبة (Composite Indexes):
   - تحسن أداء الاستعلامات التي تستخدم عدة أعمدة
   - ترتيب الأعمدة مهم (الأكثر انتقائية أولاً)

3. الفهارس الجزئية (Partial Indexes):
   - تحسن الأداء وتوفر المساحة
   - مفيدة للبيانات المفلترة

4. نصائح للصيانة:
   - راقب استخدام الفهارس بانتظام
   - احذف الفهارس غير المستخدمة
   - حدث إحصائيات الجداول دورياً
   - راقب حجم الفهارس وتأثيرها على الكتابة

5. مراقبة الأداء:
   - استخدم EXPLAIN لتحليل الاستعلامات
   - راقب slow query log
   - استخدم أدوات مراقبة الأداء
*/
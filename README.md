# نظام إدارة المسجد

نظام إدارة شامل للمساجد يوفر إدارة المحتوى، الاستطلاعات، التعليقات، والمزيد من الميزات.

## 📋 المحتويات

- [الميزات](#الميزات)
- [متطلبات النظام](#متطلبات-النظام)
- [التثبيت](#التثبيت)
- [الإعداد](#الإعداد)
- [هيكل المشروع](#هيكل-المشروع)
- [الاستخدام](#الاستخدام)
- [الأمان](#الأمان)
- [النسخ الاحتياطية](#النسخ-الاحتياطية)
- [المساهمة](#المساهمة)
- [الدعم](#الدعم)

## ✨ الميزات

### الميزات الأساسية
- 🏠 **إدارة المحتوى**: نظام بلوكات مرن لإدارة محتوى الموقع
- 📊 **نظام الاستطلاعات**: إنشاء وإدارة الاستطلاعات مع إحصائيات مفصلة
- 💬 **نظام التعليقات**: تعليقات تفاعلية مع نظام موافقة
- 👥 **إدارة المستخدمين**: نظام مستخدمين متكامل مع أدوار وصلاحيات
- 📱 **تصميم متجاوب**: يعمل على جميع الأجهزة والشاشات

### الميزات المتقدمة
- 🔒 **أمان محسن**: حماية من CSRF، XSS، وSQL Injection
- ⚡ **نظام تخزين مؤقت**: تحسين الأداء مع نظام كاش ذكي
- 📈 **إدارة الأخطاء**: تسجيل مفصل للأخطاء مع تنبيهات
- 💾 **النسخ الاحتياطية**: نظام نسخ احتياطي تلقائي ومجدول
- 🎨 **واجهة إدارة حديثة**: لوحة تحكم سهلة الاستخدام

## 🔧 متطلبات النظام

- **PHP**: 7.4 أو أحدث
- **MySQL**: 5.7 أو أحدث (أو MariaDB 10.2+)
- **Apache/Nginx**: خادم ويب
- **الإضافات المطلوبة**:
  - PDO MySQL
  - mbstring
  - openssl
  - zlib (للنسخ الاحتياطية المضغوطة)
  - gd (لمعالجة الصور)

## 🚀 التثبيت

### 1. تحميل الملفات
```bash
# استنساخ المستودع
git clone https://github.com/your-repo/mosque-system.git

# أو تحميل وفك ضغط الملفات
cd mosque-system
```

### 2. إعداد قاعدة البيانات
```sql
-- إنشاء قاعدة البيانات
CREATE DATABASE mosque_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- إنشاء مستخدم قاعدة البيانات
CREATE USER 'mosque_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON mosque_db.* TO 'mosque_user'@'localhost';
FLUSH PRIVILEGES;
```

### 3. استيراد هيكل قاعدة البيانات
```bash
mysql -u mosque_user -p mosque_db < database/schema.sql
```

### 4. إعداد الصلاحيات
```bash
# تعيين صلاحيات المجلدات
chmod 755 uploads/
chmod 755 cache/
chmod 755 logs/
chmod 755 backups/

# تعيين صلاحيات ملفات التكوين
chmod 600 config.php
```

## ⚙️ الإعداد

### 1. تكوين قاعدة البيانات
قم بتحرير ملف `config.php`:

```php
// إعدادات قاعدة البيانات
define('DB_HOST', 'localhost');
define('DB_NAME', 'mosque_db');
define('DB_USER', 'mosque_user');
define('DB_PASS', 'secure_password');
```

### 2. إعدادات الموقع
```php
// معلومات الموقع
define('SITE_NAME', 'مسجد النور');
define('SITE_URL', 'https://your-domain.com');
define('ADMIN_EMAIL', 'admin@your-domain.com');
```

### 3. إعدادات الأمان
```php
// مفاتيح الأمان (قم بتغييرها)
define('ENCRYPTION_KEY', 'your-unique-encryption-key');
define('CSRF_SECRET', 'your-csrf-secret-key');
```

### 4. إنشاء حساب المدير الأول
قم بزيارة: `http://your-domain.com/admin/setup.php`

## 📁 هيكل المشروع

```
mosque2/
├── admin/                      # لوحة التحكم الإدارية
│   ├── index.php              # الصفحة الرئيسية للإدارة
│   ├── blocks_manager.php     # إدارة البلوكات
│   ├── users_manager.php      # إدارة المستخدمين
│   └── backup_manager.php     # إدارة النسخ الاحتياطية
├── includes/                   # الملفات المساعدة
│   ├── auth/                  # نظام المصادقة
│   │   └── auth.php          # فئة المصادقة
│   ├── blocks/                # إدارة البلوكات
│   ├── comments/              # نظام التعليقات
│   ├── email/                 # إرسال البريد الإلكتروني
│   ├── functions/             # الدوال المساعدة
│   ├── UI/                    # عناصر واجهة المستخدم
│   ├── upload/                # رفع الملفات
│   ├── cache_system.php       # نظام التخزين المؤقت
│   ├── error_handler.php      # معالج الأخطاء
│   └── backup_system.php      # نظام النسخ الاحتياطية
├── uploads/                    # ملفات المستخدمين المرفوعة
├── cache/                      # ملفات التخزين المؤقت
├── logs/                       # سجلات النظام
├── backups/                    # النسخ الاحتياطية
├── assets/                     # الموارد الثابتة
│   ├── css/                   # ملفات الأنماط
│   ├── js/                    # ملفات JavaScript
│   └── images/                # الصور
├── config.php                  # ملف التكوين الرئيسي
├── index.php                   # الصفحة الرئيسية
├── database.php               # اتصال قاعدة البيانات
└── README.md                  # هذا الملف
```

## 🎯 الاستخدام

### إدارة البلوكات
```php
// إضافة بلوك جديد
$blockManager = new BlockManager();
$blockManager->addBlock([
    'title' => 'عنوان البلوك',
    'content' => 'محتوى البلوك',
    'position' => 'sidebar',
    'is_active' => true
]);

// عرض البلوكات
$blocks = $blockManager->getBlocksByPosition('sidebar');
foreach ($blocks as $block) {
    echo $block['content'];
}
```

### نظام التخزين المؤقت
```php
// حفظ في الكاش
cache_set('key', $data, 3600); // ساعة واحدة

// استرجاع من الكاش
$data = cache_get('key');

// حذف من الكاش
cache_delete('key');

// مسح الكاش كاملاً
cache_clear();
```

### تسجيل الأخطاء
```php
// تسجيل خطأ
log_error('رسالة الخطأ', ['context' => 'additional_info']);

// تسجيل تحذير
log_warning('رسالة التحذير');

// تسجيل معلومات
log_info('رسالة إعلامية');
```

### النسخ الاحتياطية
```php
// إنشاء نسخة احتياطية كاملة
$result = create_backup('نسخة يدوية');

// إنشاء نسخة تدريجية
$result = create_incremental_backup();

// استعادة نسخة احتياطية
$result = restore_backup('backup_2024-01-01_12-00-00.sql.gz');
```

## 🔒 الأمان

### الحماية المطبقة
- **CSRF Protection**: حماية من هجمات Cross-Site Request Forgery
- **XSS Prevention**: تنظيف وتعقيم جميع المدخلات
- **SQL Injection**: استخدام Prepared Statements
- **Password Hashing**: تشفير كلمات المرور بـ bcrypt
- **Session Security**: إدارة آمنة للجلسات
- **File Upload Security**: فحص وتقييد رفع الملفات

### أفضل الممارسات
```php
// تنظيف المدخلات
$clean_input = sanitize($_POST['user_input']);

// التحقق من CSRF
if (!verify_csrf_token($_POST['csrf_token'])) {
    die('Invalid CSRF token');
}

// استخدام Prepared Statements
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
```

## 💾 النسخ الاحتياطية

### النسخ التلقائية
يقوم النظام بإنشاء نسخ احتياطية تلقائية:
- **نسخة كاملة**: كل أسبوع
- **نسخة تدريجية**: كل يوم
- **تنظيف تلقائي**: الاحتفاظ بآخر 10 نسخ

### النسخ اليدوية
```bash
# إنشاء نسخة احتياطية يدوية
php -f admin/backup_manual.php

# استعادة نسخة احتياطية
php -f admin/restore_backup.php backup_file.sql.gz
```

### جدولة النسخ (Cron)
```bash
# إضافة إلى crontab
# نسخة تدريجية يومياً في الساعة 2:00 صباحاً
0 2 * * * /usr/bin/php /path/to/mosque2/admin/backup_cron.php

# نسخة كاملة أسبوعياً يوم الأحد في الساعة 3:00 صباحاً
0 3 * * 0 /usr/bin/php /path/to/mosque2/admin/backup_full_cron.php
```

## 🎨 التخصيص

### تخصيص التصميم
```css
/* ملف assets/css/custom.css */
:root {
    --primary-color: #2c5aa0;
    --secondary-color: #f8f9fa;
    --accent-color: #28a745;
}

.custom-block {
    background: var(--primary-color);
    color: white;
    padding: 20px;
    border-radius: 8px;
}
```

### إضافة بلوك مخصص
```php
// includes/blocks/custom_block.php
class CustomBlock extends BaseBlock {
    public function render() {
        return '<div class="custom-block">' . $this->content . '</div>';
    }
    
    public function getSettings() {
        return [
            'title' => 'بلوك مخصص',
            'description' => 'بلوك بتصميم مخصص',
            'category' => 'custom'
        ];
    }
}
```

## 🔧 استكشاف الأخطاء

### مشاكل شائعة

#### خطأ في الاتصال بقاعدة البيانات
```
خطأ: SQLSTATE[HY000] [1045] Access denied
```
**الحل**: تحقق من بيانات الاتصال في `config.php`

#### مشكلة في الصلاحيات
```
خطأ: Permission denied
```
**الحل**: 
```bash
chmod 755 uploads/ cache/ logs/ backups/
chown www-data:www-data uploads/ cache/ logs/ backups/
```

#### مشكلة في التخزين المؤقت
```
خطأ: Cache directory not writable
```
**الحل**:
```bash
mkdir -p cache/
chmod 755 cache/
```

### تفعيل وضع التطوير
```php
// في config.php
define('DEBUG', true);
define('DISPLAY_ERRORS', true);

// عرض الأخطاء
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

## 📊 المراقبة والإحصائيات

### مراقبة الأداء
```php
// قياس وقت التنفيذ
$start_time = microtime(true);
// كودك هنا
$execution_time = microtime(true) - $start_time;
log_info("وقت التنفيذ: {$execution_time} ثانية");
```

### إحصائيات الاستخدام
- عدد الزوار اليومي
- البلوكات الأكثر مشاهدة
- الاستطلاعات الأكثر مشاركة
- أخطاء النظام

## 🔄 التحديثات

### تحديث النظام
```bash
# نسخ احتياطية قبل التحديث
php admin/backup_manual.php

# تحميل التحديثات
git pull origin main

# تشغيل سكريبت التحديث
php admin/update.php
```

### سجل التغييرات
راجع ملف `CHANGELOG.md` لمعرفة آخر التحديثات والتحسينات.

## 🤝 المساهمة

### كيفية المساهمة
1. Fork المشروع
2. إنشاء فرع للميزة الجديدة (`git checkout -b feature/amazing-feature`)
3. Commit التغييرات (`git commit -m 'Add amazing feature'`)
4. Push للفرع (`git push origin feature/amazing-feature`)
5. فتح Pull Request

### معايير الكود
- استخدام PSR-12 لتنسيق الكود
- كتابة تعليقات باللغة العربية
- اختبار الكود قبل الإرسال
- توثيق الميزات الجديدة

## 📞 الدعم

### الحصول على المساعدة
- **الوثائق**: راجع هذا الملف والتعليقات في الكود
- **المشاكل**: افتح issue في GitHub
- **البريد الإلكتروني**: support@your-domain.com
- **المنتدى**: https://forum.your-domain.com

### الإبلاغ عن الأخطاء
عند الإبلاغ عن خطأ، يرجى تضمين:
- وصف مفصل للمشكلة
- خطوات إعادة إنتاج الخطأ
- رسائل الخطأ (إن وجدت)
- معلومات البيئة (PHP version, OS, etc.)

## 📄 الترخيص

هذا المشروع مرخص تحت رخصة MIT - راجع ملف [LICENSE](LICENSE) للتفاصيل.

## 🙏 شكر وتقدير

- فريق تطوير PHP
- مجتمع Bootstrap
- مساهمي المشروع
- مجتمع المطورين العرب

---

**ملاحظة**: هذا النظام في تطوير مستمر. نرحب بملاحظاتكم واقتراحاتكم لتحسينه.

**تاريخ آخر تحديث**: يناير 2024
**الإصدار**: 2.0.0
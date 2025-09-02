# دليل التثبيت المفصل - Invitation Manager Pro

هذا الدليل يوضح خطوات تثبيت إضافة Invitation Manager Pro بالتفصيل مع جميع الطرق المختلفة والإعدادات المطلوبة.

## متطلبات النظام

### متطلبات الخادم

قبل البدء في التثبيت، تأكد من توفر المتطلبات التالية:

**متطلبات أساسية:**
- نظام التشغيل: Linux (Ubuntu 18.04+ مُوصى به)
- خادم الويب: Apache 2.4+ أو Nginx 1.14+
- PHP: الإصدار 7.4 أو أحدث (PHP 8.0+ مُوصى به)
- قاعدة البيانات: MySQL 5.7+ أو MariaDB 10.3+
- WordPress: الإصدار 5.0 أو أحدث

**متطلبات PHP:**
- ذاكرة PHP: 128MB كحد أدنى (256MB مُوصى به للمواقع الكبيرة)
- زمن التنفيذ: 60 ثانية كحد أدنى
- حجم الرفع: 32MB كحد أدنى
- إضافات PHP المطلوبة:
  - mysqli أو pdo_mysql
  - gd أو imagick (لمعالجة الصور)
  - curl (لإرسال البريد الإلكتروني)
  - mbstring (لدعم UTF-8)
  - zip (لاستيراد الملفات)

### فحص المتطلبات

يمكنك فحص متطلبات النظام باستخدام الكود التالي:

```php
<?php
// فحص إصدار PHP
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    die('يتطلب PHP 7.4 أو أحدث');
}

// فحص إضافات PHP المطلوبة
$required_extensions = ['mysqli', 'gd', 'curl', 'mbstring', 'zip'];
foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        die("إضافة PHP مفقودة: $ext");
    }
}

// فحص إعدادات PHP
$memory_limit = ini_get('memory_limit');
$upload_max = ini_get('upload_max_filesize');
$post_max = ini_get('post_max_size');

echo "ذاكرة PHP: $memory_limit\n";
echo "حد الرفع: $upload_max\n";
echo "حد POST: $post_max\n";
?>
```

## طرق التثبيت

### الطريقة الأولى: التثبيت عبر لوحة تحكم ووردبريس

هذه هي الطريقة الأسهل والأكثر أماناً للمستخدمين العاديين.

**الخطوات:**

1. **تسجيل الدخول إلى لوحة التحكم**
   - انتقل إلى موقعك الإلكتروني
   - سجل دخولك كمدير

2. **الانتقال إلى صفحة الإضافات**
   - من القائمة الجانبية، اختر "إضافات"
   - اضغط على "أضف جديد"

3. **رفع ملف الإضافة**
   - اضغط على "رفع إضافة"
   - اختر ملف `invitation-manager-pro.zip`
   - اضغط على "تثبيت الآن"

4. **تفعيل الإضافة**
   - بعد انتهاء التثبيت، اضغط على "تفعيل الإضافة"
   - ستظهر رسالة تأكيد التفعيل

### الطريقة الثانية: التثبيت عبر FTP

هذه الطريقة مناسبة عندما تواجه مشاكل في الرفع عبر لوحة التحكم.

**الخطوات:**

1. **تحضير الملفات**
   ```bash
   # فك ضغط الملف
   unzip invitation-manager-pro.zip
   ```

2. **الاتصال بالخادم عبر FTP**
   ```bash
   # استخدام FileZilla أو أي برنامج FTP آخر
   # أو استخدام سطر الأوامر
   ftp your-server.com
   ```

3. **رفع الملفات**
   - انتقل إلى مجلد `/wp-content/plugins/`
   - أنشئ مجلد جديد باسم `invitation-manager-pro`
   - ارفع جميع ملفات الإضافة إلى هذا المجلد

4. **تعيين الصلاحيات**
   ```bash
   chmod -R 755 /path/to/wp-content/plugins/invitation-manager-pro/
   chown -R www-data:www-data /path/to/wp-content/plugins/invitation-manager-pro/
   ```

5. **تفعيل الإضافة**
   - انتقل إلى لوحة تحكم ووردبريس
   - فعّل الإضافة من صفحة الإضافات

### الطريقة الثالثة: التثبيت عبر SSH

هذه الطريقة مناسبة للمطورين والمدراء التقنيين.

**الخطوات التفصيلية:**

1. **الاتصال بالخادم**
   ```bash
   ssh username@your-server.com
   ```

2. **الانتقال إلى مجلد ووردبريس**
   ```bash
   cd /var/www/html/your-site
   # أو المسار الصحيح لموقعك
   ```

3. **إنشاء مجلد الإضافة**
   ```bash
   mkdir -p wp-content/plugins/invitation-manager-pro
   cd wp-content/plugins/invitation-manager-pro
   ```

4. **تحميل ملفات الإضافة**
   
   **الطريقة الأولى: رفع ملف مضغوط**
   ```bash
   # رفع الملف من جهازك المحلي
   scp invitation-manager-pro.zip username@server:/path/to/plugins/
   
   # فك الضغط
   unzip invitation-manager-pro.zip
   mv invitation-manager-pro/* .
   rmdir invitation-manager-pro
   rm invitation-manager-pro.zip
   ```

   **الطريقة الثانية: رفع الملفات مباشرة**
   ```bash
   # رفع جميع الملفات
   scp -r /local/path/invitation-manager-pro/* username@server:/path/to/plugins/invitation-manager-pro/
   ```

   **الطريقة الثالثة: استخدام Git (للمطورين)**
   ```bash
   git clone https://github.com/your-repo/invitation-manager-pro.git .
   ```

5. **تعيين الصلاحيات الصحيحة**
   ```bash
   # العودة إلى مجلد الإضافة
   cd /var/www/html/your-site/wp-content/plugins/invitation-manager-pro
   
   # تعيين المالك
   sudo chown -R www-data:www-data .
   
   # تعيين صلاحيات المجلدات
   find . -type d -exec chmod 755 {} \;
   
   # تعيين صلاحيات الملفات
   find . -type f -exec chmod 644 {} \;
   
   # صلاحيات خاصة لملفات معينة (إذا لزم الأمر)
   chmod 600 includes/config.php
   ```

6. **التحقق من التثبيت**
   ```bash
   # التحقق من وجود الملفات
   ls -la
   
   # التحقق من الصلاحيات
   ls -la invitation-manager-pro.php
   ```

## إعداد قاعدة البيانات

### الإعداد التلقائي

الإضافة تقوم بإنشاء الجداول تلقائياً عند التفعيل، ولكن يمكنك إنشاؤها يدوياً إذا لزم الأمر.

### الإعداد اليدوي

إذا كنت تفضل إنشاء الجداول يدوياً، استخدم الاستعلامات التالية:

```sql
-- جدول المناسبات
CREATE TABLE `wp_impro_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `event_date` date NOT NULL,
  `event_time` time DEFAULT NULL,
  `venue` varchar(255) NOT NULL,
  `address` text,
  `description` text,
  `invitation_image_url` varchar(500) DEFAULT NULL,
  `invitation_text` text,
  `location_details` text,
  `contact_info` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_event_date` (`event_date`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول المدعوين
CREATE TABLE `wp_impro_guests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `plus_one_allowed` tinyint(1) DEFAULT 0,
  `gender` enum('male','female') DEFAULT NULL,
  `age_range` enum('child','teen','adult','senior') DEFAULT NULL,
  `relationship` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_email` (`email`),
  KEY `idx_category` (`category`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول الدعوات
CREATE TABLE `wp_impro_invitations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) NOT NULL,
  `guest_id` int(11) NOT NULL,
  `token` varchar(32) NOT NULL,
  `status` enum('pending','sent','viewed','expired') DEFAULT 'pending',
  `sent_at` datetime DEFAULT NULL,
  `viewed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_token` (`token`),
  UNIQUE KEY `idx_event_guest` (`event_id`,`guest_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  FOREIGN KEY (`event_id`) REFERENCES `wp_impro_events` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`guest_id`) REFERENCES `wp_impro_guests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول ردود الحضور
CREATE TABLE `wp_impro_rsvps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) NOT NULL,
  `guest_id` int(11) NOT NULL,
  `status` enum('accepted','declined') NOT NULL,
  `plus_one_attending` tinyint(1) DEFAULT 0,
  `plus_one_name` varchar(255) DEFAULT NULL,
  `dietary_requirements` text,
  `response_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_event_guest` (`event_id`,`guest_id`),
  KEY `idx_status` (`status`),
  KEY `idx_response_date` (`response_date`),
  FOREIGN KEY (`event_id`) REFERENCES `wp_impro_events` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`guest_id`) REFERENCES `wp_impro_guests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول سجلات البريد الإلكتروني
CREATE TABLE `wp_impro_email_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invitation_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `sent_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_invitation_id` (`invitation_id`),
  KEY `idx_email` (`email`),
  KEY `idx_sent_at` (`sent_at`),
  FOREIGN KEY (`invitation_id`) REFERENCES `wp_impro_invitations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### التحقق من إنشاء الجداول

```sql
-- التحقق من وجود الجداول
SHOW TABLES LIKE 'wp_impro_%';

-- التحقق من بنية الجداول
DESCRIBE wp_impro_events;
DESCRIBE wp_impro_guests;
DESCRIBE wp_impro_invitations;
DESCRIBE wp_impro_rsvps;
DESCRIBE wp_impro_email_logs;
```

## إعداد البريد الإلكتروني

### إعداد SMTP

لضمان وصول رسائل الدعوات، يُنصح بإعداد SMTP:

1. **تثبيت إضافة SMTP**
   ```bash
   # أو استخدم إضافة WP Mail SMTP
   wp plugin install wp-mail-smtp --activate
   ```

2. **إعداد SMTP في wp-config.php**
   ```php
   // إعدادات SMTP
   define('SMTP_HOST', 'smtp.gmail.com');
   define('SMTP_PORT', 587);
   define('SMTP_SECURE', 'tls');
   define('SMTP_USERNAME', 'your-email@gmail.com');
   define('SMTP_PASSWORD', 'your-app-password');
   define('SMTP_FROM', 'your-email@gmail.com');
   define('SMTP_FROM_NAME', 'اسم موقعك');
   ```

3. **اختبار إرسال البريد**
   ```php
   // اختبار سريع
   wp_mail('test@example.com', 'اختبار', 'هذه رسالة اختبار');
   ```

### إعداد قوالب البريد الإلكتروني

1. **انتقل إلى إعدادات الإضافة**
2. **خصص قالب البريد الإلكتروني**
3. **اختبر إرسال دعوة تجريبية**

## إعدادات الأمان

### تأمين الملفات

```bash
# حماية ملفات الإعداد
chmod 600 wp-config.php
chmod 600 includes/config.php

# منع الوصول المباشر للملفات الحساسة
echo "deny from all" > includes/.htaccess
echo "deny from all" > uploads/invitation-manager-pro/.htaccess
```

### إعداد SSL

تأكد من تفعيل SSL لحماية البيانات:

```apache
# في ملف .htaccess
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### إعدادات قاعدة البيانات الآمنة

```php
// في wp-config.php
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', 'utf8mb4_unicode_ci');

// مفاتيح الأمان
define('AUTH_KEY',         'ضع مفتاح فريد هنا');
define('SECURE_AUTH_KEY',  'ضع مفتاح فريد هنا');
define('LOGGED_IN_KEY',    'ضع مفتاح فريد هنا');
define('NONCE_KEY',        'ضع مفتاح فريد هنا');
```

## الإعداد الأولي

### معالج الإعداد

بعد تفعيل الإضافة، ستظهر صفحة الإعداد الأولي:

1. **الإعدادات الأساسية**
   - اسم الموقع
   - البريد الإلكتروني الافتراضي
   - المنطقة الزمنية

2. **إعدادات البريد الإلكتروني**
   - خادم SMTP
   - بيانات المصادقة
   - قوالب الرسائل

3. **إعدادات الأمان**
   - مفاتيح التشفير
   - صلاحيات المستخدمين

4. **إعدادات الأداء**
   - التخزين المؤقت
   - تحسين قاعدة البيانات

### إنشاء أول مناسبة

1. **انتقل إلى "إدارة الدعوات"**
2. **اضغط على "إضافة مناسبة جديدة"**
3. **املأ البيانات الأساسية**
4. **احفظ المناسبة**

### إضافة المدعوين الأوائل

1. **انتقل إلى "المدعوين"**
2. **أضف مدعوين تجريبيين**
3. **اختبر إرسال الدعوات**

## استكشاف مشاكل التثبيت

### مشاكل شائعة وحلولها

#### خطأ: "لا يمكن إنشاء الجداول"

**السبب:** صلاحيات قاعدة البيانات غير كافية

**الحل:**
```sql
-- منح صلاحيات كاملة لمستخدم قاعدة البيانات
GRANT ALL PRIVILEGES ON database_name.* TO 'username'@'localhost';
FLUSH PRIVILEGES;
```

#### خطأ: "الذاكرة غير كافية"

**السبب:** حد ذاكرة PHP منخفض

**الحل:**
```php
// في wp-config.php
ini_set('memory_limit', '256M');

// أو في .htaccess
php_value memory_limit 256M
```

#### خطأ: "لا يمكن رفع الملفات"

**السبب:** حدود الرفع منخفضة

**الحل:**
```php
// في .htaccess
php_value upload_max_filesize 32M
php_value post_max_size 32M
php_value max_execution_time 300
```

#### خطأ: "صلاحيات الملفات"

**السبب:** صلاحيات غير صحيحة

**الحل:**
```bash
# إصلاح الصلاحيات
find /path/to/wordpress/ -type d -exec chmod 755 {} \;
find /path/to/wordpress/ -type f -exec chmod 644 {} \;
chmod 600 wp-config.php
```

### تفعيل وضع التطوير

لاستكشاف الأخطاء بشكل أفضل:

```php
// في wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('IMPRO_DEBUG', true);
```

### فحص سجلات الأخطاء

```bash
# فحص سجلات ووردبريس
tail -f /path/to/wordpress/wp-content/debug.log

# فحص سجلات الخادم
tail -f /var/log/apache2/error.log
# أو
tail -f /var/log/nginx/error.log
```

## التحقق من نجاح التثبيت

### قائمة التحقق

- [ ] الإضافة مفعلة في لوحة التحكم
- [ ] قائمة "إدارة الدعوات" ظاهرة في القائمة الجانبية
- [ ] جميع الجداول موجودة في قاعدة البيانات
- [ ] إعدادات البريد الإلكتروني تعمل
- [ ] يمكن إنشاء مناسبة جديدة
- [ ] يمكن إضافة مدعوين
- [ ] يمكن إرسال دعوة تجريبية

### اختبار شامل

```php
// كود اختبار شامل
function impro_installation_test() {
    $results = array();
    
    // فحص الجداول
    global $wpdb;
    $tables = array('events', 'guests', 'invitations', 'rsvps');
    foreach ($tables as $table) {
        $table_name = $wpdb->prefix . 'impro_' . $table;
        $exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        $results['table_' . $table] = !empty($exists);
    }
    
    // فحص الصلاحيات
    $results['can_manage_invitations'] = current_user_can('manage_invitations');
    
    // فحص البريد الإلكتروني
    $results['email_test'] = wp_mail('test@example.com', 'Test', 'Test message');
    
    // فحص الكلاسات
    $results['classes_loaded'] = class_exists('IMPRO_Event_Manager');
    
    return $results;
}

// تشغيل الاختبار
$test_results = impro_installation_test();
var_dump($test_results);
```

## الخطوات التالية

بعد نجاح التثبيت:

1. **اقرأ دليل المستخدم** لفهم جميع المميزات
2. **خصص الإعدادات** حسب احتياجاتك
3. **أنشئ أول مناسبة** واختبر جميع الوظائف
4. **ادرب فريقك** على استخدام الإضافة
5. **اعمل نسخة احتياطية** دورية

## الدعم الفني

إذا واجهت أي مشاكل في التثبيت:

1. **راجع هذا الدليل** مرة أخرى
2. **تحقق من سجلات الأخطاء**
3. **تواصل مع الدعم الفني**
4. **زر منتدى المجتمع**

---

**ملاحظة:** هذا الدليل يغطي معظم سيناريوهات التثبيت. إذا كان لديك إعداد خاص أو متطلبات محددة، يرجى التواصل مع فريق الدعم الفني.


# دليل المطور - Invitation Manager Pro

مرحباً بك في دليل المطور لإضافة Invitation Manager Pro. هذا الدليل مخصص للمطورين الذين يرغبون في فهم البنية الداخلية للإضافة، تخصيصها، أو تطوير إضافات مكملة لها.

## نظرة عامة على البنية

### هيكل الملفات

```
invitation-manager-pro/
├── invitation-manager-pro.php          # الملف الرئيسي
├── includes/                           # الكلاسات الأساسية
│   ├── class-impro-database.php
│   ├── class-impro-event-manager.php
│   ├── class-impro-guest-manager.php
│   ├── class-impro-invitation-manager.php
│   ├── class-impro-rsvp-manager.php
│   ├── class-impro-admin.php
│   ├── class-impro-public.php
│   ├── class-impro-security.php
│   ├── class-impro-cache.php
│   ├── class-impro-performance.php
│   ├── class-impro-validator.php
│   ├── class-impro-email.php
│   ├── class-impro-shortcodes.php
│   ├── class-impro-qr-generator.php
│   ├── class-impro-query-builder.php
│   ├── class-impro-migration.php
│   └── class-impro-installation.php
├── admin/                              # صفحات لوحة التحكم
│   └── dashboard.php
├── public/                             # الواجهة العامة
│   ├── invitation-template.php
│   ├── invitation-content.php
│   └── rsvp-form.php
├── assets/                             # الملفات الثابتة
│   ├── css/
│   │   ├── admin.css
│   │   └── public.css
│   └── js/
│       ├── admin.js
│       └── public.js
├── docs/                               # الوثائق
│   ├── user-guide.md
│   ├── developer-guide.md
│   └── code-examples.md
├── README.md
└── INSTALLATION.md
```

### نمط التسمية

جميع الكلاسات والدوال تستخدم بادئة `IMPRO_` لتجنب التعارضات مع الإضافات الأخرى. أسماء الكلاسات تتبع نمط `IMPRO_Class_Name` بينما أسماء الدوال تتبع نمط `impro_function_name`.

### التحميل التلقائي

الإضافة تستخدم نظام تحميل تلقائي للكلاسات يعتمد على PSR-4. جميع الكلاسات موجودة في مجلد `includes/` ويتم تحميلها تلقائياً عند الحاجة.

## الكلاسات الأساسية

### IMPRO_Database

هذا الكلاس مسؤول عن إدارة قاعدة البيانات وإنشاء الجداول. يوفر طرق آمنة للتفاعل مع قاعدة البيانات ويضمن سلامة البيانات.

```php
class IMPRO_Database {
    /**
     * إنشاء جداول قاعدة البيانات
     */
    public function create_tables() {
        // كود إنشاء الجداول
    }
    
    /**
     * الحصول على اسم الجدول مع البادئة
     */
    public function get_table_name($table_key) {
        global $wpdb;
        return $wpdb->prefix . 'impro_' . $table_key;
    }
    
    /**
     * التحقق من وجود الجدول
     */
    public function table_exists($table_key) {
        // كود التحقق
    }
}
```

### IMPRO_Event_Manager

يدير جميع العمليات المتعلقة بالمناسبات من إنشاء وتحديث وحذف.

```php
class IMPRO_Event_Manager {
    /**
     * إنشاء مناسبة جديدة
     */
    public function create_event($data) {
        // التحقق من صحة البيانات
        if (!IMPRO_Validator::validate_event_data($data)) {
            return false;
        }
        
        // تنظيف البيانات
        $data = IMPRO_Validator::sanitize_event_data($data);
        
        // إدراج في قاعدة البيانات
        $query_builder = new IMPRO_Query_Builder();
        return $query_builder->table('events')->insert($data);
    }
    
    /**
     * الحصول على مناسبة
     */
    public function get_event($event_id) {
        $query_builder = new IMPRO_Query_Builder();
        return $query_builder->table('events')
                            ->where('id', $event_id)
                            ->first();
    }
    
    /**
     * تحديث مناسبة
     */
    public function update_event($event_id, $data) {
        // التحقق والتحديث
    }
}
```

### IMPRO_Guest_Manager

يدير المدعوين وعملياتهم المختلفة.

```php
class IMPRO_Guest_Manager {
    /**
     * إنشاء مدعو جديد
     */
    public function create_guest($data) {
        if (!IMPRO_Validator::validate_guest_data($data)) {
            return false;
        }
        
        $data = IMPRO_Validator::sanitize_guest_data($data);
        
        $query_builder = new IMPRO_Query_Builder();
        return $query_builder->table('guests')->insert($data);
    }
    
    /**
     * استيراد مدعوين من CSV
     */
    public function import_guests_from_csv($file_path, $event_id = 0) {
        if (!IMPRO_Validator::validate_csv_file($file_path)) {
            return false;
        }
        
        // قراءة ومعالجة ملف CSV
        $handle = fopen($file_path, 'r');
        $header = fgetcsv($handle);
        $imported = 0;
        
        while (($row = fgetcsv($handle)) !== false) {
            $guest_data = array_combine($header, $row);
            
            if ($this->create_guest($guest_data)) {
                $imported++;
                
                // إنشاء دعوة إذا تم تحديد مناسبة
                if ($event_id) {
                    $invitation_manager = new IMPRO_Invitation_Manager();
                    $invitation_manager->create_invitation($event_id, $guest_id);
                }
            }
        }
        
        fclose($handle);
        return $imported;
    }
}
```

### IMPRO_Query_Builder

كلاس متقدم لبناء استعلامات SQL آمنة ومرنة.

```php
class IMPRO_Query_Builder {
    /**
     * تحديد الجدول
     */
    public function table($table_key) {
        $this->table = $this->database->get_table_name($table_key);
        return $this;
    }
    
    /**
     * إضافة شرط WHERE
     */
    public function where($column, $operator, $value = null, $boolean = 'AND') {
        // إضافة الشرط بطريقة آمنة
        return $this;
    }
    
    /**
     * تنفيذ الاستعلام
     */
    public function get($use_cache = true) {
        $sql = $this->build_select_query();
        
        if ($use_cache) {
            $cached_result = IMPRO_Cache::get_cached_query_result($sql, $this->parameters);
            if ($cached_result !== false) {
                return $cached_result;
            }
        }
        
        $prepared_sql = $this->wpdb->prepare($sql, $this->parameters);
        $results = $this->wpdb->get_results($prepared_sql);
        
        if ($use_cache && $results !== false) {
            IMPRO_Cache::cache_query_result($sql, $this->parameters, $results);
        }
        
        return $results ?: array();
    }
}
```

## نظام الهوكس والفلاتر

### الهوكس المتاحة

الإضافة توفر عدة هوكس يمكن للمطورين استخدامها لتخصيص السلوك:

```php
// قبل إنشاء مناسبة
do_action('impro_before_create_event', $event_data);

// بعد إنشاء مناسبة
do_action('impro_after_create_event', $event_id, $event_data);

// قبل إنشاء مدعو
do_action('impro_before_create_guest', $guest_data);

// بعد إنشاء مدعو
do_action('impro_after_create_guest', $guest_id, $guest_data);

// قبل إرسال دعوة
do_action('impro_before_send_invitation', $invitation, $guest, $event);

// بعد إرسال دعوة
do_action('impro_after_send_invitation', $invitation, $guest, $event, $sent);

// قبل حفظ RSVP
do_action('impro_before_save_rsvp', $rsvp_data);

// بعد حفظ RSVP
do_action('impro_after_save_rsvp', $rsvp_id, $rsvp_data);
```

### الفلاتر المتاحة

```php
// تخصيص قالب البريد الإلكتروني
add_filter('impro_email_template', function($template, $event, $guest) {
    // تخصيص القالب حسب نوع المناسبة
    if ($event->category === 'wedding') {
        return get_template_directory() . '/impro-wedding-template.php';
    }
    return $template;
}, 10, 3);

// تخصيص فئات المدعوين
add_filter('impro_guest_categories', function($categories) {
    $categories['sponsors'] = __('الرعاة', 'invitation-manager-pro');
    $categories['media'] = __('الإعلام', 'invitation-manager-pro');
    return $categories;
});

// تخصيص رابط الدعوة
add_filter('impro_invitation_url', function($url, $token) {
    // إضافة معاملات إضافية للرابط
    return add_query_arg('utm_source', 'invitation', $url);
}, 10, 2);

// تخصيص بيانات RSVP قبل الحفظ
add_filter('impro_rsvp_data_before_save', function($data, $guest, $event) {
    // إضافة بيانات إضافية
    $data['source'] = 'website';
    return $data;
}, 10, 3);
```

## تطوير إضافات مكملة

### إنشاء إضافة مكملة

يمكنك إنشاء إضافات مكملة تتفاعل مع Invitation Manager Pro:

```php
<?php
/**
 * Plugin Name: IMPRO Analytics Extension
 * Description: إضافة تحليلات متقدمة لـ Invitation Manager Pro
 * Version: 1.0.0
 */

// التحقق من وجود الإضافة الأساسية
if (!class_exists('IMPRO_Event_Manager')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>';
        echo __('يتطلب هذه الإضافة تثبيت Invitation Manager Pro أولاً.', 'impro-analytics');
        echo '</p></div>';
    });
    return;
}

class IMPRO_Analytics_Extension {
    public function __construct() {
        add_action('impro_after_save_rsvp', array($this, 'track_rsvp_analytics'));
        add_filter('impro_dashboard_widgets', array($this, 'add_analytics_widget'));
    }
    
    public function track_rsvp_analytics($rsvp_id, $rsvp_data) {
        // تتبع إحصائيات RSVP
        $this->record_analytics_event('rsvp_submitted', array(
            'rsvp_id' => $rsvp_id,
            'status' => $rsvp_data['status'],
            'timestamp' => current_time('mysql')
        ));
    }
    
    public function add_analytics_widget($widgets) {
        $widgets['analytics'] = array(
            'title' => __('تحليلات متقدمة', 'impro-analytics'),
            'callback' => array($this, 'render_analytics_widget')
        );
        return $widgets;
    }
}

new IMPRO_Analytics_Extension();
```

### استخدام API الإضافة

```php
// الحصول على جميع المناسبات
$event_manager = new IMPRO_Event_Manager();
$events = $event_manager->get_events();

// إنشاء مدعو جديد
$guest_manager = new IMPRO_Guest_Manager();
$guest_id = $guest_manager->create_guest(array(
    'name' => 'أحمد محمد',
    'email' => 'ahmed@example.com',
    'category' => 'family'
));

// إنشاء دعوة
$invitation_manager = new IMPRO_Invitation_Manager();
$invitation_id = $invitation_manager->create_invitation($event_id, $guest_id);

// إرسال الدعوة
$invitation = $invitation_manager->get_invitation($invitation_id);
$guest = $guest_manager->get_guest($guest_id);
$event = $event_manager->get_event($event_id);

IMPRO_Email::send_invitation($invitation, $guest, $event);
```

## تخصيص القوالب

### إنشاء قوالب مخصصة

يمكنك إنشاء قوالب مخصصة لصفحات الدعوة:

```php
// في ملف functions.php للثيم
add_filter('impro_invitation_template', function($template, $event) {
    // استخدام قالب مخصص للأفراح
    if ($event->category === 'wedding') {
        $custom_template = get_template_directory() . '/impro-wedding-invitation.php';
        if (file_exists($custom_template)) {
            return $custom_template;
        }
    }
    return $template;
}, 10, 2);
```

قالب الدعوة المخصص:

```php
<?php
// ملف: impro-wedding-invitation.php
?>
<div class="impro-wedding-invitation">
    <div class="invitation-header">
        <h1><?php echo esc_html($event->name); ?></h1>
        <?php if ($event->invitation_image_url): ?>
            <img src="<?php echo esc_url($event->invitation_image_url); ?>" alt="<?php echo esc_attr($event->name); ?>">
        <?php endif; ?>
    </div>
    
    <div class="invitation-content">
        <div class="guest-name">
            <p><?php printf(__('عزيزنا %s', 'theme'), esc_html($guest->name)); ?></p>
        </div>
        
        <div class="event-details">
            <p><?php echo wp_kses_post($event->invitation_text); ?></p>
            
            <div class="event-info">
                <div class="date-time">
                    <strong><?php _e('التاريخ:', 'theme'); ?></strong>
                    <?php echo date_i18n('j F Y', strtotime($event->event_date)); ?>
                    <?php if ($event->event_time): ?>
                        - <?php echo date_i18n('g:i A', strtotime($event->event_time)); ?>
                    <?php endif; ?>
                </div>
                
                <div class="venue">
                    <strong><?php _e('المكان:', 'theme'); ?></strong>
                    <?php echo esc_html($event->venue); ?>
                </div>
                
                <?php if ($event->address): ?>
                    <div class="address">
                        <strong><?php _e('العنوان:', 'theme'); ?></strong>
                        <?php echo esc_html($event->address); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="rsvp-section">
            <?php echo do_shortcode('[impro_rsvp_form token="' . $invitation->token . '"]'); ?>
        </div>
    </div>
</div>
```

### تخصيص CSS

```css
/* ملف: wedding-invitation.css */
.impro-wedding-invitation {
    max-width: 600px;
    margin: 0 auto;
    padding: 40px;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    font-family: 'Amiri', serif;
}

.invitation-header {
    text-align: center;
    margin-bottom: 30px;
}

.invitation-header h1 {
    color: #2c3e50;
    font-size: 2.5em;
    margin-bottom: 20px;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
}

.invitation-header img {
    max-width: 100%;
    height: auto;
    border-radius: 10px;
}

.guest-name p {
    font-size: 1.3em;
    color: #34495e;
    text-align: center;
    margin-bottom: 25px;
}

.event-info {
    background: rgba(255,255,255,0.8);
    padding: 20px;
    border-radius: 10px;
    margin: 20px 0;
}

.event-info > div {
    margin-bottom: 15px;
    padding: 10px;
    border-right: 4px solid #3498db;
}

.rsvp-section {
    margin-top: 30px;
    text-align: center;
}
```

## تحسين الأداء

### استخدام التخزين المؤقت

```php
// استخدام نظام التخزين المؤقت المدمج
class My_Custom_Manager {
    public function get_expensive_data($event_id) {
        $cache_key = 'expensive_data_' . $event_id;
        $cached_data = IMPRO_Cache::get($cache_key);
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        // عملية مكلفة
        $data = $this->perform_expensive_operation($event_id);
        
        // حفظ في التخزين المؤقت لمدة ساعة
        IMPRO_Cache::set($cache_key, $data, 3600);
        
        return $data;
    }
}
```

### تحسين الاستعلامات

```php
// استخدام Query Builder للاستعلامات المحسنة
$query_builder = new IMPRO_Query_Builder();

// استعلام محسن مع JOIN
$results = $query_builder
    ->table('events')
    ->select('events.*, COUNT(invitations.id) as invitation_count')
    ->left_join('invitations', 'events.id', '=', 'invitations.event_id')
    ->where('events.event_date', '>=', date('Y-m-d'))
    ->group_by('events.id')
    ->order_by('events.event_date', 'ASC')
    ->limit(10)
    ->get();
```

## الأمان

### التحقق من الصلاحيات

```php
// التحقق من صلاحيات المستخدم
function impro_check_user_permission($action, $object_id = 0) {
    if (!is_user_logged_in()) {
        return false;
    }
    
    switch ($action) {
        case 'manage_events':
            return current_user_can('manage_invitations');
            
        case 'view_event':
            // التحقق من ملكية المناسبة
            $event = new IMPRO_Event_Manager();
            $event_data = $event->get_event($object_id);
            return $event_data && ($event_data->created_by === get_current_user_id() || current_user_can('manage_invitations'));
            
        default:
            return false;
    }
}

// استخدام التحقق في الكود
if (!impro_check_user_permission('manage_events')) {
    wp_die(__('ليس لديك صلاحية للوصول إلى هذه الصفحة.', 'invitation-manager-pro'));
}
```

### تنظيف البيانات

```php
// استخدام دوال التنظيف المدمجة
function impro_sanitize_event_data($data) {
    return array(
        'name' => sanitize_text_field($data['name']),
        'description' => sanitize_textarea_field($data['description']),
        'venue' => sanitize_text_field($data['venue']),
        'event_date' => sanitize_text_field($data['event_date']),
        'invitation_image_url' => esc_url_raw($data['invitation_image_url'])
    );
}
```

## اختبار الكود

### اختبارات الوحدة

```php
class IMPRO_Event_Manager_Test extends WP_UnitTestCase {
    public function test_create_event() {
        $event_manager = new IMPRO_Event_Manager();
        
        $event_data = array(
            'name' => 'اختبار المناسبة',
            'event_date' => '2024-12-31',
            'venue' => 'قاعة الاختبار'
        );
        
        $event_id = $event_manager->create_event($event_data);
        
        $this->assertIsInt($event_id);
        $this->assertGreaterThan(0, $event_id);
        
        // التحقق من حفظ البيانات
        $saved_event = $event_manager->get_event($event_id);
        $this->assertEquals($event_data['name'], $saved_event->name);
    }
    
    public function test_invalid_event_data() {
        $event_manager = new IMPRO_Event_Manager();
        
        // بيانات غير صحيحة
        $invalid_data = array(
            'name' => '', // اسم فارغ
            'event_date' => 'invalid-date',
            'venue' => ''
        );
        
        $result = $event_manager->create_event($invalid_data);
        $this->assertFalse($result);
    }
}
```

### اختبارات التكامل

```php
class IMPRO_Integration_Test extends WP_UnitTestCase {
    public function test_full_invitation_workflow() {
        // إنشاء مناسبة
        $event_manager = new IMPRO_Event_Manager();
        $event_id = $event_manager->create_event(array(
            'name' => 'مناسبة اختبار',
            'event_date' => '2024-12-31',
            'venue' => 'قاعة الاختبار'
        ));
        
        // إنشاء مدعو
        $guest_manager = new IMPRO_Guest_Manager();
        $guest_id = $guest_manager->create_guest(array(
            'name' => 'مدعو اختبار',
            'email' => 'test@example.com'
        ));
        
        // إنشاء دعوة
        $invitation_manager = new IMPRO_Invitation_Manager();
        $invitation_id = $invitation_manager->create_invitation($event_id, $guest_id);
        
        // التحقق من إنشاء الدعوة
        $invitation = $invitation_manager->get_invitation($invitation_id);
        $this->assertNotNull($invitation);
        $this->assertEquals($event_id, $invitation->event_id);
        $this->assertEquals($guest_id, $invitation->guest_id);
        
        // اختبار RSVP
        $rsvp_manager = new IMPRO_RSVP_Manager();
        $rsvp_id = $rsvp_manager->save_rsvp(array(
            'event_id' => $event_id,
            'guest_id' => $guest_id,
            'status' => 'accepted'
        ));
        
        $this->assertIsInt($rsvp_id);
        $this->assertGreaterThan(0, $rsvp_id);
    }
}
```

## نشر الإضافة

### إعداد ملف التوزيع

```php
// في ملف build.php
<?php
$version = '1.0.0';
$plugin_files = array(
    'invitation-manager-pro.php',
    'includes/',
    'admin/',
    'public/',
    'assets/',
    'docs/',
    'README.md',
    'INSTALLATION.md'
);

// إنشاء ملف مضغوط للتوزيع
$zip = new ZipArchive();
$zip_filename = "invitation-manager-pro-{$version}.zip";

if ($zip->open($zip_filename, ZipArchive::CREATE) === TRUE) {
    foreach ($plugin_files as $file) {
        if (is_dir($file)) {
            add_directory_to_zip($zip, $file);
        } else {
            $zip->addFile($file);
        }
    }
    $zip->close();
    echo "تم إنشاء ملف التوزيع: {$zip_filename}\n";
}

function add_directory_to_zip($zip, $dir) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $zip->addFile($file->getPathname(), $file->getPathname());
        }
    }
}
?>
```

### قائمة التحقق قبل النشر

- [ ] جميع الاختبارات تمر بنجاح
- [ ] الكود محسن ومنظف
- [ ] الوثائق محدثة
- [ ] إصدار جديد في ملف الإضافة الرئيسي
- [ ] تحديث ملف CHANGELOG
- [ ] اختبار التثبيت على بيئة نظيفة
- [ ] اختبار التوافق مع أحدث إصدار من ووردبريس
- [ ] مراجعة الأمان والأداء

---

هذا الدليل يوفر أساساً قوياً لفهم وتطوير الإضافة. للحصول على معلومات أكثر تفصيلاً، راجع الكود المصدري والتعليقات المرفقة.



<?php
/**
 * Guests import page.
 *
 * @package Invitation_Manager_Pro
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// تهيئة المدير بأمان
try {
    $event_manager = new IMPRO_Event_Manager();
    $events = $event_manager->get_events() ?: array();
} catch ( Exception $e ) {
    error_log( 'Failed to load events for import page: ' . $e->getMessage() );
    $events = array();
    $event_manager = null;
}

// التحقق من صحة البيانات
if ( ! is_array( $events ) ) {
    $events = array();
}

?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e( 'استيراد المدعوين', 'invitation-manager-pro' ); ?></h1>
    <hr class="wp-header-end">

    <?php 
    // عرض رسائل الخطأ
    if ( isset( $_GET['error'] ) ) : 
        $error_messages = array(
            'file_upload_failed' => __( 'فشل في رفع الملف. يرجى التأكد من أن الملف بصيغة CSV.', 'invitation-manager-pro' ),
            'invalid_file_type' => __( 'نوع الملف غير صالح. يرجى رفع ملف CSV فقط.', 'invitation-manager-pro' ),
            'empty_file' => __( 'الملف فارغ. يرجى التأكد من أن ملف CSV يحتوي على بيانات.', 'invitation-manager-pro' ),
            'import_failed' => __( 'فشل في استيراد المدعوين. يرجى التحقق من تنسيق الملف.', 'invitation-manager-pro' ),
            'file_size_exceeded' => __( 'حجم الملف كبير جداً. الحد الأقصى هو 5 ميجابايت.', 'invitation-manager-pro' ),
            'permission_denied' => __( 'ليس لديك الصلاحية لاستيراد المدعوين.', 'invitation-manager-pro' ),
            'encoding_error' => __( 'خطأ في ترميز الملف. يرجى التأكد من أن الملف بصيغة UTF-8.', 'invitation-manager-pro' )
        );
        
        $error_key = sanitize_text_field( $_GET['error'] );
        $error_message = isset( $error_messages[ $error_key ] ) ? $error_messages[ $error_key ] : $error_messages['import_failed'];
    ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html( $error_message ); ?></p>
        </div>
    <?php endif; ?>

    <?php 
    // عرض رسائل النجاح
    if ( isset( $_GET['message'] ) ) : 
        $success_messages = array(
            'imported' => sprintf( __( 'تم استيراد %d مدعو بنجاح.', 'invitation-manager-pro' ), isset( $_GET['count'] ) ? intval( $_GET['count'] ) : 0 ),
            'imported_with_errors' => sprintf( __( 'تم استيراد %d مدعو، لكن حدثت أخطاء في %d مدعو.', 'invitation-manager-pro' ), 
                                             isset( $_GET['imported'] ) ? intval( $_GET['imported'] ) : 0,
                                             isset( $_GET['errors'] ) ? intval( $_GET['errors'] ) : 0 )
        );
        
        $message_key = sanitize_text_field( $_GET['message'] );
        $message_text = isset( $success_messages[ $message_key ] ) ? $success_messages[ $message_key ] : '';
        
        if ( ! empty( $message_text ) ) :
    ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html( $message_text ); ?></p>
        </div>
    <?php 
        endif;
    endif; 
    ?>

    <div class="impro-import-dashboard">
        <!-- بطاقة التعليمات -->
        <div class="impro-card">
            <div class="impro-card-header">
                <h2><?php _e( 'تعليمات الاستيراد', 'invitation-manager-pro' ); ?></h2>
            </div>
            <div class="impro-card-body">
                <div class="impro-instructions">
                    <p><?php _e( 'يمكنك استيراد المدعوين من ملف CSV. يجب أن يحتوي الملف على الأعمدة التالية:', 'invitation-manager-pro' ); ?></p>
                    
                    <div class="impro-required-columns">
                        <h3><?php _e( 'الأعمدة المطلوبة:', 'invitation-manager-pro' ); ?></h3>
                        <ul>
                            <li><strong>Name</strong> - <?php _e( 'الاسم الكامل (مطلوب)', 'invitation-manager-pro' ); ?></li>
                            <li><strong>Email</strong> - <?php _e( 'البريد الإلكتروني (اختياري)', 'invitation-manager-pro' ); ?></li>
                            <li><strong>Phone</strong> - <?php _e( 'رقم الهاتف (اختياري)', 'invitation-manager-pro' ); ?></li>
                            <li><strong>Category</strong> - <?php _e( 'الفئة (اختياري)', 'invitation-manager-pro' ); ?></li>
                            <li><strong>Plus_One_Allowed</strong> - <?php _e( 'مسموح بمرافق (0 أو 1) (اختياري)', 'invitation-manager-pro' ); ?></li>
                            <li><strong>Gender</strong> - <?php _e( 'الجنس (اختياري)', 'invitation-manager-pro' ); ?></li>
                            <li><strong>Age_Range</strong> - <?php _e( 'الفئة العمرية (اختياري)', 'invitation-manager-pro' ); ?></li>
                            <li><strong>Relationship</strong> - <?php _e( 'صلة القرابة/العلاقة (اختياري)', 'invitation-manager-pro' ); ?></li>
                        </ul>
                    </div>
                    
                    <div class="impro-sample-data">
                        <h3><?php _e( 'مثال على تنسيق ملف CSV:', 'invitation-manager-pro' ); ?></h3>
                        <div class="impro-code-block">
                            <pre>Name,Email,Phone,Category,Plus_One_Allowed,Gender,Age_Range,Relationship
أحمد محمد,ahmed@example.com,0501234567,family,1,male,adult,أخ
فاطمة علي,fatima@example.com,0507654321,friends,0,female,adult,صديقة
محمد عبدالله,mohammed@example.com,,colleagues,1,male,adult,زميل عمل</pre>
                        </div>
                    </div>
                    
                    <div class="impro-download-sample">
                        <a href="<?php echo esc_url( IMPRO_URL . 'assets/sample-guests.csv' ); ?>" 
                           download="sample-guests.csv" 
                           class="button button-primary">
                            <?php _e( 'تحميل ملف CSV نموذجي', 'invitation-manager-pro' ); ?>
                        </a>
                        <p class="description"><?php _e( 'استخدم هذا الملف كنموذج لإنشاء ملفك الخاص.', 'invitation-manager-pro' ); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- بطاقة رفع الملف -->
        <div class="impro-card">
            <div class="impro-card-header">
                <h2><?php _e( 'رفع ملف CSV', 'invitation-manager-pro' ); ?></h2>
            </div>
            <div class="impro-card-body">
                <form method="post" 
                      action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" 
                      enctype="multipart/form-data" 
                      class="impro-import-form"
                      id="impro-guest-import-form">
                    <input type="hidden" name="action" value="impro_admin_action">
                    <input type="hidden" name="impro_action" value="import_guests">
                    <?php wp_nonce_field( 'impro_admin_action', '_wpnonce' ); ?>
                    
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label for="csv_file"><?php _e( 'ملف CSV', 'invitation-manager-pro' ); ?> <span class="required">*</span></label>
                                </th>
                                <td>
                                    <div class="impro-file-upload">
                                        <input type="file" 
                                               name="csv_file" 
                                               id="csv_file" 
                                               accept=".csv,text/csv" 
                                               required 
                                               class="impro-file-input">
                                        <div class="impro-file-dropzone" id="file-dropzone">
                                            <div class="dropzone-content">
                                                <span class="dashicons dashicons-upload"></span>
                                                <p><?php _e( 'اسحب الملف وأفلته هنا أو انقر للاختيار', 'invitation-manager-pro' ); ?></p>
                                                <p class="description"><?php _e( 'الحد الأقصى: 5 ميجابايت', 'invitation-manager-pro' ); ?></p>
                                            </div>
                                        </div>
                                        <div class="impro-file-info" id="file-info" style="display: none;">
                                            <span id="file-name"></span>
                                            <span id="file-size"></span>
                                            <button type="button" id="remove-file" class="button-link"><?php _e( 'إزالة', 'invitation-manager-pro' ); ?></button>
                                        </div>
                                    </div>
                                    <p class="description"><?php _e( 'يرجى رفع ملف CSV يحتوي على بيانات المدعوين.', 'invitation-manager-pro' ); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="event_id"><?php _e( 'ربط المدعوين بمناسبة', 'invitation-manager-pro' ); ?></label>
                                </th>
                                <td>
                                    <select name="event_id" id="event_id" class="regular-text">
                                        <option value="0"><?php _e( 'لا يوجد - استيراد المدعوين فقط', 'invitation-manager-pro' ); ?></option>
                                        <?php if ( ! empty( $events ) ) : ?>
                                            <?php foreach ( $events as $event ) : ?>
                                                <?php
                                                $event_id = isset( $event->id ) ? intval( $event->id ) : 0;
                                                $event_name = isset( $event->name ) ? esc_html( $event->name ) : __( 'مناسبة غير محددة', 'invitation-manager-pro' );
                                                $event_date = isset( $event->event_date ) ? esc_html( date_i18n( get_option( 'date_format' ), strtotime( $event->event_date ) ) ) : '';
                                                ?>
                                                <option value="<?php echo esc_attr( $event_id ); ?>">
                                                    <?php 
                                                    if ( ! empty( $event_date ) ) {
                                                        echo sprintf( '%s (%s)', $event_name, $event_date );
                                                    } else {
                                                        echo $event_name;
                                                    }
                                                    ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                    <p class="description"><?php _e( 'إذا اخترت مناسبة، سيتم إنشاء دعوات تلقائياً للمدعوين المستوردين وربطهم بهذه المناسبة.', 'invitation-manager-pro' ); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <?php _e( 'خيارات إضافية', 'invitation-manager-pro' ); ?>
                                </th>
                                <td>
                                    <fieldset>
                                        <legend class="screen-reader-text"><?php _e( 'خيارات الاستيراد', 'invitation-manager-pro' ); ?></legend>
                                        
                                        <label>
                                            <input type="checkbox" name="send_invitations" value="1">
                                            <?php _e( 'إرسال دعوات تلقائياً بعد الاستيراد', 'invitation-manager-pro' ); ?>
                                        </label>
                                        <p class="description"><?php _e( 'سيتم إرسال دعوات إلكترونية للمدعوين المستوردين تلقائياً.', 'invitation-manager-pro' ); ?></p>
                                        
                                        <label>
                                            <input type="checkbox" name="skip_duplicates" value="1" checked>
                                            <?php _e( 'تخطي المدعوين المكررين', 'invitation-manager-pro' ); ?>
                                        </label>
                                        <p class="description"><?php _e( 'سيتم تجاهل المدعوين الذين لديهم نفس البريد الإلكتروني أو الاسم.', 'invitation-manager-pro' ); ?></p>
                                        
                                        <label>
                                            <input type="checkbox" name="validate_data" value="1" checked>
                                            <?php _e( 'التحقق من صحة البيانات', 'invitation-manager-pro' ); ?>
                                        </label>
                                        <p class="description"><?php _e( 'التحقق من صحة البريد الإلكتروني وأرقام الهواتف قبل الاستيراد.', 'invitation-manager-pro' ); ?></p>
                                    </fieldset>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <?php 
                    submit_button( 
                        __( 'استيراد المدعوين', 'invitation-manager-pro' ), 
                        'primary', 
                        'submit', 
                        true, 
                        array( 'id' => 'impro-import-button' ) 
                    ); 
                    ?>
                </form>
            </div>
        </div>

        <!-- بطاقة المساعدة -->
        <div class="impro-card">
            <div class="impro-card-header">
                <h2><?php _e( 'مساعدة الاستيراد', 'invitation-manager-pro' ); ?></h2>
            </div>
            <div class="impro-card-body">
                <div class="impro-help-section">
                    <h3><?php _e( 'مشاكل شائعة وحلولها:', 'invitation-manager-pro' ); ?></h3>
                    
                    <div class="impro-help-item">
                        <h4><?php _e( 'الملف فارغ أو لا يحتوي على بيانات', 'invitation-manager-pro' ); ?></h4>
                        <p><?php _e( 'تأكد من أن ملف CSV يحتوي على صفوف بيانات بالإضافة إلى صف العناوين.', 'invitation-manager-pro' ); ?></p>
                    </div>
                    
                    <div class="impro-help-item">
                        <h4><?php _e( 'أخطاء في ترميز الأحرف العربية', 'invitation-manager-pro' ); ?></h4>
                        <p><?php _e( 'تأكد من أن ملف CSV محفوظ بترميز UTF-8 لدعم الأحرف العربية بشكل صحيح.', 'invitation-manager-pro' ); ?></p>
                    </div>
                    
                    <div class="impro-help-item">
                        <h4><?php _e( 'أخطاء في صيغة البريد الإلكتروني أو الهاتف', 'invitation-manager-pro' ); ?></h4>
                        <p><?php _e( 'تأكد من أن البريد الإلكتروني والهاتف بصيغة صحيحة. سيتم تجاهل السجلات غير الصحيحة.', 'invitation-manager-pro' ); ?></p>
                    </div>
                    
                    <div class="impro-help-item">
                        <h4><?php _e( 'حجم الملف كبير جداً', 'invitation-manager-pro' ); ?></h4>
                        <p><?php _e( 'للمحافظة على أداء النظام، الحد الأقصى لحجم الملف هو 5 ميجابايت. قم بتقسيم الملف الكبير إلى ملفات أصغر.', 'invitation-manager-pro' ); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.wrap {
    margin: 20px 20px 0 0;
}

.impro-import-dashboard {
    margin-top: 20px;
}

/* بطاقات */
.impro-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.impro-card-header {
    border-bottom: 1px solid #ccd0d4;
    background: #f9f9f9;
    padding: 15px 20px;
}

.impro-card-header h2 {
    margin: 0;
    color: #1d2327;
    font-size: 18px;
    font-weight: 600;
}

.impro-card-body {
    padding: 20px;
}

/* تعليمات الاستيراد */
.impro-instructions ul {
    list-style: disc;
    padding-right: 20px;
    margin: 15px 0;
}

.impro-instructions li {
    margin-bottom: 8px;
}

.impro-required-columns h3,
.impro-sample-data h3 {
    margin: 20px 0 10px 0;
    color: #1d2327;
}

.impro-code-block {
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
    margin: 10px 0;
    overflow-x: auto;
}

.impro-code-block pre {
    margin: 0;
    font-family: 'Courier New', Courier, monospace;
    font-size: 12px;
    line-height: 1.4;
    color: #333;
}

.impro-download-sample {
    margin-top: 20px;
    text-align: center;
}

.impro-download-sample .button {
    margin-bottom: 10px;
}

/* رفع الملف */
.impro-file-upload {
    position: relative;
    margin-bottom: 15px;
}

.impro-file-input {
    position: absolute;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
    z-index: 2;
}

.impro-file-dropzone {
    border: 2px dashed #ddd;
    border-radius: 6px;
    padding: 30px;
    text-align: center;
    background: #fafafa;
    transition: all 0.3s ease;
    cursor: pointer;
}

.impro-file-dropzone:hover,
.impro-file-dropzone.dragover {
    border-color: #0073aa;
    background: #f0f6fc;
}

.dropzone-content .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: #0073aa;
    margin-bottom: 15px;
}

.dropzone-content p {
    margin: 0 0 5px 0;
    color: #50575e;
    font-size: 14px;
}

.dropzone-content .description {
    font-size: 12px;
    color: #646970;
}

.impro-file-info {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 10px;
    background: #f0f6fc;
    border-radius: 4px;
    margin-top: 10px;
}

.impro-file-info #file-name {
    flex: 1;
    font-weight: 600;
    color: #1d2327;
}

.impro-file-info #file-size {
    color: #646970;
    font-size: 12px;
}

.button-link {
    color: #0073aa;
    text-decoration: none;
    background: none;
    border: none;
    cursor: pointer;
    padding: 0;
    font-size: 12px;
}

.button-link:hover {
    color: #005a87;
    text-decoration: underline;
}

/* جدول النموذج */
.form-table th {
    width: 200px;
    padding: 15px 10px 15px 0;
}

.form-table td {
    padding: 15px 0;
}

.required {
    color: #d63638;
}

/* أزرار الإرسال */
#impro-import-button {
    margin-top: 10px;
}

/* قسم المساعدة */
.impro-help-item {
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.impro-help-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.impro-help-item h4 {
    margin: 0 0 10px 0;
    color: #1d2327;
    font-size: 14px;
}

.impro-help-item p {
    margin: 0;
    color: #646970;
    font-size: 13px;
    line-height: 1.5;
}

/* تصميم متجاوب */
@media (max-width: 782px) {
    .form-table th {
        width: auto;
        padding-bottom: 0;
    }
    
    .form-table td {
        padding-top: 5px;
    }
    
    .impro-file-dropzone {
        padding: 20px 10px;
    }
    
    .impro-file-info {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
}

@media (max-width: 480px) {
    .impro-card-body {
        padding: 15px;
    }
    
    .impro-card-header {
        padding: 12px 15px;
    }
    
    .impro-file-dropzone {
        padding: 15px 5px;
    }
    
    .dropzone-content .dashicons {
        font-size: 36px;
        width: 36px;
        height: 36px;
    }
    
    .dropzone-content p {
        font-size: 12px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // تحسين تجربة رفع الملف
    var fileInput = $('#csv_file');
    var dropzone = $('#file-dropzone');
    var fileInfo = $('#file-info');
    var fileName = $('#file-name');
    var fileSize = $('#file-size');
    var removeFile = $('#remove-file');
    
    // معالجة اختيار الملف
    fileInput.on('change', function(e) {
        var file = e.target.files[0];
        if (file) {
            handleFile(file);
        }
    });
    
    // معالجة سحب وإفلات الملف
    dropzone.on('dragover', function(e) {
        e.preventDefault();
        $(this).addClass('dragover');
    });
    
    dropzone.on('dragleave', function(e) {
        e.preventDefault();
        $(this).removeClass('dragover');
    });
    
    dropzone.on('drop', function(e) {
        e.preventDefault();
        $(this).removeClass('dragover');
        
        var files = e.originalEvent.dataTransfer.files;
        if (files.length > 0) {
            var file = files[0];
            if (validateFile(file)) {
                handleFile(file);
                fileInput[0].files = files;
            }
        }
    });
    
    // إزالة الملف
    removeFile.on('click', function() {
        fileInput.val('');
        fileInfo.hide();
        dropzone.show();
    });
    
    // التحقق من صحة الملف
    function validateFile(file) {
        // التحقق من نوع الملف
        if (file.type !== 'text/csv' && file.type !== 'application/vnd.ms-excel' && !file.name.endsWith('.csv')) {
            alert('<?php esc_js_e( 'يرجى اختيار ملف CSV فقط', 'invitation-manager-pro' ); ?>');
            return false;
        }
        
        // التحقق من حجم الملف (5 ميجابايت كحد أقصى)
        if (file.size > 5 * 1024 * 1024) {
            alert('<?php esc_js_e( 'حجم الملف كبير جداً. الحد الأقصى هو 5 ميجابايت', 'invitation-manager-pro' ); ?>');
            return false;
        }
        
        return true;
    }
    
    // معالجة الملف
    function handleFile(file) {
        fileName.text(file.name);
        fileSize.text(formatFileSize(file.size));
        fileInfo.show();
        dropzone.hide();
    }
    
    // تنسيق حجم الملف
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        var k = 1024;
        var sizes = ['Bytes', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    // التحقق من صحة النموذج قبل الإرسال
    $('#impro-guest-import-form').on('submit', function(e) {
        var file = fileInput[0].files[0];
        if (!file) {
            alert('<?php esc_js_e( 'يرجى اختيار ملف CSV', 'invitation-manager-pro' ); ?>');
            e.preventDefault();
            return false;
        }
        
        if (!validateFile(file)) {
            e.preventDefault();
            return false;
        }
        
        // تحديث زر الإرسال
        var submitButton = $('#impro-import-button');
        var originalText = submitButton.val();
        submitButton.prop('disabled', true).val('<?php esc_js_e( 'جاري الاستيراد...', 'invitation-manager-pro' ); ?>');
    });
    
    // إغلاق الإشعارات تلقائياً
    setTimeout(function() {
        $('.notice.is-dismissible').fadeOut(500, function() {
            $(this).remove();
        });
    }, 5000);
    
    // إضافة تأثيرات تفاعلية
    $('.impro-card').hover(
        function() {
            $(this).css('box-shadow', '0 2px 8px rgba(0,0,0,0.15)');
        },
        function() {
            $(this).css('box-shadow', '0 1px 3px rgba(0,0,0,0.1)');
        }
    );
});
</script>
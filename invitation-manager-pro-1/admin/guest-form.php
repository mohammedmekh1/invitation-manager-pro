
<?php
/**
 * Guest form page.
 *
 * @package Invitation_Manager_Pro
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// التحقق من المتغيرات المطلوبة
if ( ! isset( $action ) ) {
    $action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : 'add';
}

if ( ! isset( $guest ) && isset( $_GET['guest_id'] ) ) {
    $guest_id = intval( $_GET['guest_id'] );
    $guest_manager = new IMPRO_Guest_Manager();
    $guest = $guest_manager->get_guest( $guest_id );
}

$is_edit = ( isset( $action ) && $action === 'edit' && isset( $guest ) && $guest );
$page_title = $is_edit ? __( 'تعديل المدعو', 'invitation-manager-pro' ) : __( 'إضافة مدعو جديد', 'invitation-manager-pro' );
$submit_button_text = $is_edit ? __( 'تحديث المدعو', 'invitation-manager-pro' ) : __( 'إضافة المدعو', 'invitation-manager-pro' );
$form_action = $is_edit ? 'update_guest' : 'create_guest';

$guest_id = $is_edit && isset( $guest->id ) ? $guest->id : 0;

// تهيئة بيانات المدعو بأمان
$guest_data = array(
    'name' => '',
    'email' => '',
    'phone' => '',
    'category' => '',
    'plus_one_allowed' => false,
    'gender' => '',
    'age_range' => '',
    'relationship' => ''
);

if ( $is_edit && isset( $guest ) ) {
    $guest_data = array(
        'name' => isset( $guest->name ) ? esc_attr( $guest->name ) : '',
        'email' => isset( $guest->email ) ? esc_attr( $guest->email ) : '',
        'phone' => isset( $guest->phone ) ? esc_attr( $guest->phone ) : '',
        'category' => isset( $guest->category ) ? esc_attr( $guest->category ) : '',
        'plus_one_allowed' => isset( $guest->plus_one_allowed ) ? (bool) $guest->plus_one_allowed : false,
        'gender' => isset( $guest->gender ) ? esc_attr( $guest->gender ) : '',
        'age_range' => isset( $guest->age_range ) ? esc_attr( $guest->age_range ) : '',
        'relationship' => isset( $guest->relationship ) ? esc_attr( $guest->relationship ) : ''
    );
}

// تعريف الفئات مع دعم الفلاتر
$guest_categories = apply_filters( 'impro_guest_categories', array(
    'family' => __( 'عائلة', 'invitation-manager-pro' ),
    'friends' => __( 'أصدقاء', 'invitation-manager-pro' ),
    'colleagues' => __( 'زملاء', 'invitation-manager-pro' ),
    'vip' => __( 'شخصيات مهمة', 'invitation-manager-pro' ),
    'other' => __( 'أخرى', 'invitation-manager-pro' )
) );

$guest_genders = apply_filters( 'impro_guest_genders', array(
    'male' => __( 'ذكر', 'invitation-manager-pro' ),
    'female' => __( 'أنثى', 'invitation-manager-pro' )
) );

$guest_age_ranges = apply_filters( 'impro_guest_age_ranges', array(
    'child' => __( 'طفل (0-12 سنة)', 'invitation-manager-pro' ),
    'teen' => __( 'مراهق (13-19 سنة)', 'invitation-manager-pro' ),
    'adult' => __( 'بالغ (20-59 سنة)', 'invitation-manager-pro' ),
    'senior' => __( 'كبير السن (60+ سنة)', 'invitation-manager-pro' )
) );

?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html( $page_title ); ?></h1>
    <hr class="wp-header-end">

    <?php 
    // عرض رسائل الخطأ
    if ( isset( $_GET['error'] ) ) : 
        $error_messages = array(
            'create_failed' => __( 'فشل في إنشاء المدعو. يرجى التحقق من البيانات والمحاولة مرة أخرى.', 'invitation-manager-pro' ),
            'update_failed' => __( 'فشل في تحديث المدعو. يرجى التحقق من البيانات والمحاولة مرة أخرى.', 'invitation-manager-pro' ),
            'validation_failed' => __( 'فشل التحقق من صحة البيانات. يرجى التأكد من إدخال جميع الحقول المطلوبة بشكل صحيح.', 'invitation-manager-pro' ),
            'duplicate_email' => __( 'البريد الإلكتروني مسجل مسبقاً لهذا الحدث.', 'invitation-manager-pro' ),
            'invalid_data' => __( 'بيانات غير صحيحة. يرجى التحقق من الإدخال.', 'invitation-manager-pro' )
        );
        
        $error_key = sanitize_text_field( $_GET['error'] );
        $error_message = isset( $error_messages[ $error_key ] ) ? $error_messages[ $error_key ] : $error_messages['validation_failed'];
    ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html( $error_message ); ?></p>
        </div>
    <?php endif; ?>

    <?php 
    // عرض رسائل النجاح
    if ( isset( $_GET['message'] ) ) : 
        $success_messages = array(
            'created' => __( 'تم إنشاء المدعو بنجاح.', 'invitation-manager-pro' ),
            'updated' => __( 'تم تحديث المدعو بنجاح.', 'invitation-manager-pro' ),
            'saved' => __( 'تم حفظ التغييرات بنجاح.', 'invitation-manager-pro' )
        );
        
        $message_key = sanitize_text_field( $_GET['message'] );
        $success_message = isset( $success_messages[ $message_key ] ) ? $success_messages[ $message_key ] : '';
        
        if ( ! empty( $success_message ) ) :
    ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html( $success_message ); ?></p>
        </div>
    <?php 
        endif;
    endif; 
    ?>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="impro-guest-form" enctype="multipart/form-data">
        <input type="hidden" name="action" value="impro_admin_action">
        <input type="hidden" name="impro_action" value="<?php echo esc_attr( $form_action ); ?>">
        <?php wp_nonce_field( 'impro_admin_action', '_wpnonce' ); ?>
        
        <?php if ( $is_edit && $guest_id > 0 ) : ?>
            <input type="hidden" name="guest_id" value="<?php echo esc_attr( $guest_id ); ?>">
        <?php endif; ?>

        <div class="postbox">
            <div class="postbox-header">
                <h2 class="hndle"><?php _e( 'المعلومات الأساسية', 'invitation-manager-pro' ); ?></h2>
            </div>
            <div class="inside">
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="guest_name"><?php _e( 'الاسم الكامل', 'invitation-manager-pro' ); ?> <span class="required">*</span></label>
                            </th>
                            <td>
                                <input 
                                    type="text" 
                                    name="guest_name" 
                                    id="guest_name" 
                                    value="<?php echo esc_attr( $guest_data['name'] ); ?>" 
                                    class="regular-text" 
                                    required 
                                    maxlength="255"
                                    placeholder="<?php esc_attr_e( 'مثال: أحمد محمد علي', 'invitation-manager-pro' ); ?>"
                                >
                                <p class="description"><?php _e( 'أدخل الاسم الكامل للمدعو', 'invitation-manager-pro' ); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="guest_email"><?php _e( 'البريد الإلكتروني', 'invitation-manager-pro' ); ?></label>
                            </th>
                            <td>
                                <input 
                                    type="email" 
                                    name="guest_email" 
                                    id="guest_email" 
                                    value="<?php echo esc_attr( $guest_data['email'] ); ?>" 
                                    class="regular-text"
                                    placeholder="<?php esc_attr_e( 'example@email.com', 'invitation-manager-pro' ); ?>"
                                >
                                <p class="description"><?php _e( 'البريد الإلكتروني للإرسال (اختياري)', 'invitation-manager-pro' ); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="guest_phone"><?php _e( 'رقم الهاتف', 'invitation-manager-pro' ); ?></label>
                            </th>
                            <td>
                                <input 
                                    type="tel" 
                                    name="guest_phone" 
                                    id="guest_phone" 
                                    value="<?php echo esc_attr( $guest_data['phone'] ); ?>" 
                                    class="regular-text"
                                    placeholder="<?php esc_attr_e( '+966 50 123 4567', 'invitation-manager-pro' ); ?>"
                                >
                                <p class="description"><?php _e( 'رقم الهاتف للتواصل (اختياري)', 'invitation-manager-pro' ); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="postbox">
            <div class="postbox-header">
                <h2 class="hndle"><?php _e( 'التصنيف والتفاصيل', 'invitation-manager-pro' ); ?></h2>
            </div>
            <div class="inside">
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="guest_category"><?php _e( 'الفئة', 'invitation-manager-pro' ); ?></label>
                            </th>
                            <td>
                                <select name="guest_category" id="guest_category" class="regular-text">
                                    <option value=""><?php _e( 'اختر فئة', 'invitation-manager-pro' ); ?></option>
                                    <?php foreach ( $guest_categories as $key => $label ) : ?>
                                        <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $guest_data['category'], $key ); ?>>
                                            <?php echo esc_html( $label ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php _e( 'تصنيف المدعو حسب العلاقة', 'invitation-manager-pro' ); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="guest_gender"><?php _e( 'الجنس', 'invitation-manager-pro' ); ?></label>
                            </th>
                            <td>
                                <select name="guest_gender" id="guest_gender" class="regular-text">
                                    <option value=""><?php _e( 'اختر', 'invitation-manager-pro' ); ?></option>
                                    <?php foreach ( $guest_genders as $key => $label ) : ?>
                                        <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $guest_data['gender'], $key ); ?>>
                                            <?php echo esc_html( $label ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php _e( 'جنس المدعو (اختياري)', 'invitation-manager-pro' ); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="guest_age_range"><?php _e( 'الفئة العمرية', 'invitation-manager-pro' ); ?></label>
                            </th>
                            <td>
                                <select name="guest_age_range" id="guest_age_range" class="regular-text">
                                    <option value=""><?php _e( 'اختر', 'invitation-manager-pro' ); ?></option>
                                    <?php foreach ( $guest_age_ranges as $key => $label ) : ?>
                                        <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $guest_data['age_range'], $key ); ?>>
                                            <?php echo esc_html( $label ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php _e( 'الفئة العمرية للمدعو (اختياري)', 'invitation-manager-pro' ); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="guest_relationship"><?php _e( 'صلة القرابة/العلاقة', 'invitation-manager-pro' ); ?></label>
                            </th>
                            <td>
                                <input 
                                    type="text" 
                                    name="guest_relationship" 
                                    id="guest_relationship" 
                                    value="<?php echo esc_attr( $guest_data['relationship'] ); ?>" 
                                    class="regular-text"
                                    placeholder="<?php esc_attr_e( 'مثال: أخ العريس، صديق الطفولة', 'invitation-manager-pro' ); ?>"
                                >
                                <p class="description"><?php _e( 'وصف تفصيلي للعلاقة (اختياري)', 'invitation-manager-pro' ); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="plus_one_allowed"><?php _e( 'مسموح بمرافق؟', 'invitation-manager-pro' ); ?></label>
                            </th>
                            <td>
                                <fieldset>
                                    <legend class="screen-reader-text"><?php _e( 'خيارات المرافق', 'invitation-manager-pro' ); ?></legend>
                                    <label>
                                        <input 
                                            type="checkbox" 
                                            name="plus_one_allowed" 
                                            id="plus_one_allowed" 
                                            value="1" 
                                            <?php checked( $guest_data['plus_one_allowed'] ); ?>
                                        >
                                        <?php _e( 'السماح لهذا المدعو بإحضار مرافق', 'invitation-manager-pro' ); ?>
                                    </label>
                                    <p class="description"><?php _e( 'حدد هذا الخيار إذا كان المدعو مسموحاً له بإحضار مرافق.', 'invitation-manager-pro' ); ?></p>
                                </fieldset>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="postbox">
            <div class="postbox-header">
                <h2 class="hndle"><?php _e( 'الخيارات الإضافية', 'invitation-manager-pro' ); ?></h2>
            </div>
            <div class="inside">
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="guest_notes"><?php _e( 'ملاحظات إضافية', 'invitation-manager-pro' ); ?></label>
                            </th>
                            <td>
                                <textarea 
                                    name="guest_notes" 
                                    id="guest_notes" 
                                    rows="4" 
                                    class="large-text" 
                                    placeholder="<?php esc_attr_e( 'أي ملاحظات خاصة حول هذا المدعو...', 'invitation-manager-pro' ); ?>"
                                ><?php echo isset( $guest->notes ) ? esc_textarea( $guest->notes ) : ''; ?></textarea>
                                <p class="description"><?php _e( 'ملاحظات خاصة (لا تظهر للمدعو)', 'invitation-manager-pro' ); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="special_needs"><?php _e( 'احتياجات خاصة', 'invitation-manager-pro' ); ?></label>
                            </th>
                            <td>
                                <textarea 
                                    name="special_needs" 
                                    id="special_needs" 
                                    rows="3" 
                                    class="large-text" 
                                    placeholder="<?php esc_attr_e( 'أي احتياجات خاصة (مثل: حساسية، إعاقة...)', 'invitation-manager-pro' ); ?>"
                                ><?php echo isset( $guest->special_needs ) ? esc_textarea( $guest->special_needs ) : ''; ?></textarea>
                                <p class="description"><?php _e( 'احتياجات خاصة يجب مراعاتها (اختياري)', 'invitation-manager-pro' ); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <?php 
        // أزرار الإرسال
        submit_button( 
            $submit_button_text, 
            'primary', 
            'submit', 
            true, 
            array( 'id' => 'impro-submit-button' ) 
        ); 
        ?>
        
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-guests' ) ); ?>" class="button button-secondary">
            <?php _e( 'إلغاء', 'invitation-manager-pro' ); ?>
        </a>
        
        <?php if ( $is_edit ) : ?>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-guests&action=delete&guest_id=' . $guest_id ) ); ?>" 
               class="button button-link-delete impro-delete-guest" 
               onclick="return confirm('<?php echo esc_js( __( 'هل أنت متأكد من حذف هذا المدعو؟', 'invitation-manager-pro' ) ); ?>');">
                <?php _e( 'حذف المدعو', 'invitation-manager-pro' ); ?>
            </a>
        <?php endif; ?>
    </form>
</div>

<style>
.wrap {
    margin: 20px 20px 0 0;
}

.postbox {
    margin-bottom: 20px;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.postbox-header {
    border-bottom: 1px solid #ccd0d4;
    background: #f9f9f9;
}

.hndle {
    font-size: 14px;
    padding: 8px 12px;
    margin: 0;
    line-height: 1.4;
}

.inside {
    padding: 0 12px 12px;
}

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

.description {
    color: #646970;
    font-size: 13px;
    margin-top: 5px;
}

/* تحسين مظهر الحقول */
.regular-text {
    width: 100%;
    max-width: 400px;
}

.large-text {
    width: 100%;
    max-width: 600px;
}

/* أزرار الحذف */
.button-link-delete {
    color: #d63638;
    text-decoration: none;
}

.button-link-delete:hover {
    color: #d63638;
    text-decoration: underline;
}

/* حالة الخطأ */
.error {
    border-color: #d63638 !important;
    box-shadow: 0 0 0 1px #d63638 !important;
}

/* تحسين مظهر زر الإرسال */
#impro-submit-button {
    margin-left: 10px;
}

/* حالة فارغة */
.impro-empty-state {
    text-align: center;
    padding: 40px 20px;
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
    
    .regular-text,
    .large-text {
        max-width: none;
    }
}

/* تحسين مظهر القوائم المنسدلة */
select.regular-text {
    min-width: 200px;
}

/* إشعارات */
.notice {
    margin: 15px 0;
}

/* زر الحذف */
.impro-delete-guest {
    float: left;
    margin-top: 5px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // التحقق من صحة النموذج قبل الإرسال
    $('.impro-guest-form').on('submit', function(e) {
        var isValid = true;
        var $form = $(this);
        
        // التحقق من الحقول المطلوبة
        $form.find('[required]').each(function() {
            if (!$(this).val().trim()) {
                $(this).addClass('error');
                isValid = false;
            } else {
                $(this).removeClass('error');
            }
        });
        
        // التحقق من صحة البريد الإلكتروني
        var email = $('#guest_email').val();
        if (email && !isValidEmail(email)) {
            $('#guest_email').addClass('error');
            alert('<?php esc_js_e( 'يرجى إدخال بريد إلكتروني صحيح', 'invitation-manager-pro' ); ?>');
            isValid = false;
        } else {
            $('#guest_email').removeClass('error');
        }
        
        // التحقق من صحة رقم الهاتف
        var phone = $('#guest_phone').val();
        if (phone && !isValidPhone(phone)) {
            $('#guest_phone').addClass('error');
            alert('<?php esc_js_e( 'يرجى إدخال رقم هاتف صحيح', 'invitation-manager-pro' ); ?>');
            isValid = false;
        } else {
            $('#guest_phone').removeClass('error');
        }
        
        if (!isValid) {
            e.preventDefault();
            return false;
        }
        
        // تحديث زر الإرسال
        var $submitButton = $('#impro-submit-button');
        var originalText = $submitButton.val();
        $submitButton.prop('disabled', true).val('<?php esc_js_e( 'جاري الحفظ...', 'invitation-manager-pro' ); ?>');
    });
    
    // إزالة خطأ عند الكتابة في الحقول
    $('.impro-guest-form input, .impro-guest-form textarea, .impro-guest-form select').on('input change', function() {
        $(this).removeClass('error');
    });
    
    // التحقق من صحة البريد الإلكتروني
    function isValidEmail(email) {
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    // التحقق من صحة رقم الهاتف (بسيط)
    function isValidPhone(phone) {
        // السماح بالأرقام والمسافات والشرطات والأقواس والرمز +
        var phoneRegex = /^[\+]?[0-9\s\-\(\)]{10,20}$/;
        return phoneRegex.test(phone.replace(/\s/g, ''));
    }
    
    // إغلاق الإشعارات تلقائياً
    setTimeout(function() {
        $('.notice.is-dismissible').fadeOut(500, function() {
            $(this).remove();
        });
    }, 5000);
    
    // تحسين تجربة المستخدم للقوائم المنسدلة
    $('select').on('focus', function() {
        $(this).css('outline', '1px solid #0073aa');
    }).on('blur', function() {
        $(this).css('outline', '');
    });
});
</script>
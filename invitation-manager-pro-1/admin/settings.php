<?php
/**
 * Settings page.
 *
 * @package Invitation_Manager_Pro
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// تهيئة الخيارات بأمان
$default_options = array(
    'impro_enable_guest_comments'   => 1,
    'impro_enable_plus_one'         => 1,
    'impro_default_guests_limit'    => 200,
    'impro_invitation_expiry'       => 30,
    'impro_enable_email'            => 1,
    'impro_email_subject'           => __( 'دعوة لحضور {event_name}', 'invitation-manager-pro' ),
    'impro_email_template'          => '',
    'impro_notification_emails'     => get_option( 'admin_email' ),
    'impro_qr_code_size'           => 200,
    'impro_enable_qr_codes'        => 1,
    'impro_keep_data_on_uninstall' => 0,
    'impro_enable_sms'            => 0,
    'impro_sms_gateway'           => 'twilio',
    'impro_sms_template'          => __( 'دعوة لحضور {event_name} في {event_date}. تفاصيل الدعوة: {invitation_url}', 'invitation-manager-pro' ),
    'impro_enable_whatsapp'       => 0,
    'impro_whatsapp_template'     => __( 'دعوة لحضور {event_name} في {event_date}. تفاصيل الدعوة: {invitation_url}', 'invitation-manager-pro' ),
    'impro_enable_push_notifications' => 0,
    'impro_auto_respond_rsvps'    => 1,
    'impro_rsvp_confirmation_message' => __( 'شكراً لتأكيد حضوركم. نتطلع لرؤيتكم في المناسبة!', 'invitation-manager-pro' ),
    'impro_enable_reminders'     => 1,
    'impro_reminder_days_before' => 3,
    'impro_reminder_template'    => __( 'تذكير: دعوتكم لحضور {event_name} في {event_date}. يرجى تأكيد حضوركم.', 'invitation-manager-pro' )
);

// الحصول على الخيارات الحالية بأمان
$options = array();
foreach ( $default_options as $key => $default_value ) {
    $options[ $key ] = get_option( $key, $default_value );
}

// ترجمة العلامات
$placeholders_help = array(
    '{guest_name}' => __( 'اسم المدعو', 'invitation-manager-pro' ),
    '{event_name}' => __( 'اسم المناسبة', 'invitation-manager-pro' ),
    '{event_date}' => __( 'تاريخ المناسبة', 'invitation-manager-pro' ),
    '{event_time}' => __( 'وقت المناسبة', 'invitation-manager-pro' ),
    '{venue}' => __( 'مكان المناسبة', 'invitation-manager-pro' ),
    '{invitation_url}' => __( 'رابط الدعوة', 'invitation-manager-pro' ),
    '{contact_info}' => __( 'معلومات الاتصال', 'invitation-manager-pro' )
);

?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e( 'إعدادات إدارة الدعوات', 'invitation-manager-pro' ); ?></h1>
    <hr class="wp-header-end">

    <?php 
    // عرض رسائل النجاح
    if ( isset( $_GET['message'] ) ) : 
        $success_messages = array(
            'settings_saved' => __( 'تم حفظ الإعدادات بنجاح.', 'invitation-manager-pro' ),
            'settings_reset' => __( 'تمت إعادة تعيين الإعدادات إلى القيم الافتراضية.', 'invitation-manager-pro' ),
            'test_email_sent' => __( 'تم إرسال البريد الإلكتروني التجريبي بنجاح.', 'invitation-manager-pro' ),
            'test_sms_sent' => __( 'تم إرسال الرسالة النصية التجريبية بنجاح.', 'invitation-manager-pro' )
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

    <?php 
    // عرض رسائل الخطأ
    if ( isset( $_GET['error'] ) ) : 
        $error_messages = array(
            'save_failed' => __( 'فشل في حفظ الإعدادات. يرجى المحاولة مرة أخرى.', 'invitation-manager-pro' ),
            'validation_failed' => __( 'فشل في التحقق من صحة البيانات. يرجى التحقق من الإعدادات.', 'invitation-manager-pro' ),
            'permission_denied' => __( 'ليس لديك الصلاحية لتعديل الإعدادات.', 'invitation-manager-pro' ),
            'test_email_failed' => __( 'فشل في إرسال البريد الإلكتروني التجريبي.', 'invitation-manager-pro' ),
            'test_sms_failed' => __( 'فشل في إرسال الرسالة النصية التجريبية.', 'invitation-manager-pro' )
        );
        
        $error_key = sanitize_text_field( $_GET['error'] );
        $error_text = isset( $error_messages[ $error_key ] ) ? $error_messages[ $error_key ] : __( 'حدث خطأ غير متوقع.', 'invitation-manager-pro' );
    ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html( $error_text ); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="impro-settings-form">
        <input type="hidden" name="action" value="impro_admin_action">
        <input type="hidden" name="impro_action" value="save_settings">
        <?php wp_nonce_field( 'impro_admin_action', '_wpnonce' ); ?>

        <!-- شريط التنقل -->
        <div class="impro-settings-navigation">
            <ul class="impro-nav-tabs">
                <li class="active"><a href="#general-settings"><?php _e( 'عامة', 'invitation-manager-pro' ); ?></a></li>
                <li><a href="#email-settings"><?php _e( 'البريد الإلكتروني', 'invitation-manager-pro' ); ?></a></li>
                <li><a href="#sms-settings"><?php _e( 'الرسائل النصية', 'invitation-manager-pro' ); ?></a></li>
                <li><a href="#notifications"><?php _e( 'الإشعارات', 'invitation-manager-pro' ); ?></a></li>
                <li><a href="#qr-settings"><?php _e( 'رموز QR', 'invitation-manager-pro' ); ?></a></li>
                <li><a href="#advanced-settings"><?php _e( 'متقدمة', 'invitation-manager-pro' ); ?></a></li>
            </ul>
        </div>

        <!-- إعدادات عامة -->
        <div id="general-settings" class="impro-settings-section active">
            <h2 class="title"><?php _e( 'إعدادات عامة', 'invitation-manager-pro' ); ?></h2>
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="impro_enable_guest_comments"><?php _e( 'تمكين تعليقات المدعوين', 'invitation-manager-pro' ); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" 
                                   name="impro_enable_guest_comments" 
                                   id="impro_enable_guest_comments" 
                                   value="1" 
                                   <?php checked( $options['impro_enable_guest_comments'] ); ?>>
                            <p class="description"><?php _e( 'السماح للمدعوين بإضافة تعليقات عند تأكيد الحضور.', 'invitation-manager-pro' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="impro_enable_plus_one"><?php _e( 'تمكين خيار المرافق', 'invitation-manager-pro' ); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" 
                                   name="impro_enable_plus_one" 
                                   id="impro_enable_plus_one" 
                                   value="1" 
                                   <?php checked( $options['impro_enable_plus_one'] ); ?>>
                            <p class="description"><?php _e( 'السماح للمدعوين بإضافة مرافقين.', 'invitation-manager-pro' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="impro_default_guests_limit"><?php _e( 'الحد الافتراضي للمدعوين لكل مناسبة', 'invitation-manager-pro' ); ?></label>
                        </th>
                        <td>
                            <input type="number" 
                                   name="impro_default_guests_limit" 
                                   id="impro_default_guests_limit" 
                                   value="<?php echo esc_attr( $options['impro_default_guests_limit'] ); ?>" 
                                   class="small-text"
                                   min="1"
                                   max="10000">
                            <p class="description"><?php _e( 'الحد الأقصى لعدد المدعوين المسموح به لكل مناسبة بشكل افتراضي.', 'invitation-manager-pro' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="impro_invitation_expiry"><?php _e( 'مدة صلاحية الدعوة (بالأيام)', 'invitation-manager-pro' ); ?></label>
                        </th>
                        <td>
                            <input type="number" 
                                   name="impro_invitation_expiry" 
                                   id="impro_invitation_expiry" 
                                   value="<?php echo esc_attr( $options['impro_invitation_expiry'] ); ?>" 
                                   class="small-text"
                                   min="1"
                                   max="365">
                            <p class="description"><?php _e( 'عدد الأيام التي تظل فيها الدعوة صالحة بعد إرسالها. اتركها فارغة لعدم انتهاء الصلاحية.', 'invitation-manager-pro' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="impro_auto_respond_rsvps"><?php _e( 'الرد التلقائي على تأكيدات الحضور', 'invitation-manager-pro' ); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" 
                                   name="impro_auto_respond_rsvps" 
                                   id="impro_auto_respond_rsvps" 
                                   value="1" 
                                   <?php checked( $options['impro_auto_respond_rsvps'] ); ?>>
                            <p class="description"><?php _e( 'إرسال رد تلقائي للمدعوين عند تأكيد الحضور.', 'invitation-manager-pro' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="impro_rsvp_confirmation_message"><?php _e( 'رسالة تأكيد الحضور التلقائية', 'invitation-manager-pro' ); ?></label>
                        </th>
                        <td>
                            <textarea 
                                name="impro_rsvp_confirmation_message" 
                                id="impro_rsvp_confirmation_message" 
                                rows="3" 
                                class="large-text"
                                placeholder="<?php echo esc_attr( $default_options['impro_rsvp_confirmation_message'] ); ?>"
                            ><?php echo esc_textarea( $options['impro_rsvp_confirmation_message'] ); ?></textarea>
                            <p class="description"><?php _e( 'الرسالة التي تُرسل تلقائياً عند تأكيد الحضور.', 'invitation-manager-pro' ); ?></p>
                            <div class="impro-placeholders-help">
                                <strong><?php _e( 'العلامات المتاحة:', 'invitation-manager-pro' ); ?></strong>
                                <?php foreach ( $placeholders_help as $placeholder => $description ) : ?>
                                    <span class="placeholder-tag" title="<?php echo esc_attr( $description ); ?>"><?php echo esc_html( $placeholder ); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- إعدادات البريد الإلكتروني -->
        <div id="email-settings" class="impro-settings-section">
            <h2 class="title"><?php _e( 'إعدادات البريد الإلكتروني', 'invitation-manager-pro' ); ?></h2>
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="impro_enable_email"><?php _e( 'تمكين إرسال البريد الإلكتروني', 'invitation-manager-pro' ); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" 
                                   name="impro_enable_email" 
                                   id="impro_enable_email" 
                                   value="1" 
                                   <?php checked( $options['impro_enable_email'] ); ?>>
                            <p class="description"><?php _e( 'تفعيل أو تعطيل إرسال الدعوات عبر البريد الإلكتروني.', 'invitation-manager-pro' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="impro_email_subject"><?php _e( 'موضوع البريد الإلكتروني الافتراضي', 'invitation-manager-pro' ); ?></label>
                        </th>
                        <td>
                            <input type="text" 
                                   name="impro_email_subject" 
                                   id="impro_email_subject" 
                                   value="<?php echo esc_attr( $options['impro_email_subject'] ); ?>" 
                                   class="regular-text"
                                   placeholder="<?php echo esc_attr( $default_options['impro_email_subject'] ); ?>">
                            <p class="description"><?php _e( 'استخدم {event_name} لاسم المناسبة.', 'invitation-manager-pro' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="impro_email_template"><?php _e( 'قالب البريد الإلكتروني الافتراضي', 'invitation-manager-pro' ); ?></label>
                        </th>
                        <td>
                            <?php 
                            wp_editor( 
                                $options['impro_email_template'], 
                                'impro_email_template', 
                                array( 
                                    'textarea_name' => 'impro_email_template', 
                                    'textarea_rows' => 10,
                                    'media_buttons' => false,
                                    'tinymce' => array(
                                        'toolbar1' => 'bold,italic,underline,strikethrough,|,alignleft,aligncenter,alignright,|,bullist,numlist,|,link,unlink,|,undo,redo',
                                        'toolbar2' => '',
                                    ),
                                ) 
                            ); 
                            ?>
                            <p class="description"><?php _e( 'استخدم المتغيرات التالية: {guest_name}, {event_name}, {event_date}, {event_time}, {venue}, {invitation_url}.', 'invitation-manager-pro' ); ?></p>
                            <div class="impro-placeholders-help">
                                <strong><?php _e( 'العلامات المتاحة:', 'invitation-manager-pro' ); ?></strong>
                                <?php foreach ( $placeholders_help as $placeholder => $description ) : ?>
                                    <span class="placeholder-tag" title="<?php echo esc_attr( $description ); ?>"><?php echo esc_html( $placeholder ); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="impro_notification_emails"><?php _e( 'رسائل البريد الإلكتروني للإشعارات', 'invitation-manager-pro' ); ?></label>
                        </th>
                        <td>
                            <input type="text" 
                                   name="impro_notification_emails" 
                                   id="impro_notification_emails" 
                                   value="<?php echo esc_attr( $options['impro_notification_emails'] ); ?>" 
                                   class="regular-text"
                                   placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>">
                            <p class="description"><?php _e( 'عنوان بريد إلكتروني واحد أو أكثر مفصولة بفاصلة لاستقبال الإشعارات (مثل تأكيد الحضور).', 'invitation-manager-pro' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e( 'اختبار البريد الإلكتروني', 'invitation-manager-pro' ); ?></th>
                        <td>
                            <button type="button" 
                                    class="button secondary impro-test-email-button" 
                                    data-nonce="<?php echo esc_attr( wp_create_nonce( 'impro_test_email' ) ); ?>">
                                <?php _e( 'إرسال بريد تجريبي', 'invitation-manager-pro' ); ?>
                            </button>
                            <p class="description"><?php _e( 'إرسال بريد إلكتروني تجريبي للتحقق من إعدادات البريد.', 'invitation-manager-pro' ); ?></p>
                            <div class="impro-test-result" id="email-test-result"></div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- إعدادات الرسائل النصية -->
        <div id="sms-settings" class="impro-settings-section">
            <h2 class="title"><?php _e( 'إعدادات الرسائل النصية', 'invitation-manager-pro' ); ?></h2>
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="impro_enable_sms"><?php _e( 'تمكين إرسال الرسائل النصية', 'invitation-manager-pro' ); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" 
                                   name="impro_enable_sms" 
                                   id="impro_enable_sms" 
                                   value="1" 
                                   <?php checked( $options['impro_enable_sms'] ); ?>>
                            <p class="description"><?php _e( 'تفعيل أو تعطيل إرسال الدعوات عبر الرسائل النصية.', 'invitation-manager-pro' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="impro_sms_gateway"><?php _e( 'بوابة الرسائل النصية', 'invitation-manager-pro' ); ?></label>
                        </th>
                        <td>
                            <select name="impro_sms_gateway" id="impro_sms_gateway">
                                <option value="twilio" <?php selected( $options['impro_sms_gateway'], 'twilio' ); ?>>Twilio</option>
                                <option value="nexmo" <?php selected( $options['impro_sms_gateway'], 'nexmo' ); ?>>Nexmo (Vonage)</option>
                                <option value="custom" <?php selected( $options['impro_sms_gateway'], 'custom' ); ?>><?php _e( 'مخصص', 'invitation-manager-pro' ); ?></option>
                            </select>
                            <p class="description"><?php _e( 'اختر بوابة الرسائل النصية التي تستخدمها.', 'invitation-manager-pro' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="impro_sms_template"><?php _e( 'قالب الرسالة النصية الافتراضي', 'invitation-manager-pro' ); ?></label>
                        </th>
                        <td>
                            <textarea 
                                name="impro_sms_template" 
                                id="impro_sms_template" 
                                rows="4" 
                                class="large-text"
                                placeholder="<?php echo esc_attr( $default_options['impro_sms_template'] ); ?>"
                            ><?php echo esc_textarea( $options['impro_sms_template'] ); ?></textarea>
                            <p class="description"><?php _e( 'قالب الرسالة النصية الافتراضي. الحد الأقصى 160 حرف.', 'invitation-manager-pro' ); ?></p>
                            <div class="impro-placeholders-help">
                                <strong><?php _e( 'العلامات المتاحة:', 'invitation-manager-pro' ); ?></strong>
                                <?php foreach ( $placeholders_help as $placeholder => $description ) : ?>
                                    <span class="placeholder-tag" title="<?php echo esc_attr( $description ); ?>"><?php echo esc_html( $placeholder ); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e( 'اختبار الرسائل النصية', 'invitation-manager-pro' ); ?></th>
                        <td>
                            <button type="button" 
                                    class="button secondary impro-test-sms-button" 
                                    data-nonce="<?php echo esc_attr( wp_create_nonce( 'impro_test_sms' ) ); ?>">
                                <?php _e( 'إرسال رسالة تجريبية', 'invitation-manager-pro' ); ?>
                            </button>
                            <p class="description"><?php _e( 'إرسال رسالة نصية تجريبية للتحقق من إعدادات الرسائل.', 'invitation-manager-pro' ); ?></p>
                            <div class="impro-test-result" id="sms-test-result"></div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- إعدادات الإشعارات -->
        <div id="notifications" class="impro-settings-section">
            <h2 class="title"><?php _e( 'إعدادات الإشعارات', 'invitation-manager-pro' ); ?></h2>
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="impro_enable_whatsapp"><?php _e( 'تمكين إشعارات واتساب', 'invitation-manager-pro' ); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" 
                                   name="impro_enable_whatsapp" 
                                   id="impro_enable_whatsapp" 
                                   value="1" 
                                   <?php checked( $options['impro_enable_whatsapp'] ); ?>>
                            <p class="description"><?php _e( 'تفعيل إرسال الإشعارات عبر واتساب.', 'invitation-manager-pro' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="impro_whatsapp_template"><?php _e( 'قالب إشعارات واتساب', 'invitation-manager-pro' ); ?></label>
                        </th>
                        <td>
                            <textarea 
                                name="impro_whatsapp_template" 
                                id="impro_whatsapp_template" 
                                rows="4" 
                                class="large-text"
                                placeholder="<?php echo esc_attr( $default_options['impro_whatsapp_template'] ); ?>"
                            ><?php echo esc_textarea( $options['impro_whatsapp_template'] ); ?></textarea>
                            <p class="description"><?php _e( 'قالب إشعارات واتساب الافتراضي.', 'invitation-manager-pro' ); ?></p>
                            <div class="impro-placeholders-help">
                                <strong><?php _e( 'العلامات المتاحة:', 'invitation-manager-pro' ); ?></strong>
                                <?php foreach ( $placeholders_help as $placeholder => $description ) : ?>
                                    <span class="placeholder-tag" title="<?php echo esc_attr( $description ); ?>"><?php echo esc_html( $placeholder ); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="impro_enable_push_notifications"><?php _e( 'تمكين الإشعارات الفورية', 'invitation-manager-pro' ); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" 
                                   name="impro_enable_push_notifications" 
                                   id="impro_enable_push_notifications" 
                                   value="1" 
                                   <?php checked( $options['impro_enable_push_notifications'] ); ?>>
                            <p class="description"><?php _e( 'تفعيل الإشعارات الفورية للمدعوين.', 'invitation-manager-pro' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="impro_enable_reminders"><?php _e( 'تمكين التذكيرات التلقائية', 'invitation-manager-pro' ); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" 
                                   name="impro_enable_reminders" 
                                   id="impro_enable_reminders" 
                                   value="1" 
                                   <?php checked( $options['impro_enable_reminders'] ); ?>>
                            <p class="description"><?php _e( 'إرسال تذكيرات تلقائية قبل المناسبة.', 'invitation-manager-pro' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="impro_reminder_days_before"><?php _e( 'أيام التذكير قبل المناسبة', 'invitation-manager-pro' ); ?></label>
                        </th>
                        <td>
                            <input type="number" 
                                   name="impro_reminder_days_before" 
                                   id="impro_reminder_days_before" 
                                   value="<?php echo esc_attr( $options['impro_reminder_days_before'] ); ?>" 
                                   class="small-text"
                                   min="1"
                                   max="30">
                            <p class="description"><?php _e( 'عدد الأيام قبل المناسبة لإرسال التذكير.', 'invitation-manager-pro' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="impro_reminder_template"><?php _e( 'قالب التذكير التلقائي', 'invitation-manager-pro' ); ?></label>
                        </th>
                        <td>
                            <textarea 
                                name="impro_reminder_template" 
                                id="impro_reminder_template" 
                                rows="3" 
                                class="large-text"
                                placeholder="<?php echo esc_attr( $default_options['impro_reminder_template'] ); ?>"
                            ><?php echo esc_textarea( $options['impro_reminder_template'] ); ?></textarea>
                            <p class="description"><?php _e( 'قالب التذكير التلقائي قبل المناسبة.', 'invitation-manager-pro' ); ?></p>
                            <div class="impro-placeholders-help">
                                <strong><?php _e( 'العلامات المتاحة:', 'invitation-manager-pro' ); ?></strong>
                                <?php foreach ( $placeholders_help as $placeholder => $description ) : ?>
                                    <span class="placeholder-tag" title="<?php echo esc_attr( $description ); ?>"><?php echo esc_html( $placeholder ); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- إعدادات رموز QR -->
        <div id="qr-settings" class="impro-settings-section">
            <h2 class="title"><?php _e( 'إعدادات QR Code', 'invitation-manager-pro' ); ?></h2>
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="impro_enable_qr_codes"><?php _e( 'تمكين QR Code', 'invitation-manager-pro' ); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" 
                                   name="impro_enable_qr_codes" 
                                   id="impro_enable_qr_codes" 
                                   value="1" 
                                   <?php checked( $options['impro_enable_qr_codes'] ); ?>>
                            <p class="description"><?php _e( 'تفعيل أو تعطيل إنشاء رموز QR للدعوات.', 'invitation-manager-pro' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="impro_qr_code_size"><?php _e( 'حجم QR Code (بالبكسل)', 'invitation-manager-pro' ); ?></label>
                        </th>
                        <td>
                            <input type="number" 
                                   name="impro_qr_code_size" 
                                   id="impro_qr_code_size" 
                                   value="<?php echo esc_attr( $options['impro_qr_code_size'] ); ?>" 
                                   class="small-text"
                                   min="100"
                                   max="1000"
                                   step="10">
                            <p class="description"><?php _e( 'حجم رمز QR بالبكسل (مثال: 200).', 'invitation-manager-pro' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e( 'معاينة QR Code', 'invitation-manager-pro' ); ?></th>
                        <td>
                            <div class="impro-qr-preview">
                                <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZmZmIi8+PHBhdGggZD0iTTAgMGgxMDB2MTAwSDB6IiBmaWxsPSIjMDAwIi8+PC9zdmc+" 
                                     alt="<?php _e( 'معاينة QR Code', 'invitation-manager-pro' ); ?>" 
                                     id="qr-preview-image">
                            </div>
                            <p class="description"><?php _e( 'معاينة حجم رمز QR بناءً على الإعدادات.', 'invitation-manager-pro' ); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- إعدادات متقدمة -->
        <div id="advanced-settings" class="impro-settings-section">
            <h2 class="title"><?php _e( 'إعدادات متقدمة', 'invitation-manager-pro' ); ?></h2>
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="impro_keep_data_on_uninstall"><?php _e( 'الاحتفاظ بالبيانات عند إلغاء التثبيت', 'invitation-manager-pro' ); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" 
                                   name="impro_keep_data_on_uninstall" 
                                   id="impro_keep_data_on_uninstall" 
                                   value="1" 
                                   <?php checked( $options['impro_keep_data_on_uninstall'] ); ?>>
                            <p class="description"><?php _e( 'إذا تم تحديد هذا الخيار، لن يتم حذف بيانات الإضافة (المناسبات، المدعوين، إلخ) عند إلغاء تثبيت الإضافة.', 'invitation-manager-pro' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e( 'إعادة تعيين الإعدادات', 'invitation-manager-pro' ); ?></th>
                        <td>
                            <button type="button" 
                                    class="button secondary impro-reset-settings-button" 
                                    data-nonce="<?php echo esc_attr( wp_create_nonce( 'impro_reset_settings' ) ); ?>"
                                    onclick="return confirm('<?php echo esc_js( __( 'هل أنت متأكد من إعادة تعيين جميع الإعدادات إلى القيم الافتراضية؟', 'invitation-manager-pro' ) ); ?>');">
                                <?php _e( 'إعادة تعيين الإعدادات', 'invitation-manager-pro' ); ?>
                            </button>
                            <p class="description"><?php _e( 'إعادة تعيين جميع الإعدادات إلى القيم الافتراضية.', 'invitation-manager-pro' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e( 'نسخة الإضافة', 'invitation-manager-pro' ); ?></th>
                        <td>
                            <p><strong><?php echo defined( 'IMPRO_VERSION' ) ? IMPRO_VERSION : 'غير محددة'; ?></strong></p>
                            <p class="description"><?php _e( 'إصدار إضافة إدارة الدعوات الحالية.', 'invitation-manager-pro' ); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <?php 
        submit_button( 
            __( 'حفظ الإعدادات', 'invitation-manager-pro' ), 
            'primary', 
            'submit', 
            true, 
            array( 'id' => 'impro-save-settings-button' ) 
        ); 
        ?>
        
        <button type="button" class="button secondary" id="impro-reset-form-button">
            <?php _e( 'إعادة تعيين النموذج', 'invitation-manager-pro' ); ?>
        </button>
    </form>
</div>

<style>
.wrap {
    margin: 20px 20px 0 0;
}

/* شريط التنقل */
.impro-settings-navigation {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.impro-nav-tabs {
    display: flex;
    margin: 0;
    padding: 0;
    list-style: none;
    border-bottom: 1px solid #ccd0d4;
    flex-wrap: wrap;
}

.impro-nav-tabs li {
    margin-bottom: -1px;
}

.impro-nav-tabs a {
    display: block;
    padding: 15px 20px;
    text-decoration: none;
    border-bottom: 3px solid transparent;
    color: #50575e;
    font-weight: 600;
    transition: all 0.3s ease;
}

.impro-nav-tabs li.active a,
.impro-nav-tabs a:hover {
    color: #0073aa;
    border-bottom-color: #0073aa;
    background: #f6f7f7;
}

/* أقسام الإعدادات */
.impro-settings-section {
    display: none;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
    margin-bottom: 20px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.impro-settings-section.active {
    display: block;
}

.impro-settings-section .title {
    margin-top: 0;
    margin-bottom: 20px;
    color: #1d2327;
    font-size: 20px;
    border-bottom: 1px solid #ccd0d4;
    padding-bottom: 15px;
}

/* جداول النماذج */
.form-table th {
    width: 250px;
    padding: 15px 10px 15px 0;
}

.form-table td {
    padding: 15px 0;
}

.form-table .description {
    color: #646970;
    font-size: 13px;
    margin-top: 5px;
}

/* حقول الإدخال */
input[type="text"],
input[type="number"],
input[type="email"],
textarea {
    width: 100%;
    max-width: 500px;
}

input.small-text {
    max-width: 100px;
}

input.regular-text {
    max-width: 400px;
}

/* شارات العلامات */
.impro-placeholders-help {
    margin-top: 10px;
    padding: 10px;
    background: #f9f9f9;
    border: 1px solid #e5e5e5;
    border-radius: 4px;
    font-size: 12px;
}

.impro-placeholders-help strong {
    display: block;
    margin-bottom: 5px;
    color: #1d2327;
}

.placeholder-tag {
    display: inline-block;
    padding: 2px 6px;
    background: #e5e5e5;
    border-radius: 3px;
    margin: 2px;
    font-family: monospace;
    font-size: 11px;
    cursor: help;
}

/* معاينة QR Code */
.impro-qr-preview {
    margin: 10px 0;
    padding: 10px;
    background: #f9f9f9;
    border: 1px solid #e5e5e5;
    border-radius: 4px;
    text-align: center;
}

.impro-qr-preview img {
    max-width: 200px;
    height: auto;
    border: 1px solid #ddd;
    padding: 5px;
    background: #fff;
}

/* نتائج الاختبار */
.impro-test-result {
    margin-top: 10px;
    padding: 10px;
    border-radius: 4px;
    display: none;
}

.impro-test-result.success {
    background: #e6f4ea;
    border: 1px solid #b3e0c4;
    color: #008a20;
}

.impro-test-result.error {
    background: #f8e2df;
    border: 1px solid #f0b3b3;
    color: #d63638;
}

/* زر الحفظ */
#impro-save-settings-button {
    margin-left: 10px;
}

/* تصميم متجاوب */
@media (max-width: 782px) {
    .impro-nav-tabs {
        flex-direction: column;
    }
    
    .impro-nav-tabs a {
        border-bottom: 1px solid #ccd0d4;
        border-right: 3px solid transparent;
    }
    
    .impro-nav-tabs li.active a,
    .impro-nav-tabs a:hover {
        border-bottom-color: #ccd0d4;
        border-right-color: #0073aa;
    }
    
    .form-table th {
        width: auto;
        padding-bottom: 0;
    }
    
    .form-table td {
        padding-top: 5px;
    }
    
    input[type="text"],
    input[type="number"],
    input[type="email"],
    textarea {
        max-width: none;
    }
    
    .impro-placeholders-help {
        font-size: 11px;
    }
    
    .placeholder-tag {
        font-size: 10px;
        padding: 1px 4px;
    }
}

@media (max-width: 480px) {
    .impro-nav-tabs a {
        padding: 12px 15px;
        font-size: 14px;
    }
    
    .impro-settings-section {
        padding: 15px;
    }
    
    .impro-settings-section .title {
        font-size: 18px;
        padding-bottom: 10px;
    }
    
    .form-table th,
    .form-table td {
        display: block;
        width: 100% !important;
        padding: 10px 0 !important;
    }
    
    .form-table th {
        font-weight: 600;
        color: #1d2327;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // تبديل بين أقسام الإعدادات
    $('.impro-nav-tabs a').on('click', function(e) {
        e.preventDefault();
        
        var target = $(this).attr('href');
        
        // تحديث التنقل
        $('.impro-nav-tabs li').removeClass('active');
        $(this).parent().addClass('active');
        
        // إظهار القسم المستهدف وإخفاء الآخرين
        $('.impro-settings-section').removeClass('active');
        $(target).addClass('active');
        
        // تحديث عنوان URL
        if (history.pushState) {
            history.pushState(null, null, target);
        }
    });
    
    // تحميل القسم من عنوان URL
    var hash = window.location.hash;
    if (hash) {
        $('.impro-nav-tabs a[href="' + hash + '"]').click();
    }
    
    // تحديث معاينة QR Code
    $('#impro_qr_code_size').on('input', function() {
        var size = $(this).val();
        if (size >= 100 && size <= 1000) {
            $('#qr-preview-image').css('width', size + 'px').css('height', size + 'px');
        }
    });
    
    // اختبار البريد الإلكتروني
    $('.impro-test-email-button').on('click', function() {
        var $button = $(this);
        var nonce = $button.data('nonce');
        var $result = $('#email-test-result');
        
        $button.prop('disabled', true).text('<?php echo esc_js( __( 'جاري الإرسال...', 'invitation-manager-pro' ) ); ?>');
        $result.hide();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'impro_test_email',
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    $result.removeClass('error').addClass('success').html(response.data).show();
                } else {
                    $result.removeClass('success').addClass('error').html(response.data).show();
                }
            },
            error: function() {
                $result.removeClass('success').addClass('error').html('<?php echo esc_js( __( 'فشل في إرسال البريد التجريبي', 'invitation-manager-pro' ) ); ?>').show();
            },
            complete: function() {
                $button.prop('disabled', false).text('<?php echo esc_js( __( 'إرسال بريد تجريبي', 'invitation-manager-pro' ) ); ?>');
            }
        });
    });
    
    // اختبار الرسائل النصية
    $('.impro-test-sms-button').on('click', function() {
        var $button = $(this);
        var nonce = $button.data('nonce');
        var $result = $('#sms-test-result');
        
        $button.prop('disabled', true).text('<?php echo esc_js( __( 'جاري الإرسال...', 'invitation-manager-pro' ) ); ?>');
        $result.hide();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'impro_test_sms',
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    $result.removeClass('error').addClass('success').html(response.data).show();
                } else {
                    $result.removeClass('success').addClass('error').html(response.data).show();
                }
            },
            error: function() {
                $result.removeClass('success').addClass('error').html('<?php echo esc_js( __( 'فشل في إرسال الرسالة التجريبية', 'invitation-manager-pro' ) ); ?>').show();
            },
            complete: function() {
                $button.prop('disabled', false).text('<?php echo esc_js( __( 'إرسال رسالة تجريبية', 'invitation-manager-pro' ) ); ?>');
            }
        });
    });
    
    // إعادة تعيين الإعدادات
    $('.impro-reset-settings-button').on('click', function() {
        if (confirm('<?php echo esc_js( __( 'هل أنت متأكد من إعادة تعيين جميع الإعدادات إلى القيم الافتراضية؟', 'invitation-manager-pro' ) ); ?>')) {
            // إرسال طلب إعادة التعيين
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'impro_reset_settings',
                    nonce: $(this).data('nonce')
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('<?php echo esc_js( __( 'فشل في إعادة تعيين الإعدادات', 'invitation-manager-pro' ) ); ?>');
                    }
                },
                error: function() {
                    alert('<?php echo esc_js( __( 'فشل في إعادة تعيين الإعدادات', 'invitation-manager-pro' ) ); ?>');
                }
            });
        }
    });
    
    // إعادة تعيين النموذج
    $('#impro-reset-form-button').on('click', function() {
        if (confirm('<?php echo esc_js( __( 'هل أنت متأكد من إعادة تعيين النموذج؟ سيتم فقدان جميع التغييرات غير المحفوظة.', 'invitation-manager-pro' ) ); ?>')) {
            $('.impro-settings-form')[0].reset();
            // إعادة تحميل محرر WordPress إذا كان موجوداً
            if (typeof tinymce !== 'undefined') {
                tinymce.get('impro_email_template').setContent('');
            }
        }
    });
    
    // التحقق من صحة النموذج قبل الإرسال
    $('.impro-settings-form').on('submit', function() {
        var isValid = true;
        
        // التحقق من الحقول المطلوبة
        $(this).find('[required]').each(function() {
            if (!$(this).val().trim()) {
                $(this).addClass('error');
                isValid = false;
            } else {
                $(this).removeClass('error');
            }
        });
        
        // التحقق من صحة البريد الإلكتروني
        $(this).find('input[type="email"]').each(function() {
            var email = $(this).val();
            if (email && !isValidEmail(email)) {
                $(this).addClass('error');
                isValid = false;
                alert('<?php echo esc_js( __( 'يرجى إدخال بريد إلكتروني صحيح', 'invitation-manager-pro' ) ); ?>');
            } else {
                $(this).removeClass('error');
            }
        });
        
        if (!isValid) {
            alert('<?php echo esc_js( __( 'يرجى تصحيح الأخطاء في النموذج', 'invitation-manager-pro' ) ); ?>');
            return false;
        }
        
        // تحديث زر الإرسال
        var $submitButton = $('#impro-save-settings-button');
        var originalText = $submitButton.val();
        $submitButton.prop('disabled', true).val('<?php echo esc_js( __( 'جاري الحفظ...', 'invitation-manager-pro' ) ); ?>');
    });
    
    // إزالة خطأ عند الكتابة في الحقول
    $('.impro-settings-form input, .impro-settings-form textarea, .impro-settings-form select').on('input change', function() {
        $(this).removeClass('error');
    });
    
    // التحقق من صحة البريد الإلكتروني
    function isValidEmail(email) {
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    // إغلاق الإشعارات تلقائياً
    setTimeout(function() {
        $('.notice.is-dismissible').fadeOut(500, function() {
            $(this).remove();
        });
    }, 5000);
});
</script>
<?php
/**
 * Event form page.
 *
 * @package Invitation_Manager_Pro
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

$is_edit = ( isset( $action ) && $action === 'edit' && isset( $event ) );
$page_title = $is_edit ? __( 'تعديل المناسبة', 'invitation-manager-pro' ) : __( 'إضافة مناسبة جديدة', 'invitation-manager-pro' );
$submit_button_text = $is_edit ? __( 'تحديث المناسبة', 'invitation-manager-pro' ) : __( 'إنشاء المناسبة', 'invitation-manager-pro' );
$form_action = $is_edit ? 'update_event' : 'create_event';

$event_id = $is_edit ? $event->id : 0;
$event_name = $is_edit ? esc_attr( $event->name ) : '';
$event_date = $is_edit ? esc_attr( $event->event_date ) : '';
$event_time = $is_edit ? esc_attr( $event->event_time ) : '';
$event_venue = $is_edit ? esc_attr( $event->venue ) : '';
$event_address = $is_edit ? esc_textarea( $event->address ) : '';
$event_description = $is_edit ? esc_textarea( $event->description ) : '';
$invitation_image_url = $is_edit ? esc_url( $event->invitation_image_url ) : '';
$invitation_text = $is_edit ? esc_textarea( $event->invitation_text ) : '';
$location_details = $is_edit ? esc_textarea( $event->location_details ) : '';
$contact_info = $is_edit ? esc_attr( $event->contact_info ) : '';

?>

<div class="wrap">
    <h1><?php echo esc_html( $page_title ); ?></h1>

    <?php if ( isset( $_GET['error'] ) ) : ?>
        <div class="notice notice-error is-dismissible">
            <p>
                <?php
                switch ( $_GET['error'] ) {
                    case 'create_failed':
                        _e( 'فشل في إنشاء المناسبة. يرجى التحقق من البيانات والمحاولة مرة أخرى.', 'invitation-manager-pro' );
                        break;
                    case 'update_failed':
                        _e( 'فشل في تحديث المناسبة. يرجى التحقق من البيانات والمحاولة مرة أخرى.', 'invitation-manager-pro' );
                        break;
                    case 'validation_failed':
                        _e( 'فشل التحقق من صحة البيانات. يرجى التأكد من إدخال جميع الحقول المطلوبة بشكل صحيح.', 'invitation-manager-pro' );
                        break;
                }
                ?>
            </p>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
        <input type="hidden" name="action" value="impro_admin_action">
        <input type="hidden" name="impro_action" value="<?php echo esc_attr( $form_action ); ?>">
        <?php wp_nonce_field( 'impro_admin_action', '_wpnonce' ); ?>
        
        <?php if ( $is_edit ) : ?>
            <input type="hidden" name="event_id" value="<?php echo esc_attr( $event_id ); ?>">
        <?php endif; ?>

        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row"><label for="event_name"><?php _e( 'اسم المناسبة', 'invitation-manager-pro' ); ?></label></th>
                    <td><input type="text" name="event_name" id="event_name" value="<?php echo $event_name; ?>" class="regular-text" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="event_date"><?php _e( 'تاريخ المناسبة', 'invitation-manager-pro' ); ?></label></th>
                    <td><input type="date" name="event_date" id="event_date" value="<?php echo $event_date; ?>" class="regular-text" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="event_time"><?php _e( 'وقت المناسبة', 'invitation-manager-pro' ); ?></label></th>
                    <td><input type="time" name="event_time" id="event_time" value="<?php echo $event_time; ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="event_venue"><?php _e( 'مكان المناسبة', 'invitation-manager-pro' ); ?></label></th>
                    <td><input type="text" name="event_venue" id="event_venue" value="<?php echo $event_venue; ?>" class="regular-text" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="event_address"><?php _e( 'عنوان المناسبة', 'invitation-manager-pro' ); ?></label></th>
                    <td><textarea name="event_address" id="event_address" rows="5" class="large-text"><?php echo $event_address; ?></textarea></td>
                </tr>
                <tr>
                    <th scope="row"><label for="event_description"><?php _e( 'وصف المناسبة', 'invitation-manager-pro' ); ?></label></th>
                    <td><textarea name="event_description" id="event_description" rows="5" class="large-text"><?php echo $event_description; ?></textarea></td>
                </tr>
                <tr>
                    <th scope="row"><label for="invitation_image_url"><?php _e( 'رابط صورة الدعوة', 'invitation-manager-pro' ); ?></label></th>
                    <td>
                        <input type="text" name="invitation_image_url" id="invitation_image_url" value="<?php echo $invitation_image_url; ?>" class="regular-text">
                        <button type="button" class="button impro-upload-button"><?php _e( 'رفع صورة', 'invitation-manager-pro' ); ?></button>
                        <p class="description"><?php _e( 'رابط الصورة التي ستظهر في الدعوة.', 'invitation-manager-pro' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="invitation_text"><?php _e( 'نص الدعوة', 'invitation-manager-pro' ); ?></label></th>
                    <td><textarea name="invitation_text" id="invitation_text" rows="10" class="large-text"><?php echo $invitation_text; ?></textarea></td>
                </tr>
                <tr>
                    <th scope="row"><label for="location_details"><?php _e( 'تفاصيل الموقع (خرائط، إحداثيات)', 'invitation-manager-pro' ); ?></label></th>
                    <td><textarea name="location_details" id="location_details" rows="5" class="large-text"><?php echo $location_details; ?></textarea></td>
                </tr>
                <tr>
                    <th scope="row"><label for="contact_info"><?php _e( 'معلومات الاتصال', 'invitation-manager-pro' ); ?></label></th>
                    <td><input type="text" name="contact_info" id="contact_info" value="<?php echo $contact_info; ?>" class="regular-text"></td>
                </tr>
            </tbody>
        </table>

        <?php submit_button( $submit_button_text ); ?>
    </form>
</div>




<?php
/**
 * Invitations list page.
 *
 * @package Invitation_Manager_Pro
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// تهيئة المديرين بأمان
$invitation_manager = null;
$event_manager = null;
$guest_manager = null;

try {
    // التحقق من وجود الكلاسات قبل تهيئتها
    if (class_exists('IMPRO_Invitation_Manager')) {
        $invitation_manager = new IMPRO_Invitation_Manager();
    }
    
    if (class_exists('IMPRO_Event_Manager')) {
        $event_manager = new IMPRO_Event_Manager();
    }
    
    if (class_exists('IMPRO_Guest_Manager')) {
        $guest_manager = new IMPRO_Guest_Manager();
    }
} catch (Exception $e) {
    error_log('Error initializing managers: ' . $e->getMessage());
}

// الحصول على البيانات بأمان
$current_event_id = isset( $_GET['event_id'] ) ? intval( $_GET['event_id'] ) : 0;
$events = $event_manager ? ( method_exists( $event_manager, 'get_events' ) ? $event_manager->get_events() : array() ) : array();

// تهيئة المتغيرات الأساسية بأمان
$current_event = null;
$invitations = array();

if ( $current_event_id && $event_manager && method_exists( $event_manager, 'get_event' ) ) {
    $current_event = $event_manager->get_event( $current_event_id );
    if ( $current_event && $invitation_manager && method_exists( $invitation_manager, 'get_event_invitations' ) ) {
        $invitations = $invitation_manager->get_event_invitations( $current_event_id ) ?: array();
    } else {
        $invitations = array();
    }
} else {
    $current_event = null;
    if ( $invitation_manager && method_exists( $invitation_manager, 'get_all_invitations' ) ) {
        $invitations = $invitation_manager->get_all_invitations() ?: array();
    } else {
        $invitations = array();
    }
}

// تهيئة إحصائيات الدعوات بأمان
$total_invitations = is_array( $invitations ) ? count( $invitations ) : 0;
$sent_invitations = 0;
$viewed_invitations = 0;
$pending_invitations = 0;
$expired_invitations = 0;

if ( is_array( $invitations ) ) {
    foreach ( $invitations as $invitation ) {
        if ( isset( $invitation->status ) ) {
            switch ( $invitation->status ) {
                case 'sent':
                    $sent_invitations++;
                    break;
                case 'viewed':
                    $viewed_invitations++;
                    break;
                case 'pending':
                    $pending_invitations++;
                    break;
                case 'expired':
                    $expired_invitations++;
                    break;
            }
        }
    }
}

// ترجمة الفئات
$category_labels = array(
    'family' => __( 'عائلة', 'invitation-manager-pro' ),
    'friends' => __( 'أصدقاء', 'invitation-manager-pro' ),
    'colleagues' => __( 'زملاء', 'invitation-manager-pro' ),
    'vip' => __( 'شخصيات مهمة', 'invitation-manager-pro' ),
    'other' => __( 'أخرى', 'invitation-manager-pro' )
);

?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e( 'إدارة الدعوات', 'invitation-manager-pro' ); ?></h1>
    <hr class="wp-header-end">

    <?php 
    // عرض رسائل النجاح
    if ( isset( $_GET['message'] ) ) : 
        $success_messages = array(
            'sent' => __( 'تم إرسال الدعوة بنجاح.', 'invitation-manager-pro' ),
            'bulk_sent' => sprintf( __( 'تم إرسال %d دعوة بنجاح.', 'invitation-manager-pro' ), isset( $_GET['count'] ) ? intval( $_GET['count'] ) : 0 ),
            'reset' => __( 'تم إعادة تعيين الدعوة بنجاح.', 'invitation-manager-pro' ),
            'generated' => __( 'تم إنشاء الدعوات بنجاح.', 'invitation-manager-pro' ),
            'created' => __( 'تم إنشاء الدعوة بنجاح.', 'invitation-manager-pro' ),
            'updated' => __( 'تم تحديث الدعوة بنجاح.', 'invitation-manager-pro' ),
            'deleted' => __( 'تم حذف الدعوة بنجاح.', 'invitation-manager-pro' )
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
            'send_failed' => __( 'فشل في إرسال الدعوة.', 'invitation-manager-pro' ),
            'bulk_send_failed' => __( 'فشل في إرسال الدعوات.', 'invitation-manager-pro' ),
            'reset_failed' => __( 'فشل في إعادة تعيين الدعوة.', 'invitation-manager-pro' ),
            'generate_failed' => __( 'فشل في إنشاء الدعوات.', 'invitation-manager-pro' ),
            'create_failed' => __( 'فشل في إنشاء الدعوة.', 'invitation-manager-pro' ),
            'update_failed' => __( 'فشل في تحديث الدعوة.', 'invitation-manager-pro' ),
            'delete_failed' => __( 'فشل في حذف الدعوة.', 'invitation-manager-pro' ),
            'permission_denied' => __( 'ليس لديك الصلاحية للقيام بهذا الإجراء.', 'invitation-manager-pro' )
        );
        
        $error_key = sanitize_text_field( $_GET['error'] );
        $error_text = isset( $error_messages[ $error_key ] ) ? $error_messages[ $error_key ] : __( 'حدث خطأ غير متوقع.', 'invitation-manager-pro' );
    ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html( $error_text ); ?></p>
        </div>
    <?php endif; ?>

    <!-- لوحة الإحصائيات -->
    <div class="impro-stats-dashboard">
        <div class="impro-stats-grid">
            <div class="impro-stat-card">
                <div class="stat-icon">
                    <span class="dashicons dashicons-tickets-alt"></span>
                </div>
                <div class="stat-content">
                    <h3><?php echo esc_html( number_format( $total_invitations ) ); ?></h3>
                    <p><?php _e( 'إجمالي الدعوات', 'invitation-manager-pro' ); ?></p>
                </div>
            </div>
            <div class="impro-stat-card">
                <div class="stat-icon sent">
                    <span class="dashicons dashicons-paper-plane"></span>
                </div>
                <div class="stat-content">
                    <h3><?php echo esc_html( number_format( $sent_invitations ) ); ?></h3>
                    <p><?php _e( 'مرسلة', 'invitation-manager-pro' ); ?></p>
                </div>
            </div>
            <div class="impro-stat-card">
                <div class="stat-icon viewed">
                    <span class="dashicons dashicons-visibility"></span>
                </div>
                <div class="stat-content">
                    <h3><?php echo esc_html( number_format( $viewed_invitations ) ); ?></h3>
                    <p><?php _e( 'مشاهدات', 'invitation-manager-pro' ); ?></p>
                </div>
            </div>
            <div class="impro-stat-card">
                <div class="stat-icon pending">
                    <span class="dashicons dashicons-clock"></span>
                </div>
                <div class="stat-content">
                    <h3><?php echo esc_html( number_format( $pending_invitations ) ); ?></h3>
                    <p><?php _e( 'معلقة', 'invitation-manager-pro' ); ?></p>
                </div>
            </div>
            <div class="impro-stat-card">
                <div class="stat-icon expired">
                    <span class="dashicons dashicons-dismiss"></span>
                </div>
                <div class="stat-content">
                    <h3><?php echo esc_html( number_format( $expired_invitations ) ); ?></h3>
                    <p><?php _e( 'منتهية', 'invitation-manager-pro' ); ?></p>
                </div>
            </div>
            <div class="impro-stat-card">
                <div class="stat-icon success-rate">
                    <span class="dashicons dashicons-chart-line"></span>
                </div>
                <div class="stat-content">
                    <h3>
                        <?php 
                        $success_rate = $total_invitations > 0 ? round( ( ( $sent_invitations + $viewed_invitations ) / $total_invitations ) * 100, 1 ) : 0;
                        echo esc_html( $success_rate . '%' );
                        ?>
                    </h3>
                    <p><?php _e( 'معدل النجاح', 'invitation-manager-pro' ); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- أدوات التصفية -->
    <div class="impro-filter-section">
        <form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="impro-filter-form">
            <input type="hidden" name="page" value="impro-invitations">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="event_filter"><?php _e( 'تصفية حسب المناسبة:', 'invitation-manager-pro' ); ?></label>
                    <select name="event_id" id="event_filter" onchange="this.form.submit()">
                        <option value="0"><?php _e( 'جميع المناسبات', 'invitation-manager-pro' ); ?></option>
                        <?php if ( is_array( $events ) && ! empty( $events ) ) : ?>
                            <?php foreach ( $events as $event ) : ?>
                                <?php
                                $event_id = isset( $event->id ) ? intval( $event->id ) : 0;
                                $event_name = isset( $event->name ) ? esc_html( $event->name ) : __( 'مناسبة غير محددة', 'invitation-manager-pro' );
                                $event_date = isset( $event->event_date ) ? esc_html( date_i18n( get_option( 'date_format' ), strtotime( $event->event_date ) ) ) : '';
                                ?>
                                <option value="<?php echo esc_attr( $event_id ); ?>" <?php selected( $current_event_id, $event_id ); ?>>
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
                </div>
                
                <div class="filter-group">
                    <label for="status_filter"><?php _e( 'تصفية حسب الحالة:', 'invitation-manager-pro' ); ?></label>
                    <select name="status" id="status_filter" onchange="this.form.submit()">
                        <option value=""><?php _e( 'جميع الحالات', 'invitation-manager-pro' ); ?></option>
                        <option value="pending" <?php echo isset( $_GET['status'] ) && $_GET['status'] === 'pending' ? 'selected' : ''; ?>>
                            <?php _e( 'معلقة', 'invitation-manager-pro' ); ?>
                        </option>
                        <option value="sent" <?php echo isset( $_GET['status'] ) && $_GET['status'] === 'sent' ? 'selected' : ''; ?>>
                            <?php _e( 'مرسلة', 'invitation-manager-pro' ); ?>
                        </option>
                        <option value="viewed" <?php echo isset( $_GET['status'] ) && $_GET['status'] === 'viewed' ? 'selected' : ''; ?>>
                            <?php _e( 'مشاهد', 'invitation-manager-pro' ); ?>
                        </option>
                        <option value="expired" <?php echo isset( $_GET['status'] ) && $_GET['status'] === 'expired' ? 'selected' : ''; ?>>
                            <?php _e( 'منتهية', 'invitation-manager-pro' ); ?>
                        </option>
                    </select>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="button button-primary">
                        <?php _e( 'تطبيق', 'invitation-manager-pro' ); ?>
                    </button>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-invitations' ) ); ?>" class="button button-secondary">
                        <?php _e( 'إعادة تعيين', 'invitation-manager-pro' ); ?>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- إجراءات المناسبة -->
    <?php if ( $current_event ) : ?>
        <div class="impro-event-header">
            <div class="event-info">
                <h2>
                    <?php printf( __( 'دعوات المناسبة: %s', 'invitation-manager-pro' ), esc_html( $current_event->name ) ); ?>
                </h2>
                <p class="event-date">
                    <?php 
                    if ( isset( $current_event->event_date ) ) {
                        echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $current_event->event_date ) ) );
                    }
                    ?>
                </p>
            </div>
            <div class="impro-event-actions">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-invitations&action=generate_all&event_id=' . $current_event->id ) ); ?>" class="button button-secondary impro-generate-all-invitations">
                    <?php _e( 'إنشاء دعوات لجميع المدعوين', 'invitation-manager-pro' ); ?>
                </a>
                <button class="button button-primary impro-bulk-send-invitations" data-event-id="<?php echo esc_attr( $current_event->id ); ?>">
                    <?php _e( 'إرسال الدعوات المحددة', 'invitation-manager-pro' ); ?>
                </button>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-invitations&action=export&event_id=' . $current_event->id ) ); ?>" class="button">
                    <?php _e( 'تصدير الدعوات', 'invitation-manager-pro' ); ?>
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- أزرار الإجراءات الجماعية -->
    <?php if ( ! empty( $invitations ) ) : ?>
        <div class="impro-bulk-actions">
            <form method="post" id="bulk-action-form">
                <input type="hidden" name="impro_action" value="bulk_invitation_action">
                <?php wp_nonce_field( 'impro_admin_action', '_wpnonce' ); ?>
                
                <select name="bulk_action" id="bulk-action-selector">
                    <option value=""><?php _e( 'الإجراءات الجماعية', 'invitation-manager-pro' ); ?></option>
                    <option value="send"><?php _e( 'إرسال المحدد', 'invitation-manager-pro' ); ?></option>
                    <option value="reset"><?php _e( 'إعادة تعيين المحدد', 'invitation-manager-pro' ); ?></option>
                    <option value="delete"><?php _e( 'حذف المحدد', 'invitation-manager-pro' ); ?></option>
                    <option value="export"><?php _e( 'تصدير المحدد', 'invitation-manager-pro' ); ?></option>
                </select>
                <button type="button" id="bulk-action-button" class="button" disabled>
                    <?php _e( 'تطبيق', 'invitation-manager-pro' ); ?>
                </button>
            </form>
        </div>
    <?php endif; ?>

    <!-- جدول الدعوات -->
    <div class="impro-table-container">
        <table class="wp-list-table widefat fixed striped impro-invitations-table">
            <thead>
                <tr>
                    <th class="manage-column column-cb check-column">
                        <input type="checkbox" id="impro-select-all" />
                    </th>
                    <th class="column-guest"><?php _e( 'المدعو', 'invitation-manager-pro' ); ?></th>
                    <th class="column-event"><?php _e( 'المناسبة', 'invitation-manager-pro' ); ?></th>
                    <th class="column-status"><?php _e( 'الحالة', 'invitation-manager-pro' ); ?></th>
                    <th class="column-sent-date"><?php _e( 'تاريخ الإرسال', 'invitation-manager-pro' ); ?></th>
                    <th class="column-viewed-date"><?php _e( 'تاريخ المشاهدة', 'invitation-manager-pro' ); ?></th>
                    <th class="column-link"><?php _e( 'رابط الدعوة', 'invitation-manager-pro' ); ?></th>
                    <th class="column-actions"><?php _e( 'الإجراءات', 'invitation-manager-pro' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty( $invitations ) && is_array( $invitations ) ) : ?>
                    <?php foreach ( $invitations as $invitation ) : ?>
                        <?php
                        // تهيئة البيانات بأمان
                        $invitation_id = isset( $invitation->id ) ? intval( $invitation->id ) : 0;
                        $guest_id = isset( $invitation->guest_id ) ? intval( $invitation->guest_id ) : 0;
                        $event_id = isset( $invitation->event_id ) ? intval( $invitation->event_id ) : 0;
                        
                        // الحصول على بيانات الضيف والمناسبة بأمان
                        $guest = null;
                        $event = null;
                        
                        if ( $guest_manager && method_exists( $guest_manager, 'get_guest' ) && $guest_id > 0 ) {
                            $guest = $guest_manager->get_guest( $guest_id );
                        }
                        
                        if ( $event_manager && method_exists( $event_manager, 'get_event' ) && $event_id > 0 ) {
                            $event = $event_manager->get_event( $event_id );
                        }
                        
                        // تهيئة رابط الدعوة بأمان
                        $invitation_url = '#';
                        $token = isset( $invitation->unique_token ) ? $invitation->unique_token : ( isset( $invitation->token ) ? $invitation->token : '' );
                        
                        if ( ! empty( $token ) ) {
                            if ( class_exists( 'IMPRO_Public' ) ) {
                                $public = new IMPRO_Public();
                                if ( method_exists( $public, 'get_invitation_url' ) ) {
                                    $invitation_url = $public->get_invitation_url( $token );
                                }
                            } else {
                                // طريقة بديلة للحصول على الرابط
                                $invitation_page_id = get_option( 'impro_invitation_page_id' );
                                if ( $invitation_page_id ) {
                                    $invitation_url = get_permalink( $invitation_page_id ) . '?token=' . urlencode( $token );
                                } else {
                                    $invitation_url = home_url( '/invitation/' . urlencode( $token ) );
                                }
                            }
                        }
                        
                        // تهيئة حالة الدعوة
                        $status = isset( $invitation->status ) ? $invitation->status : 'pending';
                        $status_class = 'status-' . $status;
                        $status_texts = array(
                            'pending' => __( 'معلقة', 'invitation-manager-pro' ),
                            'sent' => __( 'مرسلة', 'invitation-manager-pro' ),
                            'viewed' => __( 'مشاهد', 'invitation-manager-pro' ),
                            'expired' => __( 'منتهية', 'invitation-manager-pro' ),
                            'cancelled' => __( 'ملغاة', 'invitation-manager-pro' )
                        );
                        $status_text = isset( $status_texts[ $status ] ) ? $status_texts[ $status ] : $status_texts['pending'];
                        
                        // تهيئة التواريخ
                        $sent_date = isset( $invitation->sent_at ) && ! empty( $invitation->sent_at ) ? 
                                   date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $invitation->sent_at ) ) : 
                                   __( 'لم ترسل بعد', 'invitation-manager-pro' );
                                   
                        $viewed_date = isset( $invitation->opened_at ) && ! empty( $invitation->opened_at ) ? 
                                     date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $invitation->opened_at ) ) : 
                                     __( 'لم تشاهد بعد', 'invitation-manager-pro' );
                        ?>
                        <tr data-invitation-id="<?php echo esc_attr( $invitation_id ); ?>">
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="invitation_ids[]" value="<?php echo esc_attr( $invitation_id ); ?>" class="impro-invitation-checkbox" />
                            </th>
                            <td class="column-guest">
                                <?php if ( $guest ) : ?>
                                    <strong>
                                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-guests&action=view&guest_id=' . $guest_id ) ); ?>">
                                            <?php echo esc_html( $guest->name ); ?>
                                        </a>
                                    </strong>
                                    <?php if ( ! empty( $guest->email ) ) : ?>
                                        <br><small><a href="mailto:<?php echo esc_attr( $guest->email ); ?>"><?php echo esc_html( $guest->email ); ?></a></small>
                                    <?php endif; ?>
                                    <?php if ( ! empty( $guest->category ) && isset( $category_labels[ $guest->category ] ) ) : ?>
                                        <br><span class="impro-category-badge category-<?php echo esc_attr( $guest->category ); ?>">
                                            <?php echo esc_html( $category_labels[ $guest->category ] ); ?>
                                        </span>
                                    <?php endif; ?>
                                <?php else : ?>
                                    <strong><?php _e( 'مدعو غير معروف', 'invitation-manager-pro' ); ?></strong>
                                <?php endif; ?>
                            </td>
                            <td class="column-event">
                                <?php if ( $event ) : ?>
                                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-events&action=view&event_id=' . $event_id ) ); ?>">
                                        <?php echo esc_html( $event->name ); ?>
                                    </a>
                                    <?php if ( ! empty( $event->event_date ) ) : ?>
                                        <br><small><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $event->event_date ) ) ); ?></small>
                                    <?php endif; ?>
                                <?php else : ?>
                                    <?php _e( 'مناسبة غير معروفة', 'invitation-manager-pro' ); ?>
                                <?php endif; ?>
                            </td>
                            <td class="column-status">
                                <span class="impro-status-badge <?php echo esc_attr( $status_class ); ?>">
                                    <?php echo esc_html( $status_text ); ?>
                                </span>
                            </td>
                            <td class="column-sent-date">
                                <?php echo esc_html( $sent_date ); ?>
                            </td>
                            <td class="column-viewed-date">
                                <?php echo esc_html( $viewed_date ); ?>
                            </td>
                            <td class="column-link">
                                <?php if ( $invitation_url !== '#' ) : ?>
                                    <a href="<?php echo esc_url( $invitation_url ); ?>" target="_blank" class="button button-small">
                                        <?php _e( 'عرض الرابط', 'invitation-manager-pro' ); ?>
                                    </a>
                                    <button type="button" class="button button-small button-secondary copy-link" data-link="<?php echo esc_url( $invitation_url ); ?>">
                                        <?php _e( 'نسخ', 'invitation-manager-pro' ); ?>
                                    </button>
                                <?php else : ?>
                                    <span class="impro-no-link"><?php _e( 'غير متوفر', 'invitation-manager-pro' ); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="column-actions">
                                <div class="impro-action-buttons">
                                    <button class="button button-small impro-send-invitation" 
                                            data-invitation-id="<?php echo esc_attr( $invitation_id ); ?>" 
                                            <?php echo ( $status === 'sent' || $status === 'viewed' ) ? 'disabled' : ''; ?>>
                                        <?php _e( 'إرسال', 'invitation-manager-pro' ); ?>
                                    </button>
                                    
                                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-invitations&action=reset&invitation_id=' . $invitation_id ) ); ?>" 
                                       class="button button-small button-secondary"
                                       onclick="return confirm('<?php echo esc_js( __( 'هل أنت متأكد من إعادة تعيين هذه الدعوة؟ سيتم إنشاء رمز جديد.', 'invitation-manager-pro' ) ); ?>');">
                                        <?php _e( 'إعادة تعيين', 'invitation-manager-pro' ); ?>
                                    </a>
                                    
                                    <div class="impro-dropdown">
                                        <button type="button" class="button button-small dropdown-toggle">
                                            <?php _e( 'المزيد', 'invitation-manager-pro' ); ?>
                                        </button>
                                        <div class="dropdown-menu">
                                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-invitations&action=edit&invitation_id=' . $invitation_id ) ); ?>">
                                                <?php _e( 'تعديل الدعوة', 'invitation-manager-pro' ); ?>
                                            </a>
                                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-invitations&action=view&invitation_id=' . $invitation_id ) ); ?>">
                                                <?php _e( 'عرض التفاصيل', 'invitation-manager-pro' ); ?>
                                            </a>
                                            <div class="dropdown-divider"></div>
                                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-rsvps&action=add&invitation_id=' . $invitation_id ) ); ?>">
                                                <?php _e( 'إضافة رد حضور', 'invitation-manager-pro' ); ?>
                                            </a>
                                            <div class="dropdown-divider"></div>
                                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-invitations&action=delete&invitation_id=' . $invitation_id ) ); ?>" 
                                               class="dropdown-item text-danger"
                                               onclick="return confirm('<?php echo esc_js( __( 'هل أنت متأكد من حذف هذه الدعوة؟', 'invitation-manager-pro' ) ); ?>');">
                                                <?php _e( 'حذف الدعوة', 'invitation-manager-pro' ); ?>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="8" class="no-invitations">
                            <div class="impro-empty-state">
                                <span class="dashicons dashicons-tickets-alt"></span>
                                <h3><?php _e( 'لا توجد دعوات', 'invitation-manager-pro' ); ?></h3>
                                <p><?php _e( 'لم يتم إنشاء أي دعوات حتى الآن.', 'invitation-manager-pro' ); ?></p>
                                <?php if ( $current_event ) : ?>
                                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-invitations&action=generate_all&event_id=' . $current_event->id ) ); ?>" class="button button-primary">
                                        <?php _e( 'إنشاء دعوات الآن', 'invitation-manager-pro' ); ?>
                                    </a>
                                <?php endif; ?>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-invitations&action=add' ) ); ?>" class="button">
                                    <?php _e( 'إنشاء دعوة يدوياً', 'invitation-manager-pro' ); ?>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- ترقيم الصفحات والمعلومات -->
    <?php if ( ! empty( $invitations ) && is_array( $invitations ) ) : ?>
        <div class="impro-table-footer">
            <div class="impro-table-info">
                <?php printf( __( 'عرض %d من أصل %d دعوة', 'invitation-manager-pro' ), count( $invitations ), $total_invitations ); ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.wrap {
    margin: 20px 20px 0 0;
}

/* لوحة الإحصائيات */
.impro-stats-dashboard {
    margin-bottom: 25px;
}

.impro-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.impro-stat-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.impro-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-left: 15px;
    font-size: 20px;
    background: #f0f0f1;
    color: #50575e;
}

.stat-icon.sent {
    background: #e1f0fa;
    color: #0073aa;
}

.stat-icon.viewed {
    background: #e6f4ea;
    color: #008a20;
}

.stat-icon.pending {
    background: #fef8ee;
    color: #d63638;
}

.stat-icon.expired {
    background: #f8e2df;
    color: #d63638;
}

.stat-icon.success-rate {
    background: #e6f4ea;
    color: #008a20;
}

.stat-content h3 {
    margin: 0 0 5px 0;
    font-size: 24px;
    font-weight: 600;
    color: #1d2327;
}

.stat-content p {
    margin: 0;
    color: #646970;
    font-size: 14px;
}

/* أدوات التصفية */
.impro-filter-section {
    margin-bottom: 25px;
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.impro-filter-form .filter-row {
    display: flex;
    align-items: end;
    gap: 15px;
    flex-wrap: wrap;
}

.impro-filter-form .filter-group {
    flex: 1;
    min-width: 200px;
}

.impro-filter-form label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
    color: #1d2327;
}

.impro-filter-form select {
    width: 100%;
    padding: 10px;
    border-radius: 4px;
    border: 1px solid #8c8f94;
    background: #fff;
    font-size: 14px;
}

.impro-filter-form .filter-actions {
    display: flex;
    gap: 10px;
}

/* رأس المناسبة */
.impro-event-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 15px;
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.impro-event-header h2 {
    margin: 0 0 5px 0;
    color: #1d2327;
}

.event-date {
    margin: 0;
    color: #646970;
    font-size: 14px;
}

.impro-event-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

/* أزرار الإجراءات الجماعية */
.impro-bulk-actions {
    margin-bottom: 20px;
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

#bulk-action-selector {
    margin-left: 10px;
}

#bulk-action-button {
    margin: 0;
}

/* جدول الدعوات */
.impro-table-container {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.impro-invitations-table {
    border: none;
    margin: 0;
}

.impro-invitations-table thead th {
    background: #f6f7f7;
    border-bottom: 2px solid #dcdcde;
    font-weight: 600;
    padding: 15px 10px;
}

.impro-invitations-table tbody td {
    padding: 12px 10px;
    vertical-align: middle;
}

.impro-invitations-table .column-guest {
    width: 20%;
}

.impro-invitations-table .column-event {
    width: 15%;
}

.impro-invitations-table .column-status {
    width: 12%;
}

.impro-invitations-table .column-sent-date,
.impro-invitations-table .column-viewed-date {
    width: 15%;
}

.impro-invitations-table .column-link {
    width: 12%;
}

.impro-invitations-table .column-actions {
    width: 16%;
}

/* شارات الفئات والحالة */
.impro-category-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-align: center;
    white-space: nowrap;
}

.category-family {
    background: #e6f4ea;
    color: #008a20;
    border: 1px solid #b3e0c4;
}

.category-friends {
    background: #e1f0fa;
    color: #0073aa;
    border: 1px solid #b3d9f0;
}

.category-colleagues {
    background: #fef8ee;
    color: #d63638;
    border: 1px solid #f8e2df;
}

.category-vip {
    background: #f8e2df;
    color: #d63638;
    border: 1px solid #f0b3b3;
}

.category-other {
    background: #f0f0f1;
    color: #50575e;
    border: 1px solid #dcdcde;
}

.impro-status-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-align: center;
    white-space: nowrap;
}

.status-pending {
    background: #fef8ee;
    color: #d63638;
    border: 1px solid #f8e2df;
}

.status-sent {
    background: #e1f0fa;
    color: #0073aa;
    border: 1px solid #b3d9f0;
}

.status-viewed {
    background: #e6f4ea;
    color: #008a20;
    border: 1px solid #b3e0c4;
}

.status-expired {
    background: #f8e2df;
    color: #d63638;
    border: 1px solid #f0b3b3;
}

.status-cancelled {
    background: #f0f0f1;
    color: #50575e;
    border: 1px solid #dcdcde;
}

/* أزرار الإجراءات */
.impro-action-buttons {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
    align-items: center;
}

.impro-action-buttons .button {
    margin: 0;
    font-size: 12px;
    padding: 4px 10px;
}

.copy-link {
    margin-top: 5px;
}

/* القائمة المنسدلة */
.impro-dropdown {
    position: relative;
    display: inline-block;
}

.dropdown-toggle {
    background: #f6f7f7;
    border: 1px solid #8c8f94;
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    left: 0;
    z-index: 1000;
    display: none;
    min-width: 160px;
    padding: 5px 0;
    margin: 2px 0 0;
    font-size: 14px;
    text-align: right;
    list-style: none;
    background-color: #fff;
    background-clip: padding-box;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    box-shadow: 0 6px 12px rgba(0,0,0,0.175);
}

.impro-dropdown:hover .dropdown-menu {
    display: block;
}

.dropdown-item {
    display: block;
    width: 100%;
    padding: 8px 12px;
    clear: both;
    font-weight: 400;
    color: #333;
    text-align: inherit;
    white-space: nowrap;
    background: none;
    border: none;
    cursor: pointer;
    text-decoration: none;
    font-size: 13px;
}

.dropdown-item:hover {
    color: #262626;
    text-decoration: none;
    background-color: #f5f5f5;
}

.dropdown-divider {
    height: 1px;
    margin: 9px 0;
    overflow: hidden;
    background-color: #e5e5e5;
}

.text-danger {
    color: #d63638;
}

/* حالة فارغة */
.impro-empty-state {
    text-align: center;
    padding: 60px 20px;
}

.impro-empty-state .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: #dcdcde;
    margin-bottom: 15px;
}

.impro-empty-state h3 {
    margin: 0 0 10px 0;
    color: #1d2327;
    font-size: 20px;
}

.impro-empty-state p {
    margin: 0 0 20px 0;
    color: #646970;
    font-size: 14px;
}

/* تذييل الجدول */
.impro-table-footer {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-top: none;
    padding: 15px 20px;
    border-radius: 0 0 8px 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.impro-table-info {
    color: #646970;
    font-size: 13px;
}

/* تصميم متجاوب */
@media (max-width: 768px) {
    .impro-stats-grid {
        grid-template-columns: 1fr 1fr;
    }
    
    .impro-filter-form .filter-row {
        flex-direction: column;
        align-items: stretch;
    }
    
    .impro-filter-form .filter-group {
        min-width: auto;
    }
    
    .impro-event-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .impro-event-actions {
        justify-content: center;
    }
    
    .impro-invitations-table thead {
        display: none;
    }
    
    .impro-invitations-table tbody td {
        display: block;
        width: 100% !important;
        border-bottom: 1px solid #eee;
        padding: 10px;
    }
    
    .impro-invitations-table tbody tr td:last-child {
        border-bottom: none;
    }
    
    .impro-invitations-table tbody tr {
        margin-bottom: 20px;
        border: 1px solid #ddd;
        border-radius: 8px;
        display: block;
    }
}

@media (max-width: 480px) {
    .impro-stats-grid {
        grid-template-columns: 1fr;
    }
    
    .impro-action-buttons {
        flex-direction: column;
    }
    
    .impro-action-buttons .button {
        width: 100%;
        margin-bottom: 5px;
    }
    
    .impro-dropdown {
        position: static;
    }
    
    .dropdown-menu {
        position: static;
        display: block;
        box-shadow: none;
        border: none;
        background: transparent;
        margin-top: 10px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // تحديد الكل / إلغاء تحديد الكل
    $('#impro-select-all').change(function() {
        $('.impro-invitation-checkbox').prop('checked', $(this).prop('checked'));
        updateBulkActionButton();
    });
    
    // تحديث حالة زر الإجراءات الجماعية
    $('.impro-invitation-checkbox').change(function() {
        var totalCheckboxes = $('.impro-invitation-checkbox').length;
        var checkedCheckboxes = $('.impro-invitation-checkbox:checked').length;
        $('#impro-select-all').prop('checked', totalCheckboxes === checkedCheckboxes);
        updateBulkActionButton();
    });
    
    // تحديث زر الإجراءات الجماعية
    function updateBulkActionButton() {
        var checkedCount = $('.impro-invitation-checkbox:checked').length;
        $('#bulk-action-button').prop('disabled', checkedCount === 0);
    }
    
    // تنفيذ الإجراءات الجماعية
    $('#bulk-action-button').click(function() {
        var action = $('#bulk-action-selector').val();
        var selectedInvitations = $('.impro-invitation-checkbox:checked').map(function() {
            return $(this).val();
        }).get();
        
        if (selectedInvitations.length === 0) {
            alert('<?php esc_js_e( 'الرجاء تحديد دعوات للتنفيذ', 'invitation-manager-pro' ); ?>');
            return;
        }
        
        if (!action) {
            alert('<?php esc_js_e( 'الرجاء اختيار إجراء جماعي', 'invitation-manager-pro' ); ?>');
            return;
        }
        
        // تأكيد الإجراءات الخطيرة
        if (action === 'delete') {
            if (!confirm('<?php echo esc_js( __( 'هل أنت متأكد من حذف الدعوات المحددة؟', 'invitation-manager-pro' ) ); ?>')) {
                return;
            }
        }
        
        // تنفيذ الإجراء
        $('#bulk-action-form').append('<input type="hidden" name="invitation_ids" value="' + selectedInvitations.join(',') + '">');
        $('#bulk-action-form').submit();
    });
    
    // إرسال دعوة فردية
    $('.impro-send-invitation').click(function() {
        var invitationId = $(this).data('invitation-id');
        if (confirm('<?php echo esc_js( __( 'هل أنت متأكد من إرسال هذه الدعوة؟', 'invitation-manager-pro' ) ); ?>')) {
            // هنا يمكنك إضافة كود إرسال الدعوة
            $(this).prop('disabled', true).text('<?php esc_js_e( 'تم الإرسال', 'invitation-manager-pro' ); ?>');
            
            // إرسال عبر AJAX (مثال)
            /*
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'impro_send_invitation',
                    invitation_id: invitationId,
                    nonce: '<?php echo esc_js( wp_create_nonce( 'impro_admin_action' ) ); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        alert('<?php esc_js_e( 'تم إرسال الدعوة بنجاح', 'invitation-manager-pro' ); ?>');
                        location.reload();
                    } else {
                        alert('<?php esc_js_e( 'فشل في إرسال الدعوة', 'invitation-manager-pro' ); ?>');
                        $(this).prop('disabled', false).text('<?php esc_js_e( 'إرسال', 'invitation-manager-pro' ); ?>');
                    }
                }
            });
            */
        }
    });
    
    // نسخ رابط الدعوة
    $('.copy-link').click(function() {
        var link = $(this).data('link');
        if (link) {
            navigator.clipboard.writeText(link).then(function() {
                var originalText = $(this).text();
                $(this).text('<?php esc_js_e( 'تم النسخ', 'invitation-manager-pro' ); ?>');
                setTimeout(function() {
                    $(this).text(originalText);
                }.bind(this), 2000);
            }.bind(this)).catch(function() {
                alert('<?php esc_js_e( 'فشل في نسخ الرابط', 'invitation-manager-pro' ); ?>');
            }.bind(this));
        }
    });
    
    // إغلاق الإشعارات تلقائياً
    setTimeout(function() {
        $('.notice.is-dismissible').fadeOut(500, function() {
            $(this).remove();
        });
    }, 5000);
    
    // تحسين تجربة القائمة المنسدلة
    $('.impro-dropdown').hover(
        function() {
            $(this).find('.dropdown-menu').stop(true, true).fadeIn(200);
        },
        function() {
            $(this).find('.dropdown-menu').stop(true, true).fadeOut(200);
        }
    );
});
</script>
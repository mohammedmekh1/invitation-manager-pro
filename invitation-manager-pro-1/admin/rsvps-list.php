
<?php
/**
 * RSVPs list page.
 *
 * @package Invitation_Manager_Pro
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// تهيئة المديرين بأمان
$rsvp_manager = null;
$event_manager = null;
$guest_manager = null;

try {
    if (class_exists('IMPRO_RSVP_Manager')) {
        $rsvp_manager = new IMPRO_RSVP_Manager();
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
$rsvps = array();
$stats = array();

if ( $current_event_id && $event_manager && method_exists( $event_manager, 'get_event' ) ) {
    $current_event = $event_manager->get_event( $current_event_id );
    if ( $rsvp_manager && method_exists( $rsvp_manager, 'get_event_rsvps' ) ) {
        $rsvps = $rsvp_manager->get_event_rsvps( $current_event_id ) ?: array();
    }
    if ( $rsvp_manager && method_exists( $rsvp_manager, 'get_event_rsvp_statistics' ) ) {
        $stats = $rsvp_manager->get_event_rsvp_statistics( $current_event_id ) ?: array();
    }
} else {
    $current_event = null;
    if ( $rsvp_manager && method_exists( $rsvp_manager, 'get_all_rsvps' ) ) {
        $rsvps = $rsvp_manager->get_all_rsvps() ?: array();
    }
    if ( $rsvp_manager && method_exists( $rsvp_manager, 'get_overall_rsvp_statistics' ) ) {
        $stats = $rsvp_manager->get_overall_rsvp_statistics() ?: array();
    }
}

// تهيئة الإحصائيات الافتراضية بأمان
$default_stats = array(
    'accepted' => 0,
    'declined' => 0,
    'pending' => 0,
    'total_attending' => 0,
    'total_expected' => 0
);

$stats = array_merge( $default_stats, is_array( $stats ) ? $stats : array() );

// ترجمة الحالات
$status_labels = array(
    'accepted' => __( 'موافق', 'invitation-manager-pro' ),
    'declined' => __( 'معتذر', 'invitation-manager-pro' ),
    'pending' => __( 'معلق', 'invitation-manager-pro' )
);

$category_labels = array(
    'family' => __( 'عائلة', 'invitation-manager-pro' ),
    'friends' => __( 'أصدقاء', 'invitation-manager-pro' ),
    'colleagues' => __( 'زملاء', 'invitation-manager-pro' ),
    'vip' => __( 'شخصيات مهمة', 'invitation-manager-pro' ),
    'other' => __( 'أخرى', 'invitation-manager-pro' )
);

?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e( 'ردود الحضور', 'invitation-manager-pro' ); ?></h1>
    <hr class="wp-header-end">

    <?php 
    // عرض رسائل النجاح
    if ( isset( $_GET['message'] ) ) : 
        $success_messages = array(
            'updated' => __( 'تم تحديث حالة رد الحضور بنجاح.', 'invitation-manager-pro' ),
            'created' => __( 'تم إنشاء رد الحضور بنجاح.', 'invitation-manager-pro' ),
            'deleted' => __( 'تم حذف رد الحضور بنجاح.', 'invitation-manager-pro' ),
            'bulk_updated' => __( 'تم تحديث ردود الحضور المحددة بنجاح.', 'invitation-manager-pro' ),
            'imported' => __( 'تم استيراد ردود الحضور بنجاح.', 'invitation-manager-pro' )
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
            'update_failed' => __( 'فشل في تحديث حالة رد الحضور.', 'invitation-manager-pro' ),
            'create_failed' => __( 'فشل في إنشاء رد الحضور.', 'invitation-manager-pro' ),
            'delete_failed' => __( 'فشل في حذف رد الحضور.', 'invitation-manager-pro' ),
            'bulk_update_failed' => __( 'فشل في تحديث ردود الحضور المحددة.', 'invitation-manager-pro' ),
            'import_failed' => __( 'فشل في استيراد ردود الحضور.', 'invitation-manager-pro' ),
            'permission_denied' => __( 'ليس لديك الصلاحية للقيام بهذا الإجراء.', 'invitation-manager-pro' )
        );
        
        $error_key = sanitize_text_field( $_GET['error'] );
        $error_text = isset( $error_messages[ $error_key ] ) ? $error_messages[ $error_key ] : __( 'حدث خطأ غير متوقع.', 'invitation-manager-pro' );
    ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html( $error_text ); ?></p>
        </div>
    <?php endif; ?>

    <!-- أدوات التصفية -->
    <div class="impro-filter-section">
        <form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="impro-filter-form">
            <input type="hidden" name="page" value="impro-rsvps">
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
                                <option value="<?php echo esc_attr( $event_id ); ?>" <?php selected( $current_event_id, $event_id, false ); ?>>
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
                        <option value="accepted" <?php echo isset( $_GET['status'] ) && $_GET['status'] === 'accepted' ? 'selected' : ''; ?>>
                            <?php _e( 'موافق', 'invitation-manager-pro' ); ?>
                        </option>
                        <option value="declined" <?php echo isset( $_GET['status'] ) && $_GET['status'] === 'declined' ? 'selected' : ''; ?>>
                            <?php _e( 'معتذر', 'invitation-manager-pro' ); ?>
                        </option>
                        <option value="pending" <?php echo isset( $_GET['status'] ) && $_GET['status'] === 'pending' ? 'selected' : ''; ?>>
                            <?php _e( 'معلق', 'invitation-manager-pro' ); ?>
                        </option>
                    </select>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="button button-primary">
                        <?php _e( 'تطبيق', 'invitation-manager-pro' ); ?>
                    </button>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-rsvps' ) ); ?>" class="button button-secondary">
                        <?php _e( 'إعادة تعيين', 'invitation-manager-pro' ); ?>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- لوحة الإحصائيات -->
    <div class="impro-rsvp-stats">
        <h2><?php _e( 'إحصائيات ردود الحضور', 'invitation-manager-pro' ); ?></h2>
        <div class="impro-stats-grid">
            <div class="impro-stat-card">
                <div class="impro-stat-icon accepted">
                    <span class="dashicons dashicons-yes"></span>
                </div>
                <div class="impro-stat-content">
                    <h3><?php echo esc_html( number_format( $stats['accepted'] ) ); ?></h3>
                    <p><?php _e( 'موافق', 'invitation-manager-pro' ); ?></p>
                </div>
            </div>
            <div class="impro-stat-card">
                <div class="impro-stat-icon declined">
                    <span class="dashicons dashicons-no"></span>
                </div>
                <div class="impro-stat-content">
                    <h3><?php echo esc_html( number_format( $stats['declined'] ) ); ?></h3>
                    <p><?php _e( 'معتذر', 'invitation-manager-pro' ); ?></p>
                </div>
            </div>
            <div class="impro-stat-card">
                <div class="impro-stat-icon pending">
                    <span class="dashicons dashicons-clock"></span>
                </div>
                <div class="impro-stat-content">
                    <h3><?php echo esc_html( number_format( $stats['pending'] ) ); ?></h3>
                    <p><?php _e( 'معلق', 'invitation-manager-pro' ); ?></p>
                </div>
            </div>
            <div class="impro-stat-card">
                <div class="impro-stat-icon total">
                    <span class="dashicons dashicons-groups"></span>
                </div>
                <div class="impro-stat-content">
                    <h3><?php echo esc_html( number_format( $stats['total_attending'] ) ); ?></h3>
                    <p><?php _e( 'إجمالي الحضور المتوقع', 'invitation-manager-pro' ); ?></p>
                </div>
            </div>
            <div class="impro-stat-card">
                <div class="impro-stat-icon percentage">
                    <span class="dashicons dashicons-chart-line"></span>
                </div>
                <div class="impro-stat-content">
                    <h3>
                        <?php 
                        $total_responses = $stats['accepted'] + $stats['declined'] + $stats['pending'];
                        $acceptance_rate = $total_responses > 0 ? round( ( $stats['accepted'] / $total_responses ) * 100, 1 ) : 0;
                        echo esc_html( $acceptance_rate . '%' );
                        ?>
                    </h3>
                    <p><?php _e( 'معدل الموافقة', 'invitation-manager-pro' ); ?></p>
                </div>
            </div>
            <div class="impro-stat-card">
                <div class="impro-stat-icon expected">
                    <span class="dashicons dashicons-calendar-alt"></span>
                </div>
                <div class="impro-stat-content">
                    <h3><?php echo esc_html( number_format( $stats['total_expected'] ) ); ?></h3>
                    <p><?php _e( 'الحضور المتوقع', 'invitation-manager-pro' ); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- أزرار الإجراءات السريعة -->
    <?php if ( ! empty( $rsvps ) ) : ?>
        <div class="impro-rsvp-actions">
            <div class="impro-action-buttons">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-rsvps&action=export' . ( $current_event_id ? '&event_id=' . $current_event_id : '' ) ) ); ?>" class="button">
                    <?php _e( 'تصدير ردود الحضور', 'invitation-manager-pro' ); ?>
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-rsvps&action=import' ) ); ?>" class="button">
                    <?php _e( 'استيراد ردود الحضور', 'invitation-manager-pro' ); ?>
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-rsvps&action=add' ) ); ?>" class="button button-primary">
                    <?php _e( 'إضافة رد حضور يدوي', 'invitation-manager-pro' ); ?>
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- أزرار الإجراءات الجماعية -->
    <?php if ( ! empty( $rsvps ) ) : ?>
        <div class="impro-bulk-actions">
            <form method="post" id="bulk-action-form">
                <input type="hidden" name="impro_action" value="bulk_rsvp_action">
                <?php wp_nonce_field( 'impro_admin_action', '_wpnonce' ); ?>
                
                <select name="bulk_action" id="bulk-action-selector">
                    <option value=""><?php _e( 'الإجراءات الجماعية', 'invitation-manager-pro' ); ?></option>
                    <option value="accept"><?php _e( 'تعيين كموافقة', 'invitation-manager-pro' ); ?></option>
                    <option value="decline"><?php _e( 'تعيين كاعتذار', 'invitation-manager-pro' ); ?></option>
                    <option value="delete"><?php _e( 'حذف المحدد', 'invitation-manager-pro' ); ?></option>
                    <option value="export"><?php _e( 'تصدير المحدد', 'invitation-manager-pro' ); ?></option>
                </select>
                <button type="button" id="bulk-action-button" class="button" disabled>
                    <?php _e( 'تطبيق', 'invitation-manager-pro' ); ?>
                </button>
            </form>
        </div>
    <?php endif; ?>

    <!-- جدول ردود الحضور -->
    <div class="impro-table-container">
        <table class="wp-list-table widefat fixed striped impro-rsvps-table">
            <thead>
                <tr>
                    <th class="manage-column column-cb check-column">
                        <input type="checkbox" id="impro-select-all" />
                    </th>
                    <th class="column-guest"><?php _e( 'المدعو', 'invitation-manager-pro' ); ?></th>
                    <th class="column-event"><?php _e( 'المناسبة', 'invitation-manager-pro' ); ?></th>
                    <th class="column-status"><?php _e( 'الحالة', 'invitation-manager-pro' ); ?></th>
                    <th class="column-plus-one"><?php _e( 'المرافقين', 'invitation-manager-pro' ); ?></th>
                    <th class="column-dietary"><?php _e( 'المتطلبات الغذائية', 'invitation-manager-pro' ); ?></th>
                    <th class="column-date"><?php _e( 'تاريخ الرد', 'invitation-manager-pro' ); ?></th>
                    <th class="column-actions"><?php _e( 'الإجراءات', 'invitation-manager-pro' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty( $rsvps ) && is_array( $rsvps ) ) : ?>
                    <?php foreach ( $rsvps as $rsvp ) : ?>
                        <?php
                        // تهيئة البيانات بأمان
                        $rsvp_id = isset( $rsvp->id ) ? intval( $rsvp->id ) : 0;
                        $guest_id = isset( $rsvp->guest_id ) ? intval( $rsvp->guest_id ) : 0;
                        $event_id = isset( $rsvp->event_id ) ? intval( $rsvp->event_id ) : 0;
                        
                        // الحصول على بيانات الضيف والمناسبة بأمان
                        $guest = null;
                        $event = null;
                        
                        if ( $guest_manager && method_exists( $guest_manager, 'get_guest' ) && $guest_id > 0 ) {
                            $guest = $guest_manager->get_guest( $guest_id );
                        }
                        
                        if ( $event_manager && method_exists( $event_manager, 'get_event' ) && $event_id > 0 ) {
                            $event = $event_manager->get_event( $event_id );
                        }
                        
                        // تهيئة حالة RSVP
                        $status = isset( $rsvp->status ) ? $rsvp->status : 'pending';
                        $status_class = 'status-' . $status;
                        $status_text = isset( $status_labels[ $status ] ) ? $status_labels[ $status ] : $status_labels['pending'];
                        
                        // تهيئة عدد المرافقين
                        $plus_one_count = isset( $rsvp->plus_one_attending ) ? intval( $rsvp->plus_one_attending ) : 0;
                        
                        // تهيئة المتطلبات الغذائية
                        $dietary_requirements = isset( $rsvp->dietary_requirements ) ? esc_html( $rsvp->dietary_requirements ) : __( 'لا توجد', 'invitation-manager-pro' );
                        
                        // تهيئة تاريخ الرد
                        $response_date = isset( $rsvp->response_date ) ? esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $rsvp->response_date ) ) ) : __( 'غير محدد', 'invitation-manager-pro' );
                        
                        // تهيئة اسم المرافق
                        $plus_one_name = isset( $rsvp->plus_one_name ) ? esc_html( $rsvp->plus_one_name ) : '';
                        ?>
                        <tr data-rsvp-id="<?php echo esc_attr( $rsvp_id ); ?>">
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="rsvp_ids[]" value="<?php echo esc_attr( $rsvp_id ); ?>" class="impro-rsvp-checkbox" />
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
                            <td class="column-plus-one">
                                <?php if ( $plus_one_count > 0 ) : ?>
                                    <span class="impro-stat-badge"><?php echo esc_html( $plus_one_count ); ?></span>
                                    <?php if ( ! empty( $plus_one_name ) ) : ?>
                                        <br><small><?php echo esc_html( $plus_one_name ); ?></small>
                                    <?php endif; ?>
                                <?php else : ?>
                                    <span class="impro-no-data"><?php _e( 'لا يوجد', 'invitation-manager-pro' ); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="column-dietary">
                                <?php if ( ! empty( $dietary_requirements ) && $dietary_requirements !== __( 'لا توجد', 'invitation-manager-pro' ) ) : ?>
                                    <span class="impro-dietary-requirements" title="<?php echo esc_attr( $dietary_requirements ); ?>">
                                        <?php echo esc_html( mb_substr( $dietary_requirements, 0, 30 ) . ( mb_strlen( $dietary_requirements ) > 30 ? '...' : '' ) ); ?>
                                    </span>
                                <?php else : ?>
                                    <span class="impro-no-data"><?php _e( 'لا توجد', 'invitation-manager-pro' ); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="column-date">
                                <?php echo esc_html( $response_date ); ?>
                            </td>
                            <td class="column-actions">
                                <div class="impro-action-buttons">
                                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-rsvps&action=edit&rsvp_id=' . $rsvp_id ) ); ?>" class="button button-small">
                                        <?php _e( 'تعديل', 'invitation-manager-pro' ); ?>
                                    </a>
                                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-rsvps&action=view&rsvp_id=' . $rsvp_id ) ); ?>" class="button button-small button-secondary">
                                        <?php _e( 'عرض', 'invitation-manager-pro' ); ?>
                                    </a>
                                    <div class="impro-dropdown">
                                        <button type="button" class="button button-small dropdown-toggle">
                                            <?php _e( 'المزيد', 'invitation-manager-pro' ); ?>
                                        </button>
                                        <div class="dropdown-menu">
                                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-guests&action=edit&guest_id=' . $guest_id ) ); ?>">
                                                <?php _e( 'تعديل المدعو', 'invitation-manager-pro' ); ?>
                                            </a>
                                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-invitations&guest_id=' . $guest_id ) ); ?>">
                                                <?php _e( 'عرض الدعوات', 'invitation-manager-pro' ); ?>
                                            </a>
                                            <div class="dropdown-divider"></div>
                                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-rsvps&action=delete&rsvp_id=' . $rsvp_id ) ); ?>" 
                                               class="dropdown-item text-danger"
                                               onclick="return confirm('<?php echo esc_js( __( 'هل أنت متأكد من حذف هذا الرد؟', 'invitation-manager-pro' ) ); ?>');">
                                                <?php _e( 'حذف الرد', 'invitation-manager-pro' ); ?>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="8" class="no-rsvps">
                            <div class="impro-empty-state">
                                <span class="dashicons dashicons-calendar-alt"></span>
                                <h3><?php _e( 'لا توجد ردود حضور', 'invitation-manager-pro' ); ?></h3>
                                <p><?php _e( 'لم يتم استلام أي ردود حضور حتى الآن.', 'invitation-manager-pro' ); ?></p>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-rsvps&action=add' ) ); ?>" class="button button-primary">
                                    <?php _e( 'إضافة رد حضور يدوي', 'invitation-manager-pro' ); ?>
                                </a>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-rsvps&action=import' ) ); ?>" class="button">
                                    <?php _e( 'استيراد ردود حضور', 'invitation-manager-pro' ); ?>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- ترقيم الصفحات والمعلومات -->
    <?php if ( ! empty( $rsvps ) && is_array( $rsvps ) ) : ?>
        <div class="impro-table-footer">
            <div class="impro-table-info">
                <?php printf( __( 'عرض %d من أصل %d رد حضور', 'invitation-manager-pro' ), count( $rsvps ), count( $rsvps ) ); ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.wrap {
    margin: 20px 20px 0 0;
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

/* لوحة الإحصائيات */
.impro-rsvp-stats {
    margin-bottom: 25px;
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.impro-rsvp-stats h2 {
    margin: 0 0 20px 0;
    color: #1d2327;
    font-size: 20px;
}

.impro-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 20px;
}

.impro-stat-card {
    background: #f9f9f9;
    border: 1px solid #e5e5e5;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.impro-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.impro-stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    background: #f0f0f1;
    color: #50575e;
}

.impro-stat-icon.accepted {
    background: #e6f4ea;
    color: #008a20;
}

.impro-stat-icon.declined {
    background: #f8e2df;
    color: #d63638;
}

.impro-stat-icon.pending {
    background: #fef8ee;
    color: #d63638;
}

.impro-stat-icon.total {
    background: #e1f0fa;
    color: #0073aa;
}

.impro-stat-icon.percentage {
    background: #e6f4ea;
    color: #008a20;
}

.impro-stat-icon.expected {
    background: #fef8ee;
    color: #d63638;
}

.impro-stat-content h3 {
    margin: 0 0 5px 0;
    font-size: 24px;
    font-weight: 600;
    color: #1d2327;
}

.impro-stat-content p {
    margin: 0;
    color: #646970;
    font-size: 14px;
}

/* أزرار الإجراءات السريعة */
.impro-rsvp-actions {
    margin-bottom: 20px;
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.impro-action-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.impro-action-buttons .button {
    margin: 0;
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

/* جدول ردود الحضور */
.impro-table-container {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.impro-rsvps-table {
    border: none;
    margin: 0;
}

.impro-rsvps-table thead th {
    background: #f6f7f7;
    border-bottom: 2px solid #dcdcde;
    font-weight: 600;
    padding: 15px 10px;
}

.impro-rsvps-table tbody td {
    padding: 12px 10px;
    vertical-align: middle;
}

.impro-rsvps-table .column-guest {
    width: 20%;
}

.impro-rsvps-table .column-event {
    width: 15%;
}

.impro-rsvps-table .column-status {
    width: 12%;
}

.impro-rsvps-table .column-plus-one {
    width: 10%;
}

.impro-rsvps-table .column-dietary {
    width: 15%;
}

.impro-rsvps-table .column-date {
    width: 15%;
}

.impro-rsvps-table .column-actions {
    width: 13%;
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

.status-accepted {
    background: #e6f4ea;
    color: #008a20;
    border: 1px solid #b3e0c4;
}

.status-declined {
    background: #f8e2df;
    color: #d63638;
    border: 1px solid #f0b3b3;
}

.status-pending {
    background: #fef8ee;
    color: #d63638;
    border: 1px solid #f8e2df;
}

.impro-stat-badge {
    display: inline-block;
    padding: 4px 10px;
    background: #f0f0f1;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    color: #1d2327;
}

.impro-no-data {
    color: #999;
    font-style: italic;
}

/* المتطلبات الغذائية */
.impro-dietary-requirements {
    cursor: help;
    text-decoration: underline;
    text-decoration-style: dotted;
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
@media (max-width: 782px) {
    .impro-filter-form .filter-row {
        flex-direction: column;
        align-items: stretch;
    }
    
    .impro-filter-form .filter-group {
        min-width: auto;
    }
    
    .impro-stats-grid {
        grid-template-columns: 1fr 1fr;
    }
    
    .impro-action-buttons {
        justify-content: center;
    }
    
    .impro-rsvps-table thead {
        display: none;
    }
    
    .impro-rsvps-table tbody td {
        display: block;
        width: 100% !important;
        border-bottom: 1px solid #eee;
        padding: 10px;
    }
    
    .impro-rsvps-table tbody tr td:last-child {
        border-bottom: none;
    }
    
    .impro-rsvps-table tbody tr {
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
        text-align: center;
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
        $('.impro-rsvp-checkbox').prop('checked', $(this).prop('checked'));
        updateBulkActionButton();
    });
    
    // تحديث حالة زر الإجراءات الجماعية
    $('.impro-rsvp-checkbox').change(function() {
        var totalCheckboxes = $('.impro-rsvp-checkbox').length;
        var checkedCheckboxes = $('.impro-rsvp-checkbox:checked').length;
        $('#impro-select-all').prop('checked', totalCheckboxes === checkedCheckboxes);
        updateBulkActionButton();
    });
    
    // تحديث زر الإجراءات الجماعية
    function updateBulkActionButton() {
        var checkedCount = $('.impro-rsvp-checkbox:checked').length;
        $('#bulk-action-button').prop('disabled', checkedCount === 0);
    }
    
    // تنفيذ الإجراءات الجماعية
    $('#bulk-action-button').click(function() {
        var action = $('#bulk-action-selector').val();
        var selectedRsvps = $('.impro-rsvp-checkbox:checked').map(function() {
            return $(this).val();
        }).get();
        
        if (selectedRsvps.length === 0) {
            alert('<?php esc_js_e( 'الرجاء تحديد ردود حضور للتنفيذ', 'invitation-manager-pro' ); ?>');
            return;
        }
        
        if (!action) {
            alert('<?php esc_js_e( 'الرجاء اختيار إجراء جماعي', 'invitation-manager-pro' ); ?>');
            return;
        }
        
        // تأكيد الإجراءات الخطيرة
        if (action === 'delete') {
            if (!confirm('<?php echo esc_js( __( 'هل أنت متأكد من حذف ردود الحضور المحددة؟', 'invitation-manager-pro' ) ); ?>')) {
                return;
            }
        }
        
        // تنفيذ الإجراء
        $('#bulk-action-form').append('<input type="hidden" name="rsvp_ids" value="' + selectedRsvps.join(',') + '">');
        $('#bulk-action-form').submit();
    });
    
    // تحديث حالة RSVP فردية
    $('.impro-update-rsvp-status').change(function() {
        var rsvpId = $(this).data('rsvp-id');
        var newStatus = $(this).val();
        
        if (confirm('<?php echo esc_js( __( 'هل أنت متأكد من تحديث حالة هذا الرد؟', 'invitation-manager-pro' ) ); ?>')) {
            // هنا يمكنك إضافة كود تحديث الحالة عبر AJAX
            $(this).closest('form').submit();
        } else {
            // إعادة تعيين القيمة الأصلية
            var originalValue = $(this).data('original-status');
            $(this).val(originalValue);
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
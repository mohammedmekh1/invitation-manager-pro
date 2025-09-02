<?php
/**
 * Events list page.
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
    error_log( 'Failed to load events: ' . $e->getMessage() );
    $events = array();
    $event_manager = null;
}

// التحقق من صحة البيانات
if ( ! is_array( $events ) ) {
    $events = array();
}

?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e( 'المناسبات', 'invitation-manager-pro' ); ?></h1>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-events&action=add' ) ); ?>" class="page-title-action">
        <?php _e( 'إضافة مناسبة جديدة', 'invitation-manager-pro' ); ?>
    </a>
    <hr class="wp-header-end">

    <?php 
    // عرض رسائل النجاح
    if ( isset( $_GET['message'] ) ) : 
        $success_messages = array(
            'created' => __( 'تم إنشاء المناسبة بنجاح.', 'invitation-manager-pro' ),
            'updated' => __( 'تم تحديث المناسبة بنجاح.', 'invitation-manager-pro' ),
            'deleted' => __( 'تم حذف المناسبة بنجاح.', 'invitation-manager-pro' ),
            'invitations_generated' => __( 'تم إنشاء الدعوات بنجاح.', 'invitation-manager-pro' ),
            'invitations_sent' => __( 'تم إرسال الدعوات بنجاح.', 'invitation-manager-pro' )
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
            'create_failed' => __( 'فشل في إنشاء المناسبة.', 'invitation-manager-pro' ),
            'update_failed' => __( 'فشل في تحديث المناسبة.', 'invitation-manager-pro' ),
            'delete_failed' => __( 'فشل في حذف المناسبة.', 'invitation-manager-pro' ),
            'load_failed' => __( 'فشل في تحميل المناسبات.', 'invitation-manager-pro' ),
            'permission_denied' => __( 'ليس لديك الصلاحية للقيام بهذا الإجراء.', 'invitation-manager-pro' )
        );
        
        $error_key = sanitize_text_field( $_GET['error'] );
        $error_text = isset( $error_messages[ $error_key ] ) ? $error_messages[ $error_key ] : __( 'حدث خطأ غير متوقع.', 'invitation-manager-pro' );
    ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html( $error_text ); ?></p>
        </div>
    <?php endif; ?>

    <!-- أدوات التصفية والبحث -->
    <div class="impro-events-toolbar">
        <div class="impro-search-box">
            <form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
                <input type="hidden" name="page" value="impro-events">
                <input 
                    type="search" 
                    name="s" 
                    value="<?php echo isset( $_GET['s'] ) ? esc_attr( $_GET['s'] ) : ''; ?>" 
                    placeholder="<?php esc_attr_e( 'البحث في المناسبات...', 'invitation-manager-pro' ); ?>"
                    class="impro-search-input"
                >
                <button type="submit" class="button"><?php _e( 'بحث', 'invitation-manager-pro' ); ?></button>
                <?php if ( isset( $_GET['s'] ) && ! empty( $_GET['s'] ) ) : ?>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-events' ) ); ?>" class="button button-secondary">
                        <?php _e( 'مسح البحث', 'invitation-manager-pro' ); ?>
                    </a>
                <?php endif; ?>
            </form>
        </div>
        
        <div class="impro-filter-options">
            <select name="status" onchange="window.location.href=this.value">
                <option value="<?php echo esc_url( admin_url( 'admin.php?page=impro-events' ) ); ?>">
                    <?php _e( 'جميع المناسبات', 'invitation-manager-pro' ); ?>
                </option>
                <option value="<?php echo esc_url( admin_url( 'admin.php?page=impro-events&status=upcoming' ) ); ?>" 
                        <?php echo isset( $_GET['status'] ) && $_GET['status'] === 'upcoming' ? 'selected' : ''; ?>>
                    <?php _e( 'القادمة', 'invitation-manager-pro' ); ?>
                </option>
                <option value="<?php echo esc_url( admin_url( 'admin.php?page=impro-events&status=past' ) ); ?>" 
                        <?php echo isset( $_GET['status'] ) && $_GET['status'] === 'past' ? 'selected' : ''; ?>>
                    <?php _e( 'الماضية', 'invitation-manager-pro' ); ?>
                </option>
            </select>
        </div>
    </div>

    <!-- إحصائيات سريعة -->
    <?php if ( ! empty( $events ) ) : ?>
        <div class="impro-events-summary">
            <div class="summary-card">
                <span class="summary-number"><?php echo esc_html( count( $events ) ); ?></span>
                <span class="summary-label"><?php _e( 'إجمالي المناسبات', 'invitation-manager-pro' ); ?></span>
            </div>
            <?php
            $upcoming_count = 0;
            $past_count = 0;
            foreach ( $events as $event ) {
                if ( isset( $event->event_date ) ) {
                    if ( strtotime( $event->event_date ) >= strtotime( 'today' ) ) {
                        $upcoming_count++;
                    } else {
                        $past_count++;
                    }
                }
            }
            ?>
            <div class="summary-card">
                <span class="summary-number"><?php echo esc_html( $upcoming_count ); ?></span>
                <span class="summary-label"><?php _e( 'المناسبات القادمة', 'invitation-manager-pro' ); ?></span>
            </div>
            <div class="summary-card">
                <span class="summary-number"><?php echo esc_html( $past_count ); ?></span>
                <span class="summary-label"><?php _e( 'المناسبات الماضية', 'invitation-manager-pro' ); ?></span>
            </div>
        </div>
    <?php endif; ?>

    <!-- جدول المناسبات -->
    <div class="impro-events-table-container">
        <table class="wp-list-table widefat fixed striped improvments-table">
            <thead>
                <tr>
                    <th class="manage-column column-primary"><?php _e( 'الاسم', 'invitation-manager-pro' ); ?></th>
                    <th class="manage-column"><?php _e( 'التاريخ', 'invitation-manager-pro' ); ?></th>
                    <th class="manage-column"><?php _e( 'المكان', 'invitation-manager-pro' ); ?></th>
                    <th class="manage-column"><?php _e( 'المدعوين', 'invitation-manager-pro' ); ?></th>
                    <th class="manage-column"><?php _e( 'الحضور', 'invitation-manager-pro' ); ?></th>
                    <th class="manage-column"><?php _e( 'الدعوات', 'invitation-manager-pro' ); ?></th>
                    <th class="manage-column column-actions"><?php _e( 'الإجراءات', 'invitation-manager-pro' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty( $events ) ) : ?>
                    <?php foreach ( $events as $event ) : ?>
                        <?php
                        // تهيئة البيانات بأمان
                        $event_id = isset( $event->id ) ? intval( $event->id ) : 0;
                        $event_name = isset( $event->name ) ? esc_html( $event->name ) : __( 'غير محدد', 'invitation-manager-pro' );
                        $event_date = isset( $event->event_date ) ? esc_html( date_i18n( get_option( 'date_format' ), strtotime( $event->event_date ) ) ) : __( 'غير محدد', 'invitation-manager-pro' );
                        $event_venue = isset( $event->venue ) ? esc_html( $event->venue ) : __( 'غير محدد', 'invitation-manager-pro' );
                        
                        // الحصول على الإحصائيات بأمان
                        try {
                            $guest_count = $event_manager ? $event_manager->get_event_guest_count( $event_id ) : 0;
                            $rsvp_stats = $event_manager ? $event_manager->get_event_rsvp_statistics( $event_id ) : array(
                                'accepted' => 0,
                                'declined' => 0,
                                'pending' => 0
                            );
                            
                            // الحصول على إحصائيات الدعوات
                            $invitation_manager = new IMPRO_Invitation_Manager();
                            $invitation_stats = $invitation_manager ? $invitation_manager->get_invitation_statistics( $event_id ) : array(
                                'total' => 0,
                                'sent' => 0,
                                'opened' => 0
                            );
                        } catch ( Exception $e ) {
                            error_log( 'Failed to get event stats for event ' . $event_id . ': ' . $e->getMessage() );
                            $guest_count = 0;
                            $rsvp_stats = array( 'accepted' => 0, 'declined' => 0, 'pending' => 0 );
                            $invitation_stats = array( 'total' => 0, 'sent' => 0, 'opened' => 0 );
                        }
                        ?>
                        <tr>
                            <td class="column-primary" data-colname="<?php esc_attr_e( 'الاسم', 'invitation-manager-pro' ); ?>">
                                <strong>
                                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-events&action=view&event_id=' . $event_id ) ); ?>">
                                        <?php echo $event_name; ?>
                                    </a>
                                </strong>
                                <button type="button" class="toggle-row"><span class="screen-reader-text"><?php _e( 'عرض المزيد', 'invitation-manager-pro' ); ?></span></button>
                            </td>
                            <td data-colname="<?php esc_attr_e( 'التاريخ', 'invitation-manager-pro' ); ?>">
                                <?php echo $event_date; ?>
                            </td>
                            <td data-colname="<?php esc_attr_e( 'المكان', 'invitation-manager-pro' ); ?>">
                                <?php echo $event_venue; ?>
                            </td>
                            <td data-colname="<?php esc_attr_e( 'المدعوين', 'invitation-manager-pro' ); ?>">
                                <span class="impro-stat-badge"><?php echo esc_html( $guest_count ); ?></span>
                            </td>
                            <td data-colname="<?php esc_attr_e( 'الحضور', 'invitation-manager-pro' ); ?>">
                                <div class="impro-rsvp-stats">
                                    <span class="rsvp-accepted" title="<?php esc_attr_e( 'موافق', 'invitation-manager-pro' ); ?>">
                                        <?php echo esc_html( isset($rsvp_stats['accepted']) ? $rsvp_stats['accepted'] : 0 ); ?>
                                    </span>
                                    <span class="rsvp-declined" title="<?php esc_attr_e( 'معتذر', 'invitation-manager-pro' ); ?>">
                                        <?php echo esc_html( isset($rsvp_stats['declined']) ? $rsvp_stats['declined'] : 0 ); ?>
                                    </span>
                                    <span class="rsvp-pending" title="<?php esc_attr_e( 'قيد الانتظار', 'invitation-manager-pro' ); ?>">
                                        <?php echo esc_html( isset($rsvp_stats['pending']) ? $rsvp_stats['pending'] : 0 ); ?>
                                    </span>
                                </div>
                            </td>
                            <td data-colname="<?php esc_attr_e( 'الدعوات', 'invitation-manager-pro' ); ?>">
                                <div class="impro-invitation-stats">
                                    <span class="invitation-sent" title="<?php esc_attr_e( 'مرسلة', 'invitation-manager-pro' ); ?>">
                                        <?php echo esc_html( isset($invitation_stats['sent']) ? $invitation_stats['sent'] : 0 ); ?>
                                    </span>
                                    <span class="invitation-opened" title="<?php esc_attr_e( 'مفتوحة', 'invitation-manager-pro' ); ?>">
                                        <?php echo esc_html( isset($invitation_stats['opened']) ? $invitation_stats['opened'] : 0 ); ?>
                                    </span>
                                    <span class="invitation-total" title="<?php esc_attr_e( 'إجمالي', 'invitation-manager-pro' ); ?>">
                                        <?php echo esc_html( isset($invitation_stats['total']) ? $invitation_stats['total'] : 0 ); ?>
                                    </span>
                                </div>
                            </td>
                            <td class="column-actions" data-colname="<?php esc_attr_e( 'الإجراءات', 'invitation-manager-pro' ); ?>">
                                <div class="impro-action-buttons">
                                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-events&action=edit&event_id=' . $event_id ) ); ?>" class="button button-small">
                                        <?php _e( 'تعديل', 'invitation-manager-pro' ); ?>
                                    </a>
                                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-events&action=view&event_id=' . $event_id ) ); ?>" class="button button-small button-secondary">
                                        <?php _e( 'عرض', 'invitation-manager-pro' ); ?>
                                    </a>
                                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-invitations&event_id=' . $event_id ) ); ?>" class="button button-small button-secondary">
                                        <?php _e( 'الدعوات', 'invitation-manager-pro' ); ?>
                                    </a>
                                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-rsvps&event_id=' . $event_id ) ); ?>" class="button button-small button-secondary">
                                        <?php _e( 'الردود', 'invitation-manager-pro' ); ?>
                                    </a>
                                    <div class="impro-dropdown">
                                        <button type="button" class="button button-small dropdown-toggle">
                                            <?php _e( 'المزيد', 'invitation-manager-pro' ); ?>
                                        </button>
                                        <div class="dropdown-menu">
                                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-guests&event_id=' . $event_id ) ); ?>">
                                                <?php _e( 'عرض المدعوين', 'invitation-manager-pro' ); ?>
                                            </a>
                                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-events&action=generate_invitations&event_id=' . $event_id ) ); ?>">
                                                <?php _e( 'إنشاء دعوات', 'invitation-manager-pro' ); ?>
                                            </a>
                                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-events&action=send_invitations&event_id=' . $event_id ) ); ?>">
                                                <?php _e( 'إرسال الدعوات', 'invitation-manager-pro' ); ?>
                                            </a>
                                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-events&action=export&event_id=' . $event_id ) ); ?>">
                                                <?php _e( 'تصدير', 'invitation-manager-pro' ); ?>
                                            </a>
                                            <div class="dropdown-divider"></div>
                                            <form method="post" style="display:block; margin:0;" 
                                                  onsubmit="return confirm('<?php echo esc_js( __( 'هل أنت متأكد من حذف هذه المناسبة؟ سيتم حذف جميع المدعوين والدعوات المرتبطة بها.', 'invitation-manager-pro' ) ); ?>');">
                                                <input type="hidden" name="impro_action" value="delete_event">
                                                <input type="hidden" name="event_id" value="<?php echo esc_attr( $event_id ); ?>">
                                                <?php wp_nonce_field( 'impro_admin_action', '_wpnonce' ); ?>
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <?php _e( 'حذف', 'invitation-manager-pro' ); ?>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="7" class="no-items">
                            <div class="impro-empty-state">
                                <span class="dashicons dashicons-calendar-alt"></span>
                                <h3><?php _e( 'لا توجد مناسبات', 'invitation-manager-pro' ); ?></h3>
                                <p><?php _e( 'لم تقم بإنشاء أي مناسبات حتى الآن.', 'invitation-manager-pro' ); ?></p>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-events&action=add' ) ); ?>" class="button button-primary">
                                    <?php _e( 'إنشاء مناسبة أولى', 'invitation-manager-pro' ); ?>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- ترقيم الصفحات -->
    <?php if ( ! empty( $events ) && count( $events ) > 20 ) : ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="displaying-num">
                    <?php printf( __( 'عرض %d من أصل %d مناسبة', 'invitation-manager-pro' ), count( $events ), count( $events ) ); ?>
                </span>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.wrap {
    margin: 20px 20px 0 0;
}

/* شريط الأدوات */
.impro-events-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 20px 0;
    padding: 15px;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    flex-wrap: wrap;
    gap: 15px;
}

.impro-search-box {
    display: flex;
    gap: 10px;
    flex: 1;
    max-width: 500px;
}

.impro-search-input {
    flex: 1;
    min-width: 200px;
}

.impro-filter-options select {
    min-width: 150px;
}

/* إحصائيات سريعة */
.impro-events-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.summary-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.summary-number {
    display: block;
    font-size: 2em;
    font-weight: bold;
    color: #1d2327;
    margin-bottom: 5px;
}

.summary-label {
    color: #646970;
    font-size: 0.9em;
}

/* جدول المناسبات */
.impro-events-table-container {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.improvments-table {
    border: none;
    margin: 0;
}

.improvments-table thead th {
    background: #f6f7f7;
    border-bottom: 2px solid #ccd0d4;
    font-weight: 600;
}

.improvments-table tbody td {
    vertical-align: middle;
}

/* شارات الإحصائيات */
.impro-stat-badge {
    display: inline-block;
    padding: 4px 10px;
    background: #f0f0f1;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    color: #1d2327;
}

/* إحصائيات RSVP */
.impro-rsvp-stats {
    display: flex;
    gap: 8px;
    align-items: center;
}

.impro-rsvp-stats span {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 600;
}

.rsvp-accepted {
    background: #e6f4ea;
    color: #008a20;
}

.rsvp-declined {
    background: #f8e2df;
    color: #d63638;
}

.rsvp-pending {
    background: #fef8ee;
    color: #d63638;
}

/* إحصائيات الدعوات */
.impro-invitation-stats {
    display: flex;
    gap: 5px;
    align-items: center;
    flex-wrap: wrap;
}

.impro-invitation-stats span {
    display: inline-block;
    padding: 2px 6px;
    border-radius: 8px;
    font-size: 10px;
    font-weight: 600;
}

.invitation-sent {
    background: #e1f0fa;
    color: #0073aa;
}

.invitation-opened {
    background: #e6f4ea;
    color: #008a20;
}

.invitation-total {
    background: #f0f0f1;
    color: #50575e;
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

/* تصميم متجاوب */
@media (max-width: 782px) {
    .impro-events-toolbar {
        flex-direction: column;
        align-items: stretch;
    }
    
    .impro-search-box {
        max-width: none;
    }
    
    .improvments-table .column-primary {
        width: auto;
    }
    
    .improvments-table .column-actions {
        width: 150px;
    }
    
    .impro-action-buttons {
        flex-direction: column;
        align-items: flex-start;
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
    
    .dropdown-item {
        padding: 5px 0;
        font-size: 13px;
    }
}

@media (max-width: 480px) {
    .impro-events-summary {
        grid-template-columns: 1fr;
    }
    
    .impro-rsvp-stats,
    .impro-invitation-stats {
        flex-direction: column;
        align-items: flex-start;
        gap: 3px;
    }
    
    .impro-stat-badge,
    .impro-rsvp-stats span,
    .impro-invitation-stats span {
        font-size: 10px;
        padding: 1px 6px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // تحسين تجربة القائمة المنسدلة
    $('.impro-dropdown').hover(
        function() {
            $(this).find('.dropdown-menu').stop(true, true).fadeIn(200);
        },
        function() {
            $(this).find('.dropdown-menu').stop(true, true).fadeOut(200);
        }
    );
    
    // إغلاق الإشعارات تلقائياً
    setTimeout(function() {
        $('.notice.is-dismissible').fadeOut(500, function() {
            $(this).remove();
        });
    }, 5000);
    
    // تأكيد الحذف
    $('form[method="post"]').on('submit', function(e) {
        var $form = $(this);
        var action = $form.find('input[name="impro_action"]').val();
        
        if (action === 'delete_event') {
            if (!confirm('<?php echo esc_js( __( 'هل أنت متأكد من حذف هذه المناسبة؟ سيتم حذف جميع المدعوين والدعوات المرتبطة بها.', 'invitation-manager-pro' ) ); ?>')) {
                e.preventDefault();
                return false;
            }
        }
    });
    
    // تحسين البحث
    $('.impro-search-input').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            $(this).closest('form').submit();
        }
    });
});
</script>
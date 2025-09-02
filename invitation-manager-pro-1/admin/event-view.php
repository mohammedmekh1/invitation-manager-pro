
<?php
/**
 * Event view page.
 *
 * @package Invitation_Manager_Pro
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// التحقق من صحة البيانات
if ( ! isset( $event ) || ! $event || ! isset( $event->id ) ) {
    wp_die( __( 'المناسبة غير موجودة.', 'invitation-manager-pro' ) );
}

// تهيئة المديرين بأمان
try {
    $event_manager = new IMPRO_Event_Manager();
    $guest_manager = new IMPRO_Guest_Manager();
    $invitation_manager = new IMPRO_Invitation_Manager();
    $rsvp_manager = new IMPRO_RSVP_Manager();
} catch ( Exception $e ) {
    error_log( 'Failed to initialize managers: ' . $e->getMessage() );
    wp_die( __( 'فشل في تحميل المكونات المطلوبة.', 'invitation-manager-pro' ) );
}

// الحصول على البيانات بأمان
try {
    $guests = $guest_manager->get_guests_by_event( $event->id ) ?: array();
    $invitations = $invitation_manager->get_event_invitations( $event->id ) ?: array();
    $rsvps = $rsvp_manager->get_event_rsvps( $event->id ) ?: array();
    $rsvp_stats = $rsvp_manager->get_event_rsvp_statistics( $event->id ) ?: array(
        'accepted' => 0,
        'declined' => 0,
        'pending' => 0,
        'total' => 0
    );
} catch ( Exception $e ) {
    error_log( 'Failed to load event data: ' . $e->getMessage() );
    $guests = array();
    $invitations = array();
    $rsvps = array();
    $rsvp_stats = array(
        'accepted' => 0,
        'declined' => 0,
        'pending' => 0,
        'total' => 0
    );
}

// تهيئة القيم الافتراضية بأمان
$event_name = isset( $event->name ) ? esc_html( $event->name ) : __( 'مناسبة غير محددة', 'invitation-manager-pro' );
$event_date = isset( $event->event_date ) ? esc_html( date_i18n( get_option( 'date_format' ), strtotime( $event->event_date ) ) ) : __( 'غير محدد', 'invitation-manager-pro' );
$event_time = isset( $event->event_time ) ? esc_html( date_i18n( get_option( 'time_format' ), strtotime( $event->event_time ) ) ) : __( 'غير محدد', 'invitation-manager-pro' );
$event_venue = isset( $event->venue ) ? esc_html( $event->venue ) : __( 'غير محدد', 'invitation-manager-pro' );
$event_address = isset( $event->address ) ? esc_html( $event->address ) : __( 'غير محدد', 'invitation-manager-pro' );
$event_description = isset( $event->description ) ? wp_kses_post( wpautop( $event->description ) ) : __( 'لا يوجد وصف', 'invitation-manager-pro' );
$invitation_image_url = isset( $event->invitation_image_url ) ? esc_url( $event->invitation_image_url ) : '';
$invitation_text = isset( $event->invitation_text ) ? wp_kses_post( wpautop( $event->invitation_text ) ) : __( 'لا يوجد نص دعوة', 'invitation-manager-pro' );
$location_details = isset( $event->location_details ) ? wp_kses_post( wpautop( $event->location_details ) ) : __( 'لا توجد تفاصيل موقع', 'invitation-manager-pro' );
$contact_info = isset( $event->contact_info ) ? esc_html( $event->contact_info ) : __( 'لا توجد معلومات اتصال', 'invitation-manager-pro' );

?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php echo $event_name; ?>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-events&action=edit&event_id=' . $event->id ) ); ?>" class="page-title-action">
            <?php _e( 'تعديل', 'invitation-manager-pro' ); ?>
        </a>
    </h1>
    <hr class="wp-header-end">

    <?php if ( isset( $_GET['message'] ) ) : ?>
        <?php
        $messages = array(
            'created' => __( 'تم إنشاء المناسبة بنجاح.', 'invitation-manager-pro' ),
            'updated' => __( 'تم تحديث المناسبة بنجاح.', 'invitation-manager-pro' ),
            'invitations_generated' => __( 'تم إنشاء الدعوات بنجاح.', 'invitation-manager-pro' ),
            'invitations_sent' => __( 'تم إرسال الدعوات بنجاح.', 'invitation-manager-pro' )
        );
        
        $message_key = sanitize_text_field( $_GET['message'] );
        $message_text = isset( $messages[ $message_key ] ) ? $messages[ $message_key ] : '';
        ?>
        <?php if ( ! empty( $message_text ) ) : ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo esc_html( $message_text ); ?></p>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ( isset( $_GET['error'] ) ) : ?>
        <div class="notice notice-error is-dismissible">
            <p>
                <?php
                switch ( $_GET['error'] ) {
                    case 'send_failed':
                        _e( 'فشل في إرسال بعض الدعوات.', 'invitation-manager-pro' );
                        break;
                    case 'generate_failed':
                        _e( 'فشل في إنشاء بعض الدعوات.', 'invitation-manager-pro' );
                        break;
                    default:
                        _e( 'حدث خطأ أثناء العملية.', 'invitation-manager-pro' );
                        break;
                }
                ?>
            </p>
        </div>
    <?php endif; ?>

    <div class="impro-event-dashboard">
        <!-- بطاقات الإحصائيات السريعة -->
        <div class="impro-stats-cards">
            <div class="impro-stat-card">
                <div class="stat-icon">
                    <span class="dashicons dashicons-groups"></span>
                </div>
                <div class="stat-content">
                    <h3><?php echo esc_html( count( $guests ) ); ?></h3>
                    <p><?php _e( 'إجمالي المدعوين', 'invitation-manager-pro' ); ?></p>
                </div>
            </div>
            
            <div class="impro-stat-card">
                <div class="stat-icon">
                    <span class="dashicons dashicons-email"></span>
                </div>
                <div class="stat-content">
                    <h3><?php echo esc_html( count( $invitations ) ); ?></h3>
                    <p><?php _e( 'الدعوات المرسلة', 'invitation-manager-pro' ); ?></p>
                </div>
            </div>
            
            <div class="impro-stat-card">
                <div class="stat-icon">
                    <span class="dashicons dashicons-yes"></span>
                </div>
                <div class="stat-content">
                    <h3><?php echo esc_html( $rsvp_stats['accepted'] ); ?></h3>
                    <p><?php _e( 'الموافقون', 'invitation-manager-pro' ); ?></p>
                </div>
            </div>
            
            <div class="impro-stat-card">
                <div class="stat-icon">
                    <span class="dashicons dashicons-no"></span>
                </div>
                <div class="stat-content">
                    <h3><?php echo esc_html( $rsvp_stats['declined'] ); ?></h3>
                    <p><?php _e( 'المعتذرون', 'invitation-manager-pro' ); ?></p>
                </div>
            </div>
        </div>

        <!-- أزرار الإجراءات السريعة -->
        <div class="impro-action-buttons">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-guests&action=add&event_id=' . $event->id ) ); ?>" class="button button-primary">
                <?php _e( 'إضافة ضيف', 'invitation-manager-pro' ); ?>
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-invitations&event_id=' . $event->id ) ); ?>" class="button">
                <?php _e( 'إدارة الدعوات', 'invitation-manager-pro' ); ?>
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-rsvps&event_id=' . $event->id ) ); ?>" class="button">
                <?php _e( 'عرض الردود', 'invitation-manager-pro' ); ?>
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-events&action=generate_invitations&event_id=' . $event->id ) ); ?>" class="button">
                <?php _e( 'إنشاء دعوات', 'invitation-manager-pro' ); ?>
            </a>
        </div>

        <!-- تفاصيل المناسبة -->
        <div class="impro-section">
            <div class="impro-section-header">
                <h2><?php _e( 'تفاصيل المناسبة', 'invitation-manager-pro' ); ?></h2>
            </div>
            
            <div class="impro-section-content">
                <div class="impro-event-details-grid">
                    <div class="impro-detail-item">
                        <div class="detail-label"><?php _e( 'التاريخ', 'invitation-manager-pro' ); ?></div>
                        <div class="detail-value"><?php echo $event_date; ?></div>
                    </div>
                    
                    <div class="impro-detail-item">
                        <div class="detail-label"><?php _e( 'الوقت', 'invitation-manager-pro' ); ?></div>
                        <div class="detail-value"><?php echo $event_time; ?></div>
                    </div>
                    
                    <div class="impro-detail-item">
                        <div class="detail-label"><?php _e( 'المكان', 'invitation-manager-pro' ); ?></div>
                        <div class="detail-value"><?php echo $event_venue; ?></div>
                    </div>
                    
                    <div class="impro-detail-item">
                        <div class="detail-label"><?php _e( 'العنوان', 'invitation-manager-pro' ); ?></div>
                        <div class="detail-value"><?php echo $event_address; ?></div>
                    </div>
                    
                    <?php if ( ! empty( $contact_info ) && $contact_info !== __( 'لا توجد معلومات اتصال', 'invitation-manager-pro' ) ) : ?>
                        <div class="impro-detail-item">
                            <div class="detail-label"><?php _e( 'معلومات الاتصال', 'invitation-manager-pro' ); ?></div>
                            <div class="detail-value"><?php echo $contact_info; ?></div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ( ! empty( $event_description ) && $event_description !== __( 'لا يوجد وصف', 'invitation-manager-pro' ) ) : ?>
                    <div class="impro-detail-section">
                        <h3><?php _e( 'الوصف', 'invitation-manager-pro' ); ?></h3>
                        <div class="detail-content"><?php echo $event_description; ?></div>
                    </div>
                <?php endif; ?>
                
                <?php if ( ! empty( $invitation_image_url ) ) : ?>
                    <div class="impro-detail-section">
                        <h3><?php _e( 'صورة الدعوة', 'invitation-manager-pro' ); ?></h3>
                        <div class="impro-invitation-image">
                            <img src="<?php echo $invitation_image_url; ?>" alt="<?php esc_attr_e( 'صورة الدعوة', 'invitation-manager-pro' ); ?>" />
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ( ! empty( $invitation_text ) && $invitation_text !== __( 'لا يوجد نص دعوة', 'invitation-manager-pro' ) ) : ?>
                    <div class="impro-detail-section">
                        <h3><?php _e( 'نص الدعوة', 'invitation-manager-pro' ); ?></h3>
                        <div class="detail-content"><?php echo $invitation_text; ?></div>
                    </div>
                <?php endif; ?>
                
                <?php if ( ! empty( $location_details ) && $location_details !== __( 'لا توجد تفاصيل موقع', 'invitation-manager-pro' ) ) : ?>
                    <div class="impro-detail-section">
                        <h3><?php _e( 'تفاصيل الموقع', 'invitation-manager-pro' ); ?></h3>
                        <div class="detail-content"><?php echo $location_details; ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- إحصائيات الحضور -->
        <div class="impro-section">
            <div class="impro-section-header">
                <h2><?php _e( 'إحصائيات الحضور', 'invitation-manager-pro' ); ?></h2>
            </div>
            
            <div class="impro-section-content">
                <div class="impro-stats-grid">
                    <div class="impro-stat-item">
                        <div class="stat-label"><?php _e( 'إجمالي المدعوين', 'invitation-manager-pro' ); ?></div>
                        <div class="stat-value"><?php echo esc_html( count( $guests ) ); ?></div>
                    </div>
                    
                    <div class="impro-stat-item">
                        <div class="stat-label"><?php _e( 'الدعوات المرسلة', 'invitation-manager-pro' ); ?></div>
                        <div class="stat-value"><?php echo esc_html( count( $invitations ) ); ?></div>
                    </div>
                    
                    <div class="impro-stat-item">
                        <div class="stat-label"><?php _e( 'الموافقون', 'invitation-manager-pro' ); ?></div>
                        <div class="stat-value stat-positive"><?php echo esc_html( $rsvp_stats['accepted'] ); ?></div>
                    </div>
                    
                    <div class="impro-stat-item">
                        <div class="stat-label"><?php _e( 'المعتذرون', 'invitation-manager-pro' ); ?></div>
                        <div class="stat-value stat-negative"><?php echo esc_html( $rsvp_stats['declined'] ); ?></div>
                    </div>
                    
                    <div class="impro-stat-item">
                        <div class="stat-label"><?php _e( 'قيد الانتظار', 'invitation-manager-pro' ); ?></div>
                        <div class="stat-value stat-neutral"><?php echo esc_html( $rsvp_stats['pending'] ); ?></div>
                    </div>
                    
                    <div class="impro-stat-item">
                        <div class="stat-label"><?php _e( 'نسبة الحضور المتوقعة', 'invitation-manager-pro' ); ?></div>
                        <div class="stat-value">
                            <?php 
                            $total_expected = $rsvp_stats['accepted'] + ( $rsvp_stats['pending'] * 0.5 );
                            $attendance_percentage = count( $guests ) > 0 ? round( ( $total_expected / count( $guests ) ) * 100 ) : 0;
                            echo esc_html( $attendance_percentage . '%' );
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- المدعوين المرتبطين -->
        <div class="impro-section">
            <div class="impro-section-header">
                <h2><?php _e( 'المدعوين المرتبطين', 'invitation-manager-pro' ); ?></h2>
                <div class="impro-section-actions">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-guests&action=add&event_id=' . $event->id ) ); ?>" class="button button-primary">
                        <?php _e( 'إضافة ضيف', 'invitation-manager-pro' ); ?>
                    </a>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-guests&event_id=' . $event->id ) ); ?>" class="button">
                        <?php _e( 'عرض الكل', 'invitation-manager-pro' ); ?>
                    </a>
                </div>
            </div>
            
            <div class="impro-section-content">
                <?php if ( ! empty( $guests ) ) : ?>
                    <div class="impro-responsive-table">
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e( 'الاسم', 'invitation-manager-pro' ); ?></th>
                                    <th><?php _e( 'البريد الإلكتروني', 'invitation-manager-pro' ); ?></th>
                                    <th><?php _e( 'الهاتف', 'invitation-manager-pro' ); ?></th>
                                    <th><?php _e( 'الحالة', 'invitation-manager-pro' ); ?></th>
                                    <th><?php _e( 'الإجراءات', 'invitation-manager-pro' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $guests as $guest ) : ?>
                                    <?php
                                    // الحصول على حالة RSVP بأمان
                                    $guest_rsvp = null;
                                    foreach ( $rsvps as $rsvp ) {
                                        if ( isset( $rsvp->guest_id ) && $rsvp->guest_id == $guest->id ) {
                                            $guest_rsvp = $rsvp;
                                            break;
                                        }
                                    }
                                    
                                    $status_text = __( 'لم يرد', 'invitation-manager-pro' );
                                    $status_class = 'status-pending';
                                    
                                    if ( $guest_rsvp && isset( $guest_rsvp->status ) ) {
                                        switch ( $guest_rsvp->status ) {
                                            case 'accepted':
                                                $status_text = __( 'موافق', 'invitation-manager-pro' );
                                                $status_class = 'status-accepted';
                                                break;
                                            case 'declined':
                                                $status_text = __( 'معتذر', 'invitation-manager-pro' );
                                                $status_class = 'status-declined';
                                                break;
                                        }
                                    }
                                    
                                    // تهيئة بيانات الضيف بأمان
                                    $guest_name = isset( $guest->name ) ? esc_html( $guest->name ) : __( 'غير محدد', 'invitation-manager-pro' );
                                    $guest_email = isset( $guest->email ) ? esc_html( $guest->email ) : __( 'غير محدد', 'invitation-manager-pro' );
                                    $guest_phone = isset( $guest->phone ) ? esc_html( $guest->phone ) : __( 'غير محدد', 'invitation-manager-pro' );
                                    ?>
                                    <tr>
                                        <td><?php echo $guest_name; ?></td>
                                        <td><?php echo $guest_email; ?></td>
                                        <td><?php echo $guest_phone; ?></td>
                                        <td>
                                            <span class="impro-status-badge <?php echo esc_attr( $status_class ); ?>">
                                                <?php echo esc_html( $status_text ); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="impro-action-buttons">
                                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-guests&action=edit&guest_id=' . ( isset( $guest->id ) ? $guest->id : 0 ) ) ); ?>" class="button button-small">
                                                    <?php _e( 'تعديل', 'invitation-manager-pro' ); ?>
                                                </a>
                                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-invitations&guest_id=' . ( isset( $guest->id ) ? $guest->id : 0 ) . '&event_id=' . $event->id ) ); ?>" class="button button-small">
                                                    <?php _e( 'الدعوة', 'invitation-manager-pro' ); ?>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else : ?>
                    <div class="impro-empty-state">
                        <span class="dashicons dashicons-groups"></span>
                        <h3><?php _e( 'لا يوجد مدعوين', 'invitation-manager-pro' ); ?></h3>
                        <p><?php _e( 'لم يتم إضافة أي مدعوين لهذه المناسبة بعد.', 'invitation-manager-pro' ); ?></p>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-guests&action=add&event_id=' . $event->id ) ); ?>" class="button button-primary">
                            <?php _e( 'إضافة ضيف أول', 'invitation-manager-pro' ); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.wrap {
    margin: 20px 20px 0 0;
}

.impro-event-dashboard {
    margin-top: 20px;
}

/* بطاقات الإحصائيات */
.impro-stats-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
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

.stat-icon .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
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

/* أزرار الإجراءات */
.impro-action-buttons {
    display: flex;
    gap: 10px;
    margin-bottom: 30px;
    flex-wrap: wrap;
}

.impro-action-buttons .button {
    margin: 0;
}

/* الأقسام */
.impro-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
    margin-bottom: 30px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.impro-section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #ccd0d4;
    flex-wrap: wrap;
    gap: 15px;
}

.impro-section-header h2 {
    margin: 0;
    color: #1d2327;
    font-size: 20px;
}

.impro-section-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.impro-section-content {
    padding: 20px;
}

/* تفاصيل المناسبة */
.impro-event-details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.impro-detail-item {
    background: #f9f9f9;
    border-radius: 6px;
    padding: 15px;
}

.detail-label {
    font-weight: 600;
    color: #50575e;
    font-size: 13px;
    margin-bottom: 5px;
    text-transform: uppercase;
}

.detail-value {
    font-size: 16px;
    color: #1d2327;
    word-break: break-word;
}

.impro-detail-section {
    margin-bottom: 25px;
}

.impro-detail-section h3 {
    margin: 0 0 15px 0;
    color: #1d2327;
    font-size: 18px;
}

.detail-content {
    background: #f9f9f9;
    border-radius: 6px;
    padding: 15px;
    color: #1d2327;
    line-height: 1.6;
}

.impro-invitation-image img {
    max-width: 100%;
    height: auto;
    border: 1px solid #ddd;
    border-radius: 6px;
    padding: 10px;
    background: #fff;
}

/* الإحصائيات */
.impro-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.impro-stat-item {
    background: #f9f9f9;
    border-radius: 6px;
    padding: 20px;
    text-align: center;
}

.stat-label {
    font-weight: 600;
    color: #50575e;
    font-size: 14px;
    margin-bottom: 10px;
}

.stat-value {
    font-size: 24px;
    font-weight: 700;
    color: #1d2327;
}

.stat-positive {
    color: #008a20 !important;
}

.stat-negative {
    color: #d63638 !important;
}

.stat-neutral {
    color: #f0ad4e !important;
}

/* حالة الحضور */
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

/* جدول المدعوين */
.impro-responsive-table {
    overflow-x: auto;
}

.impro-action-buttons {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}

.impro-action-buttons .button {
    margin: 0;
    font-size: 12px;
    padding: 4px 10px;
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
    .impro-stats-cards {
        grid-template-columns: 1fr 1fr;
    }
    
    .impro-action-buttons {
        justify-content: center;
    }
    
    .impro-section-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .impro-section-actions {
        justify-content: center;
    }
    
    .impro-event-details-grid {
        grid-template-columns: 1fr;
    }
    
    .impro-stats-grid {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 480px) {
    .impro-stats-cards {
        grid-template-columns: 1fr;
    }
    
    .impro-stats-grid {
        grid-template-columns: 1fr;
    }
    
    .impro-action-buttons {
        flex-direction: column;
    }
    
    .impro-section-actions {
        flex-direction: column;
    }
    
    .impro-action-buttons .button,
    .impro-section-actions .button {
        width: 100%;
        text-align: center;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // إضافة تأثيرات تفاعلية
    $('.impro-stat-card').hover(
        function() {
            $(this).find('.stat-icon').css('transform', 'scale(1.1)');
        },
        function() {
            $(this).find('.stat-icon').css('transform', 'scale(1)');
        }
    );
    
    // تأكيد الحذف
    $('.impro-delete-action').on('click', function(e) {
        if (!confirm('<?php esc_js_e( 'هل أنت متأكد من الحذف؟', 'invitation-manager-pro' ); ?>')) {
            e.preventDefault();
        }
    });
    
    // إغلاق الإشعارات تلقائياً
    setTimeout(function() {
        $('.notice.is-dismissible').fadeOut(500, function() {
            $(this).remove();
        });
    }, 5000);
});
</script>
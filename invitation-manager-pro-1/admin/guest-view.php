
<?php
/**
 * Guest view page.
 *
 * @package Invitation_Manager_Pro
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// التحقق من صحة البيانات
if ( ! isset( $guest ) || ! $guest || ! isset( $guest->id ) ) {
    wp_die( __( 'المدعو غير موجود.', 'invitation-manager-pro' ) );
}

// تهيئة المديرين بأمان
try {
    $invitation_manager = new IMPRO_Invitation_Manager();
    $rsvp_manager = new IMPRO_RSVP_Manager();
    $event_manager = new IMPRO_Event_Manager();
} catch ( Exception $e ) {
    error_log( 'Failed to initialize managers: ' . $e->getMessage() );
    wp_die( __( 'فشل في تحميل المكونات المطلوبة.', 'invitation-manager-pro' ) );
}

// الحصول على البيانات بأمان
try {
    $guest_invitations = $invitation_manager->get_guest_invitations( $guest->id ) ?: array();
    $guest_rsvps = $rsvp_manager->get_guest_rsvps( $guest->id ) ?: array();
} catch ( Exception $e ) {
    error_log( 'Failed to load guest data: ' . $e->getMessage() );
    $guest_invitations = array();
    $guest_rsvps = array();
}

// تهيئة بيانات المدعو بأمان
$guest_name = isset( $guest->name ) ? esc_html( $guest->name ) : __( 'غير محدد', 'invitation-manager-pro' );
$guest_email = isset( $guest->email ) ? esc_html( $guest->email ) : __( 'غير محدد', 'invitation-manager-pro' );
$guest_phone = isset( $guest->phone ) ? esc_html( $guest->phone ) : __( 'غير محدد', 'invitation-manager-pro' );
$guest_category = isset( $guest->category ) ? esc_html( $guest->category ) : __( 'غير محدد', 'invitation-manager-pro' );
$plus_one_allowed = isset( $guest->plus_one_allowed ) ? (bool) $guest->plus_one_allowed : false;
$guest_gender = isset( $guest->gender ) ? esc_html( $guest->gender ) : __( 'غير محدد', 'invitation-manager-pro' );
$guest_age_range = isset( $guest->age_range ) ? esc_html( $guest->age_range ) : __( 'غير محدد', 'invitation-manager-pro' );
$guest_relationship = isset( $guest->relationship ) ? esc_html( $guest->relationship ) : __( 'غير محدد', 'invitation-manager-pro' );

// ترجمة الفئات
$category_labels = array(
    'family' => __( 'عائلة', 'invitation-manager-pro' ),
    'friends' => __( 'أصدقاء', 'invitation-manager-pro' ),
    'colleagues' => __( 'زملاء', 'invitation-manager-pro' ),
    'vip' => __( 'شخصيات مهمة', 'invitation-manager-pro' ),
    'other' => __( 'أخرى', 'invitation-manager-pro' )
);

$gender_labels = array(
    'male' => __( 'ذكر', 'invitation-manager-pro' ),
    'female' => __( 'أنثى', 'invitation-manager-pro' )
);

$age_range_labels = array(
    'child' => __( 'طفل', 'invitation-manager-pro' ),
    'teen' => __( 'مراهق', 'invitation-manager-pro' ),
    'adult' => __( 'بالغ', 'invitation-manager-pro' ),
    'senior' => __( 'كبير السن', 'invitation-manager-pro' )
);

$category_label = isset( $category_labels[ $guest_category ] ) ? $category_labels[ $guest_category ] : $guest_category;
$gender_label = isset( $gender_labels[ $guest_gender ] ) ? $gender_labels[ $guest_gender ] : $guest_gender;
$age_range_label = isset( $age_range_labels[ $guest_age_range ] ) ? $age_range_labels[ $guest_age_range ] : $guest_age_range;

?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php echo $guest_name; ?>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-guests&action=edit&guest_id=' . $guest->id ) ); ?>" class="page-title-action">
            <?php _e( 'تعديل', 'invitation-manager-pro' ); ?>
        </a>
    </h1>
    <hr class="wp-header-end">

    <?php if ( isset( $_GET['message'] ) ) : ?>
        <?php
        $messages = array(
            'created' => __( 'تم إنشاء المدعو بنجاح.', 'invitation-manager-pro' ),
            'updated' => __( 'تم تحديث المدعو بنجاح.', 'invitation-manager-pro' ),
            'invitations_sent' => __( 'تم إرسال الدعوات بنجاح.', 'invitation-manager-pro' ),
            'rsvp_updated' => __( 'تم تحديث رد الحضور بنجاح.', 'invitation-manager-pro' )
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
                        _e( 'فشل في إرسال الدعوات.', 'invitation-manager-pro' );
                        break;
                    case 'load_failed':
                        _e( 'فشل في تحميل بيانات المدعو.', 'invitation-manager-pro' );
                        break;
                    default:
                        _e( 'حدث خطأ أثناء العملية.', 'invitation-manager-pro' );
                        break;
                }
                ?>
            </p>
        </div>
    <?php endif; ?>

    <div class="impro-guest-dashboard">
        <!-- بطاقات الإحصائيات السريعة -->
        <div class="impro-stats-cards">
            <div class="impro-stat-card">
                <div class="stat-icon">
                    <span class="dashicons dashicons-email"></span>
                </div>
                <div class="stat-content">
                    <h3><?php echo esc_html( count( $guest_invitations ) ); ?></h3>
                    <p><?php _e( 'دعوات مرسلة', 'invitation-manager-pro' ); ?></p>
                </div>
            </div>
            
            <div class="impro-stat-card">
                <div class="stat-icon">
                    <span class="dashicons dashicons-yes"></span>
                </div>
                <div class="stat-content">
                    <h3>
                        <?php 
                        $accepted_count = 0;
                        foreach ( $guest_rsvps as $rsvp ) {
                            if ( isset( $rsvp->status ) && $rsvp->status === 'accepted' ) {
                                $accepted_count++;
                            }
                        }
                        echo esc_html( $accepted_count );
                        ?>
                    </h3>
                    <p><?php _e( 'موافقات', 'invitation-manager-pro' ); ?></p>
                </div>
            </div>
            
            <div class="impro-stat-card">
                <div class="stat-icon">
                    <span class="dashicons dashicons-no"></span>
                </div>
                <div class="stat-content">
                    <h3>
                        <?php 
                        $declined_count = 0;
                        foreach ( $guest_rsvps as $rsvp ) {
                            if ( isset( $rsvp->status ) && $rsvp->status === 'declined' ) {
                                $declined_count++;
                            }
                        }
                        echo esc_html( $declined_count );
                        ?>
                    </h3>
                    <p><?php _e( 'اعتذارات', 'invitation-manager-pro' ); ?></p>
                </div>
            </div>
            
            <div class="impro-stat-card">
                <div class="stat-icon">
                    <span class="dashicons dashicons-groups"></span>
                </div>
                <div class="stat-content">
                    <h3>
                        <?php 
                        $total_plus_ones = 0;
                        foreach ( $guest_rsvps as $rsvp ) {
                            if ( isset( $rsvp->plus_one_attending ) ) {
                                $total_plus_ones += intval( $rsvp->plus_one_attending );
                            }
                        }
                        echo esc_html( $total_plus_ones );
                        ?>
                    </h3>
                    <p><?php _e( 'مرافقون', 'invitation-manager-pro' ); ?></p>
                </div>
            </div>
        </div>

        <!-- أزرار الإجراءات السريعة -->
        <div class="impro-action-buttons">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-invitations&action=send&guest_id=' . $guest->id ) ); ?>" class="button button-primary">
                <?php _e( 'إرسال دعوة', 'invitation-manager-pro' ); ?>
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-rsvps&action=add&guest_id=' . $guest->id ) ); ?>" class="button">
                <?php _e( 'إضافة رد حضور', 'invitation-manager-pro' ); ?>
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-guests&action=edit&guest_id=' . $guest->id ) ); ?>" class="button">
                <?php _e( 'تعديل المدعو', 'invitation-manager-pro' ); ?>
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-guests&action=delete&guest_id=' . $guest->id ) ); ?>" 
               class="button button-link-delete" 
               onclick="return confirm('<?php echo esc_js( __( 'هل أنت متأكد من حذف هذا المدعو؟', 'invitation-manager-pro' ) ); ?>');">
                <?php _e( 'حذف المدعو', 'invitation-manager-pro' ); ?>
            </a>
        </div>

        <!-- تفاصيل المدعو -->
        <div class="impro-section">
            <div class="impro-section-header">
                <h2><?php _e( 'تفاصيل المدعو', 'invitation-manager-pro' ); ?></h2>
            </div>
            
            <div class="impro-section-content">
                <div class="impro-guest-details-grid">
                    <div class="impro-detail-item">
                        <div class="detail-label"><?php _e( 'الاسم الكامل', 'invitation-manager-pro' ); ?></div>
                        <div class="detail-value"><?php echo $guest_name; ?></div>
                    </div>
                    
                    <div class="impro-detail-item">
                        <div class="detail-label"><?php _e( 'البريد الإلكتروني', 'invitation-manager-pro' ); ?></div>
                        <div class="detail-value">
                            <?php if ( ! empty( $guest_email ) && $guest_email !== __( 'غير محدد', 'invitation-manager-pro' ) ) : ?>
                                <a href="mailto:<?php echo esc_attr( $guest_email ); ?>"><?php echo $guest_email; ?></a>
                            <?php else : ?>
                                <?php echo $guest_email; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="impro-detail-item">
                        <div class="detail-label"><?php _e( 'رقم الهاتف', 'invitation-manager-pro' ); ?></div>
                        <div class="detail-value">
                            <?php if ( ! empty( $guest_phone ) && $guest_phone !== __( 'غير محدد', 'invitation-manager-pro' ) ) : ?>
                                <a href="tel:<?php echo esc_attr( $guest_phone ); ?>"><?php echo $guest_phone; ?></a>
                            <?php else : ?>
                                <?php echo $guest_phone; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="impro-detail-item">
                        <div class="detail-label"><?php _e( 'الفئة', 'invitation-manager-pro' ); ?></div>
                        <div class="detail-value">
                            <span class="impro-category-badge category-<?php echo esc_attr( $guest_category ); ?>">
                                <?php echo esc_html( $category_label ); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="impro-detail-item">
                        <div class="detail-label"><?php _e( 'مسموح بمرافق؟', 'invitation-manager-pro' ); ?></div>
                        <div class="detail-value">
                            <?php if ( $plus_one_allowed ) : ?>
                                <span class="impro-status-badge status-accepted"><?php _e( 'نعم', 'invitation-manager-pro' ); ?></span>
                            <?php else : ?>
                                <span class="impro-status-badge status-declined"><?php _e( 'لا', 'invitation-manager-pro' ); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="impro-detail-item">
                        <div class="detail-label"><?php _e( 'الجنس', 'invitation-manager-pro' ); ?></div>
                        <div class="detail-value"><?php echo esc_html( $gender_label ); ?></div>
                    </div>
                    
                    <div class="impro-detail-item">
                        <div class="detail-label"><?php _e( 'الفئة العمرية', 'invitation-manager-pro' ); ?></div>
                        <div class="detail-value"><?php echo esc_html( $age_range_label ); ?></div>
                    </div>
                    
                    <div class="impro-detail-item">
                        <div class="detail-label"><?php _e( 'صلة القرابة/العلاقة', 'invitation-manager-pro' ); ?></div>
                        <div class="detail-value"><?php echo esc_html( $guest_relationship ); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- الدعوات المرتبطة -->
        <div class="impro-section">
            <div class="impro-section-header">
                <h2><?php _e( 'الدعوات المرتبطة', 'invitation-manager-pro' ); ?></h2>
                <div class="impro-section-actions">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-invitations&guest_id=' . $guest->id ) ); ?>" class="button">
                        <?php _e( 'عرض الكل', 'invitation-manager-pro' ); ?>
                    </a>
                </div>
            </div>
            
            <div class="impro-section-content">
                <?php if ( ! empty( $guest_invitations ) ) : ?>
                    <div class="impro-responsive-table">
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e( 'المناسبة', 'invitation-manager-pro' ); ?></th>
                                    <th><?php _e( 'الحالة', 'invitation-manager-pro' ); ?></th>
                                    <th><?php _e( 'تاريخ الإرسال', 'invitation-manager-pro' ); ?></th>
                                    <th><?php _e( 'تم الفتح', 'invitation-manager-pro' ); ?></th>
                                    <th><?php _e( 'الإجراءات', 'invitation-manager-pro' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $guest_invitations as $invitation ) : ?>
                                    <?php
                                    // تهيئة بيانات الدعوة بأمان
                                    $event = null;
                                    $invitation_url = '#';
                                    $event_name = __( 'مناسبة غير معروفة', 'invitation-manager-pro' );
                                    $sent_date = __( 'لم ترسل بعد', 'invitation-manager-pro' );
                                    $opened_status = __( 'لا', 'invitation-manager-pro' );
                                    
                                    if ( isset( $invitation->event_id ) ) {
                                        $event = $event_manager->get_event( $invitation->event_id );
                                        $event_name = $event ? esc_html( $event->name ) : $event_name;
                                    }
                                    
                                    if ( isset( $invitation->sent_at ) && $invitation->sent_at ) {
                                        $sent_date = esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $invitation->sent_at ) ) );
                                    }
                                    
                                    if ( isset( $invitation->is_opened ) && $invitation->is_opened ) {
                                        $opened_status = __( 'نعم', 'invitation-manager-pro' );
                                    }
                                    
                                    if ( isset( $invitation->token ) ) {
                                        $public = new IMPRO_Public();
                                        $invitation_url = $public->get_invitation_url( $invitation->token );
                                    }
                                    
                                    $status_text = isset( $invitation->status ) ? esc_html( $invitation->status ) : __( 'معلقة', 'invitation-manager-pro' );
                                    $status_class = 'status-pending';
                                    
                                    switch ( $status_text ) {
                                        case 'sent':
                                            $status_text = __( 'مرسلة', 'invitation-manager-pro' );
                                            $status_class = 'status-sent';
                                            break;
                                        case 'viewed':
                                            $status_text = __( 'مشاهد', 'invitation-manager-pro' );
                                            $status_class = 'status-viewed';
                                            break;
                                        case 'expired':
                                            $status_text = __( 'منتهية', 'invitation-manager-pro' );
                                            $status_class = 'status-expired';
                                            break;
                                        default:
                                            $status_text = __( 'معلقة', 'invitation-manager-pro' );
                                            $status_class = 'status-pending';
                                            break;
                                    }
                                    ?>
                                    <tr>
                                        <td><?php echo $event_name; ?></td>
                                        <td>
                                            <span class="impro-status-badge <?php echo esc_attr( $status_class ); ?>">
                                                <?php echo $status_text; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $sent_date; ?></td>
                                        <td><?php echo $opened_status; ?></td>
                                        <td>
                                            <div class="impro-action-buttons">
                                                <?php if ( $invitation_url !== '#' ) : ?>
                                                    <a href="<?php echo esc_url( $invitation_url ); ?>" target="_blank" class="button button-small">
                                                        <?php _e( 'عرض الدعوة', 'invitation-manager-pro' ); ?>
                                                    </a>
                                                <?php endif; ?>
                                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-invitations&action=send&invitation_id=' . ( isset( $invitation->id ) ? $invitation->id : 0 ) ) ); ?>" class="button button-small button-primary">
                                                    <?php _e( 'إعادة الإرسال', 'invitation-manager-pro' ); ?>
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
                        <span class="dashicons dashicons-email"></span>
                        <h3><?php _e( 'لا توجد دعوات', 'invitation-manager-pro' ); ?></h3>
                        <p><?php _e( 'لم يتم إرسال أي دعوات لهذا المدعو حتى الآن.', 'invitation-manager-pro' ); ?></p>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-invitations&action=send&guest_id=' . $guest->id ) ); ?>" class="button button-primary">
                            <?php _e( 'إرسال دعوة الآن', 'invitation-manager-pro' ); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ردود الحضور المرتبطة -->
        <div class="impro-section">
            <div class="impro-section-header">
                <h2><?php _e( 'ردود الحضور المرتبطة', 'invitation-manager-pro' ); ?></h2>
                <div class="impro-section-actions">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-rsvps&guest_id=' . $guest->id ) ); ?>" class="button">
                        <?php _e( 'عرض الكل', 'invitation-manager-pro' ); ?>
                    </a>
                </div>
            </div>
            
            <div class="impro-section-content">
                <?php if ( ! empty( $guest_rsvps ) ) : ?>
                    <div class="impro-responsive-table">
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e( 'المناسبة', 'invitation-manager-pro' ); ?></th>
                                    <th><?php _e( 'الحالة', 'invitation-manager-pro' ); ?></th>
                                    <th><?php _e( 'عدد المرافقين', 'invitation-manager-pro' ); ?></th>
                                    <th><?php _e( 'تاريخ الرد', 'invitation-manager-pro' ); ?></th>
                                    <th><?php _e( 'المتطلبات الغذائية', 'invitation-manager-pro' ); ?></th>
                                    <th><?php _e( 'الإجراءات', 'invitation-manager-pro' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $guest_rsvps as $rsvp ) : ?>
                                    <?php
                                    // تهيئة بيانات RSVP بأمان
                                    $event = null;
                                    $event_name = __( 'مناسبة غير معروفة', 'invitation-manager-pro' );
                                    $status_text = __( 'معلق', 'invitation-manager-pro' );
                                    $status_class = 'status-pending';
                                    $plus_one_count = '0';
                                    $response_date = __( 'غير محدد', 'invitation-manager-pro' );
                                    $dietary_requirements = __( 'لا توجد', 'invitation-manager-pro' );
                                    
                                    if ( isset( $rsvp->event_id ) ) {
                                        $event = $event_manager->get_event( $rsvp->event_id );
                                        $event_name = $event ? esc_html( $event->name ) : $event_name;
                                    }
                                    
                                    if ( isset( $rsvp->status ) ) {
                                        switch ( $rsvp->status ) {
                                            case 'accepted':
                                                $status_text = __( 'موافق', 'invitation-manager-pro' );
                                                $status_class = 'status-accepted';
                                                break;
                                            case 'declined':
                                                $status_text = __( 'معتذر', 'invitation-manager-pro' );
                                                $status_class = 'status-declined';
                                                break;
                                            default:
                                                $status_text = __( 'معلق', 'invitation-manager-pro' );
                                                $status_class = 'status-pending';
                                                break;
                                        }
                                    }
                                    
                                    if ( isset( $rsvp->plus_one_attending ) ) {
                                        $plus_one_count = esc_html( $rsvp->plus_one_attending );
                                    }
                                    
                                    if ( isset( $rsvp->response_date ) && $rsvp->response_date ) {
                                        $response_date = esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $rsvp->response_date ) ) );
                                    }
                                    
                                    if ( isset( $rsvp->dietary_requirements ) && ! empty( $rsvp->dietary_requirements ) ) {
                                        $dietary_requirements = esc_html( $rsvp->dietary_requirements );
                                    }
                                    ?>
                                    <tr>
                                        <td><?php echo $event_name; ?></td>
                                        <td>
                                            <span class="impro-status-badge <?php echo esc_attr( $status_class ); ?>">
                                                <?php echo $status_text; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $plus_one_count; ?></td>
                                        <td><?php echo $response_date; ?></td>
                                        <td><?php echo $dietary_requirements; ?></td>
                                        <td>
                                            <div class="impro-action-buttons">
                                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-rsvps&action=edit&rsvp_id=' . ( isset( $rsvp->id ) ? $rsvp->id : 0 ) ) ); ?>" class="button button-small">
                                                    <?php _e( 'تعديل', 'invitation-manager-pro' ); ?>
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
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <h3><?php _e( 'لا توجد ردود حضور', 'invitation-manager-pro' ); ?></h3>
                        <p><?php _e( 'لم يقم هذا المدعو بالرد على أي دعوات حتى الآن.', 'invitation-manager-pro' ); ?></p>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-rsvps&action=add&guest_id=' . $guest->id ) ); ?>" class="button button-primary">
                            <?php _e( 'إضافة رد يدوي', 'invitation-manager-pro' ); ?>
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

.impro-guest-dashboard {
    margin-top: 20px;
}

/* بطاقات الإحصائيات */
.impro-stats-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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

/* تفاصيل المدعو */
.impro-guest-details-grid {
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

/* شارات الفئات والحالة */
.impro-category-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
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
    background: #f0f0f1;
    color: #50575e;
    border: 1px solid #dcdcde;
}

/* جداول البيانات */
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

/* زر الحذف */
.button-link-delete {
    color: #d63638;
    text-decoration: none;
    border: 1px solid #d63638;
    background: transparent;
}

.button-link-delete:hover {
    background: #d63638;
    color: #fff;
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
    
    .impro-guest-details-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .impro-stats-cards {
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
    
    // إغلاق الإشعارات تلقائياً
    setTimeout(function() {
        $('.notice.is-dismissible').fadeOut(500, function() {
            $(this).remove();
        });
    }, 5000);
    
    // تأكيد الحذف
    $('.button-link-delete').on('click', function(e) {
        if (!confirm('<?php esc_js_e( 'هل أنت متأكد من حذف هذا المدعو؟', 'invitation-manager-pro' ); ?>')) {
            e.preventDefault();
            return false;
        }
    });
});
</script>
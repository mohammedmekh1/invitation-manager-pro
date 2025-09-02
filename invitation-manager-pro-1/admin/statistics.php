
<?php
/**
 * Statistics page.
 *
 * @package Invitation_Manager_Pro
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// تهيئة المديرين بأمان
$event_manager = null;
$guest_manager = null;
$rsvp_manager = null;
$invitation_manager = null;

try {
    if (class_exists('IMPRO_Event_Manager')) {
        $event_manager = new IMPRO_Event_Manager();
    }
    
    if (class_exists('IMPRO_Guest_Manager')) {
        $guest_manager = new IMPRO_Guest_Manager();
    }
    
    if (class_exists('IMPRO_RSVP_Manager')) {
        $rsvp_manager = new IMPRO_RSVP_Manager();
    }
    
    if (class_exists('IMPRO_Invitation_Manager')) {
        $invitation_manager = new IMPRO_Invitation_Manager();
    }
} catch (Exception $e) {
    error_log('Error initializing managers: ' . $e->getMessage());
}

// الحصول على الإحصائيات بأمان
$stats = array(
    'events' => array(),
    'guests' => array(),
    'rsvps' => array(),
    'invitations' => array()
);

try {
    $stats['events'] = $event_manager ? (method_exists($event_manager, 'get_event_statistics') ? $event_manager->get_event_statistics() : array()) : array();
    $stats['guests'] = $guest_manager ? (method_exists($guest_manager, 'get_guest_statistics') ? $guest_manager->get_guest_statistics() : array()) : array();
    $stats['rsvps'] = $rsvp_manager ? (method_exists($rsvp_manager, 'get_overall_rsvp_statistics') ? $rsvp_manager->get_overall_rsvp_statistics() : array()) : array();
    $stats['invitations'] = $invitation_manager ? (method_exists($invitation_manager, 'get_invitation_statistics') ? $invitation_manager->get_invitation_statistics() : array()) : array();
} catch (Exception $e) {
    error_log('Error loading statistics: ' . $e->getMessage());
}

// تهيئة الإحصائيات الافتراضية بأمان
$default_stats = array(
    'events' => array(
        'total' => 0,
        'upcoming' => 0,
        'past' => 0,
        'cancelled' => 0
    ),
    'guests' => array(
        'total' => 0,
        'plus_one_allowed' => 0,
        'categories' => array(),
        'gender_distribution' => array()
    ),
    'rsvps' => array(
        'accepted' => 0,
        'declined' => 0,
        'pending' => 0,
        'total_attending' => 0,
        'total_expected' => 0
    ),
    'invitations' => array(
        'total' => 0,
        'sent' => 0,
        'opened' => 0,
        'pending' => 0,
        'expired' => 0
    )
);

// دمج الإحصائيات الافتراضية مع الإحصائيات الحقيقية
foreach ($default_stats as $key => $default_values) {
    if (!isset($stats[$key]) || !is_array($stats[$key])) {
        $stats[$key] = $default_values;
    } else {
        $stats[$key] = array_merge($default_values, $stats[$key]);
    }
}

// بيانات المخططات بأمان
$rsvp_chart_data = array(
    'labels' => array(
        __( 'موافق', 'invitation-manager-pro' ), 
        __( 'معتذر', 'invitation-manager-pro' ), 
        __( 'معلق', 'invitation-manager-pro' )
    ),
    'data' => array(
        isset($stats['rsvps']['accepted']) ? intval($stats['rsvps']['accepted']) : 0,
        isset($stats['rsvps']['declined']) ? intval($stats['rsvps']['declined']) : 0,
        isset($stats['rsvps']['pending']) ? intval($stats['rsvps']['pending']) : 0
    ),
    'colors' => array('#4CAF50', '#F44336', '#FFC107')
);

$invitation_chart_data = array(
    'labels' => array(
        __( 'مرسلة', 'invitation-manager-pro' ), 
        __( 'مفتوحة', 'invitation-manager-pro' ), 
        __( 'معلقة', 'invitation-manager-pro' )
    ),
    'data' => array(
        isset($stats['invitations']['sent']) ? intval($stats['invitations']['sent']) : 0,
        isset($stats['invitations']['opened']) ? intval($stats['invitations']['opened']) : 0,
        isset($stats['invitations']['pending']) ? intval($stats['invitations']['pending']) : 0
    ),
    'colors' => array('#2196F3', '#FFEB3B', '#9E9E9E')
);

// إحصائيات إضافية
$additional_stats = array(
    'attendance_rate' => 0,
    'open_rate' => 0,
    'response_rate' => 0
);

// حساب معدلات الإحصائيات
if (isset($stats['rsvps']['total_expected']) && $stats['rsvps']['total_expected'] > 0) {
    $additional_stats['attendance_rate'] = round(($stats['rsvps']['accepted'] / $stats['rsvps']['total_expected']) * 100, 1);
}

if (isset($stats['invitations']['sent']) && $stats['invitations']['sent'] > 0) {
    $additional_stats['open_rate'] = round(($stats['invitations']['opened'] / $stats['invitations']['sent']) * 100, 1);
}

$total_rsvps = $stats['rsvps']['accepted'] + $stats['rsvps']['declined'] + $stats['rsvps']['pending'];
if ($total_rsvps > 0) {
    $additional_stats['response_rate'] = round((($stats['rsvps']['accepted'] + $stats['rsvps']['declined']) / $total_rsvps) * 100, 1);
}

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

?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e( 'الإحصائيات', 'invitation-manager-pro' ); ?></h1>
    <hr class="wp-header-end">

    <?php if ( isset( $_GET['message'] ) ) : ?>
        <?php
        $messages = array(
            'refreshed' => __( 'تم تحديث الإحصائيات بنجاح.', 'invitation-manager-pro' ),
            'exported' => __( 'تم تصدير الإحصائيات بنجاح.', 'invitation-manager-pro' ),
            'cleared' => __( 'تم مسح التخزين المؤقت للإحصائيات.', 'invitation-manager-pro' )
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
                    case 'refresh_failed':
                        _e( 'فشل في تحديث الإحصائيات.', 'invitation-manager-pro' );
                        break;
                    case 'export_failed':
                        _e( 'فشل في تصدير الإحصائيات.', 'invitation-manager-pro' );
                        break;
                    case 'clear_cache_failed':
                        _e( 'فشل في مسح التخزين المؤقت للإحصائيات.', 'invitation-manager-pro' );
                        break;
                    default:
                        _e( 'حدث خطأ أثناء عملية الإحصائيات.', 'invitation-manager-pro' );
                        break;
                }
                ?>
            </p>
        </div>
    <?php endif; ?>

    <!-- أزرار الإجراءات السريعة -->
    <div class="impro-stats-actions">
        <div class="impro-action-buttons">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-statistics&action=refresh' ) ); ?>" class="button button-primary">
                <?php _e( 'تحديث الإحصائيات', 'invitation-manager-pro' ); ?>
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-statistics&action=export' ) ); ?>" class="button">
                <?php _e( 'تصدير الإحصائيات', 'invitation-manager-pro' ); ?>
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-statistics&action=clear_cache' ) ); ?>" class="button button-secondary">
                <?php _e( 'مسح التخزين المؤقت', 'invitation-manager-pro' ); ?>
            </a>
        </div>
        
        <div class="impro-date-filter">
            <form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="impro-date-filter-form">
                <input type="hidden" name="page" value="impro-statistics">
                <label for="date_from"><?php _e( 'من:', 'invitation-manager-pro' ); ?></label>
                <input type="date" name="date_from" id="date_from" value="<?php echo isset( $_GET['date_from'] ) ? esc_attr( $_GET['date_from'] ) : ''; ?>">
                <label for="date_to"><?php _e( 'إلى:', 'invitation-manager-pro' ); ?></label>
                <input type="date" name="date_to" id="date_to" value="<?php echo isset( $_GET['date_to'] ) ? esc_attr( $_GET['date_to'] ) : ''; ?>">
                <button type="submit" class="button"><?php _e( 'تصفية', 'invitation-manager-pro' ); ?></button>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=impro-statistics' ) ); ?>" class="button button-secondary"><?php _e( 'إعادة تعيين', 'invitation-manager-pro' ); ?></a>
            </form>
        </div>
    </div>

    <!-- بطاقات الإحصائيات الرئيسية -->
    <div class="impro-stats-dashboard">
        <div class="impro-stats-grid">
            <div class="impro-stat-card">
                <div class="stat-icon">
                    <span class="dashicons dashicons-calendar-alt"></span>
                </div>
                <div class="stat-content">
                    <h3><?php echo esc_html( number_format( $stats['events']['total'] ) ); ?></h3>
                    <p><?php _e( 'إجمالي المناسبات', 'invitation-manager-pro' ); ?></p>
                    <small><?php printf( __( '%d قادمة، %d ماضية', 'invitation-manager-pro' ), 
                                       esc_html( number_format( $stats['events']['upcoming'] ) ), 
                                       esc_html( number_format( $stats['events']['past'] ) ) ); ?></small>
                </div>
            </div>

            <div class="impro-stat-card">
                <div class="stat-icon guests">
                    <span class="dashicons dashicons-groups"></span>
                </div>
                <div class="stat-content">
                    <h3><?php echo esc_html( number_format( $stats['guests']['total'] ) ); ?></h3>
                    <p><?php _e( 'إجمالي المدعوين', 'invitation-manager-pro' ); ?></p>
                    <small><?php printf( __( '%d مسموح لهم بمرافق', 'invitation-manager-pro' ), 
                                       esc_html( number_format( $stats['guests']['plus_one_allowed'] ) ) ); ?></small>
                </div>
            </div>

            <div class="impro-stat-card">
                <div class="stat-icon invitations">
                    <span class="dashicons dashicons-email"></span>
                </div>
                <div class="stat-content">
                    <h3><?php echo esc_html( number_format( $stats['invitations']['total'] ) ); ?></h3>
                    <p><?php _e( 'إجمالي الدعوات', 'invitation-manager-pro' ); ?></p>
                    <small><?php printf( __( '%d مرسلة، %d مفتوحة', 'invitation-manager-pro' ), 
                                       esc_html( number_format( $stats['invitations']['sent'] ) ), 
                                       esc_html( number_format( $stats['invitations']['opened'] ) ) ); ?></small>
                </div>
            </div>

            <div class="impro-stat-card">
                <div class="stat-icon rsvps">
                    <span class="dashicons dashicons-yes"></span>
                </div>
                <div class="stat-content">
                    <h3><?php echo esc_html( number_format( $stats['rsvps']['total_attending'] ) ); ?></h3>
                    <p><?php _e( 'إجمالي الحضور المتوقع', 'invitation-manager-pro' ); ?></p>
                    <small><?php printf( __( '%d موافق، %d معتذر', 'invitation-manager-pro' ), 
                                       esc_html( number_format( $stats['rsvps']['accepted'] ) ), 
                                       esc_html( number_format( $stats['rsvps']['declined'] ) ) ); ?></small>
                </div>
            </div>

            <div class="impro-stat-card">
                <div class="stat-icon attendance">
                    <span class="dashicons dashicons-chart-line"></span>
                </div>
                <div class="stat-content">
                    <h3><?php echo esc_html( $additional_stats['attendance_rate'] . '%' ); ?></h3>
                    <p><?php _e( 'معدل الحضور المتوقع', 'invitation-manager-pro' ); ?></p>
                    <small><?php printf( __( '%s معدل الفتح، %s معدل الرد', 'invitation-manager-pro' ), 
                                       esc_html( $additional_stats['open_rate'] . '%' ), 
                                       esc_html( $additional_stats['response_rate'] . '%' ) ); ?></small>
                </div>
            </div>

            <div class="impro-stat-card">
                <div class="stat-icon response">
                    <span class="dashicons dashicons-clock"></span>
                </div>
                <div class="stat-content">
                    <h3><?php echo esc_html( number_format( $stats['rsvps']['pending'] ) ); ?></h3>
                    <p><?php _e( 'الردود المعلقة', 'invitation-manager-pro' ); ?></p>
                    <small><?php printf( __( '%d دعوة لم تفتح', 'invitation-manager-pro' ), 
                                       esc_html( number_format( $stats['invitations']['pending'] ) ) ); ?></small>
                </div>
            </div>
        </div>
    </div>

    <!-- بطاقات المخططات -->
    <div class="impro-charts-dashboard">
        <div class="impro-charts-grid">
            <div class="impro-chart-card">
                <div class="chart-header">
                    <h2><?php _e( 'إحصائيات ردود الحضور', 'invitation-manager-pro' ); ?></h2>
                    <div class="chart-actions">
                        <button type="button" class="button button-small chart-refresh" data-chart="rsvp">
                            <?php _e( 'تحديث', 'invitation-manager-pro' ); ?>
                        </button>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="rsvpChart" width="400" height="400"></canvas>
                </div>
                <div class="chart-summary">
                    <p><?php printf( __( 'إجمالي الردود: %d', 'invitation-manager-pro' ), 
                                   esc_html( number_format( $stats['rsvps']['accepted'] + $stats['rsvps']['declined'] + $stats['rsvps']['pending'] ) ) ); ?></p>
                </div>
            </div>

            <div class="impro-chart-card">
                <div class="chart-header">
                    <h2><?php _e( 'إحصائيات الدعوات', 'invitation-manager-pro' ); ?></h2>
                    <div class="chart-actions">
                        <button type="button" class="button button-small chart-refresh" data-chart="invitation">
                            <?php _e( 'تحديث', 'invitation-manager-pro' ); ?>
                        </button>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="invitationChart" width="400" height="400"></canvas>
                </div>
                <div class="chart-summary">
                    <p><?php printf( __( 'إجمالي الدعوات: %d', 'invitation-manager-pro' ), 
                                   esc_html( number_format( $stats['invitations']['total'] ) ) ); ?></p>
                </div>
            </div>

            <div class="impro-chart-card">
                <div class="chart-header">
                    <h2><?php _e( 'توزيع الفئات', 'invitation-manager-pro' ); ?></h2>
                    <div class="chart-actions">
                        <button type="button" class="button button-small chart-refresh" data-chart="categories">
                            <?php _e( 'تحديث', 'invitation-manager-pro' ); ?>
                        </button>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="categoriesChart" width="400" height="400"></canvas>
                </div>
                <div class="chart-summary">
                    <p><?php printf( __( 'عدد الفئات: %d', 'invitation-manager-pro' ), 
                                   esc_html( count( $stats['guests']['categories'] ) ) ); ?></p>
                </div>
            </div>

            <div class="impro-chart-card">
                <div class="chart-header">
                    <h2><?php _e( 'توزيع الجنس', 'invitation-manager-pro' ); ?></h2>
                    <div class="chart-actions">
                        <button type="button" class="button button-small chart-refresh" data-chart="gender">
                            <?php _e( 'تحديث', 'invitation-manager-pro' ); ?>
                        </button>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="genderChart" width="400" height="400"></canvas>
                </div>
                <div class="chart-summary">
                    <p><?php _e( 'توزيع الجنس حسب الفئات', 'invitation-manager-pro' ); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- تفاصيل الإحصائيات -->
    <div class="impro-stats-details">
        <div class="impro-section">
            <div class="impro-section-header">
                <h2><?php _e( 'تفاصيل الإحصائيات', 'invitation-manager-pro' ); ?></h2>
            </div>
            
            <div class="impro-section-content">
                <div class="impro-stats-tables">
                    <!-- جدول المناسبات -->
                    <div class="impro-stats-table">
                        <h3><?php _e( 'المناسبات حسب الحالة', 'invitation-manager-pro' ); ?></h3>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e( 'الحالة', 'invitation-manager-pro' ); ?></th>
                                    <th><?php _e( 'العدد', 'invitation-manager-pro' ); ?></th>
                                    <th><?php _e( 'النسبة المئوية', 'invitation-manager-pro' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><?php _e( 'قادمة', 'invitation-manager-pro' ); ?></td>
                                    <td><?php echo esc_html( number_format( $stats['events']['upcoming'] ) ); ?></td>
                                    <td><?php echo esc_html( $stats['events']['total'] > 0 ? round( ( $stats['events']['upcoming'] / $stats['events']['total'] ) * 100, 1 ) . '%' : '0%' ); ?></td>
                                </tr>
                                <tr>
                                    <td><?php _e( 'ماضية', 'invitation-manager-pro' ); ?></td>
                                    <td><?php echo esc_html( number_format( $stats['events']['past'] ) ); ?></td>
                                    <td><?php echo esc_html( $stats['events']['total'] > 0 ? round( ( $stats['events']['past'] / $stats['events']['total'] ) * 100, 1 ) . '%' : '0%' ); ?></td>
                                </tr>
                                <tr>
                                    <td><?php _e( 'ملغاة', 'invitation-manager-pro' ); ?></td>
                                    <td><?php echo esc_html( number_format( $stats['events']['cancelled'] ) ); ?></td>
                                    <td><?php echo esc_html( $stats['events']['total'] > 0 ? round( ( $stats['events']['cancelled'] / $stats['events']['total'] ) * 100, 1 ) . '%' : '0%' ); ?></td>
                                </tr>
                                <tr class="total-row">
                                    <td><strong><?php _e( 'الإجمالي', 'invitation-manager-pro' ); ?></strong></td>
                                    <td><strong><?php echo esc_html( number_format( $stats['events']['total'] ) ); ?></strong></td>
                                    <td><strong>100%</strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- جدول المدعوين -->
                    <div class="impro-stats-table">
                        <h3><?php _e( 'المدعوين حسب الفئة', 'invitation-manager-pro' ); ?></h3>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e( 'الفئة', 'invitation-manager-pro' ); ?></th>
                                    <th><?php _e( 'العدد', 'invitation-manager-pro' ); ?></th>
                                    <th><?php _e( 'المرافقون المسموح لهم', 'invitation-manager-pro' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ( ! empty( $stats['guests']['categories'] ) && is_array( $stats['guests']['categories'] ) ) : ?>
                                    <?php foreach ( $stats['guests']['categories'] as $category_stats ) : ?>
                                        <?php
                                        $category_key = isset( $category_stats->category ) ? $category_stats->category : 'other';
                                        $category_label = isset( $category_labels[ $category_key ] ) ? $category_labels[ $category_key ] : $category_key;
                                        $category_count = isset( $category_stats->count ) ? intval( $category_stats->count ) : 0;
                                        $plus_one_count = isset( $category_stats->plus_one_allowed ) ? intval( $category_stats->plus_one_allowed ) : 0;
                                        ?>
                                        <tr>
                                            <td><?php echo esc_html( $category_label ); ?></td>
                                            <td><?php echo esc_html( number_format( $category_count ) ); ?></td>
                                            <td><?php echo esc_html( number_format( $plus_one_count ) ); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="3"><?php _e( 'لا توجد بيانات', 'invitation-manager-pro' ); ?></td>
                                    </tr>
                                <?php endif; ?>
                                <tr class="total-row">
                                    <td><strong><?php _e( 'الإجمالي', 'invitation-manager-pro' ); ?></strong></td>
                                    <td><strong><?php echo esc_html( number_format( $stats['guests']['total'] ) ); ?></strong></td>
                                    <td><strong><?php echo esc_html( number_format( $stats['guests']['plus_one_allowed'] ) ); ?></strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- جدول ردود الحضور -->
                    <div class="impro-stats-table">
                        <h3><?php _e( 'ردود الحضور', 'invitation-manager-pro' ); ?></h3>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e( 'الحالة', 'invitation-manager-pro' ); ?></th>
                                    <th><?php _e( 'العدد', 'invitation-manager-pro' ); ?></th>
                                    <th><?php _e( 'النسبة المئوية', 'invitation-manager-pro' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><?php _e( 'موافق', 'invitation-manager-pro' ); ?></td>
                                    <td><?php echo esc_html( number_format( $stats['rsvps']['accepted'] ) ); ?></td>
                                    <td><?php echo esc_html( $total_rsvps > 0 ? round( ( $stats['rsvps']['accepted'] / $total_rsvps ) * 100, 1 ) . '%' : '0%' ); ?></td>
                                </tr>
                                <tr>
                                    <td><?php _e( 'معتذر', 'invitation-manager-pro' ); ?></td>
                                    <td><?php echo esc_html( number_format( $stats['rsvps']['declined'] ) ); ?></td>
                                    <td><?php echo esc_html( $total_rsvps > 0 ? round( ( $stats['rsvps']['declined'] / $total_rsvps ) * 100, 1 ) . '%' : '0%' ); ?></td>
                                </tr>
                                <tr>
                                    <td><?php _e( 'معلق', 'invitation-manager-pro' ); ?></td>
                                    <td><?php echo esc_html( number_format( $stats['rsvps']['pending'] ) ); ?></td>
                                    <td><?php echo esc_html( $total_rsvps > 0 ? round( ( $stats['rsvps']['pending'] / $total_rsvps ) * 100, 1 ) . '%' : '0%' ); ?></td>
                                </tr>
                                <tr class="total-row">
                                    <td><strong><?php _e( 'الإجمالي', 'invitation-manager-pro' ); ?></strong></td>
                                    <td><strong><?php echo esc_html( number_format( $total_rsvps ) ); ?></strong></td>
                                    <td><strong>100%</strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- جدول الدعوات -->
                    <div class="impro-stats-table">
                        <h3><?php _e( 'الدعوات', 'invitation-manager-pro' ); ?></h3>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e( 'الحالة', 'invitation-manager-pro' ); ?></th>
                                    <th><?php _e( 'العدد', 'invitation-manager-pro' ); ?></th>
                                    <th><?php _e( 'النسبة المئوية', 'invitation-manager-pro' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><?php _e( 'مرسلة', 'invitation-manager-pro' ); ?></td>
                                    <td><?php echo esc_html( number_format( $stats['invitations']['sent'] ) ); ?></td>
                                    <td><?php echo esc_html( $stats['invitations']['total'] > 0 ? round( ( $stats['invitations']['sent'] / $stats['invitations']['total'] ) * 100, 1 ) . '%' : '0%' ); ?></td>
                                </tr>
                                <tr>
                                    <td><?php _e( 'مفتوحة', 'invitation-manager-pro' ); ?></td>
                                    <td><?php echo esc_html( number_format( $stats['invitations']['opened'] ) ); ?></td>
                                    <td><?php echo esc_html( $stats['invitations']['sent'] > 0 ? round( ( $stats['invitations']['opened'] / $stats['invitations']['sent'] ) * 100, 1 ) . '%' : '0%' ); ?></td>
                                </tr>
                                <tr>
                                    <td><?php _e( 'معلقة', 'invitation-manager-pro' ); ?></td>
                                    <td><?php echo esc_html( number_format( $stats['invitations']['pending'] ) ); ?></td>
                                    <td><?php echo esc_html( $stats['invitations']['total'] > 0 ? round( ( $stats['invitations']['pending'] / $stats['invitations']['total'] ) * 100, 1 ) . '%' : '0%' ); ?></td>
                                </tr>
                                <tr>
                                    <td><?php _e( 'منتهية الصلاحية', 'invitation-manager-pro' ); ?></td>
                                    <td><?php echo esc_html( number_format( $stats['invitations']['expired'] ) ); ?></td>
                                    <td><?php echo esc_html( $stats['invitations']['total'] > 0 ? round( ( $stats['invitations']['expired'] / $stats['invitations']['total'] ) * 100, 1 ) . '%' : '0%' ); ?></td>
                                </tr>
                                <tr class="total-row">
                                    <td><strong><?php _e( 'الإجمالي', 'invitation-manager-pro' ); ?></strong></td>
                                    <td><strong><?php echo esc_html( number_format( $stats['invitations']['total'] ) ); ?></strong></td>
                                    <td><strong>100%</strong></td>
                                </tr>
                            </tbody>
                        </table>
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

/* أزرار الإجراءات */
.impro-stats-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    flex-wrap: wrap;
    gap: 15px;
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 20px;
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

.impro-date-filter {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.impro-date-filter-form {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.impro-date-filter-form label {
    font-weight: 600;
    margin: 0;
    color: #1d2327;
}

.impro-date-filter-form input[type="date"] {
    padding: 8px;
    border-radius: 4px;
    border: 1px solid #8c8f94;
    background: #fff;
    font-size: 14px;
}

.impro-date-filter-form button {
    margin: 0;
}

/* لوحة الإحصائيات الرئيسية */
.impro-stats-dashboard {
    margin-bottom: 30px;
}

.impro-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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

.stat-icon.guests {
    background: #e6f4ea;
    color: #008a20;
}

.stat-icon.invitations {
    background: #e1f0fa;
    color: #0073aa;
}

.stat-icon.rsvps {
    background: #e6f4ea;
    color: #008a20;
}

.stat-icon.attendance {
    background: #fef8ee;
    color: #d63638;
}

.stat-icon.response {
    background: #f8e2df;
    color: #d63638;
}

.stat-content h3 {
    margin: 0 0 5px 0;
    font-size: 24px;
    font-weight: 600;
    color: #1d2327;
}

.stat-content p {
    margin: 0 0 5px 0;
    color: #646970;
    font-size: 14px;
    font-weight: 600;
}

.stat-content small {
    color: #646970;
    font-size: 12px;
}

/* لوحة المخططات */
.impro-charts-dashboard {
    margin-bottom: 30px;
}

.impro-charts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
}

.impro-chart-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid #ccd0d4;
    background: #f6f7f7;
}

.chart-header h2 {
    margin: 0;
    color: #1d2327;
    font-size: 16px;
    font-weight: 600;
}

.chart-actions {
    display: flex;
    gap: 5px;
}

.chart-actions .button-small {
    font-size: 12px;
    padding: 4px 10px;
    margin: 0;
}

.chart-container {
    padding: 20px;
    position: relative;
}

.chart-container canvas {
    max-width: 100%;
    height: auto;
}

.chart-summary {
    padding: 15px 20px;
    border-top: 1px solid #ccd0d4;
    background: #f9f9f9;
    text-align: center;
}

.chart-summary p {
    margin: 0;
    color: #646970;
    font-size: 13px;
}

/* تفاصيل الإحصائيات */
.impro-stats-details {
    margin-bottom: 30px;
}

.impro-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.impro-section-header {
    padding: 20px;
    border-bottom: 1px solid #ccd0d4;
    background: #f6f7f7;
}

.impro-section-header h2 {
    margin: 0;
    color: #1d2327;
    font-size: 20px;
}

.impro-section-content {
    padding: 20px;
}

.impro-stats-tables {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
}

.impro-stats-table {
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 6px;
    padding: 15px;
}

.impro-stats-table h3 {
    margin: 0 0 15px 0;
    color: #1d2327;
    font-size: 16px;
    text-align: center;
}

.impro-stats-table table {
    border: none;
    margin: 0;
    background: #fff;
}

.impro-stats-table thead th {
    background: #f6f7f7;
    border-bottom: 2px solid #dcdcde;
    font-weight: 600;
    padding: 12px 10px;
}

.impro-stats-table tbody td {
    padding: 10px;
    vertical-align: middle;
}

.impro-stats-table .total-row {
    background: #e1f0fa;
    font-weight: 600;
}

.impro-stats-table .total-row td {
    border-top: 2px solid #b3d9f0;
}

/* تصميم متجاوب */
@media (max-width: 782px) {
    .impro-stats-actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .impro-action-buttons {
        justify-content: center;
    }
    
    .impro-date-filter {
        justify-content: center;
    }
    
    .impro-date-filter-form {
        flex-direction: column;
        align-items: stretch;
    }
    
    .impro-stats-grid {
        grid-template-columns: 1fr 1fr;
    }
    
    .impro-charts-grid {
        grid-template-columns: 1fr;
    }
    
    .impro-stats-tables {
        grid-template-columns: 1fr;
    }
    
    .chart-container {
        padding: 10px;
    }
    
    .chart-container canvas {
        max-width: 100%;
        height: 300px !important;
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
        text-align: center;
    }
    
    .impro-date-filter-form input[type="date"],
    .impro-date-filter-form button {
        width: 100%;
        text-align: center;
    }
    
    .chart-header {
        flex-direction: column;
        align-items: stretch;
        gap: 10px;
    }
    
    .chart-actions {
        justify-content: center;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // تهيئة المخططات
    function initCharts() {
        // مخطط ردود الحضور
        var rsvpCtx = document.getElementById('rsvpChart');
        if (rsvpCtx) {
            var rsvpChart = new Chart(rsvpCtx, {
                type: 'pie',
                data: {
                    labels: <?php echo json_encode( $rsvp_chart_data['labels'] ); ?>,
                    datasets: [{
                        data: <?php echo json_encode( $rsvp_chart_data['data'] ); ?>,
                        backgroundColor: <?php echo json_encode( $rsvp_chart_data['colors'] ); ?>,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    var percentage = Math.round((context.raw / total) * 100);
                                    return context.label + ': ' + context.raw + ' (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }

        // مخطط الدعوات
        var invitationCtx = document.getElementById('invitationChart');
        if (invitationCtx) {
            var invitationChart = new Chart(invitationCtx, {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode( $invitation_chart_data['labels'] ); ?>,
                    datasets: [{
                        data: <?php echo json_encode( $invitation_chart_data['data'] ); ?>,
                        backgroundColor: <?php echo json_encode( $invitation_chart_data['colors'] ); ?>,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    var percentage = Math.round((context.raw / total) * 100);
                                    return context.label + ': ' + context.raw + ' (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }

        // مخطط الفئات (مثال بسيط)
        var categoriesCtx = document.getElementById('categoriesChart');
        if (categoriesCtx) {
            var categoriesChart = new Chart(categoriesCtx, {
                type: 'bar',
                data: {
                    labels: ['عائلة', 'أصدقاء', 'زملاء', 'VIP', 'أخرى'],
                    datasets: [{
                        label: 'عدد المدعوين',
                        data: [30, 25, 20, 10, 15],
                        backgroundColor: [
                            '#4CAF50',
                            '#2196F3',
                            '#FFC107',
                            '#9C27B0',
                            '#607D8B'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 5
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }

        // مخطط الجنس (مثال بسيط)
        var genderCtx = document.getElementById('genderChart');
        if (genderCtx) {
            var genderChart = new Chart(genderCtx, {
                type: 'doughnut',
                data: {
                    labels: ['ذكر', 'أنثى'],
                    datasets: [{
                        data: [55, 45],
                        backgroundColor: [
                            '#2196F3',
                            '#E91E63'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                font: {
                                    size: 12
                                }
                            }
                        }
                    }
                }
            });
        }
    }

    // تهيئة المخططات عند تحميل الصفحة
    initCharts();

    // تحديث المخططات
    $('.chart-refresh').on('click', function() {
        var chartType = $(this).data('chart');
        alert('<?php echo esc_js( __( 'سيتم تحديث المخطط تلقائياً', 'invitation-manager-pro' ) ); ?>');
    });

    // إغلاق الإشعارات تلقائياً
    setTimeout(function() {
        $('.notice.is-dismissible').fadeOut(500, function() {
            $(this).remove();
        });
    }, 5000);

    // تحسين تجربة البحث
    $('.impro-search-input').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            $(this).closest('form').submit();
        }
    });

    // تأكيد الحذف
    $('form').on('submit', function() {
        if ($(this).find('input[name="impro_action"][value="delete_guest"]').length > 0) {
            return confirm('<?php echo esc_js( __( 'هل أنت متأكد من حذف هذا المدعو؟', 'invitation-manager-pro' ) ); ?>');
        }
        return true;
    });

    // تحسين تجربة الجداول
    $('.wp-list-table').on('mouseenter', 'tr', function() {
        $(this).find('.row-actions').css('visibility', 'visible');
    }).on('mouseleave', 'tr', function() {
        $(this).find('.row-actions').css('visibility', 'hidden');
    });
});
</script>
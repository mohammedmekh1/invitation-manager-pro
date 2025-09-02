<?php
/**
 * Admin dashboard page.
 *
 * @package Invitation_Manager_Pro
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}
?>

<div class="wrap">
    <h1><?php _e( 'لوحة تحكم إدارة الدعوات', 'invitation-manager-pro' ); ?></h1>

    <div class="impro-dashboard">
        <!-- Statistics Cards -->
        <div class="impro-stats-grid">
            <div class="impro-stat-card">
                <div class="impro-stat-icon">🎉</div>
                <div class="impro-stat-content">
                    <h3><?php echo esc_html( $stats['events']['total'] ); ?></h3>
                    <p><?php _e( 'إجمالي المناسبات', 'invitation-manager-pro' ); ?></p>
                    <small><?php printf( __( '%d قادمة، %d ماضية', 'invitation-manager-pro' ), $stats['events']['upcoming'], $stats['events']['past'] ); ?></small>
                </div>
            </div>

            <div class="impro-stat-card">
                <div class="impro-stat-icon">👥</div>
                <div class="impro-stat-content">
                    <h3><?php echo esc_html( $stats['guests']['total'] ); ?></h3>
                    <p><?php _e( 'إجمالي المدعوين', 'invitation-manager-pro' ); ?></p>
                    <small><?php printf( __( '%d مسموح لهم بمرافق', 'invitation-manager-pro' ), $stats['guests']['plus_one_allowed'] ); ?></small>
                </div>
            </div>

            <div class="impro-stat-card">
                <div class="impro-stat-icon">📧</div>
                <div class="impro-stat-content">
                    <h3><?php echo esc_html( $stats['invitations']['total'] ); ?></h3>
                    <p><?php _e( 'إجمالي الدعوات', 'invitation-manager-pro' ); ?></p>
                    <small><?php printf( __( '%d مرسلة، %d مفتوحة', 'invitation-manager-pro' ), $stats['invitations']['sent'], $stats['invitations']['opened'] ); ?></small>
                </div>
            </div>

            <div class="impro-stat-card">
                <div class="impro-stat-icon">✅</div>
                <div class="impro-stat-content">
                    <h3><?php echo esc_html( $stats['rsvps']['total_attending'] ); ?></h3>
                    <p><?php _e( 'إجمالي الحضور', 'invitation-manager-pro' ); ?></p>
                    <small><?php printf( __( '%d موافق، %d معتذر', 'invitation-manager-pro' ), $stats['rsvps']['accepted'], $stats['rsvps']['declined'] ); ?></small>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="impro-quick-actions">
            <h2><?php _e( 'إجراءات سريعة', 'invitation-manager-pro' ); ?></h2>
            <div class="impro-actions-grid">
                <a href="<?php echo admin_url( 'admin.php?page=impro-events&action=add' ); ?>" class="impro-action-card">
                    <div class="impro-action-icon">➕</div>
                    <h3><?php _e( 'إضافة مناسبة جديدة', 'invitation-manager-pro' ); ?></h3>
                    <p><?php _e( 'إنشاء مناسبة جديدة وإعداد تفاصيلها', 'invitation-manager-pro' ); ?></p>
                </a>

                <a href="<?php echo admin_url( 'admin.php?page=impro-guests&action=add' ); ?>" class="impro-action-card">
                    <div class="impro-action-icon">👤</div>
                    <h3><?php _e( 'إضافة ضيف جديد', 'invitation-manager-pro' ); ?></h3>
                    <p><?php _e( 'إضافة ضيف جديد إلى قائمة المدعوين', 'invitation-manager-pro' ); ?></p>
                </a>

                <a href="<?php echo admin_url( 'admin.php?page=impro-guests&action=import' ); ?>" class="impro-action-card">
                    <div class="impro-action-icon">📥</div>
                    <h3><?php _e( 'استيراد المدعوين', 'invitation-manager-pro' ); ?></h3>
                    <p><?php _e( 'استيراد قائمة المدعوين من ملف CSV', 'invitation-manager-pro' ); ?></p>
                </a>

                <a href="<?php echo admin_url( 'admin.php?page=impro-statistics' ); ?>" class="impro-action-card">
                    <div class="impro-action-icon">📊</div>
                    <h3><?php _e( 'عرض الإحصائيات', 'invitation-manager-pro' ); ?></h3>
                    <p><?php _e( 'مراجعة إحصائيات مفصلة وتقارير', 'invitation-manager-pro' ); ?></p>
                </a>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="impro-recent-activity">
            <h2><?php _e( 'النشاط الأخير', 'invitation-manager-pro' ); ?></h2>
            <div class="impro-activity-list">
                <?php
                // Get recent RSVPs
                $rsvp_manager = new IMPRO_RSVP_Manager();
                $recent_rsvps = $rsvp_manager->get_event_rsvps( 0, array( 'limit' => 5 ) );
                
                if ( $recent_rsvps ) :
                    foreach ( $recent_rsvps as $rsvp ) :
                        $status_class = $rsvp->status === 'accepted' ? 'accepted' : ( $rsvp->status === 'declined' ? 'declined' : 'pending' );
                        $status_text = $rsvp->status === 'accepted' ? __( 'موافق', 'invitation-manager-pro' ) : ( $rsvp->status === 'declined' ? __( 'معتذر', 'invitation-manager-pro' ) : __( 'في الانتظار', 'invitation-manager-pro' ) );
                ?>
                <div class="impro-activity-item">
                    <div class="impro-activity-icon status-<?php echo esc_attr( $status_class ); ?>">
                        <?php echo $rsvp->status === 'accepted' ? '✅' : ( $rsvp->status === 'declined' ? '❌' : '⏳' ); ?>
                    </div>
                    <div class="impro-activity-content">
                        <p><strong><?php echo esc_html( $rsvp->guest_name ); ?></strong> <?php echo esc_html( $status_text ); ?></p>
                        <small><?php echo esc_html( human_time_diff( strtotime( $rsvp->response_date ), current_time( 'timestamp' ) ) ); ?> <?php _e( 'منذ', 'invitation-manager-pro' ); ?></small>
                    </div>
                </div>
                <?php
                    endforeach;
                else :
                ?>
                <div class="impro-no-activity">
                    <p><?php _e( 'لا يوجد نشاط حديث', 'invitation-manager-pro' ); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- System Status -->
        <div class="impro-system-status">
            <h2><?php _e( 'حالة النظام', 'invitation-manager-pro' ); ?></h2>
            <div class="impro-status-grid">
                <?php
                $requirements = IMPRO_Installation::check_system_requirements();
                ?>
                <div class="impro-status-item">
                    <span class="impro-status-label"><?php _e( 'إصدار PHP', 'invitation-manager-pro' ); ?>:</span>
                    <span class="impro-status-value <?php echo $requirements['php_version']['status'] ? 'good' : 'warning'; ?>">
                        <?php echo esc_html( $requirements['php_version']['current'] ); ?>
                    </span>
                </div>

                <div class="impro-status-item">
                    <span class="impro-status-label"><?php _e( 'إصدار ووردبريس', 'invitation-manager-pro' ); ?>:</span>
                    <span class="impro-status-value <?php echo $requirements['wordpress_version']['status'] ? 'good' : 'warning'; ?>">
                        <?php echo esc_html( $requirements['wordpress_version']['current'] ); ?>
                    </span>
                </div>

                <div class="impro-status-item">
                    <span class="impro-status-label"><?php _e( 'حد الذاكرة', 'invitation-manager-pro' ); ?>:</span>
                    <span class="impro-status-value <?php echo $requirements['memory_limit']['status'] ? 'good' : 'warning'; ?>">
                        <?php echo esc_html( $requirements['memory_limit']['current'] ); ?>
                    </span>
                </div>

                <div class="impro-status-item">
                    <span class="impro-status-label"><?php _e( 'إصدار الإضافة', 'invitation-manager-pro' ); ?>:</span>
                    <span class="impro-status-value good">
                        <?php echo esc_html( IMPRO_VERSION ); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.impro-dashboard {
    max-width: 1200px;
}

.impro-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.impro-stat-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.impro-stat-icon {
    font-size: 2.5em;
    opacity: 0.8;
}

.impro-stat-content h3 {
    margin: 0;
    font-size: 2em;
    color: #2271b1;
}

.impro-stat-content p {
    margin: 5px 0;
    font-weight: 600;
    color: #333;
}

.impro-stat-content small {
    color: #666;
}

.impro-quick-actions, .impro-recent-activity, .impro-system-status {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.impro-actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.impro-action-card {
    border: 1px solid #ddd;
    border-radius: 6px;
    padding: 15px;
    text-decoration: none;
    color: inherit;
    transition: all 0.3s ease;
}

.impro-action-card:hover {
    border-color: #2271b1;
    box-shadow: 0 2px 8px rgba(34, 113, 177, 0.2);
    text-decoration: none;
    color: inherit;
}

.impro-action-icon {
    font-size: 2em;
    margin-bottom: 10px;
}

.impro-action-card h3 {
    margin: 0 0 10px 0;
    color: #2271b1;
}

.impro-action-card p {
    margin: 0;
    color: #666;
    font-size: 0.9em;
}

.impro-activity-list {
    margin-top: 15px;
}

.impro-activity-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 10px 0;
    border-bottom: 1px solid #eee;
}

.impro-activity-item:last-child {
    border-bottom: none;
}

.impro-activity-icon {
    font-size: 1.2em;
    width: 30px;
    text-align: center;
}

.impro-activity-content p {
    margin: 0;
}

.impro-activity-content small {
    color: #666;
}

.impro-no-activity {
    text-align: center;
    color: #666;
    padding: 20px;
}

.impro-status-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.impro-status-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    background: #f9f9f9;
    border-radius: 4px;
}

.impro-status-value.good {
    color: #46b450;
    font-weight: 600;
}

.impro-status-value.warning {
    color: #ffb900;
    font-weight: 600;
}

.impro-status-value.error {
    color: #dc3232;
    font-weight: 600;
}
</style>


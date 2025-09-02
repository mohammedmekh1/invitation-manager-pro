<?php
/**
 * Email management class.
 *
 * @package Invitation_Manager_Pro
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * IMPRO_Email class.
 */
class IMPRO_Email {

    /**
     * Send invitation email.
     *
     * @param object $invitation Invitation object.
     * @param object $guest Guest object.
     * @param object $event Event object.
     * @return bool True on success, false on failure.
     */
    public static function send_invitation( $invitation, $guest, $event ) {
        if ( ! get_option( 'impro_enable_email', 1 ) ) {
            return false;
        }

        if ( empty( $guest->email ) ) {
            return false;
        }

        $subject = self::get_email_subject( $event );
        $message = self::get_email_message( $invitation, $guest, $event );
        $headers = self::get_email_headers();

        // Add invitation URL to message
        $public = new IMPRO_Public();
        $invitation_url = $public->get_invitation_url( $invitation->token );
        
        $message = str_replace( '{invitation_url}', $invitation_url, $message );

        // Send email
        $sent = wp_mail( $guest->email, $subject, $message, $headers );

        if ( $sent ) {
            // Log email sent
            self::log_email_sent( $invitation->id, $guest->email );
            
            // Update invitation status
            $invitation_manager = new IMPRO_Invitation_Manager();
            $invitation_manager->update_invitation_status( $invitation->id, 'sent' );
        }

        return $sent;
    }

    /**
     * Send RSVP confirmation email.
     *
     * @param object $rsvp RSVP object.
     * @param object $guest Guest object.
     * @param object $event Event object.
     * @return bool True on success, false on failure.
     */
    public static function send_rsvp_confirmation( $rsvp, $guest, $event ) {
        if ( ! get_option( 'impro_enable_email', 1 ) ) {
            return false;
        }

        if ( empty( $guest->email ) ) {
            return false;
        }

        $subject = sprintf( 
            __( 'تأكيد الرد على دعوة %s', 'invitation-manager-pro' ), 
            $event->name 
        );

        $status_text = $rsvp->status === 'accepted' ? 
            __( 'موافق على الحضور', 'invitation-manager-pro' ) : 
            __( 'معتذر عن الحضور', 'invitation-manager-pro' );

        $message = sprintf(
            __( 'عزيزنا %s،<br><br>تم استلام ردكم على دعوة %s.<br><br>حالة الرد: %s<br><br>تفاصيل المناسبة:<br>التاريخ: %s<br>الوقت: %s<br>المكان: %s<br><br>شكراً لكم.', 'invitation-manager-pro' ),
            $guest->name,
            $event->name,
            $status_text,
            date_i18n( 'j F Y', strtotime( $event->event_date ) ),
            $event->event_time ? date_i18n( 'g:i A', strtotime( $event->event_time ) ) : __( 'غير محدد', 'invitation-manager-pro' ),
            $event->venue
        );

        if ( $rsvp->plus_one_attending && $rsvp->plus_one_name ) {
            $message .= '<br><br>' . sprintf( 
                __( 'المرافق: %s', 'invitation-manager-pro' ), 
                $rsvp->plus_one_name 
            );
        }

        if ( $rsvp->dietary_requirements ) {
            $message .= '<br><br>' . sprintf( 
                __( 'المتطلبات الغذائية: %s', 'invitation-manager-pro' ), 
                $rsvp->dietary_requirements 
            );
        }

        $headers = self::get_email_headers();

        return wp_mail( $guest->email, $subject, $message, $headers );
    }

    /**
     * Send reminder email.
     *
     * @param object $invitation Invitation object.
     * @param object $guest Guest object.
     * @param object $event Event object.
     * @return bool True on success, false on failure.
     */
    public static function send_reminder( $invitation, $guest, $event ) {
        if ( ! get_option( 'impro_enable_email', 1 ) ) {
            return false;
        }

        if ( empty( $guest->email ) ) {
            return false;
        }

        $subject = sprintf( 
            __( 'تذكير: دعوة %s', 'invitation-manager-pro' ), 
            $event->name 
        );

        $public = new IMPRO_Public();
        $invitation_url = $public->get_invitation_url( $invitation->token );

        $message = sprintf(
            __( 'عزيزنا %s،<br><br>هذا تذكير بدعوتكم لحضور %s.<br><br>تفاصيل المناسبة:<br>التاريخ: %s<br>الوقت: %s<br>المكان: %s<br><br>يرجى تأكيد حضوركم من خلال الرابط التالي:<br><a href="%s">تأكيد الحضور</a><br><br>مع خالص التحية', 'invitation-manager-pro' ),
            $guest->name,
            $event->name,
            date_i18n( 'j F Y', strtotime( $event->event_date ) ),
            $event->event_time ? date_i18n( 'g:i A', strtotime( $event->event_time ) ) : __( 'غير محدد', 'invitation-manager-pro' ),
            $event->venue,
            $invitation_url
        );

        $headers = self::get_email_headers();

        return wp_mail( $guest->email, $subject, $message, $headers );
    }

    /**
     * Send bulk invitations.
     *
     * @param array $invitations Array of invitation objects.
     * @return array Results array with sent and failed counts.
     */
    public static function send_bulk_invitations( $invitations ) {
        $sent = 0;
        $failed = 0;

        $guest_manager = new IMPRO_Guest_Manager();
        $event_manager = new IMPRO_Event_Manager();

        foreach ( $invitations as $invitation ) {
            $guest = $guest_manager->get_guest( $invitation->guest_id );
            $event = $event_manager->get_event( $invitation->event_id );

            if ( $guest && $event ) {
                if ( self::send_invitation( $invitation, $guest, $event ) ) {
                    $sent++;
                } else {
                    $failed++;
                }
            } else {
                $failed++;
            }

            // Add small delay to prevent overwhelming the mail server
            usleep( 100000 ); // 0.1 second
        }

        return array(
            'sent' => $sent,
            'failed' => $failed
        );
    }

    /**
     * Send test email.
     *
     * @param string $email Test email address.
     * @return bool True on success, false on failure.
     */
    public static function send_test_email( $email ) {
        $subject = __( 'اختبار البريد الإلكتروني - إدارة الدعوات', 'invitation-manager-pro' );
        $message = __( 'هذا اختبار للتأكد من عمل البريد الإلكتروني بشكل صحيح.<br><br>إذا وصلتك هذه الرسالة، فإن إعدادات البريد الإلكتروني تعمل بشكل صحيح.', 'invitation-manager-pro' );
        $headers = self::get_email_headers();

        return wp_mail( $email, $subject, $message, $headers );
    }

    /**
     * Get email subject with placeholders replaced.
     *
     * @param object $event Event object.
     * @return string Email subject.
     */
    private static function get_email_subject( $event ) {
        $subject = get_option( 'impro_email_subject', __( 'دعوة لحضور {event_name}', 'invitation-manager-pro' ) );
        
        $placeholders = array(
            '{event_name}' => $event->name,
            '{event_date}' => date_i18n( 'j F Y', strtotime( $event->event_date ) ),
            '{venue}' => $event->venue
        );

        return str_replace( array_keys( $placeholders ), array_values( $placeholders ), $subject );
    }

    /**
     * Get email message with placeholders replaced.
     *
     * @param object $invitation Invitation object.
     * @param object $guest Guest object.
     * @param object $event Event object.
     * @return string Email message.
     */
    private static function get_email_message( $invitation, $guest, $event ) {
        $template = get_option( 'impro_email_template', self::get_default_email_template() );
        
        $placeholders = array(
            '{guest_name}' => $guest->name,
            '{event_name}' => $event->name,
            '{event_date}' => date_i18n( 'j F Y', strtotime( $event->event_date ) ),
            '{event_time}' => $event->event_time ? date_i18n( 'g:i A', strtotime( $event->event_time ) ) : __( 'غير محدد', 'invitation-manager-pro' ),
            '{venue}' => $event->venue,
            '{address}' => $event->address ?: '',
            '{description}' => $event->description ?: '',
            '{contact_info}' => $event->contact_info ?: '',
            '{invitation_url}' => '{invitation_url}' // Will be replaced later
        );

        return str_replace( array_keys( $placeholders ), array_values( $placeholders ), $template );
    }

    /**
     * Get email headers.
     *
     * @return array Email headers.
     */
    private static function get_email_headers() {
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo( 'name' ) . ' <' . get_option( 'admin_email' ) . '>'
        );

        return apply_filters( 'impro_email_headers', $headers );
    }

    /**
     * Get default email template.
     *
     * @return string Default email template.
     */
    private static function get_default_email_template() {
        return __( 'مرحباً {guest_name},<br><br>يسعدنا دعوتكم لحضور {event_name} في {event_date} بـ {venue}.<br><br>يرجى تأكيد حضوركم من خلال الرابط التالي:<br><a href="{invitation_url}">تأكيد الحضور</a><br><br>مع خالص التحية', 'invitation-manager-pro' );
    }

    /**
     * Log email sent.
     *
     * @param int    $invitation_id Invitation ID.
     * @param string $email Email address.
     */
    private static function log_email_sent( $invitation_id, $email ) {
        global $wpdb;

        $database = new IMPRO_Database();
        $table = $database->get_table_name( 'email_logs' );

        // Create email logs table if it doesn't exist
        if ( ! $database->table_exists( 'email_logs' ) ) {
            $wpdb->query( "
                CREATE TABLE {$table} (
                    id int(11) NOT NULL AUTO_INCREMENT,
                    invitation_id int(11) NOT NULL,
                    email varchar(255) NOT NULL,
                    sent_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    KEY idx_invitation_id (invitation_id),
                    KEY idx_email (email),
                    KEY idx_sent_at (sent_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            " );
        }

        $wpdb->insert(
            $table,
            array(
                'invitation_id' => $invitation_id,
                'email' => $email,
                'sent_at' => current_time( 'mysql' )
            ),
            array( '%d', '%s', '%s' )
        );
    }

    /**
     * Get email statistics.
     *
     * @param int $event_id Event ID (optional).
     * @return array Email statistics.
     */
    public static function get_email_statistics( $event_id = 0 ) {
        global $wpdb;

        $database = new IMPRO_Database();
        $logs_table = $database->get_table_name( 'email_logs' );
        $invitations_table = $database->get_table_name( 'invitations' );

        if ( ! $database->table_exists( 'email_logs' ) ) {
            return array(
                'total_sent' => 0,
                'today_sent' => 0,
                'this_week_sent' => 0,
                'this_month_sent' => 0
            );
        }

        $where_clause = '';
        $params = array();

        if ( $event_id ) {
            $where_clause = "WHERE i.event_id = %d";
            $params[] = $event_id;
        }

        // Total sent
        $total_sent = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$logs_table} l 
             JOIN {$invitations_table} i ON l.invitation_id = i.id 
             {$where_clause}",
            $params
        ) );

        // Today sent
        $today_params = array_merge( $params, array( current_time( 'Y-m-d' ) ) );
        $today_sent = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$logs_table} l 
             JOIN {$invitations_table} i ON l.invitation_id = i.id 
             {$where_clause} " . ( $where_clause ? 'AND' : 'WHERE' ) . " DATE(l.sent_at) = %s",
            $today_params
        ) );

        // This week sent
        $week_start = date( 'Y-m-d', strtotime( 'monday this week' ) );
        $week_params = array_merge( $params, array( $week_start ) );
        $week_sent = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$logs_table} l 
             JOIN {$invitations_table} i ON l.invitation_id = i.id 
             {$where_clause} " . ( $where_clause ? 'AND' : 'WHERE' ) . " DATE(l.sent_at) >= %s",
            $week_params
        ) );

        // This month sent
        $month_start = date( 'Y-m-01' );
        $month_params = array_merge( $params, array( $month_start ) );
        $month_sent = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$logs_table} l 
             JOIN {$invitations_table} i ON l.invitation_id = i.id 
             {$where_clause} " . ( $where_clause ? 'AND' : 'WHERE' ) . " DATE(l.sent_at) >= %s",
            $month_params
        ) );

        return array(
            'total_sent' => intval( $total_sent ),
            'today_sent' => intval( $today_sent ),
            'this_week_sent' => intval( $week_sent ),
            'this_month_sent' => intval( $month_sent )
        );
    }

    /**
     * Schedule reminder emails.
     *
     * @param int $event_id Event ID.
     * @param int $days_before Days before event to send reminder.
     * @return bool True on success, false on failure.
     */
    public static function schedule_reminder_emails( $event_id, $days_before = 3 ) {
        $event_manager = new IMPRO_Event_Manager();
        $event = $event_manager->get_event( $event_id );

        if ( ! $event ) {
            return false;
        }

        $reminder_date = date( 'Y-m-d H:i:s', strtotime( $event->event_date . ' -' . $days_before . ' days' ) );
        $reminder_timestamp = strtotime( $reminder_date );

        if ( $reminder_timestamp <= time() ) {
            return false; // Can't schedule in the past
        }

        // Schedule the reminder
        wp_schedule_single_event( $reminder_timestamp, 'impro_send_reminder_emails', array( $event_id ) );

        return true;
    }

    /**
     * Send scheduled reminder emails.
     *
     * @param int $event_id Event ID.
     */
    public static function send_scheduled_reminders( $event_id ) {
        $invitation_manager = new IMPRO_Invitation_Manager();
        $guest_manager = new IMPRO_Guest_Manager();
        $event_manager = new IMPRO_Event_Manager();
        $rsvp_manager = new IMPRO_RSVP_Manager();

        $event = $event_manager->get_event( $event_id );
        if ( ! $event ) {
            return;
        }

        // Get invitations for guests who haven't responded
        $invitations = $invitation_manager->get_event_invitations( $event_id );
        
        foreach ( $invitations as $invitation ) {
            $rsvp = $rsvp_manager->get_rsvp_by_guest_event( $invitation->guest_id, $event_id );
            
            // Only send reminder if no RSVP yet
            if ( ! $rsvp ) {
                $guest = $guest_manager->get_guest( $invitation->guest_id );
                if ( $guest ) {
                    self::send_reminder( $invitation, $guest, $event );
                    
                    // Add small delay
                    usleep( 100000 ); // 0.1 second
                }
            }
        }
    }

    /**
     * Validate email settings.
     *
     * @return array Validation results.
     */
    public static function validate_email_settings() {
        $results = array(
            'smtp_configured' => false,
            'from_email_valid' => false,
            'template_valid' => false,
            'test_email_sent' => false
        );

        // Check if SMTP is configured
        if ( defined( 'SMTP_HOST' ) || get_option( 'smtp_host' ) ) {
            $results['smtp_configured'] = true;
        }

        // Check from email
        $from_email = get_option( 'admin_email' );
        if ( is_email( $from_email ) ) {
            $results['from_email_valid'] = true;
        }

        // Check email template
        $template = get_option( 'impro_email_template' );
        if ( ! empty( $template ) && strpos( $template, '{guest_name}' ) !== false ) {
            $results['template_valid'] = true;
        }

        // Try sending test email
        if ( $results['from_email_valid'] ) {
            $results['test_email_sent'] = self::send_test_email( $from_email );
        }

        return $results;
    }

    /**
     * Get email queue status.
     *
     * @return array Queue status.
     */
    public static function get_email_queue_status() {
        // This would integrate with a proper email queue system
        // For now, return basic status
        return array(
            'pending' => 0,
            'processing' => 0,
            'completed' => 0,
            'failed' => 0
        );
    }

    /**
     * Clean up old email logs.
     *
     * @param int $days_old Days old to clean up.
     * @return int Number of logs cleaned.
     */
    public static function cleanup_old_email_logs( $days_old = 90 ) {
        global $wpdb;

        $database = new IMPRO_Database();
        $table = $database->get_table_name( 'email_logs' );

        if ( ! $database->table_exists( 'email_logs' ) ) {
            return 0;
        }

        $cutoff_date = date( 'Y-m-d H:i:s', strtotime( "-{$days_old} days" ) );

        $deleted = $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$table} WHERE sent_at < %s",
            $cutoff_date
        ) );

        return intval( $deleted );
    }
}


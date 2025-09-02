<?php
/**
 * Invitation management class.
 *
 * @package Invitation_Manager_Pro
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * IMPRO_Invitation_Manager class.
 */
class IMPRO_Invitation_Manager {

    /**
     * Database instance.
     *
     * @var IMPRO_Database
     */
    private $database;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->database = new IMPRO_Database();
    }

    /**
     * Create a new invitation.
     *
     * @param array $invitation_data Invitation data.
     * @return int|false Invitation ID on success, false on failure.
     */
    public function create_invitation( $invitation_data ) {
        // التحقق من صحة البيانات
        if ( ! is_array( $invitation_data ) || empty( $invitation_data ) ) {
            error_log( 'Invalid invitation data provided' );
            return false;
        }

        $sanitized_data = $this->sanitize_invitation_data( $invitation_data );
        
        if ( ! $this->validate_invitation_data( $sanitized_data ) ) {
            error_log( 'Invalid invitation data after sanitization' );
            return false;
        }

        // Generate unique token if not provided
        if ( empty( $sanitized_data['unique_token'] ) ) {
            $sanitized_data['unique_token'] = $this->generate_unique_token();
        }

        // Set expiration date if not provided
        if ( empty( $sanitized_data['expires_at'] ) ) {
            $expiry_days = get_option( 'impro_invitation_expiry', 30 );
            $sanitized_data['expires_at'] = date( 'Y-m-d H:i:s', strtotime( "+{$expiry_days} days" ) );
        }

        // Set default values
        if ( ! isset( $sanitized_data['status'] ) ) {
            $sanitized_data['status'] = 'pending';
        }

        if ( ! isset( $sanitized_data['is_sent'] ) ) {
            $sanitized_data['is_sent'] = 0;
        }

        if ( ! isset( $sanitized_data['is_opened'] ) ) {
            $sanitized_data['is_opened'] = 0;
        }

        if ( ! isset( $sanitized_data['created_at'] ) ) {
            $sanitized_data['created_at'] = current_time( 'mysql' );
        }

        if ( ! isset( $sanitized_data['updated_at'] ) ) {
            $sanitized_data['updated_at'] = current_time( 'mysql' );
        }

        $table = $this->database->get_invitations_table();
        $invitation_id = $this->database->insert( $table, $sanitized_data );

        if ( $invitation_id ) {
            // تسجيل الحدث
            do_action( 'impro_invitation_created', $invitation_id, $sanitized_data );
            return $invitation_id;
        } else {
            error_log( 'Failed to create invitation: ' . $this->database->last_error );
            return false;
        }
    }

    /**
     * Update an existing invitation.
     *
     * @param int   $invitation_id   Invitation ID.
     * @param array $invitation_data Invitation data.
     * @return bool True on success, false on failure.
     */
    public function update_invitation( $invitation_id, $invitation_data ) {
        // التحقق من صحة البيانات
        if ( empty( $invitation_id ) || ! is_numeric( $invitation_id ) ) {
            error_log( 'Invalid invitation ID for update' );
            return false;
        }

        if ( ! is_array( $invitation_data ) || empty( $invitation_data ) ) {
            error_log( 'Invalid invitation data for update' );
            return false;
        }

        $sanitized_data = $this->sanitize_invitation_data( $invitation_data );

        // Update timestamp
        $sanitized_data['updated_at'] = current_time( 'mysql' );

        $table = $this->database->get_invitations_table();
        $result = $this->database->update( 
            $table, 
            $sanitized_data, 
            array( 'id' => $invitation_id ),
            null,
            array( '%d' )
        );

        if ( $result !== false ) {
            // تسجيل الحدث
            do_action( 'impro_invitation_updated', $invitation_id, $sanitized_data );
            return true;
        } else {
            error_log( 'Failed to update invitation ' . $invitation_id . ': ' . $this->database->last_error );
            return false;
        }
    }

    /**
     * Delete an invitation.
     *
     * @param int $invitation_id Invitation ID.
     * @return bool True on success, false on failure.
     */
    public function delete_invitation( $invitation_id ) {
        // التحقق من صحة البيانات
        if ( empty( $invitation_id ) || ! is_numeric( $invitation_id ) ) {
            error_log( 'Invalid invitation ID for deletion' );
            return false;
        }

        $table = $this->database->get_invitations_table();
        $result = $this->database->delete( 
            $table, 
            array( 'id' => $invitation_id ),
            array( '%d' )
        );

        if ( $result !== false ) {
            // تسجيل الحدث
            do_action( 'impro_invitation_deleted', $invitation_id );
            return true;
        } else {
            error_log( 'Failed to delete invitation ' . $invitation_id . ': ' . $this->database->last_error );
            return false;
        }
    }

    /**
     * Get invitation by ID.
     *
     * @param int $invitation_id Invitation ID.
     * @return object|null Invitation object or null if not found.
     */
    public function get_invitation( $invitation_id ) {
        // التحقق من صحة البيانات
        if ( empty( $invitation_id ) || ! is_numeric( $invitation_id ) ) {
            return null;
        }

        $table = $this->database->get_invitations_table();
        $query = "SELECT * FROM $table WHERE id = %d";
        return $this->database->get_row( $query, array( $invitation_id ) );
    }

    /**
     * Get invitation by token.
     *
     * @param string $token Invitation token.
     * @return object|null Invitation object or null if not found.
     */
    public function get_invitation_by_token( $token ) {
        if ( empty( $token ) ) {
            return null;
        }
        
        $table = $this->database->get_invitations_table();
        $query = "SELECT * FROM $table WHERE unique_token = %s";
        return $this->database->get_row( $query, array( $token ) );
    }

    /**
     * Get invitation by guest and event.
     *
     * @param int $guest_id Guest ID.
     * @param int $event_id Event ID.
     * @return object|null Invitation object or null if not found.
     */
    public function get_invitation_by_guest_event( $guest_id, $event_id ) {
        if ( empty( $guest_id ) || empty( $event_id ) ) {
            return null;
        }
        
        $table = $this->database->get_invitations_table();
        $query = "SELECT * FROM $table WHERE guest_id = %d AND event_id = %d";
        return $this->database->get_row( $query, array( $guest_id, $event_id ) );
    }

    /**
     * Get invitations for an event.
     *
     * @param int   $event_id Event ID.
     * @param array $args     Query arguments.
     * @return array Array of invitation objects.
     */
    public function get_event_invitations( $event_id, $args = array() ) {
        $defaults = array(
            'orderby' => 'created_at',
            'order'   => 'DESC',
            'limit'   => 0,
            'offset'  => 0,
            'status'  => 'all' // all, pending, sent, viewed
        );

        $args = wp_parse_args( $args, $defaults );
        
        if ( empty( $event_id ) ) {
            return array();
        }

        $table = $this->database->get_invitations_table();
        
        $query = "SELECT * FROM $table WHERE event_id = %d";
        $query_args = array( $event_id );

        // Add status filter
        if ( $args['status'] !== 'all' ) {
            if ( $args['status'] === 'sent' ) {
                $query .= " AND is_sent = 1";
            } elseif ( $args['status'] === 'viewed' ) {
                $query .= " AND is_opened = 1";
            } elseif ( $args['status'] === 'pending' ) {
                $query .= " AND is_sent = 0";
            }
        }

        // Add ORDER BY
        $query .= " ORDER BY " . sanitize_sql_orderby( $args['orderby'] . ' ' . $args['order'] );

        // Add LIMIT
        if ( $args['limit'] > 0 ) {
            $query .= " LIMIT " . intval( $args['offset'] ) . ", " . intval( $args['limit'] );
        }

        return $this->database->get_results( $query, $query_args );
    }

    /**
     * Get invitations for a guest.
     *
     * @param int $guest_id Guest ID.
     * @return array Array of invitation objects.
     */
    public function get_guest_invitations( $guest_id ) {
        if ( empty( $guest_id ) ) {
            return array();
        }
        
        $table = $this->database->get_invitations_table();
        $query = "SELECT * FROM $table WHERE guest_id = %d ORDER BY created_at DESC";
        return $this->database->get_results( $query, array( $guest_id ) );
    }

    /**
     * Get all invitations.
     *
     * @param array $args Query arguments.
     * @return array Array of invitation objects.
     */
    public function get_all_invitations( $args = array() ) {
        $defaults = array(
            'orderby' => 'created_at',
            'order'   => 'DESC',
            'limit'   => 0,
            'offset'  => 0,
            'status'  => 'all' // all, pending, sent, viewed
        );

        $args = wp_parse_args( $args, $defaults );
        $table = $this->database->get_invitations_table();
        
        $query = "SELECT * FROM $table";
        $query_args = array();

        // Add status filter
        if ( $args['status'] !== 'all' ) {
            if ( $args['status'] === 'sent' ) {
                $query .= " WHERE is_sent = 1";
            } elseif ( $args['status'] === 'viewed' ) {
                $query .= " WHERE is_opened = 1";
            } elseif ( $args['status'] === 'pending' ) {
                $query .= " WHERE is_sent = 0";
            }
        }

        // Add ORDER BY
        $query .= " ORDER BY " . sanitize_sql_orderby( $args['orderby'] . ' ' . $args['order'] );

        // Add LIMIT
        if ( $args['limit'] > 0 ) {
            $query .= " LIMIT " . intval( $args['offset'] ) . ", " . intval( $args['limit'] );
        }

        return $this->database->get_results( $query, $query_args );
    }

    /**
     * Send invitation via email.
     *
     * @param int $invitation_id Invitation ID.
     * @return bool True on success, false on failure.
     */
    public function send_invitation( $invitation_id ) {
        $invitation = $this->get_invitation( $invitation_id );
        if ( ! $invitation ) {
            error_log( 'Invitation not found: ' . $invitation_id );
            return false;
        }

        // Check if already sent
        if ( $invitation->is_sent ) {
            return true; // Already sent, consider it success
        }

        // Get guest and event data
        $guest_manager = new IMPRO_Guest_Manager();
        $event_manager = new IMPRO_Event_Manager();
        
        $guest = $guest_manager->get_guest( $invitation->guest_id );
        $event = $event_manager->get_event( $invitation->event_id );

        if ( ! $guest || ! $event || empty( $guest->email ) ) {
            error_log( 'Missing guest or event data for invitation: ' . $invitation_id );
            return false;
        }

        // Prepare email content
        $invitation_url = $this->get_invitation_url( $invitation->unique_token );
        $subject = $this->get_email_subject( $event );
        $message = $this->get_email_message( $guest, $event, $invitation_url );

        // Send email
        $headers = array( 
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        $sent = wp_mail( $guest->email, $subject, $message, $headers );

        if ( $sent ) {
            // Update invitation status
            $this->update_invitation( $invitation_id, array(
                'is_sent' => 1,
                'sent_at' => current_time( 'mysql' )
            ) );
            
            // Log the action
            do_action( 'impro_invitation_sent', $invitation_id, $guest->email );
            return true;
        } else {
            error_log( 'Failed to send invitation email to: ' . $guest->email );
        }

        return false;
    }

    /**
     * Mark invitation as opened.
     *
     * @param string $token Invitation token.
     * @return bool True on success, false on failure.
     */
    public function mark_as_opened( $token ) {
        if ( empty( $token ) ) {
            return false;
        }
        
        $invitation = $this->get_invitation_by_token( $token );
        if ( ! $invitation ) {
            return false;
        }

        // Check if already opened
        if ( $invitation->is_opened ) {
            return true;
        }

        $result = $this->update_invitation( $invitation->id, array(
            'is_opened' => 1,
            'opened_at' => current_time( 'mysql' )
        ) );
        
        if ( $result ) {
            // Log the action
            do_action( 'impro_invitation_opened', $invitation->id, $token );
        }
        
        return $result;
    }

    /**
     * Create invitations for multiple guests.
     *
     * @param int   $event_id   Event ID.
     * @param array $guest_ids  Array of guest IDs.
     * @return array Results array with created count and errors.
     */
    public function create_bulk_invitations( $event_id, $guest_ids ) {
        if ( empty( $event_id ) || empty( $guest_ids ) || ! is_array( $guest_ids ) ) {
            return array(
                'created' => 0,
                'errors'  => array( __( 'بيانات غير صحيحة', 'invitation-manager-pro' ) )
            );
        }

        $created = 0;
        $errors = array();

        foreach ( $guest_ids as $guest_id ) {
            if ( empty( $guest_id ) ) {
                continue;
            }
            
            // Check if invitation already exists
            $existing_invitation = $this->get_invitation_by_guest_event( $guest_id, $event_id );
            if ( $existing_invitation ) {
                $created++; // Count existing as created
                continue;
            }

            $invitation_data = array(
                'guest_id' => intval( $guest_id ),
                'event_id' => intval( $event_id ),
                'status'   => 'pending'
            );

            $invitation_id = $this->create_invitation( $invitation_data );
            if ( $invitation_id ) {
                $created++;
            } else {
                $errors[] = sprintf( __( 'فشل في إنشاء دعوة للضيف ID: %d', 'invitation-manager-pro' ), $guest_id );
            }
        }

        return array(
            'created' => $created,
            'errors'  => $errors
        );
    }

    /**
     * Get invitation statistics.
     *
     * @param int $event_id Optional event ID to filter by.
     * @return array Invitation statistics.
     */
    public function get_invitation_statistics( $event_id = 0 ) {
        $table = $this->database->get_invitations_table();
        
        $where_clause = '';
        $query_args = array();
        
        if ( $event_id > 0 ) {
            $where_clause = ' WHERE event_id = %d';
            $query_args[] = $event_id;
        }

        $total = $this->database->get_var( 
            $this->database->prepare_query( "SELECT COUNT(*) FROM $table" . $where_clause, $query_args )
        );
        
        $sent = $this->database->get_var( 
            $this->database->prepare_query( "SELECT COUNT(*) FROM $table" . $where_clause . ( $where_clause ? ' AND' : ' WHERE' ) . " is_sent = 1", $query_args )
        );
        
        $opened = $this->database->get_var( 
            $this->database->prepare_query( "SELECT COUNT(*) FROM $table" . $where_clause . ( $where_clause ? ' AND' : ' WHERE' ) . " is_opened = 1", $query_args )
        );

        return array(
            'total'      => intval( $total ),
            'sent'       => intval( $sent ),
            'opened'     => intval( $opened ),
            'pending'    => intval( $total ) - intval( $sent ),
            'not_opened' => intval( $sent ) - intval( $opened )
        );
    }

    /**
     * Generate unique token.
     *
     * @return string|false Unique token on success, false on failure.
     */
    private function generate_unique_token() {
        $attempts = 0;
        $max_attempts = 10;
        
        do {
            if ( function_exists( 'random_bytes' ) ) {
                $token = bin2hex( random_bytes( 32 ) );
            } elseif ( function_exists( 'openssl_random_pseudo_bytes' ) ) {
                $token = bin2hex( openssl_random_pseudo_bytes( 32 ) );
            } else {
                // Fallback to wp_generate_password
                $token = wp_generate_password( 64, false );
            }
            
            $attempts++;
        } while ( $this->token_exists( $token ) && $attempts < $max_attempts );

        if ( $attempts >= $max_attempts ) {
            error_log( 'Failed to generate unique token after ' . $max_attempts . ' attempts' );
            return false;
        }

        return $token;
    }

    /**
     * Check if token exists.
     *
     * @param string $token Token to check.
     * @return bool True if exists, false otherwise.
     */
    private function token_exists( $token ) {
        if ( empty( $token ) ) {
            return true; // Empty token is considered existing
        }
        
        $table = $this->database->get_invitations_table();
        $count = $this->database->get_var( 
            "SELECT COUNT(*) FROM $table WHERE unique_token = %s",
            array( $token )
        );
        return intval( $count ) > 0;
    }

    /**
     * Get invitation URL.
     *
     * @param string $token Invitation token.
     * @return string Invitation URL.
     */
    private function get_invitation_url( $token ) {
        if ( empty( $token ) ) {
            return home_url();
        }
        
        // Check for custom invitation page
        $invitation_page_id = get_option( 'impro_invitation_page_id' );
        if ( $invitation_page_id ) {
            return get_permalink( $invitation_page_id ) . '?token=' . urlencode( $token );
        }
        
        return home_url( '/invitation/' . urlencode( $token ) );
    }

    /**
     * Get email subject.
     *
     * @param object $event Event object.
     * @return string Email subject.
     */
    private function get_email_subject( $event ) {
        if ( ! $event ) {
            return __( 'دعوة خاصة', 'invitation-manager-pro' );
        }
        
        $template = get_option( 'impro_email_subject', __( 'دعوة لحضور {event_name}', 'invitation-manager-pro' ) );
        return str_replace( '{event_name}', $event->name, $template );
    }

    /**
     * Get email message.
     *
     * @param object $guest         Guest object.
     * @param object $event         Event object.
     * @param string $invitation_url Invitation URL.
     * @return string Email message.
     */
    private function get_email_message( $guest, $event, $invitation_url ) {
        if ( ! $guest || ! $event ) {
            return '';
        }
        
        $template = get_option( 'impro_email_template', 
            __( 'مرحباً {guest_name},<br><br>يسعدنا دعوتكم لحضور {event_name} في {event_date} بـ {venue}.<br><br>يرجى تأكيد حضوركم من خلال الرابط التالي:<br><a href="{invitation_url}">تأكيد الحضور</a><br><br>مع خالص التحية', 'invitation-manager-pro' )
        );

        $replacements = array(
            '{guest_name}'     => esc_html( $guest->name ),
            '{event_name}'     => esc_html( $event->name ),
            '{event_date}'     => esc_html( date_i18n( get_option( 'date_format' ), strtotime( $event->event_date ) ) ),
            '{event_time}'     => esc_html( date_i18n( get_option( 'time_format' ), strtotime( $event->event_time ) ) ),
            '{venue}'          => esc_html( $event->venue ),
            '{address}'        => esc_html( $event->address ),
            '{description}'    => wp_kses_post( $event->description ),
            '{invitation_url}' => esc_url( $invitation_url )
        );

        return str_replace( array_keys( $replacements ), array_values( $replacements ), $template );
    }

    /**
     * Sanitize invitation data.
     *
     * @param array $data Raw invitation data.
     * @return array Sanitized invitation data.
     */
    private function sanitize_invitation_data( $data ) {
        $sanitized = array();

        if ( isset( $data['guest_id'] ) ) {
            $sanitized['guest_id'] = intval( $data['guest_id'] );
        }

        if ( isset( $data['event_id'] ) ) {
            $sanitized['event_id'] = intval( $data['event_id'] );
        }

        if ( isset( $data['unique_token'] ) ) {
            $sanitized['unique_token'] = sanitize_text_field( $data['unique_token'] );
        }

        if ( isset( $data['status'] ) ) {
            $allowed_statuses = array( 'pending', 'sent', 'viewed', 'expired' );
            $status = sanitize_text_field( $data['status'] );
            $sanitized['status'] = in_array( $status, $allowed_statuses ) ? $status : 'pending';
        }

        if ( isset( $data['is_sent'] ) ) {
            $sanitized['is_sent'] = intval( $data['is_sent'] );
        }

        if ( isset( $data['is_opened'] ) ) {
            $sanitized['is_opened'] = intval( $data['is_opened'] );
        }

        if ( isset( $data['sent_at'] ) ) {
            $sanitized['sent_at'] = sanitize_text_field( $data['sent_at'] );
        }

        if ( isset( $data['opened_at'] ) ) {
            $sanitized['opened_at'] = sanitize_text_field( $data['opened_at'] );
        }

        if ( isset( $data['expires_at'] ) ) {
            $sanitized['expires_at'] = sanitize_text_field( $data['expires_at'] );
        }

        if ( isset( $data['created_at'] ) ) {
            $sanitized['created_at'] = sanitize_text_field( $data['created_at'] );
        }

        if ( isset( $data['updated_at'] ) ) {
            $sanitized['updated_at'] = sanitize_text_field( $data['updated_at'] );
        }

        return $sanitized;
    }

    /**
     * Validate invitation data.
     *
     * @param array $data Invitation data.
     * @return bool True if valid, false otherwise.
     */
    private function validate_invitation_data( $data ) {
        // Required fields
        if ( empty( $data['guest_id'] ) || empty( $data['event_id'] ) ) {
            error_log( 'Missing required fields: guest_id or event_id' );
            return false;
        }

        // Validate guest ID
        if ( ! is_numeric( $data['guest_id'] ) || $data['guest_id'] <= 0 ) {
            error_log( 'Invalid guest ID: ' . $data['guest_id'] );
            return false;
        }

        // Validate event ID
        if ( ! is_numeric( $data['event_id'] ) || $data['event_id'] <= 0 ) {
            error_log( 'Invalid event ID: ' . $data['event_id'] );
            return false;
        }

        return true;
    }

    /**
     * Delete invitations by event ID.
     *
     * @param int $event_id Event ID.
     * @return bool True on success, false on failure.
     */
    public function delete_invitations_by_event( $event_id ) {
        if ( empty( $event_id ) ) {
            return false;
        }
        
        $table = $this->database->get_invitations_table();
        $result = $this->database->delete( 
            $table, 
            array( 'event_id' => $event_id ),
            array( '%d' )
        );

        return $result !== false;
    }

    /**
     * Delete invitations by guest ID.
     *
     * @param int $guest_id Guest ID.
     * @return bool True on success, false on failure.
     */
    public function delete_invitations_by_guest( $guest_id ) {
        if ( empty( $guest_id ) ) {
            return false;
        }
        
        $table = $this->database->get_invitations_table();
        $result = $this->database->delete( 
            $table, 
            array( 'guest_id' => $guest_id ),
            array( '%d' )
        );

        return $result !== false;
    }

    /**
     * Get invitations with guest and event information.
     *
     * @param array $args Query arguments.
     * @return array Array of invitation objects with related data.
     */
    public function get_invitations_with_details( $args = array() ) {
        $defaults = array(
            'orderby' => 'i.created_at',
            'order'   => 'DESC',
            'limit'   => 0,
            'offset'  => 0,
            'status'  => 'all' // all, pending, sent, viewed
        );

        $args = wp_parse_args( $args, $defaults );
        
        $invitations_table = $this->database->get_invitations_table();
        $guests_table = $this->database->get_guests_table();
        $events_table = $this->database->get_events_table();
        
        $query = "SELECT i.*, g.name as guest_name, g.email as guest_email, e.name as event_name 
                  FROM $invitations_table i 
                  LEFT JOIN $guests_table g ON i.guest_id = g.id 
                  LEFT JOIN $events_table e ON i.event_id = e.id";
        
        $query_args = array();

        // Add status filter
        if ( $args['status'] !== 'all' ) {
            $query .= " WHERE ";
            if ( $args['status'] === 'sent' ) {
                $query .= "i.is_sent = 1";
            } elseif ( $args['status'] === 'viewed' ) {
                $query .= "i.is_opened = 1";
            } elseif ( $args['status'] === 'pending' ) {
                $query .= "i.is_sent = 0";
            }
        }

        // Add ORDER BY
        $query .= " ORDER BY " . sanitize_sql_orderby( $args['orderby'] . ' ' . $args['order'] );

        // Add LIMIT
        if ( $args['limit'] > 0 ) {
            $query .= " LIMIT " . intval( $args['offset'] ) . ", " . intval( $args['limit'] );
        }

        return $this->database->get_results( $query, $query_args );
    }

    /**
     * Resend invitation.
     *
     * @param int $invitation_id Invitation ID.
     * @return bool True on success, false on failure.
     */
    public function resend_invitation( $invitation_id ) {
        // Get existing invitation
        $invitation = $this->get_invitation( $invitation_id );
        if ( ! $invitation ) {
            return false;
        }

        // Generate new token
        $new_token = $this->generate_unique_token();
        if ( ! $new_token ) {
            return false;
        }

        // Update invitation with new token and reset status
        $update_result = $this->update_invitation( $invitation_id, array(
            'unique_token' => $new_token,
            'is_sent' => 0,
            'is_opened' => 0,
            'sent_at' => null,
            'opened_at' => null,
            'status' => 'pending'
        ) );

        if ( ! $update_result ) {
            return false;
        }

        // Send the invitation
        return $this->send_invitation( $invitation_id );
    }

    /**
     * Get invitation by status.
     *
     * @param string $status Invitation status.
     * @param array  $args   Query arguments.
     * @return array Array of invitation objects.
     */
    public function get_invitations_by_status( $status, $args = array() ) {
        $defaults = array(
            'orderby' => 'created_at',
            'order'   => 'DESC',
            'limit'   => 0,
            'offset'  => 0
        );

        $args = wp_parse_args( $args, $defaults );
        $table = $this->database->get_invitations_table();
        
        $query = "SELECT * FROM $table WHERE status = %s";
        $query_args = array( $status );

        // Add ORDER BY
        $query .= " ORDER BY " . sanitize_sql_orderby( $args['orderby'] . ' ' . $args['order'] );

        // Add LIMIT
        if ( $args['limit'] > 0 ) {
            $query .= " LIMIT " . intval( $args['offset'] ) . ", " . intval( $args['limit'] );
        }

        return $this->database->get_results( $query, $query_args );
    }

    /**
     * Bulk update invitations.
     *
     * @param array $invitation_ids Array of invitation IDs.
     * @param array $data           Data to update.
     * @return array Results array.
     */
    public function bulk_update_invitations( $invitation_ids, $data ) {
        if ( empty( $invitation_ids ) || ! is_array( $invitation_ids ) || empty( $data ) ) {
            return array(
                'updated' => 0,
                'errors'  => array( __( 'بيانات غير صحيحة للتحديث الجماعي', 'invitation-manager-pro' ) )
            );
        }

        $updated = 0;
        $errors = array();
        $sanitized_data = $this->sanitize_invitation_data( $data );

        foreach ( $invitation_ids as $invitation_id ) {
            $result = $this->update_invitation( $invitation_id, $sanitized_data );
            if ( $result ) {
                $updated++;
            } else {
                $errors[] = sprintf( __( 'فشل في تحديث الدعوة ID: %d', 'invitation-manager-pro' ), $invitation_id );
            }
        }

        return array(
            'updated' => $updated,
            'errors'  => $errors
        );
    }

    /**
     * Bulk delete invitations.
     *
     * @param array $invitation_ids Array of invitation IDs.
     * @return array Results array.
     */
    public function bulk_delete_invitations( $invitation_ids ) {
        if ( empty( $invitation_ids ) || ! is_array( $invitation_ids ) ) {
            return array(
                'deleted' => 0,
                'errors'  => array( __( 'بيانات غير صحيحة للحذف الجماعي', 'invitation-manager-pro' ) )
            );
        }

        $deleted = 0;
        $errors = array();

        foreach ( $invitation_ids as $invitation_id ) {
            $result = $this->delete_invitation( $invitation_id );
            if ( $result ) {
                $deleted++;
            } else {
                $errors[] = sprintf( __( 'فشل في حذف الدعوة ID: %d', 'invitation-manager-pro' ), $invitation_id );
            }
        }

        return array(
            'deleted' => $deleted,
            'errors'  => $errors
        );
    }

    /**
     * Export invitations to CSV.
     *
     * @param array $invitation_ids Optional array of invitation IDs to export.
     * @return string|false CSV file path on success, false on failure.
     */
    public function export_invitations_to_csv( $invitation_ids = array() ) {
        if ( ! empty( $invitation_ids ) && is_array( $invitation_ids ) ) {
            $invitations = array();
            foreach ( $invitation_ids as $id ) {
                $invitation = $this->get_invitation( $id );
                if ( $invitation ) {
                    $invitations[] = $invitation;
                }
            }
        } else {
            $invitations = $this->get_all_invitations();
        }

        if ( empty( $invitations ) ) {
            return false;
        }

        $upload_dir = wp_upload_dir();
        $filename = 'invitations_export_' . date( 'Y-m-d_H-i-s' ) . '.csv';
        $filepath = $upload_dir['path'] . '/' . $filename;

        $handle = fopen( $filepath, 'w' );
        if ( ! $handle ) {
            return false;
        }

        // Write BOM for Arabic support
        fprintf( $handle, chr(0xEF).chr(0xBB).chr(0xBF) );
        
        // Write headers
        fputcsv( $handle, array(
            'ID', 'الضيف', 'البريد الإلكتروني', 'المناسبة', 'الحالة', 
            'مرسلة', 'مفتوحة', 'تاريخ الإنشاء', 'تاريخ الإرسال', 'تاريخ الفتح'
        ) );

        // Write data
        foreach ( $invitations as $invitation ) {
            $guest = null;
            $event = null;
            
            if ( isset( $invitation->guest_id ) ) {
                $guest_manager = new IMPRO_Guest_Manager();
                $guest = $guest_manager->get_guest( $invitation->guest_id );
            }
            
            if ( isset( $invitation->event_id ) ) {
                $event_manager = new IMPRO_Event_Manager();
                $event = $event_manager->get_event( $invitation->event_id );
            }

            fputcsv( $handle, array(
                $invitation->id,
                $guest ? $guest->name : __( 'غير محدد', 'invitation-manager-pro' ),
                $guest ? $guest->email : __( 'غير محدد', 'invitation-manager-pro' ),
                $event ? $event->name : __( 'غير محدد', 'invitation-manager-pro' ),
                $invitation->status,
                $invitation->is_sent ? __( 'نعم', 'invitation-manager-pro' ) : __( 'لا', 'invitation-manager-pro' ),
                $invitation->is_opened ? __( 'نعم', 'invitation-manager-pro' ) : __( 'لا', 'invitation-manager-pro' ),
                $invitation->created_at,
                $invitation->sent_at ?: __( 'لم ترسل', 'invitation-manager-pro' ),
                $invitation->opened_at ?: __( 'لم تفتح', 'invitation-manager-pro' )
            ) );
        }

        fclose( $handle );
        return $filepath;
    }

    /**
     * Import invitations from CSV.
     *
     * @param string $file_path Path to CSV file.
     * @return array Import results.
     */
    public function import_invitations_from_csv( $file_path ) {
        if ( ! file_exists( $file_path ) ) {
            return array(
                'success' => false,
                'message' => __( 'ملف CSV غير موجود', 'invitation-manager-pro' ),
                'imported' => 0,
                'errors' => array()
            );
        }

        $handle = fopen( $file_path, 'r' );
        if ( ! $handle ) {
            return array(
                'success' => false,
                'message' => __( 'فشل في فتح ملف CSV', 'invitation-manager-pro' ),
                'imported' => 0,
                'errors' => array()
            );
        }

        $imported = 0;
        $errors = array();
        $line_number = 0;

        // Read header row
        $headers = fgetcsv( $handle );
        if ( empty( $headers ) ) {
            fclose( $handle );
            return array(
                'success' => false,
                'message' => __( 'ملف CSV فارغ أو غير صحيح', 'invitation-manager-pro' ),
                'imported' => 0,
                'errors' => array()
            );
        }

        // Process data rows
        while ( ( $data = fgetcsv( $handle ) ) !== false ) {
            $line_number++;
            
            if ( count( $data ) !== count( $headers ) ) {
                $errors[] = sprintf( __( 'السطر %d: عدد الأعمدة غير متطابق', 'invitation-manager-pro' ), $line_number );
                continue;
            }

            $invitation_data = array_combine( $headers, $data );
            
            // Convert plus_one_allowed to boolean
            if ( isset( $invitation_data['plus_one_allowed'] ) ) {
                $invitation_data['plus_one_allowed'] = in_array( 
                    strtolower( $invitation_data['plus_one_allowed'] ), 
                    array( '1', 'yes', 'true', 'نعم' ) 
                ) ? 1 : 0;
            }

            $invitation_id = $this->create_invitation( $invitation_data );
            if ( $invitation_id ) {
                $imported++;
            } else {
                $errors[] = sprintf( __( 'السطر %d: فشل في إضافة الدعوة', 'invitation-manager-pro' ), $line_number );
            }
        }

        fclose( $handle );

        return array(
            'success' => true,
            'message' => sprintf( __( 'تم استيراد %d دعوة بنجاح', 'invitation-manager-pro' ), $imported ),
            'imported' => $imported,
            'errors' => $errors
        );
    }

    /**
     * Get invitation count by category.
     *
     * @return array Invitation count by category.
     */
    public function get_invitation_count_by_category() {
        $table = $this->database->get_invitations_table();
        $guests_table = $this->database->get_guests_table();
        
        $query = "SELECT g.category, COUNT(*) as count 
                  FROM $table i 
                  LEFT JOIN $guests_table g ON i.guest_id = g.id 
                  WHERE g.category IS NOT NULL 
                  GROUP BY g.category 
                  ORDER BY count DESC";
        
        return $this->database->get_results( $query );
    }

    /**
     * Get invitation count by status.
     *
     * @return array Invitation count by status.
     */
    public function get_invitation_count_by_status() {
        $table = $this->database->get_invitations_table();
        
        $query = "SELECT status, COUNT(*) as count 
                  FROM $table 
                  GROUP BY status 
                  ORDER BY count DESC";
        
        return $this->database->get_results( $query );
    }

    /**
     * Get invitation count by date range.
     *
     * @param string $start_date Start date (Y-m-d).
     * @param string $end_date   End date (Y-m-d).
     * @return array Invitation count by date.
     */
    public function get_invitation_count_by_date_range( $start_date, $end_date ) {
        $table = $this->database->get_invitations_table();
        
        $query = "SELECT DATE(created_at) as date, COUNT(*) as count 
                  FROM $table 
                  WHERE created_at BETWEEN %s AND %s 
                  GROUP BY DATE(created_at) 
                  ORDER BY date ASC";
        
        return $this->database->get_results( $query, array( $start_date, $end_date ) );
    }

    /**
     * Get recent invitations.
     *
     * @param int $limit Number of invitations to return.
     * @return array Array of recent invitation objects.
     */
    public function get_recent_invitations( $limit = 10 ) {
        return $this->get_all_invitations( array(
            'orderby' => 'created_at',
            'order'   => 'DESC',
            'limit'   => $limit
        ) );
    }

    /**
     * Get expired invitations.
     *
     * @return array Array of expired invitation objects.
     */
    public function get_expired_invitations() {
        $table = $this->database->get_invitations_table();
        $query = "SELECT * FROM $table 
                  WHERE expires_at < NOW() 
                  AND status != 'expired' 
                  ORDER BY expires_at ASC";
        
        return $this->database->get_results( $query );
    }

    /**
     * Mark expired invitations.
     *
     * @return int Number of invitations marked as expired.
     */
    public function mark_expired_invitations() {
        $table = $this->database->get_invitations_table();
        $query = "UPDATE $table 
                  SET status = 'expired', updated_at = NOW() 
                  WHERE expires_at < NOW() 
                  AND status != 'expired'";
        
        $result = $this->database->query( $query );
        return $result !== false ? intval( $result ) : 0;
    }

    /**
     * Get invitation statistics summary.
     *
     * @return array Invitation statistics summary.
     */
    public function get_invitation_statistics_summary() {
        $table = $this->database->get_invitations_table();
        
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN is_sent = 1 THEN 1 ELSE 0 END) as sent,
                    SUM(CASE WHEN is_opened = 1 THEN 1 ELSE 0 END) as opened,
                    SUM(CASE WHEN is_sent = 0 THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired
                  FROM $table";
        
        $result = $this->database->get_row( $query );
        
        if ( ! $result ) {
            return array(
                'total' => 0,
                'sent' => 0,
                'opened' => 0,
                'pending' => 0,
                'expired' => 0,
                'opened_rate' => 0,
                'sent_rate' => 0
            );
        }
        
        $total = intval( $result->total );
        $sent = intval( $result->sent );
        $opened = intval( $result->opened );
        
        return array(
            'total' => $total,
            'sent' => $sent,
            'opened' => $opened,
            'pending' => intval( $result->pending ),
            'expired' => intval( $result->expired ),
            'opened_rate' => $total > 0 ? round( ( $opened / $total ) * 100, 2 ) : 0,
            'sent_rate' => $total > 0 ? round( ( $sent / $total ) * 100, 2 ) : 0
        );
    }
}
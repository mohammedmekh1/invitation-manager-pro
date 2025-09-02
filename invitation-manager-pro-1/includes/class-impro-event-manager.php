<?php
/**
 * Event management class.
 *
 * @package Invitation_Manager_Pro
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * IMPRO_Event_Manager class.
 */
class IMPRO_Event_Manager {

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
     * Create a new event.
     *
     * @param array $event_data Event data.
     * @return int|false Event ID on success, false on failure.
     */
    public function create_event( $event_data ) {
        $sanitized_data = $this->sanitize_event_data( $event_data );
        
        if ( ! $this->validate_event_data( $sanitized_data ) ) {
            return false;
        }

        $table = $this->database->get_events_table();
        return $this->database->insert( $table, $sanitized_data );
    }

    /**
     * Update an existing event.
     *
     * @param int   $event_id   Event ID.
     * @param array $event_data Event data.
     * @return bool True on success, false on failure.
     */
    public function update_event( $event_id, $event_data ) {
        $sanitized_data = $this->sanitize_event_data( $event_data );
        
        if ( ! $this->validate_event_data( $sanitized_data ) ) {
            return false;
        }

        $table = $this->database->get_events_table();
        $result = $this->database->update( 
            $table, 
            $sanitized_data, 
            array( 'id' => $event_id ),
            null,
            array( '%d' )
        );

        return $result !== false;
    }

    /**
     * Delete an event.
     *
     * @param int $event_id Event ID.
     * @return bool True on success, false on failure.
     */
    public function delete_event( $event_id ) {
        // First delete related data
        $this->delete_event_related_data( $event_id );

        $table = $this->database->get_events_table();
        $result = $this->database->delete( 
            $table, 
            array( 'id' => $event_id ),
            array( '%d' )
        );

        return $result !== false;
    }

    /**
     * Get event by ID.
     *
     * @param int $event_id Event ID.
     * @return object|null Event object or null if not found.
     */
    public function get_event( $event_id ) {
        $table = $this->database->get_events_table();
        $query = "SELECT * FROM $table WHERE id = %d";
        return $this->database->get_row( $query, array( $event_id ) );
    }

    /**
     * Get all events.
     *
     * @param array $args Query arguments.
     * @return array Array of event objects.
     */
    public function get_events( $args = array() ) {
        $defaults = array(
            'orderby' => 'event_date',
            'order'   => 'ASC',
            'limit'   => 0,
            'offset'  => 0,
            'status'  => 'all' // all, upcoming, past
        );

        $args = wp_parse_args( $args, $defaults );
        $table = $this->database->get_events_table();
        
        $query = "SELECT * FROM $table";
        $where_conditions = array();
        $query_args = array();

        // Add status filter
        if ( $args['status'] === 'upcoming' ) {
            $where_conditions[] = "event_date >= CURDATE()";
        } elseif ( $args['status'] === 'past' ) {
            $where_conditions[] = "event_date < CURDATE()";
        }

        // Add WHERE clause if conditions exist
        if ( ! empty( $where_conditions ) ) {
            $query .= " WHERE " . implode( ' AND ', $where_conditions );
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
     * Get event statistics.
     *
     * @return array Event statistics.
     */
    public function get_event_statistics() {
        $table = $this->database->get_events_table();
        
        $total = $this->database->get_var( "SELECT COUNT(*) FROM $table" );
        $upcoming = $this->database->get_var( "SELECT COUNT(*) FROM $table WHERE event_date >= CURDATE()" );
        $past = $this->database->get_var( "SELECT COUNT(*) FROM $table WHERE event_date < CURDATE()" );

        return array(
            'total'    => intval( $total ),
            'upcoming' => intval( $upcoming ),
            'past'     => intval( $past )
        );
    }

    /**
     * Sanitize event data.
     *
     * @param array $data Raw event data.
     * @return array Sanitized event data.
     */
    private function sanitize_event_data( $data ) {
        $sanitized = array();

        if ( isset( $data['name'] ) ) {
            $sanitized['name'] = sanitize_text_field( $data['name'] );
        }

        if ( isset( $data['event_date'] ) ) {
            $sanitized['event_date'] = sanitize_text_field( $data['event_date'] );
        }

        if ( isset( $data['event_time'] ) ) {
            $sanitized['event_time'] = sanitize_text_field( $data['event_time'] );
        }

        if ( isset( $data['venue'] ) ) {
            $sanitized['venue'] = sanitize_text_field( $data['venue'] );
        }

        if ( isset( $data['address'] ) ) {
            $sanitized['address'] = sanitize_textarea_field( $data['address'] );
        }

        if ( isset( $data['description'] ) ) {
            $sanitized['description'] = sanitize_textarea_field( $data['description'] );
        }

        if ( isset( $data['invitation_image_url'] ) ) {
            $sanitized['invitation_image_url'] = esc_url_raw( $data['invitation_image_url'] );
        }

        if ( isset( $data['invitation_text'] ) ) {
            $sanitized['invitation_text'] = wp_kses_post( $data['invitation_text'] );
        }

        if ( isset( $data['location_details'] ) ) {
            $sanitized['location_details'] = sanitize_textarea_field( $data['location_details'] );
        }

        if ( isset( $data['contact_info'] ) ) {
            $sanitized['contact_info'] = sanitize_text_field( $data['contact_info'] );
        }

        return $sanitized;
    }

    /**
     * Validate event data.
     *
     * @param array $data Event data.
     * @return bool True if valid, false otherwise.
     */
    private function validate_event_data( $data ) {
        // Required fields
        if ( empty( $data['name'] ) ) {
            return false;
        }

        if ( empty( $data['event_date'] ) ) {
            return false;
        }

        if ( empty( $data['venue'] ) ) {
            return false;
        }

        // Validate date format
        if ( ! $this->validate_date( $data['event_date'] ) ) {
            return false;
        }

        // Validate time format if provided
        if ( ! empty( $data['event_time'] ) && ! $this->validate_time( $data['event_time'] ) ) {
            return false;
        }

        return true;
    }

    /**
     * Validate date format.
     *
     * @param string $date Date string.
     * @return bool True if valid, false otherwise.
     */
    private function validate_date( $date ) {
        $d = DateTime::createFromFormat( 'Y-m-d', $date );
        return $d && $d->format( 'Y-m-d' ) === $date;
    }

    /**
     * Validate time format.
     *
     * @param string $time Time string.
     * @return bool True if valid, false otherwise.
     */
    private function validate_time( $time ) {
        $t = DateTime::createFromFormat( 'H:i', $time );
        return $t && $t->format( 'H:i' ) === $time;
    }

    /**
     * Delete event related data.
     *
     * @param int $event_id Event ID.
     */
    private function delete_event_related_data( $event_id ) {
        // Delete RSVPs
        $rsvps_table = $this->database->get_rsvps_table();
        $this->database->delete( 
            $rsvps_table, 
            array( 'event_id' => $event_id ),
            array( '%d' )
        );

        // Delete invitations
        $invitations_table = $this->database->get_invitations_table();
        $this->database->delete( 
            $invitations_table, 
            array( 'event_id' => $event_id ),
            array( '%d' )
        );
    }

    /**
     * Get event guest count.
     *
     * @param int $event_id Event ID.
     * @return int Guest count.
     */
    public function get_event_guest_count( $event_id ) {
        $invitations_table = $this->database->get_invitations_table();
        $query = "SELECT COUNT(DISTINCT guest_id) FROM $invitations_table WHERE event_id = %d";
        return intval( $this->database->get_var( $query, array( $event_id ) ) );
    }

    /**
     * Get event RSVP statistics.
     *
     * @param int $event_id Event ID.
     * @return array RSVP statistics.
     */
    public function get_event_rsvp_statistics( $event_id ) {
        $rsvps_table = $this->database->get_rsvps_table();
        
        $accepted = $this->database->get_var( 
            "SELECT COUNT(*) FROM $rsvps_table WHERE event_id = %d AND status = 'accepted'",
            array( $event_id )
        );
        
        $declined = $this->database->get_var( 
            "SELECT COUNT(*) FROM $rsvps_table WHERE event_id = %d AND status = 'declined'",
            array( $event_id )
        );
        
        $pending = $this->database->get_var( 
            "SELECT COUNT(*) FROM $rsvps_table WHERE event_id = %d AND status = 'pending'",
            array( $event_id )
        );

        return array(
            'accepted' => intval( $accepted ),
            'declined' => intval( $declined ),
            'pending' => intval( $pending ),
        );
    }
}
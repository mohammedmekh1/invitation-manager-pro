<?php
/**
 * RSVP management class.
 *
 * @package Invitation_Manager_Pro
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * IMPRO_RSVP_Manager class.
 */
class IMPRO_RSVP_Manager {

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
     * Create or update RSVP.
     *
     * @param array $rsvp_data RSVP data.
     * @return int|false RSVP ID on success, false on failure.
     */
    public function save_rsvp( $rsvp_data ) {
        $sanitized_data = $this->sanitize_rsvp_data( $rsvp_data );
        
        if ( ! $this->validate_rsvp_data( $sanitized_data ) ) {
            return false;
        }

        // Check if RSVP already exists
        $existing_rsvp = $this->get_rsvp_by_guest_event( 
            $sanitized_data['guest_id'], 
            $sanitized_data['event_id'] 
        );

        $sanitized_data['response_date'] = current_time( 'mysql' );

        if ( $existing_rsvp ) {
            // Update existing RSVP
            $result = $this->update_rsvp( $existing_rsvp->id, $sanitized_data );
            return $result ? $existing_rsvp->id : false;
        } else {
            // Create new RSVP
            $table = $this->database->get_rsvps_table();
            return $this->database->insert( $table, $sanitized_data );
        }
    }

    /**
     * Update an existing RSVP.
     *
     * @param int   $rsvp_id   RSVP ID.
     * @param array $rsvp_data RSVP data.
     * @return bool True on success, false on failure.
     */
    public function update_rsvp( $rsvp_id, $rsvp_data ) {
        $sanitized_data = $this->sanitize_rsvp_data( $rsvp_data );

        $table = $this->database->get_rsvps_table();
        $result = $this->database->update( 
            $table, 
            $sanitized_data, 
            array( 'id' => $rsvp_id ),
            null,
            array( '%d' )
        );

        return $result !== false;
    }

    /**
     * Delete an RSVP.
     *
     * @param int $rsvp_id RSVP ID.
     * @return bool True on success, false on failure.
     */
    public function delete_rsvp( $rsvp_id ) {
        $table = $this->database->get_rsvps_table();
        $result = $this->database->delete( 
            $table, 
            array( 'id' => $rsvp_id ),
            array( '%d' )
        );

        return $result !== false;
    }

    /**
     * Get RSVP by ID.
     *
     * @param int $rsvp_id RSVP ID.
     * @return object|null RSVP object or null if not found.
     */
    public function get_rsvp( $rsvp_id ) {
        $table = $this->database->get_rsvps_table();
        $query = "SELECT * FROM $table WHERE id = %d";
        return $this->database->get_row( $query, array( $rsvp_id ) );
    }

    /**
     * Get RSVP by guest and event.
     *
     * @param int $guest_id Guest ID.
     * @param int $event_id Event ID.
     * @return object|null RSVP object or null if not found.
     */
    public function get_rsvp_by_guest_event( $guest_id, $event_id ) {
        $table = $this->database->get_rsvps_table();
        $query = "SELECT * FROM $table WHERE guest_id = %d AND event_id = %d";
        return $this->database->get_row( $query, array( $guest_id, $event_id ) );
    }

    /**
     * Get RSVPs for an event.
     *
     * @param int   $event_id Event ID.
     * @param array $args     Query arguments.
     * @return array Array of RSVP objects with guest information.
     */
    public function get_event_rsvps( $event_id, $args = array() ) {
        $defaults = array(
            'orderby' => 'response_date',
            'order'   => 'DESC',
            'limit'   => 0,
            'offset'  => 0,
            'status'  => 'all' // all, accepted, declined, pending
        );

        $args = wp_parse_args( $args, $defaults );
        
        $rsvps_table = $this->database->get_rsvps_table();
        $guests_table = $this->database->get_guests_table();
        
        $query = "SELECT r.*, g.name as guest_name, g.email as guest_email, g.phone as guest_phone 
                  FROM $rsvps_table r 
                  LEFT JOIN $guests_table g ON r.guest_id = g.id 
                  WHERE r.event_id = %d";
        
        $query_args = array( $event_id );

        // Add status filter
        if ( $args['status'] !== 'all' ) {
            $query .= " AND r.status = %s";
            $query_args[] = $args['status'];
        }

        // Add ORDER BY
        $query .= " ORDER BY r." . sanitize_sql_orderby( $args['orderby'] . ' ' . $args['order'] );

        // Add LIMIT
        if ( $args['limit'] > 0 ) {
            $query .= " LIMIT " . intval( $args['offset'] ) . ", " . intval( $args['limit'] );
        }

        return $this->database->get_results( $query, $query_args );
    }

    /**
     * Get RSVPs for a guest.
     *
     * @param int $guest_id Guest ID.
     * @return array Array of RSVP objects with event information.
     */
    public function get_guest_rsvps( $guest_id ) {
        $rsvps_table = $this->database->get_rsvps_table();
        $events_table = $this->database->get_events_table();
        
        $query = "SELECT r.*, e.name as event_name, e.event_date, e.venue 
                  FROM $rsvps_table r 
                  LEFT JOIN $events_table e ON r.event_id = e.id 
                  WHERE r.guest_id = %d 
                  ORDER BY r.response_date DESC";
        
        return $this->database->get_results( $query, array( $guest_id ) );
    }

    /**
     * Get RSVP statistics for an event.
     *
     * @param int $event_id Event ID.
     * @return array RSVP statistics.
     */
    public function get_event_rsvp_statistics( $event_id ) {
        $table = $this->database->get_rsvps_table();
        
        $total = $this->database->get_var( 
            "SELECT COUNT(*) FROM $table WHERE event_id = %d",
            array( $event_id )
        );
        
        $accepted = $this->database->get_var( 
            "SELECT COUNT(*) FROM $table WHERE event_id = %d AND status = 'accepted'",
            array( $event_id )
        );
        
        $declined = $this->database->get_var( 
            "SELECT COUNT(*) FROM $table WHERE event_id = %d AND status = 'declined'",
            array( $event_id )
        );
        
        $pending = $this->database->get_var( 
            "SELECT COUNT(*) FROM $table WHERE event_id = %d AND status = 'pending'",
            array( $event_id )
        );
        
        $plus_one_count = $this->database->get_var( 
            "SELECT COUNT(*) FROM $table WHERE event_id = %d AND plus_one_attending = 1",
            array( $event_id )
        );

        return array(
            'total'           => intval( $total ),
            'accepted'        => intval( $accepted ),
            'declined'        => intval( $declined ),
            'pending'         => intval( $pending ),
            'plus_one_count'  => intval( $plus_one_count ),
            'total_attending' => intval( $accepted ) + intval( $plus_one_count )
        );
    }

    /**
     * Get overall RSVP statistics.
     *
     * @return array Overall RSVP statistics.
     */
    public function get_overall_rsvp_statistics() {
        $table = $this->database->get_rsvps_table();
        
        $total = $this->database->get_var( "SELECT COUNT(*) FROM $table" );
        $accepted = $this->database->get_var( "SELECT COUNT(*) FROM $table WHERE status = 'accepted'" );
        $declined = $this->database->get_var( "SELECT COUNT(*) FROM $table WHERE status = 'declined'" );
        $pending = $this->database->get_var( "SELECT COUNT(*) FROM $table WHERE status = 'pending'" );
        $plus_one_count = $this->database->get_var( "SELECT COUNT(*) FROM $table WHERE plus_one_attending = 1" );

        return array(
            'total'           => intval( $total ),
            'accepted'        => intval( $accepted ),
            'declined'        => intval( $declined ),
            'pending'         => intval( $pending ),
            'plus_one_count'  => intval( $plus_one_count ),
            'total_attending' => intval( $accepted ) + intval( $plus_one_count )
        );
    }

    /**
     * Export RSVPs to CSV.
     *
     * @param int $event_id Optional event ID to filter by.
     * @return string|false CSV file path on success, false on failure.
     */
    public function export_rsvps_to_csv( $event_id = 0 ) {
        if ( $event_id > 0 ) {
            $rsvps = $this->get_event_rsvps( $event_id );
            $filename = 'event_' . $event_id . '_rsvps_export_' . date( 'Y-m-d_H-i-s' ) . '.csv';
        } else {
            $rsvps = $this->get_all_rsvps();
            $filename = 'all_rsvps_export_' . date( 'Y-m-d_H-i-s' ) . '.csv';
        }
        
        $upload_dir = wp_upload_dir();
        $filepath = $upload_dir['path'] . '/' . $filename;

        $handle = fopen( $filepath, 'w' );
        if ( ! $handle ) {
            return false;
        }

        // Write headers
        fputcsv( $handle, array(
            'guest_name', 'guest_email', 'guest_phone', 'status', 
            'plus_one_attending', 'plus_one_name', 'dietary_requirements', 'response_date'
        ) );

        // Write data
        foreach ( $rsvps as $rsvp ) {
            fputcsv( $handle, array(
                $rsvp->guest_name ?? '',
                $rsvp->guest_email ?? '',
                $rsvp->guest_phone ?? '',
                $rsvp->status,
                $rsvp->plus_one_attending ? 'نعم' : 'لا',
                $rsvp->plus_one_name ?? '',
                $rsvp->dietary_requirements ?? '',
                $rsvp->response_date ?? ''
            ) );
        }

        fclose( $handle );
        return $filepath;
    }

    /**
     * Get all RSVPs with guest and event information.
     *
     * @return array Array of RSVP objects.
     */
public function get_all_rsvps() {
        $rsvps_table = $this->database->get_rsvps_table();
        $guests_table = $this->database->get_guests_table();
        $events_table = $this->database->get_events_table();
        
        $query = "SELECT r.*, g.name as guest_name, g.email as guest_email, g.phone as guest_phone,
                         e.name as event_name, e.event_date 
                  FROM $rsvps_table r 
                  LEFT JOIN $guests_table g ON r.guest_id = g.id 
                  LEFT JOIN $events_table e ON r.event_id = e.id 
                  ORDER BY r.response_date DESC";
        
        return $this->database->get_results( $query );
    }

    /**
     * Sanitize RSVP data.
     *
     * @param array $data Raw RSVP data.
     * @return array Sanitized RSVP data.
     */
    private function sanitize_rsvp_data( $data ) {
        $sanitized = array();

        if ( isset( $data['guest_id'] ) ) {
            $sanitized['guest_id'] = intval( $data['guest_id'] );
        }

        if ( isset( $data['event_id'] ) ) {
            $sanitized['event_id'] = intval( $data['event_id'] );
        }

        if ( isset( $data['status'] ) ) {
            $allowed_statuses = array( 'pending', 'accepted', 'declined' );
            $status = sanitize_text_field( $data['status'] );
            $sanitized['status'] = in_array( $status, $allowed_statuses ) ? $status : 'pending';
        }

        if ( isset( $data['plus_one_attending'] ) ) {
            $sanitized['plus_one_attending'] = intval( $data['plus_one_attending'] );
        }

        if ( isset( $data['plus_one_name'] ) ) {
            $sanitized['plus_one_name'] = sanitize_text_field( $data['plus_one_name'] );
        }

        if ( isset( $data['dietary_requirements'] ) ) {
            $sanitized['dietary_requirements'] = sanitize_textarea_field( $data['dietary_requirements'] );
        }

        if ( isset( $data['response_date'] ) ) {
            $sanitized['response_date'] = sanitize_text_field( $data['response_date'] );
        }

        return $sanitized;
    }

    /**
     * Validate RSVP data.
     *
     * @param array $data RSVP data.
     * @return bool True if valid, false otherwise.
     */
    private function validate_rsvp_data( $data ) {
        // Required fields
        if ( empty( $data['guest_id'] ) || empty( $data['event_id'] ) ) {
            return false;
        }

        // Validate status
        if ( isset( $data['status'] ) ) {
            $allowed_statuses = array( 'pending', 'accepted', 'declined' );
            if ( ! in_array( $data['status'], $allowed_statuses ) ) {
                return false;
            }
        }

        return true;
    }
}


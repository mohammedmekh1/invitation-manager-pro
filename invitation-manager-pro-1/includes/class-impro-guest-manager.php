<?php
/**
 * Guest management class.
 *
 * @package Invitation_Manager_Pro
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * IMPRO_Guest_Manager class.
 */
class IMPRO_Guest_Manager {

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
     * Create a new guest.
     *
     * @param array $guest_data Guest data.
     * @return int|false Guest ID on success, false on failure.
     */
    public function create_guest( $guest_data ) {
        $sanitized_data = $this->sanitize_guest_data( $guest_data );
        
        if ( ! $this->validate_guest_data( $sanitized_data ) ) {
            return false;
        }

        $table = $this->database->get_guests_table();
        return $this->database->insert( $table, $sanitized_data );
    }

    /**
     * Update an existing guest.
     *
     * @param int   $guest_id   Guest ID.
     * @param array $guest_data Guest data.
     * @return bool True on success, false on failure.
     */
    public function update_guest( $guest_id, $guest_data ) {
        $sanitized_data = $this->sanitize_guest_data( $guest_data );
        
        if ( ! $this->validate_guest_data( $sanitized_data ) ) {
            return false;
        }

        $table = $this->database->get_guests_table();
        $result = $this->database->update( 
            $table, 
            $sanitized_data, 
            array( 'id' => $guest_id ),
            null,
            array( '%d' )
        );

        return $result !== false;
    }

    /**
     * Delete a guest.
     *
     * @param int $guest_id Guest ID.
     * @return bool True on success, false on failure.
     */
    public function delete_guest( $guest_id ) {
        // First delete related data
        $this->delete_guest_related_data( $guest_id );

        $table = $this->database->get_guests_table();
        $result = $this->database->delete( 
            $table, 
            array( 'id' => $guest_id ),
            array( '%d' )
        );

        return $result !== false;
    }

    /**
     * Get guest by ID.
     *
     * @param int $guest_id Guest ID.
     * @return object|null Guest object or null if not found.
     */
    public function get_guest( $guest_id ) {
        $table = $this->database->get_guests_table();
        $query = "SELECT * FROM $table WHERE id = %d";
        return $this->database->get_row( $query, array( $guest_id ) );
    }

    /**
     * Get guest by email.
     *
     * @param string $email Guest email.
     * @return object|null Guest object or null if not found.
     */
    public function get_guest_by_email( $email ) {
        $table = $this->database->get_guests_table();
        $query = "SELECT * FROM $table WHERE email = %s";
        return $this->database->get_row( $query, array( $email ) );
    }

    /**
     * Get all guests.
     *
     * @param array $args Query arguments.
     * @return array Array of guest objects.
     */
    public function get_guests( $args = array() ) {
        $defaults = array(
            'orderby'  => 'name',
            'order'    => 'ASC',
            'limit'    => 0,
            'offset'   => 0,
            'category' => '',
            'search'   => ''
        );

        $args = wp_parse_args( $args, $defaults );
        $table = $this->database->get_guests_table();
        
        $query = "SELECT * FROM $table";
        $where_conditions = array();
        $query_args = array();

        // Add category filter
        if ( ! empty( $args['category'] ) ) {
            $where_conditions[] = "category = %s";
            $query_args[] = $args['category'];
        }

        // Add search filter
        if ( ! empty( $args['search'] ) ) {
            $where_conditions[] = "(name LIKE %s OR email LIKE %s OR phone LIKE %s)";
            $search_term = '%' . $args['search'] . '%';
            $query_args[] = $search_term;
            $query_args[] = $search_term;
            $query_args[] = $search_term;
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
     * Get guest statistics.
     *
     * @return array Guest statistics.
     */
    public function get_guest_statistics() {
        $table = $this->database->get_guests_table();
        
        $total = $this->database->get_var( "SELECT COUNT(*) FROM $table" );
        $plus_one_allowed = $this->database->get_var( "SELECT COUNT(*) FROM $table WHERE plus_one_allowed = 1" );
        
        // Get categories
        $categories_query = "SELECT category, COUNT(*) as count FROM $table WHERE category != '' GROUP BY category";
        $categories = $this->database->get_results( $categories_query );

        return array(
            'total'            => intval( $total ),
            'plus_one_allowed' => intval( $plus_one_allowed ),
            'categories'       => $categories
        );
    }

    /**
     * Import guests from CSV.
     *
     * @param string $file_path Path to CSV file.
     * @return array Import results.
     */
    public function import_guests_from_csv( $file_path ) {
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

            $guest_data = array_combine( $headers, $data );
            
            // Convert plus_one_allowed to boolean
            if ( isset( $guest_data['plus_one_allowed'] ) ) {
                $guest_data['plus_one_allowed'] = in_array( 
                    strtolower( $guest_data['plus_one_allowed'] ), 
                    array( '1', 'yes', 'true', 'نعم' ) 
                ) ? 1 : 0;
            }

            $guest_id = $this->create_guest( $guest_data );
            if ( $guest_id ) {
                $imported++;
            } else {
                $errors[] = sprintf( __( 'السطر %d: فشل في إضافة الضيف', 'invitation-manager-pro' ), $line_number );
            }
        }

        fclose( $handle );

        return array(
            'success' => true,
            'message' => sprintf( __( 'تم استيراد %d ضيف بنجاح', 'invitation-manager-pro' ), $imported ),
            'imported' => $imported,
            'errors' => $errors
        );
    }

    /**
     * Export guests to CSV.
     *
     * @return string|false CSV file path on success, false on failure.
     */
    public function export_guests_to_csv() {
        $guests = $this->get_guests();
        
        $upload_dir = wp_upload_dir();
        $filename = 'guests_export_' . date( 'Y-m-d_H-i-s' ) . '.csv';
        $filepath = $upload_dir['path'] . '/' . $filename;

        $handle = fopen( $filepath, 'w' );
        if ( ! $handle ) {
            return false;
        }

        // Write headers
        fputcsv( $handle, array(
            'name', 'email', 'phone', 'category', 'plus_one_allowed', 
            'gender', 'age_range', 'relationship'
        ) );

        // Write data
        foreach ( $guests as $guest ) {
            fputcsv( $handle, array(
                $guest->name,
                $guest->email,
                $guest->phone,
                $guest->category,
                $guest->plus_one_allowed ? 'نعم' : 'لا',
                $guest->gender,
                $guest->age_range,
                $guest->relationship
            ) );
        }

        fclose( $handle );
        return $filepath;
    }

    /**
     * Sanitize guest data.
     *
     * @param array $data Raw guest data.
     * @return array Sanitized guest data.
     */
    private function sanitize_guest_data( $data ) {
        $sanitized = array();

        if ( isset( $data['name'] ) ) {
            $sanitized['name'] = sanitize_text_field( $data['name'] );
        }

        if ( isset( $data['email'] ) ) {
            $sanitized['email'] = sanitize_email( $data['email'] );
        }

        if ( isset( $data['phone'] ) ) {
            $sanitized['phone'] = sanitize_text_field( $data['phone'] );
        }

        if ( isset( $data['category'] ) ) {
            $sanitized['category'] = sanitize_text_field( $data['category'] );
        }

        if ( isset( $data['plus_one_allowed'] ) ) {
            $sanitized['plus_one_allowed'] = intval( $data['plus_one_allowed'] );
        }

        if ( isset( $data['gender'] ) ) {
            $sanitized['gender'] = sanitize_text_field( $data['gender'] );
        }

        if ( isset( $data['age_range'] ) ) {
            $sanitized['age_range'] = sanitize_text_field( $data['age_range'] );
        }

        if ( isset( $data['relationship'] ) ) {
            $sanitized['relationship'] = sanitize_text_field( $data['relationship'] );
        }

        return $sanitized;
    }

    /**
     * Validate guest data.
     *
     * @param array $data Guest data.
     * @return bool True if valid, false otherwise.
     */
    private function validate_guest_data( $data ) {
        // Required fields
        if ( empty( $data['name'] ) ) {
            return false;
        }

        // Validate email if provided
        if ( ! empty( $data['email'] ) && ! is_email( $data['email'] ) ) {
            return false;
        }

        return true;
    }

    /**
     * Delete guest related data.
     *
     * @param int $guest_id Guest ID.
     */
    private function delete_guest_related_data( $guest_id ) {
        // Delete RSVPs
        $rsvps_table = $this->database->get_rsvps_table();
        $this->database->delete( 
            $rsvps_table, 
            array( 'guest_id' => $guest_id ),
            array( '%d' )
        );

        // Delete invitations
        $invitations_table = $this->database->get_invitations_table();
        $this->database->delete( 
            $invitations_table, 
            array( 'guest_id' => $guest_id ),
            array( '%d' )
        );
    }

    /**
     * Get guests by event.
     *
     * @param int $event_id Event ID.
     * @return array Array of guest objects.
     */
    public function get_guests_by_event( $event_id ) {
        $guests_table = $this->database->get_guests_table();
        $invitations_table = $this->database->get_invitations_table();
        
        $query = "SELECT g.* FROM $guests_table g 
                  INNER JOIN $invitations_table i ON g.id = i.guest_id 
                  WHERE i.event_id = %d 
                  ORDER BY g.name ASC";
        
        return $this->database->get_results( $query, array( $event_id ) );
    }
}


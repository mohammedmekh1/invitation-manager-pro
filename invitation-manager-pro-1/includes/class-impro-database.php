
<?php
/**
 * Database management class.
 *
 * @package Invitation_Manager_Pro
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * IMPRO_Database class.
 */
class IMPRO_Database {

    /**
     * WordPress database object.
     *
     * @var wpdb
     */
    private $wpdb;

    /**
     * Constructor.
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Get table names.
     */
    public function get_events_table() {
        return $this->wpdb->prefix . 'impro_events';
    }

    public function get_guests_table() {
        return $this->wpdb->prefix . 'impro_guests';
    }

    public function get_invitations_table() {
        return $this->wpdb->prefix . 'impro_invitations';
    }

    public function get_rsvps_table() {
        return $this->wpdb->prefix . 'impro_rsvps';
    }

    /**
     * Get all table names.
     *
     * @return array Array of table names.
     */
    public function get_table_names() {
        return array(
            'events' => $this->get_events_table(),
            'guests' => $this->get_guests_table(),
            'invitations' => $this->get_invitations_table(),
            'rsvps' => $this->get_rsvps_table()
        );
    }

    /**
     * Get table name by key.
     *
     * @param string $key Table key.
     * @return string Table name.
     */
    public function get_table_name( $key ) {
        $tables = $this->get_table_names();
        return isset( $tables[ $key ] ) ? $tables[ $key ] : '';
    }

    /**
     * Check if table exists.
     *
     * @param string $table_key Table key.
     * @return bool True if table exists, false otherwise.
     */
    public function table_exists( $table_key ) {
        $table_name = $this->get_table_name( $table_key );
        if ( empty( $table_name ) ) {
            return false;
        }
        
        $query = $this->wpdb->prepare( 
            "SHOW TABLES LIKE %s", 
            $table_name 
        );
        
        return $this->wpdb->get_var( $query ) === $table_name;
    }

    /**
     * Create database tables.
     *
     * @return bool True on success, false on failure.
     */
    public function create_tables() {
        $charset_collate = $this->wpdb->get_charset_collate();

        // Events table
        $events_table = $this->get_events_table();
        $sql_events = "CREATE TABLE $events_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            event_date date NOT NULL,
            event_time time DEFAULT '00:00:00' NOT NULL,
            venue varchar(255) NOT NULL,
            address text,
            description text,
            invitation_image_url varchar(500),
            invitation_text longtext,
            location_details text,
            contact_info varchar(255),
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            KEY idx_event_date (event_date),
            KEY idx_created_at (created_at)
        ) $charset_collate;";

        // Guests table
        $guests_table = $this->get_guests_table();
        $sql_guests = "CREATE TABLE $guests_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            email varchar(255),
            phone varchar(50),
            category varchar(100),
            plus_one_allowed tinyint(1) DEFAULT 0 NOT NULL,
            gender varchar(20),
            age_range varchar(50),
            relationship varchar(100),
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            KEY idx_email (email),
            KEY idx_category (category),
            KEY idx_created_at (created_at)
        ) $charset_collate;";

        // Invitations table (بدون مراجعات مفاتيح خارجية لتجنب المشاكل في البيئات المشتركة)
        $invitations_table = $this->get_invitations_table();
        $sql_invitations = "CREATE TABLE $invitations_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            guest_id mediumint(9) NOT NULL,
            event_id mediumint(9) NOT NULL,
            unique_token varchar(255) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            is_sent tinyint(1) DEFAULT 0 NOT NULL,
            is_opened tinyint(1) DEFAULT 0 NOT NULL,
            sent_at datetime NULL DEFAULT NULL,
            opened_at datetime NULL DEFAULT NULL,
            expires_at datetime NULL DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_token (unique_token),
            KEY idx_guest_id (guest_id),
            KEY idx_event_id (event_id),
            KEY idx_status (status),
            KEY idx_created_at (created_at)
        ) $charset_collate;";

        // RSVPs table (بدون مراجعات مفاتيح خارجية لتجنب المشاكل في البيئات المشتركة)
        $rsvps_table = $this->get_rsvps_table();
        $sql_rsvps = "CREATE TABLE $rsvps_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            guest_id mediumint(9) NOT NULL,
            event_id mediumint(9) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            plus_one_attending tinyint(1) DEFAULT 0 NOT NULL,
            plus_one_name varchar(255),
            dietary_requirements text,
            response_date datetime NULL DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            KEY idx_guest_id (guest_id),
            KEY idx_event_id (event_id),
            KEY idx_status (status),
            KEY idx_response_date (response_date),
            UNIQUE KEY guest_event (guest_id, event_id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        
        // تنفيذ إنشاء الجداول
        $result1 = dbDelta( $sql_events );
        $result2 = dbDelta( $sql_guests );
        $result3 = dbDelta( $sql_invitations );
        $result4 = dbDelta( $sql_rsvps );
        
        // التحقق من النجاح
        $success = true;
        
        // التحقق من وجود الجداول
        $required_tables = array( 'events', 'guests', 'invitations', 'rsvps' );
        foreach ( $required_tables as $table_key ) {
            if ( ! $this->table_exists( $table_key ) ) {
                error_log( 'Failed to create table: ' . $table_key );
                $success = false;
            }
        }
        
        return $success;
    }

    /**
     * Drop database tables.
     *
     * @return bool True on success, false on failure.
     */
    public function drop_tables() {
        $tables = array(
            $this->get_rsvps_table(),
            $this->get_invitations_table(),
            $this->get_guests_table(),
            $this->get_events_table()
        );

        $success = true;
        
        foreach ( $tables as $table ) {
            if ( ! empty( $table ) ) {
                $result = $this->wpdb->query( "DROP TABLE IF EXISTS `$table`" );
                if ( $result === false ) {
                    error_log( 'Failed to drop table: ' . $table );
                    $success = false;
                }
            }
        }
        
        return $success;
    }

    /**
     * Prepare SQL query safely.
     *
     * @param string $query SQL query with placeholders.
     * @param array  $args  Arguments to replace placeholders.
     * @return string Prepared SQL query.
     */
    public function prepare_query( $query, $args = array() ) {
        if ( empty( $args ) ) {
            return $query;
        }
        
        // التحقق من صحة المعطيات
        if ( ! is_array( $args ) ) {
            $args = array( $args );
        }
        
        return $this->wpdb->prepare( $query, $args );
    }

    /**
     * Execute a prepared query.
     *
     * @param string $query SQL query.
     * @param array  $args  Query arguments.
     * @return mixed Query result.
     */
    public function execute_query( $query, $args = array() ) {
        try {
            $prepared_query = $this->prepare_query( $query, $args );
            return $this->wpdb->query( $prepared_query );
        } catch ( Exception $e ) {
            error_log( 'Database query execution failed: ' . $e->getMessage() );
            error_log( 'Query: ' . $query );
            return false;
        }
    }

    /**
     * Get results from a prepared query.
     *
     * @param string $query SQL query.
     * @param array  $args  Query arguments.
     * @return array Query results.
     */
    public function get_results( $query, $args = array() ) {
        try {
            $prepared_query = $this->prepare_query( $query, $args );
            $results = $this->wpdb->get_results( $prepared_query );
            return is_array( $results ) ? $results : array();
        } catch ( Exception $e ) {
            error_log( 'Database get_results failed: ' . $e->getMessage() );
            error_log( 'Query: ' . $query );
            return array();
        }
    }

    /**
     * Get a single row from a prepared query.
     *
     * @param string $query SQL query.
     * @param array  $args  Query arguments.
     * @return object|null Single row result.
     */
    public function get_row( $query, $args = array() ) {
        try {
            $prepared_query = $this->prepare_query( $query, $args );
            return $this->wpdb->get_row( $prepared_query );
        } catch ( Exception $e ) {
            error_log( 'Database get_row failed: ' . $e->getMessage() );
            error_log( 'Query: ' . $query );
            return null;
        }
    }

    /**
     * Get a single variable from a prepared query.
     *
     * @param string $query SQL query.
     * @param array  $args  Query arguments.
     * @return string|null Single variable result.
     */
    public function get_var( $query, $args = array() ) {
        try {
            $prepared_query = $this->prepare_query( $query, $args );
            return $this->wpdb->get_var( $prepared_query );
        } catch ( Exception $e ) {
            error_log( 'Database get_var failed: ' . $e->getMessage() );
            error_log( 'Query: ' . $query );
            return null;
        }
    }

    /**
     * Insert data into a table.
     *
     * @param string $table Table name.
     * @param array  $data  Data to insert.
     * @param array  $format Data format.
     * @return int|false Insert ID on success, false on failure.
     */
    public function insert( $table, $data, $format = null ) {
        // التحقق من صحة البيانات
        if ( empty( $table ) || empty( $data ) || ! is_array( $data ) ) {
            error_log( 'Invalid data for insert operation' );
            return false;
        }
        
        try {
            $result = $this->wpdb->insert( $table, $data, $format );
            return $result ? $this->wpdb->insert_id : false;
        } catch ( Exception $e ) {
            error_log( 'Database insert failed: ' . $e->getMessage() );
            error_log( 'Table: ' . $table );
            error_log( 'Data: ' . print_r( $data, true ) );
            return false;
        }
    }

    /**
     * Update data in a table.
     *
     * @param string $table  Table name.
     * @param array  $data   Data to update.
     * @param array  $where  WHERE conditions.
     * @param array  $format Data format.
     * @param array  $where_format WHERE format.
     * @return int|false Number of rows updated, false on failure.
     */
    public function update( $table, $data, $where, $format = null, $where_format = null ) {
        // التحقق من صحة البيانات
        if ( empty( $table ) || empty( $data ) || empty( $where ) ) {
            error_log( 'Invalid data for update operation' );
            return false;
        }
        
        try {
            return $this->wpdb->update( $table, $data, $where, $format, $where_format );
        } catch ( Exception $e ) {
            error_log( 'Database update failed: ' . $e->getMessage() );
            error_log( 'Table: ' . $table );
            error_log( 'Data: ' . print_r( $data, true ) );
            error_log( 'Where: ' . print_r( $where, true ) );
            return false;
        }
    }

    /**
     * Delete data from a table.
     *
     * @param string $table  Table name.
     * @param array  $where  WHERE conditions.
     * @param array  $where_format WHERE format.
     * @return int|false Number of rows deleted, false on failure.
     */
    public function delete( $table, $where, $where_format = null ) {
        // التحقق من صحة البيانات
        if ( empty( $table ) || empty( $where ) ) {
            error_log( 'Invalid data for delete operation' );
            return false;
        }
        
        try {
            return $this->wpdb->delete( $table, $where, $where_format );
        } catch ( Exception $e ) {
            error_log( 'Database delete failed: ' . $e->getMessage() );
            error_log( 'Table: ' . $table );
            error_log( 'Where: ' . print_r( $where, true ) );
            return false;
        }
    }

    /**
     * Get database size.
     *
     * @return array Database size information.
     */
    public function get_database_size() {
        $tables = $this->get_table_names();
        $total_size = 0;
        $table_sizes = array();
        
        foreach ( $tables as $key => $table_name ) {
            $size = $this->wpdb->get_var( 
                $this->wpdb->prepare(
                    "SELECT ROUND(((data_length + index_length) / 1024 / 1024), 2) 
                     FROM information_schema.TABLES 
                     WHERE table_schema = %s AND table_name = %s",
                    DB_NAME,
                    $table_name
                )
            );
            
            $table_sizes[ $key ] = floatval( $size );
            $total_size += $table_sizes[ $key ];
        }
        
        return array(
            'total_size_mb' => round( $total_size, 2 ),
            'tables' => $table_sizes
        );
    }

    /**
     * Optimize database tables.
     *
     * @return bool True on success, false on failure.
     */
    public function optimize_tables() {
        $tables = $this->get_table_names();
        $success = true;
        
        foreach ( $tables as $table_name ) {
            $result = $this->wpdb->query( "OPTIMIZE TABLE `$table_name`" );
            if ( $result === false ) {
                error_log( 'Failed to optimize table: ' . $table_name );
                $success = false;
            }
        }
        
        return $success;
    }

    /**
     * Check database connection.
     *
     * @return bool True if connected, false otherwise.
     */
    public function check_connection() {
        return ! empty( $this->wpdb->dbh );
    }

    /**
     * Get database version.
     *
     * @return string Database version.
     */
    public function get_version() {
        return $this->wpdb->db_version();
    }

    /**
     * Escape string for database.
     *
     * @param string $string String to escape.
     * @return string Escaped string.
     */
    public function escape( $string ) {
        return $this->wpdb->_escape( $string );
    }

    /**
     * Quote string for database.
     *
     * @param string $string String to quote.
     * @return string Quoted string.
     */
    public function quote( $string ) {
        return "'" . $this->escape( $string ) . "'";
    }
}

<?php
/**
 * Database migration management class.
 *
 * @package Invitation_Manager_Pro
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * IMPRO_Migration class.
 */
class IMPRO_Migration {

    /**
     * Migration version option key.
     */
    const VERSION_OPTION = 'impro_migration_version';

    /**
     * Current migration version.
     */
    const CURRENT_VERSION = '1.0.0';

    /**
     * Database instance.
     *
     * @var IMPRO_Database
     */
    private $database;

    /**
     * WordPress database instance.
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
        $this->database = new IMPRO_Database();
    }

    /**
     * Run migrations.
     *
     * @return bool True on success, false on failure.
     */
    public function run_migrations() {
        $current_version = get_option( self::VERSION_OPTION, '0.0.0' );
        
        if ( version_compare( $current_version, self::CURRENT_VERSION, '>=' ) ) {
            return true; // Already up to date
        }

        $migrations = $this->get_migrations();
        
        foreach ( $migrations as $version => $migration ) {
            if ( version_compare( $current_version, $version, '<' ) ) {
                if ( ! $this->run_migration( $version, $migration ) ) {
                    return false;
                }
                update_option( self::VERSION_OPTION, $version );
                $current_version = $version;
            }
        }

        return true;
    }

    /**
     * Get all migrations.
     *
     * @return array Migrations array.
     */
    private function get_migrations() {
        return array(
            '1.0.0' => array(
                'description' => 'Initial database setup',
                'up' => array( $this, 'migration_1_0_0_up' ),
                'down' => array( $this, 'migration_1_0_0_down' )
            )
        );
    }

    /**
     * Run single migration.
     *
     * @param string $version Migration version.
     * @param array  $migration Migration data.
     * @return bool True on success, false on failure.
     */
    private function run_migration( $version, $migration ) {
        if ( ! isset( $migration['up'] ) || ! is_callable( $migration['up'] ) ) {
            return false;
        }

        try {
            $result = call_user_func( $migration['up'] );
            
            if ( $result ) {
                $this->log_migration( $version, 'up', 'success' );
                return true;
            } else {
                $this->log_migration( $version, 'up', 'failed' );
                return false;
            }
        } catch ( Exception $e ) {
            $this->log_migration( $version, 'up', 'error', $e->getMessage() );
            return false;
        }
    }

    /**
     * Rollback migration.
     *
     * @param string $version Migration version.
     * @return bool True on success, false on failure.
     */
    public function rollback_migration( $version ) {
        $migrations = $this->get_migrations();
        
        if ( ! isset( $migrations[ $version ] ) ) {
            return false;
        }

        $migration = $migrations[ $version ];
        
        if ( ! isset( $migration['down'] ) || ! is_callable( $migration['down'] ) ) {
            return false;
        }

        try {
            $result = call_user_func( $migration['down'] );
            
            if ( $result ) {
                $this->log_migration( $version, 'down', 'success' );
                
                // Update version to previous
                $previous_version = $this->get_previous_version( $version );
                update_option( self::VERSION_OPTION, $previous_version );
                
                return true;
            } else {
                $this->log_migration( $version, 'down', 'failed' );
                return false;
            }
        } catch ( Exception $e ) {
            $this->log_migration( $version, 'down', 'error', $e->getMessage() );
            return false;
        }
    }

    /**
     * Migration 1.0.0 up.
     *
     * @return bool True on success, false on failure.
     */
    public function migration_1_0_0_up() {
        // Create all tables
        $tables_created = $this->database->create_tables();
        
        if ( ! $tables_created ) {
            return false;
        }

        // Create indexes
        $this->create_database_indexes();
        
        // Insert default data
        $this->insert_default_data();
        
        return true;
    }

    /**
     * Migration 1.0.0 down.
     *
     * @return bool True on success, false on failure.
     */
    public function migration_1_0_0_down() {
        // Drop all tables
        return $this->database->drop_tables();
    }

    /**
     * Create database indexes.
     */
    private function create_database_indexes() {
        $tables = $this->database->get_table_names();
        
        // Events table indexes
        $this->create_index( $tables['events'], 'idx_event_date', 'event_date' );
        $this->create_index( $tables['events'], 'idx_created_at', 'created_at' );
        
        // Guests table indexes
        $this->create_index( $tables['guests'], 'idx_email', 'email' );
        $this->create_index( $tables['guests'], 'idx_category', 'category' );
        $this->create_index( $tables['guests'], 'idx_created_at', 'created_at' );
        
        // Invitations table indexes
        $this->create_index( $tables['invitations'], 'idx_token', 'token' );
        $this->create_index( $tables['invitations'], 'idx_event_guest', 'event_id, guest_id' );
        $this->create_index( $tables['invitations'], 'idx_status', 'status' );
        $this->create_index( $tables['invitations'], 'idx_created_at', 'created_at' );
        
        // RSVPs table indexes
        $this->create_index( $tables['rsvps'], 'idx_event_guest', 'event_id, guest_id' );
        $this->create_index( $tables['rsvps'], 'idx_status', 'status' );
        $this->create_index( $tables['rsvps'], 'idx_response_date', 'response_date' );
    }

    /**
     * Create database index.
     *
     * @param string $table Table name.
     * @param string $index_name Index name.
     * @param string $columns Columns to index.
     */
    private function create_index( $table, $index_name, $columns ) {
        // Check if index exists
        $index_exists = $this->wpdb->get_var( 
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
                 WHERE table_schema = %s AND table_name = %s AND index_name = %s",
                DB_NAME,
                $table,
                $index_name
            )
        );
        
        if ( ! $index_exists ) {
            $this->wpdb->query( 
                "ALTER TABLE {$table} ADD INDEX {$index_name} ({$columns})"
            );
        }
    }

    /**
     * Insert default data.
     */
    private function insert_default_data() {
        // Insert default guest categories
        $this->insert_default_guest_categories();
        
        // Insert sample data if in development mode
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            $this->insert_sample_data();
        }
    }

    /**
     * Insert default guest categories.
     */
    private function insert_default_guest_categories() {
        $categories = array(
            array(
                'name' => __( 'العائلة', 'invitation-manager-pro' ),
                'slug' => 'family',
                'description' => __( 'أفراد العائلة والأقارب', 'invitation-manager-pro' )
            ),
            array(
                'name' => __( 'الأصدقاء', 'invitation-manager-pro' ),
                'slug' => 'friends',
                'description' => __( 'الأصدقاء المقربون', 'invitation-manager-pro' )
            ),
            array(
                'name' => __( 'زملاء العمل', 'invitation-manager-pro' ),
                'slug' => 'colleagues',
                'description' => __( 'زملاء العمل والمهنة', 'invitation-manager-pro' )
            ),
            array(
                'name' => __( 'ضيوف مميزون', 'invitation-manager-pro' ),
                'slug' => 'vip',
                'description' => __( 'الضيوف المميزون والشخصيات المهمة', 'invitation-manager-pro' )
            ),
            array(
                'name' => __( 'أخرى', 'invitation-manager-pro' ),
                'slug' => 'other',
                'description' => __( 'فئات أخرى', 'invitation-manager-pro' )
            )
        );

        foreach ( $categories as $category ) {
            // Check if category already exists
            $exists = get_option( 'impro_category_' . $category['slug'] );
            if ( ! $exists ) {
                update_option( 'impro_category_' . $category['slug'], $category );
            }
        }
    }

    /**
     * Insert sample data for development.
     */
    private function insert_sample_data() {
        $query_builder = new IMPRO_Query_Builder();
        
        // Sample event
        $event_id = $query_builder->table( 'events' )->insert( array(
            'name' => 'حفل زفاف أحمد وفاطمة',
            'event_date' => date( 'Y-m-d', strtotime( '+30 days' ) ),
            'event_time' => '19:00:00',
            'venue' => 'قاعة الأفراح الكبرى',
            'address' => 'شارع الملك فهد، الرياض',
            'description' => 'يسعدنا دعوتكم لحضور حفل زفاف أحمد وفاطمة',
            'invitation_text' => 'بسم الله الرحمن الرحيم<br>يسعدنا دعوتكم لحضور حفل زفاف أحمد وفاطمة',
            'contact_info' => '0501234567',
            'created_at' => current_time( 'mysql' ),
            'updated_at' => current_time( 'mysql' )
        ) );

        if ( $event_id ) {
            // Sample guests
            $guests = array(
                array(
                    'name' => 'محمد أحمد',
                    'email' => 'mohammed@example.com',
                    'phone' => '0501111111',
                    'category' => 'family',
                    'plus_one_allowed' => 1,
                    'gender' => 'male',
                    'age_range' => 'adult',
                    'relationship' => 'أخ',
                    'created_at' => current_time( 'mysql' ),
                    'updated_at' => current_time( 'mysql' )
                ),
                array(
                    'name' => 'سارة محمد',
                    'email' => 'sara@example.com',
                    'phone' => '0502222222',
                    'category' => 'friends',
                    'plus_one_allowed' => 0,
                    'gender' => 'female',
                    'age_range' => 'adult',
                    'relationship' => 'صديقة',
                    'created_at' => current_time( 'mysql' ),
                    'updated_at' => current_time( 'mysql' )
                ),
                array(
                    'name' => 'عبدالله سالم',
                    'email' => 'abdullah@example.com',
                    'phone' => '0503333333',
                    'category' => 'colleagues',
                    'plus_one_allowed' => 1,
                    'gender' => 'male',
                    'age_range' => 'adult',
                    'relationship' => 'زميل عمل',
                    'created_at' => current_time( 'mysql' ),
                    'updated_at' => current_time( 'mysql' )
                )
            );

            foreach ( $guests as $guest_data ) {
                $guest_id = $query_builder->table( 'guests' )->insert( $guest_data );
                
                if ( $guest_id ) {
                    // Create invitation
                    $invitation_manager = new IMPRO_Invitation_Manager();
                    $invitation_manager->create_invitation( $event_id, $guest_id );
                }
            }
        }
    }

    /**
     * Get previous version.
     *
     * @param string $current_version Current version.
     * @return string Previous version.
     */
    private function get_previous_version( $current_version ) {
        $migrations = $this->get_migrations();
        $versions = array_keys( $migrations );
        
        $current_index = array_search( $current_version, $versions );
        
        if ( $current_index > 0 ) {
            return $versions[ $current_index - 1 ];
        }
        
        return '0.0.0';
    }

    /**
     * Log migration.
     *
     * @param string $version Migration version.
     * @param string $direction Migration direction (up/down).
     * @param string $status Migration status.
     * @param string $error Error message (optional).
     */
    private function log_migration( $version, $direction, $status, $error = '' ) {
        $log_entry = array(
            'version' => $version,
            'direction' => $direction,
            'status' => $status,
            'timestamp' => current_time( 'mysql' ),
            'error' => $error
        );

        $migration_log = get_option( 'impro_migration_log', array() );
        $migration_log[] = $log_entry;
        
        // Keep only last 50 entries
        if ( count( $migration_log ) > 50 ) {
            $migration_log = array_slice( $migration_log, -50 );
        }
        
        update_option( 'impro_migration_log', $migration_log );
        
        // Also log to error log if failed
        if ( $status !== 'success' ) {
            error_log( sprintf(
                'IMPRO Migration %s %s %s: %s',
                $version,
                $direction,
                $status,
                $error
            ) );
        }
    }

    /**
     * Get migration status.
     *
     * @return array Migration status.
     */
    public function get_migration_status() {
        $current_version = get_option( self::VERSION_OPTION, '0.0.0' );
        $migrations = $this->get_migrations();
        
        $status = array(
            'current_version' => $current_version,
            'latest_version' => self::CURRENT_VERSION,
            'needs_migration' => version_compare( $current_version, self::CURRENT_VERSION, '<' ),
            'migrations' => array()
        );

        foreach ( $migrations as $version => $migration ) {
            $status['migrations'][ $version ] = array(
                'version' => $version,
                'description' => $migration['description'],
                'applied' => version_compare( $current_version, $version, '>=' ),
                'can_rollback' => isset( $migration['down'] ) && is_callable( $migration['down'] )
            );
        }

        return $status;
    }

    /**
     * Get migration log.
     *
     * @param int $limit Number of entries to return.
     * @return array Migration log entries.
     */
    public function get_migration_log( $limit = 20 ) {
        $migration_log = get_option( 'impro_migration_log', array() );
        
        // Sort by timestamp descending
        usort( $migration_log, function( $a, $b ) {
            return strtotime( $b['timestamp'] ) - strtotime( $a['timestamp'] );
        } );
        
        return array_slice( $migration_log, 0, $limit );
    }

    /**
     * Clear migration log.
     *
     * @return bool True on success, false on failure.
     */
    public function clear_migration_log() {
        return delete_option( 'impro_migration_log' );
    }

    /**
     * Backup database before migration.
     *
     * @return string|false Backup file path or false on failure.
     */
    public function backup_database() {
        $upload_dir = wp_upload_dir();
        $backup_dir = $upload_dir['basedir'] . '/invitation-manager-pro/backups';
        
        if ( ! file_exists( $backup_dir ) ) {
            wp_mkdir_p( $backup_dir );
        }

        $backup_file = $backup_dir . '/backup_' . date( 'Y-m-d_H-i-s' ) . '.sql';
        $tables = $this->database->get_table_names();
        
        $sql_dump = '';
        
        foreach ( $tables as $table ) {
            // Get table structure
            $create_table = $this->wpdb->get_row( "SHOW CREATE TABLE {$table}", ARRAY_N );
            if ( $create_table ) {
                $sql_dump .= "\n\n" . $create_table[1] . ";\n\n";
            }
            
            // Get table data
            $rows = $this->wpdb->get_results( "SELECT * FROM {$table}", ARRAY_A );
            
            if ( $rows ) {
                foreach ( $rows as $row ) {
                    $values = array();
                    foreach ( $row as $value ) {
                        $values[] = "'" . $this->wpdb->_escape( $value ) . "'";
                    }
                    $sql_dump .= "INSERT INTO {$table} VALUES (" . implode( ', ', $values ) . ");\n";
                }
            }
        }

        if ( file_put_contents( $backup_file, $sql_dump ) !== false ) {
            return $backup_file;
        }

        return false;
    }

    /**
     * Restore database from backup.
     *
     * @param string $backup_file Backup file path.
     * @return bool True on success, false on failure.
     */
    public function restore_database( $backup_file ) {
        if ( ! file_exists( $backup_file ) ) {
            return false;
        }

        $sql_content = file_get_contents( $backup_file );
        
        if ( ! $sql_content ) {
            return false;
        }

        // Split SQL into individual queries
        $queries = explode( ';', $sql_content );
        
        foreach ( $queries as $query ) {
            $query = trim( $query );
            if ( ! empty( $query ) ) {
                $result = $this->wpdb->query( $query );
                if ( $result === false ) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Check database integrity.
     *
     * @return array Integrity check results.
     */
    public function check_database_integrity() {
        $results = array(
            'tables_exist' => true,
            'indexes_exist' => true,
            'foreign_keys_valid' => true,
            'data_consistent' => true,
            'issues' => array()
        );

        // Check if all tables exist
        $required_tables = array( 'events', 'guests', 'invitations', 'rsvps' );
        foreach ( $required_tables as $table_key ) {
            if ( ! $this->database->table_exists( $table_key ) ) {
                $results['tables_exist'] = false;
                $results['issues'][] = sprintf( __( 'الجدول %s غير موجود', 'invitation-manager-pro' ), $table_key );
            }
        }

        // Check indexes
        $required_indexes = array(
            'events' => array( 'idx_event_date', 'idx_created_at' ),
            'guests' => array( 'idx_email', 'idx_category' ),
            'invitations' => array( 'idx_token', 'idx_event_guest', 'idx_status' ),
            'rsvps' => array( 'idx_event_guest', 'idx_status', 'idx_response_date' )
        );

        foreach ( $required_indexes as $table_key => $indexes ) {
            $table_name = $this->database->get_table_name( $table_key );
            foreach ( $indexes as $index ) {
                $index_exists = $this->wpdb->get_var( 
                    $this->wpdb->prepare(
                        "SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
                         WHERE table_schema = %s AND table_name = %s AND index_name = %s",
                        DB_NAME,
                        $table_name,
                        $index
                    )
                );
                
                if ( ! $index_exists ) {
                    $results['indexes_exist'] = false;
                    $results['issues'][] = sprintf( __( 'الفهرس %s غير موجود في الجدول %s', 'invitation-manager-pro' ), $index, $table_key );
                }
            }
        }

        // Check data consistency
        $this->check_data_consistency( $results );

        return $results;
    }

    /**
     * Check data consistency.
     *
     * @param array &$results Results array to update.
     */
    private function check_data_consistency( &$results ) {
        $query_builder = new IMPRO_Query_Builder();

        // Check for orphaned invitations
        $orphaned_invitations = $query_builder
            ->table( 'invitations' )
            ->left_join( 'events', 'invitations.event_id', '=', 'events.id' )
            ->left_join( 'guests', 'invitations.guest_id', '=', 'guests.id' )
            ->where_null( 'events.id' )
            ->or_where( 'guests.id', null )
            ->count();

        if ( $orphaned_invitations > 0 ) {
            $results['data_consistent'] = false;
            $results['issues'][] = sprintf( __( 'يوجد %d دعوة يتيمة', 'invitation-manager-pro' ), $orphaned_invitations );
        }

        // Check for orphaned RSVPs
        $orphaned_rsvps = $query_builder
            ->table( 'rsvps' )
            ->left_join( 'events', 'rsvps.event_id', '=', 'events.id' )
            ->left_join( 'guests', 'rsvps.guest_id', '=', 'guests.id' )
            ->where_null( 'events.id' )
            ->or_where( 'guests.id', null )
            ->count();

        if ( $orphaned_rsvps > 0 ) {
            $results['data_consistent'] = false;
            $results['issues'][] = sprintf( __( 'يوجد %d رد يتيم', 'invitation-manager-pro' ), $orphaned_rsvps );
        }

        // Check for duplicate invitation tokens
        $duplicate_tokens = $query_builder
            ->table( 'invitations' )
            ->select( 'token' )
            ->group_by( 'token' )
            ->having( 'COUNT(*)', '>', 1 )
            ->count();

        if ( $duplicate_tokens > 0 ) {
            $results['data_consistent'] = false;
            $results['issues'][] = sprintf( __( 'يوجد %d رمز دعوة مكرر', 'invitation-manager-pro' ), $duplicate_tokens );
        }
    }

    /**
     * Fix database issues.
     *
     * @return array Fix results.
     */
    public function fix_database_issues() {
        $results = array(
            'fixed' => 0,
            'failed' => 0,
            'details' => array()
        );

        $query_builder = new IMPRO_Query_Builder();

        // Fix orphaned invitations
        $deleted_invitations = $query_builder
            ->table( 'invitations' )
            ->left_join( 'events', 'invitations.event_id', '=', 'events.id' )
            ->left_join( 'guests', 'invitations.guest_id', '=', 'guests.id' )
            ->where_null( 'events.id' )
            ->or_where( 'guests.id', null )
            ->delete();

        if ( $deleted_invitations > 0 ) {
            $results['fixed'] += $deleted_invitations;
            $results['details'][] = sprintf( __( 'تم حذف %d دعوة يتيمة', 'invitation-manager-pro' ), $deleted_invitations );
        }

        // Fix orphaned RSVPs
        $deleted_rsvps = $query_builder
            ->table( 'rsvps' )
            ->left_join( 'events', 'rsvps.event_id', '=', 'events.id' )
            ->left_join( 'guests', 'rsvps.guest_id', '=', 'guests.id' )
            ->where_null( 'events.id' )
            ->or_where( 'guests.id', null )
            ->delete();

        if ( $deleted_rsvps > 0 ) {
            $results['fixed'] += $deleted_rsvps;
            $results['details'][] = sprintf( __( 'تم حذف %d رد يتيم', 'invitation-manager-pro' ), $deleted_rsvps );
        }

        // Fix duplicate tokens
        $duplicate_invitations = $query_builder
            ->table( 'invitations' )
            ->select( 'token, MIN(id) as keep_id' )
            ->group_by( 'token' )
            ->having( 'COUNT(*)', '>', 1 )
            ->get();

        foreach ( $duplicate_invitations as $duplicate ) {
            $deleted = $query_builder
                ->table( 'invitations' )
                ->where( 'token', $duplicate->token )
                ->where( 'id', '!=', $duplicate->keep_id )
                ->delete();

            if ( $deleted > 0 ) {
                $results['fixed'] += $deleted;
                $results['details'][] = sprintf( __( 'تم حذف %d دعوة مكررة للرمز %s', 'invitation-manager-pro' ), $deleted, $duplicate->token );
            }
        }

        return $results;
    }
}


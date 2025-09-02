<?php
/**
 * Plugin installation and setup class.
 *
 * @package Invitation_Manager_Pro
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * IMPRO_Installation class.
 */
class IMPRO_Installation {

    /**
     * Plugin activation.
     */
    public static function activate() {
        // Create database tables
        self::create_database_tables();
        
        // Create required pages
        self::create_required_pages();
        
        // Set default options
        self::set_default_options();
        
        // Create upload directories
        self::create_upload_directories();
        
        // Set user capabilities
        self::set_user_capabilities();
        
        // Schedule cron jobs
        self::schedule_cron_jobs();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set activation flag
        update_option( 'impro_activated', true );
        update_option( 'impro_version', IMPRO_VERSION );
    }

    /**
     * Plugin deactivation.
     */
    public static function deactivate() {
        // Clear scheduled cron jobs
        self::clear_cron_jobs();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set deactivation flag
        update_option( 'impro_activated', false );
    }

    /**
     * Plugin uninstall.
     */
    public static function uninstall() {
        // Check if user wants to keep data
        if ( get_option( 'impro_keep_data_on_uninstall', false ) ) {
            return;
        }

        // Drop database tables
        self::drop_database_tables();
        
        // Delete pages
        self::delete_required_pages();
        
        // Delete options
        self::delete_plugin_options();
        
        // Remove user capabilities
        self::remove_user_capabilities();
        
        // Clear cron jobs
        self::clear_cron_jobs();
        
        // Delete upload directories
        self::delete_upload_directories();
    }

    /**
     * Create database tables.
     */
    private static function create_database_tables() {
        $database = new IMPRO_Database();
        $database->create_tables();
    }

    /**
     * Drop database tables.
     */
    private static function drop_database_tables() {
        $database = new IMPRO_Database();
        $database->drop_tables();
    }

    /**
     * Create required pages.
     */
    private static function create_required_pages() {
        $pages = array(
            'invitation' => array(
                'title'   => __( 'صفحة الدعوة', 'invitation-manager-pro' ),
                'content' => '[impro_invitation_page]',
                'slug'    => 'invitation'
            ),
            'rsvp' => array(
                'title'   => __( 'تأكيد الحضور', 'invitation-manager-pro' ),
                'content' => '[impro_rsvp_form]',
                'slug'    => 'rsvp'
            )
        );

        foreach ( $pages as $key => $page_data ) {
            $existing_page = get_page_by_path( $page_data['slug'] );
            
            if ( ! $existing_page ) {
                $page_id = wp_insert_post( array(
                    'post_title'   => $page_data['title'],
                    'post_content' => $page_data['content'],
                    'post_name'    => $page_data['slug'],
                    'post_status'  => 'publish',
                    'post_type'    => 'page'
                ) );

                if ( $page_id ) {
                    update_option( 'impro_' . $key . '_page_id', $page_id );
                }
            } else {
                update_option( 'impro_' . $key . '_page_id', $existing_page->ID );
            }
        }
    }

    /**
     * Delete required pages.
     */
    private static function delete_required_pages() {
        $page_options = array(
            'impro_invitation_page_id',
            'impro_rsvp_page_id'
        );

        foreach ( $page_options as $option ) {
            $page_id = get_option( $option );
            if ( $page_id ) {
                wp_delete_post( $page_id, true );
                delete_option( $option );
            }
        }
    }

    /**
     * Set default options.
     */
    private static function set_default_options() {
        $default_options = array(
            'impro_enable_guest_comments'   => 1,
            'impro_enable_plus_one'         => 1,
            'impro_default_guests_limit'    => 200,
            'impro_invitation_expiry'       => 30,
            'impro_enable_email'            => 1,
            'impro_email_subject'           => __( 'دعوة لحضور {event_name}', 'invitation-manager-pro' ),
            'impro_email_template'          => self::get_default_email_template(),
            'impro_notification_emails'     => get_option( 'admin_email' ),
            'impro_qr_code_size'           => 200,
            'impro_enable_qr_codes'        => 1,
            'impro_keep_data_on_uninstall' => false
        );

        foreach ( $default_options as $option => $value ) {
            if ( get_option( $option ) === false ) {
                update_option( $option, $value );
            }
        }
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
     * Delete plugin options.
     */
    private static function delete_plugin_options() {
        global $wpdb;
        
        // Delete all plugin options
        $wpdb->query( 
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'impro_%'"
        );
        
        // Delete transients
        $wpdb->query( 
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_impro_%' OR option_name LIKE '_transient_timeout_impro_%'"
        );
    }

    /**
     * Create upload directories.
     */
    private static function create_upload_directories() {
        $upload_dir = wp_upload_dir();
        $plugin_dirs = array(
            'invitation-manager-pro',
            'invitation-manager-pro/qr-codes',
            'invitation-manager-pro/exports',
            'invitation-manager-pro/images'
        );

        foreach ( $plugin_dirs as $dir ) {
            $full_path = $upload_dir['basedir'] . '/' . $dir;
            if ( ! file_exists( $full_path ) ) {
                wp_mkdir_p( $full_path );
                
                // Create .htaccess file for security
                $htaccess_content = "Options -Indexes\nDeny from all";
                file_put_contents( $full_path . '/.htaccess', $htaccess_content );
            }
        }
    }

    /**
     * Delete upload directories.
     */
    private static function delete_upload_directories() {
        $upload_dir = wp_upload_dir();
        $plugin_dir = $upload_dir['basedir'] . '/invitation-manager-pro';
        
        if ( file_exists( $plugin_dir ) ) {
            self::delete_directory( $plugin_dir );
        }
    }

    /**
     * Recursively delete directory.
     *
     * @param string $dir Directory path.
     */
    private static function delete_directory( $dir ) {
        if ( ! is_dir( $dir ) ) {
            return;
        }

        $files = array_diff( scandir( $dir ), array( '.', '..' ) );
        
        foreach ( $files as $file ) {
            $path = $dir . '/' . $file;
            if ( is_dir( $path ) ) {
                self::delete_directory( $path );
            } else {
                unlink( $path );
            }
        }
        
        rmdir( $dir );
    }

    /**
     * Set user capabilities.
     */
    private static function set_user_capabilities() {
        $capabilities = array(
            'manage_events'      => array( 'administrator', 'editor' ),
            'manage_guests'      => array( 'administrator', 'editor', 'author' ),
            'manage_invitations' => array( 'administrator', 'editor', 'author' ),
            'view_statistics'    => array( 'administrator', 'editor' ),
            'export_data'        => array( 'administrator' )
        );

        foreach ( $capabilities as $cap => $roles ) {
            foreach ( $roles as $role_name ) {
                $role = get_role( $role_name );
                if ( $role ) {
                    $role->add_cap( $cap );
                }
            }
        }
    }

    /**
     * Remove user capabilities.
     */
    private static function remove_user_capabilities() {
        $capabilities = array(
            'manage_events',
            'manage_guests',
            'manage_invitations',
            'view_statistics',
            'export_data'
        );

        $roles = array( 'administrator', 'editor', 'author', 'contributor', 'subscriber' );

        foreach ( $capabilities as $cap ) {
            foreach ( $roles as $role_name ) {
                $role = get_role( $role_name );
                if ( $role ) {
                    $role->remove_cap( $cap );
                }
            }
        }
    }

    /**
     * Schedule cron jobs.
     */
    private static function schedule_cron_jobs() {
        // Daily cleanup
        if ( ! wp_next_scheduled( 'impro_daily_cleanup' ) ) {
            wp_schedule_event( time(), 'daily', 'impro_daily_cleanup' );
        }

        // Weekly statistics
        if ( ! wp_next_scheduled( 'impro_weekly_stats' ) ) {
            wp_schedule_event( time(), 'weekly', 'impro_weekly_stats' );
        }
    }

    /**
     * Clear cron jobs.
     */
    private static function clear_cron_jobs() {
        $cron_jobs = array(
            'impro_daily_cleanup',
            'impro_weekly_stats'
        );

        foreach ( $cron_jobs as $job ) {
            $timestamp = wp_next_scheduled( $job );
            if ( $timestamp ) {
                wp_unschedule_event( $timestamp, $job );
            }
        }
    }

    /**
     * Check if plugin needs update.
     *
     * @return bool True if update needed, false otherwise.
     */
    public static function needs_update() {
        $installed_version = get_option( 'impro_version', '0.0.0' );
        return version_compare( $installed_version, IMPRO_VERSION, '<' );
    }

    /**
     * Update plugin.
     */
    public static function update() {
        $installed_version = get_option( 'impro_version', '0.0.0' );

        // Run version-specific updates
        if ( version_compare( $installed_version, '1.0.0', '<' ) ) {
            self::update_to_1_0_0();
        }

        // Update version
        update_option( 'impro_version', IMPRO_VERSION );
    }

    /**
     * Update to version 1.0.0.
     */
    private static function update_to_1_0_0() {
        // Initial release - no specific updates needed
        // This method is a placeholder for future updates
    }

    /**
     * Get system requirements.
     *
     * @return array System requirements.
     */
    public static function get_system_requirements() {
        return array(
            'php_version'        => '7.4',
            'wordpress_version'  => '5.0',
            'mysql_version'      => '5.6',
            'required_extensions' => array( 'gd', 'curl', 'json' ),
            'recommended_memory' => '128M'
        );
    }

    /**
     * Check system requirements.
     *
     * @return array Requirements check results.
     */
    public static function check_system_requirements() {
        $requirements = self::get_system_requirements();
        $results = array();

        // Check PHP version
        $results['php_version'] = array(
            'required' => $requirements['php_version'],
            'current'  => PHP_VERSION,
            'status'   => version_compare( PHP_VERSION, $requirements['php_version'], '>=' )
        );

        // Check WordPress version
        global $wp_version;
        $results['wordpress_version'] = array(
            'required' => $requirements['wordpress_version'],
            'current'  => $wp_version,
            'status'   => version_compare( $wp_version, $requirements['wordpress_version'], '>=' )
        );

        // Check PHP extensions
        $results['extensions'] = array();
        foreach ( $requirements['required_extensions'] as $extension ) {
            $results['extensions'][ $extension ] = extension_loaded( $extension );
        }

        // Check memory limit
        $memory_limit = ini_get( 'memory_limit' );
        $results['memory_limit'] = array(
            'recommended' => $requirements['recommended_memory'],
            'current'     => $memory_limit,
            'status'      => self::compare_memory_limit( $memory_limit, $requirements['recommended_memory'] )
        );

        return $results;
    }

    /**
     * Compare memory limits.
     *
     * @param string $current     Current memory limit.
     * @param string $recommended Recommended memory limit.
     * @return bool True if current meets recommended, false otherwise.
     */
    private static function compare_memory_limit( $current, $recommended ) {
        $current_bytes = self::convert_to_bytes( $current );
        $recommended_bytes = self::convert_to_bytes( $recommended );
        
        return $current_bytes >= $recommended_bytes;
    }

    /**
     * Convert memory limit to bytes.
     *
     * @param string $limit Memory limit string.
     * @return int Memory limit in bytes.
     */
    private static function convert_to_bytes( $limit ) {
        $limit = trim( $limit );
        $last = strtolower( $limit[ strlen( $limit ) - 1 ] );
        $number = (int) $limit;

        switch ( $last ) {
            case 'g':
                $number *= 1024;
            case 'm':
                $number *= 1024;
            case 'k':
                $number *= 1024;
        }

        return $number;
    }
}


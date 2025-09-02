<?php
/**
 * Performance optimization class.
 *
 * @package Invitation_Manager_Pro
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * IMPRO_Performance class.
 */
class IMPRO_Performance {

    /**
     * Constructor.
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize performance hooks.
     */
    private function init_hooks() {
        // Database optimization
        add_action( 'init', array( $this, 'optimize_database_queries' ) );
        
        // Asset optimization
        add_action( 'wp_enqueue_scripts', array( $this, 'optimize_frontend_assets' ), 999 );
        add_action( 'admin_enqueue_scripts', array( $this, 'optimize_admin_assets' ), 999 );
        
        // Image optimization
        add_filter( 'wp_generate_attachment_metadata', array( $this, 'optimize_uploaded_images' ), 10, 2 );
        
        // Cache management
        add_action( 'impro_cache_cleanup', array( $this, 'cleanup_expired_cache' ) );
        
        // Database maintenance
        add_action( 'impro_daily_cleanup', array( $this, 'daily_database_cleanup' ) );
        
        // Performance monitoring
        add_action( 'shutdown', array( $this, 'log_performance_metrics' ) );
    }

    /**
     * Optimize database queries.
     */
    public function optimize_database_queries() {
        // Add database indexes if they don't exist
        $this->ensure_database_indexes();
        
        // Optimize query caching
        $this->setup_query_caching();
    }

    /**
     * Ensure database indexes exist for better performance.
     */
    private function ensure_database_indexes() {
        global $wpdb;
        
        $database = new IMPRO_Database();
        $tables = $database->get_table_names();
        
        // Events table indexes
        $this->add_index_if_not_exists( 
            $tables['events'], 
            'idx_event_date', 
            'event_date' 
        );
        $this->add_index_if_not_exists( 
            $tables['events'], 
            'idx_created_at', 
            'created_at' 
        );
        
        // Guests table indexes
        $this->add_index_if_not_exists( 
            $tables['guests'], 
            'idx_email', 
            'email' 
        );
        $this->add_index_if_not_exists( 
            $tables['guests'], 
            'idx_category', 
            'category' 
        );
        
        // Invitations table indexes
        $this->add_index_if_not_exists( 
            $tables['invitations'], 
            'idx_token', 
            'token' 
        );
        $this->add_index_if_not_exists( 
            $tables['invitations'], 
            'idx_event_guest', 
            'event_id, guest_id' 
        );
        $this->add_index_if_not_exists( 
            $tables['invitations'], 
            'idx_status', 
            'status' 
        );
        
        // RSVPs table indexes
        $this->add_index_if_not_exists( 
            $tables['rsvps'], 
            'idx_event_guest', 
            'event_id, guest_id' 
        );
        $this->add_index_if_not_exists( 
            $tables['rsvps'], 
            'idx_status', 
            'status' 
        );
        $this->add_index_if_not_exists( 
            $tables['rsvps'], 
            'idx_response_date', 
            'response_date' 
        );
    }

    /**
     * Add database index if it doesn't exist.
     *
     * @param string $table Table name.
     * @param string $index_name Index name.
     * @param string $columns Columns to index.
     */
    private function add_index_if_not_exists( $table, $index_name, $columns ) {
        global $wpdb;
        
        // Check if index exists
        $index_exists = $wpdb->get_var( 
            $wpdb->prepare(
                "SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
                 WHERE table_schema = %s AND table_name = %s AND index_name = %s",
                DB_NAME,
                $table,
                $index_name
            )
        );
        
        if ( ! $index_exists ) {
            $wpdb->query( 
                "ALTER TABLE {$table} ADD INDEX {$index_name} ({$columns})"
            );
        }
    }

    /**
     * Setup query caching.
     */
    private function setup_query_caching() {
        // Enable query caching for frequently used queries
        add_filter( 'posts_pre_query', array( $this, 'maybe_cache_posts_query' ), 10, 2 );
    }

    /**
     * Maybe cache posts query.
     *
     * @param array    $posts Array of posts.
     * @param WP_Query $query WP_Query object.
     * @return array|null Posts array or null to continue with normal query.
     */
    public function maybe_cache_posts_query( $posts, $query ) {
        // Only cache specific queries to avoid conflicts
        if ( ! $query->is_main_query() || is_admin() ) {
            return $posts;
        }
        
        // Generate cache key
        $cache_key = md5( serialize( $query->query_vars ) );
        
        // Try to get cached result
        $cached_posts = IMPRO_Cache::get( 'posts_query_' . $cache_key, 'general' );
        
        if ( false !== $cached_posts ) {
            return $cached_posts;
        }
        
        return $posts;
    }

    /**
     * Optimize frontend assets.
     */
    public function optimize_frontend_assets() {
        if ( ! $this->is_invitation_page() ) {
            return;
        }
        
        // Minify CSS if not already minified
        $this->maybe_minify_css();
        
        // Minify JavaScript if not already minified
        $this->maybe_minify_js();
        
        // Defer non-critical JavaScript
        add_filter( 'script_loader_tag', array( $this, 'defer_non_critical_scripts' ), 10, 3 );
        
        // Preload critical resources
        add_action( 'wp_head', array( $this, 'preload_critical_resources' ), 1 );
    }

    /**
     * Optimize admin assets.
     */
    public function optimize_admin_assets() {
        $screen = get_current_screen();
        
        if ( ! $screen || strpos( $screen->id, 'impro' ) === false ) {
            return;
        }
        
        // Combine admin CSS files
        $this->combine_admin_css();
        
        // Combine admin JS files
        $this->combine_admin_js();
    }

    /**
     * Maybe minify CSS.
     */
    private function maybe_minify_css() {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            return; // Don't minify in debug mode
        }
        
        $css_file = IMPRO_PATH . 'assets/css/public.css';
        $minified_file = IMPRO_PATH . 'assets/css/public.min.css';
        
        if ( ! file_exists( $minified_file ) || filemtime( $css_file ) > filemtime( $minified_file ) ) {
            $css_content = file_get_contents( $css_file );
            $minified_css = $this->minify_css( $css_content );
            file_put_contents( $minified_file, $minified_css );
        }
        
        // Dequeue original and enqueue minified
        wp_dequeue_style( 'impro-public-style' );
        wp_enqueue_style(
            'impro-public-style-min',
            IMPRO_URL . 'assets/css/public.min.css',
            array(),
            IMPRO_VERSION
        );
    }

    /**
     * Maybe minify JavaScript.
     */
    private function maybe_minify_js() {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            return; // Don't minify in debug mode
        }
        
        $js_file = IMPRO_PATH . 'assets/js/public.js';
        $minified_file = IMPRO_PATH . 'assets/js/public.min.js';
        
        if ( ! file_exists( $minified_file ) || filemtime( $js_file ) > filemtime( $minified_file ) ) {
            $js_content = file_get_contents( $js_file );
            $minified_js = $this->minify_js( $js_content );
            file_put_contents( $minified_file, $minified_js );
        }
        
        // Dequeue original and enqueue minified
        wp_dequeue_script( 'impro-public-script' );
        wp_enqueue_script(
            'impro-public-script-min',
            IMPRO_URL . 'assets/js/public.min.js',
            array( 'jquery' ),
            IMPRO_VERSION,
            true
        );
    }

    /**
     * Minify CSS content.
     *
     * @param string $css CSS content.
     * @return string Minified CSS.
     */
    private function minify_css( $css ) {
        // Remove comments
        $css = preg_replace( '/\/\*.*?\*\//s', '', $css );
        
        // Remove whitespace
        $css = preg_replace( '/\s+/', ' ', $css );
        
        // Remove unnecessary spaces
        $css = str_replace( array( '; ', ' {', '{ ', ' }', '} ', ': ', ', ' ), 
                           array( ';', '{', '{', '}', '}', ':', ',' ), $css );
        
        return trim( $css );
    }

    /**
     * Minify JavaScript content.
     *
     * @param string $js JavaScript content.
     * @return string Minified JavaScript.
     */
    private function minify_js( $js ) {
        // Remove single-line comments (but preserve URLs)
        $js = preg_replace( '/(?<!:)\/\/.*$/m', '', $js );
        
        // Remove multi-line comments
        $js = preg_replace( '/\/\*.*?\*\//s', '', $js );
        
        // Remove extra whitespace
        $js = preg_replace( '/\s+/', ' ', $js );
        
        // Remove spaces around operators
        $js = preg_replace( '/\s*([{}();,=+\-*\/])\s*/', '$1', $js );
        
        return trim( $js );
    }

    /**
     * Defer non-critical scripts.
     *
     * @param string $tag Script tag.
     * @param string $handle Script handle.
     * @param string $src Script source.
     * @return string Modified script tag.
     */
    public function defer_non_critical_scripts( $tag, $handle, $src ) {
        // List of scripts to defer
        $defer_scripts = array(
            'impro-public-script',
            'impro-public-script-min'
        );
        
        if ( in_array( $handle, $defer_scripts ) ) {
            return str_replace( '<script ', '<script defer ', $tag );
        }
        
        return $tag;
    }

    /**
     * Preload critical resources.
     */
    public function preload_critical_resources() {
        // Preload critical CSS
        echo '<link rel="preload" href="' . esc_url( IMPRO_URL . 'assets/css/public.css' ) . '" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">' . "\n";
        
        // Preload critical fonts if any
        $this->preload_fonts();
    }

    /**
     * Preload fonts.
     */
    private function preload_fonts() {
        // Add font preloading if custom fonts are used
        // Example:
        // echo '<link rel="preload" href="' . esc_url( IMPRO_URL . 'assets/fonts/custom-font.woff2' ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
    }

    /**
     * Combine admin CSS files.
     */
    private function combine_admin_css() {
        $combined_file = IMPRO_PATH . 'assets/css/admin-combined.css';
        $css_files = array(
            IMPRO_PATH . 'assets/css/admin.css'
        );
        
        $latest_mtime = 0;
        foreach ( $css_files as $file ) {
            if ( file_exists( $file ) ) {
                $latest_mtime = max( $latest_mtime, filemtime( $file ) );
            }
        }
        
        if ( ! file_exists( $combined_file ) || filemtime( $combined_file ) < $latest_mtime ) {
            $combined_css = '';
            foreach ( $css_files as $file ) {
                if ( file_exists( $file ) ) {
                    $combined_css .= file_get_contents( $file ) . "\n";
                }
            }
            file_put_contents( $combined_file, $combined_css );
        }
    }

    /**
     * Combine admin JS files.
     */
    private function combine_admin_js() {
        $combined_file = IMPRO_PATH . 'assets/js/admin-combined.js';
        $js_files = array(
            IMPRO_PATH . 'assets/js/admin.js'
        );
        
        $latest_mtime = 0;
        foreach ( $js_files as $file ) {
            if ( file_exists( $file ) ) {
                $latest_mtime = max( $latest_mtime, filemtime( $file ) );
            }
        }
        
        if ( ! file_exists( $combined_file ) || filemtime( $combined_file ) < $latest_mtime ) {
            $combined_js = '';
            foreach ( $js_files as $file ) {
                if ( file_exists( $file ) ) {
                    $combined_js .= file_get_contents( $file ) . "\n";
                }
            }
            file_put_contents( $combined_js, $combined_js );
        }
    }

    /**
     * Optimize uploaded images.
     *
     * @param array $metadata Image metadata.
     * @param int   $attachment_id Attachment ID.
     * @return array Modified metadata.
     */
    public function optimize_uploaded_images( $metadata, $attachment_id ) {
        if ( ! isset( $metadata['file'] ) ) {
            return $metadata;
        }
        
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['basedir'] . '/' . $metadata['file'];
        
        // Optimize main image
        $this->optimize_image_file( $file_path );
        
        // Optimize thumbnails
        if ( isset( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
            $base_dir = dirname( $file_path );
            foreach ( $metadata['sizes'] as $size_data ) {
                $thumb_path = $base_dir . '/' . $size_data['file'];
                $this->optimize_image_file( $thumb_path );
            }
        }
        
        return $metadata;
    }

    /**
     * Optimize image file.
     *
     * @param string $file_path Image file path.
     */
    private function optimize_image_file( $file_path ) {
        if ( ! file_exists( $file_path ) ) {
            return;
        }
        
        $image_info = getimagesize( $file_path );
        if ( ! $image_info ) {
            return;
        }
        
        $mime_type = $image_info['mime'];
        
        switch ( $mime_type ) {
            case 'image/jpeg':
                $this->optimize_jpeg( $file_path );
                break;
            case 'image/png':
                $this->optimize_png( $file_path );
                break;
        }
    }

    /**
     * Optimize JPEG image.
     *
     * @param string $file_path JPEG file path.
     */
    private function optimize_jpeg( $file_path ) {
        if ( ! function_exists( 'imagecreatefromjpeg' ) ) {
            return;
        }
        
        $image = imagecreatefromjpeg( $file_path );
        if ( ! $image ) {
            return;
        }
        
        // Save with optimized quality
        imagejpeg( $image, $file_path, 85 );
        imagedestroy( $image );
    }

    /**
     * Optimize PNG image.
     *
     * @param string $file_path PNG file path.
     */
    private function optimize_png( $file_path ) {
        if ( ! function_exists( 'imagecreatefrompng' ) ) {
            return;
        }
        
        $image = imagecreatefrompng( $file_path );
        if ( ! $image ) {
            return;
        }
        
        // Enable compression
        imagepng( $image, $file_path, 9 );
        imagedestroy( $image );
    }

    /**
     * Cleanup expired cache.
     */
    public function cleanup_expired_cache() {
        IMPRO_Cache::clean_expired_cache();
    }

    /**
     * Daily database cleanup.
     */
    public function daily_database_cleanup() {
        global $wpdb;
        
        $database = new IMPRO_Database();
        $tables = $database->get_table_names();
        
        // Clean up old invitation tokens (older than 90 days)
        $wpdb->query( 
            $wpdb->prepare(
                "DELETE FROM {$tables['invitations']} 
                 WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY) 
                 AND status = 'expired'",
            )
        );
        
        // Clean up orphaned RSVPs
        $wpdb->query( 
            "DELETE r FROM {$tables['rsvps']} r 
             LEFT JOIN {$tables['invitations']} i ON r.guest_id = i.guest_id AND r.event_id = i.event_id 
             WHERE i.id IS NULL"
        );
        
        // Optimize tables
        $wpdb->query( "OPTIMIZE TABLE {$tables['events']}" );
        $wpdb->query( "OPTIMIZE TABLE {$tables['guests']}" );
        $wpdb->query( "OPTIMIZE TABLE {$tables['invitations']}" );
        $wpdb->query( "OPTIMIZE TABLE {$tables['rsvps']}" );
    }

    /**
     * Log performance metrics.
     */
    public function log_performance_metrics() {
        if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
            return;
        }
        
        // Log query count
        $query_count = get_num_queries();
        
        // Log memory usage
        $memory_usage = memory_get_peak_usage( true );
        
        // Log execution time
        $execution_time = timer_stop();
        
        error_log( sprintf(
            'IMPRO Performance: Queries: %d, Memory: %s, Time: %s',
            $query_count,
            size_format( $memory_usage ),
            $execution_time . 's'
        ) );
    }

    /**
     * Check if current page is an invitation page.
     *
     * @return bool True if invitation page, false otherwise.
     */
    private function is_invitation_page() {
        return get_query_var( 'invitation_token' ) || 
               is_page( get_option( 'impro_invitation_page_id' ) ) || 
               is_page( get_option( 'impro_rsvp_page_id' ) );
    }

    /**
     * Get performance recommendations.
     *
     * @return array Performance recommendations.
     */
    public static function get_performance_recommendations() {
        $recommendations = array();
        
        // Check if object cache is available
        if ( ! wp_using_ext_object_cache() ) {
            $recommendations[] = array(
                'type'        => 'warning',
                'title'       => __( 'تفعيل التخزين المؤقت للكائنات', 'invitation-manager-pro' ),
                'description' => __( 'يُنصح بتفعيل التخزين المؤقت للكائنات لتحسين الأداء', 'invitation-manager-pro' )
            );
        }
        
        // Check PHP version
        if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
            $recommendations[] = array(
                'type'        => 'warning',
                'title'       => __( 'تحديث إصدار PHP', 'invitation-manager-pro' ),
                'description' => __( 'يُنصح بالترقية إلى PHP 8.0 أو أحدث لتحسين الأداء', 'invitation-manager-pro' )
            );
        }
        
        // Check database indexes
        if ( ! self::check_database_indexes() ) {
            $recommendations[] = array(
                'type'        => 'error',
                'title'       => __( 'فهارس قاعدة البيانات مفقودة', 'invitation-manager-pro' ),
                'description' => __( 'بعض فهارس قاعدة البيانات مفقودة، مما قد يؤثر على الأداء', 'invitation-manager-pro' )
            );
        }
        
        // Check cache size
        $cache_stats = IMPRO_Cache::get_cache_stats();
        if ( $cache_stats['cache_size_mb'] > 100 ) {
            $recommendations[] = array(
                'type'        => 'info',
                'title'       => __( 'تنظيف التخزين المؤقت', 'invitation-manager-pro' ),
                'description' => sprintf( __( 'حجم التخزين المؤقت %s ميجابايت، يُنصح بالتنظيف', 'invitation-manager-pro' ), $cache_stats['cache_size_mb'] )
            );
        }
        
        return $recommendations;
    }

    /**
     * Check if database indexes exist.
     *
     * @return bool True if all indexes exist, false otherwise.
     */
    private static function check_database_indexes() {
        global $wpdb;
        
        $database = new IMPRO_Database();
        $tables = $database->get_table_names();
        
        $required_indexes = array(
            $tables['events'] => array( 'idx_event_date', 'idx_created_at' ),
            $tables['guests'] => array( 'idx_email', 'idx_category' ),
            $tables['invitations'] => array( 'idx_token', 'idx_event_guest', 'idx_status' ),
            $tables['rsvps'] => array( 'idx_event_guest', 'idx_status', 'idx_response_date' )
        );
        
        foreach ( $required_indexes as $table => $indexes ) {
            foreach ( $indexes as $index ) {
                $index_exists = $wpdb->get_var( 
                    $wpdb->prepare(
                        "SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
                         WHERE table_schema = %s AND table_name = %s AND index_name = %s",
                        DB_NAME,
                        $table,
                        $index
                    )
                );
                
                if ( ! $index_exists ) {
                    return false;
                }
            }
        }
        
        return true;
    }
}


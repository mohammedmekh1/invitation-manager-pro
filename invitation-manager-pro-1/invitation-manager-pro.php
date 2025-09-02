
<?php
/**
 * Plugin Name: Invitation Manager Pro
 * Plugin URI: https://example.com/invitation-manager-pro  
 * Description: إضافة متكاملة لإدارة دعوات الزفاف والمناسبات مع نظام RSVP متقدم
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com  
 * Text Domain: invitation-manager-pro
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.3
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html  
 */

// منع الوصول المباشر
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// تهيئة الثوابت بأمان
if ( ! defined( 'IMPRO_URL' ) ) {
    define( 'IMPRO_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'IMPRO_PATH' ) ) {
    define( 'IMPRO_PATH', plugin_dir_path( __FILE__ ) );
}

// تهيئة الثوابت الإضافية
define( 'IMPRO_VERSION', '1.0.0' );
define( 'IMPRO_PLUGIN_FILE', __FILE__ );
define( 'IMPRO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'IMPRO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'IMPRO_INCLUDES_DIR', IMPRO_PLUGIN_DIR . 'includes/' );
define( 'IMPRO_ADMIN_DIR', IMPRO_PLUGIN_DIR . 'admin/' );
define( 'IMPRO_PUBLIC_DIR', IMPRO_PLUGIN_DIR . 'public/' );
define( 'IMPRO_ASSETS_DIR', IMPRO_PLUGIN_DIR . 'assets/' );
define( 'IMPRO_ASSETS_URL', IMPRO_PLUGIN_URL . 'assets/' );
define( 'IMPRO_VENDOR_DIR', IMPRO_PLUGIN_DIR . 'vendor/' );

/**
 * تحميل مكتبات QR Code
 *
 * @return string اسم المكتبة المحملة
 */
function impro_load_qr_libraries() {
    // التحقق من وجود مجلد المكتبات
    if ( ! is_dir( IMPRO_VENDOR_DIR ) ) {
        return 'google_charts'; // fallback
    }
    
    // Bacon QR Code
    $bacon_qr_path = IMPRO_VENDOR_DIR . 'bacon/bacon-qr-code/src/autoload.php';
    if ( file_exists( $bacon_qr_path ) ) {
        require_once $bacon_qr_path;
        return 'bacon';
    }
    
    // PHP QR Code
    $php_qr_path = IMPRO_VENDOR_DIR . 'phpqrcode/qrlib.php';
    if ( file_exists( $php_qr_path ) ) {
        require_once $php_qr_path;
        return 'phpqrcode';
    }
    
    // Endroid QR Code
    $endroid_qr_path = IMPRO_VENDOR_DIR . 'endroid/qr-code/src/QrCode.php';
    if ( file_exists( $endroid_qr_path ) ) {
        require_once $endroid_qr_path;
        return 'endroid';
    }
    
    return 'google_charts'; // fallback
}

/**
 * التحقق من متطلبات النظام
 *
 * @return array متطلبات النظام
 */
function impro_check_system_requirements() {
    $requirements = array();
    
    // التحقق من إصدار PHP
    $requirements['php_version'] = array(
        'required' => '7.4',
        'current' => PHP_VERSION,
        'status' => version_compare( PHP_VERSION, '7.4', '>=' )
    );
    
    // التحقق من إصدار WordPress
    global $wp_version;
    $requirements['wp_version'] = array(
        'required' => '5.0',
        'current' => $wp_version,
        'status' => version_compare( $wp_version, '5.0', '>=' )
    );
    
    // التحقق من مكتبات PHP المطلوبة
    $required_extensions = array( 'mysqli', 'gd', 'curl', 'json', 'mbstring' );
    $requirements['extensions'] = array();
    
    foreach ( $required_extensions as $extension ) {
        $requirements['extensions'][ $extension ] = array(
            'status' => extension_loaded( $extension )
        );
    }
    
    // التحقق من صلاحيات الكتابة
    $upload_dir = wp_upload_dir();
    $requirements['write_permissions'] = array(
        'status' => is_writable( $upload_dir['basedir'] )
    );
    
    return $requirements;
}

/**
 * عرض إشعارات متطلبات النظام
 */
function impro_display_requirement_notices() {
    $requirements = impro_check_system_requirements();
    $has_issues = false;
    
    foreach ( $requirements as $key => $requirement ) {
        if ( $key === 'extensions' ) {
            foreach ( $requirement as $ext => $ext_req ) {
                if ( ! $ext_req['status'] ) {
                    $has_issues = true;
                    echo '<div class="notice notice-error"><p>';
                    printf( 
                        __( 'الإضافة تتطلب مكتبة PHP "%s" والتي غير مثبتة.', 'invitation-manager-pro' ),
                        esc_html( $ext )
                    );
                    echo '</p></div>';
                }
            }
        } elseif ( ! $requirement['status'] ) {
            $has_issues = true;
            echo '<div class="notice notice-error"><p>';
            
            switch ( $key ) {
                case 'php_version':
                    printf( 
                        __( 'الإضافة تتطلب PHP إصدار %s أو أحدث. إصدارك الحالي هو %s.', 'invitation-manager-pro' ),
                        esc_html( $requirement['required'] ),
                        esc_html( $requirement['current'] )
                    );
                    break;
                    
                case 'wp_version':
                    printf( 
                        __( 'الإضافة تتطلب WordPress إصدار %s أو أحدث. إصدارك الحالي هو %s.', 'invitation-manager-pro' ),
                        esc_html( $requirement['required'] ),
                        esc_html( $requirement['current'] )
                    );
                    break;
                    
                case 'write_permissions':
                    _e( 'الإضافة تتطلب صلاحيات كتابة في مجلد الرفع (uploads).', 'invitation-manager-pro' );
                    break;
            }
            
            echo '</p></div>';
        }
    }
    
    if ( $has_issues ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
    }
}

/**
 * تحميل الترجمات
 */
function impro_load_textdomain() {
    load_plugin_textdomain( 
        'invitation-manager-pro', 
        false, 
        dirname( plugin_basename( __FILE__ ) ) . '/languages/' 
    );
}

// تحميل الترجمات مبكراً
add_action( 'plugins_loaded', 'impro_load_textdomain' );

// عرض إشعارات المتطلبات
add_action( 'admin_notices', 'impro_display_requirement_notices' );

/**
 * Main plugin class.
 */
class Invitation_Manager_Pro {

    /**
     * Single instance of the class.
     *
     * @var Invitation_Manager_Pro
     */
    private static $instance = null;

    /**
     * Admin instance.
     *
     * @var IMPRO_Admin
     */
    public $admin;

    /**
     * Public instance.
     *
     * @var IMPRO_Public
     */
    public $public;

    /**
     * Database instance.
     *
     * @var IMPRO_Database
     */
    public $database;

    /**
     * Get single instance.
     *
     * @return Invitation_Manager_Pro
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        // التحقق من متطلبات النظام قبل التحميل
        if ( ! $this->check_requirements() ) {
            return;
        }
        
        $this->load_dependencies();
        $this->init_hooks();
        $this->init_components();
    }

    /**
     * Check system requirements.
     *
     * @return bool True if requirements met, false otherwise.
     */
    private function check_requirements() {
        $requirements = impro_check_system_requirements();
        
        foreach ( $requirements as $key => $requirement ) {
            if ( $key === 'extensions' ) {
                foreach ( $requirement as $ext_req ) {
                    if ( ! $ext_req['status'] ) {
                        return false;
                    }
                }
            } elseif ( ! $requirement['status'] ) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Load required files.
     */
    private function load_dependencies() {
        try {
            // Core classes
            $core_classes = array(
                'class-impro-database.php',
                'class-impro-installation.php',
                'class-impro-security.php',
                'class-impro-validator.php',
                'class-impro-cache.php',
                'class-impro-performance.php'
            );
            
            foreach ( $core_classes as $class_file ) {
                $file_path = IMPRO_INCLUDES_DIR . $class_file;
                if ( file_exists( $file_path ) ) {
                    require_once $file_path;
                } else {
                    error_log( 'Missing core class file: ' . $file_path );
                }
            }
            
            // Manager classes
            $manager_classes = array(
                'class-impro-event-manager.php',
                'class-impro-guest-manager.php',
                'class-impro-invitation-manager.php',
                'class-impro-rsvp-manager.php',
                'class-impro-email.php',
                'class-impro-qr-generator.php'
            );
            
            foreach ( $manager_classes as $class_file ) {
                $file_path = IMPRO_INCLUDES_DIR . $class_file;
                if ( file_exists( $file_path ) ) {
                    require_once $file_path;
                } else {
                    error_log( 'Missing manager class file: ' . $file_path );
                }
            }
            
            // Feature classes
            $feature_classes = array(
                'class-impro-shortcodes.php',
                'class-impro-query-builder.php',
                'class-impro-migration.php'
            );
            
            foreach ( $feature_classes as $class_file ) {
                $file_path = IMPRO_INCLUDES_DIR . $class_file;
                if ( file_exists( $file_path ) ) {
                    require_once $file_path;
                } else {
                    error_log( 'Missing feature class file: ' . $file_path );
                }
            }
            
            // Admin and public classes
            if ( is_admin() ) {
                $admin_file = IMPRO_INCLUDES_DIR . 'class-impro-admin.php';
                if ( file_exists( $admin_file ) ) {
                    require_once $admin_file;
                } else {
                    error_log( 'Missing admin class file: ' . $admin_file );
                }
            }
            
            if ( ! is_admin() || wp_doing_ajax() ) {
                $public_file = IMPRO_INCLUDES_DIR . 'class-impro-public.php';
                if ( file_exists( $public_file ) ) {
                    require_once $public_file;
                } else {
                    error_log( 'Missing public class file: ' . $public_file );
                }
            }
            
        } catch ( Exception $e ) {
            error_log( 'Error loading dependencies: ' . $e->getMessage() );
            return false;
        }
        
        return true;
    }

    /**
     * Initialize hooks.
     */
    private function init_hooks() {
        // Activation and deactivation hooks
        register_activation_hook( IMPRO_PLUGIN_FILE, array( 'IMPRO_Installation', 'activate' ) );
        register_deactivation_hook( IMPRO_PLUGIN_FILE, array( 'IMPRO_Installation', 'deactivate' ) );
        
        // Uninstall hook
        register_uninstall_hook( IMPRO_PLUGIN_FILE, array( 'IMPRO_Installation', 'uninstall' ) );
        
        // Plugin loaded hook
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
        
        // Init hook
        add_action( 'init', array( $this, 'init' ) );
        
        // Admin init hook
        add_action( 'admin_init', array( $this, 'admin_init' ) );
        
        // Cron jobs
        add_action( 'impro_daily_cleanup', array( $this, 'daily_cleanup' ) );
        add_action( 'impro_weekly_stats', array( $this, 'weekly_statistics' ) );
        
        // Error handling
        add_action( 'wp_die_handler', array( $this, 'custom_die_handler' ) );
    }

    /**
     * Initialize components.
     */
    private function init_components() {
        try {
            // Initialize database
            $this->database = new IMPRO_Database();
            
            // Initialize shortcodes
            new IMPRO_Shortcodes();
            
            // Initialize admin
            if ( is_admin() ) {
                $this->admin = new IMPRO_Admin();
            }
            
            // Initialize public
            if ( ! is_admin() || wp_doing_ajax() ) {
                $this->public = new IMPRO_Public();
            }
            
            // Initialize QR libraries
            $qr_library = impro_load_qr_libraries();
            
            // Initialize migration system
            $migration = new IMPRO_Migration();
            $migration->run_migrations();
            
        } catch ( Exception $e ) {
            error_log( 'Error initializing components: ' . $e->getMessage() );
        }
    }

    /**
     * Load plugin textdomain.
     */
    public function load_textdomain() {
        load_plugin_textdomain( 
            'invitation-manager-pro', 
            false, 
            dirname( plugin_basename( IMPRO_PLUGIN_FILE ) ) . '/languages/' 
        );
    }

    /**
     * Initialize plugin.
     */
    public function init() {
        // Check if plugin is activated
        if ( ! get_option( 'impro_activated' ) ) {
            return;
        }
        
        // Initialize security
        new IMPRO_Security();
        
        // Initialize performance optimizations
        new IMPRO_Performance();
        
        // Initialize cache system
        new IMPRO_Cache();
        
        // Initialize validator
        new IMPRO_Validator();
        
        // Fire plugin initialized action
        do_action( 'impro_initialized' );
    }

    /**
     * Admin initialization.
     */
    public function admin_init() {
        // Check system requirements
        $requirements = IMPRO_Installation::check_system_requirements();
        
        // Display admin notices if requirements not met
        $has_issues = false;
        foreach ( $requirements as $requirement => $data ) {
            if ( isset( $data['status'] ) && ! $data['status'] ) {
                add_action( 'admin_notices', array( $this, 'display_requirement_notice' ) );
                $has_issues = true;
                break;
            }
        }
        
        // Schedule cron jobs if not already scheduled
        if ( ! wp_next_scheduled( 'impro_daily_cleanup' ) ) {
            wp_schedule_event( time(), 'daily', 'impro_daily_cleanup' );
        }
        
        if ( ! wp_next_scheduled( 'impro_weekly_stats' ) ) {
            wp_schedule_event( time(), 'weekly', 'impro_weekly_stats' );
        }
    }

    /**
     * Display requirement notice.
     */
    public function display_requirement_notice() {
        echo '<div class="notice notice-error is-dismissible">';
        echo '<p>';
        _e( 'إضافة Invitation Manager Pro تتطلب متطلبات نظام أعلى. يرجى مراجعة صفحة الإعدادات للحصول على التفاصيل.', 'invitation-manager-pro' );
        echo '</p>';
        echo '</div>';
    }

    /**
     * Daily cleanup routine.
     */
    public function daily_cleanup() {
        // Clean up expired invitations
        $invitation_manager = new IMPRO_Invitation_Manager();
        if ( method_exists( $invitation_manager, 'cleanup_expired_invitations' ) ) {
            $invitation_manager->cleanup_expired_invitations();
        }
        
        // Clean up old cache entries
        IMPRO_Cache::clean_expired_cache();
        
        // Log cleanup
        error_log( 'IMPRO Daily cleanup completed at ' . date( 'Y-m-d H:i:s' ) );
    }

    
    /**
     * Custom die handler.
     *
     * @param callable $function Die handler function.
     * @return callable Custom die handler.
     */
    public function custom_die_handler( $function ) {
        return array( $this, 'impro_die_handler' );
    }

    /**
     * Custom die handler implementation.
     *
     * @param string $message Error message.
     * @param string $title Error title.
     * @param array  $args Arguments.
     */
    public function impro_die_handler( $message, $title = '', $args = array() ) {
        // Log the error
        error_log( 'IMPRO Error: ' . $message . ' - Title: ' . $title );
        
        // Use WordPress default die handler
        _default_wp_die_handler( $message, $title, $args );
    }

    /**
     * Get plugin version.
     *
     * @return string Plugin version.
     */
    public function get_version() {
        return IMPRO_VERSION;
    }

    /**
     * Get plugin URL.
     *
     * @return string Plugin URL.
     */
    public function get_plugin_url() {
        return IMPRO_PLUGIN_URL;
    }

    /**
     * Get plugin directory.
     *
     * @return string Plugin directory.
     */
    public function get_plugin_dir() {
        return IMPRO_PLUGIN_DIR;
    }

    /**
     * Check if plugin is fully loaded.
     *
     * @return bool True if loaded, false otherwise.
     */
    public function is_loaded() {
        return $this->database !== null && 
               ( $this->admin !== null || $this->public !== null );
    }
}

/**
 * Initialize the plugin.
 *
 * @return Invitation_Manager_Pro Plugin instance.
 */
function impro_init() {
    return Invitation_Manager_Pro::get_instance();
}

// Start the plugin
add_action( 'plugins_loaded', 'impro_init', 5 );

// تحميل مكتبات QR Code مبكراً
add_action( 'plugins_loaded', 'impro_load_qr_libraries', 1 );

/**
 * Get plugin instance.
 *
 * @return Invitation_Manager_Pro Plugin instance.
 */
function impro() {
    return Invitation_Manager_Pro::get_instance();
}

// إضافة دعم للترجمة في وقت التفعيل
register_activation_hook( __FILE__, function() {
    // تحميل الترجمات
    impro_load_textdomain();
    
    // التحقق من المتطلبات
    $requirements = impro_check_system_requirements();
    $all_met = true;
    
    foreach ( $requirements as $key => $requirement ) {
        if ( $key === 'extensions' ) {
            foreach ( $requirement as $ext_req ) {
                if ( ! $ext_req['status'] ) {
                    $all_met = false;
                    break 2;
                }
            }
        } elseif ( ! $requirement['status'] ) {
            $all_met = false;
            break;
        }
    }
    
    if ( ! $all_met ) {
        // إلغاء التفعيل إذا لم تتحقق المتطلبات
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die( __( 'لم تتحقق متطلبات النظام للإضافة. يرجى التحقق من متطلبات PHP وإعادة المحاولة.', 'invitation-manager-pro' ) );
    }
} );
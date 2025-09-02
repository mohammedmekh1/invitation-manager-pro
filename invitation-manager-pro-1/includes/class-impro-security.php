<?php
/**
 * Security management class.
 *
 * @package Invitation_Manager_Pro
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * IMPRO_Security class.
 */
class IMPRO_Security {

    /**
     * Constructor.
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize security hooks.
     */
    private function init_hooks() {
        // Add security headers
        add_action( 'send_headers', array( $this, 'add_security_headers' ) );
        
        // Sanitize and validate inputs
        add_filter( 'impro_sanitize_input', array( $this, 'sanitize_input' ), 10, 2 );
        
        // Rate limiting for invitation access
        add_action( 'impro_invitation_accessed', array( $this, 'track_invitation_access' ) );
        
        // Clean up expired tokens
        add_action( 'impro_daily_cleanup', array( $this, 'cleanup_expired_tokens' ) );
        
        // Schedule daily cleanup if not already scheduled
        if ( ! wp_next_scheduled( 'impro_daily_cleanup' ) ) {
            wp_schedule_event( time(), 'daily', 'impro_daily_cleanup' );
        }
    }

    /**
     * Add security headers.
     */
    public function add_security_headers() {
        // Only add headers for plugin pages
        if ( ! $this->is_plugin_page() ) {
            return;
        }

        // Prevent clickjacking
        header( 'X-Frame-Options: SAMEORIGIN' );
        
        // Prevent MIME type sniffing
        header( 'X-Content-Type-Options: nosniff' );
        
        // Enable XSS protection
        header( 'X-XSS-Protection: 1; mode=block' );
        
        // Referrer policy
        header( 'Referrer-Policy: strict-origin-when-cross-origin' );
    }

    /**
     * Check if current page is a plugin page.
     *
     * @return bool True if plugin page, false otherwise.
     */
    private function is_plugin_page() {
        global $pagenow;
        
        // Admin pages
        if ( is_admin() && isset( $_GET['page'] ) && strpos( $_GET['page'], 'impro' ) === 0 ) {
            return true;
        }
        
        // Frontend invitation pages
        if ( ! is_admin() && ( is_page( 'invitation' ) || get_query_var( 'invitation_token' ) ) ) {
            return true;
        }
        
        return false;
    }

    /**
     * Sanitize input data.
     *
     * @param mixed  $value Input value.
     * @param string $type  Input type.
     * @return mixed Sanitized value.
     */
    public function sanitize_input( $value, $type = 'text' ) {
        switch ( $type ) {
            case 'email':
                return sanitize_email( $value );
                
            case 'url':
                return esc_url_raw( $value );
                
            case 'textarea':
                return sanitize_textarea_field( $value );
                
            case 'html':
                return wp_kses_post( $value );
                
            case 'int':
                return intval( $value );
                
            case 'float':
                return floatval( $value );
                
            case 'bool':
                return (bool) $value;
                
            case 'array':
                return is_array( $value ) ? array_map( array( $this, 'sanitize_input' ), $value ) : array();
                
            case 'token':
                return $this->sanitize_token( $value );
                
            case 'text':
            default:
                return sanitize_text_field( $value );
        }
    }

    /**
     * Sanitize token.
     *
     * @param string $token Token to sanitize.
     * @return string Sanitized token.
     */
    private function sanitize_token( $token ) {
        // Remove any non-alphanumeric characters
        $token = preg_replace( '/[^a-zA-Z0-9]/', '', $token );
        
        // Limit length
        return substr( $token, 0, 64 );
    }

    /**
     * Validate invitation token.
     *
     * @param string $token Token to validate.
     * @return bool True if valid, false otherwise.
     */
    public function validate_invitation_token( $token ) {
        // Check token format
        if ( ! preg_match( '/^[a-zA-Z0-9]{32,64}$/', $token ) ) {
            return false;
        }

        // Check if token exists and is not expired
        $invitation_manager = new IMPRO_Invitation_Manager();
        $invitation = $invitation_manager->get_invitation_by_token( $token );
        
        if ( ! $invitation ) {
            return false;
        }

        // Check expiration
        if ( $invitation->expires_at && strtotime( $invitation->expires_at ) < time() ) {
            return false;
        }

        return true;
    }

    /**
     * Track invitation access for rate limiting.
     *
     * @param string $token Invitation token.
     */
    public function track_invitation_access( $token ) {
        $ip = $this->get_client_ip();
        $cache_key = 'impro_access_' . md5( $ip . $token );
        
        $access_count = get_transient( $cache_key );
        $access_count = $access_count ? $access_count + 1 : 1;
        
        // Allow 10 accesses per hour per IP per token
        if ( $access_count > 10 ) {
            wp_die( __( 'تم تجاوز الحد المسموح من المحاولات. يرجى المحاولة لاحقاً.', 'invitation-manager-pro' ) );
        }
        
        set_transient( $cache_key, $access_count, HOUR_IN_SECONDS );
    }

    /**
     * Get client IP address.
     *
     * @return string Client IP address.
     */
    private function get_client_ip() {
        $ip_keys = array(
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );

        foreach ( $ip_keys as $key ) {
            if ( array_key_exists( $key, $_SERVER ) === true ) {
                foreach ( explode( ',', $_SERVER[ $key ] ) as $ip ) {
                    $ip = trim( $ip );
                    
                    if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) !== false ) {
                        return $ip;
                    }
                }
            }
        }

        return isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }

    /**
     * Generate secure random token.
     *
     * @param int $length Token length.
     * @return string Random token.
     */
    public function generate_secure_token( $length = 32 ) {
        if ( function_exists( 'random_bytes' ) ) {
            return bin2hex( random_bytes( $length / 2 ) );
        } elseif ( function_exists( 'openssl_random_pseudo_bytes' ) ) {
            return bin2hex( openssl_random_pseudo_bytes( $length / 2 ) );
        } else {
            // Fallback to wp_generate_password
            return wp_generate_password( $length, false );
        }
    }

    /**
     * Hash sensitive data.
     *
     * @param string $data Data to hash.
     * @return string Hashed data.
     */
    public function hash_data( $data ) {
        return wp_hash( $data );
    }

    /**
     * Verify nonce with additional security checks.
     *
     * @param string $nonce  Nonce to verify.
     * @param string $action Nonce action.
     * @return bool True if valid, false otherwise.
     */
    public function verify_nonce( $nonce, $action ) {
        if ( ! wp_verify_nonce( $nonce, $action ) ) {
            return false;
        }

        // Additional checks can be added here
        // e.g., check user capabilities, rate limiting, etc.

        return true;
    }

    /**
     * Clean up expired tokens and data.
     */
    public function cleanup_expired_tokens() {
        $database = new IMPRO_Database();
        
        // Clean up expired invitations
        $invitations_table = $database->get_invitations_table();
        $database->execute_query( 
            "DELETE FROM $invitations_table WHERE expires_at < NOW() AND expires_at IS NOT NULL"
        );
        
        // Clean up old access logs (if implemented)
        $this->cleanup_access_logs();
        
        // Clean up transients (WordPress handles this automatically, but we can be explicit)
        $this->cleanup_plugin_transients();
    }

    /**
     * Clean up access logs.
     */
    private function cleanup_access_logs() {
        // Implementation depends on how access logs are stored
        // This is a placeholder for future implementation
    }

    /**
     * Clean up plugin-specific transients.
     */
    private function cleanup_plugin_transients() {
        global $wpdb;
        
        // Delete expired plugin transients
        $wpdb->query( 
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_timeout_impro_%' 
             AND option_value < UNIX_TIMESTAMP()"
        );
        
        // Delete the corresponding transient data
        $wpdb->query( 
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_impro_%' 
             AND option_name NOT IN (
                 SELECT REPLACE(option_name, '_timeout', '') 
                 FROM {$wpdb->options} 
                 WHERE option_name LIKE '_transient_timeout_impro_%'
             )"
        );
    }

    /**
     * Log security events.
     *
     * @param string $event   Event type.
     * @param string $message Event message.
     * @param array  $context Event context.
     */
    public function log_security_event( $event, $message, $context = array() ) {
        // Only log in debug mode or if logging is enabled
        if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
            return;
        }

        $log_entry = array(
            'timestamp' => current_time( 'mysql' ),
            'event'     => $event,
            'message'   => $message,
            'context'   => $context,
            'ip'        => $this->get_client_ip(),
            'user_id'   => get_current_user_id()
        );

        // Log to WordPress debug log
        error_log( 'IMPRO Security: ' . wp_json_encode( $log_entry ) );
    }

    /**
     * Check if user has permission for action.
     *
     * @param string $action Action to check.
     * @param int    $user_id Optional user ID.
     * @return bool True if permitted, false otherwise.
     */
    public function user_can( $action, $user_id = 0 ) {
        if ( ! $user_id ) {
            $user_id = get_current_user_id();
        }

        $capability_map = array(
            'manage_events'      => 'manage_options',
            'manage_guests'      => 'edit_posts',
            'manage_invitations' => 'edit_posts',
            'view_statistics'    => 'edit_posts',
            'export_data'        => 'manage_options'
        );

        $required_capability = isset( $capability_map[ $action ] ) ? $capability_map[ $action ] : 'manage_options';
        
        return user_can( $user_id, $required_capability );
    }
}


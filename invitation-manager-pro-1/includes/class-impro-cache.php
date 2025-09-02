<?php
/**
 * Cache management class for performance optimization.
 *
 * @package Invitation_Manager_Pro
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * IMPRO_Cache class.
 */
class IMPRO_Cache {

    /**
     * Cache group prefix.
     */
    const CACHE_GROUP = 'impro_';

    /**
     * Default cache expiration time (1 hour).
     */
    const DEFAULT_EXPIRATION = 3600;

    /**
     * Get cached data.
     *
     * @param string $key Cache key.
     * @param string $group Cache group.
     * @return mixed Cached data or false if not found.
     */
    public static function get( $key, $group = 'general' ) {
        $cache_key = self::get_cache_key( $key, $group );
        
        // Try object cache first
        $data = wp_cache_get( $cache_key, self::CACHE_GROUP . $group );
        
        if ( false === $data ) {
            // Try transient cache
            $data = get_transient( $cache_key );
        }
        
        return $data;
    }

    /**
     * Set cached data.
     *
     * @param string $key Cache key.
     * @param mixed  $data Data to cache.
     * @param int    $expiration Expiration time in seconds.
     * @param string $group Cache group.
     * @return bool True on success, false on failure.
     */
    public static function set( $key, $data, $expiration = self::DEFAULT_EXPIRATION, $group = 'general' ) {
        $cache_key = self::get_cache_key( $key, $group );
        
        // Set object cache
        wp_cache_set( $cache_key, $data, self::CACHE_GROUP . $group, $expiration );
        
        // Set transient cache as fallback
        return set_transient( $cache_key, $data, $expiration );
    }

    /**
     * Delete cached data.
     *
     * @param string $key Cache key.
     * @param string $group Cache group.
     * @return bool True on success, false on failure.
     */
    public static function delete( $key, $group = 'general' ) {
        $cache_key = self::get_cache_key( $key, $group );
        
        // Delete from object cache
        wp_cache_delete( $cache_key, self::CACHE_GROUP . $group );
        
        // Delete from transient cache
        return delete_transient( $cache_key );
    }

    /**
     * Flush cache group.
     *
     * @param string $group Cache group to flush.
     * @return bool True on success, false on failure.
     */
    public static function flush_group( $group = 'general' ) {
        global $wpdb;
        
        // Flush object cache group
        wp_cache_flush_group( self::CACHE_GROUP . $group );
        
        // Delete transients for this group
        $cache_prefix = self::get_cache_key( '', $group );
        $wpdb->query( 
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_' . $cache_prefix . '%',
                '_transient_timeout_' . $cache_prefix . '%'
            )
        );
        
        return true;
    }

    /**
     * Flush all plugin cache.
     *
     * @return bool True on success, false on failure.
     */
    public static function flush_all() {
        global $wpdb;
        
        // Flush all object cache groups
        $groups = array( 'general', 'events', 'guests', 'invitations', 'rsvps', 'statistics' );
        foreach ( $groups as $group ) {
            wp_cache_flush_group( self::CACHE_GROUP . $group );
        }
        
        // Delete all plugin transients
        $wpdb->query( 
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_impro_%' OR option_name LIKE '_transient_timeout_impro_%'"
        );
        
        return true;
    }

    /**
     * Get cache key with proper formatting.
     *
     * @param string $key Original key.
     * @param string $group Cache group.
     * @return string Formatted cache key.
     */
    private static function get_cache_key( $key, $group ) {
        return self::CACHE_GROUP . $group . '_' . md5( $key );
    }

    /**
     * Cache event data.
     *
     * @param int   $event_id Event ID.
     * @param array $event_data Event data.
     * @param int   $expiration Expiration time.
     * @return bool True on success, false on failure.
     */
    public static function cache_event( $event_id, $event_data, $expiration = self::DEFAULT_EXPIRATION ) {
        return self::set( 'event_' . $event_id, $event_data, $expiration, 'events' );
    }

    /**
     * Get cached event data.
     *
     * @param int $event_id Event ID.
     * @return mixed Event data or false if not found.
     */
    public static function get_cached_event( $event_id ) {
        return self::get( 'event_' . $event_id, 'events' );
    }

    /**
     * Delete cached event data.
     *
     * @param int $event_id Event ID.
     * @return bool True on success, false on failure.
     */
    public static function delete_cached_event( $event_id ) {
        return self::delete( 'event_' . $event_id, 'events' );
    }

    /**
     * Cache guest data.
     *
     * @param int   $guest_id Guest ID.
     * @param array $guest_data Guest data.
     * @param int   $expiration Expiration time.
     * @return bool True on success, false on failure.
     */
    public static function cache_guest( $guest_id, $guest_data, $expiration = self::DEFAULT_EXPIRATION ) {
        return self::set( 'guest_' . $guest_id, $guest_data, $expiration, 'guests' );
    }

    /**
     * Get cached guest data.
     *
     * @param int $guest_id Guest ID.
     * @return mixed Guest data or false if not found.
     */
    public static function get_cached_guest( $guest_id ) {
        return self::get( 'guest_' . $guest_id, 'guests' );
    }

    /**
     * Delete cached guest data.
     *
     * @param int $guest_id Guest ID.
     * @return bool True on success, false on failure.
     */
    public static function delete_cached_guest( $guest_id ) {
        return self::delete( 'guest_' . $guest_id, 'guests' );
    }

    /**
     * Cache invitation data.
     *
     * @param string $token Invitation token.
     * @param array  $invitation_data Invitation data.
     * @param int    $expiration Expiration time.
     * @return bool True on success, false on failure.
     */
    public static function cache_invitation( $token, $invitation_data, $expiration = self::DEFAULT_EXPIRATION ) {
        return self::set( 'invitation_' . $token, $invitation_data, $expiration, 'invitations' );
    }

    /**
     * Get cached invitation data.
     *
     * @param string $token Invitation token.
     * @return mixed Invitation data or false if not found.
     */
    public static function get_cached_invitation( $token ) {
        return self::get( 'invitation_' . $token, 'invitations' );
    }

    /**
     * Delete cached invitation data.
     *
     * @param string $token Invitation token.
     * @return bool True on success, false on failure.
     */
    public static function delete_cached_invitation( $token ) {
        return self::delete( 'invitation_' . $token, 'invitations' );
    }

    /**
     * Cache statistics data.
     *
     * @param string $stat_key Statistics key.
     * @param array  $stat_data Statistics data.
     * @param int    $expiration Expiration time.
     * @return bool True on success, false on failure.
     */
    public static function cache_statistics( $stat_key, $stat_data, $expiration = 1800 ) { // 30 minutes for stats
        return self::set( 'stats_' . $stat_key, $stat_data, $expiration, 'statistics' );
    }

    /**
     * Get cached statistics data.
     *
     * @param string $stat_key Statistics key.
     * @return mixed Statistics data or false if not found.
     */
    public static function get_cached_statistics( $stat_key ) {
        return self::get( 'stats_' . $stat_key, 'statistics' );
    }

    /**
     * Delete cached statistics data.
     *
     * @param string $stat_key Statistics key.
     * @return bool True on success, false on failure.
     */
    public static function delete_cached_statistics( $stat_key ) {
        return self::delete( 'stats_' . $stat_key, 'statistics' );
    }

    /**
     * Cache QR code data.
     *
     * @param string $token Invitation token.
     * @param string $qr_url QR code URL.
     * @param int    $expiration Expiration time.
     * @return bool True on success, false on failure.
     */
    public static function cache_qr_code( $token, $qr_url, $expiration = 86400 ) { // 24 hours for QR codes
        return self::set( 'qr_' . $token, $qr_url, $expiration, 'general' );
    }

    /**
     * Get cached QR code data.
     *
     * @param string $token Invitation token.
     * @return mixed QR code URL or false if not found.
     */
    public static function get_cached_qr_code( $token ) {
        return self::get( 'qr_' . $token, 'general' );
    }

    /**
     * Delete cached QR code data.
     *
     * @param string $token Invitation token.
     * @return bool True on success, false on failure.
     */
    public static function delete_cached_qr_code( $token ) {
        return self::delete( 'qr_' . $token, 'general' );
    }

    /**
     * Warm up cache for frequently accessed data.
     *
     * @return bool True on success, false on failure.
     */
    public static function warm_up_cache() {
        $event_manager = new IMPRO_Event_Manager();
        $guest_manager = new IMPRO_Guest_Manager();
        
        // Cache upcoming events
        $upcoming_events = $event_manager->get_events( array( 'status' => 'upcoming', 'limit' => 10 ) );
        foreach ( $upcoming_events as $event ) {
            self::cache_event( $event->id, $event );
        }
        
        // Cache recent guests
        $recent_guests = $guest_manager->get_guests( array( 'limit' => 50 ) );
        foreach ( $recent_guests as $guest ) {
            self::cache_guest( $guest->id, $guest );
        }
        
        // Cache overall statistics
        $stats = array(
            'events' => $event_manager->get_event_statistics(),
            'guests' => $guest_manager->get_guest_statistics()
        );
        self::cache_statistics( 'overall', $stats );
        
        return true;
    }

    /**
     * Get cache statistics.
     *
     * @return array Cache statistics.
     */
    public static function get_cache_stats() {
        global $wpdb;
        
        // Count transients
        $transient_count = $wpdb->get_var( 
            "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '_transient_impro_%'"
        );
        
        // Get cache size (approximate)
        $cache_size = $wpdb->get_var( 
            "SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} WHERE option_name LIKE '_transient_impro_%'"
        );
        
        return array(
            'transient_count' => intval( $transient_count ),
            'cache_size'      => intval( $cache_size ),
            'cache_size_mb'   => round( intval( $cache_size ) / 1024 / 1024, 2 )
        );
    }

    /**
     * Clean expired cache entries.
     *
     * @return int Number of cleaned entries.
     */
    public static function clean_expired_cache() {
        global $wpdb;
        
        // Clean expired transients
        $cleaned = $wpdb->query( 
            "DELETE a, b FROM {$wpdb->options} a, {$wpdb->options} b 
             WHERE a.option_name LIKE '_transient_impro_%' 
             AND a.option_name = CONCAT('_transient_', SUBSTRING(b.option_name, 20))
             AND b.option_name LIKE '_transient_timeout_impro_%' 
             AND b.option_value < UNIX_TIMESTAMP()"
        );
        
        return intval( $cleaned );
    }

    /**
     * Schedule cache cleanup.
     */
    public static function schedule_cache_cleanup() {
        if ( ! wp_next_scheduled( 'impro_cache_cleanup' ) ) {
            wp_schedule_event( time(), 'daily', 'impro_cache_cleanup' );
        }
    }

    /**
     * Unschedule cache cleanup.
     */
    public static function unschedule_cache_cleanup() {
        $timestamp = wp_next_scheduled( 'impro_cache_cleanup' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'impro_cache_cleanup' );
        }
    }

    /**
     * Handle cache invalidation on data changes.
     *
     * @param string $type Data type (event, guest, invitation, rsvp).
     * @param int    $id Data ID.
     */
    public static function invalidate_related_cache( $type, $id ) {
        switch ( $type ) {
            case 'event':
                self::delete_cached_event( $id );
                self::flush_group( 'statistics' );
                break;
                
            case 'guest':
                self::delete_cached_guest( $id );
                self::flush_group( 'statistics' );
                break;
                
            case 'invitation':
                // Invalidate by token if available
                $invitation_manager = new IMPRO_Invitation_Manager();
                $invitation = $invitation_manager->get_invitation( $id );
                if ( $invitation && $invitation->token ) {
                    self::delete_cached_invitation( $invitation->token );
                    self::delete_cached_qr_code( $invitation->token );
                }
                self::flush_group( 'statistics' );
                break;
                
            case 'rsvp':
                self::flush_group( 'statistics' );
                break;
        }
    }

    /**
     * Get cache key for database query.
     *
     * @param string $query SQL query.
     * @param array  $args Query arguments.
     * @return string Cache key.
     */
    public static function get_query_cache_key( $query, $args = array() ) {
        return md5( $query . serialize( $args ) );
    }

    /**
     * Cache database query result.
     *
     * @param string $query SQL query.
     * @param array  $args Query arguments.
     * @param mixed  $result Query result.
     * @param int    $expiration Expiration time.
     * @param string $group Cache group.
     * @return bool True on success, false on failure.
     */
    public static function cache_query_result( $query, $args, $result, $expiration = self::DEFAULT_EXPIRATION, $group = 'general' ) {
        $cache_key = self::get_query_cache_key( $query, $args );
        return self::set( 'query_' . $cache_key, $result, $expiration, $group );
    }

    /**
     * Get cached database query result.
     *
     * @param string $query SQL query.
     * @param array  $args Query arguments.
     * @param string $group Cache group.
     * @return mixed Query result or false if not found.
     */
    public static function get_cached_query_result( $query, $args = array(), $group = 'general' ) {
        $cache_key = self::get_query_cache_key( $query, $args );
        return self::get( 'query_' . $cache_key, $group );
    }
}


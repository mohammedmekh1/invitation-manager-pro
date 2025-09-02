<?php
/**
 * QR Code generator class.
 *
 * @package Invitation_Manager_Pro
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * IMPRO_QR_Generator class.
 */
class IMPRO_QR_Generator {

    /**
     * Generate QR code for invitation.
     *
     * @param string $token Invitation token.
     * @param array  $args  QR code arguments.
     * @return string|false QR code image URL on success, false on failure.
     */
    public function generate_invitation_qr( $token, $args = array() ) {
        $defaults = array(
            'size'   => get_option( 'impro_qr_code_size', 200 ),
            'margin' => 10,
            'format' => 'png'
        );

        $args = wp_parse_args( $args, $defaults );
        
        // Create invitation URL
        $invitation_page_id = get_option( 'impro_invitation_page_id' );
        if ( $invitation_page_id ) {
            $invitation_url = get_permalink( $invitation_page_id ) . '?token=' . $token;
        } else {
            $invitation_url = home_url( '/invitation/' . $token );
        }
        
        return $this->generate_qr_code( $invitation_url, $args );
    }

    /**
     * Generate QR code for event.
     *
     * @param int   $event_id Event ID.
     * @param array $args     QR code arguments.
     * @return string|false QR code image URL on success, false on failure.
     */
    public function generate_event_qr( $event_id, $args = array() ) {
        $defaults = array(
            'size'   => get_option( 'impro_qr_code_size', 200 ),
            'margin' => 10,
            'format' => 'png'
        );

        $args = wp_parse_args( $args, $defaults );
        
        // Create event URL
        $event_url = get_permalink( $event_id ) ?: home_url( '/event/' . $event_id );
        
        return $this->generate_qr_code( $event_url, $args );
    }

    /**
     * Generate QR code for custom data.
     *
     * @param string $data QR code data.
     * @param array  $args QR code arguments.
     * @return string|false QR code image URL on success, false on failure.
     */
    public function generate_qr_code( $data, $args = array() ) {
        $defaults = array(
            'size'   => 200,
            'margin' => 10,
            'format' => 'png',
            'ecc'    => 'L' // Error correction level: L, M, Q, H
        );

        $args = wp_parse_args( $args, $defaults );
        
        // Validate data
        if ( ! $this->validate_qr_data( $data ) ) {
            return false;
        }
        
        // Try local QR generator first, fallback to Google Charts
        $qr_url = $this->generate_qr_locally( $data, $args );
        
        if ( ! $qr_url ) {
            $qr_url = $this->generate_qr_with_google_charts( $data, $args );
        }
        
        return $qr_url;
    }

    /**
     * Generate QR code using local library (if available).
     *
     * @param string $data QR code data.
     * @param array  $args QR code arguments.
     * @return string|false QR code image URL on success, false on failure.
     */
    private function generate_qr_locally( $data, $args ) {
        // Check if Bacon QR Code is available
        if ( class_exists( '\\BaconQrCode\\Writer' ) ) {
            return $this->generate_with_bacon_qr( $data, $args );
        }
        
        // Check if PHP QR Code is available
        if ( function_exists( 'QRcode::png' ) ) {
            return $this->generate_with_php_qr( $data, $args );
        }
        
        return false;
    }

    // =====================================================
    // إضافة الدالتين المطلوبتين هنا:
    // =====================================================

    /**
     * Generate QR code using Bacon QR Code library.
     *
     * @param string $data QR code data.
     * @param array  $args QR code arguments.
     * @return string|false QR code image URL on success, false on failure.
     */
    private function generate_with_bacon_qr($data, $args) {
        try {
            // إنشاء renderer للصورة
            $renderer = new \BaconQrCode\Renderer\ImageRenderer(
                new \BaconQrCode\Renderer\RendererStyle\RendererStyle(
                    $args['size'],
                    $args['margin']
                ),
                new \BaconQrCode\Renderer\Image\PngImageBackEnd()
            );
            
            // إنشاء writer
            $writer = new \BaconQrCode\Writer($renderer);
            
            // إنشاء ملف مؤقت
            $temp_file = wp_tempnam('qr_code_' . md5($data));
            
            // توليد رمز QR وحفظه في الملف المؤقت
            $writer->writeFile($data, $temp_file);
            
            // رفع الملف إلى مجلد الرفع في ووردبريس
            $upload_dir = wp_upload_dir();
            $qr_dir = $upload_dir['basedir'] . '/impro-qr-codes';
            
            // إنشاء مجلد QR codes إذا لم يكن موجوداً
            if (!file_exists($qr_dir)) {
                wp_mkdir_p($qr_dir);
            }
            
            $filename = 'qr_' . md5($data) . '_' . time() . '.png';
            $destination = $qr_dir . '/' . $filename;
            
            // نسخ الملف من الموقت إلى الوجهة النهائية
            if (copy($temp_file, $destination)) {
                unlink($temp_file); // حذف الملف المؤقت
                return $upload_dir['baseurl'] . '/impro-qr-codes/' . $filename;
            }
            
            // في حالة الفشل، حذف الملف المؤقت
            unlink($temp_file);
            return false;
            
        } catch (Exception $e) {
            error_log('Bacon QR Code generation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate QR code using PHP QR Code library.
     *
     * @param string $data QR code data.
     * @param array  $args QR code arguments.
     * @return string|false QR code image URL on success, false on failure.
     */
    private function generate_with_php_qr($data, $args) {
        try {
            // التأكد من وجود مكتبة PHP QR Code
            if (!function_exists('QRcode::png')) {
                return false;
            }
            
            // إنشاء ملف مؤقت
            $temp_file = wp_tempnam('qr_code_' . md5($data));
            
            // توليد رمز QR باستخدام PHP QR Code
            QRcode::png(
                $data, 
                $temp_file, 
                'L', // مستوى تصحيح الأخطاء
                max(1, min(10, $args['size'] / 25)), // حجم الخلية
                $args['margin'] // الهامش
            );
            
            // رفع الملف إلى مجلد الرفع في ووردبريس
            $upload_dir = wp_upload_dir();
            $qr_dir = $upload_dir['basedir'] . '/impro-qr-codes';
            
            // إنشاء مجلد QR codes إذا لم يكن موجوداً
            if (!file_exists($qr_dir)) {
                wp_mkdir_p($qr_dir);
            }
            
            $filename = 'qr_' . md5($data) . '_' . time() . '.png';
            $destination = $qr_dir . '/' . $filename;
            
            // نسخ الملف من الموقت إلى الوجهة النهائية
            if (copy($temp_file, $destination)) {
                unlink($temp_file); // حذف الملف المؤقت
                return $upload_dir['baseurl'] . '/impro-qr-codes/' . $filename;
            }
            
            // في حالة الفشل، حذف الملف المؤقت
            unlink($temp_file);
            return false;
            
        } catch (Exception $e) {
            error_log('PHP QR Code generation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate QR code using Google Charts API.
     *
     * @param string $data QR code data.
     * @param array  $args QR code arguments.
     * @return string QR code image URL.
     */
    private function generate_qr_with_google_charts( $data, $args ) {
        $base_url = 'https://chart.googleapis.com/chart'; // تم إصلاح الرابط
        
        $params = array(
            'chs'  => $args['size'] . 'x' . $args['size'],
            'cht'  => 'qr',
            'chl'  => urlencode( $data ),
            'choe' => 'UTF-8',
            'chld' => $args['ecc'] . '|' . $args['margin']
        );
        
        return $base_url . '?' . http_build_query( $params );
    }

    /**
     * Generate and save QR code to file.
     *
     * @param string $data     QR code data.
     * @param string $filename Output filename.
     * @param array  $args     QR code arguments.
     * @return string|false File path on success, false on failure.
     */
    public function generate_and_save_qr( $data, $filename, $args = array() ) {
        // Validate data first
        if ( ! $this->validate_qr_data( $data ) ) {
            return false;
        }
        
        // Try to generate QR locally first for better quality
        $local_qr = $this->generate_qr_locally( $data, $args );
        
        if ( $local_qr ) {
            // If local generation successful, download and save
            return $this->download_and_save_qr( $local_qr, $filename );
        }
        
        // Fallback to Google Charts
        $qr_url = $this->generate_qr_code( $data, $args );
        
        if ( ! $qr_url ) {
            return false;
        }

        return $this->download_and_save_qr( $qr_url, $filename );
    }

    /**
     * Download and save QR code image.
     *
     * @param string $qr_url   QR code URL.
     * @param string $filename Output filename.
     * @return string|false File path on success, false on failure.
     */
    private function download_and_save_qr( $qr_url, $filename ) {
        // Download QR code image
        $response = wp_remote_get( $qr_url, array( 'timeout' => 30 ) );
        
        if ( is_wp_error( $response ) ) {
            error_log( 'QR Code download failed: ' . $response->get_error_message() );
            return false;
        }

        $image_data = wp_remote_retrieve_body( $response );
        $http_code = wp_remote_retrieve_response_code( $response );
        
        if ( empty( $image_data ) || $http_code !== 200 ) {
            error_log( 'QR Code download failed with HTTP code: ' . $http_code );
            return false;
        }

        // Save to uploads directory
        $upload_dir = wp_upload_dir();
        $qr_dir = $upload_dir['basedir'] . '/impro-qr-codes';
        
        // Create directory if it doesn't exist
        if ( ! file_exists( $qr_dir ) ) {
            if ( ! wp_mkdir_p( $qr_dir ) ) {
                error_log( 'Failed to create QR codes directory: ' . $qr_dir );
                return false;
            }
        }

        // Sanitize filename
        $filename = sanitize_file_name( $filename );
        $file_path = $qr_dir . '/' . $filename;
        
        // Save file
        if ( file_put_contents( $file_path, $image_data ) ) {
            // Return URL instead of file path
            return $upload_dir['baseurl'] . '/impro-qr-codes/' . $filename;
        }

        error_log( 'Failed to save QR code to: ' . $file_path );
        return false;
    }

    /**
     * Get QR code for invitation with caching.
     *
     * @param string $token Invitation token.
     * @param array  $args  QR code arguments.
     * @return string QR code image URL.
     */
    public function get_cached_invitation_qr( $token, $args = array() ) {
        // Validate token
        if ( empty( $token ) ) {
            return false;
        }
        
        $cache_key = 'impro_qr_' . md5( $token . serialize( $args ) );
        $cached_url = get_transient( $cache_key );
        
        if ( $cached_url ) {
            return $cached_url;
        }

        $qr_url = $this->generate_invitation_qr( $token, $args );
        
        if ( $qr_url ) {
            // Cache for 24 hours
            set_transient( $cache_key, $qr_url, DAY_IN_SECONDS );
        }

        return $qr_url;
    }

    /**
     * Clear QR code cache.
     *
     * @param string $token Optional token to clear specific cache.
     */
    public function clear_qr_cache( $token = '' ) {
        global $wpdb;
        
        if ( $token ) {
            // Clear specific token cache
            $cache_key = 'impro_qr_' . md5( $token );
            delete_transient( $cache_key );
        } else {
            // Clear all QR code caches
            $wpdb->query(
                "DELETE FROM {$wpdb->options} 
                 WHERE option_name LIKE '_transient_impro_qr_%' 
                 OR option_name LIKE '_transient_timeout_impro_qr_%'"
            );
        }
    }

    /**
     * Validate QR code data.
     *
     * @param string $data QR code data.
     * @return bool True if valid, false otherwise.
     */
    private function validate_qr_data( $data ) {
        // Check if data is not empty
        if ( empty( $data ) ) {
            return false;
        }

        // Check data length (QR codes have limits)
        if ( strlen( $data ) > 2000 ) {
            error_log( 'QR code data too long: ' . strlen( $data ) . ' characters' );
            return false;
        }

        // Validate URL if it's a URL
        if ( filter_var( $data, FILTER_VALIDATE_URL ) !== false ) {
            // Check if URL is too long
            if ( strlen( $data ) > 1000 ) {
                error_log( 'QR code URL too long: ' . strlen( $data ) . ' characters' );
                return false;
            }
        }

        return true;
    }

    /**
     * Get QR code format MIME type.
     *
     * @param string $format QR code format.
     * @return string MIME type.
     */
    private function get_mime_type( $format ) {
        $mime_types = array(
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif'  => 'image/gif',
            'svg'  => 'image/svg+xml'
        );

        return isset( $mime_types[ $format ] ) ? $mime_types[ $format ] : 'image/png';
    }
}
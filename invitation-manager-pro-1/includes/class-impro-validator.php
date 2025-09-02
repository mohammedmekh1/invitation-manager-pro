<?php
/**
 * Data validation and sanitization class.
 *
 * @package Invitation_Manager_Pro
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * IMPRO_Validator class.
 */
class IMPRO_Validator {

    /**
     * Validation errors.
     *
     * @var array
     */
    private static $errors = array();

    /**
     * Validate event data.
     *
     * @param array $data Event data to validate.
     * @return bool True if valid, false otherwise.
     */
    public static function validate_event_data( $data ) {
        self::$errors = array();

        // Required fields
        if ( empty( $data['name'] ) ) {
            self::add_error( 'name', __( 'اسم المناسبة مطلوب', 'invitation-manager-pro' ) );
        } elseif ( strlen( $data['name'] ) > 255 ) {
            self::add_error( 'name', __( 'اسم المناسبة طويل جداً', 'invitation-manager-pro' ) );
        }

        if ( empty( $data['event_date'] ) ) {
            self::add_error( 'event_date', __( 'تاريخ المناسبة مطلوب', 'invitation-manager-pro' ) );
        } elseif ( ! self::is_valid_date( $data['event_date'] ) ) {
            self::add_error( 'event_date', __( 'تاريخ المناسبة غير صحيح', 'invitation-manager-pro' ) );
        } elseif ( strtotime( $data['event_date'] ) < strtotime( 'today' ) ) {
            self::add_error( 'event_date', __( 'تاريخ المناسبة يجب أن يكون في المستقبل', 'invitation-manager-pro' ) );
        }

        if ( empty( $data['venue'] ) ) {
            self::add_error( 'venue', __( 'مكان المناسبة مطلوب', 'invitation-manager-pro' ) );
        } elseif ( strlen( $data['venue'] ) > 255 ) {
            self::add_error( 'venue', __( 'اسم المكان طويل جداً', 'invitation-manager-pro' ) );
        }

        // Optional fields validation
        if ( ! empty( $data['event_time'] ) && ! self::is_valid_time( $data['event_time'] ) ) {
            self::add_error( 'event_time', __( 'وقت المناسبة غير صحيح', 'invitation-manager-pro' ) );
        }

        if ( ! empty( $data['invitation_image_url'] ) && ! self::is_valid_url( $data['invitation_image_url'] ) ) {
            self::add_error( 'invitation_image_url', __( 'رابط الصورة غير صحيح', 'invitation-manager-pro' ) );
        }

        if ( ! empty( $data['address'] ) && strlen( $data['address'] ) > 500 ) {
            self::add_error( 'address', __( 'العنوان طويل جداً', 'invitation-manager-pro' ) );
        }

        if ( ! empty( $data['description'] ) && strlen( $data['description'] ) > 1000 ) {
            self::add_error( 'description', __( 'الوصف طويل جداً', 'invitation-manager-pro' ) );
        }

        if ( ! empty( $data['contact_info'] ) && strlen( $data['contact_info'] ) > 255 ) {
            self::add_error( 'contact_info', __( 'معلومات الاتصال طويلة جداً', 'invitation-manager-pro' ) );
        }

        return empty( self::$errors );
    }

    /**
     * Validate guest data.
     *
     * @param array $data Guest data to validate.
     * @return bool True if valid, false otherwise.
     */
    public static function validate_guest_data( $data ) {
        self::$errors = array();

        // Required fields
        if ( empty( $data['name'] ) ) {
            self::add_error( 'name', __( 'اسم الضيف مطلوب', 'invitation-manager-pro' ) );
        } elseif ( strlen( $data['name'] ) > 255 ) {
            self::add_error( 'name', __( 'اسم الضيف طويل جداً', 'invitation-manager-pro' ) );
        } elseif ( ! self::is_valid_name( $data['name'] ) ) {
            self::add_error( 'name', __( 'اسم الضيف يحتوي على أحرف غير صحيحة', 'invitation-manager-pro' ) );
        }

        // Email validation
        if ( ! empty( $data['email'] ) ) {
            if ( ! is_email( $data['email'] ) ) {
                self::add_error( 'email', __( 'البريد الإلكتروني غير صحيح', 'invitation-manager-pro' ) );
            } elseif ( self::email_exists( $data['email'], $data['id'] ?? 0 ) ) {
                self::add_error( 'email', __( 'البريد الإلكتروني مستخدم من قبل', 'invitation-manager-pro' ) );
            }
        }

        // Phone validation
        if ( ! empty( $data['phone'] ) && ! self::is_valid_phone( $data['phone'] ) ) {
            self::add_error( 'phone', __( 'رقم الهاتف غير صحيح', 'invitation-manager-pro' ) );
        }

        // Category validation
        if ( ! empty( $data['category'] ) ) {
            $valid_categories = self::get_valid_guest_categories();
            if ( ! in_array( $data['category'], $valid_categories ) ) {
                self::add_error( 'category', __( 'فئة الضيف غير صحيحة', 'invitation-manager-pro' ) );
            }
        }

        // Gender validation
        if ( ! empty( $data['gender'] ) ) {
            $valid_genders = array( 'male', 'female' );
            if ( ! in_array( $data['gender'], $valid_genders ) ) {
                self::add_error( 'gender', __( 'الجنس غير صحيح', 'invitation-manager-pro' ) );
            }
        }

        // Age range validation
        if ( ! empty( $data['age_range'] ) ) {
            $valid_age_ranges = self::get_valid_age_ranges();
            if ( ! in_array( $data['age_range'], $valid_age_ranges ) ) {
                self::add_error( 'age_range', __( 'الفئة العمرية غير صحيحة', 'invitation-manager-pro' ) );
            }
        }

        // Relationship validation
        if ( ! empty( $data['relationship'] ) && strlen( $data['relationship'] ) > 100 ) {
            self::add_error( 'relationship', __( 'صلة القرابة طويلة جداً', 'invitation-manager-pro' ) );
        }

        return empty( self::$errors );
    }

    /**
     * Validate RSVP data.
     *
     * @param array $data RSVP data to validate.
     * @return bool True if valid, false otherwise.
     */
    public static function validate_rsvp_data( $data ) {
        self::$errors = array();

        // Required fields
        if ( empty( $data['guest_id'] ) || ! is_numeric( $data['guest_id'] ) ) {
            self::add_error( 'guest_id', __( 'معرف الضيف مطلوب', 'invitation-manager-pro' ) );
        }

        if ( empty( $data['event_id'] ) || ! is_numeric( $data['event_id'] ) ) {
            self::add_error( 'event_id', __( 'معرف المناسبة مطلوب', 'invitation-manager-pro' ) );
        }

        if ( empty( $data['status'] ) ) {
            self::add_error( 'status', __( 'حالة الرد مطلوبة', 'invitation-manager-pro' ) );
        } elseif ( ! in_array( $data['status'], array( 'accepted', 'declined' ) ) ) {
            self::add_error( 'status', __( 'حالة الرد غير صحيحة', 'invitation-manager-pro' ) );
        }

        // Plus one validation
        if ( ! empty( $data['plus_one_attending'] ) && empty( $data['plus_one_name'] ) ) {
            self::add_error( 'plus_one_name', __( 'اسم المرافق مطلوب', 'invitation-manager-pro' ) );
        }

        if ( ! empty( $data['plus_one_name'] ) ) {
            if ( strlen( $data['plus_one_name'] ) > 255 ) {
                self::add_error( 'plus_one_name', __( 'اسم المرافق طويل جداً', 'invitation-manager-pro' ) );
            } elseif ( ! self::is_valid_name( $data['plus_one_name'] ) ) {
                self::add_error( 'plus_one_name', __( 'اسم المرافق يحتوي على أحرف غير صحيحة', 'invitation-manager-pro' ) );
            }
        }

        // Dietary requirements validation
        if ( ! empty( $data['dietary_requirements'] ) && strlen( $data['dietary_requirements'] ) > 500 ) {
            self::add_error( 'dietary_requirements', __( 'المتطلبات الغذائية طويلة جداً', 'invitation-manager-pro' ) );
        }

        return empty( self::$errors );
    }

    /**
     * Validate invitation token.
     *
     * @param string $token Invitation token.
     * @return bool True if valid, false otherwise.
     */
    public static function validate_invitation_token( $token ) {
        if ( empty( $token ) ) {
            return false;
        }

        // Check token format (should be 32 characters alphanumeric)
        if ( ! preg_match( '/^[a-zA-Z0-9]{32}$/', $token ) ) {
            return false;
        }

        // Check if token exists and is not expired
        $invitation_manager = new IMPRO_Invitation_Manager();
        $invitation = $invitation_manager->get_invitation_by_token( $token );

        if ( ! $invitation ) {
            return false;
        }

        // Check if invitation is expired
        if ( $invitation->status === 'expired' ) {
            return false;
        }

        // Check if event is still valid
        $event_manager = new IMPRO_Event_Manager();
        $event = $event_manager->get_event( $invitation->event_id );

        if ( ! $event || strtotime( $event->event_date ) < strtotime( 'today' ) ) {
            return false;
        }

        return true;
    }

    /**
     * Validate settings data.
     *
     * @param array $data Settings data to validate.
     * @return bool True if valid, false otherwise.
     */
    public static function validate_settings_data( $data ) {
        self::$errors = array();

        // Numeric validations
        if ( isset( $data['default_guests_limit'] ) ) {
            if ( ! is_numeric( $data['default_guests_limit'] ) || $data['default_guests_limit'] < 1 ) {
                self::add_error( 'default_guests_limit', __( 'حد المدعوين يجب أن يكون رقماً موجباً', 'invitation-manager-pro' ) );
            } elseif ( $data['default_guests_limit'] > 10000 ) {
                self::add_error( 'default_guests_limit', __( 'حد المدعوين كبير جداً', 'invitation-manager-pro' ) );
            }
        }

        if ( isset( $data['invitation_expiry'] ) ) {
            if ( ! is_numeric( $data['invitation_expiry'] ) || $data['invitation_expiry'] < 1 ) {
                self::add_error( 'invitation_expiry', __( 'مدة انتهاء الدعوة يجب أن تكون رقماً موجباً', 'invitation-manager-pro' ) );
            } elseif ( $data['invitation_expiry'] > 365 ) {
                self::add_error( 'invitation_expiry', __( 'مدة انتهاء الدعوة طويلة جداً', 'invitation-manager-pro' ) );
            }
        }

        if ( isset( $data['qr_code_size'] ) ) {
            if ( ! is_numeric( $data['qr_code_size'] ) || $data['qr_code_size'] < 50 ) {
                self::add_error( 'qr_code_size', __( 'حجم رمز QR صغير جداً', 'invitation-manager-pro' ) );
            } elseif ( $data['qr_code_size'] > 1000 ) {
                self::add_error( 'qr_code_size', __( 'حجم رمز QR كبير جداً', 'invitation-manager-pro' ) );
            }
        }

        // Email validations
        if ( ! empty( $data['notification_emails'] ) ) {
            $emails = explode( ',', $data['notification_emails'] );
            foreach ( $emails as $email ) {
                $email = trim( $email );
                if ( ! empty( $email ) && ! is_email( $email ) ) {
                    self::add_error( 'notification_emails', sprintf( __( 'البريد الإلكتروني %s غير صحيح', 'invitation-manager-pro' ), $email ) );
                    break;
                }
            }
        }

        // Template validations
        if ( ! empty( $data['email_subject'] ) && strlen( $data['email_subject'] ) > 255 ) {
            self::add_error( 'email_subject', __( 'موضوع البريد الإلكتروني طويل جداً', 'invitation-manager-pro' ) );
        }

        if ( ! empty( $data['email_template'] ) && strlen( $data['email_template'] ) > 5000 ) {
            self::add_error( 'email_template', __( 'قالب البريد الإلكتروني طويل جداً', 'invitation-manager-pro' ) );
        }

        return empty( self::$errors );
    }

    /**
     * Sanitize event data.
     *
     * @param array $data Raw event data.
     * @return array Sanitized event data.
     */
    public static function sanitize_event_data( $data ) {
        return array(
            'name'                 => sanitize_text_field( $data['name'] ?? '' ),
            'event_date'           => sanitize_text_field( $data['event_date'] ?? '' ),
            'event_time'           => sanitize_text_field( $data['event_time'] ?? '' ),
            'venue'                => sanitize_text_field( $data['venue'] ?? '' ),
            'address'              => sanitize_textarea_field( $data['address'] ?? '' ),
            'description'          => sanitize_textarea_field( $data['description'] ?? '' ),
            'invitation_image_url' => esc_url_raw( $data['invitation_image_url'] ?? '' ),
            'invitation_text'      => wp_kses_post( $data['invitation_text'] ?? '' ),
            'location_details'     => sanitize_textarea_field( $data['location_details'] ?? '' ),
            'contact_info'         => sanitize_text_field( $data['contact_info'] ?? '' )
        );
    }

    /**
     * Sanitize guest data.
     *
     * @param array $data Raw guest data.
     * @return array Sanitized guest data.
     */
    public static function sanitize_guest_data( $data ) {
        return array(
            'name'              => sanitize_text_field( $data['name'] ?? '' ),
            'email'             => sanitize_email( $data['email'] ?? '' ),
            'phone'             => sanitize_text_field( $data['phone'] ?? '' ),
            'category'          => sanitize_text_field( $data['category'] ?? '' ),
            'plus_one_allowed'  => isset( $data['plus_one_allowed'] ) ? 1 : 0,
            'gender'            => sanitize_text_field( $data['gender'] ?? '' ),
            'age_range'         => sanitize_text_field( $data['age_range'] ?? '' ),
            'relationship'      => sanitize_text_field( $data['relationship'] ?? '' )
        );
    }

    /**
     * Sanitize RSVP data.
     *
     * @param array $data Raw RSVP data.
     * @return array Sanitized RSVP data.
     */
    public static function sanitize_rsvp_data( $data ) {
        return array(
            'guest_id'             => intval( $data['guest_id'] ?? 0 ),
            'event_id'             => intval( $data['event_id'] ?? 0 ),
            'status'               => sanitize_text_field( $data['status'] ?? '' ),
            'plus_one_attending'   => isset( $data['plus_one_attending'] ) ? 1 : 0,
            'plus_one_name'        => sanitize_text_field( $data['plus_one_name'] ?? '' ),
            'dietary_requirements' => sanitize_textarea_field( $data['dietary_requirements'] ?? '' )
        );
    }

    /**
     * Get validation errors.
     *
     * @return array Validation errors.
     */
    public static function get_errors() {
        return self::$errors;
    }

    /**
     * Get first validation error.
     *
     * @return string|null First error message or null if no errors.
     */
    public static function get_first_error() {
        if ( empty( self::$errors ) ) {
            return null;
        }

        $first_field = array_keys( self::$errors )[0];
        return self::$errors[ $first_field ][0];
    }

    /**
     * Check if there are validation errors.
     *
     * @return bool True if there are errors, false otherwise.
     */
    public static function has_errors() {
        return ! empty( self::$errors );
    }

    /**
     * Clear validation errors.
     */
    public static function clear_errors() {
        self::$errors = array();
    }

    /**
     * Add validation error.
     *
     * @param string $field Field name.
     * @param string $message Error message.
     */
    private static function add_error( $field, $message ) {
        if ( ! isset( self::$errors[ $field ] ) ) {
            self::$errors[ $field ] = array();
        }
        self::$errors[ $field ][] = $message;
    }

    /**
     * Check if date is valid.
     *
     * @param string $date Date string.
     * @return bool True if valid, false otherwise.
     */
    private static function is_valid_date( $date ) {
        $d = DateTime::createFromFormat( 'Y-m-d', $date );
        return $d && $d->format( 'Y-m-d' ) === $date;
    }

    /**
     * Check if time is valid.
     *
     * @param string $time Time string.
     * @return bool True if valid, false otherwise.
     */
    private static function is_valid_time( $time ) {
        return preg_match( '/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time );
    }

    /**
     * Check if URL is valid.
     *
     * @param string $url URL string.
     * @return bool True if valid, false otherwise.
     */
    private static function is_valid_url( $url ) {
        return filter_var( $url, FILTER_VALIDATE_URL ) !== false;
    }

    /**
     * Check if name is valid.
     *
     * @param string $name Name string.
     * @return bool True if valid, false otherwise.
     */
    private static function is_valid_name( $name ) {
        // Allow Arabic, English letters, spaces, and common punctuation
        return preg_match( '/^[\p{Arabic}\p{Latin}\s\.\-\']+$/u', $name );
    }

    /**
     * Check if phone number is valid.
     *
     * @param string $phone Phone number.
     * @return bool True if valid, false otherwise.
     */
    private static function is_valid_phone( $phone ) {
        // Remove all non-digit characters
        $phone = preg_replace( '/\D/', '', $phone );
        
        // Check if it's a valid length (7-15 digits)
        return strlen( $phone ) >= 7 && strlen( $phone ) <= 15;
    }

    /**
     * Check if email already exists.
     *
     * @param string $email Email address.
     * @param int    $exclude_id Guest ID to exclude from check.
     * @return bool True if exists, false otherwise.
     */
    private static function email_exists( $email, $exclude_id = 0 ) {
        global $wpdb;
        
        $database = new IMPRO_Database();
        $table = $database->get_table_name( 'guests' );
        
        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE email = %s AND id != %d",
            $email,
            $exclude_id
        );
        
        return $wpdb->get_var( $query ) > 0;
    }

    /**
     * Get valid guest categories.
     *
     * @return array Valid categories.
     */
    private static function get_valid_guest_categories() {
        return apply_filters( 'impro_valid_guest_categories', array(
            'family',
            'friends',
            'colleagues',
            'vip',
            'other'
        ) );
    }

    /**
     * Get valid age ranges.
     *
     * @return array Valid age ranges.
     */
    private static function get_valid_age_ranges() {
        return apply_filters( 'impro_valid_age_ranges', array(
            'child',
            'teen',
            'adult',
            'senior'
        ) );
    }

    /**
     * Validate file upload.
     *
     * @param array $file File data from $_FILES.
     * @param array $allowed_types Allowed MIME types.
     * @param int   $max_size Maximum file size in bytes.
     * @return bool True if valid, false otherwise.
     */
    public static function validate_file_upload( $file, $allowed_types = array(), $max_size = 0 ) {
        self::$errors = array();

        if ( ! isset( $file['error'] ) || $file['error'] !== UPLOAD_ERR_OK ) {
            self::add_error( 'file', __( 'خطأ في رفع الملف', 'invitation-manager-pro' ) );
            return false;
        }

        if ( empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
            self::add_error( 'file', __( 'الملف غير صحيح', 'invitation-manager-pro' ) );
            return false;
        }

        // Check file size
        if ( $max_size > 0 && $file['size'] > $max_size ) {
            self::add_error( 'file', sprintf( __( 'حجم الملف كبير جداً. الحد الأقصى %s', 'invitation-manager-pro' ), size_format( $max_size ) ) );
            return false;
        }

        // Check MIME type
        if ( ! empty( $allowed_types ) ) {
            $file_type = wp_check_filetype( $file['name'] );
            if ( ! in_array( $file_type['type'], $allowed_types ) ) {
                self::add_error( 'file', __( 'نوع الملف غير مسموح', 'invitation-manager-pro' ) );
                return false;
            }
        }

        return true;
    }

    /**
     * Validate CSV file for guest import.
     *
     * @param string $file_path CSV file path.
     * @return bool True if valid, false otherwise.
     */
    public static function validate_csv_file( $file_path ) {
        self::$errors = array();

        if ( ! file_exists( $file_path ) ) {
            self::add_error( 'csv', __( 'الملف غير موجود', 'invitation-manager-pro' ) );
            return false;
        }

        $handle = fopen( $file_path, 'r' );
        if ( ! $handle ) {
            self::add_error( 'csv', __( 'لا يمكن قراءة الملف', 'invitation-manager-pro' ) );
            return false;
        }

        // Check if file has content
        $first_line = fgetcsv( $handle );
        if ( ! $first_line ) {
            self::add_error( 'csv', __( 'الملف فارغ', 'invitation-manager-pro' ) );
            fclose( $handle );
            return false;
        }

        // Check required columns
        $required_columns = array( 'name' );
        $missing_columns = array_diff( $required_columns, $first_line );
        
        if ( ! empty( $missing_columns ) ) {
            self::add_error( 'csv', sprintf( __( 'أعمدة مفقودة: %s', 'invitation-manager-pro' ), implode( ', ', $missing_columns ) ) );
            fclose( $handle );
            return false;
        }

        fclose( $handle );
        return true;
    }
}


<?php
/**
 * Public interface class.
 *
 * @package Invitation_Manager_Pro
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * IMPRO_Public class.
 */
class IMPRO_Public {

    /**
     * Constructor.
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize public hooks.
     */
    private function init_hooks() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_scripts' ) );
        add_action( 'init', array( $this, 'add_rewrite_rules' ) );
        add_action( 'template_redirect', array( $this, 'handle_invitation_page' ) );
        add_action( 'wp_ajax_impro_submit_rsvp', array( $this, 'ajax_submit_rsvp' ) );
        add_action( 'wp_ajax_nopriv_impro_submit_rsvp', array( $this, 'ajax_submit_rsvp' ) );
        add_shortcode( 'impro_invitation_page', array( $this, 'invitation_page_shortcode' ) );
        add_shortcode( 'impro_rsvp_form', array( $this, 'rsvp_form_shortcode' ) );
        add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
    }

    /**
     * Enqueue public scripts and styles.
     */
    public function enqueue_public_scripts() {
        // Only load on invitation pages
        if ( ! $this->is_invitation_page() ) {
            return;
        }

        // Enqueue public CSS
        wp_enqueue_style(
            'impro-public-style',
            IMPRO_URL . 'assets/css/public.css',
            array(),
            IMPRO_VERSION
        );

        // Enqueue public JavaScript
        wp_enqueue_script(
            'impro-public-script',
            IMPRO_URL . 'assets/js/public.js',
            array( 'jquery' ),
            IMPRO_VERSION,
            true
        );

        // Localize script
        wp_localize_script( 'impro-public-script', 'impro_public', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'impro_public_nonce' ),
            'strings'  => array(
                'submitting' => __( 'جاري الإرسال...', 'invitation-manager-pro' ),
                'submitted'  => __( 'تم الإرسال بنجاح', 'invitation-manager-pro' ),
                'error'      => __( 'حدث خطأ', 'invitation-manager-pro' ),
                'required'   => __( 'هذا الحقل مطلوب', 'invitation-manager-pro' )
            )
        ) );
    }

    /**
     * Add rewrite rules for invitation URLs.
     */
    public function add_rewrite_rules() {
        add_rewrite_rule(
            '^invitation/([a-zA-Z0-9]+)/?$',
            'index.php?invitation_token=$matches[1]',
            'top'
        );
    }

    /**
     * Add query vars.
     *
     * @param array $vars Query vars.
     * @return array Modified query vars.
     */
    public function add_query_vars( $vars ) {
        $vars[] = 'invitation_token';
        return $vars;
    }

    /**
     * Handle invitation page display.
     */
    public function handle_invitation_page() {
        $token = get_query_var( 'invitation_token' );
        
        if ( ! $token ) {
            return;
        }

        // Validate token
        $security = new IMPRO_Security();
        if ( ! $security->validate_invitation_token( $token ) ) {
            wp_die( __( 'رابط الدعوة غير صحيح أو منتهي الصلاحية', 'invitation-manager-pro' ) );
        }

        // Track invitation access
        do_action( 'impro_invitation_accessed', $token );

        // Mark invitation as opened
        $invitation_manager = new IMPRO_Invitation_Manager();
        $invitation_manager->mark_as_opened( $token );

        // Load invitation template
        $this->load_invitation_template( $token );
    }

    /**
     * Load invitation template.
     *
     * @param string $token Invitation token.
     */
    private function load_invitation_template( $token ) {
        $invitation_manager = new IMPRO_Invitation_Manager();
        $guest_manager = new IMPRO_Guest_Manager();
        $event_manager = new IMPRO_Event_Manager();
        $rsvp_manager = new IMPRO_RSVP_Manager();

        $invitation = $invitation_manager->get_invitation_by_token( $token );
        $guest = $guest_manager->get_guest( $invitation->guest_id );
        $event = $event_manager->get_event( $invitation->event_id );
        $rsvp = $rsvp_manager->get_rsvp_by_guest_event( $invitation->guest_id, $invitation->event_id );

        // Check for custom template in theme
        $template_path = get_template_directory() . '/impro-invitation.php';
        if ( file_exists( $template_path ) ) {
            include $template_path;
        } else {
            include IMPRO_PATH . 'public/invitation-template.php';
        }
        exit;
    }

    /**
     * Invitation page shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string Shortcode output.
     */
    public function invitation_page_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'token' => get_query_var( 'invitation_token' )
        ), $atts );

        if ( ! $atts['token'] ) {
            return __( 'رمز الدعوة مطلوب', 'invitation-manager-pro' );
        }

        // Validate token
        $security = new IMPRO_Security();
        if ( ! $security->validate_invitation_token( $atts['token'] ) ) {
            return __( 'رابط الدعوة غير صحيح أو منتهي الصلاحية', 'invitation-manager-pro' );
        }

        ob_start();
        $this->display_invitation_content( $atts['token'] );
        return ob_get_clean();
    }

    /**
     * RSVP form shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string Shortcode output.
     */
    public function rsvp_form_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'token' => get_query_var( 'invitation_token' )
        ), $atts );

        if ( ! $atts['token'] ) {
            return __( 'رمز الدعوة مطلوب', 'invitation-manager-pro' );
        }

        ob_start();
        $this->display_rsvp_form( $atts['token'] );
        return ob_get_clean();
    }

    /**
     * Display invitation content.
     *
     * @param string $token Invitation token.
     */
    private function display_invitation_content( $token ) {
        $invitation_manager = new IMPRO_Invitation_Manager();
        $guest_manager = new IMPRO_Guest_Manager();
        $event_manager = new IMPRO_Event_Manager();

        $invitation = $invitation_manager->get_invitation_by_token( $token );
        $guest = $guest_manager->get_guest( $invitation->guest_id );
        $event = $event_manager->get_event( $invitation->event_id );

        include IMPRO_PATH . 'public/invitation-content.php';
    }

    /**
     * Display RSVP form.
     *
     * @param string $token Invitation token.
     */
    private function display_rsvp_form( $token ) {
        $invitation_manager = new IMPRO_Invitation_Manager();
        $guest_manager = new IMPRO_Guest_Manager();
        $event_manager = new IMPRO_Event_Manager();
        $rsvp_manager = new IMPRO_RSVP_Manager();

        $invitation = $invitation_manager->get_invitation_by_token( $token );
        $guest = $guest_manager->get_guest( $invitation->guest_id );
        $event = $event_manager->get_event( $invitation->event_id );
        $rsvp = $rsvp_manager->get_rsvp_by_guest_event( $invitation->guest_id, $invitation->event_id );

        include IMPRO_PATH . 'public/rsvp-form.php';
    }

    /**
     * AJAX submit RSVP.
     */
    public function ajax_submit_rsvp() {
        check_ajax_referer( 'impro_public_nonce', 'nonce' );

        $token = sanitize_text_field( $_POST['token'] );
        $status = sanitize_text_field( $_POST['status'] );
        $plus_one_attending = isset( $_POST['plus_one_attending'] ) ? 1 : 0;
        $plus_one_name = sanitize_text_field( $_POST['plus_one_name'] ?? '' );
        $dietary_requirements = sanitize_textarea_field( $_POST['dietary_requirements'] ?? '' );

        // Validate token
        $security = new IMPRO_Security();
        if ( ! $security->validate_invitation_token( $token ) ) {
            wp_send_json_error( __( 'رابط الدعوة غير صحيح', 'invitation-manager-pro' ) );
        }

        // Get invitation
        $invitation_manager = new IMPRO_Invitation_Manager();
        $invitation = $invitation_manager->get_invitation_by_token( $token );

        if ( ! $invitation ) {
            wp_send_json_error( __( 'الدعوة غير موجودة', 'invitation-manager-pro' ) );
        }

        // Validate status
        if ( ! in_array( $status, array( 'accepted', 'declined' ) ) ) {
            wp_send_json_error( __( 'حالة الرد غير صحيحة', 'invitation-manager-pro' ) );
        }

        // Save RSVP
        $rsvp_manager = new IMPRO_RSVP_Manager();
        $rsvp_data = array(
            'guest_id'             => $invitation->guest_id,
            'event_id'             => $invitation->event_id,
            'status'               => $status,
            'plus_one_attending'   => $plus_one_attending,
            'plus_one_name'        => $plus_one_name,
            'dietary_requirements' => $dietary_requirements
        );

        $rsvp_id = $rsvp_manager->save_rsvp( $rsvp_data );

        if ( $rsvp_id ) {
            // Send notification email to admin
            $this->send_rsvp_notification( $invitation, $rsvp_data );
            
            wp_send_json_success( array(
                'message' => __( 'تم حفظ ردكم بنجاح', 'invitation-manager-pro' ),
                'status'  => $status
            ) );
        } else {
            wp_send_json_error( __( 'فشل في حفظ الرد', 'invitation-manager-pro' ) );
        }
    }

    /**
     * Send RSVP notification to admin.
     *
     * @param object $invitation Invitation object.
     * @param array  $rsvp_data  RSVP data.
     */
    private function send_rsvp_notification( $invitation, $rsvp_data ) {
        $notification_emails = get_option( 'impro_notification_emails' );
        
        if ( ! $notification_emails ) {
            return;
        }

        $guest_manager = new IMPRO_Guest_Manager();
        $event_manager = new IMPRO_Event_Manager();

        $guest = $guest_manager->get_guest( $invitation->guest_id );
        $event = $event_manager->get_event( $invitation->event_id );

        $subject = sprintf( 
            __( 'رد جديد على دعوة %s', 'invitation-manager-pro' ), 
            $event->name 
        );

        $status_text = $rsvp_data['status'] === 'accepted' ? __( 'موافق', 'invitation-manager-pro' ) : __( 'معتذر', 'invitation-manager-pro' );

        $message = sprintf(
            __( 'تم استلام رد جديد على دعوة %s:<br><br>الضيف: %s<br>الرد: %s<br>المرافق: %s<br><br>يمكنك مراجعة جميع الردود من لوحة التحكم.', 'invitation-manager-pro' ),
            $event->name,
            $guest->name,
            $status_text,
            $rsvp_data['plus_one_attending'] ? __( 'نعم', 'invitation-manager-pro' ) : __( 'لا', 'invitation-manager-pro' )
        );

        $headers = array( 'Content-Type: text/html; charset=UTF-8' );
        wp_mail( $notification_emails, $subject, $message, $headers );
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
     * Generate QR code for invitation.
     *
     * @param string $token Invitation token.
     * @return string QR code HTML.
     */
    public function generate_invitation_qr( $token ) {
        if ( ! get_option( 'impro_enable_qr_codes', 1 ) ) {
            return '';
        }

        $qr_generator = new IMPRO_QR_Generator();
        $qr_url = $qr_generator->get_cached_invitation_qr( $token, array(
            'size' => get_option( 'impro_qr_code_size', 200 )
        ) );

        if ( $qr_url ) {
            return sprintf(
                '<div class="impro-qr-code"><img src="%s" alt="%s" /></div>',
                esc_url( $qr_url ),
                esc_attr__( 'رمز QR للدعوة', 'invitation-manager-pro' )
            );
        }

        return '';
    }

    /**
     * Get invitation URL.
     *
     * @param string $token Invitation token.
     * @return string Invitation URL.
     */
    public function get_invitation_url( $token ) {
        return home_url( '/invitation/' . $token );
    }

    /**
     * Check if guest can bring plus one.
     *
     * @param object $guest Guest object.
     * @return bool True if plus one allowed, false otherwise.
     */
    public function can_bring_plus_one( $guest ) {
        return get_option( 'impro_enable_plus_one', 1 ) && $guest->plus_one_allowed;
    }
}


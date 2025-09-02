<?php
/**
 * Admin interface class.
 *
 * @package Invitation_Manager_Pro
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * IMPRO_Admin class.
 */
class IMPRO_Admin {

    /**
     * Constructor.
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize admin hooks.
     */
    private function init_hooks() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        add_action( 'admin_init', array( $this, 'handle_admin_actions' ) );
        add_action( 'wp_ajax_impro_send_invitation', array( $this, 'ajax_send_invitation' ) );
        add_action( 'wp_ajax_impro_bulk_send_invitations', array( $this, 'ajax_bulk_send_invitations' ) );
        add_action( 'wp_ajax_impro_export_data', array( $this, 'ajax_export_data' ) );
        add_filter( 'set-screen-option', array( $this, 'set_screen_option' ), 10, 3 );
    }

    /**
     * Add admin menu.
     */
    public function add_admin_menu() {
        $capability = 'manage_events';
        
        // Main menu
        add_menu_page(
            __( 'إدارة الدعوات', 'invitation-manager-pro' ),
            __( 'إدارة الدعوات', 'invitation-manager-pro' ),
            $capability,
            'impro-dashboard',
            array( $this, 'dashboard_page' ),
            'dashicons-calendar-alt',
            30
        );

        // Dashboard
        add_submenu_page(
            'impro-dashboard',
            __( 'لوحة التحكم', 'invitation-manager-pro' ),
            __( 'لوحة التحكم', 'invitation-manager-pro' ),
            $capability,
            'impro-dashboard',
            array( $this, 'dashboard_page' )
        );

        // Events
        add_submenu_page(
            'impro-dashboard',
            __( 'المناسبات', 'invitation-manager-pro' ),
            __( 'المناسبات', 'invitation-manager-pro' ),
            $capability,
            'impro-events',
            array( $this, 'events_page' )
        );

        // Guests
        add_submenu_page(
            'impro-dashboard',
            __( 'المدعوين', 'invitation-manager-pro' ),
            __( 'المدعوين', 'invitation-manager-pro' ),
            'manage_guests',
            'impro-guests',
            array( $this, 'guests_page' )
        );

        // Invitations
        add_submenu_page(
            'impro-dashboard',
            __( 'الدعوات', 'invitation-manager-pro' ),
            __( 'الدعوات', 'invitation-manager-pro' ),
            'manage_invitations',
            'impro-invitations',
            array( $this, 'invitations_page' )
        );

        // RSVPs
        add_submenu_page(
            'impro-dashboard',
            __( 'ردود الحضور', 'invitation-manager-pro' ),
            __( 'ردود الحضور', 'invitation-manager-pro' ),
            'manage_invitations',
            'impro-rsvps',
            array( $this, 'rsvps_page' )
        );

        // Statistics
        add_submenu_page(
            'impro-dashboard',
            __( 'الإحصائيات', 'invitation-manager-pro' ),
            __( 'الإحصائيات', 'invitation-manager-pro' ),
            'view_statistics',
            'impro-statistics',
            array( $this, 'statistics_page' )
        );

        // Settings
        add_submenu_page(
            'impro-dashboard',
            __( 'الإعدادات', 'invitation-manager-pro' ),
            __( 'الإعدادات', 'invitation-manager-pro' ),
            'manage_options',
            'impro-settings',
            array( $this, 'settings_page' )
        );
    }

    /**
     * Enqueue admin scripts and styles.
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_admin_scripts( $hook ) {
        // Only load on plugin pages
        if ( strpos( $hook, 'impro' ) === false ) {
            return;
        }

        // Enqueue WordPress media scripts
        wp_enqueue_media();

        // Enqueue admin CSS
        wp_enqueue_style(
            'impro-admin-style',
            IMPRO_URL . 'assets/css/admin.css',
            array(),
            IMPRO_VERSION
        );

        // Enqueue admin JavaScript
        wp_enqueue_script(
            'impro-admin-script',
            IMPRO_URL . 'assets/js/admin.js',
            array( 'jquery', 'wp-util' ),
            IMPRO_VERSION,
            true
        );

        // Localize script
        wp_localize_script( 'impro-admin-script', 'impro_admin', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'impro_admin_nonce' ),
            'strings'  => array(
                'confirm_delete' => __( 'هل أنت متأكد من الحذف؟', 'invitation-manager-pro' ),
                'sending'        => __( 'جاري الإرسال...', 'invitation-manager-pro' ),
                'sent'           => __( 'تم الإرسال', 'invitation-manager-pro' ),
                'error'          => __( 'حدث خطأ', 'invitation-manager-pro' ),
                'confirm_send'   => __( 'هل أنت متأكد من إرسال الدعوات المحددة؟', 'invitation-manager-pro' ),
                'exporting'      => __( 'جاري التصدير...', 'invitation-manager-pro' )
            )
        ) );

        // Chart.js for statistics
        if ( $hook === 'invitation-manager-pro_page_impro-statistics' ) {
            wp_enqueue_script(
                'chart-js',
                'https://cdn.jsdelivr.net/npm/chart.js',
                array(),
                '3.9.1',
                true
            );
        }
    }

    /**
     * Handle admin actions.
     */
    public function handle_admin_actions() {
        // Handle admin-post.php actions
        add_action( 'admin_post_impro_admin_action', array( $this, 'process_admin_action' ) );
    }

    /**
     * Process admin action.
     */
    public function process_admin_action() {
        // التحقق من البيانات الأساسية
        if ( ! isset( $_POST['impro_action'] ) || ! isset( $_POST['_wpnonce'] ) ) {
            wp_die( __( 'بيانات غير صحيحة', 'invitation-manager-pro' ) );
        }

        $action = sanitize_text_field( $_POST['impro_action'] );
        $nonce = sanitize_text_field( $_POST['_wpnonce'] );

        // التحقق من Nonce
        if ( ! wp_verify_nonce( $nonce, 'impro_admin_action' ) ) {
            wp_die( __( 'فشل في التحقق من الأمان', 'invitation-manager-pro' ) );
        }

        // معالجة الإجراءات مع تسجيل الأخطاء
        try {
            switch ( $action ) {
                case 'create_event':
                    $this->handle_create_event();
                    break;
                case 'update_event':
                    $this->handle_update_event();
                    break;
                case 'delete_event':
                    $this->handle_delete_event();
                    break;
                case 'create_guest':
                    $this->handle_create_guest();
                    break;
                case 'update_guest':
                    $this->handle_update_guest();
                    break;
                case 'delete_guest':
                    $this->handle_delete_guest();
                    break;
                case 'import_guests':
                    $this->handle_import_guests();
                    break;
                case 'save_settings':
                    $this->handle_save_settings();
                    break;
                case 'generate_invitations':
                    $this->handle_generate_invitations();
                    break;
                case 'reset_invitation':
                    $this->handle_reset_invitation();
                    break;
                default:
                    wp_die( __( 'إجراء غير صحيح', 'invitation-manager-pro' ) );
            }
        } catch ( Exception $e ) {
            error_log( 'Admin action failed: ' . $e->getMessage() );
            wp_die( __( 'حدث خطأ أثناء معالجة الإجراء', 'invitation-manager-pro' ) );
        }
    }

    /**
     * Dashboard page.
     */
    public function dashboard_page() {
        $event_manager = new IMPRO_Event_Manager();
        $guest_manager = new IMPRO_Guest_Manager();
        $rsvp_manager = new IMPRO_RSVP_Manager();
        $invitation_manager = new IMPRO_Invitation_Manager();

        $stats = array(
            'events' => $event_manager->get_event_statistics(),
            'guests' => $guest_manager->get_guest_statistics(),
            'rsvps'  => $rsvp_manager->get_overall_rsvp_statistics(),
            'invitations' => $invitation_manager->get_invitation_statistics()
        );

        include IMPRO_PATH . 'admin/dashboard.php';
    }

    /**
     * Events page.
     */
    public function events_page() {
        $event_manager = new IMPRO_Event_Manager();
        
        $action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : 'list';
        $event_id = isset( $_GET['event_id'] ) ? intval( $_GET['event_id'] ) : 0;

        switch ( $action ) {
            case 'add':
                include IMPRO_PATH . 'admin/event-form.php';
                break;
            case 'edit':
                $event = $event_manager->get_event( $event_id );
                if ( ! $event ) {
                    wp_die( __( 'المناسبة غير موجودة', 'invitation-manager-pro' ) );
                }
                include IMPRO_PATH . 'admin/event-form.php';
                break;
            case 'view':
                $event = $event_manager->get_event( $event_id );
                if ( ! $event ) {
                    wp_die( __( 'المناسبة غير موجودة', 'invitation-manager-pro' ) );
                }
                include IMPRO_PATH . 'admin/event-view.php';
                break;
            default:
                $events = $event_manager->get_events();
                include IMPRO_PATH . 'admin/events-list.php';
                break;
        }
    }

    /**
     * Guests page.
     */
    public function guests_page() {
        $guest_manager = new IMPRO_Guest_Manager();
        
        $action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : 'list';
        $guest_id = isset( $_GET['guest_id'] ) ? intval( $_GET['guest_id'] ) : 0;

        switch ( $action ) {
            case 'add':
                include IMPRO_PATH . 'admin/guest-form.php';
                break;
            case 'edit':
                $guest = $guest_manager->get_guest( $guest_id );
                if ( ! $guest ) {
                    wp_die( __( 'الضيف غير موجود', 'invitation-manager-pro' ) );
                }
                include IMPRO_PATH . 'admin/guest-form.php';
                break;
            case 'import':
                include IMPRO_PATH . 'admin/guests-import.php';
                break;
            default:
                $guests = $guest_manager->get_guests();
                include IMPRO_PATH . 'admin/guests-list.php';
                break;
        }
    }

    /**
     * Invitations page.
     */
    public function invitations_page() {
        // التحقق من وجود الكائنات المطلوبة
        if ( ! class_exists( 'IMPRO_Invitation_Manager' ) || ! class_exists( 'IMPRO_Event_Manager' ) ) {
            wp_die( __( 'المكتبات المطلوبة غير متوفرة', 'invitation-manager-pro' ) );
        }

        $invitation_manager = new IMPRO_Invitation_Manager();
        $event_manager = new IMPRO_Event_Manager();
        
        $event_id = isset( $_GET['event_id'] ) ? intval( $_GET['event_id'] ) : 0;
        
        if ( $event_id ) {
            $event = $event_manager->get_event( $event_id );
            if ( ! $event ) {
                wp_die( __( 'المناسبة غير موجودة', 'invitation-manager-pro' ) );
            }
            $invitations = $invitation_manager->get_event_invitations( $event_id );
        } else {
            $event = null;
            // الحصول على جميع الدعوات مع التصفح
            $paged = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
            $per_page = 20;
            $offset = ( $paged - 1 ) * $per_page;
            
            $invitations = $invitation_manager->get_all_invitations( array(
                'limit' => $per_page,
                'offset' => $offset,
                'orderby' => 'created_at',
                'order' => 'DESC'
            ) );
        }
        
        $events = $event_manager->get_events();
        
        include IMPRO_PATH . 'admin/invitations-list.php';
    }

    /**
     * RSVPs page.
     */
    public function rsvps_page() {
        // التحقق من وجود الكائنات المطلوبة
        if ( ! class_exists( 'IMPRO_RSVP_Manager' ) || ! class_exists( 'IMPRO_Event_Manager' ) ) {
            wp_die( __( 'المكتبات المطلوبة غير متوفرة', 'invitation-manager-pro' ) );
        }

        $rsvp_manager = new IMPRO_RSVP_Manager();
        $event_manager = new IMPRO_Event_Manager();
        
        $event_id = isset( $_GET['event_id'] ) ? intval( $_GET['event_id'] ) : 0;
        $status = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : 'all';
        
        if ( $event_id ) {
            $event = $event_manager->get_event( $event_id );
            if ( ! $event ) {
                wp_die( __( 'المناسبة غير موجودة', 'invitation-manager-pro' ) );
            }
            $rsvps = $rsvp_manager->get_event_rsvps( $event_id, array( 'status' => $status ) );
            $stats = $rsvp_manager->get_event_rsvp_statistics( $event_id );
        } else {
            $event = null;
            $rsvps = method_exists( $rsvp_manager, 'get_all_rsvps' ) ? $rsvp_manager->get_all_rsvps() : array();
            $stats = $rsvp_manager->get_overall_rsvp_statistics();
        }
        
        $events = $event_manager->get_events();
        
        include IMPRO_PATH . 'admin/rsvps-list.php';
    }

    /**
     * Statistics page.
     */
    public function statistics_page() {
        $event_manager = new IMPRO_Event_Manager();
        $guest_manager = new IMPRO_Guest_Manager();
        $rsvp_manager = new IMPRO_RSVP_Manager();
        $invitation_manager = new IMPRO_Invitation_Manager();

        $stats = array(
            'events' => $event_manager->get_event_statistics(),
            'guests' => $guest_manager->get_guest_statistics(),
            'rsvps'  => $rsvp_manager->get_overall_rsvp_statistics(),
            'invitations' => $invitation_manager->get_invitation_statistics()
        );

        include IMPRO_PATH . 'admin/statistics.php';
    }

    /**
     * Settings page.
     */
    public function settings_page() {
        include IMPRO_PATH . 'admin/settings.php';
    }

    /**
     * Handle create event.
     */
    private function handle_create_event() {
        if ( ! current_user_can( 'manage_events' ) ) {
            wp_die( __( 'غير مصرح لك بهذا الإجراء', 'invitation-manager-pro' ) );
        }

        $event_data = $this->sanitize_event_form_data( $_POST );
        $event_manager = new IMPRO_Event_Manager();
        
        $event_id = $event_manager->create_event( $event_data );
        
        if ( $event_id ) {
            wp_redirect( admin_url( 'admin.php?page=impro-events&action=view&event_id=' . $event_id . '&message=created' ) );
        } else {
            wp_redirect( admin_url( 'admin.php?page=impro-events&action=add&error=create_failed' ) );
        }
        exit;
    }

    /**
     * Handle update event.
     */
    private function handle_update_event() {
        if ( ! current_user_can( 'manage_events' ) ) {
            wp_die( __( 'غير مصرح لك بهذا الإجراء', 'invitation-manager-pro' ) );
        }

        $event_id = intval( $_POST['event_id'] );
        $event_data = $this->sanitize_event_form_data( $_POST );
        $event_manager = new IMPRO_Event_Manager();
        
        $result = $event_manager->update_event( $event_id, $event_data );
        
        if ( $result ) {
            wp_redirect( admin_url( 'admin.php?page=impro-events&action=view&event_id=' . $event_id . '&message=updated' ) );
        } else {
            wp_redirect( admin_url( 'admin.php?page=impro-events&action=edit&event_id=' . $event_id . '&error=update_failed' ) );
        }
        exit;
    }

    /**
     * Handle delete event.
     */
    private function handle_delete_event() {
        if ( ! current_user_can( 'manage_events' ) ) {
            wp_die( __( 'غير مصرح لك بهذا الإجراء', 'invitation-manager-pro' ) );
        }

        $event_id = intval( $_POST['event_id'] );
        $event_manager = new IMPRO_Event_Manager();
        
        $result = $event_manager->delete_event( $event_id );
        
        if ( $result ) {
            wp_redirect( admin_url( 'admin.php?page=impro-events&message=deleted' ) );
        } else {
            wp_redirect( admin_url( 'admin.php?page=impro-events&error=delete_failed' ) );
        }
        exit;
    }

    /**
     * Handle create guest.
     */
    private function handle_create_guest() {
        if ( ! current_user_can( 'manage_guests' ) ) {
            wp_die( __( 'غير مصرح لك بهذا الإجراء', 'invitation-manager-pro' ) );
        }

        $guest_data = $this->sanitize_guest_form_data( $_POST );
        $guest_manager = new IMPRO_Guest_Manager();
        
        $guest_id = $guest_manager->create_guest( $guest_data );
        
        if ( $guest_id ) {
            wp_redirect( admin_url( 'admin.php?page=impro-guests&message=created' ) );
        } else {
            wp_redirect( admin_url( 'admin.php?page=impro-guests&action=add&error=create_failed' ) );
        }
        exit;
    }

    /**
     * Handle update guest.
     */
    private function handle_update_guest() {
        if ( ! current_user_can( 'manage_guests' ) ) {
            wp_die( __( 'غير مصرح لك بهذا الإجراء', 'invitation-manager-pro' ) );
        }

        $guest_id = intval( $_POST['guest_id'] );
        $guest_data = $this->sanitize_guest_form_data( $_POST );
        $guest_manager = new IMPRO_Guest_Manager();
        
        $result = $guest_manager->update_guest( $guest_id, $guest_data );
        
        if ( $result ) {
            wp_redirect( admin_url( 'admin.php?page=impro-guests&message=updated' ) );
        } else {
            wp_redirect( admin_url( 'admin.php?page=impro-guests&action=edit&guest_id=' . $guest_id . '&error=update_failed' ) );
        }
        exit;
    }

    /**
     * Handle delete guest.
     */
    private function handle_delete_guest() {
        if ( ! current_user_can( 'manage_guests' ) ) {
            wp_die( __( 'غير مصرح لك بهذا الإجراء', 'invitation-manager-pro' ) );
        }

        $guest_id = intval( $_POST['guest_id'] );
        $guest_manager = new IMPRO_Guest_Manager();
        
        $result = $guest_manager->delete_guest( $guest_id );
        
        if ( $result ) {
            wp_redirect( admin_url( 'admin.php?page=impro-guests&message=deleted' ) );
        } else {
            wp_redirect( admin_url( 'admin.php?page=impro-guests&error=delete_failed' ) );
        }
        exit;
    }

    /**
     * Handle import guests.
     */
    private function handle_import_guests() {
        if ( ! current_user_can( 'manage_guests' ) ) {
            wp_die( __( 'غير مصرح لك بهذا الإجراء', 'invitation-manager-pro' ) );
        }

        if ( ! isset( $_FILES['csv_file'] ) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK ) {
            wp_redirect( admin_url( 'admin.php?page=impro-guests&action=import&error=upload_failed' ) );
            exit;
        }

        $guest_manager = new IMPRO_Guest_Manager();
        $result = $guest_manager->import_guests_from_csv( $_FILES['csv_file']['tmp_name'] );
        
        if ( $result['success'] ) {
            wp_redirect( admin_url( 'admin.php?page=impro-guests&message=imported&imported=' . $result['imported'] ) );
        } else {
            wp_redirect( admin_url( 'admin.php?page=impro-guests&action=import&error=import_failed' ) );
        }
        exit;
    }

    /**
     * Handle save settings.
     */
    private function handle_save_settings() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'غير مصرح لك بهذا الإجراء', 'invitation-manager-pro' ) );
        }

        $settings = $this->sanitize_settings_data( $_POST );
        
        foreach ( $settings as $key => $value ) {
            update_option( $key, $value );
        }
        
        wp_redirect( admin_url( 'admin.php?page=impro-settings&message=saved' ) );
        exit;
    }

    /**
     * Handle generate invitations.
     */
    private function handle_generate_invitations() {
        if ( ! current_user_can( 'manage_invitations' ) ) {
            wp_die( __( 'غير مصرح لك بهذا الإجراء', 'invitation-manager-pro' ) );
        }

        $event_id = intval( $_POST['event_id'] );
        $guest_ids = isset( $_POST['guest_ids'] ) ? array_map( 'intval', $_POST['guest_ids'] ) : array();
        
        if ( empty( $event_id ) ) {
            wp_redirect( admin_url( 'admin.php?page=impro-invitations&error=no_event' ) );
            exit;
        }

        $invitation_manager = new IMPRO_Invitation_Manager();
        $result = $invitation_manager->create_bulk_invitations( $event_id, $guest_ids );
        
        if ( $result['created'] > 0 ) {
            $message = sprintf( 
                __( 'تم إنشاء %d دعوة بنجاح', 'invitation-manager-pro' ), 
                $result['created'] 
            );
            wp_redirect( admin_url( 'admin.php?page=impro-invitations&event_id=' . $event_id . '&message=generated&count=' . $result['created'] ) );
        } else {
            wp_redirect( admin_url( 'admin.php?page=impro-invitations&event_id=' . $event_id . '&error=generate_failed' ) );
        }
        exit;
    }

    /**
     * Handle reset invitation.
     */
    private function handle_reset_invitation() {
        if ( ! current_user_can( 'manage_invitations' ) ) {
            wp_die( __( 'غير مصرح لك بهذا الإجراء', 'invitation-manager-pro' ) );
        }

        $invitation_id = intval( $_POST['invitation_id'] );
        $invitation_manager = new IMPRO_Invitation_Manager();
        
        // الحصول على الدعوة الحالية
        $invitation = $invitation_manager->get_invitation( $invitation_id );
        if ( ! $invitation ) {
            wp_redirect( admin_url( 'admin.php?page=impro-invitations&error=not_found' ) );
            exit;
        }
        
        // تحديث الدعوة بإنشاء رمز جديد
        $new_token = $invitation_manager->generate_unique_token();
        $result = $invitation_manager->update_invitation( $invitation_id, array(
            'unique_token' => $new_token,
            'is_sent' => 0,
            'is_opened' => 0,
            'sent_at' => null,
            'opened_at' => null,
            'status' => 'pending'
        ) );
        
        if ( $result ) {
            wp_redirect( admin_url( 'admin.php?page=impro-invitations&event_id=' . $invitation->event_id . '&message=reset' ) );
        } else {
            wp_redirect( admin_url( 'admin.php?page=impro-invitations&event_id=' . $invitation->event_id . '&error=reset_failed' ) );
        }
        exit;
    }

    /**
     * AJAX send invitation.
     */
    public function ajax_send_invitation() {
        check_ajax_referer( 'impro_admin_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_invitations' ) ) {
            wp_send_json_error( __( 'غير مصرح لك بهذا الإجراء', 'invitation-manager-pro' ) );
        }

        $invitation_id = intval( $_POST['invitation_id'] );
        $invitation_manager = new IMPRO_Invitation_Manager();
        
        $result = $invitation_manager->send_invitation( $invitation_id );
        
        if ( $result ) {
            wp_send_json_success( __( 'تم إرسال الدعوة بنجاح', 'invitation-manager-pro' ) );
        } else {
            wp_send_json_error( __( 'فشل في إرسال الدعوة', 'invitation-manager-pro' ) );
        }
    }

    /**
     * AJAX bulk send invitations.
     */
    public function ajax_bulk_send_invitations() {
        check_ajax_referer( 'impro_admin_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_invitations' ) ) {
            wp_send_json_error( __( 'غير مصرح لك بهذا الإجراء', 'invitation-manager-pro' ) );
        }

        $invitation_ids = array_map( 'intval', $_POST['invitation_ids'] );
        $invitation_manager = new IMPRO_Invitation_Manager();
        
        $sent = 0;
        $failed = 0;
        
        foreach ( $invitation_ids as $invitation_id ) {
            if ( $invitation_manager->send_invitation( $invitation_id ) ) {
                $sent++;
            } else {
                $failed++;
            }
        }
        
        wp_send_json_success( array(
            'sent'   => $sent,
            'failed' => $failed,
            'message' => sprintf( __( 'تم إرسال %d دعوة، فشل في إرسال %d دعوة', 'invitation-manager-pro' ), $sent, $failed )
        ) );
    }

    /**
     * AJAX export data.
     */
    public function ajax_export_data() {
        check_ajax_referer( 'impro_admin_nonce', 'nonce' );
        
        if ( ! current_user_can( 'export_data' ) ) {
            wp_send_json_error( __( 'غير مصرح لك بهذا الإجراء', 'invitation-manager-pro' ) );
        }

        // التحقق من صحة البيانات
        if ( ! isset( $_POST['type'] ) ) {
            wp_send_json_error( __( 'نوع التصدير غير محدد', 'invitation-manager-pro' ) );
        }

        $type = sanitize_text_field( $_POST['type'] );
        $event_id = isset( $_POST['event_id'] ) ? intval( $_POST['event_id'] ) : 0;
        
        $file_path = false;
        
        try {
            switch ( $type ) {
                case 'guests':
                    $guest_manager = new IMPRO_Guest_Manager();
                    $file_path = $guest_manager->export_guests_to_csv();
                    break;
                case 'rsvps':
                    $rsvp_manager = new IMPRO_RSVP_Manager();
                    $file_path = $rsvp_manager->export_rsvps_to_csv( $event_id );
                    break;
                case 'invitations':
                    $file_path = $this->export_invitations_to_csv( $event_id );
                    break;
                default:
                    wp_send_json_error( __( 'نوع التصدير غير صحيح', 'invitation-manager-pro' ) );
                    return;
            }
            
            if ( $file_path && file_exists( $file_path ) ) {
                $upload_dir = wp_upload_dir();
                $file_url = str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $file_path );
                wp_send_json_success( array( 
                    'url' => $file_url,
                    'message' => __( 'تم التصدير بنجاح', 'invitation-manager-pro' )
                ) );
            } else {
                wp_send_json_error( __( 'فشل في تصدير البيانات', 'invitation-manager-pro' ) );
            }
            
        } catch ( Exception $e ) {
            error_log( 'Export failed: ' . $e->getMessage() );
            wp_send_json_error( __( 'حدث خطأ أثناء التصدير', 'invitation-manager-pro' ) );
        }
    }

    /**
     * Export invitations to CSV.
     *
     * @param int $event_id Event ID.
     * @return string|false File path on success, false on failure.
     */
    private function export_invitations_to_csv( $event_id = 0 ) {
        $invitation_manager = new IMPRO_Invitation_Manager();
        
        if ( $event_id > 0 ) {
            $invitations = $invitation_manager->get_event_invitations( $event_id );
        } else {
            $invitations = $invitation_manager->get_all_invitations();
        }
        
        $upload_dir = wp_upload_dir();
        $filename = 'invitations_export_' . date( 'Y-m-d_H-i-s' ) . '.csv';
        $filepath = $upload_dir['path'] . '/' . $filename;

        $handle = fopen( $filepath, 'w' );
        if ( ! $handle ) {
            return false;
        }

        // كتابة BOM للدعم العربي
        fprintf( $handle, chr(0xEF).chr(0xBB).chr(0xBF) );
        
        // كتابة العناوين
        fputcsv( $handle, array(
            'الضيف', 'البريد الإلكتروني', 'المناسبة', 'الحالة', 'مرسلة', 'مشاهد', 'تاريخ الإنشاء'
        ) );

        // كتابة البيانات
        foreach ( $invitations as $invitation ) {
            fputcsv( $handle, array(
                $invitation->guest_name ?? '',
                $invitation->guest_email ?? '',
                $invitation->event_name ?? '',
                $this->get_invitation_status_label( $invitation->status ?? 'pending' ),
                ( $invitation->is_sent ?? 0 ) ? 'نعم' : 'لا',
                ( $invitation->is_opened ?? 0 ) ? 'نعم' : 'لا',
                $invitation->created_at ?? ''
            ) );
        }

        fclose( $handle );
        return $filepath;
    }

    /**
     * Get invitation status label.
     *
     * @param string $status Status.
     * @return string Status label.
     */
    private function get_invitation_status_label( $status ) {
        $labels = array(
            'pending' => 'معلقة',
            'sent' => 'مرسلة',
            'viewed' => 'مشاهد',
            'expired' => 'منتهية'
        );
        
        return isset( $labels[ $status ] ) ? $labels[ $status ] : $status;
    }

    /**
     * Set screen option.
     *
     * @param mixed  $status Screen option value.
     * @param string $option Option name.
     * @param mixed  $value  Option value.
     * @return mixed
     */
    public function set_screen_option( $status, $option, $value ) {
        if ( strpos( $option, 'impro_' ) === 0 ) {
            return $value;
        }
        return $status;
    }

    /**
     * Sanitize event form data.
     *
     * @param array $data Form data.
     * @return array Sanitized data.
     */
    private function sanitize_event_form_data( $data ) {
        return array(
            'name'                 => sanitize_text_field( $data['event_name'] ?? '' ),
            'event_date'           => sanitize_text_field( $data['event_date'] ?? '' ),
            'event_time'           => sanitize_text_field( $data['event_time'] ?? '' ),
            'venue'                => sanitize_text_field( $data['event_venue'] ?? '' ),
            'address'              => sanitize_textarea_field( $data['event_address'] ?? '' ),
            'description'          => sanitize_textarea_field( $data['event_description'] ?? '' ),
            'invitation_image_url' => esc_url_raw( $data['invitation_image_url'] ?? '' ),
            'invitation_text'      => wp_kses_post( $data['invitation_text'] ?? '' ),
            'location_details'     => sanitize_textarea_field( $data['location_details'] ?? '' ),
            'contact_info'         => sanitize_text_field( $data['contact_info'] ?? '' )
        );
    }

    /**
     * Sanitize guest form data.
     *
     * @param array $data Form data.
     * @return array Sanitized data.
     */
    private function sanitize_guest_form_data( $data ) {
        return array(
            'name'              => sanitize_text_field( $data['guest_name'] ?? '' ),
            'email'             => sanitize_email( $data['guest_email'] ?? '' ),
            'phone'             => sanitize_text_field( $data['guest_phone'] ?? '' ),
            'category'          => sanitize_text_field( $data['guest_category'] ?? '' ),
            'plus_one_allowed'  => isset( $data['plus_one_allowed'] ) ? 1 : 0,
            'gender'            => sanitize_text_field( $data['guest_gender'] ?? '' ),
            'age_range'         => sanitize_text_field( $data['guest_age_range'] ?? '' ),
            'relationship'      => sanitize_text_field( $data['guest_relationship'] ?? '' )
        );
    }

    /**
     * Sanitize settings data.
     *
     * @param array $data Form data.
     * @return array Sanitized data.
     */
    private function sanitize_settings_data( $data ) {
        return array(
            'impro_enable_guest_comments'   => isset( $data['impro_enable_guest_comments'] ) ? 1 : 0,
            'impro_enable_plus_one'         => isset( $data['impro_enable_plus_one'] ) ? 1 : 0,
            'impro_default_guests_limit'    => intval( $data['impro_default_guests_limit'] ?? 200 ),
            'impro_invitation_expiry'       => intval( $data['impro_invitation_expiry'] ?? 30 ),
            'impro_enable_email'            => isset( $data['impro_enable_email'] ) ? 1 : 0,
            'impro_email_subject'           => sanitize_text_field( $data['impro_email_subject'] ?? '' ),
            'impro_email_template'          => wp_kses_post( $data['impro_email_template'] ?? '' ),
            'impro_notification_emails'     => sanitize_email( $data['impro_notification_emails'] ?? '' ),
            'impro_qr_code_size'           => intval( $data['impro_qr_code_size'] ?? 200 ),
            'impro_enable_qr_codes'        => isset( $data['impro_enable_qr_codes'] ) ? 1 : 0,
            'impro_keep_data_on_uninstall' => isset( $data['impro_keep_data_on_uninstall'] ) ? 1 : 0
        );
    }
}
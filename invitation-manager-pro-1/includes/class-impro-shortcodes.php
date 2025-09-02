<?php
/**
 * Shortcodes management class.
 *
 * @package Invitation_Manager_Pro
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * IMPRO_Shortcodes class.
 */
class IMPRO_Shortcodes {

    /**
     * Constructor.
     */
    public function __construct() {
        $this->init_shortcodes();
    }

    /**
     * Initialize shortcodes.
     */
    private function init_shortcodes() {
        add_shortcode( 'impro_invitation_page', array( $this, 'invitation_page_shortcode' ) );
        add_shortcode( 'impro_rsvp_form', array( $this, 'rsvp_form_shortcode' ) );
        add_shortcode( 'impro_event_list', array( $this, 'event_list_shortcode' ) );
        add_shortcode( 'impro_guest_count', array( $this, 'guest_count_shortcode' ) );
        add_shortcode( 'impro_rsvp_stats', array( $this, 'rsvp_stats_shortcode' ) );
        add_shortcode( 'impro_event_countdown', array( $this, 'event_countdown_shortcode' ) );
        add_shortcode( 'impro_guest_list', array( $this, 'guest_list_shortcode' ) );
        add_shortcode( 'impro_invitation_link', array( $this, 'invitation_link_shortcode' ) );
    }

    /**
     * Invitation page shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string Shortcode output.
     */
    public function invitation_page_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'token' => get_query_var( 'invitation_token' ),
            'template' => 'default'
        ), $atts );

        if ( ! $atts['token'] ) {
            return '<div class="impro-error">' . __( 'رمز الدعوة مطلوب', 'invitation-manager-pro' ) . '</div>';
        }

        // Validate token
        if ( ! IMPRO_Validator::validate_invitation_token( $atts['token'] ) ) {
            return '<div class="impro-error">' . __( 'رابط الدعوة غير صحيح أو منتهي الصلاحية', 'invitation-manager-pro' ) . '</div>';
        }

        ob_start();
        $this->display_invitation_content( $atts['token'], $atts['template'] );
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
            'token' => get_query_var( 'invitation_token' ),
            'style' => 'default'
        ), $atts );

        if ( ! $atts['token'] ) {
            return '<div class="impro-error">' . __( 'رمز الدعوة مطلوب', 'invitation-manager-pro' ) . '</div>';
        }

        // Validate token
        if ( ! IMPRO_Validator::validate_invitation_token( $atts['token'] ) ) {
            return '<div class="impro-error">' . __( 'رابط الدعوة غير صحيح أو منتهي الصلاحية', 'invitation-manager-pro' ) . '</div>';
        }

        ob_start();
        $this->display_rsvp_form( $atts['token'], $atts['style'] );
        return ob_get_clean();
    }

    /**
     * Event list shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string Shortcode output.
     */
    public function event_list_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'limit' => 10,
            'status' => 'upcoming',
            'show_image' => 'yes',
            'show_date' => 'yes',
            'show_venue' => 'yes',
            'show_description' => 'yes',
            'template' => 'grid'
        ), $atts );

        $event_manager = new IMPRO_Event_Manager();
        $events = $event_manager->get_events( array(
            'limit' => intval( $atts['limit'] ),
            'status' => $atts['status']
        ) );

        if ( empty( $events ) ) {
            return '<div class="impro-no-events">' . __( 'لا توجد مناسبات', 'invitation-manager-pro' ) . '</div>';
        }

        ob_start();
        $this->display_event_list( $events, $atts );
        return ob_get_clean();
    }

    /**
     * Guest count shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string Shortcode output.
     */
    public function guest_count_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'event_id' => 0,
            'type' => 'total', // total, attending, declined
            'format' => 'number' // number, text
        ), $atts );

        $event_id = intval( $atts['event_id'] );
        
        if ( $event_id ) {
            $rsvp_manager = new IMPRO_RSVP_Manager();
            $stats = $rsvp_manager->get_event_rsvp_statistics( $event_id );
        } else {
            $guest_manager = new IMPRO_Guest_Manager();
            $stats = $guest_manager->get_guest_statistics();
        }

        $count = 0;
        switch ( $atts['type'] ) {
            case 'attending':
                $count = $stats['accepted'] ?? 0;
                break;
            case 'declined':
                $count = $stats['declined'] ?? 0;
                break;
            case 'pending':
                $count = $stats['pending'] ?? 0;
                break;
            default:
                $count = $stats['total'] ?? 0;
                break;
        }

        if ( $atts['format'] === 'text' ) {
            switch ( $atts['type'] ) {
                case 'attending':
                    return sprintf( _n( 'ضيف واحد سيحضر', '%d ضيف سيحضرون', $count, 'invitation-manager-pro' ), $count );
                case 'declined':
                    return sprintf( _n( 'ضيف واحد معتذر', '%d ضيف معتذرون', $count, 'invitation-manager-pro' ), $count );
                case 'pending':
                    return sprintf( _n( 'ضيف واحد لم يرد', '%d ضيف لم يردوا', $count, 'invitation-manager-pro' ), $count );
                default:
                    return sprintf( _n( 'ضيف واحد', '%d ضيف', $count, 'invitation-manager-pro' ), $count );
            }
        }

        return '<span class="impro-guest-count impro-guest-count-' . esc_attr( $atts['type'] ) . '">' . number_format( $count ) . '</span>';
    }

    /**
     * RSVP statistics shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string Shortcode output.
     */
    public function rsvp_stats_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'event_id' => 0,
            'show_chart' => 'yes',
            'chart_type' => 'pie', // pie, bar
            'show_numbers' => 'yes',
            'show_percentages' => 'yes'
        ), $atts );

        $event_id = intval( $atts['event_id'] );
        
        if ( $event_id ) {
            $rsvp_manager = new IMPRO_RSVP_Manager();
            $stats = $rsvp_manager->get_event_rsvp_statistics( $event_id );
        } else {
            $rsvp_manager = new IMPRO_RSVP_Manager();
            $stats = $rsvp_manager->get_overall_rsvp_statistics();
        }

        ob_start();
        $this->display_rsvp_stats( $stats, $atts );
        return ob_get_clean();
    }

    /**
     * Event countdown shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string Shortcode output.
     */
    public function event_countdown_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'event_id' => 0,
            'format' => 'full', // full, compact
            'show_labels' => 'yes',
            'expired_text' => ''
        ), $atts );

        $event_id = intval( $atts['event_id'] );
        
        if ( ! $event_id ) {
            return '<div class="impro-error">' . __( 'معرف المناسبة مطلوب', 'invitation-manager-pro' ) . '</div>';
        }

        $event_manager = new IMPRO_Event_Manager();
        $event = $event_manager->get_event( $event_id );

        if ( ! $event ) {
            return '<div class="impro-error">' . __( 'المناسبة غير موجودة', 'invitation-manager-pro' ) . '</div>';
        }

        $event_datetime = strtotime( $event->event_date . ' ' . ( $event->event_time ?: '00:00:00' ) );
        $now = current_time( 'timestamp' );

        if ( $event_datetime < $now ) {
            $expired_text = $atts['expired_text'] ?: __( 'انتهت المناسبة', 'invitation-manager-pro' );
            return '<div class="impro-countdown-expired">' . esc_html( $expired_text ) . '</div>';
        }

        ob_start();
        $this->display_countdown( $event_datetime, $atts );
        return ob_get_clean();
    }

    /**
     * Guest list shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string Shortcode output.
     */
    public function guest_list_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'event_id' => 0,
            'status' => 'all', // all, attending, declined, pending
            'show_avatar' => 'no',
            'show_category' => 'no',
            'show_plus_one' => 'no',
            'limit' => 50,
            'columns' => 3
        ), $atts );

        $event_id = intval( $atts['event_id'] );
        
        if ( ! $event_id ) {
            return '<div class="impro-error">' . __( 'معرف المناسبة مطلوب', 'invitation-manager-pro' ) . '</div>';
        }

        $rsvp_manager = new IMPRO_RSVP_Manager();
        $guests = $rsvp_manager->get_event_guests_with_rsvp( $event_id, array(
            'status' => $atts['status'],
            'limit' => intval( $atts['limit'] )
        ) );

        if ( empty( $guests ) ) {
            return '<div class="impro-no-guests">' . __( 'لا يوجد ضيوف', 'invitation-manager-pro' ) . '</div>';
        }

        ob_start();
        $this->display_guest_list( $guests, $atts );
        return ob_get_clean();
    }

    /**
     * Invitation link shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string Shortcode output.
     */
    public function invitation_link_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'guest_id' => 0,
            'event_id' => 0,
            'text' => '',
            'class' => 'impro-invitation-link',
            'target' => '_blank'
        ), $atts );

        $guest_id = intval( $atts['guest_id'] );
        $event_id = intval( $atts['event_id'] );

        if ( ! $guest_id || ! $event_id ) {
            return '<div class="impro-error">' . __( 'معرف الضيف ومعرف المناسبة مطلوبان', 'invitation-manager-pro' ) . '</div>';
        }

        $invitation_manager = new IMPRO_Invitation_Manager();
        $invitation = $invitation_manager->get_invitation_by_guest_event( $guest_id, $event_id );

        if ( ! $invitation ) {
            return '<div class="impro-error">' . __( 'الدعوة غير موجودة', 'invitation-manager-pro' ) . '</div>';
        }

        $public = new IMPRO_Public();
        $invitation_url = $public->get_invitation_url( $invitation->token );
        $link_text = $atts['text'] ?: __( 'عرض الدعوة', 'invitation-manager-pro' );

        return sprintf(
            '<a href="%s" class="%s" target="%s">%s</a>',
            esc_url( $invitation_url ),
            esc_attr( $atts['class'] ),
            esc_attr( $atts['target'] ),
            esc_html( $link_text )
        );
    }

    /**
     * Display invitation content.
     *
     * @param string $token Invitation token.
     * @param string $template Template name.
     */
    private function display_invitation_content( $token, $template ) {
        $invitation_manager = new IMPRO_Invitation_Manager();
        $guest_manager = new IMPRO_Guest_Manager();
        $event_manager = new IMPRO_Event_Manager();

        $invitation = $invitation_manager->get_invitation_by_token( $token );
        $guest = $guest_manager->get_guest( $invitation->guest_id );
        $event = $event_manager->get_event( $invitation->event_id );

        // Check for custom template
        $template_file = "impro-invitation-{$template}.php";
        $custom_template = locate_template( $template_file );
        
        if ( $custom_template ) {
            include $custom_template;
        } else {
            include IMPRO_PATH . 'public/invitation-content.php';
        }
    }

    /**
     * Display RSVP form.
     *
     * @param string $token Invitation token.
     * @param string $style Form style.
     */
    private function display_rsvp_form( $token, $style ) {
        $invitation_manager = new IMPRO_Invitation_Manager();
        $guest_manager = new IMPRO_Guest_Manager();
        $event_manager = new IMPRO_Event_Manager();
        $rsvp_manager = new IMPRO_RSVP_Manager();

        $invitation = $invitation_manager->get_invitation_by_token( $token );
        $guest = $guest_manager->get_guest( $invitation->guest_id );
        $event = $event_manager->get_event( $invitation->event_id );
        $rsvp = $rsvp_manager->get_rsvp_by_guest_event( $invitation->guest_id, $invitation->event_id );

        // Check for custom template
        $template_file = "impro-rsvp-{$style}.php";
        $custom_template = locate_template( $template_file );
        
        if ( $custom_template ) {
            include $custom_template;
        } else {
            include IMPRO_PATH . 'public/rsvp-form.php';
        }
    }

    /**
     * Display event list.
     *
     * @param array $events Events array.
     * @param array $atts Shortcode attributes.
     */
    private function display_event_list( $events, $atts ) {
        $template_class = 'impro-event-list impro-event-list-' . esc_attr( $atts['template'] );
        
        echo '<div class="' . esc_attr( $template_class ) . '">';
        
        foreach ( $events as $event ) {
            echo '<div class="impro-event-item">';
            
            if ( $atts['show_image'] === 'yes' && $event->invitation_image_url ) {
                echo '<div class="impro-event-image">';
                echo '<img src="' . esc_url( $event->invitation_image_url ) . '" alt="' . esc_attr( $event->name ) . '">';
                echo '</div>';
            }
            
            echo '<div class="impro-event-content">';
            echo '<h3 class="impro-event-title">' . esc_html( $event->name ) . '</h3>';
            
            if ( $atts['show_date'] === 'yes' ) {
                echo '<div class="impro-event-date">';
                echo '<span class="impro-event-date-label">' . __( 'التاريخ:', 'invitation-manager-pro' ) . '</span> ';
                echo esc_html( date_i18n( 'j F Y', strtotime( $event->event_date ) ) );
                if ( $event->event_time ) {
                    echo ' - ' . esc_html( date_i18n( 'g:i A', strtotime( $event->event_time ) ) );
                }
                echo '</div>';
            }
            
            if ( $atts['show_venue'] === 'yes' ) {
                echo '<div class="impro-event-venue">';
                echo '<span class="impro-event-venue-label">' . __( 'المكان:', 'invitation-manager-pro' ) . '</span> ';
                echo esc_html( $event->venue );
                echo '</div>';
            }
            
            if ( $atts['show_description'] === 'yes' && $event->description ) {
                echo '<div class="impro-event-description">';
                echo wp_kses_post( wp_trim_words( $event->description, 20 ) );
                echo '</div>';
            }
            
            echo '</div>'; // .impro-event-content
            echo '</div>'; // .impro-event-item
        }
        
        echo '</div>'; // .impro-event-list
    }

    /**
     * Display RSVP statistics.
     *
     * @param array $stats Statistics data.
     * @param array $atts Shortcode attributes.
     */
    private function display_rsvp_stats( $stats, $atts ) {
        echo '<div class="impro-rsvp-stats">';
        
        if ( $atts['show_numbers'] === 'yes' ) {
            echo '<div class="impro-rsvp-numbers">';
            echo '<div class="impro-stat-item impro-stat-accepted">';
            echo '<span class="impro-stat-number">' . number_format( $stats['accepted'] ) . '</span>';
            echo '<span class="impro-stat-label">' . __( 'موافق', 'invitation-manager-pro' ) . '</span>';
            echo '</div>';
            
            echo '<div class="impro-stat-item impro-stat-declined">';
            echo '<span class="impro-stat-number">' . number_format( $stats['declined'] ) . '</span>';
            echo '<span class="impro-stat-label">' . __( 'معتذر', 'invitation-manager-pro' ) . '</span>';
            echo '</div>';
            
            echo '<div class="impro-stat-item impro-stat-pending">';
            echo '<span class="impro-stat-number">' . number_format( $stats['pending'] ) . '</span>';
            echo '<span class="impro-stat-label">' . __( 'في الانتظار', 'invitation-manager-pro' ) . '</span>';
            echo '</div>';
            echo '</div>';
        }
        
        if ( $atts['show_percentages'] === 'yes' && $stats['total'] > 0 ) {
            $accepted_percent = round( ( $stats['accepted'] / $stats['total'] ) * 100, 1 );
            $declined_percent = round( ( $stats['declined'] / $stats['total'] ) * 100, 1 );
            $pending_percent = round( ( $stats['pending'] / $stats['total'] ) * 100, 1 );
            
            echo '<div class="impro-rsvp-percentages">';
            echo '<div class="impro-percentage-bar">';
            echo '<div class="impro-percentage-accepted" style="width: ' . $accepted_percent . '%"></div>';
            echo '<div class="impro-percentage-declined" style="width: ' . $declined_percent . '%"></div>';
            echo '<div class="impro-percentage-pending" style="width: ' . $pending_percent . '%"></div>';
            echo '</div>';
            
            echo '<div class="impro-percentage-labels">';
            echo '<span class="impro-percentage-label impro-percentage-label-accepted">' . $accepted_percent . '% ' . __( 'موافق', 'invitation-manager-pro' ) . '</span>';
            echo '<span class="impro-percentage-label impro-percentage-label-declined">' . $declined_percent . '% ' . __( 'معتذر', 'invitation-manager-pro' ) . '</span>';
            echo '<span class="impro-percentage-label impro-percentage-label-pending">' . $pending_percent . '% ' . __( 'في الانتظار', 'invitation-manager-pro' ) . '</span>';
            echo '</div>';
            echo '</div>';
        }
        
        if ( $atts['show_chart'] === 'yes' ) {
            echo '<div class="impro-rsvp-chart" data-chart-type="' . esc_attr( $atts['chart_type'] ) . '">';
            echo '<canvas id="impro-rsvp-chart-' . uniqid() . '" data-accepted="' . esc_attr( $stats['accepted'] ) . '" data-declined="' . esc_attr( $stats['declined'] ) . '" data-pending="' . esc_attr( $stats['pending'] ) . '"></canvas>';
            echo '</div>';
        }
        
        echo '</div>';
    }

    /**
     * Display countdown.
     *
     * @param int   $event_datetime Event timestamp.
     * @param array $atts Shortcode attributes.
     */
    private function display_countdown( $event_datetime, $atts ) {
        $countdown_id = 'impro-countdown-' . uniqid();
        $countdown_class = 'impro-countdown impro-countdown-' . esc_attr( $atts['format'] );
        
        echo '<div id="' . esc_attr( $countdown_id ) . '" class="' . esc_attr( $countdown_class ) . '" data-target="' . esc_attr( $event_datetime ) . '">';
        
        if ( $atts['format'] === 'full' ) {
            echo '<div class="impro-countdown-item">';
            echo '<span class="impro-countdown-number" data-unit="days">0</span>';
            if ( $atts['show_labels'] === 'yes' ) {
                echo '<span class="impro-countdown-label">' . __( 'يوم', 'invitation-manager-pro' ) . '</span>';
            }
            echo '</div>';
            
            echo '<div class="impro-countdown-item">';
            echo '<span class="impro-countdown-number" data-unit="hours">0</span>';
            if ( $atts['show_labels'] === 'yes' ) {
                echo '<span class="impro-countdown-label">' . __( 'ساعة', 'invitation-manager-pro' ) . '</span>';
            }
            echo '</div>';
            
            echo '<div class="impro-countdown-item">';
            echo '<span class="impro-countdown-number" data-unit="minutes">0</span>';
            if ( $atts['show_labels'] === 'yes' ) {
                echo '<span class="impro-countdown-label">' . __( 'دقيقة', 'invitation-manager-pro' ) . '</span>';
            }
            echo '</div>';
            
            echo '<div class="impro-countdown-item">';
            echo '<span class="impro-countdown-number" data-unit="seconds">0</span>';
            if ( $atts['show_labels'] === 'yes' ) {
                echo '<span class="impro-countdown-label">' . __( 'ثانية', 'invitation-manager-pro' ) . '</span>';
            }
            echo '</div>';
        } else {
            echo '<span class="impro-countdown-compact" data-format="compact"></span>';
        }
        
        echo '</div>';
        
        // Add countdown JavaScript
        $this->add_countdown_script();
    }

    /**
     * Display guest list.
     *
     * @param array $guests Guests array.
     * @param array $atts Shortcode attributes.
     */
    private function display_guest_list( $guests, $atts ) {
        $columns = max( 1, min( 6, intval( $atts['columns'] ) ) );
        $list_class = 'impro-guest-list impro-guest-list-columns-' . $columns;
        
        echo '<div class="' . esc_attr( $list_class ) . '">';
        
        foreach ( $guests as $guest ) {
            echo '<div class="impro-guest-item impro-guest-status-' . esc_attr( $guest->rsvp_status ?: 'pending' ) . '">';
            
            if ( $atts['show_avatar'] === 'yes' ) {
                echo '<div class="impro-guest-avatar">';
                echo get_avatar( $guest->email ?: '', 40 );
                echo '</div>';
            }
            
            echo '<div class="impro-guest-info">';
            echo '<div class="impro-guest-name">' . esc_html( $guest->name ) . '</div>';
            
            if ( $atts['show_category'] === 'yes' && $guest->category ) {
                echo '<div class="impro-guest-category">' . esc_html( $guest->category ) . '</div>';
            }
            
            if ( $atts['show_plus_one'] === 'yes' && $guest->plus_one_attending ) {
                echo '<div class="impro-guest-plus-one">';
                echo __( 'مع مرافق', 'invitation-manager-pro' );
                if ( $guest->plus_one_name ) {
                    echo ': ' . esc_html( $guest->plus_one_name );
                }
                echo '</div>';
            }
            
            echo '</div>'; // .impro-guest-info
            echo '</div>'; // .impro-guest-item
        }
        
        echo '</div>'; // .impro-guest-list
    }

    /**
     * Add countdown JavaScript.
     */
    private function add_countdown_script() {
        static $script_added = false;
        
        if ( $script_added ) {
            return;
        }
        
        $script_added = true;
        
        wp_add_inline_script( 'impro-public-script', '
            document.addEventListener("DOMContentLoaded", function() {
                const countdowns = document.querySelectorAll(".impro-countdown");
                
                countdowns.forEach(function(countdown) {
                    const target = parseInt(countdown.dataset.target) * 1000;
                    
                    function updateCountdown() {
                        const now = new Date().getTime();
                        const distance = target - now;
                        
                        if (distance < 0) {
                            countdown.innerHTML = "' . esc_js( __( 'انتهت المناسبة', 'invitation-manager-pro' ) ) . '";
                            return;
                        }
                        
                        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                        
                        const daysEl = countdown.querySelector("[data-unit=\"days\"]");
                        const hoursEl = countdown.querySelector("[data-unit=\"hours\"]");
                        const minutesEl = countdown.querySelector("[data-unit=\"minutes\"]");
                        const secondsEl = countdown.querySelector("[data-unit=\"seconds\"]");
                        const compactEl = countdown.querySelector("[data-format=\"compact\"]");
                        
                        if (daysEl) daysEl.textContent = days;
                        if (hoursEl) hoursEl.textContent = hours;
                        if (minutesEl) minutesEl.textContent = minutes;
                        if (secondsEl) secondsEl.textContent = seconds;
                        
                        if (compactEl) {
                            compactEl.textContent = days + "d " + hours + "h " + minutes + "m " + seconds + "s";
                        }
                    }
                    
                    updateCountdown();
                    setInterval(updateCountdown, 1000);
                });
            });
        ' );
    }

    /**
     * Get invitation URL.
     *
     * @param string $token Invitation token.
     * @return string Invitation URL.
     */
    public static function get_invitation_url( $token ) {
        $invitation_page_id = get_option( 'impro_invitation_page_id' );
        if ( $invitation_page_id ) {
            return get_permalink( $invitation_page_id ) . '?token=' . $token;
        }
        return home_url( '/invitation/' . $token );
    }
}


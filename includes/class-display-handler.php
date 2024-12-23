<?php
/**
 * Display Handler
 * @since 1.5.0
 */
class FLI_Display_Handler {
    public function __construct() {
        add_shortcode('display_meetings', array($this, 'display_meetings_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('template_redirect', array($this, 'handle_meeting_redirect'));
    }

    /**
     * Enqueue assets
     */
    public function enqueue_assets() {
        wp_enqueue_style('fli-meetings-style', 
            FLI_CUSTOM_MEETINGS_URL . 'assets/css/meetings.css',
            array(),
            FLI_CUSTOM_MEETINGS_VERSION
        );

        wp_enqueue_script('fli-countdown',
            FLI_CUSTOM_MEETINGS_URL . 'assets/js/countdown.js',
            array('jquery'),
            FLI_CUSTOM_MEETINGS_VERSION,
            true
        );
    }

    /**
     * Display meetings shortcode
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function display_meetings_shortcode($atts) {
        fli_log("Processing display_meetings shortcode with attributes: " . print_r($atts, true));
        
        $atts = shortcode_atts(array(
            'limit' => 5,
            'show_past' => false,
            'type' => '' // Optional: show only specific meeting type
        ), $atts);

        $args = array(
            'post_type' => 'fli_custom_meeting',
            'posts_per_page' => $atts['limit'],
            'meta_key' => '_meeting_date',
            'orderby' => 'meta_value',
            'order' => 'ASC',
            'meta_query' => array(
                array(
                    'key' => '_meeting_date',
                    'value' => current_time('Y-m-d'),
                    'compare' => $atts['show_past'] ? '<=' : '>=',
                    'type' => 'DATE'
                )
            )
        );

        // Filter by meeting type if specified
        if (!empty($atts['type'])) {
            $args['meta_query'][] = array(
                'key' => '_meeting_type',
                'value' => $atts['type']
            );
        }

        $meetings = new WP_Query($args);
        $output = '<div class="fli-meetings-container">';

        if ($meetings->have_posts()) {
            fli_log("Found " . $meetings->post_count . " meetings to display");
            while ($meetings->have_posts()) {
                $meetings->the_post();
                $meeting_date = get_post_meta(get_the_ID(), '_meeting_date', true);
                $meeting_time = get_post_meta(get_the_ID(), '_meeting_time', true);
                $zoom_link = get_post_meta(get_the_ID(), '_zoom_link', true);
                $meeting_type = get_post_meta(get_the_ID(), '_meeting_type', true);
                $meeting_timestamp = strtotime($meeting_date . ' ' . $meeting_time);
                
                $output .= '<div class="meeting-event-page">';
                
                // Meeting Type Header
                $type_name = $this->get_meeting_type_name($meeting_type);
                $output .= '<div class="meeting-header">';
                $output .= '<h2>' . esc_html($type_name) . '</h2>';
                $output .= '</div>';

                // Countdown Timer (only for future meetings)
                if ($meeting_timestamp > current_time('timestamp')) {
                    $output .= '<div class="countdown-timer" data-timestamp="' . esc_attr($meeting_timestamp) . '">';
                    $output .= '<div class="countdown-section">';
                    $output .= '<span class="countdown-amount days">00</span>';
                    $output .= '<span class="countdown-period">Days</span>';
                    $output .= '</div>';
                    $output .= '<div class="countdown-section">';
                    $output .= '<span class="countdown-amount hours">00</span>';
                    $output .= '<span class="countdown-period">Hours</span>';
                    $output .= '</div>';
                    $output .= '<div class="countdown-section">';
                    $output .= '<span class="countdown-amount minutes">00</span>';
                    $output .= '<span class="countdown-period">Minutes</span>';
                    $output .= '</div>';
                    $output .= '<div class="countdown-section">';
                    $output .= '<span class="countdown-amount seconds">00</span>';
                    $output .= '<span class="countdown-period">Seconds</span>';
                    $output .= '</div>';
                    $output .= '</div>';
                } elseif ($meeting_timestamp > current_time('timestamp') - 7200) { // Within 2 hours after start
                    $output .= '<div class="meeting-live">Meeting is Live!</div>';
                } else {
                    $output .= '<div class="meeting-ended">Meeting has Ended</div>';
                }

                // Meeting Details
                $output .= '<div class="meeting-details">';
                $output .= '<div class="detail-item">';
                $output .= '<span class="detail-label">Date:</span>';
                $output .= '<span class="detail-value">' . esc_html(date_i18n('l, F j, Y', $meeting_timestamp)) . '</span>';
                $output .= '</div>';
                $output .= '<div class="detail-item">';
                $output .= '<span class="detail-label">Time:</span>';
                $output .= '<span class="detail-value">' . esc_html(date_i18n('g:i A T', $meeting_timestamp)) . '</span>';
                $output .= '</div>';
                
                // Description if available
                $description = get_the_content();
                if (!empty($description)) {
                    $output .= '<div class="meeting-description">';
                    $output .= wp_kses_post($description);
                    $output .= '</div>';
                }
                
                // Join Button (only if logged in and meeting is within 15 minutes of start)
                if (function_exists('bp_is_active') && is_user_logged_in()) {
                    $time_until_meeting = $meeting_timestamp - current_time('timestamp');
                    if ($time_until_meeting <= 900 && $time_until_meeting > -7200) { // 15 minutes before until 2 hours after
                        $output .= '<div class="join-meeting-button">';
                        $output .= '<a href="' . esc_url($zoom_link) . '" class="button meeting-link" target="_blank">';
                        $output .= esc_html__('Join Meeting Now', 'fli-custom-meetings');
                        $output .= '</a>';
                        $output .= '</div>';
                    }
                } else {
                    $output .= '<div class="login-notice">';
                    $output .= '<p>' . esc_html__('Please log in to access the meeting.', 'fli-custom-meetings') . '</p>';
                    $output .= '</div>';
                }
                
                $output .= '</div>'; // .meeting-details
                $output .= '</div>'; // .meeting-event-page
            }
        } else {
            fli_log("No meetings found matching criteria");
            $output .= '<div class="no-meetings">';
            $output .= '<p>' . esc_html__('No meetings scheduled.', 'fli-custom-meetings') . '</p>';
            $output .= '</div>';
        }

        wp_reset_postdata();
        $output .= '</div>';
        
        return $output;
    }

    /**
     * Get meeting type display name
     * @param string $type Meeting type key
     * @return string Meeting type display name
     */
    private function get_meeting_type_name($type) {
        $meetings_handler = new FLI_Meetings_Handler();
        $types = $meetings_handler->get_meeting_types();
        return isset($types[$type]) ? $types[$type]['name'] : ucfirst($type);
    }

    /**
     * Handle meeting redirect
     */
    public function handle_meeting_redirect() {
        if ($_SERVER['REQUEST_URI'] === '/Join-FYM-Call') {
            fli_log("Processing FYM Call redirect request");
            $meetings_handler = new FLI_Meetings_Handler();
            $next_meeting = $meetings_handler->get_next_meeting();
            
            if ($next_meeting) {
                fli_log("Redirecting to meeting: " . $next_meeting->ID);
                wp_redirect(get_permalink($next_meeting->ID));
                exit;
            } else {
                fli_log("No meeting found for redirect", 'warning');
            }
        }
    }
} 
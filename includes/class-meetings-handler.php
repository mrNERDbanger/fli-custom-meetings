<?php
/**
 * Meetings Handler
 * @since 1.5.0
 */
class FLI_Meetings_Handler {
    private $meeting_types;
    private $zoom_api;

    public function __construct() {
        $this->meeting_types = array(
            'topic' => array(
                'name' => 'Monthly Topic with Rhonda',
                'schedule' => 'first_monday',
                'time' => '19:00'
            ),
            'getitdone' => array(
                'name' => 'Get-it-Done! Session',
                'schedule' => 'second_monday',
                'time' => '19:00'
            ),
            'qa' => array(
                'name' => 'Q&A: Ask Us Anything',
                'schedule' => 'third_monday',
                'time' => '19:00'
            ),
            'reflection' => array(
                'name' => 'Monthly Reflection',
                'schedule' => 'fourth_monday',
                'time' => '19:00'
            ),
            'custom' => array(
                'name' => 'Custom Meeting',
                'schedule' => 'fourth_monday', // Default schedule, can be changed
                'time' => '19:00'
            )
        );

        $this->zoom_api = new FLI_Zoom_API(
            get_option('fli_custom_meetings_api_key'),
            get_option('fli_custom_meetings_api_secret')
        );

        add_action('init', array($this, 'register_post_type'));
    }

    /**
     * Register meetings post type
     */
    public function register_post_type() {
        fli_log("Registering fli_custom_meeting post type");
        register_post_type('fli_custom_meeting', array(
            'labels' => array(
                'name' => __('Meetings', 'fli-custom-meetings'),
                'singular_name' => __('Meeting', 'fli-custom-meetings')
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor'),
            'menu_icon' => 'dashicons-video-alt2'
        ));
    }

    /**
     * Create a meeting post
     * @param string $meeting_date Meeting date
     * @param array $zoom_meeting Zoom meeting details
     * @param string $type Meeting type
     * @return int|WP_Error Post ID or error
     */
    public function create_meeting_post($meeting_date, $zoom_meeting, $type) {
        fli_log("Creating meeting post for type: {$type} on date: {$meeting_date}");
        
        $post_data = array(
            'post_title' => $this->meeting_types[$type]['name'] . ' Meeting - ' . 
                          date('F Y', strtotime($meeting_date)),
            'post_type' => 'fli_custom_meeting',
            'post_status' => 'publish'
        );

        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
            fli_log("Error creating meeting post: " . $post_id->get_error_message(), 'error');
            return $post_id;
        }

        // Log success and metadata updates
        fli_log("Successfully created meeting post ID: {$post_id}");
        
        update_post_meta($post_id, '_meeting_date', $meeting_date);
        update_post_meta($post_id, '_meeting_time', $this->meeting_types[$type]['time']);
        update_post_meta($post_id, '_zoom_link', $zoom_meeting['join_url']);
        update_post_meta($post_id, '_zoom_meeting_id', $zoom_meeting['id']);
        update_post_meta($post_id, '_meeting_type', $type);
        update_post_meta($post_id, '_meeting_status', 'scheduled');

        return $post_id;
    }

    /**
     * Calculate meeting date
     * @param string $base_date Base date
     * @param string $schedule Schedule type
     * @return string Calculated date
     */
    public function calculate_meeting_date($base_date, $schedule) {
        fli_log("Calculating meeting date for {$schedule} from base date: {$base_date}");
        $date = new DateTime($base_date);
        
        switch ($schedule) {
            case 'first_monday':
                $date->modify('first monday of this month');
                break;
            case 'second_monday':
                $date->modify('second monday of this month');
                break;
            case 'third_monday':
                $date->modify('third monday of this month');
                break;
            case 'fourth_monday':
                $date->modify('fourth monday of this month');
                break;
        }
        
        $result_date = $date->format('Y-m-d');
        fli_log("Calculated meeting date: {$result_date}");
        return $result_date;
    }

    /**
     * Check if date is a federal holiday
     * @param string $date Date to check
     * @return boolean
     */
    public function is_federal_holiday($date) {
        $holidays = array(
            '2024-01-01' => 'New Year\'s Day',
            '2024-01-15' => 'Martin Luther King Jr. Day',
            '2024-02-19' => 'Presidents Day',
            '2024-05-27' => 'Memorial Day',
            '2024-06-19' => 'Juneteenth',
            '2024-07-04' => 'Independence Day',
            '2024-09-02' => 'Labor Day',
            '2024-10-14' => 'Columbus Day',
            '2024-11-11' => 'Veterans Day',
            '2024-11-28' => 'Thanksgiving Day',
            '2024-12-25' => 'Christmas Day'
        );
        
        $is_holiday = isset($holidays[$date]);
        if ($is_holiday) {
            fli_log("Date {$date} is a federal holiday: {$holidays[$date]}");
        }
        return $is_holiday;
    }

    /**
     * Get next available date
     * @param string $date Current date
     * @return string Next available date
     */
    public function get_next_available_date($date) {
        fli_log("Finding next available date after {$date}");
        $next_date = date('Y-m-d', strtotime($date . ' +1 week'));
        
        while ($this->is_federal_holiday($next_date)) {
            fli_log("Date {$next_date} is a holiday, checking next week");
            $next_date = date('Y-m-d', strtotime($next_date . ' +1 week'));
        }
        
        fli_log("Next available date found: {$next_date}");
        return $next_date;
    }

    /**
     * Check if future meeting exists
     * @param string $type Meeting type
     * @return boolean
     */
    public function future_meeting_exists($type) {
        fli_log("Checking for future meetings of type: {$type}");
        $exists = $this->get_future_meeting_query($type)->have_posts();
        fli_log($exists ? "Future meeting exists" : "No future meeting found");
        return $exists;
    }

    /**
     * Generate next meeting
     * @param string $type Meeting type
     * @return int|WP_Error Post ID or error
     */
    public function generate_next_meeting($type) {
        if (!isset($this->meeting_types[$type])) {
            return new WP_Error('invalid_type', 'Invalid meeting type');
        }

        $settings = $this->meeting_types[$type];
        
        // Use custom settings for custom meeting type
        if ($type === 'custom') {
            $settings['schedule'] = get_option('fli_custom_meeting_schedule', 'fourth_monday');
            $settings['time'] = get_option('fli_custom_meeting_time', '19:00');
        }

        $next_date = $this->calculate_meeting_date(date('Y-m-01', strtotime('+1 month')), $settings['schedule']);
        
        while ($this->is_federal_holiday($next_date)) {
            $next_date = $this->get_next_available_date($next_date);
        }

        $zoom_meeting = $this->zoom_api->create_meeting(array(
            'topic' => $settings['name'] . ' Meeting - ' . date('F Y', strtotime($next_date)),
            'start_time' => $next_date . 'T' . $settings['time'],
            'duration' => 60
        ));

        if (is_wp_error($zoom_meeting)) {
            return $zoom_meeting;
        }

        return $this->create_meeting_post($next_date, $zoom_meeting, $type);
    }

    /**
     * Create initial meetings
     */
    public function create_initial_meetings() {
        fli_log("Starting initial meetings creation");
        foreach ($this->meeting_types as $type => $settings) {
            fli_log("Processing meeting type: {$type}");
            if (!$this->future_meeting_exists($type)) {
                $result = $this->generate_next_meeting($type);
                if (is_wp_error($result)) {
                    fli_log("Error creating initial meeting for {$type}: " . $result->get_error_message(), 'error');
                } else {
                    fli_log("Successfully created initial meeting for {$type}");
                }
            }
        }
        fli_log("Completed initial meetings creation");
    }

    /**
     * Get next meeting
     * @return WP_Post|null Next meeting post or null
     */
    public function get_next_meeting() {
        $args = array(
            'post_type' => 'fli_custom_meeting',
            'posts_per_page' => 1,
            'meta_key' => '_meeting_date',
            'orderby' => 'meta_value',
            'order' => 'ASC',
            'meta_query' => array(
                array(
                    'key' => '_meeting_date',
                    'value' => current_time('Y-m-d'),
                    'compare' => '>=',
                    'type' => 'DATE'
                ),
                array(
                    'key' => '_meeting_status',
                    'value' => 'completed',
                    'compare' => '!='
                )
            )
        );
        
        $next_meeting = new WP_Query($args);
        
        return $next_meeting->have_posts() ? $next_meeting->posts[0] : null;
    }

    /**
     * Get meeting types
     * @return array Meeting types configuration
     */
    public function get_meeting_types() {
        return $this->meeting_types;
    }
} 
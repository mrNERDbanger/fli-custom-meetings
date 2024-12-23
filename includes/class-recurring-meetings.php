<?php
class FLI_Recurring_Meetings {
    private $zoom_api;
    
    public function __construct() {
        $this->zoom_api = new FLI_Zoom_API();
        
        add_action('wp_ajax_generate_recurring_meetings', [$this, 'ajax_generate_recurring_meetings']);
        add_action('wp_ajax_update_recurring_meeting', [$this, 'ajax_update_recurring_meeting']);
        add_action('fli_generate_monthly_meetings', [$this, 'generate_monthly_meetings']);
    }

    public function generate_monthly_meetings() {
        try {
            $next_month = date('Y-m', strtotime('+1 month'));
            $meetings_generated = [];

            foreach ($this->get_recurring_patterns() as $meeting) {
                $date = $this->calculate_next_meeting_date($meeting['pattern'], $next_month);
                
                // Skip if holiday
                if (FLI_Holiday_Checker::is_holiday($date)) {
                    $date = FLI_Holiday_Checker::get_next_business_day($date);
                }

                $zoom_data = [
                    'topic' => $meeting['title'],
                    'type' => 2, // Scheduled meeting
                    'start_time' => date('Y-m-d\TH:i:s', strtotime("$date {$meeting['time']}")),
                    'duration' => $meeting['duration'],
                ];

                $result = $this->zoom_api->create_meeting($zoom_data);

                if ($result['success']) {
                    $post_id = wp_insert_post([
                        'post_title' => $meeting['title'],
                        'post_type' => 'meeting',
                        'post_status' => 'publish',
                        'meta_input' => [
                            'meeting_date' => $date,
                            'meeting_time' => $meeting['time'],
                            'meeting_type' => $meeting['type'],
                            'meeting_duration' => $meeting['duration'],
                            'zoom_meeting_id' => $result['meeting_id'],
                            'zoom_join_url' => $result['join_url'],
                            'is_recurring_instance' => true,
                            'recurring_pattern' => $meeting['pattern']
                        ]
                    ]);

                    if ($post_id) {
                        $meetings_generated[] = [
                            'title' => $meeting['title'],
                            'date' => $date,
                            'time' => $meeting['time'],
                            'zoom_url' => $result['join_url']
                        ];
                        
                        FLI_Meeting_Logger::log_activity('Generated recurring meeting', [
                            'meeting_id' => $post_id,
                            'zoom_meeting_id' => $result['meeting_id']
                        ]);
                    }
                } else {
                    FLI_Meeting_Logger::log_error('Failed to create Zoom meeting', [
                        'meeting' => $meeting,
                        'error' => $result['error']
                    ]);
                }
            }

            return $meetings_generated;

        } catch (Exception $e) {
            FLI_Meeting_Logger::log_error('Error generating monthly meetings', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    private function get_recurring_patterns() {
        return [
            [
                'title' => 'Monthly Topic with Rhonda',
                'pattern' => 'first thursday',
                'time' => '10:00:00',
                'duration' => 90,
                'type' => 'monthly-topic'
            ],
            [
                'title' => 'Get it Done',
                'pattern' => 'sunday after first thursday',
                'time' => '10:00:00',
                'duration' => 60,
                'type' => 'get-it-done'
            ],
            [
                'title' => 'Ask Us Anything',
                'pattern' => 'third thursday',
                'time' => '10:00:00',
                'duration' => 90,
                'type' => 'ask-anything'
            ],
            [
                'title' => 'Monthly Reflection',
                'pattern' => 'sunday after third thursday',
                'time' => '10:00:00',
                'duration' => 60,
                'type' => 'monthly-reflection'
            ]
        ];
    }

    private function calculate_next_meeting_date($pattern, $month) {
        $base_date = "$month-01";
        return date('Y-m-d', strtotime($pattern . ' of ' . $base_date));
    }

    public function ajax_generate_recurring_meetings() {
        check_ajax_referer('fli_meetings_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $meetings = $this->generate_monthly_meetings();
        
        if ($meetings) {
            wp_send_json_success(['meetings' => $meetings]);
        } else {
            wp_send_json_error(['message' => 'Failed to generate meetings']);
        }
    }

    public function ajax_update_recurring_meeting() {
        check_ajax_referer('fli_meetings_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $post_id = intval($_POST['meeting_id']);
        $new_date = sanitize_text_field($_POST['date']);
        $new_time = sanitize_text_field($_POST['time']);

        $zoom_meeting_id = get_post_meta($post_id, 'zoom_meeting_id', true);
        
        $result = $this->zoom_api->update_meeting($zoom_meeting_id, [
            'start_time' => date('Y-m-d\TH:i:s', strtotime("$new_date $new_time"))
        ]);

        if ($result['success']) {
            update_post_meta($post_id, 'meeting_date', $new_date);
            update_post_meta($post_id, 'meeting_time', $new_time);
            
            FLI_Meeting_Logger::log_activity('Updated recurring meeting', [
                'meeting_id' => $post_id,
                'new_date' => $new_date,
                'new_time' => $new_time
            ]);

            wp_send_json_success([
                'message' => 'Meeting updated successfully',
                'new_date' => $new_date,
                'new_time' => $new_time
            ]);
        } else {
            wp_send_json_error(['message' => 'Failed to update meeting']);
        }
    }
} 
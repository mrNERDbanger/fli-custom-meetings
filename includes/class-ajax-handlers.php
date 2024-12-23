<?php
class FLI_Meeting_Ajax_Handlers {
    private $zoom_api;

    public function __construct() {
        $this->zoom_api = new FLI_Zoom_API();
        
        add_action('wp_ajax_create_single_meeting', [$this, 'create_single_meeting']);
        add_action('wp_ajax_delete_meeting', [$this, 'delete_meeting']);
        add_action('wp_ajax_get_meeting_details', [$this, 'get_meeting_details']);
    }

    public function create_single_meeting() {
        check_ajax_referer('fli_meetings_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $title = sanitize_text_field($_POST['title']);
        $date = sanitize_text_field($_POST['date']);
        $time = sanitize_text_field($_POST['time']);
        $type = sanitize_text_field($_POST['type']);
        $duration = intval($_POST['duration']);

        $zoom_data = [
            'topic' => $title,
            'start_time' => date('Y-m-d\TH:i:s', strtotime("$date $time")),
            'duration' => $duration
        ];

        $result = $this->zoom_api->create_meeting($zoom_data);

        if ($result['success']) {
            $post_id = wp_insert_post([
                'post_title' => $title,
                'post_type' => 'meeting',
                'post_status' => 'publish',
                'meta_input' => [
                    'meeting_date' => $date,
                    'meeting_time' => $time,
                    'meeting_type' => $type,
                    'meeting_duration' => $duration,
                    'zoom_meeting_id' => $result['meeting_id'],
                    'zoom_join_url' => $result['join_url'],
                    'is_recurring_instance' => false
                ]
            ]);

            if ($post_id) {
                FLI_Meeting_Logger::log_activity('Created single meeting', [
                    'meeting_id' => $post_id,
                    'zoom_meeting_id' => $result['meeting_id']
                ]);

                wp_send_json_success([
                    'message' => 'Meeting created successfully',
                    'meeting_id' => $post_id,
                    'zoom_url' => $result['join_url']
                ]);
            }
        }

        wp_send_json_error(['message' => 'Failed to create meeting']);
    }

    public function delete_meeting() {
        check_ajax_referer('fli_meetings_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $post_id = intval($_POST['meeting_id']);
        $zoom_meeting_id = get_post_meta($post_id, 'zoom_meeting_id', true);

        $result = $this->zoom_api->delete_meeting($zoom_meeting_id);

        if ($result['success']) {
            wp_delete_post($post_id, true);
            
            FLI_Meeting_Logger::log_activity('Deleted meeting', [
                'meeting_id' => $post_id,
                'zoom_meeting_id' => $zoom_meeting_id
            ]);

            wp_send_json_success(['message' => 'Meeting deleted successfully']);
        } else {
            wp_send_json_error(['message' => 'Failed to delete meeting']);
        }
    }

    public function get_meeting_details() {
        check_ajax_referer('fli_meetings_nonce', 'nonce');
        
        $post_id = intval($_GET['meeting_id']);
        $meeting = get_post($post_id);

        if ($meeting) {
            wp_send_json_success([
                'title' => $meeting->post_title,
                'date' => get_post_meta($post_id, 'meeting_date', true),
                'time' => get_post_meta($post_id, 'meeting_time', true),
                'type' => get_post_meta($post_id, 'meeting_type', true),
                'duration' => get_post_meta($post_id, 'meeting_duration', true),
                'zoom_url' => get_post_meta($post_id, 'zoom_join_url', true)
            ]);
        } else {
            wp_send_json_error(['message' => 'Meeting not found']);
        }
    }
} 
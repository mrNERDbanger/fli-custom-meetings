<?php
/**
 * Zoom API Handler
 * @since 1.5.0
 */
class FLI_Zoom_API {
    private $api_key;
    private $api_secret;
    private $api_url = 'https://api.zoom.us/v2';

    public function __construct($api_key, $api_secret) {
        $this->api_key = $api_key;
        $this->api_secret = $api_secret;
    }

    /**
     * Create a Zoom meeting
     * @param array $params Meeting parameters
     * @return array|WP_Error Meeting data or error
     */
    public function create_meeting($params) {
        fli_log("Creating Zoom meeting with params: " . print_r($params, true));
        
        $jwt_token = $this->generate_jwt_token();
        
        $defaults = array(
            'topic' => 'Monthly Meeting',
            'type' => 2, // Scheduled meeting
            'start_time' => '', // Format: 2024-01-01T10:00:00Z
            'duration' => 60,
            'timezone' => 'America/New_York',
            'settings' => array(
                'host_video' => true,
                'participant_video' => true,
                'join_before_host' => false,
                'mute_upon_entry' => true,
                'waiting_room' => true
            )
        );

        $params = wp_parse_args($params, $defaults);

        $response = wp_remote_post($this->api_url . '/users/me/meetings', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $jwt_token,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($params)
        ));

        if (is_wp_error($response)) {
            fli_log("Zoom API Error: " . $response->get_error_message(), 'error');
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (wp_remote_retrieve_response_code($response) !== 201) {
            fli_log("Zoom API Error: " . ($body['message'] ?? 'Unknown error'), 'error');
            return new WP_Error('zoom_api_error', $body['message'] ?? 'Unknown error');
        }

        fli_log("Successfully created Zoom meeting ID: " . $body['id']);
        return $body;
    }

    /**
     * Generate JWT token for Zoom API
     * @return string JWT token
     */
    private function generate_jwt_token() {
        return FLI_JWT_Handler::generate($this->api_key, $this->api_secret);
    }

    /**
     * Delete a Zoom meeting
     * @param string $meeting_id Meeting ID
     * @return bool|WP_Error Success or error
     */
    public function delete_meeting($meeting_id) {
        fli_log("Attempting to delete Zoom meeting ID: {$meeting_id}");
        $jwt_token = $this->generate_jwt_token();

        $response = wp_remote_request($this->api_url . '/meetings/' . $meeting_id, array(
            'method' => 'DELETE',
            'headers' => array(
                'Authorization' => 'Bearer ' . $jwt_token
            )
        ));

        if (is_wp_error($response)) {
            fli_log("Error deleting Zoom meeting: " . $response->get_error_message(), 'error');
            return $response;
        }

        $success = wp_remote_retrieve_response_code($response) === 204;
        fli_log($success ? "Successfully deleted Zoom meeting" : "Failed to delete Zoom meeting", $success ? 'info' : 'error');
        return $success;
    }

    // Add other Zoom-related methods
} 
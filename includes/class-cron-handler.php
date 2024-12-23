<?php
/**
 * Cron Handler
 * @since 1.5.0
 */
class FLI_Cron_Handler {
    private $meetings_handler;

    public function __construct() {
        $this->meetings_handler = new FLI_Meetings_Handler();
        add_action('fli_generate_monthly_meetings', array($this, 'generate_meetings'));
    }

    /**
     * Generate next month's meetings
     */
    public function generate_meetings() {
        fli_log("Starting automated meeting generation");
        
        foreach ($this->meetings_handler->get_meeting_types() as $type => $settings) {
            fli_log("Checking future meetings for type: {$type}");
            
            if (!$this->meetings_handler->future_meeting_exists($type)) {
                fli_log("No future meeting exists for type: {$type}, generating new meeting");
                $result = $this->meetings_handler->generate_next_meeting($type);
                
                if (is_wp_error($result)) {
                    fli_log("Error generating meeting for type {$type}: " . $result->get_error_message(), 'error');
                } else {
                    fli_log("Successfully generated meeting ID: {$result} for type: {$type}");
                }
            }
        }
        
        fli_log("Completed automated meeting generation");
    }
} 
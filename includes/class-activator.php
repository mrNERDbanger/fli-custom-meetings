<?php
/**
 * Plugin Activation/Deactivation Handler
 * @since 1.5.0
 */
class FLI_Activator {
    /**
     * Plugin activation
     */
    public static function activate() {
        fli_log("Plugin activation started");
        
        // Create required database tables or options
        add_option('fli_custom_meetings_version', FLI_CUSTOM_MEETINGS_VERSION);
        
        // Register post type
        $meetings_handler = new FLI_Meetings_Handler();
        $meetings_handler->register_post_type();
        
        // Create initial meetings
        fli_log("Creating initial meetings");
        $meetings_handler->create_initial_meetings();
        
        // Schedule cron events
        if (!wp_next_scheduled('fli_generate_monthly_meetings')) {
            wp_schedule_event(time(), 'daily', 'fli_generate_monthly_meetings');
            fli_log("Scheduled daily meeting generation");
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
        fli_log("Plugin activation completed");
    }

    /**
     * Plugin deactivation
     */
    public static function deactivate() {
        fli_log("Plugin deactivation started");
        
        // Clear scheduled cron events
        wp_clear_scheduled_hook('fli_generate_monthly_meetings');
        wp_clear_scheduled_hook('fli_cleanup_logs');
        fli_log("Cleared scheduled cron events");
        
        // Flush rewrite rules
        flush_rewrite_rules();
        fli_log("Plugin deactivation completed");
    }
} 
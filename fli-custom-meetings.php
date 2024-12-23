<?php
/**
 * Plugin Name: FLI Custom Meetings
 * Description: Automatically schedules monthly meetings while skipping federal holidays and integrates with Zoom.
 * Version: 1.5.0
 * Author: Your Name
 * Text Domain: fli-custom-meetings
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('FLI_CUSTOM_MEETINGS_VERSION', '1.5.0');
define('FLI_CUSTOM_MEETINGS_PATH', plugin_dir_path(__FILE__));
define('FLI_CUSTOM_MEETINGS_URL', plugin_dir_url(__FILE__));

// Autoloader for classes
spl_autoload_register(function($class) {
    $prefix = 'FLI_';
    if (strpos($class, $prefix) !== 0) {
        return;
    }
    
    $class_path = str_replace($prefix, '', $class);
    $class_path = strtolower(str_replace('_', '-', $class_path));
    
    $file = FLI_CUSTOM_MEETINGS_PATH . 'includes/class-' . $class_path . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

// Include required files
require_once FLI_CUSTOM_MEETINGS_PATH . 'includes/class-meetings-handler.php';
require_once FLI_CUSTOM_MEETINGS_PATH . 'includes/class-zoom-api.php';
require_once FLI_CUSTOM_MEETINGS_PATH . 'includes/class-display-handler.php';
require_once FLI_CUSTOM_MEETINGS_PATH . 'admin/class-admin-interface.php';
require_once FLI_CUSTOM_MEETINGS_PATH . 'includes/class-activator.php';
require_once FLI_CUSTOM_MEETINGS_PATH . 'includes/class-jwt-handler.php';
require_once FLI_CUSTOM_MEETINGS_PATH . 'includes/class-cron-handler.php';
require_once FLI_CUSTOM_MEETINGS_PATH . 'includes/class-logger.php';

// Initialize the plugin
class FLI_Custom_Meetings {
    private $meetings_handler;
    private $admin_interface;
    private $display_handler;
    private $zoom_api;
    private $logger;

    public function __construct() {
        $this->init_plugin();
        $this->add_filters();
    }

    private function init_plugin() {
        // Initialize logger
        $this->logger = FLI_Logger::get_instance();
        
        // Initialize components
        $this->zoom_api = new FLI_Zoom_API(
            get_option('fli_custom_meetings_api_key'),
            get_option('fli_custom_meetings_api_secret')
        );
        $this->meetings_handler = new FLI_Meetings_Handler();
        $this->admin_interface = new FLI_Admin_Interface();
        $this->display_handler = new FLI_Display_Handler();
        
        // Initialize cron handler
        new FLI_Cron_Handler();

        // Schedule log cleanup
        if (!wp_next_scheduled('fli_cleanup_logs')) {
            wp_schedule_event(time(), 'daily', 'fli_cleanup_logs');
        }
        add_action('fli_cleanup_logs', array($this->logger, 'clear_old_logs'));
    }

    private function add_filters() {
        // Filter for meeting types
        add_filter('fli_meeting_types', function($types) {
            return $types;
        });

        // Filter for meeting display output
        add_filter('fli_meeting_display_output', function($output, $meeting) {
            return $output;
        }, 10, 2);

        // Filter for countdown timer format
        add_filter('fli_countdown_format', function($format) {
            return $format;
        });

        // Filter for join button text
        add_filter('fli_join_button_text', function($text) {
            return $text;
        });

        // Filter for meeting time format
        add_filter('fli_meeting_time_format', function($format) {
            return $format;
        });

        // Filter for meeting date format
        add_filter('fli_meeting_date_format', function($format) {
            return $format;
        });
    }

    public function get_logger() {
        return $this->logger;
    }
}

// Initialize plugin
$fli_custom_meetings = new FLI_Custom_Meetings();

// Make logger globally accessible
function fli_log($message, $level = 'info') {
    global $fli_custom_meetings;
    $fli_custom_meetings->get_logger()->log($message, $level);
}

// Register activation/deactivation hooks
register_activation_hook(__FILE__, array('FLI_Activator', 'activate'));
register_deactivation_hook(__FILE__, array('FLI_Activator', 'deactivate'));

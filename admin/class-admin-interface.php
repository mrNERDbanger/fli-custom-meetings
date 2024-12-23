<?php
/**
 * Admin Interface Handler
 * @since 1.5.0
 */
class FLI_Admin_Interface {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_filter('plugin_action_links_fli-custom-meetings/fli-custom-meetings.php', 
            array($this, 'add_settings_link'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __('Custom Meetings Settings', 'fli-custom-meetings'),
            __('Custom Meetings', 'fli-custom-meetings'),
            'manage_options',
            'fli-custom-meetings',
            array($this, 'display_admin_page')
        );
        
        add_submenu_page(
            'options-general.php',
            __('Meeting Logs', 'fli-custom-meetings'),
            __('Meeting Logs', 'fli-custom-meetings'),
            'manage_options',
            'fli-custom-meetings-logs',
            array($this, 'display_logs_page')
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        fli_log("Registering plugin settings");
        register_setting('fli_custom_meetings_options', 'fli_custom_meetings_api_key');
        register_setting('fli_custom_meetings_options', 'fli_custom_meetings_api_secret');
        register_setting('fli_custom_meetings_options', 'fli_custom_meeting_schedule');
        register_setting('fli_custom_meetings_options', 'fli_custom_meeting_time');
    }

    /**
     * Display admin page
     */
    public function display_admin_page() {
        if (!current_user_can('manage_options')) {
            fli_log("Unauthorized access attempt to admin page", 'warning');
            return;
        }

        fli_log("Displaying admin settings page");
        $nonce = wp_create_nonce('fli_custom_meetings_settings');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('fli_custom_meetings_options');
                do_settings_sections('fli_custom_meetings_options');
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e('Zoom API Key', 'fli-custom-meetings'); ?></th>
                        <td>
                            <input type="text" name="fli_custom_meetings_api_key" 
                                value="<?php echo esc_attr(get_option('fli_custom_meetings_api_key')); ?>" 
                                class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Zoom API Secret', 'fli-custom-meetings'); ?></th>
                        <td>
                            <input type="password" name="fli_custom_meetings_api_secret" 
                                value="<?php echo esc_attr(get_option('fli_custom_meetings_api_secret')); ?>" 
                                class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Custom Meeting Schedule', 'fli-custom-meetings'); ?></th>
                        <td>
                            <select name="fli_custom_meeting_schedule">
                                <option value="first_monday" <?php selected(get_option('fli_custom_meeting_schedule'), 'first_monday'); ?>>
                                    <?php esc_html_e('First Monday', 'fli-custom-meetings'); ?>
                                </option>
                                <option value="second_monday" <?php selected(get_option('fli_custom_meeting_schedule'), 'second_monday'); ?>>
                                    <?php esc_html_e('Second Monday', 'fli-custom-meetings'); ?>
                                </option>
                                <option value="third_monday" <?php selected(get_option('fli_custom_meeting_schedule'), 'third_monday'); ?>>
                                    <?php esc_html_e('Third Monday', 'fli-custom-meetings'); ?>
                                </option>
                                <option value="fourth_monday" <?php selected(get_option('fli_custom_meeting_schedule'), 'fourth_monday'); ?>>
                                    <?php esc_html_e('Fourth Monday', 'fli-custom-meetings'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Custom Meeting Time', 'fli-custom-meetings'); ?></th>
                        <td>
                            <input type="time" name="fli_custom_meeting_time" 
                                value="<?php echo esc_attr(get_option('fli_custom_meeting_time', '19:00')); ?>">
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Add settings link to plugins page
     * @param array $links Existing links
     * @return array Modified links
     */
    public function add_settings_link($links) {
        $settings_link = '<a href="options-general.php?page=fli-custom-meetings">' . 
            __('Settings', 'fli-custom-meetings') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    public function display_logs_page() {
        if (!current_user_can('manage_options')) {
            fli_log("Unauthorized access attempt to logs page", 'warning');
            return;
        }

        fli_log("Displaying logs page");
        $logger = FLI_Logger::get_instance();
        $current_log = isset($_GET['log']) ? sanitize_text_field($_GET['log']) : current_time('Y-m-d') . '.log';
        $log_dir = $logger->get_log_dir();
        $logs = glob($log_dir . '/*.log');
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="log-navigation">
                <select onchange="window.location.href='?page=fli-custom-meetings-logs&log=' + this.value">
                    <?php foreach ($logs as $log): ?>
                        <?php $filename = basename($log); ?>
                        <option value="<?php echo esc_attr($filename); ?>" <?php selected($current_log, $filename); ?>>
                            <?php echo esc_html($filename); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="log-content" style="background: #fff; padding: 20px; margin-top: 20px;">
                <?php
                $log_file = $log_dir . '/' . $current_log;
                if (file_exists($log_file)) {
                    echo '<pre>' . esc_html(file_get_contents($log_file)) . '</pre>';
                } else {
                    echo '<p>No logs found for this date.</p>';
                }
                ?>
            </div>
        </div>
        <?php
    }
} 
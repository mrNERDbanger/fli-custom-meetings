<?php
class FLI_Meeting_Settings {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function add_settings_page() {
        add_options_page(
            'Monthly Meetings Settings',
            'Monthly Meetings',
            'manage_options',
            'monthly-meetings-settings',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings() {
        register_setting('fli_meetings_settings', 'fli_zoom_client_id');
        register_setting('fli_meetings_settings', 'fli_zoom_client_secret');
        register_setting('fli_meetings_settings', 'fli_meetings_timezone', [
            'default' => 'America/Los_Angeles'
        ]);
        register_setting('fli_meetings_settings', 'fli_auto_recording', [
            'default' => 'cloud'
        ]);
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('fli_meetings_settings');
                do_settings_sections('fli_meetings_settings');
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="fli_zoom_client_id">Zoom Client ID</label>
                        </th>
                        <td>
                            <input type="text" id="fli_zoom_client_id" 
                                   name="fli_zoom_client_id" 
                                   value="<?php echo esc_attr(get_option('fli_zoom_client_id')); ?>" 
                                   class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="fli_zoom_client_secret">Zoom Client Secret</label>
                        </th>
                        <td>
                            <input type="password" id="fli_zoom_client_secret" 
                                   name="fli_zoom_client_secret" 
                                   value="<?php echo esc_attr(get_option('fli_zoom_client_secret')); ?>" 
                                   class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="fli_meetings_timezone">Default Timezone</label>
                        </th>
                        <td>
                            <select id="fli_meetings_timezone" name="fli_meetings_timezone">
                                <?php
                                $current_timezone = get_option('fli_meetings_timezone', 'America/Los_Angeles');
                                $timezones = DateTimeZone::listIdentifiers();
                                foreach ($timezones as $timezone) {
                                    printf(
                                        '<option value="%s" %s>%s</option>',
                                        esc_attr($timezone),
                                        selected($timezone, $current_timezone, false),
                                        esc_html($timezone)
                                    );
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="fli_auto_recording">Auto Recording</label>
                        </th>
                        <td>
                            <select id="fli_auto_recording" name="fli_auto_recording">
                                <?php
                                $current_setting = get_option('fli_auto_recording', 'cloud');
                                $options = [
                                    'none' => 'No Recording',
                                    'local' => 'Record on Local Computer',
                                    'cloud' => 'Record to Cloud'
                                ];
                                foreach ($options as $value => $label) {
                                    printf(
                                        '<option value="%s" %s>%s</option>',
                                        esc_attr($value),
                                        selected($value, $current_setting, false),
                                        esc_html($label)
                                    );
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
} 
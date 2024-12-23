<?php
class FLI_Custom_Meetings {
    
    private $meeting_types = [
        'topic' => 'Topic Session (90min)',
        'action' => 'Action Session (60min)',
        'special' => 'Special Event',
        'workshop' => 'Workshop'
    ];

    public function __construct() {
        add_action('init', [$this, 'register_meeting_post_type']);
        add_action('add_meta_boxes', [$this, 'add_meeting_meta_boxes']);
        add_action('save_post_meeting', [$this, 'save_meeting_meta']);
        add_filter('manage_meeting_posts_columns', [$this, 'add_meeting_columns']);
        add_action('manage_meeting_posts_custom_column', [$this, 'fill_meeting_columns'], 10, 2);
    }

    public function register_meeting_post_type() {
        register_post_type('meeting', [
            'labels' => [
                'name' => 'Meetings',
                'singular_name' => 'Meeting',
                'add_new' => 'Add New Meeting',
                'add_new_item' => 'Add New Meeting',
                'edit_item' => 'Edit Meeting',
            ],
            'public' => true,
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-video-alt2',
            'supports' => ['title'],
            'show_in_rest' => true,
        ]);
    }

    public function add_meeting_meta_boxes() {
        add_meta_box(
            'meeting_details',
            'Meeting Details',
            [$this, 'render_meeting_meta_box'],
            'meeting',
            'normal',
            'high'
        );
    }

    public function render_meeting_meta_box($post) {
        wp_nonce_field('meeting_meta_box', 'meeting_meta_box_nonce');

        // Get existing values
        $meeting_type = get_post_meta($post->ID, 'meeting_type', true);
        $meeting_date = get_post_meta($post->ID, 'meeting_date', true);
        $meeting_time = get_post_meta($post->ID, 'meeting_time', true);
        $duration = get_post_meta($post->ID, 'meeting_duration', true);
        $zoom_meeting_id = get_post_meta($post->ID, 'zoom_meeting_id', true);
        $is_recurring = get_post_meta($post->ID, 'is_recurring', true);
        $recurrence_pattern = get_post_meta($post->ID, 'recurrence_pattern', true);
        $max_attendees = get_post_meta($post->ID, 'max_attendees', true);
        $registration_required = get_post_meta($post->ID, 'registration_required', true);

        ?>
        <div class="meeting-meta-box">
            <style>
                .meeting-meta-box .form-row { margin-bottom: 15px; }
                .meeting-meta-box label { display: block; margin-bottom: 5px; font-weight: bold; }
                .meeting-meta-box input[type="text"], 
                .meeting-meta-box input[type="number"],
                .meeting-meta-box input[type="date"],
                .meeting-meta-box input[type="time"],
                .meeting-meta-box select { width: 100%; max-width: 400px; }
                .meeting-meta-box .description { color: #666; font-style: italic; margin-top: 5px; }
                .recurring-options { margin-top: 10px; padding-left: 20px; }
            </style>

            <div class="form-row">
                <label for="meeting_type">Meeting Type:</label>
                <select name="meeting_type" id="meeting_type" required>
                    <option value="">Select Type</option>
                    <?php foreach ($this->meeting_types as $value => $label): ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($meeting_type, $value); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-row">
                <label for="meeting_date">Date:</label>
                <input type="date" id="meeting_date" name="meeting_date" 
                       value="<?php echo esc_attr($meeting_date); ?>" required>
            </div>

            <div class="form-row">
                <label for="meeting_time">Time (PST):</label>
                <input type="time" id="meeting_time" name="meeting_time" 
                       value="<?php echo esc_attr($meeting_time); ?>" required>
            </div>

            <div class="form-row">
                <label for="meeting_duration">Duration (minutes):</label>
                <input type="number" id="meeting_duration" name="meeting_duration" 
                       value="<?php echo esc_attr($duration); ?>" min="15" step="15" required>
            </div>

            <div class="form-row">
                <label>
                    <input type="checkbox" name="is_recurring" value="1" 
                           <?php checked($is_recurring, '1'); ?>>
                    This is a recurring meeting
                </label>
                
                <div class="recurring-options" style="display: <?php echo $is_recurring ? 'block' : 'none'; ?>">
                    <select name="recurrence_pattern">
                        <option value="">Select Pattern</option>
                        <option value="weekly" <?php selected($recurrence_pattern, 'weekly'); ?>>Weekly</option>
                        <option value="biweekly" <?php selected($recurrence_pattern, 'biweekly'); ?>>Bi-weekly</option>
                        <option value="monthly" <?php selected($recurrence_pattern, 'monthly'); ?>>Monthly</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <label for="max_attendees">Maximum Attendees:</label>
                <input type="number" id="max_attendees" name="max_attendees" 
                       value="<?php echo esc_attr($max_attendees); ?>" min="1">
                <p class="description">Leave empty for unlimited</p>
            </div>

            <div class="form-row">
                <label>
                    <input type="checkbox" name="registration_required" value="1" 
                           <?php checked($registration_required, '1'); ?>>
                    Registration Required
                </label>
            </div>

            <div class="form-row">
                <label for="zoom_meeting_id">Zoom Meeting Link:</label>
                <input type="text" id="zoom_meeting_id" name="zoom_meeting_id" 
                       value="<?php echo esc_attr($zoom_meeting_id); ?>" readonly>
                <button type="button" class="button" id="create_zoom_meeting">Create Zoom Meeting</button>
                <p class="description">Click to generate a new Zoom meeting</p>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('input[name="is_recurring"]').change(function() {
                $('.recurring-options').toggle(this.checked);
            });

            $('#create_zoom_meeting').click(function() {
                // Get meeting details
                const meetingData = {
                    title: $('#title').val(),
                    date: $('#meeting_date').val(),
                    time: $('#meeting_time').val(),
                    duration: $('#meeting_duration').val(),
                    type: $('#meeting_type').val()
                };

                // Create Zoom meeting via AJAX
                $.post(ajaxurl, {
                    action: 'create_zoom_meeting',
                    nonce: '<?php echo wp_create_nonce('create_zoom_meeting'); ?>',
                    meeting: meetingData
                }, function(response) {
                    if (response.success) {
                        $('#zoom_meeting_id').val(response.data.join_url);
                        alert('Zoom meeting created successfully!');
                    } else {
                        alert('Error creating Zoom meeting: ' + response.data.message);
                    }
                });
            });
        });
        </script>
        <?php
    }

    public function save_meeting_meta($post_id) {
        if (!isset($_POST['meeting_meta_box_nonce']) || 
            !wp_verify_nonce($_POST['meeting_meta_box_nonce'], 'meeting_meta_box')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        $fields = [
            'meeting_type',
            'meeting_date',
            'meeting_time',
            'meeting_duration',
            'zoom_meeting_id',
            'is_recurring',
            'recurrence_pattern',
            'max_attendees',
            'registration_required'
        ];

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
    }

    private function log_error($message, $data = []) {
        $log_dir = wp_upload_dir()['basedir'] . '/meeting-logs';
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }
        
        $log_file = $log_dir . '/meeting-errors.log';
        $timestamp = current_time('mysql');
        $log_entry = sprintf("[%s] %s\n", $timestamp, $message);
        
        if (!empty($data)) {
            $log_entry .= print_r($data, true) . "\n";
        }
        
        error_log($log_entry, 3, $log_file);
    }
}

new FLI_Custom_Meetings(); 
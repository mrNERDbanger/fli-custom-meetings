<?php
class FLI_Meeting_Logger {
    private static $log_dir;
    private static $error_log;
    private static $activity_log;

    public static function init() {
        $upload_dir = wp_upload_dir();
        self::$log_dir = $upload_dir['basedir'] . '/fli-meetings-logs';
        self::$error_log = self::$log_dir . '/errors.log';
        self::$activity_log = self::$log_dir . '/activity.log';

        if (!file_exists(self::$log_dir)) {
            wp_mkdir_p(self::$log_dir);
            file_put_contents(self::$log_dir . '/.htaccess', 'deny from all');
        }
    }

    public static function log_error($message, $data = []) {
        self::write_log(self::$error_log, 'ERROR', $message, $data);
    }

    public static function log_activity($message, $data = []) {
        self::write_log(self::$activity_log, 'INFO', $message, $data);
    }

    private static function write_log($file, $level, $message, $data) {
        $timestamp = current_time('mysql');
        $log_entry = sprintf("[%s] [%s] %s\n", $timestamp, $level, $message);
        
        if (!empty($data)) {
            $log_entry .= json_encode($data, JSON_PRETTY_PRINT) . "\n";
        }
        
        error_log($log_entry, 3, $file);
    }

    public static function get_logs($type = 'error', $lines = 100) {
        $file = $type === 'error' ? self::$error_log : self::$activity_log;
        
        if (!file_exists($file)) {
            return [];
        }

        $logs = file($file);
        return array_slice($logs, -$lines);
    }

    public static function clear_logs($type = 'all') {
        if ($type === 'error' || $type === 'all') {
            file_put_contents(self::$error_log, '');
        }
        if ($type === 'activity' || $type === 'all') {
            file_put_contents(self::$activity_log, '');
        }
    }
} 
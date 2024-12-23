<?php
/**
 * Logger Handler
 * @since 1.5.0
 */
class FLI_Logger {
    private $log_dir;
    private static $instance = null;

    /**
     * Constructor
     */
    private function __construct() {
        $upload_dir = wp_upload_dir();
        $this->log_dir = $upload_dir['basedir'] . '/fli-custom-meetings/logs';
        
        // Create log directory if it doesn't exist
        if (!file_exists($this->log_dir)) {
            wp_mkdir_p($this->log_dir);
            
            // Create .htaccess to protect logs
            $htaccess = $this->log_dir . '/.htaccess';
            if (!file_exists($htaccess)) {
                file_put_contents($htaccess, 'Deny from all');
            }
        }
    }

    /**
     * Get logger instance
     * @return FLI_Logger Logger instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Log message
     * @param string $message Message to log
     * @param string $level Log level (info, warning, error)
     */
    public function log($message, $level = 'info') {
        if (!is_string($message)) {
            $message = print_r($message, true);
        }

        $timestamp = current_time('Y-m-d H:i:s');
        $log_entry = sprintf('[%s] [%s] %s%s', $timestamp, strtoupper($level), $message, PHP_EOL);
        
        $filename = $this->log_dir . '/' . current_time('Y-m-d') . '.log';
        error_log($log_entry, 3, $filename);
    }

    /**
     * Clear old logs
     * Keeps logs for 30 days by default
     * @param int $days Days to keep logs
     */
    public function clear_old_logs($days = 30) {
        $files = glob($this->log_dir . '/*.log');
        $now = time();

        foreach ($files as $file) {
            if (is_file($file)) {
                if ($now - filemtime($file) >= $days * 24 * 60 * 60) {
                    unlink($file);
                }
            }
        }
    }

    /**
     * Get log directory path
     * @return string Log directory path
     */
    public function get_log_dir() {
        return $this->log_dir;
    }
} 
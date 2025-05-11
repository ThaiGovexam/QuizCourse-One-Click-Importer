<?php
/**
 * Logger class for the QuizCourse Importer plugin.
 *
 * Handles logging of plugin actions for debugging and troubleshooting.
 */
class QCI_Logger {

    /**
     * Log file path
     * 
     * @var string
     */
    private static $log_file;

    /**
     * Whether logging is enabled
     * 
     * @var bool
     */
    private static $logging_enabled;

    /**
     * Maximum log file size in bytes (5 MB)
     * 
     * @var int
     */
    private static $max_file_size = 5242880;

    /**
     * Log retention period in days
     * 
     * @var int
     */
    private static $retention_days = 7;

    /**
     * Initialize the logger
     */
    public static function init() {
        // Check if logging is enabled
        self::$logging_enabled = get_option('qci_enable_logging', true);
        
        // If logging is disabled, don't proceed
        if (!self::$logging_enabled) {
            return;
        }
        
        // Set up log directory
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/qci-logs';
        
        // Create logs directory if it doesn't exist
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
            
            // Create an index.php file to prevent directory listing
            file_put_contents($log_dir . '/index.php', '<?php // Silence is golden');
            
            // Create .htaccess to restrict direct access
            file_put_contents($log_dir . '/.htaccess', 'deny from all');
        }
        
        // Create log file with date in filename
        $date = date('Y-m-d');
        self::$log_file = $log_dir . '/qci-log-' . $date . '.log';
        
        // Rotate logs if needed
        self::rotate_logs($log_dir);
    }

    /**
     * Log a message
     * 
     * @param string $message The message to log
     * @param string $level The log level (info, warning, error)
     * @param array $context Additional context for the log entry
     * @return bool Whether the log operation was successful
     */
    public static function log($message, $level = 'info', $context = array()) {
        // Initialize if not initialized
        if (!isset(self::$logging_enabled)) {
            self::init();
        }
        
        // If logging is disabled, return early
        if (!self::$logging_enabled) {
            return false;
        }
        
        // Prepare log entry
        $time = date('Y-m-d H:i:s');
        $level = strtoupper($level);
        
        // Format message
        $entry = "[{$time}] [{$level}] {$message}";
        
        // Add context if available
        if (!empty($context)) {
            $entry .= " | Context: " . json_encode($context);
        }
        
        // Add user info if available
        $current_user = wp_get_current_user();
        if ($current_user->exists()) {
            $entry .= " | User: {$current_user->user_login} (ID: {$current_user->ID})";
        }
        
        // Add IP address
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $entry .= " | IP: " . sanitize_text_field($_SERVER['REMOTE_ADDR']);
        }
        
        // Add new line
        $entry .= PHP_EOL;
        
        // Append to log file
        $result = file_put_contents(self::$log_file, $entry, FILE_APPEND);
        
        return ($result !== false);
    }

    /**
     * Rotate log files based on size and retention period
     * 
     * @param string $log_dir Directory where logs are stored
     */
    private static function rotate_logs($log_dir) {
        // Check current log file size
        if (file_exists(self::$log_file) && filesize(self::$log_file) > self::$max_file_size) {
            // Rename file with timestamp if it exceeds max size
            $timestamp = date('Y-m-d-H-i-s');
            $new_filename = str_replace('.log', "-{$timestamp}.log", self::$log_file);
            rename(self::$log_file, $new_filename);
        }
        
        // Delete old log files beyond retention period
        $retention_time = time() - (DAY_IN_SECONDS * self::$retention_days);
        
        // Scan log directory
        $files = glob($log_dir . '/qci-log-*.log');
        foreach ($files as $file) {
            // Extract date from filename
            if (preg_match('/qci-log-(\d{4}-\d{2}-\d{2})/', $file, $matches)) {
                $log_date = strtotime($matches[1]);
                
                // Delete if older than retention period
                if ($log_date < $retention_time) {
                    @unlink($file);
                }
            }
        }
    }

    /**
     * Clear all log files
     * 
     * @return bool Whether the operation was successful
     */
    public static function clear_logs() {
        // Initialize if not initialized
        if (!isset(self::$logging_enabled)) {
            self::init();
        }
        
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/qci-logs';
        
        // If log directory doesn't exist, nothing to clear
        if (!file_exists($log_dir)) {
            return true;
        }
        
        // Delete all log files
        $files = glob($log_dir . '/qci-log-*.log');
        $success = true;
        
        foreach ($files as $file) {
            if (!@unlink($file)) {
                $success = false;
            }
        }
        
        return $success;
    }

    /**
     * Get all log files
     * 
     * @return array Array of log files with metadata
     */
    public static function get_log_files() {
        // Initialize if not initialized
        if (!isset(self::$logging_enabled)) {
            self::init();
        }
        
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/qci-logs';
        
        // If log directory doesn't exist, return empty array
        if (!file_exists($log_dir)) {
            return array();
        }
        
        // Get all log files
        $files = glob($log_dir . '/qci-log-*.log');
        $log_files = array();
        
        foreach ($files as $file) {
            $filename = basename($file);
            $filesize = filesize($file);
            $modified = filemtime($file);
            
            $log_files[] = array(
                'filename' => $filename,
                'path' => $file,
                'size' => size_format($filesize),
                'size_bytes' => $filesize,
                'modified' => date('Y-m-d H:i:s', $modified),
                'modified_timestamp' => $modified
            );
        }
        
        // Sort by modified date (newest first)
        usort($log_files, function($a, $b) {
            return $b['modified_timestamp'] - $a['modified_timestamp'];
        });
        
        return $log_files;
    }

    /**
     * Get the contents of a specific log file
     * 
     * @param string $filename The log filename
     * @return string|WP_Error Log contents or error
     */
    public static function get_log_content($filename) {
        // Initialize if not initialized
        if (!isset(self::$logging_enabled)) {
            self::init();
        }
        
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/qci-logs';
        $file_path = $log_dir . '/' . $filename;
        
        // Validate file path (security check to prevent directory traversal)
        if (strpos($filename, '..') !== false || !file_exists($file_path)) {
            return new WP_Error('invalid_file', __('Invalid log file.', 'quizcourse-importer'));
        }
        
        // Get file contents
        $content = file_get_contents($file_path);
        
        if ($content === false) {
            return new WP_Error('read_error', __('Could not read log file.', 'quizcourse-importer'));
        }
        
        return $content;
    }

    /**
     * Enable or disable logging
     * 
     * @param bool $enable Whether to enable logging
     * @return bool Whether the operation was successful
     */
    public static function set_logging_status($enable) {
        $result = update_option('qci_enable_logging', (bool) $enable);
        self::$logging_enabled = (bool) $enable;
        return $result;
    }

    /**
     * Check if logging is enabled
     * 
     * @return bool Whether logging is enabled
     */
    public static function is_logging_enabled() {
        // Initialize if not initialized
        if (!isset(self::$logging_enabled)) {
            self::init();
        }
        
        return self::$logging_enabled;
    }

    /**
     * Set log retention period
     * 
     * @param int $days Number of days to keep logs
     * @return bool Whether the operation was successful
     */
    public static function set_retention_period($days) {
        $days = absint($days);
        if ($days < 1) {
            $days = 7; // Default to 7 days if invalid value
        }
        
        $result = update_option('qci_log_retention_days', $days);
        self::$retention_days = $days;
        return $result;
    }

    /**
     * Get log retention period
     * 
     * @return int Number of days logs are kept
     */
    public static function get_retention_period() {
        $days = get_option('qci_log_retention_days', 7);
        return absint($days);
    }

    /**
     * Download a log file
     * 
     * @param string $filename The log filename to download
     */
    public static function download_log($filename) {
        // Initialize if not initialized
        if (!isset(self::$logging_enabled)) {
            self::init();
        }
        
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/qci-logs';
        $file_path = $log_dir . '/' . $filename;
        
        // Validate file path (security check to prevent directory traversal)
        if (strpos($filename, '..') !== false || !file_exists($file_path)) {
            wp_die(__('Invalid log file.', 'quizcourse-importer'));
        }
        
        // Set headers for file download
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($file_path));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Output file contents
        readfile($file_path);
        exit;
    }

    /**
     * Log an exception
     * 
     * @param Exception $exception The exception to log
     * @param array $context Additional context for the log entry
     * @return bool Whether the log operation was successful
     */
    public static function log_exception($exception, $context = array()) {
        $message = sprintf(
            'Exception: %s in %s on line %d. Trace: %s',
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );
        
        return self::log($message, 'error', $context);
    }

    /**
     * Log a system message
     * 
     * @param string $message The message to log
     * @param array $context Additional context for the log entry
     * @return bool Whether the log operation was successful
     */
    public static function log_system($message, $context = array()) {
        return self::log($message, 'system', $context);
    }

    /**
     * Log an import operation
     * 
     * @param string $message The message to log
     * @param array $stats Import statistics
     * @return bool Whether the log operation was successful
     */
    public static function log_import($message, $stats = array()) {
        return self::log($message, 'import', $stats);
    }

    /**
     * Log a warning
     * 
     * @param string $message The warning message
     * @param array $context Additional context for the log entry
     * @return bool Whether the log operation was successful
     */
    public static function log_warning($message, $context = array()) {
        return self::log($message, 'warning', $context);
    }

    /**
     * Log an error
     * 
     * @param string $message The error message
     * @param array $context Additional context for the log entry
     * @return bool Whether the log operation was successful
     */
    public static function log_error($message, $context = array()) {
        return self::log($message, 'error', $context);
    }
}

// Initialize the logger
QCI_Logger::init();

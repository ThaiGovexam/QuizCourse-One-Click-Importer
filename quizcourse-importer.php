<?php
/**
 * Plugin Name: QuizCourse One-Click Importer
 * Description: Import Quizzes and Courses with a single file upload for internet marketers and solo entrepreneurs
 * Version: 1.0.1
 * Author: Your Name
 * Text Domain: quizcourse-importer
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('QCI_VERSION', '1.0.1');
define('QCI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('QCI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('QCI_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Debug helper function - only logs when WP_DEBUG is enabled
 */
function qci_log($message) {
    if (defined('WP_DEBUG') && WP_DEBUG === true) {
        if (is_array($message) || is_object($message)) {
            error_log(print_r($message, true));
        } else {
            error_log($message);
        }
    }
}

/**
 * Check PHP version and required extensions
 */
function qci_check_requirements() {
    $errors = array();

    // Check PHP version
    if (version_compare(PHP_VERSION, '7.2', '<')) {
        $errors[] = sprintf(
            __('QuizCourse Importer requires PHP 7.2 or higher. You are running PHP %s.', 'quizcourse-importer'),
            PHP_VERSION
        );
    }

    // Check ZipArchive for Excel processing
    if (!class_exists('ZipArchive') && !extension_loaded('zip')) {
        $errors[] = __('QuizCourse Importer requires the PHP ZIP extension for processing Excel files.', 'quizcourse-importer');
    }

    // Check for required extensions
    $required_extensions = array(
        'xml' => 'SimpleXML',
        'mbstring' => 'Multibyte String',
        'gd' => 'GD Library'
    );

    foreach ($required_extensions as $ext => $name) {
        if (!extension_loaded($ext)) {
            $errors[] = sprintf(
                __('QuizCourse Importer requires the PHP %s extension.', 'quizcourse-importer'),
                $name
            );
        }
    }

    return $errors;
}

/**
 * Displays admin notice for requirements not met
 */
function qci_requirements_notice() {
    $errors = qci_check_requirements();

    if (!empty($errors)) {
        echo '<div class="notice notice-error">';
        echo '<p>' . __('QuizCourse Importer plugin cannot be activated because it requires:', 'quizcourse-importer') . '</p>';
        echo '<ul style="padding-left: 20px; list-style-type: disc;">';
        foreach ($errors as $error) {
            echo '<li>' . $error . '</li>';
        }
        echo '</ul>';
        echo '</div>';

        // Deactivate plugin
        deactivate_plugins(plugin_basename(__FILE__));
        
        if (isset($_GET['activate'])) {
            unset($_GET['activate']);
        }
    }
}
add_action('admin_notices', 'qci_requirements_notice');

/**
 * Check and require PhpSpreadsheet dependency
 */
function qci_check_phpspreadsheet() {
    // Try standard composer autoload first
    $autoload_paths = array(
        QCI_PLUGIN_DIR . 'vendor/autoload.php',
        QCI_PLUGIN_DIR . 'libraries/PhpSpreadsheet/vendor/autoload.php',
    );
    
    $spreadsheet_loaded = false;
    
    foreach ($autoload_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $spreadsheet_loaded = class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet');
            if ($spreadsheet_loaded) {
                break;
            }
        }
    }
    
    if (!$spreadsheet_loaded) {
        qci_log('PhpSpreadsheet library not found. Trying to load minimal required classes.');
        
        // If composer autoload not available, try manual loading of core files
        // This is a fallback but not ideal - should add proper message to install dependencies
        $minimal_required = array(
            'Spreadsheet.php',
            'IOFactory.php',
            'Reader/IReader.php',
            'Reader/BaseReader.php',
            'Reader/Xlsx.php',
            'Reader/Csv.php',
        );
        
        foreach ($minimal_required as $file) {
            $file_path = QCI_PLUGIN_DIR . 'libraries/PhpSpreadsheet/src/' . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
    }
    
    return class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet');
}

/**
 * Main plugin class with error handling
 */
class QuizCourse_Importer {

    /**
     * Define the core functionality of the plugin.
     */
    public function __construct() {
        try {
            $this->load_dependencies();
            $this->define_admin_hooks();
            $this->define_ajax_hooks();
        } catch (Exception $e) {
            qci_log('Error initializing QuizCourse_Importer: ' . $e->getMessage());
            add_action('admin_notices', function() use ($e) {
                ?>
                <div class="notice notice-error">
                    <p><?php _e('Error initializing QuizCourse Importer: ', 'quizcourse-importer'); ?><?php echo esc_html($e->getMessage()); ?></p>
                </div>
                <?php
            });
        }
    }

    /**
     * Load the required dependencies for this plugin.
     */
    private function load_dependencies() {
        // Include core classes with error handling
        $core_files = array(
            'includes/class-file-processor.php',
            'includes/class-data-validator.php',
            'includes/class-data-importer.php',
            'includes/class-logger.php',
            'admin/class-admin.php'
        );
        
        foreach ($core_files as $file) {
            $file_path = QCI_PLUGIN_DIR . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            } else {
                throw new Exception(sprintf(__('Required file %s not found.', 'quizcourse-importer'), $file));
            }
        }
    }

    /**
     * Register all of the hooks related to the admin area functionality.
     */
    private function define_admin_hooks() {
        $admin = new QuizCourse_Admin();
        
        // Add menu items
        add_action('admin_menu', array($admin, 'add_admin_menu'));
        
        // Enqueue scripts and styles
        add_action('admin_enqueue_scripts', array($admin, 'enqueue_scripts'));
        
        // Add action links
        add_filter('plugin_action_links_' . QCI_PLUGIN_BASENAME, array($admin, 'add_action_links'));
    }

    /**
     * Register all AJAX callbacks.
     */
    private function define_ajax_hooks() {
        // AJAX for file upload and validation
        add_action('wp_ajax_qci_validate_file', array($this, 'ajax_validate_file'));
        add_action('wp_ajax_qci_process_import', array($this, 'ajax_process_import'));
        add_action('wp_ajax_qci_prepare_import', array($this, 'ajax_prepare_import'));
        add_action('wp_ajax_qci_process_import_stage', array($this, 'ajax_process_import_stage'));
        add_action('wp_ajax_qci_cancel_import', array($this, 'ajax_cancel_import'));
        add_action('wp_ajax_qci_save_mapping_template', array($this, 'ajax_save_mapping_template'));
        add_action('wp_ajax_qci_load_mapping_template', array($this, 'ajax_load_mapping_template'));
    }

    /**
     * AJAX callback for file validation.
     */
    public function ajax_validate_file() {
        // Check nonce
        check_ajax_referer('qci-security', 'security');
        
        try {
            // Handle file validation
            $validator = new QCI_Data_Validator();
            $result = $validator->validate_uploaded_file();
            
            if (is_wp_error($result)) {
                wp_send_json_error($result->get_error_message());
            } else {
                wp_send_json_success($result);
            }
        } catch (Exception $e) {
            qci_log('Error in ajax_validate_file: ' . $e->getMessage());
            wp_send_json_error(__('Error validating file: ', 'quizcourse-importer') . $e->getMessage());
        }
    }

    /**
     * AJAX callback for import processing.
     */
    public function ajax_process_import() {
        // Check nonce
        check_ajax_referer('qci-security', 'security');
        
        try {
            // Process the import
            $importer = new QCI_Data_Importer();
            $result = $importer->process_import($_POST);
            
            if (is_wp_error($result)) {
                wp_send_json_error($result->get_error_message());
            } else {
                wp_send_json_success($result);
            }
        } catch (Exception $e) {
            qci_log('Error in ajax_process_import: ' . $e->getMessage());
            wp_send_json_error(__('Error during import: ', 'quizcourse-importer') . $e->getMessage());
        }
    }

    /**
     * AJAX callback for import preparation.
     */
    public function ajax_prepare_import() {
        // Check nonce
        check_ajax_referer('qci-security', 'security');
        
        try {
            // Prepare import
            $importer = new QCI_Data_Importer();
            $result = $importer->prepare_import($_POST);
            
            if (is_wp_error($result)) {
                wp_send_json_error($result->get_error_message());
            } else {
                wp_send_json_success($result);
            }
        } catch (Exception $e) {
            qci_log('Error in ajax_prepare_import: ' . $e->getMessage());
            wp_send_json_error(__('Error preparing import: ', 'quizcourse-importer') . $e->getMessage());
        }
    }

    /**
     * AJAX callback for processing import stage.
     */
    public function ajax_process_import_stage() {
        // Check nonce
        check_ajax_referer('qci-security', 'security');
        
        try {
            // Process import stage
            $importer = new QCI_Data_Importer();
            $result = $importer->process_import_stage($_POST);
            
            if (is_wp_error($result)) {
                wp_send_json_error($result->get_error_message());
            } else {
                wp_send_json_success($result);
            }
        } catch (Exception $e) {
            qci_log('Error in ajax_process_import_stage: ' . $e->getMessage());
            wp_send_json_error(__('Error processing import stage: ', 'quizcourse-importer') . $e->getMessage());
        }
    }

    /**
     * AJAX callback for cancelling import.
     */
    public function ajax_cancel_import() {
        // Check nonce
        check_ajax_referer('qci-security', 'security');
        
        try {
            // Cancel import
            $importer = new QCI_Data_Importer();
            $result = $importer->cancel_import($_POST['file_id']);
            
            wp_send_json_success($result);
        } catch (Exception $e) {
            qci_log('Error in ajax_cancel_import: ' . $e->getMessage());
            wp_send_json_error(__('Error cancelling import: ', 'quizcourse-importer') . $e->getMessage());
        }
    }

    /**
     * AJAX callback for saving mapping template.
     */
    public function ajax_save_mapping_template() {
        // Check nonce
        check_ajax_referer('qci-security', 'security');
        
        try {
            $template_name = sanitize_text_field($_POST['template_name']);
            $mappings = json_decode(stripslashes($_POST['mapping_data']), true);
            
            if (empty($template_name)) {
                wp_send_json_error(__('Template name is required.', 'quizcourse-importer'));
                return;
            }
            
            // Get existing templates
            $templates = get_option('qci_mapping_templates', array());
            
            // Generate a unique ID
            $template_id = 'template_' . uniqid();
            
            // Save new template
            $templates[$template_id] = array(
                'name' => $template_name,
                'mappings' => $mappings,
                'created' => current_time('mysql')
            );
            
            update_option('qci_mapping_templates', $templates);
            
            wp_send_json_success(array(
                'message' => __('Mapping template saved successfully.', 'quizcourse-importer'),
                'template_id' => $template_id
            ));
        } catch (Exception $e) {
            qci_log('Error in ajax_save_mapping_template: ' . $e->getMessage());
            wp_send_json_error(__('Error saving mapping template: ', 'quizcourse-importer') . $e->getMessage());
        }
    }

    /**
     * AJAX callback for loading mapping template.
     */
    public function ajax_load_mapping_template() {
        // Check nonce
        check_ajax_referer('qci-security', 'security');
        
        try {
            $template_id = sanitize_text_field($_POST['template_id']);
            
            // Get existing templates
            $templates = get_option('qci_mapping_templates', array());
            
            if (!isset($templates[$template_id])) {
                wp_send_json_error(__('Template not found.', 'quizcourse-importer'));
                return;
            }
            
            wp_send_json_success(array(
                'template_name' => $templates[$template_id]['name'],
                'mapping_data' => $templates[$template_id]['mappings']
            ));
        } catch (Exception $e) {
            qci_log('Error in ajax_load_mapping_template: ' . $e->getMessage());
            wp_send_json_error(__('Error loading mapping template: ', 'quizcourse-importer') . $e->getMessage());
        }
    }

    /**
     * Run the plugin.
     */
    public function run() {
        // Check if PhpSpreadsheet is available before running the plugin
        if (!qci_check_phpspreadsheet()) {
            add_action('admin_notices', function() {
                ?>
                <div class="notice notice-error">
                    <p><?php _e('QuizCourse Importer requires the PhpSpreadsheet library. Please contact the plugin developer.', 'quizcourse-importer'); ?></p>
                </div>
                <?php
            });
            return;
        }
        
        // Plugin initialization logic
        add_action('plugins_loaded', array($this, 'load_textdomain'));
    }

    /**
     * Load plugin textdomain.
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'quizcourse-importer',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }

    /**
     * Fired during plugin activation.
     */
    public static function activate() {
        // Check requirements before activation
        $errors = qci_check_requirements();
        
        if (!empty($errors)) {
            // Requirements not met, display error
            qci_log('QuizCourse Importer activation failed due to unmet requirements');
            return;
        }
        
        // Create necessary directories
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/qci-temp';
        $log_dir = $upload_dir['basedir'] . '/qci-logs';
        
        foreach (array($temp_dir, $log_dir) as $dir) {
            if (!file_exists($dir)) {
                wp_mkdir_p($dir);
                
                // Create an index.php to prevent directory listing
                file_put_contents($dir . '/index.php', '<?php // Silence is golden');
                
                // Create .htaccess for security
                file_put_contents($dir . '/.htaccess', 'deny from all');
            }
        }
        
        // Create database tables if needed
        if (class_exists('QuizCourse_Admin')) {
            QuizCourse_Admin::create_tables();
        }
        
        // Set default settings
        $default_settings = array(
            'default_course_status' => 'draft',
            'default_quiz_status' => 'draft',
            'enable_auto_mapping' => 'yes',
            'import_batch_size' => '50',
            'enable_logging' => 'yes',
            'notification_email' => get_option('admin_email'),
            'default_author' => get_current_user_id(),
            'skip_existing' => 'yes',
            'clear_temp_files' => 'yes',
            'csv_delimiter' => ',',
            'enable_auto_publish' => 'no',
            'import_timeout' => '300',
        );
        
        $existing_settings = get_option('qci_settings', array());
        $settings = wp_parse_args($existing_settings, $default_settings);
        update_option('qci_settings', $settings);
        
        // Schedule cleanup cron job
        if (class_exists('QuizCourse_Admin')) {
            QuizCourse_Admin::schedule_cleanup();
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Fired during plugin deactivation.
     */
    public static function deactivate() {
        // Unschedule cron jobs
        if (class_exists('QuizCourse_Admin')) {
            QuizCourse_Admin::unschedule_cleanup();
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}

// Check requirements before initialization
$errors = qci_check_requirements();
if (empty($errors)) {
    // Initialize the plugin
    function run_quizcourse_importer() {
        $plugin = new QuizCourse_Importer();
        $plugin->run();
    }

    // Start the plugin
    run_quizcourse_importer();

    // Activation and deactivation hooks
    register_activation_hook(__FILE__, array('QuizCourse_Importer', 'activate'));
    register_deactivation_hook(__FILE__, array('QuizCourse_Importer', 'deactivate'));
}

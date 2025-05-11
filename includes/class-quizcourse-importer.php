<?php
/**
 * The core plugin class.
 */
class QuizCourse_Importer {

    /**
     * The loader that's responsible for maintaining and registering all hooks.
     */
    protected $loader;

    /**
     * Define the core functionality of the plugin.
     */
    public function __construct() {
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_ajax_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     */
    private function load_dependencies() {
        // Include core classes
        require_once QCI_PLUGIN_DIR . 'includes/class-file-processor.php';
        require_once QCI_PLUGIN_DIR . 'includes/class-data-validator.php';
        require_once QCI_PLUGIN_DIR . 'includes/class-data-importer.php';
        require_once QCI_PLUGIN_DIR . 'includes/class-logger.php';
        
        // Include admin classes
        require_once QCI_PLUGIN_DIR . 'admin/class-admin.php';
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
    }

    /**
     * Register all AJAX callbacks.
     */
    private function define_ajax_hooks() {
        // AJAX for file upload and validation
        add_action('wp_ajax_qci_validate_file', array($this, 'ajax_validate_file'));
        add_action('wp_ajax_qci_process_import', array($this, 'ajax_process_import'));
    }

    /**
     * AJAX callback for file validation.
     */
    public function ajax_validate_file() {
        // Check nonce
        check_ajax_referer('qci-security', 'security');
        
        // Handle file validation
        $validator = new QCI_Data_Validator();
        $result = $validator->validate_uploaded_file();
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success($result);
        }
    }

    /**
     * AJAX callback for import processing.
     */
    public function ajax_process_import() {
        // Check nonce
        check_ajax_referer('qci-security', 'security');
        
        // Process the import
        $importer = new QCI_Data_Importer();
        $result = $importer->process_import($_POST);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success($result);
        }
    }

    /**
     * Run the plugin.
     */
    public function run() {
        // Plugin initialization logic
    }

    /**
     * Fired during plugin activation.
     */
    public static function activate() {
        // Activation logic
    }

    /**
     * Fired during plugin deactivation.
     */
    public static function deactivate() {
        // Deactivation logic
    }
}

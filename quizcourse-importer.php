<?php
/**
 * Plugin Name: QuizCourse One-Click Importer
 * Description: Import Quizzes and Courses with a single file upload for internet marketers and solo entrepreneurs
 * Version: 1.0.0
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
define('QCI_VERSION', '1.0.0');
define('QCI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('QCI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('QCI_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Require Composer Autoloader if available
if (file_exists(QCI_PLUGIN_DIR . 'vendor/autoload.php')) {
    require_once QCI_PLUGIN_DIR . 'vendor/autoload.php';
}

// Include the main plugin class
require_once QCI_PLUGIN_DIR . 'includes/class-quizcourse-importer.php';

// Activation and deactivation hooks
register_activation_hook(__FILE__, array('QuizCourse_Importer', 'activate'));
register_deactivation_hook(__FILE__, array('QuizCourse_Importer', 'deactivate'));

// Initialize the plugin
function run_quizcourse_importer() {
    $plugin = new QuizCourse_Importer();
    $plugin->run();
}

// Start the plugin
run_quizcourse_importer();

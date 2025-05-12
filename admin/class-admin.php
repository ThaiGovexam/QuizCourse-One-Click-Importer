<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://yourwebsite.com
 * @since      1.0.0
 *
 * @package    QuizCourse_Importer
 * @subpackage QuizCourse_Importer/admin
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for
 * how to enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    QuizCourse_Importer
 * @subpackage QuizCourse_Importer/admin
 * @author     Your Name <email@example.com>
 */
class QuizCourse_Admin {

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        // Register custom post types if needed
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     * @param    string    $hook    The current admin page.
     */
    public function enqueue_scripts($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'quizcourse-importer') === false) {
            return;
        }

        // Add media uploader scripts and styles
        wp_enqueue_media();

        // CSS
        wp_enqueue_style('qci-admin-css', QCI_PLUGIN_URL . 'assets/css/admin.css', array(), QCI_VERSION);
        wp_enqueue_style('qci-importer-css', QCI_PLUGIN_URL . 'assets/css/importer.css', array(), QCI_VERSION);

        // jQuery UI for tabs, dialog, etc.
        wp_enqueue_style('jquery-ui-css', '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
        wp_enqueue_script('jquery-ui-tabs');
        wp_enqueue_script('jquery-ui-dialog');
        wp_enqueue_script('jquery-ui-progressbar');
        wp_enqueue_script('jquery-ui-tooltip');

        // Admin JS
        wp_enqueue_script('qci-admin-js', QCI_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), QCI_VERSION, true);
        wp_enqueue_script('qci-importer-js', QCI_PLUGIN_URL . 'assets/js/importer.js', array('jquery', 'jquery-ui-tabs', 'jquery-ui-progressbar'), QCI_VERSION, true);

        // Localize script for AJAX and translations
        wp_localize_script('qci-importer-js', 'qci_strings', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('qci-ajax-nonce'),
            'no_file_selected' => __('Please select a file to upload.', 'quizcourse-importer'),
            'validating' => __('Validating...', 'quizcourse-importer'),
            'continue_to_mapping' => __('Continue to Field Mapping', 'quizcourse-importer'),
            'validation_error' => __('File validation failed.', 'quizcourse-importer'),
            'server_error' => __('Server error. Please try again.', 'quizcourse-importer'),
            'importing' => __('Importing Your Data...', 'quizcourse-importer'),
            'processing' => __('Processing...', 'quizcourse-importer'),
            'import_complete' => __('Import Completed Successfully!', 'quizcourse-importer'),
            'import_failed' => __('Import Failed', 'quizcourse-importer'),
            'imported' => __('Successfully Imported', 'quizcourse-importer'),
            'courses' => __('Courses', 'quizcourse-importer'),
            'sections' => __('Sections', 'quizcourse-importer'),
            'quizzes' => __('Quizzes', 'quizcourse-importer'),
            'questions' => __('Questions', 'quizcourse-importer'),
            'answers' => __('Answers', 'quizcourse-importer'),
            'go_back' => __('Go Back', 'quizcourse-importer'),
            'view_courses' => __('View Courses', 'quizcourse-importer'),
            'new_import' => __('New Import', 'quizcourse-importer'),
            'courses_url' => admin_url('edit.php?post_type=course'),
            'import_url' => admin_url('admin.php?page=quizcourse-importer')
        ));
    }

    /**
     * Register the administration menu for this plugin.
     *
     * @since    1.0.0
     */
    public function add_admin_menu() {
        // Main menu
        try {
        add_menu_page(
            'QuizCourse Importer',  // page_title
            'QC Importer',         // menu_title
            'manage_options',       // capability
            'quizcourse-importer',  // menu_slug
            array($this, 'display_importer_page'),  // callback function
            'dashicons-upload',     // icon
            30                     // position
        );

        

        // Submenu pages
        add_submenu_page(
            'quizcourse-importer',
            __('Import', 'quizcourse-importer'),
            __('Import', 'quizcourse-importer'),
            'manage_options',
            'quizcourse-importer',
            array($this, 'display_importer_page')
        );

        add_submenu_page(
            'quizcourse-importer',
            __('Settings', 'quizcourse-importer'),
            __('Settings', 'quizcourse-importer'),
            'manage_options',
            'quizcourse-settings',
            array($this, 'display_settings_page')
        );

        add_submenu_page(
            'quizcourse-importer',
            __('Help & Documentation', 'quizcourse-importer'),
            __('Help', 'quizcourse-importer'),
            'manage_options',
            'quizcourse-help',
            array($this, 'display_help_page')
        );

        // Add import history page
        add_submenu_page(
            'quizcourse-importer',
            __('Import History', 'quizcourse-importer'),
            __('Import History', 'quizcourse-importer'),
            'manage_options',
            'quizcourse-history',
            array($this, 'display_history_page')
        );
    }

    /**
     * Display the importer page.
     *
     * @since    1.0.0
     */
    public function display_importer_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }

        // Add admin header
        require_once QCI_PLUGIN_DIR . 'admin/views/header.php';

        // Include the main importer view
        require_once QCI_PLUGIN_DIR . 'admin/views/importer-page.php';

        // Add admin footer
        require_once QCI_PLUGIN_DIR . 'admin/views/footer.php';
    }

    /**
     * Display the settings page.
     *
     * @since    1.0.0
     */
    public function display_settings_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }

        // Process settings form submission
        $this->process_settings_form();

        // Add admin header
        require_once QCI_PLUGIN_DIR . 'admin/views/header.php';

        // Include the settings view
        require_once QCI_PLUGIN_DIR . 'admin/views/settings-page.php';

        // Add admin footer
        require_once QCI_PLUGIN_DIR . 'admin/views/footer.php';
    }

    /**
     * Display the help/documentation page.
     *
     * @since    1.0.0
     */
    public function display_help_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }

        // Add admin header
        require_once QCI_PLUGIN_DIR . 'admin/views/header.php';

        // Include the help view
        require_once QCI_PLUGIN_DIR . 'admin/views/help-page.php';

        // Add admin footer
        require_once QCI_PLUGIN_DIR . 'admin/views/footer.php';
    }

    /**
     * Display the import history page.
     *
     * @since    1.0.0
     */
    public function display_history_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }

        // Add admin header
        require_once QCI_PLUGIN_DIR . 'admin/views/header.php';

        // Create a history table instance
        require_once QCI_PLUGIN_DIR . 'admin/class-import-history-table.php';
        $history_table = new QCI_Import_History_Table();
        $history_table->prepare_items();

        // Include the history view
        require_once QCI_PLUGIN_DIR . 'admin/views/history-page.php';

        // Add admin footer
        require_once QCI_PLUGIN_DIR . 'admin/views/footer.php';
    }

    /**
     * Process settings form submission.
     *
     * @since    1.0.0
     */
    private function process_settings_form() {
        // Check if form is submitted
        if (!isset($_POST['qci_settings_nonce'])) {
            return;
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['qci_settings_nonce'], 'qci_save_settings')) {
            add_settings_error(
                'qci_settings',
                'qci_settings_nonce_error',
                __('Security check failed. Please try again.', 'quizcourse-importer'),
                'error'
            );
            return;
        }

        // Get settings
        $default_course_status = isset($_POST['qci_default_course_status']) ? sanitize_text_field($_POST['qci_default_course_status']) : 'draft';
        $default_quiz_status = isset($_POST['qci_default_quiz_status']) ? sanitize_text_field($_POST['qci_default_quiz_status']) : 'draft';
        $enable_logging = isset($_POST['qci_enable_logging']) ? 1 : 0;
        $keep_history = isset($_POST['qci_keep_history']) ? intval($_POST['qci_keep_history']) : 30;
        $default_author_id = isset($_POST['qci_default_author_id']) ? intval($_POST['qci_default_author_id']) : get_current_user_id();

        // Validate settings
        if (!in_array($default_course_status, array('publish', 'draft', 'pending'))) {
            $default_course_status = 'draft';
        }

        if (!in_array($default_quiz_status, array('publish', 'draft', 'pending'))) {
            $default_quiz_status = 'draft';
        }

        if ($keep_history < 0) {
            $keep_history = 30;
        }

        // Save settings
        $settings = array(
            'default_course_status' => $default_course_status,
            'default_quiz_status' => $default_quiz_status,
            'enable_logging' => $enable_logging,
            'keep_history' => $keep_history,
            'default_author_id' => $default_author_id
        );

        update_option('qci_settings', $settings);

        // Add success message
        add_settings_error(
            'qci_settings',
            'qci_settings_success',
            __('Settings saved successfully.', 'quizcourse-importer'),
            'success'
        );
    }

    /**
     * Get plugin settings.
     *
     * @since    1.0.0
     * @return   array    Plugin settings.
     */
    public function get_settings() {
        $default_settings = array(
            'default_course_status' => 'draft',
            'default_quiz_status' => 'draft',
            'enable_logging' => 1,
            'keep_history' => 30,
            'default_author_id' => get_current_user_id()
        );

        $settings = get_option('qci_settings', $default_settings);

        return wp_parse_args($settings, $default_settings);
    }

    /**
     * Add a settings link to the Plugins page.
     *
     * @since    1.0.0
     * @param    array    $links    The existing links array.
     * @return   array    The modified links array.
     */
    public function add_action_links($links) {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url('admin.php?page=quizcourse-settings'),
            __('Settings', 'quizcourse-importer')
        );

        $import_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url('admin.php?page=quizcourse-importer'),
            __('Import', 'quizcourse-importer')
        );

        array_unshift($links, $settings_link, $import_link);

        return $links;
    }

    /**
     * Add help tabs to plugin pages.
     *
     * @since    1.0.0
     */
    public function add_help_tabs() {
        $screen = get_current_screen();

        // Only add help tabs on plugin pages
        if (strpos($screen->id, 'quizcourse-') === false) {
            return;
        }

        // Main help tab
        $screen->add_help_tab(array(
            'id'      => 'qci-help-overview',
            'title'   => __('Overview', 'quizcourse-importer'),
            'content' => '<p>' . __('The QuizCourse One-Click Importer allows you to import courses, quizzes, questions, and answers from CSV or Excel files.', 'quizcourse-importer') . '</p>'
        ));

        // File format help tab
        $screen->add_help_tab(array(
            'id'      => 'qci-help-file-format',
            'title'   => __('File Format', 'quizcourse-importer'),
            'content' => $this->get_file_format_help()
        ));

        // Import process help tab
        $screen->add_help_tab(array(
            'id'      => 'qci-help-process',
            'title'   => __('Import Process', 'quizcourse-importer'),
            'content' => $this->get_import_process_help()
        ));

        // Set help sidebar
        $screen->set_help_sidebar(
            '<p><strong>' . __('For more information:', 'quizcourse-importer') . '</strong></p>' .
            '<p><a href="' . admin_url('admin.php?page=quizcourse-help') . '">' . __('Documentation', 'quizcourse-importer') . '</a></p>' .
            '<p><a href="#" target="_blank">' . __('Support', 'quizcourse-importer') . '</a></p>'
        );
    }

    /**
     * Get help content for file format.
     *
     * @since    1.0.0
     * @return   string    Help content.
     */
    private function get_file_format_help() {
        $content = '<p>' . __('Your import file should be in CSV or Excel format (.csv, .xlsx, .xls).', 'quizcourse-importer') . '</p>';
        $content .= '<p>' . __('For Excel files, your file should contain the following sheets:', 'quizcourse-importer') . '</p>';
        $content .= '<ul>';
        $content .= '<li><strong>Courses</strong> - ' . __('Basic course information', 'quizcourse-importer') . '</li>';
        $content .= '<li><strong>Sections</strong> - ' . __('Course sections/modules', 'quizcourse-importer') . '</li>';
        $content .= '<li><strong>Quizzes</strong> - ' . __('Quizzes for each section', 'quizcourse-importer') . '</li>';
        $content .= '<li><strong>Questions</strong> - ' . __('Questions for each quiz', 'quizcourse-importer') . '</li>';
        $content .= '<li><strong>Answers</strong> - ' . __('Answers for each question', 'quizcourse-importer') . '</li>';
        $content .= '</ul>';
        $content .= '<p>' . __('You can download a template file from the Import page.', 'quizcourse-importer') . '</p>';

        return $content;
    }

    /**
     * Get help content for import process.
     *
     * @since    1.0.0
     * @return   string    Help content.
     */
    private function get_import_process_help() {
        $content = '<p>' . __('The import process follows these steps:', 'quizcourse-importer') . '</p>';
        $content .= '<ol>';
        $content .= '<li>' . __('Upload your CSV or Excel file', 'quizcourse-importer') . '</li>';
        $content .= '<li>' . __('Map the fields in your file to the system fields', 'quizcourse-importer') . '</li>';
        $content .= '<li>' . __('Review and start the import', 'quizcourse-importer') . '</li>';
        $content .= '<li>' . __('Wait for the import to complete', 'quizcourse-importer') . '</li>';
        $content .= '</ol>';
        $content .= '<p>' . __('During the import, the plugin will:', 'quizcourse-importer') . '</p>';
        $content .= '<ul>';
        $content .= '<li>' . __('Create courses, sections, quizzes, questions, and answers', 'quizcourse-importer') . '</li>';
        $content .= '<li>' . __('Establish relationships between these items', 'quizcourse-importer') . '</li>';
        $content .= '<li>' . __('Report on the import progress and results', 'quizcourse-importer') . '</li>';
        $content .= '</ul>';

        return $content;
    }

    /**
     * Register plugin meta boxes.
     *
     * @since    1.0.0
     */
    public function add_meta_boxes() {
        // Add meta box for course quizzes
        add_meta_box(
            'qci-course-quizzes',
            __('Course Quizzes', 'quizcourse-importer'),
            array($this, 'render_course_quizzes_meta_box'),
            'course', // Assumes 'course' is the post type
            'side',
            'default'
        );

        // Add meta box for quiz questions
        add_meta_box(
            'qci-quiz-questions',
            __('Quiz Questions', 'quizcourse-importer'),
            array($this, 'render_quiz_questions_meta_box'),
            'quiz', // Assumes 'quiz' is the post type
            'normal',
            'high'
        );
    }

    /**
     * Render Course Quizzes meta box.
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function render_course_quizzes_meta_box($post) {
        // Get quizzes for this course
        $quizzes = $this->get_course_quizzes($post->ID);

        // Meta box content
        if (!empty($quizzes)) {
            echo '<ul class="qci-quizzes-list">';
            foreach ($quizzes as $quiz) {
                printf(
                    '<li><a href="%s">%s</a></li>',
                    get_edit_post_link($quiz->ID),
                    esc_html($quiz->post_title)
                );
            }
            echo '</ul>';
        } else {
            echo '<p>' . __('No quizzes found for this course.', 'quizcourse-importer') . '</p>';
        }

        // Add button to create new quiz
        printf(
            '<p><a href="%s" class="button">%s</a></p>',
            admin_url('post-new.php?post_type=quiz&course_id=' . $post->ID),
            __('Add New Quiz', 'quizcourse-importer')
        );
    }

    /**
     * Render Quiz Questions meta box.
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function render_quiz_questions_meta_box($post) {
        // Get questions for this quiz
        $questions = $this->get_quiz_questions($post->ID);

        // Meta box content
        if (!empty($questions)) {
            echo '<table class="widefat qci-questions-table">';
            echo '<thead>';
            echo '<tr>';
            echo '<th>' . __('Question', 'quizcourse-importer') . '</th>';
            echo '<th>' . __('Type', 'quizcourse-importer') . '</th>';
            echo '<th>' . __('Answers', 'quizcourse-importer') . '</th>';
            echo '<th>' . __('Actions', 'quizcourse-importer') . '</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
            
            foreach ($questions as $question) {
                $answers_count = $this->get_question_answers_count($question->ID);
                $question_type = get_post_meta($question->ID, 'question_type', true);
                
                echo '<tr>';
                echo '<td>' . esc_html($question->post_title) . '</td>';
                echo '<td>' . esc_html($question_type) . '</td>';
                echo '<td>' . $answers_count . '</td>';
                echo '<td>';
                printf(
                    '<a href="%s" class="button button-small">%s</a> ',
                    get_edit_post_link($question->ID),
                    __('Edit', 'quizcourse-importer')
                );
                printf(
                    '<a href="%s" class="button button-small qci-delete-question" data-question-id="%d">%s</a>',
                    '#',
                    $question->ID,
                    __('Delete', 'quizcourse-importer')
                );
                echo '</td>';
                echo '</tr>';
            }
            
            echo '</tbody>';
            echo '</table>';
        } else {
            echo '<p>' . __('No questions found for this quiz.', 'quizcourse-importer') . '</p>';
        }

        // Add button to create new question
        printf(
            '<p><a href="%s" class="button">%s</a></p>',
            admin_url('post-new.php?post_type=question&quiz_id=' . $post->ID),
            __('Add New Question', 'quizcourse-importer')
        );

        // Add nonce for AJAX actions
        wp_nonce_field('qci_questions_nonce', 'qci_questions_nonce');
    }

    /**
     * Get quizzes for a course.
     *
     * @since    1.0.0
     * @param    int       $course_id    Course ID.
     * @return   array     Array of quiz post objects.
     */
    private function get_course_quizzes($course_id) {
        // This function would retrieve quizzes associated with a course
        // The implementation depends on your data structure
        
        // Example implementation:
        // 1. Get quiz IDs from course meta
        $quiz_ids = get_post_meta($course_id, 'quiz_ids', true);
        if (empty($quiz_ids)) {
            return array();
        }
        
        // 2. Get quiz posts
        $quiz_ids = explode(',', $quiz_ids);
        
        return get_posts(array(
            'post_type' => 'quiz',
            'post__in' => $quiz_ids,
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC'
        ));
    }

    /**
     * Get questions for a quiz.
     *
     * @since    1.0.0
     * @param    int       $quiz_id    Quiz ID.
     * @return   array     Array of question post objects.
     */
    private function get_quiz_questions($quiz_id) {
        // This function would retrieve questions associated with a quiz
        // The implementation depends on your data structure
        
        // Example implementation:
        // 1. Get question IDs from quiz meta
        $question_ids = get_post_meta($quiz_id, 'question_ids', true);
        if (empty($question_ids)) {
            return array();
        }
        
        // 2. Get question posts
        $question_ids = explode(',', $question_ids);
        
        return get_posts(array(
            'post_type' => 'question',
            'post__in' => $question_ids,
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC'
        ));
    }

    /**
     * Get the number of answers for a question.
     *
     * @since    1.0.0
     * @param    int       $question_id    Question ID.
     * @return   int       Number of answers.
     */
    private function get_question_answers_count($question_id) {
        // This function would count answers for a question
        // The implementation depends on your data structure
        
        // Example implementation:
        // 1. If answers are stored in a custom table
        global $wpdb;
        $answers_table = $wpdb->prefix . 'aysquiz_answers'; // Adjust to your table name
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $answers_table WHERE question_id = %d",
            $question_id
        ));
        
        // 2. Alternative: If answers are stored as post meta
        // return count(get_post_meta($question_id, 'answers', true));
    }

    /**
     * Register dashboard widgets.
     *
     * @since    1.0.0
     */
    public function add_dashboard_widgets() {
        wp_add_dashboard_widget(
            'qci_dashboard_widget',
            __('QuizCourse Stats', 'quizcourse-importer'),
            array($this, 'render_dashboard_widget')
        );
    }

    /**
     * Render dashboard widget.
     *
     * @since    1.0.0
     */
    public function render_dashboard_widget() {
        // Get stats
        $courses_count = wp_count_posts('course')->publish;
        $quizzes_count = wp_count_posts('quiz')->publish;
        $questions_count = wp_count_posts('question')->publish;
        
        // Get recent imports
        global $wpdb;
        $history_table = $wpdb->prefix . 'qci_import_history';
        
        $recent_imports = array();
        if ($wpdb->get_var("SHOW TABLES LIKE '$history_table'") === $history_table) {
            $recent_imports = $wpdb->get_results(
                "SELECT * FROM $history_table ORDER BY import_date DESC LIMIT 5"
            );
        }
        
        // Render widget
        echo '<div class="qci-dashboard-stats">';
        echo '<div class="qci-stat-item">';
        echo '<h3>' . $courses_count . '</h3>';
        echo '<p>' . __('Courses', 'quizcourse-importer') . '</p>';
        echo '</div>';
        echo '<div class="qci-stat-item">';
        echo '<h3>' . $quizzes_count . '</h3>';
        echo '<p>' . __('Quizzes', 'quizcourse-importer') . '</p>';
        echo '</div>';
        echo '<div class="qci-stat-item">';
        echo '<h3>' . $questions_count . '</h3>';
        echo '<p>' . __('Questions', 'quizcourse-importer') . '</p>';
        echo '</div>';
        echo '</div>';
        
        echo '<h4>' . __('Recent Imports', 'quizcourse-importer') . '</h4>';
        
        if (!empty($recent_imports)) {
            echo '<ul class="qci-recent-imports">';
            foreach ($recent_imports as $import) {
                $status_class = ($import->status === 'success') ? 'success' : 'error';
                
                echo '<li class="qci-import-' . $status_class . '">';
                echo '<span class="qci-import-date">' . date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($import->import_date)) . '</span>';
                echo '<span class="qci-import-file">' . esc_html($import->file_name) . '</span>';
                echo '<span class="qci-import-status">' . esc_html($import->status) . '</span>';
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>' . __('No recent imports found.', 'quizcourse-importer') . '</p>';
        }
        
        echo '<p class="qci-dashboard-links">';
        echo '<a href="' . admin_url('admin.php?page=quizcourse-importer') . '" class="button button-primary">' . __('Import Data', 'quizcourse-importer') . '</a> ';
        echo '<a href="' . admin_url('admin.php?page=quizcourse-history') . '" class="button">' . __('View History', 'quizcourse-importer') . '</a>';
        echo '</p>';
    }

    /**
     * Add custom columns to course list table.
     *
     * @since    1.0.0
     * @param    array    $columns    List of columns.
     * @return   array    Modified list of columns.
     */
    public function add_course_columns($columns) {
        $new_columns = array();
        
        // Insert columns after title
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            if ($key === 'title') {
                $new_columns['quizzes'] = __('Quizzes', 'quizcourse-importer');
                $new_columns['sections'] = __('Sections', 'quizcourse-importer');
               $new_columns['students'] = __('Students', 'quizcourse-importer');
           }
       }
       
       return $new_columns;
   }

   /**
    * Display custom column content.
    *
    * @since    1.0.0
    * @param    string    $column     Column name.
    * @param    int       $post_id    Post ID.
    */
   public function display_course_custom_column($column, $post_id) {
       switch ($column) {
           case 'quizzes':
               $quiz_count = count($this->get_course_quizzes($post_id));
               echo $quiz_count;
               break;
               
           case 'sections':
               $sections = get_post_meta($post_id, 'section_ids', true);
               $section_count = !empty($sections) ? count(explode(',', $sections)) : 0;
               echo $section_count;
               break;
               
           case 'students':
               // This would need integration with your user/student system
               $student_count = $this->get_course_student_count($post_id);
               echo $student_count;
               break;
       }
   }

   /**
    * Get student count for a course.
    *
    * @since    1.0.0
    * @param    int       $course_id    Course ID.
    * @return   int       Number of students.
    */
   private function get_course_student_count($course_id) {
       // This function would count students enrolled in a course
       // The implementation depends on your user/enrollment system
       
       // Example implementation:
       global $wpdb;
       
       // If using user meta to track course enrollment
       $enrolled_users = $wpdb->get_var($wpdb->prepare(
           "SELECT COUNT(DISTINCT user_id) FROM $wpdb->usermeta WHERE meta_key = %s",
           'enrolled_course_' . $course_id
       ));
       
       return $enrolled_users ? $enrolled_users : 0;
   }

   /**
    * Add custom bulk actions.
    *
    * @since    1.0.0
    * @param    array    $actions    Bulk actions.
    * @return   array    Modified bulk actions.
    */
   public function add_custom_bulk_actions($actions) {
       $actions['export_selected'] = __('Export Selected', 'quizcourse-importer');
       return $actions;
   }

   /**
    * Handle custom bulk actions.
    *
    * @since    1.0.0
    * @param    string    $redirect_to    URL to redirect to.
    * @param    string    $action         Bulk action name.
    * @param    array     $post_ids       Array of post IDs.
    * @return   string    Modified redirect URL.
    */
   public function handle_custom_bulk_actions($redirect_to, $action, $post_ids) {
       if ($action !== 'export_selected') {
           return $redirect_to;
       }
       
       // Process the export action
       $exported = $this->export_items($post_ids);
       
       if ($exported) {
           // Add success message parameter to URL
           $redirect_to = add_query_arg(
               'qci_exported',
               count($post_ids),
               $redirect_to
           );
       } else {
           // Add error message parameter to URL
           $redirect_to = add_query_arg(
               'qci_export_error',
               '1',
               $redirect_to
           );
       }
       
       return $redirect_to;
   }

   /**
    * Export selected items.
    *
    * @since    1.0.0
    * @param    array     $post_ids    Array of post IDs.
    * @return   bool      Whether export was successful.
    */
   private function export_items($post_ids) {
       // This function would handle exporting selected items
       // Implementation depends on your export requirements
       
       // Example implementation:
       // 1. Create a new exporter instance
       require_once QCI_PLUGIN_DIR . 'includes/class-data-exporter.php';
       $exporter = new QCI_Data_Exporter();
       
       // 2. Export the data
       return $exporter->export_items($post_ids);
   }

   /**
    * Display admin notices.
    *
    * @since    1.0.0
    */
   public function display_admin_notices() {
       // Display export success message
       if (isset($_REQUEST['qci_exported']) && intval($_REQUEST['qci_exported']) > 0) {
           $count = intval($_REQUEST['qci_exported']);
           $message = sprintf(
               _n(
                   '%d item exported successfully.',
                   '%d items exported successfully.',
                   $count,
                   'quizcourse-importer'
               ),
               $count
           );
           
           echo '<div class="notice notice-success is-dismissible"><p>' . $message . '</p></div>';
       }
       
       // Display export error message
       if (isset($_REQUEST['qci_export_error'])) {
           $message = __('An error occurred during export. Please try again.', 'quizcourse-importer');
           echo '<div class="notice notice-error is-dismissible"><p>' . $message . '</p></div>';
       }
   }

   /**
    * Add plugin row meta.
    *
    * @since    1.0.0
    * @param    array     $plugin_meta    Plugin meta.
    * @param    string    $plugin_file    Plugin file.
    * @return   array     Modified plugin meta.
    */
   public function plugin_row_meta($plugin_meta, $plugin_file) {
       if (strpos($plugin_file, 'quizcourse-importer.php') !== false) {
           $plugin_meta[] = sprintf(
               '<a href="%s">%s</a>',
               admin_url('admin.php?page=quizcourse-help'),
               __('Documentation', 'quizcourse-importer')
           );
           
           $plugin_meta[] = sprintf(
               '<a href="%s" target="_blank">%s</a>',
               '#', // Add your support URL here
               __('Support', 'quizcourse-importer')
           );
       }
       
       return $plugin_meta;
   }

   /**
    * Check if required plugins are active.
    *
    * @since    1.0.0
    * @return   bool    Whether required plugins are active.
    */
   public function check_required_plugins() {
       // This function would check if any required plugins are active
       // For example, if your importer works with a specific quiz plugin
       
       // Example implementation:
       $required_plugins = array(
           'quiz-plugin/quiz-plugin.php' => __('Quiz Plugin', 'quizcourse-importer'),
           'course-plugin/course-plugin.php' => __('Course Plugin', 'quizcourse-importer')
       );
       
       $missing_plugins = array();
       
       foreach ($required_plugins as $plugin_path => $plugin_name) {
           if (!is_plugin_active($plugin_path)) {
               $missing_plugins[] = $plugin_name;
           }
       }
       
       if (!empty($missing_plugins)) {
           // Display admin notice
           add_action('admin_notices', function() use ($missing_plugins) {
               $message = sprintf(
                   __('QuizCourse Importer requires the following plugins: %s', 'quizcourse-importer'),
                   '<strong>' . implode(', ', $missing_plugins) . '</strong>'
               );
               
               echo '<div class="notice notice-error"><p>' . $message . '</p></div>';
           });
           
           return false;
       }
       
       return true;
   }

   /**
    * Create necessary database tables.
    *
    * @since    1.0.0
    */
   public static function create_tables() {
       global $wpdb;
       
       $charset_collate = $wpdb->get_charset_collate();
       
       // Import history table
       $history_table = $wpdb->prefix . 'qci_import_history';
       
       $sql = "CREATE TABLE $history_table (
           id bigint(20) NOT NULL AUTO_INCREMENT,
           user_id bigint(20) NOT NULL,
           file_name varchar(255) NOT NULL,
           file_size bigint(20) NOT NULL,
           import_date datetime NOT NULL,
           status varchar(50) NOT NULL,
           items_imported int(11) NOT NULL,
           errors text,
           PRIMARY KEY  (id)
       ) $charset_collate;";
       
       require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
       dbDelta($sql);
   }

   /**
    * Log import to history.
    *
    * @since    1.0.0
    * @param    array     $import_data    Import data.
    * @return   int|bool  Record ID or false on failure.
    */
   public function log_import($import_data) {
       global $wpdb;
       
       $history_table = $wpdb->prefix . 'qci_import_history';
       
       // Create tables if they don't exist
       if ($wpdb->get_var("SHOW TABLES LIKE '$history_table'") !== $history_table) {
           self::create_tables();
       }
       
       // Default data
       $default_data = array(
           'user_id' => get_current_user_id(),
           'file_name' => '',
           'file_size' => 0,
           'import_date' => current_time('mysql'),
           'status' => 'success',
           'items_imported' => 0,
           'errors' => ''
       );
       
       // Merge with provided data
       $import_data = wp_parse_args($import_data, $default_data);
       
       // Insert record
       $result = $wpdb->insert(
           $history_table,
           $import_data,
           array(
               '%d', // user_id
               '%s', // file_name
               '%d', // file_size
               '%s', // import_date
               '%s', // status
               '%d', // items_imported
               '%s'  // errors
           )
       );
       
       if ($result) {
           return $wpdb->insert_id;
       }
       
       return false;
   }

   /**
    * Clean up old import history.
    *
    * @since    1.0.0
    */
   public function cleanup_import_history() {
       global $wpdb;
       
       // Get settings
       $settings = $this->get_settings();
       $keep_days = intval($settings['keep_history']);
       
       // If set to 0, keep all history
       if ($keep_days <= 0) {
           return;
       }
       
       $history_table = $wpdb->prefix . 'qci_import_history';
       
       // Delete records older than the specified number of days
       $date_limit = date('Y-m-d H:i:s', strtotime("-$keep_days days"));
       
       $wpdb->query($wpdb->prepare(
           "DELETE FROM $history_table WHERE import_date < %s",
           $date_limit
       ));
   }

   /**
    * Schedule cleanup cron job.
    *
    * @since    1.0.0
    */
   public static function schedule_cleanup() {
       if (!wp_next_scheduled('qci_cleanup_history')) {
           wp_schedule_event(time(), 'daily', 'qci_cleanup_history');
       }
   }

   /**
    * Unschedule cleanup cron job.
    *
    * @since    1.0.0
    */
   public static function unschedule_cleanup() {
       $timestamp = wp_next_scheduled('qci_cleanup_history');
       if ($timestamp) {
           wp_unschedule_event($timestamp, 'qci_cleanup_history');
       }
   }
}

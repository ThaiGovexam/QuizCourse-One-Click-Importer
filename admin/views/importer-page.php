<?php
/**
 * Main importer page template
 *
 * @package QuizCourse_Importer
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap qci-importer-wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-upload"></span>
        <?php echo esc_html(get_admin_page_title()); ?>
    </h1>
    <hr class="wp-header-end">

    <div class="qci-header-buttons">
        <a href="<?php echo esc_url(admin_url('admin.php?page=quizcourse-importer-settings')); ?>" class="page-title-action">
            <span class="dashicons dashicons-admin-settings"></span>
            <?php _e('Settings', 'quizcourse-importer'); ?>
        </a>
        <a href="<?php echo esc_url(QCI_PLUGIN_URL . 'templates/sample-import-template-single-sheet.xlsx'); ?>" class="page-title-action">
            <span class="dashicons dashicons-download"></span>
            <?php _e('Download Template', 'quizcourse-importer'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=quizcourse-importer-help')); ?>" class="page-title-action">
            <span class="dashicons dashicons-editor-help"></span>
            <?php _e('Help Guide', 'quizcourse-importer'); ?>
        </a>
    </div>

    <?php
    // Display admin notices
    if (isset($_GET['qci_error']) && !empty($_GET['qci_error'])) {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html(urldecode($_GET['qci_error'])) . '</p></div>';
    }
    
    if (isset($_GET['qci_success']) && !empty($_GET['qci_success'])) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html(urldecode($_GET['qci_success'])) . '</p></div>';
    }
    ?>

    <div class="qci-container">
        <div class="qci-steps">
            <ul class="qci-steps-list">
                <li class="active" data-step="1">
                    <span class="qci-step-number">1</span>
                    <span class="qci-step-label"><?php _e('Upload File', 'quizcourse-importer'); ?></span>
                </li>
                <li data-step="2">
                    <span class="qci-step-number">2</span>
                    <span class="qci-step-label"><?php _e('Map Fields', 'quizcourse-importer'); ?></span>
                </li>
                <li data-step="3">
                    <span class="qci-step-number">3</span>
                    <span class="qci-step-label"><?php _e('Import', 'quizcourse-importer'); ?></span>
                </li>
                <li data-step="4">
                    <span class="qci-step-number">4</span>
                    <span class="qci-step-label"><?php _e('Complete', 'quizcourse-importer'); ?></span>
                </li>
            </ul>
        </div>
        
        <div class="qci-content">
            <!-- Step 1: Upload File -->
            <div class="qci-step-content" id="qci-step-1">
                <div class="qci-panel">
                    <div class="qci-panel-header">
                        <h2><?php _e('Upload Your Data File', 'quizcourse-importer'); ?></h2>
                    </div>
                    <div class="qci-panel-body">
                        <p class="qci-intro-text">
                            <?php _e('Upload your Excel or CSV file containing courses, quizzes, questions, and answers data in a single sheet. The one-click importer will process your file and create all entities in the system at once.', 'quizcourse-importer'); ?>
                        </p>
                        
                        <form id="qci-upload-form" method="post" enctype="multipart/form-data">
                            <div class="qci-file-upload">
                                <input type="file" name="qci_import_file" id="qci_import_file" accept=".csv,.xlsx,.xls" />
                                <label for="qci_import_file">
                                    <span class="dashicons dashicons-upload"></span>
                                    <span class="qci-upload-text"><?php _e('Choose a file or drag it here', 'quizcourse-importer'); ?></span>
                                    <span class="qci-upload-hint"><?php _e('Supported formats: .xlsx, .xls, .csv (Single sheet format)', 'quizcourse-importer'); ?></span>
                                </label>
                            </div>
                            
                            <div class="qci-selected-file" style="display: none;">
                                <div class="qci-file-info">
                                    <span class="dashicons dashicons-media-spreadsheet"></span>
                                    <strong><?php _e('Selected file:', 'quizcourse-importer'); ?></strong>
                                    <span id="qci-file-name"></span>
                                    <span id="qci-file-size"></span>
                                </div>
                                <button type="button" id="qci-remove-file" class="button">
                                    <span class="dashicons dashicons-no"></span>
                                    <?php _e('Remove', 'quizcourse-importer'); ?>
                                </button>
                            </div>
                            
                            <div class="qci-actions">
                                <button type="submit" id="qci-validate-file" class="button button-primary button-hero" disabled>
                                    <span class="dashicons dashicons-yes"></span>
                                    <?php _e('Continue to Field Mapping', 'quizcourse-importer'); ?>
                                </button>
                                <div class="qci-loading" style="display: none;">
                                    <span class="spinner is-active"></span>
                                    <?php _e('Validating file...', 'quizcourse-importer'); ?>
                                </div>
                            </div>
                            
                            <?php wp_nonce_field('qci-security', 'qci_security'); ?>
                        </form>
                    </div>
                </div>
                
                <div class="qci-panel qci-template-panel">
                    <div class="qci-panel-header">
                        <h2><?php _e('Single Sheet Format Guide', 'quizcourse-importer'); ?></h2>
                    </div>
                    <div class="qci-panel-body">
                        <div class="qci-template-guide">
                            <div class="qci-template-column">
                                <h3><?php _e('Download Our Template', 'quizcourse-importer'); ?></h3>
                                <p><?php _e('Download our single-sheet template file that includes all the necessary columns with sample data to get started quickly.', 'quizcourse-importer'); ?></p>
                                <a href="<?php echo esc_url(QCI_PLUGIN_URL . 'templates/sample-import-template-single-sheet.xlsx'); ?>" class="button button-secondary">
                                    <span class="dashicons dashicons-download"></span>
                                    <?php _e('Download Excel Template', 'quizcourse-importer'); ?>
                                </a>
                                <a href="<?php echo esc_url(QCI_PLUGIN_URL . 'templates/sample-import-template-single-sheet.csv'); ?>" class="button button-secondary" style="margin-left: 10px;">
                                    <span class="dashicons dashicons-download"></span>
                                    <?php _e('Download CSV Template', 'quizcourse-importer'); ?>
                                </a>
                            </div>
                            
                            <div class="qci-template-column">
                                <h3><?php _e('Required Columns', 'quizcourse-importer'); ?></h3>
                                <p><?php _e('Your file must include these essential columns:', 'quizcourse-importer'); ?></p>
                                <ul class="qci-column-list">
                                    <li><strong>record_type</strong> - <?php _e('Type of record: "course", "quiz", "question", or "answer"', 'quizcourse-importer'); ?></li>
                                    <li><strong>id</strong> - <?php _e('Unique identifier for reference', 'quizcourse-importer'); ?></li>
                                    <li><strong>title</strong> - <?php _e('Title/name of the item', 'quizcourse-importer'); ?></li>
                                    <li><strong>parent_id</strong> - <?php _e('Reference to parent item (course for quizzes, quiz for questions, etc.)', 'quizcourse-importer'); ?></li>
                                </ul>
                            </div>
                            
                            <div class="qci-template-column">
                                <h3><?php _e('Data Structure', 'quizcourse-importer'); ?></h3>
                                <p><?php _e('All your data should be organized in a single sheet with the following hierarchy:', 'quizcourse-importer'); ?></p>
                                <ol class="qci-structure-list">
                                    <li><strong><?php _e('Courses', 'quizcourse-importer'); ?></strong> (record_type = "course")</li>
                                    <li><strong><?php _e('Quizzes', 'quizcourse-importer'); ?></strong> (record_type = "quiz", parent_id = course id)</li>
                                    <li><strong><?php _e('Questions', 'quizcourse-importer'); ?></strong> (record_type = "question", parent_id = quiz id)</li>
                                    <li><strong><?php _e('Answers', 'quizcourse-importer'); ?></strong> (record_type = "answer", parent_id = question id)</li>
                                </ol>
                            </div>
                        </div>
                        
                        <div class="qci-example-header">
                            <h3><?php _e('Example Data Format', 'quizcourse-importer'); ?></h3>
                            <p><?php _e('Here\'s how your data should be structured in a single sheet:', 'quizcourse-importer'); ?></p>
                        </div>
                        
                        <div class="qci-example-table-container">
                            <table class="qci-example-table widefat">
                                <thead>
                                    <tr>
                                        <th><?php _e('record_type', 'quizcourse-importer'); ?></th>
                                        <th><?php _e('id', 'quizcourse-importer'); ?></th>
                                        <th><?php _e('title', 'quizcourse-importer'); ?></th>
                                        <th><?php _e('description', 'quizcourse-importer'); ?></th>
                                        <th><?php _e('parent_id', 'quizcourse-importer'); ?></th>
                                        <th><?php _e('question_type', 'quizcourse-importer'); ?></th>
                                        <th><?php _e('is_correct', 'quizcourse-importer'); ?></th>
                                        <th><?php _e('image_url', 'quizcourse-importer'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>course</td>
                                        <td>C001</td>
                                        <td>WordPress Basics</td>
                                        <td>Learn the basics of WordPress</td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td>https://example.com/wp.jpg</td>
                                    </tr>
                                    <tr>
                                        <td>quiz</td>
                                        <td>Q001</td>
                                        <td>WordPress Intro Quiz</td>
                                        <td>Test your WordPress knowledge</td>
                                        <td>C001</td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td>question</td>
                                        <td>QU001</td>
                                        <td>What is WordPress?</td>
                                        <td></td>
                                        <td>Q001</td>
                                        <td>multiple_choice</td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td>answer</td>
                                        <td>A001</td>
                                        <td>A content management system</td>
                                        <td></td>
                                        <td>QU001</td>
                                        <td></td>
                                        <td>1</td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td>answer</td>
                                        <td>A002</td>
                                        <td>A web browser</td>
                                        <td></td>
                                        <td>QU001</td>
                                        <td></td>
                                        <td>0</td>
                                        <td></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="qci-panel qci-format-help">
                    <div class="qci-panel-header">
                        <h2><?php _e('Important Tips', 'quizcourse-importer'); ?></h2>
                    </div>
                    <div class="qci-panel-body">
                        <div class="qci-tips-grid">
                            <div class="qci-tip">
                                <h4><span class="dashicons dashicons-info-outline"></span> <?php _e('Record Type', 'quizcourse-importer'); ?></h4>
                                <p><?php _e('The "record_type" column must contain one of these values: "course", "quiz", "question", or "answer". This tells the system what kind of item each row represents.', 'quizcourse-importer'); ?></p>
                            </div>
                            
                            <div class="qci-tip">
                                <h4><span class="dashicons dashicons-format-aside"></span> <?php _e('Parent ID', 'quizcourse-importer'); ?></h4>
                                <p><?php _e('The "parent_id" column creates relationships between items. For example, a quiz\'s parent_id should match a course\'s id, a question\'s parent_id should match a quiz\'s id, etc.', 'quizcourse-importer'); ?></p>
                            </div>
                            
                            <div class="qci-tip">
                                <h4><span class="dashicons dashicons-editor-help"></span> <?php _e('Question Types', 'quizcourse-importer'); ?></h4>
                                <p><?php _e('Valid question types include: multiple_choice, true_false, short_answer, essay, fill_in_blank, matching. For question rows, make sure to specify the type.', 'quizcourse-importer'); ?></p>
                            </div>
                            
                            <div class="qci-tip">
                                <h4><span class="dashicons dashicons-forms"></span> <?php _e('Correct Answers', 'quizcourse-importer'); ?></h4>
                                <p><?php _e('For answer rows, the "is_correct" column should contain 1 for correct answers and 0 for incorrect answers. Each question should have at least one correct answer.', 'quizcourse-importer'); ?></p>
                            </div>
                        </div>
                        
                        <div class="qci-additional-fields">
                            <h4><?php _e('Additional Supported Fields', 'quizcourse-importer'); ?></h4>
                            <div class="qci-fields-columns">
                                <div class="qci-fields-column">
                                    <h5><?php _e('For Courses:', 'quizcourse-importer'); ?></h5>
                                    <ul>
                                        <li><strong>status</strong> - <?php _e('publish, draft, or pending', 'quizcourse-importer'); ?></li>
                                        <li><strong>category_ids</strong> - <?php _e('comma-separated category IDs', 'quizcourse-importer'); ?></li>
                                        <li><strong>ordering</strong> - <?php _e('display order (numeric)', 'quizcourse-importer'); ?></li>
                                        <li><strong>author_id</strong> - <?php _e('WordPress user ID for course author', 'quizcourse-importer'); ?></li>
                                    </ul>
                                </div>
                                <div class="qci-fields-column">
                                    <h5><?php _e('For Quizzes:', 'quizcourse-importer'); ?></h5>
                                    <ul>
                                        <li><strong>quiz_category_id</strong> - <?php _e('category ID for the quiz', 'quizcourse-importer'); ?></li>
                                        <li><strong>published</strong> - <?php _e('1 for published, 0 for unpublished', 'quizcourse-importer'); ?></li>
                                        <li><strong>ordering</strong> - <?php _e('display order (numeric)', 'quizcourse-importer'); ?></li>
                                        <li><strong>options</strong> - <?php _e('JSON-encoded options for quiz settings', 'quizcourse-importer'); ?></li>
                                    </ul>
                                </div>
                                <div class="qci-fields-column">
                                    <h5><?php _e('For Questions:', 'quizcourse-importer'); ?></h5>
                                    <ul>
                                        <li><strong>hint</strong> - <?php _e('hint text for the question', 'quizcourse-importer'); ?></li>
                                        <li><strong>explanation</strong> - <?php _e('explanation shown after answering', 'quizcourse-importer'); ?></li>
                                        <li><strong>category_id</strong> - <?php _e('question category ID', 'quizcourse-importer'); ?></li>
                                        <li><strong>weight</strong> - <?php _e('question points value (numeric)', 'quizcourse-importer'); ?></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <p class="qci-help-link">
                            <?php _e('For detailed instructions and troubleshooting, check our', 'quizcourse-importer'); ?> 
                            <a href="<?php echo esc_url(admin_url('admin.php?page=quizcourse-importer-help')); ?>">
                                <?php _e('complete documentation', 'quizcourse-importer'); ?>
                            </a>.
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Step 2: Map Fields (Will be populated dynamically) -->
            <div class="qci-step-content" id="qci-step-2" style="display: none;">
                <!-- Content will be dynamically added by the JavaScript -->
            </div>
            
            <!-- Step 3: Import Progress (Will be populated dynamically) -->
            <div class="qci-step-content" id="qci-step-3" style="display: none;">
                <!-- Content will be dynamically added by the JavaScript -->
            </div>
            
            <!-- Step 4: Complete (Will be populated dynamically) -->
            <div class="qci-step-content" id="qci-step-4" style="display: none;">
                <!-- Content will be dynamically added by the JavaScript -->
            </div>
        </div>
    </div>
    
    <div id="qci-error-modal" class="qci-modal" style="display: none;">
        <div class="qci-modal-content">
            <div class="qci-modal-header">
                <h2><?php _e('Import Error', 'quizcourse-importer'); ?></h2>
                <button type="button" class="qci-modal-close">&times;</button>
            </div>
            <div class="qci-modal-body">
                <div class="qci-error-message"></div>
            </div>
            <div class="qci-modal-footer">
                <button type="button" class="button qci-modal-close"><?php _e('Close', 'quizcourse-importer'); ?></button>
            </div>
        </div>
    </div>
    
    <div id="qci-confirm-modal" class="qci-modal" style="display: none;">
        <div class="qci-modal-content">
            <div class="qci-modal-header">
                <h2><?php _e('Confirm Import', 'quizcourse-importer'); ?></h2>
                <button type="button" class="qci-modal-close">&times;</button>
            </div>
            <div class="qci-modal-body">
                <p><?php _e('You are about to import your data. This will create new courses, quizzes, questions, and answers in your system.', 'quizcourse-importer'); ?></p>
                
                <div class="qci-import-summary">
                    <h3><?php _e('Import Summary:', 'quizcourse-importer'); ?></h3>
                    <ul id="qci-import-summary-list"></ul>
                </div>
                
                <div class="qci-warning">
                    <p><strong><?php _e('This action cannot be undone automatically.', 'quizcourse-importer'); ?></strong></p>
                    <p><?php _e('Please ensure that you have a backup of your database before proceeding if you are importing to a production system.', 'quizcourse-importer'); ?></p>
                </div>
            </div>
            <div class="qci-modal-footer">
                <button type="button" class="button qci-modal-close"><?php _e('Cancel', 'quizcourse-importer'); ?></button>
                <button type="button" class="button button-primary" id="qci-confirm-import"><?php _e('Start Import', 'quizcourse-importer'); ?></button>
            </div>
        </div>
    </div>
    
    <div id="qci-log-modal" class="qci-modal qci-log-modal" style="display: none;">
        <div class="qci-modal-content">
            <div class="qci-modal-header">
                <h2><?php _e('Import Log', 'quizcourse-importer'); ?></h2>
                <button type="button" class="qci-modal-close">&times;</button>
            </div>
            <div class="qci-modal-body">
                <div class="qci-log-container">
                    <pre id="qci-log-content"></pre>
                </div>
            </div>
            <div class="qci-modal-footer">
                <button type="button" class="button qci-modal-close"><?php _e('Close', 'quizcourse-importer'); ?></button>
                <button type="button" class="button button-secondary" id="qci-copy-log"><?php _e('Copy Log', 'quizcourse-importer'); ?></button>
                <a href="#" class="button button-secondary" id="qci-download-log" download="import-log.txt"><?php _e('Download Log', 'quizcourse-importer'); ?></a>
            </div>
        </div>
    </div>
</div>

<!-- Templates for dynamic content -->
<script type="text/template" id="qci-mapping-panel-template">
    <div class="qci-panel">
        <div class="qci-panel-header">
            <h2><?php _e('Map Your Fields', 'quizcourse-importer'); ?></h2>
        </div>
        <div class="qci-panel-body">
            <p class="qci-intro-text">
                <?php _e('Match the columns in your file to the corresponding fields in the system. The importer will try to match them automatically, but you can adjust as needed.', 'quizcourse-importer'); ?>
            </p>
            
            <div class="qci-mapping-tools">
                <button type="button" id="qci-auto-map" class="button">
                    <span class="dashicons dashicons-update"></span>
                    <?php _e('Auto Map Fields', 'quizcourse-importer'); ?>
                </button>
                
                <button type="button" id="qci-reset-mapping" class="button">
                    <span class="dashicons dashicons-dismiss"></span>
                    <?php _e('Reset Mapping', 'quizcourse-importer'); ?>
                </button>
                
                <div class="qci-mapping-templates">
                    <select id="qci-load-mapping-select">
                        <option value=""><?php _e('-- Select a saved template --', 'quizcourse-importer'); ?></option>
                        <!-- Saved templates will be loaded here -->
                    </select>
                    <button type="button" id="qci-load-mapping-btn" class="button">
                        <span class="dashicons dashicons-download"></span>
                        <?php _e('Load', 'quizcourse-importer'); ?>
                    </button>
                    <button type="button" id="qci-save-mapping" class="button">
                        <span class="dashicons dashicons-save"></span>
                        <?php _e('Save Current Mapping', 'quizcourse-importer'); ?>
                    </button>
                    <button type="button" id="qci-delete-mapping-btn" class="button">
                        <span class="dashicons dashicons-trash"></span>
                        <?php _e('Delete', 'quizcourse-importer'); ?>
                    </button>
                </div>
            </div>
            
            <div class="qci-mapping-container">
                <!-- This will be filled dynamically -->
            </div>
            
            <div class="qci-data-preview">
                <h3><?php _e('Data Preview', 'quizcourse-importer'); ?></h3>
                <p><?php _e('Below is a preview of your data. Make sure the field mapping matches your data structure.', 'quizcourse-importer'); ?></p>
                
                <div class="qci-preview-container">
                    <!-- Preview table will be inserted here -->
                </div>
            </div>
            
            <div class="qci-record-type-guide">
                <h3><?php _e('Field Mapping Guide for Single Sheet Format', 'quizcourse-importer'); ?></h3>
                
                <div class="qci-record-types">
                    <div class="qci-record-type">
                        <h4><?php _e('Course Fields', 'quizcourse-importer'); ?></h4>
                        <p><?php _e('For rows where record_type = "course":', 'quizcourse-importer'); ?></p>
                        <ul>
                            <li><strong>id</strong> - <?php _e('Unique identifier (required)', 'quizcourse-importer'); ?></li>
                            <li><strong>title</strong> - <?php _e('Course title (required)', 'quizcourse-importer'); ?></li>
                            <li><strong>description</strong> - <?php _e('Course description', 'quizcourse-importer'); ?></li>
                            <li><strong>image_url</strong> - <?php _e('Course image', 'quizcourse-importer'); ?></li>
                            <li><strong>status</strong> - <?php _e('Course status (publish/draft)', 'quizcourse-importer'); ?></li>
                        </ul>
                    </div>
                    
                    <div class="qci-record-type">
                        <h4><?php _e('Quiz Fields', 'quizcourse-importer'); ?></h4>
                        <p><?php _e('For rows where record_type = "quiz":', 'quizcourse-importer'); ?></p>
                        <ul>
                            <li><strong>id</strong> - <?php _e('Unique identifier (required)', 'quizcourse-importer'); ?></li>
                            <li><strong>title</strong> - <?php _e('Quiz title (required)', 'quizcourse-importer'); ?></li>
                            <li><strong>description</strong> - <?php _e('Quiz description', 'quizcourse-importer'); ?></li>
                            <li><strong>parent_id</strong> - <?php _e('Reference to course ID (required)', 'quizcourse-importer'); ?></li>
                            <li><strong>quiz_category_id</strong> - <?php _e('Quiz category', 'quizcourse-importer'); ?></li>
                            <li><strong>image_url</strong> - <?php _e('Quiz image', 'quizcourse-importer'); ?></li>
                        </ul>
                    </div>
                    
                    <div class="qci-record-type">
                       <h4><?php _e('Question Fields', 'quizcourse-importer'); ?></h4>
                       <p><?php _e('For rows where record_type = "question":', 'quizcourse-importer'); ?></p>
                       <ul>
                           <li><strong>id</strong> - <?php _e('Unique identifier (required)', 'quizcourse-importer'); ?></li>
                           <li><strong>title</strong> - <?php _e('Question text (required)', 'quizcourse-importer'); ?></li>
                           <li><strong>parent_id</strong> - <?php _e('Reference to quiz ID (required)', 'quizcourse-importer'); ?></li>
                           <li><strong>question_type</strong> - <?php _e('Type of question (required)', 'quizcourse-importer'); ?></li>
                           <li><strong>image_url</strong> - <?php _e('Question image', 'quizcourse-importer'); ?></li>
                           <li><strong>hint</strong> - <?php _e('Question hint', 'quizcourse-importer'); ?></li>
                           <li><strong>explanation</strong> - <?php _e('Answer explanation', 'quizcourse-importer'); ?></li>
                           <li><strong>category_id</strong> - <?php _e('Question category', 'quizcourse-importer'); ?></li>
                           <li><strong>tag_id</strong> - <?php _e('Question tag', 'quizcourse-importer'); ?></li>
                           <li><strong>weight</strong> - <?php _e('Points value', 'quizcourse-importer'); ?></li>
                       </ul>
                   </div>
                   
                   <div class="qci-record-type">
                       <h4><?php _e('Answer Fields', 'quizcourse-importer'); ?></h4>
                       <p><?php _e('For rows where record_type = "answer":', 'quizcourse-importer'); ?></p>
                       <ul>
                           <li><strong>id</strong> - <?php _e('Unique identifier (optional)', 'quizcourse-importer'); ?></li>
                           <li><strong>title</strong> - <?php _e('Answer text (required)', 'quizcourse-importer'); ?></li>
                           <li><strong>parent_id</strong> - <?php _e('Reference to question ID (required)', 'quizcourse-importer'); ?></li>
                           <li><strong>is_correct</strong> - <?php _e('Whether the answer is correct: 1 for correct, 0 for incorrect (required)', 'quizcourse-importer'); ?></li>
                           <li><strong>image_url</strong> - <?php _e('Answer image', 'quizcourse-importer'); ?></li>
                           <li><strong>ordering</strong> - <?php _e('Display order', 'quizcourse-importer'); ?></li>
                           <li><strong>weight</strong> - <?php _e('Answer weight (for weighted scoring)', 'quizcourse-importer'); ?></li>
                       </ul>
                   </div>
               </div>
           </div>
           
           <div class="qci-validation-status">
               <!-- This area will be populated with validation results via JavaScript -->
           </div>
           
           <div class="qci-actions">
               <button type="button" class="button qci-back-button">
                   <span class="dashicons dashicons-arrow-left-alt"></span>
                   <?php _e('Back', 'quizcourse-importer'); ?>
               </button>
               <button type="button" id="qci-validate-mapping" class="button button-secondary">
                   <span class="dashicons dashicons-yes-alt"></span>
                   <?php _e('Validate Mapping', 'quizcourse-importer'); ?>
               </button>
               <button type="button" id="qci-start-import" class="button button-primary button-hero" disabled>
                   <span class="dashicons dashicons-database-import"></span>
                   <?php _e('Continue to Import', 'quizcourse-importer'); ?>
               </button>
           </div>
       </div>
   </div>
</script>

<script type="text/template" id="qci-progress-panel-template">
   <div class="qci-panel">
       <div class="qci-panel-header">
           <h2><?php _e('Importing Your Data', 'quizcourse-importer'); ?></h2>
       </div>
       <div class="qci-panel-body">
           <div class="qci-progress-container">
               <div class="qci-progress-status">
                   <span class="qci-current-task"><?php _e('Preparing import...', 'quizcourse-importer'); ?></span>
                   <span class="qci-progress-percentage">0%</span>
               </div>
               
               <div class="qci-progress-bar-container">
                   <div class="qci-progress-bar" style="width: 0%"></div>
               </div>
               
               <div class="qci-progress-detail">
                   <!-- This will be updated dynamically -->
                   <span class="qci-item-count">0 / 0</span>
                   <span class="qci-current-entity"><?php _e('items', 'quizcourse-importer'); ?></span>
               </div>
           </div>
           
           <div class="qci-import-stages">
               <ul class="qci-stages-list">
                   <li class="qci-stage" data-stage="courses">
                       <span class="qci-stage-icon dashicons dashicons-welcome-learn-more"></span>
                       <span class="qci-stage-name"><?php _e('Courses', 'quizcourse-importer'); ?></span>
                       <span class="qci-stage-status"><?php _e('Waiting...', 'quizcourse-importer'); ?></span>
                   </li>
                   <li class="qci-stage" data-stage="quizzes">
                       <span class="qci-stage-icon dashicons dashicons-clipboard"></span>
                       <span class="qci-stage-name"><?php _e('Quizzes', 'quizcourse-importer'); ?></span>
                       <span class="qci-stage-status"><?php _e('Waiting...', 'quizcourse-importer'); ?></span>
                   </li>
                   <li class="qci-stage" data-stage="questions">
                       <span class="qci-stage-icon dashicons dashicons-editor-help"></span>
                       <span class="qci-stage-name"><?php _e('Questions', 'quizcourse-importer'); ?></span>
                       <span class="qci-stage-status"><?php _e('Waiting...', 'quizcourse-importer'); ?></span>
                   </li>
                   <li class="qci-stage" data-stage="answers">
                       <span class="qci-stage-icon dashicons dashicons-editor-ul"></span>
                       <span class="qci-stage-name"><?php _e('Answers', 'quizcourse-importer'); ?></span>
                       <span class="qci-stage-status"><?php _e('Waiting...', 'quizcourse-importer'); ?></span>
                   </li>
                   <li class="qci-stage" data-stage="linking">
                       <span class="qci-stage-icon dashicons dashicons-networking"></span>
                       <span class="qci-stage-name"><?php _e('Linking Data', 'quizcourse-importer'); ?></span>
                       <span class="qci-stage-status"><?php _e('Waiting...', 'quizcourse-importer'); ?></span>
                   </li>
               </ul>
           </div>
           
           <div class="qci-import-log-preview">
               <h3><?php _e('Activity Log', 'quizcourse-importer'); ?></h3>
               <div class="qci-log-preview-container">
                   <ul class="qci-log-messages"></ul>
               </div>
               <button type="button" class="button qci-show-full-log">
                   <span class="dashicons dashicons-visibility"></span>
                   <?php _e('View Full Log', 'quizcourse-importer'); ?>
               </button>
           </div>
           
           <div class="qci-actions qci-import-actions">
               <button type="button" class="button qci-cancel-import">
                   <span class="dashicons dashicons-no"></span>
                   <?php _e('Cancel Import', 'quizcourse-importer'); ?>
               </button>
           </div>
       </div>
   </div>
</script>

<script type="text/template" id="qci-complete-panel-template">
   <div class="qci-panel">
       <div class="qci-panel-header">
           <h2><?php _e('Import Completed', 'quizcourse-importer'); ?></h2>
       </div>
       <div class="qci-panel-body">
           <div class="qci-complete-container">
               <div class="qci-complete-icon">
                   <span class="dashicons dashicons-yes-alt"></span>
               </div>
               
               <h3 class="qci-complete-message"><?php _e('Your data has been successfully imported!', 'quizcourse-importer'); ?></h3>
               
               <div class="qci-import-summary">
                   <h4><?php _e('Import Summary:', 'quizcourse-importer'); ?></h4>
                   <ul class="qci-import-results">
                       <!-- Will be filled dynamically -->
                   </ul>
               </div>
               
               <div class="qci-import-info">
                   <p><?php _e('All your data has been imported successfully and proper relationships have been established between courses, quizzes, questions, and answers.', 'quizcourse-importer'); ?></p>
                   
                   <div class="qci-what-next">
                       <h4><?php _e('What\'s Next?', 'quizcourse-importer'); ?></h4>
                       <ul>
                           <li><?php _e('View your imported courses and check that everything looks correct', 'quizcourse-importer'); ?></li>
                           <li><?php _e('Make any needed adjustments to your quiz settings', 'quizcourse-importer'); ?></li>
                           <li><?php _e('Import additional content or create a new import', 'quizcourse-importer'); ?></li>
                       </ul>
                   </div>
               </div>
               
               <div class="qci-complete-actions">
                   <a href="#" class="button button-secondary qci-view-log">
                       <span class="dashicons dashicons-visibility"></span>
                       <?php _e('View Log', 'quizcourse-importer'); ?>
                   </a>
                   
                   <a href="<?php echo esc_url(admin_url('edit.php?post_type=course')); ?>" class="button button-secondary qci-view-courses">
                       <span class="dashicons dashicons-welcome-learn-more"></span>
                       <?php _e('View Courses', 'quizcourse-importer'); ?>
                   </a>
                   
                   <a href="<?php echo esc_url(admin_url('admin.php?page=quizcourse-importer')); ?>" class="button button-primary qci-new-import">
                       <span class="dashicons dashicons-upload"></span>
                       <?php _e('New Import', 'quizcourse-importer'); ?>
                   </a>
               </div>
           </div>
       </div>
   </div>
</script>

<script type="text/template" id="qci-error-panel-template">
   <div class="qci-panel qci-error-panel">
       <div class="qci-panel-header">
           <h2><?php _e('Import Error', 'quizcourse-importer'); ?></h2>
       </div>
       <div class="qci-panel-body">
           <div class="qci-error-container">
               <div class="qci-error-icon">
                   <span class="dashicons dashicons-warning"></span>
               </div>
               
               <h3 class="qci-error-message"><?php _e('An error occurred during the import process.', 'quizcourse-importer'); ?></h3>
               
               <div class="qci-error-details">
                   <h4><?php _e('Error Details:', 'quizcourse-importer'); ?></h4>
                   <div class="qci-error-message-container">
                       <!-- Will be filled dynamically -->
                   </div>
               </div>
               
               <div class="qci-troubleshooting-tips">
                   <h4><?php _e('Troubleshooting Tips:', 'quizcourse-importer'); ?></h4>
                   <ul>
                       <li><?php _e('Make sure your file follows the required single-sheet format with all required columns.', 'quizcourse-importer'); ?></li>
                       <li><?php _e('Check that all parent_id references point to valid IDs within your file.', 'quizcourse-importer'); ?></li>
                       <li><?php _e('Ensure record_type values are correct for each row (course, quiz, question, answer).', 'quizcourse-importer'); ?></li>
                       <li><?php _e('Verify question_type values are valid for all question records.', 'quizcourse-importer'); ?></li>
                       <li><?php _e('Make sure each question has at least one correct answer (is_correct=1).', 'quizcourse-importer'); ?></li>
                   </ul>
               </div>
               
               <div class="qci-error-actions">
                   <a href="#" class="button button-secondary qci-view-log">
                       <span class="dashicons dashicons-visibility"></span>
                       <?php _e('View Log', 'quizcourse-importer'); ?>
                   </a>
                   
                   <a href="<?php echo esc_url(admin_url('admin.php?page=quizcourse-importer-help')); ?>" class="button button-secondary">
                       <span class="dashicons dashicons-editor-help"></span>
                       <?php _e('Help Guide', 'quizcourse-importer'); ?>
                   </a>
                   
                   <a href="<?php echo esc_url(admin_url('admin.php?page=quizcourse-importer')); ?>" class="button button-primary qci-try-again">
                       <span class="dashicons dashicons-upload"></span>
                       <?php _e('Try Again', 'quizcourse-importer'); ?>
                   </a>
               </div>
           </div>
       </div>
   </div>
</script>

<style>
/* Additional Styles for Single Sheet Format */
.qci-example-table-container {
   max-width: 100%;
   overflow-x: auto;
   margin: 20px 0;
   border: 1px solid #ddd;
   border-radius: 4px;
}

.qci-example-table {
   min-width: 100%;
   border-collapse: collapse;
}

.qci-example-table th {
   background: #f5f5f5;
   padding: 10px;
   border-bottom: 2px solid #ddd;
   text-align: left;
   font-weight: 600;
}

.qci-example-table td {
   padding: 8px 10px;
   border-bottom: 1px solid #eee;
   border-right: 1px solid #eee;
}

.qci-example-table tr:nth-child(even) {
   background-color: #f9f9f9;
}

.qci-example-table tr:hover {
   background-color: #f0f0f0;
}

.qci-template-guide {
   display: flex;
   flex-wrap: wrap;
   gap: 20px;
   margin-bottom: 20px;
}

.qci-template-column {
   flex: 1 1 300px;
}

.qci-template-column h3 {
   margin-top: 0;
   border-bottom: 1px solid #eee;
   padding-bottom: 10px;
}

.qci-column-list, .qci-structure-list {
   margin-left: 20px;
}

.qci-column-list li, .qci-structure-list li {
   margin-bottom: 8px;
}

.qci-tips-grid {
   display: grid;
   grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
   gap: 15px;
   margin: 20px 0;
}

.qci-tip {
   background: #fff;
   border: 1px solid #ddd;
   border-radius: 4px;
   padding: 15px;
}

.qci-tip h4 {
   margin-top: 0;
   display: flex;
   align-items: center;
}

.qci-tip h4 .dashicons {
   margin-right: 8px;
   color: #2271b1;
}

.qci-additional-fields h4 {
   margin-bottom: 10px;
   border-top: 1px solid #eee;
   padding-top: 15px;
}

.qci-fields-columns {
   display: flex;
   flex-wrap: wrap;
   gap: 20px;
}

.qci-fields-column {
   flex: 1 1 200px;
}

.qci-fields-column h5 {
   margin-bottom: 8px;
}

.qci-fields-column ul {
   margin-left: 15px;
}

.qci-record-types {
   display: grid;
   grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
   gap: 20px;
   margin-top: 15px;
}

.qci-record-type {
   background: #fff;
   border: 1px solid #ddd;
   border-radius: 4px;
   padding: 15px;
}

.qci-record-type h4 {
   margin-top: 0;
   border-bottom: 1px solid #eee;
   padding-bottom: 8px;
}

.qci-record-type p {
   font-style: italic;
   margin-bottom: 10px;
}

.qci-mapping-tools {
   display: flex;
   flex-wrap: wrap;
   gap: 10px;
   margin-bottom: 20px;
   padding-bottom: 15px;
   border-bottom: 1px solid #eee;
}

.qci-mapping-templates {
   margin-left: auto;
   display: flex;
   gap: 5px;
}

.qci-import-stages {
   margin: 30px 0;
}

.qci-stages-list {
   list-style: none;
   margin: 0;
   padding: 0;
}

.qci-stage {
   display: flex;
   align-items: center;
   padding: 12px 15px;
   border: 1px solid #eee;
   margin-bottom: 8px;
   border-radius: 4px;
   background: #f9f9f9;
}

.qci-stage-icon {
   margin-right: 10px;
   color: #888;
}

.qci-stage-name {
   flex-grow: 1;
   font-weight: 500;
}

.qci-stage-status {
   font-style: italic;
   color: #888;
}

.qci-stage.active {
   background: #f0f6fc;
   border-color: #c5d9ed;
}

.qci-stage.active .qci-stage-icon {
   color: #2271b1;
}

.qci-stage.active .qci-stage-status {
   color: #2271b1;
}

.qci-stage.completed {
   background: #f0f7f0;
   border-color: #c3e6cb;
}

.qci-stage.completed .qci-stage-icon {
   color: #3c9a3c;
}

.qci-stage.completed .qci-stage-status {
   color: #3c9a3c;
}

.qci-stage.error {
   background: #fbeaea;
   border-color: #f5c6cb;
}

.qci-stage.error .qci-stage-icon,
.qci-stage.error .qci-stage-status {
   color: #dc3232;
}

.qci-what-next {
   background: #f9f9f9;
   border: 1px solid #eee;
   border-radius: 4px;
   padding: 15px;
   margin: 20px 0;
}

.qci-what-next h4 {
   margin-top: 0;
}

@media (max-width: 782px) {
   .qci-mapping-tools {
       flex-direction: column;
       align-items: flex-start;
   }
   
   .qci-mapping-templates {
       margin-left: 0;
       margin-top: 10px;
       flex-wrap: wrap;
   }
   
   .qci-tip {
       grid-column: span 2;
   }
   
   .qci-record-type {
       grid-column: span 2;
   }
}
</style>

<script>
jQuery(document).ready(function($) {
   // File input change handler - Enable/disable the continue button
   $('#qci_import_file').on('change', function() {
       if ($(this).val()) {
           $('#qci-validate-file').prop('disabled', false);
       } else {
           $('#qci-validate-file').prop('disabled', true);
       }
   });
   
   // Tooltip for fields in example table
   $('.qci-example-table th').hover(function() {
       // Show tooltip on hover
       var colName = $(this).text();
       var description = getColumnDescription(colName);
       
       if (description) {
           if ($('#col-tooltip').length === 0) {
               $('body').append('<div id="col-tooltip" class="qci-tooltip"></div>');
           }
           
           $('#col-tooltip')
               .text(description)
               .css({
                   left: $(this).offset().left,
                   top: $(this).offset().top + $(this).outerHeight()
               })
               .show();
       }
   }, function() {
       // Hide tooltip
       $('#col-tooltip').hide();
   });
   
   // Helper function to get column descriptions
   function getColumnDescription(colName) {
       const descriptions = {
           'record_type': 'Identifies the type of row: course, quiz, question, or answer',
           'id': 'Unique identifier used for references between records',
           'title': 'The name or text content of the item',
           'description': 'Detailed description of the course or quiz',
           'parent_id': 'Reference to the parent item\'s ID',
           'question_type': 'Type of question: multiple_choice, true_false, etc.',
           'is_correct': 'For answers: 1 = correct, 0 = incorrect',
           'image_url': 'URL to the image for this item'
       };
       
       return descriptions[colName] || '';
   }
});
</script>

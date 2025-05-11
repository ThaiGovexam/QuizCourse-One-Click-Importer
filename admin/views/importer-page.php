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
        <a href="<?php echo esc_url(QCI_PLUGIN_URL . 'templates/sample-import-template.xlsx'); ?>" class="page-title-action">
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
                            <?php _e('Upload your Excel or CSV file containing courses, sections, quizzes, questions, and answers data. The one-click importer will process your file and create all entities in the system.', 'quizcourse-importer'); ?>
                        </p>
                        
                        <form id="qci-upload-form" method="post" enctype="multipart/form-data">
                            <div class="qci-file-upload">
                                <input type="file" name="qci_import_file" id="qci_import_file" accept=".csv,.xlsx,.xls" />
                                <label for="qci_import_file">
                                    <span class="dashicons dashicons-upload"></span>
                                    <span class="qci-upload-text"><?php _e('Choose a file or drag it here', 'quizcourse-importer'); ?></span>
                                    <span class="qci-upload-hint"><?php _e('Supported formats: .xlsx, .xls, .csv', 'quizcourse-importer'); ?></span>
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
                                <button type="submit" id="qci-validate-file" class="button button-primary button-hero">
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
                        <h2><?php _e('How to Prepare Your Data', 'quizcourse-importer'); ?></h2>
                    </div>
                    <div class="qci-panel-body">
                        <div class="qci-template-guide">
                            <div class="qci-template-column">
                                <h3><?php _e('Download Our Template', 'quizcourse-importer'); ?></h3>
                                <p><?php _e('The easiest way to get started is to download our template file. It includes all the necessary sheets and columns with sample data.', 'quizcourse-importer'); ?></p>
                                <a href="<?php echo esc_url(QCI_PLUGIN_URL . 'templates/sample-import-template.xlsx'); ?>" class="button button-secondary">
                                    <span class="dashicons dashicons-download"></span>
                                    <?php _e('Download Template', 'quizcourse-importer'); ?>
                                </a>
                            </div>
                            
                            <div class="qci-template-column">
                                <h3><?php _e('Required Structure', 'quizcourse-importer'); ?></h3>
                                <p><?php _e('Your Excel file should include the following sheets:', 'quizcourse-importer'); ?></p>
                                <ul class="qci-sheet-list">
                                    <li><strong>Courses</strong> - <?php _e('Basic course information', 'quizcourse-importer'); ?></li>
                                    <li><strong>Sections</strong> - <?php _e('Course sections or modules', 'quizcourse-importer'); ?></li>
                                    <li><strong>Quizzes</strong> - <?php _e('Quizzes for each section', 'quizcourse-importer'); ?></li>
                                    <li><strong>Questions</strong> - <?php _e('Quiz questions', 'quizcourse-importer'); ?></li>
                                    <li><strong>Answers</strong> - <?php _e('Answers for each question', 'quizcourse-importer'); ?></li>
                                </ul>
                            </div>
                            
                            <div class="qci-template-column">
                                <h3><?php _e('Relationships', 'quizcourse-importer'); ?></h3>
                                <p><?php _e('Entities are linked using reference columns:', 'quizcourse-importer'); ?></p>
                                <ul class="qci-reference-list">
                                    <li><strong>Sections</strong> → <?php _e('link to Courses via', 'quizcourse-importer'); ?> <code>course_reference</code></li>
                                    <li><strong>Quizzes</strong> → <?php _e('link to Sections via', 'quizcourse-importer'); ?> <code>section_reference</code></li>
                                    <li><strong>Questions</strong> → <?php _e('link to Quizzes via', 'quizcourse-importer'); ?> <code>quiz_reference</code></li>
                                    <li><strong>Answers</strong> → <?php _e('link to Questions via', 'quizcourse-importer'); ?> <code>question_reference</code></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="qci-panel qci-format-help">
                    <div class="qci-panel-header">
                        <h2><?php _e('Format Tips', 'quizcourse-importer'); ?></h2>
                    </div>
                    <div class="qci-panel-body">
                        <div class="qci-tips-grid">
                            <div class="qci-tip">
                                <h4><span class="dashicons dashicons-editor-help"></span> <?php _e('Question Types', 'quizcourse-importer'); ?></h4>
                                <p><?php _e('Valid question types include: multiple_choice, true_false, short_answer, essay, fill_in_blank, and matching.', 'quizcourse-importer'); ?></p>
                            </div>
                            
                            <div class="qci-tip">
                                <h4><span class="dashicons dashicons-editor-help"></span> <?php _e('Correct Answers', 'quizcourse-importer'); ?></h4>
                                <p><?php _e('For answers, set is_correct to 1 for correct answers and 0 for incorrect answers.', 'quizcourse-importer'); ?></p>
                            </div>
                            
                            <div class="qci-tip">
                                <h4><span class="dashicons dashicons-editor-help"></span> <?php _e('Images', 'quizcourse-importer'); ?></h4>
                                <p><?php _e('For images, you can use either full URLs or media library paths.', 'quizcourse-importer'); ?></p>
                            </div>
                            
                            <div class="qci-tip">
                                <h4><span class="dashicons dashicons-editor-help"></span> <?php _e('HTML Content', 'quizcourse-importer'); ?></h4>
                                <p><?php _e('You can include HTML in description, question, and answer fields for rich formatting.', 'quizcourse-importer'); ?></p>
                            </div>
                        </div>
                        
                        <p class="qci-help-link">
                            <?php _e('For detailed instructions, check our', 'quizcourse-importer'); ?> 
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
                <p><?php _e('You are about to import your data. This will create new courses, sections, quizzes, questions, and answers in your system.', 'quizcourse-importer'); ?></p>
                
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
                <?php _e('Match the fields in your file to the corresponding fields in the system. The importer will try to match them automatically, but you can adjust as needed.', 'quizcourse-importer'); ?>
            </p>
            
            <div class="qci-mapping-container">
                <!-- Content will be dynamically filled -->
            </div>
            
            <div class="qci-actions qci-mapping-actions">
                <button type="button" class="button qci-back-button">
                    <span class="dashicons dashicons-arrow-left-alt"></span>
                    <?php _e('Back', 'quizcourse-importer'); ?>
                </button>
                <button type="button" id="qci-start-import" class="button button-primary button-hero">
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
            
            <div class="qci-actions qci-import-actions" style="display: none;">
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
                
                <div class="qci-complete-actions">
                    <a href="#" class="button button-secondary qci-view-log">
                        <span class="dashicons dashicons-visibility"></span>
                        <?php _e('View Log', 'quizcourse-importer'); ?>
                    </a>
                    
                    <a href="<?php echo esc_url(admin_url('admin.php?page=your-courses-page')); ?>" class="button button-secondary qci-view-courses">
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
                        <li><?php _e('Check that your file follows the required format.', 'quizcourse-importer'); ?></li>
                        <li><?php _e('Ensure all required fields are included.', 'quizcourse-importer'); ?></li>
                        <li><?php _e('Verify that reference IDs are consistent across sheets.', 'quizcourse-importer'); ?></li>
                        <li><?php _e('Make sure special characters are properly encoded.', 'quizcourse-importer'); ?></li>
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

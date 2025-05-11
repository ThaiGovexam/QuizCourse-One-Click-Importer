<?php
// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

// Get saved settings
$settings = get_option('qci_settings', array());

// Set default values if not set
$defaults = array(
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
    'custom_css' => '',
);

$settings = wp_parse_args($settings, $defaults);

// Get all WordPress users for author selection
$users = get_users(array(
    'role__in' => array('administrator', 'editor', 'author'),
    'orderby' => 'display_name',
    'fields' => array('ID', 'display_name'),
));
?>

<div class="wrap">
    <h1><?php _e('QuizCourse Importer Settings', 'quizcourse-importer'); ?></h1>
    
    <?php if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true'): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Settings saved successfully.', 'quizcourse-importer'); ?></p>
        </div>
    <?php endif; ?>
    
    <form method="post" action="options.php" id="qci-settings-form">
        <?php settings_fields('qci_settings_group'); ?>
        
        <div class="qci-settings-container">
            <div class="qci-settings-tabs">
                <ul class="qci-tabs-nav">
                    <li class="active"><a href="#general-settings"><?php _e('General', 'quizcourse-importer'); ?></a></li>
                    <li><a href="#import-settings"><?php _e('Import Options', 'quizcourse-importer'); ?></a></li>
                    <li><a href="#advanced-settings"><?php _e('Advanced', 'quizcourse-importer'); ?></a></li>
                    <li><a href="#help-settings"><?php _e('Help & Documentation', 'quizcourse-importer'); ?></a></li>
                </ul>
                
                <!-- General Settings Tab -->
                <div id="general-settings" class="qci-tab-content active">
                    <h2><?php _e('General Settings', 'quizcourse-importer'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="qci_settings_default_course_status"><?php _e('Default Course Status', 'quizcourse-importer'); ?></label>
                            </th>
                            <td>
                                <select name="qci_settings[default_course_status]" id="qci_settings_default_course_status">
                                    <option value="publish" <?php selected($settings['default_course_status'], 'publish'); ?>><?php _e('Published', 'quizcourse-importer'); ?></option>
                                    <option value="draft" <?php selected($settings['default_course_status'], 'draft'); ?>><?php _e('Draft', 'quizcourse-importer'); ?></option>
                                    <option value="pending" <?php selected($settings['default_course_status'], 'pending'); ?>><?php _e('Pending Review', 'quizcourse-importer'); ?></option>
                                </select>
                                <p class="description"><?php _e('Default status for imported courses if not specified in the import file.', 'quizcourse-importer'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="qci_settings_default_quiz_status"><?php _e('Default Quiz Status', 'quizcourse-importer'); ?></label>
                            </th>
                            <td>
                                <select name="qci_settings[default_quiz_status]" id="qci_settings_default_quiz_status">
                                    <option value="publish" <?php selected($settings['default_quiz_status'], 'publish'); ?>><?php _e('Published', 'quizcourse-importer'); ?></option>
                                    <option value="draft" <?php selected($settings['default_quiz_status'], 'draft'); ?>><?php _e('Draft', 'quizcourse-importer'); ?></option>
                                    <option value="pending" <?php selected($settings['default_quiz_status'], 'pending'); ?>><?php _e('Pending Review', 'quizcourse-importer'); ?></option>
                                </select>
                                <p class="description"><?php _e('Default status for imported quizzes if not specified in the import file.', 'quizcourse-importer'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="qci_settings_default_author"><?php _e('Default Author', 'quizcourse-importer'); ?></label>
                            </th>
                            <td>
                                <select name="qci_settings[default_author]" id="qci_settings_default_author">
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo esc_attr($user->ID); ?>" <?php selected($settings['default_author'], $user->ID); ?>>
                                            <?php echo esc_html($user->display_name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php _e('Default author for imported courses and quizzes if not specified in the import file.', 'quizcourse-importer'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="qci_settings_notification_email"><?php _e('Notification Email', 'quizcourse-importer'); ?></label>
                            </th>
                            <td>
                                <input type="email" name="qci_settings[notification_email]" id="qci_settings_notification_email" value="<?php echo esc_attr($settings['notification_email']); ?>" class="regular-text">
                                <p class="description"><?php _e('Email address to receive import notifications.', 'quizcourse-importer'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="qci_settings_enable_auto_mapping"><?php _e('Enable Auto Field Mapping', 'quizcourse-importer'); ?></label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" name="qci_settings[enable_auto_mapping]" id="qci_settings_enable_auto_mapping" value="yes" <?php checked($settings['enable_auto_mapping'], 'yes'); ?>>
                                    <?php _e('Automatically map fields based on column names', 'quizcourse-importer'); ?>
                                </label>
                                <p class="description"><?php _e('When enabled, the importer will try to automatically match file columns to system fields.', 'quizcourse-importer'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Import Options Tab -->
                <div id="import-settings" class="qci-tab-content">
                    <h2><?php _e('Import Options', 'quizcourse-importer'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="qci_settings_import_batch_size"><?php _e('Import Batch Size', 'quizcourse-importer'); ?></label>
                            </th>
                            <td>
                                <input type="number" name="qci_settings[import_batch_size]" id="qci_settings_import_batch_size" value="<?php echo esc_attr($settings['import_batch_size']); ?>" min="10" max="500" step="10" class="small-text">
                                <p class="description"><?php _e('Number of items to process in each batch. Lower numbers may help with timeout issues on some servers.', 'quizcourse-importer'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="qci_settings_import_timeout"><?php _e('Import Timeout', 'quizcourse-importer'); ?></label>
                            </th>
                            <td>
                                <input type="number" name="qci_settings[import_timeout]" id="qci_settings_import_timeout" value="<?php echo esc_attr($settings['import_timeout']); ?>" min="30" max="900" step="30" class="small-text">
                                <p class="description"><?php _e('Maximum execution time in seconds for each import batch. Adjust if you experience timeout errors.', 'quizcourse-importer'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="qci_settings_skip_existing"><?php _e('Skip Existing Items', 'quizcourse-importer'); ?></label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" name="qci_settings[skip_existing]" id="qci_settings_skip_existing" value="yes" <?php checked($settings['skip_existing'], 'yes'); ?>>
                                    <?php _e('Skip items that already exist in the system', 'quizcourse-importer'); ?>
                                </label>
                                <p class="description"><?php _e('When enabled, the importer will skip items that have the same title or reference ID instead of updating them.', 'quizcourse-importer'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="qci_settings_enable_auto_publish"><?php _e('Auto-Publish After Import', 'quizcourse-importer'); ?></label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" name="qci_settings[enable_auto_publish]" id="qci_settings_enable_auto_publish" value="yes" <?php checked($settings['enable_auto_publish'], 'yes'); ?>>
                                    <?php _e('Automatically publish courses and quizzes after successful import', 'quizcourse-importer'); ?>
                                </label>
                                <p class="description"><?php _e('This will override the default status settings above for successful imports.', 'quizcourse-importer'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="qci_settings_csv_delimiter"><?php _e('CSV Delimiter', 'quizcourse-importer'); ?></label>
                            </th>
                            <td>
                                <select name="qci_settings[csv_delimiter]" id="qci_settings_csv_delimiter">
                                    <option value="," <?php selected($settings['csv_delimiter'], ','); ?>><?php _e('Comma (,)', 'quizcourse-importer'); ?></option>
                                    <option value=";" <?php selected($settings['csv_delimiter'], ';'); ?>><?php _e('Semicolon (;)', 'quizcourse-importer'); ?></option>
                                    <option value="tab" <?php selected($settings['csv_delimiter'], 'tab'); ?>><?php _e('Tab', 'quizcourse-importer'); ?></option>
                                </select>
                                <p class="description"><?php _e('Character used to separate fields in CSV files.', 'quizcourse-importer'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Advanced Settings Tab -->
                <div id="advanced-settings" class="qci-tab-content">
                    <h2><?php _e('Advanced Settings', 'quizcourse-importer'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="qci_settings_enable_logging"><?php _e('Enable Logging', 'quizcourse-importer'); ?></label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" name="qci_settings[enable_logging]" id="qci_settings_enable_logging" value="yes" <?php checked($settings['enable_logging'], 'yes'); ?>>
                                    <?php _e('Log import activities and errors', 'quizcourse-importer'); ?>
                                </label>
                                <p class="description"><?php _e('Logs will be stored in the wp-content/uploads/qci-logs directory.', 'quizcourse-importer'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="qci_settings_clear_temp_files"><?php _e('Auto-Clear Temporary Files', 'quizcourse-importer'); ?></label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" name="qci_settings[clear_temp_files]" id="qci_settings_clear_temp_files" value="yes" <?php checked($settings['clear_temp_files'], 'yes'); ?>>
                                    <?php _e('Automatically delete temporary files after import', 'quizcourse-importer'); ?>
                                </label>
                                <p class="description"><?php _e('Recommended for security. Temporary files will be deleted after successful or failed imports.', 'quizcourse-importer'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="qci_settings_custom_css"><?php _e('Custom CSS', 'quizcourse-importer'); ?></label>
                            </th>
                            <td>
                                <textarea name="qci_settings[custom_css]" id="qci_settings_custom_css" rows="6" class="large-text code"><?php echo esc_textarea($settings['custom_css']); ?></textarea>
                                <p class="description"><?php _e('Add custom CSS to style the importer interface.', 'quizcourse-importer'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Clear Logs', 'quizcourse-importer'); ?></th>
                            <td>
                                <button type="button" id="qci-clear-logs" class="button">
                                    <?php _e('Clear All Logs', 'quizcourse-importer'); ?>
                                </button>
                                <p class="description"><?php _e('Delete all import logs. This cannot be undone.', 'quizcourse-importer'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Reset Plugin Settings', 'quizcourse-importer'); ?></th>
                            <td>
                                <button type="button" id="qci-reset-settings" class="button">
                                    <?php _e('Reset to Defaults', 'quizcourse-importer'); ?>
                                </button>
                                <p class="description"><?php _e('Reset all plugin settings to their default values. This cannot be undone.', 'quizcourse-importer'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Help & Documentation Tab -->
                <div id="help-settings" class="qci-tab-content">
                    <h2><?php _e('Help & Documentation', 'quizcourse-importer'); ?></h2>
                    
                    <div class="qci-help-section">
                        <h3><?php _e('Getting Started', 'quizcourse-importer'); ?></h3>
                        <p><?php _e('QuizCourse Importer allows you to import quizzes, questions, answers, courses, and sections with a single file upload. Follow these steps to get started:', 'quizcourse-importer'); ?></p>
                        
                        <ol>
                            <li><?php _e('<strong>Prepare your data file</strong> - Create an Excel file with separate sheets for Courses, Sections, Quizzes, Questions, and Answers. Each sheet should contain the required columns.', 'quizcourse-importer'); ?></li>
                            <li><?php _e('<strong>Upload your file</strong> - Go to the Import page and upload your Excel or CSV file.', 'quizcourse-importer'); ?></li>
                            <li><?php _e('<strong>Map fields</strong> - Match the columns in your file to the corresponding fields in the system.', 'quizcourse-importer'); ?></li>
                            <li><?php _e('<strong>Import data</strong> - Start the import process and wait for it to complete.', 'quizcourse-importer'); ?></li>
                            <li><?php _e('<strong>Review results</strong> - Check the import results and address any errors if needed.', 'quizcourse-importer'); ?></li>
                        </ol>
                    </div>
                    
                    <div class="qci-help-section">
                        <h3><?php _e('File Format Requirements', 'quizcourse-importer'); ?></h3>
                        
                        <h4><?php _e('Excel File', 'quizcourse-importer'); ?></h4>
                        <p><?php _e('Your Excel file should contain the following sheets:', 'quizcourse-importer'); ?></p>
                        
                        <ul>
                            <li>
                                <strong><?php _e('Courses', 'quizcourse-importer'); ?></strong>
                                <p><?php _e('Required columns: course_title, course_description', 'quizcourse-importer'); ?></p>
                            </li>
                            <li>
                                <strong><?php _e('Sections', 'quizcourse-importer'); ?></strong>
                                <p><?php _e('Required columns: section_title, course_reference', 'quizcourse-importer'); ?></p>
                            </li>
                            <li>
                                <strong><?php _e('Quizzes', 'quizcourse-importer'); ?></strong>
                                <p><?php _e('Required columns: quiz_title, section_reference', 'quizcourse-importer'); ?></p>
                            </li>
                            <li>
                                <strong><?php _e('Questions', 'quizcourse-importer'); ?></strong>
                                <p><?php _e('Required columns: question_text, quiz_reference, question_type', 'quizcourse-importer'); ?></p>
                            </li>
                            <li>
                                <strong><?php _e('Answers', 'quizcourse-importer'); ?></strong>
                                <p><?php _e('Required columns: answer_text, question_reference, is_correct', 'quizcourse-importer'); ?></p>
                            </li>
                        </ul>
                        
                        <h4><?php _e('CSV File', 'quizcourse-importer'); ?></h4>
                        <p><?php _e('If you\'re using a CSV file, it should contain columns for all required fields, including reference fields to establish relationships between entities.', 'quizcourse-importer'); ?></p>
                        
                        <div class="qci-download-section">
                            <h4><?php _e('Download Template Files', 'quizcourse-importer'); ?></h4>
                            <p><?php _e('Use our template files to ensure your data is formatted correctly:', 'quizcourse-importer'); ?></p>
                            <a href="<?php echo QCI_PLUGIN_URL; ?>templates/sample-import-template.xlsx" class="button">
                                <span class="dashicons dashicons-download"></span>
                                <?php _e('Download Excel Template', 'quizcourse-importer'); ?>
                            </a>
                            <a href="<?php echo QCI_PLUGIN_URL; ?>templates/sample-import-template.csv" class="button">
                                <span class="dashicons dashicons-download"></span>
                                <?php _e('Download CSV Template', 'quizcourse-importer'); ?>
                            </a>
                        </div>
                    </div>
                    
                    <div class="qci-help-section">
                        <h3><?php _e('Troubleshooting', 'quizcourse-importer'); ?></h3>
                        
                        <div class="qci-faq">
                            <h4><?php _e('Import fails with timeout error', 'quizcourse-importer'); ?></h4>
                            <p><?php _e('If your import fails with a timeout error, try the following:', 'quizcourse-importer'); ?></p>
                            <ul>
                                <li><?php _e('Reduce the Import Batch Size in the Import Options tab.', 'quizcourse-importer'); ?></li>
                                <li><?php _e('Increase the Import Timeout setting if your server allows it.', 'quizcourse-importer'); ?></li>
                                <li><?php _e('Split your import file into smaller files and import them separately.', 'quizcourse-importer'); ?></li>
                            </ul>
                        </div>
                        
                        <div class="qci-faq">
                            <h4><?php _e('Imported items are not linked correctly', 'quizcourse-importer'); ?></h4>
                            <p><?php _e('Ensure that your reference columns (course_reference, section_reference, etc.) contain unique identifiers that match across sheets.', 'quizcourse-importer'); ?></p>
                        </div>
                        
                        <div class="qci-faq">
                            <h4><?php _e('Questions appear without answers', 'quizcourse-importer'); ?></h4>
                            <p><?php _e('Check that your Answers sheet has the correct question_reference values that match the Questions sheet.', 'quizcourse-importer'); ?></p>
                        </div>
                        
                        <div class="qci-faq">
                            <h4><?php _e('File validation fails', 'quizcourse-importer'); ?></h4>
                            <p><?php _e('Make sure your file contains all required sheets (for Excel) or columns (for CSV) as described in the File Format Requirements section.', 'quizcourse-importer'); ?></p>
                        </div>
                    </div>
                    
                    <div class="qci-help-section">
                        <h3><?php _e('Support & Resources', 'quizcourse-importer'); ?></h3>
                        <p><?php _e('If you need further assistance, check out these resources:', 'quizcourse-importer'); ?></p>
                        
                        <ul>
                            <li><a href="#" target="_blank"><?php _e('Plugin Documentation', 'quizcourse-importer'); ?></a></li>
                            <li><a href="#" target="_blank"><?php _e('FAQ', 'quizcourse-importer'); ?></a></li>
                            <li><a href="#" target="_blank"><?php _e('Support Forum', 'quizcourse-importer'); ?></a></li>
                        </ul>
                        
                        <p><?php _e('For bug reports or feature requests, please contact plugin support.', 'quizcourse-importer'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Save Settings', 'quizcourse-importer'); ?>">
        </p>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Tab navigation
    $('.qci-tabs-nav a').on('click', function(e) {
        e.preventDefault();
        
        // Remove active class from all tabs and content
        $('.qci-tabs-nav li').removeClass('active');
        $('.qci-tab-content').removeClass('active');
        
        // Add active class to selected tab and content
        $(this).parent().addClass('active');
        $($(this).attr('href')).addClass('active');
    });
    
    // Clear logs confirmation
    $('#qci-clear-logs').on('click', function() {
        if (confirm('<?php _e('Are you sure you want to clear all logs? This action cannot be undone.', 'quizcourse-importer'); ?>')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'qci_clear_logs',
                    security: '<?php echo wp_create_nonce('qci-security'); ?>'
                },
                beforeSend: function() {
                    $('#qci-clear-logs').prop('disabled', true).text('<?php _e('Clearing...', 'quizcourse-importer'); ?>');
                },
                success: function(response) {
                    if (response.success) {
                        alert('<?php _e('All logs have been cleared successfully.', 'quizcourse-importer'); ?>');
                    } else {
                        alert('<?php _e('Failed to clear logs: ', 'quizcourse-importer'); ?>' + response.data);
                    }
                },
                error: function() {
                    alert('<?php _e('An error occurred while clearing logs.', 'quizcourse-importer'); ?>');
                },
                complete: function() {
                    $('#qci-clear-logs').prop('disabled', false).text('<?php _e('Clear All Logs', 'quizcourse-importer'); ?>');
                }
            });
        }
    });
    
    // Reset settings confirmation
    $('#qci-reset-settings').on('click', function() {
        if (confirm('<?php _e('Are you sure you want to reset all settings to their default values? This action cannot be undone.', 'quizcourse-importer'); ?>')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'qci_reset_settings',
                    security: '<?php echo wp_create_nonce('qci-security'); ?>'
                },
                beforeSend: function() {
                    $('#qci-reset-settings').prop('disabled', true).text('<?php _e('Resetting...', 'quizcourse-importer'); ?>');
                },
                success: function(response) {
                    if (response.success) {
                        alert('<?php _e('Settings have been reset to default values. The page will now reload.', 'quizcourse-importer'); ?>');
                        window.location.href = window.location.href + '&settings-updated=true';
                    } else {
                        alert('<?php _e('Failed to reset settings: ', 'quizcourse-importer'); ?>' + response.data);
                    }
                },
                error: function() {
                    alert('<?php _e('An error occurred while resetting settings.', 'quizcourse-importer'); ?>');
                },
                complete: function() {
                    $('#qci-reset-settings').prop('disabled', false).text('<?php _e('Reset to Defaults', 'quizcourse-importer'); ?>');
                }
            });
        }
    });
    
    // Toggle dependent options
    $('#qci_settings_enable_auto_publish').on('change', function() {
        if ($(this).is(':checked')) {
            $('#qci_settings_default_course_status, #qci_settings_default_quiz_status')
                .closest('tr')
                .css('opacity', '0.5');
        } else {
            $('#qci_settings_default_course_status, #qci_settings_default_quiz_status')
                .closest('tr')
                .css('opacity', '1');
        }
    }).trigger('change');
});
</script>

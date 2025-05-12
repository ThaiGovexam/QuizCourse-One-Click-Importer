<?php
/**
 * Template for field mapping interface.
 * 
 * This page displays after file upload and validation,
 * allowing users to map their spreadsheet fields to database fields.
 * Updated to support single-sheet import format for AysQuiz and FoxLMS.
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

// Get mapping data from the session
$mapping_data = isset($_SESSION['qci_mapping_data']) ? $_SESSION['qci_mapping_data'] : array();
$file_id = isset($_SESSION['qci_file_id']) ? $_SESSION['qci_file_id'] : '';
$file_type = isset($_SESSION['qci_file_type']) ? $_SESSION['qci_file_type'] : '';
$preview_data = isset($_SESSION['qci_preview_data']) ? $_SESSION['qci_preview_data'] : array();

// If no data is available, redirect to upload page
if (empty($mapping_data) || empty($file_id)) {
    wp_redirect(admin_url('admin.php?page=quizcourse-importer'));
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="qci-breadcrumbs">
        <ol>
            <li><a href="<?php echo esc_url(admin_url('admin.php?page=quizcourse-importer')); ?>"><?php _e('Upload File', 'quizcourse-importer'); ?></a></li>
            <li class="active"><?php _e('Map Fields', 'quizcourse-importer'); ?></li>
            <li><?php _e('Import', 'quizcourse-importer'); ?></li>
            <li><?php _e('Complete', 'quizcourse-importer'); ?></li>
        </ol>
    </div>
    
    <div class="qci-container qci-mapping-container">
        <div class="qci-mapping-instructions">
            <h2><?php _e('Map Your File Fields to System Fields', 'quizcourse-importer'); ?></h2>
            <p><?php _e('Tell us which columns in your file correspond to which fields in our system. This helps us correctly import your data.', 'quizcourse-importer'); ?></p>
            
            <div class="qci-mapping-tips">
                <h4><?php _e('Mapping Tips:', 'quizcourse-importer'); ?></h4>
                <ul>
                    <li><?php _e('Fields with <span class="required">*</span> are required.', 'quizcourse-importer'); ?></li>
                    <li><?php _e('You must have a "record_type" column in your file to identify each row as course, quiz, question, or answer.', 'quizcourse-importer'); ?></li>
                    <li><?php _e('Reference fields connect different parts of your data (e.g., linking questions to quizzes).', 'quizcourse-importer'); ?></li>
                    <li><?php _e('If you don\'t need a field, select "--Skip this field--".', 'quizcourse-importer'); ?></li>
                    <li><?php _e('Hover over field names for additional information.', 'quizcourse-importer'); ?></li>
                </ul>
            </div>
            
            <div class="qci-auto-mapping-tools">
                <button type="button" id="qci-auto-map" class="button"><?php _e('Auto-Map Fields', 'quizcourse-importer'); ?></button>
                <button type="button" id="qci-reset-mapping" class="button"><?php _e('Reset Mapping', 'quizcourse-importer'); ?></button>
                <button type="button" id="qci-load-template" class="button"><?php _e('Load Saved Template', 'quizcourse-importer'); ?></button>
                <button type="button" id="qci-save-template" class="button"><?php _e('Save as Template', 'quizcourse-importer'); ?></button>
            </div>
        </div>
        
        <form id="qci-mapping-form" method="post" action="<?php echo esc_url(admin_url('admin.php?page=quizcourse-importer&step=import')); ?>">
            <input type="hidden" name="qci_file_id" id="qci_file_id" value="<?php echo esc_attr($file_id); ?>">
            <input type="hidden" name="qci_file_type" value="<?php echo esc_attr($file_type); ?>">
            <input type="hidden" name="qci_action" value="process_import">
            <?php wp_nonce_field('qci_import_data', 'qci_nonce'); ?>
            
            <!-- Single Sheet Mapping Interface -->
            <div class="qci-field-mapping-section">
                <h3><?php _e('Field Mapping for Single Sheet', 'quizcourse-importer'); ?></h3>
                
                <div class="qci-field-mapping-instructions">
                    <p><?php _e('Your file contains a single sheet with all data types. For each column, select the appropriate field in our system.', 'quizcourse-importer'); ?></p>
                    <p><?php _e('Make sure to map the "record_type" column, which should contain values like "course", "quiz", "question", or "answer".', 'quizcourse-importer'); ?></p>
                </div>
                
                <div class="qci-field-mapping-table">
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th class="column-file-field"><?php _e('File Column', 'quizcourse-importer'); ?></th>
                                <th class="column-system-field"><?php _e('System Field', 'quizcourse-importer'); ?></th>
                                <th class="column-sample-data"><?php _e('Sample Data', 'quizcourse-importer'); ?></th>
                                <th class="column-field-info"><?php _e('Field Info', 'quizcourse-importer'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mapping_data as $field_name): ?>
                                <tr>
                                    <td class="column-file-field">
                                        <?php echo esc_html($field_name); ?>
                                    </td>
                                    <td class="column-system-field">
                                        <select name="qci_mapping[<?php echo esc_attr($field_name); ?>]" class="qci-mapping-select" data-field="<?php echo esc_attr($field_name); ?>">
                                            <option value=""><?php _e('-- Skip this field --', 'quizcourse-importer'); ?></option>
                                            
                                            <?php if (strtolower($field_name) === 'record_type'): ?>
                                                <option value="record_type" selected><?php _e('Record Type (required)', 'quizcourse-importer'); ?></option>
                                            <?php else: ?>
                                                <!-- Common Fields for All Record Types -->
                                                <optgroup label="<?php _e('Common Fields', 'quizcourse-importer'); ?>">
                                                    <option value="id" <?php selected($this->get_suggested_mapping($field_name), 'id'); ?>><?php _e('ID/Reference Key', 'quizcourse-importer'); ?> <?php $this->required_mark('id'); ?></option>
                                                    <option value="title" <?php selected($this->get_suggested_mapping($field_name), 'title'); ?>><?php _e('Title/Name', 'quizcourse-importer'); ?> <?php $this->required_mark('title'); ?></option>
                                                    <option value="description" <?php selected($this->get_suggested_mapping($field_name), 'description'); ?>><?php _e('Description', 'quizcourse-importer'); ?></option>
                                                    <option value="status" <?php selected($this->get_suggested_mapping($field_name), 'status'); ?>><?php _e('Status (publish/draft/1/0)', 'quizcourse-importer'); ?></option>
                                                    <option value="ordering" <?php selected($this->get_suggested_mapping($field_name), 'ordering'); ?>><?php _e('Display Order', 'quizcourse-importer'); ?></option>
                                                    <option value="image" <?php selected($this->get_suggested_mapping($field_name), 'image'); ?>><?php _e('Image URL', 'quizcourse-importer'); ?></option>
                                                </optgroup>
                                                
                                                <!-- Course-specific Fields -->
                                                <optgroup label="<?php _e('Course Fields', 'quizcourse-importer'); ?>">
                                                    <option value="course_author_id" <?php selected($this->get_suggested_mapping($field_name), 'course_author_id'); ?>><?php _e('Author ID', 'quizcourse-importer'); ?></option>
                                                    <option value="course_category_ids" <?php selected($this->get_suggested_mapping($field_name), 'course_category_ids'); ?>><?php _e('Category IDs (comma-separated)', 'quizcourse-importer'); ?></option>
                                                    <option value="course_date_created" <?php selected($this->get_suggested_mapping($field_name), 'course_date_created'); ?>><?php _e('Creation Date', 'quizcourse-importer'); ?></option>
                                                    <option value="course_options" <?php selected($this->get_suggested_mapping($field_name), 'course_options'); ?>><?php _e('Course Options (JSON)', 'quizcourse-importer'); ?></option>
                                                </optgroup>
                                                
                                                <!-- Quiz-specific Fields -->
                                                <optgroup label="<?php _e('Quiz Fields', 'quizcourse-importer'); ?>">
                                                    <option value="quiz_course_reference" <?php selected($this->get_suggested_mapping($field_name), 'quiz_course_reference'); ?>><?php _e('Course Reference', 'quizcourse-importer'); ?> <?php $this->required_mark('quiz_course_reference'); ?></option>
                                                    <option value="quiz_category_id" <?php selected($this->get_suggested_mapping($field_name), 'quiz_category_id'); ?>><?php _e('Quiz Category ID', 'quizcourse-importer'); ?></option>
                                                    <option value="quiz_author_id" <?php selected($this->get_suggested_mapping($field_name), 'quiz_author_id'); ?>><?php _e('Author ID', 'quizcourse-importer'); ?></option>
                                                    <option value="quiz_published" <?php selected($this->get_suggested_mapping($field_name), 'quiz_published'); ?>><?php _e('Published (1/0)', 'quizcourse-importer'); ?></option>
                                                    <option value="quiz_options" <?php selected($this->get_suggested_mapping($field_name), 'quiz_options'); ?>><?php _e('Quiz Options (JSON)', 'quizcourse-importer'); ?></option>
                                                </optgroup>
                                                
                                                <!-- Question-specific Fields -->
                                                <optgroup label="<?php _e('Question Fields', 'quizcourse-importer'); ?>">
                                                    <option value="question_quiz_reference" <?php selected($this->get_suggested_mapping($field_name), 'question_quiz_reference'); ?>><?php _e('Quiz Reference', 'quizcourse-importer'); ?> <?php $this->required_mark('question_quiz_reference'); ?></option>
                                                    <option value="question_type" <?php selected($this->get_suggested_mapping($field_name), 'question_type'); ?>><?php _e('Question Type', 'quizcourse-importer'); ?> <?php $this->required_mark('question_type'); ?></option>
                                                    <option value="question_category_id" <?php selected($this->get_suggested_mapping($field_name), 'question_category_id'); ?>><?php _e('Category ID', 'quizcourse-importer'); ?></option>
                                                    <option value="question_tag_id" <?php selected($this->get_suggested_mapping($field_name), 'question_tag_id'); ?>><?php _e('Tag ID', 'quizcourse-importer'); ?></option>
                                                    <option value="question_hint" <?php selected($this->get_suggested_mapping($field_name), 'question_hint'); ?>><?php _e('Hint', 'quizcourse-importer'); ?></option>
                                                    <option value="question_explanation" <?php selected($this->get_suggested_mapping($field_name), 'question_explanation'); ?>><?php _e('Explanation', 'quizcourse-importer'); ?></option>
                                                    <option value="question_weight" <?php selected($this->get_suggested_mapping($field_name), 'question_weight'); ?>><?php _e('Weight/Points', 'quizcourse-importer'); ?></option>
                                                    <option value="question_options" <?php selected($this->get_suggested_mapping($field_name), 'question_options'); ?>><?php _e('Question Options (JSON)', 'quizcourse-importer'); ?></option>
                                                </optgroup>
                                                
                                                <!-- Answer-specific Fields -->
                                                <optgroup label="<?php _e('Answer Fields', 'quizcourse-importer'); ?>">
                                                    <option value="answer_question_reference" <?php selected($this->get_suggested_mapping($field_name), 'answer_question_reference'); ?>><?php _e('Question Reference', 'quizcourse-importer'); ?> <?php $this->required_mark('answer_question_reference'); ?></option>
                                                    <option value="answer_correct" <?php selected($this->get_suggested_mapping($field_name), 'answer_correct'); ?>><?php _e('Is Correct (1/0)', 'quizcourse-importer'); ?> <?php $this->required_mark('answer_correct'); ?></option>
                                                    <option value="answer_weight" <?php selected($this->get_suggested_mapping($field_name), 'answer_weight'); ?>><?php _e('Weight/Points', 'quizcourse-importer'); ?></option>
                                                    <option value="answer_ordering" <?php selected($this->get_suggested_mapping($field_name), 'answer_ordering'); ?>><?php _e('Display Order', 'quizcourse-importer'); ?></option>
                                                    <option value="answer_options" <?php selected($this->get_suggested_mapping($field_name), 'answer_options'); ?>><?php _e('Answer Options (JSON)', 'quizcourse-importer'); ?></option>
                                                </optgroup>
                                            <?php endif; ?>
                                        </select>
                                        <div class="qci-field-detection">
                                            <?php if ($this->get_suggested_mapping($field_name)): ?>
                                                <small><?php _e('Suggested mapping based on field name', 'quizcourse-importer'); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="column-sample-data">
                                        <?php if (isset($preview_data[0][$field_name])): ?>
                                            <code><?php echo esc_html($preview_data[0][$field_name]); ?></code>
                                        <?php else: ?>
                                            <em><?php _e('No data', 'quizcourse-importer'); ?></em>
                                        <?php endif; ?>
                                    </td>
                                    <td class="column-field-info">
                                        <span class="qci-field-info-icon dashicons dashicons-info"></span>
                                        <div class="qci-field-info-tooltip">
                                            <?php echo $this->get_field_description($field_name); ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="qci-preview-section">
                <h3><?php _e('Data Preview', 'quizcourse-importer'); ?> <button type="button" class="qci-toggle-preview button button-small"><?php _e('Show/Hide Preview', 'quizcourse-importer'); ?></button></h3>
                
                <div class="qci-preview-data-container" style="display: none;">
                    <?php if (!empty($preview_data)): ?>
                        <div class="qci-preview-table-container">
                            <table class="widefat striped qci-preview-table">
                                <thead>
                                    <tr>
                                        <?php foreach ($mapping_data as $field_name): ?>
                                            <th><?php echo esc_html($field_name); ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $max_preview_rows = 5;
                                    $preview_count = min(count($preview_data), $max_preview_rows);
                                    for ($i = 0; $i < $preview_count; $i++): 
                                    ?>
                                        <tr>
                                            <?php foreach ($mapping_data as $field_name): ?>
                                                <td>
                                                    <?php 
                                                    if (isset($preview_data[$i][$field_name])) {
                                                        echo esc_html($preview_data[$i][$field_name]);
                                                    } else {
                                                        echo '<em>' . __('Empty', 'quizcourse-importer') . '</em>';
                                                    }
                                                    ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endfor; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php if (count($preview_data) > $max_preview_rows): ?>
                            <p class="qci-preview-note">
                                <?php printf(__('Showing %d of %d rows.', 'quizcourse-importer'), $max_preview_rows, count($preview_data)); ?>
                            </p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="qci-no-preview"><?php _e('No preview data available.', 'quizcourse-importer'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="qci-data-structure-guide">
                <h3><?php _e('Single Sheet Data Structure Guide', 'quizcourse-importer'); ?></h3>
                
                <div class="qci-data-structure-explanation">
                    <p><?php _e('Your single sheet should contain all data types (courses, quizzes, questions, answers) with a "record_type" column to identify each row. The following relationships are required:', 'quizcourse-importer'); ?></p>
                    
                    <ul class="qci-relationship-list">
                        <li><strong><?php _e('Courses:', 'quizcourse-importer'); ?></strong> <?php _e('record_type = "course"', 'quizcourse-importer'); ?></li>
                        <li><strong><?php _e('Quizzes:', 'quizcourse-importer'); ?></strong> <?php _e('record_type = "quiz", reference courses via quiz_course_reference', 'quizcourse-importer'); ?></li>
                        <li><strong><?php _e('Questions:', 'quizcourse-importer'); ?></strong> <?php _e('record_type = "question", reference quizzes via question_quiz_reference', 'quizcourse-importer'); ?></li>
                        <li><strong><?php _e('Answers:', 'quizcourse-importer'); ?></strong> <?php _e('record_type = "answer", reference questions via answer_question_reference', 'quizcourse-importer'); ?></li>
                    </ul>
                    
                    <div class="qci-example-section">
                        <h4><?php _e('Example Structure:', 'quizcourse-importer'); ?></h4>
                        <pre class="qci-example-code">
record_type,id,title,description,course_reference,quiz_reference,question_reference,is_correct
course,C001,WordPress Course,Learn WordPress from scratch,,,
quiz,Q001,WordPress Basics,Test your knowledge,C001,,
question,QU001,What is WordPress?,Choose the best answer,C001,Q001,
answer,A001,A content management system,,C001,Q001,QU001,1
answer,A002,A web browser,,C001,Q001,QU001,0
                        </pre>
                    </div>
                </div>
                
                <div class="qci-help-note">
                    <p>
                        <span class="dashicons dashicons-lightbulb"></span>
                        <?php _e('Tip: It\'s best to place records in the order of courses, quizzes, questions, then answers to ensure references can be resolved properly during import.', 'quizcourse-importer'); ?>
                    </p>
                </div>
            </div>

            <div class="qci-validation-section">
                <h3><?php _e('Field Validation', 'quizcourse-importer'); ?></h3>
                
                <div class="qci-validation-status-container">
                    <!-- Will be populated via AJAX -->
                    <div id="qci-validation-results"></div>
                </div>
                
                <div class="qci-validation-actions">
                    <button type="button" id="qci-validate-mapping" class="button button-secondary"><?php _e('Validate Mapping', 'quizcourse-importer'); ?></button>
                </div>
            </div>
            
            <div class="qci-actions">
                <button type="button" class="button qci-back-button">
                    <span class="dashicons dashicons-arrow-left-alt"></span>
                    <?php _e('Back to Upload', 'quizcourse-importer'); ?>
                </button>
                
                <button type="submit" id="qci-start-import" class="button button-primary" disabled>
                    <span class="dashicons dashicons-database-import"></span>
                    <?php _e('Start Import', 'quizcourse-importer'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Toggle preview data
    $('.qci-toggle-preview').on('click', function() {
        $('.qci-preview-data-container').slideToggle();
    });
    
    // Auto-map fields
    $('#qci-auto-map').on('click', function() {
        // Attempt to find record_type column and map it
        $('select[data-field="record_type"]').val('record_type');
        
        // Common field mappings
        const commonMappings = {
            'id': ['id', 'reference', 'key', 'identifier'],
            'title': ['title', 'name', 'heading'],
            'description': ['description', 'desc', 'content', 'summary'],
            'status': ['status', 'state', 'published'],
            'ordering': ['order', 'ordering', 'sequence', 'position', 'sort'],
            'image': ['image', 'img', 'picture', 'photo', 'featured_image']
        };
        
        // Course-specific mappings
        const courseMappings = {
            'course_author_id': ['author', 'author_id', 'creator'],
            'course_category_ids': ['category', 'categories', 'category_id'],
            'course_date_created': ['date', 'created', 'date_created'],
            'course_options': ['course_options', 'course_settings']
        };
        
        // Quiz-specific mappings
        const quizMappings = {
            'quiz_course_reference': ['course', 'course_id', 'course_ref', 'course_reference'],
            'quiz_category_id': ['quiz_category', 'quiz_cat'],
            'quiz_published': ['quiz_published', 'quiz_active'],
            'quiz_options': ['quiz_options', 'quiz_settings']
        };
        
        // Question-specific mappings
        const questionMappings = {
            'question_quiz_reference': ['quiz', 'quiz_id', 'quiz_ref', 'quiz_reference'],
            'question_type': ['type', 'question_type', 'format'],
            'question_category_id': ['question_category', 'question_cat'],
            'question_hint': ['hint', 'clue', 'tip'],
            'question_explanation': ['explanation', 'answer_explanation', 'solution'],
            'question_weight': ['weight', 'points', 'score'],
            'question_options': ['question_options', 'question_settings']
        };
        
        // Answer-specific mappings
        const answerMappings = {
            'answer_question_reference': ['question', 'question_id', 'question_ref', 'question_reference'],
            'answer_correct': ['correct', 'is_correct', 'iscorrect', 'right'],
            'answer_weight': ['answer_weight', 'answer_points', 'answer_score'],
            'answer_ordering': ['answer_order', 'answer_position', 'answer_sort'],
            'answer_options': ['answer_options', 'answer_settings']
        };
        
        // Combine all mappings
        const allMappings = {
            ...commonMappings,
            ...courseMappings,
            ...quizMappings,
            ...questionMappings,
            ...answerMappings
        };
        
        // Auto-map fields based on names
        $('.qci-mapping-select').each(function() {
            // Skip if already mapped to record_type
            if ($(this).val() === 'record_type') {
                return;
            }
            
            const fieldName = $(this).data('field').toLowerCase();
            
            // Check for exact matches or pattern matches
            let matched = false;
            
            for (const [systemField, patterns] of Object.entries(allMappings)) {
                for (const pattern of patterns) {
                    if (fieldName === pattern || fieldName.includes(pattern)) {
                        $(this).val(systemField);
                        matched = true;
                        break;
                    }
                }
                if (matched) break;
            }
        });
        
        // Alert when done
        alert('<?php _e('Auto-mapping completed. Please review the mappings before importing.', 'quizcourse-importer'); ?>');
    });
    
    // Reset mapping
    $('#qci-reset-mapping').on('click', function() {
        if (confirm('<?php _e('Are you sure you want to reset all field mappings?', 'quizcourse-importer'); ?>')) {
            $('.qci-mapping-select').val('');
            
            // Keep record_type mapping if it exists
            $('select[data-field="record_type"]').val('record_type');
        }
    });
    
    // Save mapping template
    $('#qci-save-template').on('click', function() {
        const templateName = prompt('<?php _e('Enter a name for this mapping template:', 'quizcourse-importer'); ?>');
        if (!templateName) return;
        
        const mappings = {};
        $('.qci-mapping-select').each(function() {
            if ($(this).val()) {
                mappings[$(this).data('field')] = $(this).val();
            }
        });
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'qci_save_mapping_template',
                security: '<?php echo wp_create_nonce('qci-security'); ?>',
                template_name: templateName,
                mappings: mappings
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php _e('Mapping template saved successfully.', 'quizcourse-importer'); ?>');
                } else {
                    alert('<?php _e('Failed to save mapping template.', 'quizcourse-importer'); ?>');
                }
            }
        });
    });
    
    // Load mapping template
    $('#qci-load-template').on('click', function() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'qci_get_mapping_templates',
                security: '<?php echo wp_create_nonce('qci-security'); ?>'
            },
            success: function(response) {
                if (response.success && response.data.templates.length > 0) {
                    const templateSelect = $('<select id="qci-template-select"></select>');
                    $.each(response.data.templates, function(i, template) {
                        templateSelect.append($('<option></option>').val(template.id).text(template.name));
                    });
                    
                    const dialog = $('<div id="qci-template-dialog" title="<?php _e('Load Mapping Template', 'quizcourse-importer'); ?>"></div>')
                        .append('<p><?php _e('Select a template to load:', 'quizcourse-importer'); ?></p>')
                        .append(templateSelect);
                    
                    $(document.body).append(dialog);
                    
                    dialog.dialog({
                        modal: true,
                        width: 400,
                        buttons: {
                            '<?php _e('Load', 'quizcourse-importer'); ?>': function() {
                                const templateId = templateSelect.val();
                                loadMappingTemplate(templateId);
                                $(this).dialog('close');
                            },
                            '<?php _e('Cancel', 'quizcourse-importer'); ?>': function() {
                                $(this).dialog('close');
                            }
                        },
                        close: function() {
                            $(this).remove();
                        }
                    });
                } else {
                    alert('<?php _e('No saved templates found.', 'quizcourse-importer'); ?>');
                }
            }
        });
    });
    
    function loadMappingTemplate(templateId) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'qci_load_mapping_template',
                security: '<?php echo wp_create_nonce('qci-security'); ?>',
                template_id: templateId
            },
            success: function(response) {
                if (response.success) {
                    // Reset existing mappings
                    $('.qci-mapping-select').val('');
                    
                    // Apply template mappings
                    const mappings = response.data.mappings;
                    $.each(mappings, function(fieldName, systemField) {
                        $('select[data-field="' + fieldName + '"]').val(systemField);
                    });
                    
                    alert('<?php _e('Mapping template loaded successfully.', 'quizcourse-importer'); ?>');
                } else {
                    alert('<?php _e('Failed to load mapping template.', 'quizcourse-importer'); ?>');
                }
            }
        });
    }
    
    // Validate field mapping
    $('#qci-validate-mapping').on('click', function() {
        const $button = $(this);
        $button.prop('disabled', true).text('<?php _e('Validating...', 'quizcourse-importer'); ?>');
        
        // Collect mapping data
        const mappings = {};
        $('.qci-mapping-select').each(function() {
            if ($(this).val()) {
                mappings[$(this).data('field')] = $(this).val();
            }
        });
        
        // Check for record_type mapping
        let hasRecordType = false;
        $.each(mappings, function(field, value) {
            if (value === 'record_type') {
                hasRecordType = true;
                return false;
            }
        });
        
        if (!hasRecordType) {
            $('#qci-validation-results').html(
                '<div class="notice notice-error">' +
                '<p><strong><?php _e('Error:', 'quizcourse-importer'); ?></strong> <?php _e('You must map a column to "Record Type" to identify each row\'s data type.', 'quizcourse-importer'); ?></p>' +
                '</div>'
            );
            $button.prop('disabled', false).text('<?php _e('Validate Mapping', 'quizcourse-importer'); ?>');
            return;
        }
        
        // Validate required mappings for each record type
        const requiredMappings = {
            // Common required fields
            'common': ['id', 'title'],
            // Quiz-specific required fields
            'quiz': ['quiz_course_reference'],
            // Question-specific required fields
            'question': ['question_quiz_reference', 'question_type'],
            // Answer-specific required fields
            'answer': ['answer_question_reference', 'answer_correct']
        };
        
        // Track if we have all required mappings
        let missingFields = [];
        
        // Check common required fields
        $.each(requiredMappings.common, function(i, field) {
            let found = false;
            $.each(mappings, function(sourceField, targetField) {
                if (targetField === field) {
                    found = true;
                    return false;
                }
            });
            if (!found) {
                missingFields.push(field);
            }
        });
        
        // Check for required fields for specific record types
        // Note: Here we're just checking if the fields are mapped at all,
        // not if they're properly associated with record types (which requires server-side validation)
        ['quiz', 'question', 'answer'].forEach(function(recordType) {
            $.each(requiredMappings[recordType], function(i, field) {
                let found = false;
                $.each(mappings, function(sourceField, targetField) {
                    if (targetField === field) {
                        found = true;
                        return false;
                    }
                });
                if (!found) {
                    missingFields.push(field);
                }
            });
        });
        
        // If missing required fields, show error
        if (missingFields.length > 0) {
            $('#qci-validation-results').html(
                '<div class="notice notice-error">' +
                '<p><strong><?php _e('Missing Required Fields:', 'quizcourse-importer'); ?></strong></p>' +
                '<ul><li>' + missingFields.join('</li><li>') + '</li></ul>' +
                '<p><?php _e('These fields are required for proper import. Please map them to continue.', 'quizcourse-importer'); ?></p>' +
                '</div>'
            );
            $button.prop('disabled', false).text('<?php _e('Validate Mapping', 'quizcourse-importer'); ?>');
            return;
        }
        
        // Send to server for more detailed validation
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'qci_validate_mapping',
                security: '<?php echo wp_create_nonce('qci-security'); ?>',
                file_id: $('#qci_file_id').val(),
                mappings: mappings
            },
            success: function(response) {
                if (response.success) {
                    $('#qci-validation-results').html(
                        '<div class="notice notice-success">' +
                        '<p><strong><?php _e('Validation Successful!', 'quizcourse-importer'); ?></strong> ' + response.data.message + '</p>' +
                        '</div>'
                    );
                    
                    // Enable import button
                    $('#qci-start-import').prop('disabled', false);
                } else {
                    // Show validation errors
                    let errorHtml = '<div class="notice notice-error">' +
                        '<p><strong><?php _e('Validation Failed:', 'quizcourse-importer'); ?></strong></p>';
                    
                    if (Array.isArray(response.data.errors)) {
                        errorHtml += '<ul>';
                        $.each(response.data.errors, function(i, error) {
                            errorHtml += '<li>' + error + '</li>';
                        });
                        errorHtml += '</ul>';
                    } else {
                        errorHtml += '<p>' + response.data.errors + '</p>';
                    }
                    
                    errorHtml += '</div>';
                    
                    $('#qci-validation-results').html(errorHtml);
                }
            },
            error: function() {
                $('#qci-validation-results').html(
                    '<div class="notice notice-error">' +
                    '<p><?php _e('Server error while validating. Please try again.', 'quizcourse-importer'); ?></p>' +
                    '</div>'
                );
            },
            complete: function() {
                $button.prop('disabled', false).text('<?php _e('Validate Mapping', 'quizcourse-importer'); ?>');
            }
        });
    });
    
    // Show field info tooltip on hover
    $('.qci-field-info-icon').hover(
        function() {
            $(this).siblings('.qci-field-info-tooltip').fadeIn(200);
        },
        function() {
            $(this).siblings('.qci-field-info-tooltip').fadeOut(200);
        }
    );
    
    // Confirm before starting import
    $('#qci-mapping-form').on('submit', function(e) {
        if (!$('#qci-start-import').prop('disabled')) {
            if (!confirm('<?php _e('Are you sure you want to start importing? This process cannot be undone.', 'quizcourse-importer'); ?>')) {
                e.preventDefault();
            }
        } else {
            e.preventDefault();
            alert('<?php _e('Please validate your field mapping first.', 'quizcourse-importer'); ?>');
        }
    });
    
    // Back button functionality
    $('.qci-back-button').on('click', function() {
        window.location.href = '<?php echo esc_url(admin_url('admin.php?page=quizcourse-importer')); ?>';
    });
});
</script>

<style>
/* Additional Custom Styles for Mapping Page */
.qci-mapping-container {
    background: #fff;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.qci-mapping-instructions {
    margin-bottom: 20px;
    border-bottom: 1px solid #f0f0f0;
    padding-bottom: 15px;
}

.qci-mapping-tips {
    background: #f9f9f9;
    border-left: 4px solid #2271b1;
    padding: 10px 15px;
    margin-top: 15px;
}

.qci-mapping-tips ul {
    margin-bottom: 0;
}

.qci-auto-mapping-tools {
    margin: 15px 0;
    padding: 10px 0;
    border-top: 1px dashed #eee;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.qci-field-mapping-table {
    margin-top: 15px;
    overflow-x: auto;
}

.qci-field-mapping-table th,
.qci-field-mapping-table td {
    padding: 12px 15px;
}

.column-file-field {
    width: 20%;
    font-weight: 600;
}

.column-system-field {
    width: 40%;
}

.column-sample-data {
    width: 30%;
}

.column-sample-data code {
    display: block;
    padding: 5px;
    background: #f5f5f5;
    border-radius: 3px;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.column-field-info {
    width: 10%;
    text-align: center;
}

.qci-field-info-icon {
    cursor: help;
    color: #2271b1;
}

.qci-field-info-tooltip {
    display: none;
    position: absolute;
    z-index: 100;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 10px;
    max-width: 300px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    font-size: 12px;
    line-height: 1.4;
    margin-left: -150px;
    color: #444;
}

.qci-field-detection {
    margin-top: 5px;
    font-size: 11px;
    color: #2271b1;
}

.qci-preview-section {
    margin-top: 30px;
    border-top: 1px solid #f0f0f0;
    padding-top: 20px;
}

.qci-preview-data-container {
    margin-top: 15px;
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid #e5e5e5;
    background: #f9f9f9;
}

.qci-preview-table-container {
    overflow-x: auto;
}

.qci-preview-table {
    border-collapse: collapse;
    width: 100%;
}

.qci-preview-table th {
    position: sticky;
    top: 0;
    background: #f1f1f1;
    z-index: 1;
}

.qci-preview-table th,
.qci-preview-table td {
    border: 1px solid #e5e5e5;
    padding: 8px 10px;
    font-size: 12px;
}

.qci-preview-note {
    margin: 10px;
    font-size: 12px;
    font-style: italic;
    color: #666;
}

.qci-data-structure-guide {
    margin-top: 30px;
    border-top: 1px solid #f0f0f0;
    padding-top: 20px;
}

.qci-data-structure-explanation {
    background: #f9f9f9;
    padding: 15px;
    border-radius: 4px;
}

.qci-relationship-list {
    margin-left: 20px;
}

.qci-relationship-list li {
    margin-bottom: 5px;
}

.qci-example-section {
    margin-top: 15px;
    border-top: 1px dashed #e5e5e5;
    padding-top: 15px;
}

.qci-example-code {
    background: #f5f5f5;
    padding: 15px;
    border-radius: 4px;
    overflow-x: auto;
    font-family: monospace;
    font-size: 12px;
    line-height: 1.5;
    margin-top: 10px;
}

.qci-help-note {
    background: #fef8ee;
    border-left: 4px solid #ffb900;
    padding: 10px 15px;
    margin-top: 15px;
}

.qci-help-note .dashicons {
    color: #ffb900;
    margin-right: 5px;
}

.qci-validation-section {
    margin-top: 30px;
    border-top: 1px solid #f0f0f0;
    padding-top: 20px;
}

.qci-validation-status-container {
    min-height: 50px;
    margin-bottom: 15px;
}

.qci-validation-actions {
    margin-bottom: 15px;
}

.required {
    color: #d63638;
    margin-left: 2px;
}

.qci-actions {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #f0f0f0;
    display: flex;
    justify-content: space-between;
}

@media screen and (max-width: 782px) {
    .qci-auto-mapping-tools {
        flex-direction: column;
    }
    
    .qci-auto-mapping-tools .button {
        width: 100%;
        text-align: center;
    }
    
    .qci-field-mapping-table th,
    .qci-field-mapping-table td {
        padding: 8px 10px;
    }
    
    .column-file-field,
    .column-system-field,
    .column-sample-data,
    .column-field-info {
        width: auto;
    }
    
    .qci-field-info-tooltip {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        margin-left: 0;
        width: 80%;
        max-width: none;
    }
    
    .qci-actions {
        flex-direction: column;
        gap: 10px;
    }
    
    .qci-actions button {
        width: 100%;
    }
}
</style>

<?php
/**
* Helper method to output the required field marker.
*
* @param string $field_name The field name to check if required.
*/
private function required_mark($field_name) {
   $required_fields = array(
       'id', 'title', 
       'quiz_course_reference',
       'question_quiz_reference', 'question_type',
       'answer_question_reference', 'answer_correct'
   );
   
   if (in_array($field_name, $required_fields)) {
       echo '<span class="required">*</span>';
   }
}

/**
* Helper method to get the suggested field mapping.
*
* @param string $field_name The file field name.
* @return string The suggested system field.
*/
private function get_suggested_mapping($field_name) {
   $field_name_lower = strtolower($field_name);
   
   // Common field name patterns
   $mapping_patterns = array(
       'id' => array('id', 'reference', 'key', 'identifier', 'ref'),
       'title' => array('title', 'name', 'heading'),
       'description' => array('desc', 'description', 'content', 'summary'),
       'status' => array('status', 'state', 'published'),
       'ordering' => array('order', 'ordering', 'sequence', 'position', 'sort'),
       'image' => array('image', 'img', 'picture', 'photo', 'featured_image'),
       
       // Course fields
       'course_author_id' => array('course_author', 'course_creator'),
       'course_category_ids' => array('course_category', 'course_categories', 'course_cat'),
       'course_date_created' => array('course_date', 'course_created'),
       'course_options' => array('course_options', 'course_settings', 'course_config'),
       
       // Quiz fields
       'quiz_course_reference' => array('quiz_course', 'quiz_course_id', 'quiz_course_ref'),
       'quiz_category_id' => array('quiz_category', 'quiz_cat'),
       'quiz_published' => array('quiz_published', 'quiz_active', 'quiz_status'),
       'quiz_options' => array('quiz_options', 'quiz_settings', 'quiz_config'),
       
       // Question fields
       'question_quiz_reference' => array('question_quiz', 'question_quiz_id', 'question_quiz_ref'),
       'question_type' => array('question_type', 'qtype', 'q_type'),
       'question_category_id' => array('question_category', 'question_cat'),
       'question_hint' => array('hint', 'clue', 'tip', 'help'),
       'question_explanation' => array('explanation', 'solution', 'rationale'),
       'question_weight' => array('question_weight', 'question_points', 'question_score'),
       'question_options' => array('question_options', 'question_settings', 'question_config'),
       
       // Answer fields
       'answer_question_reference' => array('answer_question', 'answer_question_id', 'answer_question_ref'),
       'answer_correct' => array('correct', 'is_correct', 'iscorrect', 'right'),
       'answer_weight' => array('answer_weight', 'answer_points', 'answer_score'),
       'answer_ordering' => array('answer_order', 'answer_position', 'answer_sort'),
       'answer_options' => array('answer_options', 'answer_settings', 'answer_config')
   );
   
   // Check record_type specifically
   if ($field_name_lower === 'record_type' || $field_name_lower === 'type' || $field_name_lower === 'entity_type') {
       return 'record_type';
   }
   
   // Check for matches in mapping patterns
   foreach ($mapping_patterns as $mapping => $patterns) {
       foreach ($patterns as $pattern) {
           if ($field_name_lower === $pattern || strpos($field_name_lower, $pattern) !== false) {
               return $mapping;
           }
       }
   }
   
   return '';
}

/**
* Helper method to get the field description.
*
* @param string $field_name The field name.
* @return string The field description.
*/
private function get_field_description($field_name) {
   $field_name_lower = strtolower($field_name);
   
   $field_descriptions = array(
       'record_type' => __('Identifies the type of record (course, quiz, question, or answer).', 'quizcourse-importer'),
       'id' => __('Unique identifier used for referencing this item.', 'quizcourse-importer'),
       'title' => __('The name or title of the item.', 'quizcourse-importer'),
       'description' => __('Detailed description or content of the item.', 'quizcourse-importer'),
       'status' => __('Publication status (publish, draft, or 1/0).', 'quizcourse-importer'),
       'ordering' => __('Display order/position of the item.', 'quizcourse-importer'),
       'image' => __('URL to the featured image.', 'quizcourse-importer'),
       
       // Course fields
       'course_author_id' => __('WordPress user ID of the course author.', 'quizcourse-importer'),
       'course_category_ids' => __('Comma-separated IDs of course categories.', 'quizcourse-importer'),
       'course_date_created' => __('Creation date in YYYY-MM-DD format.', 'quizcourse-importer'),
       'course_options' => __('JSON-encoded options for the course.', 'quizcourse-importer'),
       
       // Quiz fields
       'quiz_course_reference' => __('Reference to the course this quiz belongs to.', 'quizcourse-importer'),
       'quiz_category_id' => __('Category ID for the quiz.', 'quizcourse-importer'),
       'quiz_published' => __('Whether the quiz is published (1) or not (0).', 'quizcourse-importer'),
       'quiz_options' => __('JSON-encoded options for the quiz.', 'quizcourse-importer'),
       
       // Question fields
       'question_quiz_reference' => __('Reference to the quiz this question belongs to.', 'quizcourse-importer'),
       'question_type' => __('Type of question (multiple_choice, true_false, etc.).', 'quizcourse-importer'),
       'question_category_id' => __('Category ID for the question.', 'quizcourse-importer'),
       'question_tag_id' => __('Tag ID for the question.', 'quizcourse-importer'),
       'question_hint' => __('Hint text shown to help answer the question.', 'quizcourse-importer'),
       'question_explanation' => __('Explanation shown after answering.', 'quizcourse-importer'),
       'question_weight' => __('Points value for this question.', 'quizcourse-importer'),
       'question_options' => __('JSON-encoded options for the question.', 'quizcourse-importer'),
       
       // Answer fields
       'answer_question_reference' => __('Reference to the question this answer belongs to.', 'quizcourse-importer'),
       'answer_correct' => __('Whether this is the correct answer (1) or not (0).', 'quizcourse-importer'),
       'answer_weight' => __('Points value for selecting this answer.', 'quizcourse-importer'),
       'answer_ordering' => __('Display order for this answer.', 'quizcourse-importer'),
       'answer_options' => __('JSON-encoded options for the answer.', 'quizcourse-importer')
   );
   
   // If there's a specific description for this field name
   foreach ($field_descriptions as $key => $description) {
       if ($field_name_lower === $key || strpos($field_name_lower, $key) !== false) {
           return $description;
       }
   }
   
   // Default description
   return __('This field can be mapped to an appropriate system field.', 'quizcourse-importer');
}
?>

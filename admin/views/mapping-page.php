<?php
/**
 * Template for field mapping interface.
 * 
 * This page displays after file upload and validation,
 * allowing users to map their spreadsheet fields to database fields.
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
                    <li><?php _e('Reference fields connect different parts of your data (e.g., connecting questions to quizzes).', 'quizcourse-importer'); ?></li>
                    <li><?php _e('If you don\'t need a field, select "--Skip this field--".', 'quizcourse-importer'); ?></li>
                    <li><?php _e('Hover over field names for additional information.', 'quizcourse-importer'); ?></li>
                    <li><?php _e('We\'ve pre-selected mappings based on your column names, but you can change them.', 'quizcourse-importer'); ?></li>
                </ul>
            </div>
        </div>
        
        <form id="qci-mapping-form" method="post" action="<?php echo esc_url(admin_url('admin.php?page=quizcourse-importer&step=import')); ?>">
            <input type="hidden" name="qci_file_id" value="<?php echo esc_attr($file_id); ?>">
            <input type="hidden" name="qci_file_type" value="<?php echo esc_attr($file_type); ?>">
            <input type="hidden" name="qci_action" value="process_import">
            <?php wp_nonce_field('qci_import_data', 'qci_nonce'); ?>
            
            <?php if ($file_type === 'csv'): ?>
                <!-- CSV Mapping Interface -->
                <div class="qci-field-mapping-section">
                    <h3><?php _e('CSV Field Mapping', 'quizcourse-importer'); ?></h3>
                    
                    <div class="qci-field-mapping-table">
                        <table class="widefat striped">
                            <thead>
                                <tr>
                                    <th class="column-file-field"><?php _e('File Field', 'quizcourse-importer'); ?></th>
                                    <th class="column-system-field"><?php _e('System Field', 'quizcourse-importer'); ?></th>
                                    <th class="column-sample-data"><?php _e('Sample Data', 'quizcourse-importer'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mapping_data as $field_name): ?>
                                    <tr>
                                        <td class="column-file-field">
                                            <?php echo esc_html($field_name); ?>
                                        </td>
                                        <td class="column-system-field">
                                            <select name="qci_mapping[<?php echo esc_attr($field_name); ?>]" class="qci-mapping-select">
                                                <option value=""><?php _e('-- Skip this field --', 'quizcourse-importer'); ?></option>
                                                
                                                <!-- Course Fields -->
                                                <optgroup label="<?php _e('Course Fields', 'quizcourse-importer'); ?>">
                                                    <option value="course|title" <?php selected($this->get_suggested_mapping($field_name), 'course|title'); ?>><?php _e('Course Title', 'quizcourse-importer'); ?> <?php $this->required_mark('course|title'); ?></option>
                                                    <option value="course|description" <?php selected($this->get_suggested_mapping($field_name), 'course|description'); ?>><?php _e('Course Description', 'quizcourse-importer'); ?></option>
                                                    <option value="course|image" <?php selected($this->get_suggested_mapping($field_name), 'course|image'); ?>><?php _e('Course Image URL', 'quizcourse-importer'); ?></option>
                                                    <option value="course|status" <?php selected($this->get_suggested_mapping($field_name), 'course|status'); ?>><?php _e('Course Status (publish/draft)', 'quizcourse-importer'); ?></option>
                                                    <option value="course|order" <?php selected($this->get_suggested_mapping($field_name), 'course|order'); ?>><?php _e('Course Display Order', 'quizcourse-importer'); ?></option>
                                                    <option value="course|id" <?php selected($this->get_suggested_mapping($field_name), 'course|id'); ?>><?php _e('Course ID/Reference Key', 'quizcourse-importer'); ?> <?php $this->required_mark('course|id'); ?></option>
                                                </optgroup>
                                                
                                                <!-- Section Fields -->
                                                <optgroup label="<?php _e('Section Fields', 'quizcourse-importer'); ?>">
                                                    <option value="section|title" <?php selected($this->get_suggested_mapping($field_name), 'section|title'); ?>><?php _e('Section Title', 'quizcourse-importer'); ?> <?php $this->required_mark('section|title'); ?></option>
                                                    <option value="section|description" <?php selected($this->get_suggested_mapping($field_name), 'section|description'); ?>><?php _e('Section Description', 'quizcourse-importer'); ?></option>
                                                    <option value="section|course_ref" <?php selected($this->get_suggested_mapping($field_name), 'section|course_ref'); ?>><?php _e('Section\'s Course Reference', 'quizcourse-importer'); ?> <?php $this->required_mark('section|course_ref'); ?></option>
                                                    <option value="section|order" <?php selected($this->get_suggested_mapping($field_name), 'section|order'); ?>><?php _e('Section Display Order', 'quizcourse-importer'); ?></option>
                                                    <option value="section|id" <?php selected($this->get_suggested_mapping($field_name), 'section|id'); ?>><?php _e('Section ID/Reference Key', 'quizcourse-importer'); ?> <?php $this->required_mark('section|id'); ?></option>
                                                </optgroup>
                                                
                                                <!-- Quiz Fields -->
                                                <optgroup label="<?php _e('Quiz Fields', 'quizcourse-importer'); ?>">
                                                    <option value="quiz|title" <?php selected($this->get_suggested_mapping($field_name), 'quiz|title'); ?>><?php _e('Quiz Title', 'quizcourse-importer'); ?> <?php $this->required_mark('quiz|title'); ?></option>
                                                    <option value="quiz|description" <?php selected($this->get_suggested_mapping($field_name), 'quiz|description'); ?>><?php _e('Quiz Description', 'quizcourse-importer'); ?></option>
                                                    <option value="quiz|section_ref" <?php selected($this->get_suggested_mapping($field_name), 'quiz|section_ref'); ?>><?php _e('Quiz\'s Section Reference', 'quizcourse-importer'); ?> <?php $this->required_mark('quiz|section_ref'); ?></option>
                                                    <option value="quiz|image" <?php selected($this->get_suggested_mapping($field_name), 'quiz|image'); ?>><?php _e('Quiz Image URL', 'quizcourse-importer'); ?></option>
                                                    <option value="quiz|category" <?php selected($this->get_suggested_mapping($field_name), 'quiz|category'); ?>><?php _e('Quiz Category', 'quizcourse-importer'); ?></option>
                                                    <option value="quiz|status" <?php selected($this->get_suggested_mapping($field_name), 'quiz|status'); ?>><?php _e('Quiz Status (1=active, 0=inactive)', 'quizcourse-importer'); ?></option>
                                                    <option value="quiz|order" <?php selected($this->get_suggested_mapping($field_name), 'quiz|order'); ?>><?php _e('Quiz Display Order', 'quizcourse-importer'); ?></option>
                                                    <option value="quiz|id" <?php selected($this->get_suggested_mapping($field_name), 'quiz|id'); ?>><?php _e('Quiz ID/Reference Key', 'quizcourse-importer'); ?> <?php $this->required_mark('quiz|id'); ?></option>
                                                </optgroup>
                                                
                                                <!-- Question Fields -->
                                                <optgroup label="<?php _e('Question Fields', 'quizcourse-importer'); ?>">
                                                    <option value="question|text" <?php selected($this->get_suggested_mapping($field_name), 'question|text'); ?>><?php _e('Question Text', 'quizcourse-importer'); ?> <?php $this->required_mark('question|text'); ?></option>
                                                    <option value="question|title" <?php selected($this->get_suggested_mapping($field_name), 'question|title'); ?>><?php _e('Question Title/Heading', 'quizcourse-importer'); ?></option>
                                                    <option value="question|quiz_ref" <?php selected($this->get_suggested_mapping($field_name), 'question|quiz_ref'); ?>><?php _e('Question\'s Quiz Reference', 'quizcourse-importer'); ?> <?php $this->required_mark('question|quiz_ref'); ?></option>
                                                    <option value="question|type" <?php selected($this->get_suggested_mapping($field_name), 'question|type'); ?>><?php _e('Question Type', 'quizcourse-importer'); ?></option>
                                                    <option value="question|image" <?php selected($this->get_suggested_mapping($field_name), 'question|image'); ?>><?php _e('Question Image URL', 'quizcourse-importer'); ?></option>
                                                    <option value="question|hint" <?php selected($this->get_suggested_mapping($field_name), 'question|hint'); ?>><?php _e('Question Hint', 'quizcourse-importer'); ?></option>
                                                    <option value="question|explanation" <?php selected($this->get_suggested_mapping($field_name), 'question|explanation'); ?>><?php _e('Question Explanation', 'quizcourse-importer'); ?></option>
                                                    <option value="question|category" <?php selected($this->get_suggested_mapping($field_name), 'question|category'); ?>><?php _e('Question Category', 'quizcourse-importer'); ?></option>
                                                    <option value="question|tag" <?php selected($this->get_suggested_mapping($field_name), 'question|tag'); ?>><?php _e('Question Tag', 'quizcourse-importer'); ?></option>
                                                    <option value="question|weight" <?php selected($this->get_suggested_mapping($field_name), 'question|weight'); ?>><?php _e('Question Weight/Points', 'quizcourse-importer'); ?></option>
                                                    <option value="question|order" <?php selected($this->get_suggested_mapping($field_name), 'question|order'); ?>><?php _e('Question Display Order', 'quizcourse-importer'); ?></option>
                                                    <option value="question|id" <?php selected($this->get_suggested_mapping($field_name), 'question|id'); ?>><?php _e('Question ID/Reference Key', 'quizcourse-importer'); ?> <?php $this->required_mark('question|id'); ?></option>
                                                </optgroup>
                                                
                                                <!-- Answer Fields -->
                                                <optgroup label="<?php _e('Answer Fields', 'quizcourse-importer'); ?>">
                                                    <option value="answer|text" <?php selected($this->get_suggested_mapping($field_name), 'answer|text'); ?>><?php _e('Answer Text', 'quizcourse-importer'); ?> <?php $this->required_mark('answer|text'); ?></option>
                                                    <option value="answer|question_ref" <?php selected($this->get_suggested_mapping($field_name), 'answer|question_ref'); ?>><?php _e('Answer\'s Question Reference', 'quizcourse-importer'); ?> <?php $this->required_mark('answer|question_ref'); ?></option>
                                                    <option value="answer|is_correct" <?php selected($this->get_suggested_mapping($field_name), 'answer|is_correct'); ?>><?php _e('Is Correct Answer (1=yes, 0=no)', 'quizcourse-importer'); ?> <?php $this->required_mark('answer|is_correct'); ?></option>
                                                    <option value="answer|image" <?php selected($this->get_suggested_mapping($field_name), 'answer|image'); ?>><?php _e('Answer Image URL', 'quizcourse-importer'); ?></option>
                                                    <option value="answer|weight" <?php selected($this->get_suggested_mapping($field_name), 'answer|weight'); ?>><?php _e('Answer Weight/Points', 'quizcourse-importer'); ?></option>
                                                    <option value="answer|order" <?php selected($this->get_suggested_mapping($field_name), 'answer|order'); ?>><?php _e('Answer Display Order', 'quizcourse-importer'); ?></option>
                                                </optgroup>
                                            </select>
                                        </td>
                                        <td class="column-sample-data">
                                            <?php if (isset($preview_data[0][$field_name])): ?>
                                                <code><?php echo esc_html($preview_data[0][$field_name]); ?></code>
                                            <?php else: ?>
                                                <em><?php _e('No data', 'quizcourse-importer'); ?></em>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
            <?php else: ?>
                <!-- Excel Mapping Interface -->
                <div class="qci-excel-tabs">
                    <ul class="qci-tabs-nav">
                        <?php foreach ($mapping_data as $sheet_name => $sheet_fields): ?>
                            <li>
                                <a href="#sheet-<?php echo esc_attr(sanitize_title($sheet_name)); ?>" class="<?php echo ($sheet_name === 'Courses') ? 'active' : ''; ?>">
                                    <?php echo esc_html($sheet_name); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <?php foreach ($mapping_data as $sheet_name => $sheet_fields): ?>
                        <div id="sheet-<?php echo esc_attr(sanitize_title($sheet_name)); ?>" class="qci-tab-content" <?php echo ($sheet_name !== 'Courses') ? 'style="display:none;"' : ''; ?>>
                            <h3><?php echo esc_html(sprintf(__('%s Sheet Mapping', 'quizcourse-importer'), $sheet_name)); ?></h3>
                            
                            <div class="qci-field-mapping-table">
                                <table class="widefat striped">
                                    <thead>
                                        <tr>
                                            <th class="column-file-field"><?php _e('File Field', 'quizcourse-importer'); ?></th>
                                            <th class="column-system-field"><?php _e('System Field', 'quizcourse-importer'); ?></th>
                                            <th class="column-sample-data"><?php _e('Sample Data', 'quizcourse-importer'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($sheet_fields as $field_name): ?>
                                            <tr>
                                                <td class="column-file-field">
                                                    <?php echo esc_html($field_name); ?>
                                                </td>
                                                <td class="column-system-field">
                                                    <select name="qci_mapping[<?php echo esc_attr($sheet_name); ?>][<?php echo esc_attr($field_name); ?>]" class="qci-mapping-select">
                                                        <option value=""><?php _e('-- Skip this field --', 'quizcourse-importer'); ?></option>
                                                        
                                                        <?php if ($sheet_name === 'Courses'): ?>
                                                            <option value="title" <?php selected($this->get_excel_suggested_mapping($sheet_name, $field_name), 'title'); ?>><?php _e('Course Title', 'quizcourse-importer'); ?> <?php $this->required_mark('title'); ?></option>
                                                            <option value="description" <?php selected($this->get_excel_suggested_mapping($sheet_name, $field_name), 'description'); ?>><?php _e('Course Description', 'quizcourse-importer'); ?></option>
                                                            <option value="image" <?php selected($this->get_excel_suggested_mapping($sheet_name, $field_name), 'image'); ?>><?php _e('Course Image URL', 'quizcourse-importer'); ?></option>
                                                            <option value="status" <?php selected($this->get_excel_suggested_mapping($sheet_name, $field_name), 'status'); ?>><?php _e('Course Status (publish/draft)', 'quizcourse-importer'); ?></option>
                                                            <option value="order" <?php selected($this->get_excel_suggested_mapping($sheet_name, $field_name), 'order'); ?>><?php _e('Course Display Order', 'quizcourse-importer'); ?></option>
                                                            <option value="id" <?php selected($this->get_excel_suggested_mapping($sheet_name, $field_name), 'id'); ?>><?php _e('Course ID/Reference Key', 'quizcourse-importer'); ?> <?php $this->required_mark('id'); ?></option>
                                                        
                                                        <?php elseif ($sheet_name === 'Sections'): ?>
                                                            <option value="title" <?php selected($this->get_excel_suggested_mapping($sheet_name, $field_name), 'title'); ?>><?php _e('Section Title', 'quizcourse-importer'); ?> <?php $this->required_mark('title'); ?></option>
                                                            <option value="description" <?php selected($this->get_excel_suggested_mapping($sheet_name, $field_name), 'description'); ?>><?php _e('Section Description', 'quizcourse-importer'); ?></option>
                                                            <option value="course_ref" <?php selected($this->get_excel_suggested_mapping($sheet_name, $field_name), 'course_ref'); ?>><?php _e('Section\'s Course Reference', 'quizcourse-importer'); ?> <?php $this->required_mark('course_ref'); ?></option>
                                                            <option value="order" <?php selected($this->get_excel_suggested_mapping($sheet_name, $field_name), 'order'); ?>><?php _e('Section Display Order', 'quizcourse-importer'); ?></option>
                                                            <option value="id" <?php selected($this->get_excel_suggested_mapping($sheet_name, $field_name), 'id'); ?>><?php _e('Section ID/Reference Key', 'quizcourse-importer'); ?> <?php $this->required_mark('id'); ?></option>
                                                        
                                                        <?php elseif ($sheet_name === 'Quizzes'): ?>
                                                            <option value="title" <?php selected($this->get_excel_suggested_mapping($sheet_name, $field_name), 'title'); ?>><?php _e('Quiz Title', 'quizcourse-importer'); ?> <?php $this->required_mark('title'); ?></option>
                                                            <option value="description" <?php selected($this->get_excel_suggested_mapping($sheet_name, $field_name), 'description'); ?>><?php _e('Quiz Description', 'quizcourse-importer'); ?></option>
                                                            <option value="section_ref" <?php selected($this->get_excel_suggested_mapping($sheet_name, $field_name), 'section_ref'); ?>><?php _e('Quiz\'s Section Reference', 'quizcourse-importer'); ?> <?php $this->required_mark('section_ref'); ?></option>
                                                            <option value="image" <?php selected($this->get_excel_suggested_mapping($sheet_name, $field_name), 'image'); ?>><?php _e('Quiz Image URL', 'quizcourse-importer'); ?></option>
                                                            <option value="category" <?php selected($this->get_excel_suggested_mapping($sheet_name, $field_name), 'category'); ?>><?php _e('Quiz Category', 'quizcourse-importer'); ?></option>
                                                            <option value="status" <?php selected($this->get_excel_suggested_mapping($sheet_name, $field_name), 'status'); ?>><?php _e('Quiz Status (1=active, 0=inactive)', 'quizcourse-importer'); ?></option>
                                                            <option value="order" <?php selected($this->get_excel_suggested_mapping($sheet_name, $field_name), 'order'); ?>><?php _e('Quiz Display Order', 'quizcourse-importer'); ?></option>
                                                            <option value="id" <?php selected($this->get_excel_suggested_mapping($sheet_name, $field_name), 'id'); ?>><?php _e('Quiz ID/Reference Key', 'quizcourse-importer'); ?> <?php $this->required_mark('id'); ?></option>
                                                        
                                                        <?php elseif ($sheet_name === 'Questions'): ?>
                                                            <option value="text" <?php selected($this->get_excel_suggested_mapping($sheet_name, $field_name), 'text'); ?>><?php _e('Question Text', 'quizcourse-importer'); ?> <?php $this->required_mark('text'); ?></option>
                                                            <option value="title" <?php selected($this->get_excel_suggested_mapping($sheet_name, $field_name), 'title'); ?>><?php _e('Question Title/Heading', 'quizcourse-importer'); ?></option>
                                                            <option value="quiz_ref" <?php selected($this->get_excel_suggested_mapping($sheet_name, $field_name), 'quiz_ref'); ?>><?php _e('Question\'s Quiz Reference', 'quizcourse-importer'); ?> <?php $this->required_mark('quiz_ref'); ?></option>
                                                            <option value="type" <?php selected($this->get_excel_suggested_mapping($sheet_name, $field_name), 'type'); ?>><?php _e('Question Type', 'quizcourse-importer'); ?></option>
                                                            <option value="image" <?php selected($this->get_excel_suggested_mapping($sheet_name, $field_name), 'image'); ?>><?php _e('Question Image URL', 'quizcourse-importer'); ?></option>
                                                            <option value="hint" <?php selected($this->get_excel_suggested_mapping($sheet_name, $field_name), 'hint'); ?>><?php _e('Question Hint', 'quizcourse-importer'); ?></option>
                                                            <option value="explanation" <?php selected($this->get_excel_suggested_mapping($sheet_name, $field_name), 'explanation'); ?>><?php _e('Question Explanation', 'quizcourse-importer'); ?></option>
                                                            <option value="category" <?php selected($this->get_excel_suggested_mapping($sheet_name, $field_name), 'category'); ?>><?php _e('Question Category', 'quizcourse-importer'); ?></option>
                                                            <option value="tag" <?php selected($this->get_excel_suggested_mapping($sheet_name, $field_name), 'tag'); ?>><?php _e('Question Tag', 'quizcourse-importer'); ?></option>
                                                            <option value="weight" <?php selected($this->get_excel_suggested_mapping($sheet_name, $field_name), 'weight'); ?>><?php _e('Question Weight/Points', 'quizcourse-importer'); ?></option>
                                                            <option value="order" <?php selected($this->get_excel_suggested_mapping($sheet_name, $field_name), 'order'); ?>><?php _e('Question Display Order', 'quizcourse-importer'); ?></option>
                                                            <option value="id" <?php selected($this->get_excel_suggested_mapping($sheet_name, $field_name), 'id'); ?>><?php _e('Question ID/Reference Key', 'quizcourse-importer'); ?> <?php $this->required_mark('id'); ?></option>
                                                        
                                                        <?php elseif ($sheet_name === 'Answers'): ?>
                                                            <option value="text" <?php selected($this->get_excel_suggested_mapping($sheet_name, $field_name), 'text'); ?>><?php _e('Answer Text', 'quizcourse-importer'); ?> <?php $this->required_mark('text'); ?></option>
                                                            <option value="question_ref" <?php selected($this->get_excel_suggested_mapping($sheet_name, $field_name), 'question_ref'); ?>><?php _e('Answer\'s Question Reference', 'quizcourse-importer'); ?> <?php $this->required_mark('question_ref'); ?></option>
                                                            <option value="is_correct" <?php selected($this->get_excel_suggested_mapping($sheet_name, $field_name), 'is_correct'); ?>><?php _e('Is Correct Answer (1=yes, 0=no)', 'quizcourse-importer'); ?> <?php $this->required_mark('is_correct'); ?></option>
                                                            <option value="image" <?php selected($this->get_excel_suggested_mapping($sheet_name, $field_name), 'image'); ?>><?php _e('Answer Image URL', 'quizcourse-importer'); ?></option>
                                                            <option value="weight" <?php selected($this->get_excel_suggested_mapping($sheet_name, $field_name), 'weight'); ?>><?php _e('Answer Weight/Points', 'quizcourse-importer'); ?></option>
                                                            <option value="order" <?php selected($this->get_excel_suggested_mapping($sheet_name, $field_name), 'order'); ?>><?php _e('Answer Display Order', 'quizcourse-importer'); ?></option>
                                                        <?php endif; ?>
                                                    </select>
                                                </td>
                                                <td class="column-sample-data">
                                                    <?php if (isset($preview_data[$sheet_name][0][$field_name])): ?>
                                                        <code><?php echo esc_html($preview_data[$sheet_name][0][$field_name]); ?></code>
                                                    <?php else: ?>
                                                        <em><?php _e('No data', 'quizcourse-importer'); ?></em>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="qci-sheet-preview">
                                <h4><?php _e('Data Preview', 'quizcourse-importer'); ?> <span class="qci-toggle-preview"><?php _e('(show/hide)', 'quizcourse-importer'); ?></span></h4>
                                
                                <div class="qci-preview-data" style="display: none;">
                                    <?php if (!empty($preview_data[$sheet_name])): ?>
                                        <table class="widefat striped">
                                            <thead>
                                                <tr>
                                                    <?php foreach ($sheet_fields as $field_name): ?>
                                                        <th><?php echo esc_html($field_name); ?></th>
                                                    <?php endforeach; ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php for ($i = 0; $i < min(3, count($preview_data[$sheet_name])); $i++): ?>
                                                   <tr>
                                                       <?php foreach ($sheet_fields as $field_name): ?>
                                                           <td>
                                                               <?php if (isset($preview_data[$sheet_name][$i][$field_name])): ?>
                                                                   <?php echo esc_html($preview_data[$sheet_name][$i][$field_name]); ?>
                                                               <?php else: ?>
                                                                   <em><?php _e('Empty', 'quizcourse-importer'); ?></em>
                                                               <?php endif; ?>
                                                           </td>
                                                       <?php endforeach; ?>
                                                   </tr>
                                               <?php endfor; ?>
                                           </tbody>
                                       </table>
                                       
                                       <?php if (count($preview_data[$sheet_name]) > 3): ?>
                                           <p class="qci-preview-note"><?php echo sprintf(__('Showing 3 of %d rows.', 'quizcourse-importer'), count($preview_data[$sheet_name])); ?></p>
                                       <?php endif; ?>
                                   
                                   <?php else: ?>
                                       <p class="qci-no-data"><?php _e('No preview data available for this sheet.', 'quizcourse-importer'); ?></p>
                                   <?php endif; ?>
                               </div>
                           </div>
                       </div>
                   <?php endforeach; ?>
               </div>
           <?php endif; ?>
           
           <div class="qci-mapping-reference">
               <h3><?php _e('Data Structure Reference', 'quizcourse-importer'); ?></h3>
               <p><?php _e('Your data should maintain these relationships for successful importing:', 'quizcourse-importer'); ?></p>
               
               <div class="qci-reference-diagram">
                   <img src="<?php echo QCI_PLUGIN_URL; ?>assets/images/data-structure.png" alt="<?php esc_attr_e('Data Structure Diagram', 'quizcourse-importer'); ?>">
               </div>
               
               <div class="qci-reference-explanation">
                   <ul>
                       <li><strong><?php _e('Courses', 'quizcourse-importer'); ?>:</strong> <?php _e('The top level entity that contains sections.', 'quizcourse-importer'); ?></li>
                       <li><strong><?php _e('Sections', 'quizcourse-importer'); ?>:</strong> <?php _e('Must link to a Course via Course Reference field.', 'quizcourse-importer'); ?></li>
                       <li><strong><?php _e('Quizzes', 'quizcourse-importer'); ?>:</strong> <?php _e('Must link to a Section via Section Reference field.', 'quizcourse-importer'); ?></li>
                       <li><strong><?php _e('Questions', 'quizcourse-importer'); ?>:</strong> <?php _e('Must link to a Quiz via Quiz Reference field.', 'quizcourse-importer'); ?></li>
                       <li><strong><?php _e('Answers', 'quizcourse-importer'); ?>:</strong> <?php _e('Must link to a Question via Question Reference field.', 'quizcourse-importer'); ?></li>
                   </ul>
                   
                   <p><strong><?php _e('Required Fields:', 'quizcourse-importer'); ?></strong> <?php _e('Fields marked with ', 'quizcourse-importer'); ?><span class="required">*</span> <?php _e('are required.', 'quizcourse-importer'); ?></p>
                   
                   <p><strong><?php _e('Reference Fields:', 'quizcourse-importer'); ?></strong> <?php _e('These fields connect different parts of your data.', 'quizcourse-importer'); ?></p>
                   <ul>
                       <li><?php _e('Example: If a question has "Quiz23" in its Quiz Reference field, there should be a quiz with "Quiz23" as its ID/Reference Key.', 'quizcourse-importer'); ?></li>
                   </ul>
               </div>
           </div>
           
           <div class="qci-validation-status">
               <!-- This area will be populated with validation results via JavaScript -->
           </div>
           
           <div class="qci-mapping-actions">
               <button type="button" id="qci-validate-mapping" class="button button-secondary"><?php _e('Validate Mapping', 'quizcourse-importer'); ?></button>
               <button type="submit" id="qci-start-import" class="button button-primary" disabled><?php _e('Start Import', 'quizcourse-importer'); ?></button>
               <a href="<?php echo esc_url(admin_url('admin.php?page=quizcourse-importer')); ?>" class="button"><?php _e('Back to Upload', 'quizcourse-importer'); ?></a>
           </div>
       </form>
   </div>
</div>

<script type="text/javascript">
   jQuery(document).ready(function($) {
       // Tab navigation for Excel sheets
       $('.qci-tabs-nav a').on('click', function(e) {
           e.preventDefault();
           
           // Update active tab
           $('.qci-tabs-nav a').removeClass('active');
           $(this).addClass('active');
           
           // Show the corresponding tab content
           var targetId = $(this).attr('href');
           $('.qci-tab-content').hide();
           $(targetId).show();
       });
       
       // Toggle preview data
       $('.qci-toggle-preview').on('click', function() {
           $(this).closest('.qci-sheet-preview').find('.qci-preview-data').slideToggle();
       });
       
       // Smart field detection for select boxes
       $('.qci-mapping-select').each(function() {
           const $select = $(this);
           const fieldName = $select.attr('name').split('[').pop().split(']')[0];
           
           // Auto-select based on field name if not already selected
           if (!$select.val() && fieldName) {
               const lowerFieldName = fieldName.toLowerCase();
               
               // Find the best matching option
               $select.find('option').each(function() {
                   const optionValue = $(this).val();
                   const optionText = $(this).text().toLowerCase();
                   
                   if (optionValue && (lowerFieldName.includes(optionValue) || optionText.includes(lowerFieldName))) {
                       $select.val(optionValue);
                       return false; // Break the loop
                   }
               });
           }
       });
       
       // Validate mapping
       $('#qci-validate-mapping').on('click', function() {
           const $btn = $(this);
           const $form = $btn.closest('form');
           const formData = $form.serialize() + '&action=qci_validate_mapping';
           
           $btn.prop('disabled', true).text('<?php _e('Validating...', 'quizcourse-importer'); ?>');
           
           $.ajax({
               url: ajaxurl,
               type: 'POST',
               data: formData,
               success: function(response) {
                   if (response.success) {
                       // Display success message
                       $('.qci-validation-status').html(
                           '<div class="notice notice-success">' +
                           '<p>' + response.data.message + '</p>' +
                           '</div>'
                       );
                       
                       // Enable import button
                       $('#qci-start-import').prop('disabled', false);
                   } else {
                       // Display error message
                       $('.qci-validation-status').html(
                           '<div class="notice notice-error">' +
                           '<p><strong><?php _e('Validation Failed:', 'quizcourse-importer'); ?></strong></p>' +
                           '<ul class="qci-error-list">' +
                           response.data.errors.map(error => '<li>' + error + '</li>').join('') +
                           '</ul>' +
                           '</div>'
                       );
                   }
               },
               error: function() {
                   $('.qci-validation-status').html(
                       '<div class="notice notice-error">' +
                       '<p><?php _e('An error occurred during validation. Please try again.', 'quizcourse-importer'); ?></p>' +
                       '</div>'
                   );
               },
               complete: function() {
                   $btn.prop('disabled', false).text('<?php _e('Validate Mapping', 'quizcourse-importer'); ?>');
               }
           });
       });
       
       // Show confirmation dialog before starting import
       $('#qci-mapping-form').on('submit', function(e) {
           if (!confirm('<?php _e('Are you sure you want to start importing? This process cannot be undone.', 'quizcourse-importer'); ?>')) {
               e.preventDefault();
           }
       });
   });
</script>

<?php
/**
* Helper method to output the required field marker.
*
* @param string $field_name The field name to check if required.
*/
private function required_mark($field_name) {
   $required_fields = array(
       'course|title', 'course|id', 
       'section|title', 'section|course_ref', 'section|id',
       'quiz|title', 'quiz|section_ref', 'quiz|id',
       'question|text', 'question|quiz_ref', 'question|id',
       'answer|text', 'answer|question_ref', 'answer|is_correct',
       'title', 'id', 'course_ref', 'section_ref', 'quiz_ref', 'question_ref', 'text', 'is_correct'
   );
   
   if (in_array($field_name, $required_fields)) {
       echo '<span class="required">*</span>';
   }
}

/**
* Helper method to get the suggested field mapping for CSV.
*
* @param string $field_name The file field name.
* @return string The suggested system field.
*/
private function get_suggested_mapping($field_name) {
   $field_name_lower = strtolower($field_name);
   
   // Common field name patterns
   $mapping_patterns = array(
       'course|title' => array('coursetitle', 'course_title', 'course name', 'coursename'),
       'course|description' => array('coursedesc', 'course_description', 'course desc'),
       'course|image' => array('courseimage', 'course_image', 'course img', 'courseimg'),
       'course|status' => array('coursestatus', 'course_status'),
       'course|order' => array('courseorder', 'course_order'),
       'course|id' => array('courseid', 'course_id', 'coursereference', 'course_reference', 'courseref'),
       
       'section|title' => array('sectiontitle', 'section_title', 'section name', 'sectionname'),
       'section|description' => array('sectiondesc', 'section_description', 'section desc'),
       'section|course_ref' => array('courseid', 'course_id', 'coursereference', 'course_reference', 'courseref', 'sectioncourseid'),
       'section|order' => array('sectionorder', 'section_order'),
       'section|id' => array('sectionid', 'section_id', 'sectionreference', 'section_reference', 'sectionref'),
       
       'quiz|title' => array('quiztitle', 'quiz_title', 'quiz name', 'quizname'),
       'quiz|description' => array('quizdesc', 'quiz_description', 'quiz desc'),
       'quiz|section_ref' => array('sectionid', 'section_id', 'sectionreference', 'section_reference', 'sectionref', 'quizsectionid'),
       'quiz|image' => array('quizimage', 'quiz_image', 'quiz img', 'quizimg'),
       'quiz|category' => array('quizcategory', 'quiz_category', 'quizcat'),
       'quiz|status' => array('quizstatus', 'quiz_status'),
       'quiz|order' => array('quizorder', 'quiz_order'),
       'quiz|id' => array('quizid', 'quiz_id', 'quizreference', 'quiz_reference', 'quizref'),
       
       'question|text' => array('questiontext', 'question_text', 'question'),
       'question|title' => array('questiontitle', 'question_title', 'questionname'),
       'question|quiz_ref' => array('quizid', 'quiz_id', 'quizreference', 'quiz_reference', 'quizref', 'questionquizid'),
       'question|type' => array('questiontype', 'question_type', 'type'),
       'question|image' => array('questionimage', 'question_image', 'questionimg'),
       'question|hint' => array('questionhint', 'question_hint', 'hint'),
       'question|explanation' => array('questionexplanation', 'question_explanation', 'explanation'),
       'question|category' => array('questioncategory', 'question_category', 'questioncat'),
       'question|tag' => array('questiontag', 'question_tag', 'tag'),
       'question|weight' => array('questionweight', 'question_weight', 'questionpoints', 'question_points'),
       'question|order' => array('questionorder', 'question_order'),
       'question|id' => array('questionid', 'question_id', 'questionreference', 'question_reference', 'questionref'),
       
       'answer|text' => array('answertext', 'answer_text', 'answer'),
       'answer|question_ref' => array('questionid', 'question_id', 'questionreference', 'question_reference', 'questionref', 'answerquestionid'),
       'answer|is_correct' => array('iscorrect', 'is_correct', 'correct', 'rightanswer', 'right_answer'),
       'answer|image' => array('answerimage', 'answer_image', 'answerimg'),
       'answer|weight' => array('answerweight', 'answer_weight', 'answerpoints', 'answer_points'),
       'answer|order' => array('answerorder', 'answer_order')
   );
   
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
* Helper method to get the suggested field mapping for Excel.
*
* @param string $sheet_name The sheet name.
* @param string $field_name The file field name.
* @return string The suggested system field.
*/
private function get_excel_suggested_mapping($sheet_name, $field_name) {
   $field_name_lower = strtolower($field_name);
   
   // Common field name patterns by sheet
   $mapping_patterns = array(
       'Courses' => array(
           'title' => array('title', 'name', 'coursetitle', 'course title', 'course name'),
           'description' => array('description', 'desc', 'content', 'coursedescription', 'course description'),
           'image' => array('image', 'img', 'picture', 'photo', 'featured image', 'featuredimage'),
           'status' => array('status', 'state', 'published'),
           'order' => array('order', 'ordering', 'sequence', 'position', 'sort'),
           'id' => array('id', 'reference', 'key', 'identifier')
       ),
       'Sections' => array(
           'title' => array('title', 'name', 'sectiontitle', 'section title', 'section name'),
           'description' => array('description', 'desc', 'content', 'sectiondescription', 'section description'),
           'course_ref' => array('course', 'courseid', 'course_id', 'course reference', 'coursereference', 'course_reference'),
           'order' => array('order', 'ordering', 'sequence', 'position', 'sort'),
           'id' => array('id', 'reference', 'key', 'identifier')
       ),
       'Quizzes' => array(
           'title' => array('title', 'name', 'quiztitle', 'quiz title', 'quiz name'),
           'description' => array('description', 'desc', 'content', 'quizdescription', 'quiz description'),
           'section_ref' => array('section', 'sectionid', 'section_id', 'section reference', 'sectionreference', 'section_reference'),
           'image' => array('image', 'img', 'picture', 'photo', 'featured image', 'featuredimage'),
           'category' => array('category', 'cat', 'categories'),
           'status' => array('status', 'state', 'published', 'active'),
           'order' => array('order', 'ordering', 'sequence', 'position', 'sort'),
           'id' => array('id', 'reference', 'key', 'identifier')
       ),
       'Questions' => array(
           'text' => array('text', 'content', 'question', 'questiontext', 'question text'),
           'title' => array('title', 'name', 'heading', 'questiontitle', 'question title'),
           'quiz_ref' => array('quiz', 'quizid', 'quiz_id', 'quiz reference', 'quizreference', 'quiz_reference'),
           'type' => array('type', 'questiontype', 'question type', 'format'),
           'image' => array('image', 'img', 'picture', 'photo'),
           'hint' => array('hint', 'clue', 'tip'),
           'explanation' => array('explanation', 'answer explanation', 'solution'),
           'category' => array('category', 'cat', 'categories'),
           'tag' => array('tag', 'tags', 'label'),
           'weight' => array('weight', 'points', 'score', 'value'),
           'order' => array('order', 'ordering', 'sequence', 'position', 'sort'),
           'id' => array('id', 'reference', 'key', 'identifier')
       ),
       'Answers' => array(
           'text' => array('text', 'content', 'answer', 'answertext', 'answer text'),
           'question_ref' => array('question', 'questionid', 'question_id', 'question reference', 'questionreference', 'question_reference'),
           'is_correct' => array('correct', 'iscorrect', 'is correct', 'is_correct', 'right', 'right answer'),
           'image' => array('image', 'img', 'picture', 'photo'),
           'weight' => array('weight', 'points', 'score', 'value'),
           'order' => array('order', 'ordering', 'sequence', 'position', 'sort')
       )
   );
   
   // Check for matches based on sheet name and field name
   if (isset($mapping_patterns[$sheet_name])) {
       foreach ($mapping_patterns[$sheet_name] as $mapping => $patterns) {
           foreach ($patterns as $pattern) {
               if ($field_name_lower === $pattern || strpos($field_name_lower, $pattern) !== false) {
                   return $mapping;
               }
           }
       }
   }
   
   return '';
}

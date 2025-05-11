<?php
/**
 * Help tab content for QuizCourse One-Click Importer
 *
 * @package    QuizCourse_Importer
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="qci-help-tab-content">
    <h2><?php _e('QuizCourse One-Click Importer Help', 'quizcourse-importer'); ?></h2>
    
    <div class="qci-help-section">
        <h3><?php _e('Getting Started', 'quizcourse-importer'); ?></h3>
        <p><?php _e('This plugin allows you to import courses, quizzes, questions, and answers in a single operation, saving you time and reducing manual work.', 'quizcourse-importer'); ?></p>
        <p><?php _e('Follow these steps to import your content:', 'quizcourse-importer'); ?></p>
        <ol>
            <li><?php _e('Prepare your data file (Excel or CSV) using our template', 'quizcourse-importer'); ?></li>
            <li><?php _e('Upload the file through the import interface', 'quizcourse-importer'); ?></li>
            <li><?php _e('Map your file fields to the system fields', 'quizcourse-importer'); ?></li>
            <li><?php _e('Start the import process', 'quizcourse-importer'); ?></li>
        </ol>
    </div>
    
    <div class="qci-help-section">
        <h3><?php _e('Preparing Your Data File', 'quizcourse-importer'); ?></h3>
        <p><?php _e('Your file should contain the following sheets (for Excel) or columns (for CSV):', 'quizcourse-importer'); ?></p>
        
        <h4><?php _e('Courses', 'quizcourse-importer'); ?></h4>
        <ul>
            <li><strong>course_title</strong>: <?php _e('The title of your course (required)', 'quizcourse-importer'); ?></li>
            <li><strong>course_description</strong>: <?php _e('Description or content of the course', 'quizcourse-importer'); ?></li>
            <li><strong>course_image_url</strong>: <?php _e('URL to the course featured image', 'quizcourse-importer'); ?></li>
            <li><strong>course_status</strong>: <?php _e('publish, draft, or pending', 'quizcourse-importer'); ?></li>
            <li><strong>course_ordering</strong>: <?php _e('Numeric value for ordering courses', 'quizcourse-importer'); ?></li>
        </ul>
        
        <h4><?php _e('Sections', 'quizcourse-importer'); ?></h4>
        <ul>
            <li><strong>section_title</strong>: <?php _e('The title of your section (required)', 'quizcourse-importer'); ?></li>
            <li><strong>section_description</strong>: <?php _e('Description of the section', 'quizcourse-importer'); ?></li>
            <li><strong>course_reference</strong>: <?php _e('Reference to the course this section belongs to (required)', 'quizcourse-importer'); ?></li>
            <li><strong>section_order</strong>: <?php _e('Numeric value for ordering sections within the course', 'quizcourse-importer'); ?></li>
        </ul>
        
        <h4><?php _e('Quizzes', 'quizcourse-importer'); ?></h4>
        <ul>
            <li><strong>quiz_title</strong>: <?php _e('The title of your quiz (required)', 'quizcourse-importer'); ?></li>
            <li><strong>quiz_description</strong>: <?php _e('Description of the quiz', 'quizcourse-importer'); ?></li>
            <li><strong>section_reference</strong>: <?php _e('Reference to the section this quiz belongs to (required)', 'quizcourse-importer'); ?></li>
            <li><strong>quiz_image_url</strong>: <?php _e('URL to the quiz featured image', 'quizcourse-importer'); ?></li>
            <li><strong>quiz_category</strong>: <?php _e('Category name for the quiz', 'quizcourse-importer'); ?></li>
            <li><strong>quiz_ordering</strong>: <?php _e('Numeric value for ordering quizzes within the section', 'quizcourse-importer'); ?></li>
            <li><strong>quiz_published</strong>: <?php _e('1 for published, 0 for unpublished', 'quizcourse-importer'); ?></li>
        </ul>
        
        <h4><?php _e('Questions', 'quizcourse-importer'); ?></h4>
        <ul>
            <li><strong>question_text</strong>: <?php _e('The text of your question (required)', 'quizcourse-importer'); ?></li>
            <li><strong>quiz_reference</strong>: <?php _e('Reference to the quiz this question belongs to (required)', 'quizcourse-importer'); ?></li>
            <li><strong>question_type</strong>: <?php _e('Type of question (multiple_choice, true_false, short_answer, etc.)', 'quizcourse-importer'); ?></li>
            <li><strong>question_image_url</strong>: <?php _e('URL to an image for the question', 'quizcourse-importer'); ?></li>
            <li><strong>question_hint</strong>: <?php _e('Hint for the question', 'quizcourse-importer'); ?></li>
            <li><strong>explanation</strong>: <?php _e('Explanation to show after answering', 'quizcourse-importer'); ?></li>
            <li><strong>category_id</strong>: <?php _e('Question category ID', 'quizcourse-importer'); ?></li>
            <li><strong>tag_id</strong>: <?php _e('Question tag ID', 'quizcourse-importer'); ?></li>
            <li><strong>question_ordering</strong>: <?php _e('Numeric value for ordering questions within the quiz', 'quizcourse-importer'); ?></li>
        </ul>
        
        <h4><?php _e('Answers', 'quizcourse-importer'); ?></h4>
        <ul>
            <li><strong>answer_text</strong>: <?php _e('The text of your answer (required)', 'quizcourse-importer'); ?></li>
            <li><strong>question_reference</strong>: <?php _e('Reference to the question this answer belongs to (required)', 'quizcourse-importer'); ?></li>
            <li><strong>is_correct</strong>: <?php _e('1 for correct answer, 0 for incorrect answer', 'quizcourse-importer'); ?></li>
            <li><strong>answer_ordering</strong>: <?php _e('Numeric value for ordering answers within the question', 'quizcourse-importer'); ?></li>
        </ul>
    </div>
    
    <div class="qci-help-section">
        <h3><?php _e('References Between Records', 'quizcourse-importer'); ?></h3>
        <p><?php _e('To establish relationships between your data, use reference fields:', 'quizcourse-importer'); ?></p>
        <ul>
            <li><strong>course_reference</strong>: <?php _e('In the Sections sheet, this connects a section to a course', 'quizcourse-importer'); ?></li>
            <li><strong>section_reference</strong>: <?php _e('In the Quizzes sheet, this connects a quiz to a section', 'quizcourse-importer'); ?></li>
            <li><strong>quiz_reference</strong>: <?php _e('In the Questions sheet, this connects a question to a quiz', 'quizcourse-importer'); ?></li>
            <li><strong>question_reference</strong>: <?php _e('In the Answers sheet, this connects an answer to a question', 'quizcourse-importer'); ?></li>
        </ul>
        <p><strong><?php _e('Important:', 'quizcourse-importer'); ?></strong> <?php _e('Reference values must be consistent across your file. You can use IDs, slugs, or any unique identifier, but they must match between sheets.', 'quizcourse-importer'); ?></p>
        <p><?php _e('Example: If your Courses sheet has a course with ID "course-123", then in your Sections sheet, use "course-123" as the course_reference value to link a section to this course.', 'quizcourse-importer'); ?></p>
    </div>
    
    <div class="qci-help-section">
        <h3><?php _e('CSV File Format', 'quizcourse-importer'); ?></h3>
        <p><?php _e('If using CSV format, your file should include all required fields in a single file. Each row should include identifiers to establish relationships between entities.', 'quizcourse-importer'); ?></p>
        <p><?php _e('Example CSV structure:', 'quizcourse-importer'); ?></p>
        <pre>
entity_type,id,title,description,parent_reference,...
course,course-123,WordPress Mastery,Learn WordPress from scratch,,
section,section-456,Getting Started,Introduction to WordPress,course-123,
quiz,quiz-789,WordPress Basics Quiz,Test your knowledge,section-456,
question,question-101,What is WordPress?,Select the correct definition,quiz-789,
answer,answer-201,A content management system,1,question-101,
answer,answer-202,A social media platform,0,question-101,
        </pre>
        <p><?php _e('The "entity_type" column specifies whether the row is a course, section, quiz, question, or answer.', 'quizcourse-importer'); ?></p>
    </div>
    
    <div class="qci-help-section">
        <h3><?php _e('Field Mapping', 'quizcourse-importer'); ?></h3>
        <p><?php _e('After uploading your file, you\'ll see the Field Mapping screen. This is where you match columns from your file to the appropriate fields in our system.', 'quizcourse-importer'); ?></p>
        <p><?php _e('The importer will attempt to automatically map fields based on column names, but you should review all mappings to ensure they\'re correct.', 'quizcourse-importer'); ?></p>
        <p><?php _e('For fields you don\'t want to import, select "-- Skip this field --" from the dropdown.', 'quizcourse-importer'); ?></p>
    </div>
    
    <div class="qci-help-section">
        <h3><?php _e('Question Types', 'quizcourse-importer'); ?></h3>
        <p><?php _e('Supported question types include:', 'quizcourse-importer'); ?></p>
        <ul>
            <li><strong>multiple_choice</strong>: <?php _e('Multiple choice with one correct answer', 'quizcourse-importer'); ?></li>
            <li><strong>true_false</strong>: <?php _e('True/False questions', 'quizcourse-importer'); ?></li>
            <li><strong>short_answer</strong>: <?php _e('Short text answer', 'quizcourse-importer'); ?></li>
            <li><strong>essay</strong>: <?php _e('Long text answer', 'quizcourse-importer'); ?></li>
            <li><strong>fill_in_blank</strong>: <?php _e('Fill in the blank questions', 'quizcourse-importer'); ?></li>
            <li><strong>matching</strong>: <?php _e('Matching items', 'quizcourse-importer'); ?></li>
        </ul>
        <p><?php _e('The answer structure varies by question type. For multiple-choice and true/false questions, create multiple answer rows for each question, with is_correct=1 for correct answers and is_correct=0 for incorrect options.', 'quizcourse-importer'); ?></p>
    </div>
    
    <div class="qci-help-section">
        <h3><?php _e('Troubleshooting', 'quizcourse-importer'); ?></h3>
        <h4><?php _e('File Upload Issues', 'quizcourse-importer'); ?></h4>
        <ul>
            <li><?php _e('Make sure your file is in CSV, XLSX, or XLS format', 'quizcourse-importer'); ?></li>
            <li><?php _e('Check that your file doesn\'t exceed the maximum upload size', 'quizcourse-importer'); ?></li>
            <li><?php _e('If using Excel, save your file with a simple name without special characters', 'quizcourse-importer'); ?></li>
        </ul>
        
        <h4><?php _e('Data Validation Errors', 'quizcourse-importer'); ?></h4>
        <ul>
            <li><?php _e('Ensure all required fields have values (title, references)', 'quizcourse-importer'); ?></li>
            <li><?php _e('Check that reference values are consistent across your data', 'quizcourse-importer'); ?></li>
            <li><?php _e('Verify that you\'re using valid values for fields like status, is_correct, etc.', 'quizcourse-importer'); ?></li>
        </ul>
        
        <h4><?php _e('Import Failures', 'quizcourse-importer'); ?></h4>
        <ul>
            <li><?php _e('Check the error message for specific details', 'quizcourse-importer'); ?></li>
            <li><?php _e('Ensure your WordPress installation has enough memory and execution time for large imports', 'quizcourse-importer'); ?></li>
            <li><?php _e('Try importing smaller batches of data if you experience timeouts', 'quizcourse-importer'); ?></li>
        </ul>
    </div>
    
    <div class="qci-help-section">
        <h3><?php _e('Tips for Successful Imports', 'quizcourse-importer'); ?></h3>
        <ul>
            <li><?php _e('Start with our template to ensure your file has the correct structure', 'quizcourse-importer'); ?></li>
            <li><?php _e('For large imports, consider breaking them into smaller files', 'quizcourse-importer'); ?></li>
            <li><?php _e('Back up your WordPress database before performing imports', 'quizcourse-importer'); ?></li>
            <li><?php _e('Test the import with a small sample of data first', 'quizcourse-importer'); ?></li>
            <li><?php _e('Use unique, descriptive reference values to make troubleshooting easier', 'quizcourse-importer'); ?></li>
        </ul>
    </div>
    
    <div class="qci-help-section">
        <h3><?php _e('Getting Support', 'quizcourse-importer'); ?></h3>
        <p><?php _e('If you encounter any issues or have questions about using this plugin:', 'quizcourse-importer'); ?></p>
        <ul>
            <li><?php _e('Check the plugin documentation for detailed guides', 'quizcourse-importer'); ?></li>
            <li><?php _e('Visit our support forum for community assistance', 'quizcourse-importer'); ?></li>
            <li><?php _e('Contact our support team directly for premium support', 'quizcourse-importer'); ?></li>
        </ul>
        <p>
            <a href="https://example.com/documentation" target="_blank" class="button"><?php _e('Documentation', 'quizcourse-importer'); ?></a>
            <a href="https://example.com/support" target="_blank" class="button"><?php _e('Support Forum', 'quizcourse-importer'); ?></a>
        </p>
    </div>
</div>

<script type="text/javascript">
    // Smooth scrolling for help section links
    jQuery(document).ready(function($) {
        $('.qci-help-tab-content a[href^="#"]').on('click', function(e) {
            e.preventDefault();
            
            var target = $(this.getAttribute('href'));
            if (target.length) {
                $('html, body').stop().animate({
                    scrollTop: target.offset().top - 50
                }, 500);
            }
        });
    });
</script>

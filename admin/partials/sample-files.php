<?php
/**
 * Sample files partial
 *
 * This file provides information about sample import files and templates
 * with download links for users to understand the expected format.
 *
 * @link       https://yourwebsite.com
 * @since      1.0.0
 *
 * @package    QuizCourse_Importer
 * @subpackage QuizCourse_Importer/admin/partials
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="qci-sample-files-section">
    <h3><?php _e('Sample Files & Templates', 'quizcourse-importer'); ?></h3>
    
    <p>
        <?php _e('Download and use these templates to ensure your data is correctly formatted for import. The import system requires specific column names and data relationships to properly create your courses, quizzes and questions.', 'quizcourse-importer'); ?>
    </p>

    <div class="qci-sample-files-container">
        <div class="qci-sample-file-card">
            <div class="qci-file-icon">
                <span class="dashicons dashicons-media-spreadsheet"></span>
            </div>
            <div class="qci-file-info">
                <h4><?php _e('Excel Template (Recommended)', 'quizcourse-importer'); ?></h4>
                <p><?php _e('Use this Excel template with multiple sheets for courses, sections, quizzes, questions, and answers.', 'quizcourse-importer'); ?></p>
                <a href="<?php echo esc_url(QCI_PLUGIN_URL . 'templates/quizcourse-import-template.xlsx'); ?>" class="button button-primary" download>
                    <span class="dashicons dashicons-download"></span>
                    <?php _e('Download Excel Template', 'quizcourse-importer'); ?>
                </a>
            </div>
        </div>

        <div class="qci-sample-file-card">
            <div class="qci-file-icon">
                <span class="dashicons dashicons-media-text"></span>
            </div>
            <div class="qci-file-info">
                <h4><?php _e('CSV Template', 'quizcourse-importer'); ?></h4>
                <p><?php _e('A single CSV file template that includes all required fields in one sheet.', 'quizcourse-importer'); ?></p>
                <a href="<?php echo esc_url(QCI_PLUGIN_URL . 'templates/quizcourse-import-template.csv'); ?>" class="button button-primary" download>
                    <span class="dashicons dashicons-download"></span>
                    <?php _e('Download CSV Template', 'quizcourse-importer'); ?>
                </a>
            </div>
        </div>

        <div class="qci-sample-file-card">
            <div class="qci-file-icon">
                <span class="dashicons dashicons-media-spreadsheet"></span>
            </div>
            <div class="qci-file-info">
                <h4><?php _e('Sample Data Excel', 'quizcourse-importer'); ?></h4>
                <p><?php _e('Example Excel file with sample course data to demonstrate the correct format.', 'quizcourse-importer'); ?></p>
                <a href="<?php echo esc_url(QCI_PLUGIN_URL . 'templates/quizcourse-sample-data.xlsx'); ?>" class="button button-secondary" download>
                    <span class="dashicons dashicons-download"></span>
                    <?php _e('Download Sample Data', 'quizcourse-importer'); ?>
                </a>
            </div>
        </div>
    </div>

    <div class="qci-file-structure">
        <h3><?php _e('Template Structure Guide', 'quizcourse-importer'); ?></h3>
        
        <div class="qci-tabs">
            <ul class="qci-tabs-nav">
                <li class="active"><a href="#qci-excel-guide"><?php _e('Excel Format', 'quizcourse-importer'); ?></a></li>
                <li><a href="#qci-csv-guide"><?php _e('CSV Format', 'quizcourse-importer'); ?></a></li>
                <li><a href="#qci-references-guide"><?php _e('References Guide', 'quizcourse-importer'); ?></a></li>
            </ul>
            
            <div id="qci-excel-guide" class="qci-tab-content active">
                <h4><?php _e('Excel File Structure', 'quizcourse-importer'); ?></h4>
                <p><?php _e('Your Excel file should contain the following sheets:', 'quizcourse-importer'); ?></p>
                
                <div class="qci-sheet-info">
                    <h5><?php _e('1. Courses Sheet', 'quizcourse-importer'); ?></h5>
                    <p><?php _e('This sheet contains the main course information.', 'quizcourse-importer'); ?></p>
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th><?php _e('Column', 'quizcourse-importer'); ?></th>
                                <th><?php _e('Description', 'quizcourse-importer'); ?></th>
                                <th><?php _e('Required', 'quizcourse-importer'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>course_id</code></td>
                                <td><?php _e('Unique identifier for the course (for reference only)', 'quizcourse-importer'); ?></td>
                                <td><?php _e('Yes', 'quizcourse-importer'); ?></td>
                            </tr>
                            <tr>
                                <td><code>course_title</code></td>
                                <td><?php _e('The title of the course', 'quizcourse-importer'); ?></td>
                                <td><?php _e('Yes', 'quizcourse-importer'); ?></td>
                            </tr>
                            <tr>
                                <td><code>course_description</code></td>
                                <td><?php _e('The description of the course', 'quizcourse-importer'); ?></td>
                                <td><?php _e('Yes', 'quizcourse-importer'); ?></td>
                            </tr>
                            <tr>
                                <td><code>course_image_url</code></td>
                                <td><?php _e('URL to the course image', 'quizcourse-importer'); ?></td>
                                <td><?php _e('No', 'quizcourse-importer'); ?></td>
                            </tr>
                            <tr>
                                <td><code>course_status</code></td>
                                <td><?php _e('Status: "publish", "draft", or "pending"', 'quizcourse-importer'); ?></td>
                                <td><?php _e('No', 'quizcourse-importer'); ?></td>
                            </tr>
                            <tr>
                                <td><code>course_order</code></td>
                                <td><?php _e('Display order (numeric)', 'quizcourse-importer'); ?></td>
                                <td><?php _e('No', 'quizcourse-importer'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <h5><?php _e('2. Sections Sheet', 'quizcourse-importer'); ?></h5>
                    <p><?php _e('This sheet contains sections that organize the course content.', 'quizcourse-importer'); ?></p>
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th><?php _e('Column', 'quizcourse-importer'); ?></th>
                                <th><?php _e('Description', 'quizcourse-importer'); ?></th>
                                <th><?php _e('Required', 'quizcourse-importer'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>section_id</code></td>
                                <td><?php _e('Unique identifier for the section (for reference only)', 'quizcourse-importer'); ?></td>
                                <td><?php _e('Yes', 'quizcourse-importer'); ?></td>
                            </tr>
                            <tr>
                                <td><code>section_title</code></td>
                                <td><?php _e('The title of the section', 'quizcourse-importer'); ?></td>
                                <td><?php _e('Yes', 'quizcourse-importer'); ?></td>
                            </tr>
                            <tr>
                                <td><code>section_description</code></td>
                                <td><?php _e('The description of the section', 'quizcourse-importer'); ?></td>
                                <td><?php _e('No', 'quizcourse-importer'); ?></td>
                            </tr>
                            <tr>
                                <td><code>course_reference</code></td>
                                <td><?php _e('Reference to course_id in Courses sheet', 'quizcourse-importer'); ?></td>
                                <td><?php _e('Yes', 'quizcourse-importer'); ?></td>
                            </tr>
                            <tr>
                                <td><code>section_order</code></td>
                                <td><?php _e('Display order within the course (numeric)', 'quizcourse-importer'); ?></td>
                                <td><?php _e('No', 'quizcourse-importer'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <h5><?php _e('3. Quizzes Sheet', 'quizcourse-importer'); ?></h5>
                    <p><?php _e('This sheet contains the quizzes for each section.', 'quizcourse-importer'); ?></p>
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th><?php _e('Column', 'quizcourse-importer'); ?></th>
                                <th><?php _e('Description', 'quizcourse-importer'); ?></th>
                                <th><?php _e('Required', 'quizcourse-importer'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>quiz_id</code></td>
                                <td><?php _e('Unique identifier for the quiz (for reference only)', 'quizcourse-importer'); ?></td>
                                <td><?php _e('Yes', 'quizcourse-importer'); ?></td>
                            </tr>
                            <tr>
                                <td><code>quiz_title</code></td>
                                <td><?php _e('The title of the quiz', 'quizcourse-importer'); ?></td>
                                <td><?php _e('Yes', 'quizcourse-importer'); ?></td>
                            </tr>
                            <tr>
                                <td><code>quiz_description</code></td>
                                <td><?php _e('The description of the quiz', 'quizcourse-importer'); ?></td>
                                <td><?php _e('No', 'quizcourse-importer'); ?></td>
                            </tr>
                            <tr>
                                <td><code>section_reference</code></td>
                                <td><?php _e('Reference to section_id in Sections sheet', 'quizcourse-importer'); ?></td>
                                <td><?php _e('Yes', 'quizcourse-importer'); ?></td>
                            </tr>
                            <tr>
                                <td><code>quiz_category</code></td>
                                <td><?php _e('Category name for the quiz', 'quizcourse-importer'); ?></td>
                                <td><?php _e('No', 'quizcourse-importer'); ?></td>
                            </tr>
                            <tr>
                                <td><code>quiz_image_url</code></td>
                                <td><?php _e('URL to the quiz image', 'quizcourse-importer'); ?></td>
                                <td><?php _e('No', 'quizcourse-importer'); ?></td>
                            </tr>
                            <tr>
                                <td><code>quiz_published</code></td>
                                <td><?php _e('Published status: "1" for published, "0" for unpublished', 'quizcourse-importer'); ?></td>
                                <td><?php _e('No', 'quizcourse-importer'); ?></td>
                            </tr>
                            <tr>
                                <td><code>quiz_order</code></td>
                                <td><?php _e('Display order within the section (numeric)', 'quizcourse-importer'); ?></td>
                                <td><?php _e('No', 'quizcourse-importer'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <h5><?php _e('4. Questions Sheet', 'quizcourse-importer'); ?></h5>
                    <p><?php _e('This sheet contains the questions for each quiz.', 'quizcourse-importer'); ?></p>
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th><?php _e('Column', 'quizcourse-importer'); ?></th>
                                <th><?php _e('Description', 'quizcourse-importer'); ?></th>
                                <th><?php _e('Required', 'quizcourse-importer'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>question_id</code></td>
                                <td><?php _e('Unique identifier for the question (for reference only)', 'quizcourse-importer'); ?></td>
                                <td><?php _e('Yes', 'quizcourse-importer'); ?></td>
                            </tr>
                            <tr>
                                <td><code>question_text</code></td>
                                <td><?php _e('The text of the question', 'quizcourse-importer'); ?></td>
                                <td><?php _e('Yes', 'quizcourse-importer'); ?></td>
                            </tr>
                            <tr>
                                <td><code>question_title</code></td>
                                <td><?php _e('Optional title for the question', 'quizcourse-importer'); ?></td>
                                <td><?php _e('No', 'quizcourse-importer'); ?></td>
                            </tr>
                            <tr>
                                <td><code>quiz_reference</code></td>
                                <td><?php _e('Reference to quiz_id in Quizzes sheet', 'quizcourse-importer'); ?></td>
                                <td><?php _e('Yes', 'quizcourse-importer'); ?></td>
                            </tr>
                            <tr>
                                <td><code>question_type</code></td>
                                <td><?php _e('Type: "multiple_choice", "true_false", "short_answer", etc.', 'quizcourse-importer'); ?></td>
                                <td><?php _e('Yes', 'quizcourse-importer'); ?></td>
                            </tr>
                            <tr>
                                <td><code>question_image_url</code></td>
                                <td><?php _e('URL to the question image', 'quizcourse-importer'); ?></td>
                                <td><?php _e('No', 'quizcourse-importer'); ?></td>
                            </tr>
                            <tr>
                                <td><code>question_hint</code></td>
                                <td><?php _e('Hint text for the question', 'quizcourse-importer'); ?></td>
                                <td><?php _e('No', 'quizcourse-importer'); ?></td>
                            </tr>
                            <tr>
                                <td><code>explanation</code></td>
                                <td><?php _e('Explanation for the correct answer', 'quizcourse-importer'); ?></td>
                                <td><?php _e('No', 'quizcourse-importer'); ?></td>
                            </tr>
                            <tr>
                                <td><code>category_id</code></td>
                                <td><?php _e('Question category ID or name', 'quizcourse-importer'); ?></td>
                                <td><?php _e('No', 'quizcourse-importer'); ?></td>
                            </tr>
                            <tr>
                                <td><code>tag_id</code></td>
                                <td><?php _e('Question tag ID or name', 'quizcourse-importer'); ?></td>
                                <td><?php _e('No', 'quizcourse-importer'); ?></td>
                            </tr>
                            <tr>
                                <td><code>weight</code></td>
                                <td><?php _e('Question weight/points (numeric)', 'quizcourse-importer'); ?></td>
                                <td><?php _e('No', 'quizcourse-importer'); ?></td>
                            </tr>
                            <tr>
                                <td><code>question_order</code></td>
                                <td><?php _e('Display order within the quiz (numeric)', 'quizcourse-importer'); ?></td>
                                <td><?php _e('No', 'quizcourse-importer'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <h5><?php _e('5. Answers Sheet', 'quizcourse-importer'); ?></h5>
                    <p><?php _e('This sheet contains the answers for each question.', 'quizcourse-importer'); ?></p>
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th><?php _e('Column', 'quizcourse-importer'); ?></th>
                                <th><?php _e('Description', 'quizcourse-importer'); ?></th>
                                <th><?php _e('Required', 'quizcourse-importer'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>answer_id</code></td>
                                <td><?php _e('Unique identifier for the answer (for reference only)', 'quizcourse-importer'); ?></td>
                                <td><?php _e('No', 'quizcourse-importer'); ?></td>
                            </tr>
                            <tr>
                                <td><code>answer_text</code></td>
                                <td><?php _e('The text of the answer', 'quizcourse-importer'); ?></td>
                                <td><?php _e('Yes', 'quizcourse-importer'); ?></td>
                            </tr>
                            <tr>
                                <td><code>question_reference</code></td>
                                <td><?php _e('Reference to question_id in Questions sheet', 'quizcourse-importer'); ?></td>
                                <td><?php _e('Yes', 'quizcourse-importer'); ?></td>
                            </tr>
                            <tr>
                                <td><code>is_correct</code></td>
                                <td><?php _e('"1" for correct answer, "0" for incorrect answer', 'quizcourse-importer'); ?></td>
                                <td><?php _e('Yes', 'quizcourse-importer'); ?></td>
                            </tr>
                            <tr>
                                <td><code>answer_image_url</code></td>
                                <td><?php _e('URL to the answer image', 'quizcourse-importer'); ?></td>
                                <td><?php _e('No', 'quizcourse-importer'); ?></td>
                            </tr>
                            <tr>
                                <td><code>answer_order</code></td>
                                <td><?php _e('Display order within the question (numeric)', 'quizcourse-importer'); ?></td>
                                <td><?php _e('No', 'quizcourse-importer'); ?></td>
                            </tr>
                            <tr>
                                <td><code>weight</code></td>
                                <td><?php _e('Answer weight/points (numeric)', 'quizcourse-importer'); ?></td>
                                <td><?php _e('No', 'quizcourse-importer'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div id="qci-csv-guide" class="qci-tab-content">
                <h4><?php _e('CSV File Structure', 'quizcourse-importer'); ?></h4>
                <p><?php _e('For CSV import, all data must be in a single file with the following required columns:', 'quizcourse-importer'); ?></p>
                
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php _e('Column', 'quizcourse-importer'); ?></th>
                            <th><?php _e('Description', 'quizcourse-importer'); ?></th>
                            <th><?php _e('Required', 'quizcourse-importer'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>record_type</code></td>
                            <td><?php _e('Type of record: "course", "section", "quiz", "question", or "answer"', 'quizcourse-importer'); ?></td>
                            <td><?php _e('Yes', 'quizcourse-importer'); ?></td>
                        </tr>
                        <tr>
                            <td><code>record_id</code></td>
                            <td><?php _e('Unique identifier for this record (for reference only)', 'quizcourse-importer'); ?></td>
                            <td><?php _e('Yes', 'quizcourse-importer'); ?></td>
                        </tr>
                        <tr>
                            <td><code>title</code></td>
                            <td><?php _e('Title or text for this record', 'quizcourse-importer'); ?></td>
                            <td><?php _e('Yes', 'quizcourse-importer'); ?></td>
                        </tr>
                        <tr>
                            <td><code>description</code></td>
                            <td><?php _e('Description text (for courses, sections, quizzes)', 'quizcourse-importer'); ?></td>
                            <td><?php _e('No', 'quizcourse-importer'); ?></td>
                        </tr>
                        <tr>
                            <td><code>parent_id</code></td>
                            <td><?php _e('Reference to parent record (course for sections, section for quizzes, etc.)', 'quizcourse-importer'); ?></td>
                            <td><?php _e('Yes (except for courses)', 'quizcourse-importer'); ?></td>
                        </tr>
                        <tr>
                            <td><code>is_correct</code></td>
                            <td><?php _e('For answers: "1" for correct, "0" for incorrect', 'quizcourse-importer'); ?></td>
                            <td><?php _e('Yes (for answers)', 'quizcourse-importer'); ?></td>
                        </tr>
                        <tr>
                            <td><code>question_type</code></td>
                            <td><?php _e('For questions: "multiple_choice", "true_false", etc.', 'quizcourse-importer'); ?></td>
                            <td><?php _e('Yes (for questions)', 'quizcourse-importer'); ?></td>
                        </tr>
                        <tr>
                            <td><code>image_url</code></td>
                            <td><?php _e('URL to image for this record', 'quizcourse-importer'); ?></td>
                            <td><?php _e('No', 'quizcourse-importer'); ?></td>
                        </tr>
                        <tr>
                            <td><code>order</code></td>
                            <td><?php _e('Display order (numeric)', 'quizcourse-importer'); ?></td>
                            <td><?php _e('No', 'quizcourse-importer'); ?></td>
                        </tr>
                        <tr>
                            <td><code>status</code></td>
                            <td><?php _e('Status: "publish", "draft", or "1"/"0" for published/unpublished', 'quizcourse-importer'); ?></td>
                            <td><?php _e('No', 'quizcourse-importer'); ?></td>
                        </tr>
                        <tr>
                            <td><code>additional_data</code></td>
                            <td><?php _e('JSON-encoded additional data for specific record types', 'quizcourse-importer'); ?></td>
                            <td><?php _e('No', 'quizcourse-importer'); ?></td>
                        </tr>
                    </tbody>
                </table>
                
                <div class="qci-csv-example">
                    <h5><?php _e('Example CSV Format', 'quizcourse-importer'); ?></h5>
                    <pre class="qci-code-sample">
record_type,record_id,title,description,parent_id,is_correct,question_type,image_url,order,status
course,C1,WordPress Development Course,Learn WordPress development from scratch,,,,https://example.com/wp-course.jpg,1,publish
section,S1,Introduction to WordPress,Basic concepts of WordPress,C1,,,,1,1
quiz,Q1,WordPress Basics Quiz,Test your knowledge of WordPress basics,S1,,,,1,1
question,QU1,What is WordPress?,Multiple choice question about WordPress,Q1,,multiple_choice,,1,1
answer,A1,A content management system,Answer 1,QU1,1,,,1,
answer,A2,A web browser,Answer 2,QU1,0,,,2,
question,QU2,WordPress is open-source software.,True/False question about WordPress,Q1,,true_false,,2,1
answer,A3,True,Correct answer,QU2,1,,,1,
answer,A4,False,Incorrect answer,QU2,0,,,2,
section,S2,WordPress Themes,Working with themes in WordPress,C1,,,,2,1
quiz,Q2,WordPress Themes Quiz,Test your knowledge of WordPress themes,S2,,,,1,1
                    </pre>
                </div>
                
                <div class="qci-csv-notes">
                    <h5><?php _e('Important Notes for CSV Import', 'quizcourse-importer'); ?></h5>
                    <ul>
                        <li><?php _e('Records should be arranged in a hierarchical order: courses first, then sections, quizzes, questions, and answers.', 'quizcourse-importer'); ?></li>
                        <li><?php _e('The parent_id column must reference the record_id of the parent item.', 'quizcourse-importer'); ?></li>
                        <li><?php _e('Each question must have at least one answer.', 'quizcourse-importer'); ?></li>
                        <li><?php _e('For multiple choice questions, at least one answer must be marked as correct (is_correct=1).', 'quizcourse-importer'); ?></li>
                        <li><?php _e('For true/false questions, exactly one answer should be marked as correct.', 'quizcourse-importer'); ?></li>
                        <li><?php _e('The additional_data column can be used for JSON-encoded extra data specific to your quiz system.', 'quizcourse-importer'); ?></li>
                    </ul>
                </div>
            </div>
            
            <div id="qci-references-guide" class="qci-tab-content">
                <h4><?php _e('Understanding References Between Records', 'quizcourse-importer'); ?></h4>
                <p><?php _e('One of the most important aspects of the import is correctly setting up the relationships between different records. Here\'s how references work:', 'quizcourse-importer'); ?></p>
                
                <div class="qci-references-diagram">
                    <img src="<?php echo esc_url(QCI_PLUGIN_URL . 'assets/images/references-diagram.png'); ?>" alt="References Diagram">
                </div>
                
                <div class="qci-references-explanation">
                    <h5><?php _e('Reference Chain', 'quizcourse-importer'); ?></h5>
                    <ol>
                        <li>
                            <strong><?php _e('Course', 'quizcourse-importer'); ?></strong>
                            <p><?php _e('The top-level entity with a unique course_id', 'quizcourse-importer'); ?></p>
                        </li>
                        <li>
                            <strong><?php _e('Section', 'quizcourse-importer'); ?></strong>
                            <p><?php _e('Has a course_reference that must match a course_id', 'quizcourse-importer'); ?></p>
                        </li>
                        <li>
                            <strong><?php _e('Quiz', 'quizcourse-importer'); ?></strong>
                            <p><?php _e('Has a section_reference that must match a section_id', 'quizcourse-importer'); ?></p>
                        </li>
                        <li>
                            <strong><?php _e('Question', 'quizcourse-importer'); ?></strong>
                            <p><?php _e('Has a quiz_reference that must match a quiz_id', 'quizcourse-importer'); ?></p>
                        </li>
                        <li>
                            <strong><?php _e('Answer', 'quizcourse-importer'); ?></strong>
                           <p><?php _e('Has a question_reference that must match a question_id', 'quizcourse-importer'); ?></p>
                       </li>
                   </ol>
                   
                   <h5><?php _e('Example of References in Excel Format', 'quizcourse-importer'); ?></h5>
                   <p><?php _e('For Excel imports with multiple sheets:', 'quizcourse-importer'); ?></p>
                   
                   <div class="qci-code-example">
                       <pre>
<strong>Courses Sheet:</strong>
course_id | course_title            | course_description
---------|-------------------------|------------------------
C001     | WordPress Development   | Learn WordPress development
C002     | PHP Programming         | Complete PHP course

<strong>Sections Sheet:</strong>
section_id | section_title      | course_reference
-----------|-------------------|----------------
S001       | Getting Started   | C001             (refers to WordPress Development)
S002       | Advanced Topics   | C001             (refers to WordPress Development)
S003       | Basics of PHP     | C002             (refers to PHP Programming)

<strong>Quizzes Sheet:</strong>
quiz_id | quiz_title          | section_reference
--------|--------------------|-----------------
Q001    | WordPress Basics   | S001              (refers to Getting Started)
Q002    | Theme Development  | S002              (refers to Advanced Topics)
Q003    | PHP Syntax Quiz    | S003              (refers to Basics of PHP)

<strong>Questions Sheet:</strong>
question_id | question_text                  | quiz_reference
------------|-------------------------------|---------------
QU001       | What is WordPress?            | Q001           (refers to WordPress Basics)
QU002       | What is a WordPress theme?    | Q002           (refers to Theme Development)
QU003       | What does PHP stand for?      | Q003           (refers to PHP Syntax Quiz)

<strong>Answers Sheet:</strong>
answer_id | answer_text                     | question_reference | is_correct
----------|--------------------------------|-------------------|----------
A001      | A content management system    | QU001              | 1
A002      | A web browser                  | QU001              | 0
A003      | A template for website design  | QU002              | 1
A004      | A plugin                       | QU002              | 0
A005      | Hypertext Preprocessor         | QU003              | 1
A006      | Personal Home Page             | QU003              | 0
                       </pre>
                   </div>
                   
                   <h5><?php _e('Example of References in CSV Format', 'quizcourse-importer'); ?></h5>
                   <p><?php _e('For CSV imports with a single file, the record_id and parent_id columns create the references:', 'quizcourse-importer'); ?></p>
                   
                   <div class="qci-code-example">
                       <pre>
record_type | record_id | title                | parent_id
------------|-----------|----------------------|----------
course      | C001      | WordPress Development| 
section     | S001      | Getting Started      | C001      (refers to WordPress Development)
quiz        | Q001      | WordPress Basics     | S001      (refers to Getting Started)
question    | QU001     | What is WordPress?   | Q001      (refers to WordPress Basics)
answer      | A001      | CMS                  | QU001     (refers to What is WordPress?)
                       </pre>
                   </div>
                   
                   <h5><?php _e('Common Reference Issues', 'quizcourse-importer'); ?></h5>
                   <ul>
                       <li><?php _e('Missing or incorrect reference IDs: Make sure every reference points to an existing ID', 'quizcourse-importer'); ?></li>
                       <li><?php _e('Case sensitivity: "C001" and "c001" are treated as different references', 'quizcourse-importer'); ?></li>
                       <li><?php _e('Extra spaces: Trim any whitespace in your reference IDs', 'quizcourse-importer'); ?></li>
                       <li><?php _e('Circular references: Avoid situations where items reference each other', 'quizcourse-importer'); ?></li>
                       <li><?php _e('Duplicate IDs: Each ID must be unique within its type', 'quizcourse-importer'); ?></li>
                   </ul>
               </div>
           </div>
       </div>
   </div>
   
   <div class="qci-troubleshooting">
       <h3><?php _e('Troubleshooting Common Import Issues', 'quizcourse-importer'); ?></h3>
       
       <div class="qci-troubleshooting-item">
           <h4><?php _e('Import Fails During Validation', 'quizcourse-importer'); ?></h4>
           <p><?php _e('If your import fails during the validation phase:', 'quizcourse-importer'); ?></p>
           <ul>
               <li><?php _e('Check for missing required columns', 'quizcourse-importer'); ?></li>
               <li><?php _e('Ensure all reference IDs point to existing records', 'quizcourse-importer'); ?></li>
               <li><?php _e('Verify that questions have the correct type specified', 'quizcourse-importer'); ?></li>
               <li><?php _e('Make sure all answers have is_correct values (0 or 1)', 'quizcourse-importer'); ?></li>
               <li><?php _e('Check that each question has at least one answer', 'quizcourse-importer'); ?></li>
           </ul>
       </div>
       
       <div class="qci-troubleshooting-item">
           <h4><?php _e('Incomplete Import Results', 'quizcourse-importer'); ?></h4>
           <p><?php _e('If your import completes but some items are missing:', 'quizcourse-importer'); ?></p>
           <ul>
               <li><?php _e('Verify the hierarchical order (course → section → quiz → question → answer)', 'quizcourse-importer'); ?></li>
               <li><?php _e('Check for broken reference chains', 'quizcourse-importer'); ?></li>
               <li><?php _e('Ensure numeric fields contain only numbers', 'quizcourse-importer'); ?></li>
               <li><?php _e('Verify that status values are valid ("publish", "draft", "1", "0")', 'quizcourse-importer'); ?></li>
           </ul>
       </div>
       
       <div class="qci-troubleshooting-item">
           <h4><?php _e('Image Import Issues', 'quizcourse-importer'); ?></h4>
           <p><?php _e('If images are not importing correctly:', 'quizcourse-importer'); ?></p>
           <ul>
               <li><?php _e('Use complete URLs for image_url fields (starting with http:// or https://)', 'quizcourse-importer'); ?></li>
               <li><?php _e('Ensure the image URLs are accessible from your server', 'quizcourse-importer'); ?></li>
               <li><?php _e('Check that the image file formats are supported (jpg, png, gif)', 'quizcourse-importer'); ?></li>
               <li><?php _e('Verify your WordPress has proper permissions to download external images', 'quizcourse-importer'); ?></li>
           </ul>
       </div>
   </div>
   
   <div class="qci-expert-tips">
       <h3><?php _e('Expert Tips for Efficient Importing', 'quizcourse-importer'); ?></h3>
       
       <div class="qci-tips-grid">
           <div class="qci-tip-card">
               <div class="qci-tip-icon">
                   <span class="dashicons dashicons-performance"></span>
               </div>
               <h4><?php _e('Start Small', 'quizcourse-importer'); ?></h4>
               <p><?php _e('Begin with a small test import (one course with a few questions) to verify everything works correctly before importing large datasets.', 'quizcourse-importer'); ?></p>
           </div>
           
           <div class="qci-tip-card">
               <div class="qci-tip-icon">
                   <span class="dashicons dashicons-database"></span>
               </div>
               <h4><?php _e('Backup First', 'quizcourse-importer'); ?></h4>
               <p><?php _e('Always back up your WordPress database before performing large imports in case you need to revert changes.', 'quizcourse-importer'); ?></p>
           </div>
           
           <div class="qci-tip-card">
               <div class="qci-tip-icon">
                   <span class="dashicons dashicons-id"></span>
               </div>
               <h4><?php _e('Consistent IDs', 'quizcourse-importer'); ?></h4>
               <p><?php _e('Use a clear naming convention for your IDs to make relationships easier to trace (e.g., C001, S001-C001, Q001-S001).', 'quizcourse-importer'); ?></p>
           </div>
           
           <div class="qci-tip-card">
               <div class="qci-tip-icon">
                   <span class="dashicons dashicons-format-aside"></span>
               </div>
               <h4><?php _e('Use Excel for Complex Data', 'quizcourse-importer'); ?></h4>
               <p><?php _e('For complex course structures with many questions and answers, the Excel format with multiple sheets is easier to manage than CSV.', 'quizcourse-importer'); ?></p>
           </div>
           
           <div class="qci-tip-card">
               <div class="qci-tip-icon">
                   <span class="dashicons dashicons-admin-appearance"></span>
               </div>
               <h4><?php _e('Preview Before Publishing', 'quizcourse-importer'); ?></h4>
               <p><?php _e('Set status to "draft" initially, then review the imported content before publishing to ensure everything appears correctly.', 'quizcourse-importer'); ?></p>
           </div>
           
           <div class="qci-tip-card">
               <div class="qci-tip-icon">
                   <span class="dashicons dashicons-editor-code"></span>
               </div>
               <h4><?php _e('Use HTML Formatting', 'quizcourse-importer'); ?></h4>
               <p><?php _e('You can include basic HTML tags in description and question text fields for better formatting (e.g., &lt;b&gt;, &lt;i&gt;, &lt;ul&gt;).', 'quizcourse-importer'); ?></p>
           </div>
       </div>
   </div>
</div>

<style>
   .qci-sample-files-section {
       margin: 20px 0;
   }
   
   .qci-sample-files-container {
       display: flex;
       gap: 20px;
       flex-wrap: wrap;
       margin: 20px 0;
   }
   
   .qci-sample-file-card {
       background: #fff;
       border: 1px solid #ddd;
       border-radius: 5px;
       padding: 15px;
       width: calc(33.333% - 20px);
       min-width: 250px;
       display: flex;
       align-items: flex-start;
       box-shadow: 0 1px 3px rgba(0,0,0,0.1);
   }
   
   .qci-file-icon {
       margin-right: 15px;
   }
   
   .qci-file-icon .dashicons {
       font-size: 40px;
       width: 40px;
       height: 40px;
       color: #2271b1;
   }
   
   .qci-file-info h4 {
       margin-top: 0;
       margin-bottom: 10px;
   }
   
   .qci-file-info p {
       margin-bottom: 15px;
   }
   
   .qci-tabs {
       margin: 20px 0;
       border: 1px solid #ccc;
       background: #fff;
       border-radius: 5px;
       overflow: hidden;
   }
   
   .qci-tabs-nav {
       display: flex;
       margin: 0;
       padding: 0;
       list-style: none;
       background: #f7f7f7;
       border-bottom: 1px solid #ccc;
   }
   
   .qci-tabs-nav li {
       margin: 0;
   }
   
   .qci-tabs-nav a {
       display: block;
       padding: 10px 15px;
       text-decoration: none;
       color: #444;
       font-weight: 500;
       border-right: 1px solid #ccc;
   }
   
   .qci-tabs-nav li.active a {
       background: #fff;
       border-bottom: 2px solid #2271b1;
       color: #2271b1;
   }
   
   .qci-tab-content {
       padding: 20px;
       display: none;
   }
   
   .qci-tab-content.active {
       display: block;
   }
   
   .qci-code-sample, .qci-code-example pre {
       background: #f5f5f5;
       padding: 15px;
       border: 1px solid #ddd;
       border-radius: 4px;
       overflow-x: auto;
       font-family: monospace;
       font-size: 13px;
       line-height: 1.4;
       white-space: pre;
   }
   
   .qci-sheet-info h5 {
       margin: 25px 0 10px;
       padding-bottom: 5px;
       border-bottom: 1px solid #eee;
   }
   
   .qci-sheet-info table {
       margin-bottom: 20px;
   }
   
   .qci-sheet-info code {
       background: #f7f7f7;
       padding: 2px 5px;
       border-radius: 3px;
   }
   
   .qci-troubleshooting, .qci-expert-tips {
       margin-top: 30px;
   }
   
   .qci-troubleshooting-item {
       background: #fff;
       border: 1px solid #ddd;
       border-radius: 5px;
       padding: 15px 20px;
       margin-bottom: 15px;
   }
   
   .qci-troubleshooting-item h4 {
       margin-top: 0;
   }
   
   .qci-references-diagram {
       text-align: center;
       margin: 20px 0;
   }
   
   .qci-references-diagram img {
       max-width: 100%;
       height: auto;
   }
   
   .qci-references-explanation ol {
       margin-left: 20px;
   }
   
   .qci-references-explanation li {
       margin-bottom: 15px;
   }
   
   .qci-references-explanation p {
       margin: 5px 0 0 0;
   }
   
   .qci-tips-grid {
       display: grid;
       grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
       gap: 20px;
       margin-top: 20px;
   }
   
   .qci-tip-card {
       background: #fff;
       border: 1px solid #ddd;
       border-radius: 5px;
       padding: 20px;
   }
   
   .qci-tip-icon {
       margin-bottom: 15px;
   }
   
   .qci-tip-icon .dashicons {
       font-size: 30px;
       width: 30px;
       height: 30px;
       color: #2271b1;
   }
   
   .qci-tip-card h4 {
       margin-top: 0;
       margin-bottom: 10px;
   }
   
   .qci-tip-card p {
       margin: 0;
   }
   
   @media (max-width: 782px) {
       .qci-sample-file-card {
           width: 100%;
       }
       
       .qci-sample-files-container {
           flex-direction: column;
       }
       
       .qci-tips-grid {
           grid-template-columns: 1fr;
       }
   }
</style>

<script>
   jQuery(document).ready(function($) {
       // Tab functionality
       $('.qci-tabs-nav a').on('click', function(e) {
           e.preventDefault();
           
           // Remove active class from all tabs and content
           $('.qci-tabs-nav li').removeClass('active');
           $('.qci-tab-content').removeClass('active').hide();
           
           // Add active class to clicked tab and show corresponding content
           $(this).parent().addClass('active');
           $($(this).attr('href')).addClass('active').show();
       });
   });
</script>

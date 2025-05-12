<?php
/**
 * Handles the data import process for QuizCourse Importer.
 * 
 * Class for importing data from single-sheet CSV/Excel to multiple related tables.
 * Optimized for wp_aysquiz_* and wp_foxlms_* database structure.
 *
 * @package QuizCourse_Importer
 * @since 1.0.0
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Data Importer Class
 */
class QCI_Data_Importer {

    /**
     * Status log for import process
     * @var array
     */
    private $import_log = array();

    /**
     * Course ID mapping (temp_id => real_id)
     * @var array
     */
    private $course_id_map = array();

    /**
     * Quiz ID mapping (temp_id => real_id)
     * @var array
     */
    private $quiz_id_map = array();

    /**
     * Question ID mapping (temp_id => real_id)
     * @var array
     */
    private $question_id_map = array();

    /**
     * Import settings
     * @var array
     */
    private $settings = array();

    /**
     * Constructor
     */
    public function __construct() {
        $this->settings = $this->get_settings();
    }

    /**
     * Get plugin settings
     * 
     * @return array Settings with defaults
     */
    private function get_settings() {
        $default_settings = array(
            'default_course_status' => 'draft',
            'default_quiz_status' => 'draft',
            'update_existing' => true,
            'skip_validation' => false,
            'import_images' => true,
            'batch_size' => 50,
            'default_author_id' => get_current_user_id(),
        );

        $settings = get_option('qci_settings', array());
        return wp_parse_args($settings, $default_settings);
    }

    /**
     * Process the import operation.
     * 
     * @param array $import_data Import options and file data
     * @return array|WP_Error Import results or error
     */
    public function process_import($import_data) {
        global $wpdb;
        
        // Start transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            // Log start of import process
            QCI_Logger::log('Starting import process', 'info', ['file_id' => $import_data['file_id']]);
            $this->add_to_log('Import process started');
            
            // Get the temporary file path
            $file_path = get_transient('qci_temp_file_' . $import_data['file_id']);
            if (!$file_path || !file_exists($file_path)) {
                return new WP_Error('invalid_file', __('Invalid or expired file. Please upload again.', 'quizcourse-importer'));
            }
            
            // Process the file
            $processor = new QCI_File_Processor();
            $import_data['mapping'] = $this->prepare_mapping($import_data['mapping']);
            
            // Check if we're using the single sheet or multi-sheet format
            $file_ext = pathinfo($file_path, PATHINFO_EXTENSION);
            $is_single_sheet = $this->is_single_sheet_format($import_data['mapping']);
            
            if ($is_single_sheet) {
                $processed_data = $processor->process_single_sheet($file_path, $import_data['mapping']);
            } else {
                $processed_data = $processor->process_file($file_path, $import_data['mapping']);
            }
            
            if (is_wp_error($processed_data)) {
                return $processed_data;
            }
            
            // Validate the processed data
            if (!$this->settings['skip_validation']) {
                $validator = new QCI_Data_Validator();
                $validation_result = $validator->validate_data($processed_data);
                
                if (is_wp_error($validation_result)) {
                    return $validation_result;
                }
            }
            
            // Import data in the correct order
            $this->add_to_log('Importing courses');
            $course_stats = $this->import_courses($processed_data['courses']);
            
            $this->add_to_log('Importing quizzes');
            $quiz_stats = $this->import_quizzes($processed_data['quizzes']);
            
            $this->add_to_log('Importing questions');
            $question_stats = $this->import_questions($processed_data['questions']);
            
            $this->add_to_log('Importing answers');
            $answer_stats = $this->import_answers($processed_data['answers']);
            
            // Link all the imported data together
            $this->add_to_log('Establishing relationships between imported items');
            $this->establish_relationships();
            
            // Commit transaction
            $wpdb->query('COMMIT');
            
            // Log successful import
            QCI_Logger::log('Import completed successfully', 'info', [
                'courses' => $course_stats['imported'],
                'quizzes' => $quiz_stats['imported'],
                'questions' => $question_stats['imported'],
                'answers' => $answer_stats['imported']
            ]);
            
            // Remove temporary file if cleanup is enabled
            if ($this->settings['clear_temp_files']) {
                @unlink($file_path);
                delete_transient('qci_temp_file_' . $import_data['file_id']);
                $this->add_to_log('Temporary files cleaned up');
            }
            
            // Return success stats
            return array(
                'status' => 'success',
                'message' => __('Import completed successfully!', 'quizcourse-importer'),
                'stats' => array(
                    'courses' => $course_stats['imported'],
                    'quizzes' => $quiz_stats['imported'],
                    'questions' => $question_stats['imported'],
                    'answers' => $answer_stats['imported'],
                    'skipped' => $course_stats['skipped'] + $quiz_stats['skipped'] + 
                                $question_stats['skipped'] + $answer_stats['skipped'],
                    'updated' => $course_stats['updated'] + $quiz_stats['updated'] + 
                                $question_stats['updated'] + $answer_stats['updated']
                ),
                'log' => $this->import_log
            );
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $wpdb->query('ROLLBACK');
            
            // Log the error
            QCI_Logger::log_exception($e);
            $this->add_to_log('Import failed: ' . $e->getMessage(), 'error');
            
            return new WP_Error('import_failed', __('Import failed: ', 'quizcourse-importer') . $e->getMessage());
        }
    }

    /**
     * Prepare the mapping data for processing
     * 
     * @param array $mapping Raw mapping data from form
     * @return array Processed mapping data
     */
    private function prepare_mapping($mapping) {
        $prepared_mapping = array();
        
        // If this is a flat array (CSV mapping), convert to nested structure
        if (isset($mapping[0]) && !is_array($mapping[0])) {
            foreach ($mapping as $file_field => $system_field) {
                if (strpos($system_field, '|') !== false) {
                    list($entity, $field) = explode('|', $system_field);
                    $prepared_mapping[$entity][$file_field] = $field;
                }
            }
        } else {
            // Already in the right format (Excel mapping)
            $prepared_mapping = $mapping;
        }
        
        return $prepared_mapping;
    }

    /**
     * Check if the mapping is for a single sheet format
     * 
     * @param array $mapping Mapping data
     * @return bool Whether this is a single sheet format
     */
    private function is_single_sheet_format($mapping) {
        // Check if we have a record_type field in the mapping
        foreach ($mapping as $sheet => $fields) {
            if (is_array($fields)) {
                foreach ($fields as $file_field => $system_field) {
                    if ($file_field === 'record_type' || strtolower($file_field) === 'record_type') {
                        return true;
                    }
                }
            } elseif ($sheet === 'record_type' || strtolower($sheet) === 'record_type') {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Import courses data
     * 
     * @param array $courses_data Array of course data
     * @return array Import statistics
     */
    private function import_courses($courses_data) {
        global $wpdb;
        
        $stats = array('imported' => 0, 'updated' => 0, 'skipped' => 0);
        $courses_table = $wpdb->prefix . 'foxlms_courses';
        
        if (empty($courses_data)) {
            $this->add_to_log('No courses data to import');
            return $stats;
        }
        
        foreach ($courses_data as $index => $course) {
            // Generate a unique ID if none provided
            $temp_id = isset($course['temp_id']) ? $course['temp_id'] : 'course_' . ($index + 1);
            
            // Check required fields
            if (empty($course['title'])) {
                $this->add_to_log("Skipping course at index $index: Missing title", 'warning');
                $stats['skipped']++;
                continue;
            }
            
            // Check if course already exists
            $existing_course_id = $this->get_existing_course_id($course);
            
            if ($existing_course_id && !$this->settings['update_existing']) {
                $this->add_to_log("Skipping existing course: " . $course['title'], 'info');
                $this->course_id_map[$temp_id] = $existing_course_id;
                $stats['skipped']++;
                continue;
            }
            
            // Prepare course data
            $course_data = array(
                'title' => $course['title'],
                'description' => isset($course['description']) ? $course['description'] : '',
                'author_id' => isset($course['author_id']) ? $course['author_id'] : $this->settings['default_author_id'],
                'status' => isset($course['status']) ? $course['status'] : $this->settings['default_course_status'],
                'date_modified' => current_time('mysql')
            );
            
            if (isset($course['image']) && !empty($course['image'])) {
                $course_data['image'] = $course['image'];
            }
            
            if (isset($course['category_ids'])) {
                $course_data['category_ids'] = $course['category_ids'];
            }
            
            // Handle ordering
            if (isset($course['ordering']) && is_numeric($course['ordering'])) {
                $course_data['ordering'] = intval($course['ordering']);
            }
            
            // Handle options
            if (isset($course['options']) && !empty($course['options'])) {
                $course_data['options'] = $this->maybe_serialize($course['options']);
            }
            
            // Update or insert
            if ($existing_course_id) {
                // Update existing course
                $result = $wpdb->update(
                    $courses_table,
                    $course_data,
                    array('id' => $existing_course_id)
                );
                
                if ($result !== false) {
                    $this->add_to_log("Updated course: " . $course['title']);
                    $this->course_id_map[$temp_id] = $existing_course_id;
                    $stats['updated']++;
                } else {
                    $this->add_to_log("Failed to update course: " . $course['title'], 'error');
                    $stats['skipped']++;
                }
            } else {
                // Insert new course
                $course_data['date_created'] = current_time('mysql');
                
                $result = $wpdb->insert(
                    $courses_table,
                    $course_data
                );
                
                if ($result) {
                    $new_course_id = $wpdb->insert_id;
                    $this->add_to_log("Imported new course: " . $course['title']);
                    $this->course_id_map[$temp_id] = $new_course_id;
                    $stats['imported']++;
                } else {
                    $this->add_to_log("Failed to import course: " . $course['title'], 'error');
                    $stats['skipped']++;
                }
            }
        }
        
        return $stats;
    }

    /**
     * Import quizzes data
     * 
     * @param array $quizzes_data Array of quiz data
     * @return array Import statistics
     */
    private function import_quizzes($quizzes_data) {
        global $wpdb;
        
        $stats = array('imported' => 0, 'updated' => 0, 'skipped' => 0);
        $quizzes_table = $wpdb->prefix . 'aysquiz_quizes';
        
        if (empty($quizzes_data)) {
            $this->add_to_log('No quizzes data to import');
            return $stats;
        }
        
        foreach ($quizzes_data as $index => $quiz) {
            // Generate a unique ID if none provided
            $temp_id = isset($quiz['temp_id']) ? $quiz['temp_id'] : 'quiz_' . ($index + 1);
            
            // Check required fields
            if (empty($quiz['title'])) {
                $this->add_to_log("Skipping quiz at index $index: Missing title", 'warning');
                $stats['skipped']++;
                continue;
            }
            
            // Check if quiz already exists
            $existing_quiz_id = $this->get_existing_quiz_id($quiz);
            
            if ($existing_quiz_id && !$this->settings['update_existing']) {
                $this->add_to_log("Skipping existing quiz: " . $quiz['title'], 'info');
                $this->quiz_id_map[$temp_id] = $existing_quiz_id;
                $stats['skipped']++;
                continue;
            }
            
            // Prepare quiz data
            $quiz_data = array(
                'title' => $quiz['title'],
                'description' => isset($quiz['description']) ? $quiz['description'] : '',
                'author_id' => isset($quiz['author_id']) ? $quiz['author_id'] : $this->settings['default_author_id'],
                'published' => isset($quiz['published']) ? intval($quiz['published']) : 1
            );
            
            if (isset($quiz['quiz_image']) && !empty($quiz['quiz_image'])) {
                $quiz_data['quiz_image'] = $quiz['quiz_image'];
            }
            
            if (isset($quiz['quiz_category_id'])) {
                $quiz_data['quiz_category_id'] = intval($quiz['quiz_category_id']);
            }
            
            // Handle ordering
            if (isset($quiz['ordering']) && is_numeric($quiz['ordering'])) {
                $quiz_data['ordering'] = intval($quiz['ordering']);
            }
            
            // Handle options
            if (isset($quiz['options']) && !empty($quiz['options'])) {
                $quiz_data['options'] = $this->maybe_serialize($quiz['options']);
            } else {
                // Default options
                $quiz_data['options'] = serialize(array(
                    'quiz_theme' => 'classic_light',
                    'color' => '#27AE60',
                    'bg_color' => '#fff',
                    'text_color' => '#000',
                    'height' => 350,
                    'width' => 400,
                    'timer' => 100,
                    'information_form' => 'disable',
                    'form_name' => '',
                    'form_email' => '',
                    'form_phone' => '',
                    'enable_logged_users' => 'off',
                    'image_width' => '',
                    'image_height' => '',
                    'enable_correction' => 'off',
                    'enable_questions_counter' => 'on',
                    'limit_users' => 'off',
                    'limitation_message' => '',
                    'redirect_url' => '',
                    'redirection_delay' => '',
                    'enable_progress_bar' => 'on',
                    'randomize_questions' => 'off',
                    'randomize_answers' => 'off',
                    'enable_questions_result' => 'on',
                    'custom_css' => '',
                    'enable_restriction_pass' => 'off',
                    'restriction_pass_message' => '',
                    'user_role' => ''
                ));
            }
            
            // Handle intervals
            if (isset($quiz['intervals']) && !empty($quiz['intervals'])) {
                $quiz_data['intervals'] = $quiz['intervals'];
            } else {
                // Default intervals
                $quiz_data['intervals'] = '';
            }
            
            // Update or insert
            if ($existing_quiz_id) {
                // Update existing quiz
                $result = $wpdb->update(
                    $quizzes_table,
                    $quiz_data,
                    array('id' => $existing_quiz_id)
                );
                
                if ($result !== false) {
                    $this->add_to_log("Updated quiz: " . $quiz['title']);
                    $this->quiz_id_map[$temp_id] = $existing_quiz_id;
                    $stats['updated']++;
                } else {
                    $this->add_to_log("Failed to update quiz: " . $quiz['title'], 'error');
                    $stats['skipped']++;
                }
            } else {
                // Insert new quiz
                $quiz_data['create_date'] = current_time('mysql');
                
                $result = $wpdb->insert(
                    $quizzes_table,
                    $quiz_data
                );
                
                if ($result) {
                    $new_quiz_id = $wpdb->insert_id;
                    $this->add_to_log("Imported new quiz: " . $quiz['title']);
                    $this->quiz_id_map[$temp_id] = $new_quiz_id;
                    $stats['imported']++;
                } else {
                    $this->add_to_log("Failed to import quiz: " . $quiz['title'], 'error');
                    $stats['skipped']++;
                }
            }
        }
        
        return $stats;
    }

    /**
     * Import questions data
     * 
     * @param array $questions_data Array of question data
     * @return array Import statistics
     */
    private function import_questions($questions_data) {
        global $wpdb;
        
        $stats = array('imported' => 0, 'updated' => 0, 'skipped' => 0);
        $questions_table = $wpdb->prefix . 'aysquiz_questions';
        
        if (empty($questions_data)) {
            $this->add_to_log('No questions data to import');
            return $stats;
        }
        
        foreach ($questions_data as $index => $question) {
            // Generate a unique ID if none provided
            $temp_id = isset($question['temp_id']) ? $question['temp_id'] : 'question_' . ($index + 1);
            
            // Check required fields
            if (empty($question['question'])) {
                $this->add_to_log("Skipping question at index $index: Missing question text", 'warning');
                $stats['skipped']++;
                continue;
            }
            
            // Check if question already exists
            $existing_question_id = $this->get_existing_question_id($question);
            
            if ($existing_question_id && !$this->settings['update_existing']) {
                $this->add_to_log("Skipping existing question: " . substr($question['question'], 0, 50) . "...", 'info');
                $this->question_id_map[$temp_id] = $existing_question_id;
                $stats['skipped']++;
                continue;
            }
            
            // Prepare question data
            $question_data = array(
                'question' => $question['question'],
                'question_title' => isset($question['question_title']) ? $question['question_title'] : '',
                'author_id' => isset($question['author_id']) ? $question['author_id'] : $this->settings['default_author_id'],
                'published' => isset($question['published']) ? intval($question['published']) : 1
            );
            
            // Handle question image
            if (isset($question['question_image']) && !empty($question['question_image'])) {
                $question_data['question_image'] = $question['question_image'];
            }
            
            // Handle question type
            if (isset($question['type']) && !empty($question['type'])) {
                $question_data['type'] = $question['type'];
            } else {
                $question_data['type'] = 'radio'; // Default to multiple choice
            }
            
            // Handle category
            if (isset($question['category_id']) && !empty($question['category_id'])) {
                $question_data['category_id'] = intval($question['category_id']);
            }
            
            // Handle tag
            if (isset($question['tag_id']) && !empty($question['tag_id'])) {
                $question_data['tag_id'] = intval($question['tag_id']);
            }
            
            // Handle hint
            if (isset($question['question_hint']) && !empty($question['question_hint'])) {
                $question_data['question_hint'] = $question['question_hint'];
            }
            
            // Handle explanation
            if (isset($question['explanation']) && !empty($question['explanation'])) {
                $question_data['explanation'] = $question['explanation'];
            }
            
            // Handle wrong and right answer text
            if (isset($question['wrong_answer_text'])) {
                $question_data['wrong_answer_text'] = $question['wrong_answer_text'];
            }
            
            if (isset($question['right_answer_text'])) {
                $question_data['right_answer_text'] = $question['right_answer_text'];
            }
            
            // Handle weight
            if (isset($question['weight']) && is_numeric($question['weight'])) {
                $question_data['weight'] = intval($question['weight']);
            }
            
            // Handle answers weight
            if (isset($question['answers_weight']) && is_numeric($question['answers_weight'])) {
                $question_data['answers_weight'] = intval($question['answers_weight']);
            }
            
            // Handle not influence to score
            if (isset($question['not_influence_to_score'])) {
                $question_data['not_influence_to_score'] = $question['not_influence_to_score'];
            }
            
            // Handle options
            if (isset($question['options']) && !empty($question['options'])) {
                $question_data['options'] = $this->maybe_serialize($question['options']);
            }
            
            // Update or insert
            if ($existing_question_id) {
                // Update existing question
                $result = $wpdb->update(
                    $questions_table,
                    $question_data,
                    array('id' => $existing_question_id)
                );
                
                if ($result !== false) {
                    $this->add_to_log("Updated question: " . substr($question['question'], 0, 50) . "...");
                    $this->question_id_map[$temp_id] = $existing_question_id;
                    $stats['updated']++;
                } else {
                    $this->add_to_log("Failed to update question: " . substr($question['question'], 0, 50) . "...", 'error');
                    $stats['skipped']++;
                }
            } else {
                // Insert new question
                $question_data['create_date'] = current_time('mysql');
                
                $result = $wpdb->insert(
                    $questions_table,
                    $question_data
                );
                
                if ($result) {
                    $new_question_id = $wpdb->insert_id;
                    $this->add_to_log("Imported new question: " . substr($question['question'], 0, 50) . "...");
                    $this->question_id_map[$temp_id] = $new_question_id;
                    $stats['imported']++;
                } else {
                    $this->add_to_log("Failed to import question: " . substr($question['question'], 0, 50) . "...", 'error');
                    $stats['skipped']++;
                }
            }
        }
        
        return $stats;
    }

    /**
     * Import answers data
     * 
     * @param array $answers_data Array of answer data
     * @return array Import statistics
     */
    private function import_answers($answers_data) {
        global $wpdb;
        
        $stats = array('imported' => 0, 'updated' => 0, 'skipped' => 0);
        $answers_table = $wpdb->prefix . 'aysquiz_answers';
        
        if (empty($answers_data)) {
            $this->add_to_log('No answers data to import');
            return $stats;
        }
        
        foreach ($answers_data as $index => $answer) {
            // Check required fields
            if (empty($answer['answer'])) {
                $this->add_to_log("Skipping answer at index $index: Missing answer text", 'warning');
                $stats['skipped']++;
                continue;
            }
            
            // Get question ID for this answer
            $question_id = null;
            if (isset($answer['question_temp_id']) && isset($this->question_id_map[$answer['question_temp_id']])) {
                $question_id = $this->question_id_map[$answer['question_temp_id']];
            } elseif (isset($answer['question_reference'])) {
                // Try to find question ID by reference
                foreach ($this->question_id_map as $temp_id => $real_id) {
                    if (strpos($temp_id, $answer['question_reference']) !== false) {
                        $question_id = $real_id;
                        break;
                    }
                }
            }
            
            if (!$question_id) {
                $this->add_to_log("Skipping answer: Cannot find referenced question", 'warning');
                $stats['skipped']++;
                continue;
            }
            
            // Check if answer already exists
            $existing_answer_id = $this->get_existing_answer_id($answer, $question_id);
            
            if ($existing_answer_id && !$this->settings['update_existing']) {
                $this->add_to_log("Skipping existing answer: " . substr($answer['answer'], 0, 50) . "...", 'info');
                $stats['skipped']++;
                continue;
            }
            
            // Prepare answer data
            $answer_data = array(
                'question_id' => $question_id,
                'answer' => $answer['answer'],
                'correct' => isset($answer['correct']) ? intval($answer['correct']) : (isset($answer['is_correct']) ? intval($answer['is_correct']) : 0)
            );
            
            // Handle answer image
            if (isset($answer['image']) && !empty($answer['image'])) {
                $answer_data['image'] = $answer['image'];
            }
            
            // Handle ordering
            if (isset($answer['ordering']) && is_numeric($answer['ordering'])) {
                $answer_data['ordering'] = intval($answer['ordering']);
            }
            
            // Handle weight
            if (isset($answer['weight']) && is_numeric($answer['weight'])) {
                $answer_data['weight'] = intval($answer['weight']);
            }
            
            // Handle keyword and placeholder
            if (isset($answer['keyword'])) {
                $answer_data['keyword'] = $answer['keyword'];
            }
            
            if (isset($answer['placeholder'])) {
                $answer_data['placeholder'] = $answer['placeholder'];
            }
            
            // Handle options
            if (isset($answer['options']) && !empty($answer['options'])) {
                $answer_data['options'] = $this->maybe_serialize($answer['options']);
            }
            
            // Update or insert
            if ($existing_answer_id) {
                // Update existing answer
                $result = $wpdb->update(
                    $answers_table,
                    $answer_data,
                    array('id' => $existing_answer_id)
                );
                
                if ($result !== false) {
                    $this->add_to_log("Updated answer: " . substr($answer['answer'], 0, 50) . "...");
                    $stats['updated']++;
                } else {
                    $this->add_to_log("Failed to update answer: " . substr($answer['answer'], 0, 50) . "...", 'error');
                    $stats['skipped']++;
                }
            } else {
                // Insert new answer
                $result = $wpdb->insert(
                    $answers_table,
                    $answer_data
                );
                
                if ($result) {
                    $this->add_to_log("Imported new answer: " . substr($answer['answer'], 0, 50) . "...");
                    $stats['imported']++;
                } else {
                    $this->add_to_log("Failed to import answer: " . substr($answer['answer'], 0, 50) . "...", 'error');
                    $stats['skipped']++;
                }
            }
        }
        
        return $stats;
    }

    /**
     * Establish relationships between imported entities
     */
    private function establish_relationships() {
        global $wpdb;
        
        $this->add_to_log('Establishing relationships between quizzes and courses');
        
        // Link quizzes to courses
        foreach ($this->quiz_id_map as $quiz_temp_id => $quiz_id) {
            // Find the course this quiz belongs to
            foreach ($this->course_id_map as $course_temp_id => $course_id) {
                if (strpos($quiz_temp_id, $course_temp_id) !== false) {
                    $this->link_quiz_to_course($quiz_id, $course_id);
                    break;
                }
            }
        }
        
        $this->add_to_log('Establishing relationships between questions and quizzes');
        
        // Link questions to quizzes
        foreach ($this->question_id_map as $question_temp_id => $question_id) {
            // Find the quiz this question belongs to
            foreach ($this->quiz_id_map as $quiz_temp_id => $quiz_id) {
                if (strpos($question_temp_id, $quiz_temp_id) !== false) {
                    $this->link_question_to_quiz($question_id, $quiz_id);
                    break;
                }
            }
        }
    }

    /**
    * Link a quiz to a course
    * 
    * @param int $quiz_id Quiz ID
    * @param int $course_id Course ID
    */
   private function link_quiz_to_course($quiz_id, $course_id) {
       global $wpdb;
       $courses_table = $wpdb->prefix . 'foxlms_courses';
       
       // Get existing question_ids from course
       $current_question_ids = $wpdb->get_var(
           $wpdb->prepare(
               "SELECT question_ids FROM $courses_table WHERE id = %d",
               $course_id
           )
       );
       
       // Update question_ids column
       $question_ids = empty($current_question_ids) ? $quiz_id : $current_question_ids . ',' . $quiz_id;
       
       // Remove any duplicates
       $question_ids_array = array_unique(explode(',', $question_ids));
       $question_ids = implode(',', $question_ids_array);
       
       // Update course
       $result = $wpdb->update(
           $courses_table,
           array('question_ids' => $question_ids),
           array('id' => $course_id)
       );
       
       if ($result !== false) {
           $this->add_to_log("Linked quiz ID $quiz_id to course ID $course_id");
       } else {
           $this->add_to_log("Failed to link quiz ID $quiz_id to course ID $course_id", 'warning');
       }
   }
   
   /**
    * Link a question to a quiz
    * 
    * @param int $question_id Question ID
    * @param int $quiz_id Quiz ID
    */
   private function link_question_to_quiz($question_id, $quiz_id) {
       global $wpdb;
       $quizzes_table = $wpdb->prefix . 'aysquiz_quizes';
       
       // Get existing question_ids from quiz
       $current_question_ids = $wpdb->get_var(
           $wpdb->prepare(
               "SELECT question_ids FROM $quizzes_table WHERE id = %d",
               $quiz_id
           )
       );
       
       // Update question_ids column
       $question_ids = empty($current_question_ids) ? $question_id : $current_question_ids . ',' . $question_id;
       
       // Remove any duplicates
       $question_ids_array = array_unique(explode(',', $question_ids));
       $question_ids = implode(',', $question_ids_array);
       
       // Update quiz
       $result = $wpdb->update(
           $quizzes_table,
           array('question_ids' => $question_ids),
           array('id' => $quiz_id)
       );
       
       if ($result !== false) {
           $this->add_to_log("Linked question ID $question_id to quiz ID $quiz_id");
       } else {
           $this->add_to_log("Failed to link question ID $question_id to quiz ID $quiz_id", 'warning');
       }
   }

   /**
    * Check if a course already exists
    * 
    * @param array $course Course data
    * @return int|false Course ID if exists, false otherwise
    */
   private function get_existing_course_id($course) {
       global $wpdb;
       $courses_table = $wpdb->prefix . 'foxlms_courses';
       
       // Try to find by title
       $existing_id = $wpdb->get_var(
           $wpdb->prepare(
               "SELECT id FROM $courses_table WHERE title = %s",
               $course['title']
           )
       );
       
       if ($existing_id) {
           return $existing_id;
       }
       
       // Try to find by custom identifier if provided
       if (isset($course['custom_id']) && !empty($course['custom_id'])) {
           $existing_id = $wpdb->get_var(
               $wpdb->prepare(
                   "SELECT id FROM $courses_table WHERE custom_post_id = %s",
                   $course['custom_id']
               )
           );
           
           if ($existing_id) {
               return $existing_id;
           }
       }
       
       return false;
   }
   
   /**
    * Check if a quiz already exists
    * 
    * @param array $quiz Quiz data
    * @return int|false Quiz ID if exists, false otherwise
    */
   private function get_existing_quiz_id($quiz) {
       global $wpdb;
       $quizzes_table = $wpdb->prefix . 'aysquiz_quizes';
       
       // Try to find by title
       $existing_id = $wpdb->get_var(
           $wpdb->prepare(
               "SELECT id FROM $quizzes_table WHERE title = %s",
               $quiz['title']
           )
       );
       
       if ($existing_id) {
           return $existing_id;
       }
       
       // Try to find by custom identifier if provided
       if (isset($quiz['custom_id']) && !empty($quiz['custom_id'])) {
           $existing_id = $wpdb->get_var(
               $wpdb->prepare(
                   "SELECT id FROM $quizzes_table WHERE quiz_url = %s",
                   $quiz['custom_id']
               )
           );
           
           if ($existing_id) {
               return $existing_id;
           }
       }
       
       return false;
   }
   
   /**
    * Check if a question already exists
    * 
    * @param array $question Question data
    * @return int|false Question ID if exists, false otherwise
    */
   private function get_existing_question_id($question) {
       global $wpdb;
       $questions_table = $wpdb->prefix . 'aysquiz_questions';
       
       // Try to find by question text
       $existing_id = $wpdb->get_var(
           $wpdb->prepare(
               "SELECT id FROM $questions_table WHERE question = %s",
               $question['question']
           )
       );
       
       return $existing_id;
   }
   
   /**
    * Check if an answer already exists
    * 
    * @param array $answer Answer data
    * @param int $question_id Question ID
    * @return int|false Answer ID if exists, false otherwise
    */
   private function get_existing_answer_id($answer, $question_id) {
       global $wpdb;
       $answers_table = $wpdb->prefix . 'aysquiz_answers';
       
       // Try to find by answer text and question ID
       $existing_id = $wpdb->get_var(
           $wpdb->prepare(
               "SELECT id FROM $answers_table WHERE answer = %s AND question_id = %d",
               $answer['answer'],
               $question_id
           )
       );
       
       return $existing_id;
   }
   
   /**
    * Add an entry to the import log
    * 
    * @param string $message Log message
    * @param string $type Log type (info, warning, error)
    */
   private function add_to_log($message, $type = 'info') {
       $log_entry = array(
           'time' => current_time('mysql'),
           'message' => $message,
           'type' => $type
       );
       
       $this->import_log[] = $log_entry;
   }
   
   /**
    * Maybe serialize data if it's not already serialized
    * 
    * @param mixed $data Data to serialize
    * @return string Serialized data
    */
   private function maybe_serialize($data) {
       if (is_array($data) || is_object($data)) {
           return serialize($data);
       }
       
       // Check if it's a JSON string
       if (is_string($data) && $this->is_json($data)) {
           return serialize(json_decode($data, true));
       }
       
       // If it's already serialized, return as is
       if (is_string($data) && $this->is_serialized($data)) {
           return $data;
       }
       
       return serialize($data);
   }
   
   /**
    * Check if a string is valid JSON
    * 
    * @param string $string String to check
    * @return bool Whether the string is valid JSON
    */
   private function is_json($string) {
       if (!is_string($string)) {
           return false;
       }
       
       json_decode($string);
       return (json_last_error() == JSON_ERROR_NONE);
   }
   
   /**
    * Check if a string is serialized
    * 
    * @param string $data String to check
    * @return bool Whether the string is serialized
    */
   private function is_serialized($data) {
       // If it isn't a string, it isn't serialized.
       if (!is_string($data)) {
           return false;
       }
       $data = trim($data);
       if ('N;' == $data) {
           return true;
       }
       if (!preg_match('/^([adObis]):/', $data, $badions)) {
           return false;
       }
       switch ($badions[1]) {
           case 'a' :
           case 'O' :
           case 's' :
               if (preg_match("/^{$badions[1]}:[0-9]+:.*[;}]\$/s", $data)) {
                   return true;
               }
               break;
           case 'b' :
           case 'i' :
           case 'd' :
               if (preg_match("/^{$badions[1]}:[0-9.E-]+;\$/", $data)) {
                   return true;
               }
               break;
       }
       return false;
   }
   
   /**
    * Process import in stages
    * This method is used for AJAX-based imports to handle large data sets
    * 
    * @param array $import_data Import options and stage info
    * @return array|WP_Error Stage results or error
    */
   public function process_import_stage($import_data) {
       global $wpdb;
       
       // Check if we need to start a transaction
       if ($import_data['stage_index'] == 0) {
           $wpdb->query('START TRANSACTION');
       }
       
       try {
           // Get the temporary file path
           $file_path = get_transient('qci_temp_file_' . $import_data['file_id']);
           if (!$file_path || !file_exists($file_path)) {
               return new WP_Error('invalid_file', __('Invalid or expired file. Please upload again.', 'quizcourse-importer'));
           }
           
           // Process based on stage
           $stage = $import_data['stage'];
           $items_processed = 0;
           $log = array();
           
           switch ($stage) {
               case 'courses':
                   $processor = new QCI_File_Processor();
                   $import_data['mapping'] = $this->prepare_mapping($import_data['mapping']);
                   $processed_data = $processor->get_courses_data($file_path, $import_data['mapping']);
                   
                   if (is_wp_error($processed_data)) {
                       return $processed_data;
                   }
                   
                   $stats = $this->import_courses($processed_data);
                   $items_processed = $stats['imported'] + $stats['updated'];
                   $log[] = sprintf(
                       __('Imported %d courses, updated %d, skipped %d.', 'quizcourse-importer'),
                       $stats['imported'],
                       $stats['updated'],
                       $stats['skipped']
                   );
                   break;
                   
               case 'quizzes':
                   $processor = new QCI_File_Processor();
                   $import_data['mapping'] = $this->prepare_mapping($import_data['mapping']);
                   $processed_data = $processor->get_quizzes_data($file_path, $import_data['mapping']);
                   
                   if (is_wp_error($processed_data)) {
                       return $processed_data;
                   }
                   
                   $stats = $this->import_quizzes($processed_data);
                   $items_processed = $stats['imported'] + $stats['updated'];
                   $log[] = sprintf(
                       __('Imported %d quizzes, updated %d, skipped %d.', 'quizcourse-importer'),
                       $stats['imported'],
                       $stats['updated'],
                       $stats['skipped']
                   );
                   break;
                   
               case 'questions':
                   $processor = new QCI_File_Processor();
                   $import_data['mapping'] = $this->prepare_mapping($import_data['mapping']);
                   $processed_data = $processor->get_questions_data($file_path, $import_data['mapping']);
                   
                   if (is_wp_error($processed_data)) {
                       return $processed_data;
                   }
                   
                   $stats = $this->import_questions($processed_data);
                   $items_processed = $stats['imported'] + $stats['updated'];
                   $log[] = sprintf(
                       __('Imported %d questions, updated %d, skipped %d.', 'quizcourse-importer'),
                       $stats['imported'],
                       $stats['updated'],
                       $stats['skipped']
                   );
                   break;
                   
               case 'answers':
                   $processor = new QCI_File_Processor();
                   $import_data['mapping'] = $this->prepare_mapping($import_data['mapping']);
                   $processed_data = $processor->get_answers_data($file_path, $import_data['mapping']);
                   
                   if (is_wp_error($processed_data)) {
                       return $processed_data;
                   }
                   
                   $stats = $this->import_answers($processed_data);
                   $items_processed = $stats['imported'] + $stats['updated'];
                   $log[] = sprintf(
                       __('Imported %d answers, updated %d, skipped %d.', 'quizcourse-importer'),
                       $stats['imported'],
                       $stats['updated'],
                       $stats['skipped']
                   );
                   break;
                   
               case 'relationships':
                   $this->establish_relationships();
                   $items_processed = count($this->quiz_id_map) + count($this->question_id_map);
                   $log[] = __('Established relationships between imported items.', 'quizcourse-importer');
                   break;
                   
               case 'cleanup':
                   // Final stage - commit transaction and cleanup
                   $wpdb->query('COMMIT');
                   
                   // Remove temporary file if cleanup is enabled
                   if ($this->settings['clear_temp_files']) {
                       @unlink($file_path);
                       delete_transient('qci_temp_file_' . $import_data['file_id']);
                       $log[] = __('Temporary files cleaned up.', 'quizcourse-importer');
                   }
                   
                   $log[] = __('Import completed successfully!', 'quizcourse-importer');
                   $items_processed = 1;
                   break;
                   
               default:
                   return new WP_Error('invalid_stage', __('Invalid import stage.', 'quizcourse-importer'));
           }
           
           return array(
               'status' => 'success',
               'stage' => $stage,
               'items_processed' => $items_processed,
               'log' => $log
           );
           
       } catch (Exception $e) {
           // Rollback transaction on error
           $wpdb->query('ROLLBACK');
           
           // Log the error
           QCI_Logger::log_exception($e);
           
           return new WP_Error('import_stage_failed', __('Import stage failed: ', 'quizcourse-importer') . $e->getMessage());
       }
   }
   
   /**
    * Cancel an in-progress import
    * 
    * @param string $file_id File ID
    * @return bool Whether the cancellation was successful
    */
   public function cancel_import($file_id) {
       global $wpdb;
       
       // Rollback any active transaction
       $wpdb->query('ROLLBACK');
       
       // Remove temporary file
       $file_path = get_transient('qci_temp_file_' . $file_id);
       if ($file_path && file_exists($file_path)) {
           @unlink($file_path);
       }
       
       delete_transient('qci_temp_file_' . $file_id);
       
       // Log cancellation
       QCI_Logger::log('Import cancelled by user', 'info', ['file_id' => $file_id]);
       
       return true;
   }
   
   /**
    * Prepare import
    * Calculate the total number of items and define stages
    * 
    * @param array $import_data Import options
    * @return array|WP_Error Preparation results or error
    */
   public function prepare_import($import_data) {
       try {
           // Get the temporary file path
           $file_path = get_transient('qci_temp_file_' . $import_data['file_id']);
           if (!$file_path || !file_exists($file_path)) {
               return new WP_Error('invalid_file', __('Invalid or expired file. Please upload again.', 'quizcourse-importer'));
           }
           
           // Process file to count items
           $processor = new QCI_File_Processor();
           $import_data['mapping'] = $this->prepare_mapping($import_data['mapping']);
           $counts = $processor->count_items($file_path, $import_data['mapping']);
           
           if (is_wp_error($counts)) {
               return $counts;
           }
           
           // Define import stages
           $stages = array(
               array(
                   'key' => 'courses',
                   'message' => __('Importing courses...', 'quizcourse-importer')
               ),
               array(
                   'key' => 'quizzes',
                   'message' => __('Importing quizzes...', 'quizcourse-importer')
               ),
               array(
                   'key' => 'questions',
                   'message' => __('Importing questions...', 'quizcourse-importer')
               ),
               array(
                   'key' => 'answers',
                   'message' => __('Importing answers...', 'quizcourse-importer')
               ),
               array(
                   'key' => 'relationships',
                   'message' => __('Establishing relationships...', 'quizcourse-importer')
               ),
               array(
                   'key' => 'cleanup',
                   'message' => __('Finalizing import...', 'quizcourse-importer')
               )
           );
           
           return array(
               'total_items' => $counts['courses'] + $counts['quizzes'] + $counts['questions'] + $counts['answers'] + 2, // +2 for relationships and cleanup
               'stages' => $stages
           );
           
       } catch (Exception $e) {
           return new WP_Error('preparation_failed', __('Failed to prepare import: ', 'quizcourse-importer') . $e->getMessage());
       }
   }
}

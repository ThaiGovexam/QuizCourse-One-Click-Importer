<?php
/**
 * File processor class.
 * 
 * This class handles the processing of uploaded Excel/CSV files
 * and extracts structured data for import.
 *
 * @package QuizCourse_Importer
 * @since 1.0.0
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * File Processor Class
 */
class QCI_File_Processor {

    /**
     * Available sheet types for import
     * 
     * @var array
     */
    private $sheet_types = array(
        'courses'   => 'Courses',
        'sections'  => 'Sections',
        'quizzes'   => 'Quizzes',
        'questions' => 'Questions',
        'answers'   => 'Answers'
    );

    /**
     * Process the uploaded file and extract data based on mapping.
     * 
     * @param string $file_path Path to the uploaded file.
     * @param array $mapping Field mapping configuration.
     * @return array|WP_Error Processed data or error.
     */
    public function process_file($file_path, $mapping) {
        try {
            // Get file extension
            $file_ext = pathinfo($file_path, PATHINFO_EXTENSION);
            
            // Process based on file type
            if ($file_ext === 'csv') {
                return $this->process_csv($file_path, $mapping);
            } else if (in_array($file_ext, array('xlsx', 'xls'))) {
                return $this->process_excel($file_path, $mapping);
            } else {
                return new WP_Error('invalid_file_type', __('Unsupported file type. Please upload a CSV or Excel file.', 'quizcourse-importer'));
            }
        } catch (Exception $e) {
            QCI_Logger::log('File processing error: ' . $e->getMessage(), 'error');
            return new WP_Error('file_processing_error', $e->getMessage());
        }
    }

    /**
     * Process CSV file.
     * 
     * @param string $file_path Path to the CSV file.
     * @param array $mapping Field mapping configuration.
     * @return array Processed data.
     */
    private function process_csv($file_path, $mapping) {
        // Initialize data arrays
        $data = $this->initialize_data_arrays();
        
        // Get CSV data
        $csv_data = $this->read_csv($file_path);
        if (is_wp_error($csv_data)) {
            return $csv_data;
        }
        
        // Process each sheet (assuming CSV has sheet name as first cell or in filename)
        foreach ($csv_data as $sheet_name => $rows) {
            $sheet_key = $this->get_sheet_key($sheet_name);
            if (!$sheet_key || empty($mapping[$sheet_key])) {
                continue; // Skip unrecognized or unmapped sheets
            }
            
            // Process the rows for this sheet
            $data[$sheet_key] = $this->process_rows($rows, $mapping[$sheet_key]);
        }
        
        return $this->validate_and_clean_data($data);
    }

    /**
     * Process Excel file.
     * 
     * @param string $file_path Path to the Excel file.
     * @param array $mapping Field mapping configuration.
     * @return array Processed data.
     */
    private function process_excel($file_path, $mapping) {
        // Make sure PhpSpreadsheet is available
        if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
            // Try to load from composer autoload first
            $autoload_path = QCI_PLUGIN_DIR . 'vendor/autoload.php';
            if (file_exists($autoload_path)) {
                require_once $autoload_path;
            } else {
                // Fallback to bundled library
                require_once QCI_PLUGIN_DIR . 'libraries/PhpSpreadsheet/vendor/autoload.php';
            }
            
            if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
                return new WP_Error('missing_dependency', __('PhpSpreadsheet library is missing. Please contact the plugin developer.', 'quizcourse-importer'));
            }
        }
        
        // Initialize data arrays
        $data = $this->initialize_data_arrays();
        
        try {
            // Load spreadsheet
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);
            
            // Process each worksheet
            foreach ($spreadsheet->getAllSheets() as $sheet) {
                $sheet_name = $sheet->getTitle();
                $sheet_key = $this->get_sheet_key($sheet_name);
                
                if (!$sheet_key || empty($mapping[$sheet_key])) {
                    continue; // Skip unrecognized or unmapped sheets
                }
                
                // Convert worksheet to array
                $rows = $sheet->toArray();
                
                // Get header row
                $headers = array_shift($rows);
                
                // Process the data for this sheet
                $sheet_data = array();
                foreach ($rows as $row) {
                    // Skip empty rows
                    if ($this->is_empty_row($row)) {
                        continue;
                    }
                    
                    $row_data = array();
                    foreach ($headers as $col_index => $header) {
                        if (isset($row[$col_index])) {
                            $row_data[$header] = $row[$col_index];
                        }
                    }
                    
                    $sheet_data[] = $row_data;
                }
                
                // Map the data according to the field mapping
                $data[$sheet_key] = $this->process_rows($sheet_data, $mapping[$sheet_key]);
            }
            
            return $this->validate_and_clean_data($data);
            
        } catch (Exception $e) {
            QCI_Logger::log('Excel processing error: ' . $e->getMessage(), 'error');
            return new WP_Error('excel_processing_error', $e->getMessage());
        }
    }

    /**
     * Read CSV file and convert to associative array.
     * 
     * @param string $file_path Path to the CSV file.
     * @return array|WP_Error CSV data or error.
     */
    private function read_csv($file_path) {
        $csv_data = array();
        
        // Determine if the CSV contains multiple sheets or just one
        $file_content = file_get_contents($file_path);
        if ($file_content === false) {
            return new WP_Error('file_read_error', __('Unable to read the CSV file.', 'quizcourse-importer'));
        }
        
        // Check if this is a multi-sheet CSV (with sheet markers)
        if (preg_match_all('/^###SHEET:(.+?)$/m', $file_content, $matches)) {
            // Multi-sheet CSV format
            $sheet_sections = preg_split('/^###SHEET:.+?$/m', $file_content);
            array_shift($sheet_sections); // Remove the part before the first sheet marker
            
            foreach ($matches[1] as $i => $sheet_name) {
                $sheet_content = $sheet_sections[$i];
                $csv_data[$sheet_name] = $this->parse_csv_content($sheet_content);
            }
        } else {
            // Single sheet CSV - determine the sheet type from filename
            $filename = basename($file_path, '.csv');
            $sheet_key = $this->get_sheet_key($filename);
            $sheet_name = $sheet_key ? $this->sheet_types[$sheet_key] : 'Sheet1';
            
            $csv_data[$sheet_name] = $this->parse_csv_content($file_content);
        }
        
        return $csv_data;
    }

    /**
     * Parse CSV content string into array of data.
     * 
     * @param string $content CSV content.
     * @return array Parsed CSV data.
     */
    private function parse_csv_content($content) {
        $rows = array();
        $lines = explode("\n", trim($content));
        
        // Get headers
        $headers = str_getcsv(array_shift($lines));
        
        // Process data rows
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            $row_data = array();
            $values = str_getcsv($line);
            
            foreach ($headers as $i => $header) {
                if (isset($values[$i])) {
                    $row_data[$header] = $values[$i];
                } else {
                    $row_data[$header] = '';
                }
            }
            
            $rows[] = $row_data;
        }
        
        return $rows;
    }

    /**
     * Process rows according to mapping.
     * 
     * @param array $rows Raw data rows.
     * @param array $mapping Field mapping for this sheet.
     * @return array Processed rows.
     */
    private function process_rows($rows, $mapping) {
        $processed_rows = array();
        
        foreach ($rows as $row) {
            $processed_row = array();
            
            foreach ($mapping as $sheet_field => $db_field) {
                if ($db_field && isset($row[$sheet_field])) {
                    $processed_row[$db_field] = $row[$sheet_field];
                }
            }
            
            // Only add non-empty rows
            if (!empty($processed_row)) {
                $processed_rows[] = $processed_row;
            }
        }
        
        return $processed_rows;
    }

    /**
     * Initialize data arrays for all sheet types.
     * 
     * @return array Empty data structure.
     */
    private function initialize_data_arrays() {
        $data = array();
        
        foreach ($this->sheet_types as $key => $name) {
            $data[$key] = array();
        }
        
        return $data;
    }

    /**
     * Get the internal sheet key from a display sheet name.
     * 
     * @param string $sheet_name Sheet name from file.
     * @return string|bool Internal sheet key or false if not recognized.
     */
    private function get_sheet_key($sheet_name) {
        $sheet_name = strtolower(trim($sheet_name));
        
        // Direct match
        if (isset($this->sheet_types[$sheet_name])) {
            return $sheet_name;
        }
        
        // Case-insensitive match with sheet types values
        $flip = array_map('strtolower', $this->sheet_types);
        $flip = array_flip($flip);
        
        if (isset($flip[$sheet_name])) {
            return $flip[$sheet_name];
        }
        
        // Partial match (e.g. "course" for "courses")
        foreach (array_keys($this->sheet_types) as $key) {
            if (strpos($sheet_name, rtrim($key, 's')) !== false) {
                return $key;
            }
        }
        
        return false;
    }

    /**
     * Check if a row is empty.
     * 
     * @param array $row Row data.
     * @return bool True if the row is empty.
     */
    private function is_empty_row($row) {
        if (empty($row)) {
            return true;
        }
        
        foreach ($row as $value) {
            if (!empty($value) && $value !== null && $value !== '') {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Validate the overall data structure and clean up any issues.
     * 
     * @param array $data Processed data.
     * @return array Validated and cleaned data.
     */
    private function validate_and_clean_data($data) {
        // Add temporary IDs for new items to enable relationships
        $data = $this->add_temp_ids($data);
        
        // Validate required sheets
        if (empty($data['courses']) && empty($data['quizzes'])) {
            return new WP_Error(
                'missing_data', 
                __('The uploaded file must contain at least Courses or Quizzes data.', 'quizcourse-importer')
            );
        }
        
        // Validate questions data
        if (!empty($data['quizzes']) && empty($data['questions'])) {
            return new WP_Error(
                'missing_questions',
                __('Quizzes were found but no questions data was provided. Each quiz must have questions.', 'quizcourse-importer')
            );
        }
        
        // Validate answers data
        if (!empty($data['questions']) && empty($data['answers'])) {
            return new WP_Error(
                'missing_answers',
                __('Questions were found but no answers data was provided. Each question must have answers.', 'quizcourse-importer')
            );
        }
        
        // More validation can be added here...
        
        return $data;
    }

    /**
     * Add temporary IDs to data items for establishing relationships.
     * 
     * @param array $data Processed data.
     * @return array Data with temporary IDs.
     */
    private function add_temp_ids($data) {
        // Add temp_id to courses
        foreach ($data['courses'] as $index => $course) {
            $data['courses'][$index]['temp_id'] = 'course_' . ($index + 1);
        }
        
        // Add temp_id to sections and link to courses
        foreach ($data['sections'] as $index => $section) {
            $data['sections'][$index]['temp_id'] = 'section_' . ($index + 1);
            
            // Link to course if course_reference exists
            if (!empty($section['course_reference'])) {
                $course_found = false;
                foreach ($data['courses'] as $course_index => $course) {
                    if (isset($course['title']) && $course['title'] == $section['course_reference']) {
                        $data['sections'][$index]['course_temp_id'] = $data['courses'][$course_index]['temp_id'];
                        $course_found = true;
                        break;
                    }
                }
                
                if (!$course_found) {
                    // Log warning but don't stop the import
                    QCI_Logger::log(
                        sprintf(
                            __('Section "%s" references course "%s" which was not found in the import data.', 'quizcourse-importer'),
                            $section['title'] ?? $index,
                            $section['course_reference']
                        ),
                        'warning'
                    );
                }
            }
        }
        
        // Add temp_id to quizzes and link to sections
        foreach ($data['quizzes'] as $index => $quiz) {
            $data['quizzes'][$index]['temp_id'] = 'quiz_' . ($index + 1);
            
            // Link to section if section_reference exists
            if (!empty($quiz['section_reference'])) {
                $section_found = false;
                foreach ($data['sections'] as $section_index => $section) {
                    if (isset($section['title']) && $section['title'] == $quiz['section_reference']) {
                        $data['quizzes'][$index]['section_temp_id'] = $data['sections'][$section_index]['temp_id'];
                        $section_found = true;
                        break;
                    }
                }
                
                if (!$section_found) {
                    // Log warning
                    QCI_Logger::log(
                        sprintf(
                            __('Quiz "%s" references section "%s" which was not found in the import data.', 'quizcourse-importer'),
                            $quiz['title'] ?? $index,
                            $quiz['section_reference']
                        ),
                        'warning'
                    );
                }
            }
        }
        
        // Add temp_id to questions and link to quizzes
        foreach ($data['questions'] as $index => $question) {
            $data['questions'][$index]['temp_id'] = 'question_' . ($index + 1);
            
            // Link to quiz if quiz_reference exists
            if (!empty($question['quiz_reference'])) {
                $quiz_found = false;
                foreach ($data['quizzes'] as $quiz_index => $quiz) {
                    if (isset($quiz['title']) && $quiz['title'] == $question['quiz_reference']) {
                        $data['questions'][$index]['quiz_temp_id'] = $data['quizzes'][$quiz_index]['temp_id'];
                        $quiz_found = true;
                        break;
                    }
                }
                
                if (!$quiz_found) {
                    // Log warning
                    QCI_Logger::log(
                        sprintf(
                            __('Question "%s" references quiz "%s" which was not found in the import data.', 'quizcourse-importer'),
                            $question['question'] ?? $index,
                            $question['quiz_reference']
                        ),
                        'warning'
                    );
                }
            }
        }
        
        // Link answers to questions
        foreach ($data['answers'] as $index => $answer) {
            $data['answers'][$index]['temp_id'] = 'answer_' . ($index + 1);
            
            // Link to question if question_reference exists
            if (!empty($answer['question_reference'])) {
                $question_found = false;
                foreach ($data['questions'] as $question_index => $question) {
                    if (isset($question['question']) && $question['question'] == $answer['question_reference']) {
                        $data['answers'][$index]['question_temp_id'] = $data['questions'][$question_index]['temp_id'];
                        $question_found = true;
                        break;
                    }
                }
                
                if (!$question_found) {
                    // Log warning
                    QCI_Logger::log(
                        sprintf(
                            __('Answer "%s" references question "%s" which was not found in the import data.', 'quizcourse-importer'),
                            $answer['answer'] ?? $index,
                            $answer['question_reference']
                        ),
                        'warning'
                    );
                }
            }
        }
        
        return $data;
    }

    /**
     * Analyze the file and determine its structure.
     * This is used for the initial validation and to help with field mapping.
     * 
     * @param string $file_path Path to the uploaded file.
     * @return array|WP_Error File structure information or error.
     */
    public function analyze_file($file_path) {
        // Get file extension
        $file_ext = pathinfo($file_path, PATHINFO_EXTENSION);
        
        try {
            if ($file_ext === 'csv') {
                return $this->analyze_csv($file_path);
            } else if (in_array($file_ext, array('xlsx', 'xls'))) {
                return $this->analyze_excel($file_path);
            } else {
                return new WP_Error('invalid_file_type', __('Unsupported file type. Please upload a CSV or Excel file.', 'quizcourse-importer'));
            }
        } catch (Exception $e) {
            QCI_Logger::log('File analysis error: ' . $e->getMessage(), 'error');
            return new WP_Error('file_analysis_error', $e->getMessage());
        }
    }

    /**
     * Analyze CSV file structure.
     * 
     * @param string $file_path Path to the CSV file.
     * @return array CSV structure information.
     */
    private function analyze_csv($file_path) {
        // Get CSV data
        $csv_data = $this->read_csv($file_path);
        if (is_wp_error($csv_data)) {
            return $csv_data;
        }
        
        // Analyze structure
        $structure = array();
        
        foreach ($csv_data as $sheet_name => $rows) {
            if (empty($rows)) continue;
            
            // Get available fields from the first row (headers)
            $fields = array_keys($rows[0]);
            
            // Determine sheet type
            $sheet_key = $this->get_sheet_key($sheet_name);
            if (!$sheet_key) {
                // Try to guess the sheet type based on field names
                $sheet_key = $this->guess_sheet_type($fields);
            }
            
            $structure[$sheet_key ?: $sheet_name] = array(
                'name' => $sheet_name,
                'fields' => $fields,
                'row_count' => count($rows),
                'detected_type' => $sheet_key ?: 'unknown'
            );
        }
        
        return array(
            'file_type' => 'csv',
            'sheets' => $structure
        );
    }

    /**
     * Analyze Excel file structure.
     * 
     * @param string $file_path Path to the Excel file.
     * @return array Excel structure information.
     */
    private function analyze_excel($file_path) {
        // Make sure PhpSpreadsheet is available
        if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
            // Try to load from composer autoload first
            $autoload_path = QCI_PLUGIN_DIR . 'vendor/autoload.php';
            if (file_exists($autoload_path)) {
                require_once $autoload_path;
            } else {
                // Fallback to bundled library
                require_once QCI_PLUGIN_DIR . 'libraries/PhpSpreadsheet/vendor/autoload.php';
            }
            
            if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
                return new WP_Error('missing_dependency', __('PhpSpreadsheet library is missing. Please contact the plugin developer.', 'quizcourse-importer'));
            }
        }
        
        // Load spreadsheet
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);
        
        // Analyze structure
        $structure = array();
        
        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $sheet_name = $sheet->getTitle();
            
            // Get the first row as headers
            $highest_column = $sheet->getHighestDataColumn(1);
            $headers = $sheet->rangeToArray('A1:' . $highest_column . '1', null, true, false)[0];
            
            // Filter out empty headers
            $headers = array_filter($headers, function($value) {
                return !empty($value);
            });
            
            // Count rows
            $highest_row = $sheet->getHighestDataRow();
            $row_count = $highest_row > 1 ? $highest_row - 1 : 0; // Subtract header row
            
            // Determine sheet type
            $sheet_key = $this->get_sheet_key($sheet_name);
            if (!$sheet_key) {
                // Try to guess the sheet type based on field names
                $sheet_key = $this->guess_sheet_type($headers);
            }
            
            $structure[$sheet_key ?: $sheet_name] = array(
                'name' => $sheet_name,
                'fields' => $headers,
                'row_count' => $row_count,
                'detected_type' => $sheet_key ?: 'unknown'
            );
        }
        
        return array(
            'file_type' => 'excel',
            'sheets' => $structure
        );
    }

    /**
     * Guess the sheet type based on field names.
     * 
     * @param array $fields Available fields in the sheet.
     * @return string|bool Guessed sheet type or false if can't determine.
     */
    private function guess_sheet_type($fields) {
        // Typical field patterns for each sheet type
        $patterns = array(
            'courses' => array('course', 'title', 'description'),
            'sections' => array('section', 'course_id'),
            'quizzes' => array('quiz', 'question_ids', 'title'),
            'questions' => array('question', 'type', 'quiz_id'),
            'answers' => array('answer', 'correct', 'question_id')
        );
        
        $scores = array();
        
        // Convert fields to lowercase for comparison
        $lowercase_fields = array_map('strtolower', $fields);
        
        foreach ($patterns as $type => $pattern_fields) {
            $scores[$type] = 0;
            
            foreach ($pattern_fields as $pattern_field) {
                foreach ($lowercase_fields as $field) {
                    if (strpos($field, $pattern_field) !== false) {
                        $scores[$type]++;
                        break;
                    }
                }
            }
        }
        
        // Get type with highest score
        arsort($scores);
        $top_type = key($scores);
        
        // Only return a type if the score is at least 2
        return $scores[$top_type] >= 2 ? $top_type : false;
    }

    /**
     * Get database field options for each sheet type.
     * These are used for field mapping during import.
     * 
     * @return array Database field options.
     */
    public function get_db_field_options() {
        return array(
            'courses' => array(
                'title' => __('Title', 'quizcourse-importer'),
                'description' => __('Description', 'quizcourse-importer'),
                'image' => __('Image URL', 'quizcourse-importer'),
                'category_ids' => __('Category IDs', 'quizcourse-importer'),
                'status' => __('Status', 'quizcourse-importer'),
                'author_id' => __('Author ID', 'quizcourse-importer'),
                'date_created' => __('Date Created', 'quizcourse-importer'),
                'ordering' => __('Ordering', 'quizcourse-importer')
            ),
            'sections' => array(
                'title' => __('Title', 'quizcourse-importer'),
                'description' => __('Description', 'quizcourse-importer'),
                'course_reference' => __('Course Reference', 'quizcourse-importer'),
                'ordering' => __('Ordering', 'quizcourse-importer')
            ),
            'quizzes' => array(
                'title' => __('Title', 'quizcourse-importer'),
                'description' => __('Description', 'quizcourse-importer'),
                'quiz_image' => __('Image URL', 'quizcourse-importer'),
                'quiz_category_id' => __('Category ID', 'quizcourse-importer'),
                'section_reference' => __('Section Reference', 'quizcourse-importer'),
                'published' => __('Published', 'quizcourse-importer'),
                'author_id' => __('Author ID', 'quizcourse-importer'),
                'ordering' => __('Ordering', 'quizcourse-importer')
            ),
            'questions' => array(
                'question' => __('Question Text', 'quizcourse-importer'),
                'question_title' => __('Question Title', 'quizcourse-importer'),
                'question_image' => __('Image URL', 'quizcourse-importer'),
                'type' => __('Question Type', 'quizcourse-importer'),
                'category_id' => __('Category ID', 'quizcourse-importer'),
                'tag_id' => __('Tag ID', 'quizcourse-importer'),
                'quiz_reference' => __('Quiz Reference', 'quizcourse-importer'),
                'wrong_answer_text' => __('Wrong Answer Text', 'quizcourse-importer'),
                'right_answer_text' => __('Right Answer Text', 'quizcourse-importer'),
                'question_hint' => __('Hint', 'quizcourse-importer'),
                'explanation' => __('Explanation', 'quizcourse-importer'),
                'published' => __('Published', 'quizcourse-importer'),
                'weight' => __('Weight', 'quizcourse-importer'),
                'ordering' => __('Ordering', 'quizcourse-importer')
            ),
            'answers' => array(
                'answer' => __('Answer Text', 'quizcourse-importer'),
                'image' => __('Image URL', 'quizcourse-importer'),
                'correct' => __('Is Correct', 'quizcourse-importer'),
                'question_reference' => __('Question Reference', 'quizcourse-importer'),
                'weight' => __('Weight', 'quizcourse-importer'),
                'ordering' => __('Ordering', 'quizcourse-importer')
            )
        );
    }

    /**
     * Create a mapping suggestion based on file analysis.
     * 
     * @param array $file_structure File structure information.
     * @return array Suggested mapping.
     */
    public function suggest_mapping($file_structure) {
        $mapping = array();
        $db_fields = $this->get_db_field_options();
        
        foreach ($file_structure['sheets'] as $sheet_key => $sheet_info) {
            $sheet_type = $sheet_info['detected_type'];
            
            // Skip if the sheet type is not recognized
            if ($sheet_type === 'unknown' || !isset($db_fields[$sheet_type])) {
                continue;
            }
            
            $mapping[$sheet_type] = array();
            
            // Try to match file fields with database fields
            foreach ($sheet_info['fields'] as $file_field) {
                $file_field_lower = strtolower($file_field);
                $best_match = null;
                $best_score = 0;
                
                foreach ($db_fields[$sheet_type] as $db_field => $label) {
                    // Calculate match score
                    $db_field_lower = strtolower($db_field);
                    $score = 0;
                    
                    // Exact match
                    if ($file_field_lower === $db_field_lower) {
                        $score = 100;
                    } 
                    // Contains the db field
                    else if (strpos($file_field_lower, $db_field_lower) !== false) {
                        $score = 75;
                    }
                    // DB field contains the file field
                    else if (strpos($db_field_lower, $file_field_lower) !== false) {
                        $score = 50;
                    }
                    // Partial match based on similar words
                    else {
                        $file_words = explode('_', $file_field_lower);
                        $db_words = explode('_', $db_field_lower);
                        
                        $common_words = array_intersect($file_words, $db_words);
                        if (!empty($common_words)) {
                            $score = 25 * count($common_words);
                        }
                    }
                    
                    if ($score > $best_score) {
                        $best_score = $score;
                        $best_match = $db_field;
                    }
                }
                
                $mapping[$sheet_type][$file_field] = $best_score >= 25 ? $best_match : '';
            }
        }
        
        return $mapping;
    }
}

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
     * Available record types for import
     * 
     * @var array
     */
    private $record_types = array(
        'course'   => 'Course',
        'quiz'     => 'Quiz',
        'question' => 'Question',
        'answer'   => 'Answer'
    );

    /**
     * Process the uploaded file and extract data based on mapping.
     * 
     * @param string $file_path Path to the uploaded file.
     * @param array $mapping Field mapping configuration.
     * @param bool $single_sheet Whether to process file as a single sheet.
     * @return array|WP_Error Processed data or error.
     */
    public function process_file($file_path, $mapping, $single_sheet = true) {
        try {
            // Get file extension
            $file_ext = pathinfo($file_path, PATHINFO_EXTENSION);
            
            // Process based on file type
            if ($single_sheet) {
                // Process as a single sheet (all data in one sheet)
                if ($file_ext === 'csv') {
                    return $this->process_single_sheet_csv($file_path, $mapping);
                } else if (in_array($file_ext, array('xlsx', 'xls'))) {
                    return $this->process_single_sheet_excel($file_path, $mapping);
                } else {
                    return new WP_Error('invalid_file_type', __('Unsupported file type. Please upload a CSV or Excel file.', 'quizcourse-importer'));
                }
            } else {
                // Process as multi-sheet (original functionality)
                if ($file_ext === 'csv') {
                    return $this->process_csv($file_path, $mapping);
                } else if (in_array($file_ext, array('xlsx', 'xls'))) {
                    return $this->process_excel($file_path, $mapping);
                } else {
                    return new WP_Error('invalid_file_type', __('Unsupported file type. Please upload a CSV or Excel file.', 'quizcourse-importer'));
                }
            }
        } catch (Exception $e) {
            QCI_Logger::log('File processing error: ' . $e->getMessage(), 'error');
            return new WP_Error('file_processing_error', $e->getMessage());
        }
    }

    /**
     * Process a single sheet Excel file.
     * 
     * @param string $file_path Path to the Excel file.
     * @param array $mapping Field mapping configuration.
     * @return array|WP_Error Processed data or error.
     */
    private function process_single_sheet_excel($file_path, $mapping) {
        // Make sure PhpSpreadsheet is available
        if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
            // Try to load from composer autoload first
            $autoload_paths = array(
                // Plugin's vendor directory
                QCI_PLUGIN_DIR . 'vendor/autoload.php',
                // WordPress root vendor directory
                ABSPATH . 'vendor/autoload.php',
                // Parent directory vendor
                dirname(QCI_PLUGIN_DIR) . '/vendor/autoload.php'
            );
            
            $loaded = false;
            foreach ($autoload_paths as $autoload_path) {
                if (file_exists($autoload_path)) {
                    require_once $autoload_path;
                    $loaded = true;
                    break;
                }
            }
            
            // Fallback to bundled library if exists
            if (!$loaded && file_exists(QCI_PLUGIN_DIR . 'libraries/PhpSpreadsheet/vendor/autoload.php')) {
                require_once QCI_PLUGIN_DIR . 'libraries/PhpSpreadsheet/vendor/autoload.php';
                $loaded = true;
            }
            
            if (!$loaded || !class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
                return new WP_Error('missing_dependency', __('PhpSpreadsheet library is missing. Please contact the plugin developer or install the library using Composer.', 'quizcourse-importer'));
            }
        }
        
        // Initialize data arrays
        $data = $this->initialize_data_arrays();
        
        try {
            // Load spreadsheet
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);
            $sheet = $spreadsheet->getActiveSheet();
            
            // Get the first row as headers
            $rows = $sheet->toArray();
            if (empty($rows)) {
                return new WP_Error('empty_file', __('The file appears to be empty.', 'quizcourse-importer'));
            }
            
            $headers = array_shift($rows);
            
            // Check for record_type column (required for single sheet)
            $type_column_index = $this->find_column_index($headers, array('record_type', 'type', 'entity_type'));
            if ($type_column_index === false) {
                return new WP_Error('missing_type_column', __('Missing required column: "record_type" or "type" or "entity_type". This column is needed to identify the type of each row.', 'quizcourse-importer'));
            }
            
            // Check for parent_reference column for relationships
            $parent_column_index = $this->find_column_index($headers, array('parent_id', 'parent_reference', 'parent'));
            
            // Process each row
            foreach ($rows as $row) {
                // Skip empty rows
                if ($this->is_empty_row($row)) {
                    continue;
                }
                
                // Get record type
                $record_type = strtolower(trim($row[$type_column_index]));
                
                // Skip rows with invalid record types
                if (!in_array($record_type, array_keys($this->record_types)) && 
                    !in_array($record_type, array('quizzes', 'questions', 'answers', 'courses'))) {
                    continue;
                }
                
                // Normalize record type (handle plurals)
                $record_type = $this->normalize_record_type($record_type);
                
                // Create row data
                $row_data = array();
                
                foreach ($headers as $index => $header) {
                    if (isset($mapping[$header]) && !empty($mapping[$header])) {
                        $mapped_field = $mapping[$header];
                        $row_data[$mapped_field] = $row[$index];
                    }
                }
                
                // Add parent reference if exists
                if ($parent_column_index !== false && !empty($row[$parent_column_index])) {
                    $parent_ref = $row[$parent_column_index];
                    
                    // Set appropriate reference field based on record type
                    switch ($record_type) {
                        case 'quiz':
                            $row_data['course_reference'] = $parent_ref;
                            break;
                        case 'question':
                            $row_data['quiz_reference'] = $parent_ref;
                            break;
                        case 'answer':
                            $row_data['question_reference'] = $parent_ref;
                            break;
                    }
                }
                
                // Add to appropriate data array
                if (!empty($row_data)) {
                    $data[$record_type . 's'][] = $row_data; // Add 's' for plural form
                }
            }
            
            return $this->validate_and_clean_data($data);
            
        } catch (Exception $e) {
            QCI_Logger::log('Excel processing error: ' . $e->getMessage(), 'error');
            return new WP_Error('excel_processing_error', $e->getMessage());
        }
    }

    /**
     * Process a single sheet CSV file.
     * 
     * @param string $file_path Path to the CSV file.
     * @param array $mapping Field mapping configuration.
     * @return array|WP_Error Processed data or error.
     */
    private function process_single_sheet_csv($file_path, $mapping) {
        // Check if file exists and is readable
        if (!file_exists($file_path) || !is_readable($file_path)) {
            return new WP_Error('file_error', __('Cannot read the CSV file.', 'quizcourse-importer'));
        }
        
        // Initialize data arrays
        $data = $this->initialize_data_arrays();
        
        // Open the CSV file
        $handle = fopen($file_path, 'r');
        if ($handle === false) {
            return new WP_Error('file_error', __('Failed to open the CSV file.', 'quizcourse-importer'));
        }
        
        // Read the first row to get headers
        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);
            return new WP_Error('csv_error', __('The CSV file is empty or has an invalid format.', 'quizcourse-importer'));
        }
        
        // Check for record_type column (required for single sheet)
        $type_column_index = $this->find_column_index($headers, array('record_type', 'type', 'entity_type'));
        if ($type_column_index === false) {
            fclose($handle);
            return new WP_Error('missing_type_column', __('Missing required column: "record_type" or "type" or "entity_type". This column is needed to identify the type of each row.', 'quizcourse-importer'));
        }
        
        // Check for parent_reference column for relationships
        $parent_column_index = $this->find_column_index($headers, array('parent_id', 'parent_reference', 'parent'));
        
        // Process each row
        while (($row = fgetcsv($handle)) !== false) {
            // Skip empty rows
            if (count(array_filter($row)) === 0) {
                continue;
            }
            
            // Get record type
            if (!isset($row[$type_column_index])) {
                continue; // Skip rows without record type
            }
            
            $record_type = strtolower(trim($row[$type_column_index]));
            
            // Skip rows with invalid record types
            if (!in_array($record_type, array_keys($this->record_types)) && 
                !in_array($record_type, array('quizzes', 'questions', 'answers', 'courses'))) {
                continue;
            }
            
            // Normalize record type (handle plurals)
            $record_type = $this->normalize_record_type($record_type);
            
            // Create row data
            $row_data = array();
            
            foreach ($headers as $index => $header) {
                if (isset($row[$index]) && isset($mapping[$header]) && !empty($mapping[$header])) {
                    $mapped_field = $mapping[$header];
                    $row_data[$mapped_field] = $row[$index];
                }
            }
            
            // Add parent reference if exists
            if ($parent_column_index !== false && isset($row[$parent_column_index]) && !empty($row[$parent_column_index])) {
                $parent_ref = $row[$parent_column_index];
                
                // Set appropriate reference field based on record type
                switch ($record_type) {
                    case 'quiz':
                        $row_data['course_reference'] = $parent_ref;
                        break;
                    case 'question':
                        $row_data['quiz_reference'] = $parent_ref;
                        break;
                    case 'answer':
                        $row_data['question_reference'] = $parent_ref;
                        break;
                }
            }
            
            // Add to appropriate data array
            if (!empty($row_data)) {
                $data[$record_type . 's'][] = $row_data; // Add 's' for plural form
            }
        }
        
        fclose($handle);
        
        return $this->validate_and_clean_data($data);
    }

    /**
     * Process CSV file (multi-sheet format).
     * 
     * @param string $file_path Path to the CSV file.
     * @param array $mapping Field mapping configuration.
     * @return array|WP_Error Processed data.
     */
    private function process_csv($file_path, $mapping) {
        // Check if file exists and is readable
        if (!file_exists($file_path) || !is_readable($file_path)) {
            return new WP_Error('file_error', __('Cannot read the CSV file.', 'quizcourse-importer'));
        }

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
     * Process Excel file (multi-sheet format).
     * 
     * @param string $file_path Path to the Excel file.
     * @param array $mapping Field mapping configuration.
     * @return array|WP_Error Processed data.
     */
    private function process_excel($file_path, $mapping) {
        // Make sure PhpSpreadsheet is available
        if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
            // Try to load from multiple possible locations
            $autoload_paths = array(
                // Plugin's vendor directory
                QCI_PLUGIN_DIR . 'vendor/autoload.php',
                // WordPress root vendor directory
                ABSPATH . 'vendor/autoload.php',
                // Parent directory vendor
                dirname(QCI_PLUGIN_DIR) . '/vendor/autoload.php'
            );
            
            $loaded = false;
            foreach ($autoload_paths as $autoload_path) {
                if (file_exists($autoload_path)) {
                    require_once $autoload_path;
                    $loaded = true;
                    break;
                }
            }
            
            // Fallback to bundled library if exists
            if (!$loaded && file_exists(QCI_PLUGIN_DIR . 'libraries/PhpSpreadsheet/vendor/autoload.php')) {
                require_once QCI_PLUGIN_DIR . 'libraries/PhpSpreadsheet/vendor/autoload.php';
                $loaded = true;
            }
            
            if (!$loaded || !class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
                return new WP_Error('missing_dependency', __('PhpSpreadsheet library is missing. Please contact the plugin developer or install the library using Composer.', 'quizcourse-importer'));
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
            $sheet_name = $sheet_key ? $this->record_types[$sheet_key] : 'Sheet1';
            
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
     * Initialize data arrays for all record types.
     * 
     * @return array Empty data structure.
     */
    private function initialize_data_arrays() {
        return array(
            'courses' => array(),
            'quizzes' => array(),
            'questions' => array(),
            'answers' => array()
        );
    }

    /**
     * Normalize record type to handle plurals and variations.
     * 
     * @param string $type Record type.
     * @return string Normalized record type.
     */
    private function normalize_record_type($type) {
        $type = strtolower(trim($type));
        
        // Handle plural forms
        if ($type === 'quizzes') {
            return 'quiz';
        } else if ($type === 'questions') {
            return 'question';
        } else if ($type === 'answers') {
            return 'answer';
        } else if ($type === 'courses') {
            return 'course';
        }
        
        return $type;
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
        if (isset($this->record_types[$sheet_name])) {
            return $sheet_name;
        }
        
        // Case-insensitive match with record types values
        $flip = array_map('strtolower', $this->record_types);
        $flip = array_flip($flip);
        
        if (isset($flip[$sheet_name])) {
            return $flip[$sheet_name];
        }
        
        // Partial match (e.g. "quiz" for "quizzes")
        if (strpos($sheet_name, 'course') !== false) {
            return 'course';
        } else if (strpos($sheet_name, 'quiz') !== false) {
            return 'quiz';
        } else if (strpos($sheet_name, 'question') !== false) {
            return 'question';
        } else if (strpos($sheet_name, 'answer') !== false) {
            return 'answer';
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
     * Find the index of a column by name from a list of possible names.
     * 
     * @param array $headers Row headers.
     * @param array $possible_names Possible column names.
     * @return int|false Column index or false if not found.
     */
    private function find_column_index($headers, $possible_names) {
        $headers = array_map('strtolower', $headers);
        
        foreach ($possible_names as $name) {
            $index = array_search(strtolower($name), $headers);
            if ($index !== false) {
                return $index;
            }
        }
        
        return false;
    }

    /**
     * Validate the overall data structure and clean up any issues.
     * 
     * @param array $data Processed data.
     * @return array|WP_Error Validated and cleaned data or error.
     */
    private function validate_and_clean_data($data) {
        // Add temporary IDs for new items to enable relationships
        $data = $this->add_temp_ids($data);
        
        // Validate that we have some usable data
        if (empty($data['courses']) && empty($data['quizzes'])) {
            return new WP_Error(
                'missing_data', 
                __('The uploaded file must contain at least Courses or Quizzes data.', 'quizcourse-importer')
            );
        }
        
        // For AysQuiz and FoxLMS compatibility - check specific required fields
        if (!empty($data['quizzes'])) {
            foreach ($data['quizzes'] as $index => $quiz) {
                // Make sure quiz has a title
                if (empty($quiz['title'])) {
                    return new WP_Error(
                        'missing_title',
                        __('One or more quizzes is missing a title. All quizzes must have a title.', 'quizcourse-importer')
                    );
                }
                
                // If empty description, set a default
                if (empty($quiz['description'])) {
                    $data['quizzes'][$index]['description'] = '';
                }
            }
        }
        
        // Check questions data
        if (!empty($data['quizzes']) && empty($data['questions'])) {
            return new WP_Error(
                'missing_questions',
                __('Quizzes were found but no questions data was provided. Each quiz must have questions.', 'quizcourse-importer')
            );
        }
        
        // Check questions types and format
        if (!empty($data['questions'])) {
            foreach ($data['questions'] as $index => $question) {
                // Make sure each question has text
                if (empty($question['question'])) {
                    return new WP_Error(
                        'missing_question_text',
                        __('One or more questions is missing question text. All questions must have text.', 'quizcourse-importer')
                    );
                }
                
                // Set default type if missing
                if (empty($question['type'])) {
                    $data['questions'][$index]['type'] = 'radio'; // Default for AysQuiz
                }
            }
        }
        
        // Validate answers data
        if (!empty($data['questions']) && empty($data['answers'])) {
            return new WP_Error(
                'missing_answers',
                __('Questions were found but no answers data was provided. Each question must have answers.', 'quizcourse-importer')
            );
        }
        
        // Check answers format
        if (!empty($data['answers'])) {
            foreach ($data['answers'] as $index => $answer) {
                // Make sure each answer has text
                if (empty($answer['answer'])) {
                    return new WP_Error(
                        'missing_answer_text',
                        __('One or more answers is missing answer text. All answers must have text.', 'quizcourse-importer')
                    );
                }
                
                // Make sure is_correct is set
                if (!isset($answer['correct'])) {
                    $data['answers'][$index]['correct'] = 0; // Default to incorrect
                }
            }
        }
        
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
            
            // Add default values for required fields if missing
            if (empty($course['title'])) {
                $data['courses'][$index]['title'] = 'Course ' . ($index + 1);
            }
        }
        
        // Add temp_id to quizzes and link to courses
        foreach ($data['quizzes'] as $index => $quiz) {
            $data['quizzes'][$index]['temp_id'] = 'quiz_' . ($index + 1);
            
            // Link to course if course_reference exists
            if (!empty($quiz['course_reference'])) {
                $course_found = false;
                foreach ($data['courses'] as $course_index => $course) {
                    // Use ID if available, otherwise title
                    $course_id = !empty($course['id']) ? $course['id'] : '';
                    $course_title = !empty($course['title']) ? $course['title'] : '';
                    
                    if ($course_id == $quiz['course_reference'] || $course_title == $quiz['course_reference']) {
                        $data['quizzes'][$index]['course_temp_id'] = $data['courses'][$course_index]['temp_id'];
                        $course_found = true;
                        break;
                    }
                }
                
                if (!$course_found) {
                    // Log warning but don't stop the import
                    QCI_Logger::log(
                        sprintf(
                            __('Quiz "%s" references course "%s" which was not found in the import data.', 'quizcourse-importer'),
                            $quiz['title'] ?? $index,
                            $quiz['course_reference']
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
                    // Use ID if available, otherwise title
                    $quiz_id = !empty($quiz['id']) ? $quiz['id'] : '';
                    $quiz_title = !empty($quiz['title']) ? $quiz['title'] : '';
                    
                    if ($quiz_id == $question['quiz_reference'] || $quiz_title == $question['quiz_reference']) {
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
                           isset($question['question']) ? substr($question['question'], 0, 30) . '...' : $index,
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
                   // Use ID if available, otherwise check by question text
                   $question_id = !empty($question['id']) ? $question['id'] : '';
                   $question_text = !empty($question['question']) ? $question['question'] : '';
                   
                   if ($question_id == $answer['question_reference'] || $question_text == $answer['question_reference']) {
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
                           isset($answer['answer']) ? substr($answer['answer'], 0, 30) . '...' : $index,
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
    * @param bool $single_sheet Whether to analyze as a single sheet.
    * @return array|WP_Error File structure information or error.
    */
   public function analyze_file($file_path, $single_sheet = true) {
       // Get file extension
       $file_ext = pathinfo($file_path, PATHINFO_EXTENSION);
       
       try {
           if ($file_ext === 'csv') {
               return $single_sheet ? $this->analyze_single_sheet_csv($file_path) : $this->analyze_csv($file_path);
           } else if (in_array($file_ext, array('xlsx', 'xls'))) {
               return $single_sheet ? $this->analyze_single_sheet_excel($file_path) : $this->analyze_excel($file_path);
           } else {
               return new WP_Error('invalid_file_type', __('Unsupported file type. Please upload a CSV or Excel file.', 'quizcourse-importer'));
           }
       } catch (Exception $e) {
           QCI_Logger::log('File analysis error: ' . $e->getMessage(), 'error');
           return new WP_Error('file_analysis_error', $e->getMessage());
       }
   }

   /**
    * Analyze structure of a single sheet CSV file.
    * 
    * @param string $file_path Path to the CSV file.
    * @return array|WP_Error CSV structure information.
    */
   private function analyze_single_sheet_csv($file_path) {
       // Open the CSV file
       $handle = fopen($file_path, 'r');
       if ($handle === false) {
           return new WP_Error('file_error', __('Failed to open the CSV file.', 'quizcourse-importer'));
       }

       // Read the first row to get headers
       $headers = fgetcsv($handle);
       if ($headers === false) {
           fclose($handle);
           return new WP_Error('csv_error', __('The CSV file is empty or has an invalid format.', 'quizcourse-importer'));
       }

       // Check for record_type column
       $type_column_index = $this->find_column_index($headers, array('record_type', 'type', 'entity_type'));
       if ($type_column_index === false) {
           fclose($handle);
           return new WP_Error('missing_type_column', __('Missing required column: "record_type" or "type" or "entity_type". This column is needed to identify the type of each row.', 'quizcourse-importer'));
       }

       // Read rows to determine types and create structure
       $record_types = array();
       $preview_data = array();
       $row_count = 0;
       $max_preview = 5; // Maximum number of rows for preview

       while (($row = fgetcsv($handle)) !== false && $row_count < 50) { // Check first 50 rows
           if (count(array_filter($row)) === 0) {
               continue; // Skip empty rows
           }
           
           if (!isset($row[$type_column_index])) {
               continue; // Skip if no type
           }
           
           $record_type = strtolower(trim($row[$type_column_index]));
           $record_type = $this->normalize_record_type($record_type);
           
           if (!in_array($record_type, array_keys($this->record_types))) {
               continue; // Skip if invalid type
           }
           
           // Count types
           if (!isset($record_types[$record_type])) {
               $record_types[$record_type] = 0;
           }
           $record_types[$record_type]++;
           
           // Collect preview data
           if (count($preview_data) < $max_preview) {
               $row_data = array();
               foreach ($headers as $i => $header) {
                   if (isset($row[$i])) {
                       $row_data[$header] = $row[$i];
                   } else {
                       $row_data[$header] = '';
                   }
               }
               $preview_data[] = $row_data;
           }
           
           $row_count++;
       }

       fclose($handle);

       // Return structure information
       return array(
           'file_type' => 'single_sheet_csv',
           'headers' => $headers,
           'record_types' => $record_types,
           'total_rows' => $row_count,
           'preview_data' => $preview_data
       );
   }

   /**
    * Analyze structure of a single sheet Excel file.
    * 
    * @param string $file_path Path to the Excel file.
    * @return array|WP_Error Excel structure information.
    */
   private function analyze_single_sheet_excel($file_path) {
       // Make sure PhpSpreadsheet is available
       if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
           // Try to load from multiple possible locations
           $autoload_paths = array(
               // Plugin's vendor directory
               QCI_PLUGIN_DIR . 'vendor/autoload.php',
               // WordPress root vendor directory
               ABSPATH . 'vendor/autoload.php',
               // Parent directory vendor
               dirname(QCI_PLUGIN_DIR) . '/vendor/autoload.php'
           );
           
           $loaded = false;
           foreach ($autoload_paths as $autoload_path) {
               if (file_exists($autoload_path)) {
                   require_once $autoload_path;
                   $loaded = true;
                   break;
               }
           }
           
           // Fallback to bundled library if exists
           if (!$loaded && file_exists(QCI_PLUGIN_DIR . 'libraries/PhpSpreadsheet/vendor/autoload.php')) {
               require_once QCI_PLUGIN_DIR . 'libraries/PhpSpreadsheet/vendor/autoload.php';
               $loaded = true;
           }
           
           if (!$loaded || !class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
               return new WP_Error('missing_dependency', __('PhpSpreadsheet library is missing. Please contact the plugin developer or install the library using Composer.', 'quizcourse-importer'));
           }
       }
       
       try {
           // Create a reader based on file extension
           $file_ext = pathinfo($file_path, PATHINFO_EXTENSION);
           if ($file_ext === 'xlsx') {
               $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
           } else {
               $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
           }
           
           // Set reader to read only to improve performance
           $reader->setReadDataOnly(true);
           
           // Load the spreadsheet
           $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);
           $sheet = $spreadsheet->getActiveSheet();
           
           // Get the first row as headers
           $rows = $sheet->toArray();
           if (empty($rows)) {
               return new WP_Error('empty_file', __('The Excel file appears to be empty.', 'quizcourse-importer'));
           }
           
           $headers = array_shift($rows);
           
           // Check for record_type column
           $type_column_index = $this->find_column_index($headers, array('record_type', 'type', 'entity_type'));
           if ($type_column_index === false) {
               return new WP_Error('missing_type_column', __('Missing required column: "record_type" or "type" or "entity_type". This column is needed to identify the type of each row.', 'quizcourse-importer'));
           }
           
           // Analyze rows to determine types and create structure
           $record_types = array();
           $preview_data = array();
           $row_count = 0;
           $max_preview = 5; // Maximum number of rows for preview
           
           foreach ($rows as $row) {
               if ($this->is_empty_row($row)) {
                   continue;
               }
               
               if (!isset($row[$type_column_index])) {
                   continue; // Skip if no type
               }
               
               $record_type = strtolower(trim($row[$type_column_index]));
               $record_type = $this->normalize_record_type($record_type);
               
               if (!in_array($record_type, array_keys($this->record_types))) {
                   continue; // Skip if invalid type
               }
               
               // Count types
               if (!isset($record_types[$record_type])) {
                   $record_types[$record_type] = 0;
               }
               $record_types[$record_type]++;
               
               // Collect preview data
               if (count($preview_data) < $max_preview) {
                   $row_data = array();
                   foreach ($headers as $i => $header) {
                       if (isset($row[$i])) {
                           $row_data[$header] = $row[$i];
                       } else {
                           $row_data[$header] = '';
                       }
                   }
                   $preview_data[] = $row_data;
               }
               
               $row_count++;
               
               if ($row_count >= 50) { // Only check first 50 rows
                   break;
               }
           }
           
           // Return structure information
           return array(
               'file_type' => 'single_sheet_excel',
               'headers' => $headers,
               'record_types' => $record_types,
               'total_rows' => count($rows),
               'preview_data' => $preview_data
           );
           
       } catch (Exception $e) {
           return new WP_Error('excel_error', __('Error processing Excel file: ', 'quizcourse-importer') . $e->getMessage());
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
           // Try to load from multiple possible locations
           $autoload_paths = array(
               // Plugin's vendor directory
               QCI_PLUGIN_DIR . 'vendor/autoload.php',
               // WordPress root vendor directory
               ABSPATH . 'vendor/autoload.php',
               // Parent directory vendor
               dirname(QCI_PLUGIN_DIR) . '/vendor/autoload.php'
           );
           
           $loaded = false;
           foreach ($autoload_paths as $autoload_path) {
               if (file_exists($autoload_path)) {
                   require_once $autoload_path;
                   $loaded = true;
                   break;
               }
           }
           
           // Fallback to bundled library if exists
           if (!$loaded && file_exists(QCI_PLUGIN_DIR . 'libraries/PhpSpreadsheet/vendor/autoload.php')) {
               require_once QCI_PLUGIN_DIR . 'libraries/PhpSpreadsheet/vendor/autoload.php';
               $loaded = true;
           }
           
           if (!$loaded || !class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
               return new WP_Error('missing_dependency', __('PhpSpreadsheet library is missing. Please contact the plugin developer or install the library using Composer.', 'quizcourse-importer'));
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
           'course' => array('course', 'title', 'description'),
           'quiz' => array('quiz', 'question_ids', 'title'),
           'question' => array('question', 'type', 'quiz_id'),
           'answer' => array('answer', 'correct', 'question_id')
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
    * Get database field options for mapping.
    * These are specific to the wp_aysquiz and wp_foxlms tables.
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
               'section_ids' => __('Section IDs', 'quizcourse-importer'),
               'lesson_ids' => __('Lesson IDs', 'quizcourse-importer'),
               'question_ids' => __('Question IDs', 'quizcourse-importer'),
               'status' => __('Status', 'quizcourse-importer'),
               'date_created' => __('Date Created', 'quizcourse-importer'),
               'ordering' => __('Ordering', 'quizcourse-importer'),
               'author_id' => __('Author ID', 'quizcourse-importer'),
               'options' => __('Options (JSON)', 'quizcourse-importer')
           ),
           'quizzes' => array(
               'title' => __('Title', 'quizcourse-importer'),
               'description' => __('Description', 'quizcourse-importer'),
               'quiz_image' => __('Image URL', 'quizcourse-importer'),
               'quiz_category_id' => __('Category ID', 'quizcourse-importer'),
               'question_ids' => __('Question IDs', 'quizcourse-importer'),
               'published' => __('Published', 'quizcourse-importer'),
               'author_id' => __('Author ID', 'quizcourse-importer'),
               'create_date' => __('Creation Date', 'quizcourse-importer'),
               'ordering' => __('Ordering', 'quizcourse-importer'),
               'course_reference' => __('Course Reference', 'quizcourse-importer'),
               'options' => __('Options (JSON)', 'quizcourse-importer')
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
               'create_date' => __('Creation Date', 'quizcourse-importer'),
               'options' => __('Options (JSON)', 'quizcourse-importer')
           ),
           'answers' => array(
               'answer' => __('Answer Text', 'quizcourse-importer'),
               'image' => __('Image URL', 'quizcourse-importer'),
               'correct' => __('Is Correct', 'quizcourse-importer'),
               'question_reference' => __('Question Reference', 'quizcourse-importer'),
               'weight' => __('Weight', 'quizcourse-importer'),
               'ordering' => __('Ordering', 'quizcourse-importer'),
               'keyword' => __('Keyword', 'quizcourse-importer'),
               'options' => __('Options (JSON)', 'quizcourse-importer')
           )
       );
   }

   /**
    * Create a mapping suggestion based on file analysis.
    * 
    * @param array $file_structure File structure information.
    * @param bool $single_sheet Whether this is a single sheet file.
    * @return array Suggested mapping.
    */
   public function suggest_mapping($file_structure, $single_sheet = true) {
       $mapping = array();
       $db_fields = $this->get_db_field_options();
       
       if ($single_sheet) {
           // For single sheet, create mapping for all headers
           if (isset($file_structure['headers']) && is_array($file_structure['headers'])) {
               foreach ($file_structure['headers'] as $header) {
                   $mapping[$header] = $this->suggest_field_mapping($header);
               }
           }
       } else {
           // For multi-sheet, create mapping per sheet
           if (isset($file_structure['sheets']) && is_array($file_structure['sheets'])) {
               foreach ($file_structure['sheets'] as $sheet_key => $sheet_info) {
                   $sheet_type = $sheet_info['detected_type'];
                   
                   // Skip if the sheet type is not recognized
                   if ($sheet_type === 'unknown' || !isset($db_fields[$sheet_type])) {
                       continue;
                   }
                   
                   $mapping[$sheet_type] = array();
                   
                   // Try to match file fields with database fields
                   foreach ($sheet_info['fields'] as $file_field) {
                       $mapping[$sheet_type][$file_field] = $this->suggest_field_mapping($file_field, $sheet_type);
                   }
               }
           }
       }
       
       return $mapping;
   }

   /**
    * Suggest a field mapping for a given file field.
    * 
    * @param string $file_field The file field name.
    * @param string $entity_type Optional entity type for context.
    * @return string Suggested database field.
    */
   private function suggest_field_mapping($file_field, $entity_type = null) {
       $file_field_lower = strtolower($file_field);
       $db_fields = $this->get_db_field_options();
       
       // Skip record_type and similar fields
       if (in_array($file_field_lower, array('record_type', 'type', 'entity_type'))) {
           return 'record_type';
       }
       
       // Skip ID fields that are only for reference
       if ($file_field_lower === 'id' || $file_field_lower === 'record_id') {
           return 'id';
       }
       
       // If we know the entity type, check only those fields
       if ($entity_type && isset($db_fields[$entity_type])) {
           foreach ($db_fields[$entity_type] as $db_field => $label) {
               if ($this->is_field_match($file_field, $db_field)) {
                   return $db_field;
               }
           }
       } else {
           // Check all entity types
           foreach ($db_fields as $type => $fields) {
               foreach ($fields as $db_field => $label) {
                   if ($this->is_field_match($file_field, $db_field)) {
                       return $db_field;
                   }
               }
           }
       }
       
       // Special handling for common fields
       if (in_array($file_field_lower, array('title', 'name', 'heading'))) {
           return 'title';
       } else if (in_array($file_field_lower, array('description', 'desc', 'content'))) {
           return 'description';
       } else if (in_array($file_field_lower, array('parent_id', 'parent', 'parent_reference'))) {
           return 'parent_id';
       }
       
       return '';
   }

   /**
    * Check if a file field matches a database field.
    * 
    * @param string $file_field File field name.
    * @param string $db_field Database field name.
    * @return bool Whether they match.
    */
   private function is_field_match($file_field, $db_field) {
       // Normalize both fields
       $file_field = strtolower(str_replace(array(' ', '_', '-'), '', $file_field));
       $db_field = strtolower(str_replace(array(' ', '_', '-'), '', $db_field));
       
       // Direct match
       if ($file_field === $db_field) {
           return true;
       }
       
       // Check for contained match
       if (strpos($file_field, $db_field) !== false || strpos($db_field, $file_field) !== false) {
           return true;
       }
       
       // Check specific common patterns
       $patterns = array(
           'title' => array('name', 'heading'),
           'description' => array('desc', 'content', 'summary'),
           'question' => array('questiontext', 'questioncontent'),
           'answer' => array('answertext', 'answercontent'),
           'image' => array('img', 'picture', 'photo', 'thumbnail'),
           'correct' => array('iscorrect', 'isright', 'right'),
           'quiz_reference' => array('quizid', 'quiz'),
           'question_reference' => array('questionid', 'question'),
           'course_reference' => array('courseid', 'course')
       );
       
       foreach ($patterns as $key => $alternatives) {
           if ($db_field === $key) {
               foreach ($alternatives as $alt) {
                   if (strpos($file_field, $alt) !== false) {
                       return true;
                   }
               }
           }
       }
       
       return false;
   }

   /**
    * Get courses data from a file.
    * 
    * @param string $file_path Path to the file.
    * @param array $mapping Field mapping configuration.
    * @return array Courses data.
    */
   public function get_courses_data($file_path, $mapping) {
       $data = $this->process_file($file_path, $mapping);
       if (is_wp_error($data)) {
           return $data;
       }
       
       return $data['courses'];
   }
   
   /**
    * Get quizzes data from a file.
    * 
    * @param string $file_path Path to the file.
    * @param array $mapping Field mapping configuration.
    * @return array Quizzes data.
    */
   public function get_quizzes_data($file_path, $mapping) {
       $data = $this->process_file($file_path, $mapping);
       if (is_wp_error($data)) {
           return $data;
       }
       
       return $data['quizzes'];
   }
   
   /**
    * Get questions data from a file.
    * 
    * @param string $file_path Path to the file.
    * @param array $mapping Field mapping configuration.
    * @return array Questions data.
    */
   public function get_questions_data($file_path, $mapping) {
       $data = $this->process_file($file_path, $mapping);
       if (is_wp_error($data)) {
           return $data;
       }
       
       return $data['questions'];
   }
   
   /**
    * Get answers data from a file.
    * 
    * @param string $file_path Path to the file.
    * @param array $mapping Field mapping configuration.
    * @return array Answers data.
    */
   public function get_answers_data($file_path, $mapping) {
       $data = $this->process_file($file_path, $mapping);
       if (is_wp_error($data)) {
           return $data;
       }
       
       return $data['answers'];
   }
   
   /**
    * Count items in a file.
    * 
    * @param string $file_path Path to the file.
    * @param array $mapping Field mapping configuration.
    * @return array Count of items by type.
    */
   public function count_items($file_path, $mapping) {
       $data = $this->process_file($file_path, $mapping);
       if (is_wp_error($data)) {
           return $data;
       }
       
       return array(
           'courses' => count($data['courses']),
           'quizzes' => count($data['quizzes']),
           'questions' => count($data['questions']),
           'answers' => count($data['answers'])
       );
   }
}

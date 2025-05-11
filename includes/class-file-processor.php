<?php
/**
 * File processor class.
 * 
 * This class handles the processing of uploaded Excel/CSV files
 * and extracts structured data for import into AysQuiz and FoxLMS tables.
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
     * Database table structure references
     * 
     * @var array
     */
    private $table_structure = array(
        'courses' => array(
            'table' => 'wp_foxlms_courses',
            'fields' => array(
                'id', 'post_id', 'author_id', 'title', 'description', 'category_ids', 
                'question_ids', 'lesson_ids', 'section_ids', 'sections_count', 
                'questions_count', 'lessons_count', 'date_created', 'date_modified', 
                'image', 'status', 'trash_status', 'ordering', 'custom_post_id', 
                'conditions', 'options'
            ),
            'required' => array('title', 'description'),
            'defaults' => array(
                'author_id' => 1,
                'status' => 'publish',
                'trash_status' => '0',
                'date_created' => 'NOW()',
                'date_modified' => 'NOW()'
            )
        ),
        'quizzes' => array(
            'table' => 'wp_aysquiz_quizes',
            'fields' => array(
                'id', 'author_id', 'post_id', 'title', 'description', 'quiz_image', 
                'quiz_category_id', 'question_ids', 'ordering', 'published', 
                'create_date', 'quiz_url', 'options', 'intervals', 'product_id'
            ),
            'required' => array('title'),
            'defaults' => array(
                'author_id' => 1,
                'published' => 1,
                'create_date' => 'NOW()',
                'options' => '{"quiz_theme":"classic_light","color":"#27AE60","bg_color":"#fff","text_color":"#000","height":350,"width":400,"information_form":"disable","form_name":"","form_email":"","form_phone":"","enable_audio_autoplay":"off","ays_enable_background_music":"off","quiz_background_music":"","ays_quiz_bg_music_on_off":"on","ays_quiz_bg_music_volume":50,"active_date_check":"off","activeInterval":"2022-01-01 00:00:00","deactiveInterval":"2022-01-01 23:59:59","active_date_pre_start_message":"The quiz will be available soon!","active_date_message":"The quiz has expired!","active_date_message_soon_appear":false,"active_date_period":"string","show_quiz_title":"on","show_quiz_desc":"on","enable_randomize_answers":"off","enable_randomize_questions":"off","disable_answer_hover":"off","question_bank":"off","quiz_theme":"classic_light","enable_bg_music":"off","quiz_loader_text":"Loading...","limit_users":"off","limitation_message":"","redirect_after_submit":"off","submit_redirect_url":"","submit_redirect_delay":"","progress_bar":"on","enable_exit_button":"off","exit_redirect_url":"","image_width":"","image_height":"","enable_restart_button":"on","quiz_timer":"off","quiz_timer_in_title":"off","enable_navigation_bar":"on","enable_next_button":"on","enable_previous_button":"on","enable_arrows":"off","timer_text":"","quiz_timer_tooltip":"on","quiz_timer_tooltip_text":"This is timer for this quiz","redirect_after_timer_url":"","quiz_timer_red_warning":"off","quiz_timer_red_warning_second":"5","timer_warning_color":"#000","quiz_loader":"step1","show_attempts_count":"off","attempts_count":"1","attempts_count_by_quiz":"on","info_form":"disable","form_name":"off","form_email":"off","form_phone":"off","enable_mail_user":"off","mail_message":"","enable_double_opt_in":"off","enable_mail_admin":"off","mail_message_admin":"","send_mail_to_site_admin":"on","additional_emails":"","mail_message_admin_subjects":"Notification about quiz passes","user_mail_subject":"Thanks for your participation","user_mail_message":"Thanks for your participation","user_mail_img":"","mail_message_ydb":"on","mail_message_subject":"on","mail_message_body":"on","mail_message_from":"on","mail_message_from_name":"off","user_message":"off","user_phone":"off","enable_user_asnwers":"off","autofill_user_data":"off","enable_copy_protection":"off","enable_leave_page":"off","show_category":"off","show_question_category":"off","display_score":"by_percentage","quiz_display_score_by":"by_percantage","show_rate_after_rate":"on","show_score_by":"by_percentage","show_score_after_submit":"on","hide_score":"off","rate_form_title":"Did you like this quiz?","enable_box_shadow":"on","box_shadow_color":"#000","quiz_border_radius":"0","quiz_bg_image":"","quiz_border_width":"1","quiz_border_style":"solid","quiz_border_color":"#000","quiz_timer_in_title":"off","enable_background_gradient":"off","background_gradient_color_1":"#000","background_gradient_color_2":"#fff","quiz_gradient_direction":"vertical","animation_top":"shake","question_animation":"fade","animation_top_set":"shake","limit_users_by":"ip","limitation_message":"","redirect_after_submit":"off","submit_redirect_url":"","submit_redirect_delay":"","progress_bar_style":"second","enable_exit_button":"off","exit_redirect_url":"","image_sizing":"cover","quiz_bg_img_position":"center center","reshow_questions_pagination_numbering":"on","show_questions_numbering":"none","show_answers_numbering":"none","quiz_loader_custom_gif":"","disable_hover_effect":"off","limit_attempt_qty_by_js":"off","quiz_enable_logged_users":"off","question_count_per_page":"1","question_count_message":"Thank you for your response","enable_rw_asnwers_sounds":"off","active_date_pre_start_message":"The quiz will be available soon!","active_date_message":"The quiz has expired!","active_date_msg_descr":"","active_date_message_soon_appear_title":"The quiz will be available soon!","active_date_message_soon_appear":"","end_date_message_show":"","time_left":"off","enable_questions_reporting":"off","enable_questions_reporting_user":"off","questions_reporting_button_text":"Click here to report this question","quiz_notifications_by":"hide","quiz_notifications_title_for_all":"ALL","quiz_notifications_title_for_logged":"LOGGED","quiz_notifications_title_for_not_logged":"NOT LOGGED","display_meterial_type":"default","quiz_box_shadow_opacity":"0.4","quiz_box_shadow_blur":"5","quiz_box_shadow_spread":"0","quiz_box_shadow_hoffset":"0","quiz_box_shadow_voffset":"4","quiz_question_text_alignment":"center","quiz_theme_data_color":"#27AE60","quiz_theme_data_description_color":"#27AE60","quiz_theme_data_text_color":"#333","quiz_theme_data_answers_box_shadow":"rgba(0,0,0,0)","questions_hint_button_value":"Click for hint","enable_early_finish":"off","enable_next_button_mobile":"off","answers_sort_select":"default","send_results_user":"off","send_interval_msg":"off","additional_emails":[],"create_date":"2022-11-03 18:30:40","allow_collecting_logged_in_users_data":"off","quiz_pass_score":"0","information_form":"disable","active_date_intervals":"off","generate_password":"general","timer_countdown":"on","hide_correct_answers":"off","quiz_waiting_time":"off","waiting_time":"0","limit_attempts_by_quiz":"off","randomize_all":"off","answer_maximum_length":"40000","answer_minimum_length":"0","answer_unset":"on","submit_type":"after_timer_text"}'
            )
        ),
        'quiz_categories' => array(
            'table' => 'wp_aysquiz_quizcategories',
            'fields' => array(
                'id', 'author_id', 'title', 'description', 'published', 'options'
            ),
            'required' => array('title'),
            'defaults' => array(
                'author_id' => 1,
                'published' => 1
            )
        ),
        'questions' => array(
            'table' => 'wp_aysquiz_questions',
            'fields' => array(
                'id', 'author_id', 'category_id', 'tag_id', 'question', 'question_title', 
                'question_image', 'wrong_answer_text', 'right_answer_text', 'question_hint', 
                'explanation', 'user_explanation', 'type', 'published', 'create_date', 
                'not_influence_to_score', 'weight', 'answers_weight', 'options'
            ),
            'required' => array('question'),
            'defaults' => array(
                'author_id' => 1,
                'category_id' => 1,
                'type' => 'radio',
                'published' => 1,
                'create_date' => 'NOW()',
                'not_influence_to_score' => 'off',
                'weight' => 1,
                'options' => '{"bg_image":"","use_html":"off","enable_question_text_max_length":"off","question_text_max_length":"","question_limit_text_type":"characters","question_enable_text_message":"off","enable_question_number_max_length":"off","question_number_max_length":"","question_limit_number_type":"characters","quiz_question_number_message":"off","enable_max_selection_number":"off","max_selection_number":"","quiz_max_selection_number_message":"","question_image_sizing":"cover","question_image_position":"center center","wrong_answer_text":"","right_answer_text":"","question_hint_value":"","enable_question_html":"off","question_not_influence":"off","question_weight":"1","answer_sort_type":"default","question_box_shadow":"off","generate_random_answers":"off"}'
            )
        ),
        'question_categories' => array(
            'table' => 'wp_aysquiz_categories',
            'fields' => array(
                'id', 'author_id', 'title', 'description', 'published'
            ),
            'required' => array('title'),
            'defaults' => array(
                'author_id' => 1,
                'published' => 1
            )
        ),
        'question_tags' => array(
            'table' => 'wp_aysquiz_question_tags',
            'fields' => array(
                'id', 'author_id', 'title', 'description', 'status', 'options'
            ),
            'required' => array('title'),
            'defaults' => array(
                'author_id' => 1,
                'status' => 'published'
            )
        ),
        'answers' => array(
            'table' => 'wp_aysquiz_answers',
            'fields' => array(
                'id', 'question_id', 'answer', 'image', 'correct', 'ordering',
                'weight', 'keyword', 'placeholder', 'slug', 'options'
            ),
            'required' => array('answer', 'question_id'),
            'defaults' => array(
                'weight' => 0,
                'ordering' => 1,
                'options' => '{"use_html":"off"}'
            )
        )
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
        
        // Check if this is a combined format CSV (with record_type column)
        $combined_format = false;
        $first_row = reset($csv_data);
        
        if (isset($first_row['record_type']) || (isset($mapping['record_type']) && !empty($mapping['record_type']))) {
            $combined_format = true;
            
            // Process as combined format
            foreach ($csv_data as $row) {
                $record_type = isset($row['record_type']) ? strtolower($row['record_type']) : '';
                
                // Map record type if needed
                if (empty($record_type) && isset($mapping['record_type'])) {
                    $record_type_field = $mapping['record_type'];
                    $record_type = isset($row[$record_type_field]) ? strtolower($row[$record_type_field]) : '';
                }
                
                // Skip if no valid record type
                if (empty($record_type) || !in_array($record_type, array_keys($this->sheet_types))) {
                    continue;
                }
                
                // Process the row for this record type
                $processed_row = array();
                
                foreach ($row as $field => $value) {
                    // Skip record_type field
                    if ($field === 'record_type') {
                        continue;
                    }
                    
                    // Check if field is mapped
                    $mapped_field = isset($mapping[$field]) ? $mapping[$field] : '';
                    if (empty($mapped_field)) {
                        continue;
                    }
                    
                    // Check if mapping has record_type prefix
                    if (strpos($mapped_field, $record_type . '|') === 0) {
                        $db_field = substr($mapped_field, strlen($record_type) + 1);
                        $processed_row[$db_field] = $value;
                    } elseif (in_array($mapped_field, $this->table_structure[$record_type]['fields'])) {
                        // Direct field mapping
                        $processed_row[$mapped_field] = $value;
                    }
                }
                
                // Add to appropriate data array if not empty
                if (!empty($processed_row)) {
                    $data[$record_type][] = $processed_row;
                }
            }
        } else {
            // Process as separate entity format
            foreach ($mapping as $csv_field => $db_field) {
                if (empty($db_field) || strpos($db_field, '|') === false) {
                    continue;
                }
                
                list($entity, $field) = explode('|', $db_field, 2);
                
                if (!isset($data[$entity])) {
                    continue;
                }
                
                // For each row in the CSV
                foreach ($csv_data as $index => $row) {
                    if (!isset($row[$csv_field])) {
                        continue;
                    }
                    
                    // Create row if it doesn't exist
                    if (!isset($data[$entity][$index])) {
                        $data[$entity][$index] = array();
                    }
                    
                    // Add field value
                    $data[$entity][$index][$field] = $row[$csv_field];
                }
            }
            
            // Filter out empty rows
            foreach ($data as $entity => $rows) {
                $data[$entity] = array_filter($rows, function($row) {
                    return !empty($row);
                });
                
                // Re-index array
                $data[$entity] = array_values($data[$entity]);
            }
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
        
        // Check if file exists and is readable
        if (!file_exists($file_path) || !is_readable($file_path)) {
            return new WP_Error('file_read_error', __('Unable to read the CSV file.', 'quizcourse-importer'));
        }
        
        // Detect delimiter
        $delimiter = $this->detect_csv_delimiter($file_path);
        
        // Open file
        $handle = fopen($file_path, 'r');
        if ($handle === false) {
            return new WP_Error('file_open_error', __('Failed to open the CSV file.', 'quizcourse-importer'));
        }
        
        // Get headers (first row)
        $headers = fgetcsv($handle, 0, $delimiter);
        if ($headers === false) {
            fclose($handle);
            return new WP_Error('csv_error', __('Invalid CSV format or empty file.', 'quizcourse-importer'));
        }
        
        // Trim headers and ensure they're unique
        $headers = array_map('trim', $headers);
        
        // Check for duplicate headers
        $unique_headers = array_unique($headers);
        if (count($unique_headers) < count($headers)) {
            // Add suffixes to make duplicate headers unique
            $header_counts = array_count_values($headers);
            $header_suffixes = array_fill_keys($headers, 0);
            
            foreach ($headers as $index => $header) {
                if ($header_counts[$header] > 1) {
                    $header_suffixes[$header]++;
                    if ($header_suffixes[$header] > 1) {
                        $headers[$index] .= '_' . $header_suffixes[$header];
                    }
                }
            }
        }
        
        // Read data rows
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }
            
            // Make sure row has same number of columns as headers
            if (count($row) < count($headers)) {
                $row = array_pad($row, count($headers), '');
            } elseif (count($row) > count($headers)) {
                $row = array_slice($row, 0, count($headers));
            }
            
            // Create associative array
            $data_row = array_combine($headers, $row);
            $csv_data[] = $data_row;
        }
        
        fclose($handle);
        return $csv_data;
    }

    /**
     * Detect CSV delimiter.
     * 
     * @param string $file_path Path to the CSV file.
     * @return string Detected delimiter.
     */
    private function detect_csv_delimiter($file_path) {
        $delimiters = array(',', ';', "\t", '|');
        $counts = array_fill_keys($delimiters, 0);
        $first_line = '';
        
        // Read first line
        if (($handle = fopen($file_path, 'r')) !== false) {
            $first_line = fgets($handle);
            fclose($handle);
        }
        
        // Count occurrences of each delimiter
        foreach ($delimiters as $delimiter) {
            $counts[$delimiter] = substr_count($first_line, $delimiter);
        }
        
        // Get delimiter with highest count
        arsort($counts);
        $delimiter = key($counts);
        
        // Default to comma if no delimiter found
        if ($counts[$delimiter] === 0) {
            $delimiter = ',';
        }
        
        return $delimiter;
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
     * @return array|WP_Error Validated and cleaned data or error.
     */
    private function validate_and_clean_data($data) {
        // Add temporary IDs and enriched data for items to enable relationships
        $data = $this->enrich_data($data);
        
        // Validate required data
        if (empty($data['courses']) && empty($data['quizzes'])) {
            // We need at least courses or quizzes
            return new WP_Error(
                'missing_data', 
                __('The uploaded file must contain at least Courses or Quizzes data.', 'quizcourse-importer')
            );
        }
        
        // If we have quizzes, we should also have questions
        if (!empty($data['quizzes']) && empty($data['questions'])) {
            return new WP_Error(
                'missing_questions',
                __('Quizzes were found but no questions data was provided. Each quiz must have questions.', 'quizcourse-importer')
            );
        }
        
        // If we have questions, we should also have answers
        if (!empty($data['questions']) && empty($data['answers'])) {
            return new WP_Error(
                'missing_answers',
                __('Questions were found but no answers data was provided. Each question must have answers.', 'quizcourse-importer')
            );
        }
        
        // Additional validations based on table structure
        $errors = $this->validate_required_fields($data);
        if (!empty($errors)) {
            return new WP_Error('validation_error', implode(' ', $errors));
        }
        
        return $data;
    }

    /**
     * Enrich data with defaults and calculated values.
     * 
     * @param array $data Processed data.
     * @return array Enriched data.
     */
    private function enrich_data($data) {
        // Process courses
        if (!empty($data['courses'])) {
            foreach ($data['courses'] as $index => $course) {
                // Add temp ID
                $data['courses'][$index]['temp_id'] = 'course_' . ($index + 1);
                
                // Add default values
                foreach ($this->table_structure['courses']['defaults'] as $field => $default) {
                    if (!isset($course[$field])) {
                        $data['courses'][$index][$field] = $default;
                    }
                }
                
                // Set creation date
                if (!isset($course['date_created'])) {
                    $data['courses'][$index]['date_created'] = current_time('mysql');
                }
                if (!isset($course['date_modified'])) {
                    $data['courses'][$index]['date_modified'] = current_time('mysql');
                }
                
                // Convert timestamp if needed
                if (isset($course['date_created']) && is_numeric($course['date_created'])) {
                    $data['courses'][$index]['date_created'] = date('Y-m-d H:i:s', $course['date_created']);
                }
                if (isset($course['date_modified']) && is_numeric($course['date_modified'])) {
                    $data['courses'][$index]['date_modified'] = date('Y-m-d H:i:s', $course['date_modified']);
                }
                
                // Prepare options field if needed
               if (!isset($course['options']) && !empty($course)) {
                   $data['courses'][$index]['options'] = json_encode(array(
                       'show_progress' => true,
                       'enable_review' => true
                   ));
               } elseif (isset($course['options']) && is_array($course['options'])) {
                   $data['courses'][$index]['options'] = json_encode($course['options']);
               }
           }
       }
       
       // Process quizzes
       if (!empty($data['quizzes'])) {
           foreach ($data['quizzes'] as $index => $quiz) {
               // Add temp ID
               $data['quizzes'][$index]['temp_id'] = 'quiz_' . ($index + 1);
               
               // Add default values
               foreach ($this->table_structure['quizzes']['defaults'] as $field => $default) {
                   if (!isset($quiz[$field])) {
                       $data['quizzes'][$index][$field] = $default;
                   }
               }
               
               // Set creation date
               if (!isset($quiz['create_date'])) {
                   $data['quizzes'][$index]['create_date'] = current_time('mysql');
               }
               
               // Convert timestamp if needed
               if (isset($quiz['create_date']) && is_numeric($quiz['create_date'])) {
                   $data['quizzes'][$index]['create_date'] = date('Y-m-d H:i:s', $quiz['create_date']);
               }
               
               // Prepare options field
               if (!isset($quiz['options']) && !empty($quiz)) {
                   $data['quizzes'][$index]['options'] = $this->table_structure['quizzes']['defaults']['options'];
               } elseif (isset($quiz['options']) && is_array($quiz['options'])) {
                   $data['quizzes'][$index]['options'] = json_encode($quiz['options']);
               }
               
               // Link quiz to course if course_reference exists
               if (isset($quiz['course_reference']) && !empty($data['courses'])) {
                   foreach ($data['courses'] as $course_index => $course) {
                       if (isset($course['id']) && $course['id'] == $quiz['course_reference']) {
                           // Add course_temp_id to quiz
                           $data['quizzes'][$index]['course_temp_id'] = $data['courses'][$course_index]['temp_id'];
                           
                           // Add quiz_id to course's question_ids
                           if (!isset($data['courses'][$course_index]['question_ids'])) {
                               $data['courses'][$course_index]['question_ids'] = $index + 1;
                           } else {
                               $data['courses'][$course_index]['question_ids'] .= ',' . ($index + 1);
                           }
                           
                           break;
                       }
                   }
               }
           }
       }
       
       // Process questions
       if (!empty($data['questions'])) {
           foreach ($data['questions'] as $index => $question) {
               // Add temp ID
               $data['questions'][$index]['temp_id'] = 'question_' . ($index + 1);
               
               // Add default values
               foreach ($this->table_structure['questions']['defaults'] as $field => $default) {
                   if (!isset($question[$field])) {
                       $data['questions'][$index][$field] = $default;
                   }
               }
               
               // Set creation date
               if (!isset($question['create_date'])) {
                   $data['questions'][$index]['create_date'] = current_time('mysql');
               }
               
               // Convert timestamp if needed
               if (isset($question['create_date']) && is_numeric($question['create_date'])) {
                   $data['questions'][$index]['create_date'] = date('Y-m-d H:i:s', $question['create_date']);
               }
               
               // Prepare options field
               if (!isset($question['options']) && !empty($question)) {
                   $data['questions'][$index]['options'] = $this->table_structure['questions']['defaults']['options'];
               } elseif (isset($question['options']) && is_array($question['options'])) {
                   $data['questions'][$index]['options'] = json_encode($question['options']);
               }
               
               // Link question to quiz if quiz_reference exists
               if (isset($question['quiz_reference']) && !empty($data['quizzes'])) {
                   foreach ($data['quizzes'] as $quiz_index => $quiz) {
                       if (isset($quiz['id']) && $quiz['id'] == $question['quiz_reference']) {
                           // Add quiz_temp_id to question
                           $data['questions'][$index]['quiz_temp_id'] = $data['quizzes'][$quiz_index]['temp_id'];
                           
                           // Add question_id to quiz's question_ids
                           if (!isset($data['quizzes'][$quiz_index]['question_ids'])) {
                               $data['quizzes'][$quiz_index]['question_ids'] = $index + 1;
                           } else {
                               $data['quizzes'][$quiz_index]['question_ids'] .= ',' . ($index + 1);
                           }
                           
                           break;
                       }
                   }
               }
           }
       }
       
       // Process answers
       if (!empty($data['answers'])) {
           foreach ($data['answers'] as $index => $answer) {
               // Add temp ID
               $data['answers'][$index]['temp_id'] = 'answer_' . ($index + 1);
               
               // Add default values
               foreach ($this->table_structure['answers']['defaults'] as $field => $default) {
                   if (!isset($answer[$field])) {
                       $data['answers'][$index][$field] = $default;
                   }
               }
               
               // Ensure correct is 0 or 1
               if (isset($answer['correct'])) {
                   // Convert various formats to 0/1
                   if (is_string($answer['correct'])) {
                       $correct_value = strtolower(trim($answer['correct']));
                       if (in_array($correct_value, array('yes', 'true', '1'))) {
                           $data['answers'][$index]['correct'] = 1;
                       } else {
                           $data['answers'][$index]['correct'] = 0;
                       }
                   } else {
                       $data['answers'][$index]['correct'] = $answer['correct'] ? 1 : 0;
                   }
               } else {
                   $data['answers'][$index]['correct'] = 0;
               }
               
               // Prepare options field
               if (!isset($answer['options']) && !empty($answer)) {
                   $data['answers'][$index]['options'] = $this->table_structure['answers']['defaults']['options'];
               } elseif (isset($answer['options']) && is_array($answer['options'])) {
                   $data['answers'][$index]['options'] = json_encode($answer['options']);
               }
               
               // Link answer to question if question_reference exists
               if (isset($answer['question_reference']) && !empty($data['questions'])) {
                   foreach ($data['questions'] as $question_index => $question) {
                       if (isset($question['id']) && $question['id'] == $answer['question_reference']) {
                           // Add question_id to answer
                           $data['answers'][$index]['question_id'] = $question_index + 1;
                           $data['answers'][$index]['question_temp_id'] = $data['questions'][$question_index]['temp_id'];
                           break;
                       }
                   }
               }
           }
       }
       
       return $data;
   }

   /**
    * Validate required fields for each entity.
    * 
    * @param array $data The data to validate
    * @return array Array of error messages
    */
   private function validate_required_fields($data) {
       $errors = array();
       
       // Validate courses
       if (!empty($data['courses'])) {
           foreach ($data['courses'] as $index => $course) {
               foreach ($this->table_structure['courses']['required'] as $required_field) {
                   if (empty($course[$required_field])) {
                       $errors[] = sprintf(
                           __('Course at row %d is missing required field: %s', 'quizcourse-importer'),
                           $index + 1,
                           $required_field
                       );
                   }
               }
           }
       }
       
       // Validate quizzes
       if (!empty($data['quizzes'])) {
           foreach ($data['quizzes'] as $index => $quiz) {
               foreach ($this->table_structure['quizzes']['required'] as $required_field) {
                   if (empty($quiz[$required_field])) {
                       $errors[] = sprintf(
                           __('Quiz at row %d is missing required field: %s', 'quizcourse-importer'),
                           $index + 1,
                           $required_field
                       );
                   }
               }
           }
       }
       
       // Validate questions
       if (!empty($data['questions'])) {
           foreach ($data['questions'] as $index => $question) {
               foreach ($this->table_structure['questions']['required'] as $required_field) {
                   if (empty($question[$required_field])) {
                       $errors[] = sprintf(
                           __('Question at row %d is missing required field: %s', 'quizcourse-importer'),
                           $index + 1,
                           $required_field
                       );
                   }
               }
               
               // Validate question type if present
               if (isset($question['type']) && !empty($question['type'])) {
                   $valid_types = array('radio', 'checkbox', 'select', 'text', 'short_text', 'number', 'date', 'true_or_false');
                   if (!in_array($question['type'], $valid_types)) {
                       $errors[] = sprintf(
                           __('Question at row %d has invalid type. Valid types are: %s', 'quizcourse-importer'),
                           $index + 1,
                           implode(', ', $valid_types)
                       );
                   }
               }
           }
       }
       
       // Validate answers
       if (!empty($data['answers'])) {
           foreach ($data['answers'] as $index => $answer) {
               foreach ($this->table_structure['answers']['required'] as $required_field) {
                   if (empty($answer[$required_field]) && $required_field != 'question_id') {
                       $errors[] = sprintf(
                           __('Answer at row %d is missing required field: %s', 'quizcourse-importer'),
                           $index + 1,
                           $required_field
                       );
                   }
               }
               
               // Check if question_id or question_reference is set
               if (empty($answer['question_id']) && empty($answer['question_reference'])) {
                   $errors[] = sprintf(
                       __('Answer at row %d is missing question ID or reference', 'quizcourse-importer'),
                       $index + 1
                   );
               }
           }
       }
       
       return $errors;
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
       
       // Check if empty
       if (empty($csv_data)) {
           return new WP_Error('empty_csv', __('The CSV file is empty.', 'quizcourse-importer'));
       }
       
       // Get headers from first row
       $headers = array_keys($csv_data[0]);
       
       // Check for record_type column (combined format)
       if (in_array('record_type', $headers)) {
           // This is a combined format CSV
           $sheet_types = array();
           
           // Group rows by record type
           foreach ($csv_data as $row) {
               $record_type = isset($row['record_type']) ? strtolower($row['record_type']) : '';
               if (empty($record_type)) {
                   continue;
               }
               
               if (!isset($sheet_types[$record_type])) {
                   $sheet_types[$record_type] = array(
                       'name' => ucfirst($record_type),
                       'fields' => array_keys($row),
                       'rows' => array()
                   );
               }
               
               $sheet_types[$record_type]['rows'][] = $row;
           }
           
           // Count rows for each type
           foreach ($sheet_types as $type => $data) {
               $sheet_types[$type]['row_count'] = count($data['rows']);
               
               // Limit to 3 sample rows
               $sheet_types[$type]['preview'] = array_slice($data['rows'], 0, 3);
               
               // Remove rows from structure data
               unset($sheet_types[$type]['rows']);
           }
           
           return array(
               'file_type' => 'csv',
               'format' => 'combined',
               'sheets' => $sheet_types,
               'total_rows' => count($csv_data)
           );
       } else {
           // This is a simple CSV - treat as a single sheet
           // Try to guess the entity type based on headers
           $entity_type = $this->guess_entity_type($headers);
           
           return array(
               'file_type' => 'csv',
               'format' => 'simple',
               'sheets' => array(
                   $entity_type => array(
                       'name' => ucfirst($entity_type),
                       'fields' => $headers,
                       'row_count' => count($csv_data),
                       'preview' => array_slice($csv_data, 0, 3),
                       'detected_type' => $entity_type
                   )
               ),
               'total_rows' => count($csv_data)
           );
       }
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
       $total_rows = 0;
       
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
           $total_rows += $row_count;
           
           // Get preview rows
           $preview_rows = array();
           if ($row_count > 0) {
               $preview_range = 'A2:' . $highest_column . min($highest_row, 4); // Get up to 3 rows
               $preview_data = $sheet->rangeToArray($preview_range, null, true, false);
               
               // Convert to associative array
               foreach ($preview_data as $row) {
                   $preview_row = array();
                   foreach ($headers as $index => $header) {
                       if (isset($row[$index])) {
                           $preview_row[$header] = $row[$index];
                       } else {
                           $preview_row[$header] = '';
                       }
                   }
                   $preview_rows[] = $preview_row;
               }
           }
           
           // Determine sheet type
           $sheet_key = $this->get_sheet_key($sheet_name);
           if (!$sheet_key) {
               // Try to guess the sheet type based on field names
               $sheet_key = $this->guess_entity_type($headers);
           }
           
           $structure[$sheet_key ?: $sheet_name] = array(
               'name' => $sheet_name,
               'fields' => $headers,
               'row_count' => $row_count,
               'preview' => $preview_rows,
               'detected_type' => $sheet_key ?: 'unknown'
           );
       }
       
       return array(
           'file_type' => 'excel',
           'sheets' => $structure,
           'total_rows' => $total_rows
       );
   }

   /**
    * Guess the entity type based on field names.
    * 
    * @param array $fields Available fields in the sheet.
    * @return string Guessed entity type.
    */
   private function guess_entity_type($fields) {
       // Convert fields to lowercase for comparison
       $lowercase_fields = array_map('strtolower', $fields);
       
       // Define field patterns for each entity type
       $patterns = array(
           'courses' => array('course', 'title', 'description'),
           'sections' => array('section', 'course_reference', 'course_id'),
           'quizzes' => array('quiz', 'category_id', 'title'),
           'questions' => array('question', 'type', 'quiz_id', 'quiz_reference'),
           'answers' => array('answer', 'correct', 'question_id', 'question_reference')
       );
       
       $scores = array();
       
       // Score each entity type based on field matches
       foreach ($patterns as $type => $keywords) {
           $scores[$type] = 0;
           
           foreach ($keywords as $keyword) {
               foreach ($lowercase_fields as $field) {
                   if (strpos($field, $keyword) !== false) {
                       $scores[$type]++;
                       break;  // Only count one match per keyword
                   }
               }
           }
       }
       
       // Get type with highest score
       arsort($scores);
       return key($scores);
   }

   /**
    * Get database field options for each sheet type.
    * Used for field mapping during import.
    * 
    * @return array Database field options.
    */
   public function get_db_field_options() {
       return array(
           'courses' => array(
               'title' => __('Title', 'quizcourse-importer'),
               'description' => __('Description', 'quizcourse-importer'),
               'category_ids' => __('Category IDs', 'quizcourse-importer'),
               'question_ids' => __('Question IDs', 'quizcourse-importer'),
               'lesson_ids' => __('Lesson IDs', 'quizcourse-importer'),
               'section_ids' => __('Section IDs', 'quizcourse-importer'),
               'image' => __('Image URL', 'quizcourse-importer'),
               'status' => __('Status', 'quizcourse-importer'),
               'ordering' => __('Ordering', 'quizcourse-importer'),
               'author_id' => __('Author ID', 'quizcourse-importer'),
               'options' => __('Options', 'quizcourse-importer')
           ),
           'quizzes' => array(
               'title' => __('Title', 'quizcourse-importer'),
               'description' => __('Description', 'quizcourse-importer'),
               'quiz_image' => __('Image URL', 'quizcourse-importer'),
               'quiz_category_id' => __('Category ID', 'quizcourse-importer'),
               'question_ids' => __('Question IDs', 'quizcourse-importer'),
               'ordering' => __('Ordering', 'quizcourse-importer'),
               'published' => __('Published', 'quizcourse-importer'),
               'course_reference' => __('Course Reference', 'quizcourse-importer'),
               'author_id' => __('Author ID', 'quizcourse-importer'),
               'options' => __('Options', 'quizcourse-importer')
           ),
           'questions' => array(
               'question' => __('Question Text', 'quizcourse-importer'),
               'question_title' => __('Question Title', 'quizcourse-importer'),
               'quiz_reference' => __('Quiz Reference', 'quizcourse-importer'),
               'category_id' => __('Category ID', 'quizcourse-importer'),
               'tag_id' => __('Tag ID', 'quizcourse-importer'),
               'type' => __('Question Type', 'quizcourse-importer'),
               'question_image' => __('Image URL', 'quizcourse-importer'),
               'wrong_answer_text' => __('Wrong Answer Text', 'quizcourse-importer'),
               'right_answer_text' => __('Right Answer Text', 'quizcourse-importer'),
               'question_hint' => __('Hint', 'quizcourse-importer'),
               'explanation' => __('Explanation', 'quizcourse-importer'),
               'weight' => __('Weight', 'quizcourse-importer'),
               'published' => __('Published', 'quizcourse-importer'),
               'author_id' => __('Author ID', 'quizcourse-importer'),
               'options' => __('Options', 'quizcourse-importer')
           ),
           'answers' => array(
               'answer' => __('Answer Text', 'quizcourse-importer'),
               'question_reference' => __('Question Reference', 'quizcourse-importer'),
               'correct' => __('Is Correct', 'quizcourse-importer'),
               'image' => __('Image URL', 'quizcourse-importer'),
               'ordering' => __('Ordering', 'quizcourse-importer'),
               'weight' => __('Weight', 'quizcourse-importer'),
               'options' => __('Options', 'quizcourse-importer')
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
                       $file_words = explode('_', str_replace(' ', '_', $file_field_lower));
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

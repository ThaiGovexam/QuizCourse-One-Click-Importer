<?php
/**
 * Data Validator class.
 * 
 * Handles validation of imported data files and their content.
 * ปรับปรุงให้รองรับการใช้งานกับ Sheet เดียว
 */
class QCI_Data_Validator {

    /**
     * Supported file extensions
     * 
     * @var array
     */
    private $supported_extensions = array('csv', 'xlsx', 'xls');

    /**
     * Required columns for single sheet format
     * ฟิลด์ที่จำเป็นสำหรับการใช้งานแบบ Sheet เดียว
     * 
     * @var array
     */
    private $required_columns = array(
        'record_type',   // ประเภทข้อมูล (course, quiz, question, answer)
        'record_id',     // ID สำหรับอ้างอิง
        'title',         // หัวข้อ/ชื่อ (สำหรับทุกประเภท)
        'parent_id'      // ID อ้างอิงถึงรายการแม่ (เช่น quiz อ้างถึง course)
    );

    /**
     * Valid record types
     * 
     * @var array
     */
    private $valid_record_types = array(
        'course', 'quiz', 'question', 'answer'
    );

    /**
     * Valid question types
     * 
     * @var array
     */
    private $valid_question_types = array(
        'multiple_choice',
        'true_false',
        'short_answer',
        'essay',
        'fill_in_blank',
        'matching'
    );

    /**
     * Database field mapping for each record type
     * การจับคู่ระหว่างฟิลด์ในไฟล์กับฟิลด์ในฐานข้อมูล
     * 
     * @var array
     */
    private $db_field_mapping = array(
        'course' => array(
            'id' => 'id',
            'title' => 'title',
            'description' => 'description',
            'image' => 'image',
            'category_ids' => 'category_ids',
            'status' => 'status',
            'ordering' => 'ordering',
            'options' => 'options',
        ),
        'quiz' => array(
            'id' => 'id',
            'title' => 'title',
            'description' => 'description',
            'quiz_image' => 'quiz_image',
            'quiz_category_id' => 'quiz_category_id',
            'question_ids' => 'question_ids',
            'published' => 'published',
            'ordering' => 'ordering',
            'options' => 'options',
            'parent_id' => 'course_id',  // course ที่ quiz นี้สังกัดอยู่
        ),
        'question' => array(
            'id' => 'id',
            'question' => 'question',
            'question_title' => 'question_title',
            'question_image' => 'question_image',
            'type' => 'type',
            'category_id' => 'category_id',
            'tag_id' => 'tag_id',
            'explanation' => 'explanation',
            'hint' => 'question_hint',
            'weight' => 'weight',
            'ordering' => 'ordering',
            'published' => 'published',
            'parent_id' => 'quiz_id',  // quiz ที่ question นี้สังกัดอยู่
        ),
        'answer' => array(
            'id' => 'id',
            'answer' => 'answer',
            'image' => 'image',
            'correct' => 'correct',
            'weight' => 'weight',
            'ordering' => 'ordering',
            'parent_id' => 'question_id',  // question ที่ answer นี้สังกัดอยู่
        )
    );

    /**
     * Validate uploaded file
     * 
     * @return array|WP_Error Validation results or error
     */
    public function validate_uploaded_file() {
        // Check if file is uploaded
        if (empty($_FILES['qci_import_file'])) {
            return new WP_Error('no_file', __('No file was uploaded.', 'quizcourse-importer'));
        }

        $file = $_FILES['qci_import_file'];

        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return new WP_Error('upload_error', $this->get_upload_error_message($file['error']));
        }

        // Validate file size
        if ($file['size'] > wp_max_upload_size()) {
            return new WP_Error('file_too_large', __('The file is too large. Maximum allowed size is ' . size_format(wp_max_upload_size()), 'quizcourse-importer'));
        }

        // Validate file extension
        $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        if (!in_array(strtolower($file_ext), $this->supported_extensions)) {
            return new WP_Error('invalid_extension', __('Invalid file format. Please upload CSV or Excel file (xlsx, xls).', 'quizcourse-importer'));
        }

        // Create temp directory if it doesn't exist
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/qci-temp';
        if (!file_exists($temp_dir)) {
            wp_mkdir_p($temp_dir);
            
            // Create an index.php file to prevent directory listing
            file_put_contents($temp_dir . '/index.php', '<?php // Silence is golden');
            
            // Create .htaccess to restrict direct access
            file_put_contents($temp_dir . '/.htaccess', 'deny from all');
        }

        // Generate unique file name
        $file_id = uniqid();
        $temp_file = $temp_dir . '/' . $file_id . '.' . $file_ext;

        // Move uploaded file to temp directory
        if (!move_uploaded_file($file['tmp_name'], $temp_file)) {
            return new WP_Error('move_error', __('Failed to save the uploaded file.', 'quizcourse-importer'));
        }

        // Store the file path in a transient for later use
        set_transient('qci_temp_file_' . $file_id, $temp_file, HOUR_IN_SECONDS);

        // Validate file content based on type
        if ($file_ext === 'csv') {
            $validation_result = $this->validate_csv_file($temp_file);
        } else {
            $validation_result = $this->validate_excel_file($temp_file);
        }

        if (is_wp_error($validation_result)) {
            // Delete the temporary file if validation fails
            unlink($temp_file);
            delete_transient('qci_temp_file_' . $file_id);
            return $validation_result;
        }

        // Return validation results
        return array(
            'file_id' => $file_id,
            'file_name' => $file['name'],
            'file_type' => $file_ext,
            'file_size' => size_format($file['size']),
            'mapping_html' => $this->generate_mapping_html($validation_result, $file_id),
            'data_preview' => $validation_result
        );
    }

    /**
     * Validate CSV file content - สำหรับการใช้งานกับ Sheet เดียว
     * 
     * @param string $file_path Path to the CSV file
     * @return array|WP_Error Validation results or error
     */
    private function validate_csv_file($file_path) {
        // Check if file exists and is readable
        if (!file_exists($file_path) || !is_readable($file_path)) {
            return new WP_Error('file_error', __('Cannot read the CSV file.', 'quizcourse-importer'));
        }

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

        // Check for required columns
        $missing_columns = $this->check_required_columns($headers);
        if (!empty($missing_columns)) {
            fclose($handle);
            return new WP_Error(
                'missing_columns', 
                __('The CSV file is missing required columns: ', 'quizcourse-importer') . implode(', ', $missing_columns)
            );
        }

        // Preview data (up to 5 rows)
        $preview_data = array();
        $row_count = 0;
        $max_preview_rows = 5;

        // Get index of record_type column
        $type_index = array_search('record_type', array_map('strtolower', $headers));
        
        while (($row = fgetcsv($handle)) !== false && $row_count < $max_preview_rows) {
            // Skip empty rows
            if (count(array_filter($row)) === 0) {
                continue;
            }

            $row_data = array();
            foreach ($headers as $index => $header) {
                if (isset($row[$index])) {
                    $row_data[$header] = $row[$index];
                } else {
                    $row_data[$header] = '';
                }
            }

            $preview_data[] = $row_data;
            $row_count++;
            
            // Validate record_type if available
            if ($type_index !== false && isset($row[$type_index])) {
                $record_type = strtolower(trim($row[$type_index]));
                if (!in_array($record_type, $this->valid_record_types)) {
                    fclose($handle);
                    return new WP_Error(
                        'invalid_record_type', 
                        sprintf(__('Invalid record_type "%s" at row %d. Valid types are: %s', 'quizcourse-importer'),
                            $record_type, $row_count + 1, implode(', ', $this->valid_record_types))
                    );
                }
            }
        }

        // Count remaining rows
        $total_rows = $row_count;
        while (fgetcsv($handle) !== false) {
            $total_rows++;
        }

        fclose($handle);

        return array(
            'type' => 'csv',
            'headers' => $headers,
            'preview' => $preview_data,
            'total_rows' => $total_rows
        );
    }

    /**
     * Validate Excel file content - สำหรับการใช้งานกับ Sheet เดียว
     * 
     * @param string $file_path Path to the Excel file
     * @return array|WP_Error Validation results or error
     */
    private function validate_excel_file($file_path) {
        // Check if PhpSpreadsheet is available
        if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            // Try to load from composer autoload if available
            $autoload_file = QCI_PLUGIN_DIR . 'vendor/autoload.php';
            if (file_exists($autoload_file)) {
                require_once $autoload_file;
            }
            
            // If still not available, try to load directly
            if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
                require_once QCI_PLUGIN_DIR . 'libraries/PhpSpreadsheet/vendor/autoload.php';
            }
            
            // Final check if PhpSpreadsheet is available
            if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
                return new WP_Error('missing_library', __('Required PhpSpreadsheet library is missing.', 'quizcourse-importer'));
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
            $spreadsheet = $reader->load($file_path);
            
            // Get the active sheet (assume it's the first sheet for single sheet import)
            $sheet = $spreadsheet->getActiveSheet();
            
            // Get data from the sheet
            $sheet_data = $sheet->toArray();
            
            // Check if sheet has data
            if (empty($sheet_data)) {
                return new WP_Error(
                    'empty_sheet',
                    __('The sheet is empty.', 'quizcourse-importer')
                );
            }
            
            // Get headers from first row
            $headers = $sheet_data[0];
            
            // Check for required columns
            $missing_columns = $this->check_required_columns($headers);
            if (!empty($missing_columns)) {
                return new WP_Error(
                    'missing_columns',
                    __('The sheet is missing required columns: ', 'quizcourse-importer') . implode(', ', $missing_columns)
                );
            }
            
            // Preview data (up to 5 rows)
            $preview_data = array();
            $row_count = min(count($sheet_data) - 1, 5);
            
            // Get index of record_type column
            $type_index = array_search('record_type', array_map('strtolower', $headers));
            
            for ($i = 1; $i <= $row_count; $i++) {
                $row_data = array();
                foreach ($headers as $index => $header) {
                    if (isset($sheet_data[$i][$index])) {
                        $row_data[$header] = $sheet_data[$i][$index];
                    } else {
                        $row_data[$header] = '';
                    }
                }
                $preview_data[] = $row_data;
                
                // Validate record_type if available
                if ($type_index !== false && isset($sheet_data[$i][$type_index])) {
                    $record_type = strtolower(trim($sheet_data[$i][$type_index]));
                    if (!in_array($record_type, $this->valid_record_types)) {
                        return new WP_Error(
                            'invalid_record_type', 
                            sprintf(__('Invalid record_type "%s" at row %d. Valid types are: %s', 'quizcourse-importer'),
                                $record_type, $i + 1, implode(', ', $this->valid_record_types))
                        );
                    }
                }
            }
            
            return array(
                'type' => 'excel',
                'headers' => $headers,
                'preview' => $preview_data,
                'total_rows' => count($sheet_data) - 1  // ลบแถวหัวข้อออก
            );
            
        } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
            return new WP_Error('excel_error', __('Error processing Excel file: ', 'quizcourse-importer') . $e->getMessage());
        } catch (Exception $e) {
            return new WP_Error('file_error', __('Error processing file: ', 'quizcourse-importer') . $e->getMessage());
        }
    }

    /**
     * Check for required columns in the file
     * 
     * @param array $headers File headers
     * @return array Missing required columns
     */
    private function check_required_columns($headers) {
        // Convert headers to lowercase for case-insensitive comparison
        $headers_lower = array_map('strtolower', $headers);
        
        // Check which required columns are missing
        $missing = array();
        foreach ($this->required_columns as $required_column) {
            if (!in_array(strtolower($required_column), $headers_lower)) {
                $missing[] = $required_column;
            }
        }
        
        return $missing;
    }

    /**
     * Validate data relationships in the file
     * 
     * @param array $data Data from the file
     * @return array|bool Relationship errors or true if valid
     */
    private function validate_relationships($data) {
        $errors = array();
        $record_types = array('course', 'quiz', 'question', 'answer');
        
        // Create lookup arrays for each record type
        $records_by_type = array();
        foreach ($record_types as $type) {
            $records_by_type[$type] = array();
        }
        
        // First pass: collect all record IDs by type
        foreach ($data as $row_index => $row) {
            $record_type = strtolower(trim($row['record_type']));
            $record_id = trim($row['record_id']);
            
            if (in_array($record_type, $record_types) && $record_id) {
                $records_by_type[$record_type][$record_id] = $row_index + 2;  // +2 for header row and 1-based index
            }
        }
        
        // Second pass: validate parent-child relationships
        foreach ($data as $row_index => $row) {
            $record_type = strtolower(trim($row['record_type']));
            $parent_id = isset($row['parent_id']) ? trim($row['parent_id']) : '';
            
            // Skip courses (top level) or rows without parent_id
            if ($record_type === 'course' || empty($parent_id)) {
                continue;
            }
            
            // Determine parent type based on child type
            $parent_type = '';
            switch ($record_type) {
                case 'quiz':
                    $parent_type = 'course';
                    break;
                case 'question':
                    $parent_type = 'quiz';
                    break;
                case 'answer':
                    $parent_type = 'question';
                    break;
            }
            
            // Validate that parent exists
            if ($parent_type && !isset($records_by_type[$parent_type][$parent_id])) {
                $errors[] = sprintf(
                    __('%s at row %d references %s with ID "%s" which was not found in the import data.', 'quizcourse-importer'),
                    ucfirst($record_type),
                    $row_index + 2,
                    $parent_type,
                    $parent_id
                );
            }
        }
        
        return empty($errors) ? true : $errors;
    }

    /**
     * Get upload error message
     * 
     * @param int $error_code PHP upload error code
     * @return string Error message
     */
    private function get_upload_error_message($error_code) {
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
                return __('The uploaded file exceeds the upload_max_filesize directive in php.ini.', 'quizcourse-importer');
            case UPLOAD_ERR_FORM_SIZE:
                return __('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.', 'quizcourse-importer');
            case UPLOAD_ERR_PARTIAL:
                return __('The uploaded file was only partially uploaded.', 'quizcourse-importer');
            case UPLOAD_ERR_NO_FILE:
                return __('No file was uploaded.', 'quizcourse-importer');
            case UPLOAD_ERR_NO_TMP_DIR:
                return __('Missing a temporary folder.', 'quizcourse-importer');
            case UPLOAD_ERR_CANT_WRITE:
                return __('Failed to write file to disk.', 'quizcourse-importer');
            case UPLOAD_ERR_EXTENSION:
                return __('A PHP extension stopped the file upload.', 'quizcourse-importer');
            default:
                return __('Unknown upload error.', 'quizcourse-importer');
        }
    }

    /**
     * Generate HTML for field mapping interface - ปรับปรุงสำหรับการใช้งานกับ Sheet เดียว
     * 
     * @param array $validation_result Validation result
     * @param string $file_id Temporary file ID
     * @return string HTML for field mapping
     */
    private function generate_mapping_html($validation_result, $file_id) {
        ob_start();
        ?>
        <h2><?php _e('Map Your File Fields', 'quizcourse-importer'); ?></h2>
        <p><?php _e('Match the fields in your file to the corresponding fields in the system.', 'quizcourse-importer'); ?></p>
        
        <div class="qci-mapping-container">
            <form id="qci-mapping-form">
                <input type="hidden" id="qci_file_id" name="qci_file_id" value="<?php echo esc_attr($file_id); ?>">
                <?php wp_nonce_field('qci-security', 'qci_security'); ?>
                
                <!-- Single Sheet Mapping Interface -->
                <div class="qci-single-sheet-info">
                    <p><strong><?php _e('File has a total of', 'quizcourse-importer'); ?> <?php echo $validation_result['total_rows']; ?> <?php _e('rows.', 'quizcourse-importer'); ?></strong></p>
                    <p><?php _e('Remember that your file should contain these record types:', 'quizcourse-importer'); ?></p>
                    <ul>
                        <li><strong>course</strong> - <?php _e('Course information', 'quizcourse-importer'); ?></li>
                        <li><strong>quiz</strong> - <?php _e('Quiz information (linked to courses)', 'quizcourse-importer'); ?></li>
                        <li><strong>question</strong> - <?php _e('Questions (linked to quizzes)', 'quizcourse-importer'); ?></li>
                        <li><strong>answer</strong> - <?php _e('Answers (linked to questions)', 'quizcourse-importer'); ?></li>
                    </ul>
                </div>
                
                <div class="qci-field-mapping-table">
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th><?php _e('File Column', 'quizcourse-importer'); ?></th>
                                <th><?php _e('System Field', 'quizcourse-importer'); ?></th>
                                <th><?php _e('Sample Data', 'quizcourse-importer'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($validation_result['headers'] as $header_index => $header): ?>
                                <tr>
                                    <td><?php echo esc_html($header); ?></td>
                                    <td>
                                        <select class="qci-field-mapping" name="mapping[<?php echo esc_attr($header); ?>]" data-sheet="single_sheet" data-sheet-field="<?php echo esc_attr($header); ?>">
                                            <option value=""><?php _e('-- Skip this field --', 'quizcourse-importer'); ?></option>
                                            
                                            <?php
                                            // For record_type field, we have a special mapping
                                            if (strtolower($header) === 'record_type'): ?>
                                                <option value="record_type" selected><?php _e('Record Type (required)', 'quizcourse-importer'); ?></option>
                                            <?php 
                                            // For all other fields, show appropriate mappings based on context
                                            else: 
                                                // Get all field groups
                                                echo $this->get_field_options_for_single_sheet($header);
                                            endif;
                                            ?>
                                        </select>
                                    </td>
                                    <td>
                                        <?php
                                        // Show sample data from the preview
                                        if (!empty($validation_result['preview'][0][$header])):
                                            echo esc_html($validation_result['preview'][0][$header]);
                                        else:
                                            echo '<em>' . __('No data', 'quizcourse-importer') . '</em>';
                                        endif;
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <h3><?php _e('Preview Data', 'quizcourse-importer'); ?></h3>
                <div class="qci-preview-table">
                    <table class="widefat">
                        <thead>
                            <tr>
                                <?php foreach ($validation_result['headers'] as $header): ?>
                                    <th><?php echo esc_html($header); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($validation_result['preview'] as $row): ?>
                                <tr>
                                    <?php foreach ($validation_result['headers'] as $header): ?>
                                        <td><?php echo esc_html(isset($row[$header]) ? $row[$header] : ''); ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="qci-mapping-help">
                    <h3><?php _e('How to Map Your Fields', 'quizcourse-importer'); ?></h3>
                    <p><?php _e('For each column in your file, select the corresponding field in our system:', 'quizcourse-importer'); ?></p>
                    <ul>
                        <li><?php _e('<strong>record_type</strong> must be mapped to "Record Type"', 'quizcourse-importer'); ?></li>
                        <li><?php _e('<strong>record_id</strong> should be mapped to the ID field for each record type', 'quizcourse-importer'); ?></li>
                        <li><?php _e('<strong>title</strong> should be mapped to the title/name field for each record type', 'quizcourse-importer'); ?></li>
                        <li><?php _e('<strong>parent_id</strong> should be mapped to the reference field connecting to parent records', 'quizcourse-importer'); ?></li>
                        <li><?php _e('For answer records, make sure to map the "correct" field to indicate correct answers', 'quizcourse-importer'); ?></li>
                    </ul>
                </div>
                
                <div class="qci-actions">
                    <button type="button" id="qci-auto-map" class="button button-secondary">
                        <span class="dashicons dashicons-admin-generic"></span>
                        <?php _e('Auto-Map Fields', 'quizcourse-importer'); ?>
                    </button>
                    <button type="button" class="button qci-back-button">
                        <span class="dashicons dashicons-arrow-left-alt"></span>
                        <?php _e('Back', 'quizcourse-importer'); ?>
                    </button>
                    <button type="button" id="qci-start-import" class="button button-primary">
                        <span class="dashicons dashicons-database-import"></span>
                        <?php _e('Start Import', 'quizcourse-importer'); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get field options for single sheet mapping interface
     * 
     * @param string $header The header from the file
     * @return string HTML options for select element
     */
    private function get_field_options_for_single_sheet($header) {
        $options = '';
        
        // Common fields that appear in most record types
        $common_fields = array(
            'record_id' => __('Record ID (required)', 'quizcourse-importer'),
            'title' => __('Title/Name (required)', 'quizcourse-importer'),
            'description' => __('Description', 'quizcourse-importer'),
            'parent_id' => __('Parent Reference ID (required for quiz/question/answer)', 'quizcourse-importer'),
            'status' => __('Status (publish, draft)', 'quizcourse-importer'),
            'ordering' => __('Display Order', 'quizcourse-importer'),
            'image_url' => __('Image URL', 'quizcourse-importer')
        );
        
        // Add common fields
        $options .= '<optgroup label="' . __('Common Fields', 'quizcourse-importer') . '">';
        foreach ($common_fields as $field_key => $field_label) {
            $selected = $this->is_field_match($header, $field_key) ? ' selected="selected"' : '';
            $options .= '<option value="' . esc_attr($field_key) . '"' . $selected . '>' . esc_html($field_label) . '</option>';
        }
        $options .= '</optgroup>';
        
        // Add course-specific fields
        $options .= '<optgroup label="' . __('Course Fields', 'quizcourse-importer') . '">';
        $course_fields = array(
            'course_category_ids' => __('Course Category IDs', 'quizcourse-importer'),
            'course_options' => __('Course Options (JSON)', 'quizcourse-importer')
        );
        foreach ($course_fields as $field_key => $field_label) {
            $selected = $this->is_field_match($header, $field_key) ? ' selected="selected"' : '';
            $options .= '<option value="' . esc_attr($field_key) . '"' . $selected . '>' . esc_html($field_label) . '</option>';
        }
        $options .= '</optgroup>';
        
        // Add quiz-specific fields
        $options .= '<optgroup label="' . __('Quiz Fields', 'quizcourse-importer') . '">';
        $quiz_fields = array(
            'quiz_category_id' => __('Quiz Category ID', 'quizcourse-importer'),
           'quiz_published' => __('Quiz Published (1/0)', 'quizcourse-importer'),
           'quiz_options' => __('Quiz Options (JSON)', 'quizcourse-importer'),
           'quiz_intervals' => __('Quiz Intervals', 'quizcourse-importer')
       );
       foreach ($quiz_fields as $field_key => $field_label) {
           $selected = $this->is_field_match($header, $field_key) ? ' selected="selected"' : '';
           $options .= '<option value="' . esc_attr($field_key) . '"' . $selected . '>' . esc_html($field_label) . '</option>';
       }
       $options .= '</optgroup>';
       
       // Add question-specific fields
       $options .= '<optgroup label="' . __('Question Fields', 'quizcourse-importer') . '">';
       $question_fields = array(
           'question_type' => __('Question Type (multiple_choice, true_false, etc.)', 'quizcourse-importer'),
           'question_hint' => __('Question Hint', 'quizcourse-importer'),
           'explanation' => __('Explanation (for correct answer)', 'quizcourse-importer'),
           'wrong_answer_text' => __('Wrong Answer Text', 'quizcourse-importer'),
           'right_answer_text' => __('Right Answer Text', 'quizcourse-importer'),
           'question_category_id' => __('Question Category ID', 'quizcourse-importer'),
           'question_tag_id' => __('Question Tag ID', 'quizcourse-importer'),
           'question_published' => __('Question Published (1/0)', 'quizcourse-importer'),
           'question_weight' => __('Question Weight/Points', 'quizcourse-importer'),
           'user_explanation' => __('User Explanation', 'quizcourse-importer'),
           'question_options' => __('Question Options (JSON)', 'quizcourse-importer')
       );
       foreach ($question_fields as $field_key => $field_label) {
           $selected = $this->is_field_match($header, $field_key) ? ' selected="selected"' : '';
           $options .= '<option value="' . esc_attr($field_key) . '"' . $selected . '>' . esc_html($field_label) . '</option>';
       }
       $options .= '</optgroup>';
       
       // Add answer-specific fields
       $options .= '<optgroup label="' . __('Answer Fields', 'quizcourse-importer') . '">';
       $answer_fields = array(
           'correct' => __('Is Correct Answer (1/0)', 'quizcourse-importer'),
           'answer_weight' => __('Answer Weight/Points', 'quizcourse-importer'),
           'keyword' => __('Answer Keyword', 'quizcourse-importer'),
           'placeholder' => __('Answer Placeholder', 'quizcourse-importer'),
           'slug' => __('Answer Slug', 'quizcourse-importer'),
           'answer_options' => __('Answer Options (JSON)', 'quizcourse-importer')
       );
       foreach ($answer_fields as $field_key => $field_label) {
           $selected = $this->is_field_match($header, $field_key) ? ' selected="selected"' : '';
           $options .= '<option value="' . esc_attr($field_key) . '"' . $selected . '>' . esc_html($field_label) . '</option>';
       }
       $options .= '</optgroup>';
       
       return $options;
   }

   /**
    * Check if a header matches a field key for auto-mapping
    * 
    * @param string $header The header from the file
    * @param string $field_key The system field key
    * @return bool Whether the header matches the field
    */
   private function is_field_match($header, $field_key) {
       // Normalize both strings for comparison
       $normalized_header = strtolower(str_replace(array(' ', '_', '-'), '', $header));
       $normalized_key = strtolower(str_replace(array(' ', '_', '-'), '', $field_key));
       
       // Direct match
       if ($normalized_header === $normalized_key) {
           return true;
       }
       
       // Check for partial matches or common variations
       $field_variations = array(
           'record_id' => array('id', 'recordid', 'identifier', 'key'),
           'title' => array('name', 'heading', 'subject'),
           'description' => array('desc', 'content', 'text', 'body'),
           'parent_id' => array('parentid', 'parent', 'parentrecord', 'reference', 'ref'),
           'status' => array('state', 'published', 'visibility'),
           'ordering' => array('order', 'sequence', 'position', 'sort'),
           'image_url' => array('image', 'img', 'photo', 'picture', 'thumbnail'),
           
           // Course variations
           'course_category_ids' => array('coursecategories', 'coursecat', 'coursecats'),
           'course_options' => array('courseoptions', 'coursesettings'),
           
           // Quiz variations
           'quiz_category_id' => array('quizcategory', 'quizcat'),
           'quiz_published' => array('quizpublished', 'quizstatus', 'quizvisible'),
           'quiz_options' => array('quizoptions', 'quizsettings'),
           
           // Question variations
           'question_type' => array('questiontype', 'qtype', 'type'),
           'question_hint' => array('hint', 'questionhint', 'clue'),
           'explanation' => array('answer_explanation', 'solution', 'explanation'),
           'question_category_id' => array('questioncat', 'qcat'),
           'question_tag_id' => array('questiontag', 'qtag'),
           'question_weight' => array('qweight', 'questionpoints', 'qpoints'),
           
           // Answer variations
           'correct' => array('iscorrect', 'rightanswer', 'correct_answer'),
           'answer_weight' => array('aweight', 'answerpoints', 'apoints')
       );
       
       if (isset($field_variations[$field_key])) {
           foreach ($field_variations[$field_key] as $variation) {
               if (strpos($normalized_header, $variation) !== false) {
                   return true;
               }
           }
       }
       
       return false;
   }

   /**
    * Validate single sheet data for importing
    * 
    * @param array $data Data from mapped file
    * @return true|WP_Error True if valid, error otherwise
    */
   public function validate_single_sheet_data($data) {
       $errors = array();
       
       // Group data by record type
       $grouped_data = array(
           'course' => array(),
           'quiz' => array(),
           'question' => array(),
           'answer' => array()
       );
       
       foreach ($data as $row_index => $row) {
           if (!isset($row['record_type'])) {
               $errors[] = sprintf(__('Row %d is missing record_type.', 'quizcourse-importer'), $row_index + 2);
               continue;
           }
           
           $record_type = strtolower(trim($row['record_type']));
           
           // Check if record type is valid
           if (!in_array($record_type, array_keys($grouped_data))) {
               $errors[] = sprintf(
                   __('Row %d has invalid record_type "%s". Valid types are: %s', 'quizcourse-importer'),
                   $row_index + 2,
                   $record_type,
                   implode(', ', array_keys($grouped_data))
               );
               continue;
           }
           
           // Add row to the appropriate group
           $grouped_data[$record_type][] = array(
               'row_index' => $row_index,
               'data' => $row
           );
       }
       
       // Check required fields for each record type
       foreach ($grouped_data as $record_type => $records) {
           foreach ($records as $record) {
               $row = $record['data'];
               $row_index = $record['row_index'];
               
               // Check for record_id
               if (empty($row['record_id'])) {
                   $errors[] = sprintf(
                       __('%s at row %d is missing record_id.', 'quizcourse-importer'),
                       ucfirst($record_type),
                       $row_index + 2
                   );
               }
               
               // Check for title/name
               if (empty($row['title'])) {
                   $errors[] = sprintf(
                       __('%s at row %d is missing title.', 'quizcourse-importer'),
                       ucfirst($record_type),
                       $row_index + 2
                   );
               }
               
               // Check for parent_id (except for courses)
               if ($record_type !== 'course' && empty($row['parent_id'])) {
                   $errors[] = sprintf(
                       __('%s at row %d is missing parent_id.', 'quizcourse-importer'),
                       ucfirst($record_type),
                       $row_index + 2
                   );
               }
               
               // Additional validations for specific record types
               switch ($record_type) {
                   case 'question':
                       // Check for question type
                       if (empty($row['question_type'])) {
                           $errors[] = sprintf(
                               __('Question at row %d is missing question_type.', 'quizcourse-importer'),
                               $row_index + 2
                           );
                       } elseif (!in_array($row['question_type'], $this->valid_question_types)) {
                           $errors[] = sprintf(
                               __('Question at row %d has invalid question_type "%s". Valid types are: %s', 'quizcourse-importer'),
                               $row_index + 2,
                               $row['question_type'],
                               implode(', ', $this->valid_question_types)
                           );
                       }
                       break;
                       
                   case 'answer':
                       // Check for 'correct' field
                       if (!isset($row['correct'])) {
                           $errors[] = sprintf(
                               __('Answer at row %d is missing correct field.', 'quizcourse-importer'),
                               $row_index + 2
                           );
                       } elseif (!in_array($row['correct'], array('0', '1', 0, 1))) {
                           $errors[] = sprintf(
                               __('Answer at row %d has invalid correct value "%s". Use 0 or 1.', 'quizcourse-importer'),
                               $row_index + 2,
                               $row['correct']
                           );
                       }
                       break;
               }
           }
       }
       
       // Validate relationships
       $relation_errors = $this->validate_data_relationships($grouped_data);
       $errors = array_merge($errors, $relation_errors);
       
       return empty($errors) ? true : new WP_Error('validation_error', implode('<br>', $errors));
   }

   /**
    * Validate relationships between parent and child records
    * 
    * @param array $grouped_data Data grouped by record type
    * @return array Validation errors
    */
   private function validate_data_relationships($grouped_data) {
       $errors = array();
       
       // Create lookup tables for each record type
       $record_ids = array();
       foreach ($grouped_data as $record_type => $records) {
           $record_ids[$record_type] = array();
           foreach ($records as $record) {
               if (!empty($record['data']['record_id'])) {
                   $record_ids[$record_type][$record['data']['record_id']] = $record['row_index'] + 2;
               }
           }
       }
       
       // Define parent-child relationships
       $relationships = array(
           'quiz' => 'course',
           'question' => 'quiz',
           'answer' => 'question'
       );
       
       // Check that each child has a valid parent
       foreach ($relationships as $child_type => $parent_type) {
           foreach ($grouped_data[$child_type] as $child) {
               if (empty($child['data']['parent_id'])) {
                   continue; // Already validated for required parent_id
               }
               
               $parent_id = $child['data']['parent_id'];
               
               // Check if parent exists
               if (!isset($record_ids[$parent_type][$parent_id])) {
                   $errors[] = sprintf(
                       __('%s at row %d references %s with ID "%s" which was not found in the import data.', 'quizcourse-importer'),
                       ucfirst($child_type),
                       $child['row_index'] + 2,
                       $parent_type,
                       $parent_id
                   );
               }
           }
       }
       
       // Ensure every question has at least one answer (if there are any questions)
       if (!empty($grouped_data['question'])) {
           $questions_with_answers = array();
           
           foreach ($grouped_data['answer'] as $answer) {
               if (!empty($answer['data']['parent_id'])) {
                   $questions_with_answers[$answer['data']['parent_id']] = true;
               }
           }
           
           foreach ($grouped_data['question'] as $question) {
               $question_id = $question['data']['record_id'];
               if (!isset($questions_with_answers[$question_id])) {
                   $errors[] = sprintf(
                       __('Question with ID "%s" at row %d has no answers in the import data.', 'quizcourse-importer'),
                       $question_id,
                       $question['row_index'] + 2
                   );
               }
           }
       }
       
       return $errors;
   }

   /**
    * Validate the mapping configuration
    * 
    * @param array $mapping The field mapping configuration
    * @return true|WP_Error True if valid, error otherwise
    */
   public function validate_mapping($mapping) {
       $errors = array();
       
       // Essential fields that must be mapped for a successful import
       $required_mappings = array(
           'record_type' => __('Record Type', 'quizcourse-importer'),
           'record_id' => __('Record ID', 'quizcourse-importer'),
           'title' => __('Title/Name', 'quizcourse-importer'),
           'parent_id' => __('Parent Reference ID', 'quizcourse-importer')
       );
       
       foreach ($required_mappings as $field => $label) {
           $field_mapped = false;
           
           foreach ($mapping as $file_field => $system_field) {
               if ($system_field === $field) {
                   $field_mapped = true;
                   break;
               }
           }
           
           if (!$field_mapped) {
               $errors[] = sprintf(
                   __('Required field "%s" is not mapped to any column.', 'quizcourse-importer'),
                   $label
               );
           }
       }
       
       // Question-specific required fields
       $question_type_mapped = false;
       foreach ($mapping as $file_field => $system_field) {
           if ($system_field === 'question_type') {
               $question_type_mapped = true;
               break;
           }
       }
       
       if (!$question_type_mapped) {
           $errors[] = __('Question Type field is not mapped. This is required for question records.', 'quizcourse-importer');
       }
       
       // Answer-specific required fields
       $answer_correct_mapped = false;
       foreach ($mapping as $file_field => $system_field) {
           if ($system_field === 'correct') {
               $answer_correct_mapped = true;
               break;
           }
       }
       
       if (!$answer_correct_mapped) {
           $errors[] = __('Correct field is not mapped. This is required for answer records.', 'quizcourse-importer');
       }
       
       return empty($errors) ? true : new WP_Error('mapping_error', implode('<br>', $errors));
   }

   /**
    * Prepare the data for importing
    * 
    * @param array $data Raw data from the file
    * @param array $mapping The field mapping configuration
    * @return array Prepared data ready for importing
    */
   public function prepare_import_data($data, $mapping) {
       $prepared_data = array(
           'courses' => array(),
           'quizzes' => array(),
           'questions' => array(),
           'answers' => array()
       );
       
       // First pass: Group data by record type and prepare basic data
       foreach ($data as $row) {
           $record_type = strtolower($row[$this->get_mapped_field($mapping, 'record_type')]);
           $record_id = $row[$this->get_mapped_field($mapping, 'record_id')];
           
           // Prepare record data with mapped fields
           $record_data = array(
               'original_id' => $record_id  // Save original ID for reference
           );
           
           // Map fields according to the mapping configuration
           foreach ($mapping as $file_field => $system_field) {
               if (empty($system_field) || !isset($row[$file_field])) continue;
               
               $record_data[$system_field] = $row[$file_field];
           }
           
           // Add to appropriate array based on record type
           switch ($record_type) {
               case 'course':
                   $prepared_data['courses'][$record_id] = $record_data;
                   break;
               case 'quiz':
                   $prepared_data['quizzes'][$record_id] = $record_data;
                   break;
               case 'question':
                   $prepared_data['questions'][$record_id] = $record_data;
                   break;
               case 'answer':
                   $prepared_data['answers'][$record_id] = $record_data;
                   break;
           }
       }
       
       // Second pass: Setup relationships between records
       $this->setup_import_relationships($prepared_data);
       
       return $prepared_data;
   }
   
   /**
    * Setup relationships between records for importing
    * 
    * @param array &$prepared_data Reference to prepared data
    */
   private function setup_import_relationships(&$prepared_data) {
       // Link quizzes to courses
       foreach ($prepared_data['quizzes'] as $quiz_id => &$quiz) {
           if (isset($quiz['parent_id']) && isset($prepared_data['courses'][$quiz['parent_id']])) {
               $quiz['course_id'] = $quiz['parent_id']; // Store course ID
               
               // Add quiz ID to course's quiz list
               $course_id = $quiz['parent_id'];
               if (!isset($prepared_data['courses'][$course_id]['quizzes'])) {
                   $prepared_data['courses'][$course_id]['quizzes'] = array();
               }
               $prepared_data['courses'][$course_id]['quizzes'][] = $quiz_id;
           }
       }
       
       // Link questions to quizzes
       foreach ($prepared_data['questions'] as $question_id => &$question) {
           if (isset($question['parent_id']) && isset($prepared_data['quizzes'][$question['parent_id']])) {
               $question['quiz_id'] = $question['parent_id']; // Store quiz ID
               
               // Add question ID to quiz's question list
               $quiz_id = $question['parent_id'];
               if (!isset($prepared_data['quizzes'][$quiz_id]['questions'])) {
                   $prepared_data['quizzes'][$quiz_id]['questions'] = array();
               }
               $prepared_data['quizzes'][$quiz_id]['questions'][] = $question_id;
           }
       }
       
       // Link answers to questions
       foreach ($prepared_data['answers'] as $answer_id => &$answer) {
           if (isset($answer['parent_id']) && isset($prepared_data['questions'][$answer['parent_id']])) {
               $answer['question_id'] = $answer['parent_id']; // Store question ID
               
               // Add answer ID to question's answer list
               $question_id = $answer['parent_id'];
               if (!isset($prepared_data['questions'][$question_id]['answers'])) {
                   $prepared_data['questions'][$question_id]['answers'] = array();
               }
               $prepared_data['questions'][$question_id]['answers'][] = $answer_id;
           }
       }
   }
   
   /**
    * Get the file field that is mapped to a specific system field
    * 
    * @param array $mapping The field mapping configuration
    * @param string $system_field The system field to look for
    * @return string|null The mapped file field or null if not found
    */
   private function get_mapped_field($mapping, $system_field) {
       foreach ($mapping as $file_field => $mapped_field) {
           if ($mapped_field === $system_field) {
               return $file_field;
           }
       }
       return null;
   }
}

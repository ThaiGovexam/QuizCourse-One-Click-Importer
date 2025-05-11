<?php
/**
 * Data Validator class.
 * 
 * Handles validation of imported data files and their content.
 */
class QCI_Data_Validator {

    /**
     * Supported file extensions
     * 
     * @var array
     */
    private $supported_extensions = array('csv', 'xlsx', 'xls');

    /**
     * Required sheets for Excel files
     * 
     * @var array
     */
    private $required_sheets = array('Courses', 'Sections', 'Quizzes', 'Questions', 'Answers');

    /**
     * Required columns for each sheet
     * 
     * @var array
     */
    private $required_columns = array(
        'Courses' => array(
            'course_title',
            'course_description'
        ),
        'Sections' => array(
            'section_title',
            'course_reference'
        ),
        'Quizzes' => array(
            'quiz_title',
            'section_reference'
        ),
        'Questions' => array(
            'question_text',
            'quiz_reference',
            'question_type'
        ),
        'Answers' => array(
            'answer_text',
            'question_reference',
            'is_correct'
        )
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
     * Validate CSV file content
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
        $missing_columns = $this->check_csv_required_columns($headers);
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
     * Validate Excel file content
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

            // Check if the required sheets exist
            $sheet_names = $spreadsheet->getSheetNames();
            $missing_sheets = array_diff($this->required_sheets, $sheet_names);
            
            if (!empty($missing_sheets)) {
                return new WP_Error(
                    'missing_sheets',
                    __('The Excel file is missing required sheets: ', 'quizcourse-importer') . implode(', ', $missing_sheets)
                );
            }

            // Validate each sheet
            $validation_results = array();
            
            foreach ($this->required_sheets as $sheet_name) {
                $sheet = $spreadsheet->getSheetByName($sheet_name);
                $sheet_data = $sheet->toArray();
                
                // Check if sheet has data
                if (empty($sheet_data)) {
                    return new WP_Error(
                        'empty_sheet',
                        sprintf(__('The %s sheet is empty.', 'quizcourse-importer'), $sheet_name)
                    );
                }
                
                // Get headers from first row
                $headers = $sheet_data[0];
                
                // Check for required columns
                $missing_columns = $this->check_excel_required_columns($sheet_name, $headers);
                if (!empty($missing_columns)) {
                    return new WP_Error(
                        'missing_columns',
                        sprintf(__('The %s sheet is missing required columns: ', 'quizcourse-importer'), $sheet_name) . implode(', ', $missing_columns)
                    );
                }
                
                // Preview data (up to 5 rows)
                $preview_data = array();
                $row_count = min(count($sheet_data) - 1, 5);
                
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
                }
                
                $validation_results[$sheet_name] = array(
                    'headers' => $headers,
                    'preview' => $preview_data,
                    'total_rows' => count($sheet_data) - 1
                );
            }
            
            // Validate relationships between sheets
            $relationship_errors = $this->validate_sheet_relationships($validation_results);
            if (!empty($relationship_errors)) {
                return new WP_Error(
                    'relationship_errors',
                    __('The Excel file has relationship errors: ', 'quizcourse-importer') . implode(', ', $relationship_errors)
                );
            }
            
            return array(
                'type' => 'excel',
                'sheets' => $validation_results
            );
            
        } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
            return new WP_Error('excel_error', __('Error processing Excel file: ', 'quizcourse-importer') . $e->getMessage());
        } catch (Exception $e) {
            return new WP_Error('file_error', __('Error processing file: ', 'quizcourse-importer') . $e->getMessage());
        }
    }

    /**
     * Check for required columns in CSV file
     * 
     * @param array $headers CSV headers
     * @return array Missing required columns
     */
    private function check_csv_required_columns($headers) {
        // For CSV, we need a simplified structure that has all required columns
        $all_required = array();
        
        // Get all required columns from all sheets
        foreach ($this->required_columns as $sheet => $columns) {
            foreach ($columns as $column) {
                $all_required[] = $column;
            }
        }
        
        // Also need reference columns to establish relationships
        $reference_columns = array(
            'course_reference',
            'section_reference',
            'quiz_reference', 
            'question_reference'
        );
        
        $all_required = array_merge($all_required, $reference_columns);
        $all_required = array_unique($all_required);
        
        // Check which required columns are missing
        return array_diff($all_required, $headers);
    }

    /**
     * Check for required columns in Excel sheet
     * 
     * @param string $sheet_name Sheet name
     * @param array $headers Sheet headers
     * @return array Missing required columns
     */
    private function check_excel_required_columns($sheet_name, $headers) {
        if (!isset($this->required_columns[$sheet_name])) {
            return array();
        }
        
        // Check which required columns are missing
        return array_diff($this->required_columns[$sheet_name], $headers);
    }

    /**
     * Validate relationships between sheets
     * 
     * @param array $validation_results Validation results for each sheet
     * @return array Relationship errors
     */
    private function validate_sheet_relationships($validation_results) {
        $errors = array();
        
        // We need to check that references between sheets can be resolved
        // For example, section_reference in Quizzes should refer to valid entries in Sections
        
        // This would require checking preview data, but for a complete validation
        // we would need to check all data, not just preview
        // For now, we'll just ensure the reference columns exist
        
        if (!$this->column_exists($validation_results, 'Sections', 'course_reference')) {
            $errors[] = __('Sections sheet must have a course_reference column to link to Courses', 'quizcourse-importer');
        }
        
        if (!$this->column_exists($validation_results, 'Quizzes', 'section_reference')) {
            $errors[] = __('Quizzes sheet must have a section_reference column to link to Sections', 'quizcourse-importer');
        }
        
        if (!$this->column_exists($validation_results, 'Questions', 'quiz_reference')) {
            $errors[] = __('Questions sheet must have a quiz_reference column to link to Quizzes', 'quizcourse-importer');
        }
        
        if (!$this->column_exists($validation_results, 'Answers', 'question_reference')) {
            $errors[] = __('Answers sheet must have a question_reference column to link to Questions', 'quizcourse-importer');
        }
        
        return $errors;
    }

    /**
     * Check if a column exists in a sheet
     * 
     * @param array $validation_results Validation results
     * @param string $sheet_name Sheet name
     * @param string $column_name Column name
     * @return bool Whether the column exists
     */
    private function column_exists($validation_results, $sheet_name, $column_name) {
        if (!isset($validation_results[$sheet_name]) || !isset($validation_results[$sheet_name]['headers'])) {
            return false;
        }
        
        return in_array($column_name, $validation_results[$sheet_name]['headers']);
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
     * Generate HTML for field mapping interface
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
                
                <?php if ($validation_result['type'] === 'csv'): ?>
                    <!-- CSV Mapping -->
                    <div class="qci-field-mapping-table">
                        <table class="widefat">
                            <thead>
                                <tr>
                                    <th><?php _e('CSV Field', 'quizcourse-importer'); ?></th>
                                    <th><?php _e('System Field', 'quizcourse-importer'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($validation_result['headers'] as $header): ?>
                                    <tr>
                                        <td><?php echo esc_html($header); ?></td>
                                        <td>
                                            <select class="qci-field-mapping" name="mapping[<?php echo esc_attr($header); ?>]" data-sheet="csv" data-sheet-field="<?php echo esc_attr($header); ?>">
                                                <option value=""><?php _e('-- Skip this field --', 'quizcourse-importer'); ?></option>
                                                <?php echo $this->get_system_fields_options($header); ?>
                                            </select>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <!-- Excel Mapping -->
                    <div class="qci-tabs">
                        <ul class="qci-tabs-nav">
                            <?php foreach ($validation_result['sheets'] as $sheet_name => $sheet_data): ?>
                                <li>
                                    <a href="#qci-sheet-<?php echo sanitize_title($sheet_name); ?>"><?php echo esc_html($sheet_name); ?></a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <?php foreach ($validation_result['sheets'] as $sheet_name => $sheet_data): ?>
                            <div id="qci-sheet-<?php echo sanitize_title($sheet_name); ?>" class="qci-tab-content">
                                <h3><?php echo esc_html($sheet_name); ?> <?php _e('Mapping', 'quizcourse-importer'); ?></h3>
                                
                                <div class="qci-field-mapping-table">
                                    <table class="widefat">
                                        <thead>
                                            <tr>
                                                <th><?php _e('Excel Field', 'quizcourse-importer'); ?></th>
                                                <th><?php _e('System Field', 'quizcourse-importer'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($sheet_data['headers'] as $header): ?>
                                                <tr>
                                                    <td><?php echo esc_html($header); ?></td>
                                                    <td>
                                                        <select class="qci-field-mapping" name="mapping[<?php echo esc_attr($sheet_name); ?>][<?php echo esc_attr($header); ?>]" data-sheet="<?php echo esc_attr($sheet_name); ?>" data-sheet-field="<?php echo esc_attr($header); ?>">
                                                            <option value=""><?php _e('-- Skip this field --', 'quizcourse-importer'); ?></option>
                                                            <?php echo $this->get_system_fields_options($header, $sheet_name); ?>
                                                        </select>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <h4><?php _e('Preview Data', 'quizcourse-importer'); ?></h4>
                                <div class="qci-preview-table">
                                    <table class="widefat">
                                        <thead>
                                            <tr>
                                                <?php foreach ($sheet_data['headers'] as $header): ?>
                                                    <th><?php echo esc_html($header); ?></th>
                                                <?php endforeach; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($sheet_data['preview'] as $row): ?>
                                                <tr>
                                                    <?php foreach ($sheet_data['headers'] as $header): ?>
                                                        <td><?php echo esc_html(isset($row[$header]) ? $row[$header] : ''); ?></td>
                                                    <?php endforeach; ?>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <div class="qci-actions">
                    <button type="button" id="qci-start-import" class="button button-primary"><?php _e('Start Import', 'quizcourse-importer'); ?></button>
                    <button type="button" class="button qci-back-button"><?php _e('Back', 'quizcourse-importer'); ?></button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get system fields options for a form select element
     * 
     * @param string $header Header name
     * @param string $sheet_name Sheet name (for Excel files)
     * @return string HTML options
     */
    private function get_system_fields_options($header, $sheet_name = '') {
        $options = '';
        $selected = '';
        
        // Define system fields for each entity
        $system_fields = array(
            'Courses' => array(
                'title' => __('Course Title', 'quizcourse-importer'),
                'description' => __('Course Description', 'quizcourse-importer'),
                'featured_image' => __('Featured Image URL', 'quizcourse-importer'),
                'status' => __('Status (publish/draft)', 'quizcourse-importer'),
                'author' => __('Author Username', 'quizcourse-importer'),
                'date_created' => __('Creation Date', 'quizcourse-importer'),
                'ordering' => __('Order', 'quizcourse-importer'),
                'custom_field_' => __('Custom Field Prefix', 'quizcourse-importer')
            ),
            'Sections' => array(
                'title' => __('Section Title', 'quizcourse-importer'),
                'description' => __('Section Description', 'quizcourse-importer'),
                'course_id' => __('Course ID', 'quizcourse-importer'),
                'course_reference' => __('Course Reference', 'quizcourse-importer'),
                'ordering' => __('Order', 'quizcourse-importer')
            ),
            'Quizzes' => array(
                'title' => __('Quiz Title', 'quizcourse-importer'),
                'description' => __('Quiz Description', 'quizcourse-importer'),
                'section_id' => __('Section ID', 'quizcourse-importer'),
                'section_reference' => __('Section Reference', 'quizcourse-importer'),
                'category_id' => __('Category ID', 'quizcourse-importer'),
                'featured_image' => __('Featured Image URL', 'quizcourse-importer'),
                'status' => __('Status (publish/draft)', 'quizcourse-importer'),
                'author' => __('Author Username', 'quizcourse-importer'),
                'ordering' => __('Order', 'quizcourse-importer')
            ),
            'Questions' => array(
                'title' => __('Question Title', 'quizcourse-importer'),
                'text' => __('Question Text', 'quizcourse-importer'),
                'quiz_id' => __('Quiz ID', 'quizcourse-importer'),
                'quiz_reference' => __('Quiz Reference', 'quizcourse-importer'),
                'type' => __('Question Type', 'quizcourse-importer'),
                'category_id' => __('Category ID', 'quizcourse-importer'),
                'tag_id' => __('Tag ID', 'quizcourse-importer'),
                'image' => __('Image URL', 'quizcourse-importer'),
                'hint' => __('Hint', 'quizcourse-importer'),
                'explanation' => __('Explanation', 'quizcourse-importer'),
                'ordering' => __('Order', 'quizcourse-importer'),
                'weight' => __('Weight', 'quizcourse-importer')
            ),
            'Answers' => array(
                'text' => __('Answer Text', 'quizcourse-importer'),
                'question_id' => __('Question ID', 'quizcourse-importer'),
                'question_reference' => __('Question Reference', 'quizcourse-importer'),
                'is_correct' => __('Is Correct (1/0)', 'quizcourse-importer'),
                'image' => __('Image URL', 'quizcourse-importer'),
                'ordering' => __('Order', 'quizcourse-importer'),
                'weight' => __('Weight', 'quizcourse-importer')
            )
        );
        
        // If it's a CSV, show all fields
        if (empty($sheet_name)) {
            foreach ($system_fields as $entity => $fields) {
                $options .= '<optgroup label="' . esc_attr($entity) . '">';
                foreach ($fields as $field_key => $field_label) {
                    // Auto-select field if header matches common patterns
                    $selected = $this->is_field_match($header, $field_key) ? ' selected="selected"' : '';
                    $options .= '<option value="' . esc_attr($entity . '|' . $field_key) . '"' . $selected . '>' . esc_html($field_label) . '</option>';
                }
                $options .= '</optgroup>';
            }
        } 
        // For Excel, show only relevant fields for the current sheet
        else if (isset($system_fields[$sheet_name])) {
            foreach ($system_fields[$sheet_name] as $field_key => $field_label) {
                // Auto-select field if header matches common patterns
                $selected = $this->is_field_match($header, $field_key) ? ' selected="selected"' : '';
                $options .= '<option value="' . esc_attr($field_key) . '"' . $selected . '>' . esc_html($field_label) . '</option>';
            }
        }
        
        return $options;
    }

    /**
     * Check if a header matches a system field key
     * 
     * @param string $header Header name
     * @param string $field_key System field key
     * @return bool Whether the header matches the field
     */
    private function is_field_match($header, $field_key) {
        // Normalize both strings: lowercase, no spaces, no underscores
        $normalized_header = strtolower(str_replace(array(' ', '_', '-'), '', $header));
        $normalized_key = strtolower(str_replace(array(' ', '_', '-'), '', $field_key));
        
        // Direct match
        if ($normalized_header === $normalized_key) {
            return true;
        }
        
        // Check for partial matches based on common patterns
        $matches = array(
            'title' => array('name', 'title', 'heading'),
            'description' => array('desc', 'description', 'content', 'text'),
            'featured_image' => array('image', 'featuredimage', 'thumbnail', 'photo'),
            'status' => array('status', 'state', 'published'),
            'ordering' => array('order', 'ordering', 'position', 'sequence', 'sort'),
           'course_reference' => array('courseid', 'coursereference', 'course', 'courseref'),
           'section_reference' => array('sectionid', 'sectionreference', 'section', 'sectionref'),
           'quiz_reference' => array('quizid', 'quizreference', 'quiz', 'quizref'),
           'question_reference' => array('questionid', 'questionreference', 'question', 'questionref'),
           'is_correct' => array('correct', 'iscorrect', 'rightanswer', 'right'),
           'text' => array('text', 'content', 'body'),
           'weight' => array('weight', 'score', 'points', 'value'),
           'type' => array('type', 'questiontype', 'format')
       );
       
       if (isset($matches[$field_key])) {
           foreach ($matches[$field_key] as $match) {
               if (strpos($normalized_header, $match) !== false) {
                   return true;
               }
           }
       }
       
       return false;
   }

   /**
    * Validate data before importing
    *
    * @param array $data The data to validate
    * @return bool|WP_Error True if valid, WP_Error if invalid
    */
   public function validate_data($data) {
       $errors = array();
       
       // Validate courses
       if (isset($data['Courses'])) {
           foreach ($data['Courses'] as $index => $course) {
               if (empty($course['title'])) {
                   $errors[] = sprintf(__('Course at row %d is missing a title.', 'quizcourse-importer'), $index + 2);
               }
               
               if (isset($course['status']) && !in_array($course['status'], array('publish', 'draft', 'pending'))) {
                   $errors[] = sprintf(__('Course at row %d has an invalid status. Use "publish", "draft", or "pending".', 'quizcourse-importer'), $index + 2);
               }
           }
       }
       
       // Validate sections
       if (isset($data['Sections'])) {
           foreach ($data['Sections'] as $index => $section) {
               if (empty($section['title'])) {
                   $errors[] = sprintf(__('Section at row %d is missing a title.', 'quizcourse-importer'), $index + 2);
               }
               
               if (empty($section['course_reference'])) {
                   $errors[] = sprintf(__('Section at row %d is missing a course reference.', 'quizcourse-importer'), $index + 2);
               }
           }
       }
       
       // Validate quizzes
       if (isset($data['Quizzes'])) {
           foreach ($data['Quizzes'] as $index => $quiz) {
               if (empty($quiz['title'])) {
                   $errors[] = sprintf(__('Quiz at row %d is missing a title.', 'quizcourse-importer'), $index + 2);
               }
               
               if (empty($quiz['section_reference'])) {
                   $errors[] = sprintf(__('Quiz at row %d is missing a section reference.', 'quizcourse-importer'), $index + 2);
               }
           }
       }
       
       // Validate questions
       if (isset($data['Questions'])) {
           foreach ($data['Questions'] as $index => $question) {
               if (empty($question['text'])) {
                   $errors[] = sprintf(__('Question at row %d is missing text.', 'quizcourse-importer'), $index + 2);
               }
               
               if (empty($question['quiz_reference'])) {
                   $errors[] = sprintf(__('Question at row %d is missing a quiz reference.', 'quizcourse-importer'), $index + 2);
               }
               
               if (isset($question['type']) && !in_array($question['type'], $this->valid_question_types)) {
                   $errors[] = sprintf(
                       __('Question at row %d has an invalid type. Valid types are: %s', 'quizcourse-importer'),
                       $index + 2,
                       implode(', ', $this->valid_question_types)
                   );
               }
           }
       }
       
       // Validate answers
       if (isset($data['Answers'])) {
           foreach ($data['Answers'] as $index => $answer) {
               if (empty($answer['text'])) {
                   $errors[] = sprintf(__('Answer at row %d is missing text.', 'quizcourse-importer'), $index + 2);
               }
               
               if (empty($answer['question_reference'])) {
                   $errors[] = sprintf(__('Answer at row %d is missing a question reference.', 'quizcourse-importer'), $index + 2);
               }
               
               if (isset($answer['is_correct']) && !in_array($answer['is_correct'], array('0', '1', 0, 1, true, false))) {
                   $errors[] = sprintf(
                       __('Answer at row %d has an invalid is_correct value. Use 0 or 1.', 'quizcourse-importer'),
                       $index + 2
                   );
               }
           }
       }
       
       // Check if questions have answers
       if (isset($data['Questions']) && isset($data['Answers'])) {
           $question_refs = array_column($data['Questions'], 'id');
           $answer_question_refs = array_column($data['Answers'], 'question_reference');
           
           $questions_without_answers = array_diff($question_refs, $answer_question_refs);
           if (!empty($questions_without_answers)) {
               $errors[] = __('Some questions do not have answers. Every question should have at least one answer.', 'quizcourse-importer');
           }
       }
       
       // Return validation result
       if (!empty($errors)) {
           return new WP_Error('validation_error', implode('<br>', $errors));
       }
       
       return true;
   }

   /**
    * Validate references between entities
    *
    * @param array $data The data to validate
    * @return bool|WP_Error True if valid, WP_Error if invalid
    */
   public function validate_references($data) {
       $errors = array();
       
       // Build reference maps
       $course_refs = array();
       $section_refs = array();
       $quiz_refs = array();
       $question_refs = array();
       
       if (isset($data['Courses'])) {
           foreach ($data['Courses'] as $course) {
               if (isset($course['id'])) {
                   $course_refs[] = $course['id'];
               }
           }
       }
       
       if (isset($data['Sections'])) {
           foreach ($data['Sections'] as $index => $section) {
               if (isset($section['id'])) {
                   $section_refs[] = $section['id'];
               }
               
               if (isset($section['course_reference']) && !in_array($section['course_reference'], $course_refs)) {
                   $errors[] = sprintf(
                       __('Section at row %d references a course that does not exist: %s', 'quizcourse-importer'),
                       $index + 2,
                       $section['course_reference']
                   );
               }
           }
       }
       
       if (isset($data['Quizzes'])) {
           foreach ($data['Quizzes'] as $index => $quiz) {
               if (isset($quiz['id'])) {
                   $quiz_refs[] = $quiz['id'];
               }
               
               if (isset($quiz['section_reference']) && !in_array($quiz['section_reference'], $section_refs)) {
                   $errors[] = sprintf(
                       __('Quiz at row %d references a section that does not exist: %s', 'quizcourse-importer'),
                       $index + 2,
                       $quiz['section_reference']
                   );
               }
           }
       }
       
       if (isset($data['Questions'])) {
           foreach ($data['Questions'] as $index => $question) {
               if (isset($question['id'])) {
                   $question_refs[] = $question['id'];
               }
               
               if (isset($question['quiz_reference']) && !in_array($question['quiz_reference'], $quiz_refs)) {
                   $errors[] = sprintf(
                       __('Question at row %d references a quiz that does not exist: %s', 'quizcourse-importer'),
                       $index + 2,
                       $question['quiz_reference']
                   );
               }
           }
       }
       
       if (isset($data['Answers'])) {
           foreach ($data['Answers'] as $index => $answer) {
               if (isset($answer['question_reference']) && !in_array($answer['question_reference'], $question_refs)) {
                   $errors[] = sprintf(
                       __('Answer at row %d references a question that does not exist: %s', 'quizcourse-importer'),
                       $index + 2,
                       $answer['question_reference']
                   );
               }
           }
       }
       
       // Return validation result
       if (!empty($errors)) {
           return new WP_Error('reference_error', implode('<br>', $errors));
       }
       
       return true;
   }
}

/**
* QuizCourse Importer - Main JavaScript file
* Handles file uploads, field mapping, and the import process for single sheet data
*/
(function($) {
   'use strict';
   
   // Global variables
   const QCI = {
       currentStep: 1,
       fileId: '',
       fileName: '',
       uploadComplete: false,
       mapComplete: false,
       importing: false,
       importTotal: 0,
       importProcessed: 0,
       mappingFields: {},
       requiredFields: {
           'course': ['title', 'description'],
           'quiz': ['title', 'course_reference'],
           'question': ['question_text', 'quiz_reference', 'type'],
           'answer': ['answer_text', 'question_reference', 'is_correct']
       }
   };
   
   // Localized strings from WordPress
   const strings = window.qci_strings || {
       no_file_selected: 'โปรดเลือกไฟล์ที่จะอัพโหลด',
       file_too_large: 'ไฟล์มีขนาดใหญ่เกินไป',
       invalid_file_type: 'รูปแบบไฟล์ไม่ถูกต้อง โปรดใช้ไฟล์ CSV หรือ Excel',
       validating: 'กำลังตรวจสอบ...',
       continue_to_mapping: 'ไปยังการจับคู่ฟิลด์',
       uploading: 'กำลังอัพโหลด...',
       mapping_error: 'โปรดตรวจสอบการจับคู่ฟิลด์ที่จำเป็นทั้งหมด',
       no_record_type: 'ไม่พบฟิลด์ประเภทเรคอร์ด (record_type) ซึ่งจำเป็นสำหรับการระบุประเภทข้อมูล',
       importing: 'กำลังนำเข้าข้อมูล...',
       import_complete: 'นำเข้าข้อมูลเสร็จสมบูรณ์!',
       import_failed: 'การนำเข้าข้อมูลล้มเหลว',
       confirm_cancel: 'คุณแน่ใจที่จะยกเลิก? ข้อมูลทั้งหมดที่ดำเนินการไปแล้วจะหายไป',
       processing: 'กำลังประมวลผลข้อมูล...',
       courses: 'คอร์ส',
       quizzes: 'ควิซ',
       questions: 'คำถาม',
       answers: 'คำตอบ',
       imported: 'นำเข้าสำเร็จแล้ว',
       server_error: 'เกิดข้อผิดพลาดที่เซิร์ฟเวอร์ โปรดลองอีกครั้ง',
       go_back: 'ย้อนกลับ',
       view_courses: 'ดูคอร์ส',
       new_import: 'นำเข้าข้อมูลใหม่',
       preview_data: 'ดูตัวอย่างข้อมูล',
       hide_preview: 'ซ่อนตัวอย่าง',
       confirm_import: 'ยืนยันการนำเข้าข้อมูล',
       missing_fields: 'พบฟิลด์ที่จำเป็นแต่ยังไม่ได้จับคู่: ',
       auto_map_success: 'จับคู่ฟิลด์อัตโนมัติสำเร็จ',
       mapping_note: 'หมายเหตุ: คอลัมน์ record_type ถูกจับคู่โดยอัตโนมัติเพื่อระบุประเภทของข้อมูล',
       processing_courses: 'กำลังประมวลผลคอร์ส...',
       processing_quizzes: 'กำลังประมวลผลควิซ...',
       processing_questions: 'กำลังประมวลผลคำถาม...',
       processing_answers: 'กำลังประมวลผลคำตอบ...',
       finalizing: 'กำลังสรุปผลการนำเข้าข้อมูล...'
   };
   
   // Initialize when document is ready
   $(document).ready(function() {
       initImporter();
   });
   
   /**
    * Initialize the importer
    */
   function initImporter() {
       // File input change handler
       $('#qci_import_file').on('change', handleFileSelect);
       
       // Form submit handler for file validation
       $('#qci-upload-form').on('submit', validateFile);
       
       // Remove file button
       $('#qci-remove-file').on('click', removeFile);
       
       // Dynamic event handlers
       $(document).on('click', '#qci-validate-mapping', validateMapping);
       $(document).on('click', '#qci-start-import', startImport);
       $(document).on('click', '.qci-back-button', goBack);
       $(document).on('click', '.qci-steps-list li', navigateStep);
       $(document).on('click', '#qci-auto-map', autoMapFields);
       $(document).on('click', '#qci-reset-mapping', resetMapping);
       $(document).on('click', '#qci-toggle-preview', togglePreview);
       $(document).on('click', '#qci-save-template', saveTemplate);
       $(document).on('click', '#qci-load-template', loadTemplate);
       
       // Initialize tooltips
       initTooltips();
       
       // Initialize drag and drop
       initDragDrop();
   }
   
   /**
    * Initialize tooltips
    */
   function initTooltips() {
       $('.qci-tooltip').each(function() {
           const $this = $(this);
           const $tooltip = $('<span class="qci-tooltip-text">' + $this.data('tooltip') + '</span>');
           
           $this.append($tooltip);
           
           $this.on('mouseenter', function() {
               $tooltip.fadeIn(200);
           }).on('mouseleave', function() {
               $tooltip.fadeOut(200);
           });
       });
   }
   
   /**
    * Initialize drag and drop functionality
    */
   function initDragDrop() {
       const dropArea = $('.qci-file-upload');
       
       if (!dropArea.length) return;
       
       // Prevent default behaviors
       ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
           dropArea[0].addEventListener(eventName, preventDefaults, false);
       });
       
       // Highlight drop area when file is dragged over
       ['dragenter', 'dragover'].forEach(eventName => {
           dropArea[0].addEventListener(eventName, highlight, false);
       });
       
       // Remove highlight when file is dragged out or dropped
       ['dragleave', 'drop'].forEach(eventName => {
           dropArea[0].addEventListener(eventName, unhighlight, false);
       });
       
       // Handle dropped files
       dropArea[0].addEventListener('drop', handleDrop, false);
       
       function preventDefaults(e) {
           e.preventDefault();
           e.stopPropagation();
       }
       
       function highlight() {
           dropArea.addClass('highlight');
       }
       
       function unhighlight() {
           dropArea.removeClass('highlight');
       }
       
       function handleDrop(e) {
           const dt = e.dataTransfer;
           const files = dt.files;
           
           if (files.length) {
               document.getElementById('qci_import_file').files = files;
               handleFileSelect();
           }
       }
   }
   
   /**
    * Handle file selection
    */
   function handleFileSelect() {
       const fileInput = $('#qci_import_file')[0];
       
       if (fileInput.files.length === 0) {
           removeFile();
           return;
       }
       
       const file = fileInput.files[0];
       const maxSize = parseInt($('#qci_import_file').data('max-size') || 10485760); // 10MB default
       
       // Validate file size
       if (file.size > maxSize) {
           showError(strings.file_too_large);
           removeFile();
           return;
       }
       
       // Validate file type
       const fileExt = file.name.split('.').pop().toLowerCase();
       if (!['csv', 'xlsx', 'xls'].includes(fileExt)) {
           showError(strings.invalid_file_type);
           removeFile();
           return;
       }
       
       // Update UI with file information
       QCI.fileName = file.name;
       $('#qci-file-name').text(file.name);
       $('#qci-file-size').text(' (' + formatFileSize(file.size) + ')');
       $('.qci-selected-file').show();
       $('.qci-file-upload').addClass('has-file');
       
       // Enable the validate button
       $('#qci-validate-file').prop('disabled', false);
       
       // Hide previous errors
       hideError();
   }
   
   /**
    * Format file size into readable format
    */
   function formatFileSize(bytes) {
       if (bytes === 0) return '0 Bytes';
       
       const k = 1024;
       const sizes = ['Bytes', 'KB', 'MB', 'GB'];
       const i = Math.floor(Math.log(bytes) / Math.log(k));
       
       return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
   }
   
   /**
    * Remove file from input
    */
   function removeFile(e) {
       if (e) e.preventDefault();
       
       $('#qci_import_file').val('');
       $('.qci-selected-file').hide();
       $('.qci-file-upload').removeClass('has-file');
       QCI.fileName = '';
       QCI.fileId = '';
       QCI.uploadComplete = false;
       
       // Disable validate button
       $('#qci-validate-file').prop('disabled', true);
   }
   
   /**
    * Validate the uploaded file
    */
   function validateFile(e) {
       e.preventDefault();
       
       // Check if file is selected
       if ($('#qci_import_file')[0].files.length === 0) {
           showError(strings.no_file_selected);
           return;
       }
       
       // Create form data for upload
       const formData = new FormData();
       formData.append('action', 'qci_validate_file');
       formData.append('security', $('#qci_security').val());
       formData.append('qci_import_file', $('#qci_import_file')[0].files[0]);
       formData.append('single_sheet', 'true'); // Flag for single sheet processing
       
       // Update UI to show loading state
       $('#qci-validate-file').prop('disabled', true).text(strings.validating);
       $('.qci-actions').addClass('loading');
       $('.qci-loading').show();
       
       // Send AJAX request
       $.ajax({
           url: ajaxurl,
           type: 'POST',
           data: formData,
           processData: false,
           contentType: false,
           success: function(response) {
               if (response.success) {
                   QCI.uploadComplete = true;
                   QCI.fileId = response.data.file_id;
                   
                   // Show mapping step with the returned data
                   showMappingStep(response.data);
               } else {
                   // Show error message
                   showError(response.data || strings.server_error);
                   $('#qci-validate-file').prop('disabled', false).text(strings.continue_to_mapping);
               }
           },
           error: function() {
               showError(strings.server_error);
               $('#qci-validate-file').prop('disabled', false).text(strings.continue_to_mapping);
           },
           complete: function() {
               $('.qci-actions').removeClass('loading');
               $('.qci-loading').hide();
           }
       });
   }
   
   /**
    * Show the field mapping step
    */
   function showMappingStep(data) {
       // Update step indicators
       QCI.currentStep = 2;
       updateStepIndicator();
       
       // Hide upload step, show mapping step
       $('#qci-step-1').hide();
       
       // Create mapping interface HTML
       let mappingHTML = `
           <div class="qci-panel">
               <div class="qci-panel-header">
                   <h2>จับคู่ฟิลด์ข้อมูล</h2>
                   <p>จับคู่คอลัมน์ในไฟล์ของคุณกับฟิลด์ในระบบ</p>
               </div>
               <div class="qci-panel-body">
                   <div class="qci-mapping-tools">
                       <button type="button" id="qci-auto-map" class="button">
                           <span class="dashicons dashicons-superhero"></span> จับคู่อัตโนมัติ
                       </button>
                       <button type="button" id="qci-reset-mapping" class="button">
                           <span class="dashicons dashicons-update"></span> รีเซ็ต
                       </button>
                       <button type="button" id="qci-toggle-preview" class="button">
                           <span class="dashicons dashicons-visibility"></span> <span class="qci-preview-text">${strings.preview_data}</span>
                       </button>
                       <div class="qci-template-tools">
                           <select id="qci-template-select">
                               <option value="">-- เลือกเทมเพลต --</option>
                               ${generateTemplateOptions(data.templates || [])}
                           </select>
                           <button type="button" id="qci-load-template" class="button">
                               <span class="dashicons dashicons-download"></span> โหลด
                           </button>
                           <button type="button" id="qci-save-template" class="button">
                               <span class="dashicons dashicons-save"></span> บันทึก
                           </button>
                       </div>
                   </div>
                   
                   <div class="qci-mapping-instructions">
                       <div class="qci-mapping-note">
                           <p><strong>หมายเหตุ:</strong> ฟิลด์ที่มีเครื่องหมาย <span class="required">*</span> เป็นฟิลด์ที่จำเป็นต้องจับคู่</p>
                           <p>${strings.mapping_note}</p>
                       </div>
                   </div>
                   
                   <form id="qci-mapping-form">
                       <input type="hidden" id="qci_file_id" name="file_id" value="${data.file_id}">
                       <input type="hidden" name="action" value="qci_process_import">
                       <input type="hidden" name="security" value="${$('#qci_security').val()}">
                       
                       <div class="qci-field-mapping-table">
                           <table class="widefat">
                               <thead>
                                   <tr>
                                       <th>ฟิลด์ในไฟล์</th>
                                       <th>ฟิลด์ในระบบ</th>
                                       <th width="30%">ตัวอย่างข้อมูล</th>
                                   </tr>
                               </thead>
                               <tbody>
                                   ${generateMappingRows(data.headers, data.preview || [])}
                               </tbody>
                           </table>
                       </div>
                   </form>
                   
                   <div class="qci-preview-container" style="display: none;">
                       <h3>ตัวอย่างข้อมูล</h3>
                       <div class="qci-data-preview">
                           ${generatePreviewTable(data.headers, data.preview || [])}
                       </div>
                   </div>
                   
                   <div class="qci-required-fields">
                       <h3>ฟิลด์ที่จำเป็น</h3>
                       <div class="qci-required-fields-grid">
                           <div class="qci-required-group">
                               <h4>คอร์ส (Course)</h4>
                               <ul>
                                   <li><code>title</code> - ชื่อคอร์ส</li>
                                   <li><code>description</code> - รายละเอียดคอร์ส</li>
                               </ul>
                           </div>
                           <div class="qci-required-group">
                               <h4>ควิซ (Quiz)</h4>
                               <ul>
                                   <li><code>title</code> - ชื่อควิซ</li>
                                   <li><code>course_reference</code> - อ้างอิงถึงคอร์ส</li>
                               </ul>
                           </div>
                           <div class="qci-required-group">
                               <h4>คำถาม (Question)</h4>
                               <ul>
                                   <li><code>question_text</code> - ข้อความคำถาม</li>
                                   <li><code>quiz_reference</code> - อ้างอิงถึงควิซ</li>
                                   <li><code>type</code> - ประเภทคำถาม</li>
                               </ul>
                           </div>
                           <div class="qci-required-group">
                               <h4>คำตอบ (Answer)</h4>
                               <ul>
                                   <li><code>answer_text</code> - ข้อความคำตอบ</li>
                                   <li><code>question_reference</code> - อ้างอิงถึงคำถาม</li>
                                   <li><code>is_correct</code> - เป็นคำตอบที่ถูกต้องหรือไม่ (1/0)</li>
                               </ul>
                           </div>
                       </div>
                   </div>
                   
                   <div class="qci-validation-messages"></div>
                   
                   <div class="qci-actions">
                       <button type="button" class="button qci-back-button">
                           <span class="dashicons dashicons-arrow-left-alt"></span> ย้อนกลับ
                       </button>
                       <button type="button" id="qci-validate-mapping" class="button button-secondary">
                           <span class="dashicons dashicons-yes-alt"></span> ตรวจสอบการจับคู่
                       </button>
                       <button type="button" id="qci-start-import" class="button button-primary" disabled>
                           <span class="dashicons dashicons-database-import"></span> เริ่มนำเข้าข้อมูล
                       </button>
                   </div>
               </div>
           </div>
       `;
       
       // Insert the HTML into the page
       $('#qci-step-2').html(mappingHTML).show();
       
       // Store available fields data
       QCI.mappingFields = data.system_fields || {};
       
       // Pre-select record_type field if it exists
       const recordTypeIndex = data.headers.findIndex(header => 
           header.toLowerCase() === 'record_type' || 
           header.toLowerCase() === 'type' || 
           header.toLowerCase() === 'entity_type'
       );
       
       if (recordTypeIndex !== -1) {
           $(`select[name="mapping[${data.headers[recordTypeIndex]}]"]`).val('record_type').prop('disabled', true);
       } else {
           showError(strings.no_record_type);
       }
       
       // Auto-map fields if enabled
       if (data.auto_map === true) {
           autoMapFields();
       }
   }
   
   /**
    * Generate options for template selection dropdown
    */
   function generateTemplateOptions(templates) {
       if (!templates || !templates.length) return '';
       
       let options = '';
       templates.forEach(template => {
           options += `<option value="${template.id}">${template.name}</option>`;
       });
       
       return options;
   }
   
   /**
    * Generate the mapping rows HTML
    */
   function generateMappingRows(headers, previewData) {
       let rows = '';
       
       headers.forEach((header, index) => {
           // Get sample value from preview data
           let sampleValue = '';
           if (previewData.length > 0) {
               sampleValue = previewData[0][index] || '';
           }
           
           // Required marker for record_type field
           const isRecordType = header.toLowerCase() === 'record_type' || header.toLowerCase() === 'type' || header.toLowerCase() === 'entity_type';
           const requiredMark = isRecordType ? '<span class="required">*</span>' : '';
           
           rows += `
               <tr${isRecordType ? ' class="qci-record-type-row"' : ''}>
                   <td>
                       ${header} ${requiredMark}
                   </td>
                   <td>
                       <select name="mapping[${header}]" class="qci-field-mapping-select" data-field="${header}"${isRecordType ? ' disabled' : ''}>
                           <option value="">-- ข้ามฟิลด์นี้ --</option>
                           ${generateFieldOptions(header)}
                       </select>
                   </td>
                   <td>
                       <code class="qci-sample-value">${sampleValue}</code>
                   </td>
               </tr>
           `;
       });
       
       return rows;
   }
   
   /**
    * Generate field options for select dropdown
    */
   function generateFieldOptions(header) {
       // List of all system fields grouped by entity
       const allFields = {
           'meta': [
               { value: 'record_type', label: 'ประเภทเรคอร์ด (record_type)', required: true }
           ],
           'course': [
               { value: 'title', label: 'ชื่อคอร์ส (title)', required: true },
               { value: 'description', label: 'รายละเอียดคอร์ส (description)', required: true },
               { value: 'id', label: 'รหัสอ้างอิงคอร์ส (id)', required: false },
               { value: 'image', label: 'รูปภาพคอร์ส (image URL)', required: false },
               { value: 'author_id', label: 'ผู้สร้างคอร์ส (author_id)', required: false },
               { value: 'category_ids', label: 'หมวดหมู่คอร์ส (category_ids)', required: false },
               { value: 'status', label: 'สถานะคอร์ส (status)', required: false },
               { value: 'date_created', label: 'วันที่สร้างคอร์ส (date_created)', required: false },
               { value: 'ordering', label: 'ลำดับการแสดงผล (ordering)', required: false },
               { value: 'options', label: 'ตัวเลือกเพิ่มเติม (JSON options)', required: false }
           ],
           'quiz': [
               { value: 'title', label: 'ชื่อควิซ (title)', required: true },
               { value: 'description', label: 'รายละเอียดควิซ (description)', required: false },
               { value: 'id', label: 'รหัสอ้างอิงควิซ (id)', required: false },
               { value: 'course_reference', label: 'อ้างอิงถึงคอร์ส (course_reference)', required: true },
               { value: 'quiz_image', label: 'รูปภาพควิซ (image URL)', required: false },
               { value: 'quiz_category_id', label: 'รหัสหมวดหมู่ควิซ (category_id)', required: false },
               { value: 'published', label: 'สถานะเผยแพร่ (1/0)', required: false },
               { value: 'ordering', label: 'ลำดับการแสดงผล (ordering)', required: false },
               { value: 'options', label: 'ตัวเลือกเพิ่มเติม (JSON options)', required: false }
           ],
           'question': [
               { value: 'question_text', label: 'ข้อความคำถาม (question_text)', required: true },
               { value: 'question_title', label: 'หัวข้อคำถาม (question_title)', required: false },
               { value: 'id', label: 'รหัสอ้างอิงคำถาม (id)', required: false },
               { value: 'quiz_reference', label: 'อ้างอิงถึงควิซ (quiz_reference)', required: true },
               { value: 'type', label: 'ประเภทคำถาม (type)', required: true },
               { value: 'question_image', label: 'รูปภาพคำถาม (image URL)', required: false },
               { value: 'category_id', label: 'รหัสหมวดหมู่คำถาม (category_id)', required: false },
               { value: 'tag_id', label: 'รหัสแท็กคำถาม (tag_id)', required: false },
               { value: 'hint', label: 'คำใบ้ (hint)', required: false },
               { value: 'explanation', label: 'คำอธิบาย (explanation)', required: false },
               { value: 'weight', label: 'น้ำหนักคะแนน (weight)', required: false },
               { value: 'ordering', label: 'ลำดับการแสดงผล (ordering)', required: false },
               { value: 'published', label: 'สถานะเผยแพร่ (1/0)', required: false },
               { value: 'options', label: 'ตัวเลือกเพิ่มเติม (JSON options)', required: false }
           ],
           'answer': [
               { value: 'answer_text', label: 'ข้อความคำตอบ (answer_text)', required: true },
               { value: 'id', label: 'รหัสอ้างอิงคำตอบ (id)', required: false },
               { value: 'question_reference', label: 'อ้างอิงถึงคำถาม (question_reference)', required: true },
               { value: 'is_correct', label: 'เป็นคำตอบที่ถูกต้อง (1/0)', required: true },
               { value: 'image', label: 'รูปภาพคำตอบ (image URL)', required: false },
               { value: 'weight', label: 'น้ำหนักคะแนน (weight)', required: false },
               { value: 'ordering', label: 'ลำดับการแสดงผล (ordering)', required: false },
               { value: 'options', label: 'ตัวเลือกเพิ่มเติม (JSON options)', required: false }
           ]
       };
       
       let options = '';
       
       // Add options from each entity group
       Object.keys(allFields).forEach(group => {
           options += `<optgroup label="${getGroupLabel(group)}">`;
           
           allFields[group].forEach(field => {
               const requiredMark = field.required ? ' *' : '';
               const selected = isFieldMatch(header, field.value) ? ' selected' : '';
               options += `<option value="${field.value}"${selected}>${field.label}${requiredMark}</option>`;
           });
           
           options += '</optgroup>';
       });
       
       return options;
   }
   
   /**
    * Get user-friendly group label
    */
   function getGroupLabel(group) {
       switch (group) {
           case 'meta': return 'ฟิลด์ข้อมูลทั่วไป';
           case 'course': return 'ฟิลด์คอร์ส';
           case 'quiz': return 'ฟิลด์ควิซ';
           case 'question': return 'ฟิลด์คำถาม';
           case 'answer': return 'ฟิลด์คำตอบ';
           default: return group;
       }
   }
   
   /**
    * Check if header matches a field
    */
   function isFieldMatch(header, field) {
       // Normalize header and field for comparison
       const normalizedHeader = header.toLowerCase().replace(/[_\s-]/g, '');
       const normalizedField = field.toLowerCase().replace(/[_\s-]/g, '');
       
       // Special case for record_type field
       if (field === 'record_type' && 
           (normalizedHeader === 'recordtype' || 
            normalizedHeader === 'type' || 
            normalizedHeader === 'entitytype')) {
           return true;
       }
       
     // Simple matching patterns - can be expanded for better auto-detection
       const fieldPatterns = {
           'title': ['title', 'name', 'heading'],
           'description': ['description', 'desc', 'content', 'text'],
           'id': ['id', 'identifier', 'key', 'reference'],
           'record_type': ['recordtype', 'type', 'entitytype'],
           'course_reference': ['coursereference', 'courseid', 'coursekey', 'course'],
           'quiz_reference': ['quizreference', 'quizid', 'quizkey', 'quiz'],
           'question_reference': ['questionreference', 'questionid', 'questionkey', 'question'],
           'question_text': ['questiontext', 'question', 'questioncontent'],
           'answer_text': ['answertext', 'answer', 'answercontent', 'response'],
           'is_correct': ['iscorrect', 'correct', 'right', 'iscorrectanswer'],
           'image': ['image', 'imageurl', 'picture', 'photo', 'thumbnail'],
           'quiz_image': ['quizimage', 'quizpicture', 'quizphoto'],
           'question_image': ['questionimage', 'questionpicture'],
           'type': ['type', 'questiontype', 'answertype', 'format'],
           'published': ['published', 'active', 'status', 'isactive'],
           'ordering': ['ordering', 'order', 'sequence', 'position', 'sortorder'],
           'category_id': ['categoryid', 'category', 'categorykey'],
           'tag_id': ['tagid', 'tag', 'tagkey'],
           'weight': ['weight', 'score', 'points', 'value'],
           'hint': ['hint', 'clue', 'tip'],
           'explanation': ['explanation', 'reason', 'solution', 'answerexplanation'],
           'options': ['options', 'settings', 'configuration', 'config', 'preferences']
       };

       // Check if field name matches any pattern
       for (const [key, patterns] of Object.entries(fieldPatterns)) {
           if (key === field) {
               for (const pattern of patterns) {
                   if (normalizedHeader.includes(pattern) || pattern.includes(normalizedHeader)) {
                       return true;
                   }
               }
           }
       }
       
       return false;
   }
   
   /**
    * Generate preview table HTML
    */
   function generatePreviewTable(headers, previewData) {
       if (!previewData || !previewData.length) {
           return '<p>ไม่มีข้อมูลตัวอย่าง</p>';
       }
       
       let html = `
           <div class="qci-preview-table-wrapper">
               <table class="widefat qci-preview-table">
                   <thead>
                       <tr>
                           ${headers.map(header => `<th>${header}</th>`).join('')}
                       </tr>
                   </thead>
                   <tbody>
       `;
       
       // Add up to 5 rows of preview data
       const maxRows = Math.min(previewData.length, 5);
       for (let i = 0; i < maxRows; i++) {
           html += '<tr>';
           headers.forEach((header, index) => {
               html += `<td>${previewData[i][index] || ''}</td>`;
           });
           html += '</tr>';
       }
       
       html += `
                   </tbody>
               </table>
           </div>
           <p class="qci-preview-note">แสดง ${maxRows} แถวแรกจากทั้งหมด ${previewData.length} แถว</p>
       `;
       
       return html;
   }
   
   /**
    * Toggle preview table visibility
    */
   function togglePreview(e) {
       e.preventDefault();
       
       const $container = $('.qci-preview-container');
       const $button = $('#qci-toggle-preview');
       const $text = $button.find('.qci-preview-text');
       
       if ($container.is(':visible')) {
           $container.slideUp();
           $text.text(strings.preview_data);
       } else {
           $container.slideDown();
           $text.text(strings.hide_preview);
       }
   }
   
   /**
    * Auto-map fields based on field name patterns
    */
   function autoMapFields(e) {
       if (e) e.preventDefault();
       
       $('.qci-field-mapping-select').each(function() {
           const $select = $(this);
           
           // Skip if already mapped or disabled
           if ($select.val() || $select.prop('disabled')) {
               return;
           }
           
           const fieldName = $select.data('field');
           
           // Find the best match
           let bestMatch = '';
           let bestMatchScore = 0;
           
           $select.find('option').each(function() {
               const $option = $(this);
               const value = $option.val();
               
               if (!value) return; // Skip empty option
               
               // Calculate match score
               const score = calculateMatchScore(fieldName, value);
               
               if (score > bestMatchScore) {
                   bestMatchScore = score;
                   bestMatch = value;
               }
           });
           
           // Apply best match if score is above threshold
           if (bestMatchScore > 0.3) {
               $select.val(bestMatch);
           }
       });
       
       // Show success message
       showNotification(strings.auto_map_success, 'success');
   }
   
   /**
    * Calculate match score between field name and system field
    */
   function calculateMatchScore(fieldName, systemField) {
       const normalizedField = fieldName.toLowerCase().replace(/[_\s-]/g, '');
       const normalizedSystem = systemField.toLowerCase().replace(/[_\s-]/g, '');
       
       // Exact match
       if (normalizedField === normalizedSystem) {
           return 1.0;
       }
       
       // Contains the field name
       if (normalizedField.includes(normalizedSystem) || normalizedSystem.includes(normalizedField)) {
           return 0.8;
       }
       
       // Check for common patterns
       const fieldPatterns = {
           'title': ['title', 'name', 'heading'],
           'description': ['description', 'desc', 'content', 'text'],
           'id': ['id', 'identifier', 'key', 'reference'],
           'record_type': ['recordtype', 'type', 'entitytype'],
           'course_reference': ['coursereference', 'courseid', 'coursekey', 'course'],
           'quiz_reference': ['quizreference', 'quizid', 'quizkey', 'quiz'],
           'question_reference': ['questionreference', 'questionid', 'questionkey', 'question'],
           'question_text': ['questiontext', 'question', 'questioncontent'],
           'answer_text': ['answertext', 'answer', 'answercontent', 'response'],
           'is_correct': ['iscorrect', 'correct', 'right', 'iscorrectanswer'],
           'image': ['image', 'imageurl', 'picture', 'photo', 'thumbnail'],
           'quiz_image': ['quizimage', 'quizpicture', 'quizphoto'],
           'question_image': ['questionimage', 'questionpicture'],
           'type': ['type', 'questiontype', 'answertype', 'format'],
           'published': ['published', 'active', 'status', 'isactive'],
           'ordering': ['ordering', 'order', 'sequence', 'position', 'sortorder'],
           'category_id': ['categoryid', 'category', 'categorykey'],
           'tag_id': ['tagid', 'tag', 'tagkey'],
           'weight': ['weight', 'score', 'points', 'value'],
           'hint': ['hint', 'clue', 'tip'],
           'explanation': ['explanation', 'reason', 'solution', 'answerexplanation'],
           'options': ['options', 'settings', 'configuration', 'config', 'preferences']
       };
       
       if (fieldPatterns[systemField]) {
           for (const pattern of fieldPatterns[systemField]) {
               if (normalizedField.includes(pattern)) {
                   return 0.7;
               }
           }
       }
       
       // Check for partial matches (at least 3 characters)
       for (let i = 0; i < normalizedField.length - 2; i++) {
           const part = normalizedField.substr(i, 3);
           if (normalizedSystem.includes(part)) {
               return 0.4;
           }
       }
       
       return 0;
   }
   
   /**
    * Reset all field mappings
    */
   function resetMapping(e) {
       e.preventDefault();
       
       $('.qci-field-mapping-select').each(function() {
           const $select = $(this);
           
           // Skip disabled fields (like record_type)
           if (!$select.prop('disabled')) {
               $select.val('');
           }
       });
       
       // Clear validation messages
       $('.qci-validation-messages').empty();
       $('#qci-start-import').prop('disabled', true);
   }
   
   /**
    * Save current mapping as a template
    */
   function saveTemplate(e) {
       e.preventDefault();
       
       // Get template name from user
       const templateName = prompt('ชื่อเทมเพลต:');
       if (!templateName) return;
       
       // Collect mapping data
       const mappingData = {};
       $('.qci-field-mapping-select').each(function() {
           const $select = $(this);
           const fieldName = $select.data('field');
           const systemField = $select.val();
           
           if (systemField) {
               mappingData[fieldName] = systemField;
           }
       });
       
       // Send AJAX request to save template
       $.ajax({
           url: ajaxurl,
           type: 'POST',
           data: {
               action: 'qci_save_mapping_template',
               security: $('#qci_security').val(),
               template_name: templateName,
               mapping_data: JSON.stringify(mappingData)
           },
           success: function(response) {
               if (response.success) {
                   // Add to template select dropdown
                   $('#qci-template-select').append(`<option value="${response.data.id}">${templateName}</option>`);
                   showNotification('บันทึกเทมเพลตสำเร็จ', 'success');
               } else {
                   showNotification('ไม่สามารถบันทึกเทมเพลต: ' + response.data, 'error');
               }
           },
           error: function() {
               showNotification(strings.server_error, 'error');
           }
       });
   }
   
   /**
    * Load a saved mapping template
    */
   function loadTemplate(e) {
       e.preventDefault();
       
       const templateId = $('#qci-template-select').val();
       if (!templateId) return;
       
       // Send AJAX request to load template
       $.ajax({
           url: ajaxurl,
           type: 'POST',
           data: {
               action: 'qci_load_mapping_template',
               security: $('#qci_security').val(),
               template_id: templateId
           },
           success: function(response) {
               if (response.success) {
                   // Apply template to form
                   const mappingData = response.data.mapping_data;
                   
                   $('.qci-field-mapping-select').each(function() {
                       const $select = $(this);
                       const fieldName = $select.data('field');
                       
                       // Skip disabled fields
                       if ($select.prop('disabled')) {
                           return;
                       }
                       
                       // Apply mapping if exists
                       if (mappingData[fieldName]) {
                           $select.val(mappingData[fieldName]);
                       } else {
                           $select.val('');
                       }
                   });
                   
                   showNotification('โหลดเทมเพลตสำเร็จ', 'success');
               } else {
                   showNotification('ไม่สามารถโหลดเทมเพลต: ' + response.data, 'error');
               }
           },
           error: function() {
               showNotification(strings.server_error, 'error');
           }
       });
   }
   
   /**
    * Validate field mappings before import
    */
   function validateMapping(e) {
       e.preventDefault();
       
       // Get all mapped fields
       const mappedFields = {
           meta: {},
           course: {},
           quiz: {},
           question: {},
           answer: {}
       };
       
       $('.qci-field-mapping-select').each(function() {
           const $select = $(this);
           const value = $select.val();
           
           if (value) {
               // Determine entity from option group
               let entity = '';
               $select.find('option:selected').closest('optgroup').each(function() {
                   const label = $(this).attr('label');
                   if (label.includes('คอร์ส')) entity = 'course';
                   else if (label.includes('ควิซ')) entity = 'quiz';
                   else if (label.includes('คำถาม')) entity = 'question';
                   else if (label.includes('คำตอบ')) entity = 'answer';
                   else entity = 'meta';
               });
               
               if (entity) {
                   mappedFields[entity][value] = true;
               }
           }
       });
       
       // Check for record_type field
       const hasRecordType = $('.qci-field-mapping-select').filter(function() {
           return $(this).val() === 'record_type';
       }).length > 0;
       
       if (!hasRecordType) {
           showValidationError('ไม่พบการจับคู่สำหรับฟิลด์ "record_type" ซึ่งจำเป็นต้องมี');
           return;
       }
       
       // Check missing required fields
       let missingFields = [];
       
       // Check course required fields
       ['title', 'description'].forEach(field => {
           if (!mappedFields.course[field]) {
               missingFields.push(`คอร์ส: ${field}`);
           }
       });
       
       // Check quiz required fields
       ['title', 'course_reference'].forEach(field => {
           if (!mappedFields.quiz[field]) {
               missingFields.push(`ควิซ: ${field}`);
           }
       });
       
       // Check question required fields
       ['question_text', 'quiz_reference', 'type'].forEach(field => {
           if (!mappedFields.question[field]) {
               missingFields.push(`คำถาม: ${field}`);
           }
       });
       
       // Check answer required fields
       ['answer_text', 'question_reference', 'is_correct'].forEach(field => {
           if (!mappedFields.answer[field]) {
               missingFields.push(`คำตอบ: ${field}`);
           }
       });
       
       if (missingFields.length > 0) {
           showValidationError(strings.missing_fields + missingFields.join(', '));
           return;
       }
       
       // All validations passed
       showValidationSuccess('การจับคู่ฟิลด์ผ่านการตรวจสอบแล้ว คุณสามารถเริ่มนำเข้าข้อมูลได้');
       $('#qci-start-import').prop('disabled', false);
   }
   
   /**
    * Show validation error message
    */
   function showValidationError(message) {
       $('.qci-validation-messages').html(`
           <div class="qci-validation-error">
               <span class="dashicons dashicons-warning"></span>
               ${message}
           </div>
       `);
       
       // Scroll to message
       $('html, body').animate({
           scrollTop: $('.qci-validation-messages').offset().top - 100
       }, 300);
   }
   
   /**
    * Show validation success message
    */
   function showValidationSuccess(message) {
       $('.qci-validation-messages').html(`
           <div class="qci-validation-success">
               <span class="dashicons dashicons-yes-alt"></span>
               ${message}
           </div>
       `);
   }
   
   /**
    * Start the import process
    */
   function startImport(e) {
       e.preventDefault();
       
       // Confirm import
       if (!confirm(strings.confirm_import)) {
           return;
       }
       
       // Update UI to show we're on step 3
       QCI.currentStep = 3;
       updateStepIndicator();
       
       // Show import progress screen
       $('#qci-step-2').hide();
       
       // Create import progress HTML
       const progressHtml = `
           <div class="qci-panel">
               <div class="qci-panel-header">
                   <h2>${strings.importing}</h2>
               </div>
               <div class="qci-panel-body">
                   <div class="qci-progress-container">
                       <div class="qci-progress-status">
                           <span class="qci-progress-text">${strings.processing}</span>
                           <span class="qci-progress-percentage">0%</span>
                       </div>
                       <div class="qci-progress">
                           <div class="qci-progress-bar" style="width: 0%"></div>
                       </div>
                   </div>
                   
                   <div class="qci-import-log">
                       <h3>บันทึกการนำเข้าข้อมูล</h3>
                       <div class="qci-log-container">
                           <ul class="qci-log-entries"></ul>
                       </div>
                   </div>
                   
                   <div class="qci-actions">
                       <button type="button" class="button qci-cancel-import">
                           <span class="dashicons dashicons-no-alt"></span> ยกเลิกการนำเข้า
                       </button>
                   </div>
               </div>
           </div>
       `;
       
       $('#qci-step-3').html(progressHtml).show();
       
       // Collect mapping data
       const mappingData = {};
       $('.qci-field-mapping-select').each(function() {
           const $select = $(this);
           const field = $select.data('field');
           const value = $select.val();
           
           if (value) {
               mappingData[field] = value;
           }
       });
       
       // Prepare import data
       const importData = {
           action: 'qci_process_import',
           security: $('#qci_security').val(),
           file_id: $('#qci_file_id').val(),
           mapping: mappingData,
           single_sheet: true
       };
       
       // Start importing
       QCI.importing = true;
       processImport(importData);
       
       // Set up cancel button
       $('.qci-cancel-import').on('click', function() {
           if (confirm(strings.confirm_cancel)) {
               cancelImport();
           }
       });
   }
   
   /**
    * Process the import
    */
   function processImport(importData) {
       // Add log entry
       addLogEntry('เริ่มนำเข้าข้อมูล...');
       
       // First get import info
       $.ajax({
           url: ajaxurl,
           type: 'POST',
           data: {
               action: 'qci_prepare_import',
               security: importData.security,
               file_id: importData.file_id,
               mapping: importData.mapping,
               single_sheet: importData.single_sheet
           },
           success: function(response) {
               if (response.success) {
                   // Store total items for progress tracking
                   QCI.importTotal = response.data.total_items || 100;
                   QCI.importStages = response.data.stages || [];
                   
                   addLogEntry(`พบข้อมูลที่ต้องนำเข้าทั้งหมด ${QCI.importTotal} รายการ`);
                   
                   // Start processing stages
                   processImportStage(importData, 0);
                   
               } else {
                   importFailed(response.data || strings.server_error);
               }
           },
           error: function() {
               importFailed(strings.server_error);
           }
       });
   }
   
   /**
    * Process a specific import stage
    */
   function processImportStage(importData, stageIndex) {
       // Check if import was cancelled
       if (!QCI.importing) {
           return;
       }
       
       // Check if all stages are processed
       if (stageIndex >= QCI.importStages.length) {
           importComplete();
           return;
       }
       
       // Get current stage
       const stage = QCI.importStages[stageIndex];
       
       // Update UI with current stage
       $('.qci-progress-text').text(stage.message || getStageMessage(stage.key));
       addLogEntry(stage.message || getStageMessage(stage.key));
       
       // Process this stage
       $.ajax({
           url: ajaxurl,
           type: 'POST',
           data: {
               action: 'qci_process_import_stage',
               security: importData.security,
               file_id: importData.file_id,
               mapping: importData.mapping,
               stage: stage.key,
               stage_index: stageIndex,
               single_sheet: importData.single_sheet
           },
           success: function(response) {
               if (!QCI.importing) {
                   return; // Import was cancelled
               }
               
               if (response.success) {
                   // Update progress
                   QCI.importProcessed += response.data.items_processed || 0;
                   updateImportProgress();
                   
                   // Add log entries
                   if (response.data.log && response.data.log.length) {
                       response.data.log.forEach(logEntry => {
                           addLogEntry(logEntry);
                       });
                   }
                   
                   // Process next stage
                   processImportStage(importData, stageIndex + 1);
                   
               } else {
                   importFailed(response.data || strings.server_error);
               }
           },
           error: function() {
               importFailed(strings.server_error);
           }
       });
   }
   
   /**
    * Get stage message based on stage key
    */
   function getStageMessage(stageKey) {
       switch (stageKey) {
           case 'courses': return strings.processing_courses;
           case 'quizzes': return strings.processing_quizzes;
           case 'questions': return strings.processing_questions;
           case 'answers': return strings.processing_answers;
           case 'finalize': return strings.finalizing;
           default: return strings.processing;
       }
   }
   
   /**
    * Update import progress display
    */
   function updateImportProgress() {
       // Calculate percentage
       const progress = Math.min(100, Math.round((QCI.importProcessed / QCI.importTotal) * 100));
       
       // Update UI
       $('.qci-progress-bar').css('width', progress + '%');
       $('.qci-progress-percentage').text(progress + '%');
   }
   
   /**
    * Add entry to import log
    */
   function addLogEntry(message) {
       const timestamp = new Date().toLocaleTimeString();
       $('.qci-log-entries').append(`<li>[${timestamp}] ${message}</li>`);
       
       // Auto-scroll to bottom
       const logContainer = $('.qci-log-container');
       logContainer.scrollTop(logContainer[0].scrollHeight);
   }
   
   /**
    * Handle successful import completion
    */
   function importComplete() {
       // Update progress to 100%
       $('.qci-progress-bar').css('width', '100%');
       $('.qci-progress-percentage').text('100%');
       
       // Add final log entry
       addLogEntry('การนำเข้าข้อมูลเสร็จสมบูรณ์แล้ว');
       
       // Update UI to show we're on step 4
       QCI.currentStep = 4;
       updateStepIndicator();
       
       // Show completion screen
       $('#qci-step-3').hide();
       
       const completeHtml = `
           <div class="qci-panel">
               <div class="qci-panel-header">
                   <h2>${strings.import_complete}</h2>
               </div>
               <div class="qci-panel-body">
                   <div class="qci-complete-container">
                       <div class="qci-success-icon">
                           <span class="dashicons dashicons-yes-alt"></span>
                       </div>
                       
                       <div class="qci-import-summary">
                           <h3>${strings.imported}:</h3>
                           <ul class="qci-import-stats">
                               <li><strong>คอร์ส:</strong> <span class="qci-stat-courses">${QCI.importStats?.courses || 0}</span></li>
                               <li><strong>ควิซ:</strong> <span class="qci-stat-quizzes">${QCI.importStats?.quizzes || 0}</span></li>
                               <li><strong>คำถาม:</strong> <span class="qci-stat-questions">${QCI.importStats?.questions || 0}</span></li>
                               <li><strong>คำตอบ:</strong> <span class="qci-stat-answers">${QCI.importStats?.answers || 0}</span></li>
                           </ul>
                       </div>
                       
                       <div class="qci-complete-message">
                           <p>การนำเข้าข้อมูลเสร็จสมบูรณ์แล้ว คุณสามารถดูคอร์สและควิซที่นำเข้าได้ในระบบ</p>
                       </div>
                       
                       <div class="qci-actions">
                           <a href="${QCI.coursesUrl || '#'}" class="button button-secondary">
                               <span class="dashicons dashicons-welcome-learn-more"></span> ${strings.view_courses}
                           </a>
                           <a href="${window.location.href}" class="button button-primary">
                               <span class="dashicons dashicons-upload"></span> ${strings.new_import}
                           </a>
                       </div>
                   </div>
               </div>
           </div>
       `;
       
       $('#qci-step-4').html(completeHtml).show();
   }
   
   /**
    * Handle import failure
    */
   function importFailed(errorMessage) {
       // Update UI to show error
       $('.qci-progress-bar').css('width', '100%').addClass('error');
       $('.qci-progress-text').text(strings.import_failed);
       
       // Add error log entry
       addLogEntry(`การนำเข้าข้อมูลล้มเหลว: ${errorMessage}`);
       
       // Update actions
       $('.qci-actions').html(`
           <button type="button" class="button qci-back-button">
               <span class="dashicons dashicons-arrow-left-alt"></span> ${strings.go_back}
           </button>
       `);
       
       // Show error details
       $('.qci-import-log').after(`
           <div class="qci-error-details">
               <h3>รายละเอียดข้อผิดพลาด</h3>
               <div class="qci-error-message">
                   <span class="dashicons dashicons-warning"></span>
                   ${errorMessage}
               </div>
           </div>
       `);
       
       // Set importing to false
       QCI.importing = false;
   }
   
   /**
    * Cancel import process
    */
   function cancelImport() {
       // Set flag to stop processing
       QCI.importing = false;
       
       // Add log entry
       addLogEntry('การนำเข้าข้อมูลถูกยกเลิกโดยผู้ใช้');
       
       // Update UI
       $('.qci-progress-text').text('การนำเข้าข้อมูลถูกยกเลิก');
       $('.qci-progress-bar').addClass('cancelled');
       
       // Update actions
       $('.qci-actions').html(`
           <button type="button" class="button qci-back-button">
               <span class="dashicons dashicons-arrow-left-alt"></span> ${strings.go_back}
           </button>
       `);
       
       // Send cancel request to server
       $.ajax({
           url: ajaxurl,
           type: 'POST',
           data: {
               action: 'qci_cancel_import',
               security: $('#qci_security').val(),
               file_id: $('#qci_file_id').val()
           }
       });
   }
   
   /**
    * Go back to previous step
    */
   function goBack(e) {
       e.preventDefault();
       
       // If importing, confirm cancellation
       if (QCI.importing && !confirm(strings.confirm_cancel)) {
           return;
       }
       
       // Cancel import if in progress
       if (QCI.importing) {
           QCI.importing = false;
       }
       
       // Go back one step
       if (QCI.currentStep > 1) {
           QCI.currentStep--;
           updateStepIndicator();
           
           // Show appropriate content
           $('.qci-step-content').hide();
           $(`#qci-step-${QCI.currentStep}`).show();
       }
   }
   
   /**
    * Navigate to step (if possible)
    */
   function navigateStep(e) {
       e.preventDefault();
       
       const $step = $(this);
       const stepNumber = parseInt($step.data('step'));
       
       // Can only navigate to completed steps or current step
       if (stepNumber >= QCI.currentStep) {
           return;
       }
       
       // If importing, confirm cancellation
       if (QCI.importing && !confirm(strings.confirm_cancel)) {
           return;
       }
       
       // Cancel import if in progress
       if (QCI.importing) {
           QCI.importing = false;
       }
       
       // Update current step
       QCI.currentStep = stepNumber;
       updateStepIndicator();
       
       // Show appropriate content
       $('.qci-step-content').hide();
       $(`#qci-step-${QCI.currentStep}`).show();
   }
   
   /**
    * Update step indicator
    */
   function updateStepIndicator() {
       // Remove active and completed classes
      // Update step indicator
       $('.qci-steps-list li').removeClass('active completed');
       
       // Mark current step as active
       $(`.qci-steps-list li[data-step="${QCI.currentStep}"]`).addClass('active');
       
       // Mark previous steps as completed
       for (let i = 1; i < QCI.currentStep; i++) {
           $(`.qci-steps-list li[data-step="${i}"]`).addClass('completed');
       }
   }
   
   /**
    * Show error message
    */
   function showError(message) {
       // Check if error container exists
       let $errorContainer = $('.qci-error-message');
       
       // Create if not exists
       if (!$errorContainer.length) {
           $errorContainer = $('<div class="qci-error-message"></div>');
           $('.qci-container').prepend($errorContainer);
       }
       
       // Add error message
       $errorContainer.html(`
           <span class="dashicons dashicons-warning"></span>
           <span class="qci-error-text">${message}</span>
       `).show();
       
       // Scroll to error
       $('html, body').animate({
           scrollTop: $errorContainer.offset().top - 100
       }, 300);
   }
   
   /**
    * Hide error message
    */
   function hideError() {
       $('.qci-error-message').hide();
   }
   
   /**
    * Show notification message
    */
   function showNotification(message, type = 'info') {
       // Create notification element
       const $notification = $(`
           <div class="qci-notification qci-notification-${type}">
               <span class="qci-notification-icon dashicons ${type === 'success' ? 'dashicons-yes-alt' : type === 'error' ? 'dashicons-warning' : 'dashicons-info'}"></span>
               <span class="qci-notification-text">${message}</span>
               <button type="button" class="qci-notification-close">
                   <span class="dashicons dashicons-no-alt"></span>
               </button>
           </div>
       `);
       
       // Add to page
       let $container = $('.qci-notifications');
       if (!$container.length) {
           $container = $('<div class="qci-notifications"></div>');
           $('.qci-container').prepend($container);
       }
       
       $container.append($notification);
       
       // Setup dismiss button
       $notification.find('.qci-notification-close').on('click', function() {
           $notification.fadeOut(300, function() {
               $(this).remove();
           });
       });
       
       // Auto dismiss after 5 seconds
       setTimeout(function() {
           $notification.fadeOut(300, function() {
               $(this).remove();
           });
       }, 5000);
   }
   
})(jQuery);
      

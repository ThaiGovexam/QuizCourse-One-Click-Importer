/**
* QuizCourse Importer - Main JavaScript file
* Handles file uploads, field mapping, and the import process
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
       importProcessed: 0
   };
   
   // Localized strings from WordPress
   const strings = window.qci_strings || {
       no_file_selected: 'Please select a file to upload.',
       file_too_large: 'The file is too large.',
       invalid_file_type: 'Invalid file type. Please use CSV or Excel files.',
       validating: 'Validating...',
       continue_to_mapping: 'Continue to Field Mapping',
       uploading: 'Uploading...',
       mapping_error: 'Please map all required fields.',
       importing: 'Importing...',
       import_complete: 'Import complete!',
       import_failed: 'Import failed',
       confirm_cancel: 'Are you sure you want to cancel? All progress will be lost.',
       processing: 'Processing data...',
       courses: 'courses',
       sections: 'sections',
       quizzes: 'quizzes',
       questions: 'questions',
       answers: 'answers',
       imported: 'Successfully imported',
       server_error: 'Server error occurred. Please try again.',
       go_back: 'Go Back',
       view_courses: 'View Courses',
       new_import: 'New Import'
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
       $(document).on('click', '#qci-start-import', startImport);
       $(document).on('click', '.qci-back-button', goBack);
       $(document).on('click', '.qci-steps-list li', navigateStep);
       
       // Initialize drag and drop
       initDragDrop();
       
       // Initialize tabs for field mapping
       $(document).on('click', '.qci-tabs-nav a', function(e) {
           e.preventDefault();
           const target = $(this).attr('href');
           $('.qci-tabs-nav li').removeClass('active');
           $(this).parent().addClass('active');
           $('.qci-tab-content').hide();
           $(target).fadeIn();
       });
       
       // Field mapping auto-fill buttons
       $(document).on('click', '.qci-auto-map', autoMapFields);
       $(document).on('click', '.qci-clear-map', clearMapFields);
       
       // Tooltips
       if (typeof $.fn.tooltip === 'function') {
           $(document).on('mouseenter', '.qci-help-tip', function() {
               $(this).tooltip({
                   content: $(this).data('tip'),
                   position: { my: 'left top+5', at: 'left bottom', collision: 'flipfit' },
                   tooltipClass: 'qci-tooltip',
                   show: { duration: 100 },
                   hide: { duration: 100 }
               }).tooltip('open');
           });
       }
   }
   
   /**
    * Initialize drag and drop functionality
    */
   function initDragDrop() {
       const dropArea = $('.qci-file-upload');
       
       // Prevent default drag behaviors
       ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
           dropArea[0].addEventListener(eventName, preventDefaults, false);
       });
       
       // Highlight drop area when drag over
       ['dragenter', 'dragover'].forEach(eventName => {
           dropArea[0].addEventListener(eventName, highlight, false);
       });
       
       // Remove highlight when drag leave or drop
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
       
       // Update UI to show selected file
       QCI.fileName = file.name;
       $('#qci-file-name').text(file.name);
       $('.qci-selected-file').show();
       $('.qci-file-upload').addClass('has-file');
       
       // Enable the validate button
       $('#qci-validate-file').prop('disabled', false);
       
       // Hide any previous error messages
       hideError();
   }
   
   /**
    * Remove the selected file
    */
   function removeFile(e) {
       if (e) e.preventDefault();
       
       $('#qci_import_file').val('');
       $('.qci-selected-file').hide();
       $('.qci-file-upload').removeClass('has-file');
       QCI.fileName = '';
       QCI.fileId = '';
       QCI.uploadComplete = false;
       
       // Disable the validate button
       $('#qci-validate-file').prop('disabled', true);
   }
   
   /**
    * Validate the selected file
    */
   function validateFile(e) {
       e.preventDefault();
       
       // Check if a file is selected
       if ($('#qci_import_file')[0].files.length === 0) {
           showError(strings.no_file_selected);
           return;
       }
       
       // Create FormData object
       const formData = new FormData();
       formData.append('action', 'qci_validate_file');
       formData.append('security', $('#qci_security').val());
       formData.append('qci_import_file', $('#qci_import_file')[0].files[0]);
       
       // Update UI to show loading state
       $('#qci-validate-file').prop('disabled', true).text(strings.validating);
       $('.qci-actions').addClass('loading');
       
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
                   showMappingStep(response.data);
               } else {
                   showError(response.data || strings.server_error);
                   $('#qci-validate-file').prop('disabled', false).text(strings.continue_to_mapping);
               }
           },
           error: function(xhr, status, error) {
               showError(strings.server_error);
               $('#qci-validate-file').prop('disabled', false).text(strings.continue_to_mapping);
           },
           complete: function() {
               $('.qci-actions').removeClass('loading');
           }
       });
   }
   
   /**
    * Show the field mapping step
    */
   function showMappingStep(data) {
       // Update UI to show we're on step 2
       QCI.currentStep = 2;
       updateStepIndicator();
       
       // Insert the mapping HTML into the DOM
       $('#qci-step-2').html(data.mapping_html).show();
       $('#qci-step-1').hide();
       
       // Initialize the tabs if they exist
       if ($('.qci-tabs-nav li').length > 0) {
           $('.qci-tabs-nav li:first-child a').trigger('click');
       }
       
       // Add help tooltips
       $('.qci-field-mapping select').each(function() {
           const select = $(this);
           const helpTip = $('<span class="qci-help-tip dashicons dashicons-info" data-tip="Select the system field that corresponds to this file column"></span>');
           select.after(helpTip);
       });
   }
   
   /**
    * Start the import process
    */
   function startImport(e) {
       e.preventDefault();
       
       // Validate required mappings
       if (!validateMappings()) {
           showError(strings.mapping_error);
           return;
       }
       
       // Update UI to show we're on step 3
       QCI.currentStep = 3;
       updateStepIndicator();
       
       // Show import progress screen
       $('#qci-step-2').hide();
       $('#qci-step-3').html(`
           <h2>${strings.importing}</h2>
           <div class="qci-progress-container">
               <div class="qci-progress">
                   <div class="qci-progress-bar" style="width: 0%"></div>
               </div>
               <div class="qci-progress-text">0%</div>
           </div>
           <div class="qci-import-status">${strings.processing}</div>
           <div class="qci-import-details"></div>
       `).show();
       
       // Collect mapping data
       const importData = {
           action: 'qci_process_import',
           security: $('#qci_security').val(),
           file_id: QCI.fileId,
           mapping: {}
       };
       
       // Get mappings from the form
       $('.qci-field-mapping select').each(function() {
           const select = $(this);
           const sheet = select.data('sheet');
           const fileField = select.data('sheet-field');
           const systemField = select.val();
           
           if (systemField) {
               if (!importData.mapping[sheet]) {
                   importData.mapping[sheet] = {};
               }
               importData.mapping[sheet][fileField] = systemField;
           }
       });
       
       // Set importing state
       QCI.importing = true;
       
       // Start the import process
       runImport(importData);
   }
   
   /**
    * Run the import process
    */
   function runImport(importData) {
       $.ajax({
           url: ajaxurl,
           type: 'POST',
           data: importData,
           success: function(response) {
               QCI.importing = false;
               
               if (response.success) {
                   importComplete(response.data);
               } else {
                   importFailed(response.data || strings.server_error);
               }
           },
           error: function() {
               QCI.importing = false;
               importFailed(strings.server_error);
           }
       });
       
       // Start progress animation
       simulateProgress();
   }
   
   /**
    * Simulate progress for better UX
    */
   function simulateProgress() {
       let progress = 0;
       const interval = setInterval(function() {
           if (!QCI.importing || progress >= 90) {
               clearInterval(interval);
               return;
           }
           
           // Increment progress more quickly at first, then slow down
           if (progress < 30) {
               progress += 2;
           } else if (progress < 60) {
               progress += 1;
           } else {
               progress += 0.5;
           }
           
           // Update progress bar
           $('.qci-progress-bar').css('width', progress + '%');
           $('.qci-progress-text').text(Math.round(progress) + '%');
           
           // Update status text based on progress
           if (progress > 20 && progress < 40) {
               $('.qci-import-status').text('Processing courses...');
           } else if (progress > 40 && progress < 60) {
               $('.qci-import-status').text('Processing sections and quizzes...');
           } else if (progress > 60 && progress < 80) {
               $('.qci-import-status').text('Processing questions and answers...');
           } else if (progress > 80) {
               $('.qci-import-status').text('Finalizing import...');
           }
       }, 200);
   }
   
   /**
    * Handle successful import completion
    */
   function importComplete(data) {
       // Update progress to 100%
       $('.qci-progress-bar').css('width', '100%');
       $('.qci-progress-text').text('100%');
       
       // Update UI to show we're on step 4
       QCI.currentStep = 4;
       updateStepIndicator();
       
       // Show completion screen
       $('#qci-step-3').hide();
       $('#qci-step-4').html(`
           <div class="qci-complete">
               <div class="qci-success-icon">
                   <span class="dashicons dashicons-yes-alt"></span>
               </div>
               <h2>${strings.import_complete}</h2>
               <div class="qci-import-stats">
                   <p>${strings.imported}:</p>
                   <ul>
                       <li><strong>${data.stats.courses}</strong> ${strings.courses}</li>
                       <li><strong>${data.stats.sections}</strong> ${strings.sections}</li>
                       <li><strong>${data.stats.quizzes}</strong> ${strings.quizzes}</li>
                       <li><strong>${data.stats.questions}</strong> ${strings.questions}</li>
                       ${data.stats.answers ? `<li><strong>${data.stats.answers}</strong> ${strings.answers}</li>` : ''}
                   </ul>
               </div>
               <div class="qci-actions">
                   <a href="${QCI.coursesUrl || '#'}" class="button button-secondary">
                       <span class="dashicons dashicons-visibility"></span> ${strings.view_courses}
                   </a>
                   <a href="${window.location.href}" class="button button-primary">
                       <span class="dashicons dashicons-upload"></span> ${strings.new_import}
                   </a>
               </div>
           </div>
       `).show();
   }
   
   /**
    * Handle import failure
    */
   function importFailed(errorMessage) {
       // Update progress bar to show error
       $('.qci-progress-bar').css('width', '100%').addClass('error');
       $('.qci-progress-text').text('0%');
       
       // Show error message
       $('.qci-import-status').html(`
           <div class="qci-error">
               <span class="dashicons dashicons-warning"></span>
               <strong>${strings.import_failed}:</strong> ${errorMessage}
           </div>
           <div class="qci-actions">
               <button type="button" class="button qci-back-button">
                   <span class="dashicons dashicons-arrow-left-alt"></span> ${strings.go_back}
               </button>
           </div>
       `);
   }
   
   /**
    * Validate required field mappings
    */
   function validateMappings() {
       // Get all required fields
       const requiredFields = {
           Courses: ['title'],
           Sections: ['title', 'course_reference'],
           Quizzes: ['title', 'section_reference'],
           Questions: ['text', 'quiz_reference'],
           Answers: ['text', 'question_reference', 'is_correct']
       };
       
       // Check if Excel or CSV
       const isExcel = $('.qci-tabs').length > 0;
       
       // For Excel files, check each sheet separately
       if (isExcel) {
           let valid = true;
           
           // Check each sheet
           Object.keys(requiredFields).forEach(sheet => {
               // Get all mappings for this sheet
               const mappings = {};
               $(`.qci-field-mapping select[data-sheet="${sheet}"]`).each(function() {
                   mappings[$(this).val()] = true;
               });
               
               // Check required fields
               requiredFields[sheet].forEach(field => {
                   if (!mappings[field]) {
                       valid = false;
                       // Highlight the tab with missing mapping
                       $(`.qci-tabs-nav a[href="#qci-sheet-${sheet.toLowerCase()}"]`).parent().addClass('qci-error');
                   }
               });
           });
           
           return valid;
       } 
       // For CSV files, check all required fields are mapped somewhere
       else {
           const mappings = {};
           $('.qci-field-mapping select').each(function() {
               if ($(this).val()) {
                   const parts = $(this).val().split('|');
                   if (parts.length === 2) {
                       const entity = parts[0];
                       const field = parts[1];
                       
                       if (!mappings[entity]) {
                           mappings[entity] = {};
                       }
                       
                       mappings[entity][field] = true;
                   }
               }
           });
           
           // Check that each entity has all required fields
           let valid = true;
           
           Object.keys(requiredFields).forEach(entity => {
               if (!mappings[entity]) {
                   valid = false;
                   return;
               }
               
               requiredFields[entity].forEach(field => {
                   if (!mappings[entity][field]) {
                       valid = false;
                   }
               });
           });
           
           return valid;
       }
   }
   
   /**
    * Auto-map fields based on field names
    */
   function autoMapFields(e) {
       e.preventDefault();
       
       const sheet = $(this).data('sheet');
       
       // Get mappable fields for this sheet
       const mappings = {
           'title': ['title', 'name', 'heading'],
           'description': ['description', 'desc', 'content', 'summary'],
           'course_reference': ['course', 'courseid', 'course_id', 'course_reference'],
           'section_reference': ['section', 'sectionid', 'section_id', 'section_reference'],
           'quiz_reference': ['quiz', 'quizid', 'quiz_id', 'quiz_reference'],
           'question_reference': ['question', 'questionid', 'question_id', 'question_reference'],
           'text': ['text', 'question', 'content', 'body', 'answer'],
           'is_correct': ['correct', 'is_correct', 'iscorrect', 'right', 'rightanswer'],
           'type': ['type', 'questiontype', 'question_type', 'format'],
           'ordering': ['order', 'ordering', 'sequence', 'position'],
           'featured_image': ['image', 'featured_image', 'featuredimage', 'thumbnail'],
           'status': ['status', 'state', 'published']
       };
       
       // Loop through all select fields for this sheet
       $(`.qci-field-mapping select[data-sheet="${sheet}"]`).each(function() {
           const select = $(this);
           const fileField = select.data('sheet-field').toLowerCase();
           
           // Try to auto-map this field
           let mapped = false;
           
           // Check exact match first
           const options = select.find('option');
           for (let i = 0; i < options.length; i++) {
               const option = $(options[i]);
               const value = option.val();
               
               if (value && value === fileField) {
                   select.val(value);
                   mapped = true;
                   break;
               }
           }
           
           // If no exact match, try pattern matching
           if (!mapped) {
               for (const [systemField, patterns] of Object.entries(mappings)) {
                   for (const pattern of patterns) {
                       if (fileField.includes(pattern)) {
                           // Find the option with this system field
                           const matchingOption = select.find(`option[value="${systemField}"]`);
                           if (matchingOption.length) {
                               select.val(systemField);
                               mapped = true;
                               break;
                           }
                       }
                   }
                   if (mapped) break;
               }
           }
       });
   }
   
   /**
    * Clear all field mappings for a sheet
    */
   function clearMapFields(e) {
       e.preventDefault();
       
       const sheet = $(this).data('sheet');
       $(`.qci-field-mapping select[data-sheet="${sheet}"]`).val('');
   }
   
   /**
    * Navigate back to previous step
    */
   function goBack(e) {
       e.preventDefault();
       
       // Check if we're importing
       if (QCI.importing) {
           if (!confirm(strings.confirm_cancel)) {
               return;
           }
           QCI.importing = false;
       }
       
       // Go back one step
       if (QCI.currentStep > 1) {
           QCI.currentStep--;
           updateStepIndicator();
           
           // Show the appropriate step
           $('.qci-step-content').hide();
           $(`#qci-step-${QCI.currentStep}`).show();
       }
   }
   
   /**
    * Navigate to a specific step
    */
   function navigateStep(e) {
       e.preventDefault();
       
       const targetStep = parseInt($(this).data('step'));
       
       // Can only navigate to previous steps or the current step
       if (targetStep >= QCI.currentStep) {
           return;
       }
       
       // Check if we're importing
       if (QCI.importing) {
           if (!confirm(strings.confirm_cancel)) {
               return;
           }
           QCI.importing = false;
       }
       
       // Update current step
       QCI.currentStep = targetStep;
       updateStepIndicator();
       
       // Show the appropriate step
       $('.qci-step-content').hide();
       $(`#qci-step-${QCI.currentStep}`).show();
   }
   
   /**
    * Update the step indicator
    */
   function updateStepIndicator() {
       $('.qci-steps-list li').removeClass('active completed');
       
       // Mark current step as active
       $(`.qci-steps-list li[data-step="${QCI.currentStep}"]`).addClass('active');
       
       // Mark completed steps
       for (let i = 1; i < QCI.currentStep; i++) {
           $(`.qci-steps-list li[data-step="${i}"]`).addClass('completed');
       }
   }
   
   /**
    * Show error message
    */
   function showError(message) {
       // Check if error container exists, if not create it
       let errorContainer = $('.qci-error-message');
       if (errorContainer.length === 0) {
           errorContainer = $('<div class="qci-error-message"></div>');
           $('.qci-container').prepend(errorContainer);
       }
       
       // Add error message
       errorContainer.html(`<p><span class="dashicons dashicons-warning"></span> ${message}</p>`).show();
       
       // Scroll to error
       $('html, body').animate({
           scrollTop: errorContainer.offset().top - 100
       }, 500);
   }
   
   /**
    * Hide error message
    */
   function hideError() {
       $('.qci-error-message').hide();
   }
   
})(jQuery);

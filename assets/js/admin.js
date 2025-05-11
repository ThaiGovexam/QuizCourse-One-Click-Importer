/**
 * QuizCourse Importer - Admin JavaScript
 * 
 * Handles all admin UI interactions for the QuizCourse Importer plugin.
 */
(function($) {
    'use strict';

    // Main Admin Object
    const QCIAdmin = {
        /**
         * Initialize the admin interface
         */
        init: function() {
            this.initTabs();
            this.initFileUpload();
            this.initFieldMapping();
            this.initHelpTabs();
            this.initSettings();
            this.initTooltips();
        },

        /**
         * Initialize tabbed interfaces
         */
        initTabs: function() {
            $('.qci-tabs').each(function() {
                const $tabs = $(this);
                const $navItems = $tabs.find('.qci-tabs-nav li');
                const $contentItems = $tabs.find('.qci-tab-content');

                // Set first tab as active by default
                if (!$navItems.filter('.active').length) {
                    $navItems.first().addClass('active');
                    $contentItems.hide().first().show();
                }

                // Handle tab clicks
                $navItems.on('click', 'a', function(e) {
                    e.preventDefault();
                    
                    const targetId = $(this).attr('href');
                    
                    // Update tab navigation
                    $navItems.removeClass('active');
                    $(this).parent().addClass('active');
                    
                    // Show target content
                    $contentItems.hide();
                    $(targetId).show();
                });
            });
        },

        /**
         * Initialize file upload interactions
         */
        initFileUpload: function() {
            const $fileUpload = $('.qci-file-upload');
            
            if (!$fileUpload.length) return;
            
            // File input change handler
            $('#qci_import_file').on('change', function() {
                const fileName = $(this).val().split('\\').pop();
                if (fileName) {
                    $('#qci-file-name').text(fileName);
                    $('.qci-selected-file').show();
                    $fileUpload.addClass('has-file');
                } else {
                    QCIAdmin.removeFile();
                }
            });
            
            // Remove file button
            $('#qci-remove-file').on('click', function(e) {
                e.preventDefault();
                QCIAdmin.removeFile();
            });
            
            // Drag and drop functionality
            const dropArea = $fileUpload[0];
            if (dropArea) {
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                    dropArea.addEventListener(eventName, QCIAdmin.preventDefaults, false);
                });
                
                ['dragenter', 'dragover'].forEach(eventName => {
                    dropArea.addEventListener(eventName, function() {
                        $fileUpload.addClass('highlight');
                    }, false);
                });
                
                ['dragleave', 'drop'].forEach(eventName => {
                    dropArea.addEventListener(eventName, function() {
                        $fileUpload.removeClass('highlight');
                    }, false);
                });
                
                dropArea.addEventListener('drop', function(e) {
                    const dt = e.dataTransfer;
                    const files = dt.files;
                    
                    if (files.length) {
                        document.getElementById('qci_import_file').files = files;
                        $('#qci_import_file').trigger('change');
                    }
                }, false);
            }
        },

        /**
         * Reset file upload form
         */
        removeFile: function() {
            $('#qci_import_file').val('');
            $('.qci-selected-file').hide();
            $('.qci-file-upload').removeClass('has-file');
        },

        /**
         * Prevent default behavior for events
         */
        preventDefaults: function(e) {
            e.preventDefault();
            e.stopPropagation();
        },

        /**
         * Initialize field mapping functionality
         */
        initFieldMapping: function() {
            // Auto-mapping button
            $('#qci-auto-map').on('click', function(e) {
                e.preventDefault();
                
                $('.qci-field-mapping').each(function() {
                    const $select = $(this);
                    const sheetField = $select.data('sheet-field').toLowerCase();
                    
                    // Try to find a matching option
                    let matched = false;
                    
                    $select.find('option').each(function() {
                        const optionText = $(this).text().toLowerCase();
                        const optionValue = $(this).val();
                        
                        if (optionValue && 
                            (optionText.includes(sheetField) || 
                             sheetField.includes(optionText) ||
                             QCIAdmin.similarText(sheetField, optionText) > 0.7)) {
                            $select.val(optionValue);
                            matched = true;
                            return false; // Break the loop
                        }
                    });
                });
                
                // Show notification
                QCIAdmin.showNotification(qci_strings.auto_map_complete, 'success');
            });
            
            // Reset mapping button
            $('#qci-reset-mapping').on('click', function(e) {
                e.preventDefault();
                
                $('.qci-field-mapping').val('');
                
                // Show notification
                QCIAdmin.showNotification(qci_strings.reset_complete, 'info');
            });
            
            // Save mapping template button
            $('#qci-save-mapping').on('click', function(e) {
                e.preventDefault();
                
                const mappingData = {};
                
                // Collect mapping data
                $('.qci-field-mapping').each(function() {
                    const sheet = $(this).data('sheet');
                    const field = $(this).data('sheet-field');
                    const value = $(this).val();
                    
                    if (value) {
                        if (!mappingData[sheet]) {
                            mappingData[sheet] = {};
                        }
                        
                        mappingData[sheet][field] = value;
                    }
                });
                
                // Prompt for template name
                const templateName = prompt(qci_strings.enter_template_name, '');
                
                if (templateName) {
                    // Save template via AJAX
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
                                QCIAdmin.showNotification(response.data.message, 'success');
                                
                                // Add the new template to the dropdown
                                $('#qci-load-mapping-select').append(
                                    $('<option></option>')
                                        .attr('value', response.data.template_id)
                                        .text(templateName)
                                );
                            } else {
                                QCIAdmin.showNotification(response.data, 'error');
                            }
                        },
                        error: function() {
                            QCIAdmin.showNotification(qci_strings.server_error, 'error');
                        }
                    });
                }
            });
            
            // Load mapping template
            $('#qci-load-mapping-btn').on('click', function(e) {
                e.preventDefault();
                
                const templateId = $('#qci-load-mapping-select').val();
                
                if (templateId) {
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
                                // Apply the mapping data
                                const mappingData = response.data.mapping_data;
                                
                                // Reset all dropdowns first
                                $('.qci-field-mapping').val('');
                                
                                // Set values from template
                                for (const sheet in mappingData) {
                                    for (const field in mappingData[sheet]) {
                                        const value = mappingData[sheet][field];
                                        $(`.qci-field-mapping[data-sheet="${sheet}"][data-sheet-field="${field}"]`).val(value);
                                    }
                                }
                                
                                QCIAdmin.showNotification(qci_strings.template_loaded, 'success');
                            } else {
                                QCIAdmin.showNotification(response.data, 'error');
                            }
                        },
                        error: function() {
                            QCIAdmin.showNotification(qci_strings.server_error, 'error');
                        }
                    });
                }
            });
            
            // Delete mapping template
            $('#qci-delete-mapping-btn').on('click', function(e) {
                e.preventDefault();
                
                const templateId = $('#qci-load-mapping-select').val();
                const templateName = $('#qci-load-mapping-select option:selected').text();
                
                if (templateId && confirm(qci_strings.confirm_delete_template.replace('%s', templateName))) {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'qci_delete_mapping_template',
                            security: $('#qci_security').val(),
                            template_id: templateId
                        },
                        success: function(response) {
                            if (response.success) {
                                // Remove the template from dropdown
                                $('#qci-load-mapping-select option[value="' + templateId + '"]').remove();
                                
                                QCIAdmin.showNotification(response.data, 'success');
                            } else {
                                QCIAdmin.showNotification(response.data, 'error');
                            }
                        },
                        error: function() {
                            QCIAdmin.showNotification(qci_strings.server_error, 'error');
                        }
                    });
                }
            });
        },

        /**
         * Initialize help tabs
         */
        initHelpTabs: function() {
            // Toggle help content
            $('.qci-help-toggle').on('click', function(e) {
                e.preventDefault();
                $(this).next('.qci-help-content').slideToggle();
                $(this).toggleClass('open');
            });
            
            // Show help modal
            $('.qci-show-help').on('click', function(e) {
                e.preventDefault();
                
                const helpType = $(this).data('help');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'qci_get_help_content',
                        security: $('#qci_security').val(),
                        help_type: helpType
                    },
                    success: function(response) {
                        if (response.success) {
                            QCIAdmin.showModal(response.data.title, response.data.content);
                        }
                    }
                });
            });
        },

        /**
         * Initialize settings page functionality
         */
        initSettings: function() {
            if (!$('.qci-settings-page').length) return;
            
            // Toggle advanced settings
            $('#qci-toggle-advanced').on('click', function(e) {
                e.preventDefault();
                $('.qci-advanced-settings').toggle();
                
                const $button = $(this);
                if ($('.qci-advanced-settings').is(':visible')) {
                    $button.text(qci_strings.hide_advanced);
                } else {
                    $button.text(qci_strings.show_advanced);
                }
            });
            
            // Reset settings confirmation
            $('#qci-reset-settings').on('click', function(e) {
                if (!confirm(qci_strings.confirm_reset_settings)) {
                    e.preventDefault();
                }
            });
            
            // Testing database connection
            $('#qci-test-connection').on('click', function(e) {
                e.preventDefault();
                
                const $button = $(this);
                const $result = $('#qci-connection-test-result');
                
                $button.prop('disabled', true).addClass('loading');
                $result.html('').removeClass('success error');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'qci_test_db_connection',
                        security: $('#qci_security').val(),
                        db_host: $('#qci_db_host').val(),
                        db_name: $('#qci_db_name').val(),
                        db_user: $('#qci_db_user').val(),
                        db_password: $('#qci_db_password').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            $result.html(response.data).addClass('success');
                        } else {
                            $result.html(response.data).addClass('error');
                        }
                    },
                    error: function() {
                        $result.html(qci_strings.server_error).addClass('error');
                    },
                    complete: function() {
                        $button.prop('disabled', false).removeClass('loading');
                    }
                });
            });
        },

        /**
         * Initialize tooltips
         */
        initTooltips: function() {
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
        },

        /**
         * Show notification
         */
        showNotification: function(message, type = 'info') {
            const $notification = $('<div class="qci-notification ' + type + '">' +
                '<p>' + message + '</p>' +
                '<button type="button" class="qci-close-notification">&times;</button>' +
                '</div>');
            
            // Add to notification area
            $('.qci-notifications').append($notification);
            
            // Auto dismiss after 5 seconds
            setTimeout(function() {
                $notification.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
            
            // Close button
            $notification.find('.qci-close-notification').on('click', function() {
                $notification.fadeOut(300, function() {
                    $(this).remove();
                });
            });
        },

        /**
         * Show modal dialog
         */
        showModal: function(title, content) {
            // Create modal if it doesn't exist
            if (!$('#qci-modal').length) {
                const $modal = $('<div id="qci-modal" class="qci-modal">' +
                    '<div class="qci-modal-content">' +
                    '<div class="qci-modal-header">' +
                    '<span class="qci-modal-close">&times;</span>' +
                    '<h2 class="qci-modal-title"></h2>' +
                    '</div>' +
                    '<div class="qci-modal-body"></div>' +
                    '</div>' +
                    '</div>');
                
                $('body').append($modal);
                
                // Close modal when clicking on close button or outside the modal
                $('.qci-modal-close').on('click', function() {
                    $('#qci-modal').hide();
                });
                
                $(window).on('click', function(e) {
                    if ($(e.target).is('#qci-modal')) {
                        $('#qci-modal').hide();
                    }
                });
            }
            
            // Set content and show modal
            $('.qci-modal-title').text(title);
            $('.qci-modal-body').html(content);
            $('#qci-modal').show();
        },

        /**
         * Calculate similarity between two strings (for auto-mapping)
         * Implementation of Levenshtein distance ratio
         */
        similarText: function(first, second) {
            if (first === second) return 1.0;
            
            const len1 = first.length;
            const len2 = second.length;
            
            if (len1 === 0 || len2 === 0) return 0.0;
            
            // Calculate Levenshtein distance
            const distance = QCIAdmin.levenshtein(first, second);
            
            // Calculate ratio: 1 - (distance / max length)
            return 1 - (distance / Math.max(len1, len2));
        },

        /**
         * Calculate Levenshtein distance between two strings
         */
        levenshtein: function(first, second) {
            const a = first.toLowerCase();
            const b = second.toLowerCase();
            
            const matrix = [];
            
            // Initialize matrix
            for (let i = 0; i <= a.length; i++) {
                matrix[i] = [i];
            }
            
            for (let j = 0; j <= b.length; j++) {
                matrix[0][j] = j;
            }
            
            // Fill matrix
            for (let i = 1; i <= a.length; i++) {
                for (let j = 1; j <= b.length; j++) {
                    const cost = a.charAt(i - 1) === b.charAt(j - 1) ? 0 : 1;
                    matrix[i][j] = Math.min(
                        matrix[i - 1][j] + 1,        // deletion
                        matrix[i][j - 1] + 1,        // insertion
                        matrix[i - 1][j - 1] + cost  // substitution
                    );
                }
            }
            
            return matrix[a.length][b.length];
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        QCIAdmin.init();
        
        // Initialize import process
        if ($('#qci-upload-form').length) {
            $('#qci-upload-form').on('submit', function(e) {
                e.preventDefault();
                
                if (!$('#qci_import_file').val()) {
                    QCIAdmin.showNotification(qci_strings.no_file_selected, 'error');
                    return;
                }
                
                const formData = new FormData(this);
                formData.append('action', 'qci_validate_file');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    beforeSend: function() {
                        $('.qci-actions').addClass('loading');
                        $('#qci-validate-file').prop('disabled', true).text(qci_strings.validating);
                    },
                    success: function(response) {
                        if (response.success) {
                            // Update steps UI
                            $('.qci-steps-list li').removeClass('active');
                            $('.qci-steps-list li[data-step="2"]').addClass('active');
                            
                            // Show mapping step
                            $('#qci-step-2').html(response.data.mapping_html).show();
                            $('#qci-step-1').hide();
                            
                            // Initialize mapping functionality
                            QCIAdmin.initFieldMapping();
                            QCIAdmin.initTabs();
                            QCIAdmin.initTooltips();
                        } else {
                            QCIAdmin.showNotification(response.data || qci_strings.validation_error, 'error');
                        }
                    },
                    error: function() {
                        QCIAdmin.showNotification(qci_strings.server_error, 'error');
                    },
                    complete: function() {
                        $('.qci-actions').removeClass('loading');
                        $('#qci-validate-file').prop('disabled', false).text(qci_strings.continue_to_mapping);
                    }
                });
            });
        }
        
        // Import history table - Show details
        $('.qci-show-import-details').on('click', function(e) {
            e.preventDefault();
            
            const importId = $(this).data('import-id');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'qci_get_import_details',
                    security: $('#qci_security').val(),
                    import_id: importId
                },
                success: function(response) {
                    if (response.success) {
                        QCIAdmin.showModal(qci_strings.import_details, response.data);
                    } else {
                        QCIAdmin.showNotification(response.data, 'error');
                    }
                },
                error: function() {
                    QCIAdmin.showNotification(qci_strings.server_error, 'error');
                }
            });
        });
        
        // Handle start import button
        $(document).on('click', '#qci-start-import', function(e) {
            e.preventDefault();
            
            // Update steps UI
            $('.qci-steps-list li').removeClass('active');
            $('.qci-steps-list li[data-step="3"]').addClass('active');
            
            // Show import progress screen
            $('#qci-step-2').hide();
            $('#qci-step-3').html(
                '<h2>' + qci_strings.importing + '</h2>' +
                '<div class="qci-progress-container">' +
                '<div class="qci-progress">' +
                '<div class="qci-progress-bar"></div>' +
                '</div>' +
                '<div class="qci-progress-percentage">0%</div>' +
                '</div>' +
                '<div class="qci-import-status">' + qci_strings.preparing_import + '</div>' +
                '<div class="qci-import-log"></div>'
            ).show();
            
            // Collect mapping data
            const importData = {
                action: 'qci_process_import',
                security: $('#qci_security').val(),
                file_id: $('#qci_file_id').val(),
                mapping: {}
            };
            
            // Add mapping values
            $('.qci-field-mapping').each(function() {
                const sheet = $(this).data('sheet');
                const field = $(this).data('sheet-field');
                const value = $(this).val();
                
                if (value) {
                    if (!importData.mapping[sheet]) {
                        importData.mapping[sheet] = {};
                    }
                    
                    importData.mapping[sheet][field] = value;
                }
            });
            
            // Add import options
            importData.options = {
                update_existing: $('#qci_update_existing').is(':checked'),
                skip_validation: $('#qci_skip_validation').is(':checked'),
                import_featured_images: $('#qci_import_images').is(':checked')
            };
            
            // Start the import process
            QCIAdmin.startImport(importData);
        });
        
        // Handle back button clicks
        $(document).on('click', '.qci-back-button', function(e) {
            e.preventDefault();
            
            const currentStep = $('.qci-steps-list li.active').data('step');
            const prevStep = currentStep - 1;
            
            if (prevStep > 0) {
                // Update steps UI
                $('.qci-steps-list li').removeClass('active');
                $('.qci-steps-list li[data-step="' + prevStep + '"]').addClass('active');
                
                // Show previous step
                $('.qci-step-content').hide();
                $('#qci-step-' + prevStep).show();
            } else {
                // Go to first step
                window.location.reload();
            }
        });
    });
    
    // Import functionality
    $.extend(QCIAdmin, {
        /**
         * Start the import process
         */
        startImport: function(importData) {
            // Track progress
            QCIAdmin.importProgress = 0;
            QCIAdmin.importTotal = 100; // Will be updated from server
            QCIAdmin.importAborted = false;
            
            // First get the total count and setup stages
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'qci_prepare_import',
                    security: importData.security,
                    file_id: importData.file_id,
                    mapping: importData.mapping
                },
                success: function(response) {
                    if (response.success) {
                        QCIAdmin.importTotal = response.data.total_items;
                        QCIAdmin.importStages = response.data.stages;
                        QCIAdmin.updateImportLog(qci_strings.import_prepared + ': ' + QCIAdmin.importTotal + ' ' + qci_strings.items_to_import);
                        
                        // Start the actual import process
                        QCIAdmin.processImportStage(importData, 0);
                    } else {
                        QCIAdmin.importFailed(response.data || qci_strings.preparation_failed);
                    }
                },
                error: function() {
                    QCIAdmin.importFailed(qci_strings.server_error);
                }
            });
            
            // Add cancel button
            $('#qci-step-3').append(
                '<div class="qci-actions">' +
                '<button type="button" id="qci-cancel-import" class="button button-secondary">' + 
                qci_strings.cancel_import + '</button>' +
                '</div>'
            );
            
            // Handle cancel button
            $('#qci-cancel-import').on('click', function() {
                if (confirm(qci_strings.confirm_cancel_import)) {
                    QCIAdmin.importAborted = true;
                    $(this).prop('disabled', true).text(qci_strings.cancelling);
                    QCIAdmin.updateImportStatus(qci_strings.cancelling_import);
                }
            });
        },
        
        /**
         * Process a single import stage
         */
        processImportStage: function(importData, stageIndex) {
            if (QCIAdmin.importAborted) {
                QCIAdmin.importCancelled();
                return;
            }
            
            if (stageIndex >= QCIAdmin.importStages.length) {
                QCIAdmin.importComplete();
                return;
            }
            
            const stage = QCIAdmin.importStages[stageIndex];
            
            QCIAdmin.updateImportStatus(stage.message);
            QCIAdmin.updateImportLog(stage.message);
            
            // Add stage data to the import request
            const stageData = {
                action: 'qci_process_import_stage',
                security: importData.security,
                file_id: importData.file_id,
                mapping: importData.mapping,
                options: importData.options,
                stage: stage.key,
                stage_index: stageIndex
            };
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: stageData,
                success: function(response) {
                    if (QCIAdmin.importAborted) {
                        QCIAdmin.importCancelled();
                        return;
                    }
                    
                    if (response.success) {
                        // Update progress
                        QCIAdmin.importProgress += response.data.items_processed;
                        QCIAdmin.updateImportProgress();
                        
                        // Add log entries
                        if (response.data.log) {
                            response.data.log.forEach(function(entry) {
                                QCIAdmin.updateImportLog(entry);
                            });
                        }
                        
                        // Process next stage
                        QCIAdmin.processImportStage(importData, stageIndex + 1);
                    } else {
                        QCIAdmin.importFailed(response.data || qci_strings.import_failed);
                    }
                },
                error: function() {
                    QCIAdmin.importFailed(qci_strings.server_error);
                }
            });
        },
        
        /**
         * Update import progress bar and percentage
         */
        updateImportProgress: function() {
            const percent = Math.min(Math.round((QCIAdmin.importProgress / QCIAdmin.importTotal) * 100), 100);
            $('.qci-progress-bar').css('width', percent + '%');
            $('.qci-progress-percentage').text(percent + '%');
        },
        
        /**
         * Update import status message
         */
        updateImportStatus: function(message) {
            $('.qci-import-status').text(message);
        },
        
        /**
         * Add a message to the import log
         */
        updateImportLog: function(message) {
            const timestamp = new Date().toLocaleTimeString();
            $('.qci-import-log').append('<div class="qci-log-entry">[' + timestamp + '] ' + message + '</div>');
            
            // Auto-scroll to bottom
            const $log = $('.qci-import-log');
            $log.scrollTop($log[0].scrollHeight);
        },
        
        /**
         * Handle import completion
         */
        importComplete: function() {
            // Update steps UI
            $('.qci-steps-list li').removeClass('active');
            $('.qci-steps-list li[data-step="4"]').addClass('active');
            
            // Show completion screen
            $('#qci-step-3').hide();
            $('#qci-step-4').html(
                '<div class="qci-complete">' +
                '<span class="dashicons dashicons-yes-alt"></span>' +
                '<h2>' + qci_strings.import_complete + '</h2>' +
                '<p>' + qci_strings.items_imported.replace('%d', QCIAdmin.importProgress) + '</p>' +
                '<div class="qci-actions">' +
                '<a href="' + qci_strings.courses_url + '" class="button button-secondary">' + 
                qci_strings.view_courses + '</a>' +
                '<a href="' + qci_strings.import_url + '" class="button button-primary">' +
                qci_strings.new_import + '</a>' +
                '</div>' +
                '</div>'
            ).show();
        },
        
        /**
         * Handle import failure
         */
        importFailed: function(errorMessage) {
            $('.qci-progress-bar').css('width', '0%').addClass('error');
            $('.qci-import-status').html(
                '<div class="qci-error">' +
                '<span class="dashicons dashicons-warning"></span> ' +
              '<strong>' + qci_strings.import_failed + ':</strong> ' +
               errorMessage +
               '</div>'
           );
           
           // Change the cancel button to a back button
           $('#qci-cancel-import')
               .prop('disabled', false)
               .text(qci_strings.go_back)
               .attr('id', 'qci-back-from-error')
               .addClass('qci-back-button');
           
           QCIAdmin.updateImportLog(qci_strings.import_failed + ': ' + errorMessage);
       },
       
       /**
        * Handle import cancellation
        */
       importCancelled: function() {
           $.ajax({
               url: ajaxurl,
               type: 'POST',
               data: {
                   action: 'qci_cancel_import',
                   security: $('#qci_security').val(),
                   file_id: $('#qci_file_id').val()
               },
               complete: function() {
                   $('.qci-progress-bar').addClass('cancelled');
                   $('.qci-import-status').html(
                       '<div class="qci-warning">' +
                       '<span class="dashicons dashicons-no"></span> ' +
                       '<strong>' + qci_strings.import_cancelled + '</strong>' +
                       '</div>'
                   );
                   
                   QCIAdmin.updateImportLog(qci_strings.import_cancelled_log);
                   
                   // Change the cancel button to a back button
                   $('#qci-cancel-import')
                       .prop('disabled', false)
                       .text(qci_strings.go_back)
                       .attr('id', 'qci-back-from-cancel')
                       .addClass('qci-back-button');
               }
           });
       }
   });

})(jQuery);

/**
* Include translation strings in PHP:
*
* wp_localize_script('qci-admin-js', 'qci_strings', array(
*    'validating' => __('Validating...', 'quizcourse-importer'),
*    'continue_to_mapping' => __('Continue to Field Mapping', 'quizcourse-importer'),
*    'no_file_selected' => __('Please select a file to upload.', 'quizcourse-importer'),
*    'validation_error' => __('File validation failed.', 'quizcourse-importer'),
*    'server_error' => __('Server error. Please try again.', 'quizcourse-importer'),
*    'importing' => __('Importing Data...', 'quizcourse-importer'),
*    'preparing_import' => __('Preparing to import...', 'quizcourse-importer'),
*    'import_prepared' => __('Import prepared', 'quizcourse-importer'),
*    'items_to_import' => __('items to import', 'quizcourse-importer'),
*    'preparation_failed' => __('Failed to prepare import.', 'quizcourse-importer'),
*    'import_complete' => __('Import Complete!', 'quizcourse-importer'),
*    'import_failed' => __('Import Failed', 'quizcourse-importer'),
*    'cancel_import' => __('Cancel Import', 'quizcourse-importer'),
*    'cancelling' => __('Cancelling...', 'quizcourse-importer'),
*    'cancelling_import' => __('Cancelling import process...', 'quizcourse-importer'),
*    'import_cancelled' => __('Import Cancelled', 'quizcourse-importer'),
*    'import_cancelled_log' => __('Import process was cancelled by user.', 'quizcourse-importer'),
*    'go_back' => __('Go Back', 'quizcourse-importer'),
*    'items_imported' => __('%d items were successfully imported.', 'quizcourse-importer'),
*    'view_courses' => __('View Courses', 'quizcourse-importer'),
*    'new_import' => __('New Import', 'quizcourse-importer'),
*    'courses_url' => admin_url('edit.php?post_type=course'),
*    'import_url' => admin_url('admin.php?page=quizcourse-importer'),
*    'auto_map_complete' => __('Auto-mapping completed.', 'quizcourse-importer'),
*    'reset_complete' => __('Field mapping reset.', 'quizcourse-importer'),
*    'enter_template_name' => __('Enter a name for this mapping template:', 'quizcourse-importer'),
*    'template_loaded' => __('Mapping template loaded successfully.', 'quizcourse-importer'),
*    'confirm_delete_template' => __('Are you sure you want to delete the template "%s"?', 'quizcourse-importer'),
*    'import_details' => __('Import Details', 'quizcourse-importer'),
*    'confirm_reset_settings' => __('Are you sure you want to reset all settings to default values?', 'quizcourse-importer'),
*    'hide_advanced' => __('Hide Advanced Settings', 'quizcourse-importer'),
*    'show_advanced' => __('Show Advanced Settings', 'quizcourse-importer'),
*    'confirm_cancel_import' => __('Are you sure you want to cancel the import? All progress will be lost.', 'quizcourse-importer')
* ));
*/

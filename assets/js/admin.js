/**
 * Crelate Job Board - Admin JavaScript
 */

(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        CrelateJobBoardAdmin.init();
        CrelateFieldsBuilder.init();
    });

    // Main admin plugin object
    var CrelateJobBoardAdmin = {
        
        // Initialize the admin plugin
        init: function() {
            this.bindEvents();
            this.initSettingsPage();
            this.initImportPage();
        },

        // Bind event handlers
        bindEvents: function() {
            // Test connection button
            $(document).on('click', '#crelate-test-connection', this.testConnection);
            
            // Import jobs button
            $(document).on('click', '#crelate-import-jobs', this.importJobs);
            
            // Settings form validation
            $(document).on('submit', 'form[action="options.php"]', this.validateSettings);
            
            // Auto import toggle
            $(document).on('change', 'input[name="crelate_job_board_settings[auto_import]"]', this.toggleAutoImport);
        },

        // Initialize settings page
        initSettingsPage: function() {
            // Show/hide import frequency based on auto import setting
            this.toggleAutoImport();
            
            // Initialize tooltips
            this.initTooltips();
        },

        // Initialize import page
        initImportPage: function() {
            // Load import statistics
            this.loadImportStats();
        },

        // Test API connection
        testConnection: function(e) {
            e.preventDefault();
            
            var button = $(this);
            var resultDiv = $('#connection-result');
            var isDebug = (typeof window.crelate_ajax !== 'undefined' && !!window.crelate_ajax.debug);
            var debugLog = function(){ if (isDebug && typeof console !== 'undefined' && console.log) { console.log.apply(console, arguments); } };
            
            // Disable button and show loading
            button.prop('disabled', true).text('Testing...');
            resultDiv.removeClass('success error').html('<span class="crelate-loading"></span> Testing connection...');
            
            $.ajax({
                url: crelate_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'crelate_test_connection',
                    nonce: crelate_ajax.nonce
                },
                success: function(response) {
                    debugLog('AJAX Response:', response);
                    debugLog('Response type:', typeof response);
                    
                    if (response.success && response.data && response.data.success) {
                        resultDiv.addClass('success').html('<strong>Success!</strong> ' + response.data.message);
                    } else {
                        var errorMessage = response.data && response.data.message ? response.data.message : 'Unknown error occurred';
                        resultDiv.addClass('error').html('<strong>Error!</strong> ' + errorMessage);
                    }
                },
                error: function() {
                    resultDiv.addClass('error').html('<strong>Error!</strong> Failed to test connection. Please try again.');
                },
                complete: function() {
                    button.prop('disabled', false).text('Test Connection');
                }
            });
        },

        // Import jobs
        importJobs: function(e) {
            e.preventDefault();
            
            var button = $(this);
            var resultDiv = $('#import-result');
            // Debounce to prevent double-click duplicates
            if (button.data('busy')) { return; }
            button.data('busy', true);
            
            // Disable button and show loading
            button.prop('disabled', true).text('Importing...');
            resultDiv.removeClass('success error').html('<span class="crelate-loading"></span> Importing jobs...');
            
            $.ajax({
                url: crelate_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'crelate_import_jobs',
                    force_update: false,
                    nonce: crelate_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var data = response.data;
                        var message = 'Import completed successfully!<br>';
                        message += 'Imported: ' + data.imported + ' jobs<br>';
                        message += 'Updated: ' + data.updated + ' jobs<br>';
                        message += 'Errors: ' + data.errors + ' jobs<br>';
                        message += 'Total processed: ' + data.total + ' jobs';
                        
                        resultDiv.addClass('success').html(message);
                        
                        // Reload import statistics
                        CrelateJobBoardAdmin.loadImportStats();
                    } else {
                        resultDiv.addClass('error').html('<strong>Error!</strong> ' + response.data);
                    }
                },
                error: function() {
                    resultDiv.addClass('error').html('<strong>Error!</strong> Import failed. Please try again.');
                },
                complete: function() {
                    button.prop('disabled', false).text('Import Jobs');
                    button.data('busy', false);
                }
            });
        },

        // Load import statistics
        loadImportStats: function() {
            var statsDiv = $('#import-stats');
            
            statsDiv.html('<span class="crelate-loading"></span> Loading statistics...');
            
            $.ajax({
                url: crelate_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'crelate_get_import_stats',
                    nonce: crelate_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var data = response.data;
                        var html = '<div class="stats-summary">';
                        html += '<p><strong>Total Jobs:</strong> ' + data.total_jobs + '</p>';
                        html += '<p><strong>Published Jobs:</strong> ' + data.published_jobs + '</p>';
                        html += '<p><strong>Draft Jobs:</strong> ' + data.draft_jobs + '</p>';
                        
                        if (data.last_import) {
                            html += '<h4>Last Import:</h4>';
                            html += '<p><strong>Date:</strong> ' + data.last_import.timestamp + '</p>';
                            html += '<p><strong>Imported:</strong> ' + data.last_import.imported + '</p>';
                            html += '<p><strong>Updated:</strong> ' + data.last_import.updated + '</p>';
                            html += '<p><strong>Errors:</strong> ' + data.last_import.errors + '</p>';
                        } else {
                            html += '<p><em>No import history available.</em></p>';
                        }
                        
                        html += '</div>';
                        statsDiv.html(html);
                    } else {
                        statsDiv.html('<p class="error">Failed to load statistics.</p>');
                    }
                },
                error: function() {
                    statsDiv.html('<p class="error">Failed to load statistics.</p>');
                }
            });
        },

        // Validate settings form
        validateSettings: function(e) {
            var apiKey = $('input[name="crelate_job_board_settings[api_key]"]').val();
            var portalId = $('input[name="crelate_job_board_settings[portal_id]"]').val();
            
            if (!apiKey.trim()) {
                alert('Please enter your Crelate API key.');
                e.preventDefault();
                return false;
            }
            
            // Portal ID recommended but not strictly required
            
            return true;
        },

        // Toggle auto import frequency field
        toggleAutoImport: function() {
            var autoImport = $('input[name="crelate_job_board_settings[auto_import]"]').is(':checked');
            var frequencyField = $('select[name="crelate_job_board_settings[import_frequency]"]').closest('tr');
            
            if (autoImport) {
                frequencyField.show();
            } else {
                frequencyField.hide();
            }
        },

        // Initialize tooltips
        initTooltips: function() {
            $('.crelate-tooltip').each(function() {
                var tooltip = $(this);
                var text = tooltip.attr('title');
                
                if (text) {
                    tooltip.removeAttr('title').attr('data-tooltip', text);
                }
            });
        },

        // Show notification
        showNotification: function(message, type) {
            type = type || 'info';
            
            var notification = $('<div class="crelate-notice ' + type + '">' + message + '</div>');
            $('.wrap h1').after(notification);
            
            setTimeout(function() {
                notification.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },

        // Confirm action
        confirmAction: function(message) {
            return confirm(message || 'Are you sure you want to proceed?');
        },

        // Format date
        formatDate: function(dateString) {
            var date = new Date(dateString);
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
        },

        // Format number
        formatNumber: function(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        },

        // Show loading state
        showLoading: function(element) {
            element.addClass('loading').prop('disabled', true);
        },

        // Hide loading state
        hideLoading: function(element) {
            element.removeClass('loading').prop('disabled', false);
        },

        // Validate API key format
        validateApiKey: function(apiKey) {
            // Basic validation - adjust based on actual API key format
            return apiKey.length >= 10;
        },

        // Validate URL format
        validateUrl: function(url) {
            try {
                new URL(url);
                return true;
            } catch (e) {
                return false;
            }
        },

        // Sanitize input
        sanitizeInput: function(input) {
            return $('<div>').text(input).html();
        },

        // Debounce function
        debounce: function(func, wait, immediate) {
            var timeout;
            return function() {
                var context = this, args = arguments;
                var later = function() {
                    timeout = null;
                    if (!immediate) func.apply(context, args);
                };
                var callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(context, args);
            };
        }
    };

    // Make it globally accessible
    window.CrelateJobBoardAdmin = CrelateJobBoardAdmin;

    // Lightweight builder UI for Application Fields JSON
    var CrelateFieldsBuilder = {
        init: function(){
            var $ta = $('#application_fields');
            if (!$ta.length) return;
            this.$ta = $ta;
            this.$builder = $('<div class="crelate-fields-builder" />');
            $ta.after(this.$builder);
            this.$ui = $('#application_fields_ui');
            this.load();
            var self = this;
            $ta.on('change', function(){ self.load(); });

            // Add field button
            $('#crelate-add-field').on('click', function(){
                var label = ($('#crelate-field-label').val() || '').trim();
                var type = $('#crelate-field-type').val();
                var required = $('#crelate-field-required').is(':checked');
                if (!label) { alert('Enter a field label'); return; }
                var id = label.toLowerCase().replace(/[^a-z0-9]+/g,'_').replace(/^_|_$/g,'');
                var data = self.parse();
                data.push({ id: id, label: label, type: type, required: required });
                self.$ta.val(JSON.stringify(data, null, 2)).trigger('change');
                $('#crelate-field-label').val('');
                $('#crelate-field-required').prop('checked', false);
            });
        },
        load: function(){
            var data = [];
            try { data = JSON.parse(this.$ta.val() || '[]'); } catch(e) { data = []; }
            if (!Array.isArray(data)) data = [];
            this.$builder.empty();
            if (this.$ui && this.$ui.length) { this.$ui.empty(); }
            var self = this;
            data.forEach(function(f){ self.addPill(f); self.addPreview(f); });
            this.makeSortable();
        },
        addPill: function(field){
            var id = field.id || '';
            var label = field.label || id || 'Field';
            var type = field.type || 'text';
            var $pill = $('<div class="crelate-field-pill" />');
            $pill.append('<span class="pill-label">'+ this.escape(label) +'</span>');
            $pill.append('<span class="pill-type">('+ this.escape(type) +')</span>');
            var $close = $('<span class="pill-remove">✕</span>');
            $pill.append($close);
            var self = this;
            $close.on('click', function(){ self.removeById(id); });
            this.$builder.append($pill);
        },
        addPreview: function(field){
            if (!this.$ui || !this.$ui.length) return;
            var id = field.id || '';
            var label = field.label || id || 'Field';
            var type = field.type || 'text';
            var required = field.required ? ' *' : '';
            var $card = $('<div class="crelate-field-pill" style="cursor:default;" />');
            $card.append('<span class="pill-label">'+ this.escape(label) + required +'</span>');
            $card.append('<span class="pill-type">('+ this.escape(type) +')</span>');
            this.$ui.append($card);
        },
        removeById: function(id){
            var data = this.parse();
            data = data.filter(function(f){ return (f.id||'') !== id; });
            this.$ta.val(JSON.stringify(data, null, 2)).trigger('change');
            this.load();
        },
        parse: function(){
            try { var d = JSON.parse(this.$ta.val() || '[]'); return Array.isArray(d) ? d : []; } catch(e) { return []; }
        },
        makeSortable: function(){
            var self = this;
            this.$builder.sortable({
                stop: function(){
                    var order = [];
                    self.$builder.find('.crelate-field-pill .pill-label').each(function(){ order.push($(this).text()); });
                    var data = self.parse();
                    data.sort(function(a,b){ return order.indexOf(a.label||a.id) - order.indexOf(b.label||b.id); });
                    self.$ta.val(JSON.stringify(data, null, 2));
                }
            });
        },
        escape: function(s){ return String(s).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[c];}); }
    };

})(jQuery);

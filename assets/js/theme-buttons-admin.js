/**
 * Crelate Theme Button Admin Management
 * 
 * This script provides admin tools for managing theme button inheritance,
 * debugging theme detection, and testing button enhancement.
 * 
 * @package Crelate_Job_Board
 * @since 1.0.2
 */

(function($) {
    'use strict';
    
    // Admin theme button management object
    var CrelateThemeButtonsAdmin = {
        
        // Configuration
        config: {
            selectors: {
                debugPanel: '#crelate-theme-button-debug',
                themeInfo: '#crelate-theme-info',
                buttonTest: '#crelate-button-test',
                settingsForm: '#crelate-theme-button-settings'
            },
            classes: {
                active: 'active',
                loading: 'loading',
                error: 'error',
                success: 'success'
            }
        },
        
        /**
         * Initialize admin functionality
         */
        init: function() {
            // Only initialize on Crelate admin pages
            if (!this.isCrelateAdminPage()) {
                return;
            }
            
            this.setupEventListeners();
            this.loadThemeInfo();
            this.setupButtonTest();
            
            // Debug logging
            if (this.isDebugMode()) {
                console.log('Crelate Theme Buttons Admin initialized');
            }
        },
        
        /**
         * Check if we're on a Crelate admin page
         */
        isCrelateAdminPage: function() {
            return window.location.href.indexOf('page=crelate-job-board') !== -1;
        },
        
        /**
         * Check if debug mode is enabled
         */
        isDebugMode: function() {
            return typeof __CRELATE_DBG__ !== 'undefined' && __CRELATE_DBG__;
        },
        
        /**
         * Set up event listeners
         */
        setupEventListeners: function() {
            var self = this;
            
            // Theme button settings form
            $(this.config.selectors.settingsForm).on('submit', function(e) {
                self.handleSettingsSubmit(e);
            });
            
            // Debug panel toggle
            $('.crelate-debug-toggle').on('click', function(e) {
                e.preventDefault();
                self.toggleDebugPanel();
            });
            
            // Test button enhancement
            $('.crelate-test-buttons').on('click', function(e) {
                e.preventDefault();
                self.testButtonEnhancement();
            });
            
            // Reset theme detection
            $('.crelate-reset-theme-detection').on('click', function(e) {
                e.preventDefault();
                self.resetThemeDetection();
            });
            
            // Export theme info
            $('.crelate-export-theme-info').on('click', function(e) {
                e.preventDefault();
                self.exportThemeInfo();
            });
        },
        
        /**
         * Load theme information
         */
        loadThemeInfo: function() {
            var self = this;
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'crelate_get_theme_button_info',
                    nonce: crelate_theme_buttons_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.displayThemeInfo(response.data);
                    } else {
                        self.showError('Failed to load theme information');
                    }
                },
                error: function() {
                    self.showError('Failed to load theme information');
                }
            });
        },
        
        /**
         * Display theme information in the admin panel
         */
        displayThemeInfo: function(data) {
            var $themeInfo = $(this.config.selectors.themeInfo);
            
            if (!$themeInfo.length) {
                return;
            }
            
            var html = '<div class="crelate-theme-info">';
            html += '<h3>Theme Detection Results</h3>';
            html += '<table class="widefat">';
            html += '<tr><th>Property</th><th>Value</th></tr>';
            html += '<tr><td>Theme Name</td><td>' + (data.theme_info.name || 'Unknown') + '</td></tr>';
            html += '<tr><td>Theme Slug</td><td>' + (data.theme_info.slug || 'Unknown') + '</td></tr>';
            html += '<tr><td>Theme Type</td><td>' + (data.theme_info.type || 'Unknown') + '</td></tr>';
            html += '<tr><td>Has Theme.json</td><td>' + (data.theme_info.has_theme_json ? 'Yes' : 'No') + '</td></tr>';
            html += '<tr><td>Parent Theme</td><td>' + (data.theme_info.parent_name || 'None') + '</td></tr>';
            html += '</table>';
            
            html += '<h4>Button Classes</h4>';
            html += '<table class="widefat">';
            html += '<tr><th>Button Type</th><th>Classes</th></tr>';
            html += '<tr><td>Primary</td><td><code>' + (data.button_classes.primary || 'None') + '</code></td></tr>';
            html += '<tr><td>Secondary</td><td><code>' + (data.button_classes.secondary || 'None') + '</code></td></tr>';
            html += '</table>';
            
            html += '<h4>Available Theme Types</h4>';
            html += '<ul>';
            data.available_themes.forEach(function(theme) {
                html += '<li><code>' + theme + '</code></li>';
            });
            html += '</ul>';
            
            html += '</div>';
            
            $themeInfo.html(html);
        },
        
        /**
         * Set up button test functionality
         */
        setupButtonTest: function() {
            var self = this;
            var $testArea = $(this.config.selectors.buttonTest);
            
            if (!$testArea.length) {
                return;
            }
            
            // Create test buttons
            var testButtons = [
                { text: 'Primary Button', type: 'primary', class: 'crelate-btn-primary' },
                { text: 'Secondary Button', type: 'secondary', class: 'crelate-btn-secondary' },
                { text: 'Load More Button', type: 'primary', class: 'crelate-btn-primary', id: 'crelate-load-more' },
                { text: 'Search Button', type: 'primary', class: 'crelate-btn-primary', id: 'crelate-search-btn' },
                { text: 'Clear Filters', type: 'secondary', class: 'crelate-btn-secondary', id: 'crelate-clear-filters' }
            ];
            
            var html = '<div class="crelate-button-test-area">';
            html += '<h3>Button Enhancement Test</h3>';
            html += '<p>These buttons will be enhanced with theme classes when the page loads.</p>';
            html += '<div class="crelate-test-buttons">';
            
            testButtons.forEach(function(button) {
                var buttonHtml = '<button type="button" class="' + button.class + '"';
                if (button.id) {
                    buttonHtml += ' id="' + button.id + '"';
                }
                buttonHtml += '>' + button.text + '</button>';
                html += buttonHtml;
            });
            
            html += '</div>';
            html += '<div class="crelate-test-controls">';
            html += '<button type="button" class="button crelate-test-enhancement">Test Enhancement</button>';
            html += '<button type="button" class="button crelate-reset-test">Reset Test</button>';
            html += '</div>';
            html += '</div>';
            
            $testArea.html(html);
            
            // Set up test controls
            $testArea.find('.crelate-test-enhancement').on('click', function() {
                self.testButtonEnhancement();
            });
            
            $testArea.find('.crelate-reset-test').on('click', function() {
                self.resetButtonTest();
            });
        },
        
        /**
         * Test button enhancement
         */
        testButtonEnhancement: function() {
            var self = this;
            
            // Trigger button enhancement
            if (typeof CrelateThemeButtons !== 'undefined') {
                CrelateThemeButtons.enhanceButtons('.crelate-test-buttons button');
                
                // Show success message
                this.showSuccess('Button enhancement applied successfully');
                
                // Highlight enhanced buttons
                $('.crelate-test-buttons button').addClass('crelate-enhanced-highlight');
                
                setTimeout(function() {
                    $('.crelate-test-buttons button').removeClass('crelate-enhanced-highlight');
                }, 2000);
            } else {
                this.showError('Theme button enhancement not available');
            }
        },
        
        /**
         * Reset button test
         */
        resetButtonTest: function() {
            $('.crelate-test-buttons button').removeClass('crelate-theme-enhanced crelate-enhanced-highlight');
            this.showSuccess('Button test reset');
        },
        
        /**
         * Toggle debug panel
         */
        toggleDebugPanel: function() {
            var $debugPanel = $(this.config.selectors.debugPanel);
            $debugPanel.toggleClass(this.config.classes.active);
        },
        
        /**
         * Handle settings form submission
         */
        handleSettingsSubmit: function(e) {
            var self = this;
            var $form = $(e.target);
            var $submitButton = $form.find('input[type="submit"]');
            
            // Show loading state
            $submitButton.prop('disabled', true).addClass(this.config.classes.loading);
            
            // Submit form via AJAX
            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                success: function(response) {
                    if (response.success) {
                        self.showSuccess('Settings saved successfully');
                    } else {
                        self.showError('Failed to save settings');
                    }
                },
                error: function() {
                    self.showError('Failed to save settings');
                },
                complete: function() {
                    $submitButton.prop('disabled', false).removeClass(self.config.classes.loading);
                }
            });
            
            e.preventDefault();
        },
        
        /**
         * Reset theme detection
         */
        resetThemeDetection: function() {
            var self = this;
            
            if (!confirm('Are you sure you want to reset theme detection? This will clear the cache and re-detect the theme.')) {
                return;
            }
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'crelate_reset_theme_detection',
                    nonce: crelate_theme_buttons_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.showSuccess('Theme detection reset successfully');
                        self.loadThemeInfo(); // Reload theme info
                    } else {
                        self.showError('Failed to reset theme detection');
                    }
                },
                error: function() {
                    self.showError('Failed to reset theme detection');
                }
            });
        },
        
        /**
         * Export theme information
         */
        exportThemeInfo: function() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'crelate_export_theme_info',
                    nonce: crelate_theme_buttons_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Create download link
                        var dataStr = JSON.stringify(response.data, null, 2);
                        var dataBlob = new Blob([dataStr], {type: 'application/json'});
                        var url = window.URL.createObjectURL(dataBlob);
                        var link = document.createElement('a');
                        link.href = url;
                        link.download = 'crelate-theme-info.json';
                        link.click();
                        window.URL.revokeObjectURL(url);
                    } else {
                        this.showError('Failed to export theme information');
                    }
                },
                error: function() {
                    this.showError('Failed to export theme information');
                }
            });
        },
        
        /**
         * Show success message
         */
        showSuccess: function(message) {
            this.showMessage(message, this.config.classes.success);
        },
        
        /**
         * Show error message
         */
        showError: function(message) {
            this.showMessage(message, this.config.classes.error);
        },
        
        /**
         * Show message
         */
        showMessage: function(message, type) {
            var $message = $('<div class="crelate-admin-message ' + type + '">' + message + '</div>');
            $('.wrap h1').after($message);
            
            // Auto-remove after 5 seconds
            setTimeout(function() {
                $message.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },
        
        /**
         * Public method to get theme information
         */
        getThemeInfo: function() {
            return this.themeInfo;
        },
        
        /**
         * Public method to test button enhancement
         */
        testEnhancement: function(selector) {
            if (typeof CrelateThemeButtons !== 'undefined') {
                CrelateThemeButtons.enhanceButtons(selector);
                return true;
            }
            return false;
        }
    };
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        CrelateThemeButtonsAdmin.init();
    });
    
    // Make it globally available
    window.CrelateThemeButtonsAdmin = CrelateThemeButtonsAdmin;
    
})(jQuery);

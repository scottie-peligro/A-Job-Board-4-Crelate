/**
 * Crelate Theme Button Enhancement
 * 
 * This script provides progressive enhancement for Crelate Job Board buttons
 * to automatically inherit the theme's button styling, similar to Gravity Forms.
 * 
 * @package Crelate_Job_Board
 * @since 1.0.2
 */

(function($) {
    'use strict';
    
    // Theme button enhancement object
    var CrelateThemeButtons = {
        
        // Configuration
        config: {
            selectors: {
                buttons: '.crelate-btn, .crelate-btn-primary, .crelate-btn-secondary, .crelate-template-btn, .crelate-quick-action, .crelate-share-btn, .crelate-sidebar-toggle',
                primaryButtons: '.crelate-btn-primary, .crelate-template-btn.active',
                secondaryButtons: '.crelate-btn-secondary, .crelate-template-btn:not(.active)',
                loadMore: '#crelate-load-more',
                searchBtn: '#crelate-search-btn',
                clearFilters: '#crelate-clear-filters'
            },
            attributes: {
                noThemeButton: 'data-no-theme-button',
                buttonType: 'data-button-type',
                buttonContext: 'data-button-context'
            },
            classes: {
                enhanced: 'crelate-theme-enhanced',
                loading: 'crelate-theme-loading'
            }
        },
        
        // Theme information from PHP
        themeInfo: null,
        buttonClasses: null,
        
        /**
         * Initialize the theme button enhancement
         */
        init: function() {
            // Get theme information from PHP
            if (typeof crelate_theme_buttons !== 'undefined') {
                this.themeInfo = crelate_theme_buttons.theme_info;
                this.buttonClasses = crelate_theme_buttons.button_classes;
            }
            
            // Check if theme button enhancement is enabled
            if (!this.isEnabled()) {
                return;
            }
            
            // Enhance existing buttons
            this.enhanceExistingButtons();
            
            // Set up observers for dynamically added content
            this.setupObservers();
            
            // Handle AJAX-loaded content
            this.handleAjaxContent();
            
            // Debug logging
            if (this.isDebugMode()) {
                console.log('Crelate Theme Buttons initialized:', {
                    themeInfo: this.themeInfo,
                    buttonClasses: this.buttonClasses
                });
            }
        },
        
        /**
         * Check if theme button enhancement is enabled
         */
        isEnabled: function() {
            // Check if user has disabled theme buttons globally
            if (localStorage.getItem('crelate_disable_theme_buttons') === 'true') {
                return false;
            }
            
            // Check if theme info is available
            if (!this.themeInfo || !this.buttonClasses) {
                return false;
            }
            
            return true;
        },
        
        /**
         * Check if debug mode is enabled
         */
        isDebugMode: function() {
            return typeof __CRELATE_DBG__ !== 'undefined' && __CRELATE_DBG__;
        },
        
        /**
         * Enhance existing buttons on the page
         */
        enhanceExistingButtons: function() {
            var self = this;
            
            // Enhance all button types
            $(this.config.selectors.buttons).each(function() {
                self.enhanceButton($(this));
            });
            
            // Specific enhancements for different button types
            this.enhancePrimaryButtons();
            this.enhanceSecondaryButtons();
            this.enhanceSpecialButtons();
        },
        
        /**
         * Enhance a single button
         */
        enhanceButton: function($button) {
            // Skip if already enhanced or explicitly disabled
            if ($button.hasClass(this.config.classes.enhanced) || 
                $button.attr(this.config.attributes.noThemeButton) === '1') {
                return;
            }
            
            // Determine button type and context
            var buttonType = this.getButtonType($button);
            var buttonContext = this.getButtonContext($button);
            
            // Get theme classes for this button
            var themeClasses = this.getThemeClasses(buttonType, buttonContext);
            
            if (themeClasses) {
                // Apply theme classes
                this.applyThemeClasses($button, themeClasses);
                
                // Mark as enhanced
                $button.addClass(this.config.classes.enhanced);
                
                // Add data attributes for debugging
                $button.attr(this.config.attributes.buttonType, buttonType);
                $button.attr(this.config.attributes.buttonContext, buttonContext);
                
                if (this.isDebugMode()) {
                    console.log('Enhanced button:', {
                        element: $button[0],
                        type: buttonType,
                        context: buttonContext,
                        classes: themeClasses
                    });
                }
            }
        },
        
        /**
         * Enhance primary buttons specifically
         */
        enhancePrimaryButtons: function() {
            var self = this;
            $(this.config.selectors.primaryButtons).each(function() {
                self.enhanceButton($(this));
            });
        },
        
        /**
         * Enhance secondary buttons specifically
         */
        enhanceSecondaryButtons: function() {
            var self = this;
            $(this.config.selectors.secondaryButtons).each(function() {
                self.enhanceButton($(this));
            });
        },
        
        /**
         * Enhance special buttons with specific contexts
         */
        enhanceSpecialButtons: function() {
            var self = this;
            
            // Load more button
            $(this.config.selectors.loadMore).each(function() {
                self.enhanceButton($(this));
            });
            
            // Search button
            $(this.config.selectors.searchBtn).each(function() {
                self.enhanceButton($(this));
            });
            
            // Clear filters button
            $(this.config.selectors.clearFilters).each(function() {
                self.enhanceButton($(this));
            });
        },
        
        /**
         * Determine button type based on classes and context
         */
        getButtonType: function($button) {
            // Check for explicit type attribute
            var explicitType = $button.attr(this.config.attributes.buttonType);
            if (explicitType) {
                return explicitType;
            }
            
            // Determine type based on classes
            if ($button.hasClass('crelate-btn-primary') || 
                $button.hasClass('crelate-template-btn') && $button.hasClass('active')) {
                return 'primary';
            }
            
            if ($button.hasClass('crelate-btn-secondary') || 
                $button.hasClass('crelate-template-btn') && !$button.hasClass('active')) {
                return 'secondary';
            }
            
            // Default to primary for most buttons
            return 'primary';
        },
        
        /**
         * Determine button context based on classes and attributes
         */
        getButtonContext: function($button) {
            // Check for explicit context attribute
            var explicitContext = $button.attr(this.config.attributes.buttonContext);
            if (explicitContext) {
                return explicitContext;
            }
            
            // Determine context based on classes and attributes
            if ($button.attr('id') === 'crelate-load-more') {
                return 'load-more';
            }
            
            if ($button.attr('id') === 'crelate-search-btn') {
                return 'search';
            }
            
            if ($button.attr('id') === 'crelate-clear-filters') {
                return 'clear-filters';
            }
            
            if ($button.hasClass('crelate-template-btn')) {
                return 'template-switcher';
            }
            
            if ($button.hasClass('crelate-quick-action')) {
                return 'quick-action';
            }
            
            if ($button.hasClass('crelate-share-btn')) {
                return 'share';
            }
            
            if ($button.hasClass('crelate-sidebar-toggle')) {
                return 'sidebar-toggle';
            }
            
            return 'default';
        },
        
        /**
         * Get theme classes for a button type and context
         */
        getThemeClasses: function(buttonType, context) {
            // Use cached classes if available
            if (this.buttonClasses && this.buttonClasses[buttonType]) {
                return this.buttonClasses[buttonType];
            }
            
            // Fallback to AJAX request if needed
            return this.getThemeClassesViaAjax(buttonType, context);
        },
        
        /**
         * Get theme classes via AJAX (fallback)
         */
        getThemeClassesViaAjax: function(buttonType, context) {
            var self = this;
            
            $.ajax({
                url: crelate_theme_buttons.ajax_url,
                type: 'POST',
                data: {
                    action: 'crelate_get_theme_button_classes',
                    nonce: crelate_theme_buttons.nonce,
                    type: buttonType,
                    context: context
                },
                success: function(response) {
                    if (response.success && response.data.classes) {
                        // Cache the result
                        if (!self.buttonClasses) {
                            self.buttonClasses = {};
                        }
                        self.buttonClasses[buttonType] = response.data.classes;
                        
                        if (self.isDebugMode()) {
                            console.log('Retrieved theme classes via AJAX:', response.data);
                        }
                    }
                },
                error: function(xhr, status, error) {
                    if (self.isDebugMode()) {
                        console.error('Failed to get theme classes via AJAX:', error);
                    }
                }
            });
            
            return null;
        },
        
        /**
         * Apply theme classes to a button
         */
        applyThemeClasses: function($button, themeClasses) {
            if (!themeClasses) {
                return;
            }
            
            // Split classes and apply them
            var classes = themeClasses.split(' ');
            
            classes.forEach(function(className) {
                if (className.trim()) {
                    $button.addClass(className.trim());
                }
            });
            
            // Ensure proper button semantics
            this.ensureButtonSemantics($button);
        },
        
        /**
         * Ensure proper button semantics and accessibility
         */
        ensureButtonSemantics: function($button) {
            // Ensure button has proper type
            if (!$button.attr('type')) {
                $button.attr('type', 'button');
            }
            
            // Ensure proper ARIA attributes for accessibility
            if (!$button.attr('aria-label') && $button.attr('title')) {
                $button.attr('aria-label', $button.attr('title'));
            }
            
            // Add focus-visible support for better accessibility
            $button.on('focus', function() {
                $(this).addClass('focus-visible');
            }).on('blur', function() {
                $(this).removeClass('focus-visible');
            });
        },
        
        /**
         * Set up observers for dynamically added content
         */
        setupObservers: function() {
            var self = this;
            
            // Use MutationObserver to watch for new buttons
            if (typeof MutationObserver !== 'undefined') {
                var observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'childList') {
                            mutation.addedNodes.forEach(function(node) {
                                if (node.nodeType === Node.ELEMENT_NODE) {
                                    // Check if the added node is a button
                                    if (node.matches && node.matches(self.config.selectors.buttons)) {
                                        self.enhanceButton($(node));
                                    }
                                    
                                    // Check for buttons within the added node
                                    $(node).find(self.config.selectors.buttons).each(function() {
                                        self.enhanceButton($(this));
                                    });
                                }
                            });
                        }
                    });
                });
                
                // Start observing
                observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });
            }
        },
        
        /**
         * Handle AJAX-loaded content
         */
        handleAjaxContent: function() {
            var self = this;
            
            // Listen for AJAX completion events
            $(document).on('crelate_ajax_complete', function() {
                setTimeout(function() {
                    self.enhanceExistingButtons();
                }, 100);
            });
            
            // Listen for job board initialization
            $(document).on('crelate_job_board_ready', function() {
                setTimeout(function() {
                    self.enhanceExistingButtons();
                }, 100);
            });
        },
        
        /**
         * Public method to enhance buttons manually
         */
        enhanceButtons: function(selector) {
            var self = this;
            $(selector || this.config.selectors.buttons).each(function() {
                self.enhanceButton($(this));
            });
        },
        
        /**
         * Public method to disable theme buttons for an element
         */
        disableThemeButtons: function($element) {
            $element.attr(this.config.attributes.noThemeButton, '1');
            $element.removeClass(this.config.classes.enhanced);
        },
        
        /**
         * Public method to enable theme buttons for an element
         */
        enableThemeButtons: function($element) {
            $element.removeAttr(this.config.attributes.noThemeButton);
            this.enhanceButton($element);
        }
    };
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        CrelateThemeButtons.init();
    });
    
    // Make it globally available
    window.CrelateThemeButtons = CrelateThemeButtons;
    
})(jQuery);

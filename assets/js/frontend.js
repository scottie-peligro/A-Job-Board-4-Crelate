/**
 * Crelate Job Board Frontend JavaScript
 */

(function($) {
    'use strict';

    // Main job board object
    var CrelateJobBoard = {
        
        // Configuration
        config: {
            template: 'grid',
            postsPerPage: null, // Will be set from template
            showFilters: true,
            showSearch: true,
            currentPage: 1,
            maxPages: 1
        },
        
        // Initialize the job board
        init: function(options) {
            var isDebug = (typeof window.crelate_ajax !== 'undefined' && !!window.crelate_ajax.debug);
            var debugLog = function(){ if (isDebug && typeof console !== 'undefined' && console.log) { console.log.apply(console, arguments); } };
            this._debugLog = debugLog;
            this._isDebug = isDebug;
            debugLog('CrelateJobBoard.init called with options:', options);
            this.config = $.extend({}, this.config, options);
            this.bindEvents();
            this.initFilters();
            debugLog('CrelateJobBoard initialization complete');
        },
        
        // Bind event handlers
        bindEvents: function() {
            var debugLog = this._debugLog || function(){};
            debugLog('Binding events...');
            
            // Search functionality
            $('#crelate-search-btn').on('click', this.handleSearch.bind(this));
            $('#crelate-search').on('keypress', function(e) {
                if (e.which === 13) {
                    CrelateJobBoard.handleSearch();
                }
            });
            
            // Filter functionality
            $('#crelate-location-filter, #crelate-department-filter, #crelate-type-filter, #crelate-experience-filter').on('change', this.handleFilter.bind(this));
            $('#crelate-remote-filter').on('change', this.handleFilter.bind(this));
            $('#crelate-sort').on('change', this.handleSort.bind(this));
            
            // Clear filters
            $('#crelate-clear-filters').on('click', this.clearFilters.bind(this));
            

            
            // Load more
            $('#crelate-load-more').on('click', this.loadMore.bind(this));
            
            // Quick actions
            $(document).on('click', '.crelate-quick-action', this.handleQuickAction.bind(this));
            
            // Share buttons
            $(document).on('click', '.crelate-share-btn', this.handleShare.bind(this));
            
            debugLog('Events bound successfully');
        },
        
        // Initialize filters with current values
        initFilters: function() {
            // Set current filter values from URL parameters
            var urlParams = new URLSearchParams(window.location.search);
            
            if (urlParams.get('search')) {
                $('#crelate-search').val(urlParams.get('search'));
            }
            
            if (urlParams.get('location')) {
                $('#crelate-location-filter').val(urlParams.get('location'));
            }
            
            if (urlParams.get('department')) {
                $('#crelate-department-filter').val(urlParams.get('department'));
            }
            
            if (urlParams.get('job_type')) {
                $('#crelate-type-filter').val(urlParams.get('job_type'));
            }
            
            if (urlParams.get('experience')) {
                $('#crelate-experience-filter').val(urlParams.get('experience'));
            }
            
            if (urlParams.get('remote') === 'true') {
                $('#crelate-remote-filter').prop('checked', true);
            }
            
            if (urlParams.get('sort')) {
                $('#crelate-sort').val(urlParams.get('sort'));
            }
        },
        
        // Handle search
        handleSearch: function() {
            var debugLog = this._debugLog || function(){};
            debugLog('Search triggered');
            var searchTerm = $('#crelate-search').val();
            debugLog('Search term:', searchTerm);
            this.updateJobs({ search: searchTerm });
        },
        
        // Handle filter changes
        handleFilter: function() {
            var debugLog = this._debugLog || function(){};
            debugLog('Filter triggered');
            var filters = this.getFilters();
            debugLog('Filters:', filters);
            this.updateJobs(filters);
        },
        
        // Handle sort changes
        handleSort: function() {
            var sortValue = $('#crelate-sort').val();
            var sortParts = sortValue.split('-');
            var filters = this.getFilters();
            filters.orderby = sortParts[0];
            filters.order = sortParts[1];
            this.updateJobs(filters);
        },
        
        // Get current filter values
        getFilters: function() {
            var filters = {};
            
            var search = $('#crelate-search').val();
            if (search) filters.search = search;
            
            var location = $('#crelate-location-filter').val();
            if (location) filters.location = location;
            
            var department = $('#crelate-department-filter').val();
            if (department) filters.department = department;
            
            var jobType = $('#crelate-type-filter').val();
            if (jobType) filters.job_type = jobType;
            
            var experience = $('#crelate-experience-filter').val();
            if (experience) filters.experience = experience;
            
            var remote = $('#crelate-remote-filter').is(':checked');
            if (remote) filters.remote = 'true';
            
            return filters;
        },
        
        // Clear all filters
        clearFilters: function() {
            $('#crelate-search').val('');
            $('#crelate-location-filter').val('');
            $('#crelate-department-filter').val('');
            $('#crelate-type-filter').val('');
            $('#crelate-experience-filter').val('');
            $('#crelate-remote-filter').prop('checked', false);
            $('#crelate-sort').val('date-desc');
            
            this.updateJobs({});
        },
        

        
        // Load more jobs
        loadMore: function() {
            var button = $('#crelate-load-more');
            var currentPage = parseInt(button.data('page'));
            var maxPages = parseInt(button.data('max-pages'));
            
            if (currentPage >= maxPages) {
                button.prop('disabled', true).text('No more jobs');
                return;
            }
            
            button.prop('disabled', true).text('Loading...');
            
            var filters = this.getFilters();
            filters.page = currentPage + 1;
            filters.load_more = true;
            
                         this.updateJobs(filters, function(data) {
                 if (data && data.html) {
                     // Append new jobs
                     var container = $('.crelate-jobs-grid, .crelate-jobs-list');
                     container.append(data.html);
                     
                     // Update button
                     button.data('page', currentPage + 1);
                     button.prop('disabled', false).text('Load More Jobs');
                     
                     // Hide button if no more pages
                     if (currentPage + 1 >= maxPages) {
                         button.hide();
                     }
                 }
             });
        },
        
        // Update jobs via AJAX
        updateJobs: function(filters, callback) {
            var self = this;
            
            // Show loading
            this.showLoading();
            
            // Prepare data
            var data = {
                action: 'crelate_filter_jobs',
                nonce: crelate_ajax.nonce,
                template: this.config.template,
                per_page: this.config.postsPerPage || 12
            };
            
            // Add filters
            $.extend(data, filters);
            
            // Make AJAX request
            $.ajax({
                url: crelate_ajax.ajax_url,
                type: 'POST',
                data: data,
                success: function(response) {
                    var debugLog = self._debugLog || function(){};
                    debugLog('AJAX response received:', response);
                    debugLog('Response type:', typeof response);
                    
                    if (response.success) {
                        // Handle both wrapped and flat response structures
                        var data = response.data || response;
                        
                        if (callback) {
                            callback(data);
                        } else {
                            // Replace jobs content
                            var container = $('.crelate-jobs-grid, .crelate-jobs-list');
                            if (data && data.html) {
                                container.html(data.html);
                            } else {
                                console.error('data.html is undefined or missing');
                                console.error('Response data:', data);
                            }
                            
                            // Update results count
                            if (data && data.found_posts !== undefined) {
                                self.updateResultsCount(data.found_posts);
                            } else if (data && data.total_posts !== undefined) {
                                self.updateResultsCount(data.total_posts);
                            }
                            
                            // Update load more button
                            if (data && data.max_num_pages !== undefined) {
                                self.updateLoadMoreButton(data.max_num_pages);
                            } else if (data && data.max_pages !== undefined) {
                                self.updateLoadMoreButton(data.max_pages);
                            }
                            
                            // Update URL
                            self.updateURL(filters);
                        }
                    } else {
                        console.error('Error updating jobs:', response);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error);
                },
                complete: function() {
                    self.hideLoading();
                }
            });
        },
        
        // Show loading indicator
        showLoading: function() {
            $('#crelate-loading').show();
        },
        
        // Hide loading indicator
        hideLoading: function() {
            $('#crelate-loading').hide();
        },
        
        // Update results count
        updateResultsCount: function(count) {
            var text = count === 1 ? '1 job found' : count + ' jobs found';
            $('.crelate-results-count').text(text);
        },
        
        // Update load more button
        updateLoadMoreButton: function(maxPages) {
            var button = $('#crelate-load-more');
            if (maxPages > 1) {
                button.show().data('page', 1).data('max-pages', maxPages);
            } else {
                button.hide();
            }
        },
        
        // Update URL with filters
        updateURL: function(filters) {
            var url = new URL(window.location);
            
            // Clear existing parameters
            url.searchParams.delete('search');
            url.searchParams.delete('location');
            url.searchParams.delete('department');
            url.searchParams.delete('job_type');
            url.searchParams.delete('experience');
            url.searchParams.delete('remote');
            url.searchParams.delete('sort');
            
            // Add new parameters
            if (filters.search) url.searchParams.set('search', filters.search);
            if (filters.location) url.searchParams.set('location', filters.location);
            if (filters.department) url.searchParams.set('department', filters.department);
            if (filters.job_type) url.searchParams.set('job_type', filters.job_type);
            if (filters.experience) url.searchParams.set('experience', filters.experience);
            if (filters.remote) url.searchParams.set('remote', filters.remote);
            if (filters.orderby && filters.order) url.searchParams.set('sort', filters.orderby + '-' + filters.order);
            
            // Update URL without page reload
            window.history.pushState({}, '', url);
        },
        
        // Handle quick actions
        handleQuickAction: function(e) {
            e.preventDefault();
            
            var action = $(e.currentTarget).data('action');
            var jobId = $(e.currentTarget).closest('[data-job-id]').data('job-id');
            
            switch (action) {
                case 'save':
                    this.saveJob(jobId, e.currentTarget);
                    break;
                case 'share':
                    this.shareJob(jobId);
                    break;
            }
        },
        
        // Handle share button clicks
        handleShare: function(e) {
            e.preventDefault();
            var $button = $(e.currentTarget);
            var jobId = $button.data('job-id');
            
            if (jobId) {
                this.shareJob(jobId);
            } else {
                console.error('No job ID found for share button');
            }
        },
        
        // Save job
        saveJob: function(jobId, button) {
            var $button = $(button);
            var $icon = $button.find('i');
            
            // Toggle saved state
            if ($button.hasClass('saved')) {
                $button.removeClass('saved');
                $icon.removeClass('crelate-icon-bookmark-filled').addClass('crelate-icon-bookmark');
                localStorage.removeItem('crelate_saved_job_' + jobId);
            } else {
                $button.addClass('saved');
                $icon.removeClass('crelate-icon-bookmark').addClass('crelate-icon-bookmark-filled');
                localStorage.setItem('crelate_saved_job_' + jobId, 'true');
            }
        },
        
        // Share job
        shareJob: function(jobId) {
            var jobUrl, jobTitle;
            
            // Check if we're on a job details page
            if (window.location.pathname.includes('/job/') || window.location.pathname.includes('/jobs/')) {
                jobUrl = window.location.href;
                jobTitle = $('.crelate-job-detail-title h1, .entry-title').first().text().trim();
            } else {
                // Get the job slug for the URL
                var jobSlug = $('[data-job-id="' + jobId + '"]').find('a[href*="/job/"]').attr('href');
                if (jobSlug) {
                    jobUrl = jobSlug.startsWith('http') ? jobSlug : window.location.origin + jobSlug;
                } else {
                    jobUrl = window.location.origin + '/job/' + jobId;
                }
                jobTitle = $('[data-job-id="' + jobId + '"] .crelate-job-title').text().trim();
            }
            
            if (navigator.share) {
                navigator.share({
                    title: jobTitle,
                    url: jobUrl
                }).catch(function(error) {
                    console.error('Error sharing:', error);
                    // Fallback to clipboard
                    CrelateJobBoard.copyToClipboard(jobUrl);
                });
            } else {
                // Fallback: copy to clipboard
                this.copyToClipboard(jobUrl);
            }
        },
        
        // Copy to clipboard helper
        copyToClipboard: function(text) {
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(function() {
                    CrelateJobBoard.showNotification('Job URL copied to clipboard!', 'success');
                }).catch(function() {
                    CrelateJobBoard.fallbackCopyToClipboard(text);
                });
            } else {
                this.fallbackCopyToClipboard(text);
            }
        },
        
        // Fallback copy to clipboard for older browsers
        fallbackCopyToClipboard: function(text) {
            var textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                document.execCommand('copy');
                this.showNotification('Job URL copied to clipboard!', 'success');
            } catch (err) {
                this.showNotification('Failed to copy URL. Please copy manually: ' + text, 'error');
            }
            
            document.body.removeChild(textArea);
        },
        
        // Show notification
        showNotification: function(message, type) {
            // Remove existing notifications
            $('.crelate-notification').remove();
            
            var notification = $('<div class="crelate-notification crelate-notification-' + type + '">' + message + '</div>');
            $('body').append(notification);
            
            // Auto remove after 3 seconds
            setTimeout(function() {
                notification.fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
        },
        
        // Initialize saved jobs
        initSavedJobs: function() {
            $('.crelate-job-card, .crelate-job-item').each(function() {
                var jobId = $(this).data('job-id');
                if (localStorage.getItem('crelate_saved_job_' + jobId)) {
                    var $button = $(this).find('[data-action="save"]');
                    var $icon = $button.find('i');
                    $button.addClass('saved');
                    $icon.removeClass('crelate-icon-bookmark').addClass('crelate-icon-bookmark-filled');
                }
            });
        },
        

    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        // Initialize saved jobs
        CrelateJobBoard.initSavedJobs();
    });
    
    // Make it globally accessible
    window.CrelateJobBoard = CrelateJobBoard;
    
})(jQuery);

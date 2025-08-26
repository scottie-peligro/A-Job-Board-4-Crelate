/**
 * Crelate Job Board - Frontend JavaScript
 */

(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        CrelateJobBoard.init();
    });

    // Main plugin object
    var CrelateJobBoard = {
        
        // Initialize the plugin
        init: function() {
            this.bindEvents();
            this.initSearch();
            this.initFilters();
            this.initLoadMore();
            this.initJobCards();
        },

        // Bind event handlers
        bindEvents: function() {
            // Search form submission
            $(document).on('submit', '.crelate-search-form form', this.handleSearch);
            
            // Filter form submission
            $(document).on('submit', '#job-filters-form', this.handleFilters);
            
            // Load more button
            $(document).on('click', '.load-more-jobs', this.handleLoadMore);
            
            // Apply button tracking
            $(document).on('click', '.apply-job', this.trackApplication);
            
            // Job card interactions
            $(document).on('click', '.crelate-job-card', this.handleJobCardClick);
            
            // Clear filters
            $(document).on('click', '.clear-filters', this.clearFilters);
        },

        // Initialize search functionality
        initSearch: function() {
            var searchInput = $('.search-input');
            var searchTimeout;

            // Debounced search
            searchInput.on('input', function() {
                clearTimeout(searchTimeout);
                var query = $(this).val();
                
                if (query.length >= 2) {
                    searchTimeout = setTimeout(function() {
                        CrelateJobBoard.performSearch(query);
                    }, 500);
                } else if (query.length === 0) {
                    CrelateJobBoard.clearSearch();
                }
            });
        },

        // Initialize filters
        initFilters: function() {
            // Auto-submit filters on change
            $('.filter-group select').on('change', function() {
                $('#job-filters-form').submit();
            });
        },

        // Initialize load more functionality
        initLoadMore: function() {
            // Check if we're near the bottom of the page
            $(window).on('scroll', function() {
                if ($(window).scrollTop() + $(window).height() > $(document).height() - 100) {
                    if ($('.load-more-jobs').length && !$('.load-more-jobs').hasClass('loading')) {
                        CrelateJobBoard.handleLoadMore();
                    }
                }
            });
        },

        // Initialize job card interactions
        initJobCards: function() {
            // Add hover effects
            $('.crelate-job-card').hover(
                function() {
                    $(this).addClass('hover');
                },
                function() {
                    $(this).removeClass('hover');
                }
            );
        },

        // Handle search form submission
        handleSearch: function(e) {
            e.preventDefault();
            var query = $(this).find('.search-input').val();
            CrelateJobBoard.performSearch(query);
        },

        // Handle filter form submission
        handleFilters: function(e) {
            e.preventDefault();
            CrelateJobBoard.performFilters();
        },

        // Handle load more
        handleLoadMore: function(e) {
            if (e) e.preventDefault();
            
            var loadMoreBtn = $('.load-more-jobs');
            if (loadMoreBtn.hasClass('loading')) return;

            loadMoreBtn.addClass('loading').text(crelate_ajax.strings.loading);
            
            var currentPage = parseInt(loadMoreBtn.data('page')) || 1;
            var nextPage = currentPage + 1;
            
            CrelateJobBoard.loadMoreJobs(nextPage, function() {
                loadMoreBtn.data('page', nextPage);
                loadMoreBtn.removeClass('loading').text('Load More Jobs');
            });
        },

        // Handle job card click
        handleJobCardClick: function(e) {
            // Don't trigger if clicking on links or buttons
            if ($(e.target).is('a, button, .apply-job')) {
                return;
            }
            
            var jobUrl = $(this).find('.job-title a').attr('href');
            if (jobUrl) {
                window.location.href = jobUrl;
            }
        },

        // Track application clicks
        trackApplication: function(e) {
            var jobId = $(this).data('job-id');
            var jobTitle = $(this).data('job-title');
            
            // Send tracking data
            $.ajax({
                url: crelate_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'crelate_track_application',
                    job_id: jobId,
                    job_title: jobTitle,
                    user_email: 'anonymous', // Could be enhanced to get user email
                    user_name: 'Anonymous User', // Could be enhanced to get user name
                    nonce: crelate_ajax.nonce
                },
                success: function(response) {
                    console.log('Application tracked:', response);
                }
            });
        },

        // Clear filters
        clearFilters: function(e) {
            e.preventDefault();
            window.location.href = $(this).attr('href');
        },

        // Perform search
        performSearch: function(query) {
            var jobsContainer = $('.crelate-jobs-grid');
            var resultsCount = $('.crelate-jobs-count');
            
            // Show loading state
            jobsContainer.addClass('loading');
            
            $.ajax({
                url: crelate_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'crelate_search_jobs',
                    search_term: query,
                    page: 1,
                    per_page: 10,
                    nonce: crelate_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        CrelateJobBoard.updateJobsDisplay(response.jobs, response.total_posts);
                        CrelateJobBoard.updateURL('job_search', query);
                    } else {
                        CrelateJobBoard.showError(crelate_ajax.strings.error);
                    }
                },
                error: function() {
                    CrelateJobBoard.showError(crelate_ajax.strings.error);
                },
                complete: function() {
                    jobsContainer.removeClass('loading');
                }
            });
        },

        // Perform filters
        performFilters: function() {
            var form = $('#job-filters-form');
            var jobsContainer = $('.crelate-jobs-grid');
            var formData = form.serialize();
            
            // Show loading state
            jobsContainer.addClass('loading');
            
            $.ajax({
                url: crelate_ajax.ajax_url,
                type: 'POST',
                data: formData + '&action=crelate_filter_jobs&nonce=' + crelate_ajax.nonce,
                success: function(response) {
                    if (response.success) {
                        CrelateJobBoard.updateJobsDisplay(response.jobs, response.total_posts);
                        CrelateJobBoard.updateURLFromForm(form);
                    } else {
                        CrelateJobBoard.showError(crelate_ajax.strings.error);
                    }
                },
                error: function() {
                    CrelateJobBoard.showError(crelate_ajax.strings.error);
                },
                complete: function() {
                    jobsContainer.removeClass('loading');
                }
            });
        },

        // Load more jobs
        loadMoreJobs: function(page, callback) {
            var jobsContainer = $('.crelate-jobs-grid');
            var currentFilters = CrelateJobBoard.getCurrentFilters();
            var searchTerm = $('.search-input').val();
            
            $.ajax({
                url: crelate_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'crelate_load_more_jobs',
                    page: page,
                    per_page: 10,
                    search_term: searchTerm,
                    filters: currentFilters,
                    nonce: crelate_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        CrelateJobBoard.appendJobs(response.jobs);
                        
                        if (!response.has_more) {
                            $('.load-more-jobs').hide();
                        }
                        
                        if (callback) callback();
                    } else {
                        CrelateJobBoard.showError(crelate_ajax.strings.error);
                        if (callback) callback();
                    }
                },
                error: function() {
                    CrelateJobBoard.showError(crelate_ajax.strings.error);
                    if (callback) callback();
                }
            });
        },

        // Update jobs display
        updateJobsDisplay: function(jobs, totalPosts) {
            var jobsContainer = $('.crelate-jobs-grid');
            var resultsCount = $('.crelate-jobs-count');
            
            // Clear existing jobs
            jobsContainer.empty();
            
            // Add new jobs
            if (jobs.length > 0) {
                jobs.forEach(function(job) {
                    jobsContainer.append(CrelateJobBoard.createJobCard(job));
                });
                
                // Update results count
                resultsCount.text(totalPosts + ' job' + (totalPosts !== 1 ? 's' : '') + ' found');
                
                // Show/hide load more button
                if (totalPosts > jobs.length) {
                    CrelateJobBoard.showLoadMoreButton();
                } else {
                    $('.load-more-jobs').hide();
                }
            } else {
                jobsContainer.html('<div class="crelate-no-jobs"><p>' + crelate_ajax.strings.no_results + '</p></div>');
                resultsCount.text('0 jobs found');
                $('.load-more-jobs').hide();
            }
        },

        // Append jobs (for load more)
        appendJobs: function(jobs) {
            var jobsContainer = $('.crelate-jobs-grid');
            
            jobs.forEach(function(job) {
                jobsContainer.append(CrelateJobBoard.createJobCard(job));
            });
        },

        // Create job card HTML
        createJobCard: function(job) {
            var card = $('<div class="crelate-job-card"></div>');
            
            var html = '<div class="job-card-header">';
            html += '<h3 class="job-title"><a href="' + job.permalink + '">' + job.title + '</a></h3>';
            
            if (job.location) {
                html += '<div class="job-location"><i class="dashicons dashicons-location"></i>' + job.location + '</div>';
            }
            html += '</div>';
            
            html += '<div class="job-card-meta">';
            if (job.department) {
                html += '<span class="job-department"><i class="dashicons dashicons-building"></i>' + job.department + '</span>';
            }
            if (job.employment_type) {
                html += '<span class="job-type"><i class="dashicons dashicons-clock"></i>' + job.employment_type + '</span>';
            }
            if (job.salary) {
                html += '<span class="job-salary"><i class="dashicons dashicons-money-alt"></i>' + job.salary + '</span>';
            }
            html += '</div>';
            
            html += '<div class="job-card-excerpt">' + job.excerpt + '</div>';
            
            html += '<div class="job-card-actions">';
            html += '<a href="' + job.permalink + '" class="view-job">View Details</a>';
            
            if (!job.is_expired && job.apply_url) {
                html += '<a href="' + job.apply_url + '" target="_blank" class="apply-job" data-job-id="' + job.id + '" data-job-title="' + job.title + '">Apply Now</a>';
            } else if (job.is_expired) {
                html += '<span class="job-expired">Application Closed</span>';
            }
            html += '</div>';
            
            card.html(html);
            return card;
        },

        // Show load more button
        showLoadMoreButton: function() {
            if ($('.load-more-jobs').length === 0) {
                $('.crelate-jobs-results').append('<div class="text-center"><button class="button load-more-jobs" data-page="1">Load More Jobs</button></div>');
            } else {
                $('.load-more-jobs').show();
            }
        },

        // Clear search
        clearSearch: function() {
            var jobsContainer = $('.crelate-jobs-grid');
            jobsContainer.addClass('loading');
            
            // Reload original content
            location.reload();
        },

        // Get current filters
        getCurrentFilters: function() {
            var filters = {};
            $('#job-filters-form select').each(function() {
                var name = $(this).attr('name');
                var value = $(this).val();
                if (value) {
                    filters[name] = value;
                }
            });
            return filters;
        },

        // Update URL with search parameter
        updateURL: function(param, value) {
            var url = new URL(window.location);
            if (value) {
                url.searchParams.set(param, value);
            } else {
                url.searchParams.delete(param);
            }
            window.history.replaceState({}, '', url);
        },

        // Update URL from form
        updateURLFromForm: function(form) {
            var url = new URL(window.location);
            var formData = new FormData(form[0]);
            
            for (var pair of formData.entries()) {
                if (pair[1]) {
                    url.searchParams.set(pair[0], pair[1]);
                } else {
                    url.searchParams.delete(pair[0]);
                }
            }
            
            window.history.replaceState({}, '', url);
        },

        // Show error message
        showError: function(message) {
            var errorDiv = $('<div class="crelate-error">' + message + '</div>');
            $('.crelate-jobs-results').prepend(errorDiv);
            
            setTimeout(function() {
                errorDiv.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },

        // Utility function to debounce
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
    window.CrelateJobBoard = CrelateJobBoard;

})(jQuery);

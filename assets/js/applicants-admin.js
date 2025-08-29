jQuery(document).ready(function($) {
    
    // Handle applicant status updates
    $('.applicant-status').on('change', function() {
        var $select = $(this);
        var applicantId = $select.data('applicant-id');
        var newStatus = $select.val();
        var $originalValue = $select.val();
        
        // Show loading state
        $select.prop('disabled', true);
        
        $.ajax({
            url: crelateApplicants.ajax_url,
            type: 'POST',
            data: {
                action: 'crelate_update_applicant_status',
                applicant_id: applicantId,
                status: newStatus,
                nonce: crelateApplicants.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    showNotice(crelateApplicants.strings.status_updated, 'success');
                } else {
                    // Revert to original value
                    $select.val($originalValue);
                    showNotice(response.data.message || crelateApplicants.strings.error, 'error');
                }
            },
            error: function() {
                // Revert to original value
                $select.val($originalValue);
                showNotice(crelateApplicants.strings.error, 'error');
            },
            complete: function() {
                // Re-enable select
                $select.prop('disabled', false);
            }
        });
    });
    
    // Handle search form submission
    $('#applicants-search-form').on('submit', function(e) {
        var $form = $(this);
        var searchTerm = $form.find('input[name="search"]').val().trim();
        
        // Don't submit if search is empty
        if (searchTerm === '') {
            e.preventDefault();
            return false;
        }
    });
    
    // Handle filter form submission
    $('#applicants-filter-form').on('submit', function(e) {
        var $form = $(this);
        var jobId = $form.find('select[name="job_id"]').val();
        var status = $form.find('select[name="status"]').val();
        
        // Don't submit if no filters are selected
        if (jobId === '' && status === '') {
            e.preventDefault();
            return false;
        }
    });
    
    // Handle bulk actions
    $('#doaction, #doaction2').on('click', function(e) {
        var action = $(this).prev('select').val();
        
        if (action === '') {
            e.preventDefault();
            alert('Please select an action.');
            return false;
        }
        
        var checkedBoxes = $('input[name="applicant_ids[]"]:checked');
        
        if (checkedBoxes.length === 0) {
            e.preventDefault();
            alert('Please select at least one applicant.');
            return false;
        }
        
        if (action === 'delete' && !confirm(crelateApplicants.strings.confirm_delete)) {
            e.preventDefault();
            return false;
        }
    });
    
    // Handle select all checkbox
    $('#cb-select-all-1, #cb-select-all-2').on('change', function() {
        var isChecked = $(this).prop('checked');
        $('input[name="applicant_ids[]"]').prop('checked', isChecked);
    });
    
    // Handle individual checkboxes
    $('input[name="applicant_ids[]"]').on('change', function() {
        var totalCheckboxes = $('input[name="applicant_ids[]"]').length;
        var checkedCheckboxes = $('input[name="applicant_ids[]"]:checked').length;
        
        // Update select all checkboxes
        if (checkedCheckboxes === 0) {
            $('#cb-select-all-1, #cb-select-all-2').prop('checked', false).prop('indeterminate', false);
        } else if (checkedCheckboxes === totalCheckboxes) {
            $('#cb-select-all-1, #cb-select-all-2').prop('checked', true).prop('indeterminate', false);
        } else {
            $('#cb-select-all-1, #cb-select-all-2').prop('checked', false).prop('indeterminate', true);
        }
    });
    
    // Handle date range picker
    if ($.fn.datepicker) {
        $('.date-range-picker').datepicker({
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true,
            yearRange: '-10:+0'
        });
    }
    
    // Handle export functionality
    $('.export-applicants').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var originalText = $button.text();
        
        // Show loading state
        $button.text('Exporting...').prop('disabled', true);
        
        // Get current filters
        var filters = {
            search: $('input[name="search"]').val(),
            job_id: $('select[name="job_id"]').val(),
            status: $('select[name="status"]').val(),
            date_from: $('input[name="date_from"]').val(),
            date_to: $('input[name="date_to"]').val()
        };
        
        // Create form and submit
        var $form = $('<form>', {
            method: 'POST',
            action: crelateApplicants.ajax_url,
            target: '_blank'
        });
        
        $form.append($('<input>', {
            type: 'hidden',
            name: 'action',
            value: 'crelate_export_applicants'
        }));
        
        $form.append($('<input>', {
            type: 'hidden',
            name: 'nonce',
            value: crelateApplicants.nonce
        }));
        
        $.each(filters, function(key, value) {
            if (value) {
                $form.append($('<input>', {
                    type: 'hidden',
                    name: key,
                    value: value
                }));
            }
        });
        
        $('body').append($form);
        $form.submit();
        $form.remove();
        
        // Reset button
        setTimeout(function() {
            $button.text(originalText).prop('disabled', false);
        }, 2000);
    });
    
    // Handle applicant detail view enhancements
    if ($('.applicant-details').length) {
        // Add copy to clipboard functionality
        $('.copy-to-clipboard').on('click', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var textToCopy = $button.data('clipboard-text');
            
            if (navigator.clipboard && window.isSecureContext) {
                // Use modern clipboard API
                navigator.clipboard.writeText(textToCopy).then(function() {
                    showNotice('Copied to clipboard!', 'success');
                }).catch(function() {
                    fallbackCopyTextToClipboard(textToCopy);
                });
            } else {
                // Fallback for older browsers
                fallbackCopyTextToClipboard(textToCopy);
            }
        });
        
        // Add email template functionality
        $('.email-template').on('click', function(e) {
            e.preventDefault();
            
            var template = $(this).data('template');
            var applicantEmail = $('.applicant-email').text().trim();
            var applicantName = $('.applicant-name').text().trim();
            
            var subject = '';
            var body = '';
            
            switch (template) {
                case 'interview':
                    subject = 'Interview Invitation - ' + $('.job-title').text().trim();
                    body = 'Dear ' + applicantName + ',\n\nThank you for your application for the ' + $('.job-title').text().trim() + ' position. We would like to invite you for an interview.\n\nPlease let us know your availability.\n\nBest regards,\n[Your Name]';
                    break;
                case 'rejection':
                    subject = 'Application Update - ' + $('.job-title').text().trim();
                    body = 'Dear ' + applicantName + ',\n\nThank you for your interest in the ' + $('.job-title').text().trim() + ' position and for taking the time to apply.\n\nAfter careful consideration, we regret to inform you that we have decided to move forward with other candidates.\n\nWe wish you the best in your future endeavors.\n\nBest regards,\n[Your Name]';
                    break;
                case 'followup':
                    subject = 'Application Follow-up - ' + $('.job-title').text().trim();
                    body = 'Dear ' + applicantName + ',\n\nThank you for your application for the ' + $('.job-title').text().trim() + ' position. We are currently reviewing applications and will be in touch soon.\n\nBest regards,\n[Your Name]';
                    break;
            }
            
            // Open email client
            var mailtoLink = 'mailto:' + applicantEmail + '?subject=' + encodeURIComponent(subject) + '&body=' + encodeURIComponent(body);
            window.open(mailtoLink);
        });
    }
    
    // Helper function to show notices
    function showNotice(message, type) {
        var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        $('.wrap h1').after($notice);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    // Fallback copy function for older browsers
    function fallbackCopyTextToClipboard(text) {
        var textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.top = '0';
        textArea.style.left = '0';
        textArea.style.position = 'fixed';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            var successful = document.execCommand('copy');
            if (successful) {
                showNotice('Copied to clipboard!', 'success');
            } else {
                showNotice('Failed to copy to clipboard', 'error');
            }
        } catch (err) {
            showNotice('Failed to copy to clipboard', 'error');
        }
        
        document.body.removeChild(textArea);
    }
    
    // Handle responsive table
    function handleResponsiveTable() {
        if ($(window).width() < 768) {
            $('.wp-list-table').addClass('responsive');
        } else {
            $('.wp-list-table').removeClass('responsive');
        }
    }
    
    // Call on load and resize
    handleResponsiveTable();
    $(window).on('resize', handleResponsiveTable);
    
    // Handle keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Ctrl/Cmd + F to focus search
        if ((e.ctrlKey || e.metaKey) && e.keyCode === 70) {
            e.preventDefault();
            $('input[name="search"]').focus();
        }
        
        // Ctrl/Cmd + E to export
        if ((e.ctrlKey || e.metaKey) && e.keyCode === 69) {
            e.preventDefault();
            $('.export-applicants').click();
        }
    });
    
    // Handle tooltips
    $('[data-tooltip]').on('mouseenter', function() {
        var $element = $(this);
        var tooltipText = $element.data('tooltip');
        
        var $tooltip = $('<div class="crelate-tooltip">' + tooltipText + '</div>');
        $('body').append($tooltip);
        
        var elementOffset = $element.offset();
        var elementWidth = $element.outerWidth();
        var elementHeight = $element.outerHeight();
        var tooltipWidth = $tooltip.outerWidth();
        var tooltipHeight = $tooltip.outerHeight();
        
        var left = elementOffset.left + (elementWidth / 2) - (tooltipWidth / 2);
        var top = elementOffset.top - tooltipHeight - 10;
        
        $tooltip.css({
            left: left + 'px',
            top: top + 'px'
        });
    }).on('mouseleave', function() {
        $('.crelate-tooltip').remove();
    });
    
    // Add CSS for tooltips
    if (!$('#crelate-tooltip-styles').length) {
        $('head').append('<style id="crelate-tooltip-styles">' +
            '.crelate-tooltip { ' +
            'position: absolute; ' +
            'background: #333; ' +
            'color: white; ' +
            'padding: 5px 10px; ' +
            'border-radius: 3px; ' +
            'font-size: 12px; ' +
            'z-index: 9999; ' +
            'pointer-events: none; ' +
            'white-space: nowrap; ' +
            '}' +
            '.crelate-tooltip:after { ' +
            'content: ""; ' +
            'position: absolute; ' +
            'top: 100%; ' +
            'left: 50%; ' +
            'margin-left: -5px; ' +
            'border: 5px solid transparent; ' +
            'border-top-color: #333; ' +
            '}' +
            '</style>');
    }
});



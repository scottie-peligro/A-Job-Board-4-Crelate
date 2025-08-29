/**
 * Crelate Debug JavaScript
 */

jQuery(document).ready(function($) {
    
    // Test submission form
    $('#crelate-test-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $button = $form.find('button[type="submit"]');
        var $result = $('#test-result');
        
        // Disable button and show loading
        $button.prop('disabled', true).text('Testing...');
        $result.removeClass('success-message error-message').text('');
        
        // Get form data
        var formData = {
            action: 'crelate_test_submission',
            nonce: crelateDebug.nonce,
            email: $('#test-email').val(),
            first_name: $('#test-first-name').val(),
            last_name: $('#test-last-name').val(),
            phone: $('#test-phone').val(),
            job_id: $('#test-job-id').val()
        };
        
        // Make AJAX request
        $.post(crelateDebug.ajaxUrl, formData, function(response) {
            if (response.success) {
                $result.addClass('success-message').text(response.data.message);
            } else {
                $result.addClass('error-message').text(response.data.message);
            }
        }).fail(function() {
            $result.addClass('error-message').text('Request failed. Please try again.');
        }).always(function() {
            $button.prop('disabled', false).text('Test Submission');
        });
    });
    
    // Download logs
    $('#download-logs').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        $button.prop('disabled', true).text('Downloading...');
        
        // Create form for download
        var $form = $('<form>', {
            method: 'POST',
            action: crelateDebug.ajaxUrl,
            target: '_blank'
        });
        
        $form.append($('<input>', {
            type: 'hidden',
            name: 'action',
            value: 'crelate_download_logs'
        }));
        
        $form.append($('<input>', {
            type: 'hidden',
            name: 'nonce',
            value: crelateDebug.nonce
        }));
        
        $('body').append($form);
        $form.submit();
        $form.remove();
        
        setTimeout(function() {
            $button.prop('disabled', false).text('Download Logs');
        }, 1000);
    });
    
    // Clear logs
    $('#clear-logs').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('Are you sure you want to clear all logs? This action cannot be undone.')) {
            return;
        }
        
        var $button = $(this);
        $button.prop('disabled', true).text('Clearing...');
        
        $.post(crelateDebug.ajaxUrl, {
            action: 'crelate_clear_logs',
            nonce: crelateDebug.nonce
        }, function(response) {
            if (response.success) {
                $('#logs-container').html('<p>Logs cleared successfully.</p>');
            } else {
                alert('Failed to clear logs: ' + response.data);
            }
        }).fail(function() {
            alert('Request failed. Please try again.');
        }).always(function() {
            $button.prop('disabled', false).text('Clear Logs');
        });
    });
    
    // Refresh logs
    $('#refresh-logs').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        $button.prop('disabled', true).text('Refreshing...');
        
        // Reload the page to refresh logs
        location.reload();
    });
    
    // Auto-refresh logs every 30 seconds
    setInterval(function() {
        // Only refresh if the page is visible
        if (!document.hidden) {
            $('#refresh-logs').click();
        }
    }, 30000);
    
    // Add some helpful tooltips
    $('.crelate-debug-section h2').each(function() {
        var $h2 = $(this);
        var text = $h2.text();
        
        if (text === 'API Status') {
            $h2.attr('title', 'Current status of the Crelate API connection and configuration');
        } else if (text === 'Test Submission') {
            $h2.attr('title', 'Test the Crelate API submission functionality with sample data');
        } else if (text === 'Recent Logs') {
            $h2.attr('title', 'Recent activity logs from the Crelate integration');
        } else if (text === 'Gravity Forms Integration') {
            $h2.attr('title', 'Statistics and status of Gravity Forms integration');
        } else if (text === 'System Information') {
            $h2.attr('title', 'System information and requirements');
        }
    });
    
    // Add keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Ctrl/Cmd + R to refresh logs
        if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
            e.preventDefault();
            $('#refresh-logs').click();
        }
        
        // Ctrl/Cmd + T to test submission
        if ((e.ctrlKey || e.metaKey) && e.key === 't') {
            e.preventDefault();
            $('#crelate-test-form').submit();
        }
    });
    
    // Show keyboard shortcuts help
    $('<div>', {
        class: 'crelate-shortcuts-help',
        html: '<small>Keyboard shortcuts: Ctrl+R (refresh), Ctrl+T (test submission)</small>',
        css: {
            position: 'fixed',
            bottom: '10px',
            right: '10px',
            background: '#f0f0f0',
            padding: '5px 10px',
            border: '1px solid #ccc',
            borderRadius: '3px',
            fontSize: '11px',
            opacity: '0.7'
        }
    }).appendTo('body');
    
    // Hide shortcuts help after 5 seconds
    setTimeout(function() {
        $('.crelate-shortcuts-help').fadeOut();
    }, 5000);
    
    // Add copy to clipboard functionality for log entries
    $(document).on('click', '.log-entry', function() {
        var $entry = $(this);
        var text = $entry.text();
        
        // Create temporary textarea to copy text
        var $textarea = $('<textarea>').val(text).appendTo('body');
        $textarea.select();
        document.execCommand('copy');
        $textarea.remove();
        
        // Show feedback
        var $feedback = $('<span>', {
            text: 'Copied!',
            css: {
                position: 'absolute',
                background: '#0073aa',
                color: 'white',
                padding: '2px 6px',
                borderRadius: '3px',
                fontSize: '11px',
                zIndex: 1000
            }
        });
        
        $entry.css('position', 'relative').append($feedback);
        
        setTimeout(function() {
            $feedback.fadeOut(function() {
                $(this).remove();
            });
        }, 1000);
    });
    
    // Add hover effect for log entries
    $('.log-entry').hover(
        function() {
            $(this).css('cursor', 'pointer');
        },
        function() {
            $(this).css('cursor', 'default');
        }
    );
    
    // Add search functionality for logs
    var $searchBox = $('<input>', {
        type: 'text',
        placeholder: 'Search logs...',
        css: {
            width: '100%',
            marginBottom: '10px',
            padding: '5px'
        }
    }).insertBefore('#logs-container');
    
    $searchBox.on('input', function() {
        var searchTerm = $(this).val().toLowerCase();
        
        $('.log-entry').each(function() {
            var $entry = $(this);
            var text = $entry.text().toLowerCase();
            
            if (text.indexOf(searchTerm) !== -1) {
                $entry.show();
            } else {
                $entry.hide();
            }
        });
    });
    
    // Add filter buttons for log levels
    var $filterButtons = $('<div>', {
        css: {
            marginBottom: '10px'
        }
    }).insertAfter($searchBox);
    
    var levels = ['ALL', 'ERROR', 'SUCCESS', 'WARNING', 'INFO'];
    
    levels.forEach(function(level) {
        $('<button>', {
            text: level,
            class: 'button',
            css: {
                marginRight: '5px'
            }
        }).appendTo($filterButtons).on('click', function() {
            var $button = $(this);
            var filterLevel = $button.text();
            
            // Update button states
            $filterButtons.find('button').removeClass('button-primary');
            $button.addClass('button-primary');
            
            // Filter logs
            $('.log-entry').each(function() {
                var $entry = $(this);
                var entryLevel = $entry.find('strong').text().match(/\[([^\]]+)\]/);
                
                if (filterLevel === 'ALL' || (entryLevel && entryLevel[1] === filterLevel)) {
                    $entry.show();
                } else {
                    $entry.hide();
                }
            });
        });
    });
    
    // Set ALL as default active filter
    $filterButtons.find('button').first().addClass('button-primary');
});

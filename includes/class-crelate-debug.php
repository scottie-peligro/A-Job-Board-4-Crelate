<?php
/**
 * Crelate Debug Admin Page
 * 
 * Simple debugging and testing functionality for Crelate integration.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crelate_Debug {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_debug_menu'));
        add_action('wp_ajax_crelate_test_submission', array($this, 'ajax_test_submission'));
    }
    
    /**
     * Add debug menu
     */
    public function add_debug_menu() {
        add_submenu_page(
            'crelate-job-board',
            __('Crelate Debug', 'crelate-job-board'),
            __('Debug', 'crelate-job-board'),
            'manage_options',
            'crelate-debug',
            array($this, 'debug_page')
        );
    }
    
    /**
     * Debug page content
     */
    public function debug_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        $api = new Crelate_API();
        $api_status = $api->test_connection();
        
        ?>
        <div class="wrap">
            <h1><?php _e('Crelate Debug', 'crelate-job-board'); ?></h1>
            
            <!-- API Status -->
            <div class="crelate-debug-section">
                <h2><?php _e('API Status', 'crelate-job-board'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php _e('API Endpoint', 'crelate-job-board'); ?></th>
                        <td><?php echo esc_html($api->api_endpoint ?? 'https://app.crelate.com/api/pub/v1'); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Portal ID', 'crelate-job-board'); ?></th>
                        <td><?php echo esc_html($api->portal_id ?: __('Not configured', 'crelate-job-board')); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('API Key', 'crelate-job-board'); ?></th>
                        <td>
                            <?php if (!empty($api->api_key)): ?>
                                <span class="dashicons dashicons-yes-alt" style="color: green;"></span>
                                <?php _e('Configured', 'crelate-job-board'); ?>
                            <?php else: ?>
                                <span class="dashicons dashicons-no-alt" style="color: red;"></span>
                                <?php _e('Not configured', 'crelate-job-board'); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Connection Test', 'crelate-job-board'); ?></th>
                        <td>
                            <?php if ($api_status['success']): ?>
                                <span class="dashicons dashicons-yes-alt" style="color: green;"></span>
                                <?php echo esc_html($api_status['message']); ?>
                            <?php else: ?>
                                <span class="dashicons dashicons-no-alt" style="color: red;"></span>
                                <?php echo esc_html($api_status['message']); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Test Submission -->
            <div class="crelate-debug-section">
                <h2><?php _e('Test Submission', 'crelate-job-board'); ?></h2>
                <form id="crelate-test-form">
                    <table class="form-table">
                        <tr>
                            <th><?php _e('Email', 'crelate-job-board'); ?></th>
                            <td>
                                <input type="email" id="test-email" name="email" value="test@example.com" required>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('First Name', 'crelate-job-board'); ?></th>
                            <td>
                                <input type="text" id="test-first-name" name="first_name" value="Test" required>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Last Name', 'crelate-job-board'); ?></th>
                            <td>
                                <input type="text" id="test-last-name" name="last_name" value="User" required>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Phone', 'crelate-job-board'); ?></th>
                            <td>
                                <input type="text" id="test-phone" name="phone" value="555-123-4567">
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Job ID (optional)', 'crelate-job-board'); ?></th>
                            <td>
                                <input type="text" id="test-job-id" name="job_id" placeholder="Enter job ID to link">
                            </td>
                        </tr>
                    </table>
                    <p>
                        <button type="submit" class="button button-primary">
                            <?php _e('Test Submission', 'crelate-job-board'); ?>
                        </button>
                        <span id="test-result"></span>
                    </p>
                </form>
            </div>
            
            <!-- System Information -->
            <div class="crelate-debug-section">
                <h2><?php _e('System Information', 'crelate-job-board'); ?></h2>
                <?php $this->display_system_info(); ?>
            </div>
        </div>
        
        <style>
            .crelate-debug-section {
                background: #fff;
                border: 1px solid #ccd0d4;
                margin: 20px 0;
                padding: 20px;
            }
            .crelate-debug-section h2 {
                margin-top: 0;
                border-bottom: 1px solid #eee;
                padding-bottom: 10px;
            }
            #test-result {
                margin-left: 10px;
                font-weight: bold;
            }
            .success-message {
                color: green;
            }
            .error-message {
                color: red;
            }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('#crelate-test-form').on('submit', function(e) {
                e.preventDefault();
                
                var formData = {
                    action: 'crelate_test_submission',
                    nonce: '<?php echo wp_create_nonce('crelate_debug_nonce'); ?>',
                    email: $('#test-email').val(),
                    first_name: $('#test-first-name').val(),
                    last_name: $('#test-last-name').val(),
                    phone: $('#test-phone').val(),
                    job_id: $('#test-job-id').val()
                };
                
                $('#test-result').html('Testing...').removeClass('success-message error-message');
                
                $.post(ajaxurl, formData, function(response) {
                    if (response.success) {
                        $('#test-result').html(response.data.message).addClass('success-message');
                    } else {
                        $('#test-result').html(response.data.message).addClass('error-message');
                    }
                }).fail(function() {
                    $('#test-result').html('Request failed').addClass('error-message');
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Display system information
     */
    private function display_system_info() {
        echo '<table class="form-table">';
        echo '<tr><th>' . __('WordPress Version', 'crelate-job-board') . '</th>';
        echo '<td>' . get_bloginfo('version') . '</td></tr>';
        echo '<tr><th>' . __('PHP Version', 'crelate-job-board') . '</th>';
        echo '<td>' . phpversion() . '</td></tr>';
        echo '<tr><th>' . __('Plugin Version', 'crelate-job-board') . '</th>';
        echo '<td>' . CRELATE_JOB_BOARD_VERSION . '</td></tr>';
        echo '<tr><th>' . __('cURL Available', 'crelate-job-board') . '</th>';
        echo '<td>' . (function_exists('curl_init') ? __('Yes', 'crelate-job-board') : __('No', 'crelate-job-board')) . '</td></tr>';
        echo '<tr><th>' . __('Gravity Forms Active', 'crelate-job-board') . '</th>';
        echo '<td>' . (class_exists('GFAPI') ? __('Yes', 'crelate-job-board') : __('No', 'crelate-job-board')) . '</td></tr>';
        echo '</table>';
    }
    
    /**
     * AJAX test submission
     */
    public function ajax_test_submission() {
        check_ajax_referer('crelate_debug_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions'));
        }
        
        $email = sanitize_email($_POST['email']);
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $phone = sanitize_text_field($_POST['phone']);
        $job_id = sanitize_text_field($_POST['job_id']);
        
        $test_data = array(
            'firstName' => $first_name,
            'lastName' => $last_name,
            'email' => $email,
            'phone' => $phone
        );
        
        if (!empty($job_id)) {
            $test_data['job_id'] = $job_id;
        }
        
        try {
            $api = new Crelate_API();
            $result = $api->submit_job_application($job_id ?: 'test', $test_data);
            
            if ($result['success']) {
                wp_send_json_success(array(
                    'message' => 'Test submission successful!'
                ));
            } else {
                wp_send_json_error(array(
                    'message' => 'Test submission failed: ' . $result['message']
                ));
            }
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => 'Test submission failed: ' . $e->getMessage()
            ));
        }
    }
}

<?php
/**
 * Crelate WP-CLI Commands
 * 
 * Provides WP-CLI commands for testing and debugging Crelate integration.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Only load WP-CLI commands if WP-CLI is available
if (defined('WP_CLI') && WP_CLI) {
    
    /**
     * Crelate WP-CLI Commands
     */
    class Crelate_CLI {
        
        /**
         * Test Crelate API submission
         *
         * ## OPTIONS
         *
         * --email=<email>
         * : Email address for test submission
         *
         * --resume=<file>
         * : Path to resume file (optional)
         *
         * --job-id=<id>
         * : Job ID to link (optional)
         *
         * ## EXAMPLES
         *
         *     wp crelate:test --email=test@example.com
         *     wp crelate:test --email=test@example.com --resume=/path/to/resume.pdf
         *     wp crelate:test --email=test@example.com --job-id=12345
         *
         * @param array $args
         * @param array $assoc_args
         */
        public function test($args, $assoc_args) {
            WP_CLI::log('Testing Crelate API submission...');
            
            // Validate required parameters
            if (empty($assoc_args['email'])) {
                WP_CLI::error('Email is required. Use --email=test@example.com');
            }
            
            $email = sanitize_email($assoc_args['email']);
            if (!is_email($email)) {
                WP_CLI::error('Invalid email address: ' . $assoc_args['email']);
            }
            
            // Prepare test data
            $test_data = array(
                'firstName' => 'Test',
                'lastName' => 'User',
                'email' => $email,
                'phone' => '555-123-4567'
            );
            
            // Add resume if provided
            if (!empty($assoc_args['resume'])) {
                $resume_path = $assoc_args['resume'];
                if (!file_exists($resume_path)) {
                    WP_CLI::error('Resume file not found: ' . $resume_path);
                }
                $test_data['resume'] = $resume_path;
            }
            
            // Add job ID if provided
            if (!empty($assoc_args['job-id'])) {
                $test_data['job_id'] = $assoc_args['job-id'];
            }
            
            try {
                // Test submission
                $submit_service = new Crelate_SubmitService();
                $result = $submit_service->submit_application($test_data, 'cli-test');
                
                if ($result['success']) {
                    WP_CLI::success('Test submission successful!');
                    WP_CLI::log('Crelate ID: ' . $result['crelate_id']);
                    
                    if (!empty($result['warnings'])) {
                        WP_CLI::warning('Warnings: ' . implode(', ', $result['warnings']));
                    }
                } else {
                    WP_CLI::error('Test submission failed: ' . implode(', ', $result['errors']));
                }
                
            } catch (Exception $e) {
                WP_CLI::error('Exception: ' . $e->getMessage());
            }
        }
        
        /**
         * Test network connectivity to Crelate API
         *
         * ## EXAMPLES
         *
         *     wp crelate:net-test
         *
         * @param array $args
         * @param array $assoc_args
         */
        public function net_test($args, $assoc_args) {
            WP_CLI::log('Testing network connectivity to Crelate API...');
            
            $api = new Crelate_API();
            $settings = get_option('crelate_job_board_settings');
            $api_endpoint = !empty($settings['api_endpoint']) ? $settings['api_endpoint'] : 'https://app.crelate.com/api/pub/v1';
            
            // Parse API endpoint
            $parsed_url = parse_url($api_endpoint);
            $host = $parsed_url['host'];
            $port = isset($parsed_url['port']) ? $parsed_url['port'] : 443;
            
            WP_CLI::log('API Endpoint: ' . $api_endpoint);
            WP_CLI::log('Host: ' . $host);
            WP_CLI::log('Port: ' . $port);
            
            // Test DNS resolution
            WP_CLI::log('');
            WP_CLI::log('1. Testing DNS resolution...');
            
            $ip = gethostbyname($host);
            if ($ip === $host) {
                WP_CLI::error('DNS resolution failed for ' . $host);
                return;
            } else {
                WP_CLI::success('DNS resolution successful: ' . $host . ' -> ' . $ip);
            }
            
            // Test port connectivity
            WP_CLI::log('');
            WP_CLI::log('2. Testing port connectivity...');
            
            $connection = @fsockopen($host, $port, $errno, $errstr, 10);
            if ($connection) {
                WP_CLI::success('Port ' . $port . ' is reachable');
                fclose($connection);
            } else {
                WP_CLI::error('Port ' . $port . ' is not reachable: ' . $errstr . ' (' . $errno . ')');
            }
            
            // Test HTTPS connection
            WP_CLI::log('');
            WP_CLI::log('3. Testing HTTPS connection...');
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $api_endpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Crelate-WP/1.0.3');
            
            $start_time = microtime(true);
            $response = curl_exec($ch);
            $end_time = microtime(true);
            
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $total_time = round(($end_time - $start_time) * 1000, 2);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                WP_CLI::error('HTTPS connection failed: ' . $error);
            } else {
                WP_CLI::success('HTTPS connection successful');
                WP_CLI::log('Response time: ' . $total_time . 'ms');
                WP_CLI::log('HTTP status: ' . $http_code);
            }
            
            // Test API authentication
            WP_CLI::log('');
            WP_CLI::log('4. Testing API authentication...');
            
            if (empty($api->api_key)) {
                WP_CLI::warning('No API key configured');
            } else {
                $auth_test = $api->test_connection();
                if ($auth_test['success']) {
                    WP_CLI::success('API authentication successful');
                } else {
                    WP_CLI::error('API authentication failed: ' . $auth_test['message']);
                }
            }
            
            // Network diagnostics
            WP_CLI::log('');
            WP_CLI::log('5. Network diagnostics...');
            
            // Check for proxy settings
            $proxy_vars = array('HTTP_PROXY', 'HTTPS_PROXY', 'http_proxy', 'https_proxy');
            $proxy_found = false;
            
            foreach ($proxy_vars as $var) {
                if (!empty($_SERVER[$var])) {
                    WP_CLI::log('Proxy detected: ' . $var . ' = ' . $_SERVER[$var]);
                    $proxy_found = true;
                }
            }
            
            if (!$proxy_found) {
                WP_CLI::log('No proxy detected');
            }
            
            // Check firewall/security settings
            WP_CLI::log('');
            WP_CLI::log('6. Security/firewall check...');
            
            // Test common blocked ports
            $common_ports = array(80, 443, 8080, 8443);
            foreach ($common_ports as $test_port) {
                $test_connection = @fsockopen($host, $test_port, $errno, $errstr, 5);
                if ($test_connection) {
                    WP_CLI::log('Port ' . $test_port . ': Open');
                    fclose($test_connection);
                } else {
                    WP_CLI::log('Port ' . $test_port . ': Closed/Blocked');
                }
            }
            
            WP_CLI::log('');
            WP_CLI::success('Network test completed');
        }
        
        /**
         * Show Crelate configuration
         *
         * ## EXAMPLES
         *
         *     wp crelate:config
         *
         * @param array $args
         * @param array $assoc_args
         */
        public function config($args, $assoc_args) {
            WP_CLI::log('Crelate Configuration:');
            
            $api = new Crelate_API();
            $settings = get_option('crelate_job_board_settings', array());
            
            WP_CLI::log('API Endpoint: ' . ($api->api_endpoint ?? 'https://app.crelate.com/api/pub/v1'));
            WP_CLI::log('Portal ID: ' . ($api->portal_id ?: 'Not configured'));
            WP_CLI::log('API Key: ' . (!empty($api->api_key) ? 'Configured' : 'Not configured'));
            
            WP_CLI::log('');
            WP_CLI::log('Plugin Settings:');
            
            $display_settings = array(
                'import_frequency' => 'Import Frequency',
                'jobs_per_page' => 'Jobs Per Page',
                'enable_search' => 'Enable Search',
                'enable_filters' => 'Enable Filters',
                'track_applications' => 'Track Applications',
                'notification_email' => 'Notification Email'
            );
            
            foreach ($display_settings as $key => $label) {
                $value = isset($settings[$key]) ? $settings[$key] : 'Not set';
                WP_CLI::log($label . ': ' . $value);
            }
            
            // Show Gravity Forms integration status
            if (class_exists('GFAPI')) {
                WP_CLI::log('');
                WP_CLI::log('Gravity Forms Integration:');
                
                $enabled_forms = get_option('crelate_enabled_forms', array());
                WP_CLI::log('Enabled Forms: ' . count($enabled_forms));
                
                if (!empty($enabled_forms)) {
                    foreach ($enabled_forms as $form_id) {
                        $form = GFAPI::get_form($form_id);
                        if ($form) {
                            WP_CLI::log('  - Form ' . $form_id . ': ' . $form['title']);
                        }
                    }
                }
            }
        }
        
        /**
         * Show recent logs
         *
         * ## OPTIONS
         *
         * [--lines=<number>]
         * : Number of lines to show (default: 50)
         *
         * [--level=<level>]
         * : Filter by log level (ERROR, SUCCESS, WARNING, INFO)
         *
         * ## EXAMPLES
         *
         *     wp crelate:logs
         *     wp crelate:logs --lines=100
         *     wp crelate:logs --level=ERROR
         *
         * @param array $args
         * @param array $assoc_args
         */
        public function logs($args, $assoc_args) {
            $lines = isset($assoc_args['lines']) ? (int) $assoc_args['lines'] : 50;
            $level = isset($assoc_args['level']) ? strtoupper($assoc_args['level']) : null;
            
            $log_file = WP_CONTENT_DIR . '/uploads/crelate-logs/crelate.log';
            
            if (!file_exists($log_file)) {
                WP_CLI::warning('No log file found');
                return;
            }
            
            $log_lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            if (empty($log_lines)) {
                WP_CLI::log('Log file is empty');
                return;
            }
            
            // Reverse to show most recent first
            $log_lines = array_reverse($log_lines);
            
            // Apply line limit
            $log_lines = array_slice($log_lines, 0, $lines);
            
            // Apply level filter
            if ($level) {
                $log_lines = array_filter($log_lines, function($line) use ($level) {
                    return strpos($line, '[' . $level . ']') !== false;
                });
            }
            
            if (empty($log_lines)) {
                WP_CLI::log('No logs found matching criteria');
                return;
            }
            
            WP_CLI::log('Recent Crelate logs:');
            WP_CLI::log('');
            
            foreach ($log_lines as $line) {
                // Colorize log levels
                if (strpos($line, '[ERROR]') !== false || strpos($line, '[CRITICAL]') !== false) {
                    WP_CLI::log('%r' . $line . '%n');
                } elseif (strpos($line, '[SUCCESS]') !== false) {
                    WP_CLI::log('%g' . $line . '%n');
                } elseif (strpos($line, '[WARNING]') !== false) {
                    WP_CLI::log('%y' . $line . '%n');
                } else {
                    WP_CLI::log($line);
                }
            }
        }
        
        /**
         * Clear all logs
         *
         * ## EXAMPLES
         *
         *     wp crelate:clear-logs
         *
         * @param array $args
         * @param array $assoc_args
         */
        public function clear_logs($args, $assoc_args) {
            $log_file = WP_CONTENT_DIR . '/uploads/crelate-logs/crelate.log';
            
            if (!file_exists($log_file)) {
                WP_CLI::warning('No log file found');
                return;
            }
            
            if (unlink($log_file)) {
                WP_CLI::success('Logs cleared successfully');
            } else {
                WP_CLI::error('Failed to clear logs');
            }
        }
        
        /**
         * Test Gravity Forms integration
         *
         * ## OPTIONS
         *
         * --form-id=<id>
         * : Form ID to test
         *
         * ## EXAMPLES
         *
         *     wp crelate:test-gf --form-id=1
         *
         * @param array $args
         * @param array $assoc_args
         */
        public function test_gf($args, $assoc_args) {
            if (!class_exists('GFAPI')) {
                WP_CLI::error('Gravity Forms is not active');
            }
            
            if (empty($assoc_args['form-id'])) {
                WP_CLI::error('Form ID is required. Use --form-id=1');
            }
            
            $form_id = (int) $assoc_args['form-id'];
            $form = GFAPI::get_form($form_id);
            
            if (!$form) {
                WP_CLI::error('Form not found with ID: ' . $form_id);
            }
            
            WP_CLI::log('Testing Gravity Forms integration for: ' . $form['title']);
            
            // Check if form has Crelate fields
            $has_crelate_fields = false;
            foreach ($form['fields'] as $field) {
                if (!empty($field->crelateFieldType)) {
                    $has_crelate_fields = true;
                    WP_CLI::log('Found Crelate field: ' . $field->label . ' -> ' . $field->crelateFieldType);
                }
            }
            
            if (!$has_crelate_fields) {
                WP_CLI::warning('No Crelate fields found in form');
            }
            
            // Check if form is enabled for Crelate
            $enabled_forms = get_option('crelate_enabled_forms', array());
            if (in_array($form_id, $enabled_forms)) {
                WP_CLI::success('Form is enabled for Crelate integration');
            } else {
                WP_CLI::warning('Form is not enabled for Crelate integration');
            }
            
            // Show submission statistics
            $stats = Crelate_Gravity_Forms::get_submission_stats($form_id);
            WP_CLI::log('');
            WP_CLI::log('Submission Statistics:');
            WP_CLI::log('  Total: ' . $stats['total_submissions']);
            WP_CLI::log('  Successful: ' . $stats['successful_submissions']);
            WP_CLI::log('  Failed: ' . $stats['failed_submissions']);
            
            if ($stats['last_submission']) {
                WP_CLI::log('  Last: ' . date('Y-m-d H:i:s', strtotime($stats['last_submission'])));
            }
        }
    }
    
    // Register WP-CLI commands
    WP_CLI::add_command('crelate', 'Crelate_CLI');
}

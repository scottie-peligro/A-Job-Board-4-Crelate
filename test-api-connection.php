<?php
/**
 * Test API Connection Script
 * Place this in your WordPress root directory and access via browser
 */

// Load WordPress
require_once('wp-load.php');

// Check if user is logged in and has admin privileges
if (!current_user_can('manage_options')) {
    die('Access denied. Admin privileges required.');
}

echo '<h1>Crelate API Connection Test</h1>';

// Get current settings
$settings = get_option('crelate_job_board_settings');
echo '<h2>Current Settings:</h2>';
echo '<ul>';
echo '<li>API Key: ' . (!empty($settings['api_key']) ? substr($settings['api_key'], 0, 4) . '****' : 'NOT SET') . '</li>';
echo '<li>API Endpoint: ' . (!empty($settings['api_endpoint']) ? $settings['api_endpoint'] : 'NOT SET') . '</li>';
echo '<li>Portal ID: ' . (!empty($settings['portal_id']) ? $settings['portal_id'] : 'NOT SET') . '</li>';
echo '</ul>';

// Test API connection
if (!empty($settings['api_key'])) {
    echo '<h2>Testing API Connection...</h2>';
    
    // Create API instance
    $api = new Crelate_API();
    
    // Test connection
    $result = $api->test_connection();
    
    echo '<h3>Connection Test Result:</h3>';
    echo '<p><strong>Success:</strong> ' . ($result['success'] ? 'Yes' : 'No') . '</p>';
    echo '<p><strong>Message:</strong> ' . esc_html($result['message']) . '</p>';
    
    if ($result['success']) {
        echo '<h3>Testing Job Import...</h3>';
        
        // Try to get a few jobs
        $jobs = $api->get_jobs(array('take' => 5));
        
        if ($jobs && is_array($jobs)) {
            echo '<p><strong>Success!</strong> Retrieved ' . count($jobs) . ' jobs from API.</p>';
            echo '<h4>Sample Job Data:</h4>';
            echo '<pre>' . esc_html(print_r(array_slice($jobs, 0, 1), true)) . '</pre>';
        } else {
            echo '<p><strong>Error:</strong> Could not retrieve jobs from API.</p>';
        }
    }
} else {
    echo '<p><strong>Error:</strong> API key is not configured. Please configure it in the admin settings.</p>';
}

echo '<hr>';
echo '<p><a href="' . admin_url('admin.php?page=crelate-job-board') . '">Go to Plugin Settings</a></p>';
?>

<?php
/**
 * Check Current API Settings
 * Run this script to verify current configuration
 */

// Load WordPress
require_once('../../../wp-load.php');

// Check if user is logged in and has admin privileges
if (!current_user_can('manage_options')) {
    die('Access denied. Admin privileges required.');
}

echo '<h1>Crelate API Settings Check</h1>';

// Get current settings
$settings = get_option('crelate_job_board_settings');

echo '<h2>Current Settings:</h2>';
echo '<ul>';
echo '<li><strong>API Key:</strong> ' . (!empty($settings['api_key']) ? substr($settings['api_key'], 0, 4) . '****' : '<span style="color: red;">NOT SET</span>') . '</li>';
echo '<li><strong>API Endpoint:</strong> ' . (!empty($settings['api_endpoint']) ? $settings['api_endpoint'] : '<span style="color: red;">NOT SET</span>') . '</li>';
echo '<li><strong>Portal ID:</strong> ' . (!empty($settings['portal_id']) ? $settings['portal_id'] : '<span style="color: red;">NOT SET</span>') . '</li>';
echo '</ul>';

echo '<h2>Configuration Analysis:</h2>';

// Check API Endpoint
if (!empty($settings['api_endpoint'])) {
    if ($settings['api_endpoint'] === 'https://app.crelate.com/api/pub/v1') {
        echo '<p style="color: green;">✓ <strong>API Endpoint:</strong> Correctly set to official Crelate API endpoint</p>';
    } else {
        echo '<p style="color: orange;">⚠ <strong>API Endpoint:</strong> Set to ' . $settings['api_endpoint'] . ' - should be https://app.crelate.com/api/pub/v1</p>';
    }
} else {
    echo '<p style="color: red;">✗ <strong>API Endpoint:</strong> Not set - required for API connection</p>';
}

// Check API Key
if (!empty($settings['api_key'])) {
    echo '<p style="color: green;">✓ <strong>API Key:</strong> Configured (first 4 chars: ' . substr($settings['api_key'], 0, 4) . ')</p>';
} else {
    echo '<p style="color: red;">✗ <strong>API Key:</strong> Not set - required for authentication</p>';
}

// Check Portal ID
if (!empty($settings['portal_id'])) {
    echo '<p style="color: green;">✓ <strong>Portal ID:</strong> Configured as "' . $settings['portal_id'] . '"</p>';
} else {
    echo '<p style="color: orange;">⚠ <strong>Portal ID:</strong> Not set - may be required for some features</p>';
}

echo '<h2>Official Crelate API Requirements:</h2>';
echo '<ul>';
echo '<li><strong>Base URL:</strong> https://app.crelate.com/api/pub/v1 ✓</li>';
echo '<li><strong>Authentication:</strong> Bearer token (API key in Authorization header) ✓</li>';
echo '<li><strong>Content-Type:</strong> application/json ✓</li>';
echo '<li><strong>Accept:</strong> application/json ✓</li>';
echo '</ul>';

echo '<h2>Current Implementation Check:</h2>';

// Check if API class is properly configured
if (class_exists('Crelate_API')) {
    $api = new Crelate_API();
    echo '<p style="color: green;">✓ <strong>API Class:</strong> Properly loaded</p>';
    
    // Check if API endpoint is private (as it should be)
    $reflection = new ReflectionClass($api);
    $properties = $reflection->getProperties(ReflectionProperty::IS_PRIVATE);
    $has_private_endpoint = false;
    foreach ($properties as $property) {
        if ($property->getName() === 'api_endpoint') {
            $has_private_endpoint = true;
            break;
        }
    }
    
    if ($has_private_endpoint) {
        echo '<p style="color: green;">✓ <strong>API Endpoint Property:</strong> Properly set as private</p>';
    } else {
        echo '<p style="color: orange;">⚠ <strong>API Endpoint Property:</strong> Should be private for security</p>';
    }
} else {
    echo '<p style="color: red;">✗ <strong>API Class:</strong> Not found</p>';
}

echo '<h2>Recommendations:</h2>';
echo '<ol>';
echo '<li>Ensure API key is obtained from Crelate Help Center: <a href="https://help.crelate.com/en/articles/4120535-find-and-manage-your-api-key" target="_blank">Find and Manage Your API Key</a></li>';
echo '<li>Verify API endpoint is set to: https://app.crelate.com/api/pub/v1</li>';
echo '<li>Set Portal ID if you have a specific portal/company slug</li>';
echo '<li>Test API connection using the test script</li>';
echo '</ol>';

echo '<p><a href="test-api-connection.php" style="background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px;">Test API Connection</a></p>';
?>

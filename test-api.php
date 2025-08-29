<?php
/**
 * Simple Crelate API Test
 * 
 * Clean, simple test to verify API connection and basic functionality.
 */

// Load WordPress
require_once('../../../wp-load.php');

// Check if we're in WordPress context
if (!defined('ABSPATH')) {
    echo '<h1>Error: WordPress not loaded</h1>';
    exit;
}

// Get settings
$settings = get_option('crelate_job_board_settings');
$api_key = !empty($settings['api_key']) ? $settings['api_key'] : '';
$portal_id = !empty($settings['portal_id']) ? $settings['portal_id'] : '';

echo '<!DOCTYPE html>';
echo '<html lang="en">';
echo '<head>';
echo '<meta charset="UTF-8">';
echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
echo '<title>Crelate API Test</title>';
echo '<style>';
echo 'body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }';
echo '.container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }';
echo '.test-section { margin: 20px 0; padding: 20px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #0073aa; }';
echo '.success { border-left-color: #28a745; }';
echo '.error { border-left-color: #dc3545; }';
echo '.warning { border-left-color: #ffc107; }';
echo '.code { background: #e9ecef; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 12px; margin: 10px 0; }';
echo '.button { display: inline-block; padding: 10px 20px; background: #0073aa; color: white; text-decoration: none; border-radius: 4px; margin: 5px; }';
echo '.button:hover { background: #005a87; }';
echo '</style>';
echo '</head>';
echo '<body>';

echo '<div class="container">';
echo '<h1>Crelate API Test</h1>';

// Display current settings
echo '<div class="test-section">';
echo '<h2>Current Settings</h2>';
echo '<p><strong>API Endpoint:</strong> https://app.crelate.com/api</p>';
echo '<p><strong>Portal ID:</strong> ' . esc_html($portal_id ?: 'Not set') . '</p>';
echo '<p><strong>API Key:</strong> ' . (!empty($api_key) ? substr($api_key, 0, 4) . '...' : 'Not set') . '</p>';
echo '</div>';

if (empty($api_key)) {
    echo '<div class="test-section error">';
    echo '<h2>Error: API Key Not Configured</h2>';
    echo '<p>Please configure your API key in WordPress Admin → Job Board → Settings</p>';
    echo '<a href="' . admin_url('admin.php?page=crelate-job-board') . '" class="button">Go to Settings</a>';
    echo '</div>';
    echo '</div>';
    echo '</body>';
    echo '</html>';
    exit;
}

// Test API connection
echo '<div class="test-section">';
echo '<h2>API Connection Test</h2>';

try {
    $api = new Crelate_API();
    $result = $api->test_connection();
    
    if ($result['success']) {
        echo '<div class="code success">';
        echo '✅ ' . esc_html($result['message']);
        echo '</div>';
    } else {
        echo '<div class="code error">';
        echo '❌ ' . esc_html($result['message']);
        echo '</div>';
    }
} catch (Exception $e) {
    echo '<div class="code error">';
    echo '❌ Exception: ' . esc_html($e->getMessage());
    echo '</div>';
}

echo '</div>';

// Test raw API response
echo '<div class="test-section">';
echo '<h2>Raw API Response Test</h2>';

try {
    $api = new Crelate_API();
    $jobs = $api->get_jobs(array('take' => 3)); // Get 3 jobs to see the structure
    
    echo '<div class="code">';
    if ($jobs !== false) {
        echo '<strong>Jobs Retrieved:</strong> ' . count($jobs) . '<br>';
        echo '<strong>Response Structure:</strong><br>';
        echo '<pre>' . esc_html(print_r($jobs, true)) . '</pre>';
    } else {
        echo '<strong>No jobs retrieved or API error</strong><br>';
    }
    echo '</div>';
} catch (Exception $e) {
    echo '<div class="code error">';
    echo '❌ Exception: ' . esc_html($e->getMessage());
    echo '</div>';
}

echo '</div>';

// Test job import
echo '<div class="test-section">';
echo '<h2>Job Import Test</h2>';

try {
    // Check if classes are loaded
    if (!class_exists('Crelate_API')) {
        throw new Exception('Crelate_API class not found');
    }
    
    // Test API response
    $api = new Crelate_API();
    $jobs = $api->get_jobs(array('take' => 3));
    
    echo '<div class="code">';
    if ($jobs !== false) {
        echo '<strong>Jobs Retrieved:</strong> ' . count($jobs) . '<br>';
        echo '<strong>Response Type:</strong> ' . gettype($jobs) . '<br>';
        
        if (is_array($jobs) && !empty($jobs)) {
            echo '<strong>First Job Keys:</strong> ' . implode(', ', array_keys($jobs[0])) . '<br>';
        }
    } else {
        echo '<strong>No jobs retrieved or API error</strong><br>';
    }
    echo '</div>';
    
    // Check if post type is registered
    if (!post_type_exists('crelate_job')) {
        echo '<div class="code warning">';
        echo '⚠️ Warning: Crelate job post type not registered. This might cause import issues.';
        echo '</div>';
    }
    
    // Now try the import
    $api = new Crelate_API();
    $result = $api->import_jobs(5); // Import 5 jobs for testing
    
    if ($result['success']) {
        echo '<div class="code success">';
        echo '✅ Import successful!<br>';
        echo 'Imported: ' . ($result['imported'] ?? 0) . ' jobs<br>';
        echo 'Updated: ' . ($result['updated'] ?? 0) . ' jobs<br>';
        echo 'Total processed: ' . ($result['total'] ?? 0) . ' jobs';
        if (!empty($result['errors'])) {
            echo '<br>Errors: ' . count($result['errors']);
            echo '<br><strong>Error Details:</strong><br>';
            foreach ($result['errors'] as $error) {
                echo '- ' . esc_html($error) . '<br>';
            }
        }
        echo '</div>';
    } else {
        echo '<div class="code error">';
        echo '❌ Import failed: ' . esc_html($result['message']);
        echo '</div>';
    }
} catch (Exception $e) {
    echo '<div class="code error">';
    echo '❌ Exception: ' . esc_html($e->getMessage());
    echo '<br><strong>Stack trace:</strong><br>';
    echo '<pre>' . esc_html($e->getTraceAsString()) . '</pre>';
    echo '</div>';
}

echo '</div>';

// Show current jobs
echo '<div class="test-section">';
echo '<h2>Current Jobs in WordPress</h2>';

$jobs = get_posts(array(
    'post_type' => 'crelate_job',
    'post_status' => 'publish',
    'numberposts' => 10,
    'orderby' => 'date',
    'order' => 'DESC'
));

if (empty($jobs)) {
    echo '<p>No jobs found in WordPress.</p>';
} else {
    echo '<ul>';
    foreach ($jobs as $job) {
        $crelate_id = get_post_meta($job->ID, '_crelate_job_id', true);
        echo '<li>' . esc_html($job->post_title) . ' (Crelate ID: ' . esc_html($crelate_id ?: 'None') . ')</li>';
    }
    echo '</ul>';
}

echo '</div>';

// Quick actions
echo '<div class="test-section">';
echo '<h2>Quick Actions</h2>';
echo '<a href="' . admin_url('admin.php?page=crelate-job-board') . '" class="button">Go to Settings</a>';
echo '<a href="' . admin_url('admin.php?page=crelate-debug') . '" class="button">View Debug Logs</a>';
echo '<a href="test-iframe-form.php" class="button">Test Iframe Form</a>';
echo '</div>';

echo '</div>';
echo '</body>';
echo '</html>';
?>


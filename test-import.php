<?php
/**
 * Test Job Import Script
 * 
 * This script tests the job import functionality using the updated API implementation.
 * Access this file directly in your browser to test the import.
 */

// Load WordPress
require_once('../../../wp-load.php');

// Check if user is logged in and has admin privileges
if (!current_user_can('manage_options')) {
    wp_die('You do not have sufficient permissions to access this page.');
}

// Set up the page
?>
<!DOCTYPE html>
<html>
<head>
    <title>Crelate Job Import Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background-color: #d4edda; border-color: #c3e6cb; color: #155724; }
        .error { background-color: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .warning { background-color: #fff3cd; border-color: #ffeaa7; color: #856404; }
        .code { background-color: #f8f9fa; padding: 10px; border-radius: 3px; font-family: monospace; }
        .button { background-color: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 3px; cursor: pointer; }
        .button:hover { background-color: #005a87; }
    </style>
</head>
<body>
    <h1>Crelate Job Import Test</h1>
    
    <?php
    // Test API connection
    echo '<div class="test-section">';
    echo '<h2>1. API Connection Test</h2>';
    
    try {
        $api = new Crelate_API();
        $result = $api->test_connection();
        
        if ($result['success']) {
            echo '<div class="success">';
            echo '✅ ' . esc_html($result['message']);
            echo '</div>';
        } else {
            echo '<div class="error">';
            echo '❌ ' . esc_html($result['message']);
            echo '</div>';
        }
    } catch (Exception $e) {
        echo '<div class="error">';
        echo '❌ Exception: ' . esc_html($e->getMessage());
        echo '</div>';
    }
    echo '</div>';
    
    // Test job retrieval
    echo '<div class="test-section">';
    echo '<h2>2. Job Retrieval Test</h2>';
    
    try {
        $api = new Crelate_API();
        $jobs = $api->get_jobs(array('take' => 5));
        
        if ($jobs !== false && is_array($jobs)) {
            echo '<div class="success">';
            echo '✅ Successfully retrieved ' . count($jobs) . ' jobs from API';
            echo '</div>';
            
            if (!empty($jobs)) {
                echo '<div class="code">';
                echo '<strong>First job sample:</strong><br>';
                echo '<pre>' . esc_html(print_r($jobs[0], true)) . '</pre>';
                echo '</div>';
            }
        } else {
            echo '<div class="error">';
            echo '❌ Failed to retrieve jobs from API';
            echo '</div>';
        }
    } catch (Exception $e) {
        echo '<div class="error">';
        echo '❌ Exception: ' . esc_html($e->getMessage());
        echo '</div>';
    }
    echo '</div>';
    
    // Test job import
    echo '<div class="test-section">';
    echo '<h2>3. Job Import Test</h2>';
    
    if (isset($_POST['test_import'])) {
        try {
            $api = new Crelate_API();
            $result = $api->import_jobs();
            
            if ($result['success']) {
                echo '<div class="success">';
                echo '✅ Import successful!<br>';
                echo 'Imported: ' . ($result['imported'] ?? 0) . ' jobs<br>';
                echo 'Updated: ' . ($result['updated'] ?? 0) . ' jobs<br>';
                echo 'Errors: ' . ($result['errors'] ?? 0) . ' jobs<br>';
                echo 'Total processed: ' . ($result['total'] ?? 0) . ' jobs';
                echo '</div>';
                
                if (!empty($result['errors'])) {
                    echo '<div class="warning">';
                    echo '<strong>Errors encountered:</strong><br>';
                    foreach ($result['errors'] as $error) {
                        echo '- ' . esc_html($error) . '<br>';
                    }
                    echo '</div>';
                }
            } else {
                echo '<div class="error">';
                echo '❌ Import failed: ' . esc_html($result['message']);
                echo '</div>';
            }
        } catch (Exception $e) {
            echo '<div class="error">';
            echo '❌ Exception: ' . esc_html($e->getMessage());
            echo '</div>';
        }
    } else {
        echo '<form method="post">';
        echo '<p>Click the button below to test job import:</p>';
        echo '<input type="submit" name="test_import" value="Test Job Import" class="button">';
        echo '</form>';
    }
    echo '</div>';
    
    // Show current job statistics
    echo '<div class="test-section">';
    echo '<h2>4. Current Job Statistics</h2>';
    
    try {
        $stats = $api->get_import_stats();
        echo '<div class="code">';
        echo '<strong>Total Jobs:</strong> ' . $stats['total_jobs'] . '<br>';
        echo '<strong>Published Jobs:</strong> ' . $stats['published_jobs'] . '<br>';
        echo '<strong>Draft Jobs:</strong> ' . $stats['draft_jobs'] . '<br>';
        
        if (!empty($stats['last_import'])) {
            echo '<strong>Last Import:</strong> ' . $stats['last_import']['timestamp'] . '<br>';
            echo '<strong>Last Import Results:</strong> ' . $stats['last_import']['imported'] . ' imported, ' . $stats['last_import']['updated'] . ' updated, ' . $stats['last_import']['errors'] . ' errors<br>';
        }
        echo '</div>';
    } catch (Exception $e) {
        echo '<div class="error">';
        echo '❌ Exception: ' . esc_html($e->getMessage());
        echo '</div>';
    }
    echo '</div>';
    ?>
    
    <div class="test-section">
        <h2>5. Next Steps</h2>
        <p>If all tests pass, you can:</p>
        <ul>
            <li>Go to the WordPress admin and configure the plugin settings</li>
            <li>Set up automatic job imports via cron</li>
            <li>Create job board pages using the shortcodes</li>
        </ul>
        <p><a href="<?php echo admin_url('admin.php?page=crelate-job-board'); ?>" class="button">Go to Plugin Settings</a></p>
    </div>
</body>
</html>



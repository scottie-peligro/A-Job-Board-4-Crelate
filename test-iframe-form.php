<?php
/**
 * Test file for iframe application form
 * 
 * This file tests the new iframe-based application form functionality.
 * Access this file directly in your browser to test the iframe implementation.
 */

// Load WordPress
require_once('../../../wp-load.php');

// Check if user is logged in and has admin privileges
if (!current_user_can('manage_options')) {
    die('Access denied. Admin privileges required.');
}

// Get settings
$settings = get_option('crelate_job_board_settings');
$portal_id = !empty($settings['portal_id']) ? $settings['portal_id'] : '';
$api_key = !empty($settings['api_key']) ? substr($settings['api_key'], 0, 4) . '****' : 'NOT SET';

// Get a sample job
$sample_job = get_posts(array(
    'post_type' => 'crelate_job',
    'post_status' => 'publish',
    'numberposts' => 1
));

$sample_job_id = '';
$sample_crelate_job_id = '';
if (!empty($sample_job)) {
    $sample_job_id = $sample_job[0]->ID;
    $sample_crelate_job_id = get_post_meta($sample_job_id, '_job_crelate_id', true);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Iframe Application Form</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-container { max-width: 1200px; margin: 0 auto; }
        .test-section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .iframe-container { border: 2px solid #ddd; border-radius: 8px; overflow: hidden; margin: 20px 0; }
        .iframe-container iframe { display: block; }
        .status { padding: 10px; border-radius: 4px; margin: 10px 0; }
        .status.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status.warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .status.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .button { display: inline-block; padding: 10px 20px; background: #0073aa; color: white; text-decoration: none; border-radius: 3px; margin: 5px; }
        .button:hover { background: #005a87; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>Test: Iframe Application Form</h1>
        
        <div class="test-section">
            <h2>Configuration Status</h2>
            
            <div class="status <?php echo !empty($portal_id) ? 'success' : 'error'; ?>">
                <strong>Portal ID:</strong> <?php echo !empty($portal_id) ? $portal_id : 'NOT SET'; ?>
            </div>
            
            <div class="status <?php echo !empty($settings['api_key']) ? 'success' : 'error'; ?>">
                <strong>API Key:</strong> <?php echo $api_key; ?>
            </div>
            
            <div class="status <?php echo !empty($sample_job_id) ? 'success' : 'warning'; ?>">
                <strong>Sample Job ID:</strong> <?php echo !empty($sample_job_id) ? $sample_job_id : 'No jobs found'; ?>
            </div>
            
            <div class="status <?php echo !empty($sample_crelate_job_id) ? 'success' : 'warning'; ?>">
                <strong>Sample Crelate Job ID:</strong> <?php echo !empty($sample_crelate_job_id) ? $sample_crelate_job_id : 'No Crelate job ID found'; ?>
            </div>
        </div>

        <?php if (!empty($portal_id) && !empty($sample_crelate_job_id)): ?>
        <div class="test-section">
            <h2>Modal Iframe Application Form Test</h2>
            
            <h3>Application Form (Modal Iframe)</h3>
            <p>This will show an "Apply Now" button that opens the Crelate application form in a modal popup:</p>
            
            <?php echo do_shortcode('[crelate_job_apply_iframe job_id="' . esc_attr($sample_job_id) . '"]'); ?>
            
            <p><strong>Direct Link:</strong> 
                <a href="https://jobs.crelate.com/portal/<?php echo esc_attr($portal_id); ?>/apply/<?php echo esc_attr($sample_crelate_job_id); ?>" target="_blank" rel="noopener noreferrer">
                    Open Application Form in New Tab
                </a>
            </p>
        </div>
        <?php else: ?>
        <div class="test-section">
            <div class="status error">
                <h3>Configuration Required</h3>
                <p>This job does not have a Crelate job ID associated with it. The iframe form cannot be displayed.</p>
                <p>Please ensure:</p>
                <ul>
                    <li>Portal ID is configured in the admin settings</li>
                    <li>Jobs have been imported from Crelate with proper job IDs</li>
                    <li>API connection is working properly</li>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <div class="test-section">
            <h2>Shortcode Usage Examples</h2>
            
            <h3>Iframe Form Shortcode</h3>
            <pre>[crelate_job_apply_iframe job_id="<?php echo esc_attr($sample_job_id); ?>" height="600px" show_title="true"]</pre>
            
            <h3>Custom Form Shortcode</h3>
            <pre>[crelate_job_apply job_id="<?php echo esc_attr($sample_job_id); ?>"]</pre>
            
            <h3>Dynamic Form (Based on Settings)</h3>
            <p>In templates, use this PHP code to automatically choose the form type based on admin settings:</p>
            <pre>&lt;?php 
$settings = get_option('crelate_job_board_settings');
$application_form_type = !empty($settings['application_form_type']) ? $settings['application_form_type'] : 'iframe';

if ($application_form_type === 'iframe') {
    echo do_shortcode('[crelate_job_apply_iframe job_id="' . get_the_ID() . '" height="600px" show_title="false"]');
} else {
    echo do_shortcode('[crelate_job_apply job_id="' . get_the_ID() . '"]');
}
?&gt;</pre>
        </div>

        <div class="test-section">
            <h2>Testing Checklist</h2>
            <ul>
                <li>✅ Check if the iframe loads the Crelate application form correctly</li>
                <li>✅ Verify that the form is functional and can be submitted</li>
                <li>✅ Test on different browsers and devices</li>
                <li>✅ Verify that the fallback link works if iframe fails</li>
                <li>✅ Check browser console for any JavaScript errors</li>
            </ul>
            
            <h3>Troubleshooting</h3>
            <ul>
                <li><strong>Iframe not loading:</strong> Check if the Crelate job ID is correct and the job exists in Crelate</li>
                <li><strong>Portal ID issues:</strong> Verify the portal ID in your Crelate settings</li>
                <li><strong>Cross-origin errors:</strong> This is normal for iframes, but check browser console for specific errors</li>
                <li><strong>Form not submitting:</strong> Ensure the job is active in Crelate</li>
            </ul>
        </div>

        <div class="test-section">
            <h2>Navigation</h2>
            <a href="test-api.php" class="button">Test API Connection</a>
            <a href="check-settings.php" class="button">Check Settings</a>
            <a href="test-security.php" class="button">Test Security</a>
            <a href="<?php echo admin_url('admin.php?page=crelate-job-board'); ?>" class="button">Admin Settings</a>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        // Check if iframe loads successfully
        var iframe = $('.iframe-container iframe');
        
        iframe.on('load', function() {
            console.log('Crelate application iframe loaded successfully');
        }).on('error', function() {
            console.log('Crelate application iframe failed to load');
        });
    });
    </script>
</body>
</html>

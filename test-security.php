<?php
/**
 * Test Security Features
 * Run this script to verify the API security lockdown is working
 */

// Load WordPress
require_once('../../../wp-load.php');

// Check if user is logged in and has admin privileges
if (!current_user_can('manage_options')) {
    die('Access denied. Admin privileges required.');
}

echo '<h1>Crelate API Security Test</h1>';

// Get current security status
$settings_locked = get_option('crelate_api_settings_locked', false);
$authorized_users = get_option('crelate_authorized_users', array());
$security_log = get_option('crelate_security_log', array());

echo '<h2>Current Security Status:</h2>';
echo '<ul>';
echo '<li><strong>Settings Locked:</strong> ' . ($settings_locked ? 'YES' : 'NO') . '</li>';
echo '<li><strong>Authorized Users:</strong> ' . count($authorized_users) . ' users</li>';
echo '<li><strong>Security Log Entries:</strong> ' . count($security_log) . ' entries</li>';
echo '<li><strong>Current User ID:</strong> ' . get_current_user_id() . '</li>';
echo '<li><strong>Current User Login:</strong> ' . wp_get_current_user()->user_login . '</li>';
echo '<li><strong>Is Super Admin:</strong> ' . (is_super_admin() ? 'YES' : 'NO') . '</li>';
echo '</ul>';

// Test authorization
$admin = new Crelate_Admin();
$reflection = new ReflectionClass($admin);
$is_authorized_method = $reflection->getMethod('is_authorized_user');
$is_authorized_method->setAccessible(true);
$is_authorized = $is_authorized_method->invoke($admin);

echo '<h2>Authorization Test:</h2>';
echo '<p><strong>Current User Authorized:</strong> ' . ($is_authorized ? 'YES' : 'NO') . '</p>';

// Show recent security events
echo '<h2>Recent Security Events:</h2>';
if (empty($security_log)) {
    echo '<p><em>No security events logged yet.</em></p>';
} else {
    echo '<div style="background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; max-height: 300px; overflow-y: auto;">';
    foreach (array_reverse(array_slice($security_log, -10)) as $entry) {
        echo '<div style="border-bottom: 1px solid #eee; padding: 8px 0;">';
        echo '<strong>' . esc_html($entry['timestamp']) . '</strong><br>';
        echo esc_html($entry['message']) . '<br>';
        if (!empty($entry['data'])) {
            echo '<small>User: ' . esc_html($entry['data']['user_login'] ?? 'Unknown') . ' | ';
            echo 'IP: ' . esc_html($entry['data']['ip'] ?? 'Unknown') . '</small>';
        }
        echo '</div>';
    }
    echo '</div>';
}

// Test functions
echo '<h2>Security Test Functions:</h2>';
echo '<p><a href="?test=lock" style="background: #d63638; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px; margin-right: 10px;">Lock API Settings</a>';
echo '<a href="?test=unlock" style="background: #00a32a; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px; margin-right: 10px;">Unlock API Settings</a>';
echo '<a href="?test=log" style="background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px;">Add Test Log Entry</a></p>';

// Handle test actions
if (isset($_GET['test'])) {
    $test = $_GET['test'];
    
    switch ($test) {
        case 'lock':
            update_option('crelate_api_settings_locked', true);
            echo '<p style="color: green;">✓ API settings locked successfully!</p>';
            break;
            
        case 'unlock':
            update_option('crelate_api_settings_locked', false);
            echo '<p style="color: green;">✓ API settings unlocked successfully!</p>';
            break;
            
        case 'log':
            $log_entry = array(
                'timestamp' => current_time('mysql'),
                'message' => 'Test security log entry',
                'data' => array(
                    'user_id' => get_current_user_id(),
                    'user_login' => wp_get_current_user()->user_login,
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                )
            );
            
            $security_log = get_option('crelate_security_log', array());
            $security_log[] = $log_entry;
            update_option('crelate_security_log', $security_log);
            
            echo '<p style="color: green;">✓ Test log entry added successfully!</p>';
            break;
    }
}

echo '<h2>Security Recommendations:</h2>';
echo '<ol>';
echo '<li><strong>Lock the API settings</strong> once you have configured them correctly</li>';
echo '<li><strong>Monitor the security log</strong> for any unauthorized access attempts</li>';
echo '<li><strong>Only unlock settings</strong> when you need to make changes</li>';
echo '<li><strong>Review authorized users</strong> regularly and remove unnecessary access</li>';
echo '<li><strong>Check the security log</strong> in the WordPress admin under Job Board > Security Log</li>';
echo '</ol>';

echo '<p><a href="check-settings.php" style="background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px;">Check API Settings</a></p>';
?>

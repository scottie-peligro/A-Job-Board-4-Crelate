<?php
/**
 * Plugin Name: A Job Board 4 Crelate
 * Plugin URI: https://talentsphere.ca
 * Description: Integrates Crelate ATS Job Board with WordPress to display job listings.
 * Version: 1.0.1
 * Author: Scott Minnis
 * License: GPL v2 or later
 * Text Domain: crelate-job-board
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CRELATE_JOB_BOARD_VERSION', '1.0.1');
define('CRELATE_JOB_BOARD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CRELATE_JOB_BOARD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CRELATE_JOB_BOARD_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Include required files
require_once CRELATE_JOB_BOARD_PLUGIN_DIR . 'includes/class-crelate-job-board.php';
require_once CRELATE_JOB_BOARD_PLUGIN_DIR . 'includes/class-crelate-api.php';
require_once CRELATE_JOB_BOARD_PLUGIN_DIR . 'includes/class-crelate-job-post-type.php';
require_once CRELATE_JOB_BOARD_PLUGIN_DIR . 'includes/class-crelate-admin.php';
require_once CRELATE_JOB_BOARD_PLUGIN_DIR . 'includes/class-crelate-shortcodes.php';
// Removed Crelate_AJAX include - now handled by Crelate_Templates class
require_once CRELATE_JOB_BOARD_PLUGIN_DIR . 'includes/class-crelate-templates.php';

// Initialize the plugin
function crelate_job_board_init() {
    global $crelate_job_board;
    $crelate_job_board = Crelate_Job_Board::get_instance();
    $crelate_job_board->init();
}
add_action('plugins_loaded', 'crelate_job_board_init');

// Activation hook
register_activation_hook(__FILE__, 'crelate_job_board_activate');
function crelate_job_board_activate() {
    // Set default options first
    $default_options = array(
        // Do not ship a default API key
        'api_key' => '',
        'api_endpoint' => 'https://app.crelate.com/api/pub/v1',
        'import_frequency' => 'hourly',
        'jobs_per_page' => 12,
        'enable_search' => true,
        'enable_filters' => true,
        'enable_load_more' => true,
        'track_applications' => true,
        'notification_email' => get_option('admin_email'),
        // New defaults
        'portal_id' => '',

        'last_import' => '',
        'total_jobs_imported' => 0,
        'last_import_status' => '',
        'last_import_message' => ''
    );
    
    add_option('crelate_job_board_settings', $default_options);
    
    // Create custom post type safely
    try {
        $post_type = new Crelate_Job_Post_Type();
        $post_type->register_post_type();
        $post_type->register_taxonomies();
    } catch (Exception $e) {
        // Log error but don't break activation
        error_log('Crelate Job Board: Error creating post type: ' . $e->getMessage());
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();
    
    // Schedule cron job
    if (!wp_next_scheduled('crelate_job_board_import_cron')) {
        wp_schedule_event(time(), 'hourly', 'crelate_job_board_import_cron');
    }

    // Set flag to redirect to onboarding after activation
    add_option('crelate_job_board_do_activation_redirect', true);
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'crelate_job_board_deactivate');
function crelate_job_board_deactivate() {
    // Clear scheduled cron job
    wp_clear_scheduled_hook('crelate_job_board_import_cron');
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

// Uninstall hook
register_uninstall_hook(__FILE__, 'crelate_job_board_uninstall');
function crelate_job_board_uninstall() {
    // Delete options
    delete_option('crelate_job_board_settings');
    
    // Delete all job posts
    $jobs = get_posts(array(
        'post_type' => 'crelate_job',
        'numberposts' => -1,
        'post_status' => 'any'
    ));
    
    foreach ($jobs as $job) {
        wp_delete_post($job->ID, true);
    }
    
    // Delete taxonomies
    $terms = get_terms(array(
        'taxonomy' => array('job_department', 'job_location', 'job_type', 'job_experience', 'job_remote'),
        'hide_empty' => false
    ));
    
    if (!is_wp_error($terms)) {
        foreach ($terms as $term) {
            if (is_object($term)) {
                wp_delete_term($term->term_id, $term->taxonomy);
            }
        }
    }
}

// Activation redirect to onboarding page
add_action('admin_init', function() {
    if (!current_user_can('manage_options')) {
        return;
    }
    $redirect = get_option('crelate_job_board_do_activation_redirect', false);
    if ($redirect) {
        delete_option('crelate_job_board_do_activation_redirect');
        // Avoid redirect during network or bulk activation
        if (!isset($_GET['activate-multi'])) {
            wp_safe_redirect(admin_url('admin.php?page=crelate-job-board&tab=onboarding'));
            exit;
        }
    }
});
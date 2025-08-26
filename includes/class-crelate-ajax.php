<?php
/**
 * Crelate Job Board AJAX Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crelate_AJAX {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_crelate_search_jobs', array($this, 'search_jobs'));
        add_action('wp_ajax_nopriv_crelate_search_jobs', array($this, 'search_jobs'));
        
        // Removed duplicate filter_jobs handler - now handled by Crelate_Templates class
        
        add_action('wp_ajax_crelate_load_more_jobs', array($this, 'load_more_jobs'));
        add_action('wp_ajax_nopriv_crelate_load_more_jobs', array($this, 'load_more_jobs'));
        
        add_action('wp_ajax_crelate_get_job_details', array($this, 'get_job_details'));
        add_action('wp_ajax_nopriv_crelate_get_job_details', array($this, 'get_job_details'));
        
        add_action('wp_ajax_crelate_track_application', array($this, 'track_application'));
        add_action('wp_ajax_nopriv_crelate_track_application', array($this, 'track_application'));
    }
    
    /**
     * Search jobs AJAX handler
     */
    public function search_jobs() {
        check_ajax_referer('crelate_nonce', 'nonce');
        
        $search_term = sanitize_text_field($_POST['search_term']);
        $page = intval($_POST['page']) ?: 1;
        
        // Get admin settings for default posts per page
        $settings = get_option('crelate_job_board_settings', array());
        $default_posts_per_page = !empty($settings['jobs_per_page']) ? intval($settings['jobs_per_page']) : 12;
        $per_page = intval($_POST['per_page']) ?: $default_posts_per_page;
        
        $args = array(
            'post_type' => 'crelate_job',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
            's' => $search_term,
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        $jobs_query = new WP_Query($args);
        $jobs = array();
        
        if ($jobs_query->have_posts()) {
            while ($jobs_query->have_posts()) {
                $jobs_query->the_post();
                $post_id = get_the_ID();
                $job_meta = Crelate_Job_Post_Type::get_job_meta($post_id);
                
                $jobs[] = array(
                    'id' => $post_id,
                    'title' => get_the_title(),
                    'excerpt' => get_the_excerpt(),
                    'permalink' => get_permalink(),
                    'location' => $job_meta['location'],
                    'department' => $job_meta['department'],
                    'employment_type' => $job_meta['employment_type'],
                    'salary' => Crelate_Job_Post_Type::format_salary($job_meta['salary_min'], $job_meta['salary_max']),
                    'date' => get_the_date(),
                    'apply_url' => $this->get_apply_url($post_id, $job_meta),
                    'is_expired' => Crelate_Job_Post_Type::is_job_expired($post_id)
                );
            }
        }
        
        wp_reset_postdata();
        
        $response = array(
            'success' => true,
            'jobs' => $jobs,
            'total_posts' => $jobs_query->found_posts,
            'max_pages' => $jobs_query->max_num_pages,
            'current_page' => $page
        );
        
        wp_send_json($response);
    }
    
    // Removed filter_jobs method - now handled by Crelate_Templates class
    
    /**
     * Load more jobs AJAX handler
     */
    public function load_more_jobs() {
        check_ajax_referer('crelate_nonce', 'nonce');
        
        $page = intval($_POST['page']) ?: 1;
        
        // Get admin settings for default posts per page
        $settings = get_option('crelate_job_board_settings', array());
        $default_posts_per_page = !empty($settings['jobs_per_page']) ? intval($settings['jobs_per_page']) : 12;
        $per_page = intval($_POST['per_page']) ?: $default_posts_per_page;
        $search_term = !empty($_POST['search_term']) ? sanitize_text_field($_POST['search_term']) : '';
        $filters = !empty($_POST['filters']) ? $_POST['filters'] : array();
        
        $args = array(
            'post_type' => 'crelate_job',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        // Add search
        if (!empty($search_term)) {
            $args['s'] = $search_term;
        }
        
        // Add filters
        if (!empty($filters)) {
            $meta_query = array();
            
            foreach ($filters as $key => $value) {
                if (!empty($value)) {
                    $meta_key = '_crelate_' . $key;
                    $compare = in_array($key, array('location', 'department')) ? 'LIKE' : '=';
                    
                    $meta_query[] = array(
                        'key' => $meta_key,
                        'value' => $value,
                        'compare' => $compare
                    );
                }
            }
            
            if (!empty($meta_query)) {
                $args['meta_query'] = $meta_query;
            }
        }
        
        $jobs_query = new WP_Query($args);
        $jobs = array();
        
        if ($jobs_query->have_posts()) {
            while ($jobs_query->have_posts()) {
                $jobs_query->the_post();
                $post_id = get_the_ID();
                $job_meta = Crelate_Job_Post_Type::get_job_meta($post_id);
                
                $jobs[] = array(
                    'id' => $post_id,
                    'title' => get_the_title(),
                    'excerpt' => get_the_excerpt(),
                    'permalink' => get_permalink(),
                    'location' => $job_meta['location'],
                    'department' => $job_meta['department'],
                    'employment_type' => $job_meta['employment_type'],
                    'salary' => Crelate_Job_Post_Type::format_salary($job_meta['salary_min'], $job_meta['salary_max']),
                    'date' => get_the_date(),
                    'apply_url' => $this->get_apply_url($post_id, $job_meta),
                    'is_expired' => Crelate_Job_Post_Type::is_job_expired($post_id)
                );
            }
        }
        
        wp_reset_postdata();
        
        $response = array(
            'success' => true,
            'jobs' => $jobs,
            'has_more' => $page < $jobs_query->max_num_pages,
            'current_page' => $page
        );
        
        wp_send_json($response);
    }
    
    /**
     * Get job details AJAX handler
     */
    public function get_job_details() {
        check_ajax_referer('crelate_nonce', 'nonce');
        
        $job_id = intval($_POST['job_id']);
        
        if (!$job_id) {
            wp_send_json_error(__('Job ID is required.', 'crelate-job-board'));
        }
        
        $job = get_post($job_id);
        
        if (!$job || $job->post_type !== 'crelate_job') {
            wp_send_json_error(__('Job not found.', 'crelate-job-board'));
        }
        
        $job_meta = Crelate_Job_Post_Type::get_job_meta($job_id);
        $settings = get_option('crelate_job_board_settings');
        
        $job_data = array(
            'id' => $job_id,
            'title' => get_the_title($job_id),
            'content' => apply_filters('the_content', $job->post_content),
            'excerpt' => get_the_excerpt($job_id),
            'permalink' => get_permalink($job_id),
            'location' => $job_meta['location'],
            'department' => $job_meta['department'],
            'employment_type' => $job_meta['employment_type'],
            'experience_level' => $job_meta['experience_level'],
            'remote_work' => $job_meta['remote_work'],
            'salary' => Crelate_Job_Post_Type::format_salary($job_meta['salary_min'], $job_meta['salary_max']),
            'date' => get_the_date('', $job_id),
            'modified' => get_the_modified_date('', $job_id),
            'apply_url' => $this->get_apply_url($job_id, $job_meta),
            'enable_apply' => !empty($job_meta['enable_apply']),
            'is_expired' => Crelate_Job_Post_Type::is_job_expired($job_id),
            'application_deadline' => $job_meta['application_deadline']
        );
        
        wp_send_json_success($job_data);
    }
    
    /**
     * Track application AJAX handler
     */
    public function track_application() {
        check_ajax_referer('crelate_nonce', 'nonce');
        
        $job_id = intval($_POST['job_id']);
        $user_email = sanitize_email($_POST['user_email']);
        $user_name = sanitize_text_field($_POST['user_name']);
        
        if (!$job_id || !$user_email) {
            wp_send_json_error(__('Job ID and email are required.', 'crelate-job-board'));
        }
        
        // Validate job exists
        $job = get_post($job_id);
        if (!$job || $job->post_type !== 'crelate_job') {
            wp_send_json_error(__('Job not found.', 'crelate-job-board'));
        }
        
        // Check if job is expired
        if (Crelate_Job_Post_Type::is_job_expired($job_id)) {
            wp_send_json_error(__('This job posting has expired.', 'crelate-job-board'));
        }
        
        // Store application tracking data
        $application_data = array(
            'job_id' => $job_id,
            'job_title' => get_the_title($job_id),
            'user_email' => $user_email,
            'user_name' => $user_name,
            'timestamp' => current_time('mysql'),
            'ip_address' => $this->get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        );
        
        $applications = get_option('crelate_job_applications', array());
        $applications[] = $application_data;
        update_option('crelate_job_applications', $applications);
        
        // Send notification email if configured
        $this->send_application_notification($application_data);
        
        wp_send_json_success(__('Application tracked successfully.', 'crelate-job-board'));
    }
    
    /**
     * Get apply URL
     */
    private function get_apply_url($post_id, $job_meta) {
        $settings = get_option('crelate_job_board_settings');
        
        if (!empty($settings['apply_redirect_url'])) {
            return $settings['apply_redirect_url'];
        }
        
        if (!empty($job_meta['apply_url'])) {
            return $job_meta['apply_url'];
        }
        
        return 'https://jobs.crelate.com/portal/talentsphere/job/' . $job_meta['job_id'];
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '';
    }
    
    /**
     * Send application notification email
     */
    private function send_application_notification($application_data) {
        $settings = get_option('crelate_job_board_settings');
        
        // Check if notification email is configured
        if (empty($settings['notification_email'])) {
            return;
        }
        
        $to = $settings['notification_email'];
        $subject = sprintf(__('New Job Application: %s', 'crelate-job-board'), $application_data['job_title']);
        
        $message = sprintf(
            __("A new job application has been submitted:\n\nJob: %s\nApplicant: %s\nEmail: %s\nDate: %s\n\nView job: %s", 'crelate-job-board'),
            $application_data['job_title'],
            $application_data['user_name'],
            $application_data['user_email'],
            $application_data['timestamp'],
            get_permalink($application_data['job_id'])
        );
        
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        wp_mail($to, $subject, $message, $headers);
    }
    
    /**
     * Get filter options for AJAX
     */
    public function get_filter_options() {
        check_ajax_referer('crelate_nonce', 'nonce');
        
        $filter_type = sanitize_text_field($_POST['filter_type']);
        
        switch ($filter_type) {
            case 'location':
                $terms = get_terms(array(
                    'taxonomy' => 'job_location',
                    'hide_empty' => true
                ));
                break;
                
            case 'department':
                $terms = get_terms(array(
                    'taxonomy' => 'job_department',
                    'hide_empty' => true
                ));
                break;
                
            case 'type':
                $terms = get_terms(array(
                    'taxonomy' => 'job_type',
                    'hide_empty' => true
                ));
                break;
                
            case 'experience':
                $terms = get_terms(array(
                    'taxonomy' => 'job_experience',
                    'hide_empty' => true
                ));
                break;
                
            case 'remote':
                $terms = get_terms(array(
                    'taxonomy' => 'job_remote',
                    'hide_empty' => true
                ));
                break;
                
            default:
                wp_send_json_error(__('Invalid filter type.', 'crelate-job-board'));
        }
        
        $options = array();
        foreach ($terms as $term) {
            $options[] = array(
                'value' => $term->slug,
                'label' => $term->name,
                'count' => $term->count
            );
        }
        
        wp_send_json_success($options);
    }
}

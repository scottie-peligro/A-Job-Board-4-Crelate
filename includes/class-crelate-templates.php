<?php
/**
 * Crelate Job Board Templates Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crelate_Templates {
    private static $instance = null;
    
    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    public function __construct() {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Crelate_Templates constructor called');
        }
        
        // Enqueue on frontend
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'), 20);
        
        // Shortcode owned here; ensure no duplicates elsewhere
        add_shortcode('crelate_job_board', array($this, 'job_board_shortcode'));
        
        // Register AJAX handlers
        add_action('wp_ajax_crelate_filter_jobs', array($this, 'filter_jobs_ajax'));
        add_action('wp_ajax_nopriv_crelate_filter_jobs', array($this, 'filter_jobs_ajax'));
        add_action('wp_ajax_crelate_submit_application', array($this, 'submit_application_ajax'));
        add_action('wp_ajax_nopriv_crelate_submit_application', array($this, 'submit_application_ajax'));
        add_action('wp_ajax_crelate_submit_application_api', array($this, 'submit_application_api_ajax'));
        add_action('wp_ajax_nopriv_crelate_submit_application_api', array($this, 'submit_application_api_ajax'));
        
        // Clear filter cache when jobs are updated
        add_action('save_post_crelate_job', array($this, 'clear_filter_cache'));
        add_action('deleted_post', array($this, 'clear_filter_cache'));
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Crelate_Templates hooks registered');
        }
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        // Force jQuery to load
        wp_enqueue_script('jquery');
        
        // Always enqueue on frontend
        wp_enqueue_style(
            'crelate-job-board-frontend',
            plugin_dir_url(dirname(__FILE__)) . 'assets/css/frontend.css',
            array(),
            CRELATE_JOB_BOARD_VERSION
        );
        
        // Add dynamic CSS
        $dynamic_css = $this->generate_dynamic_css();
        wp_add_inline_style('crelate-job-board-frontend', $dynamic_css);
        
        // Enqueue Font Awesome if icon style is set to Font Awesome
        $styling_settings = get_option('crelate_job_board_styling', array());
        $icon_style = $styling_settings['icon_style'] ?? 'fontawesome';
        
        if ($icon_style === 'fontawesome') {
            wp_enqueue_style(
                'font-awesome',
                'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
                array(),
                '6.0.0'
            );
        }
        
        wp_enqueue_script(
            'crelate-job-board-frontend',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/frontend.js',
            array('jquery'),
            CRELATE_JOB_BOARD_VERSION,
            true // Load in footer to ensure jQuery is available
        );
        
        wp_localize_script('crelate-job-board-frontend', 'crelate_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('crelate_nonce'),
            'debug' => (defined('WP_DEBUG') && WP_DEBUG),
            'strings' => array(
                'loading' => __('Loading...', 'crelate-job-board'),
                'no_jobs' => __('No jobs found.', 'crelate-job-board'),
                'load_more' => __('Load More Jobs', 'crelate-job-board')
            )
        ));
        
        // Minimal inline check in debug only
        if (defined('WP_DEBUG') && WP_DEBUG) {
            wp_add_inline_script('crelate-job-board-frontend', 'console.log("Crelate scripts enqueued")');
        }
    }

    
    /**
     * Job board shortcode
     */
    public function job_board_shortcode($atts) {
        // Get admin settings for default values
        $settings = get_option('crelate_job_board_settings', array());
        $default_posts_per_page = !empty($settings['jobs_per_page']) ? intval($settings['jobs_per_page']) : 12;
        
        $atts = shortcode_atts(array(
            'template' => 'grid', // grid, list, cards
            'posts_per_page' => $default_posts_per_page,
            'show_filters' => 'true',
            'show_search' => 'true',
            'show_pagination' => 'true',
            'orderby' => 'date', // date, title, location, department, salary
            'order' => 'DESC',
            'categories' => '',
            'locations' => '',
            'job_types' => '',
            'experience_levels' => '',
            'remote_only' => 'false'
        ), $atts);
        
        // Get jobs
        $jobs = $this->get_jobs($atts);
        
        // Start output buffering
        ob_start();
        
        // Include the appropriate template
        $template_file = $this->get_template_file($atts['template']);
        if (file_exists($template_file)) {
            // Make $this available to the template
            $templates = $this;
            include $template_file;
        } else {
            // Fallback to default template
            $templates = $this;
            include $this->get_template_file('grid');
        }
        
        return ob_get_clean();
    }
    
    /**
     * Get jobs based on filters
     */
    public function get_jobs($args = array()) {
        // Get admin settings for default values
        $settings = get_option('crelate_job_board_settings', array());
        $default_posts_per_page = !empty($settings['jobs_per_page']) ? intval($settings['jobs_per_page']) : 12;
        
        $defaults = array(
            'post_type' => 'crelate_job',
            'post_status' => 'publish',
            'posts_per_page' => $default_posts_per_page,
            'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => array(),
            'tax_query' => array()
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Handle custom ordering
        if ($args['orderby'] === 'location') {
            $args['meta_key'] = '_job_location';
            $args['orderby'] = 'meta_value';
        } elseif ($args['orderby'] === 'department') {
            $args['meta_key'] = '_job_department';
            $args['orderby'] = 'meta_value';
        } elseif ($args['orderby'] === 'salary') {
            $args['meta_key'] = '_job_salary';
            $args['orderby'] = 'meta_value_num';
        }
        
        // Add filters from args (for AJAX) or GET (for initial load)
        if (!empty($args['s'])) {
            // Search is already set
        } elseif (!empty($_GET['search'])) {
            $args['s'] = sanitize_text_field($_GET['search']);
        }
        
        if (!empty($args['location'])) {
            $args['meta_query'][] = array(
                'key' => '_job_location',
                'value' => sanitize_text_field($args['location']),
                'compare' => 'LIKE'
            );
        } elseif (!empty($_GET['location'])) {
            $args['meta_query'][] = array(
                'key' => '_job_location',
                'value' => sanitize_text_field($_GET['location']),
                'compare' => 'LIKE'
            );
        }
        
        if (!empty($args['department'])) {
            $args['tax_query'][] = array(
                'taxonomy' => 'job_department',
                'field' => 'slug',
                'terms' => sanitize_text_field($args['department'])
            );
        } elseif (!empty($_GET['department'])) {
            $args['tax_query'][] = array(
                'taxonomy' => 'job_department',
                'field' => 'slug',
                'terms' => sanitize_text_field($_GET['department'])
            );
        }
        
        if (!empty($args['job_type'])) {
            $args['tax_query'][] = array(
                'taxonomy' => 'job_type',
                'field' => 'slug',
                'terms' => sanitize_text_field($args['job_type'])
            );
        } elseif (!empty($_GET['job_type'])) {
            $args['tax_query'][] = array(
                'taxonomy' => 'job_type',
                'field' => 'slug',
                'terms' => sanitize_text_field($_GET['job_type'])
            );
        }
        
        if (!empty($args['experience'])) {
            $args['tax_query'][] = array(
                'taxonomy' => 'job_experience',
                'field' => 'slug',
                'terms' => sanitize_text_field($args['experience'])
            );
        } elseif (!empty($_GET['experience'])) {
            $args['tax_query'][] = array(
                'taxonomy' => 'job_experience',
                'field' => 'slug',
                'terms' => sanitize_text_field($_GET['experience'])
            );
        }
        
        if (!empty($args['remote']) && $args['remote'] === 'true') {
            $args['meta_query'][] = array(
                'key' => '_job_remote',
                'value' => 'remote',
                'compare' => 'LIKE'
            );
        } elseif (!empty($_GET['remote']) && $_GET['remote'] === 'true') {
            $args['meta_query'][] = array(
                'key' => '_job_remote',
                'value' => 'remote',
                'compare' => 'LIKE'
            );
        }
        
        return new WP_Query($args);
    }
    
    /**
     * Get template file path
     */
    private function get_template_file($template) {
        return dirname(dirname(__FILE__)) . '/templates/job-board-' . $template . '.php';
    }
    
    /**
     * Get filter options
     */
    public function get_filter_options() {
        $options = array();
        
        // Cache filter options for 1 hour to improve performance
        $cache_key = 'crelate_filter_options';
        $cached_options = wp_cache_get($cache_key);
        
        if ($cached_options !== false) {
            return $cached_options;
        }
        
        // Get locations
        global $wpdb;
        $locations = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT meta_value 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = %s 
            AND meta_value != '' 
            ORDER BY meta_value ASC
        ", '_job_location'));
        $options['locations'] = $locations;
        
        // Get departments
        $departments = get_terms(array(
            'taxonomy' => 'job_department',
            'hide_empty' => true,
            'orderby' => 'name',
            'order' => 'ASC'
        ));
        $options['departments'] = $departments;
        
        // Get job types
        $job_types = get_terms(array(
            'taxonomy' => 'job_type',
            'hide_empty' => true,
            'orderby' => 'name',
            'order' => 'ASC'
        ));
        $options['job_types'] = $job_types;
        
        // Get experience levels
        $experience_levels = get_terms(array(
            'taxonomy' => 'job_experience',
            'hide_empty' => true,
            'orderby' => 'name',
            'order' => 'ASC'
        ));
        $options['experience_levels'] = $experience_levels;
        
        // Cache the results for 1 hour
        wp_cache_set($cache_key, $options, '', HOUR_IN_SECONDS);
        
        return $options;
    }
    
    /**
     * Filter jobs AJAX handler
     */
    public function filter_jobs_ajax() {
        try {
            // Verify nonce
            if (!check_ajax_referer('crelate_nonce', 'nonce', false)) {
                wp_send_json_error(array('message' => 'Security check failed'));
                return;
            }
            
            $args = array();
        
        if (!empty($_POST['search'])) {
            $args['s'] = sanitize_text_field($_POST['search']);
        }
        
        if (!empty($_POST['location'])) {
            $args['location'] = sanitize_text_field($_POST['location']);
        }
        
        if (!empty($_POST['department'])) {
            $args['department'] = sanitize_text_field($_POST['department']);
        }
        
        if (!empty($_POST['job_type'])) {
            $args['job_type'] = sanitize_text_field($_POST['job_type']);
        }
        
        if (!empty($_POST['experience'])) {
            $args['experience'] = sanitize_text_field($_POST['experience']);
        }
        
        if (!empty($_POST['remote'])) {
            $args['remote'] = sanitize_text_field($_POST['remote']);
        }
        
        if (!empty($_POST['orderby'])) {
            $args['orderby'] = sanitize_text_field($_POST['orderby']);
        }
        
        if (!empty($_POST['order'])) {
            $args['order'] = sanitize_text_field($_POST['order']);
        }
        
        if (!empty($_POST['template'])) {
            $args['template'] = sanitize_text_field($_POST['template']);
        }
        
        // Handle posts per page
        if (!empty($_POST['per_page'])) {
            $args['posts_per_page'] = intval($_POST['per_page']);
        }
        
        // Handle pagination for load more
        if (!empty($_POST['page'])) {
            $args['paged'] = intval($_POST['page']);
        }
        
        $jobs = $this->get_jobs($args);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('AJAX Filter - Found posts: ' . $jobs->found_posts);
        }
        
        ob_start();
        
        if ($jobs->have_posts()) {
            while ($jobs->have_posts()) {
                $jobs->the_post();
                $template = isset($args['template']) ? $args['template'] : 'grid';
                $this->render_job_item($template);
            }
        } else {
            echo '<div class="crelate-no-jobs">' . __('No jobs found matching your criteria.', 'crelate-job-board') . '</div>';
        }
        
        wp_reset_postdata();
        
        $html = ob_get_clean();
        
        $response_data = array(
            'html' => $html,
            'found_posts' => $jobs->found_posts,
            'max_num_pages' => $jobs->max_num_pages
        );
        
        // Send JSON response with success wrapper to match JavaScript expectations
        wp_send_json_success($response_data);
        
        } catch (Exception $e) {
            error_log('Crelate AJAX Error: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => 'An error occurred while filtering jobs',
                'error' => $e->getMessage()
            ));
        }
    }
    
    /**
     * Render job item
     */
    private function render_job_item($template = 'grid') {
        // Get the correct path to the templates directory
        $template_file = dirname(dirname(__FILE__)) . '/templates/item-' . $template . '.php';
        
        if (file_exists($template_file)) {
            
            // Make $this available to the template
            $templates = $this;
            include $template_file;
        } else {
            $fallback_file = dirname(dirname(__FILE__)) . '/templates/item-grid.php';
            if (file_exists($fallback_file)) {
                $templates = $this;
                include $fallback_file;
            } else {
                echo '<div class="crelate-job-card">Template file not found</div>';
            }
        }
    }
    
    /**
     * Get job meta
     */
    public function get_job_meta($post_id, $key) {
        // Prefer unified _crelate_ keys; fallback to _job_
        $value = get_post_meta($post_id, '_crelate_' . $key, true);
        if ($value === '' || $value === null) {
            $value = get_post_meta($post_id, '_job_' . $key, true);
        }
        return $value;
    }
    
    /**
     * Format salary
     */
    public function format_salary($salary) {
        if (empty($salary)) {
            return '';
        }
        
        // If it's already formatted, return as is
        if (strpos($salary, '$') !== false) {
            return $salary;
        }
        
        // If it's a number, format it
        if (is_numeric($salary)) {
            return '$' . number_format($salary);
        }
        
        return $salary;
    }


    
    /**
     * Get job categories
     */
    public function get_job_categories($post_id) {
        $categories = array();
        
        $departments = wp_get_post_terms($post_id, 'job_department');
        if (!is_wp_error($departments)) {
            $categories['department'] = $departments;
        }
        
        $locations = wp_get_post_terms($post_id, 'job_location');
        if (!is_wp_error($locations)) {
            $categories['location'] = $locations;
        }
        
        $types = wp_get_post_terms($post_id, 'job_type');
        if (!is_wp_error($types)) {
            $categories['type'] = $types;
        }
        
        $experience = wp_get_post_terms($post_id, 'job_experience');
        if (!is_wp_error($experience)) {
            $categories['experience'] = $experience;
        }
        
        return $categories;
    }
    
    /**
     * Clear filter options cache
     */
    public function clear_filter_cache() {
        wp_cache_delete('crelate_filter_options');
    }
    
    /**
     * Submit application AJAX handler
     */
    public function submit_application_ajax() {
        try {
            // Verify nonce
            if (!check_ajax_referer('crelate_application_nonce', 'application_nonce', false)) {
                wp_send_json_error(array('message' => 'Security check failed'));
                return;
            }
            
            // Validate required fields
            $required_fields = array('applicant_name', 'applicant_email', 'resume_file');
            foreach ($required_fields as $field) {
                if (empty($_POST[$field])) {
                    wp_send_json_error(array('message' => 'Please fill in all required fields.'));
                    return;
                }
            }
            
            // Validate email
            if (!is_email($_POST['applicant_email'])) {
                wp_send_json_error(array('message' => 'Please enter a valid email address.'));
                return;
            }
            
            // Handle file upload
            if (!empty($_FILES['resume_file'])) {
                $file = $_FILES['resume_file'];
                
                // Check file size (5MB limit)
                if ($file['size'] > 5 * 1024 * 1024) {
                    wp_send_json_error(array('message' => 'Resume file is too large. Maximum size is 5MB.'));
                    return;
                }
                
                // Check file type
                $allowed_types = array('application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
                if (!in_array($file['type'], $allowed_types)) {
                    wp_send_json_error(array('message' => 'Invalid file type. Please upload PDF, DOC, or DOCX files only.'));
                    return;
                }
            }
            
            // Get job details
            $job_id = intval($_POST['job_id']);
            $job_title = get_the_title($job_id);
            $crelate_job_id = sanitize_text_field($_POST['crelate_job_id']);
            
            // Prepare application data
            $application_data = array(
                'job_id' => $job_id,
                'job_title' => $job_title,
                'crelate_job_id' => $crelate_job_id,
                'applicant_name' => sanitize_text_field($_POST['applicant_name']),
                'applicant_email' => sanitize_email($_POST['applicant_email']),
                'applicant_phone' => sanitize_text_field($_POST['applicant_phone']),
                'applicant_location' => sanitize_text_field($_POST['applicant_location']),
                'cover_letter' => sanitize_textarea_field($_POST['cover_letter']),
                'applicant_linkedin' => esc_url_raw($_POST['applicant_linkedin']),
                'applicant_website' => esc_url_raw($_POST['applicant_website']),
                'how_heard' => sanitize_text_field($_POST['how_heard']),
                'submitted_at' => current_time('mysql'),
                'ip_address' => $_SERVER['REMOTE_ADDR']
            );
            
            // Save application to database (you can create a custom table for this)
            $this->save_application($application_data);
            
            // Send email notification
            $this->send_application_notification($application_data);
            
            wp_send_json_success(array(
                'message' => 'Thank you for your application! We will review your submission and get back to you soon.'
            ));
            
        } catch (Exception $e) {
            error_log('Crelate Application Error: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => 'An error occurred while submitting your application. Please try again.'
            ));
        }
    }
    
    /**
     * Save application to database
     */
    private function save_application($data) {
        // For now, we'll save to WordPress options as a simple solution
        // In a production environment, you'd want to create a custom table
        $applications = get_option('crelate_applications', array());
        $applications[] = $data;
        update_option('crelate_applications', $applications);
    }
    
    /**
     * Submit application via Crelate API
     */
    public function submit_application_api_ajax() {
        try {
            // Verify nonce
            if (!check_ajax_referer('crelate_application_nonce', 'application_nonce', false)) {
                wp_send_json_error(array('message' => 'Security check failed'));
                return;
            }
            
            // Validate required fields
            $required_fields = array('applicant_name', 'applicant_email', 'resume_file');
            foreach ($required_fields as $field) {
                if (empty($_POST[$field])) {
                    wp_send_json_error(array('message' => 'Please fill in all required fields.'));
                    return;
                }
            }
            
            // Validate email
            if (!is_email($_POST['applicant_email'])) {
                wp_send_json_error(array('message' => 'Please enter a valid email address.'));
                return;
            }
            
            // Handle file upload
            if (!empty($_FILES['resume_file'])) {
                $file = $_FILES['resume_file'];
                
                // Check file size (5MB limit)
                if ($file['size'] > 5 * 1024 * 1024) {
                    wp_send_json_error(array('message' => 'Resume file is too large. Maximum size is 5MB.'));
                    return;
                }
                
                // Check file type
                $allowed_types = array('application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
                if (!in_array($file['type'], $allowed_types)) {
                    wp_send_json_error(array('message' => 'Invalid file type. Please upload PDF, DOC, or DOCX files only.'));
                    return;
                }
            }
            
            // Get job details
            $job_id = intval($_POST['job_id']);
            $job_title = get_the_title($job_id);
            $crelate_job_id = sanitize_text_field($_POST['crelate_job_id']);
            
            if (empty($crelate_job_id)) {
                wp_send_json_error(array('message' => 'Invalid job ID. Please try again.'));
                return;
            }
            
            // Prepare application data for Crelate API
            $application_data = array(
                'job_id' => $job_id,
                'job_title' => $job_title,
                'crelate_job_id' => $crelate_job_id,
                'applicant_name' => sanitize_text_field($_POST['applicant_name']),
                'applicant_email' => sanitize_email($_POST['applicant_email']),
                'applicant_phone' => sanitize_text_field($_POST['applicant_phone']),
                'applicant_location' => sanitize_text_field($_POST['applicant_location']),
                'cover_letter' => sanitize_textarea_field($_POST['cover_letter']),
                'applicant_linkedin' => esc_url_raw($_POST['applicant_linkedin']),
                'applicant_website' => esc_url_raw($_POST['applicant_website']),
                'how_heard' => sanitize_text_field($_POST['how_heard']),
                'submitted_at' => current_time('mysql'),
                'ip_address' => $_SERVER['REMOTE_ADDR']
            );

            
            // Submit to Crelate API
            $api_result = $this->submit_to_crelate_api($application_data, $file);
            
            if ($api_result['success']) {
                // Save application to database for record keeping
                $this->save_application($application_data);
                
                wp_send_json_success(array(
                    'message' => 'Thank you for your application! Your submission has been sent to our hiring team.'
                ));
            } else {
                wp_send_json_error(array(
                    'message' => 'There was an issue submitting your application. Please try again or contact us directly.'
                ));
            }
            
        } catch (Exception $e) {
            error_log('Crelate API Application Error: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => 'An error occurred while submitting your application. Please try again.'
            ));
        }
    }
    
    /**
     * Submit application to Crelate API
     */
    private function submit_to_crelate_api($data, $file) {
        $settings = get_option('crelate_job_board_settings');
        $api_key = $settings['api_key'] ?? '';
        $api_endpoint = $settings['api_endpoint'] ?? 'https://app.crelate.com/api/pub/v1';
        
        if (empty($api_key)) {
            return array('success' => false, 'message' => 'API key not configured');
        }
        
        // Prepare the API request
        $api_url = $api_endpoint . '/jobPostings/' . $data['crelate_job_id'] . '/applications';
        
        // Read and encode the resume file
        $resume_content = base64_encode(file_get_contents($file['tmp_name']));
        $resume_filename = $file['name'];
        
        // Prepare the request body
        $request_body = array(
            'ResumeContent' => $resume_content,
            'ResumeFilename' => $resume_filename,
            'Email' => $data['applicant_email'],
            'FirstName' => $this->extract_first_name($data['applicant_name']),
            'LastName' => $this->extract_last_name($data['applicant_name']),
            'Phone' => $data['applicant_phone'],
            'Location' => $data['applicant_location'],
            'CoverLetter' => $data['cover_letter'],
            'LinkedInUrl' => $data['applicant_linkedin'],
            'WebsiteUrl' => $data['applicant_website'],
            'Source' => $data['how_heard'] ?: 'Website'
        );

        
        // Make the API request
        $response = wp_remote_post($api_url, array(
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Authorization' => 'Bearer ' . $api_key
            ),
            'body' => $request_body,
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            error_log('Crelate API Error: ' . $response->get_error_message());
            return array('success' => false, 'message' => 'API request failed');
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code === 200 || $response_code === 201) {
            return array('success' => true, 'message' => 'Application submitted successfully');
        } else {
            error_log('Crelate API Error: HTTP ' . $response_code . ' - ' . $response_body);
            return array('success' => false, 'message' => 'API returned error: ' . $response_code);
        }
    }
    
    /**
     * Extract first name from full name
     */
    private function extract_first_name($full_name) {
        $name_parts = explode(' ', trim($full_name));
        return $name_parts[0] ?? '';
    }
    
    /**
     * Extract last name from full name
     */
    private function extract_last_name($full_name) {
        $name_parts = explode(' ', trim($full_name));
        if (count($name_parts) > 1) {
            return implode(' ', array_slice($name_parts, 1));
        }
        return '';
    }
    
    /**
     * Send application notification email
     */
    private function send_application_notification($data) {
        $to = get_option('admin_email');
        $subject = 'New Job Application: ' . $data['job_title'];
        
        $message = "A new job application has been submitted:\n\n";
        $message .= "Job: " . $data['job_title'] . "\n";
        $message .= "Applicant: " . $data['applicant_name'] . "\n";
        $message .= "Email: " . $data['applicant_email'] . "\n";
        $message .= "Phone: " . $data['applicant_phone'] . "\n";
        $message .= "Location: " . $data['applicant_location'] . "\n";
        $message .= "Submitted: " . $data['submitted_at'] . "\n\n";
        
        if (!empty($data['cover_letter'])) {
            $message .= "Cover Letter:\n" . $data['cover_letter'] . "\n\n";
        }
        
        wp_mail($to, $subject, $message);
    }
    
    /**
     * Generate dynamic CSS based on styling settings
     */
    public function generate_dynamic_css() {
        $styling_settings = get_option('crelate_job_board_styling', array());
        $primary_color = $styling_settings['primary_color'] ?? '#0073aa';
        $border_radius = $styling_settings['border_radius'] ?? 'rounded';
        $icon_style = $styling_settings['icon_style'] ?? 'fontawesome';
        $button_text_color = $styling_settings['button_text_color'] ?? 'light';
        $button_text_case = $styling_settings['button_text_case'] ?? 'uppercase';
        $job_title_font_size = $styling_settings['job_title_font_size'] ?? 'default';
        $job_title_line_height = $styling_settings['job_title_line_height'] ?? '1.3';
        $use_theme_style = $styling_settings['use_theme_style'] ?? '0';
        
        // Generate font size CSS only if not default
        $font_size_css = '';
        if ($job_title_font_size !== 'default') {
            $font_size_css = "font-size: {$job_title_font_size} !important;";
        }
        
        $border_radius_value = $border_radius === 'rounded' ? '8px' : '0px';
        
        // Determine text color based on primary color brightness
        $text_color = $button_text_color === 'light' ? '#ffffff' : '#000000';
        if ($button_text_color === 'light') {
            // Use light text (white) for dark backgrounds
            $text_color = '#ffffff';
        } else {
            // Use dark text (black) for light backgrounds
            $text_color = '#000000';
        }
        
        // Determine text case
        $text_transform = 'uppercase';
        $text_case_style = '';
        switch ($button_text_case) {
            case 'titlecase':
                $text_transform = 'none';
                $text_case_style = 'text-transform: capitalize !important;';
                break;
            case 'lowercase':
                $text_transform = 'lowercase';
                break;
            default:
                $text_transform = 'uppercase';
                break;
        }
        
        $css = "
        /* Dynamic CSS for Crelate Job Board */
        .crelate-job-board {
            --primary-color: {$primary_color};
            --border-radius: {$border_radius_value};
            --button-text-color: {$text_color};
        }
        .single-crelate_job {
            --primary-color: {$primary_color};
            --border-radius: {$border_radius_value};
            --button-text-color: {$text_color};
        }
        
        /* Apply border radius to elements */
        " . ($use_theme_style === '1' ? "
        /* Theme style enabled - only apply border radius to non-button elements */
        .crelate-search input,
        .crelate-filters select,
        .crelate-filters-section,
        .crelate-job-card,
        .crelate-job-item,
        .crelate-application-form,
        .crelate-form-group input,
        .crelate-form-group select,
        .crelate-form-group textarea,
        .crelate-form-message,
        .crelate-template-btn,
        .crelate-quick-action,
        .crelate-remote-badge,
        .crelate-badge,
        .crelate-tag,
        .crelate-expired-badge {
            border-radius: var(--border-radius) !important;
        }
        " : "
        /* Custom style enabled - apply border radius to all elements including buttons */
        .crelate-btn,
        .crelate-btn-primary,
        .crelate-btn-secondary,
        .crelate-search input,
        .crelate-filters select,
        .crelate-filters-section,
        .crelate-job-card,
        .crelate-job-item,
        .crelate-application-form,
        .crelate-form-group input,
        .crelate-form-group select,
        .crelate-form-group textarea,
        .crelate-form-message,
        .crelate-template-btn,
        .crelate-quick-action,
        .crelate-remote-badge,
        .crelate-badge,
        .crelate-tag,
        .crelate-expired-badge {
            border-radius: var(--border-radius) !important;
        }
        ") . "
        
        /* Button styling */
        " . ($use_theme_style === '1' ? "
        /* Theme style enabled - buttons will inherit theme styling completely */
        .crelate-btn-primary,
        .crelate-btn-secondary {
            /* Only preserve text transform settings, let theme handle everything else */
            text-transform: {$text_transform} !important;
            {$text_case_style}
        }
        " : "
        .crelate-btn-primary {
            background-color: var(--primary-color) !important;
            color: var(--button-text-color) !important;
            text-transform: {$text_transform} !important;
            {$text_case_style}
        }
        
        .crelate-btn-primary:hover {
            background-color: " . $this->darken_color($primary_color, 20) . " !important;
            color: var(--button-text-color) !important;
        }
        
        .crelate-btn-secondary {
            background-color: #6c757d !important;
            color: #ffffff !important;
            text-transform: {$text_transform} !important;
            {$text_case_style}
        }
        
        .crelate-btn-secondary:hover {
            background-color: #5a6268 !important;
            color: #ffffff !important;
        }
        ") . "
        
        /* Search and filter styling */
        .crelate-search input:focus,
        .crelate-filters select:focus {
            border-color: var(--primary-color) !important;
            box-shadow: 0 0 0 3px " . $this->hex_to_rgba($primary_color, 0.1) . " !important;
        }
        
        .crelate-template-btn.active {
            background: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
            color: var(--button-text-color) !important;
        }
        
        .crelate-template-btn.active:hover {
            background: " . $this->darken_color($primary_color, 20) . " !important;
            color: var(--button-text-color) !important;
        }
        
        /* Job card styling */
        .crelate-job-title {
            line-height: {$job_title_line_height} !important;
            {$font_size_css}
        }
        
        .crelate-job-title a:hover {
            color: var(--primary-color) !important;
        }
        
        .crelate-quick-action:hover {
            border-color: var(--primary-color) !important;
            color: var(--primary-color) !important;
        }
        
        /* Spinner styling */
        .crelate-spinner {
            border-top-color: var(--primary-color) !important;
        }
        
        /* Application form styling */
        .crelate-application-form {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
        }
        
        /* Submit Application button styling to match View Details */
        .crelate-application-form .crelate-btn-primary,
        .crelate-submit-application {
            background-color: var(--primary-color) !important;
            color: var(--button-text-color) !important;
            text-transform: {$text_transform} !important;
            {$text_case_style}
            border-radius: var(--border-radius) !important;
            padding: 8px 16px !important;
            font-size: 14px !important;
            font-weight: 500 !important;
            border: none !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
        }
        
        .crelate-application-form .crelate-btn-primary:hover,
        .crelate-submit-application:hover {
            background-color: " . $this->darken_color($primary_color, 20) . " !important;
            color: var(--button-text-color) !important;
        }
        
        .crelate-application-form h3 {
            color: var(--primary-color) !important;
        }
        
        .crelate-form-group input:focus,
        .crelate-form-group select:focus,
        .crelate-form-group textarea:focus {
            border-color: var(--primary-color) !important;
            box-shadow: 0 0 0 3px " . $this->hex_to_rgba($primary_color, 0.1) . " !important;
        }
        
        .crelate-form-message.success {
            background-color: " . $this->hex_to_rgba($primary_color, 0.1) . " !important;
            border-color: var(--primary-color) !important;
            color: " . $this->darken_color($primary_color, 30) . " !important;
        }
        
        /* Job details page styling */
        .single-crelate_job .entry-content h1,
        .single-crelate_job .entry-title,
        .single-crelate_job h1,
        .single-crelate_job h2,
        .single-crelate_job h3,
        .crelate-job-detail-title h1,
        .crelate-job-detail-description h2,
        .crelate-job-detail-requirements h2,
        .crelate-job-detail-benefits h2,
        .crelate-job-detail-apply h3,
        .crelate-job-detail-info h3 {
            color: var(--primary-color) !important;
        }
        
        /* Primary color line under job details heading */
        .single-crelate_job .entry-title::after,
        .crelate-job-detail-title h1::after {
            content: '';
            display: block;
            width: 60px;
            height: 3px;
            background-color: var(--primary-color);
            margin-top: 10px;
            margin-bottom: 20px;
        }
        
        /* Job details page links - more specific to avoid affecting header */
        .single-crelate_job .entry-content a,
        .single-crelate_job .crelate-job-meta a,
        .crelate-job-detail-breadcrumb a,
        .crelate-job-detail-content a {
            color: var(--primary-color) !important;
        }
        
        .single-crelate_job a:hover,
        .crelate-job-detail-breadcrumb a:hover {
            color: " . $this->darken_color($primary_color, 20) . " !important;
        }
        
        .single-crelate_job .crelate-job-meta i,
        .single-crelate_job .fas,
        .single-crelate_job .fa,
        .job-meta-item i {
            color: var(--primary-color) !important;
        }
        
        .single-crelate_job .crelate-job-description,
        .crelate-job-detail-description,
        .crelate-job-detail-requirements,
        .crelate-job-detail-benefits {
            border-left: 3px solid var(--primary-color) !important;
            padding-left: 15px;
        }
        
        .single-crelate_job .crelate-job-description h2,
        .crelate-job-detail-description h2,
        .crelate-job-detail-requirements h2,
        .crelate-job-detail-benefits h2 {
            border-bottom: 2px solid var(--primary-color) !important;
            padding-bottom: 10px;
        }
        
        .crelate-job-detail-apply,
        .crelate-job-detail-info {
            border-radius: var(--border-radius) !important;
        }
        
        /* Fix duplicate form headings */
        .crelate-application-form h3:first-of-type {
            display: none !important;
        }
        
        /* Fix form borders */
        .crelate-application-form {
            border: 1px solid #e9ecef !important;
            background: #f8f9fa !important;
        }
        
        .crelate-application-form .crelate-form {
            border: none !important;
            background: transparent !important;
        }
        
        /* Font Awesome icons color - comprehensive coverage */
        .crelate-job-board .fas,
        .crelate-job-board .fa,
        .crelate-job-meta i,
        .crelate-job-date i,
        .crelate-job-card .fas,
        .crelate-job-card .fa,
        .crelate-job-item .fas,
        .crelate-job-item .fa {
            color: var(--primary-color) !important;
        }
        
        /* Remote badge icon should always be white */
        .crelate-remote-badge i,
        .crelate-remote-badge .fas,
        .crelate-remote-badge .fa {
            color: white !important;
        }
        
        /* Job details page icons */
        .single-crelate_job .fas,
        .single-crelate_job .fa,
        .crelate-job-detail-content .fas,
        .crelate-job-detail-content .fa,
        .job-meta-item i,
        .crelate-job-detail-meta i,
        .crelate-job-detail-meta .fas,
        .crelate-job-detail-meta .fa,
        .crelate-job-detail-container .fas,
        .crelate-job-detail-container .fa {
            color: var(--primary-color) !important;
        }
        
        /* Share button alignment when no remote button */
        .crelate-job-card-top:not(:has(.crelate-remote-badge)) .crelate-job-quick-actions {
            margin-left: auto;
        }
        
        /* Share button styling */
        .crelate-share-btn {
            background: transparent;
            border: none;
            color: var(--primary-color);
            font-size: 18px;
            cursor: pointer;
            padding: 5px;
            transition: color 0.3s ease;
        }
        
        .crelate-share-btn:hover {
            color: " . $this->darken_color($primary_color, 20) . ";
        }
        
        /* Fix share button alignment on job details page */
        .crelate-job-detail-meta {
            justify-content: flex-start !important;
        }
        
        .crelate-job-detail-actions {
            margin-left: 0 !important;
        }
        
        /* Search icon color */
        .crelate-search .fas,
        .crelate-search .fa {
            color: var(--button-text-color) !important;
        }
        
        /* Experience dropdown options in Title Caps */
        #crelate-experience-filter option {
            text-transform: capitalize !important;
        }
        
        /* Notification styling */
        .crelate-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            border-radius: var(--border-radius);
            color: white;
            font-weight: 500;
            z-index: 9999;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            animation: slideInRight 0.3s ease;
        }
        
        .crelate-notification-success {
            background-color: #28a745;
        }
        
        .crelate-notification-error {
            background-color: #dc3545;
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        ";
        
        return $css;
    }
    
    /**
     * Darken a hex color by a percentage
     */
    private function darken_color($hex, $percent) {
        $hex = str_replace('#', '', $hex);
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        $r = max(0, min(255, $r - ($r * $percent / 100)));
        $g = max(0, min(255, $g - ($g * $percent / 100)));
        $b = max(0, min(255, $b - ($b * $percent / 100)));
        
        return sprintf("#%02x%02x%02x", $r, $g, $b);
    }
    
    /**
     * Convert hex color to rgba
     */
    private function hex_to_rgba($hex, $alpha) {
        $hex = str_replace('#', '', $hex);
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        return "rgba($r, $g, $b, $alpha)";
    }

    /**
     * Get icon based on styling settings
     */
    public function get_icon($icon_name) {
        $styling_settings = get_option('crelate_job_board_styling', array());
        $icon_style = $styling_settings['icon_style'] ?? 'fontawesome';
        
        $icons = array(
            'search' => array(
                'fontawesome' => '<i class="fas fa-search"></i>',
                'emoji' => '🔍'
            ),
            'grid' => array(
                'fontawesome' => '<i class="fas fa-th"></i>',
                'emoji' => '⊞'
            ),
            'list' => array(
                'fontawesome' => '<i class="fas fa-list"></i>',
                'emoji' => '☰'
            ),
            'location' => array(
                'fontawesome' => '<i class="fas fa-map-marker-alt"></i>',
                'emoji' => '📍'
            ),
            'department' => array(
                'fontawesome' => '<i class="fas fa-building"></i>',
                'emoji' => '🏢'
            ),
            'type' => array(
                'fontawesome' => '<i class="fas fa-briefcase"></i>',
                'emoji' => '💼'
            ),
            'experience' => array(
                'fontawesome' => '<i class="fas fa-star"></i>',
                'emoji' => '⭐'
            ),
            'salary' => array(
                'fontawesome' => '<i class="fas fa-dollar-sign"></i>',
                'emoji' => '💰'
            ),
            'calendar' => array(
                'fontawesome' => '<i class="fas fa-calendar"></i>',
                'emoji' => '📅'
            ),
            'remote' => array(
                'fontawesome' => '<i class="fas fa-home"></i>',
                'emoji' => '🏠'
            ),
            'bookmark' => array(
                'fontawesome' => '<i class="fas fa-bookmark"></i>',
                'emoji' => '🔖'
            ),
            'share' => array(
                'fontawesome' => '<i class="fas fa-share"></i>',
                'emoji' => '📤'
            ),
            'no-jobs' => array(
                'fontawesome' => '<i class="fas fa-clipboard-list"></i>',
                'emoji' => '📋'
            )
        );
        
        if (isset($icons[$icon_name])) {
            return $icons[$icon_name][$icon_style];
        }
        
        return '';
    }
}

// Initialize the templates class
function crelate_templates_init() {
    Crelate_Templates::instance();
}
// Try multiple hooks to ensure initialization
add_action('plugins_loaded', 'crelate_templates_init', 20);

<?php
/**
 * Crelate Job Post Type Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crelate_Job_Post_Type {
    
    /**
     * Register the job post type
     */
    public static function register_post_type() {
        $labels = array(
            'name' => _x('Jobs', 'post type general name', 'crelate-job-board'),
            'singular_name' => _x('Job', 'post type singular name', 'crelate-job-board'),
            'menu_name' => _x('Jobs', 'admin menu', 'crelate-job-board'),
            'name_admin_bar' => _x('Job', 'add new on admin bar', 'crelate-job-board'),
            'add_new' => _x('Add New', 'job', 'crelate-job-board'),
            'add_new_item' => __('Add New Job', 'crelate-job-board'),
            'new_item' => __('New Job', 'crelate-job-board'),
            'edit_item' => __('Edit Job', 'crelate-job-board'),
            'view_item' => __('View Job', 'crelate-job-board'),
            'all_items' => __('All Jobs', 'crelate-job-board'),
            'search_items' => __('Search Jobs', 'crelate-job-board'),
            'parent_item_colon' => __('Parent Jobs:', 'crelate-job-board'),
            'not_found' => __('No jobs found.', 'crelate-job-board'),
            'not_found_in_trash' => __('No jobs found in Trash.', 'crelate-job-board')
        );
        
        $args = array(
            'labels' => $labels,
            'description' => __('Job listings from Crelate ATS', 'crelate-job-board'),
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'jobs'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 20,
            'menu_icon' => 'dashicons-businessman',
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
            'show_in_rest' => true,
            'rest_base' => 'jobs',
            'rest_controller_class' => 'WP_REST_Posts_Controller'
        );
        
        register_post_type('crelate_job', $args);
    }
    
    /**
     * Register job taxonomies
     */
    public static function register_taxonomies() {
        // Job Department/Category
        $department_labels = array(
            'name' => _x('Departments', 'taxonomy general name', 'crelate-job-board'),
            'singular_name' => _x('Department', 'taxonomy singular name', 'crelate-job-board'),
            'search_items' => __('Search Departments', 'crelate-job-board'),
            'all_items' => __('All Departments', 'crelate-job-board'),
            'parent_item' => __('Parent Department', 'crelate-job-board'),
            'parent_item_colon' => __('Parent Department:', 'crelate-job-board'),
            'edit_item' => __('Edit Department', 'crelate-job-board'),
            'update_item' => __('Update Department', 'crelate-job-board'),
            'add_new_item' => __('Add New Department', 'crelate-job-board'),
            'new_item_name' => __('New Department Name', 'crelate-job-board'),
            'menu_name' => __('Departments', 'crelate-job-board'),
        );
        
        register_taxonomy('job_department', array('crelate_job'), array(
            'hierarchical' => true,
            'labels' => $department_labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'department'),
            'show_in_rest' => true,
        ));
        
        // Job Location
        $location_labels = array(
            'name' => _x('Locations', 'taxonomy general name', 'crelate-job-board'),
            'singular_name' => _x('Location', 'taxonomy singular name', 'crelate-job-board'),
            'search_items' => __('Search Locations', 'crelate-job-board'),
            'all_items' => __('All Locations', 'crelate-job-board'),
            'parent_item' => __('Parent Location', 'crelate-job-board'),
            'parent_item_colon' => __('Parent Location:', 'crelate-job-board'),
            'edit_item' => __('Edit Location', 'crelate-job-board'),
            'update_item' => __('Update Location', 'crelate-job-board'),
            'add_new_item' => __('Add New Location', 'crelate-job-board'),
            'new_item_name' => __('New Location Name', 'crelate-job-board'),
            'menu_name' => __('Locations', 'crelate-job-board'),
        );
        
        register_taxonomy('job_location', array('crelate_job'), array(
            'hierarchical' => true,
            'labels' => $location_labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'location'),
            'show_in_rest' => true,
        ));
        
        // Job Type (Employment Type)
        $type_labels = array(
            'name' => _x('Job Types', 'taxonomy general name', 'crelate-job-board'),
            'singular_name' => _x('Job Type', 'taxonomy singular name', 'crelate-job-board'),
            'search_items' => __('Search Job Types', 'crelate-job-board'),
            'all_items' => __('All Job Types', 'crelate-job-board'),
            'parent_item' => __('Parent Job Type', 'crelate-job-board'),
            'parent_item_colon' => __('Parent Job Type:', 'crelate-job-board'),
            'edit_item' => __('Edit Job Type', 'crelate-job-board'),
            'update_item' => __('Update Job Type', 'crelate-job-board'),
            'add_new_item' => __('Add New Job Type', 'crelate-job-board'),
            'new_item_name' => __('New Job Type Name', 'crelate-job-board'),
            'menu_name' => __('Job Types', 'crelate-job-board'),
        );
        
        register_taxonomy('job_type', array('crelate_job'), array(
            'hierarchical' => true,
            'labels' => $type_labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'job-type'),
            'show_in_rest' => true,
        ));
        
        // Experience Level
        $experience_labels = array(
            'name' => _x('Experience Levels', 'taxonomy general name', 'crelate-job-board'),
            'singular_name' => _x('Experience Level', 'taxonomy singular name', 'crelate-job-board'),
            'search_items' => __('Search Experience Levels', 'crelate-job-board'),
            'all_items' => __('All Experience Levels', 'crelate-job-board'),
            'parent_item' => __('Parent Experience Level', 'crelate-job-board'),
            'parent_item_colon' => __('Parent Experience Level:', 'crelate-job-board'),
            'edit_item' => __('Edit Experience Level', 'crelate-job-board'),
            'update_item' => __('Update Experience Level', 'crelate-job-board'),
            'add_new_item' => __('Add New Experience Level', 'crelate-job-board'),
            'new_item_name' => __('New Experience Level Name', 'crelate-job-board'),
            'menu_name' => __('Experience Levels', 'crelate-job-board'),
        );
        
        register_taxonomy('job_experience', array('crelate_job'), array(
            'hierarchical' => true,
            'labels' => $experience_labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'experience-level'),
            'show_in_rest' => true,
        ));
        
        // Remote Work Options
        $remote_labels = array(
            'name' => _x('Remote Work Options', 'taxonomy general name', 'crelate-job-board'),
            'singular_name' => _x('Remote Work Option', 'taxonomy singular name', 'crelate-job-board'),
            'search_items' => __('Search Remote Work Options', 'crelate-job-board'),
            'all_items' => __('All Remote Work Options', 'crelate-job-board'),
            'parent_item' => __('Parent Remote Work Option', 'crelate-job-board'),
            'parent_item_colon' => __('Parent Remote Work Option:', 'crelate-job-board'),
            'edit_item' => __('Edit Remote Work Option', 'crelate-job-board'),
            'update_item' => __('Update Remote Work Option', 'crelate-job-board'),
            'add_new_item' => __('Add New Remote Work Option', 'crelate-job-board'),
            'new_item_name' => __('New Remote Work Option Name', 'crelate-job-board'),
            'menu_name' => __('Remote Work Options', 'crelate-job-board'),
        );
        
        register_taxonomy('job_remote', array('crelate_job'), array(
            'hierarchical' => true,
            'labels' => $remote_labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'remote-work'),
            'show_in_rest' => true,
        ));
    }
    
    /**
     * Add default terms for taxonomies
     */
    public static function add_default_terms() {
        // Default job types
        $job_types = array(
            'Full Time',
            'Part Time',
            'Contract',
            'Temporary',
            'Internship'
        );
        
        foreach ($job_types as $type) {
            if (!term_exists($type, 'job_type')) {
                wp_insert_term($type, 'job_type');
            }
        }
        
        // Default experience levels
        $experience_levels = array(
            'Entry Level',
            'Mid Level',
            'Senior Level',
            'Executive Level'
        );
        
        foreach ($experience_levels as $level) {
            if (!term_exists($level, 'job_experience')) {
                wp_insert_term($level, 'job_experience');
            }
        }
        
        // Default remote work options
        $remote_options = array(
            'Remote',
            'Hybrid',
            'On-Site'
        );
        
        foreach ($remote_options as $option) {
            if (!term_exists($option, 'job_remote')) {
                wp_insert_term($option, 'job_remote');
            }
        }
    }
    
    /**
     * Get job meta data
     */
    public static function get_job_meta($post_id, $key = '') {
        if (empty($key)) {
            return array(
                'location' => get_post_meta($post_id, '_crelate_location', true),
                'department' => get_post_meta($post_id, '_crelate_department', true),
                'salary_min' => get_post_meta($post_id, '_crelate_salary_min', true),
                'salary_max' => get_post_meta($post_id, '_crelate_salary_max', true),
                'employment_type' => get_post_meta($post_id, '_crelate_employment_type', true),
                'experience_level' => get_post_meta($post_id, '_crelate_experience_level', true),
                'remote_work' => get_post_meta($post_id, '_crelate_remote_work', true),
                'apply_url' => get_post_meta($post_id, '_crelate_apply_url', true),
                'enable_apply' => get_post_meta($post_id, '_crelate_enable_apply', true),
                'application_deadline' => get_post_meta($post_id, '_crelate_application_deadline', true),
                'job_id' => get_post_meta($post_id, '_crelate_job_id', true)
            );
        }
        
        return get_post_meta($post_id, '_crelate_' . $key, true);
    }
    
    /**
     * Format salary range
     */
    public static function format_salary($min, $max, $currency = '$') {
        if (empty($min) && empty($max)) {
            return '';
        }
        
        if (!empty($min) && !empty($max)) {
            return $currency . number_format($min) . ' - ' . $currency . number_format($max);
        } elseif (!empty($min)) {
            return $currency . number_format($min) . '+';
        } else {
            return 'Up to ' . $currency . number_format($max);
        }
    }
    
    /**
     * Get job status badge
     */
    public static function get_status_badge($post_id) {
        $status = get_post_status($post_id);
        $deadline = get_post_meta($post_id, '_crelate_application_deadline', true);
        
        if ($status === 'publish') {
            if (!empty($deadline) && strtotime($deadline) < time()) {
                return '<span class="job-status expired">' . __('Expired', 'crelate-job-board') . '</span>';
            } else {
                return '<span class="job-status active">' . __('Active', 'crelate-job-board') . '</span>';
            }
        } else {
            return '<span class="job-status draft">' . __('Draft', 'crelate-job-board') . '</span>';
        }
    }
    
    /**
     * Check if job is expired
     */
    public static function is_job_expired($post_id) {
        $deadline = get_post_meta($post_id, '_crelate_application_deadline', true);
        
        if (!empty($deadline) && strtotime($deadline) < time()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Get jobs by criteria
     */
    public static function get_jobs_by_criteria($args = array()) {
        $default_args = array(
            'post_type' => 'crelate_job',
            'post_status' => 'publish',
            'posts_per_page' => 10,
            'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $default_args);
        
        // Add meta query for filtering
        $meta_query = array();
        
        if (!empty($args['location'])) {
            $meta_query[] = array(
                'key' => '_crelate_location',
                'value' => $args['location'],
                'compare' => 'LIKE'
            );
        }
        
        if (!empty($args['department'])) {
            $meta_query[] = array(
                'key' => '_crelate_department',
                'value' => $args['department'],
                'compare' => 'LIKE'
            );
        }
        
        if (!empty($args['employment_type'])) {
            $meta_query[] = array(
                'key' => '_crelate_employment_type',
                'value' => $args['employment_type'],
                'compare' => '='
            );
        }
        
        if (!empty($args['experience_level'])) {
            $meta_query[] = array(
                'key' => '_crelate_experience_level',
                'value' => $args['experience_level'],
                'compare' => '='
            );
        }
        
        if (!empty($args['remote_work'])) {
            $meta_query[] = array(
                'key' => '_crelate_remote_work',
                'value' => $args['remote_work'],
                'compare' => '='
            );
        }
        
        if (!empty($meta_query)) {
            $args['meta_query'] = $meta_query;
        }
        
        // Add search
        if (!empty($args['search'])) {
            $args['s'] = $args['search'];
        }
        
        return new WP_Query($args);
    }
}

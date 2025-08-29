<?php
/**
 * Crelate Job Board Applicants Class
 * Handles applicant data storage and management
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crelate_Applicants {
    
    /**
     * Table name
     */
    private $table_name;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'crelate_applicants';
        
        // Hook into WordPress
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Create table on plugin activation
        add_action('crelate_job_board_activate', array($this, 'create_table'));
        
        // Handle application submissions
        add_action('wp_ajax_crelate_submit_application', array($this, 'handle_application_submission'));
        add_action('wp_ajax_nopriv_crelate_submit_application', array($this, 'handle_application_submission'));
    }
    
    /**
     * Create the applicants table
     */
    public function create_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$this->table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            job_id bigint(20) unsigned NOT NULL,
            crelate_job_id varchar(255) NOT NULL,
            applicant_name varchar(255) NOT NULL,
            applicant_email varchar(255) NOT NULL,
            applicant_phone varchar(50) DEFAULT NULL,
            applicant_location varchar(255) DEFAULT NULL,
            cover_letter text DEFAULT NULL,
            applicant_linkedin varchar(500) DEFAULT NULL,
            applicant_website varchar(500) DEFAULT NULL,
            how_heard varchar(255) DEFAULT NULL,
            resume_file_path varchar(500) NOT NULL,
            resume_file_name varchar(255) NOT NULL,
            resume_file_size int(11) NOT NULL,
            resume_file_type varchar(100) NOT NULL,
            ip_address varchar(45) NOT NULL,
            user_agent text DEFAULT NULL,
            status varchar(50) DEFAULT 'new',
            submitted_at datetime NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY job_id (job_id),
            KEY crelate_job_id (crelate_job_id),
            KEY applicant_email (applicant_email),
            KEY status (status),
            KEY submitted_at (submitted_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Handle application submission
     */
    public function handle_application_submission() {
        try {
            // Verify nonce
            if (!check_ajax_referer('crelate_application_nonce', 'application_nonce', false)) {
                wp_send_json_error(array('message' => 'Security check failed'));
                return;
            }
            
            // Validate required fields
            $required_fields = array('applicant_name', 'applicant_email', 'job_id', 'crelate_job_id');
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
            if (empty($_FILES['resume_file']) || $_FILES['resume_file']['error'] !== UPLOAD_ERR_OK) {
                wp_send_json_error(array('message' => 'Please upload a resume file.'));
                return;
            }
            
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
            
            // Save resume file
            $resume_data = $this->save_resume_file($file);
            if (is_wp_error($resume_data)) {
                wp_send_json_error(array('message' => $resume_data->get_error_message()));
                return;
            }
            
            // Prepare application data
            $application_data = array(
                'job_id' => intval($_POST['job_id']),
                'crelate_job_id' => sanitize_text_field($_POST['crelate_job_id']),
                'applicant_name' => sanitize_text_field($_POST['applicant_name']),
                'applicant_email' => sanitize_email($_POST['applicant_email']),
                'applicant_phone' => sanitize_text_field($_POST['applicant_phone'] ?? ''),
                'applicant_location' => sanitize_text_field($_POST['applicant_location'] ?? ''),
                'cover_letter' => sanitize_textarea_field($_POST['cover_letter'] ?? ''),
                'applicant_linkedin' => esc_url_raw($_POST['applicant_linkedin'] ?? ''),
                'applicant_website' => esc_url_raw($_POST['applicant_website'] ?? ''),
                'how_heard' => sanitize_text_field($_POST['how_heard'] ?? ''),
                'resume_file_path' => $resume_data['file_path'],
                'resume_file_name' => $resume_data['file_name'],
                'resume_file_size' => $resume_data['file_size'],
                'resume_file_type' => $resume_data['file_type'],
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'submitted_at' => current_time('mysql')
            );
            
            // Save to database
            $applicant_id = $this->save_application($application_data);
            if (!$applicant_id) {
                wp_send_json_error(array('message' => 'Failed to save application. Please try again.'));
                return;
            }
            
            // Send email notification
            $this->send_application_notification($application_data);
            
            wp_send_json_success(array(
                'message' => '✅ Your application has been submitted successfully! We have received your resume and will review your application. You will hear from us soon.',
                'applicant_id' => $applicant_id
            ));
            
        } catch (Exception $e) {
            error_log('Crelate Application Error: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => 'An error occurred while submitting your application. Please try again.'
            ));
        }
    }
    
    /**
     * Save resume file to secure location
     */
    private function save_resume_file($file) {
        // Create upload directory structure
        $upload_dir = wp_upload_dir();
        $resume_dir = $upload_dir['basedir'] . '/nlmc-jobboard/resumes/' . date('Y/m');
        
        if (!wp_mkdir_p($resume_dir)) {
            return new WP_Error('upload_error', 'Failed to create upload directory');
        }
        
        // Generate unique filename
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $unique_filename = wp_generate_uuid4() . '.' . $file_extension;
        $file_path = $resume_dir . '/' . $unique_filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            return new WP_Error('upload_error', 'Failed to save uploaded file');
        }
        
        return array(
            'file_path' => $file_path,
            'file_name' => $file['name'],
            'file_size' => $file['size'],
            'file_type' => $file['type']
        );
    }
    
    /**
     * Save application to database
     */
    private function save_application($data) {
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->table_name,
            $data,
            array(
                '%d', // job_id
                '%s', // crelate_job_id
                '%s', // applicant_name
                '%s', // applicant_email
                '%s', // applicant_phone
                '%s', // applicant_location
                '%s', // cover_letter
                '%s', // applicant_linkedin
                '%s', // applicant_website
                '%s', // how_heard
                '%s', // resume_file_path
                '%s', // resume_file_name
                '%d', // resume_file_size
                '%s', // resume_file_type
                '%s', // ip_address
                '%s', // user_agent
                '%s'  // submitted_at
            )
        );
        
        if ($result === false) {
            error_log('Crelate Applicants: Database insert failed: ' . $wpdb->last_error);
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Send application notification email
     */
    private function send_application_notification($data) {
        $settings = get_option('crelate_job_board_settings', array());
        $notification_email = !empty($settings['notification_email']) ? $settings['notification_email'] : get_option('admin_email');
        
        $subject = sprintf(__('New Job Application: %s', 'crelate-job-board'), $data['applicant_name']);
        
        $message = sprintf(
            __("A new job application has been submitted:\n\nJob: %s\nApplicant: %s\nEmail: %s\nPhone: %s\nLocation: %s\nDate: %s\n\nView job: %s", 'crelate-job-board'),
            $data['job_title'] ?? get_the_title($data['job_id']),
            $data['applicant_name'],
            $data['applicant_email'],
            $data['applicant_phone'] ?: 'Not provided',
            $data['applicant_location'] ?: 'Not provided',
            $data['submitted_at'],
            get_permalink($data['job_id'])
        );
        
        if (!empty($data['cover_letter'])) {
            $message .= "\n\nCover Letter:\n" . $data['cover_letter'];
        }
        
        wp_mail($notification_email, $subject, $message);
    }
    
    /**
     * Get applicants with pagination and filters
     */
    public function get_applicants($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'per_page' => 20,
            'page' => 1,
            'search' => '',
            'job_id' => '',
            'status' => '',
            'orderby' => 'submitted_at',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where_conditions = array('1=1');
        $where_values = array();
        
        // Search filter
        if (!empty($args['search'])) {
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_conditions[] = '(applicant_name LIKE %s OR applicant_email LIKE %s OR resume_file_name LIKE %s)';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        // Job filter
        if (!empty($args['job_id'])) {
            $where_conditions[] = 'job_id = %d';
            $where_values[] = intval($args['job_id']);
        }
        
        // Status filter
        if (!empty($args['status'])) {
            $where_conditions[] = 'status = %s';
            $where_values[] = sanitize_text_field($args['status']);
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        // Build query
        $query = "SELECT a.*, p.post_title as job_title 
                  FROM {$this->table_name} a 
                  LEFT JOIN {$wpdb->posts} p ON a.job_id = p.ID 
                  WHERE {$where_clause}";
        
        // Add ORDER BY
        $allowed_orderby = array('submitted_at', 'applicant_name', 'job_title');
        $orderby = in_array($args['orderby'], $allowed_orderby) ? $args['orderby'] : 'submitted_at';
        $order = $args['order'] === 'ASC' ? 'ASC' : 'DESC';
        
        $query .= " ORDER BY a.{$orderby} {$order}";
        
        // Add LIMIT for pagination
        $offset = ($args['page'] - 1) * $args['per_page'];
        $query .= " LIMIT %d OFFSET %d";
        $where_values[] = $args['per_page'];
        $where_values[] = $offset;
        
        // Prepare and execute query
        $query = $wpdb->prepare($query, $where_values);
        $applicants = $wpdb->get_results($query);
        
        // Get total count for pagination
        $count_query = "SELECT COUNT(*) FROM {$this->table_name} a WHERE {$where_clause}";
        $count_query = $wpdb->prepare($count_query, array_slice($where_values, 0, -2));
        $total = $wpdb->get_var($count_query);
        
        return array(
            'applicants' => $applicants,
            'total' => intval($total),
            'pages' => ceil($total / $args['per_page']),
            'current_page' => $args['page']
        );
    }
    
    /**
     * Get single applicant by ID
     */
    public function get_applicant($id) {
        global $wpdb;
        
        $query = "SELECT a.*, p.post_title as job_title 
                  FROM {$this->table_name} a 
                  LEFT JOIN {$wpdb->posts} p ON a.job_id = p.ID 
                  WHERE a.id = %d";
        
        return $wpdb->get_row($wpdb->prepare($query, $id));
    }
    
    /**
     * Update applicant status
     */
    public function update_status($id, $status) {
        global $wpdb;
        
        return $wpdb->update(
            $this->table_name,
            array('status' => $status),
            array('id' => $id),
            array('%s'),
            array('%d')
        );
    }
    
    /**
     * Log file access
     */
    public function log_file_access($applicant_id, $requester_ip) {
        global $wpdb;
        
        $log_table = $wpdb->prefix . 'crelate_file_access_log';
        
        // Create log table if it doesn't exist
        $this->create_log_table();
        
        return $wpdb->insert(
            $log_table,
            array(
                'applicant_id' => $applicant_id,
                'ip_address' => $requester_ip,
                'accessed_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s')
        );
    }
    
    /**
     * Create file access log table
     */
    private function create_log_table() {
        global $wpdb;
        
        $log_table = $wpdb->prefix . 'crelate_file_access_log';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$log_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            applicant_id bigint(20) unsigned NOT NULL,
            ip_address varchar(45) NOT NULL,
            accessed_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY applicant_id (applicant_id),
            KEY accessed_at (accessed_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

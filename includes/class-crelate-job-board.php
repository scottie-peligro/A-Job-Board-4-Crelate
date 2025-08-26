<?php
/**
 * Main Crelate Job Board Plugin Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crelate_Job_Board {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * API instance
     */
    public $api;
    
    /**
     * Admin instance
     */
    public $admin;
    
    /**
     * Shortcodes instance
     */
    public $shortcodes;
    
    /**
     * AJAX instance
     */
    public $ajax;
    
    /**
     * Get plugin instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Initialize components
        $this->init_components();
        
        // Hook into WordPress
        $this->init_hooks();
    }
    
    /**
     * Initialize plugin components
     */
    private function init_components() {
        $this->api = new Crelate_API();
        $this->admin = new Crelate_Admin();
        $this->shortcodes = new Crelate_Shortcodes();
        // Removed Crelate_AJAX instantiation - now handled by Crelate_Templates class
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Load text domain
        add_action('init', array($this, 'load_textdomain'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Register custom post type
        add_action('init', array('Crelate_Job_Post_Type', 'register_post_type'));
        add_action('init', array('Crelate_Job_Post_Type', 'register_taxonomies'));
        
        // Add custom meta boxes
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
        
        // Add custom columns
        add_filter('manage_crelate_job_posts_columns', array($this, 'add_custom_columns'));
        add_action('manage_crelate_job_posts_custom_column', array($this, 'display_custom_columns'), 10, 2);
        
        // Add cron job for auto import
        add_action('crelate_job_board_import_cron', array($this, 'auto_import_jobs'));
        
        // Add rewrite rules
        add_action('init', array($this, 'add_rewrite_rules'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        
        // Add template redirect
        add_action('template_redirect', array($this, 'template_redirect'));

        // Track single job views
        add_action('wp', array($this, 'maybe_track_job_view'));
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Set up cron job if auto import is enabled
        $settings = get_option('crelate_job_board_settings');
        if (!empty($settings['auto_import'])) {
            if (!wp_next_scheduled('crelate_job_board_import_cron')) {
                $frequency = !empty($settings['import_frequency']) ? $settings['import_frequency'] : 'daily';
                wp_schedule_event(time(), $frequency, 'crelate_job_board_import_cron');
            }
        }
    }
    
    /**
     * Load text domain for internationalization
     */
    public function load_textdomain() {
        load_plugin_textdomain('crelate-job-board', false, dirname(CRELATE_JOB_BOARD_PLUGIN_BASENAME) . '/languages');
    }
    
    /**
     * Enqueue frontend scripts and styles
     * Note: Scripts are now handled by Crelate_Templates class
     */
    public function enqueue_scripts() {
        // Scripts are now handled by Crelate_Templates class to avoid conflicts
        // This method is kept for backward compatibility but does nothing
    }
    
    /**
 * Enqueue admin scripts and styles
 */
public function enqueue_admin_scripts($hook) {
    if (strpos($hook, 'crelate') !== false || strpos($hook, 'post.php') !== false || strpos($hook, 'post-new.php') !== false) {
        wp_enqueue_style(
            'crelate-job-board-admin',
            CRELATE_JOB_BOARD_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            CRELATE_JOB_BOARD_VERSION
        );
        
        wp_enqueue_script(
            'crelate-job-board-admin',
            CRELATE_JOB_BOARD_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            CRELATE_JOB_BOARD_VERSION,
            true
        );
        // Enable drag-and-drop in admin Styling builder
        wp_enqueue_script('jquery-ui-sortable');
        
        // Add this missing wp_localize_script
        wp_localize_script('crelate-job-board-admin', 'crelate_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('crelate_nonce'),
            'strings' => array(
                'loading' => __('Loading...', 'crelate-job-board'),
                'testing' => __('Testing...', 'crelate-job-board'),
                'importing' => __('Importing...', 'crelate-job-board'),
                'success' => __('Success!', 'crelate-job-board'),
                'error' => __('Error!', 'crelate-job-board')
            )
        ));
    }
}
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'crelate_job_details',
            __('Job Details', 'crelate-job-board'),
            array($this, 'job_details_meta_box'),
            'crelate_job',
            'normal',
            'high'
        );
        
        add_meta_box(
            'crelate_job_application',
            __('Application Settings', 'crelate-job-board'),
            array($this, 'job_application_meta_box'),
            'crelate_job',
            'side',
            'default'
        );
    }
    
    /**
     * Job details meta box
     */
    public function job_details_meta_box($post) {
        wp_nonce_field('crelate_job_details', 'crelate_job_details_nonce');
        
        $job_id = get_post_meta($post->ID, '_crelate_job_id', true);
        $location = get_post_meta($post->ID, '_crelate_location', true);
        $department = get_post_meta($post->ID, '_crelate_department', true);
        $salary_min = get_post_meta($post->ID, '_crelate_salary_min', true);
        $salary_max = get_post_meta($post->ID, '_crelate_salary_max', true);
        $employment_type = get_post_meta($post->ID, '_crelate_employment_type', true);
        $experience_level = get_post_meta($post->ID, '_crelate_experience_level', true);
        $remote_work = get_post_meta($post->ID, '_crelate_remote_work', true);
        $apply_url = get_post_meta($post->ID, '_crelate_apply_url', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="crelate_job_id"><?php _e('Crelate Job ID', 'crelate-job-board'); ?></label></th>
                <td><input type="text" id="crelate_job_id" name="crelate_job_id" value="<?php echo esc_attr($job_id); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="crelate_location"><?php _e('Location', 'crelate-job-board'); ?></label></th>
                <td><input type="text" id="crelate_location" name="crelate_location" value="<?php echo esc_attr($location); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="crelate_department"><?php _e('Department', 'crelate-job-board'); ?></label></th>
                <td><input type="text" id="crelate_department" name="crelate_department" value="<?php echo esc_attr($department); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="crelate_salary_min"><?php _e('Salary Min', 'crelate-job-board'); ?></label></th>
                <td><input type="number" id="crelate_salary_min" name="crelate_salary_min" value="<?php echo esc_attr($salary_min); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="crelate_salary_max"><?php _e('Salary Max', 'crelate-job-board'); ?></label></th>
                <td><input type="number" id="crelate_salary_max" name="crelate_salary_max" value="<?php echo esc_attr($salary_max); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="crelate_employment_type"><?php _e('Employment Type', 'crelate-job-board'); ?></label></th>
                <td>
                    <select id="crelate_employment_type" name="crelate_employment_type">
                        <option value=""><?php _e('Select Type', 'crelate-job-board'); ?></option>
                        <option value="full-time" <?php selected($employment_type, 'full-time'); ?>><?php _e('Full Time', 'crelate-job-board'); ?></option>
                        <option value="part-time" <?php selected($employment_type, 'part-time'); ?>><?php _e('Part Time', 'crelate-job-board'); ?></option>
                        <option value="contract" <?php selected($employment_type, 'contract'); ?>><?php _e('Contract', 'crelate-job-board'); ?></option>
                        <option value="temporary" <?php selected($employment_type, 'temporary'); ?>><?php _e('Temporary', 'crelate-job-board'); ?></option>
                        <option value="internship" <?php selected($employment_type, 'internship'); ?>><?php _e('Internship', 'crelate-job-board'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="crelate_experience_level"><?php _e('Experience Level', 'crelate-job-board'); ?></label></th>
                <td>
                    <select id="crelate_experience_level" name="crelate_experience_level">
                        <option value=""><?php _e('Select Level', 'crelate-job-board'); ?></option>
                        <option value="entry" <?php selected($experience_level, 'entry'); ?>><?php _e('Entry Level', 'crelate-job-board'); ?></option>
                        <option value="mid" <?php selected($experience_level, 'mid'); ?>><?php _e('Mid Level', 'crelate-job-board'); ?></option>
                        <option value="senior" <?php selected($experience_level, 'senior'); ?>><?php _e('Senior Level', 'crelate-job-board'); ?></option>
                        <option value="executive" <?php selected($experience_level, 'executive'); ?>><?php _e('Executive Level', 'crelate-job-board'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="crelate_remote_work"><?php _e('Remote Work', 'crelate-job-board'); ?></label></th>
                <td>
                    <select id="crelate_remote_work" name="crelate_remote_work">
                        <option value=""><?php _e('Select Option', 'crelate-job-board'); ?></option>
                        <option value="remote" <?php selected($remote_work, 'remote'); ?>><?php _e('Remote', 'crelate-job-board'); ?></option>
                        <option value="hybrid" <?php selected($remote_work, 'hybrid'); ?>><?php _e('Hybrid', 'crelate-job-board'); ?></option>
                        <option value="on-site" <?php selected($remote_work, 'on-site'); ?>><?php _e('On-Site', 'crelate-job-board'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="crelate_apply_url"><?php _e('Apply URL', 'crelate-job-board'); ?></label></th>
                <td><input type="url" id="crelate_apply_url" name="crelate_apply_url" value="<?php echo esc_attr($apply_url); ?>" class="regular-text" /></td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Job application meta box
     */
    public function job_application_meta_box($post) {
        $enable_apply = get_post_meta($post->ID, '_crelate_enable_apply', true);
        $application_deadline = get_post_meta($post->ID, '_crelate_application_deadline', true);
        
        ?>
        <p>
            <label>
                <input type="checkbox" name="crelate_enable_apply" value="1" <?php checked($enable_apply, '1'); ?> />
                <?php _e('Enable applications for this job', 'crelate-job-board'); ?>
            </label>
        </p>
        <p>
            <label for="crelate_application_deadline"><?php _e('Application Deadline', 'crelate-job-board'); ?></label><br>
            <input type="date" id="crelate_application_deadline" name="crelate_application_deadline" value="<?php echo esc_attr($application_deadline); ?>" />
        </p>
        <?php
    }
    
    /**
     * Save meta boxes
     */
    public function save_meta_boxes($post_id) {
        // Check nonce
        if (!isset($_POST['crelate_job_details_nonce']) || !wp_verify_nonce($_POST['crelate_job_details_nonce'], 'crelate_job_details')) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save job details
        $fields = array(
            'crelate_job_id',
            'crelate_location',
            'crelate_department',
            'crelate_salary_min',
            'crelate_salary_max',
            'crelate_employment_type',
            'crelate_experience_level',
            'crelate_remote_work',
            'crelate_apply_url'
        );
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
            }
        }
        
        // Save application settings
        $enable_apply = isset($_POST['crelate_enable_apply']) ? '1' : '0';
        update_post_meta($post_id, '_crelate_enable_apply', $enable_apply);
        
        if (isset($_POST['crelate_application_deadline'])) {
            update_post_meta($post_id, '_crelate_application_deadline', sanitize_text_field($_POST['crelate_application_deadline']));
        }
    }
    
    /**
     * Add custom columns
     */
    public function add_custom_columns($columns) {
        $new_columns = array();
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'title') {
                $new_columns['location'] = __('Location', 'crelate-job-board');
                $new_columns['department'] = __('Department', 'crelate-job-board');
                $new_columns['employment_type'] = __('Employment Type', 'crelate-job-board');
                $new_columns['status'] = __('Status', 'crelate-job-board');
            }
        }
        return $new_columns;
    }
    
    /**
     * Display custom columns
     */
    public function display_custom_columns($column, $post_id) {
        switch ($column) {
            case 'location':
                echo esc_html(get_post_meta($post_id, '_crelate_location', true));
                break;
            case 'department':
                echo esc_html(get_post_meta($post_id, '_crelate_department', true));
                break;
            case 'employment_type':
                echo esc_html(get_post_meta($post_id, '_crelate_employment_type', true));
                break;
            case 'status':
                $status = get_post_status($post_id);
                echo esc_html(ucfirst($status));
                break;
        }
    }
    
    /**
     * Add rewrite rules
     */
    public function add_rewrite_rules() {
        add_rewrite_rule(
            '^jobs/([^/]+)/?$',
            'index.php?post_type=crelate_job&name=$matches[1]',
            'top'
        );
    }
    
    /**
     * Add query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'job_search';
        $vars[] = 'job_location';
        $vars[] = 'job_department';
        $vars[] = 'job_type';
        return $vars;
    }
    
    /**
     * Template redirect
     */
    public function template_redirect() {
        if (is_post_type_archive('crelate_job')) {
            // Handle job board archive page
            $this->load_job_board_template();
        } elseif (is_singular('crelate_job')) {
            // Handle single job page
            $this->load_single_job_template();
        }
    }
    
    /**
     * Load job board template
     */
    private function load_job_board_template() {
        $template = locate_template('archive-crelate_job.php');
        if (!$template) {
            $template = CRELATE_JOB_BOARD_PLUGIN_DIR . 'templates/archive-crelate_job.php';
        }
        if (file_exists($template)) {
            include $template;
            exit;
        }
    }
    
    /**
     * Load single job template
     */
    private function load_single_job_template() {
        $template = locate_template('single-crelate_job.php');
        if (!$template) {
            $template = CRELATE_JOB_BOARD_PLUGIN_DIR . 'templates/single-crelate_job.php';
        }
        if (file_exists($template)) {
            include $template;
            exit;
        }
    }

    /**
     * Track job view counts
     */
    public function maybe_track_job_view() {
        if (is_singular('crelate_job')) {
            global $post;
            if ($post && $post->ID) {
                $views = get_option('crelate_job_views', array());
                $views[$post->ID] = isset($views[$post->ID]) ? intval($views[$post->ID]) + 1 : 1;
                update_option('crelate_job_views', $views);
            }
        }
    }
    
    /**
     * Auto import jobs
     */
    public function auto_import_jobs() {
        $settings = get_option('crelate_job_board_settings');
        if (!empty($settings['api_key'])) {
            $this->api->import_jobs();
        }
    }
}

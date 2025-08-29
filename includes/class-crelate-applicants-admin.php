<?php
/**
 * Crelate Job Board Applicants Admin Class
 * Handles admin interface for managing applicants
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crelate_Applicants_Admin {
    
    /**
     * Applicants instance
     */
    private $applicants;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->applicants = new Crelate_Applicants();
        
        // Hook into WordPress
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_crelate_update_applicant_status', array($this, 'update_applicant_status'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'crelate-job-board',
            __('Applicants', 'crelate-job-board'),
            __('Applicants', 'crelate-job-board'),
            'manage_options',
            'crelate-applicants',
            array($this, 'applicants_page')
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'crelate-applicants') !== false) {
            wp_enqueue_script('jquery');
            wp_enqueue_script('jquery-ui-datepicker');
            wp_enqueue_style('jquery-ui', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
            
            wp_enqueue_style(
                'crelate-applicants-admin',
                CRELATE_JOB_BOARD_PLUGIN_URL . 'assets/css/applicants-admin.css',
                array(),
                CRELATE_JOB_BOARD_VERSION
            );
            
            wp_enqueue_script(
                'crelate-applicants-admin',
                CRELATE_JOB_BOARD_PLUGIN_URL . 'assets/js/applicants-admin.js',
                array('jquery'),
                CRELATE_JOB_BOARD_VERSION,
                true
            );
            
            wp_localize_script('crelate-applicants-admin', 'crelateApplicants', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('crelate_applicants_nonce'),
                'strings' => array(
                    'confirm_delete' => __('Are you sure you want to delete this applicant?', 'crelate-job-board'),
                    'status_updated' => __('Status updated successfully.', 'crelate-job-board'),
                    'error' => __('An error occurred. Please try again.', 'crelate-job-board')
                )
            ));
        }
    }
    
    /**
     * Applicants list page
     */
    public function applicants_page() {
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        
        if ($action === 'view' && isset($_GET['id'])) {
            $this->applicant_detail_page(intval($_GET['id']));
        } else {
            $this->applicants_list_page();
        }
    }
    
    /**
     * Applicants list page
     */
    private function applicants_list_page() {
        // Handle filters and pagination
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        $job_id = isset($_GET['job_id']) ? intval($_GET['job_id']) : '';
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        
        // Get applicants
        $args = array(
            'per_page' => 20,
            'page' => $page,
            'search' => $search,
            'job_id' => $job_id,
            'status' => $status
        );
        
        $result = $this->applicants->get_applicants($args);
        
        // Get jobs for filter dropdown
        $jobs = get_posts(array(
            'post_type' => 'crelate_job',
            'numberposts' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('Applicants', 'crelate-job-board'); ?></h1>
            
            <!-- Search and Filters -->
            <div class="tablenav top">
                <form method="get" action="">
                    <input type="hidden" name="page" value="crelate-applicants">
                    
                    <div class="alignleft actions">
                        <input type="text" name="search" value="<?php echo esc_attr($search); ?>" placeholder="<?php _e('Search by name, email, or resume...', 'crelate-job-board'); ?>" style="width: 250px;">
                        
                        <select name="job_id">
                            <option value=""><?php _e('All Jobs', 'crelate-job-board'); ?></option>
                            <?php foreach ($jobs as $job): ?>
                                <option value="<?php echo $job->ID; ?>" <?php selected($job_id, $job->ID); ?>>
                                    <?php echo esc_html($job->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <select name="status">
                            <option value=""><?php _e('All Statuses', 'crelate-job-board'); ?></option>
                            <option value="new" <?php selected($status, 'new'); ?>><?php _e('New', 'crelate-job-board'); ?></option>
                            <option value="reviewed" <?php selected($status, 'reviewed'); ?>><?php _e('Reviewed', 'crelate-job-board'); ?></option>
                            <option value="contacted" <?php selected($status, 'contacted'); ?>><?php _e('Contacted', 'crelate-job-board'); ?></option>
                            <option value="rejected" <?php selected($status, 'rejected'); ?>><?php _e('Rejected', 'crelate-job-board'); ?></option>
                        </select>
                        
                        <input type="submit" class="button" value="<?php _e('Filter', 'crelate-job-board'); ?>">
                    </div>
                </form>
                
                <div class="tablenav-pages">
                    <?php
                    $total_pages = $result['pages'];
                    $current_page = $result['current_page'];
                    
                    if ($total_pages > 1) {
                        echo paginate_links(array(
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => __('&laquo;'),
                            'next_text' => __('&raquo;'),
                            'total' => $total_pages,
                            'current' => $current_page
                        ));
                    }
                    ?>
                </div>
            </div>
            
            <!-- Applicants Table -->
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Applicant', 'crelate-job-board'); ?></th>
                        <th><?php _e('Job', 'crelate-job-board'); ?></th>
                        <th><?php _e('Date Applied', 'crelate-job-board'); ?></th>
                        <th><?php _e('Status', 'crelate-job-board'); ?></th>
                        <th><?php _e('Resume', 'crelate-job-board'); ?></th>
                        <th><?php _e('Actions', 'crelate-job-board'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($result['applicants'])): ?>
                        <tr>
                            <td colspan="6"><?php _e('No applicants found.', 'crelate-job-board'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($result['applicants'] as $applicant): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($applicant->applicant_name); ?></strong><br>
                                    <small><?php echo esc_html($applicant->applicant_email); ?></small>
                                    <?php if (!empty($applicant->applicant_phone)): ?>
                                        <br><small><?php echo esc_html($applicant->applicant_phone); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo get_permalink($applicant->job_id); ?>" target="_blank">
                                        <?php echo esc_html($applicant->job_title); ?>
                                    </a>
                                    <?php if (!empty($applicant->crelate_job_id)): ?>
                                        <br><small><?php _e('Crelate ID:', 'crelate-job-board'); ?> <?php echo esc_html($applicant->crelate_job_id); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($applicant->submitted_at)); ?>
                                </td>
                                <td>
                                    <select class="applicant-status" data-applicant-id="<?php echo $applicant->id; ?>">
                                        <option value="new" <?php selected($applicant->status, 'new'); ?>><?php _e('New', 'crelate-job-board'); ?></option>
                                        <option value="reviewed" <?php selected($applicant->status, 'reviewed'); ?>><?php _e('Reviewed', 'crelate-job-board'); ?></option>
                                        <option value="contacted" <?php selected($applicant->status, 'contacted'); ?>><?php _e('Contacted', 'crelate-job-board'); ?></option>
                                        <option value="rejected" <?php selected($applicant->status, 'rejected'); ?>><?php _e('Rejected', 'crelate-job-board'); ?></option>
                                    </select>
                                </td>
                                <td>
                                    <?php if (!empty($applicant->resume_file_name)): ?>
                                        <a href="<?php echo $this->get_resume_download_url($applicant->id); ?>" target="_blank" class="button button-small">
                                            <?php _e('Download', 'crelate-job-board'); ?>
                                        </a>
                                        <br><small><?php echo esc_html($applicant->resume_file_name); ?></small>
                                    <?php else: ?>
                                        <span class="description"><?php _e('No resume', 'crelate-job-board'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo add_query_arg(array('action' => 'view', 'id' => $applicant->id)); ?>" class="button button-small">
                                        <?php _e('View Details', 'crelate-job-board'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <?php
                        echo paginate_links(array(
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => __('&laquo;'),
                            'next_text' => __('&raquo;'),
                            'total' => $total_pages,
                            'current' => $current_page
                        ));
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Applicant detail page
     */
    private function applicant_detail_page($applicant_id) {
        $applicant = $this->applicants->get_applicant($applicant_id);
        
        if (!$applicant) {
            wp_die(__('Applicant not found.', 'crelate-job-board'));
        }
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">
                <?php _e('Applicant Details', 'crelate-job-board'); ?>
            </h1>
            <a href="<?php echo admin_url('admin.php?page=crelate-applicants'); ?>" class="page-title-action">
                <?php _e('← Back to Applicants', 'crelate-job-board'); ?>
            </a>
            
            <div class="applicant-details">
                <div class="postbox">
                    <h2 class="hndle"><?php echo esc_html($applicant->applicant_name); ?></h2>
                    <div class="inside">
                        <table class="form-table">
                            <tr>
                                <th><?php _e('Email', 'crelate-job-board'); ?></th>
                                <td><a href="mailto:<?php echo esc_attr($applicant->applicant_email); ?>"><?php echo esc_html($applicant->applicant_email); ?></a></td>
                            </tr>
                            <?php if (!empty($applicant->applicant_phone)): ?>
                                <tr>
                                    <th><?php _e('Phone', 'crelate-job-board'); ?></th>
                                    <td><?php echo esc_html($applicant->applicant_phone); ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if (!empty($applicant->applicant_location)): ?>
                                <tr>
                                    <th><?php _e('Location', 'crelate-job-board'); ?></th>
                                    <td><?php echo esc_html($applicant->applicant_location); ?></td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <th><?php _e('Job Applied For', 'crelate-job-board'); ?></th>
                                <td>
                                    <a href="<?php echo get_permalink($applicant->job_id); ?>" target="_blank">
                                        <?php echo esc_html($applicant->job_title); ?>
                                    </a>
                                    <?php if (!empty($applicant->crelate_job_id)): ?>
                                        <br><small><?php _e('Crelate Job ID:', 'crelate-job-board'); ?> <?php echo esc_html($applicant->crelate_job_id); ?></small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Date Applied', 'crelate-job-board'); ?></th>
                                <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($applicant->submitted_at)); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Status', 'crelate-job-board'); ?></th>
                                <td>
                                    <select class="applicant-status" data-applicant-id="<?php echo $applicant->id; ?>">
                                        <option value="new" <?php selected($applicant->status, 'new'); ?>><?php _e('New', 'crelate-job-board'); ?></option>
                                        <option value="reviewed" <?php selected($applicant->status, 'reviewed'); ?>><?php _e('Reviewed', 'crelate-job-board'); ?></option>
                                        <option value="contacted" <?php selected($applicant->status, 'contacted'); ?>><?php _e('Contacted', 'crelate-job-board'); ?></option>
                                        <option value="rejected" <?php selected($applicant->status, 'rejected'); ?>><?php _e('Rejected', 'crelate-job-board'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <?php if (!empty($applicant->applicant_linkedin)): ?>
                                <tr>
                                    <th><?php _e('LinkedIn', 'crelate-job-board'); ?></th>
                                    <td><a href="<?php echo esc_url($applicant->applicant_linkedin); ?>" target="_blank"><?php echo esc_html($applicant->applicant_linkedin); ?></a></td>
                                </tr>
                            <?php endif; ?>
                            <?php if (!empty($applicant->applicant_website)): ?>
                                <tr>
                                    <th><?php _e('Website', 'crelate-job-board'); ?></th>
                                    <td><a href="<?php echo esc_url($applicant->applicant_website); ?>" target="_blank"><?php echo esc_html($applicant->applicant_website); ?></a></td>
                                </tr>
                            <?php endif; ?>
                            <?php if (!empty($applicant->how_heard)): ?>
                                <tr>
                                    <th><?php _e('How They Heard', 'crelate-job-board'); ?></th>
                                    <td><?php echo esc_html($applicant->how_heard); ?></td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <th><?php _e('IP Address', 'crelate-job-board'); ?></th>
                                <td><?php echo esc_html($applicant->ip_address); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <?php if (!empty($applicant->cover_letter)): ?>
                    <div class="postbox">
                        <h2 class="hndle"><?php _e('Cover Letter', 'crelate-job-board'); ?></h2>
                        <div class="inside">
                            <div class="cover-letter-content">
                                <?php echo wpautop(esc_html($applicant->cover_letter)); ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($applicant->resume_file_name)): ?>
                    <div class="postbox">
                        <h2 class="hndle"><?php _e('Resume', 'crelate-job-board'); ?></h2>
                        <div class="inside">
                            <p>
                                <strong><?php _e('File:', 'crelate-job-board'); ?></strong> <?php echo esc_html($applicant->resume_file_name); ?><br>
                                <strong><?php _e('Size:', 'crelate-job-board'); ?></strong> <?php echo size_format($applicant->resume_file_size); ?><br>
                                <strong><?php _e('Type:', 'crelate-job-board'); ?></strong> <?php echo esc_html($applicant->resume_file_type); ?>
                            </p>
                            <p>
                                <a href="<?php echo $this->get_resume_download_url($applicant->id); ?>" class="button button-primary" target="_blank">
                                    <?php _e('Download Resume', 'crelate-job-board'); ?>
                                </a>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get resume download URL
     */
    private function get_resume_download_url($applicant_id) {
        $secret_key = wp_salt('auth');
        $expiry = time() + (24 * 60 * 60); // 24 hours
        $data = $applicant_id . '|' . $expiry;
        $signature = hash_hmac('sha256', $data, $secret_key);
        
        return add_query_arg(array(
            'crelate_download' => 'resume',
            'applicant_id' => $applicant_id,
            'expiry' => $expiry,
            'signature' => $signature
        ), home_url());
    }
    
    /**
     * Update applicant status via AJAX
     */
    public function update_applicant_status() {
        check_ajax_referer('crelate_applicants_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        $applicant_id = intval($_POST['applicant_id']);
        $status = sanitize_text_field($_POST['status']);
        
        $allowed_statuses = array('new', 'reviewed', 'contacted', 'rejected');
        if (!in_array($status, $allowed_statuses)) {
            wp_send_json_error(array('message' => 'Invalid status'));
            return;
        }
        
        $result = $this->applicants->update_status($applicant_id, $status);
        
        if ($result !== false) {
            wp_send_json_success(array('message' => 'Status updated successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to update status'));
        }
    }
}

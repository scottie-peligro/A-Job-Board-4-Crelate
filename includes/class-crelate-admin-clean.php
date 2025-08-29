<?php
/**
 * Crelate Job Board Admin Class - Clean Version
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crelate_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
        add_action('admin_notices', array($this, 'admin_notices'));
        add_action('wp_ajax_crelate_test_connection', array($this, 'test_connection'));
        add_action('wp_ajax_crelate_import_jobs', array($this, 'import_jobs'));
        add_action('wp_ajax_crelate_get_import_stats', array($this, 'get_import_stats'));
        add_action('wp_ajax_crelate_get_current_stats', array($this, 'get_current_stats'));
        add_action('update_option_crelate_job_board_settings', array($this, 'update_cron_job'), 10, 2);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('A Job Board 4 Crelate', 'crelate-job-board'),
            __('Job Board', 'crelate-job-board'),
            'manage_options',
            'crelate-job-board',
            array($this, 'settings_page'),
            'dashicons-businessman',
            30
        );
        
        add_submenu_page(
            'crelate-job-board',
            __('Settings', 'crelate-job-board'),
            __('Settings', 'crelate-job-board'),
            'manage_options',
            'crelate-job-board',
            array($this, 'settings_page')
        );
        
        add_submenu_page(
            'crelate-job-board',
            __('Statistics', 'crelate-job-board'),
            __('Statistics', 'crelate-job-board'),
            'manage_options',
            'crelate-statistics',
            array($this, 'statistics_page')
        );
        
        add_submenu_page(
            'crelate-job-board',
            __('Test Page', 'crelate-job-board'),
            __('Test Page', 'crelate-job-board'),
            'manage_options',
            'crelate-test-page',
            array($this, 'test_page')
        );
    }
    
    /**
     * Initialize settings
     */
    public function init_settings() {
        register_setting('crelate_job_board_settings', 'crelate_job_board_settings', array($this, 'sanitize_settings'));
        register_setting('crelate_job_board_styling', 'crelate_job_board_styling', array($this, 'sanitize_styling_settings'));
        
        add_settings_section(
            'crelate_api_settings',
            __('API Settings', 'crelate-job-board'),
            array($this, 'api_settings_section'),
            'crelate_job_board_settings'
        );
        
        add_settings_field(
            'api_key',
            __('API Key', 'crelate-job-board'),
            array($this, 'api_key_field'),
            'crelate_job_board_settings',
            'crelate_api_settings'
        );
        
        add_settings_field(
            'api_endpoint',
            __('API Endpoint', 'crelate-job-board'),
            array($this, 'api_endpoint_field'),
            'crelate_job_board_settings',
            'crelate_api_settings'
        );
        
        add_settings_field(
            'portal_id',
            __('Portal ID', 'crelate-job-board'),
            array($this, 'portal_id_field'),
            'crelate_job_board_settings',
            'crelate_api_settings'
        );
        
        // Add other settings sections and fields as needed
    }
    
    /**
     * API settings section
     */
    public function api_settings_section() {
        echo '<p>' . __('Configure your Crelate API connection settings below.', 'crelate-job-board') . '</p>';
    }
    
    /**
     * API key field
     */
    public function api_key_field() {
        $settings = get_option('crelate_job_board_settings');
        $api_key = !empty($settings['api_key']) ? $settings['api_key'] : '';
        // Mask for display: show first 4, rest as *
        $display_value = '';
        if (!empty($api_key)) {
            $first = substr($api_key, 0, 4);
            $display_value = $first . str_repeat('*', max(0, strlen($api_key) - 4));
        }
        ?>
        <input type="text" name="crelate_job_board_settings[api_key]" value="<?php echo esc_attr($display_value); ?>" class="regular-text" />
        <p class="description"><?php _e('Enter your Crelate API key. The input field shows only the first 4 characters when a key is saved.', 'crelate-job-board'); ?></p>
        <?php
    }
    
    /**
     * API endpoint field
     */
    public function api_endpoint_field() {
        $settings = get_option('crelate_job_board_settings');
        $api_endpoint = !empty($settings['api_endpoint']) ? $settings['api_endpoint'] : 'https://app.crelate.com/api/pub/v1';
        ?>
        <input type="url" name="crelate_job_board_settings[api_endpoint]" value="<?php echo esc_attr($api_endpoint); ?>" class="regular-text" />
        <p class="description"><?php _e('Enter the Crelate API endpoint URL.', 'crelate-job-board'); ?></p>
        <?php
    }

    /**
     * Portal ID field
     */
    public function portal_id_field() {
        $settings = get_option('crelate_job_board_settings');
        $portal_id = !empty($settings['portal_id']) ? $settings['portal_id'] : '';
        ?>
        <input type="text" name="crelate_job_board_settings[portal_id]" value="<?php echo esc_attr($portal_id); ?>" class="regular-text" />
        <p class="description"><?php _e('Enter your Crelate portal/company slug used in job URLs.', 'crelate-job-board'); ?></p>
        <?php
    }
    
    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        $sanitized['api_key'] = sanitize_text_field($input['api_key'] ?? '');
        $sanitized['api_endpoint'] = esc_url_raw($input['api_endpoint'] ?? '');
        $sanitized['portal_id'] = sanitize_text_field($input['portal_id'] ?? '');
        
        return $sanitized;
    }
    
    /**
     * Sanitize styling settings
     */
    public function sanitize_styling_settings($input) {
        $sanitized = array();
        // Add styling settings sanitization as needed
        return $sanitized;
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        $this->admin_page_html();
    }
    
    /**
     * Admin page HTML
     */
    public function admin_page_html() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $settings = get_option('crelate_job_board_settings');
        $enable_onboarding = isset($settings['enable_onboarding']) ? $settings['enable_onboarding'] : '1';
        
        // Set default tab based on onboarding setting
        $default_tab = $enable_onboarding === '1' ? 'onboarding' : 'general';
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : $default_tab;
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <nav class="nav-tab-wrapper">
                <a href="?page=crelate-job-board&tab=onboarding" class="nav-tab <?php echo $active_tab === 'onboarding' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Onboarding', 'crelate-job-board'); ?>
                </a>
                <a href="?page=crelate-job-board&tab=general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('General Settings', 'crelate-job-board'); ?>
                </a>
                <a href="?page=crelate-job-board&tab=styling" class="nav-tab <?php echo $active_tab === 'styling' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Styling', 'crelate-job-board'); ?>
                </a>
                <a href="?page=crelate-job-board&tab=testing" class="nav-tab <?php echo $active_tab === 'testing' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Testing', 'crelate-job-board'); ?>
                </a>
            </nav>
            
            <div class="tab-content">
                <?php
                switch ($active_tab) {
                    case 'onboarding':
                        $this->onboarding_tab();
                        break;
                    case 'general':
                        $this->general_settings_tab();
                        break;
                    case 'styling':
                        $this->styling_tab();
                        break;
                    case 'testing':
                        $this->testing_tab();
                        break;
                    default:
                        $this->general_settings_tab();
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Onboarding tab
     */
    public function onboarding_tab() {
        $settings = get_option('crelate_job_board_settings');
        ?>
        <div class="crelate-onboarding">
            <h2><?php _e('Welcome to Crelate Job Board', 'crelate-job-board'); ?></h2>
            <p><?php _e('Configure your API settings to get started.', 'crelate-job-board'); ?></p>
            
            <form method="post" action="options.php">
                <?php settings_fields('crelate_job_board_settings'); ?>
                <?php do_settings_sections('crelate_job_board_settings'); ?>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * General settings tab
     */
    public function general_settings_tab() {
        echo '<h2>' . __('General Settings', 'crelate-job-board') . '</h2>';
        echo '<p>' . __('General settings will be configured here.', 'crelate-job-board') . '</p>';
    }
    
    /**
     * Styling tab
     */
    public function styling_tab() {
        echo '<h2>' . __('Styling Settings', 'crelate-job-board') . '</h2>';
        echo '<p>' . __('Styling settings will be configured here.', 'crelate-job-board') . '</p>';
    }
    
    /**
     * Testing tab
     */
    public function testing_tab() {
        echo '<h2>' . __('Testing', 'crelate-job-board') . '</h2>';
        echo '<p>' . __('Testing tools will be available here.', 'crelate-job-board') . '</p>';
    }
    
    /**
     * Statistics page
     */
    public function statistics_page() {
        echo '<h1>' . __('Statistics', 'crelate-job-board') . '</h1>';
        echo '<p>' . __('Statistics will be displayed here.', 'crelate-job-board') . '</p>';
    }
    
    /**
     * Test page
     */
    public function test_page() {
        echo '<h1>' . __('Test Page', 'crelate-job-board') . '</h1>';
        echo '<p>' . __('Test functionality will be available here.', 'crelate-job-board') . '</p>';
    }
    
    /**
     * Admin notices
     */
    public function admin_notices() {
        // Add admin notices as needed
    }
    
    /**
     * Test connection
     */
    public function test_connection() {
        // Add test connection functionality
    }
    
    /**
     * Import jobs
     */
    public function import_jobs() {
        // Add import jobs functionality
    }
    
    /**
     * Get import stats
     */
    public function get_import_stats() {
        // Add get import stats functionality
    }
    
    /**
     * Get current stats
     */
    public function get_current_stats() {
        // Add get current stats functionality
    }
    
    /**
     * Update cron job
     */
    public function update_cron_job($old_value, $new_value) {
        // Add cron job update functionality
    }
}

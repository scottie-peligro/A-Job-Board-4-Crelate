<?php
/**
 * Crelate Job Board Admin Class
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
        // Removed additional application fields feature
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
        

        
        add_settings_section(
            'crelate_import_settings',
            __('Import Settings', 'crelate-job-board'),
            array($this, 'import_settings_section'),
            'crelate_job_board_settings'
        );
        
        add_settings_field(
            'auto_import',
            __('Auto Import', 'crelate-job-board'),
            array($this, 'auto_import_field'),
            'crelate_job_board_settings',
            'crelate_import_settings'
        );
        
        add_settings_field(
            'import_frequency',
            __('Import Frequency', 'crelate-job-board'),
            array($this, 'import_frequency_field'),
            'crelate_job_board_settings',
            'crelate_import_settings'
        );
        
        add_settings_section(
            'crelate_display_settings',
            __('Display Settings', 'crelate-job-board'),
            array($this, 'display_settings_section'),
            'crelate_job_board_settings'
        );
        
        add_settings_field(
            'jobs_per_page',
            __('Jobs Per Page', 'crelate-job-board'),
            array($this, 'jobs_per_page_field'),
            'crelate_job_board_settings',
            'crelate_display_settings'
        );
        
        add_settings_field(
            'enable_search',
            __('Enable Search', 'crelate-job-board'),
            array($this, 'enable_search_field'),
            'crelate_job_board_settings',
            'crelate_display_settings'
        );
        
        add_settings_field(
            'enable_filters',
            __('Enable Filters', 'crelate-job-board'),
            array($this, 'enable_filters_field'),
            'crelate_job_board_settings',
            'crelate_display_settings'
        );
        
        add_settings_field(
            'enable_apply',
            __('Enable Apply Button', 'crelate-job-board'),
            array($this, 'enable_apply_field'),
            'crelate_job_board_settings',
            'crelate_display_settings'
        );
        
        add_settings_field(
            'apply_redirect_url',
            __('Apply Redirect URL', 'crelate-job-board'),
            array($this, 'apply_redirect_url_field'),
            'crelate_job_board_settings',
            'crelate_display_settings'
        );

        add_settings_field(
            'input_height_mode',
            __('Form Input Height', 'crelate-job-board'),
            array($this, 'input_height_mode_field'),
            'crelate_job_board_settings',
            'crelate_display_settings'
        );
        add_settings_field(
            'input_height_custom',
            __('Custom Input Height (px)', 'crelate-job-board'),
            array($this, 'input_height_custom_field'),
            'crelate_job_board_settings',
            'crelate_display_settings'
        );


    }
    
    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        $sanitized['api_key'] = sanitize_text_field($input['api_key']);
        $sanitized['api_endpoint'] = esc_url_raw($input['api_endpoint']);
        $sanitized['portal_id'] = sanitize_text_field($input['portal_id']);
        $sanitized['auto_import'] = isset($input['auto_import']) ? '1' : '0';
        $sanitized['import_frequency'] = sanitize_text_field($input['import_frequency']);
        $sanitized['jobs_per_page'] = intval($input['jobs_per_page']);
        $sanitized['enable_search'] = isset($input['enable_search']) ? '1' : '0';
        $sanitized['enable_filters'] = isset($input['enable_filters']) ? '1' : '0';
        $sanitized['enable_apply'] = isset($input['enable_apply']) ? '1' : '0';
        $sanitized['apply_redirect_url'] = esc_url_raw($input['apply_redirect_url']);

        // Input height settings
        $sanitized['input_height_mode'] = in_array(($input['input_height_mode'] ?? 'default'), array('default','custom'), true) ? $input['input_height_mode'] : 'default';
        $sanitized['input_height_custom'] = isset($input['input_height_custom']) ? intval($input['input_height_custom']) : 0;

        
        return $sanitized;
    }


    
    /**
     * Sanitize styling settings
     */
    public function sanitize_styling_settings($input) {
        $sanitized = array();
        
        $sanitized['primary_color_type'] = sanitize_text_field($input['primary_color_type']);
        $sanitized['primary_color'] = sanitize_hex_color($input['primary_color']);
        $sanitized['border_radius'] = sanitize_text_field($input['border_radius']);
        $sanitized['icon_style'] = sanitize_text_field($input['icon_style']);
        $sanitized['button_text_color'] = sanitize_text_field($input['button_text_color']);
        $sanitized['button_text_case'] = sanitize_text_field($input['button_text_case']);
        $sanitized['application_form_type'] = sanitize_text_field($input['application_form_type']);
        $sanitized['policy_checkbox_text'] = wp_kses_post($input['policy_checkbox_text']);

        // Remove deprecated application_fields feature

        $sanitized['show_job_details'] = isset($input['show_job_details']) ? '1' : '0';
        $sanitized['show_job_tags'] = isset($input['show_job_tags']) ? '1' : '0';
        $sanitized['show_job_date'] = isset($input['show_job_date']) ? '1' : '0';
        $sanitized['job_title_font_size'] = sanitize_text_field($input['job_title_font_size']);
        $sanitized['job_title_line_height'] = sanitize_text_field($input['job_title_line_height']);
        $sanitized['grid_columns'] = sanitize_text_field($input['grid_columns']);
        $sanitized['use_theme_style'] = isset($input['use_theme_style']) ? '1' : '0';
        
        return $sanitized;
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        $this->admin_page_html();
    }
    
    /**
     * Import page
     */
    public function import_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Import Jobs', 'crelate-job-board'); ?></h1>
            
            <div class="crelate-import-section">
                <h3><?php _e('Manual Import', 'crelate-job-board'); ?></h3>
                <p><?php _e('Click the button below to manually import jobs from Crelate.', 'crelate-job-board'); ?></p>
                <button type="button" class="button button-primary" id="crelate-import-jobs">
                    <?php _e('Import Jobs', 'crelate-job-board'); ?>
                </button>
                <div id="import-result"></div>
            </div>
            
            <div class="crelate-import-stats">
                <h3><?php _e('Import Statistics', 'crelate-job-board'); ?></h3>
                <div id="import-stats">
                    <p><?php _e('Loading statistics...', 'crelate-job-board'); ?></p>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Statistics page
     */
    public function statistics_page() {
        $api = new Crelate_API();
        $stats = $api->get_import_stats();
        ?>
        <div class="wrap">
            <h1><?php _e('Job Board Statistics', 'crelate-job-board'); ?></h1>
            
            <div class="crelate-stats-grid">
                <div class="crelate-stat-card">
                    <h3><?php _e('Total Jobs', 'crelate-job-board'); ?></h3>
                    <div class="stat-number"><?php echo esc_html($stats['total_jobs']); ?></div>
                </div>
                
                <div class="crelate-stat-card">
                    <h3><?php _e('Published Jobs', 'crelate-job-board'); ?></h3>
                    <div class="stat-number"><?php echo esc_html($stats['published_jobs']); ?></div>
                </div>
                
                <div class="crelate-stat-card">
                    <h3><?php _e('Draft Jobs', 'crelate-job-board'); ?></h3>
                    <div class="stat-number"><?php echo esc_html($stats['draft_jobs']); ?></div>
                </div>
            </div>
            
            <?php if (!empty($stats['last_import'])): ?>
            <div class="crelate-last-import">
                <h3><?php _e('Last Import', 'crelate-job-board'); ?></h3>
                <p>
                    <strong><?php _e('Date:', 'crelate-job-board'); ?></strong> 
                    <?php echo esc_html($stats['last_import']['timestamp']); ?>
                </p>
                <p>
                    <strong><?php _e('Imported:', 'crelate-job-board'); ?></strong> 
                    <?php echo esc_html($stats['last_import']['imported']); ?>
                </p>
                <p>
                    <strong><?php _e('Updated:', 'crelate-job-board'); ?></strong> 
                    <?php echo esc_html($stats['last_import']['updated']); ?>
                </p>
                <p>
                    <strong><?php _e('Errors:', 'crelate-job-board'); ?></strong> 
                    <?php echo esc_html($stats['last_import']['errors']); ?>
                </p>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * API settings section
     */
    public function api_settings_section() {
        echo '<p>' . __('Configure your Crelate API settings below.', 'crelate-job-board') . '</p>';
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
     * Import settings section
     */
    public function import_settings_section() {
        echo '<p>' . __('Configure automatic job import settings.', 'crelate-job-board') . '</p>';
    }
    
    /**
     * Auto import field
     */
    public function auto_import_field() {
        $settings = get_option('crelate_job_board_settings');
        $auto_import = !empty($settings['auto_import']) ? $settings['auto_import'] : '0';
        ?>
        <label>
            <input type="checkbox" name="crelate_job_board_settings[auto_import]" value="1" <?php checked($auto_import, '1'); ?> />
            <?php _e('Enable automatic job import', 'crelate-job-board'); ?>
        </label>
        <?php
    }
    
    /**
     * Import frequency field
     */
    public function import_frequency_field() {
        $settings = get_option('crelate_job_board_settings');
        $frequency = !empty($settings['import_frequency']) ? $settings['import_frequency'] : 'daily';
        ?>
        <select name="crelate_job_board_settings[import_frequency]">
            <option value="hourly" <?php selected($frequency, 'hourly'); ?>><?php _e('Hourly', 'crelate-job-board'); ?></option>
            <option value="twicedaily" <?php selected($frequency, 'twicedaily'); ?>><?php _e('Twice Daily', 'crelate-job-board'); ?></option>
            <option value="daily" <?php selected($frequency, 'daily'); ?>><?php _e('Daily', 'crelate-job-board'); ?></option>
            <option value="weekly" <?php selected($frequency, 'weekly'); ?>><?php _e('Weekly', 'crelate-job-board'); ?></option>
        </select>
        <?php
    }
    
    /**
     * Display settings section
     */
    public function display_settings_section() {
        echo '<p>' . __('Configure how jobs are displayed on your website.', 'crelate-job-board') . '</p>';
    }
    
    /**
     * Jobs per page field
     */
    public function jobs_per_page_field() {
        $settings = get_option('crelate_job_board_settings');
        $jobs_per_page = !empty($settings['jobs_per_page']) ? $settings['jobs_per_page'] : 10;
        ?>
        <input type="number" name="crelate_job_board_settings[jobs_per_page]" value="<?php echo esc_attr($jobs_per_page); ?>" min="1" max="100" />
        <?php
    }
    
    /**
     * Enable search field
     */
    public function enable_search_field() {
        $settings = get_option('crelate_job_board_settings');
        $enable_search = !empty($settings['enable_search']) ? $settings['enable_search'] : '1';
        ?>
        <label>
            <input type="checkbox" name="crelate_job_board_settings[enable_search]" value="1" <?php checked($enable_search, '1'); ?> />
            <?php _e('Enable job search functionality', 'crelate-job-board'); ?>
        </label>
        <?php
    }
    
    /**
     * Enable filters field
     */
    public function enable_filters_field() {
        $settings = get_option('crelate_job_board_settings');
        $enable_filters = !empty($settings['enable_filters']) ? $settings['enable_filters'] : '1';
        ?>
        <label>
            <input type="checkbox" name="crelate_job_board_settings[enable_filters]" value="1" <?php checked($enable_filters, '1'); ?> />
            <?php _e('Enable job filtering options', 'crelate-job-board'); ?>
        </label>
        <?php
    }
    
    /**
     * Enable apply field
     */
    public function enable_apply_field() {
        $settings = get_option('crelate_job_board_settings');
        $enable_apply = !empty($settings['enable_apply']) ? $settings['enable_apply'] : '1';
        ?>
        <label>
            <input type="checkbox" name="crelate_job_board_settings[enable_apply]" value="1" <?php checked($enable_apply, '1'); ?> />
            <?php _e('Enable apply button on job listings', 'crelate-job-board'); ?>
        </label>
        <?php
    }


    
    /**
     * Apply redirect URL field
     */
    public function apply_redirect_url_field() {
        $settings = get_option('crelate_job_board_settings');
        $apply_redirect_url = !empty($settings['apply_redirect_url']) ? $settings['apply_redirect_url'] : '';
        ?>
        <input type="url" name="crelate_job_board_settings[apply_redirect_url]" value="<?php echo esc_attr($apply_redirect_url); ?>" class="regular-text" />
        <p class="description"><?php _e('Optional: Redirect all apply buttons to this URL instead of individual job apply URLs.', 'crelate-job-board'); ?></p>
        <?php
    }

    /**
     * Input height mode field
     */
    public function input_height_mode_field() {
        $settings = get_option('crelate_job_board_settings');
        $mode = !empty($settings['input_height_mode']) ? $settings['input_height_mode'] : 'default';
        ?>
        <select name="crelate_job_board_settings[input_height_mode]" id="input_height_mode">
            <option value="default" <?php selected($mode, 'default'); ?>><?php _e('Default', 'crelate-job-board'); ?></option>
            <option value="custom" <?php selected($mode, 'custom'); ?>><?php _e('Custom', 'crelate-job-board'); ?></option>
        </select>
        <p class="description"><?php _e('Choose Default for theme-controlled heights, or Custom to set a pixel height.', 'crelate-job-board'); ?></p>
        <?php
    }

    /**
     * Input height custom value field
     */
    public function input_height_custom_field() {
        $settings = get_option('crelate_job_board_settings');
        $mode = !empty($settings['input_height_mode']) ? $settings['input_height_mode'] : 'default';
        $val = isset($settings['input_height_custom']) ? intval($settings['input_height_custom']) : 36;
        ?>
        <input type="number" name="crelate_job_board_settings[input_height_custom]" value="<?php echo esc_attr($val); ?>" min="24" max="80" /> px
        <p class="description"><?php _e('When mode is Custom, this height will be applied to form inputs on the application form.', 'crelate-job-board'); ?></p>
        <script>jQuery(function($){ function toggle(){ var m=$('#input_height_mode').val(); $('input[name=\'crelate_job_board_settings[input_height_custom]\']').closest('tr')[m==='custom'?'show':'hide'](); } $('#input_height_mode').on('change', toggle); toggle(); });</script>
        <?php
    }
    
    /**
     * Admin notices
     */
    public function admin_notices() {
        if (isset($_GET['page']) && $_GET['page'] === 'crelate-job-board') {
            $settings = get_option('crelate_job_board_settings');
            if (empty($settings['api_key'])) {
                echo '<div class="notice notice-warning is-dismissible"><p>' . __('Please configure your Crelate API settings.', 'crelate-job-board') . '</p></div>';
            }
        }
    }
    
    /**
     * Test connection AJAX handler
     */
    public function test_connection() {
        check_ajax_referer('crelate_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        $api = new Crelate_API();
        $result = $api->test_connection();
        
        // Always send success response with the result data
        wp_send_json_success($result);
    }
    
    /**
     * Import jobs AJAX handler
     */
    public function import_jobs() {
        check_ajax_referer('crelate_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        $force_update = isset($_POST['force_update']) ? (bool) $_POST['force_update'] : false;
        $api = new Crelate_API();
        $result = $api->import_jobs($force_update);
        
        if ($result) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error(__('Import failed. Please check your API settings.', 'crelate-job-board'));
        }
    }
    
    /**
     * Get import stats AJAX handler
     */
    public function get_import_stats() {
        check_ajax_referer('crelate_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        $api = new Crelate_API();
        $stats = $api->get_import_stats();
        
        wp_send_json_success($stats);
    }

    /**
     * Suggest application fields from API
     */
    public function suggest_application_fields() {
        check_ajax_referer('crelate_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied', 'crelate-job-board'));
        }
        $suggestions = array();
        // Minimal suggestions: known contact fields often useful
        $suggestions[] = array('id' => 'applicant_linkedin', 'label' => __('LinkedIn Profile', 'crelate-job-board'), 'type' => 'url', 'required' => false);
        $suggestions[] = array('id' => 'applicant_website', 'label' => __('Portfolio/Website', 'crelate-job-board'), 'type' => 'url', 'required' => false);
        $suggestions[] = array('id' => 'applicant_location', 'label' => __('Location', 'crelate-job-board'), 'type' => 'text', 'required' => false);
        $suggestions[] = array('id' => 'how_heard', 'label' => __('How did you hear about this position?', 'crelate-job-board'), 'type' => 'select', 'required' => false, 'options' => array(
            array('value' => 'linkedin', 'label' => 'LinkedIn'),
            array('value' => 'indeed', 'label' => 'Indeed'),
            array('value' => 'glassdoor', 'label' => 'Glassdoor'),
            array('value' => 'company_website', 'label' => 'Company Website'),
            array('value' => 'referral', 'label' => 'Referral'),
            array('value' => 'other', 'label' => 'Other'),
        ));
        wp_send_json_success(array('fields' => $suggestions));
    }
    
    /**
     * General settings tab
     */
    public function general_settings_tab() {
        ?>
        <form method="post" action="options.php">
            <?php
            settings_fields('crelate_job_board_settings');
            do_settings_sections('crelate_job_board_settings');
            submit_button();
            ?>
        </form>
        <?php
    }
    

    
    /**
     * Styling tab
     */
    public function styling_tab() {
        $settings = get_option('crelate_job_board_styling', array());
        ?>
        <div class="crelate-styling-section">
            <h2><?php _e('Job Board Styling', 'crelate-job-board'); ?></h2>
            
            <form method="post" action="options.php">
                <?php settings_fields('crelate_job_board_styling'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="primary_color"><?php _e('Primary Color', 'crelate-job-board'); ?></label>
                        </th>
                        <td>
                            <select name="crelate_job_board_styling[primary_color_type]" id="primary_color_type">
                                <option value="preset" <?php selected($settings['primary_color_type'] ?? 'preset', 'preset'); ?>>
                                    <?php _e('Preset Colors', 'crelate-job-board'); ?>
                                </option>
                                <option value="custom" <?php selected($settings['primary_color_type'] ?? 'preset', 'custom'); ?>>
                                    <?php _e('Custom Color', 'crelate-job-board'); ?>
                                </option>
                            </select>
                            
                            <div id="preset_colors" class="color-options" style="<?php echo ($settings['primary_color_type'] ?? 'preset') === 'custom' ? 'display: none;' : ''; ?>">
                                <div class="color-presets">
                                    <label class="color-option">
                                        <input type="radio" name="crelate_job_board_styling[primary_color]" value="#0073aa" <?php checked($settings['primary_color'] ?? '#0073aa', '#0073aa'); ?>>
                                        <span class="color-swatch" style="background-color: #0073aa;" title="Blue"></span>
                                    </label>
                                    <label class="color-option">
                                        <input type="radio" name="crelate_job_board_styling[primary_color]" value="#28a745" <?php checked($settings['primary_color'] ?? '#0073aa', '#28a745'); ?>>
                                        <span class="color-swatch" style="background-color: #28a745;" title="Green"></span>
                                    </label>
                                    <label class="color-option">
                                        <input type="radio" name="crelate_job_board_styling[primary_color]" value="#dc3545" <?php checked($settings['primary_color'] ?? '#0073aa', '#dc3545'); ?>>
                                        <span class="color-swatch" style="background-color: #dc3545;" title="Red"></span>
                                    </label>
                                    <label class="color-option">
                                        <input type="radio" name="crelate_job_board_styling[primary_color]" value="#ffc107" <?php checked($settings['primary_color'] ?? '#0073aa', '#ffc107'); ?>>
                                        <span class="color-swatch" style="background-color: #ffc107;" title="Yellow"></span>
                                    </label>
                                    <label class="color-option">
                                        <input type="radio" name="crelate_job_board_styling[primary_color]" value="#6f42c1" <?php checked($settings['primary_color'] ?? '#0073aa', '#6f42c1'); ?>>
                                        <span class="color-swatch" style="background-color: #6f42c1;" title="Purple"></span>
                                    </label>
                                    <label class="color-option">
                                        <input type="radio" name="crelate_job_board_styling[primary_color]" value="#fd7e14" <?php checked($settings['primary_color'] ?? '#0073aa', '#fd7e14'); ?>>
                                        <span class="color-swatch" style="background-color: #fd7e14;" title="Orange"></span>
                                    </label>
                                    <label class="color-option">
                                        <input type="radio" name="crelate_job_board_styling[primary_color]" value="#20c997" <?php checked($settings['primary_color'] ?? '#0073aa', '#20c997'); ?>>
                                        <span class="color-swatch" style="background-color: #20c997;" title="Teal"></span>
                                    </label>
                                    <label class="color-option">
                                        <input type="radio" name="crelate_job_board_styling[primary_color]" value="#e83e8c" <?php checked($settings['primary_color'] ?? '#0073aa', '#e83e8c'); ?>>
                                        <span class="color-swatch" style="background-color: #e83e8c;" title="Pink"></span>
                                    </label>
                                </div>
                            </div>
                            
                            <div id="custom_color" class="color-options" style="<?php echo ($settings['primary_color_type'] ?? 'preset') === 'custom' ? '' : 'display: none;'; ?>">
                                <input type="color" name="crelate_job_board_styling[primary_color]" value="<?php echo esc_attr($settings['primary_color'] ?? '#0073aa'); ?>" class="color-picker">
                                <input type="text" name="crelate_job_board_styling[primary_color_hex]" value="<?php echo esc_attr($settings['primary_color'] ?? '#0073aa'); ?>" class="hex-input" placeholder="#000000">
                            </div>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="border_radius"><?php _e('Border Radius', 'crelate-job-board'); ?></label>
                        </th>
                        <td>
                            <select name="crelate_job_board_styling[border_radius]" id="border_radius">
                                <option value="rounded" <?php selected($settings['border_radius'] ?? 'rounded', 'rounded'); ?>>
                                    <?php _e('Rounded', 'crelate-job-board'); ?>
                                </option>
                                <option value="square" <?php selected($settings['border_radius'] ?? 'rounded', 'square'); ?>>
                                    <?php _e('Square', 'crelate-job-board'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="icon_style"><?php _e('Icon Style', 'crelate-job-board'); ?></label>
                        </th>
                        <td>
                            <select name="crelate_job_board_styling[icon_style]" id="icon_style">
                                <option value="fontawesome" <?php selected($settings['icon_style'] ?? 'fontawesome', 'fontawesome'); ?>>
                                    <?php _e('Font Awesome', 'crelate-job-board'); ?>
                                </option>
                                <option value="emoji" <?php selected($settings['icon_style'] ?? 'fontawesome', 'emoji'); ?>>
                                    <?php _e('Emoji Icons', 'crelate-job-board'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="button_text_color"><?php _e('Button Text Color', 'crelate-job-board'); ?></label>
                        </th>
                        <td>
                            <select name="crelate_job_board_styling[button_text_color]" id="button_text_color">
                                <option value="light" <?php selected($settings['button_text_color'] ?? 'light', 'light'); ?>>
                                    <?php _e('Light Text', 'crelate-job-board'); ?>
                                </option>
                                <option value="dark" <?php selected($settings['button_text_color'] ?? 'light', 'dark'); ?>>
                                    <?php _e('Dark Text', 'crelate-job-board'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="button_text_case"><?php _e('Button Text Case', 'crelate-job-board'); ?></label>
                        </th>
                        <td>
                            <select name="crelate_job_board_styling[button_text_case]" id="button_text_case">
                                <option value="uppercase" <?php selected($settings['button_text_case'] ?? 'uppercase', 'uppercase'); ?>>
                                    <?php _e('UPPERCASE', 'crelate-job-board'); ?>
                                </option>
                                <option value="titlecase" <?php selected($settings['button_text_case'] ?? 'uppercase', 'titlecase'); ?>>
                                    <?php _e('Title Case', 'crelate-job-board'); ?>
                                </option>
                                <option value="lowercase" <?php selected($settings['button_text_case'] ?? 'uppercase', 'lowercase'); ?>>
                                    <?php _e('lowercase', 'crelate-job-board'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="grid_columns"><?php _e('Grid Columns (Full Width)', 'crelate-job-board'); ?></label>
                        </th>
                        <td>
                            <select name="crelate_job_board_styling[grid_columns]" id="grid_columns">
                                <option value="auto" <?php selected($settings['grid_columns'] ?? 'auto', 'auto'); ?>>
                                    <?php _e('Auto (Responsive)', 'crelate-job-board'); ?>
                                </option>
                                <option value="2" <?php selected($settings['grid_columns'] ?? 'auto', '2'); ?>>
                                    <?php _e('2 Columns', 'crelate-job-board'); ?>
                                </option>
                                <option value="3" <?php selected($settings['grid_columns'] ?? 'auto', '3'); ?>>
                                    <?php _e('3 Columns', 'crelate-job-board'); ?>
                                </option>
                                <option value="4" <?php selected($settings['grid_columns'] ?? 'auto', '4'); ?>>
                                    <?php _e('4 Columns', 'crelate-job-board'); ?>
                                </option>
                            </select>
                            <p class="description">
                                <?php _e('Choose the number of columns for the job grid layout when using full width. Auto will use responsive sizing.', 'crelate-job-board'); ?>
                            </p>
                        </td>
                    </tr>
                    
                                         <tr>
                         <th scope="row">
                             <label for="application_form_type"><?php _e('Application Form Type', 'crelate-job-board'); ?></label>
                         </th>
                         <td>
                             <select name="crelate_job_board_styling[application_form_type]" id="application_form_type">
                                 <option value="custom" <?php selected($settings['application_form_type'] ?? 'custom', 'custom'); ?>>
                                     <?php _e('Custom WordPress Form', 'crelate-job-board'); ?>
                                 </option>
                                 <option value="crelate" <?php selected($settings['application_form_type'] ?? 'custom', 'crelate'); ?>>
                                     <?php _e('Crelate API Integration', 'crelate-job-board'); ?>
                                 </option>
                             </select>
                             <p class="description">
                                 <?php _e('Custom Form: Uses WordPress form handling and email notifications. Crelate API: Sends applications directly to Crelate via their API.', 'crelate-job-board'); ?>
                             </p>
                         </td>
                     </tr>
                     
                     <tr>
                         <th scope="row">
                             <label><?php _e('Job Card Display Options', 'crelate-job-board'); ?></label>
                         </th>
                         <td>
                             <fieldset>
                                 <legend class="screen-reader-text"><?php _e('Job Card Display Options', 'crelate-job-board'); ?></legend>
                                 
                                 <label>
                                     <input type="checkbox" name="crelate_job_board_styling[show_job_details]" value="1" <?php checked($settings['show_job_details'] ?? '1', '1'); ?>>
                                     <?php _e('Show Job Details (Department, Location, Type, Experience, Salary)', 'crelate-job-board'); ?>
                                 </label>
                                 <br><br>
                                 
                                 <label>
                                     <input type="checkbox" name="crelate_job_board_styling[show_job_tags]" value="1" <?php checked($settings['show_job_tags'] ?? '1', '1'); ?>>
                                     <?php _e('Show Job Tags', 'crelate-job-board'); ?>
                                 </label>
                                 <br><br>
                                 
                                 <label>
                                     <input type="checkbox" name="crelate_job_board_styling[show_job_date]" value="1" <?php checked($settings['show_job_date'] ?? '1', '1'); ?>>
                                     <?php _e('Show Posted Date', 'crelate-job-board'); ?>
                                 </label>
                                 <br><br>
                                 
                                                                   <p class="description">
                                      <?php _e('Control which sections are displayed on job cards. Uncheck to hide sections completely.', 'crelate-job-board'); ?>
                                  </p>
                              </fieldset>
                          </td>
                      </tr>
                      
                      <tr>
                          <th scope="row">
                              <label for="job_title_font_size"><?php _e('Job Title Font Size', 'crelate-job-board'); ?></label>
                          </th>
                          <td>
                              <select name="crelate_job_board_styling[job_title_font_size]" id="job_title_font_size">
                                  <option value="default" <?php selected($settings['job_title_font_size'] ?? 'default', 'default'); ?>>
                                      <?php _e('Default (Theme)', 'crelate-job-board'); ?>
                                  </option>
                                  <option value="20px" <?php selected($settings['job_title_font_size'] ?? 'default', '20px'); ?>>
                                      <?php _e('20px', 'crelate-job-board'); ?>
                                  </option>
                                  <option value="22px" <?php selected($settings['job_title_font_size'] ?? 'default', '22px'); ?>>
                                      <?php _e('22px', 'crelate-job-board'); ?>
                                  </option>
                                  <option value="25px" <?php selected($settings['job_title_font_size'] ?? 'default', '25px'); ?>>
                                      <?php _e('25px', 'crelate-job-board'); ?>
                                  </option>
                                  <option value="30px" <?php selected($settings['job_title_font_size'] ?? 'default', '30px'); ?>>
                                      <?php _e('30px', 'crelate-job-board'); ?>
                                  </option>
                                  <option value="35px" <?php selected($settings['job_title_font_size'] ?? 'default', '35px'); ?>>
                                      <?php _e('35px', 'crelate-job-board'); ?>
                                  </option>
                              </select>
                              <p class="description">
                                  <?php _e('Choose the font size for job titles on job cards.', 'crelate-job-board'); ?>
                              </p>
                          </td>
                      </tr>
                      
                      <tr>
                          <th scope="row">
                              <label for="job_title_line_height"><?php _e('Job Title Line Height', 'crelate-job-board'); ?></label>
                          </th>
                          <td>
                              <select name="crelate_job_board_styling[job_title_line_height]" id="job_title_line_height">
                                  <option value="1.2" <?php selected($settings['job_title_line_height'] ?? '1.3', '1.2'); ?>>
                                      <?php _e('Tight (1.2)', 'crelate-job-board'); ?>
                                  </option>
                                  <option value="1.3" <?php selected($settings['job_title_line_height'] ?? '1.3', '1.3'); ?>>
                                      <?php _e('Normal (1.3)', 'crelate-job-board'); ?>
                                  </option>
                                  <option value="1.4" <?php selected($settings['job_title_line_height'] ?? '1.3', '1.4'); ?>>
                                      <?php _e('Relaxed (1.4)', 'crelate-job-board'); ?>
                                  </option>
                                  <option value="1.5" <?php selected($settings['job_title_line_height'] ?? '1.3', '1.5'); ?>>
                                      <?php _e('Loose (1.5)', 'crelate-job-board'); ?>
                                  </option>
                              </select>
                              <p class="description">
                                  <?php _e('Choose the line height for job titles on job cards.', 'crelate-job-board'); ?>
                              </p>
                          </td>
                      </tr>
                      
                      <tr>
                          <th scope="row">
                              <label><?php _e('Button Styling', 'crelate-job-board'); ?></label>
                          </th>
                          <td>
                              <fieldset>
                                  <legend class="screen-reader-text"><?php _e('Button Styling', 'crelate-job-board'); ?></legend>
                                  
                                  <label>
                                      <input type="checkbox" name="crelate_job_board_styling[use_theme_style]" value="1" <?php checked($settings['use_theme_style'] ?? '0', '1'); ?>>
                                      <?php _e('Use Theme Button Styles', 'crelate-job-board'); ?>
                                  </label>
                                  <br><br>
                                  
                                  <p class="description">
                                      <?php _e('When enabled, buttons will use your theme\'s default button styling instead of custom colors. This provides better integration with your theme design.', 'crelate-job-board'); ?>
                                  </p>
                              </fieldset>
                          </td>
                      </tr>
                    <tr>
                        <th scope="row">
                            <label for="policy_checkbox_text"><?php _e('Policy Checkbox Text', 'crelate-job-board'); ?></label>
                        </th>
                        <td>
                            <textarea name="crelate_job_board_styling[policy_checkbox_text]" id="policy_checkbox_text" class="large-text" rows="3"><?php echo esc_textarea($settings['policy_checkbox_text'] ?? 'I agree to the processing of my personal data in accordance with the privacy policy.'); ?></textarea>
                            <p class="description"><?php _e('Customize the consent/policy text shown next to the checkbox in the application form. Basic HTML allowed.', 'crelate-job-board'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(__('Save Styling Settings', 'crelate-job-board')); ?>
            </form>
            
            <div class="crelate-preview-section">
                <h3><?php _e('Preview', 'crelate-job-board'); ?></h3>
                <div id="crelate-style-preview" class="crelate-preview-container">
                    <!-- Preview will be loaded here -->
                </div>
            </div>
        </div>
        
        <style>
        .color-presets {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        
        .color-option {
            display: flex;
            flex-direction: column;
            align-items: center;
            cursor: pointer;
        }
        
        .color-option input[type="radio"] {
            display: none;
        }
        
        .color-swatch {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: 2px solid #ddd;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .color-option input[type="radio"]:checked + .color-swatch {
            border-color: #333;
            transform: scale(1.1);
        }
        
        .color-picker {
            width: 50px;
            height: 40px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .hex-input {
            width: 100px;
            margin-left: 10px;
        }
        
        .crelate-preview-container {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('#primary_color_type').on('change', function() {
                var type = $(this).val();
                if (type === 'preset') {
                    $('#preset_colors').show();
                    $('#custom_color').hide();
                } else {
                    $('#preset_colors').hide();
                    $('#custom_color').show();
                }
            });
            
            $('.color-picker').on('change', function() {
                $('.hex-input').val($(this).val());
            });
            
            $('.hex-input').on('input', function() {
                $('.color-picker').val($(this).val());
            });
        });
        </script>
        <?php
    }

    /**
     * Admin page HTML
     */
    public function admin_page_html() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'onboarding';
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
                }
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Onboarding tab: enter API key, test connection, and show stats
     */
    public function onboarding_tab() {
        $settings = get_option('crelate_job_board_settings');
        $api = new Crelate_API();
        $stats = $api->get_import_stats();
        ?>
        <div class="crelate-onboarding">
            <!-- Welcome Hero Section -->
            <div class="onboarding-hero">
                <div class="hero-content">
                    <div class="hero-logo">
                        <div class="logo-icon">
                            <svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect width="48" height="48" rx="8" fill="#149263"/>
                                <path d="M12 16h24v2H12v-2zm0 6h24v2H12v-2zm0 6h16v2H12v-2z" fill="white"/>
                            </svg>
                        </div>
                        <h2><?php _e('A Job Board 4 Crelate', 'crelate-job-board'); ?></h2>
                    </div>
                    <p class="hero-subtitle"><?php _e('Enter your API settings and click Save. We\'ll validate and import automatically. If nothing happens, click Test Connection.', 'crelate-job-board'); ?></p>
                </div>
            </div>

            <!-- API Configuration Form -->
            <div class="onboarding-form-section">
                <form method="post" action="options.php" class="api-config-form">
                    <?php settings_fields('crelate_job_board_settings'); ?>
                    
                    <div class="form-group">
                        <label for="api_key"><?php _e('API Key', 'crelate-job-board'); ?></label>
                        <input type="text" id="api_key" name="crelate_job_board_settings[api_key]" value="<?php echo esc_attr($this->get_api_key_display_value()); ?>" class="regular-text" />
                        <p class="field-description"><?php _e('Enter your Crelate API key. The input field shows only the first 4 characters when a key is saved.', 'crelate-job-board'); ?></p>
                    </div>

                    <div class="form-group">
                        <label for="api_endpoint"><?php _e('API Endpoint', 'crelate-job-board'); ?></label>
                        <input type="url" id="api_endpoint" name="crelate_job_board_settings[api_endpoint]" value="<?php echo esc_attr($this->get_api_endpoint_value()); ?>" class="regular-text" />
                        <p class="field-description"><?php _e('Enter the Crelate API endpoint URL.', 'crelate-job-board'); ?></p>
                    </div>

                    <div class="form-group">
                        <label for="portal_id"><?php _e('Portal ID', 'crelate-job-board'); ?></label>
                        <input type="text" id="portal_id" name="crelate_job_board_settings[portal_id]" value="<?php echo esc_attr($this->get_portal_id_value()); ?>" class="regular-text" />
                        <p class="field-description"><?php _e('Enter your Crelate portal/company slug used in job URLs.', 'crelate-job-board'); ?></p>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="button button-primary save-settings-btn"><?php _e('Save Settings', 'crelate-job-board'); ?></button>
                    </div>
                </form>
            </div>

            <!-- Action Buttons Section -->
            <div class="onboarding-actions">
                <div class="action-buttons">
                    <button type="button" id="crelate-test-connection" class="button button-secondary"><?php _e('Test Connection', 'crelate-job-board'); ?></button>
                    <button type="button" id="crelate-import-jobs" class="button button-primary"><?php _e('Import Jobs', 'crelate-job-board'); ?></button>
                    <a href="?page=crelate-statistics" class="button button-secondary"><?php _e('View Statistics', 'crelate-job-board'); ?></a>
                    <a href="?page=crelate-job-board&tab=general" class="button button-secondary"><?php _e('Next Step: General Settings', 'crelate-job-board'); ?></a>
                </div>
            </div>

            <!-- Results Section -->
            <div id="connection-result" class="result-section"></div>
            <div id="import-result" class="result-section"></div>
            
            <!-- Statistics Section -->
            <div class="onboarding-stats">
                <h3><?php _e('Current Statistics', 'crelate-job-board'); ?></h3>
                <div class="stats-grid">
                    <div class="stat-item">
                        <span class="stat-label"><?php _e('Total Jobs:', 'crelate-job-board'); ?></span>
                        <span class="stat-value"><?php echo esc_html($stats['total_jobs']); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label"><?php _e('Published Jobs:', 'crelate-job-board'); ?></span>
                        <span class="stat-value"><?php echo esc_html($stats['published_jobs']); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label"><?php _e('Draft Jobs:', 'crelate-job-board'); ?></span>
                        <span class="stat-value"><?php echo esc_html($stats['draft_jobs']); ?></span>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Get API key display value (masked)
     */
    private function get_api_key_display_value() {
        $settings = get_option('crelate_job_board_settings');
        $api_key = !empty($settings['api_key']) ? $settings['api_key'] : '';
        if (!empty($api_key)) {
            $first = substr($api_key, 0, 4);
            return $first . str_repeat('*', max(0, strlen($api_key) - 4));
        }
        return '';
    }

    /**
     * Get API endpoint value
     */
    private function get_api_endpoint_value() {
        $settings = get_option('crelate_job_board_settings');
        return !empty($settings['api_endpoint']) ? $settings['api_endpoint'] : 'https://app.crelate.com/api/pub/v1';
    }

    /**
     * Get Portal ID value
     */
    private function get_portal_id_value() {
        $settings = get_option('crelate_job_board_settings');
        return !empty($settings['portal_id']) ? $settings['portal_id'] : '';
    }

    /**
     * Render job views table (simple view counter)
     */
    private function render_job_views_table() {
        $views = get_option('crelate_job_views', array());
        if (empty($views)) {
            echo '<p><em>' . esc_html__('No views tracked yet.', 'crelate-job-board') . '</em></p>';
            return;
        }
        echo '<table class="widefat striped"><thead><tr><th>' . esc_html__('Job', 'crelate-job-board') . '</th><th>' . esc_html__('URL', 'crelate-job-board') . '</th><th>' . esc_html__('Views', 'crelate-job-board') . '</th></tr></thead><tbody>';
        foreach ($views as $post_id => $count) {
            $permalink = get_permalink($post_id);
            echo '<tr>';
            echo '<td>' . esc_html(get_the_title($post_id)) . '</td>';
            echo '<td><a href="' . esc_url($permalink) . '" target="_blank">' . esc_html($permalink) . '</a></td>';
            echo '<td>' . intval($count) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }
}

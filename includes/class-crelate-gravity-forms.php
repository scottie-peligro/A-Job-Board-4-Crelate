<?php
/**
 * Crelate Gravity Forms Integration
 * 
 * Handles Gravity Forms integration with Crelate API.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crelate_Gravity_Forms {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('gform_after_submission', array($this, 'handle_form_submission'), 10, 2);
        add_action('gform_field_standard_settings', array($this, 'add_crelate_field_setting'), 10, 2);
        add_action('gform_editor_js', array($this, 'add_crelate_field_script'));
        add_action('gform_field_css_class', array($this, 'add_crelate_field_class'), 10, 3);
        
        // Add feed settings
        add_filter('gform_add_field_buttons', array($this, 'add_crelate_field_button'));
        add_action('gform_field_standard_settings', array($this, 'add_crelate_field_settings'), 10, 2);
        add_filter('gform_tooltips', array($this, 'add_crelate_field_tooltips'));
    }
    
    /**
     * Handle form submission
     */
    public function handle_form_submission($entry, $form) {
        // Check if form has Crelate integration enabled
        if (!$this->is_crelate_form_enabled($form['id'])) {
            return;
        }
        
        try {
            // Map form entry to Crelate data
            $mapped_data = Crelate_FieldMap::map_entry_to_crelate($entry, $form['id']);
            
            if (!empty($mapped_data['errors'])) {
                $this->log_error('Field mapping errors', $mapped_data['errors'], $entry);
                return;
            }
            
            // Submit to Crelate
            $submit_service = new Crelate_SubmitService();
            $result = $submit_service->submit_application($mapped_data['data'], $form['id']);
            
            if ($result['success']) {
                // Store Crelate ID in entry meta
                gform_update_meta($entry['id'], 'crelate_id', $result['crelate_id']);
                
                // Log success
                $this->log_success('Application submitted successfully', $result['crelate_id'], $entry);
                
                // Show success message to user
                $this->add_success_message($result['crelate_id']);
                
            } else {
                // Log errors
                $this->log_error('Application submission failed', $result['errors'], $entry);
                
                // Show generic error message to user
                $this->add_error_message();
            }
            
        } catch (Exception $e) {
            $this->log_error('Form submission exception', array($e->getMessage()), $entry);
            $this->add_error_message();
        }
    }
    
    /**
     * Check if Crelate integration is enabled for form
     */
    private function is_crelate_form_enabled($form_id) {
        $enabled_forms = get_option('crelate_enabled_forms', array());
        return in_array($form_id, $enabled_forms);
    }
    
    /**
     * Add Crelate field setting to form editor
     */
    public function add_crelate_field_setting($position, $form_id) {
        if ($position == 50) {
            ?>
            <li class="crelate_field_setting field_setting">
                <label for="crelate_field_type">
                    <?php _e('Crelate Field Type', 'crelate-job-board'); ?>
                    <?php gform_tooltip('form_crelate_field_type'); ?>
                </label>
                <select id="crelate_field_type" onchange="SetFieldProperty('crelateFieldType', this.value);">
                    <option value=""><?php _e('Not mapped', 'crelate-job-board'); ?></option>
                    <option value="first_name"><?php _e('First Name', 'crelate-job-board'); ?></option>
                    <option value="last_name"><?php _e('Last Name', 'crelate-job-board'); ?></option>
                    <option value="email"><?php _e('Email', 'crelate-job-board'); ?></option>
                    <option value="phone"><?php _e('Phone', 'crelate-job-board'); ?></option>
                    <option value="company"><?php _e('Company', 'crelate-job-board'); ?></option>
                    <option value="title"><?php _e('Job Title', 'crelate-job-board'); ?></option>
                    <option value="linkedin_url"><?php _e('LinkedIn URL', 'crelate-job-board'); ?></option>
                    <option value="resume"><?php _e('Resume', 'crelate-job-board'); ?></option>
                    <option value="job_id"><?php _e('Job ID', 'crelate-job-board'); ?></option>
                    <option value="cover_letter"><?php _e('Cover Letter', 'crelate-job-board'); ?></option>
                    <option value="salary_expectation"><?php _e('Salary Expectation', 'crelate-job-board'); ?></option>
                    <option value="availability"><?php _e('Availability', 'crelate-job-board'); ?></option>
                    <option value="notes"><?php _e('Notes', 'crelate-job-board'); ?></option>
                </select>
            </li>
            <?php
        }
    }
    
    /**
     * Add JavaScript for field settings
     */
    public function add_crelate_field_script() {
        ?>
        <script type="text/javascript">
            fieldSettings.text += ', .crelate_field_setting';
            
            jQuery(document).ready(function($) {
                // Bind to field change event
                $(document).on('gform_load_field_settings', function(event, field, form) {
                    $('#crelate_field_type').val(field.crelateFieldType || '');
                });
            });
        </script>
        <?php
    }
    
    /**
     * Add CSS class for Crelate fields
     */
    public function add_crelate_field_class($classes, $field, $form) {
        if (!empty($field->crelateFieldType)) {
            $classes .= ' crelate-field crelate-field-' . $field->crelateFieldType;
        }
        return $classes;
    }
    
    /**
     * Add Crelate field button to form editor
     */
    public function add_crelate_field_button($field_groups) {
        foreach ($field_groups as &$group) {
            if ($group['name'] == 'standard_fields') {
                $group['fields'][] = array(
                    'class' => 'button',
                    'value' => __('Crelate Field', 'crelate-job-board'),
                    'onclick' => "StartAddField('crelate');"
                );
                break;
            }
        }
        return $field_groups;
    }
    
    /**
     * Add Crelate field settings
     */
    public function add_crelate_field_settings($position, $form_id) {
        if ($position == 50) {
            ?>
            <li class="crelate_field_setting field_setting">
                <label for="crelate_field_type">
                    <?php _e('Crelate Field Type', 'crelate-job-board'); ?>
                    <?php gform_tooltip('form_crelate_field_type'); ?>
                </label>
                <select id="crelate_field_type" onchange="SetFieldProperty('crelateFieldType', this.value);">
                    <option value=""><?php _e('Not mapped', 'crelate-job-board'); ?></option>
                    <option value="first_name"><?php _e('First Name', 'crelate-job-board'); ?></option>
                    <option value="last_name"><?php _e('Last Name', 'crelate-job-board'); ?></option>
                    <option value="email"><?php _e('Email', 'crelate-job-board'); ?></option>
                    <option value="phone"><?php _e('Phone', 'crelate-job-board'); ?></option>
                    <option value="company"><?php _e('Company', 'crelate-job-board'); ?></option>
                    <option value="title"><?php _e('Job Title', 'crelate-job-board'); ?></option>
                    <option value="linkedin_url"><?php _e('LinkedIn URL', 'crelate-job-board'); ?></option>
                    <option value="resume"><?php _e('Resume', 'crelate-job-board'); ?></option>
                    <option value="job_id"><?php _e('Job ID', 'crelate-job-board'); ?></option>
                    <option value="cover_letter"><?php _e('Cover Letter', 'crelate-job-board'); ?></option>
                    <option value="salary_expectation"><?php _e('Salary Expectation', 'crelate-job-board'); ?></option>
                    <option value="availability"><?php _e('Availability', 'crelate-job-board'); ?></option>
                    <option value="notes"><?php _e('Notes', 'crelate-job-board'); ?></option>
                </select>
            </li>
            <?php
        }
    }
    
    /**
     * Add tooltips for Crelate fields
     */
    public function add_crelate_field_tooltips($tooltips) {
        $tooltips['form_crelate_field_type'] = __('Select the Crelate field type this form field should map to.', 'crelate-job-board');
        return $tooltips;
    }
    
    /**
     * Add success message
     */
    private function add_success_message($crelate_id) {
        $message = __('Thank you for your application! We have received your submission and will review it shortly.', 'crelate-job-board');
        
        // Add to session for display on next page load
        if (!session_id()) {
            session_start();
        }
        $_SESSION['crelate_success_message'] = $message;
        $_SESSION['crelate_id'] = $crelate_id;
    }
    
    /**
     * Add error message
     */
    private function add_error_message() {
        $message = __('We encountered an issue processing your application. Please try again or contact us for assistance.', 'crelate-job-board');
        
        if (!session_id()) {
            session_start();
        }
        $_SESSION['crelate_error_message'] = $message;
    }
    
    /**
     * Display success/error messages
     */
    public static function display_messages() {
        if (!session_id()) {
            session_start();
        }
        
        if (isset($_SESSION['crelate_success_message'])) {
            echo '<div class="crelate-message crelate-success">' . esc_html($_SESSION['crelate_success_message']) . '</div>';
            unset($_SESSION['crelate_success_message']);
            unset($_SESSION['crelate_id']);
        }
        
        if (isset($_SESSION['crelate_error_message'])) {
            echo '<div class="crelate-message crelate-error">' . esc_html($_SESSION['crelate_error_message']) . '</div>';
            unset($_SESSION['crelate_error_message']);
        }
    }
    
    /**
     * Log success
     */
    private function log_success($message, $crelate_id, $entry) {
        $log_data = array(
            'action' => 'gravity_forms_submission',
            'message' => $message,
            'crelate_id' => $crelate_id,
            'entry_id' => $entry['id'],
            'form_id' => $entry['form_id'],
            'timestamp' => current_time('mysql')
        );
        
        $this->log(json_encode($log_data), 'success');
    }
    
    /**
     * Log error
     */
    private function log_error($message, $errors, $entry) {
        $log_data = array(
            'action' => 'gravity_forms_submission',
            'message' => $message,
            'errors' => $errors,
            'entry_id' => $entry['id'],
            'form_id' => $entry['form_id'],
            'timestamp' => current_time('mysql')
        );
        
        $this->log(json_encode($log_data), 'error');
    }
    
    /**
     * Log message
     */
    private function log($message, $level = 'info') {
        $log_dir = WP_CONTENT_DIR . '/uploads/crelate-logs';
        
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }
        
        $log_file = $log_dir . '/crelate.log';
        $timestamp = current_time('mysql');
        $log_entry = sprintf("[%s] [%s] %s\n", $timestamp, strtoupper($level), $message);
        
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Get forms with Crelate integration
     */
    public static function get_crelate_forms() {
        if (!class_exists('GFAPI')) {
            return array();
        }
        
        $forms = GFAPI::get_forms();
        $crelate_forms = array();
        
        foreach ($forms as $form) {
            if (self::has_crelate_fields($form)) {
                $crelate_forms[] = $form;
            }
        }
        
        return $crelate_forms;
    }
    
    /**
     * Check if form has Crelate fields
     */
    private static function has_crelate_fields($form) {
        if (!isset($form['fields'])) {
            return false;
        }
        
        foreach ($form['fields'] as $field) {
            if (!empty($field->crelateFieldType)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Enable Crelate integration for form
     */
    public static function enable_form($form_id) {
        $enabled_forms = get_option('crelate_enabled_forms', array());
        
        if (!in_array($form_id, $enabled_forms)) {
            $enabled_forms[] = $form_id;
            update_option('crelate_enabled_forms', $enabled_forms);
        }
    }
    
    /**
     * Disable Crelate integration for form
     */
    public static function disable_form($form_id) {
        $enabled_forms = get_option('crelate_enabled_forms', array());
        
        $enabled_forms = array_diff($enabled_forms, array($form_id));
        update_option('crelate_enabled_forms', $enabled_forms);
    }
    
    /**
     * Get form submission statistics
     */
    public static function get_submission_stats($form_id = null) {
        global $wpdb;
        
        $stats = array(
            'total_submissions' => 0,
            'successful_submissions' => 0,
            'failed_submissions' => 0,
            'last_submission' => null
        );
        
        $meta_table = GFFormsModel::get_entry_meta_table_name();
        
        if ($form_id) {
            $sql = $wpdb->prepare(
                "SELECT COUNT(*) as total, 
                        SUM(CASE WHEN meta_value IS NOT NULL THEN 1 ELSE 0 END) as successful,
                        MAX(e.date_created) as last_submission
                 FROM {$meta_table} em
                 LEFT JOIN {$wpdb->prefix}gf_entry e ON em.entry_id = e.id
                 WHERE em.meta_key = 'crelate_id' AND e.form_id = %d",
                $form_id
            );
        } else {
            $sql = "SELECT COUNT(*) as total, 
                           SUM(CASE WHEN meta_value IS NOT NULL THEN 1 ELSE 0 END) as successful,
                           MAX(e.date_created) as last_submission
                    FROM {$meta_table} em
                    LEFT JOIN {$wpdb->prefix}gf_entry e ON em.entry_id = e.id
                    WHERE em.meta_key = 'crelate_id'";
        }
        
        $result = $wpdb->get_row($sql);
        
        if ($result) {
            $stats['total_submissions'] = (int) $result->total;
            $stats['successful_submissions'] = (int) $result->successful;
            $stats['failed_submissions'] = $stats['total_submissions'] - $stats['successful_submissions'];
            $stats['last_submission'] = $result->last_submission;
        }
        
        return $stats;
    }
}

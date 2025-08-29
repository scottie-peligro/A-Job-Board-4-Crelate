<?php
/**
 * Crelate Field Mapping
 * 
 * Handles mapping between Gravity Forms fields and Crelate API fields.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crelate_FieldMap {
    
    /**
     * Default field mappings
     */
    private static $default_mappings = array(
        // Basic contact information
        'first_name' => array(
            'crelate_field' => 'firstName',
            'required' => true,
            'sanitize' => 'text'
        ),
        'last_name' => array(
            'crelate_field' => 'lastName',
            'required' => true,
            'sanitize' => 'text'
        ),
        'email' => array(
            'crelate_field' => 'email',
            'required' => true,
            'sanitize' => 'email'
        ),
        'phone' => array(
            'crelate_field' => 'phone',
            'required' => false,
            'sanitize' => 'phone'
        ),
        
        // Professional information
        'company' => array(
            'crelate_field' => 'currentCompany',
            'required' => false,
            'sanitize' => 'text'
        ),
        'title' => array(
            'crelate_field' => 'currentTitle',
            'required' => false,
            'sanitize' => 'text'
        ),
        'linkedin_url' => array(
            'crelate_field' => 'socialProfile',
            'required' => false,
            'sanitize' => 'url'
        ),
        
        // Resume/attachment
        'resume' => array(
            'crelate_field' => 'attachment',
            'required' => false,
            'sanitize' => 'file'
        ),
        
        // Job information
        'job_id' => array(
            'crelate_field' => 'requisition',
            'required' => false,
            'sanitize' => 'text'
        ),
        
        // UTM tracking
        'utm_source' => array(
            'crelate_field' => 'source',
            'required' => false,
            'sanitize' => 'text'
        ),
        'utm_medium' => array(
            'crelate_field' => 'medium',
            'required' => false,
            'sanitize' => 'text'
        ),
        'utm_campaign' => array(
            'crelate_field' => 'campaign',
            'required' => false,
            'sanitize' => 'text'
        ),
        
        // Additional fields
        'cover_letter' => array(
            'crelate_field' => 'coverLetter',
            'required' => false,
            'sanitize' => 'textarea'
        ),
        'salary_expectation' => array(
            'crelate_field' => 'salaryExpectation',
            'required' => false,
            'sanitize' => 'text'
        ),
        'availability' => array(
            'crelate_field' => 'availability',
            'required' => false,
            'sanitize' => 'text'
        ),
        'notes' => array(
            'crelate_field' => 'notes',
            'required' => false,
            'sanitize' => 'textarea'
        )
    );
    
    /**
     * Get field mappings for a specific form
     */
    public static function get_mappings($form_id = null) {
        if ($form_id) {
            $custom_mappings = get_option('crelate_field_mappings_' . $form_id, array());
            return array_merge(self::$default_mappings, $custom_mappings);
        }
        
        return self::$default_mappings;
    }
    
    /**
     * Map Gravity Forms entry to Crelate data
     */
    public static function map_entry_to_crelate($entry, $form_id) {
        $mappings = self::get_mappings($form_id);
        $crelate_data = array();
        $errors = array();
        
        foreach ($mappings as $gf_field => $mapping) {
            $field_value = self::get_field_value($entry, $gf_field);
            
            if ($field_value !== null) {
                $sanitized_value = self::sanitize_field($field_value, $mapping['sanitize']);
                
                if ($sanitized_value !== false) {
                    $crelate_data[$mapping['crelate_field']] = $sanitized_value;
                } else {
                    $errors[] = sprintf('Invalid value for field %s', $gf_field);
                }
            } elseif ($mapping['required']) {
                $errors[] = sprintf('Required field %s is missing', $gf_field);
            }
        }
        
        // Handle special cases
        $crelate_data = self::handle_special_fields($crelate_data, $entry, $form_id);
        
        return array(
            'data' => $crelate_data,
            'errors' => $errors
        );
    }
    
    /**
     * Get field value from Gravity Forms entry
     */
    private static function get_field_value($entry, $field_key) {
        // Handle different field types
        if (isset($entry[$field_key])) {
            return $entry[$field_key];
        }
        
        // Check for field by label
        foreach ($entry as $key => $value) {
            if (strpos($key, $field_key) !== false) {
                return $value;
            }
        }
        
        return null;
    }
    
    /**
     * Sanitize field value based on type
     */
    private static function sanitize_field($value, $type) {
        switch ($type) {
            case 'text':
                return sanitize_text_field($value);
                
            case 'email':
                return sanitize_email($value);
                
            case 'phone':
                return self::sanitize_phone($value);
                
            case 'url':
                return esc_url_raw($value);
                
            case 'textarea':
                return sanitize_textarea_field($value);
                
            case 'file':
                return $value; // File handling is done separately
                
            default:
                return sanitize_text_field($value);
        }
    }
    
    /**
     * Sanitize phone number
     */
    private static function sanitize_phone($phone) {
        // Remove all non-numeric characters except +, -, (, ), and space
        $phone = preg_replace('/[^0-9+\-\(\)\s]/', '', $phone);
        
        // Remove extra spaces
        $phone = preg_replace('/\s+/', ' ', trim($phone));
        
        return $phone;
    }
    
    /**
     * Handle special field mappings
     */
    private static function handle_special_fields($crelate_data, $entry, $form_id) {
        // Handle full name splitting
        if (isset($entry['full_name']) && !isset($crelate_data['firstName'])) {
            $name_parts = explode(' ', trim($entry['full_name']), 2);
            $crelate_data['firstName'] = $name_parts[0];
            $crelate_data['lastName'] = isset($name_parts[1]) ? $name_parts[1] : '';
        }
        
        // Handle UTM parameters as tags
        $utm_tags = array();
        if (isset($crelate_data['source'])) {
            $utm_tags[] = 'source:' . $crelate_data['source'];
        }
        if (isset($crelate_data['medium'])) {
            $utm_tags[] = 'medium:' . $crelate_data['medium'];
        }
        if (isset($crelate_data['campaign'])) {
            $utm_tags[] = 'campaign:' . $crelate_data['campaign'];
        }
        
        if (!empty($utm_tags)) {
            $crelate_data['tags'] = $utm_tags;
        }
        
        // Handle LinkedIn URL
        if (isset($crelate_data['socialProfile'])) {
            $crelate_data['socialProfile'] = array(
                'type' => 'LinkedIn',
                'url' => $crelate_data['socialProfile']
            );
        }
        
        // Add form submission metadata
        $crelate_data['source'] = 'WordPress Job Board';
        $crelate_data['submittedAt'] = current_time('c');
        $crelate_data['formId'] = $form_id;
        
        return $crelate_data;
    }
    
    /**
     * Get available Gravity Forms fields for mapping
     */
    public static function get_available_gf_fields($form_id) {
        if (!class_exists('GFAPI')) {
            return array();
        }
        
        $form = GFAPI::get_form($form_id);
        if (!$form) {
            return array();
        }
        
        $fields = array();
        foreach ($form['fields'] as $field) {
            $fields[$field->id] = array(
                'id' => $field->id,
                'label' => $field->label,
                'type' => $field->type,
                'required' => $field->isRequired
            );
        }
        
        return $fields;
    }
    
    /**
     * Save custom field mappings for a form
     */
    public static function save_mappings($form_id, $mappings) {
        update_option('crelate_field_mappings_' . $form_id, $mappings);
    }
    
    /**
     * Get mapping configuration for admin interface
     */
    public static function get_mapping_config($form_id) {
        $mappings = self::get_mappings($form_id);
        $gf_fields = self::get_available_gf_fields($form_id);
        
        $config = array();
        foreach ($mappings as $field_key => $mapping) {
            $config[$field_key] = array(
                'crelate_field' => $mapping['crelate_field'],
                'required' => $mapping['required'],
                'sanitize' => $mapping['sanitize'],
                'description' => self::get_field_description($field_key),
                'gf_fields' => $gf_fields
            );
        }
        
        return $config;
    }
    
    /**
     * Get field description
     */
    private static function get_field_description($field_key) {
        $descriptions = array(
            'first_name' => 'Candidate\'s first name',
            'last_name' => 'Candidate\'s last name',
            'email' => 'Candidate\'s email address',
            'phone' => 'Candidate\'s phone number',
            'company' => 'Current or most recent company',
            'title' => 'Current or most recent job title',
            'linkedin_url' => 'LinkedIn profile URL',
            'resume' => 'Resume file upload',
            'job_id' => 'Job requisition ID (hidden field)',
            'utm_source' => 'UTM source parameter',
            'utm_medium' => 'UTM medium parameter',
            'utm_campaign' => 'UTM campaign parameter',
            'cover_letter' => 'Cover letter text',
            'salary_expectation' => 'Salary expectation',
            'availability' => 'Availability information',
            'notes' => 'Additional notes'
        );
        
        return isset($descriptions[$field_key]) ? $descriptions[$field_key] : '';
    }
    
    /**
     * Validate mapping configuration
     */
    public static function validate_mappings($mappings) {
        $errors = array();
        
        foreach ($mappings as $field_key => $mapping) {
            if (empty($mapping['crelate_field'])) {
                $errors[] = sprintf('Crelate field is required for %s', $field_key);
            }
            
            if (!in_array($mapping['sanitize'], array('text', 'email', 'phone', 'url', 'textarea', 'file'))) {
                $errors[] = sprintf('Invalid sanitize type for %s', $field_key);
            }
        }
        
        return $errors;
    }
}

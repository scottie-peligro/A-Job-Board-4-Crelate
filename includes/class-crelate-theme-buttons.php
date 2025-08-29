<?php
/**
 * Crelate Theme Button Inheritance System
 * 
 * This class handles automatic theme button inheritance for the Crelate Job Board plugin.
 * It detects the active theme and applies appropriate button classes to match the theme's
 * primary button styling, similar to how Gravity Forms achieves theme compatibility.
 * 
 * @package Crelate_Job_Board
 * @since 1.0.2
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Crelate_Theme_Buttons {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Theme detection cache
     */
    private $theme_info = null;
    
    /**
     * Button class mappings for different themes
     */
    private $theme_button_classes = array(
        // Block themes (theme.json support)
        'block' => array(
            'primary' => 'wp-element-button wp-block-button__link',
            'secondary' => 'wp-element-button',
            'size' => 'wp-block-button__link'
        ),
        
        // Avada theme
        'avada' => array(
            'primary' => 'fusion-button button-default button-shape-round button-type-flat button-size-medium',
            'secondary' => 'fusion-button button-default button-shape-round button-type-flat button-size-medium',
            'size' => 'button-size-medium'
        ),
        
        // Astra theme
        'astra' => array(
            'primary' => 'ast-button ast-primary-button',
            'secondary' => 'ast-button ast-secondary-button',
            'size' => 'ast-button'
        ),
        
        // GeneratePress theme
        'generatepress' => array(
            'primary' => 'button button--primary',
            'secondary' => 'button button--secondary',
            'size' => 'button'
        ),
        
        // Bootstrap themes
        'bootstrap' => array(
            'primary' => 'btn btn-primary',
            'secondary' => 'btn btn-secondary',
            'size' => 'btn'
        ),
        
        // Tailwind CSS themes
        'tailwind' => array(
            'primary' => 'bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded',
            'secondary' => 'bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded',
            'size' => 'py-2 px-4'
        ),
        
        // Divi theme
        'divi' => array(
            'primary' => 'et_pb_button et_pb_more_button',
            'secondary' => 'et_pb_button et_pb_more_button',
            'size' => 'et_pb_button'
        ),
        
        // Elementor themes
        'elementor' => array(
            'primary' => 'elementor-button elementor-size-sm elementor-animation-grow',
            'secondary' => 'elementor-button elementor-size-sm elementor-animation-grow',
            'size' => 'elementor-button'
        ),
        
        // Default/fallback
        'default' => array(
            'primary' => 'button button-primary',
            'secondary' => 'button button-secondary',
            'size' => 'button'
        )
    );
    
    /**
     * Get singleton instance
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
        add_action('init', array($this, 'init'));
    }
    
    /**
     * Initialize the theme button system
     */
    public function init() {
        // Check if theme button system is disabled
        if (defined('CRELATE_DISABLE_THEME_BUTTONS') && CRELATE_DISABLE_THEME_BUTTONS) {
            return;
        }
        
        // Add filters for button classes
        add_filter('crelate_theme_button_classes', array($this, 'get_theme_button_classes'), 10, 2);
        add_filter('crelate_button_markup', array($this, 'apply_theme_button_markup'), 10, 3);
        
        // Defer everything until the page is actually loaded (like Gravity Forms does)
        add_action('wp_footer', array($this, 'enqueue_assets'), 5);
        add_action('admin_footer', array($this, 'enqueue_admin_assets'), 5);
        
        // Add AJAX handlers for dynamic button enhancement
        add_action('wp_ajax_crelate_get_theme_button_classes', array($this, 'ajax_get_theme_button_classes'));
        add_action('wp_ajax_nopriv_crelate_get_theme_button_classes', array($this, 'ajax_get_theme_button_classes'));
        
        // Add admin AJAX handlers
        add_action('wp_ajax_crelate_get_theme_button_info', array($this, 'ajax_get_theme_button_info'));
        add_action('wp_ajax_crelate_reset_theme_detection', array($this, 'ajax_reset_theme_detection'));
        add_action('wp_ajax_crelate_export_theme_info', array($this, 'ajax_export_theme_info'));
    }
    
    /**
     * Detect the active theme and return theme information
     * Simplified version that mimics Gravity Forms approach
     */
    public function detect_theme() {
        if ($this->theme_info !== null) {
            return $this->theme_info;
        }
        
        // Ultra-simple detection - just get theme name and slug
        try {
            $theme = wp_get_theme();
            $theme_name = strtolower($theme->get('Name'));
            $theme_slug = strtolower($theme->get_stylesheet());
            
            // Simple theme detection based on name only
            $theme_type = 'default';
            
            if (strpos($theme_name, 'avada') !== false || strpos($theme_slug, 'avada') !== false) {
                $theme_type = 'avada';
            } elseif (strpos($theme_name, 'astra') !== false || strpos($theme_slug, 'astra') !== false) {
                $theme_type = 'astra';
            } elseif (strpos($theme_name, 'generatepress') !== false || strpos($theme_slug, 'generatepress') !== false) {
                $theme_type = 'generatepress';
            } elseif (strpos($theme_name, 'divi') !== false || strpos($theme_slug, 'divi') !== false) {
                $theme_type = 'divi';
            } elseif (strpos($theme_name, 'elementor') !== false || strpos($theme_slug, 'elementor') !== false) {
                $theme_type = 'elementor';
            } elseif (strpos($theme_name, 'bootstrap') !== false || strpos($theme_slug, 'bootstrap') !== false) {
                $theme_type = 'bootstrap';
            } elseif (strpos($theme_name, 'tailwind') !== false || strpos($theme_slug, 'tailwind') !== false) {
                $theme_type = 'tailwind';
            }
            
            $this->theme_info = array(
                'name' => $theme_name,
                'slug' => $theme_slug,
                'type' => $theme_type,
                'has_theme_json' => false
            );
            
            return $this->theme_info;
            
        } catch (Exception $e) {
            // Return default theme info on any error
            return $this->get_default_theme_info();
        }
    }
    

    
    /**
     * Get default theme information when detection fails
     */
    private function get_default_theme_info() {
        return array(
            'name' => 'default',
            'slug' => 'default',
            'parent_name' => '',
            'parent_slug' => '',
            'has_theme_json' => false,
            'type' => 'default'
        );
    }
    
    /**
     * Convert memory limit string to bytes
     */
    private function convert_memory_limit_to_bytes($memory_limit) {
        if ($memory_limit === '-1') {
            return -1; // No limit
        }
        
        $unit = strtolower(substr($memory_limit, -1));
        $value = (int) substr($memory_limit, 0, -1);
        
        switch ($unit) {
            case 'k':
                return $value * 1024;
            case 'm':
                return $value * 1024 * 1024;
            case 'g':
                return $value * 1024 * 1024 * 1024;
            default:
                return $value;
        }
    }
    
    /**
     * Get button classes for the current theme
     */
    public function get_theme_button_classes($type = 'primary', $context = '') {
        $theme_info = $this->detect_theme();
        $theme_type = $theme_info['type'];
        
        // Get base classes for the theme
        $base_classes = isset($this->theme_button_classes[$theme_type]) 
            ? $this->theme_button_classes[$theme_type] 
            : $this->theme_button_classes['default'];
        
        // Get specific button type classes
        $button_classes = isset($base_classes[$type]) ? $base_classes[$type] : $base_classes['primary'];
        
        // Add context-specific classes
        if ($context) {
            $button_classes .= ' crelate-button-' . sanitize_html_class($context);
        }
        
        // Allow filtering
        $button_classes = apply_filters('crelate_theme_button_classes', $button_classes, $type, $context, $theme_info);
        
        return $button_classes;
    }
    
    /**
     * Generate theme-compatible button markup
     */
    public function generate_button_markup($text, $type = 'primary', $attributes = array(), $context = '') {
        $default_attributes = array(
            'type' => 'button',
            'class' => $this->get_theme_button_classes($type, $context),
            'data-no-theme-button' => '0'
        );
        
        $attributes = wp_parse_args($attributes, $default_attributes);
        
        // Build the button element
        $button_html = '<button';
        
        foreach ($attributes as $key => $value) {
            if ($key === 'class') {
                $button_html .= ' class="' . esc_attr($value) . '"';
            } else {
                $button_html .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
            }
        }
        
        $button_html .= '>' . esc_html($text) . '</button>';
        
        return apply_filters('crelate_button_markup', $button_html, $text, $type, $attributes, $context);
    }
    
    /**
     * Apply theme button markup to existing buttons
     */
    public function apply_theme_button_markup($markup, $text, $type) {
        // This is a filter callback - return the markup as is for now
        // The actual enhancement will happen via JavaScript
        return $markup;
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_assets() {
        wp_enqueue_script(
            'crelate-theme-buttons',
            CRELATE_JOB_BOARD_PLUGIN_URL . 'assets/js/theme-buttons.js',
            array('jquery'),
            CRELATE_JOB_BOARD_VERSION,
            true
        );
        
        // Get theme info with error handling
        try {
            $theme_info = $this->detect_theme();
            $primary_classes = $this->get_theme_button_classes('primary');
            $secondary_classes = $this->get_theme_button_classes('secondary');
        } catch (Exception $e) {
            // Fallback to default theme info if detection fails
            $theme_info = array(
                'name' => 'default',
                'slug' => 'default',
                'type' => 'default',
                'has_theme_json' => false
            );
            $primary_classes = 'button button-primary';
            $secondary_classes = 'button button-secondary';
        }
        
        wp_localize_script('crelate-theme-buttons', 'crelate_theme_buttons', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('crelate_theme_buttons_nonce'),
            'theme_info' => $theme_info,
            'button_classes' => array(
                'primary' => $primary_classes,
                'secondary' => $secondary_classes
            )
        ));
        
        // Enqueue minimal CSS for theme button inheritance
        wp_enqueue_style(
            'crelate-theme-buttons',
            CRELATE_JOB_BOARD_PLUGIN_URL . 'assets/css/theme-buttons.css',
            array(),
            CRELATE_JOB_BOARD_VERSION
        );
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets() {
        wp_enqueue_script(
            'crelate-theme-buttons-admin',
            CRELATE_JOB_BOARD_PLUGIN_URL . 'assets/js/theme-buttons-admin.js',
            array('jquery'),
            CRELATE_JOB_BOARD_VERSION,
            true
        );
        
        wp_localize_script('crelate-theme-buttons-admin', 'crelate_theme_buttons_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('crelate_theme_buttons_nonce')
        ));
    }
    
    /**
     * AJAX handler for getting theme button classes
     */
    public function ajax_get_theme_button_classes() {
        check_ajax_referer('crelate_theme_buttons_nonce', 'nonce');
        
        $type = sanitize_text_field($_POST['type'] ?? 'primary');
        $context = sanitize_text_field($_POST['context'] ?? '');
        
        $classes = $this->get_theme_button_classes($type, $context);
        
        wp_send_json_success(array(
            'classes' => $classes,
            'theme_info' => $this->detect_theme()
        ));
    }
    
    /**
     * Get theme button information for debugging
     */
    public function get_theme_button_info() {
        $theme_info = $this->detect_theme();
        
        return array(
            'theme_info' => $theme_info,
            'button_classes' => array(
                'primary' => $this->get_theme_button_classes('primary'),
                'secondary' => $this->get_theme_button_classes('secondary')
            ),
            'available_themes' => array_keys($this->theme_button_classes)
        );
    }
    
    /**
     * AJAX handler for getting theme button info
     */
    public function ajax_get_theme_button_info() {
        check_ajax_referer('crelate_theme_buttons_nonce', 'nonce');
        
        $info = $this->get_theme_button_info();
        wp_send_json_success($info);
    }
    
    /**
     * AJAX handler for resetting theme detection
     */
    public function ajax_reset_theme_detection() {
        check_ajax_referer('crelate_theme_buttons_nonce', 'nonce');
        
        // Clear the cached theme info
        $this->theme_info = null;
        
        // Get fresh theme info
        $info = $this->get_theme_button_info();
        wp_send_json_success($info);
    }
    
    /**
     * AJAX handler for exporting theme info
     */
    public function ajax_export_theme_info() {
        check_ajax_referer('crelate_theme_buttons_nonce', 'nonce');
        
        $info = $this->get_theme_button_info();
        wp_send_json_success($info);
    }
}

// Initialize the theme button system
Crelate_Theme_Buttons::get_instance();

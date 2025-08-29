<?php
/**
 * Crelate Simple Theme Button System
 * 
 * A lightweight version that mimics Gravity Forms' approach to theme button inheritance.
 * This version uses minimal memory and defers theme detection until needed.
 * 
 * @package Crelate_Job_Board
 * @since 1.0.2
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Crelate_Simple_Theme_Buttons {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Theme detection cache
     */
    private $theme_info = null;
    
    /**
     * Simple button class mappings
     */
    private $button_classes = array(
        'avada' => array(
            'primary' => 'fusion-button button-default button-shape-round button-type-flat button-size-medium',
            'secondary' => 'fusion-button button-default button-shape-round button-type-flat button-size-medium'
        ),
        'astra' => array(
            'primary' => 'ast-button ast-primary-button',
            'secondary' => 'ast-button ast-secondary-button'
        ),
        'generatepress' => array(
            'primary' => 'button button--primary',
            'secondary' => 'button button--secondary'
        ),
        'divi' => array(
            'primary' => 'et_pb_button et_pb_more_button',
            'secondary' => 'et_pb_button et_pb_more_button'
        ),
        'elementor' => array(
            'primary' => 'elementor-button elementor-size-sm elementor-animation-grow',
            'secondary' => 'elementor-button elementor-size-sm elementor-animation-grow'
        ),
        'bootstrap' => array(
            'primary' => 'btn btn-primary',
            'secondary' => 'btn btn-secondary'
        ),
        'default' => array(
            'primary' => 'button button-primary',
            'secondary' => 'button button-secondary'
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
        // Only add the filter - no initialization
        add_filter('crelate_theme_button_classes', array($this, 'get_button_classes'), 10, 2);
    }
    
    /**
     * Get button classes for the current theme (deferred until needed)
     */
    public function get_button_classes($default_classes, $type = 'primary') {
        // Only detect theme when this method is actually called
        $theme_type = $this->get_theme_type();
        
        // Get classes for the detected theme
        $classes = isset($this->button_classes[$theme_type][$type]) 
            ? $this->button_classes[$theme_type][$type] 
            : $this->button_classes['default'][$type];
        
        // Debug: Log button classes
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Crelate Button Classes: Theme = ' . $theme_type . ', Type = ' . $type . ', Classes = ' . $classes);
        }
        
        return $classes;
    }
    
    /**
     * Simple theme detection (mimics Gravity Forms approach)
     */
    private function get_theme_type() {
        if ($this->theme_info !== null) {
            return $this->theme_info;
        }
        
        // Ultra-simple detection - just get theme name
        try {
            $theme = wp_get_theme();
            $theme_name = strtolower($theme->get('Name'));
            $theme_slug = strtolower($theme->get_stylesheet());
            
            // Debug: Log theme detection
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Crelate Theme Detection: Theme Name = ' . $theme_name . ', Theme Slug = ' . $theme_slug);
            }
            
            // Simple string matching (like Gravity Forms does)
            if (strpos($theme_name, 'avada') !== false || strpos($theme_slug, 'avada') !== false) {
                $this->theme_info = 'avada';
            } elseif (strpos($theme_name, 'astra') !== false || strpos($theme_slug, 'astra') !== false) {
                $this->theme_info = 'astra';
            } elseif (strpos($theme_name, 'generatepress') !== false || strpos($theme_slug, 'generatepress') !== false) {
                $this->theme_info = 'generatepress';
            } elseif (strpos($theme_name, 'divi') !== false || strpos($theme_slug, 'divi') !== false) {
                $this->theme_info = 'divi';
            } elseif (strpos($theme_name, 'elementor') !== false || strpos($theme_slug, 'elementor') !== false) {
                $this->theme_info = 'elementor';
            } elseif (strpos($theme_name, 'bootstrap') !== false || strpos($theme_slug, 'bootstrap') !== false) {
                $this->theme_info = 'bootstrap';
            } else {
                $this->theme_info = 'default';
            }
            
            // Debug: Log detected theme type
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Crelate Theme Detection: Detected Type = ' . $this->theme_info);
            }
            
            return $this->theme_info;
            
        } catch (Exception $e) {
            // Return default on any error
            $this->theme_info = 'default';
            return 'default';
        }
    }
    
    /**
     * Generate button markup (optional helper method)
     */
    public function generate_button($text, $type = 'primary', $attributes = array()) {
        $classes = $this->get_button_classes('', $type);
        
        $default_attributes = array(
            'type' => 'button',
            'class' => $classes
        );
        
        $attributes = wp_parse_args($attributes, $default_attributes);
        
        $button_html = '<button';
        foreach ($attributes as $key => $value) {
            $button_html .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
        }
        $button_html .= '>' . esc_html($text) . '</button>';
        
        return $button_html;
    }
}

// Initialize the simple theme button system
Crelate_Simple_Theme_Buttons::get_instance();

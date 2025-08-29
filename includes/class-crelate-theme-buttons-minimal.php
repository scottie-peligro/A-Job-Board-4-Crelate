<?php
/**
 * Crelate Minimal Theme Button System
 * 
 * A safe, minimal approach to theme button inheritance that mimics Gravity Forms.
 * This version uses CSS classes and deferred loading to avoid memory issues.
 * 
 * @package Crelate_Job_Board
 * @since 1.0.2
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Crelate_Minimal_Theme_Buttons {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Theme detection cache
     */
    private $theme_info = null;
    
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
        // Only add the CSS class filter - no heavy initialization
        add_filter('body_class', array($this, 'add_theme_button_class'));
        add_action('wp_head', array($this, 'add_theme_button_css'));
    }
    
    /**
     * Add theme-specific CSS class to body
     */
    public function add_theme_button_class($classes) {
        $theme_type = $this->get_theme_type();
        $classes[] = 'crelate-theme-' . $theme_type;
        return $classes;
    }
    
    /**
     * Add minimal CSS for theme button inheritance
     */
    public function add_theme_button_css() {
        $theme_type = $this->get_theme_type();
        
        // Only add CSS if we're on a page with job board content
        if (!$this->has_job_board_content()) {
            return;
        }
        
        echo '<style id="crelate-theme-buttons">';
        
        // Base button styling that works with theme inheritance
        echo '
        .crelate-btn-primary {
            /* Let theme handle most styling */
            font-family: inherit;
            font-size: inherit;
            line-height: inherit;
            border-radius: inherit;
            transition: all 0.2s ease;
        }
        ';
        
        // Theme-specific overrides (minimal)
        switch ($theme_type) {
            case 'avada':
                echo '
                .crelate-theme-avada .crelate-btn-primary {
                    /* Avada button styling */
                    background: var(--fusion-primary-color, #0073aa);
                    color: #fff;
                    border: none;
                    padding: 12px 25px;
                    border-radius: 3px;
                    font-weight: 600;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }
                .crelate-theme-avada .crelate-btn-primary:hover {
                    background: var(--fusion-primary-color-hover, #005a87);
                }
                ';
                break;
                
            case 'astra':
                echo '
                .crelate-theme-astra .crelate-btn-primary {
                    /* Astra button styling */
                    background: var(--ast-global-color-0, #0274be);
                    color: #fff;
                    border: none;
                    padding: 10px 20px;
                    border-radius: 2px;
                    font-weight: 500;
                }
                .crelate-theme-astra .crelate-btn-primary:hover {
                    background: var(--ast-global-color-1, #005a87);
                }
                ';
                break;
                
            case 'generatepress':
                echo '
                .crelate-theme-generatepress .crelate-btn-primary {
                    /* GeneratePress button styling */
                    background: var(--primary-color, #0073aa);
                    color: #fff;
                    border: none;
                    padding: 10px 20px;
                    border-radius: 3px;
                    font-weight: 500;
                }
                .crelate-theme-generatepress .crelate-btn-primary:hover {
                    background: var(--primary-hover, #005a87);
                }
                ';
                break;
                
            case 'divi':
                echo '
                .crelate-theme-divi .crelate-btn-primary {
                    /* Divi button styling */
                    background: var(--et_pb_button-bg-color, #2ea3f2);
                    color: #fff;
                    border: none;
                    padding: 12px 24px;
                    border-radius: 3px;
                    font-weight: 500;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }
                .crelate-theme-divi .crelate-btn-primary:hover {
                    background: var(--et_pb_button-bg-color-hover, #0c71c3);
                }
                ';
                break;
                
            case 'elementor':
                echo '
                .crelate-theme-elementor .crelate-btn-primary {
                    /* Elementor button styling */
                    background: var(--e-global-color-primary, #61ce70);
                    color: #fff;
                    border: none;
                    padding: 12px 24px;
                    border-radius: 3px;
                    font-weight: 500;
                }
                .crelate-theme-elementor .crelate-btn-primary:hover {
                    background: var(--e-global-color-primary-hover, #4caf50);
                }
                ';
                break;
                
            case 'bootstrap':
                echo '
                .crelate-theme-bootstrap .crelate-btn-primary {
                    /* Bootstrap button styling */
                    background: var(--bs-primary, #0d6efd);
                    color: #fff;
                    border: none;
                    padding: 8px 16px;
                    border-radius: 4px;
                    font-weight: 400;
                }
                .crelate-theme-bootstrap .crelate-btn-primary:hover {
                    background: var(--bs-primary-hover, #0b5ed7);
                }
                ';
                break;
                
            default:
                // Default WordPress button styling
                echo '
                .crelate-theme-default .crelate-btn-primary {
                    background: #0073aa;
                    color: #fff;
                    border: none;
                    padding: 10px 20px;
                    border-radius: 3px;
                    font-weight: 500;
                }
                .crelate-theme-default .crelate-btn-primary:hover {
                    background: #005a87;
                }
                ';
                break;
        }
        
        echo '</style>';
    }
    
    /**
     * Simple theme detection (deferred until needed)
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
            
            return $this->theme_info;
            
        } catch (Exception $e) {
            // Return default on any error
            $this->theme_info = 'default';
            return 'default';
        }
    }
    
    /**
     * Check if current page has job board content
     */
    private function has_job_board_content() {
        global $post;
        
        if (!$post) {
            return false;
        }
        
        $content = $post->post_content;
        
        // Check for job board shortcodes
        return (
            strpos($content, '[crelate_job_search]') !== false ||
            strpos($content, '[crelate_job_filters]') !== false ||
            strpos($content, '[crelate_jobs]') !== false ||
            strpos($content, '[crelate_job_board]') !== false ||
            is_post_type_archive('crelate_job') ||
            is_singular('crelate_job')
        );
    }
}

// Initialize the minimal theme button system
Crelate_Minimal_Theme_Buttons::get_instance();

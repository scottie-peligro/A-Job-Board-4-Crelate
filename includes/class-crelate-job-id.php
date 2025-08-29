<?php
/**
 * Crelate Job Board Job ID Class
 * Handles Crelate job ID storage and display
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crelate_Job_ID {
    
    /**
     * Meta key for Crelate job ID
     */
    const META_KEY = '_crelate_job_id';
    
    /**
     * Constructor
     */
    public function __construct() {
        // Hook into WordPress
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Add meta box for Crelate job ID
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta_box'));
        
        // Register shortcode
        add_shortcode('crelate_job_id', array($this, 'shortcode'));
        
        // Register ACF field if ACF is active
        add_action('acf/init', array($this, 'register_acf_field'));
        
        // Add template tag
        add_action('init', array($this, 'register_template_tag'));
    }
    
    /**
     * Add meta box for Crelate job ID
     */
    public function add_meta_box() {
        add_meta_box(
            'crelate_job_id',
            __('Crelate Job ID', 'crelate-job-board'),
            array($this, 'meta_box_callback'),
            'crelate_job',
            'side',
            'high'
        );
    }
    
    /**
     * Meta box callback
     */
    public function meta_box_callback($post) {
        wp_nonce_field('crelate_job_id_nonce', 'crelate_job_id_nonce');
        
        $crelate_job_id = get_post_meta($post->ID, self::META_KEY, true);
        ?>
        <p>
            <label for="crelate_job_id"><?php _e('Crelate Job ID:', 'crelate-job-board'); ?></label>
            <input type="text" id="crelate_job_id" name="crelate_job_id" value="<?php echo esc_attr($crelate_job_id); ?>" style="width: 100%;" />
        </p>
        <p class="description">
            <?php _e('The unique identifier for this job in Crelate ATS.', 'crelate-job-board'); ?>
        </p>
        <?php
    }
    
    /**
     * Save meta box data
     */
    public function save_meta_box($post_id) {
        // Check if nonce is valid
        if (!isset($_POST['crelate_job_id_nonce']) || !wp_verify_nonce($_POST['crelate_job_id_nonce'], 'crelate_job_id_nonce')) {
            return;
        }
        
        // Check if user has permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Check if not an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Save the data
        if (isset($_POST['crelate_job_id'])) {
            $crelate_job_id = sanitize_text_field($_POST['crelate_job_id']);
            update_post_meta($post_id, self::META_KEY, $crelate_job_id);
        }
    }
    
    /**
     * Shortcode to display Crelate job ID
     */
    public function shortcode($atts) {
        $atts = shortcode_atts(array(
            'post_id' => get_the_ID(),
            'label' => __('Crelate Job ID:', 'crelate-job-board'),
            'show_label' => 'true'
        ), $atts);
        
        $post_id = intval($atts['post_id']);
        $crelate_job_id = get_post_meta($post_id, self::META_KEY, true);
        
        if (empty($crelate_job_id)) {
            return '';
        }
        
        $output = '';
        if ($atts['show_label'] === 'true') {
            $output .= '<span class="crelate-job-id-label">' . esc_html($atts['label']) . '</span> ';
        }
        $output .= '<span class="crelate-job-id-value">' . esc_html($crelate_job_id) . '</span>';
        
        return '<span class="crelate-job-id">' . $output . '</span>';
    }
    
    /**
     * Template tag function
     */
    public function the_crelate_id($post_id = null) {
        if ($post_id === null) {
            $post_id = get_the_ID();
        }
        
        $crelate_job_id = get_post_meta($post_id, self::META_KEY, true);
        
        if (!empty($crelate_job_id)) {
            echo '<span class="crelate-job-id">';
            echo '<span class="crelate-job-id-label">' . __('Crelate Job ID:', 'crelate-job-board') . '</span> ';
            echo '<span class="crelate-job-id-value">' . esc_html($crelate_job_id) . '</span>';
            echo '</span>';
        }
    }
    
    /**
     * Get Crelate job ID
     */
    public function get_crelate_id($post_id = null) {
        if ($post_id === null) {
            $post_id = get_the_ID();
        }
        
        return get_post_meta($post_id, self::META_KEY, true);
    }
    
    /**
     * Register template tag function
     */
    public function register_template_tag() {
        if (!function_exists('nlmc_jobboard_the_crelate_id')) {
            function nlmc_jobboard_the_crelate_id($post_id = null) {
                global $crelate_job_id;
                if ($crelate_job_id) {
                    $crelate_job_id->the_crelate_id($post_id);
                }
            }
        }
        
        if (!function_exists('nlmc_jobboard_get_crelate_id')) {
            function nlmc_jobboard_get_crelate_id($post_id = null) {
                global $crelate_job_id;
                if ($crelate_job_id) {
                    return $crelate_job_id->get_crelate_id($post_id);
                }
                return '';
            }
        }
    }
    
    /**
     * Register ACF field if ACF is active
     */
    public function register_acf_field() {
        if (function_exists('acf_add_local_field_group')) {
            acf_add_local_field_group(array(
                'key' => 'group_crelate_job_id',
                'title' => 'Crelate Job ID',
                'fields' => array(
                    array(
                        'key' => 'field_crelate_job_id',
                        'label' => 'Crelate Job ID',
                        'name' => 'crelate_job_id',
                        'type' => 'text',
                        'instructions' => 'The unique identifier for this job in Crelate ATS.',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '',
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'maxlength' => '',
                    ),
                ),
                'location' => array(
                    array(
                        array(
                            'param' => 'post_type',
                            'operator' => '==',
                            'value' => 'crelate_job',
                        ),
                    ),
                ),
                'menu_order' => 0,
                'position' => 'side',
                'style' => 'default',
                'label_placement' => 'top',
                'instruction_placement' => 'label',
                'hide_on_screen' => '',
                'active' => true,
                'description' => '',
            ));
        }
    }
    
    /**
     * Update Crelate job ID during import
     */
    public function update_job_id($post_id, $crelate_job_id) {
        if (!empty($crelate_job_id)) {
            update_post_meta($post_id, self::META_KEY, sanitize_text_field($crelate_job_id));
        }
    }
    
    /**
     * Get job by Crelate ID
     */
    public function get_job_by_crelate_id($crelate_job_id) {
        $args = array(
            'post_type' => 'crelate_job',
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => self::META_KEY,
                    'value' => $crelate_job_id,
                    'compare' => '='
                )
            ),
            'posts_per_page' => 1
        );
        
        $query = new WP_Query($args);
        
        if ($query->have_posts()) {
            $query->the_post();
            $post = get_post();
            wp_reset_postdata();
            return $post;
        }
        
        return null;
    }
}



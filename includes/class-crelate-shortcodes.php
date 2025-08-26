<?php
/**
 * Crelate Job Board Shortcodes Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crelate_Shortcodes {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Shortcode is registered in Crelate_Templates to avoid duplicates
        add_shortcode('crelate_job_list', array($this, 'job_list_shortcode'));
        add_shortcode('crelate_job_count', array($this, 'job_count_shortcode'));
        add_shortcode('crelate_job_apply', array($this, 'job_application_form'));
    }
    
    /**
     * Main job board shortcode
     */
    public function job_board_shortcode($atts) {
        // Get admin settings for default values
        $settings = get_option('crelate_job_board_settings', array());
        $default_posts_per_page = !empty($settings['jobs_per_page']) ? intval($settings['jobs_per_page']) : 12;
        
        $atts = shortcode_atts(array(
            'template' => 'grid', // grid, list, cards
            'posts_per_page' => $default_posts_per_page,
            'show_filters' => 'true',
            'show_search' => 'true',
            'show_pagination' => 'true',
            'orderby' => 'date', // date, title, location, department, salary
            'order' => 'DESC',
            'categories' => '',
            'locations' => '',
            'job_types' => '',
            'experience_levels' => '',
            'remote_only' => 'false'
        ), $atts);
        
        // Delegate rendering to templates class instance
        if (!class_exists('Crelate_Templates')) {
            require_once CRELATE_JOB_BOARD_PLUGIN_DIR . 'includes/class-crelate-templates.php';
        }
        $templates = new Crelate_Templates();
        return $templates->job_board_shortcode($atts);
    }
    
    /**
     * Simple job list shortcode
     */
    public function job_list_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 5,
            'orderby' => 'date',
            'order' => 'DESC',
            'show_apply_button' => 'true',
            'show_location' => 'true',
            'show_salary' => 'true'
        ), $atts);
        
        $args = array(
            'post_type' => 'crelate_job',
            'post_status' => 'publish',
            'posts_per_page' => intval($atts['limit']),
            'orderby' => $atts['orderby'],
            'order' => $atts['order']
        );
        
        $jobs = new WP_Query($args);
        
        if (!$jobs->have_posts()) {
            return '<p>' . __('No jobs found.', 'crelate-job-board') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="crelate-simple-job-list">
            <?php while ($jobs->have_posts()): $jobs->the_post(); ?>
                <div class="crelate-simple-job-item">
                    <h4><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
                    
                    <?php if ($atts['show_location'] === 'true'): ?>
                        <?php $location = get_post_meta(get_the_ID(), '_job_location', true); ?>
                        <?php if ($location): ?>
                            <p class="crelate-job-location">📍 <?php echo esc_html($location); ?></p>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php if ($atts['show_salary'] === 'true'): ?>
                        <?php $salary = get_post_meta(get_the_ID(), '_job_salary', true); ?>
                        <?php if ($salary): ?>
                            <p class="crelate-job-salary">💰 <?php echo esc_html($salary); ?></p>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <p class="crelate-job-excerpt"><?php echo wp_trim_words(get_the_excerpt(), 20, '...'); ?></p>
                    
                    <?php if ($atts['show_apply_button'] === 'true'): ?>
                        <?php $apply_url = get_post_meta(get_the_ID(), '_job_apply_url', true); ?>
                        <?php if ($apply_url): ?>
                            <a href="<?php echo esc_url($apply_url); ?>" class="crelate-btn crelate-btn-primary" target="_blank">
                                <?php _e('Apply Now', 'crelate-job-board'); ?>
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        </div>
        <?php
        wp_reset_postdata();
        
        return ob_get_clean();
    }
    
    /**
     * Job count shortcode
     */
    public function job_count_shortcode($atts) {
        $atts = shortcode_atts(array(
            'type' => 'total', // total, published, draft
            'format' => 'number' // number, text
        ), $atts);
        
        $count = 0;
        
        switch ($atts['type']) {
            case 'total':
                $count = wp_count_posts('crelate_job')->publish + wp_count_posts('crelate_job')->draft;
                break;
            case 'published':
                $count = wp_count_posts('crelate_job')->publish;
                break;
            case 'draft':
                $count = wp_count_posts('crelate_job')->draft;
                break;
        }
        
        if ($atts['format'] === 'text') {
            return sprintf(
                _n('%d job available', '%d jobs available', $count, 'crelate-job-board'),
                $count
            );
        }
        
        return $count;
    }

    /**
     * Job application form shortcode
     */
    public function job_application_form($atts) {
        $atts = shortcode_atts(array(
            'job_id' => '',
            'title' => __('Apply for this Position', 'crelate-job-board'),
            'show_fields' => 'name,email,phone,resume,cover_letter',
            'redirect_url' => ''
        ), $atts);
        
        // If no job_id provided, try to get from current post
        if (empty($atts['job_id'])) {
            global $post;
            if ($post && $post->post_type === 'crelate_job') {
                $atts['job_id'] = $post->ID;
            }
        }
        
        if (empty($atts['job_id'])) {
            return '<p>' . __('Error: No job specified for application form.', 'crelate-job-board') . '</p>';
        }
        
        // Get job details
        $job_title = get_the_title($atts['job_id']);
        $crelate_job_id = get_post_meta($atts['job_id'], '_job_crelate_id', true);
        
        // Get application form type from settings
        $styling_settings = get_option('crelate_job_board_styling', array());
        $application_form_type = $styling_settings['application_form_type'] ?? 'custom';
        
        ob_start();
        ?>
        <div class="crelate-application-form">
            <h3><?php echo esc_html($atts['title']); ?></h3>
            
            <?php if ($application_form_type === 'crelate'): ?>
                <!-- Crelate API Integration Form -->
                <form id="crelate-api-application-form" class="crelate-form" enctype="multipart/form-data">
                    <input type="hidden" name="job_id" value="<?php echo esc_attr($atts['job_id']); ?>">
                    <input type="hidden" name="crelate_job_id" value="<?php echo esc_attr($crelate_job_id); ?>">
                    <input type="hidden" name="action" value="crelate_submit_application_api">
                    <?php wp_nonce_field('crelate_application_nonce', 'application_nonce'); ?>
            <?php else: ?>
                <!-- Custom WordPress Form -->
                <form id="crelate-application-form" class="crelate-form" enctype="multipart/form-data">
                    <input type="hidden" name="job_id" value="<?php echo esc_attr($atts['job_id']); ?>">
                    <input type="hidden" name="crelate_job_id" value="<?php echo esc_attr($crelate_job_id); ?>">
                    <input type="hidden" name="action" value="crelate_submit_application">
                    <?php wp_nonce_field('crelate_application_nonce', 'application_nonce'); ?>
            <?php endif; ?>
                
                <div class="crelate-form-row">
                    <div class="crelate-form-group">
                        <label for="applicant_name"><?php _e('Full Name', 'crelate-job-board'); ?> *</label>
                        <input type="text" id="applicant_name" name="applicant_name" required>
                    </div>
                    
                    <div class="crelate-form-group">
                        <label for="applicant_email"><?php _e('Email Address', 'crelate-job-board'); ?> *</label>
                        <input type="email" id="applicant_email" name="applicant_email" required>
                    </div>
                </div>
                
                <div class="crelate-form-row">
                    <div class="crelate-form-group">
                        <label for="applicant_phone"><?php _e('Phone Number', 'crelate-job-board'); ?></label>
                        <input type="tel" id="applicant_phone" name="applicant_phone">
                    </div>
                    
                    <div class="crelate-form-group">
                        <label for="applicant_location"><?php _e('Location', 'crelate-job-board'); ?></label>
                        <input type="text" id="applicant_location" name="applicant_location">
                    </div>
                </div>
                
                <div class="crelate-form-group">
                    <label for="resume_file"><?php _e('Resume/CV', 'crelate-job-board'); ?> *</label>
                    <input type="file" id="resume_file" name="resume_file" accept=".pdf,.doc,.docx" required>
                    <small><?php _e('Accepted formats: PDF, DOC, DOCX (Max 5MB)', 'crelate-job-board'); ?></small>
                </div>
                
                <div class="crelate-form-group">
                    <label for="cover_letter"><?php _e('Cover Letter', 'crelate-job-board'); ?></label>
                    <textarea id="cover_letter" name="cover_letter" rows="5" placeholder="<?php _e('Tell us why you\'re interested in this position...', 'crelate-job-board'); ?>"></textarea>
                </div>
                
                <div class="crelate-form-group">
                    <label for="applicant_linkedin"><?php _e('LinkedIn Profile', 'crelate-job-board'); ?></label>
                    <input type="url" id="applicant_linkedin" name="applicant_linkedin" placeholder="https://linkedin.com/in/yourprofile">
                </div>
                
                <div class="crelate-form-group">
                    <label for="applicant_website"><?php _e('Portfolio/Website', 'crelate-job-board'); ?></label>
                    <input type="url" id="applicant_website" name="applicant_website" placeholder="https://yourwebsite.com">
                </div>
                
                <div class="crelate-form-group">
                    <label for="how_heard"><?php _e('How did you hear about this position?', 'crelate-job-board'); ?></label>
                    <select id="how_heard" name="how_heard">
                        <option value=""><?php _e('Select an option', 'crelate-job-board'); ?></option>
                        <option value="linkedin"><?php _e('LinkedIn', 'crelate-job-board'); ?></option>
                        <option value="indeed"><?php _e('Indeed', 'crelate-job-board'); ?></option>
                        <option value="glassdoor"><?php _e('Glassdoor', 'crelate-job-board'); ?></option>
                        <option value="company_website"><?php _e('Company Website', 'crelate-job-board'); ?></option>
                        <option value="referral"><?php _e('Referral', 'crelate-job-board'); ?></option>
                        <option value="other"><?php _e('Other', 'crelate-job-board'); ?></option>
                    </select>
                </div>
                
                <?php $styling_settings = get_option('crelate_job_board_styling', array()); ?>
                <div class="crelate-form-group">
                    <label class="crelate-checkbox-label">
                        <input type="checkbox" name="agree_terms" required>
                        <span class="crelate-checkbox-text">
                            <?php echo wp_kses_post($styling_settings['policy_checkbox_text'] ?? __('I agree to the processing of my personal data in accordance with the privacy policy.', 'crelate-job-board')); ?>
                        </span>
                    </label>
                </div>
                
                

                <div class="crelate-form-actions">
                    <button type="submit" class="crelate-btn crelate-btn-primary">
                        <?php _e('Submit Application', 'crelate-job-board'); ?>
                    </button>
                </div>
                
                <div class="crelate-form-message" style="display: none;"></div>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Handle both application form types
            $('#crelate-application-form, #crelate-api-application-form').on('submit', function(e) {
                e.preventDefault();
                
                var $form = $(this);
                var $submitBtn = $form.find('button[type="submit"]');
                var $message = $form.find('.crelate-form-message');
                
                // Show loading state
                $submitBtn.prop('disabled', true).text('<?php _e('Submitting...', 'crelate-job-board'); ?>');
                $message.hide();
                
                // Create FormData object for file upload
                var formData = new FormData(this);
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            $message.removeClass('error').addClass('success').html(response.data.message).show();
                            $form[0].reset();
                            
                            // Redirect if specified
                            <?php if (!empty($atts['redirect_url'])): ?>
                            setTimeout(function() {
                                window.location.href = '<?php echo esc_url($atts['redirect_url']); ?>';
                            }, 2000);
                            <?php endif; ?>
                        } else {
                            $message.removeClass('success').addClass('error').html(response.data.message).show();
                        }
                    },
                    error: function() {
                        $message.removeClass('success').addClass('error').html('<?php _e('An error occurred. Please try again.', 'crelate-job-board'); ?>').show();
                    },
                    complete: function() {
                        $submitBtn.prop('disabled', false).text('<?php _e('Submit Application', 'crelate-job-board'); ?>');
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
}

// Initialize shortcodes
new Crelate_Shortcodes();

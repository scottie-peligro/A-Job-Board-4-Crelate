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

    /**
     * Iframe-based job application form that embeds Crelate's native form
     */
    public function job_application_iframe($atts) {
        $atts = shortcode_atts(array(
            'job_id' => get_the_ID(),
            'height' => '800px',
            'width' => '100%',
            'title' => __('Apply for this Position', 'crelate-job-board'),
            'show_title' => 'true'
        ), $atts);
        
        // Get Crelate job ID using multiple methods (prioritize apply URL)
        $crelate_job_id = '';
        
        // Method 1: Try to extract from the apply URL (highest priority - this has the correct UUID)
        $apply_url = get_post_meta($atts['job_id'], '_job_apply_url', true);
        if (!empty($apply_url)) {
            // Extract job ID from URL like: https://jobs.crelate.com/portal/TalentSphere/job/apply/e4df0697-7e57-4644-93c7-e4dd08049c83
            if (preg_match('/\/job\/apply\/([^\/\?]+)/', $apply_url, $matches)) {
                $crelate_job_id = $matches[1];
            }
        }
        
        // Method 2: Try the new system
        if (empty($crelate_job_id)) {
            global $crelate_job_board;
            if (isset($crelate_job_board->job_id)) {
                $crelate_job_id = $crelate_job_board->job_id->get_crelate_id($atts['job_id']);
            }
        }
        
        // Method 3: Try the old meta key
        if (empty($crelate_job_id)) {
            $crelate_job_id = get_post_meta($atts['job_id'], '_job_crelate_id', true);
        }
        
        // Method 4: Try the other meta key
        if (empty($crelate_job_id)) {
            $crelate_job_id = get_post_meta($atts['job_id'], '_crelate_job_id', true);
        }
        
        if (empty($crelate_job_id)) {
            return '<div class="crelate-error">' . __('Error: Crelate job ID not found. Please check that the job has been imported from Crelate.', 'crelate-job-board') . '</div>';
        }
        
        // Get settings for portal configuration
        $settings = get_option('crelate_job_board_settings');
        $styling_settings = get_option('crelate_job_board_styling', array());
        $portal_url = !empty($settings['portal_url']) ? $settings['portal_url'] : 'https://jobs.crelate.com/portal';
        $portal_name = !empty($settings['portal_name']) ? $settings['portal_name'] : 'TalentSphere';
        
        // Get styling values
        $primary_color = !empty($styling_settings['primary_color']) ? $styling_settings['primary_color'] : '#0073aa';
        $modal_background_color = !empty($styling_settings['modal_background_color']) ? $styling_settings['modal_background_color'] : '#EBF7FC';
        $border_radius = !empty($styling_settings['border_radius']) ? $styling_settings['border_radius'] : 'rounded';
        $button_text_color = !empty($styling_settings['button_text_color']) ? $styling_settings['button_text_color'] : 'white';
        
        // Calculate border radius value
        $border_radius_value = ($border_radius === 'square') ? '0px' : '6px';
        
        // Construct the Crelate application URL with parameters to hide branding
        $crelate_apply_url = $portal_url . '/' . $portal_name . '/job/apply/' . $crelate_job_id;
        
        // Add parameters to potentially hide branding (if Crelate supports them)
        $crelate_apply_url .= '?embedded=true&hide_branding=true&minimal=true';
        
        // Debug information (remove this in production)
        if (current_user_can('manage_options')) {
            error_log('Crelate Iframe Debug - Job ID: ' . $crelate_job_id . ', Portal: ' . $portal_name . ', URL: ' . $crelate_apply_url);
        }
        
        ob_start();
        ?>
        <div class="crelate-iframe-application-container">
            <!-- Apply Button -->
            <?php
            $styling_settings = get_option('crelate_job_board_styling', array());
            $apply_now_text = $styling_settings['apply_now_button_text'] ?? 'Apply Now';
            ?>
            <button type="button" class="crelate-btn crelate-btn-primary crelate-btn-sm" onclick="openCrelateApplicationModal('<?php echo esc_js($crelate_apply_url); ?>', '<?php echo esc_js($atts['title']); ?>')">
                <?php echo esc_html($apply_now_text); ?>
            </button>
            
            <!-- Modal Overlay -->
            <div id="crelate-application-modal" class="crelate-modal-overlay" style="display: none;">
                <div class="crelate-modal-content">
                    <div class="crelate-modal-header">
                        <button type="button" class="crelate-modal-close" onclick="closeCrelateApplicationModal()">&times;</button>
                    </div>
                    <div class="crelate-modal-body">
                        <iframe 
                            id="crelate-application-iframe"
                            src=""
                            width="100%"
                            height="600px"
                            frameborder="0"
                            scrolling="yes"
                            title="<?php echo esc_attr($atts['title']); ?>"
                            class="crelate-application-iframe"
                            allow="fullscreen"
                            loading="lazy">
                            <p><?php _e('Your browser does not support iframes. Please visit the application page directly:', 'crelate-job-board'); ?> 
                                <a href="<?php echo esc_url($crelate_apply_url); ?>" target="_blank"><?php _e('Apply Here', 'crelate-job-board'); ?></a>
                            </p>
                        </iframe>
                    </div>
                </div>
            </div>
            
            <!-- Fallback Link -->
            <div class="crelate-iframe-fallback" style="display: none;">
                <p><?php _e('Having trouble with the application form?', 'crelate-job-board'); ?></p>
                <a href="<?php echo esc_url($crelate_apply_url); ?>" target="_blank" class="crelate-btn crelate-btn-primary crelate-btn-sm">
                    <?php echo esc_html($apply_now_text); ?>
                </a>
            </div>
        </div>
        
        <style>
        .crelate-iframe-application-container {
            margin: 20px 0;
        }
        
        /* Apply button sections */
        .crelate-job-detail-apply-top {
            margin-bottom: 30px;
            text-align: center;
        }
        
        .crelate-job-detail-apply-bottom {
            margin-top: 30px;
            text-align: center;
        }
        
        /* Modal Overlay */
        .crelate-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .crelate-modal-overlay.show {
            opacity: 1;
            visibility: visible;
        }
        
        /* Modal Content */
        .crelate-modal-content {
            background: <?php echo esc_attr($modal_background_color); ?>;
            border-radius: <?php echo esc_attr($border_radius_value); ?>;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            transform: scale(0.9);
            transition: transform 0.3s ease;
        }
        
        .crelate-modal-overlay.show .crelate-modal-content {
            transform: scale(1);
        }
        
        /* Modal Header */
        .crelate-modal-header {
            padding: 15px 20px;
            border-bottom: none;
            display: flex;
            justify-content: flex-end;
            background: <?php echo esc_attr($modal_background_color); ?>;
        }
        
        .crelate-modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s ease;
        }
        
        .crelate-modal-close:hover {
            background: #e9ecef;
            color: #333;
        }
        
        /* Modal Body */
        .crelate-modal-body {
            padding: 0;
            overflow: hidden;
        }
        
        .crelate-application-iframe {
            display: block;
            width: 100%;
            height: 600px;
            border: none;
            outline: none;
            overflow-y: auto;
            /* Hide top 150px and bottom 50px of iframe content */
            clip-path: inset(150px 0 50px 0);
            -webkit-clip-path: inset(150px 0 50px 0);
            /* Pull iframe content up by 150px to show form at the top */
            margin-top: -150px;
        }
        
        /* Additional styling for better modal experience */
        .crelate-modal-content {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            transform: scale(0.9);
            transition: transform 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        
        .crelate-modal-body {
            padding: 0;
            overflow: hidden;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        /* Fallback */
        .crelate-iframe-fallback {
            margin-top: 15px;
            text-align: center;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 6px;
        }
        
        .crelate-iframe-fallback p {
            margin-bottom: 10px;
            color: #666;
        }
        
        .crelate-btn {
            display: inline-block;
            padding: 10px 20px;
            background: var(--primary-color, #0073aa);
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 500;
            transition: background-color 0.2s ease;
        }
        
        .crelate-btn:hover {
            background: var(--primary-color-dark, #005a87);
            color: white;
            text-decoration: none;
        }
        
        .crelate-btn-primary {
            background: var(--primary-color, #0073aa);
        }
        
        .crelate-btn-primary:hover {
            background: var(--primary-color-dark, #005a87);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .crelate-modal-content {
                width: 95%;
                max-height: 95vh;
            }
            
            .crelate-application-iframe {
                height: 500px;
            }
            
            .crelate-apply-btn {
                padding: 10px 20px;
                font-size: 14px;
            }
        }
        
        /* Prevent body scroll when modal is open */
        body.modal-open {
            overflow: hidden;
        }
        </style>
        
        <script>
        // Modal functions
        function openCrelateApplicationModal(url, title) {
            const modal = document.getElementById('crelate-application-modal');
            const iframe = document.getElementById('crelate-application-iframe');
            
            if (modal && iframe) {
                // Set iframe source
                iframe.src = url;
                
                // Show modal
                modal.style.display = 'flex';
                setTimeout(() => {
                    modal.classList.add('show');
                }, 10);
                
                // Prevent body scroll
                document.body.classList.add('modal-open');
                
                // Focus management
                modal.setAttribute('aria-hidden', 'false');
                
                // Log for debugging
                console.log('Opening Crelate application modal:', url);
            }
        }
        
        function closeCrelateApplicationModal() {
            const modal = document.getElementById('crelate-application-modal');
            const iframe = document.getElementById('crelate-application-iframe');
            
            if (modal && iframe) {
                // Hide modal
                modal.classList.remove('show');
                setTimeout(() => {
                    modal.style.display = 'none';
                    // Clear iframe source to stop loading
                    iframe.src = '';
                }, 300);
                
                // Restore body scroll
                document.body.classList.remove('modal-open');
                
                // Focus management
                modal.setAttribute('aria-hidden', 'true');
                
                console.log('Closing Crelate application modal');
            }
        }
        
        // Close modal when clicking outside
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('crelate-application-modal');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        closeCrelateApplicationModal();
                    }
                });
            }
            
            // Close modal with Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    const modal = document.getElementById('crelate-application-modal');
                    if (modal && modal.style.display !== 'none') {
                        closeCrelateApplicationModal();
                    }
                }
            });
            
            // Handle iframe loading states
            const iframe = document.getElementById('crelate-application-iframe');
            if (iframe) {
                iframe.addEventListener('load', function() {
                    console.log('Crelate application iframe loaded successfully');
                });
                
                iframe.addEventListener('error', function() {
                    console.error('Error loading Crelate application iframe');
                    // Show fallback message
                    const fallback = document.querySelector('.crelate-iframe-fallback');
                    if (fallback) {
                        fallback.style.display = 'block';
                    }
                });
            }
        });
        </script>
        <?php
        return ob_get_clean();
    }
}

// Initialize shortcodes
new Crelate_Shortcodes();

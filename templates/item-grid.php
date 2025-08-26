<?php
/**
 * Grid Item Template for Job Board
 */

$post_id = get_the_ID();
$job_meta = array(
    'location' => $templates->get_job_meta($post_id, 'location'),
    'department' => $templates->get_job_meta($post_id, 'department'),
    'type' => $templates->get_job_meta($post_id, 'type'),
    'experience' => $templates->get_job_meta($post_id, 'experience'),
    'remote' => $templates->get_job_meta($post_id, 'remote'),
    'salary' => $templates->get_job_meta($post_id, 'salary'),
    'apply_url' => $templates->get_job_meta($post_id, 'apply_url'),
    'expires' => $templates->get_job_meta($post_id, 'expires')
);

$categories = $templates->get_job_categories($post_id);
$is_remote = !empty($job_meta['remote']) && strpos(strtolower($job_meta['remote']), 'remote') !== false;
$is_expired = !empty($job_meta['expires']) && strtotime($job_meta['expires']) < time();
?>

<article class="crelate-job-card" data-job-id="<?php echo esc_attr($post_id); ?>">
    
    <?php if ($is_expired): ?>
    <div class="crelate-expired-badge">
        <span><?php _e('Expired', 'crelate-job-board'); ?></span>
    </div>
    <?php endif; ?>
    
    <!-- Remote Badge and Quick Actions Row -->
    <div class="crelate-job-card-top">
        <?php if ($is_remote): ?>
        <div class="crelate-remote-badge">
            <?php echo $templates->get_icon('remote'); ?>
            <span><?php _e('Remote', 'crelate-job-board'); ?></span>
        </div>
        <?php endif; ?>
        
        <!-- Quick Actions -->
        <div class="crelate-job-quick-actions">
            <button type="button" class="crelate-quick-action" data-action="save" title="<?php _e('Save Job', 'crelate-job-board'); ?>">
                <?php echo $templates->get_icon('bookmark'); ?>
            </button>
            <button type="button" class="crelate-quick-action" data-action="share" title="<?php _e('Share Job', 'crelate-job-board'); ?>">
                <?php echo $templates->get_icon('share'); ?>
            </button>
        </div>
    </div>
    
    <div class="crelate-job-card-header">
        <h3 class="crelate-job-title">
            <a href="<?php the_permalink(); ?>" class="crelate-job-link">
                <?php the_title(); ?>
            </a>
        </h3>
    </div>
    
    <div class="crelate-job-card-body">
        
        <?php
        // Get display settings
        $styling_settings = get_option('crelate_job_board_styling', array());
        $show_job_details = $styling_settings['show_job_details'] ?? '1';
        $show_job_tags = $styling_settings['show_job_tags'] ?? '1';
        ?>
        

        
        <!-- Job meta items -->
        <?php if ($show_job_details === '1'): ?>
        <div class="crelate-job-meta-list">
            <!-- Company/Department -->
            <?php if (!empty($job_meta['department'])): ?>
            <div class="crelate-job-meta">
                <?php echo $templates->get_icon('department'); ?>
                <span class="crelate-job-department"><?php echo esc_html($job_meta['department']); ?></span>
            </div>
            <?php endif; ?>
            
            <!-- Location -->
            <?php if (!empty($job_meta['location'])): ?>
            <div class="crelate-job-meta">
                <?php echo $templates->get_icon('location'); ?>
                <span class="crelate-job-location"><?php echo esc_html($job_meta['location']); ?></span>
            </div>
            <?php endif; ?>
            
            <!-- Job Type -->
            <?php if (!empty($job_meta['type'])): ?>
            <div class="crelate-job-meta">
                <?php echo $templates->get_icon('type'); ?>
                <span class="crelate-job-type"><?php echo esc_html($job_meta['type']); ?></span>
            </div>
            <?php endif; ?>
            
            <!-- Experience Level -->
            <?php if (!empty($job_meta['experience'])): ?>
            <div class="crelate-job-meta">
                <?php echo $templates->get_icon('experience'); ?>
                <span class="crelate-job-experience"><?php echo esc_html(ucfirst($job_meta['experience'])); ?></span>
            </div>
            <?php endif; ?>
            
            <!-- Salary -->
            <?php if (!empty($job_meta['salary'])): ?>
            <div class="crelate-job-meta crelate-salary">
                <?php echo $templates->get_icon('salary'); ?>
                <span class="crelate-job-salary"><?php echo esc_html($templates->format_salary($job_meta['salary'])); ?></span>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Categories/Tags -->
        <?php if ($show_job_tags === '1' && !empty($categories)): ?>
        <div class="crelate-job-tags">
            <?php foreach ($categories as $taxonomy => $terms): ?>
                <?php if (!empty($terms) && is_array($terms)): ?>
                    <?php foreach (array_slice($terms, 0, 3) as $term): ?>
                        <span class="crelate-tag crelate-tag-<?php echo esc_attr($taxonomy); ?>">
                            <?php echo esc_html(ucwords(strtolower($term->name))); ?>
                        </span>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
    </div>
    
    <div class="crelate-job-card-footer">
        
        <?php
        // Get display settings
        $show_job_date = $styling_settings['show_job_date'] ?? '1';
        ?>
        
        <!-- Posted Date -->
        <?php if ($show_job_date === '1'): ?>
        <div class="crelate-job-date">
            <?php echo $templates->get_icon('calendar'); ?>
            <span><?php echo get_the_date(); ?></span>
        </div>
        <?php endif; ?>
        
        <!-- View Details Button -->
        <div class="crelate-job-actions">
            <a href="<?php the_permalink(); ?>" class="crelate-btn crelate-btn-primary crelate-btn-sm">
                <?php _e('View Details', 'crelate-job-board'); ?>
            </a>
        </div>
        
    </div>
    
</article>

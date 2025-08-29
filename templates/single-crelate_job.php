<?php
/**
 * Template for displaying single job details
 */

get_header(); ?>

<div class="crelate-job-detail-container">
    <?php while (have_posts()) : the_post(); ?>
        <div class="crelate-job-detail-header">
            <div class="crelate-job-detail-breadcrumb">
                <a href="<?php echo home_url('/job-board/'); ?>">
                    <?php _e('← Back to Jobs', 'crelate-job-board'); ?>
                </a>
            </div>
            
            <div class="crelate-job-detail-title">
                <h1><?php the_title(); ?></h1>
                <div class="crelate-job-detail-meta">
                    <div class="crelate-job-detail-actions">
                        <button type="button" class="crelate-share-btn" data-job-id="<?php echo get_the_ID(); ?>" title="<?php _e('Share this job', 'crelate-job-board'); ?>">
                            <i class="fas fa-share"></i>
                        </button>
                    </div>
                    <?php
                    $location = get_post_meta(get_the_ID(), '_job_location', true);
                    $type = get_post_meta(get_the_ID(), '_job_type', true);
                    $department = get_post_meta(get_the_ID(), '_job_department', true);
                    $salary = get_post_meta(get_the_ID(), '_job_salary', true);
                    $remote = get_post_meta(get_the_ID(), '_job_remote', true);
                    ?>
                    
                    <?php if ($location) : ?>
                        <span class="job-meta-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <?php echo esc_html($location); ?>
                        </span>
                    <?php endif; ?>
                    
                    <?php if ($type) : ?>
                        <span class="job-meta-item">
                            <i class="fas fa-clock"></i>
                            <?php echo esc_html($type); ?>
                        </span>
                    <?php endif; ?>
                    
                    <?php if ($department) : ?>
                        <span class="job-meta-item">
                            <i class="fas fa-building"></i>
                            <?php echo esc_html($department); ?>
                        </span>
                    <?php endif; ?>
                    
                    <?php if ($remote) : ?>
                        <span class="job-meta-item">
                            <i class="fas fa-home"></i>
                            <?php echo esc_html($remote); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="crelate-job-detail-content">
            <div class="crelate-job-detail-main">
                <div class="crelate-job-detail-description">
                    <h2><?php _e('Job Description', 'crelate-job-board'); ?></h2>
                    <?php the_content(); ?>
                </div>

                <?php
                $requirements = get_post_meta(get_the_ID(), '_job_requirements', true);
                $benefits = get_post_meta(get_the_ID(), '_job_benefits', true);
                ?>

                <?php if ($requirements) : ?>
                    <div class="crelate-job-detail-requirements">
                        <h2><?php _e('Requirements', 'crelate-job-board'); ?></h2>
                        <?php echo wp_kses_post( wpautop( $requirements ) ); ?>
                    </div>
                <?php endif; ?>

                <?php if ($benefits) : ?>
                    <div class="crelate-job-detail-benefits">
                        <h2><?php _e('Benefits', 'crelate-job-board'); ?></h2>
                        <?php echo wp_kses_post( wpautop( $benefits ) ); ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="crelate-job-detail-sidebar">
                <button type="button" class="crelate-sidebar-toggle" aria-label="Expand apply panel" title="Expand apply panel">
                    <i class="fas fa-angle-left" aria-hidden="true"></i>
                </button>
                <div class="crelate-job-detail-apply">
                    <h3><?php _e('Apply for this Position', 'crelate-job-board'); ?></h3>
                    <?php echo do_shortcode('[crelate_job_apply_iframe job_id="' . get_the_ID() . '"]'); ?>
                </div>

                <div class="crelate-job-detail-info">
                    <h3><?php _e('Job Information', 'crelate-job-board'); ?></h3>
                    <ul>
                        <?php if ($salary) : ?>
                            <li>
                                <strong><?php _e('Salary:', 'crelate-job-board'); ?></strong>
                                <?php echo esc_html($salary); ?>
                            </li>
                        <?php endif; ?>
                        
                        <?php if ($location) : ?>
                            <li>
                                <strong><?php _e('Location:', 'crelate-job-board'); ?></strong>
                                <?php echo esc_html($location); ?>
                            </li>
                        <?php endif; ?>
                        
                        <?php if ($type) : ?>
                            <li>
                                <strong><?php _e('Type:', 'crelate-job-board'); ?></strong>
                                <?php echo esc_html($type); ?>
                            </li>
                        <?php endif; ?>
                        
                        <?php if ($department) : ?>
                            <li>
                                <strong><?php _e('Department:', 'crelate-job-board'); ?></strong>
                                <?php echo esc_html($department); ?>
                            </li>
                        <?php endif; ?>
                        
                        <?php if ($remote) : ?>
                            <li>
                                <strong><?php _e('Remote:', 'crelate-job-board'); ?></strong>
                                <?php echo esc_html($remote); ?>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    <?php endwhile; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof CrelateJobBoard !== 'undefined') {
        CrelateJobBoard.init({
            template: 'single',
            postsPerPage: 1,
            showFilters: false,
            showSearch: false
        });
    }
});
</script>

<style>
.crelate-job-detail-container {
    width: 100%;
    max-width: none;
    margin: 0;
    padding: 20px;
}

.crelate-job-detail-header {
    margin-bottom: 40px;
}

.crelate-job-detail-breadcrumb {
    margin-bottom: 20px;
}

.crelate-job-detail-breadcrumb a {
    text-decoration: none;
    font-weight: 500;
}

.crelate-job-detail-breadcrumb a:hover {
    text-decoration: underline;
}

.crelate-job-detail-title h1 {
    font-size: 2.5em;
    margin-bottom: 15px;
}

.crelate-job-detail-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    align-items: center;
    justify-content: flex-start;
}

.crelate-job-detail-actions {
    margin-left: 0;
}

.job-meta-item {
    display: flex;
    align-items: center;
    gap: 5px;
    color: #666;
    font-size: 0.9em;
}

.crelate-job-detail-content {
    display: flex;
    gap: 40px;
}

.crelate-job-detail-main {
    flex: 1;
}

.crelate-job-detail-sidebar {
    flex: 0 0 300px;
}

/* Sidebar expand/collapse behavior (desktop) */
.crelate-job-detail-sidebar { position: relative; }
.crelate-sidebar-toggle {
    position: absolute;
    top: 8px;
    left: -12px;
    width: 28px;
    height: 28px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: var(--primary-color, #0073aa);
    color: #ffffff;
    border: none;
    border-radius: var(--border-radius, 8px);
    box-shadow: 0 2px 6px rgba(0,0,0,0.15);
    cursor: pointer;
    opacity: 0;
    transition: opacity 0.2s ease, transform 0.2s ease;
    z-index: 2;
}
.crelate-sidebar-toggle i,
.crelate-sidebar-toggle .fas,
.crelate-sidebar-toggle .fa {
    color: #ffffff !important;
}
.crelate-job-detail-sidebar:hover .crelate-sidebar-toggle,
.sidebar-expanded .crelate-sidebar-toggle {
    opacity: 1;
}
.sidebar-expanded .crelate-job-detail-main {
    flex: 0 0 50%;
}
.sidebar-expanded .crelate-job-detail-sidebar {
    flex: 0 0 50%;
}

.crelate-job-detail-description,
.crelate-job-detail-requirements,
.crelate-job-detail-benefits {
    margin-bottom: 30px;
}

.crelate-job-detail-description h2,
.crelate-job-detail-requirements h2,
.crelate-job-detail-benefits h2 {
    font-size: 1.5em;
    margin-bottom: 15px;
    padding-bottom: 5px;
}

.crelate-job-detail-apply,
.crelate-job-detail-info {
    background: #f9f9f9;
    padding: 20px;
    margin-bottom: 20px;
}

.crelate-job-detail-apply h3,
.crelate-job-detail-info h3 {
    margin-bottom: 15px;
}

.crelate-job-detail-info ul {
    list-style: none;
    padding: 0;
}

.crelate-job-detail-info li {
    margin-bottom: 10px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.crelate-job-detail-info li:last-child {
    border-bottom: none;
}

@media (max-width: 768px) {
    .crelate-job-detail-content {
        flex-direction: column;
    }
    
    .crelate-job-detail-sidebar {
        flex: none;
        order: 1;
    }
    
    .crelate-job-detail-main {
        order: 2;
    }
    
    .crelate-job-detail-meta {
        flex-direction: column;
        gap: 10px;
    }
    .crelate-sidebar-toggle { display: none; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var content = document.querySelector('.crelate-job-detail-content');
    var toggle = document.querySelector('.crelate-sidebar-toggle');
    if (!content || !toggle) { return; }
    toggle.addEventListener('click', function() {
        var expanded = content.classList.toggle('sidebar-expanded');
        toggle.setAttribute('aria-label', expanded ? '<?php echo esc_js(__('Collapse apply panel', 'crelate-job-board')); ?>' : '<?php echo esc_js(__('Expand apply panel', 'crelate-job-board')); ?>');
        toggle.title = toggle.getAttribute('aria-label');
        var icon = toggle.querySelector('i');
        if (icon) {
            if (expanded) {
                icon.classList.remove('fa-angle-left');
                icon.classList.add('fa-angle-right');
            } else {
                icon.classList.remove('fa-angle-right');
                icon.classList.add('fa-angle-left');
            }
        }
    });
});
</script>

<?php get_footer(); ?>

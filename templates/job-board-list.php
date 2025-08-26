<?php
/**
 * List Template for Job Board
 */

// Get filter options
$filter_options = $templates->get_filter_options();
?>

<div class="crelate-job-board crelate-list-template">
    
    <!-- Search and Filters -->
    <?php if ($atts['show_search'] === 'true' || $atts['show_filters'] === 'true'): ?>
    <div class="crelate-filters-section">
        
        <!-- Search Bar -->
        <?php if ($atts['show_search'] === 'true'): ?>
        <div class="crelate-search">
            <input type="text" id="crelate-search" placeholder="<?php _e('Search jobs...', 'crelate-job-board'); ?>" value="<?php echo esc_attr($_GET['search'] ?? ''); ?>">
            <button type="button" id="crelate-search-btn" class="crelate-btn crelate-btn-primary">
                <?php echo $templates->get_icon('search'); ?>
            </button>
        </div>
        <?php endif; ?>
        
        <!-- Filters -->
        <?php if ($atts['show_filters'] === 'true'): ?>
        <div class="crelate-filters">
            
            <!-- Location Filter -->
            <?php if (!empty($filter_options['locations'])): ?>
            <div class="crelate-filter-group">
                <label for="crelate-location-filter"><?php _e('Location', 'crelate-job-board'); ?></label>
                <select id="crelate-location-filter">
                    <option value=""><?php _e('All Locations', 'crelate-job-board'); ?></option>
                    <?php foreach ($filter_options['locations'] as $location): ?>
                    <option value="<?php echo esc_attr($location); ?>" <?php selected($_GET['location'] ?? '', $location); ?>>
                        <?php echo esc_html($location); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <!-- Department Filter -->
            <?php if (!empty($filter_options['departments'])): ?>
            <div class="crelate-filter-group">
                <label for="crelate-department-filter"><?php _e('Department', 'crelate-job-board'); ?></label>
                <select id="crelate-department-filter">
                    <option value=""><?php _e('All Departments', 'crelate-job-board'); ?></option>
                    <?php foreach ($filter_options['departments'] as $department): ?>
                    <option value="<?php echo esc_attr($department->slug); ?>" <?php selected($_GET['department'] ?? '', $department->slug); ?>>
                        <?php echo esc_html($department->name); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <!-- Job Type Filter -->
            <?php if (!empty($filter_options['job_types'])): ?>
            <div class="crelate-filter-group">
                <label for="crelate-type-filter"><?php _e('Job Type', 'crelate-job-board'); ?></label>
                <select id="crelate-type-filter">
                    <option value=""><?php _e('All Types', 'crelate-job-board'); ?></option>
                    <?php foreach ($filter_options['job_types'] as $type): ?>
                    <option value="<?php echo esc_attr($type->slug); ?>" <?php selected($_GET['job_type'] ?? '', $type->slug); ?>>
                        <?php echo esc_html($type->name); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <!-- Experience Level Filter -->
            <?php if (!empty($filter_options['experience_levels'])): ?>
            <div class="crelate-filter-group">
                <label for="crelate-experience-filter"><?php _e('Experience', 'crelate-job-board'); ?></label>
                <select id="crelate-experience-filter">
                    <option value=""><?php _e('All Levels', 'crelate-job-board'); ?></option>
                    <?php foreach ($filter_options['experience_levels'] as $level): ?>
                    <option value="<?php echo esc_attr($level->slug); ?>" <?php selected($_GET['experience'] ?? '', $level->slug); ?>>
                        <?php echo esc_html($level->name); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <!-- Remote Work Filter -->
            <div class="crelate-filter-group">
                <label class="crelate-checkbox-label">
                    <input type="checkbox" id="crelate-remote-filter" <?php checked($_GET['remote'] ?? '', 'true'); ?>>
                    <span class="crelate-checkbox-text"><?php _e('Remote Only', 'crelate-job-board'); ?></span>
                </label>
            </div>
            
            <!-- Sort Options -->
            <div class="crelate-filter-group">
                <label for="crelate-sort"><?php _e('Sort By', 'crelate-job-board'); ?></label>
                <select id="crelate-sort">
                    <option value="date-desc" <?php selected($atts['orderby'] . '-' . strtolower($atts['order']), 'date-desc'); ?>>
                        <?php _e('Newest First', 'crelate-job-board'); ?>
                    </option>
                    <option value="date-asc" <?php selected($atts['orderby'] . '-' . strtolower($atts['order']), 'date-asc'); ?>>
                        <?php _e('Oldest First', 'crelate-job-board'); ?>
                    </option>
                    <option value="title-asc" <?php selected($atts['orderby'] . '-' . strtolower($atts['order']), 'title-asc'); ?>>
                        <?php _e('Title A-Z', 'crelate-job-board'); ?>
                    </option>
                    <option value="title-desc" <?php selected($atts['orderby'] . '-' . strtolower($atts['order']), 'title-desc'); ?>>
                        <?php _e('Title Z-A', 'crelate-job-board'); ?>
                    </option>
                    <option value="location-asc" <?php selected($atts['orderby'] . '-' . strtolower($atts['order']), 'location-asc'); ?>>
                        <?php _e('Location A-Z', 'crelate-job-board'); ?>
                    </option>
                </select>
            </div>
            
            <!-- Clear Filters -->
            <div class="crelate-filter-group">
                <button type="button" id="crelate-clear-filters" class="crelate-btn crelate-btn-secondary">
                    <?php _e('Clear Filters', 'crelate-job-board'); ?>
                </button>
            </div>
            
        </div>
        <?php endif; ?>
        
    </div>
    <?php endif; ?>
    
    <!-- Results Summary -->
    <div class="crelate-results-summary">
        <div class="crelate-results-count">
            <?php 
            $total_jobs = $jobs->found_posts;
            printf(
                _n('%d job found', '%d jobs found', $total_jobs, 'crelate-job-board'),
                $total_jobs
            );
            ?>
        </div>
        
        <!-- Template Switcher -->
        <div class="crelate-template-switcher">
            <button type="button" class="crelate-template-btn" data-template="grid">
                <?php echo $templates->get_icon('grid'); ?>
            </button>
            <button type="button" class="crelate-template-btn active" data-template="list">
                <?php echo $templates->get_icon('list'); ?>
            </button>
        </div>
    </div>
    
    <!-- Jobs List -->
    <div class="crelate-jobs-container">
        <div class="crelate-jobs-list" id="crelate-jobs-list">
            
            <?php if ($jobs->have_posts()): ?>
                <?php while ($jobs->have_posts()): $jobs->the_post(); ?>
                    <?php $templates->render_job_item('list'); ?>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="crelate-no-jobs">
                    <div class="crelate-no-jobs-icon">
                        <?php echo $templates->get_icon('no-jobs'); ?>
                    </div>
                    <h3><?php _e('No Jobs Found', 'crelate-job-board'); ?></h3>
                    <p><?php _e('Try adjusting your search criteria or filters.', 'crelate-job-board'); ?></p>
                </div>
            <?php endif; ?>
            
        </div>
        
        <!-- Load More Button -->
        <?php if ($jobs->max_num_pages > 1 && $atts['show_pagination'] === 'true'): ?>
        <div class="crelate-load-more">
            <button type="button" id="crelate-load-more" class="crelate-btn crelate-btn-primary" data-page="1" data-max-pages="<?php echo $jobs->max_num_pages; ?>">
                <?php _e('Load More Jobs', 'crelate-job-board'); ?>
            </button>
        </div>
        <?php endif; ?>
        
    </div>
    
    <!-- Loading Indicator -->
    <div class="crelate-loading" id="crelate-loading" style="display: none;">
        <div class="crelate-spinner"></div>
        <p><?php _e('Loading jobs...', 'crelate-job-board'); ?></p>
    </div>
    
</div>

<script>
var __CRELATE_DBG__ = (typeof crelate_ajax !== 'undefined' && !!crelate_ajax.debug);
if (__CRELATE_DBG__) console.log('Template script loaded');
if (__CRELATE_DBG__) console.log('jQuery available:', typeof jQuery !== 'undefined');
if (__CRELATE_DBG__) console.log('CrelateJobBoard available:', typeof CrelateJobBoard !== 'undefined');
if (__CRELATE_DBG__) console.log('crelate_ajax available:', typeof crelate_ajax !== 'undefined');

// Wait for both jQuery and CrelateJobBoard to be available
function initializeJobBoard() {
    if (typeof jQuery !== 'undefined' && typeof CrelateJobBoard !== 'undefined') {
        if (__CRELATE_DBG__) console.log('jQuery and CrelateJobBoard ready - initializing');
        CrelateJobBoard.init({
            template: '<?php echo esc_js($atts['template']); ?>',
            postsPerPage: <?php echo intval($atts['posts_per_page']); ?>,
            showFilters: <?php echo $atts['show_filters'] === 'true' ? 'true' : 'false'; ?>,
            showSearch: <?php echo $atts['show_search'] === 'true' ? 'true' : 'false'; ?>
        });
    } else {
        if (__CRELATE_DBG__) console.log('Waiting for dependencies...');
        setTimeout(initializeJobBoard, 100);
    }
}

// Start initialization when document is ready (pure JavaScript approach)
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        if (__CRELATE_DBG__) console.log('DOM ready');
        initializeJobBoard();
    });
} else {
    if (__CRELATE_DBG__) console.log('DOM already ready');
    initializeJobBoard();
}
</script>

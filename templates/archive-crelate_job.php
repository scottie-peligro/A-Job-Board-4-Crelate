<?php
/**
 * Template for displaying job listings
 */

get_header(); ?>

<div class="crelate-job-board-container">
    <div class="crelate-job-board-header">
        <h1><?php _e('Job Opportunities', 'crelate-job-board'); ?></h1>
        <p><?php _e('Find your next career opportunity with our team', 'crelate-job-board'); ?></p>
    </div>

    <div class="crelate-job-board-content">
        <div class="crelate-job-board-sidebar">
            <?php echo do_shortcode('[crelate_job_search]'); ?>
            <?php echo do_shortcode('[crelate_job_filters]'); ?>
        </div>

        <div class="crelate-job-board-main">
            <div class="crelate-job-board-results">
                <?php echo do_shortcode('[crelate_jobs]'); ?>
            </div>
        </div>
    </div>
</div>

<style>
.crelate-job-board-container {
    width: 100%;
    max-width: none;
    margin: 0;
    padding: 20px;
}

.crelate-job-board-header {
    text-align: center;
    margin-bottom: 40px;
}

.crelate-job-board-header h1 {
    font-size: 2.5em;
    margin-bottom: 10px;
    color: #333;
}

.crelate-job-board-header p {
    font-size: 1.2em;
    color: #666;
}

.crelate-job-board-content {
    display: flex;
    gap: 30px;
}

.crelate-job-board-sidebar {
    flex: 0 0 300px;
}

.crelate-job-board-main {
    flex: 1;
}

@media (max-width: 768px) {
    .crelate-job-board-content {
        flex-direction: column;
    }
    
    .crelate-job-board-sidebar {
        flex: none;
        order: 2;
    }
    
    .crelate-job-board-main {
        order: 1;
    }
}
</style>

<?php get_footer(); ?>

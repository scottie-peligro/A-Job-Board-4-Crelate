# Crelate Job Board Template Usage Guide

## Overview

The Crelate Job Board plugin now includes a flexible template system that allows you to display jobs in multiple layouts with extensive customization options.

## Available Shortcodes

### 1. Full Job Board (`[crelate_job_board]`)

This is the main shortcode that displays a complete job board with search, filters, and pagination.

**Basic Usage:**
```
[crelate_job_board]
```

**Advanced Usage with Parameters:**
```
[crelate_job_board 
    template="grid" 
    posts_per_page="12" 
    show_filters="true" 
    show_search="true" 
    show_pagination="true" 
    orderby="date" 
    order="DESC"
]
```

**Available Parameters:**
- `template`: Layout template ("grid", "list", "cards")
- `posts_per_page`: Number of jobs per page (default: 12)
- `show_filters`: Show filter options (true/false)
- `show_search`: Show search box (true/false)
- `show_pagination`: Show pagination (true/false)
- `orderby`: Sort by ("date", "title", "location", "department", "salary")
- `order`: Sort order ("ASC", "DESC")
- `categories`: Filter by specific categories (comma-separated)
- `locations`: Filter by specific locations (comma-separated)
- `job_types`: Filter by job types (comma-separated)
- `experience_levels`: Filter by experience levels (comma-separated)
- `remote_only`: Show only remote jobs (true/false)

### 2. Simple Job List (`[crelate_job_list]`)

A simpler shortcode for displaying just a list of jobs without search/filters.

**Usage:**
```
[crelate_job_list 
    posts_per_page="10" 
    orderby="date" 
    order="DESC"
]
```

### 3. Job Count (`[crelate_job_count]`)

Display the total number of available jobs.

**Usage:**
```
[crelate_job_count]
```

## Template Layouts

### Grid Layout (Default)
- Jobs displayed in a responsive grid
- Each job shows as a card with image, title, location, and key details
- Hover effects and interactive elements
- Mobile-responsive design

### List Layout
- Jobs displayed in a vertical list
- More compact design with detailed information
- Good for showing more jobs per page
- Easy to scan through multiple positions

## Customization Options

### CSS Customization

You can customize the appearance by adding CSS to your theme. The main CSS classes are:

```css
/* Job board container */
.crelate-job-board

/* Search and filters section */
.crelate-job-board .job-board-header
.crelate-job-board .search-filters

/* Job grid/list items */
.crelate-job-board .job-grid
.crelate-job-board .job-list
.crelate-job-board .job-item

/* Individual job cards */
.crelate-job-board .job-card
.crelate-job-board .job-meta
.crelate-job-board .job-tags
.crelate-job-board .job-actions
```

### JavaScript Customization

The frontend JavaScript (`assets/js/frontend.js`) handles:
- Search functionality
- Filter interactions
- Template switching
- Load more pagination
- URL parameter management
- Quick actions (save, share)

You can extend or modify this functionality by adding custom JavaScript to your theme.

## Filter Options

The job board includes filters for:
- **Location**: Filter by job location
- **Department**: Filter by job department/category
- **Job Type**: Filter by employment type (Full-time, Part-time, Contract, etc.)
- **Experience Level**: Filter by required experience
- **Remote Work**: Filter for remote/hybrid positions
- **Salary Range**: Filter by salary brackets

## Search Functionality

The search feature searches across:
- Job titles
- Job descriptions
- Requirements
- Company information
- Location details

## Responsive Design

The templates are fully responsive and work on:
- Desktop computers
- Tablets
- Mobile phones

## Performance Features

- Lazy loading for job images
- AJAX-powered search and filtering
- Efficient database queries
- Caching of filter options
- Optimized for large job databases

## Troubleshooting

### Jobs Not Displaying
1. Check if jobs have been imported successfully
2. Verify the shortcode is placed correctly on a page
3. Check browser console for JavaScript errors
4. Ensure the plugin is activated and configured

### Styling Issues
1. Check if your theme CSS conflicts with the job board styles
2. Use browser developer tools to inspect elements
3. Add custom CSS to override default styles

### Search/Filters Not Working
1. Check if JavaScript is enabled in the browser
2. Verify the AJAX URL is correct
3. Check for JavaScript errors in browser console

## Advanced Usage

### Custom Template Integration

You can integrate the job board into your theme templates:

```php
<?php
// In your theme template
if (class_exists('Crelate_Templates')) {
    $templates = new Crelate_Templates();
    $atts = array(
        'template' => 'grid',
        'posts_per_page' => 6,
        'show_filters' => false
    );
    echo $templates->job_board_shortcode($atts);
}
?>
```

### Custom Filtering

You can add custom filters by modifying the `Crelate_Templates` class:

```php
// Add custom filter logic in class-crelate-templates.php
public function get_filtered_jobs($filters = array()) {
    // Your custom filtering logic here
}
```

## Support

For additional support or customization requests, please refer to the plugin documentation or contact the development team.

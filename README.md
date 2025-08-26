# A Job Board 4 Crelate (WordPress Plugin)

A comprehensive WordPress plugin that integrates the Crelate ATS Job Board with your WordPress website to display job listings, search, and filtering capabilities.

## Features

- **Automatic Job Import**: Import jobs from Crelate ATS via API
- **Custom Post Type**: Jobs stored as WordPress posts with custom fields
- **Search & Filtering**: Advanced search and filtering capabilities
- **Responsive Design**: Mobile-friendly job listings and detail pages
- **Application Tracking**: Track job application clicks and conversions
- **Admin Interface**: Easy-to-use admin panel for configuration
- **Shortcodes**: Flexible shortcodes for displaying job content
- **Cron Integration**: Automated job imports via WordPress cron
- **Template Support**: Custom templates for job archive and single pages

## Installation

1. Upload the plugin files to `/wp-content/plugins/crelate-job-board/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to 'Job Board' in the admin menu to configure settings

## Packaging and Releases

This repository is prepared to build a distributable ZIP on GitHub when you push a version tag.

- Commit your changes to `main`.
- Update the plugin header `Version` in `crelate-job-board.php` and the `CRELATE_JOB_BOARD_VERSION` constant.
- Create a tag like `v1.0.0` and push. A GitHub Actions workflow will:
  - Validate the plugin
  - Create a zip excluding development files
  - Attach it to the release

Manual build locally (PowerShell):

```
powershell -NoProfile -ExecutionPolicy Bypass -Command "Compress-Archive -Path * -DestinationPath crelate-job-board.zip -Force -CompressionLevel Optimal -Exclude \".git*\",\".github*\",\"*.zip\""
```

## Configuration

### API Settings

1. Navigate to **Job Board > Settings** in the WordPress admin
2. Enter your Crelate API key
3. (Optional) Enter your portal ID for apply URLs
4. Configure import frequency (hourly, daily, weekly)
5. Set jobs per page and other display options

### Manual Import

1. From **Job Board > Settings (Onboarding)**, click "Test Connection" to verify API access
2. Click "Import Jobs" to manually import jobs from Crelate
3. Monitor import progress and view statistics under **Job Board > Statistics**

## Shortcodes

### [crelate_jobs]
Display a list of jobs with search and filtering.

**Parameters:**
- `posts_per_page` - Number of jobs to display (default: 12)
- `department` - Filter by department
- `location` - Filter by location
- `type` - Filter by job type
- `experience` - Filter by experience level
- `remote` - Filter by remote work option

**Example:**
```
[crelate_jobs posts_per_page="6" department="Engineering"]
```

### [crelate_job_search]
Display a job search form.

**Parameters:**
- `placeholder` - Search input placeholder text
- `button_text` - Search button text

**Example:**
```
[crelate_job_search placeholder="Search jobs..." button_text="Find Jobs"]
```

### [crelate_job_filters]
Display job filter options.

**Parameters:**
- `show_department` - Show department filter (true/false)
- `show_location` - Show location filter (true/false)
- `show_type` - Show job type filter (true/false)
- `show_experience` - Show experience filter (true/false)
- `show_remote` - Show remote work filter (true/false)

**Example:**
```
[crelate_job_filters show_department="true" show_location="true"]
```

### [crelate_job_detail]
Display a single job's details.

**Parameters:**
- `job_id` - ID of the job to display
- `show_apply_button` - Show apply button (true/false)

**Example:**
```
[crelate_job_detail job_id="123" show_apply_button="true"]
```

### [crelate_job_apply]
Display an apply button for a job.

**Parameters:**
- `job_id` - ID of the job
- `button_text` - Button text (default: "Apply Now")
- `button_class` - CSS class for the button

**Example:**
```
[crelate_job_apply job_id="123" button_text="Apply for this Position"]
```

## Templates

The plugin includes custom templates for job display:

- `templates/archive-crelate_job.php` - Job listing page template
- `templates/single-crelate_job.php` - Single job detail page template

To customize these templates, copy them to your theme directory and modify as needed.

## Custom Post Type

The plugin creates a custom post type `crelate_job` with the following custom fields:

- `_job_location` - Job location
- `_job_type` - Job type (Full-time, Part-time, Contract, etc.)
- `_job_department` - Department
- `_job_experience` - Experience level
- `_job_remote` - Remote work option
- `_job_salary` - Salary information
- `_job_requirements` - Job requirements
- `_job_benefits` - Job benefits
- `_job_apply_url` - External application URL
- `_job_crelate_id` - Original Crelate job ID
- `_job_expires` - Job expiration date

## Custom Taxonomies

The plugin creates the following taxonomies for job categorization:

- `job_department` - Job departments
- `job_location` - Job locations
- `job_type` - Job types
- `job_experience` - Experience levels
- `job_remote` - Remote work options

## AJAX Endpoints

The plugin provides AJAX endpoints for dynamic functionality:

- `crelate_search_jobs` - Search jobs
- `crelate_filter_jobs` - Filter jobs
- `crelate_load_more_jobs` - Load more jobs (pagination)
- `crelate_get_job_detail` - Get single job details
- `crelate_track_application` - Track application clicks

## Admin Functions

### Test API Connection
Verify your Crelate API credentials are working correctly.

### Manual Import
Manually trigger job imports from Crelate.

### Import Statistics
View import history and statistics including:
- Last import date and time
- Total jobs imported
- Import status and messages
- Success/failure rates

## Styling

The plugin includes CSS files for styling:

- `assets/css/crelate-job-board.css` - Frontend styles
- `assets/css/admin.css` - Admin interface styles

You can customize these styles by adding custom CSS to your theme.

## JavaScript

The plugin includes JavaScript files for interactive functionality:

- `assets/js/crelate-job-board.js` - Frontend interactions
- `assets/js/admin.js` - Admin interface interactions

## Cron Jobs

The plugin uses WordPress cron to automatically import jobs:

- **Hook**: `crelate_job_board_import_cron`
- **Frequency**: Configurable (hourly, daily, weekly)
- **Function**: `Crelate_API::import_jobs()`

## Error Handling

The plugin includes comprehensive error handling:

- API connection errors
- Import failures
- Missing required fields
- Invalid data formats

Errors are logged and displayed in the admin interface.

## Security

- All user inputs are sanitized and validated
- API credentials are stored securely (masked in admin views)
- AJAX requests include nonce verification
- SQL queries use prepared statements

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- cURL extension enabled
- WordPress cron enabled

## Support

For support and documentation, please contact the development team.

## Changelog

### Version 1.0.0
- Initial release
- Crelate API integration
- Custom post type and taxonomies
- Search and filtering functionality
- Admin interface
- Shortcodes
- Templates
- AJAX functionality
- Cron integration

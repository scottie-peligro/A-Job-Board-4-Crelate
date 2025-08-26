<?php
/**
 * Crelate API Integration Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crelate_API {
    
    /**
     * API endpoint
     */
    private $api_endpoint = 'https://app.crelate.com/api/pub/v1';
    
    /**
     * API key
     */
    private $api_key;
    
    /**
     * Portal ID
     */
    private $portal_id;
    
    /**
     * Last API response for pagination
     */
    private $last_response;
    
    /**
     * Constructor
     */
    public function __construct() {
        $settings = get_option('crelate_job_board_settings');
        $this->api_key = !empty($settings['api_key']) ? $settings['api_key'] : '';
        $this->portal_id = !empty($settings['portal_id']) ? $settings['portal_id'] : '';
        $this->api_endpoint = !empty($settings['api_endpoint']) ? $settings['api_endpoint'] : $this->api_endpoint;
    }
    
    /**
     * Make API request
     */
    private function make_request($endpoint, $method = 'GET', $data = null) {
        $url = $this->api_endpoint . $endpoint;
        
        $args = array(
            'method' => $method,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                // Try multiple auth mechanisms
                'Authorization' => 'Bearer ' . $this->api_key,
                'X-API-Key' => $this->api_key,
            ),
            'timeout' => 30,
            'sslverify' => true
        );
        
        if ($data && in_array($method, array('POST', 'PUT', 'PATCH'))) {
            $args['body'] = json_encode($data);
        }
        
        // Optional debug: avoid logging secrets
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Crelate API Request: ' . $url);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            error_log('Crelate API Error: ' . $response->get_error_message());
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $response_headers = wp_remote_retrieve_headers($response);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Crelate API Response Code: ' . $response_code);
        }

        // Fallback: if not successful, retry with api_key query param
        if ($response_code < 200 || $response_code >= 300) {
            $separator = strpos($endpoint, '?') !== false ? '&' : '?';
            $fallback_url = $this->api_endpoint . $endpoint . $separator . 'api_key=' . urlencode($this->api_key);
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Crelate API Fallback request with query api_key');
            }
            $fallback_args = $args;
            // Keep headers; some APIs accept either/both
            $response = wp_remote_request($fallback_url, $fallback_args);
            if (is_wp_error($response)) {
                error_log('Crelate API Fallback Error: ' . $response->get_error_message());
                return false;
            }
            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);
        }

        // Fallback 2: try header-only X-API-Key (no Authorization)
        if ($response_code < 200 || $response_code >= 300) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Crelate API Fallback 2: header-only X-API-Key');
            }
            $header_only_args = $args;
            unset($header_only_args['headers']['Authorization']);
            $header_only_args['headers']['X-API-Key'] = $this->api_key;
            $response = wp_remote_request($url, $header_only_args);
            if (is_wp_error($response)) {
                error_log('Crelate API Fallback2 Error: ' . $response->get_error_message());
                return false;
            }
            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);
        }
        
        if ($response_code >= 200 && $response_code < 300) {
            $decoded = json_decode($response_body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('Crelate API JSON Decode Error: ' . json_last_error_msg());
                return false;
            }
            return $decoded;
        } else {
            error_log('Crelate API Error: HTTP ' . $response_code . ' - ' . $response_body);
            return false;
        }
    }
    
    /**
     * Get jobs from Crelate
     */
    public function get_jobs($params = array()) {
        // Check if we have required credentials
        if (empty($this->api_key)) {
            error_log('Crelate API: Missing API key');
            return false;
        }
        
        $default_params = array(
            'take' => 100, // Reduced from 500 to avoid HTTP 400 errors
            'skip' => 0
        );
        
        $params = wp_parse_args($params, $default_params);
        
        // Use the correct endpoint from Crelate documentation
        $endpoint = '/jobPostings?' . http_build_query($params);
        
        $response = $this->make_request($endpoint);
        
        // Endpoint fallback (case variant)
        if ($response === false) {
            $endpoint2 = '/jobpostings?' . http_build_query($params);
            $response = $this->make_request($endpoint2);
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Crelate API get_jobs received response');
        }
        
        // Handle different possible response structures
        if ($response) {
            // Store the full response for pagination info
            $this->last_response = $response;
            
            if (isset($response['Results'])) {
                return $response['Results'];
            } elseif (isset($response['data'])) {
                return $response['data'];
            } elseif (isset($response['items'])) {
                return $response['items'];
            } elseif (is_array($response)) {
                // If response is directly an array of jobs
                return $response;
            }
        }
        
        return false;
    }
    

    
    /**
     * Get single job by ID
     */
    public function get_job($job_id) {
        // Use the correct endpoint from Crelate documentation
        $endpoint = '/jobPostings/' . $job_id;
        
        $response = $this->make_request($endpoint);
        if ($response && isset($response['data'])) {
            return $response['data'];
        }
        
        return false;
    }
    
    /**
     * Import jobs from Crelate
     */
    public function import_jobs($force_update = false) {
        try {
            $all_jobs = array();
            $skip = 0;
                            $take = 100; // Reduced from 500 to avoid HTTP 400 errors
            $has_more = true;
            
            // Fetch all jobs using pagination
            while ($has_more) {
                $jobs = $this->get_jobs(array('take' => $take, 'skip' => $skip));
                
                if (!$jobs || !is_array($jobs)) {
                    break;
                }
                
                $all_jobs = array_merge($all_jobs, $jobs);
                
                // Check if there are more records based on API response
                if (isset($this->last_response['MoreRecords']) && $this->last_response['MoreRecords']) {
                    $skip += $take;
                } else {
                    $has_more = false;
                }
                
                // Safety check to prevent infinite loops
                if ($skip > 10000) {
                    error_log('Crelate API: Safety limit reached during pagination');
                    break;
                }
            }
            
            // Debug the jobs data
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Crelate API import_jobs - total jobs count: ' . count($all_jobs));
            }
            
            if (empty($all_jobs)) {
                return array(
                    'success' => false,
                    'message' => __('No jobs found or API connection failed.', 'crelate-job-board'),
                    'imported' => 0,
                    'updated' => 0,
                    'errors' => 0
                );
            }
        
        $imported = 0;
        $updated = 0;
        $errors = 0;
        
        foreach ($all_jobs as $job_data) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Crelate API: Processing job');
            }
            $result = $this->import_single_job($job_data, $force_update);
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Crelate API: Import result');
            }
            if ($result === 'imported') {
                $imported++;
            } elseif ($result === 'updated') {
                $updated++;
            } else {
                $errors++;
            }
        }
        
        // Update import statistics
        $last_import = array(
            'timestamp' => current_time('mysql'),
            'imported' => $imported,
            'updated' => $updated,
            'errors' => $errors,
        );
        
        // Store both in settings and a dedicated option for quick access
        $settings = get_option('crelate_job_board_settings', array());
        $settings['last_import'] = $last_import;
        $settings['total_jobs_imported'] = intval($settings['total_jobs_imported'] ?? 0) + $imported;
        update_option('crelate_job_board_settings', $settings);
        update_option('crelate_job_board_import_log', $last_import);
        
        return array(
            'success' => true,
            'message' => sprintf(__('Import completed. %d new jobs, %d updated, %d errors.', 'crelate-job-board'), $imported, $updated, $errors),
            'imported' => $imported,
            'updated' => $updated,
            'errors' => $errors,
            'total' => ($imported + $updated + $errors)
        );
        } catch (Exception $e) {
            error_log('Crelate API import_jobs error: ' . $e->getMessage());
            return array(
                'success' => false,
                'message' => __('Import failed: ' . $e->getMessage(), 'crelate-job-board'),
                'imported' => 0,
                'updated' => 0,
                'errors' => 1
            );
        }
    }
    
    /**
     * Import single job
     */
    private function import_single_job($job_data, $force_update = false) {
        try {
            // Validate job data - handle both uppercase and lowercase field names
            $job_id = !empty($job_data['Id']) ? $job_data['Id'] : (!empty($job_data['id']) ? $job_data['id'] : '');
            if (empty($job_id)) {
                error_log('Crelate API: Job data missing ID: ' . print_r($job_data, true));
                return 'error';
            }
            
            // Check if job already exists
            $existing_posts = get_posts(array(
                'post_type' => 'crelate_job',
                'meta_query' => array(
                    array(
                        'key' => '_job_crelate_id',
                        'value' => $job_id,
                        'compare' => '='
                    )
                ),
                'post_status' => 'any',
                'numberposts' => 1,
                'fields' => 'ids'
            ));
            
            // Get the job's published date from Crelate
            $published_date = !empty($job_data['CreatedOn']) ? $job_data['CreatedOn'] : (!empty($job_data['createdOn']) ? $job_data['createdOn'] : '');
            $post_date = !empty($published_date) ? date('Y-m-d H:i:s', strtotime($published_date)) : current_time('mysql');
            
            $post_data = array(
            'post_title' => $this->get_job_title($job_data),
            'post_content' => $this->get_job_description($job_data),
            'post_status' => 'publish',
            'post_type' => 'crelate_job',
            'post_date' => $post_date,
            'post_date_gmt' => get_gmt_from_date($post_date)
        );
        
        if (!empty($existing_posts)) {
            $post_id = is_array($existing_posts) ? intval($existing_posts[0]) : intval($existing_posts);
            
            // Check if job has been updated
            $modified_date = !empty($job_data['ModifiedOn']) ? $job_data['ModifiedOn'] : (!empty($job_data['modifiedDate']) ? $job_data['modifiedDate'] : '');
            $last_modified = get_post_meta($post_id, '_job_last_modified', true);
            if (!$force_update && $last_modified === $modified_date) {
                return 'skipped'; // No changes
            }
            
            $post_data['ID'] = $post_id;
            wp_update_post($post_data);
            $action = 'updated';
        } else {
            $post_id = wp_insert_post($post_data);
            $action = 'imported';
        }
        
        if (is_wp_error($post_id)) {
            return 'error';
        }
        
        // Set job meta data
        $this->set_job_meta($post_id, $job_data);
        
        // Set job taxonomies
        $this->set_job_taxonomies($post_id, $job_data);
        
        return $action;
        } catch (Exception $e) {
            error_log('Crelate API import_single_job error: ' . $e->getMessage());
            return 'error';
        }
    }
    
    /**
     * Get job title
     */
    private function get_job_title($job_data) {
        // Handle both lowercase and uppercase field names
        if (!empty($job_data['Title'])) {
            return sanitize_text_field($job_data['Title']);
        } elseif (!empty($job_data['title'])) {
            return sanitize_text_field($job_data['title']);
        }
        
        return __('Untitled Job', 'crelate-job-board');
    }
    
    /**
     * Get job description
     */
    private function get_job_description($job_data) {
        $description = '';
        
        // Handle both lowercase and uppercase field names
        if (!empty($job_data['Description'])) {
            $description .= wp_kses_post($job_data['Description']);
        } elseif (!empty($job_data['description'])) {
            $description .= wp_kses_post($job_data['description']);
        }
        
        if (!empty($job_data['Requirements'])) {
            $description .= '<h3>' . __('Requirements', 'crelate-job-board') . '</h3>';
            $description .= wp_kses_post($job_data['Requirements']);
        } elseif (!empty($job_data['requirements'])) {
            $description .= '<h3>' . __('Requirements', 'crelate-job-board') . '</h3>';
            $description .= wp_kses_post($job_data['requirements']);
        }
        
        if (!empty($job_data['Benefits'])) {
            $description .= '<h3>' . __('Benefits', 'crelate-job-board') . '</h3>';
            $description .= wp_kses_post($job_data['Benefits']);
        } elseif (!empty($job_data['benefits'])) {
            $description .= '<h3>' . __('Benefits', 'crelate-job-board') . '</h3>';
            $description .= wp_kses_post($job_data['benefits']);
        }
        
        return $description;
    }
    
    /**
     * Set job meta data
     */
    private function set_job_meta($post_id, $job_data) {
        // Handle both lowercase and uppercase field names
        $job_id = !empty($job_data['Id']) ? $job_data['Id'] : (!empty($job_data['id']) ? $job_data['id'] : '');
        $requirements = !empty($job_data['Requirements']) ? $job_data['Requirements'] : (!empty($job_data['requirements']) ? $job_data['requirements'] : '');
        $benefits = !empty($job_data['Benefits']) ? $job_data['Benefits'] : (!empty($job_data['benefits']) ? $job_data['benefits'] : '');
        $expires = !empty($job_data['ExpirationDate']) ? $job_data['ExpirationDate'] : (!empty($job_data['expirationDate']) ? $job_data['expirationDate'] : '');
        $modified = !empty($job_data['ModifiedOn']) ? $job_data['ModifiedOn'] : (!empty($job_data['modifiedDate']) ? $job_data['modifiedDate'] : '');
        
        $meta_fields = array(
            '_job_crelate_id' => $job_id,
            '_job_location' => $this->get_job_location($job_data),
            '_job_type' => $this->get_job_type($job_data),
            '_job_department' => $this->get_job_department($job_data),
            '_job_experience' => $this->get_job_experience_level($job_data),
            '_job_remote' => $this->get_job_remote_work($job_data),
            '_job_salary' => $this->get_job_salary($job_data),
            '_job_requirements' => $this->extract_requirements($job_data),
            '_job_benefits' => $this->extract_benefits($job_data),
            '_job_apply_url' => $this->get_job_apply_url($job_data),
            '_job_expires' => $expires,
            '_job_last_modified' => $modified,
            '_job_crelate_raw' => json_encode($job_data)
        );
        
        foreach ($meta_fields as $key => $value) {
            update_post_meta($post_id, $key, $value);
        }
    }
    
    /**
     * Get job location
     */
    private function get_job_location($job_data) {
        // Handle both nested location object and flat fields
        if (!empty($job_data['location']['city']) && !empty($job_data['location']['state'])) {
            return $job_data['location']['city'] . ', ' . $job_data['location']['state'];
        } elseif (!empty($job_data['location']['city'])) {
            return $job_data['location']['city'];
        } elseif (!empty($job_data['location']['state'])) {
            return $job_data['location']['state'];
        }
        
        // Handle flat location fields (Crelate API format)
        if (!empty($job_data['City']) && !empty($job_data['State'])) {
            return $job_data['City'] . ', ' . $job_data['State'];
        } elseif (!empty($job_data['City'])) {
            return $job_data['City'];
        } elseif (!empty($job_data['State'])) {
            return $job_data['State'];
        }
        
        return '';
    }
    
    /**
     * Get job type
     */
    private function get_job_type($job_data) {
        // Handle both lowercase and uppercase field names
        if (!empty($job_data['EmploymentType'])) {
            return ucfirst(strtolower($job_data['EmploymentType']));
        } elseif (!empty($job_data['employmentType'])) {
            return ucfirst(strtolower($job_data['employmentType']));
        }
        
        // Try to extract from job title or description
        $title = !empty($job_data['Title']) ? strtolower($job_data['Title']) : '';
        $description = !empty($job_data['Description']) ? strtolower($job_data['Description']) : '';
        
        // Look for employment type indicators
        if (strpos($title, 'full-time') !== false || strpos($description, 'full-time') !== false) {
            return 'Full-time';
        } elseif (strpos($title, 'part-time') !== false || strpos($description, 'part-time') !== false) {
            return 'Part-time';
        } elseif (strpos($title, 'contract') !== false || strpos($description, 'contract') !== false) {
            return 'Contract';
        } elseif (strpos($title, 'temporary') !== false || strpos($description, 'temporary') !== false) {
            return 'Temporary';
        }
        
        return '';
    }
    
    /**
     * Get job department
     */
    private function get_job_department($job_data) {
        // Handle both nested category object and flat fields
        if (!empty($job_data['category']['name'])) {
            return $job_data['category']['name'];
        }
        
        // Handle flat category fields (Crelate API format)
        if (!empty($job_data['Category'])) {
            return $job_data['Category'];
        }
        
        // Try to extract from job title or description
        $title = !empty($job_data['Title']) ? strtolower($job_data['Title']) : '';
        $description = !empty($job_data['Description']) ? strtolower($job_data['Description']) : '';
        
        // Look for department indicators
        if (strpos($title, 'sales') !== false || strpos($description, 'sales') !== false) {
            return 'Sales';
        } elseif (strpos($title, 'engineering') !== false || strpos($description, 'engineering') !== false) {
            return 'Engineering';
        } elseif (strpos($title, 'marketing') !== false || strpos($description, 'marketing') !== false) {
            return 'Marketing';
        } elseif (strpos($title, 'hr') !== false || strpos($title, 'human resources') !== false || strpos($description, 'human resources') !== false) {
            return 'Human Resources';
        } elseif (strpos($title, 'it') !== false || strpos($title, 'technology') !== false || strpos($description, 'technology') !== false) {
            return 'Information Technology';
        } elseif (strpos($title, 'finance') !== false || strpos($description, 'finance') !== false) {
            return 'Finance';
        } elseif (strpos($title, 'operations') !== false || strpos($description, 'operations') !== false) {
            return 'Operations';
        }
        
        return '';
    }
    
    /**
     * Get job salary
     */
    private function get_job_salary($job_data) {
        // Handle both nested salary object and flat fields
        if (!empty($job_data['salary']['min']) && !empty($job_data['salary']['max'])) {
            return '$' . number_format($job_data['salary']['min']) . ' - $' . number_format($job_data['salary']['max']);
        } elseif (!empty($job_data['salary']['min'])) {
            return '$' . number_format($job_data['salary']['min']) . '+';
        } elseif (!empty($job_data['salary']['max'])) {
            return 'Up to $' . number_format($job_data['salary']['max']);
        }
        
        // Handle flat compensation field (Crelate API format)
        if (!empty($job_data['Compensation'])) {
            return $job_data['Compensation'];
        }
        
        return '';
    }
    
    /**
     * Get job experience level
     */
    private function get_job_experience_level($job_data) {
        // Handle both lowercase and uppercase field names
        if (!empty($job_data['ExperienceLevel'])) {
            return strtolower($job_data['ExperienceLevel']);
        } elseif (!empty($job_data['experienceLevel'])) {
            return strtolower($job_data['experienceLevel']);
        }
        
        // Try to extract from job title or description
        $title = !empty($job_data['Title']) ? strtolower($job_data['Title']) : '';
        $description = !empty($job_data['Description']) ? strtolower($job_data['Description']) : '';
        
        // Look for experience level indicators
        if (strpos($title, 'senior') !== false || strpos($description, 'senior') !== false) {
            return 'senior';
        } elseif (strpos($title, 'junior') !== false || strpos($description, 'junior') !== false) {
            return 'junior';
        } elseif (strpos($title, 'entry') !== false || strpos($description, 'entry level') !== false) {
            return 'entry';
        } elseif (strpos($title, 'lead') !== false || strpos($description, 'lead') !== false) {
            return 'lead';
        } elseif (strpos($title, 'manager') !== false || strpos($description, 'manager') !== false) {
            return 'manager';
        } elseif (strpos($title, 'director') !== false || strpos($description, 'director') !== false) {
            return 'director';
        }
        
        return '';
    }
    
    /**
     * Get job remote work option
     */
    private function get_job_remote_work($job_data) {
        // Handle both lowercase and uppercase field names
        if (!empty($job_data['RemoteWork'])) {
            return strtolower($job_data['RemoteWork']);
        } elseif (!empty($job_data['remoteWork'])) {
            return strtolower($job_data['remoteWork']);
        }
        
        // Try to extract from job title or description
        $title = !empty($job_data['Title']) ? strtolower($job_data['Title']) : '';
        $description = !empty($job_data['Description']) ? strtolower($job_data['Description']) : '';
        
        // Look for remote work indicators
        if (strpos($title, 'remote') !== false || strpos($description, 'remote') !== false) {
            return 'remote';
        } elseif (strpos($title, 'work from home') !== false || strpos($description, 'work from home') !== false) {
            return 'remote';
        } elseif (strpos($title, 'hybrid') !== false || strpos($description, 'hybrid') !== false) {
            return 'hybrid';
        } elseif (strpos($title, 'on-site') !== false || strpos($description, 'on-site') !== false) {
            return 'on-site';
        }
        
        return '';
    }
    
    /**
     * Get job apply URL
     */
    private function get_job_apply_url($job_data) {
        // Handle both lowercase and uppercase field names
        if (!empty($job_data['ApplyUrl'])) {
            return $job_data['ApplyUrl'];
        } elseif (!empty($job_data['applyUrl'])) {
            return $job_data['applyUrl'];
        }
        
        // Generate default apply URL using the correct Crelate structure
        $job_code = !empty($job_data['JobCode']) ? $job_data['JobCode'] : (!empty($job_data['jobCode']) ? $job_data['jobCode'] : '');
        if ($job_code) {
            $settings = get_option('crelate_job_board_settings', array());
            $portal = !empty($settings['portal_id']) ? sanitize_title($settings['portal_id']) : '';
            if (!empty($portal)) {
                return 'https://jobs.crelate.com/portal/' . $portal . '/job/apply/' . $job_code;
            }
            // Fallback without portal
            return 'https://jobs.crelate.com/portal/job/apply/' . $job_code;
        }
        
        return '';
    }
    
    /**
     * Set job taxonomies
     */
    private function set_job_taxonomies($post_id, $job_data) {
        // Set department/category using enhanced extraction
        $department = $this->get_job_department($job_data);
        if (!empty($department)) {
            wp_set_object_terms($post_id, $department, 'job_department');
        }
        
        // Set location
        if (!empty($job_data['City'])) {
            wp_set_object_terms($post_id, $job_data['City'], 'job_location');
        }
        
        // Set employment type using enhanced extraction
        $job_type = $this->get_job_type($job_data);
        if (!empty($job_type)) {
            wp_set_object_terms($post_id, $job_type, 'job_type');
        }
        
        // Set experience level using enhanced extraction
        $experience = $this->get_job_experience_level($job_data);
        if (!empty($experience)) {
            wp_set_object_terms($post_id, $experience, 'job_experience');
        }
        
        // Set remote work using enhanced extraction
        $remote = $this->get_job_remote_work($job_data);
        if (!empty($remote)) {
            wp_set_object_terms($post_id, $remote, 'job_remote');
        }
    }
    
       /**
     * Test API connection
     */
    public function test_connection() {
        // Check if we have required credentials
        if (empty($this->api_key)) {
            return array(
                'success' => false,
                'message' => __('API key is required. Please enter your Crelate API key in the settings.', 'crelate-job-board')
            );
        }
        
        // Try multiple strategies/endpoints
        $attempts = array(
            '/jobPostings?take=1',
            '/jobpostings?take=1'
        );
        foreach ($attempts as $ep) {
            $response = $this->make_request($ep);
            if ($response !== false) {
                return array(
                    'success' => true,
                    'message' => sprintf(__('API connection successful! Endpoint: %s', 'crelate-job-board'), $ep)
                );
            }
        }
        
        return array(
            'success' => false,
            'message' => __('API connection failed after multiple attempts. Please verify your API key and endpoint. If this previously worked, it may be a temporary service issue or rate limit. Try again shortly.', 'crelate-job-board')
        );
    }
    
        /**
     * Test API key format and basic connectivity
     */
    public function test_api_key() {
        // Test if the API key looks valid
        if (empty($this->api_key) || strlen($this->api_key) < 10) {
            return array(
                'success' => false,
                'message' => __('API key appears to be invalid or too short. Crelate API keys are typically longer.', 'crelate-job-board')
            );
        }
        
        // Test basic connectivity to the API base URL
        $test_url = $this->api_endpoint . '/jobPostings?take=1';
        $response = wp_remote_get($test_url, array(
            'timeout' => 10,
            'sslverify' => true,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Accept' => 'application/json'
            ),
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => __('Cannot connect to Crelate API. Network error: ' . $response->get_error_message(), 'crelate-job-board')
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code === 200) {
            return array(
                'success' => true,
                'message' => __('API connection successful! The API key is valid and the endpoint is accessible.', 'crelate-job-board')
            );
        }
        
        return array(
            'success' => false,
            'message' => __('API returned HTTP ' . $response_code . '. Please check your API key and ensure API access is enabled.', 'crelate-job-board')
        );
    }
    
    /**
     * Extract requirements from job data
     */
    private function extract_requirements($job_data) {
        // Handle both lowercase and uppercase field names
        if (!empty($job_data['Requirements'])) {
            return $job_data['Requirements'];
        } elseif (!empty($job_data['requirements'])) {
            return $job_data['requirements'];
        }
        
        // Try to extract from description
        $description = !empty($job_data['Description']) ? $job_data['Description'] : '';
        if (!empty($description)) {
            // Look for requirements section
            if (preg_match('/<h3>Requirements<\/h3>(.*?)(?=<h3>|$)/s', $description, $matches)) {
                return trim($matches[1]);
            } elseif (preg_match('/requirements:(.*?)(?=benefits:|$)/is', $description, $matches)) {
                return trim($matches[1]);
            }
        }
        
        return '';
    }
    
    /**
     * Extract benefits from job data
     */
    private function extract_benefits($job_data) {
        // Handle both lowercase and uppercase field names
        if (!empty($job_data['Benefits'])) {
            return $job_data['Benefits'];
        } elseif (!empty($job_data['benefits'])) {
            return $job_data['benefits'];
        }
        
        // Try to extract from description
        $description = !empty($job_data['Description']) ? $job_data['Description'] : '';
        if (!empty($description)) {
            // Look for benefits section
            if (preg_match('/<h3>Benefits<\/h3>(.*?)(?=<h3>|$)/s', $description, $matches)) {
                return trim($matches[1]);
            } elseif (preg_match('/benefits:(.*?)(?=requirements:|$)/is', $description, $matches)) {
                return trim($matches[1]);
            }
        }
        
        return '';
    }
    
    /**
     * Get import statistics
     */
    public function get_import_stats() {
        $total_jobs = wp_count_posts('crelate_job');
        $published_jobs = $total_jobs->publish;
        $draft_jobs = $total_jobs->draft;
        
        $last_import = get_option('crelate_job_board_import_log');
        
        return array(
            'total_jobs' => intval($published_jobs + $draft_jobs),
            'published_jobs' => intval($published_jobs),
            'draft_jobs' => intval($draft_jobs),
            'last_import' => $last_import
        );
    }
}
<?php
/**
 * Crelate API Client
 * 
 * Simple, clean API client for Crelate integration.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crelate_Client {
    
    /**
     * API endpoint
     */
    private $api_endpoint = 'https://app.crelate.com/api';
    
    /**
     * API key
     */
    private $api_key;
    
    /**
     * Portal ID
     */
    private $portal_id;
    
    /**
     * Constructor
     */
    public function __construct() {
        $settings = get_option('crelate_job_board_settings');
        $this->api_key = !empty($settings['api_key']) ? $settings['api_key'] : '';
        $this->portal_id = !empty($settings['portal_id']) ? $settings['portal_id'] : '';
        
        // Allow custom endpoint override
        if (!empty($settings['api_endpoint'])) {
            $this->api_endpoint = $settings['api_endpoint'];
        }
    }
    
    /**
     * Make API request
     */
    public function request($method, $path, $data = null) {
        if (empty($this->api_key)) {
            throw new Exception('API key not configured');
        }
        
        $url = $this->api_endpoint . $path;
        $headers = $this->get_headers();
        
        $args = array(
            'method' => $method,
            'timeout' => 30,
            'headers' => $headers,
        );
        
        if ($data) {
            $args['body'] = json_encode($data);
        }
        
        // Make API request with Bearer token authentication
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            throw new Exception('Request failed: ' . $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        // Try to decode JSON, but handle errors gracefully
        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            // If JSON decode fails, return the raw body
            $data = $body;
        }
        
        return array(
            'status' => $status_code,
            'body' => $body,
            'data' => $data
        );
    }
    
    /**
     * Get request headers
     */
    private function get_headers() {
        $headers = array(
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'User-Agent' => 'WordPress-Crelate-Plugin/1.0.6'
        );
        
        // Set Bearer token authentication
        if (!empty($this->api_key)) {
            $headers['Authorization'] = 'Bearer ' . $this->api_key;
        }
        
        // Portal ID is optional and may not be required for all API calls
        if (!empty($this->portal_id)) {
            $headers['X-Portal-ID'] = $this->portal_id;
        }
        
        return $headers;
    }
    
    /**
     * Test API connection
     */
    public function test_connection() {
        try {
            if (empty($this->api_key)) {
                return array(
                    'success' => false,
                    'message' => 'API key not configured'
                );
            }
            
            $response = $this->request('GET', '/jobPostings?take=1');
            
            if ($response['status'] >= 200 && $response['status'] < 300) {
                return array(
                    'success' => true,
                    'message' => 'API connection successful'
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'API returned status ' . $response['status'] . ': ' . $response['body']
                );
            }
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
    
    /**
     * Get job postings
     */
    public function get_job_postings($limit = 50, $offset = 0) {
        $path = "/jobPostings?take={$limit}&skip={$offset}";
        return $this->request('GET', $path);
    }
    
    /**
     * Get single job posting
     */
    public function get_job_posting($job_id) {
        return $this->request('GET', "/jobPostings/{$job_id}");
    }
    
    /**
     * Submit job application
     */
    public function submit_application($job_id, $application_data) {
        $path = "/jobPostings/{$job_id}/applications";
        return $this->request('POST', $path, $application_data);
    }
    
    /**
     * Get API endpoint
     */
    public function get_api_endpoint() {
        return $this->api_endpoint;
    }
    
    /**
     * Get portal ID
     */
    public function get_portal_id() {
        return $this->portal_id;
    }
    
    /**
     * Check if API key is configured
     */
    public function has_api_key() {
        return !empty($this->api_key);
    }
}

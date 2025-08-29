<?php
/**
 * Crelate Submission Service
 * 
 * Handles candidate creation, resume upload, and job linking.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crelate_SubmitService {
    
    /**
     * Crelate API client
     */
    private $client;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->client = new Crelate_Client();
    }
    
    /**
     * Submit candidate application
     */
    public function submit_application($data, $form_id = null) {
        $result = array(
            'success' => false,
            'crelate_id' => null,
            'errors' => array(),
            'warnings' => array()
        );
        
        try {
            // Check API configuration first
            $api_test = $this->client->test_connection();
            if (!$api_test['success']) {
                $result['errors'][] = 'API configuration error: ' . $api_test['message'];
                $this->log_error('API configuration check failed', $api_test['message'], $data);
                return $result;
            }
            
            // Validate required data
            $validation = $this->validate_submission_data($data);
            if (!$validation['valid']) {
                $result['errors'] = $validation['errors'];
                $this->log_error('Data validation failed', $validation['errors'], $data);
                return $result;
            }
            
            // Generate idempotency key
            $idempotency_key = $this->client->generate_idempotency_key(
                $data['email'],
                $form_id ?: 'default',
                time()
            );
            
            // Create candidate
            $candidate_result = $this->create_candidate($data, $idempotency_key);
            if (!$candidate_result['success']) {
                $result['errors'] = $candidate_result['errors'];
                return $result;
            }
            
            $crelate_id = $candidate_result['crelate_id'];
            $result['crelate_id'] = $crelate_id;
            
            // Handle resume upload if provided
            if (!empty($data['resume'])) {
                $resume_result = $this->upload_resume($crelate_id, $data['resume']);
                if (!$resume_result['success']) {
                    $result['warnings'][] = 'Resume upload failed: ' . $resume_result['error'];
                }
            }
            
            // Link to job if job_id provided
            if (!empty($data['job_id'])) {
                $job_result = $this->link_job($crelate_id, $data['job_id']);
                if (!$job_result['success']) {
                    $result['warnings'][] = 'Job linking failed: ' . $job_result['error'];
                }
            }
            
            $result['success'] = true;
            
        } catch (Exception $e) {
            $result['errors'][] = 'Submission failed: ' . $e->getMessage();
            $this->log_error('Application submission failed', $e->getMessage(), $data);
        }
        
        return $result;
    }
    
    /**
     * Create candidate in Crelate
     */
    public function create_candidate($data, $idempotency_key = null) {
        $result = array(
            'success' => false,
            'crelate_id' => null,
            'errors' => array()
        );
        
        try {
            // Prepare candidate data
            $candidate_data = $this->prepare_candidate_data($data);
            
            // Make API request
            $response = $this->client->request('POST', '/candidates', array(
                'data' => $candidate_data,
                'idempotency_key' => $idempotency_key
            ));
            
            if ($response['status'] === 201 || $response['status'] === 200) {
                $response_data = json_decode($response['body'], true);
                
                if (isset($response_data['id'])) {
                    $result['success'] = true;
                    $result['crelate_id'] = $response_data['id'];
                    $this->log_success('Candidate created', $response_data['id'], $data);
                } else {
                    $result['errors'][] = 'Invalid response format from Crelate API';
                }
            } else {
                $result['errors'][] = $this->parse_api_error($response);
            }
            
        } catch (Exception $e) {
            $result['errors'][] = 'Candidate creation failed: ' . $e->getMessage();
            $this->log_error('Candidate creation failed', $e->getMessage(), $data);
        }
        
        return $result;
    }
    
    /**
     * Upload resume for candidate
     */
    public function upload_resume($candidate_id, $file_data) {
        $result = array(
            'success' => false,
            'error' => null
        );
        
        try {
            // Handle different file data formats
            $file_path = $this->get_file_path($file_data);
            if (!$file_path || !file_exists($file_path)) {
                $result['error'] = 'Resume file not found';
                return $result;
            }
            
            // Upload file using multipart form data
            $response = $this->upload_file_multipart($candidate_id, $file_path);
            
            if ($response['status'] === 201 || $response['status'] === 200) {
                $result['success'] = true;
                $this->log_success('Resume uploaded', $candidate_id, array('file' => basename($file_path)));
            } else {
                $result['error'] = $this->parse_api_error($response);
            }
            
        } catch (Exception $e) {
            $result['error'] = 'Resume upload failed: ' . $e->getMessage();
            $this->log_error('Resume upload failed', $e->getMessage(), array('candidate_id' => $candidate_id));
        }
        
        return $result;
    }
    
    /**
     * Link candidate to job
     */
    public function link_job($candidate_id, $job_id) {
        $result = array(
            'success' => false,
            'error' => null
        );
        
        try {
            $link_data = array(
                'candidateId' => $candidate_id,
                'requisitionId' => $job_id,
                'status' => 'Applied'
            );
            
            $response = $this->client->request('POST', '/candidates/' . $candidate_id . '/requisitions', array(
                'data' => $link_data
            ));
            
            if ($response['status'] === 201 || $response['status'] === 200) {
                $result['success'] = true;
                $this->log_success('Job linked', $candidate_id, array('job_id' => $job_id));
            } else {
                $result['error'] = $this->parse_api_error($response);
            }
            
        } catch (Exception $e) {
            $result['error'] = 'Job linking failed: ' . $e->getMessage();
            $this->log_error('Job linking failed', $e->getMessage(), array('candidate_id' => $candidate_id, 'job_id' => $job_id));
        }
        
        return $result;
    }
    
    /**
     * Validate submission data
     */
    private function validate_submission_data($data) {
        $errors = array();
        
        // Check required fields
        $required_fields = array('firstName', 'lastName', 'email');
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                $errors[] = sprintf('Required field %s is missing', $field);
            }
        }
        
        // Validate email
        if (!empty($data['email']) && !is_email($data['email'])) {
            $errors[] = 'Invalid email address';
        }
        
        return array(
            'valid' => empty($errors),
            'errors' => $errors
        );
    }
    
    /**
     * Prepare candidate data for API
     */
    private function prepare_candidate_data($data) {
        $candidate_data = array();
        
        // Map basic fields
        $field_mapping = array(
            'firstName' => 'firstName',
            'lastName' => 'lastName',
            'email' => 'email',
            'phone' => 'phone',
            'currentCompany' => 'currentCompany',
            'currentTitle' => 'currentTitle',
            'coverLetter' => 'coverLetter',
            'salaryExpectation' => 'salaryExpectation',
            'availability' => 'availability',
            'notes' => 'notes'
        );
        
        foreach ($field_mapping as $source => $target) {
            if (!empty($data[$source])) {
                $candidate_data[$target] = $data[$source];
            }
        }
        
        // Handle social profiles
        if (!empty($data['socialProfile'])) {
            $candidate_data['socialProfiles'] = array($data['socialProfile']);
        }
        
        // Handle tags
        if (!empty($data['tags'])) {
            $candidate_data['tags'] = is_array($data['tags']) ? $data['tags'] : array($data['tags']);
        }
        
        // Add source information
        $candidate_data['source'] = isset($data['source']) ? $data['source'] : 'WordPress Job Board';
        
        return $candidate_data;
    }
    
    /**
     * Get file path from file data
     */
    private function get_file_path($file_data) {
        if (is_string($file_data)) {
            return $file_data;
        }
        
        if (is_array($file_data)) {
            if (isset($file_data['tmp_name'])) {
                return $file_data['tmp_name'];
            }
            if (isset($file_data['file'])) {
                return $file_data['file'];
            }
        }
        
        return null;
    }
    
    /**
     * Upload file using multipart form data
     */
    private function upload_file_multipart($candidate_id, $file_path) {
        $url = $this->client->get_api_endpoint() . '/candidates/' . $candidate_id . '/attachments';
        
        // Use cURL for multipart upload
        if (function_exists('curl_init')) {
            return $this->upload_file_curl($url, $file_path);
        }
        
        // Fallback to WordPress HTTP API
        return $this->upload_file_wp($url, $file_path);
    }
    
    /**
     * Upload file using cURL
     */
    private function upload_file_curl($url, $file_path) {
        $ch = curl_init();
        
        $file_name = basename($file_path);
        $file_size = filesize($file_path);
        $file_type = mime_content_type($file_path);
        
        $boundary = uniqid();
        $delimiter = "\r\n";
        
        $post_data = '';
        $post_data .= '--' . $boundary . $delimiter;
        $post_data .= 'Content-Disposition: form-data; name="file"; filename="' . $file_name . '"' . $delimiter;
        $post_data .= 'Content-Type: ' . $file_type . $delimiter . $delimiter;
        $post_data .= file_get_contents($file_path) . $delimiter;
        $post_data .= '--' . $boundary . '--' . $delimiter;
        
        $headers = array(
            'Content-Type: multipart/form-data; boundary=' . $boundary,
            'Content-Length: ' . strlen($post_data),
            'Authorization: Bearer ' . $this->client->get_api_key(),
            'User-Agent: Crelate-WP/1.0.3'
        );
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        
        $response_body = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return array(
            'status' => $http_code,
            'body' => $response_body,
            'headers' => array()
        );
    }
    
    /**
     * Upload file using WordPress HTTP API
     */
    private function upload_file_wp($url, $file_path) {
        $file_name = basename($file_path);
        $file_type = mime_content_type($file_path);
        
        $args = array(
            'method' => 'POST',
            'timeout' => 60,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->client->get_api_key(),
                'User-Agent' => 'Crelate-WP/1.0.3'
            ),
            'body' => array(
                'file' => array(
                    'name' => $file_name,
                    'type' => $file_type,
                    'tmp_name' => $file_path,
                    'error' => 0,
                    'size' => filesize($file_path)
                )
            )
        );
        
        $response = wp_remote_post($url, $args);
        
        if (is_wp_error($response)) {
            throw new Exception('WordPress HTTP error: ' . $response->get_error_message());
        }
        
        return array(
            'status' => wp_remote_retrieve_response_code($response),
            'body' => wp_remote_retrieve_body($response),
            'headers' => wp_remote_retrieve_headers($response)
        );
    }
    
    /**
     * Parse API error response
     */
    private function parse_api_error($response) {
        $error_message = 'API request failed with status ' . $response['status'];
        
        if (!empty($response['body'])) {
            $error_data = json_decode($response['body'], true);
            if ($error_data && isset($error_data['message'])) {
                $error_message = $error_data['message'];
            } elseif ($error_data && isset($error_data['error'])) {
                $error_message = $error_data['error'];
            }
        }
        
        return $error_message;
    }
    
    /**
     * Log success
     */
    private function log_success($action, $crelate_id, $data = array()) {
        $log_data = array(
            'action' => $action,
            'crelate_id' => $crelate_id,
            'timestamp' => current_time('mysql'),
            'data' => $this->mask_pii($data)
        );
        
        $this->log(json_encode($log_data), 'success');
    }
    
    /**
     * Log error
     */
    private function log_error($action, $error, $data = array()) {
        $log_data = array(
            'action' => $action,
            'error' => $error,
            'timestamp' => current_time('mysql'),
            'data' => $this->mask_pii($data)
        );
        
        $this->log(json_encode($log_data), 'error');
    }
    
    /**
     * Log message
     */
    private function log($message, $level = 'info') {
        $log_dir = WP_CONTENT_DIR . '/uploads/crelate-logs';
        
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }
        
        $log_file = $log_dir . '/crelate.log';
        $timestamp = current_time('mysql');
        $log_entry = sprintf("[%s] [%s] %s\n", $timestamp, strtoupper($level), $message);
        
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Mask PII in log data
     */
    private function mask_pii($data) {
        $masked = $data;
        
        // Mask email addresses
        if (isset($masked['email'])) {
            $masked['email'] = $this->mask_email($masked['email']);
        }
        
        // Mask phone numbers
        if (isset($masked['phone'])) {
            $masked['phone'] = $this->mask_phone($masked['phone']);
        }
        
        // Mask names
        if (isset($masked['firstName'])) {
            $masked['firstName'] = substr($masked['firstName'], 0, 1) . '***';
        }
        if (isset($masked['lastName'])) {
            $masked['lastName'] = substr($masked['lastName'], 0, 1) . '***';
        }
        
        return $masked;
    }
    
    /**
     * Mask email address
     */
    private function mask_email($email) {
        $parts = explode('@', $email);
        if (count($parts) === 2) {
            $local = $parts[0];
            $domain = $parts[1];
            
            if (strlen($local) > 2) {
                $local = substr($local, 0, 2) . '***';
            }
            
            return $local . '@' . $domain;
        }
        
        return '***@***';
    }
    
    /**
     * Mask phone number
     */
    private function mask_phone($phone) {
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($cleaned) >= 4) {
            return '***-' . substr($cleaned, -4);
        }
        
        return '***';
    }
}

<?php
/**
 * Crelate Job Board Resume Handler Class
 * Handles secure resume file downloads
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crelate_Resume_Handler {
    
    /**
     * Applicants instance
     */
    private $applicants;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->applicants = new Crelate_Applicants();
        
        // Hook into WordPress
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'handle_download_request'));
        add_action('init', array($this, 'add_rewrite_rules'));
        add_filter('query_vars', array($this, 'add_query_vars'));
    }
    
    /**
     * Add rewrite rules for download URLs
     */
    public function add_rewrite_rules() {
        add_rewrite_rule(
            '^crelate-download/([^/]+)/([^/]+)/([^/]+)/([^/]+)/?$',
            'index.php?crelate_download=$matches[1]&applicant_id=$matches[2]&expiry=$matches[3]&signature=$matches[4]',
            'top'
        );
    }
    
    /**
     * Add query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'crelate_download';
        $vars[] = 'applicant_id';
        $vars[] = 'expiry';
        $vars[] = 'signature';
        return $vars;
    }
    
    /**
     * Handle download request
     */
    public function handle_download_request() {
        $download_type = get_query_var('crelate_download');
        
        if ($download_type === 'resume') {
            $this->handle_resume_download();
        }
    }
    
    /**
     * Handle resume download
     */
    private function handle_resume_download() {
        $applicant_id = intval(get_query_var('applicant_id'));
        $expiry = intval(get_query_var('expiry'));
        $signature = sanitize_text_field(get_query_var('signature'));
        
        // Check if this is a direct request (not through rewrite)
        if (empty($applicant_id) && isset($_GET['applicant_id'])) {
            $applicant_id = intval($_GET['applicant_id']);
            $expiry = intval($_GET['expiry']);
            $signature = sanitize_text_field($_GET['signature']);
        }
        
        if (empty($applicant_id) || empty($expiry) || empty($signature)) {
            $this->send_error_response('Invalid download request');
            return;
        }
        
        // Verify signature
        if (!$this->verify_signature($applicant_id, $expiry, $signature)) {
            $this->send_error_response('Invalid signature');
            return;
        }
        
        // Check expiry
        if (time() > $expiry) {
            $this->send_error_response('Download link has expired');
            return;
        }
        
        // Get applicant data
        $applicant = $this->applicants->get_applicant($applicant_id);
        if (!$applicant) {
            $this->send_error_response('Applicant not found');
            return;
        }
        
        // Check if file exists
        if (empty($applicant->resume_file_path) || !file_exists($applicant->resume_file_path)) {
            $this->send_error_response('Resume file not found');
            return;
        }
        
        // Log access
        $this->applicants->log_file_access($applicant_id, $_SERVER['REMOTE_ADDR']);
        
        // Serve file
        $this->serve_file($applicant->resume_file_path, $applicant->resume_file_name, $applicant->resume_file_type);
    }
    
    /**
     * Verify signature
     */
    private function verify_signature($applicant_id, $expiry, $signature) {
        $secret_key = wp_salt('auth');
        $data = $applicant_id . '|' . $expiry;
        $expected_signature = hash_hmac('sha256', $data, $secret_key);
        
        return hash_equals($expected_signature, $signature);
    }
    
    /**
     * Serve file for download
     */
    private function serve_file($file_path, $file_name, $file_type) {
        // Clear any output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Set headers
        header('Content-Type: ' . $file_type);
        header('Content-Disposition: attachment; filename="' . $file_name . '"');
        header('Content-Length: ' . filesize($file_path));
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');
        
        // Read and output file
        readfile($file_path);
        exit;
    }
    
    /**
     * Send error response
     */
    private function send_error_response($message) {
        wp_die($message, 'Download Error', array('response' => 403));
    }
    
    /**
     * Generate secure download URL
     */
    public function generate_download_url($applicant_id, $expiry_hours = 24) {
        $secret_key = wp_salt('auth');
        $expiry = time() + ($expiry_hours * 60 * 60);
        $data = $applicant_id . '|' . $expiry;
        $signature = hash_hmac('sha256', $data, $secret_key);
        
        return add_query_arg(array(
            'crelate_download' => 'resume',
            'applicant_id' => $applicant_id,
            'expiry' => $expiry,
            'signature' => $signature
        ), home_url());
    }
    
    /**
     * Create .htaccess file to protect uploads directory
     */
    public function create_htaccess_protection() {
        $upload_dir = wp_upload_dir();
        $resume_dir = $upload_dir['basedir'] . '/nlmc-jobboard/resumes';
        
        if (!is_dir($resume_dir)) {
            wp_mkdir_p($resume_dir);
        }
        
        $htaccess_content = "# Protect resume files from direct access\n";
        $htaccess_content .= "Order Deny,Allow\n";
        $htaccess_content .= "Deny from all\n";
        $htaccess_content .= "\n";
        $htaccess_content .= "# Allow access only through WordPress\n";
        $htaccess_content .= "<FilesMatch \"\\.(pdf|doc|docx)$\">\n";
        $htaccess_content .= "    Order Deny,Allow\n";
        $htaccess_content .= "    Deny from all\n";
        $htaccess_content .= "</FilesMatch>\n";
        
        $htaccess_file = $resume_dir . '/.htaccess';
        file_put_contents($htaccess_file, $htaccess_content);
        
        // Also create web.config for IIS
        $webconfig_content = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $webconfig_content .= '<configuration>' . "\n";
        $webconfig_content .= '    <system.webServer>' . "\n";
        $webconfig_content .= '        <security>' . "\n";
        $webconfig_content .= '            <requestFiltering>' . "\n";
        $webconfig_content .= '                <fileExtensions>' . "\n";
        $webconfig_content .= '                    <add fileExtension=".pdf" allowed="false" />' . "\n";
        $webconfig_content .= '                    <add fileExtension=".doc" allowed="false" />' . "\n";
        $webconfig_content .= '                    <add fileExtension=".docx" allowed="false" />' . "\n";
        $webconfig_content .= '                </fileExtensions>' . "\n";
        $webconfig_content .= '            </requestFiltering>' . "\n";
        $webconfig_content .= '        </security>' . "\n";
        $webconfig_content .= '    </system.webServer>' . "\n";
        $webconfig_content .= '</configuration>' . "\n";
        
        $webconfig_file = $resume_dir . '/web.config';
        file_put_contents($webconfig_file, $webconfig_content);
    }
}



<?php
/**
 * License Manager Class
 * 
 * Handles Envato/CodeCanyon license verification
 * 
 * @package WP_News_Audio_Pro
 * @since 1.0.0
 */

defined('ABSPATH') or die('Direct access not allowed');

/**
 * WNAP_License_Manager class
 * 
 * Manage plugin licensing and verification
 * 
 * @since 1.0.0
 */
class WNAP_License_Manager {
    
    /**
     * License option name
     * 
     * @var string
     */
    private $license_option = 'wnap_license';
    
    /**
     * Envato API token
     * 
     * @var string
     */
    private $api_token = '';
    
    /**
     * Envato item ID
     * 
     * @var string|null
     */
    private $item_id = null;
    
    /**
     * Test license code
     * 
     * @var string
     */
    private $test_code = 'WNAP-DEV-TEST-2025';
    
    /**
     * Constructor
     * 
     * @since 1.0.0
     */
    public function __construct() {
        // Safe initialization - no early WordPress function calls
        add_action('admin_init', array($this, 'init_license_hooks'));
        add_action('wp_ajax_wnap_activate_license', array($this, 'ajax_activate_license'));
        add_action('wp_ajax_wnap_deactivate_license', array($this, 'ajax_deactivate_license'));
    }
    
    /**
     * Initialize hooks after WordPress fully loads
     * 
     * @since 1.0.0
     */
    public function init_license_hooks() {
        try {
            $this->check_license_status();
            
            // Schedule license check
            if (!wp_next_scheduled('wnap_license_check_cron')) {
                wp_schedule_event(time(), 'weekly', 'wnap_license_check_cron');
            }
            
            add_action('wnap_license_check_cron', array($this, 'remote_license_validation'));
        } catch (Exception $e) {
            error_log('WNAP: License hooks initialization error: ' . $e->getMessage());
        }
    }
    
    /**
     * Verify purchase code with Envato API
     * CRITICAL: Graceful error handling
     * 
     * @param string $code Purchase code
     * @return array Result array with success status and message
     * @since 1.0.0
     */
    private function verify_with_envato($code) {
        try {
            // Check test mode first
            if ($this->is_test_mode() && $code === $this->test_code) {
                $this->activate_test_license();
                return array(
                    'success' => true,
                    'message' => __('Test license activated', 'wp-news-audio-pro'),
                    'type' => 'test'
                );
            }
            
            // Validate code format
            if (!$this->is_valid_code_format($code)) {
                return array(
                    'success' => false,
                    'message' => __('Invalid purchase code format. Please check and try again.', 'wp-news-audio-pro'),
                    'action' => 'buy',
                    'buy_url' => $this->get_buy_url()
                );
            }
            
            // Use configured API token or fallback
            $api_token = !empty(WNAP_ENVATO_API_TOKEN) ? WNAP_ENVATO_API_TOKEN : $this->api_token;
            
            // Call Envato API
            $url = 'https://api.envato.com/v3/market/author/sale?code=' . urlencode($code);
            
            $response = wp_remote_get($url, array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_token,
                    'User-Agent' => 'WordPress/WNAP-License-Verification'
                ),
                'timeout' => 20,
                'sslverify' => true
            ));
            
            // Check for request errors
            if (is_wp_error($response)) {
                error_log('WNAP: Envato API error: ' . $response->get_error_message());
                
                return array(
                    'success' => false,
                    'message' => __('Connection error. Please check your internet connection and try again.', 'wp-news-audio-pro')
                );
            }
            
            $response_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body);
            
            // Handle response codes
            if ($response_code === 404) {
                // Purchase code not found
                return array(
                    'success' => false,
                    'message' => __('Purchase code not found. Please verify your code.', 'wp-news-audio-pro'),
                    'action' => 'buy',
                    'buy_url' => $this->get_buy_url()
                );
            }
            
            if ($response_code === 403 || $response_code === 401) {
                // API authentication error (our side)
                error_log('WNAP: API authentication failed');
                
                return array(
                    'success' => false,
                    'message' => __('Verification service error. Please contact support.', 'wp-news-audio-pro')
                );
            }
            
            if ($response_code !== 200) {
                // Other error
                error_log('WNAP: Unexpected API response: ' . $response_code);
                
                return array(
                    'success' => false,
                    'message' => __('Verification failed. Please try again later.', 'wp-news-audio-pro')
                );
            }
            
            // Validate item ID (if set)
            $item_id = !empty(WNAP_ENVATO_ITEM_ID) ? WNAP_ENVATO_ITEM_ID : $this->item_id;
            if ($item_id && isset($data->item->id)) {
                if ((string)$data->item->id !== (string)$item_id) {
                    return array(
                        'success' => false,
                        'message' => __('This purchase code is for a different product.', 'wp-news-audio-pro'),
                        'action' => 'buy',
                        'buy_url' => $this->get_buy_url()
                    );
                }
            }
            
            // Success - prepare license data
            $license_data = array(
                'code' => $code,
                'domain' => $this->get_current_domain(),
                'activated_at' => current_time('timestamp'),
                'buyer' => isset($data->buyer) ? sanitize_text_field($data->buyer) : '',
                'purchase_date' => isset($data->sold_at) ? sanitize_text_field($data->sold_at) : '',
                'supported_until' => isset($data->supported_until) ? sanitize_text_field($data->supported_until) : '',
                'item_id' => isset($data->item->id) ? sanitize_text_field($data->item->id) : ''
            );
            
            // Save license (safely)
            $this->save_license($license_data);
            
            return array(
                'success' => true,
                'message' => __('License activated successfully!', 'wp-news-audio-pro'),
                'data' => $license_data
            );
            
        } catch (Exception $e) {
            error_log('WNAP: Exception in Envato verification: ' . $e->getMessage());
            
            return array(
                'success' => false,
                'message' => __('An unexpected error occurred. Please try again.', 'wp-news-audio-pro')
            );
        }
    }
    
    /**
     * Save license data safely
     * 
     * @param array $license_data License data to save
     * @since 1.0.0
     */
    private function save_license($license_data) {
        try {
            // Add fingerprint and signature
            $fingerprint = $this->generate_domain_fingerprint();
            $signature = $this->generate_license_signature(
                $license_data['code'],
                $license_data['domain'],
                $fingerprint
            );
            
            $license_data['fingerprint'] = $fingerprint;
            $license_data['signature'] = $signature;
            $license_data['status'] = 'active';
            $license_data['last_checked'] = time();
            
            // Encrypt license data
            $encrypted = $this->encrypt_license_data($license_data);
            
            // Store license data
            update_option($this->license_option, $encrypted, false);
            update_option('wnap_license_status', 'active', false);
            
            // Schedule regular checks
            if (!wp_next_scheduled('wnap_license_check_cron')) {
                wp_schedule_event(time(), 'weekly', 'wnap_license_check_cron');
            }
            
            // Create file integrity checksums
            if (class_exists('WNAP_Security_Scanner')) {
                $scanner = new WNAP_Security_Scanner();
                $scanner->create_file_checksums();
            }
            
        } catch (Exception $e) {
            error_log('WNAP: Error saving license: ' . $e->getMessage());
        }
    }
    
    /**
     * Deactivate license
     * 
     * @return bool True on success, false on failure
     * @since 1.0.0
     */
    /**
     * Deactivate license
     * 
     * @return bool True on success, false on failure
     * @since 1.0.0
     */
    public function deactivate_license() {
        try {
            delete_option($this->license_option);
            delete_option('wnap_license_status');
            wp_clear_scheduled_hook('wnap_license_check_cron');
            return true;
        } catch (Exception $e) {
            error_log('WNAP: Deactivation error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check license status
     * 
     * @return bool True if license is valid, false otherwise
     * @since 1.0.0
     */
    public function check_license_status() {
        try {
            if (!$this->is_license_valid()) {
                add_action('admin_notices', array($this, 'license_notice'));
            }
        } catch (Exception $e) {
            error_log('WNAP: Error in license status check: ' . $e->getMessage());
        }
    }
    
    /**
     * Show license notice
     * 
     * @since 1.0.0
     */
    public function license_notice() {
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'news-audio-pro') === false) {
            return;
        }
        ?>
        <div class="notice notice-warning is-dismissible">
            <p>
                <strong><?php esc_html_e('WP News Audio Pro:', 'wp-news-audio-pro'); ?></strong>
                <?php esc_html_e('Please activate your license to unlock all features.', 'wp-news-audio-pro'); ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=news-audio-pro&tab=license')); ?>">
                    <?php esc_html_e('Activate Now', 'wp-news-audio-pro'); ?>
                </a>
            </p>
        </div>
        <?php
    }
    
    /**
     * Check if test mode is enabled
     * 
     * @return bool
     * @since 1.0.0
     */
    private function is_test_mode() {
        return defined('WNAP_TEST_MODE') && WNAP_TEST_MODE === true;
    }
    
    /**
     * Activate test license
     * 
     * @since 1.0.0
     */
    private function activate_test_license() {
        $license_data = array(
            'code' => $this->test_code,
            'domain' => $this->get_current_domain(),
            'activated_at' => current_time('timestamp'),
            'buyer' => 'Test User',
            'type' => 'test'
        );
        
        $this->save_license($license_data);
    }
    
    /**
     * Validate purchase code format
     * 
     * @param string $code Purchase code
     * @return bool
     * @since 1.0.0
     */
    private function is_valid_code_format($code) {
        // Envato codes are typically 36 characters (UUID format)
        // or various other formats
        if (strlen($code) < 10) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get current domain
     * 
     * @return string
     * @since 1.0.0
     */
    private function get_current_domain() {
        return isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : 'unknown';
    }
    
    /**
     * Get buy URL for license
     * 
     * @return string
     * @since 1.0.0
     */
    private function get_buy_url() {
        $item_id = !empty(WNAP_ENVATO_ITEM_ID) ? WNAP_ENVATO_ITEM_ID : $this->item_id;
        
        if ($item_id) {
            return 'https://codecanyon.net/item/wp-news-audio-pro/' . $item_id;
        }
        
        // Fallback to generic CodeCanyon search
        return 'https://codecanyon.net/search?term=wp+news+audio+pro';
    }
    
    /**
     * Check if license is valid
     * 
     * @return bool True if license is valid, false otherwise
     * @since 1.0.0
     */
    public function is_license_valid() {
        try {
            $status = get_option('wnap_license_status', 'inactive');
            
            // Test mode always valid
            if ($this->is_test_mode()) {
                return true;
            }
            
            if ($status !== 'active') {
                return false;
            }
            
            $license = $this->get_license_data();
            
            if (!$license) {
                return false;
            }
            
            // Check status
            if (!isset($license['status']) || $license['status'] !== 'active') {
                return false;
            }
            
            // Check domain
            $current_domain = $this->get_current_domain();
            
            if (!empty($license['domain']) && $license['domain'] !== $current_domain) {
                return false;
            }
            
            // Verify domain fingerprint
            if (isset($license['fingerprint'])) {
                $current_fingerprint = $this->generate_domain_fingerprint();
                if ($license['fingerprint'] !== $current_fingerprint) {
                    // Domain fingerprint mismatch - license copied to different domain
                    $this->deactivate_license();
                    return false;
                }
            }
            
            // Verify HMAC signature
            if (isset($license['signature']) && isset($license['code']) && isset($license['domain']) && isset($license['fingerprint'])) {
                $expected_signature = $this->generate_license_signature(
                    $license['code'],
                    $license['domain'],
                    $license['fingerprint']
                );
                
                if (!hash_equals($license['signature'], $expected_signature)) {
                    // Signature mismatch - license data has been tampered with
                    $this->deactivate_license();
                    return false;
                }
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log('WNAP: Error checking license: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get license data
     * 
     * @return array|false License data or false if not found
     * @since 1.0.0
     */
    public function get_license_data() {
        $encrypted = get_option($this->license_option, false);
        
        if (!$encrypted) {
            return false;
        }
        
        return $this->decrypt_license_data($encrypted);
    }
    
    /**
     * Encrypt license data
     * 
     * @param array $data License data
     * @return string Encrypted data
     * @since 1.0.0
     */
    private function encrypt_license_data($data) {
        try {
            $json = wp_json_encode($data);
            
            // Use AUTH_KEY for encryption key - require it to be defined
            if (!defined('AUTH_KEY') || empty(AUTH_KEY)) {
                error_log('WNAP: AUTH_KEY not defined - cannot encrypt license data securely');
                // Still encode but mark as unencrypted
                return base64_encode($json);
            }
            
            $key = AUTH_KEY;
            
            // Simple XOR encryption with the salt
            $encrypted = '';
            $key_length = strlen($key);
            $json_length = strlen($json);
            
            for ($i = 0; $i < $json_length; $i++) {
                $encrypted .= chr(ord($json[$i]) ^ ord($key[$i % $key_length]));
            }
            
            return base64_encode($encrypted);
        } catch (Exception $e) {
            error_log('WNAP: Encryption error: ' . $e->getMessage());
            // Return base64 encoded to maintain compatibility
            return base64_encode(wp_json_encode($data));
        }
    }
    
    /**
     * Decrypt license data
     * 
     * @param string $encrypted Encrypted data
     * @return array|false Decrypted data or false on failure
     * @since 1.0.0
     */
    private function decrypt_license_data($encrypted) {
        try {
            $decoded = base64_decode($encrypted);
            
            if ($decoded === false) {
                return false;
            }
            
            // Try to decode as JSON first (in case it's not encrypted)
            $json_data = json_decode($decoded, true);
            if (is_array($json_data)) {
                return $json_data;
            }
            
            // Use AUTH_KEY for decryption key
            if (!defined('AUTH_KEY') || empty(AUTH_KEY)) {
                error_log('WNAP: AUTH_KEY not defined - cannot decrypt license data');
                return false;
            }
            
            $key = AUTH_KEY;
            
            // Simple XOR decryption with the salt
            $decrypted = '';
            $key_length = strlen($key);
            $decoded_length = strlen($decoded);
            
            for ($i = 0; $i < $decoded_length; $i++) {
                $decrypted .= chr(ord($decoded[$i]) ^ ord($key[$i % $key_length]));
            }
            
            $data = json_decode($decrypted, true);
            
            return is_array($data) ? $data : false;
        } catch (Exception $e) {
            error_log('WNAP: Decryption error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * AJAX handler for license activation
     * CRITICAL: Must not cause fatal errors
     * 
     * @since 1.0.0
     */
    public function ajax_activate_license() {
        try {
            // Verify nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'wnap_admin_nonce')) {
                wp_send_json_error(array(
                    'message' => __('Security check failed', 'wp-news-audio-pro')
                ));
                return;
            }
            
            // Check permissions
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array(
                    'message' => __('Unauthorized access', 'wp-news-audio-pro')
                ));
                return;
            }
            
            // Get license code
            $code = isset($_POST['purchase_code']) ? sanitize_text_field(wp_unslash($_POST['purchase_code'])) : '';
            
            if (empty($code)) {
                wp_send_json_error(array(
                    'message' => __('Please enter a purchase code', 'wp-news-audio-pro')
                ));
                return;
            }
            
            // Validate with Envato API
            $result = $this->verify_with_envato($code);
            
            if ($result['success']) {
                wp_send_json_success($result);
            } else {
                wp_send_json_error($result);
            }
            
        } catch (Exception $e) {
            // Catch ALL errors - never break site
            error_log('WNAP License Error: ' . $e->getMessage());
            
            wp_send_json_error(array(
                'message' => __('An error occurred. Please try again.', 'wp-news-audio-pro')
            ));
        }
    }
    
    /**
     * AJAX handler for license deactivation
     * 
     * @since 1.0.0
     */
    public function ajax_deactivate_license() {
        try {
            // Verify nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'wnap_admin_nonce')) {
                wp_send_json_error(array(
                    'message' => __('Security check failed', 'wp-news-audio-pro')
                ));
                return;
            }
            
            // Check capabilities
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array(
                    'message' => __('Unauthorized', 'wp-news-audio-pro')
                ));
                return;
            }
            
            // Deactivate license
            $deactivated = $this->deactivate_license();
            
            if ($deactivated) {
                wp_send_json_success(array(
                    'message' => __('License deactivated successfully', 'wp-news-audio-pro')
                ));
            } else {
                wp_send_json_error(array(
                    'message' => __('Failed to deactivate license', 'wp-news-audio-pro')
                ));
            }
            
        } catch (Exception $e) {
            error_log('WNAP: Deactivation error: ' . $e->getMessage());
            
            wp_send_json_error(array(
                'message' => __('An error occurred', 'wp-news-audio-pro')
            ));
        }
    }
    
    /**
     * Generate domain fingerprint
     * 
     * Unique signature from domain, server, and installation data
     * 
     * @return string Domain fingerprint hash
     * @since 1.0.0
     */
    private function generate_domain_fingerprint() {
        $domain = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '';
        $server_ip = isset($_SERVER['SERVER_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_ADDR'])) : '';
        $site_url = get_site_url();
        $abspath = ABSPATH;
        $auth_key = defined('AUTH_KEY') ? AUTH_KEY : '';
        
        // Combine all data
        $fingerprint_data = implode('|', array(
            $domain,
            $server_ip,
            $site_url,
            $abspath,
            $auth_key,
        ));
        
        // Generate hash
        return hash('sha256', $fingerprint_data);
    }
    
    /**
     * Generate HMAC signature for license data
     * 
     * @param string $code Purchase code
     * @param string $domain Domain name
     * @param string $fingerprint Domain fingerprint
     * @return string HMAC signature
     * @since 1.0.0
     */
    private function generate_license_signature($code, $domain, $fingerprint) {
        try {
            $data = implode('|', array($code, $domain, $fingerprint));
            
            // Use AUTH_KEY and SECURE_AUTH_KEY for HMAC key - require them
            if (!defined('AUTH_KEY') || empty(AUTH_KEY)) {
                error_log('WNAP: AUTH_KEY not defined - signature security compromised');
                // Use a hash of the data itself as a last resort
                return hash_hmac('sha256', $data, hash('sha256', $data . ABSPATH));
            }
            
            $key_parts = array(AUTH_KEY);
            if (defined('SECURE_AUTH_KEY') && !empty(SECURE_AUTH_KEY)) {
                $key_parts[] = SECURE_AUTH_KEY;
            }
            $key = implode('', $key_parts);
            
            return hash_hmac('sha256', $data, $key);
        } catch (Exception $e) {
            error_log('WNAP: Signature generation error: ' . $e->getMessage());
            // Emergency fallback - better than fatal error
            return hash('sha256', $code . $domain . $fingerprint);
        }
    }
    
    /**
     * Perform remote license validation (daily check)
     * 
     * @return bool True if validation passed, false otherwise
     * @since 1.0.0
     */
    public function remote_license_validation() {
        try {
            $license = $this->get_license_data();
            
            if (!$license || !isset($license['code'])) {
                return false;
            }
            
            // Check if we need to validate (once per day)
            $last_checked = isset($license['last_checked']) ? $license['last_checked'] : 0;
            $time_since_check = time() - $last_checked;
            
            // Check every 24 hours
            if ($time_since_check < DAY_IN_SECONDS) {
                return true;
            }
            
            // Verify purchase code again
            $verification = $this->verify_with_envato($license['code']);
            
            if (!$verification['success']) {
                // Verification failed
                $this->deactivate_license();
                
                // Send email to admin
                $this->send_license_failure_email($verification['message']);
                
                return false;
            }
            
            // Update last checked time
            $license['last_checked'] = time();
            $encrypted = $this->encrypt_license_data($license);
            update_option($this->license_option, $encrypted);
            
            return true;
        } catch (Exception $e) {
            error_log('WNAP: Remote validation error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send license failure notification email
     * 
     * @param string $reason Failure reason
     * @since 1.0.0
     */
    private function send_license_failure_email($reason) {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        
        $subject = sprintf('[%s] WP News Audio Pro - License Deactivated', $site_name);
        
        $message = "Your WP News Audio Pro license has been deactivated.\n\n";
        $message .= "Reason: {$reason}\n\n";
        $message .= "Please contact support to resolve this issue:\n";
        $message .= "Email: info.geniusplugtechnology@gmail.com\n";
        $message .= "WhatsApp: +880 1761 487193\n";
        $message .= "Website: https://geniusplug.com/support/\n";
        
        wp_mail($admin_email, $subject, $message);
    }
}

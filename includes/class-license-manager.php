<?php
/**
 * License Manager Class - Complete Rewrite
 * 
 * Handles Envato/CodeCanyon license verification with:
 * - Test code for localhost only
 * - One domain per license enforcement
 * - No fatal errors ever
 * - Database-backed license storage
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
     * Test code constant
     * 
     * @var string
     */
    const TEST_CODE = 'WNAP-DEV-TEST-2025';
    
    /**
     * Test code expiration days
     * 
     * @var int
     */
    private $test_expiration_days = 90;
    
    /**
     * Constructor
     * 
     * @since 1.0.0
     */
    public function __construct() {
        try {
            add_action('plugins_loaded', array($this, 'init_license_check'));
            add_action('wp_ajax_wnap_activate_license', array($this, 'ajax_activate_license'));
            add_action('wp_ajax_wnap_deactivate_license', array($this, 'ajax_deactivate_license'));
        } catch (Exception $e) {
            error_log('WNAP License Manager Constructor Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Initialize license checking
     * 
     * @since 1.0.0
     */
    public function init_license_check() {
        if (is_admin()) {
            $is_valid = $this->is_license_valid();
            error_log('WNAP: License check result = ' . ($is_valid ? 'VALID' : 'INVALID'));
        }
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
     * Verify license on every admin page load
     * 
     * Note: This runs on every admin page load to ensure license validity.
     * Performance impact is minimal as it only reads cached options.
     * 
     * @since 1.0.0
     */
    public function verify_license_on_load() {
        try {
            // Check license validity on every admin page load
            $is_valid = $this->is_license_valid();
            
            // Log current status for debugging
            $this->debug_log('WNAP: License check on page load - ' . ($is_valid ? 'VALID' : 'INVALID'));
            
            if (!$is_valid) {
                // Show activation notice on plugin pages
                add_action('admin_notices', array($this, 'show_activation_notice'));
            }
        } catch (Exception $e) {
            error_log('WNAP: Error in verify_license_on_load: ' . $e->getMessage());
        }
    }
    
    /**
     * Show activation notice when license is not active
     * 
     * @since 1.0.0
     */
    public function show_activation_notice() {
        try {
            $screen = get_current_screen();
            if (!$screen || strpos($screen->id, 'news-audio-pro') === false) {
                return;
            }
            ?>
            <div class="notice notice-warning">
                <p>
                    <strong><?php esc_html_e('WP News Audio Pro:', 'wp-news-audio-pro'); ?></strong>
                    <?php esc_html_e('Please activate your license to access all features.', 'wp-news-audio-pro'); ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=news-audio-pro&tab=license')); ?>" class="button button-primary">
                        <?php esc_html_e('Activate License', 'wp-news-audio-pro'); ?>
                    </a>
                </p>
            </div>
            <?php
        } catch (Exception $e) {
            error_log('WNAP: Error showing activation notice: ' . $e->getMessage());
        }
    }
    
    /**
     * Check if current domain is localhost
     * 
     * Checks for common localhost patterns including:
     * - Standard localhost addresses (localhost, 127.0.0.1, ::1)
     * - Development TLDs (.local, .test, .dev)
     * - Common development server ports (8080, 8888, 3000)
     * - Private network IP ranges (10.x, 192.168.x, 172.16-31.x)
     * 
     * @return bool True if localhost, false otherwise
     * @since 1.0.0
     */
    private function is_localhost() {
        try {
            $host = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '';
            
            // Check for localhost patterns including common development ports
            $localhost_patterns = array(
                'localhost',
                '127.0.0.1',
                '::1',
                '.local',
                '.test',
                '.dev',
                '.localhost',
                ':8080',    // Common alternative HTTP port
                ':8888',    // MAMP/XAMPP default port
                ':3000'     // Node.js development server default port
            );
            
            foreach ($localhost_patterns as $pattern) {
                if (strpos($host, $pattern) !== false) {
                    return true;
                }
            }
            
            // Also check the domain without port
            $domain = $this->get_current_domain();
            foreach ($localhost_patterns as $pattern) {
                if (strpos($domain, $pattern) !== false || $domain === $pattern) {
                    return true;
                }
            }
            
            // Check if it's a local IP address (private network ranges)
            if (preg_match('/^(10\.|172\.(1[6-9]|2[0-9]|3[01])\.|192\.168\.)/', $domain)) {
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log('WNAP: Error checking localhost: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verify test code
     * 
     * @param string $code Test code to verify
     * @return bool True if valid test code, false otherwise
     * @since 1.0.0
     */
    private function verify_test_code($code) {
        try {
            // Test code only works on localhost
            if (!$this->is_localhost()) {
                return false;
            }
            
            // Check if code matches
            if ($code !== self::TEST_CODE) {
                return false;
            }
            
            return true;
        } catch (Exception $e) {
            error_log('WNAP: Error verifying test code: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if license is registered to another domain
     * 
     * @param string $purchase_code Purchase code to check
     * @return array|false Array with domain info if registered elsewhere, false otherwise
     * @since 1.0.0
     */
    private function check_domain_registration($purchase_code) {
        global $wpdb;
        
        try {
            $table_name = $wpdb->prefix . 'wnap_licenses';
            $current_domain = $this->get_current_domain();
            
            // Hash the purchase code before checking
            $code_hash = hash('sha256', $purchase_code);
            
            // Check if code is already registered
            $existing = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM $table_name WHERE purchase_code_hash = %s",
                    $code_hash
                )
            );
            
            if ($existing) {
                // Check if it's registered to a different domain
                if ($existing->domain !== $current_domain && $existing->status === 'active') {
                    return array(
                        'registered' => true,
                        'domain' => $existing->domain,
                        'status' => $existing->status
                    );
                }
            }
            
            return false;
        } catch (Exception $e) {
            error_log('WNAP: Error checking domain registration: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Register license in database
     * 
     * @param string $purchase_code Purchase code
     * @param string $domain Domain name
     * @return bool True on success, false on failure
     * @since 1.0.0
     */
    private function register_license_in_db($purchase_code, $domain) {
        global $wpdb;
        
        try {
            $table_name = $wpdb->prefix . 'wnap_licenses';
            
            // Hash the purchase code before storing
            $code_hash = hash('sha256', $purchase_code);
            
            // Check if already exists
            $existing = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM $table_name WHERE purchase_code_hash = %s",
                    $code_hash
                )
            );
            
            if ($existing) {
                // Update existing record
                $result = $wpdb->update(
                    $table_name,
                    array(
                        'domain' => $domain,
                        'status' => 'active',
                        'updated_at' => current_time('mysql')
                    ),
                    array('purchase_code_hash' => $code_hash),
                    array('%s', '%s', '%s'),
                    array('%s')
                );
            } else {
                // Insert new record
                $result = $wpdb->insert(
                    $table_name,
                    array(
                        'purchase_code_hash' => $code_hash,
                        'domain' => $domain,
                        'status' => 'active',
                        'created_at' => current_time('mysql'),
                        'updated_at' => current_time('mysql')
                    ),
                    array('%s', '%s', '%s', '%s', '%s')
                );
            }
            
            return $result !== false;
        } catch (Exception $e) {
            error_log('WNAP: Error registering license: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verify purchase code with Envato API
     * CRITICAL: Graceful error handling - no fatal errors
     * 
     * @param string $code Purchase code
     * @return array Result array with success status and message
     * @since 1.0.0
     */
    private function verify_with_envato($code) {
        try {
            // Check test code first
            if ($this->verify_test_code($code)) {
                // Activate test license
                return $this->activate_test_code();
            }
            
            // Test code was entered but domain is not localhost
            if ($code === self::TEST_CODE && !$this->is_localhost()) {
                return array(
                    'success' => false,
                    'message' => __('Test code only works on localhost/development environments. Please use a valid purchase code on live sites.', 'wp-news-audio-pro'),
                    'action' => 'buy',
                    'buy_url' => $this->get_buy_url()
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
            
            // Check if code is already registered to another domain
            $domain_check = $this->check_domain_registration($code);
            if ($domain_check && $domain_check['registered']) {
                return array(
                    'success' => false,
                    'message' => sprintf(
                        __('This license is already activated on %s. Please deactivate it there first or contact support.', 'wp-news-audio-pro'),
                        esc_html($domain_check['domain'])
                    ),
                    'domain' => $domain_check['domain']
                );
            }
            
            // Use configured API token
            $api_token = WNAP_ENVATO_API_TOKEN;
            
            if (empty($api_token)) {
                return array(
                    'success' => false,
                    'message' => __('API token not configured. Please contact support.', 'wp-news-audio-pro')
                );
            }
            
            // Call Envato API - CORRECT ENDPOINT
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
            
            // Handle response codes as per requirements
            if ($response_code === 404) {
                // Purchase code not found
                return array(
                    'success' => false,
                    'message' => __('Purchase code not found. Please verify your code is correct.', 'wp-news-audio-pro'),
                    'action' => 'buy',
                    'buy_url' => $this->get_buy_url()
                );
            }
            
            if ($response_code === 403 || $response_code === 401) {
                // API authentication error (our side)
                error_log('WNAP: API authentication failed - Response code: ' . $response_code);
                
                return array(
                    'success' => false,
                    'message' => __('Verification service error. Please contact support for assistance.', 'wp-news-audio-pro')
                );
            }
            
            if ($response_code !== 200) {
                // Other error
                error_log('WNAP: Unexpected API response: ' . $response_code . ' - Body: ' . $body);
                
                return array(
                    'success' => false,
                    'message' => __('Verification failed. Please try again later or contact support.', 'wp-news-audio-pro')
                );
            }
            
            // Validate item ID (if set)
            $item_id = WNAP_ENVATO_ITEM_ID;
            if (!empty($item_id) && isset($data->item->id)) {
                if ((string)$data->item->id !== (string)$item_id) {
                    return array(
                        'success' => false,
                        'message' => __('This purchase code is for a different product. Please use the correct code.', 'wp-news-audio-pro'),
                        'action' => 'buy',
                        'buy_url' => $this->get_buy_url()
                    );
                }
            }
            
            // Success - activate real license
            return $this->activate_real_code($code, $data);
            
        } catch (Exception $e) {
            error_log('WNAP: Exception in Envato verification: ' . $e->getMessage());
            
            return array(
                'success' => false,
                'message' => __('An unexpected error occurred. Please try again or contact support.', 'wp-news-audio-pro')
            );
        }
    }
    
    /**
     * Activate test license
     * 
     * @param string $code Test code
     * @return array Result with success status and message
     * @since 1.0.0
     */
    private function activate_test_code() {
        try {
            if (!$this->is_localhost()) {
                return array(
                    'success' => false,
                    'message' => 'Test code only works on localhost'
                );
            }
            
            $expires_at = current_time('timestamp') + (90 * DAY_IN_SECONDS);
            
            $license_data = array(
                'type' => 'test',
                'domain' => $this->get_current_domain(),
                'activated_at' => current_time('timestamp'),
                'expires_at' => $expires_at,
                'status' => 'active'
            );
            
            // CRITICAL: Save properly
            update_option('wnap_license_data', $license_data, false);
            update_option('wnap_license_status', 'active', false);
            
            error_log('WNAP: Test license saved - ' . json_encode($license_data));
            
            return array(
                'success' => true,
                'message' => 'License activated successfully for 90 days'
            );
        } catch (Exception $e) {
            error_log('WNAP: Error activating test license: ' . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'An unexpected error occurred'
            );
        }
    }
    
    /**
     * Activate real license
     * 
     * @param string $code Purchase code
     * @param object $validation Envato API response data
     * @return array Result with success status and message
     * @since 1.0.0
     */
    private function activate_real_code($code, $validation) {
        try {
            // Register in domain tracking database
            $current_domain = $this->get_current_domain();
            $registered = $this->register_license_in_db($code, $current_domain);
            
            if (!$registered) {
                return array(
                    'success' => false,
                    'message' => __('Failed to register license. Please try again or contact support.', 'wp-news-audio-pro')
                );
            }
            
            $license_data = array(
                'type' => 'regular',
                'domain' => $current_domain,
                'activated_at' => current_time('timestamp'),
                'buyer' => isset($validation->buyer) ? sanitize_text_field($validation->buyer) : '',
                'item_id' => isset($validation->item->id) ? sanitize_text_field($validation->item->id) : '',
                'status' => 'active'
                // NOTE: NOT storing the purchase code itself!
            );
            
            // Save
            update_option('wnap_license_data', $license_data, false);
            update_option('wnap_license_status', 'active', false);
            
            return array(
                'success' => true,
                'message' => 'License activated successfully'
            );
        } catch (Exception $e) {
            error_log('WNAP: Error activating real license: ' . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'An unexpected error occurred'
            );
        }
    }
    
    /**
     * Deactivate license
     * 
     * @return bool True on success, false on failure
     * @since 1.0.0
     */
    public function deactivate_license() {
        global $wpdb;
        
        try {
            // Get current license data
            $license = $this->get_license_data();
            
            if ($license && isset($license['type']) && $license['type'] !== 'test') {
                // For real licenses, we need to look up by hash in the database
                // We don't have the original code stored, so we'll just mark all for this domain as inactive
                $table_name = $wpdb->prefix . 'wnap_licenses';
                $current_domain = $this->get_current_domain();
                $wpdb->update(
                    $table_name,
                    array('status' => 'inactive', 'updated_at' => current_time('mysql')),
                    array('domain' => $current_domain),
                    array('%s', '%s'),
                    array('%s')
                );
            }
            
            // Remove WordPress options
            delete_option('wnap_license_data');
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
        try {
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
        } catch (Exception $e) {
            error_log('WNAP: Error showing license notice: ' . $e->getMessage());
        }
    }
    
    /**
     * Validate purchase code format
     * 
     * @param string $code Purchase code
     * @return bool
     * @since 1.0.0
     */
    private function is_valid_code_format($code) {
        try {
            // Envato codes are typically 36 characters (UUID format)
            // or various other formats, minimum 10 characters
            if (strlen($code) < 10) {
                return false;
            }
            
            return true;
        } catch (Exception $e) {
            error_log('WNAP: Error validating code format: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get current domain
     * 
     * @return string
     * @since 1.0.0
     */
    private function get_current_domain() {
        try {
            $domain = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '';
            
            // Remove port if present
            $domain = preg_replace('/:\d+$/', '', $domain);
            
            return $domain;
        } catch (Exception $e) {
            error_log('WNAP: Error getting domain: ' . $e->getMessage());
            return 'unknown';
        }
    }
    
    /**
     * Get buy URL for license
     * 
     * @return string
     * @since 1.0.0
     */
    private function get_buy_url() {
        try {
            $item_id = WNAP_ENVATO_ITEM_ID;
            
            if (!empty($item_id)) {
                return 'https://codecanyon.net/item/wp-news-audio-pro/' . $item_id;
            }
            
            // Fallback to generic CodeCanyon search
            return 'https://codecanyon.net/search?term=wp+news+audio+pro';
        } catch (Exception $e) {
            error_log('WNAP: Error getting buy URL: ' . $e->getMessage());
            return 'https://codecanyon.net/';
        }
    }
    
    /**
     * Log debug message (only when WP_DEBUG enabled)
     * 
     * @param string $message Message to log
     * @since 1.0.0
     */
    private function debug_log($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log($message);
        }
    }
    
    /**
     * Check if license is valid
     * 
     * @return bool True if license is valid, false otherwise
     * @since 1.0.0
     */
    public function is_license_valid() {
        try {
            // Get status
            $status = get_option('wnap_license_status', 'inactive');
            
            if ($status !== 'active') {
                error_log('WNAP: License status is ' . $status);
                return false;
            }
            
            // Get license data
            $license = get_option('wnap_license_data');
            
            if (!$license || !is_array($license)) {
                error_log('WNAP: No license data found');
                return false;
            }
            
            // Check type
            $type = isset($license['type']) ? $license['type'] : 'regular';
            
            // For test licenses
            if ($type === 'test') {
                // Verify localhost
                if (!$this->is_localhost()) {
                    error_log('WNAP: Test license but not localhost');
                    return false;
                }
                
                // Check expiration
                if (isset($license['expires_at']) && $license['expires_at'] < time()) {
                    error_log('WNAP: Test license expired');
                    return false;
                }
                
                error_log('WNAP: Test license VALID');
                return true;
            }
            
            // For regular licenses - verify domain
            $current_domain = $this->get_current_domain();
            $licensed_domain = isset($license['domain']) ? $license['domain'] : '';
            
            if ($current_domain !== $licensed_domain) {
                error_log('WNAP: Domain mismatch');
                return false;
            }
            
            error_log('WNAP: Regular license VALID');
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
        try {
            $license_data = get_option('wnap_license_data', false);
            
            if (!$license_data || !is_array($license_data)) {
                return false;
            }
            
            return $license_data;
        } catch (Exception $e) {
            error_log('WNAP: Error getting license data: ' . $e->getMessage());
            return false;
        }
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
                    'message' => __('Security check failed. Please refresh the page and try again.', 'wp-news-audio-pro')
                ));
                return;
            }
            
            // Check permissions
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array(
                    'message' => __('Unauthorized access. You do not have permission to perform this action.', 'wp-news-audio-pro')
                ));
                return;
            }
            
            // Get license code
            $code = isset($_POST['purchase_code']) ? sanitize_text_field(wp_unslash($_POST['purchase_code'])) : '';
            
            if (empty($code)) {
                wp_send_json_error(array(
                    'message' => __('Please enter a purchase code.', 'wp-news-audio-pro')
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
            error_log('WNAP License Activation Error: ' . $e->getMessage());
            
            wp_send_json_error(array(
                'message' => __('An unexpected error occurred. Please try again or contact support.', 'wp-news-audio-pro')
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
                    'message' => __('Security check failed. Please refresh the page and try again.', 'wp-news-audio-pro')
                ));
                return;
            }
            
            // Check capabilities
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array(
                    'message' => __('Unauthorized. You do not have permission to perform this action.', 'wp-news-audio-pro')
                ));
                return;
            }
            
            // Deactivate license
            $deactivated = $this->deactivate_license();
            
            if ($deactivated) {
                wp_send_json_success(array(
                    'message' => __('License deactivated successfully. You can now activate it on a different domain.', 'wp-news-audio-pro')
                ));
            } else {
                wp_send_json_error(array(
                    'message' => __('Failed to deactivate license. Please try again or contact support.', 'wp-news-audio-pro')
                ));
            }
            
        } catch (Exception $e) {
            error_log('WNAP: Deactivation error: ' . $e->getMessage());
            
            wp_send_json_error(array(
                'message' => __('An unexpected error occurred. Please try again or contact support.', 'wp-news-audio-pro')
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
        try {
            $domain = $this->get_current_domain();
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
        } catch (Exception $e) {
            error_log('WNAP: Error generating fingerprint: ' . $e->getMessage());
            return hash('sha256', time());
        }
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
            return hash('sha256', $code . $domain . $fingerprint . time());
        }
    }
    
    /**
     * Perform remote license validation (weekly check)
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
            
            // Skip validation for test licenses
            if (isset($license['type']) && $license['type'] === 'test') {
                return true;
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
        try {
            $admin_email = get_option('admin_email');
            $site_name = get_bloginfo('name');
            
            $subject = sprintf('[%s] WP News Audio Pro - License Deactivated', $site_name);
            
            $message = "Your WP News Audio Pro license has been deactivated.\n\n";
            $message .= "Reason: {$reason}\n\n";
            $message .= "Please contact support to resolve this issue:\n";
            $message .= "Email: " . WNAP_SUPPORT_EMAIL . "\n";
            $message .= "WhatsApp: " . WNAP_SUPPORT_WHATSAPP . "\n";
            $message .= "Website: " . WNAP_SUPPORT_URL . "\n";
            
            wp_mail($admin_email, $subject, $message);
        } catch (Exception $e) {
            error_log('WNAP: Error sending failure email: ' . $e->getMessage());
        }
    }
}

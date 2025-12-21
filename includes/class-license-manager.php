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
     * Constructor
     * 
     * @since 1.0.0
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     * 
     * @since 1.0.0
     */
    private function init_hooks() {
        // AJAX handlers
        add_action('wp_ajax_wnap_activate_license', array($this, 'ajax_activate_license'));
        add_action('wp_ajax_wnap_deactivate_license', array($this, 'ajax_deactivate_license'));
        
        // Admin notices
        add_action('admin_notices', array($this, 'admin_notices'));
        
        // Schedule license check
        if (!wp_next_scheduled('wnap_license_check')) {
            wp_schedule_event(time(), 'weekly', 'wnap_license_check');
        }
        
        add_action('wnap_license_check', array($this, 'check_license_status'));
    }
    
    /**
     * Verify Envato purchase code
     * 
     * @param string $code Purchase code
     * @return bool|WP_Error True on success, WP_Error on failure
     * @since 1.0.0
     */
    public function verify_purchase_code($code) {
        // Validate input
        $code = sanitize_text_field($code);
        
        if (empty($code)) {
            return new WP_Error('empty_code', __('Purchase code cannot be empty', 'wp-news-audio-pro'));
        }
        
        // Check if API token is configured
        if (empty(WNAP_ENVATO_API_TOKEN)) {
            // For development/testing, allow activation without API token
            return true;
        }
        
        // Envato API endpoint
        $url = 'https://api.envato.com/v3/market/author/sale?code=' . urlencode($code);
        
        // Make API request
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . WNAP_ENVATO_API_TOKEN,
                'User-Agent' => 'WordPress/Purchase-Verification'
            ),
            'timeout' => 15,
        ));
        
        // Check for errors
        if (is_wp_error($response)) {
            return new WP_Error('api_error', $response->get_error_message());
        }
        
        // Get response code
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code !== 200) {
            return new WP_Error('invalid_code', __('Invalid purchase code', 'wp-news-audio-pro'));
        }
        
        // Parse response
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);
        
        // Validate response
        if (!isset($data->item)) {
            return new WP_Error('invalid_response', __('Invalid API response', 'wp-news-audio-pro'));
        }
        
        // Validate item ID (if configured)
        if (!empty(WNAP_ENVATO_ITEM_ID) && isset($data->item->id)) {
            if (strval($data->item->id) !== strval(WNAP_ENVATO_ITEM_ID)) {
                return new WP_Error('wrong_item', __('This purchase code is for a different product', 'wp-news-audio-pro'));
            }
        }
        
        return true;
    }
    
    /**
     * Activate license
     * 
     * @param string $code Purchase code
     * @param string $domain Domain name
     * @return bool True on success, false on failure
     * @since 1.0.0
     */
    public function activate_license($code, $domain) {
        $license_data = array(
            'code' => sanitize_text_field($code),
            'domain' => sanitize_text_field($domain),
            'activated_at' => time(),
            'status' => 'active',
        );
        
        // Encrypt license data
        $encrypted = $this->encrypt_license_data($license_data);
        
        return update_option($this->license_option, $encrypted);
    }
    
    /**
     * Deactivate license
     * 
     * @return bool True on success, false on failure
     * @since 1.0.0
     */
    public function deactivate_license() {
        return delete_option($this->license_option);
    }
    
    /**
     * Check license status
     * 
     * @return bool True if license is valid, false otherwise
     * @since 1.0.0
     */
    public function check_license_status() {
        $license = $this->get_license_data();
        
        if (!$license) {
            return false;
        }
        
        // Check if domain matches
        $current_domain = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '';
        
        if (!empty($license['domain']) && $license['domain'] !== $current_domain) {
            // Domain mismatch - deactivate license
            $this->deactivate_license();
            return false;
        }
        
        // Verify purchase code again
        $verification = $this->verify_purchase_code($license['code']);
        
        if (is_wp_error($verification)) {
            // Update status to invalid
            $license['status'] = 'invalid';
            $encrypted = $this->encrypt_license_data($license);
            update_option($this->license_option, $encrypted);
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if license is valid
     * 
     * @return bool True if license is valid, false otherwise
     * @since 1.0.0
     */
    public function is_license_valid() {
        $license = $this->get_license_data();
        
        if (!$license) {
            return false;
        }
        
        // Check status
        if (!isset($license['status']) || $license['status'] !== 'active') {
            return false;
        }
        
        // Check domain
        $current_domain = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '';
        
        if (!empty($license['domain']) && $license['domain'] !== $current_domain) {
            return false;
        }
        
        return true;
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
        $json = wp_json_encode($data);
        
        // Use WordPress salts for encryption key
        $key = wp_salt('auth');
        
        // Simple XOR encryption with the salt
        $encrypted = '';
        $key_length = strlen($key);
        $json_length = strlen($json);
        
        for ($i = 0; $i < $json_length; $i++) {
            $encrypted .= chr(ord($json[$i]) ^ ord($key[$i % $key_length]));
        }
        
        return base64_encode($encrypted);
    }
    
    /**
     * Decrypt license data
     * 
     * @param string $encrypted Encrypted data
     * @return array|false Decrypted data or false on failure
     * @since 1.0.0
     */
    private function decrypt_license_data($encrypted) {
        $decoded = base64_decode($encrypted);
        
        if ($decoded === false) {
            return false;
        }
        
        // Use WordPress salts for decryption key
        $key = wp_salt('auth');
        
        // Simple XOR decryption with the salt
        $decrypted = '';
        $key_length = strlen($key);
        $decoded_length = strlen($decoded);
        
        for ($i = 0; $i < $decoded_length; $i++) {
            $decrypted .= chr(ord($decoded[$i]) ^ ord($key[$i % $key_length]));
        }
        
        $data = json_decode($decrypted, true);
        
        return is_array($data) ? $data : false;
    }
    
    /**
     * AJAX handler for license activation
     * 
     * @since 1.0.0
     */
    public function ajax_activate_license() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'wnap_admin_nonce')) {
            wp_send_json_error(array(
                'message' => __('Security check failed', 'wp-news-audio-pro')
            ));
        }
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Unauthorized access', 'wp-news-audio-pro')
            ));
        }
        
        // Get purchase code
        $code = isset($_POST['purchase_code']) ? sanitize_text_field(wp_unslash($_POST['purchase_code'])) : '';
        
        if (empty($code)) {
            wp_send_json_error(array(
                'message' => __('Please enter a purchase code', 'wp-news-audio-pro')
            ));
        }
        
        // Verify purchase code
        $verification = $this->verify_purchase_code($code);
        
        if (is_wp_error($verification)) {
            wp_send_json_error(array(
                'message' => $verification->get_error_message()
            ));
        }
        
        // Activate license
        $domain = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '';
        $activated = $this->activate_license($code, $domain);
        
        if ($activated) {
            wp_send_json_success(array(
                'message' => __('License activated successfully', 'wp-news-audio-pro')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to activate license', 'wp-news-audio-pro')
            ));
        }
    }
    
    /**
     * AJAX handler for license deactivation
     * 
     * @since 1.0.0
     */
    public function ajax_deactivate_license() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'wnap_admin_nonce')) {
            wp_send_json_error(array(
                'message' => __('Security check failed', 'wp-news-audio-pro')
            ));
        }
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Unauthorized access', 'wp-news-audio-pro')
            ));
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
    }
    
    /**
     * Display admin notices
     * 
     * @since 1.0.0
     */
    public function admin_notices() {
        // Only show on plugin pages
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'news-audio-pro') === false) {
            return;
        }
        
        // Check if license is valid
        if (!$this->is_license_valid()) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <strong><?php esc_html_e('WP News Audio Pro:', 'wp-news-audio-pro'); ?></strong>
                    <?php esc_html_e('Please activate your license to use all features.', 'wp-news-audio-pro'); ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=news-audio-pro&tab=license')); ?>">
                        <?php esc_html_e('Activate Now', 'wp-news-audio-pro'); ?>
                    </a>
                </p>
            </div>
            <?php
        }
    }
}

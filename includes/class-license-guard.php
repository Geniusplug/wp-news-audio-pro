<?php
/**
 * License Guard Class
 * 
 * Enforces license-first architecture - blocks all features without valid license
 * 
 * @package WP_News_Audio_Pro
 * @since 1.0.0
 */

defined('ABSPATH') or die('Direct access not allowed');

/**
 * WNAP_License_Guard class
 * 
 * Complete feature lockdown without license activation
 * 
 * @since 1.0.0
 */
class WNAP_License_Guard {
    
    /**
     * License manager instance
     * 
     * @var WNAP_License_Manager
     */
    private $license_manager;
    
    /**
     * Whether license is valid
     * 
     * @var bool
     */
    private $is_licensed = false;
    
    /**
     * Constructor
     * 
     * @param WNAP_License_Manager $license_manager License manager instance
     * @since 1.0.0
     */
    public function __construct($license_manager) {
        $this->license_manager = $license_manager;
        $this->is_licensed = $this->check_license();
        $this->init_hooks();
    }
    
    /**
     * Check if license is valid
     * 
     * @return bool
     * @since 1.0.0
     */
    private function check_license() {
        // Check for test mode
        if (defined('WNAP_TEST_MODE') && WNAP_TEST_MODE === true) {
            $test_code = defined('WNAP_TEST_LICENSE') ? WNAP_TEST_LICENSE : 'WNAP-DEV-TEST-2025';
            $stored_license = $this->license_manager->get_license_data();
            
            if ($stored_license && $stored_license['code'] === $test_code) {
                return true;
            }
        }
        
        return $this->license_manager->is_license_valid();
    }
    
    /**
     * Initialize hooks for feature lockdown
     * 
     * @since 1.0.0
     */
    private function init_hooks() {
        if ($this->is_licensed) {
            // License is valid, allow normal operation
            return;
        }
        
        // Block admin menus (except license page)
        add_action('admin_menu', array($this, 'restrict_admin_menu'), 999);
        
        // Redirect admin pages to license activation
        add_action('admin_init', array($this, 'redirect_to_license_page'));
        
        // Show admin notice
        add_action('admin_notices', array($this, 'show_license_required_notice'));
        
        // Disable frontend popup
        add_filter('wnap_show_frontend_popup', '__return_false', 999);
        
        // Prevent asset loading
        add_action('wp_enqueue_scripts', array($this, 'block_frontend_assets'), 999);
        add_action('admin_enqueue_scripts', array($this, 'block_admin_assets'), 999);
        
        // Block AJAX requests
        add_action('wp_ajax_wnap_generate_audio', array($this, 'block_ajax_request'), 1);
        add_action('wp_ajax_nopriv_wnap_generate_audio', array($this, 'block_ajax_request'), 1);
        
        // Block REST API endpoints
        add_filter('rest_pre_dispatch', array($this, 'block_rest_api'), 10, 3);
        
        // Remove shortcodes
        add_action('init', array($this, 'remove_shortcodes'), 999);
        
        // Disable frontend functionality
        add_action('wp', array($this, 'disable_frontend_features'), 1);
    }
    
    /**
     * Restrict admin menu to license page only
     * 
     * @since 1.0.0
     */
    public function restrict_admin_menu() {
        global $menu, $submenu;
        
        // Get the main menu page
        $allowed_pages = array(
            'news-audio-pro', // Our settings page
        );
        
        // Don't restrict for admins viewing the license page
        $current_page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
        if (in_array($current_page, $allowed_pages)) {
            return;
        }
        
        // Remove all submenus except license tab
        if (isset($submenu['news-audio-pro'])) {
            unset($submenu['news-audio-pro']);
        }
    }
    
    /**
     * Redirect non-license pages to license activation page
     * 
     * @since 1.0.0
     */
    public function redirect_to_license_page() {
        // Get current page
        $current_page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
        $current_tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : '';
        
        // Allow license page and license tab
        if ($current_page === 'news-audio-pro') {
            // If not on license tab, redirect to license tab
            if ($current_tab !== 'license' && $current_tab !== '') {
                wp_safe_redirect(admin_url('admin.php?page=news-audio-pro&tab=license'));
                exit;
            }
            return;
        }
        
        // Allow AJAX requests for license activation
        if (defined('DOING_AJAX') && DOING_AJAX) {
            $action = isset($_REQUEST['action']) ? sanitize_text_field(wp_unslash($_REQUEST['action'])) : '';
            if (in_array($action, array('wnap_activate_license', 'wnap_deactivate_license'))) {
                return;
            }
        }
        
        // Allow other WordPress admin pages
        if (!$current_page || strpos($current_page, 'news-audio-pro') === false) {
            return;
        }
        
        // Redirect to license page
        wp_safe_redirect(admin_url('admin.php?page=news-audio-pro&tab=license'));
        exit;
    }
    
    /**
     * Show admin notice about license requirement
     * 
     * @since 1.0.0
     */
    public function show_license_required_notice() {
        $current_page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
        
        // Only show on plugin pages
        if (strpos($current_page, 'news-audio-pro') === false && empty($current_page)) {
            // Show on all admin pages to encourage activation
            ?>
            <div class="notice notice-error">
                <p>
                    <strong>🔒 <?php esc_html_e('WP News Audio Pro:', 'wp-news-audio-pro'); ?></strong>
                    <?php esc_html_e('License Required - No features available until activation.', 'wp-news-audio-pro'); ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=news-audio-pro&tab=license')); ?>" class="button button-primary" style="margin-left: 10px;">
                        <?php esc_html_e('Activate License Now', 'wp-news-audio-pro'); ?>
                    </a>
                </p>
            </div>
            <?php
        }
    }
    
    /**
     * Block frontend assets from loading
     * 
     * @since 1.0.0
     */
    public function block_frontend_assets() {
        // Dequeue all plugin frontend assets
        wp_dequeue_style('wnap-frontend-style');
        wp_dequeue_script('wnap-frontend-script');
        wp_dequeue_style('plyr-css');
        wp_dequeue_script('plyr-js');
    }
    
    /**
     * Block admin assets from loading (except on license page)
     * 
     * @since 1.0.0
     */
    public function block_admin_assets($hook) {
        $current_tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : '';
        
        // Allow assets on license tab
        if ($current_tab === 'license') {
            return;
        }
        
        // Block assets on other tabs
        if (strpos($hook, 'news-audio-pro') !== false) {
            wp_dequeue_style('wnap-admin-style');
            wp_dequeue_script('wnap-admin-script');
        }
    }
    
    /**
     * Block AJAX requests
     * 
     * @since 1.0.0
     */
    public function block_ajax_request() {
        wp_send_json_error(array(
            'message' => __('🔒 License required. Please activate your license to use this feature.', 'wp-news-audio-pro'),
            'license_required' => true
        ));
    }
    
    /**
     * Block REST API endpoints
     * 
     * @param mixed $result Response to replace the requested version with
     * @param WP_REST_Server $server Server instance
     * @param WP_REST_Request $request Request used to generate the response
     * @return mixed
     * @since 1.0.0
     */
    public function block_rest_api($result, $server, $request) {
        $route = $request->get_route();
        
        // Block our plugin's REST routes
        if (strpos($route, '/wnap/') === 0 || strpos($route, '/news-audio-pro/') === 0) {
            return new WP_Error(
                'license_required',
                __('🔒 License required. Please activate your license to use this feature.', 'wp-news-audio-pro'),
                array('status' => 403)
            );
        }
        
        return $result;
    }
    
    /**
     * Remove shortcodes
     * 
     * @since 1.0.0
     */
    public function remove_shortcodes() {
        // Remove any shortcodes registered by the plugin
        remove_shortcode('wnap_audio_player');
        remove_shortcode('news_audio_player');
        remove_shortcode('wnap_audio');
    }
    
    /**
     * Disable frontend features
     * 
     * @since 1.0.0
     */
    public function disable_frontend_features() {
        // Remove frontend actions
        remove_action('wp_footer', array('WNAP_Audio_Player', 'render_player_container'));
        remove_action('wp_footer', array('WNAP_Frontend_Popup', 'render_popup'));
        
        // Prevent any output from frontend classes
        add_filter('wnap_render_frontend', '__return_false', 999);
    }
    
    /**
     * Check if current user can bypass license check (for development)
     * 
     * @return bool
     * @since 1.0.0
     */
    private function can_bypass_license() {
        // Allow bypass in development mode
        if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_ENVIRONMENT_TYPE') && WP_ENVIRONMENT_TYPE === 'local') {
            return current_user_can('manage_options');
        }
        
        return false;
    }
    
    /**
     * Get license status
     * 
     * @return bool
     * @since 1.0.0
     */
    public function is_licensed() {
        return $this->is_licensed;
    }
}

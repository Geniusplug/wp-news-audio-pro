<?php
/**
 * Frontend Popup Class
 * 
 * Handles the animated popup modal on single post pages
 * 
 * @package WP_News_Audio_Pro
 * @since 1.0.0
 */

defined('ABSPATH') or die('Direct access not allowed');

/**
 * WNAP_Frontend_Popup class
 * 
 * Display animated popup on frontend
 * 
 * @since 1.0.0
 */
class WNAP_Frontend_Popup {
    
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
        add_action('wp_footer', array($this, 'render_popup'));
        add_action('wp_footer', array($this, 'render_floating_button'));
    }
    
    /**
     * Check if button should be shown
     * 
     * @return bool True if button should be shown
     * @since 1.0.0
     */
    public function should_show_button() {
        $settings = get_option('wnap_settings', array());
        
        // Check if current page type is enabled
        if (is_singular('post') && empty($settings['show_on_posts'])) {
            return false;
        }
        
        if (is_page() && empty($settings['show_on_pages'])) {
            return false;
        }
        
        if (is_front_page() && empty($settings['show_on_home'])) {
            return false;
        }
        
        // Check excluded IDs
        if (!empty($settings['exclude_pages'])) {
            $excluded_ids = array_map('trim', explode(',', $settings['exclude_pages']));
            $current_id = get_the_ID();
            if ($current_id && in_array($current_id, $excluded_ids)) {
                return false;
            }
        }
        
        // Check excluded URL patterns
        if (!empty($settings['exclude_urls'])) {
            $current_url = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
            $patterns = explode("\n", $settings['exclude_urls']);
            
            foreach ($patterns as $pattern) {
                $pattern = trim($pattern);
                if (!empty($pattern) && strpos($current_url, $pattern) !== false) {
                    return false;
                }
            }
        }
        
        return true;
    }
    
    /**
     * Render floating button HTML
     * 
     * @since 1.0.0
     */
    public function render_floating_button() {
        // Check if button should be shown
        if (!$this->should_show_button()) {
            return;
        }
        
        // Get settings
        $settings = get_option('wnap_settings', array());
        
        ?>
        <div class="wnap-floating-button" id="wnapFloatingBtn" style="display: none;">
            <div class="wnap-fab-closed">
                <span class="wnap-audio-icon">🎧</span>
            </div>
            
            <div class="wnap-fab-open" style="display: none;">
                <div class="wnap-fab-header">
                    <span class="wnap-fab-title"><?php esc_html_e('Listen to Article', 'wp-news-audio-pro'); ?></span>
                    <button class="wnap-fab-close" aria-label="<?php esc_attr_e('Close', 'wp-news-audio-pro'); ?>">✕</button>
                </div>
                
                <div class="wnap-fab-controls">
                    <button class="wnap-fab-play" id="wnapFabPlay" aria-label="<?php esc_attr_e('Play', 'wp-news-audio-pro'); ?>">▶</button>
                    <button class="wnap-fab-pause" id="wnapFabPause" style="display:none;" aria-label="<?php esc_attr_e('Pause', 'wp-news-audio-pro'); ?>">⏸</button>
                    <button class="wnap-fab-stop" id="wnapFabStop" aria-label="<?php esc_attr_e('Stop', 'wp-news-audio-pro'); ?>">⏹</button>
                </div>
                
                <div class="wnap-fab-progress">
                    <div class="wnap-progress-bar">
                        <div class="wnap-progress-fill"></div>
                    </div>
                    <span class="wnap-time">0:00 / 0:00</span>
                </div>
                
                <div class="wnap-fab-speed">
                    <button class="wnap-speed-btn" data-speed="0.5">0.5x</button>
                    <button class="wnap-speed-btn active" data-speed="1">1x</button>
                    <button class="wnap-speed-btn" data-speed="1.5">1.5x</button>
                    <button class="wnap-speed-btn" data-speed="2">2x</button>
                </div>
                
                <button class="wnap-fab-hide"><?php esc_html_e('Hide Forever', 'wp-news-audio-pro'); ?></button>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render popup HTML
     * 
     * @since 1.0.0
     */
    public function render_popup() {
        // Only show on single posts
        if (!is_single()) {
            return;
        }
        
        // Get settings
        $settings = get_option('wnap_settings', array());
        
        // Check if popup is enabled
        if (!isset($settings['enable_popup']) || !$settings['enable_popup']) {
            return;
        }
        
        ?>
        <div id="wnap-popup-overlay" class="wnap-popup-overlay" style="display: none;">
            <div id="wnap-popup-modal" class="wnap-popup-modal">
                <button class="wnap-close" aria-label="<?php esc_attr_e('Close', 'wp-news-audio-pro'); ?>">&times;</button>
                
                <div class="wnap-icon">🎧</div>
                
                <h3><?php esc_html_e('Listen to this article', 'wp-news-audio-pro'); ?></h3>
                
                <p><?php esc_html_e("Choose how you'd like to consume this content", 'wp-news-audio-pro'); ?></p>
                
                <div class="wnap-buttons">
                    <button class="wnap-btn wnap-btn-audio" data-post-id="<?php echo esc_attr(get_the_ID()); ?>">
                        🎧 <?php esc_html_e('Listen to Audio', 'wp-news-audio-pro'); ?>
                    </button>
                    <button class="wnap-btn wnap-btn-read">
                        📖 <?php esc_html_e('Read Article', 'wp-news-audio-pro'); ?>
                    </button>
                </div>
                
                <label class="wnap-checkbox">
                    <input type="checkbox" id="wnap-dont-show">
                    <?php esc_html_e("Don't show again", 'wp-news-audio-pro'); ?>
                </label>
            </div>
        </div>
        <?php
    }
    
    /**
     * Check if popup should be shown
     * 
     * @return bool True if popup should be shown
     * @since 1.0.0
     */
    public function should_show_popup() {
        // Check if on single post
        if (!is_single()) {
            return false;
        }
        
        // Get settings
        $settings = get_option('wnap_settings', array());
        
        // Check if popup is enabled
        if (!isset($settings['enable_popup']) || !$settings['enable_popup']) {
            return false;
        }
        
        return true;
    }
}

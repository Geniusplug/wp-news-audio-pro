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

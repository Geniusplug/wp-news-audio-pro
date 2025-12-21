<?php
/**
 * Audio Player Class
 * 
 * Handles the HTML5 audio player with Plyr.js
 * 
 * @package WP_News_Audio_Pro
 * @since 1.0.0
 */

defined('ABSPATH') or die('Direct access not allowed');

/**
 * WNAP_Audio_Player class
 * 
 * Display and manage audio player
 * 
 * @since 1.0.0
 */
class WNAP_Audio_Player {
    
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
        add_action('wp_footer', array($this, 'render_player_container'));
    }
    
    /**
     * Render audio player container
     * 
     * @since 1.0.0
     */
    public function render_player_container() {
        // Only show on single posts
        if (!is_single()) {
            return;
        }
        
        // Get settings
        $settings = get_option('wnap_settings', array());
        $player_position = isset($settings['player_position']) ? $settings['player_position'] : 'popup';
        
        $container_class = 'wnap-player-container';
        if ($player_position === 'fixed-bottom') {
            $container_class .= ' wnap-player-fixed-bottom';
        } elseif ($player_position === 'inline') {
            $container_class .= ' wnap-player-inline';
        } else {
            $container_class .= ' wnap-player-floating';
        }
        
        ?>
        <div id="wnap-player-container" class="<?php echo esc_attr($container_class); ?>" style="display: none;">
            <div class="wnap-player-wrapper">
                <audio id="wnap-audio-player" controls>
                    <source src="" type="audio/mpeg">
                    <?php esc_html_e('Your browser does not support the audio element.', 'wp-news-audio-pro'); ?>
                </audio>
                <button class="wnap-player-close" aria-label="<?php esc_attr_e('Close player', 'wp-news-audio-pro'); ?>">×</button>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get player HTML
     * 
     * @param string $audio_url Audio file URL
     * @return string Player HTML
     * @since 1.0.0
     */
    public function get_player_html($audio_url) {
        if (empty($audio_url)) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="wnap-player-wrapper">
            <audio id="wnap-audio-player" controls>
                <source src="<?php echo esc_url($audio_url); ?>" type="audio/mpeg">
                <?php esc_html_e('Your browser does not support the audio element.', 'wp-news-audio-pro'); ?>
            </audio>
        </div>
        <?php
        return ob_get_clean();
    }
}

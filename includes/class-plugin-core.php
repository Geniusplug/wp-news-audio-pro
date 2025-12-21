<?php
/**
 * Plugin Core Class
 * 
 * Core functionality and utilities for the plugin
 * 
 * @package WP_News_Audio_Pro
 * @since 1.0.0
 */

defined('ABSPATH') or die('Direct access not allowed');

/**
 * WNAP_Plugin_Core class
 * 
 * Handles core plugin functionality
 * 
 * @since 1.0.0
 */
class WNAP_Plugin_Core {
    
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
        // Add custom post meta
        add_action('add_meta_boxes', array($this, 'add_audio_meta_box'));
        
        // AJAX handlers
        add_action('wp_ajax_wnap_generate_audio', array($this, 'ajax_generate_audio'));
        add_action('wp_ajax_nopriv_wnap_generate_audio', array($this, 'ajax_generate_audio'));
        
        // Cleanup cron
        add_action('wnap_cleanup_old_audio', array($this, 'cleanup_old_audio'));
    }
    
    /**
     * Add audio meta box to posts
     * 
     * @since 1.0.0
     */
    public function add_audio_meta_box() {
        add_meta_box(
            'wnap_audio_meta',
            __('News Audio', 'wp-news-audio-pro'),
            array($this, 'render_audio_meta_box'),
            'post',
            'side',
            'default'
        );
    }
    
    /**
     * Render audio meta box
     * 
     * @param WP_Post $post Current post object
     * @since 1.0.0
     */
    public function render_audio_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('wnap_meta_box_nonce', 'wnap_meta_box_nonce');
        
        $audio_url = get_post_meta($post->ID, '_wnap_audio_url', true);
        
        echo '<div class="wnap-meta-box">';
        
        if ($audio_url) {
            echo '<p>' . esc_html__('Audio file generated:', 'wp-news-audio-pro') . '</p>';
            echo '<audio controls style="width:100%;"><source src="' . esc_url($audio_url) . '" type="audio/mpeg"></audio>';
            echo '<p><button type="button" class="button button-secondary wnap-regenerate-audio" data-post-id="' . esc_attr($post->ID) . '">' . esc_html__('Regenerate Audio', 'wp-news-audio-pro') . '</button></p>';
        } else {
            echo '<p>' . esc_html__('No audio file generated yet.', 'wp-news-audio-pro') . '</p>';
            echo '<p><button type="button" class="button button-primary wnap-generate-audio" data-post-id="' . esc_attr($post->ID) . '">' . esc_html__('Generate Audio', 'wp-news-audio-pro') . '</button></p>';
        }
        
        echo '</div>';
    }
    
    /**
     * AJAX handler for audio generation
     * 
     * @since 1.0.0
     */
    public function ajax_generate_audio() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'wnap_frontend_nonce')) {
            wp_send_json_error(array(
                'message' => __('Security check failed', 'wp-news-audio-pro')
            ));
        }
        
        // Get post ID
        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        
        if (!$post_id) {
            wp_send_json_error(array(
                'message' => __('Invalid post ID', 'wp-news-audio-pro')
            ));
        }
        
        // Get post content
        $post = get_post($post_id);
        
        if (!$post) {
            wp_send_json_error(array(
                'message' => __('Post not found', 'wp-news-audio-pro')
            ));
        }
        
        // Check if audio already exists
        $tts_engine = new WNAP_TTS_Engine();
        $audio_url = $tts_engine->get_audio_url($post_id);
        
        if ($audio_url) {
            wp_send_json_success(array(
                'audio_url' => $audio_url,
                'message' => __('Audio retrieved successfully', 'wp-news-audio-pro')
            ));
        }
        
        // Generate audio
        $settings = get_option('wnap_settings', array());
        $content = $post->post_content;
        $language = isset($settings['default_language']) ? $settings['default_language'] : 'en-US';
        
        $result = $tts_engine->generate_audio($post_id, $content, $language, $settings);
        
        if ($result) {
            wp_send_json_success(array(
                'audio_url' => $result,
                'message' => __('Audio generated successfully', 'wp-news-audio-pro')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Error generating audio', 'wp-news-audio-pro')
            ));
        }
    }
    
    /**
     * Cleanup old audio files
     * 
     * @since 1.0.0
     */
    public function cleanup_old_audio() {
        $tts_engine = new WNAP_TTS_Engine();
        $tts_engine->clean_old_audio();
    }
    
    /**
     * Get plugin settings
     * 
     * @param string $key Optional. Specific setting key to retrieve
     * @param mixed $default Optional. Default value if setting not found
     * @return mixed
     * @since 1.0.0
     */
    public static function get_setting($key = '', $default = null) {
        $settings = get_option('wnap_settings', array());
        
        if (empty($key)) {
            return $settings;
        }
        
        return isset($settings[$key]) ? $settings[$key] : $default;
    }
    
    /**
     * Update plugin settings
     * 
     * @param string|array $key Setting key or array of settings
     * @param mixed $value Setting value (if $key is string)
     * @return bool
     * @since 1.0.0
     */
    public static function update_setting($key, $value = null) {
        $settings = get_option('wnap_settings', array());
        
        if (is_array($key)) {
            $settings = array_merge($settings, $key);
        } else {
            $settings[$key] = $value;
        }
        
        return update_option('wnap_settings', $settings);
    }
}

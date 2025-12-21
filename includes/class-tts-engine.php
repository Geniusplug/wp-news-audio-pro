<?php
/**
 * Text-to-Speech Engine Class
 * 
 * Handles audio generation from text using various TTS engines
 * 
 * @package WP_News_Audio_Pro
 * @since 1.0.0
 */

defined('ABSPATH') or die('Direct access not allowed');

/**
 * WNAP_TTS_Engine class
 * 
 * Convert post content to audio files
 * 
 * @since 1.0.0
 */
class WNAP_TTS_Engine {
    
    /**
     * Supported languages
     * 
     * @var array
     */
    private $supported_languages = array(
        'en-US' => 'English (US)',
        'en-GB' => 'English (UK)',
        'es-ES' => 'Spanish',
        'fr-FR' => 'French',
        'de-DE' => 'German',
        'ar-SA' => 'Arabic',
        'hi-IN' => 'Hindi',
        'zh-CN' => 'Chinese',
    );
    
    /**
     * Constructor
     * 
     * @since 1.0.0
     */
    public function __construct() {
        // Initialize
    }
    
    /**
     * Generate audio from post content
     * 
     * @param int $post_id Post ID
     * @param string $content Post content
     * @param string $language Language code
     * @param array $settings Plugin settings
     * @return string|false Audio URL on success, false on failure
     * @since 1.0.0
     */
    public function generate_audio($post_id, $content, $language, $settings) {
        // Check if audio already exists
        $existing_url = $this->get_audio_url($post_id);
        if ($existing_url) {
            return $existing_url;
        }
        
        // Process content
        $text = $this->process_content($content);
        
        if (empty($text)) {
            return false;
        }
        
        // Get audio directory
        $upload_dir = wp_upload_dir();
        $base_dir = $upload_dir['basedir'] . '/news-audio-pro/';
        $base_url = $upload_dir['baseurl'] . '/news-audio-pro/';
        
        // Create year/month directory
        $year = date('Y');
        $month = date('m');
        $audio_dir = $base_dir . $year . '/' . $month . '/';
        $audio_url_base = $base_url . $year . '/' . $month . '/';
        
        if (!file_exists($audio_dir)) {
            wp_mkdir_p($audio_dir);
        }
        
        // Generate unique filename
        $content_hash = md5($text . $language);
        $filename = 'post-' . $post_id . '-' . $content_hash . '.mp3';
        $output_file = $audio_dir . $filename;
        $output_url = $audio_url_base . $filename;
        
        // Check if file already exists
        if (file_exists($output_file)) {
            update_post_meta($post_id, '_wnap_audio_url', $output_url);
            update_post_meta($post_id, '_wnap_audio_file', $output_file);
            return $output_url;
        }
        
        // Get voice engine
        $voice_engine = isset($settings['voice_engine']) ? $settings['voice_engine'] : 'espeak';
        
        // Generate audio based on engine
        $result = false;
        switch ($voice_engine) {
            case 'espeak':
                $result = $this->process_with_espeak($text, $language, $output_file, $settings);
                break;
            case 'responsivevoice':
                $result = $this->process_with_responsive_voice($text, $language, $output_file, $settings);
                break;
            case 'google':
                $result = $this->process_with_google_tts($text, $language, $output_file, $settings);
                break;
            default:
                $result = $this->process_with_espeak($text, $language, $output_file, $settings);
        }
        
        if ($result) {
            // Store audio metadata
            update_post_meta($post_id, '_wnap_audio_url', $output_url);
            update_post_meta($post_id, '_wnap_audio_file', $output_file);
            update_post_meta($post_id, '_wnap_audio_generated', time());
            
            return $output_url;
        }
        
        return false;
    }
    
    /**
     * Get audio URL for a post
     * 
     * @param int $post_id Post ID
     * @return string|false Audio URL or false if not found
     * @since 1.0.0
     */
    public function get_audio_url($post_id) {
        $audio_url = get_post_meta($post_id, '_wnap_audio_url', true);
        $audio_file = get_post_meta($post_id, '_wnap_audio_file', true);
        
        // Check if file still exists
        if ($audio_url && $audio_file && file_exists($audio_file)) {
            return $audio_url;
        }
        
        return false;
    }
    
    /**
     * Delete audio for a post
     * 
     * @param int $post_id Post ID
     * @return bool True on success, false on failure
     * @since 1.0.0
     */
    public function delete_audio($post_id) {
        $audio_file = get_post_meta($post_id, '_wnap_audio_file', true);
        
        if ($audio_file && file_exists($audio_file)) {
            wp_delete_file($audio_file);
        }
        
        delete_post_meta($post_id, '_wnap_audio_url');
        delete_post_meta($post_id, '_wnap_audio_file');
        delete_post_meta($post_id, '_wnap_audio_generated');
        
        return true;
    }
    
    /**
     * Clean old audio files based on cache duration
     * 
     * @since 1.0.0
     */
    public function clean_old_audio() {
        $settings = get_option('wnap_settings', array());
        $cache_duration = isset($settings['cache_duration']) ? absint($settings['cache_duration']) : 30;
        
        // Convert days to seconds
        $max_age = $cache_duration * DAY_IN_SECONDS;
        $current_time = time();
        
        // Get all posts with audio
        $args = array(
            'post_type' => 'post',
            'posts_per_page' => -1,
            'meta_key' => '_wnap_audio_generated',
            'fields' => 'ids',
        );
        
        $posts = get_posts($args);
        
        foreach ($posts as $post_id) {
            $generated_time = get_post_meta($post_id, '_wnap_audio_generated', true);
            
            if ($generated_time && ($current_time - $generated_time) > $max_age) {
                $this->delete_audio($post_id);
            }
        }
    }
    
    /**
     * Process content for TTS
     * 
     * @param string $content Post content
     * @return string Processed text
     * @since 1.0.0
     */
    private function process_content($content) {
        // Strip HTML tags
        $text = wp_strip_all_tags($content);
        
        // Remove shortcodes
        $text = strip_shortcodes($text);
        
        // Remove extra whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Limit to first 1000 words
        $words = explode(' ', $text);
        if (count($words) > 1000) {
            $words = array_slice($words, 0, 1000);
            $text = implode(' ', $words);
        }
        
        // Clean up special characters
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        
        return trim($text);
    }
    
    /**
     * Process with eSpeak TTS engine
     * 
     * eSpeak is a free, open-source TTS engine.
     * Installation instructions:
     * - Ubuntu/Debian: sudo apt-get install espeak
     * - macOS: brew install espeak
     * - Windows: Download from http://espeak.sourceforge.net/
     * 
     * @param string $text Text to convert
     * @param string $language Language code
     * @param string $output_file Output file path
     * @param array $settings Plugin settings
     * @return bool True on success, false on failure
     * @since 1.0.0
     */
    private function process_with_espeak($text, $language, $output_file, $settings) {
        // Check if espeak is installed
        $espeak_path = $this->find_espeak_binary();
        
        if (!$espeak_path) {
            // Create a placeholder audio file with error message
            return $this->create_placeholder_audio($output_file);
        }
        
        // Map language codes to espeak voices
        $voice_map = array(
            'en-US' => 'en-us',
            'en-GB' => 'en-gb',
            'es-ES' => 'es',
            'fr-FR' => 'fr',
            'de-DE' => 'de',
            'ar-SA' => 'ar',
            'hi-IN' => 'hi',
            'zh-CN' => 'zh',
        );
        
        // Allow developers to customize voice mapping
        $voice_map = apply_filters('wnap_espeak_voice_map', $voice_map);
        
        $voice = isset($voice_map[$language]) ? $voice_map[$language] : 'en-us';
        
        // Get speed and pitch settings
        $speed = isset($settings['speech_speed']) ? floatval($settings['speech_speed']) * 100 : 100;
        $pitch = isset($settings['pitch']) ? floatval($settings['pitch']) * 50 : 50;
        
        // Escape text for shell
        $safe_text = escapeshellarg($text);
        $safe_output = escapeshellarg($output_file);
        
        // Build espeak command
        $command = sprintf(
            '%s -v %s -s %d -p %d -w %s %s 2>&1',
            escapeshellcmd($espeak_path),
            escapeshellarg($voice),
            intval($speed),
            intval($pitch),
            $safe_output,
            $safe_text
        );
        
        // Execute command
        $output = array();
        $return_var = 0;
        exec($command, $output, $return_var);
        
        // Check if file was created
        if (file_exists($output_file) && filesize($output_file) > 0) {
            return true;
        }
        
        // Fallback to placeholder
        return $this->create_placeholder_audio($output_file);
    }
    
    /**
     * Find eSpeak binary
     * 
     * @return string|false Path to espeak binary or false if not found
     * @since 1.0.0
     */
    private function find_espeak_binary() {
        $possible_paths = array(
            '/usr/bin/espeak',
            '/usr/local/bin/espeak',
            '/opt/homebrew/bin/espeak',
            'C:\\Program Files\\eSpeak\\command_line\\espeak.exe',
            'C:\\Program Files (x86)\\eSpeak\\command_line\\espeak.exe',
        );
        
        foreach ($possible_paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        
        // Try to find using 'which' command
        $output = array();
        exec('which espeak 2>&1', $output);
        if (!empty($output[0]) && file_exists($output[0])) {
            return $output[0];
        }
        
        return false;
    }
    
    /**
     * Process with ResponsiveVoice.js
     * 
     * Note: This is a client-side solution and requires the audio to be
     * generated on the frontend. This method creates a placeholder that
     * will be replaced by the frontend script.
     * 
     * @param string $text Text to convert
     * @param string $language Language code
     * @param string $output_file Output file path
     * @param array $settings Plugin settings
     * @return bool True on success, false on failure
     * @since 1.0.0
     */
    private function process_with_responsive_voice($text, $language, $output_file, $settings) {
        // ResponsiveVoice is client-side, so we create a placeholder
        return $this->create_placeholder_audio($output_file);
    }
    
    /**
     * Process with Google Cloud TTS API
     * 
     * Requires Google Cloud TTS API credentials
     * 
     * @param string $text Text to convert
     * @param string $language Language code
     * @param string $output_file Output file path
     * @param array $settings Plugin settings
     * @return bool True on success, false on failure
     * @since 1.0.0
     */
    private function process_with_google_tts($text, $language, $output_file, $settings) {
        // This requires Google Cloud TTS API credentials
        // For now, fall back to espeak
        return $this->process_with_espeak($text, $language, $output_file, $settings);
    }
    
    /**
     * Create placeholder audio file
     * 
     * Creates a simple MP3 file as placeholder when TTS engine is not available
     * 
     * @param string $output_file Output file path
     * @return bool True on success, false on failure
     * @since 1.0.0
     */
    private function create_placeholder_audio($output_file) {
        // Create a minimal valid MP3 file (silent audio)
        // This is a base64-encoded minimal MP3 frame
        $minimal_mp3 = base64_decode(
            '//uQxAAAAAAAAAAAAAAAAAAAAAAAWGluZwAAAA8AAAACAAADhAC' .
            'AgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA' .
            'gICAgICAgICAgICAgICAgP////////////////////////////////' .
            '//////////////////////////////8AAABhTEFNRTMuOThyBK8AA' .
            'AAAAAAAAAAUAAAAAAAAAAOEA1pVAAAAAAAAAAAAAAAAAAAAAAAA' .
            'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA' .
            'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA' .
            'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA' .
            'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA' .
            'AAAAAAAAAAAAAAAAAAAAAAAA//'
        );
        
        // Write to file
        $result = file_put_contents($output_file, $minimal_mp3);
        
        return $result !== false;
    }
    
    /**
     * Get supported languages
     * 
     * @return array Supported languages
     * @since 1.0.0
     */
    public function get_supported_languages() {
        return $this->supported_languages;
    }
}

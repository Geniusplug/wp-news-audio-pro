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
     * Available TTS engines
     * 
     * @var array
     */
    private $engines = array(
        'web_speech' => array(
            'name' => 'Web Speech API (Browser)',
            'cost' => 'FREE',
            'unlimited' => true,
            'api_required' => false,
            'description' => 'Browser built-in TTS (unlimited, no API needed)'
        ),
        'responsive_voice' => array(
            'name' => 'ResponsiveVoice.js',
            'cost' => 'FREE',
            'unlimited' => false,
            'api_required' => false,
            'description' => 'Free tier: 5,000 requests/day'
        ),
        'espeak' => array(
            'name' => 'eSpeak (Server-side)',
            'cost' => 'FREE',
            'unlimited' => true,
            'api_required' => false,
            'description' => 'Offline TTS (requires eSpeak installed on server)'
        ),
        'google_tts' => array(
            'name' => 'Google Cloud Text-to-Speech',
            'cost' => 'PAID',
            'unlimited' => false,
            'api_required' => true,
            'description' => '$4 per 1 million characters'
        ),
        'amazon_polly' => array(
            'name' => 'Amazon Polly',
            'cost' => 'PAID',
            'unlimited' => false,
            'api_required' => true,
            'description' => '$4 per 1 million characters, neural voices available'
        )
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
     * @param array $settings Plugin settings (optional, uses saved settings if not provided)
     * @return array|false Audio data on success, false on failure
     * @since 1.0.0
     */
    public function generate_audio($post_id, $content, $settings = null) {
        // Get settings if not provided
        if (null === $settings) {
            $settings = get_option('wnap_settings', array());
        }
        
        // Process content
        $text = $this->process_content($content);
        
        if (empty($text)) {
            return false;
        }
        
        // Get TTS engine
        $engine = isset($settings['tts_engine']) ? $settings['tts_engine'] : 'web_speech';
        
        // Generate audio based on engine
        switch ($engine) {
            case 'web_speech':
                return $this->use_web_speech_api($post_id, $text, $settings);
            
            case 'responsive_voice':
                return $this->use_responsive_voice($post_id, $text, $settings);
            
            case 'espeak':
                return $this->use_espeak($post_id, $text, $settings);
            
            case 'google_tts':
                return $this->use_google_tts($post_id, $text, $settings);
            
            case 'amazon_polly':
                return $this->use_amazon_polly($post_id, $text, $settings);
            
            default:
                return $this->use_web_speech_api($post_id, $text, $settings);
        }
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
     * Web Speech API (FREE, UNLIMITED, NO API KEY)
     * 
     * @param int $post_id Post ID
     * @param string $content Text content
     * @param array $settings Plugin settings
     * @return array Audio data for client-side processing
     * @since 1.0.0
     */
    private function use_web_speech_api($post_id, $content, $settings) {
        // Return special flag - handled by JavaScript on client-side
        return array(
            'type' => 'web_speech',
            'text' => $content,
            'voice' => isset($settings['default_language']) ? $settings['default_language'] : 'en-US',
            'rate' => isset($settings['speech_speed']) ? floatval($settings['speech_speed']) : 1.0,
            'pitch' => isset($settings['pitch']) ? floatval($settings['pitch']) : 1.0,
            'post_id' => $post_id
        );
    }
    
    /**
     * ResponsiveVoice.js (FREE, 5000/day)
     * 
     * @param int $post_id Post ID
     * @param string $content Text content
     * @param array $settings Plugin settings
     * @return array Audio data for client-side processing
     * @since 1.0.0
     */
    private function use_responsive_voice($post_id, $content, $settings) {
        // Return data for ResponsiveVoice.js - handled by JavaScript on client-side
        return array(
            'type' => 'responsive_voice',
            'text' => $content,
            'voice' => isset($settings['default_language']) ? $settings['default_language'] : 'UK English Female',
            'post_id' => $post_id
        );
    }
    
    /**
     * eSpeak (FREE, UNLIMITED, SERVER-SIDE)
     * 
     * @param int $post_id Post ID
     * @param string $content Text content
     * @param array $settings Plugin settings
     * @return array|false Audio URL or false on failure
     * @since 1.0.0
     */
    private function use_espeak($post_id, $content, $settings) {
        // Check if eSpeak installed
        if (!$this->is_espeak_installed()) {
            return array(
                'error' => 'eSpeak not installed. Install with: sudo apt-get install espeak'
            );
        }
        
        // Get audio directory
        $upload_dir = wp_upload_dir();
        $audio_dir = $upload_dir['basedir'] . '/news-audio-pro/' . date('Y/m');
        
        if (!file_exists($audio_dir)) {
            wp_mkdir_p($audio_dir);
        }
        
        $filename = 'post-' . $post_id . '-' . md5($content) . '.wav';
        $filepath = $audio_dir . '/' . $filename;
        
        // Check if file already exists
        if (file_exists($filepath)) {
            return array(
                'type' => 'file',
                'url' => $upload_dir['baseurl'] . '/news-audio-pro/' . date('Y/m') . '/' . $filename
            );
        }
        
        // eSpeak command
        $language = isset($settings['default_language']) ? $settings['default_language'] : 'en-US';
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
        $voice = isset($voice_map[$language]) ? $voice_map[$language] : 'en-us';
        $speed = isset($settings['speech_speed']) ? intval(floatval($settings['speech_speed']) * 175) : 175;
        $pitch = isset($settings['pitch']) ? intval(floatval($settings['pitch']) * 50) : 50;
        
        $command = sprintf(
            'espeak -v %s -s %d -p %d -w %s %s 2>&1',
            escapeshellarg($voice),
            intval($speed),
            intval($pitch),
            escapeshellarg($filepath),
            escapeshellarg($content)
        );
        
        exec($command, $output, $return_code);
        
        if ($return_code === 0 && file_exists($filepath)) {
            return array(
                'type' => 'file',
                'url' => $upload_dir['baseurl'] . '/news-audio-pro/' . date('Y/m') . '/' . $filename
            );
        }
        
        return array('error' => 'eSpeak generation failed');
    }
    
    /**
     * Google Cloud TTS (PAID, API KEY REQUIRED)
     * 
     * @param int $post_id Post ID
     * @param string $content Text content
     * @param array $settings Plugin settings
     * @return array|false Audio URL or error
     * @since 1.0.0
     */
    private function use_google_tts($post_id, $content, $settings) {
        $api_key = get_option('wnap_google_tts_api_key');
        
        if (empty($api_key)) {
            return array('error' => 'Google TTS API key required');
        }
        
        $url = 'https://texttospeech.googleapis.com/v1/text:synthesize?key=' . $api_key;
        
        $language = isset($settings['default_language']) ? $settings['default_language'] : 'en-US';
        $data = array(
            'input' => array('text' => $content),
            'voice' => array(
                'languageCode' => $language,
                'name' => $language . '-Wavenet-D'
            ),
            'audioConfig' => array(
                'audioEncoding' => 'MP3',
                'speakingRate' => isset($settings['speech_speed']) ? floatval($settings['speech_speed']) : 1.0,
                'pitch' => isset($settings['pitch']) ? floatval($settings['pitch']) : 0.0
            )
        );
        
        $response = wp_remote_post($url, array(
            'headers' => array('Content-Type' => 'application/json'),
            'body' => wp_json_encode($data),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return array('error' => $response->get_error_message());
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['audioContent'])) {
            // Save audio file
            $upload_dir = wp_upload_dir();
            $audio_dir = $upload_dir['basedir'] . '/news-audio-pro/' . date('Y/m');
            wp_mkdir_p($audio_dir);
            
            $filename = 'post-' . $post_id . '-google.mp3';
            $filepath = $audio_dir . '/' . $filename;
            
            file_put_contents($filepath, base64_decode($body['audioContent']));
            
            return array(
                'type' => 'file',
                'url' => $upload_dir['baseurl'] . '/news-audio-pro/' . date('Y/m') . '/' . $filename
            );
        }
        
        return array('error' => 'Google TTS API error');
    }
    
    /**
     * Amazon Polly (PAID, AWS CREDENTIALS REQUIRED)
     * 
     * @param int $post_id Post ID
     * @param string $content Text content
     * @param array $settings Plugin settings
     * @return array|false Audio URL or error
     * @since 1.0.0
     */
    private function use_amazon_polly($post_id, $content, $settings) {
        // Check for AWS SDK
        if (!class_exists('Aws\Polly\PollyClient')) {
            return array('error' => 'AWS SDK for PHP not installed. Install via Composer: composer require aws/aws-sdk-php');
        }
        
        $access_key = get_option('wnap_aws_access_key');
        $secret_key = get_option('wnap_aws_secret_key');
        $region = get_option('wnap_aws_region', 'us-east-1');
        
        if (empty($access_key) || empty($secret_key)) {
            return array('error' => 'AWS credentials required');
        }
        
        try {
            $polly = new Aws\Polly\PollyClient(array(
                'version' => 'latest',
                'region' => $region,
                'credentials' => array(
                    'key' => $access_key,
                    'secret' => $secret_key
                )
            ));
            
            $result = $polly->synthesizeSpeech(array(
                'Text' => $content,
                'OutputFormat' => 'mp3',
                'VoiceId' => isset($settings['voice_id']) ? $settings['voice_id'] : 'Joanna',
                'Engine' => 'neural'
            ));
            
            // Save audio
            $upload_dir = wp_upload_dir();
            $audio_dir = $upload_dir['basedir'] . '/news-audio-pro/' . date('Y/m');
            wp_mkdir_p($audio_dir);
            
            $filename = 'post-' . $post_id . '-polly.mp3';
            $filepath = $audio_dir . '/' . $filename;
            
            file_put_contents($filepath, $result['AudioStream']);
            
            return array(
                'type' => 'file',
                'url' => $upload_dir['baseurl'] . '/news-audio-pro/' . date('Y/m') . '/' . $filename
            );
            
        } catch (Exception $e) {
            return array('error' => 'Amazon Polly error: ' . $e->getMessage());
        }
    }
    
    /**
     * Check if eSpeak is installed
     * 
     * @return bool True if installed, false otherwise
     * @since 1.0.0
     */
    private function is_espeak_installed() {
        exec('which espeak 2>&1', $output, $return_code);
        return $return_code === 0;
    }
    
    /**
     * Get available engines
     * 
     * @return array Available engines
     * @since 1.0.0
     */
    public function get_engines() {
        return $this->engines;
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

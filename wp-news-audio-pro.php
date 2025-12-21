<?php
/**
 * Plugin Name: WP News Audio Pro
 * Plugin URI: https://github.com/Geniusplug/wp-news-audio-pro
 * Description: Convert WordPress posts to audio with animated popup, multi-language TTS support, and license verification system.
 * Version: 1.0.0
 * Author: Geniusplug
 * Author URI: https://github.com/Geniusplug
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-news-audio-pro
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Tested up to: 6.4
 *
 * @package WP_News_Audio_Pro
 * @author Geniusplug
 * @copyright 2025 Geniusplug
 * @license GPL-2.0-or-later
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 */

defined('ABSPATH') or die('Direct access not allowed');

// Plugin constants
define('WNAP_VERSION', '1.0.0');
define('WNAP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WNAP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WNAP_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('WNAP_ENVATO_ITEM_ID', defined('WNAP_ITEM_ID') ? WNAP_ITEM_ID : ''); // Update after CodeCanyon approval
// API token can be overridden via wp-config.php with define('WNAP_API_TOKEN', 'your_token_here');
// For production, add the token to wp-config.php instead of hardcoding it here
define('WNAP_ENVATO_API_TOKEN', defined('WNAP_API_TOKEN') ? WNAP_API_TOKEN : 'IRXxacDkuYPM8lFe9NCNZ3rh3RMQTp49');

// Support contact information - intentionally hardcoded for security (cannot be changed by site admin)
define('WNAP_SUPPORT_EMAIL', defined('WNAP_EMAIL') ? WNAP_EMAIL : 'info.geniusplugtechnology@gmail.com');
define('WNAP_SUPPORT_WHATSAPP', '+880 1761 487193');
define('WNAP_SUPPORT_URL', 'https://geniusplug.com/support/');

/**
 * Main Plugin Class
 * 
 * Singleton pattern implementation for the main plugin functionality.
 * 
 * @since 1.0.0
 */
class WP_News_Audio_Pro {
    
    /**
     * The single instance of the class
     * 
     * @var WP_News_Audio_Pro
     */
    private static $instance = null;
    
    /**
     * Plugin core instance
     * 
     * @var WNAP_Plugin_Core
     */
    public $core;
    
    /**
     * Admin settings instance
     * 
     * @var WNAP_Admin_Settings
     */
    public $admin_settings;
    
    /**
     * TTS engine instance
     * 
     * @var WNAP_TTS_Engine
     */
    public $tts_engine;
    
    /**
     * License manager instance
     * 
     * @var WNAP_License_Manager
     */
    public $license_manager;
    
    /**
     * Frontend popup instance
     * 
     * @var WNAP_Frontend_Popup
     */
    public $frontend_popup;
    
    /**
     * License guard instance
     * 
     * @var WNAP_License_Guard
     */
    public $license_guard;
    
    /**
     * Security scanner instance
     * 
     * @var WNAP_Security_Scanner
     */
    public $security_scanner;
    
    /**
     * Audio player instance
     * 
     * @var WNAP_Audio_Player
     */
    public $audio_player;
    
    /**
     * Get the singleton instance
     * 
     * @return WP_News_Audio_Pro
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor - Initialize the plugin
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
        $this->init_classes();
    }
    
    /**
     * Load required dependencies
     * 
     * @since 1.0.0
     */
    private function load_dependencies() {
        require_once WNAP_PLUGIN_DIR . 'includes/class-plugin-core.php';
        require_once WNAP_PLUGIN_DIR . 'includes/class-tts-engine.php';
        require_once WNAP_PLUGIN_DIR . 'includes/class-license-manager.php';
        require_once WNAP_PLUGIN_DIR . 'includes/class-license-guard.php';
        require_once WNAP_PLUGIN_DIR . 'includes/class-security-scanner.php';
        require_once WNAP_PLUGIN_DIR . 'includes/class-admin-settings.php';
        require_once WNAP_PLUGIN_DIR . 'includes/class-frontend-popup.php';
        require_once WNAP_PLUGIN_DIR . 'includes/class-audio-player.php';
    }
    
    /**
     * Initialize WordPress hooks
     * 
     * @since 1.0.0
     */
    private function init_hooks() {
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Initialize plugin
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('init', array($this, 'init'));
        
        // Enqueue scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
    }
    
    /**
     * Initialize plugin classes
     * 
     * @since 1.0.0
     */
    private function init_classes() {
        $this->core = new WNAP_Plugin_Core();
        $this->tts_engine = new WNAP_TTS_Engine();
        $this->license_manager = new WNAP_License_Manager();
        $this->security_scanner = new WNAP_Security_Scanner();
        $this->license_guard = new WNAP_License_Guard($this->license_manager);
        $this->admin_settings = new WNAP_Admin_Settings();
        
        // Only initialize frontend features if licensed
        if ($this->license_guard->is_licensed()) {
            $this->frontend_popup = new WNAP_Frontend_Popup();
            $this->audio_player = new WNAP_Audio_Player();
        }
    }
    
    /**
     * Plugin activation
     * 
     * @since 1.0.0
     */
    public function activate() {
        global $wpdb;
        
        // Set default options
        $default_settings = array(
            'enable_popup' => false, // Popup disabled by default, button enabled
            'auto_play' => false,
            'default_language' => 'en-US',
            'player_position' => 'popup',
            'tts_engine' => 'web_speech', // FREE, UNLIMITED by default
            'voice_engine' => 'espeak',
            'speech_speed' => 1.0,
            'pitch' => 1.0,
            'volume' => 80,
            'audio_format' => 'mp3',
            'cache_duration' => 30,
            'show_on_posts' => true, // Show button on posts by default
            'show_on_pages' => true, // Show button on pages by default
            'show_on_home' => true,  // Show button on home by default
            'exclude_pages' => '',
            'exclude_urls' => '',
        );
        
        add_option('wnap_settings', $default_settings);
        add_option('wnap_version', WNAP_VERSION);
        
        // Create license table with hashed purchase code
        $table_name = $wpdb->prefix . 'wnap_licenses';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            purchase_code_hash varchar(64) NOT NULL,
            domain varchar(255) NOT NULL,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY purchase_code_hash (purchase_code_hash)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Migrate existing data if needed
        $this->migrate_license_table();
        
        // Create upload directory
        $upload_dir = wp_upload_dir();
        $audio_dir = $upload_dir['basedir'] . '/news-audio-pro/';
        if (!file_exists($audio_dir)) {
            wp_mkdir_p($audio_dir);
        }
        
        // Schedule cleanup cron
        if (!wp_next_scheduled('wnap_cleanup_old_audio')) {
            wp_schedule_event(time(), 'daily', 'wnap_cleanup_old_audio');
        }
    }
    
    /**
     * Migrate license table from plain text to hashed codes
     * 
     * @since 1.0.0
     */
    private function migrate_license_table() {
        global $wpdb;
        
        try {
            $table_name = $wpdb->prefix . 'wnap_licenses';
            
            // Check if old column exists
            // Check if old column exists - table name is already sanitized by WordPress
            $column_exists = $wpdb->get_results(
                "SHOW COLUMNS FROM `{$table_name}` LIKE 'purchase_code'"
            );
            
            if (!empty($column_exists)) {
                // Get all existing records with plain text codes
                $existing_licenses = $wpdb->get_results(
                    "SELECT * FROM `{$table_name}` WHERE purchase_code IS NOT NULL AND purchase_code != ''"
                );
                
                if (!empty($existing_licenses)) {
                    foreach ($existing_licenses as $license) {
                        // Hash the purchase code
                        $code_hash = hash('sha256', $license->purchase_code);
                        
                        // Update with hash
                        $wpdb->update(
                            $table_name,
                            array('purchase_code_hash' => $code_hash),
                            array('id' => $license->id),
                            array('%s'),
                            array('%d')
                        );
                    }
                }
                
                // Drop the old column to ensure security - use backticks for safety
                $result = $wpdb->query("ALTER TABLE `{$table_name}` DROP COLUMN `purchase_code`");
                
                if ($result !== false) {
                    error_log('WNAP: Successfully migrated license table to use hashed purchase codes');
                } else {
                    error_log('WNAP: Failed to drop purchase_code column: ' . $wpdb->last_error);
                }
            }
        } catch (Exception $e) {
            error_log('WNAP: Error migrating license table: ' . $e->getMessage());
        }
    }
    
    /**
     * Plugin deactivation
     * 
     * @since 1.0.0
     */
    public function deactivate() {
        // Clear scheduled cron jobs
        wp_clear_scheduled_hook('wnap_cleanup_old_audio');
        wp_clear_scheduled_hook('wnap_license_check');
    }
    
    /**
     * Load plugin textdomain for translations
     * 
     * @since 1.0.0
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'wp-news-audio-pro',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }
    
    /**
     * Initialize plugin
     * 
     * @since 1.0.0
     */
    public function init() {
        // Additional initialization if needed
        do_action('wnap_init');
    }
    
    /**
     * Enqueue admin assets
     * 
     * @param string $hook Current admin page hook
     * @since 1.0.0
     */
    public function enqueue_admin_assets($hook) {
        // Only load on plugin pages
        if (strpos($hook, 'news-audio-pro') === false) {
            return;
        }
        
        // Admin CSS
        wp_enqueue_style(
            'wnap-admin-style',
            WNAP_PLUGIN_URL . 'assets/css/admin-style.css',
            array(),
            WNAP_VERSION,
            'all'
        );
        
        // Admin JS
        wp_enqueue_script(
            'wnap-admin-script',
            WNAP_PLUGIN_URL . 'assets/js/admin-script.js',
            array('jquery'),
            WNAP_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('wnap-admin-script', 'wnapAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wnap_admin_nonce'),
            'strings' => array(
                'saving' => __('Saving...', 'wp-news-audio-pro'),
                'saved' => __('Settings saved successfully', 'wp-news-audio-pro'),
                'error' => __('Error saving settings', 'wp-news-audio-pro'),
            ),
        ));
    }
    
    /**
     * Enqueue frontend assets
     * 
     * @since 1.0.0
     */
    public function enqueue_frontend_assets() {
        // Check if licensed
        if (!$this->license_guard || !$this->license_guard->is_licensed()) {
            return;
        }
        
        // Check if we should load on this page
        $settings = get_option('wnap_settings', array());
        
        // Use default values if settings not set
        $show_on_posts = isset($settings['show_on_posts']) ? $settings['show_on_posts'] : true;
        $show_on_pages = isset($settings['show_on_pages']) ? $settings['show_on_pages'] : true;
        $show_on_home = isset($settings['show_on_home']) ? $settings['show_on_home'] : true;
        
        $should_load = false;
        
        if (is_singular('post') && $show_on_posts) {
            $should_load = true;
        } elseif (is_page() && $show_on_pages) {
            $should_load = true;
        } elseif (is_front_page() && $show_on_home) {
            $should_load = true;
        } elseif (is_single()) {
            // Default to showing on single posts if settings not configured
            $should_load = true;
        }
        
        if (!$should_load) {
            return;
        }
        
        // Frontend CSS
        wp_enqueue_style(
            'wnap-frontend-style',
            WNAP_PLUGIN_URL . 'assets/css/frontend-style.css',
            array(),
            WNAP_VERSION,
            'all'
        );
        
        // Premium Player CSS
        wp_enqueue_style(
            'wnap-player-premium',
            WNAP_PLUGIN_URL . 'assets/css/player-premium.css',
            array('wnap-frontend-style'),
            WNAP_VERSION,
            'all'
        );
        
        // Frontend JS
        wp_enqueue_script(
            'wnap-frontend-script',
            WNAP_PLUGIN_URL . 'assets/js/frontend-script.js',
            array('jquery'),
            WNAP_VERSION,
            true
        );
        
        // Player Draggable JS
        wp_enqueue_script(
            'wnap-player-draggable',
            WNAP_PLUGIN_URL . 'assets/js/player-draggable.js',
            array('jquery', 'wnap-frontend-script'),
            WNAP_VERSION,
            true
        );
        
        // Player Keyboard JS
        wp_enqueue_script(
            'wnap-player-keyboard',
            WNAP_PLUGIN_URL . 'assets/js/player-keyboard.js',
            array('jquery', 'wnap-frontend-script'),
            WNAP_VERSION,
            true
        );
        
        // Plyr.js for audio player
        wp_enqueue_style(
            'plyr-css',
            'https://cdn.plyr.io/3.7.8/plyr.css',
            array(),
            '3.7.8'
        );
        
        wp_enqueue_script(
            'plyr-js',
            'https://cdn.plyr.io/3.7.8/plyr.js',
            array(),
            '3.7.8',
            true
        );
        
        // Localize script
        wp_localize_script('wnap-frontend-script', 'wnapFrontend', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wnap_frontend_nonce'),
            'postId' => get_the_ID(),
            'settings' => $settings,
            'strings' => array(
                'loading' => __('Generating audio...', 'wp-news-audio-pro'),
                'error' => __('Error generating audio', 'wp-news-audio-pro'),
            ),
        ));
    }
}

/**
 * Initialize the plugin
 * 
 * @return WP_News_Audio_Pro
 */
function wnap() {
    return WP_News_Audio_Pro::get_instance();
}

// Initialize plugin
wnap();

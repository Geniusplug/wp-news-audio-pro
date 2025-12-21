<?php
/**
 * Admin Settings Class
 * 
 * Handles admin settings panel with tabbed interface
 * 
 * @package WP_News_Audio_Pro
 * @since 1.0.0
 */

defined('ABSPATH') or die('Direct access not allowed');

/**
 * WNAP_Admin_Settings class
 * 
 * Manage plugin settings in WordPress admin
 * 
 * @since 1.0.0
 */
class WNAP_Admin_Settings {
    
    /**
     * Settings page slug
     * 
     * @var string
     */
    private $page_slug = 'news-audio-pro';
    
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
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_wnap_save_settings', array($this, 'ajax_save_settings'));
    }
    
    /**
     * Add admin menu
     * 
     * @since 1.0.0
     */
    public function add_admin_menu() {
        add_menu_page(
            __('News Audio Pro', 'wp-news-audio-pro'),
            __('News Audio Pro', 'wp-news-audio-pro'),
            'manage_options',
            $this->page_slug,
            array($this, 'render_settings_page'),
            'dashicons-controls-volumeon',
            30
        );
    }
    
    /**
     * Register settings
     * 
     * @since 1.0.0
     */
    public function register_settings() {
        register_setting(
            'wnap_settings_group',
            'wnap_settings',
            array($this, 'sanitize_settings')
        );
        
        // Register API key options separately
        register_setting('wnap_settings_group', 'wnap_google_tts_api_key', 'sanitize_text_field');
        register_setting('wnap_settings_group', 'wnap_aws_access_key', 'sanitize_text_field');
        register_setting('wnap_settings_group', 'wnap_aws_secret_key', 'sanitize_text_field');
        register_setting('wnap_settings_group', 'wnap_aws_region', 'sanitize_text_field');
    }
    
    /**
     * Sanitize settings
     * 
     * @param array $input Input settings
     * @return array Sanitized settings
     * @since 1.0.0
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        // General settings
        $sanitized['enable_popup'] = isset($input['enable_popup']) ? (bool) $input['enable_popup'] : false;
        $sanitized['auto_play'] = isset($input['auto_play']) ? (bool) $input['auto_play'] : false;
        $sanitized['default_language'] = isset($input['default_language']) ? sanitize_text_field($input['default_language']) : 'en-US';
        $sanitized['player_position'] = isset($input['player_position']) ? sanitize_text_field($input['player_position']) : 'popup';
        
        // Display settings
        $sanitized['show_on_posts'] = isset($input['show_on_posts']) ? (bool) $input['show_on_posts'] : false;
        $sanitized['show_on_pages'] = isset($input['show_on_pages']) ? (bool) $input['show_on_pages'] : false;
        $sanitized['show_on_home'] = isset($input['show_on_home']) ? (bool) $input['show_on_home'] : false;
        $sanitized['exclude_pages'] = isset($input['exclude_pages']) ? sanitize_text_field($input['exclude_pages']) : '';
        $sanitized['exclude_urls'] = isset($input['exclude_urls']) ? sanitize_textarea_field($input['exclude_urls']) : '';
        
        // Audio settings
        $sanitized['tts_engine'] = isset($input['tts_engine']) ? sanitize_text_field($input['tts_engine']) : 'web_speech';
        $sanitized['voice_engine'] = isset($input['voice_engine']) ? sanitize_text_field($input['voice_engine']) : 'espeak';
        $sanitized['speech_speed'] = isset($input['speech_speed']) ? floatval($input['speech_speed']) : 1.0;
        $sanitized['pitch'] = isset($input['pitch']) ? floatval($input['pitch']) : 1.0;
        $sanitized['volume'] = isset($input['volume']) ? absint($input['volume']) : 80;
        $sanitized['audio_format'] = isset($input['audio_format']) ? sanitize_text_field($input['audio_format']) : 'mp3';
        $sanitized['cache_duration'] = isset($input['cache_duration']) ? absint($input['cache_duration']) : 30;
        
        return $sanitized;
    }
    
    /**
     * Render settings page
     * 
     * @since 1.0.0
     */
    public function render_settings_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized access', 'wp-news-audio-pro'));
        }
        
        // Get current settings
        $settings = get_option('wnap_settings', array());
        
        // Get current tab
        $current_tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : 'general';
        
        // Get license manager
        $license_manager = new WNAP_License_Manager();
        $license_data = $license_manager->get_license_data();
        $is_license_valid = $license_manager->is_license_valid();
        
        ?>
        <div class="wrap wnap-settings-wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <nav class="nav-tab-wrapper">
                <a href="?page=<?php echo esc_attr($this->page_slug); ?>&tab=general" 
                   class="nav-tab <?php echo $current_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('General', 'wp-news-audio-pro'); ?>
                </a>
                <a href="?page=<?php echo esc_attr($this->page_slug); ?>&tab=audio" 
                   class="nav-tab <?php echo $current_tab === 'audio' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Audio Settings', 'wp-news-audio-pro'); ?>
                </a>
                <a href="?page=<?php echo esc_attr($this->page_slug); ?>&tab=license" 
                   class="nav-tab <?php echo $current_tab === 'license' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('License', 'wp-news-audio-pro'); ?>
                </a>
                <a href="?page=<?php echo esc_attr($this->page_slug); ?>&tab=about" 
                   class="nav-tab <?php echo $current_tab === 'about' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('About', 'wp-news-audio-pro'); ?>
                </a>
            </nav>
            
            <div class="wnap-tab-content">
                <?php
                switch ($current_tab) {
                    case 'general':
                        $this->render_general_tab($settings);
                        break;
                    case 'audio':
                        $this->render_audio_tab($settings);
                        break;
                    case 'license':
                        $this->render_license_tab($license_data, $is_license_valid);
                        break;
                    case 'about':
                        $this->render_about_tab();
                        break;
                    default:
                        $this->render_general_tab($settings);
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render general settings tab
     * 
     * @param array $settings Current settings
     * @since 1.0.0
     */
    private function render_general_tab($settings) {
        $tts_engine = new WNAP_TTS_Engine();
        $languages = $tts_engine->get_supported_languages();
        ?>
        <form method="post" action="options.php" class="wnap-settings-form">
            <?php settings_fields('wnap_settings_group'); ?>
            
            <h2><?php esc_html_e('General Settings', 'wp-news-audio-pro'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="enable_popup">
                            <?php esc_html_e('Enable Popup', 'wp-news-audio-pro'); ?>
                        </label>
                    </th>
                    <td>
                        <label class="wnap-toggle">
                            <input type="checkbox" 
                                   name="wnap_settings[enable_popup]" 
                                   id="enable_popup" 
                                   value="1" 
                                   <?php checked(isset($settings['enable_popup']) ? $settings['enable_popup'] : true, true); ?>>
                            <span class="wnap-toggle-slider"></span>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Show the audio popup on single post pages', 'wp-news-audio-pro'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="auto_play">
                            <?php esc_html_e('Auto-play on Page Load', 'wp-news-audio-pro'); ?>
                        </label>
                    </th>
                    <td>
                        <label class="wnap-toggle">
                            <input type="checkbox" 
                                   name="wnap_settings[auto_play]" 
                                   id="auto_play" 
                                   value="1" 
                                   <?php checked(isset($settings['auto_play']) ? $settings['auto_play'] : false, true); ?>>
                            <span class="wnap-toggle-slider"></span>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Automatically play audio when the page loads', 'wp-news-audio-pro'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="default_language">
                            <?php esc_html_e('Default Language', 'wp-news-audio-pro'); ?>
                        </label>
                    </th>
                    <td>
                        <select name="wnap_settings[default_language]" id="default_language" class="regular-text">
                            <?php foreach ($languages as $code => $name) : ?>
                                <option value="<?php echo esc_attr($code); ?>" 
                                        <?php selected(isset($settings['default_language']) ? $settings['default_language'] : 'en-US', $code); ?>>
                                    <?php echo esc_html($name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php esc_html_e('Select the default language for text-to-speech conversion', 'wp-news-audio-pro'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="player_position">
                            <?php esc_html_e('Player Position', 'wp-news-audio-pro'); ?>
                        </label>
                    </th>
                    <td>
                        <select name="wnap_settings[player_position]" id="player_position" class="regular-text">
                            <option value="popup" <?php selected(isset($settings['player_position']) ? $settings['player_position'] : 'popup', 'popup'); ?>>
                                <?php esc_html_e('Popup Modal', 'wp-news-audio-pro'); ?>
                            </option>
                            <option value="fixed-bottom" <?php selected(isset($settings['player_position']) ? $settings['player_position'] : '', 'fixed-bottom'); ?>>
                                <?php esc_html_e('Fixed Bottom', 'wp-news-audio-pro'); ?>
                            </option>
                            <option value="inline" <?php selected(isset($settings['player_position']) ? $settings['player_position'] : '', 'inline'); ?>>
                                <?php esc_html_e('Inline After Content', 'wp-news-audio-pro'); ?>
                            </option>
                        </select>
                        <p class="description">
                            <?php esc_html_e('Choose where the audio player appears on the page', 'wp-news-audio-pro'); ?>
                        </p>
                    </td>
                </tr>
            </table>
            
            <h2><?php esc_html_e('Display Settings', 'wp-news-audio-pro'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <?php esc_html_e('Show Floating Button On', 'wp-news-audio-pro'); ?>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="wnap_settings[show_on_posts]" 
                                   value="1" 
                                   <?php checked(isset($settings['show_on_posts']) ? $settings['show_on_posts'] : true, true); ?>>
                            <?php esc_html_e('Posts', 'wp-news-audio-pro'); ?>
                        </label><br>
                        
                        <label>
                            <input type="checkbox" 
                                   name="wnap_settings[show_on_pages]" 
                                   value="1" 
                                   <?php checked(isset($settings['show_on_pages']) ? $settings['show_on_pages'] : true, true); ?>>
                            <?php esc_html_e('Pages', 'wp-news-audio-pro'); ?>
                        </label><br>
                        
                        <label>
                            <input type="checkbox" 
                                   name="wnap_settings[show_on_home]" 
                                   value="1" 
                                   <?php checked(isset($settings['show_on_home']) ? $settings['show_on_home'] : false, true); ?>>
                            <?php esc_html_e('Home Page', 'wp-news-audio-pro'); ?>
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="exclude_pages">
                            <?php esc_html_e('Exclude Pages (IDs)', 'wp-news-audio-pro'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="text" 
                               name="wnap_settings[exclude_pages]" 
                               id="exclude_pages"
                               value="<?php echo esc_attr(isset($settings['exclude_pages']) ? $settings['exclude_pages'] : ''); ?>" 
                               class="regular-text">
                        <p class="description">
                            <?php esc_html_e('Enter page/post IDs to exclude, separated by commas. Example: 5, 12, 34', 'wp-news-audio-pro'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="exclude_urls">
                            <?php esc_html_e('Exclude by URL Pattern', 'wp-news-audio-pro'); ?>
                        </label>
                    </th>
                    <td>
                        <textarea name="wnap_settings[exclude_urls]" 
                                  id="exclude_urls"
                                  rows="3" 
                                  class="large-text"><?php echo esc_textarea(isset($settings['exclude_urls']) ? $settings['exclude_urls'] : ''); ?></textarea>
                        <p class="description">
                            <?php esc_html_e('One URL pattern per line. Example:', 'wp-news-audio-pro'); ?><br>
                            /cart/<br>
                            /checkout/<br>
                            /account/
                        </p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(); ?>
        </form>
        <?php
    }
    
    /**
     * Render audio settings tab
     * 
     * @param array $settings Current settings
     * @since 1.0.0
     */
    private function render_audio_tab($settings) {
        $selected_engine = isset($settings['tts_engine']) ? $settings['tts_engine'] : 'web_speech';
        ?>
        <form method="post" action="options.php" class="wnap-settings-form">
            <?php settings_fields('wnap_settings_group'); ?>
            
            <h2><?php esc_html_e('Audio Settings', 'wp-news-audio-pro'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="tts_engine">
                            <?php esc_html_e('TTS Engine', 'wp-news-audio-pro'); ?>
                        </label>
                    </th>
                    <td>
                        <select name="wnap_settings[tts_engine]" id="wnap_tts_engine" class="regular-text">
                            <option value="web_speech" <?php selected($selected_engine, 'web_speech'); ?>>
                                <?php esc_html_e('Web Speech API (FREE - Unlimited) ⭐ RECOMMENDED', 'wp-news-audio-pro'); ?>
                            </option>
                            <option value="responsive_voice" <?php selected($selected_engine, 'responsive_voice'); ?>>
                                <?php esc_html_e('ResponsiveVoice.js (FREE - 5,000/day)', 'wp-news-audio-pro'); ?>
                            </option>
                            <option value="espeak" <?php selected($selected_engine, 'espeak'); ?>>
                                <?php esc_html_e('eSpeak (FREE - Unlimited, Server-side)', 'wp-news-audio-pro'); ?>
                            </option>
                            <option value="google_tts" <?php selected($selected_engine, 'google_tts'); ?>>
                                <?php esc_html_e('Google Cloud TTS (PAID - API Key Required)', 'wp-news-audio-pro'); ?>
                            </option>
                            <option value="amazon_polly" <?php selected($selected_engine, 'amazon_polly'); ?>>
                                <?php esc_html_e('Amazon Polly (PAID - AWS Credentials Required)', 'wp-news-audio-pro'); ?>
                            </option>
                        </select>
                        <p class="description">
                            <?php esc_html_e('Default: Web Speech API (no setup needed)', 'wp-news-audio-pro'); ?>
                        </p>
                    </td>
                </tr>
                
                <!-- Dynamic API Fields (shown based on selection) -->
                <tr class="wnap-api-field" data-engine="google_tts" style="display: none;">
                    <th scope="row">
                        <label for="wnap_google_tts_api_key">
                            <?php esc_html_e('Google TTS API Key', 'wp-news-audio-pro'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="text" 
                               name="wnap_google_tts_api_key" 
                               id="wnap_google_tts_api_key"
                               value="<?php echo esc_attr(get_option('wnap_google_tts_api_key')); ?>" 
                               class="regular-text">
                        <p class="description">
                            <?php esc_html_e('Get your API key from:', 'wp-news-audio-pro'); ?>
                            <a href="https://console.cloud.google.com/apis/credentials" target="_blank">
                                <?php esc_html_e('Google Cloud Console', 'wp-news-audio-pro'); ?>
                            </a>
                        </p>
                    </td>
                </tr>
                
                <tr class="wnap-api-field" data-engine="amazon_polly" style="display: none;">
                    <th scope="row">
                        <label for="wnap_aws_access_key">
                            <?php esc_html_e('AWS Access Key', 'wp-news-audio-pro'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="text" 
                               name="wnap_aws_access_key" 
                               id="wnap_aws_access_key"
                               value="<?php echo esc_attr(get_option('wnap_aws_access_key')); ?>" 
                               class="regular-text">
                    </td>
                </tr>
                
                <tr class="wnap-api-field" data-engine="amazon_polly" style="display: none;">
                    <th scope="row">
                        <label for="wnap_aws_secret_key">
                            <?php esc_html_e('AWS Secret Key', 'wp-news-audio-pro'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="password" 
                               name="wnap_aws_secret_key" 
                               id="wnap_aws_secret_key"
                               value="<?php echo esc_attr(get_option('wnap_aws_secret_key')); ?>" 
                               class="regular-text">
                    </td>
                </tr>
                
                <tr class="wnap-api-field" data-engine="amazon_polly" style="display: none;">
                    <th scope="row">
                        <label for="wnap_aws_region">
                            <?php esc_html_e('AWS Region', 'wp-news-audio-pro'); ?>
                        </label>
                    </th>
                    <td>
                        <select name="wnap_aws_region" id="wnap_aws_region" class="regular-text">
                            <option value="us-east-1" <?php selected(get_option('wnap_aws_region', 'us-east-1'), 'us-east-1'); ?>>
                                <?php esc_html_e('US East (N. Virginia)', 'wp-news-audio-pro'); ?>
                            </option>
                            <option value="us-west-2" <?php selected(get_option('wnap_aws_region', 'us-east-1'), 'us-west-2'); ?>>
                                <?php esc_html_e('US West (Oregon)', 'wp-news-audio-pro'); ?>
                            </option>
                            <option value="eu-west-1" <?php selected(get_option('wnap_aws_region', 'us-east-1'), 'eu-west-1'); ?>>
                                <?php esc_html_e('EU (Ireland)', 'wp-news-audio-pro'); ?>
                            </option>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="speech_speed">
                            <?php esc_html_e('Speech Speed', 'wp-news-audio-pro'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="range" 
                               name="wnap_settings[speech_speed]" 
                               id="speech_speed" 
                               min="0.5" 
                               max="2.0" 
                               step="0.1" 
                               value="<?php echo esc_attr(isset($settings['speech_speed']) ? $settings['speech_speed'] : 1.0); ?>"
                               class="wnap-range-slider">
                        <span class="wnap-range-value"><?php echo esc_html(isset($settings['speech_speed']) ? $settings['speech_speed'] : 1.0); ?>x</span>
                        <p class="description">
                            <?php esc_html_e('Adjust the speech speed (0.5x to 2.0x)', 'wp-news-audio-pro'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="pitch">
                            <?php esc_html_e('Pitch', 'wp-news-audio-pro'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="range" 
                               name="wnap_settings[pitch]" 
                               id="pitch" 
                               min="0.5" 
                               max="2.0" 
                               step="0.1" 
                               value="<?php echo esc_attr(isset($settings['pitch']) ? $settings['pitch'] : 1.0); ?>"
                               class="wnap-range-slider">
                        <span class="wnap-range-value"><?php echo esc_html(isset($settings['pitch']) ? $settings['pitch'] : 1.0); ?></span>
                        <p class="description">
                            <?php esc_html_e('Adjust the voice pitch (0.5 to 2.0)', 'wp-news-audio-pro'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="volume">
                            <?php esc_html_e('Volume', 'wp-news-audio-pro'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="range" 
                               name="wnap_settings[volume]" 
                               id="volume" 
                               min="0" 
                               max="100" 
                               step="5" 
                               value="<?php echo esc_attr(isset($settings['volume']) ? $settings['volume'] : 80); ?>"
                               class="wnap-range-slider">
                        <span class="wnap-range-value"><?php echo esc_html(isset($settings['volume']) ? $settings['volume'] : 80); ?>%</span>
                        <p class="description">
                            <?php esc_html_e('Set the default volume (0 to 100)', 'wp-news-audio-pro'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="cache_duration">
                            <?php esc_html_e('Cache Duration (Days)', 'wp-news-audio-pro'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" 
                               name="wnap_settings[cache_duration]" 
                               id="cache_duration" 
                               min="1" 
                               max="365" 
                               value="<?php echo esc_attr(isset($settings['cache_duration']) ? $settings['cache_duration'] : 30); ?>"
                               class="small-text">
                        <p class="description">
                            <?php esc_html_e('Number of days to keep audio files before cleanup', 'wp-news-audio-pro'); ?>
                        </p>
                    </td>
                </tr>
            </table>
            
            <script>
            jQuery(document).ready(function($) {
                // Show/hide API fields based on engine selection
                $('#wnap_tts_engine').on('change', function() {
                    var engine = $(this).val();
                    $('.wnap-api-field').hide();
                    $('.wnap-api-field[data-engine="' + engine + '"]').show();
                }).trigger('change');
            });
            </script>
            
            <?php submit_button(); ?>
        </form>
        <?php
    }
    
    /**
     * Render license tab
     * 
     * @param array|false $license_data License data
     * @param bool $is_valid Whether license is valid
     * @since 1.0.0
     */
    private function render_license_tab($license_data, $is_valid) {
        $current_domain = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '';
        ?>
        <div class="wnap-license-section">
            <h2><?php esc_html_e('License Activation', 'wp-news-audio-pro'); ?></h2>
            
            <?php if ($is_valid && $license_data) : ?>
                <?php 
                // Check if it's a test license
                $is_test_license = isset($license_data['type']) && $license_data['type'] === 'test';
                ?>
                
                <?php if ($is_test_license) : ?>
                    <?php
                    // Calculate days remaining for test license
                    $days_remaining = 0;
                    if (isset($license_data['expires_at'])) {
                        $days_remaining = ceil(($license_data['expires_at'] - time()) / DAY_IN_SECONDS);
                    } elseif (isset($license_data['activated_at'])) {
                        $days_remaining = 90 - ceil((time() - $license_data['activated_at']) / DAY_IN_SECONDS);
                    }
                    ?>
                    <div class="notice notice-success" style="padding: 15px; border-left-color: #46b450;">
                        <h3 style="margin-top: 0;">
                            <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                            <?php esc_html_e('✅ Test License Active', 'wp-news-audio-pro'); ?>
                        </h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php esc_html_e('License Type', 'wp-news-audio-pro'); ?></th>
                                <td><strong><?php esc_html_e('Test License - 90 Days', 'wp-news-audio-pro'); ?></strong></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Domain', 'wp-news-audio-pro'); ?></th>
                                <td><code><?php echo esc_html($license_data['domain'] ?? $current_domain); ?></code></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Expires In', 'wp-news-audio-pro'); ?></th>
                                <td>
                                    <strong style="color: <?php echo esc_attr($days_remaining > 30 ? '#46b450' : '#dc3232'); ?>">
                                        <?php echo esc_html($days_remaining); ?> days
                                    </strong>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Activated', 'wp-news-audio-pro'); ?></th>
                                <td>
                                    <?php 
                                    if (isset($license_data['activated_at'])) {
                                        echo esc_html(date_i18n('Y-m-d H:i:s', $license_data['activated_at']));
                                    } else {
                                        esc_html_e('Unknown', 'wp-news-audio-pro');
                                    }
                                    ?>
                                </td>
                            </tr>
                        </table>
                        <p class="description">
                            <?php esc_html_e('This is a test license valid only on localhost/development environments.', 'wp-news-audio-pro'); ?>
                        </p>
                    </div>
                <?php else : ?>
                    <div class="notice notice-success" style="padding: 15px; border-left-color: #46b450;">
                        <h3 style="margin-top: 0;">
                            <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                            <?php esc_html_e('✅ License Active', 'wp-news-audio-pro'); ?>
                        </h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php esc_html_e('Domain', 'wp-news-audio-pro'); ?></th>
                                <td><code><?php echo esc_html($license_data['domain']); ?></code></td>
                            </tr>
                            <?php if (isset($license_data['buyer'])) : ?>
                            <tr>
                                <th scope="row"><?php esc_html_e('Buyer', 'wp-news-audio-pro'); ?></th>
                                <td><?php echo esc_html($license_data['buyer']); ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <th scope="row"><?php esc_html_e('Activated', 'wp-news-audio-pro'); ?></th>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), $license_data['activated_at'])); ?></td>
                            </tr>
                            <?php if (isset($license_data['license'])) : ?>
                            <tr>
                                <th scope="row"><?php esc_html_e('License Type', 'wp-news-audio-pro'); ?></th>
                                <td><?php echo esc_html($license_data['license']); ?></td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                <?php endif; ?>
                
                <p>
                    <button type="button" class="button button-secondary wnap-deactivate-license">
                        <?php esc_html_e('Deactivate License', 'wp-news-audio-pro'); ?>
                    </button>
                </p>
            <?php else : ?>
                <div class="wnap-license-status wnap-license-inactive">
                    <span class="dashicons dashicons-warning"></span>
                    <strong><?php esc_html_e('License Not Activated', 'wp-news-audio-pro'); ?></strong>
                </div>
                
                <form class="wnap-license-form">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="purchase_code">
                                    <?php esc_html_e('Purchase Code', 'wp-news-audio-pro'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="text" 
                                       name="purchase_code" 
                                       id="purchase_code" 
                                       class="regular-text" 
                                       placeholder="<?php esc_attr_e('Enter your CodeCanyon purchase code', 'wp-news-audio-pro'); ?>">
                                <p class="description">
                                    <?php esc_html_e('Enter your purchase code from CodeCanyon', 'wp-news-audio-pro'); ?>
                                    <br>
                                    <a href="https://help.market.envato.com/hc/en-us/articles/202822600-Where-Is-My-Purchase-Code-" target="_blank">
                                        <?php esc_html_e('Where to find your purchase code?', 'wp-news-audio-pro'); ?>
                                    </a>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Domain', 'wp-news-audio-pro'); ?></th>
                            <td>
                                <code><?php echo esc_html($current_domain); ?></code>
                                <p class="description">
                                    <?php esc_html_e('Your license will be activated for this domain', 'wp-news-audio-pro'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                    
                    <p>
                        <button type="submit" class="button button-primary wnap-activate-license">
                            <?php esc_html_e('Activate License', 'wp-news-audio-pro'); ?>
                        </button>
                        <span class="wnap-license-loader" style="display:none;">
                            <span class="spinner is-active"></span>
                        </span>
                    </p>
                </form>
            <?php endif; ?>
            
            <div class="wnap-license-message" style="display:none;"></div>
            
            <hr>
            
            <h3><?php esc_html_e('Support', 'wp-news-audio-pro'); ?></h3>
            <div class="wnap-support-card" style="background: #f9f9f9; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-top: 20px;">
                <div class="wnap-support-item" style="margin-bottom: 15px; padding: 10px; background: white; border-radius: 5px;">
                    <span class="dashicons dashicons-email-alt" style="color: #4A90E2; font-size: 20px; vertical-align: middle;"></span>
                    <strong><?php esc_html_e('Email Support', 'wp-news-audio-pro'); ?></strong><br>
                    <a href="mailto:info.geniusplugtechnology@gmail.com" style="margin-left: 30px;">
                        info.geniusplugtechnology@gmail.com
                    </a>
                </div>
                
                <div class="wnap-support-item" style="margin-bottom: 15px; padding: 10px; background: white; border-radius: 5px;">
                    <span class="dashicons dashicons-whatsapp" style="color: #25D366; font-size: 20px; vertical-align: middle;"></span>
                    <strong><?php esc_html_e('WhatsApp Support', 'wp-news-audio-pro'); ?></strong><br>
                    <a href="https://wa.me/8801761487193" target="_blank" style="margin-left: 30px;">
                        +880 1761 487193
                    </a>
                </div>
                
                <div class="wnap-support-item" style="padding: 10px; background: white; border-radius: 5px;">
                    <span class="dashicons dashicons-admin-links" style="color: #7B68EE; font-size: 20px; vertical-align: middle;"></span>
                    <strong><?php esc_html_e('Support Portal', 'wp-news-audio-pro'); ?></strong><br>
                    <a href="https://geniusplug.com/support/" target="_blank" style="margin-left: 30px;">
                        <?php esc_html_e('Submit Ticket', 'wp-news-audio-pro'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render about tab
     * 
     * @since 1.0.0
     */
    private function render_about_tab() {
        ?>
        <div class="wnap-about-section">
            <h2><?php esc_html_e('About WP News Audio Pro', 'wp-news-audio-pro'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('Version', 'wp-news-audio-pro'); ?></th>
                    <td><?php echo esc_html(WNAP_VERSION); ?></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Author', 'wp-news-audio-pro'); ?></th>
                    <td>
                        <a href="https://github.com/Geniusplug" target="_blank">Geniusplug</a>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Documentation', 'wp-news-audio-pro'); ?></th>
                    <td>
                        <a href="https://yoursite.com/docs" target="_blank">
                            <?php esc_html_e('View Documentation', 'wp-news-audio-pro'); ?>
                        </a>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Support', 'wp-news-audio-pro'); ?></th>
                    <td>
                        <a href="mailto:<?php echo esc_attr(WNAP_SUPPORT_EMAIL); ?>"><?php echo esc_html(WNAP_SUPPORT_EMAIL); ?></a>
                    </td>
                </tr>
            </table>
            
            <hr>
            
            <h3><?php esc_html_e('Support & Contact', 'wp-news-audio-pro'); ?></h3>
            <div class="wnap-support-card" style="background: #f9f9f9; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-top: 20px;">
                <div class="wnap-support-item" style="margin-bottom: 15px; padding: 10px; background: white; border-radius: 5px;">
                    <span class="dashicons dashicons-email-alt" style="color: #4A90E2; font-size: 20px; vertical-align: middle;"></span>
                    <strong><?php esc_html_e('Email Support', 'wp-news-audio-pro'); ?></strong><br>
                    <a href="mailto:info.geniusplugtechnology@gmail.com" style="margin-left: 30px;">
                        info.geniusplugtechnology@gmail.com
                    </a>
                </div>
                
                <div class="wnap-support-item" style="margin-bottom: 15px; padding: 10px; background: white; border-radius: 5px;">
                    <span class="dashicons dashicons-whatsapp" style="color: #25D366; font-size: 20px; vertical-align: middle;"></span>
                    <strong><?php esc_html_e('WhatsApp Support', 'wp-news-audio-pro'); ?></strong><br>
                    <a href="https://wa.me/8801761487193" target="_blank" style="margin-left: 30px;">
                        +880 1761 487193
                    </a>
                </div>
                
                <div class="wnap-support-item" style="padding: 10px; background: white; border-radius: 5px;">
                    <span class="dashicons dashicons-admin-links" style="color: #7B68EE; font-size: 20px; vertical-align: middle;"></span>
                    <strong><?php esc_html_e('Support Portal', 'wp-news-audio-pro'); ?></strong><br>
                    <a href="https://geniusplug.com/support/" target="_blank" style="margin-left: 30px;">
                        <?php esc_html_e('Submit Ticket', 'wp-news-audio-pro'); ?>
                    </a>
                </div>
            </div>
            
            <hr>
            
            <h3><?php esc_html_e('Changelog', 'wp-news-audio-pro'); ?></h3>
            <div class="wnap-changelog">
                <h4>1.0.0 (<?php echo esc_html(date('Y-m-d')); ?>)</h4>
                <ul>
                    <li><?php esc_html_e('Initial release', 'wp-news-audio-pro'); ?></li>
                    <li><?php esc_html_e('Multi-language TTS support', 'wp-news-audio-pro'); ?></li>
                    <li><?php esc_html_e('Animated popup and audio player', 'wp-news-audio-pro'); ?></li>
                    <li><?php esc_html_e('License verification system', 'wp-news-audio-pro'); ?></li>
                    <li><?php esc_html_e('Admin settings panel', 'wp-news-audio-pro'); ?></li>
                </ul>
            </div>
            
            <hr>
            
            <h3><?php esc_html_e('Credits', 'wp-news-audio-pro'); ?></h3>
            <ul>
                <li>
                    <strong>Plyr.js</strong> - 
                    <a href="https://plyr.io" target="_blank">https://plyr.io</a>
                </li>
                <li>
                    <strong>eSpeak</strong> - 
                    <a href="http://espeak.sourceforge.net" target="_blank">http://espeak.sourceforge.net</a>
                </li>
            </ul>
        </div>
        <?php
    }
    
    /**
     * AJAX handler for saving settings
     * 
     * @since 1.0.0
     */
    public function ajax_save_settings() {
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
        
        // Get settings
        $settings = isset($_POST['settings']) ? wp_unslash($_POST['settings']) : array();
        
        // Sanitize settings
        $sanitized = $this->sanitize_settings($settings);
        
        // Update settings
        $updated = update_option('wnap_settings', $sanitized);
        
        if ($updated) {
            wp_send_json_success(array(
                'message' => __('Settings saved successfully', 'wp-news-audio-pro')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Error saving settings', 'wp-news-audio-pro')
            ));
        }
    }
}

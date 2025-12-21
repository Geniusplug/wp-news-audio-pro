# Developer Documentation

## WP News Audio Pro - WordPress Plugin

---

## 📚 Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [File Structure](#file-structure)
3. [Class Reference](#class-reference)
4. [Hooks & Filters](#hooks--filters)
5. [Database Schema](#database-schema)
6. [AJAX Endpoints](#ajax-endpoints)
7. [JavaScript API](#javascript-api)
8. [Extending the Plugin](#extending-the-plugin)
9. [Code Standards](#code-standards)
10. [Testing](#testing)

---

## Architecture Overview

### Design Pattern

The plugin uses **Object-Oriented Programming** with the following patterns:

- **Singleton Pattern:** Main plugin class ensures single instance
- **Dependency Injection:** Classes receive dependencies via constructor
- **Hook-based Architecture:** WordPress actions and filters for extensibility
- **MVC-like Separation:** Classes handle specific concerns

### Core Components

```
Main Plugin (wp-news-audio-pro.php)
├── Plugin Core (class-plugin-core.php)
├── TTS Engine (class-tts-engine.php)
├── License Manager (class-license-manager.php)
├── Admin Settings (class-admin-settings.php)
├── Frontend Popup (class-frontend-popup.php)
└── Audio Player (class-audio-player.php)
```

---

## File Structure

```
wp-news-audio-pro/
├── wp-news-audio-pro.php          # Main plugin file, entry point
├── uninstall.php                   # Cleanup on uninstall
├── /includes/                      # PHP classes
│   ├── class-plugin-core.php      # Core functionality
│   ├── class-tts-engine.php       # TTS conversion logic
│   ├── class-license-manager.php  # License verification
│   ├── class-admin-settings.php   # Admin panel UI
│   ├── class-frontend-popup.php   # Popup rendering
│   └── class-audio-player.php     # Player rendering
├── /assets/                        # Frontend assets
│   ├── /css/
│   │   ├── admin-style.css        # Admin styles
│   │   └── frontend-style.css     # Frontend styles
│   ├── /js/
│   │   ├── admin-script.js        # Admin JavaScript
│   │   └── frontend-script.js     # Frontend JavaScript
│   └── /images/                    # Plugin images
├── /languages/                     # Translation files
│   └── wp-news-audio-pro.pot      # Translation template
└── Documentation files
```

---

## Class Reference

### WP_News_Audio_Pro

**File:** `wp-news-audio-pro.php`

Main plugin class using Singleton pattern.

#### Methods

```php
// Get singleton instance
public static function get_instance()

// Load plugin dependencies
private function load_dependencies()

// Initialize WordPress hooks
private function init_hooks()

// Plugin activation
public function activate()

// Plugin deactivation
public function deactivate()

// Load translations
public function load_textdomain()

// Enqueue admin assets
public function enqueue_admin_assets($hook)

// Enqueue frontend assets
public function enqueue_frontend_assets()
```

#### Example Usage

```php
// Get plugin instance
$plugin = wnap();

// Access TTS engine
$tts = $plugin->tts_engine;

// Access license manager
$license = $plugin->license_manager;
```

---

### WNAP_Plugin_Core

**File:** `includes/class-plugin-core.php`

Core functionality and utilities.

#### Static Methods

```php
// Get plugin setting
public static function get_setting($key = '', $default = null)

// Update plugin setting
public static function update_setting($key, $value = null)
```

#### Instance Methods

```php
// Handle audio generation AJAX
public function ajax_generate_audio()

// Add meta box to posts
public function add_audio_meta_box()

// Cleanup old audio files
public function cleanup_old_audio()
```

#### Example Usage

```php
// Get a specific setting
$language = WNAP_Plugin_Core::get_setting('default_language', 'en-US');

// Update multiple settings
WNAP_Plugin_Core::update_setting([
    'speech_speed' => 1.5,
    'volume' => 90
]);
```

---

### WNAP_TTS_Engine

**File:** `includes/class-tts-engine.php`

Text-to-speech conversion engine.

#### Methods

```php
// Generate audio from content
public function generate_audio($post_id, $content, $language, $settings)

// Get audio URL for post
public function get_audio_url($post_id)

// Delete audio for post
public function delete_audio($post_id)

// Clean old audio files
public function clean_old_audio()

// Get supported languages
public function get_supported_languages()
```

#### Example Usage

```php
$tts = new WNAP_TTS_Engine();
$settings = get_option('wnap_settings');

// Generate audio
$audio_url = $tts->generate_audio(
    123,                    // Post ID
    'Your text here...',    // Content
    'en-US',               // Language
    $settings              // Settings array
);

// Get existing audio
$url = $tts->get_audio_url(123);

// Delete audio
$tts->delete_audio(123);

// Get supported languages
$languages = $tts->get_supported_languages();
```

---

### WNAP_License_Manager

**File:** `includes/class-license-manager.php`

License verification and management.

#### Methods

```php
// Verify purchase code
public function verify_purchase_code($code)

// Activate license
public function activate_license($code, $domain)

// Deactivate license
public function deactivate_license()

// Check license status
public function check_license_status()

// Check if license is valid
public function is_license_valid()

// Get license data
public function get_license_data()
```

#### Example Usage

```php
$license = new WNAP_License_Manager();

// Verify purchase code
$result = $license->verify_purchase_code('abc123-def456-ghi789');

if (!is_wp_error($result)) {
    // Activate license
    $license->activate_license('abc123-def456-ghi789', $_SERVER['HTTP_HOST']);
}

// Check if valid
if ($license->is_license_valid()) {
    // License is active
}
```

---

### WNAP_Admin_Settings

**File:** `includes/class-admin-settings.php`

Admin panel and settings management.

#### Methods

```php
// Add admin menu
public function add_admin_menu()

// Register settings
public function register_settings()

// Sanitize settings
public function sanitize_settings($input)

// Render settings page
public function render_settings_page()
```

#### Tabs

- **General:** Basic plugin settings
- **Audio Settings:** TTS configuration
- **License:** License activation
- **About:** Plugin information

---

### WNAP_Frontend_Popup

**File:** `includes/class-frontend-popup.php`

Frontend popup rendering.

#### Methods

```php
// Render popup HTML
public function render_popup()

// Check if popup should be shown
public function should_show_popup()
```

---

### WNAP_Audio_Player

**File:** `includes/class-audio-player.php`

Audio player rendering and management.

#### Methods

```php
// Render player container
public function render_player_container()

// Get player HTML
public function get_player_html($audio_url)
```

---

## Hooks & Filters

### Actions

#### Plugin Lifecycle

```php
// After plugin initialization
do_action('wnap_init');

// Before audio generation
do_action('wnap_before_generate_audio', $post_id, $content);

// After audio generation
do_action('wnap_after_generate_audio', $post_id, $audio_url);

// Before audio deletion
do_action('wnap_before_delete_audio', $post_id);

// After audio deletion
do_action('wnap_after_delete_audio', $post_id);
```

#### Example Usage

```php
// Log audio generation
add_action('wnap_after_generate_audio', function($post_id, $audio_url) {
    error_log("Audio generated for post {$post_id}: {$audio_url}");
}, 10, 2);
```

---

### Filters

#### Settings

```php
// Modify default settings
apply_filters('wnap_default_settings', $settings);

// Filter settings before save
apply_filters('wnap_save_settings', $settings);
```

#### Content Processing

```php
// Modify content before TTS
apply_filters('wnap_process_content', $content, $post_id);

// Filter word limit
apply_filters('wnap_word_limit', 1000);
```

#### Display Control

```php
// Control popup display
apply_filters('wnap_show_popup', $show, $post_id);

// Modify audio URL
apply_filters('wnap_audio_url', $url, $post_id);
```

#### Engine Configuration

```php
// Customize eSpeak voice mapping
apply_filters('wnap_espeak_voice_map', $voice_map);

// Modify TTS engine
apply_filters('wnap_tts_engine', $engine);
```

#### Example Usage

```php
// Increase word limit to 2000
add_filter('wnap_word_limit', function($limit) {
    return 2000;
});

// Don't show popup on pages
add_filter('wnap_show_popup', function($show, $post_id) {
    if (get_post_type($post_id) === 'page') {
        return false;
    }
    return $show;
}, 10, 2);

// Strip specific shortcodes
add_filter('wnap_process_content', function($content, $post_id) {
    $content = preg_replace('/\[gallery.*?\]/', '', $content);
    return $content;
}, 10, 2);
```

---

## Database Schema

### Options Table

Plugin settings stored in `wp_options`:

```sql
-- Plugin settings
option_name: wnap_settings
option_value: {serialized array}

-- License data (encrypted)
option_name: wnap_license
option_value: {encrypted string}

-- Plugin version
option_name: wnap_version
option_value: 1.0.0
```

### Settings Structure

```php
array(
    'enable_popup' => true,
    'auto_play' => false,
    'default_language' => 'en-US',
    'player_position' => 'popup',
    'voice_engine' => 'espeak',
    'speech_speed' => 1.0,
    'pitch' => 1.0,
    'volume' => 80,
    'audio_format' => 'mp3',
    'cache_duration' => 30
)
```

### Post Meta

Audio data stored in `wp_postmeta`:

```sql
-- Audio file URL
meta_key: _wnap_audio_url
meta_value: {audio URL}

-- Audio file path
meta_key: _wnap_audio_file
meta_value: {file path}

-- Generation timestamp
meta_key: _wnap_audio_generated
meta_value: {unix timestamp}
```

---

## AJAX Endpoints

### Generate Audio

**Action:** `wnap_generate_audio`  
**Nonce:** `wnap_frontend_nonce`

```javascript
jQuery.ajax({
    url: ajaxurl,
    type: 'POST',
    data: {
        action: 'wnap_generate_audio',
        nonce: wnapFrontend.nonce,
        post_id: 123
    },
    success: function(response) {
        if (response.success) {
            console.log(response.data.audio_url);
        }
    }
});
```

### Activate License

**Action:** `wnap_activate_license`  
**Nonce:** `wnap_admin_nonce`  
**Capability:** `manage_options`

```javascript
jQuery.ajax({
    url: ajaxurl,
    type: 'POST',
    data: {
        action: 'wnap_activate_license',
        nonce: wnapAdmin.nonce,
        purchase_code: 'abc123'
    },
    success: function(response) {
        if (response.success) {
            console.log('License activated');
        }
    }
});
```

### Deactivate License

**Action:** `wnap_deactivate_license`  
**Nonce:** `wnap_admin_nonce`  
**Capability:** `manage_options`

---

## JavaScript API

### Frontend Object

```javascript
// Available via wnapFrontend global
{
    ajaxUrl: '/wp-admin/admin-ajax.php',
    nonce: 'abc123',
    postId: 123,
    settings: {
        enable_popup: true,
        auto_play: false,
        // ... other settings
    },
    strings: {
        loading: 'Generating audio...',
        error: 'Error generating audio'
    }
}
```

### Admin Object

```javascript
// Available via wnapAdmin global
{
    ajaxUrl: '/wp-admin/admin-ajax.php',
    nonce: 'abc123',
    strings: {
        saving: 'Saving...',
        saved: 'Settings saved successfully',
        error: 'Error saving settings'
    }
}
```

---

## Extending the Plugin

### Add Custom TTS Engine

```php
add_filter('wnap_tts_engine', function($engine_class) {
    return 'My_Custom_TTS_Engine';
});

class My_Custom_TTS_Engine extends WNAP_TTS_Engine {
    public function generate_audio($post_id, $content, $language, $settings) {
        // Custom implementation
    }
}
```

### Add Custom Language

```php
add_filter('wnap_supported_languages', function($languages) {
    $languages['pt-BR'] = 'Portuguese (Brazil)';
    return $languages;
});

add_filter('wnap_espeak_voice_map', function($map) {
    $map['pt-BR'] = 'pt-br';
    return $map;
});
```

### Customize Popup Trigger

```php
add_action('wp_footer', function() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Custom trigger: show popup after 10 seconds
        setTimeout(function() {
            $('.wnap-popup-overlay').fadeIn();
        }, 10000);
    });
    </script>
    <?php
});
```

### Add Custom Post Type Support

```php
add_action('init', function() {
    add_post_type_support('product', 'wnap-audio');
});

add_filter('wnap_show_popup', function($show, $post_id) {
    if (get_post_type($post_id) === 'product') {
        return true;
    }
    return $show;
}, 10, 2);
```

---

## Code Standards

### WordPress Coding Standards

The plugin follows [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/):

- **Indentation:** 4 spaces (no tabs)
- **Line Length:** 100 characters (soft limit)
- **Naming:** snake_case for functions, PascalCase for classes
- **Documentation:** PHPDoc for all functions and classes

### Security

All code implements WordPress security best practices:

- ✅ Direct access protection
- ✅ Nonce verification
- ✅ Capability checks
- ✅ Input sanitization
- ✅ Output escaping
- ✅ Prepared SQL queries

### Translation

All user-facing strings use translation functions:

```php
__('Text', 'wp-news-audio-pro')
_e('Text', 'wp-news-audio-pro')
esc_html__('Text', 'wp-news-audio-pro')
esc_html_e('Text', 'wp-news-audio-pro')
```

---

## Testing

### Manual Testing Checklist

- [ ] Plugin activates without errors
- [ ] Settings save correctly
- [ ] Popup appears on posts
- [ ] Audio generates successfully
- [ ] Player functions properly
- [ ] License activation works
- [ ] Uninstall cleans up data

### Testing with WP-CLI

```bash
# Activate plugin
wp plugin activate wp-news-audio-pro

# Check settings
wp option get wnap_settings

# Generate audio for post 123
wp eval "do_action('wnap_generate_audio', 123);"

# Run cleanup
wp cron event run wnap_cleanup_old_audio

# Deactivate and delete
wp plugin deactivate wp-news-audio-pro
wp plugin delete wp-news-audio-pro
```

### Debug Mode

Enable debugging in wp-config.php:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('SCRIPT_DEBUG', true);
```

---

## Support & Contributing

**Email:** support@example.com  
**GitHub:** https://github.com/Geniusplug/wp-news-audio-pro

---

**Last Updated:** 2025-01-20  
**Version:** 1.0.0  
**License:** GPL v2 or later

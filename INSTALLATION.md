# Installation and Configuration Guide

## WP News Audio Pro - WordPress Plugin

---

## 📋 Table of Contents

1. [Prerequisites](#prerequisites)
2. [Installation](#installation)
3. [Initial Configuration](#initial-configuration)
4. [Server Setup for eSpeak](#server-setup-for-espeak)
5. [WordPress Configuration](#wordpress-configuration)
6. [License Activation](#license-activation)
7. [Plugin Settings](#plugin-settings)
8. [Testing](#testing)
9. [Troubleshooting](#troubleshooting)
10. [Advanced Configuration](#advanced-configuration)

---

## Prerequisites

Before installing WP News Audio Pro, ensure your server meets these requirements:

- **WordPress:** 5.0 or higher
- **PHP:** 7.4 or higher
- **MySQL:** 5.6+ or MariaDB 10.1+
- **Memory Limit:** 128MB minimum (256MB recommended)
- **Disk Space:** 50MB for plugin + storage for audio files
- **Required PHP Extensions:**
  - json
  - mbstring
  - curl (for license verification)

---

## Installation

### Method 1: Via WordPress Admin (Recommended)

1. Log in to your WordPress admin panel
2. Navigate to **Plugins → Add New**
3. Click **Upload Plugin** button
4. Choose the `wp-news-audio-pro.zip` file
5. Click **Install Now**
6. Click **Activate Plugin**

### Method 2: Via FTP

1. Extract the zip file to get the `wp-news-audio-pro` folder
2. Upload the folder to `/wp-content/plugins/`
3. Log in to WordPress admin
4. Navigate to **Plugins**
5. Find **WP News Audio Pro** and click **Activate**

### Method 3: Via SSH (Advanced)

```bash
cd /path/to/wordpress/wp-content/plugins/
unzip /path/to/wp-news-audio-pro.zip
chown -R www-data:www-data wp-news-audio-pro
chmod -R 755 wp-news-audio-pro
```

---

## Initial Configuration

After activation, you'll see a new menu item in WordPress admin:

**News Audio Pro** (with speaker icon)

Click this menu to access the settings panel.

---

## Server Setup for eSpeak

For the best free TTS option, install eSpeak on your server:

### Ubuntu/Debian

```bash
sudo apt-get update
sudo apt-get install espeak
```

### CentOS/RHEL

```bash
sudo yum install espeak
```

### macOS (via Homebrew)

```bash
brew install espeak
```

### Windows

Download and install from: http://espeak.sourceforge.net/

### Verify Installation

```bash
espeak --version
```

You should see version information if installed correctly.

---

## WordPress Configuration

Add these constants to your `wp-config.php` file for production use:

```php
// WP News Audio Pro Configuration
define('WNAP_API_TOKEN', 'your-envato-api-token-here');
define('WNAP_ITEM_ID', 'your-item-id-here');
define('WNAP_EMAIL', 'support@yourcompany.com');
```

### Getting Your Envato API Token

1. Log in to Envato Market
2. Go to **Settings → API Keys**
3. Create a new token with **View and Search Envato Sites** permission
4. Copy the token and add it to `wp-config.php`

---

## License Activation

1. Navigate to **News Audio Pro → License** tab
2. Enter your CodeCanyon purchase code
3. Click **Activate License**
4. Wait for confirmation message

Your license is tied to your domain. To use on multiple domains, purchase additional licenses.

### Where to Find Purchase Code

1. Log in to CodeCanyon
2. Go to **Downloads**
3. Find **WP News Audio Pro**
4. Click **Download** → **License Certificate & Purchase Code**

---

## Plugin Settings

### General Tab

**Enable Popup**
- Toggle to show/hide the popup on single posts
- Default: ON

**Auto-play on Page Load**
- Automatically play audio when available
- Default: OFF (recommended for better UX)

**Default Language**
- Select the TTS language for audio generation
- Options: English (US/UK), Spanish, French, German, Arabic, Hindi, Chinese
- Default: English (US)

**Player Position**
- Choose where the audio player appears
- Options:
  - **Popup Modal** (default) - Appears in centered popup
  - **Fixed Bottom** - Sticky player at bottom of screen
  - **Inline After Content** - Embedded after post content

### Audio Settings Tab

**Voice Engine**
- Select the TTS engine
- Options:
  - **eSpeak** (Free, offline) - Requires server installation
  - **ResponsiveVoice.js** (Free tier) - Client-side generation
  - **Google Cloud TTS** (Paid) - Premium quality

**Speech Speed**
- Adjust playback speed
- Range: 0.5x to 2.0x
- Default: 1.0x (normal speed)

**Pitch**
- Modify voice pitch
- Range: 0.5 to 2.0
- Default: 1.0 (normal pitch)

**Volume**
- Set default volume
- Range: 0 to 100
- Default: 80

**Audio Format**
- Choose file format
- Options: MP3 (recommended), WAV
- Default: MP3

**Cache Duration**
- Days to keep audio files before cleanup
- Range: 1 to 365 days
- Default: 30 days
- Cleanup runs daily via wp-cron

### License Tab

- View activation status
- See purchase code (masked)
- View activated domain
- Activation date
- Deactivate option

### About Tab

- Plugin version
- Author information
- Documentation links
- Support contact
- Changelog

---

## Testing

### 1. Test Popup Display

1. Create or open a published post
2. View the post on frontend (logged out or incognito)
3. Wait 2 seconds or scroll down 30%
4. Popup should appear with two buttons

### 2. Test Audio Generation

1. Click **Listen to Audio** button in popup
2. Loading overlay should appear
3. Audio player should load with the generated audio
4. Click play to verify audio

### 3. Test "Don't Show Again"

1. Check the "Don't show again" box
2. Close the popup
3. Refresh the page
4. Popup should not appear again

### 4. Test Admin Settings

1. Go to **News Audio Pro** settings
2. Change speech speed to 1.5x
3. Click **Save Changes**
4. Generate new audio
5. Verify audio plays at 1.5x speed

### 5. Test License Activation

1. Go to **License** tab
2. Enter a test purchase code
3. Verify activation message
4. Check status badge shows "Active"

---

## Troubleshooting

### Popup Not Appearing

**Symptoms:** Popup doesn't show on posts

**Solutions:**
1. Verify plugin is activated
2. Check "Enable Popup" is ON in settings
3. Clear browser cache and localStorage
4. Check browser console for JavaScript errors
5. Verify you're viewing a single post (not homepage or archive)

### Audio Not Generating

**Symptoms:** Error message when clicking "Listen to Audio"

**Solutions:**

1. **eSpeak not installed:**
   - Run `espeak --version` to verify installation
   - Install eSpeak using instructions above
   - Restart web server after installation

2. **Permission issues:**
   ```bash
   sudo chmod -R 755 /path/to/wordpress/wp-content/uploads/
   sudo chown -R www-data:www-data /path/to/wordpress/wp-content/uploads/
   ```

3. **PHP exec() disabled:**
   - Check `php.ini` for `disable_functions`
   - Remove `exec` from disabled functions
   - Restart PHP-FPM or Apache

4. **Memory limit:**
   - Increase PHP memory limit in `wp-config.php`:
   ```php
   define('WP_MEMORY_LIMIT', '256M');
   ```

### License Activation Failed

**Symptoms:** "Invalid purchase code" error

**Solutions:**
1. Verify purchase code is correct (no extra spaces)
2. Ensure API token is configured in `wp-config.php`
3. Check server can connect to api.envato.com:
   ```bash
   curl -I https://api.envato.com
   ```
4. Verify purchase code matches plugin item ID

### Player Not Showing

**Symptoms:** Audio generates but player doesn't appear

**Solutions:**
1. Check browser console for JavaScript errors
2. Verify Plyr.js CDN is accessible
3. Check for JavaScript conflicts with theme/plugins
4. Try switching to default WordPress theme temporarily

### Audio Files Taking Too Much Space

**Solutions:**
1. Reduce cache duration in settings
2. Manually trigger cleanup:
   ```bash
   wp cron event run wnap_cleanup_old_audio
   ```
3. Delete old files manually:
   ```bash
   find /path/to/wp-content/uploads/news-audio-pro/ -mtime +30 -delete
   ```

---

## Advanced Configuration

### Customize Popup Appearance

Add custom CSS to your theme:

```css
/* Customize popup colors */
#wnap-popup-modal {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

/* Change button styles */
.wnap-btn-audio {
    background: #ff6b6b;
    border-radius: 50px;
}
```

### Disable Popup on Specific Posts

Add to your theme's `functions.php`:

```php
add_filter('wnap_show_popup', function($show, $post_id) {
    // Don't show on post ID 123
    if ($post_id === 123) {
        return false;
    }
    return $show;
}, 10, 2);
```

### Custom Language Mapping

```php
add_filter('wnap_espeak_voice_map', function($voice_map) {
    $voice_map['pt-BR'] = 'pt-br'; // Add Portuguese Brazil
    $voice_map['ja-JP'] = 'ja'; // Add Japanese
    return $voice_map;
});
```

### Programmatic Audio Generation

```php
// Generate audio for a post
$tts = new WNAP_TTS_Engine();
$settings = get_option('wnap_settings');
$post = get_post($post_id);

$audio_url = $tts->generate_audio(
    $post_id,
    $post->post_content,
    'en-US',
    $settings
);

if ($audio_url) {
    echo 'Audio generated: ' . esc_url($audio_url);
}
```

### Schedule Custom Cleanup

```php
// Run cleanup weekly instead of daily
add_action('init', function() {
    wp_clear_scheduled_hook('wnap_cleanup_old_audio');
    if (!wp_next_scheduled('wnap_cleanup_old_audio')) {
        wp_schedule_event(time(), 'weekly', 'wnap_cleanup_old_audio');
    }
});
```

### Debug Mode

Enable debug logging:

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Check logs at: `wp-content/debug.log`

---

## Performance Optimization

### Enable Object Caching

Install Redis or Memcached for better performance:

```php
// Persist license check for 7 days
set_transient('wnap_license_valid', true, WEEK_IN_SECONDS);
```

### CDN Configuration

Serve audio files via CDN:

```php
add_filter('wnap_audio_url', function($url) {
    return str_replace(
        'yoursite.com/wp-content/uploads',
        'cdn.yoursite.com',
        $url
    );
});
```

### Lazy Load Player

The player only loads when needed, but you can optimize further:

```javascript
// Load Plyr.js only when audio button is clicked
// Already implemented in frontend-script.js
```

---

## Security Best Practices

1. **Keep WordPress Updated:** Always use latest version
2. **Use Strong Purchase Codes:** Don't share your license
3. **Secure wp-config.php:** Set proper file permissions (400 or 440)
4. **Regular Backups:** Backup before major updates
5. **SSL Certificate:** Use HTTPS for license verification
6. **Limit Login Attempts:** Install security plugin
7. **Hide wp-admin:** Use security through obscurity when needed

---

## Support

If you encounter issues not covered in this guide:

1. **Email Support:** support@example.com
2. **Documentation:** Check README.txt and README.md
3. **GitHub Issues:** Report bugs on GitHub repository
4. **CodeCanyon Comments:** Ask questions on item page

**Response Time:** Within 24-48 hours (business days)

---

## Uninstallation

To completely remove the plugin:

1. **Deactivate Plugin:**
   - Go to **Plugins**
   - Find **WP News Audio Pro**
   - Click **Deactivate**

2. **Delete Plugin:**
   - Click **Delete** link
   - Confirm deletion

The uninstall script will automatically:
- Delete all plugin settings
- Remove audio files from uploads directory
- Clear scheduled cron jobs
- Delete post metadata
- Remove transients

**Note:** This action is permanent and cannot be undone.

---

## Changelog

See `CHANGELOG.txt` for detailed version history.

---

## License

GPL v2 or later. See `LICENSE.txt` for full license text.

---

**Copyright © 2025 Geniusplug. All rights reserved.**

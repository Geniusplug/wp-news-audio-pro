# 🎙️ WP News Audio Pro

**Convert WordPress posts to audio with animated popup and multi-language TTS support.**

[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPLv2-green.svg)](LICENSE)
[![CodeCanyon](https://img.shields.io/badge/CodeCanyon-Ready-orange.svg)](https://codecanyon.net/)

## ✨ Features

- 🎙️ **Multi-Language TTS** - Support for 8+ languages (English, Spanish, French, German, Arabic, Hindi, Chinese)
- 🎨 **Animated Popup** - Modern, user-friendly interface with smooth transitions
- 🎵 **Audio Player** - Powered by Plyr.js with playback controls
- 🔐 **License System** - Envato API verification for secure licensing
- 📱 **Responsive** - Works perfectly on all devices
- ♿ **Accessible** - WCAG 2.1 compliant with ARIA labels
- 🚀 **Optimized** - Intelligent caching and performance-focused
- 🌐 **Translation Ready** - Fully internationalized with .pot file
- 💾 **Auto Cleanup** - Automatically removes old audio files
- ⚙️ **Easy Setup** - Intuitive admin panel with tabbed interface

## 📦 Installation

### From CodeCanyon

1. Download from CodeCanyon
2. Upload to `/wp-content/plugins/`
3. Activate plugin
4. Enter license key
5. Configure settings

### Manual Installation

```bash
# Clone repository
git clone https://github.com/Geniusplug/wp-news-audio-pro.git

# Copy to WordPress plugins directory
cp -r wp-news-audio-pro /path/to/wordpress/wp-content/plugins/

# Or create symbolic link for development
ln -s $(pwd)/wp-news-audio-pro /path/to/wordpress/wp-content/plugins/
```

## 🔧 Configuration

Navigate to **News Audio Pro** in WordPress admin:

### General Settings
- **Enable Popup** - Toggle popup display on/off
- **Auto-play** - Automatically play audio on page load
- **Default Language** - Select TTS language
- **Player Position** - Choose popup, fixed bottom, or inline

### Audio Settings
- **Voice Engine** - Select eSpeak, ResponsiveVoice, or Google TTS
- **Speech Speed** - Adjust playback speed (0.5x to 2.0x)
- **Pitch** - Modify voice pitch (0.5 to 2.0)
- **Volume** - Set default volume (0 to 100)
- **Audio Format** - Choose MP3 or WAV
- **Cache Duration** - Days to keep audio files

### License
- Enter CodeCanyon purchase code
- Activate for current domain
- View activation status

### About
- Plugin version and changelog
- Author information
- Documentation and support links

## 🛠️ Development

### Prerequisites

- WordPress 5.0+
- PHP 7.4+
- MySQL 5.6+ or MariaDB 10.1+

### Server Requirements for eSpeak

**Ubuntu/Debian:**
```bash
sudo apt-get install espeak
```

**CentOS/RHEL:**
```bash
sudo yum install espeak
```

**macOS:**
```bash
brew install espeak
```

### File Structure

```
/wp-news-audio-pro/
├── wp-news-audio-pro.php          # Main plugin file
├── uninstall.php                   # Cleanup on uninstall
├── README.txt                      # WordPress format
├── README.md                       # GitHub format
├── LICENSE                         # GPL v2 license
├── CHANGELOG.txt                   # Version history
├── /assets/
│   ├── /css/
│   │   ├── admin-style.css        # Admin panel styles
│   │   ├── frontend-style.css     # Popup and player styles
│   ├── /js/
│   │   ├── admin-script.js        # Admin functionality
│   │   ├── frontend-script.js     # Popup and player JS
│   ├── /images/
│   │   ├── icon-128x128.svg       # Plugin icon
│   │   ├── icon-256x256.svg       # Large icon
│   │   ├── banner-772x250.svg     # Banner image
├── /includes/
│   ├── class-plugin-core.php      # Core functionality
│   ├── class-tts-engine.php       # Text-to-speech engine
│   ├── class-license-manager.php  # License verification
│   ├── class-admin-settings.php   # Admin panel
│   ├── class-frontend-popup.php   # Popup modal
│   ├── class-audio-player.php     # Audio player
├── /languages/
│   ├── wp-news-audio-pro.pot      # Translation template
```

## 🎯 Usage

### Basic Usage

1. Activate the plugin
2. Configure settings in admin panel
3. Visit any single post page
4. Popup will appear automatically
5. Click "Listen to Audio" to generate and play

### For Developers

#### Hooks and Filters

```php
// Modify default settings
add_filter('wnap_default_settings', function($settings) {
    $settings['speech_speed'] = 1.5;
    return $settings;
});

// Customize popup display condition
add_filter('wnap_show_popup', function($show, $post_id) {
    // Custom logic here
    return $show;
}, 10, 2);

// Modify processed content before TTS
add_filter('wnap_process_content', function($content, $post_id) {
    // Custom processing
    return $content;
}, 10, 2);
```

#### Programmatic Audio Generation

```php
// Generate audio for a post
$tts_engine = new WNAP_TTS_Engine();
$settings = get_option('wnap_settings');
$content = get_post_field('post_content', $post_id);

$audio_url = $tts_engine->generate_audio(
    $post_id,
    $content,
    'en-US',
    $settings
);
```

## 📝 Requirements

- **WordPress:** 5.0 or higher
- **PHP:** 7.4 or higher
- **MySQL:** 5.6+ or MariaDB 10.1+
- **Optional:** eSpeak for offline TTS

## 🤝 Support

For support, please contact:

- **Email:** support@yoursite.com
- **Documentation:** [https://yoursite.com/docs](https://yoursite.com/docs)
- **GitHub Issues:** [https://github.com/Geniusplug/wp-news-audio-pro/issues](https://github.com/Geniusplug/wp-news-audio-pro/issues)

## 📄 License

This project is licensed under GPL v2 or later - see [LICENSE](LICENSE) file for details.

```
Copyright (C) 2025 Geniusplug

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.
```

## 🙏 Credits

- **Author:** [Geniusplug](https://github.com/Geniusplug)
- **Plyr.js:** [https://plyr.io](https://plyr.io) - Modern HTML5 media player
- **eSpeak:** [http://espeak.sourceforge.net](http://espeak.sourceforge.net) - Open-source TTS
- **WordPress:** [https://wordpress.org](https://wordpress.org) - CMS platform

## 🔄 Changelog

### 1.0.0 (2025-01-20)

**Initial Release**
- Multi-language TTS support (8 languages)
- Animated popup modal
- Modern audio player with Plyr.js
- License verification system
- Admin settings panel
- Smart audio caching
- Responsive design
- Translation ready
- Auto cleanup functionality

## 🚀 Roadmap

- [ ] Support for custom post types
- [ ] Additional TTS engines (Amazon Polly, Azure)
- [ ] Batch audio generation
- [ ] Advanced customization options
- [ ] WordPress Gutenberg block
- [ ] Shortcode support
- [ ] Widget support
- [ ] Analytics and tracking

## 👥 Contributing

This is a commercial plugin. For feature requests or bug reports, please contact support.

## ⚠️ Disclaimer

This plugin requires a valid CodeCanyon license for production use. The license verification system ensures compliance with Envato's licensing terms.

## 📞 Contact

**Geniusplug**
- Website: https://yoursite.com
- Email: support@yoursite.com
- GitHub: [@Geniusplug](https://github.com/Geniusplug)

---

Made with ❤️ by [Geniusplug](https://github.com/Geniusplug)

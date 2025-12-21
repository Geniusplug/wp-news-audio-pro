=== WP News Audio Pro ===
Contributors: Geniusplug
Tags: text-to-speech, tts, audio, accessibility, news, post-to-audio
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Convert WordPress posts to audio with animated popup and multi-language TTS support.

== Description ==

WP News Audio Pro automatically converts your WordPress posts into audio format with professional text-to-speech technology. Features include:

* 🎙️ Multi-language TTS support (8+ languages including English, Spanish, French, German, Arabic, Hindi, Chinese)
* 🎨 Animated popup interface with smooth transitions and modern design
* 🎵 Modern audio player powered by Plyr.js with playback controls
* 🔐 Secure license verification system with Envato API integration
* 📱 Fully responsive design that works on mobile, tablet, and desktop
* ♿ Accessibility-ready with ARIA labels and keyboard navigation
* 🚀 Performance optimized with intelligent audio caching system
* 🌐 Translation ready with .pot file included
* 💾 Auto-cleanup of old audio files to save server space
* ⚙️ Easy-to-use admin settings panel with tabbed interface

= Supported TTS Engines =

* **eSpeak** - Free, open-source, offline TTS engine
* **ResponsiveVoice.js** - Free tier available with attribution
* **Google Cloud TTS** - Premium quality, paid API option

= Supported Languages =

* English (US)
* English (UK)
* Spanish
* French
* German
* Arabic
* Hindi
* Chinese

= Key Features =

**Animated Popup**
Show a beautiful, animated popup on your posts that gives visitors the choice to listen to audio or read the article. The popup appears after 2 seconds or when the user scrolls 30% down the page.

**Audio Player**
Modern HTML5 audio player with playback speed control, volume adjustment, and progress tracking. Remembers playback position for returning visitors.

**Admin Settings**
Comprehensive settings panel with tabs for General settings, Audio configuration, License management, and About information.

**License System**
Secure license verification using Envato/CodeCanyon purchase codes. One license per domain with automatic validation.

**Performance**
Smart caching system stores generated audio files and automatically cleans up old files based on your configured duration.

== Installation ==

1. Purchase the plugin from CodeCanyon
2. Download the plugin zip file
3. Upload to `/wp-content/plugins/` directory
4. Extract the zip file
5. Activate the plugin through the 'Plugins' menu in WordPress
6. Go to 'News Audio Pro' in the WordPress admin menu
7. Enter your license key from CodeCanyon
8. Configure your audio settings
9. Visit any single post to see the popup in action

= Manual Installation =

1. Upload the `wp-news-audio-pro` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure the plugin settings

= Server Requirements =

For best results, install eSpeak on your server:

**Ubuntu/Debian:**
`sudo apt-get install espeak`

**CentOS/RHEL:**
`sudo yum install espeak`

**macOS:**
`brew install espeak`

== Frequently Asked Questions ==

= Does this work with all WordPress themes? =

Yes, this plugin is compatible with all WordPress themes that follow WordPress coding standards. It uses standard WordPress hooks and enqueues scripts properly.

= Which TTS engines are supported? =

The plugin supports three TTS engines:
1. eSpeak (free, offline, requires server installation)
2. ResponsiveVoice.js (free tier available)
3. Google Cloud TTS API (paid, premium quality)

= Can I customize the popup design? =

Yes, you can customize the CSS through custom CSS in your theme or child theme. All elements have specific classes for easy styling.

= How is the audio stored? =

Audio files are stored in `/wp-content/uploads/news-audio-pro/{year}/{month}/` with unique filenames based on post ID and content hash.

= Will the popup annoy my visitors? =

No, the popup has a "Don't show again" option that stores the user's preference in localStorage. Once dismissed with this option, it won't appear again for that visitor.

= Does it work with custom post types? =

Currently, the plugin works with standard WordPress posts. Support for custom post types can be added with custom code using WordPress filters.

= What happens when I update a post? =

The plugin generates a unique filename based on content hash. If you update the post content, a new audio file will be generated on the next visit while the old one will be cleaned up based on your cache duration setting.

= Is the plugin translation ready? =

Yes, the plugin is fully translation ready with all strings properly wrapped in translation functions. A .pot file is included in the `/languages/` directory.

= How do I get support? =

For support, please email support@yoursite.com or use the support forum on CodeCanyon.

== Screenshots ==

1. Animated popup modal with audio/read options
2. Admin settings - General tab
3. Admin settings - Audio configuration tab
4. Admin settings - License activation tab
5. Modern audio player with Plyr.js
6. Mobile responsive design

== Changelog ==

= 1.0.0 =
* Initial release
* Multi-language TTS support (8 languages)
* Animated popup modal with smooth transitions
* Modern audio player with Plyr.js integration
* License verification system with Envato API
* Comprehensive admin settings panel
* Smart audio caching system
* Responsive design for all devices
* Translation ready with .pot file
* Automatic cleanup of old audio files
* Keyboard navigation support
* Dark mode support

== Upgrade Notice ==

= 1.0.0 =
Initial release of WP News Audio Pro.

== License ==

This plugin is licensed under GPL v2 or later.

Copyright (C) 2025 Geniusplug

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

== Credits ==

* **Plyr.js** - Modern HTML5 audio player (https://plyr.io)
* **eSpeak** - Open-source TTS engine (http://espeak.sourceforge.net)
* **WordPress** - Content management system (https://wordpress.org)

== Support ==

For technical support, feature requests, or bug reports:
* Email: support@yoursite.com
* Documentation: https://yoursite.com/docs
* GitHub: https://github.com/Geniusplug/wp-news-audio-pro

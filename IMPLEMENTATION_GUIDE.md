# Implementation Guide - WP News Audio Pro v1.0.0

## Overview
This document outlines the complete system overhaul implementing 5 TTS engines, animated floating button UI, and advanced display settings.

## Key Features Implemented

### 1. Five TTS Engines (3 Free + 2 Paid)

#### A. Web Speech API (Default - Recommended)
- **Type:** Browser-based, Client-side
- **Cost:** FREE, Unlimited
- **API Required:** No
- **Implementation:** `class-tts-engine.php` → `use_web_speech_api()`
- **Frontend:** `frontend-script.js` → Web Speech API integration
- **Advantages:**
  - No server resources needed
  - Works offline
  - No API keys required
  - Unlimited usage
  - Multi-language support
  - Low latency

#### B. ResponsiveVoice.js
- **Type:** Client-side JavaScript library
- **Cost:** FREE (5,000 requests/day)
- **API Required:** No
- **Implementation:** `class-tts-engine.php` → `use_responsive_voice()`
- **Advantages:**
  - High-quality voices
  - Easy integration
  - No server processing

#### C. eSpeak
- **Type:** Server-side command-line TTS
- **Cost:** FREE, Unlimited
- **API Required:** No (requires server installation)
- **Implementation:** `class-tts-engine.php` → `use_espeak()`
- **Installation:** `sudo apt-get install espeak`
- **Advantages:**
  - Completely offline
  - No API dependencies
  - Unlimited usage
  - Audio file caching

#### D. Google Cloud Text-to-Speech
- **Type:** Cloud API
- **Cost:** PAID ($4 per 1 million characters)
- **API Required:** Yes
- **Implementation:** `class-tts-engine.php` → `use_google_tts()`
- **Setup:** Requires Google Cloud API key
- **Advantages:**
  - High-quality neural voices
  - WaveNet technology
  - Multiple languages
  - Natural-sounding speech

#### E. Amazon Polly
- **Type:** Cloud API
- **Cost:** PAID ($4 per 1 million characters)
- **API Required:** Yes
- **Implementation:** `class-tts-engine.php` → `use_amazon_polly()`
- **Setup:** Requires AWS credentials
- **Advantages:**
  - Neural voices available
  - Multiple languages
  - SSML support
  - High-quality output

### 2. Animated Floating Button UI

#### Design Specifications
- **Position:** Fixed bottom-right (30px margins)
- **Size:** 60px × 60px (closed state)
- **Expanded:** 320px width (open state)
- **Colors:** Gradient purple/blue (#667eea to #764ba2)
- **Animations:**
  - Pulse effect (2s infinite)
  - Hover scale (1.1x + 5deg rotation)
  - Audio wave icon animation
  - Expand animation (cubic-bezier)

#### Button States

**State 1: Closed**
- Small circular button
- Audio icon (🎧)
- Pulse animation
- Click → Opens player

**State 2: Open**
- Expands to mini player (320px)
- Header with title
- Close button (✕)
- Play/Pause/Stop controls
- Progress bar with time
- Speed controls (0.5x, 1x, 1.5x, 2x)
- "Hide Forever" button

**State 3: Hidden**
- Stored in localStorage
- Persists across sessions
- Can be re-enabled in settings

#### Implementation Files

**HTML:** `includes/class-frontend-popup.php`
```php
public function render_floating_button()
```

**CSS:** `assets/css/frontend-style.css`
- `.wnap-floating-button`
- `.wnap-fab-closed`
- `.wnap-fab-open`
- Animations: `fabPulse`, `audioWave`, `fabExpand`

**JavaScript:** `assets/js/frontend-script.js`
- `initFloatingButton()`
- `openFloatingButton()`
- `closeFloatingButton()`
- `playAudio()` (Web Speech API)
- `pauseAudio()`
- `stopAudio()`
- `setSpeed()`
- `updateProgressBar()`

### 3. Display Settings System

#### Show On Options
- **Posts:** Checkbox to enable on blog posts
- **Pages:** Checkbox to enable on static pages
- **Home Page:** Checkbox to enable on homepage

#### Exclude Options
- **By Page ID:** Comma-separated IDs (e.g., "5, 12, 34")
- **By URL Pattern:** One pattern per line (e.g., "/cart/", "/checkout/")

#### Implementation

**Admin Settings:** `includes/class-admin-settings.php`
```php
private function render_general_tab($settings)
```

**Frontend Logic:** `includes/class-frontend-popup.php`
```php
public function should_show_button()
```

**Asset Loading:** `wp-news-audio-pro.php`
```php
public function enqueue_frontend_assets()
```

### 4. Admin Settings Panel

#### TTS Engine Selector
- Dropdown with 5 engine options
- Default: Web Speech API
- Dynamic API fields based on selection

#### Dynamic API Fields

**Google Cloud TTS:**
- API Key field (text input)
- Link to Google Cloud Console

**Amazon Polly:**
- AWS Access Key (text input)
- AWS Secret Key (password input)
- AWS Region (dropdown: us-east-1, us-west-2, eu-west-1)

**JavaScript Toggle:**
```javascript
$('#wnap_tts_engine').on('change', function() {
    var engine = $(this).val();
    $('.wnap-api-field').hide();
    $('.wnap-api-field[data-engine="' + engine + '"]').show();
}).trigger('change');
```

#### Audio Settings
- **Speech Speed:** Range slider (0.5 to 2.0)
- **Pitch:** Range slider (0.5 to 2.0)
- **Volume:** Range slider (0 to 100)
- **Cache Duration:** Number input (1-365 days)

## File Changes Summary

### Modified Files

1. **`includes/class-tts-engine.php`**
   - Added 5 engine methods
   - Removed old duplicate methods
   - Added `get_engines()` method
   - Updated `generate_audio()` signature

2. **`includes/class-admin-settings.php`**
   - Added TTS engine dropdown
   - Added dynamic API fields
   - Added display settings section
   - Updated `sanitize_settings()`
   - Registered API key options

3. **`includes/class-frontend-popup.php`**
   - Added `should_show_button()` method
   - Added `render_floating_button()` method
   - Implemented display logic

4. **`assets/css/frontend-style.css`**
   - Added 200+ lines for floating button
   - Animations: fabPulse, audioWave, fabExpand
   - Responsive breakpoints
   - Dark mode support

5. **`assets/js/frontend-script.js`**
   - Added Web Speech API integration
   - Added floating button functionality
   - Play/Pause/Stop controls
   - Speed controls
   - Progress bar updates
   - localStorage management

6. **`wp-news-audio-pro.php`**
   - Updated `enqueue_frontend_assets()`
   - Added display settings check
   - Updated default settings in `activate()`

### New Files

1. **`TESTING.md`**
   - Comprehensive testing guide
   - All test cases documented
   - Expected results
   - Troubleshooting guide

2. **`IMPLEMENTATION_GUIDE.md`** (this file)
   - Complete documentation
   - Implementation details
   - Usage instructions

## Configuration Options

### Default Settings
```php
$default_settings = array(
    'enable_popup' => true,
    'auto_play' => false,
    'default_language' => 'en-US',
    'player_position' => 'popup',
    'tts_engine' => 'web_speech',  // NEW
    'voice_engine' => 'espeak',
    'speech_speed' => 1.0,
    'pitch' => 1.0,
    'volume' => 80,
    'audio_format' => 'mp3',
    'cache_duration' => 30,
    'show_on_posts' => true,        // NEW
    'show_on_pages' => true,        // NEW
    'show_on_home' => false,        // NEW
    'exclude_pages' => '',          // NEW
    'exclude_urls' => '',           // NEW
);
```

### WordPress Options
```php
// Main settings
get_option('wnap_settings')

// API Keys (separate for security)
get_option('wnap_google_tts_api_key')
get_option('wnap_aws_access_key')
get_option('wnap_aws_secret_key')
get_option('wnap_aws_region')
```

### LocalStorage Keys
```javascript
// Hide forever state
localStorage.getItem('wnap_fab_hidden')
localStorage.setItem('wnap_fab_hidden', 'true')

// Don't show popup again
localStorage.getItem('wnap_dont_show')
```

## Usage Instructions

### For End Users

1. **Activate Plugin**
   - Install and activate
   - License auto-activates on localhost
   - Enter purchase code on live server

2. **Configure TTS Engine**
   - Go to **News Audio Pro > Audio Settings**
   - Select preferred TTS engine
   - Enter API keys if needed (Google/AWS)
   - Adjust speech speed, pitch, volume

3. **Configure Display Settings**
   - Go to **News Audio Pro > General**
   - Check where to show button (Posts/Pages/Home)
   - Add excluded page IDs
   - Add excluded URL patterns

4. **Test on Frontend**
   - Visit configured pages
   - Floating button appears bottom-right
   - Click to open player
   - Click Play to hear audio
   - Adjust speed as needed

### For Developers

#### Extending TTS Engines

Add a new engine in `includes/class-tts-engine.php`:

```php
private function use_custom_engine($post_id, $content, $settings) {
    // Your implementation
    return array(
        'type' => 'file', // or 'web_speech', 'responsive_voice'
        'url' => $audio_url,
        // or
        'text' => $content,
        'voice' => $voice_name,
    );
}
```

Update `generate_audio()` switch statement:
```php
case 'custom_engine':
    return $this->use_custom_engine($post_id, $text, $settings);
```

#### Filtering Content

Hook into content processing:
```php
add_filter('wnap_espeak_voice_map', function($voice_map) {
    $voice_map['pt-BR'] = 'pt-br';
    return $voice_map;
});
```

#### Custom Display Logic

Override button display:
```php
add_filter('wnap_should_show_button', function($should_show) {
    // Custom logic
    if (is_user_logged_in()) {
        return true;
    }
    return $should_show;
});
```

## Architecture Decisions

### Why Web Speech API as Default?

1. **No Setup Required:** Works immediately, no configuration
2. **Free & Unlimited:** No API costs or usage limits
3. **Browser Native:** Leverages built-in capabilities
4. **Good Quality:** Modern browsers have quality voices
5. **Low Latency:** No server processing time
6. **Privacy Friendly:** Content stays in browser

### Why Separate API Key Options?

1. **Security:** Not stored in serialized settings array
2. **Flexibility:** Can be managed independently
3. **Easier Updates:** Change keys without affecting other settings
4. **Better Organization:** Clear separation of concerns

### Why LocalStorage for Hide Forever?

1. **User Privacy:** No server tracking needed
2. **Instant Response:** No AJAX calls required
3. **Persistent:** Survives page reloads
4. **Client-Side:** No database queries

### Why should_show_button() Method?

1. **Centralized Logic:** One place to check all conditions
2. **Easy Testing:** Can mock and test independently
3. **Extensible:** Can add more conditions easily
4. **Maintainable:** Clear separation of concerns

## Performance Optimizations

### Asset Loading
- Scripts only load on configured pages
- Conditional loading based on display settings
- Deferred script execution
- CSS minification ready

### Audio Caching
- Server-side engines cache generated files
- Cache duration configurable (1-365 days)
- Automatic cleanup of old files
- Prevents redundant API calls

### Browser Optimization
- Web Speech API uses no bandwidth
- Progress bar updates throttled
- Event listeners properly removed
- No memory leaks

## Security Considerations

### Input Sanitization
```php
sanitize_text_field()
sanitize_textarea_field()
esc_attr()
esc_html()
esc_url()
```

### Output Escaping
```php
echo esc_html($variable);
echo esc_attr($attribute);
echo esc_url($url);
```

### Nonce Verification
```php
wp_verify_nonce($nonce, 'wnap_frontend_nonce');
```

### API Key Storage
- Separate WordPress options
- Not exposed in JavaScript
- Server-side validation
- Capability checks

## Browser Compatibility

### Web Speech API
- Chrome/Edge: ✅ Full support
- Firefox: ✅ Full support
- Safari: ✅ Full support
- Opera: ✅ Full support
- IE11: ❌ Not supported

### CSS Features
- Flexbox: ✅ All modern browsers
- CSS Grid: ✅ All modern browsers
- Animations: ✅ All modern browsers
- Backdrop-filter: ⚠️ Safari requires -webkit prefix

### JavaScript Features
- ES5+: ✅ All browsers
- LocalStorage: ✅ All browsers
- speechSynthesis: ✅ Modern browsers

## Troubleshooting

### Issue: Button not appearing
**Solutions:**
1. Check license activation
2. Verify display settings enabled
3. Check page not excluded
4. Clear browser cache
5. Check console for errors

### Issue: Web Speech API not working
**Solutions:**
1. Use HTTPS (required for API)
2. Check browser compatibility
3. Test in different browser
4. Check browser permissions

### Issue: eSpeak not generating audio
**Solutions:**
1. Install eSpeak: `sudo apt-get install espeak`
2. Verify installation: `which espeak`
3. Check file permissions
4. Check uploads directory writable

### Issue: API keys not saving
**Solutions:**
1. Verify WordPress settings API
2. Check user capabilities
3. Clear WordPress cache
4. Check PHP error logs

## Future Enhancements

### Potential Features
- [ ] Voice selection dropdown
- [ ] Playlist for multiple posts
- [ ] Download audio option
- [ ] Share audio link
- [ ] Analytics integration
- [ ] Custom voice mapping
- [ ] SSML support
- [ ] Background playback

### Potential Engines
- [ ] Microsoft Azure TTS
- [ ] IBM Watson TTS
- [ ] Festival TTS
- [ ] Flite TTS
- [ ] More open-source engines

## Support & Resources

### Documentation
- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [Web Speech API](https://developer.mozilla.org/en-US/docs/Web/API/Web_Speech_API)
- [Google Cloud TTS](https://cloud.google.com/text-to-speech/docs)
- [Amazon Polly](https://docs.aws.amazon.com/polly/)

### Contact
- **Email:** info.geniusplugtechnology@gmail.com
- **WhatsApp:** +880 1761 487193
- **Support Portal:** https://geniusplug.com/support/

### GitHub
- **Repository:** https://github.com/Geniusplug/wp-news-audio-pro
- **Issues:** Report bugs and feature requests
- **Pull Requests:** Contributions welcome

## Changelog

### Version 1.0.0 (2025-12-21)
- ✅ Implemented 5 TTS engines
- ✅ Added animated floating button UI
- ✅ Added display settings system
- ✅ Added page/post include/exclude
- ✅ Added Web Speech API integration
- ✅ Added dynamic API fields
- ✅ Improved admin settings panel
- ✅ Enhanced mobile responsiveness
- ✅ Added comprehensive documentation
- ✅ Fixed license system
- ✅ Improved security

## License

This plugin is licensed under GPL v2 or later.

## Credits

- **Development:** Geniusplug
- **TTS Engines:** Google, Amazon, eSpeak, ResponsiveVoice
- **Audio Player:** Plyr.js
- **Icons:** Unicode Emoji

---

**Last Updated:** December 21, 2025  
**Version:** 1.0.0  
**Author:** Geniusplug

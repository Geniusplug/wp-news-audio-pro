# Testing Guide - WP News Audio Pro

## Overview
This guide covers testing the complete system overhaul including 5 TTS engines, floating button UI, and page/post include/exclude system.

## Prerequisites
- WordPress 5.0+
- PHP 7.4+
- Valid license (or test license for localhost)

## 1. License System Testing

### Test on Localhost
1. Navigate to **News Audio Pro > License** in WordPress admin
2. The test license should automatically activate on localhost
3. Verify you see "✅ Test License Active" with 90 days expiration
4. Check that plugin features are accessible

### Test on Live Server
1. Navigate to **News Audio Pro > License**
2. Enter a valid CodeCanyon purchase code
3. Click "Activate License"
4. Verify license activation success message
5. Confirm features are now accessible

## 2. TTS Engine Testing

### A. Web Speech API (Default - FREE, Unlimited)

**Setup:**
1. Go to **News Audio Pro > Audio Settings**
2. Select "Web Speech API (FREE - Unlimited) ⭐ RECOMMENDED"
3. Click "Save Changes"

**Testing:**
1. Visit any post on your site
2. Wait for the floating button to appear (bottom-right corner)
3. Click the floating button (🎧 icon)
4. Click the **Play** button (▶)
5. **Expected:** Browser should start reading the article content aloud
6. Test **Pause** button (⏸)
7. Test **Stop** button (⏹)
8. Test speed controls (0.5x, 1x, 1.5x, 2x)
9. **Expected:** Speed changes should take effect immediately

**Browser Support:**
- Chrome/Edge: Full support ✅
- Firefox: Full support ✅
- Safari: Full support ✅
- Opera: Full support ✅

### B. eSpeak (FREE, Unlimited, Server-side)

**Setup:**
1. Install eSpeak on server: `sudo apt-get install espeak`
2. Verify installation: `which espeak`
3. Go to **News Audio Pro > Audio Settings**
4. Select "eSpeak (FREE - Unlimited, Server-side)"
5. Click "Save Changes"

**Testing:**
1. Visit any post
2. Click floating button
3. Click **Play**
4. **Expected:** Audio file generated on server and played
5. Check audio directory: `wp-content/uploads/news-audio-pro/`
6. Verify WAV files are created

### C. ResponsiveVoice.js (FREE, 5,000/day)

**Setup:**
1. Go to **News Audio Pro > Audio Settings**
2. Select "ResponsiveVoice.js (FREE - 5,000/day)"
3. Click "Save Changes"

**Testing:**
1. Visit any post
2. Click floating button
3. Click **Play**
4. **Expected:** ResponsiveVoice.js API used (client-side)

### D. Google Cloud TTS (PAID)

**Setup:**
1. Get API key from [Google Cloud Console](https://console.cloud.google.com/apis/credentials)
2. Go to **News Audio Pro > Audio Settings**
3. Select "Google Cloud TTS (PAID - API Key Required)"
4. Enter your **Google TTS API Key**
5. Click "Save Changes"

**Testing:**
1. Visit any post
2. Click floating button
3. Click **Play**
4. **Expected:** Audio generated via Google Cloud TTS API
5. Verify MP3 files created in uploads directory

### E. Amazon Polly (PAID)

**Setup:**
1. Get AWS credentials from AWS Console
2. Go to **News Audio Pro > Audio Settings**
3. Select "Amazon Polly (PAID - AWS Credentials Required)"
4. Enter:
   - **AWS Access Key**
   - **AWS Secret Key**
   - **AWS Region** (e.g., us-east-1)
5. Click "Save Changes"

**Testing:**
1. Visit any post
2. Click floating button
3. Click **Play**
4. **Expected:** Audio generated via Amazon Polly
5. Verify MP3 files with neural voices

## 3. Floating Button UI Testing

### Visual Design
- [ ] Button appears in bottom-right corner
- [ ] Circular shape with 60px diameter
- [ ] Gradient background (purple/blue)
- [ ] Pulse animation (scale 1 to 1.05)
- [ ] Audio icon (🎧) with wave animation
- [ ] Hover effect (scale 1.1 + rotate 5deg)

### Button States

**State 1: Closed (Initial)**
- [ ] Small circular button visible
- [ ] Pulse animation active
- [ ] Click opens player

**State 2: Open (Playing/Controls)**
- [ ] Expands to 320px wide
- [ ] Shows header with title "Listen to Article"
- [ ] Close button (✕) visible
- [ ] Play/Pause/Stop buttons visible
- [ ] Progress bar visible
- [ ] Speed controls visible (0.5x, 1x, 1.5x, 2x)
- [ ] "Hide Forever" button visible

**State 3: Hidden (User dismissed)**
- [ ] Click "Hide Forever"
- [ ] Button disappears
- [ ] Refresh page - button should not reappear
- [ ] Check localStorage: `wnap_fab_hidden` = `true`

### Controls Testing

**Play/Pause/Stop:**
1. Click **Play** (▶)
   - [ ] Button changes to **Pause** (⏸)
   - [ ] Audio starts playing
   - [ ] Progress bar fills
2. Click **Pause** (⏸)
   - [ ] Button changes to **Play** (▶)
   - [ ] Audio pauses
   - [ ] Progress bar pauses
3. Click **Stop** (⏹)
   - [ ] Audio stops
   - [ ] Progress resets to 0:00

**Speed Controls:**
- [ ] Click **0.5x** - Audio plays at half speed
- [ ] Click **1x** - Audio plays at normal speed (active by default)
- [ ] Click **1.5x** - Audio plays at 1.5x speed
- [ ] Click **2x** - Audio plays at double speed

**Progress Bar:**
- [ ] Shows current time / total time
- [ ] Fills progressively as audio plays
- [ ] Accurate time display (MM:SS format)

### Responsive Design

**Desktop (>768px):**
- [ ] Button at bottom-right (30px margin)
- [ ] Expanded width: 320px
- [ ] All controls visible

**Tablet (≤768px):**
- [ ] Button at bottom-right (20px margin)
- [ ] Expanded width: calc(100vw - 40px)
- [ ] Max width: 320px

**Mobile (≤480px):**
- [ ] Button at bottom-right (15px margin)
- [ ] Expanded width: calc(100vw - 30px)
- [ ] Button size: 50px (smaller)

## 4. Display Settings Testing

### Show On Pages

**Setup:**
1. Go to **News Audio Pro > General > Display Settings**
2. Check/uncheck options:
   - [ ] Posts
   - [ ] Pages
   - [ ] Home Page

**Test: Show on Posts**
1. Enable "Posts"
2. Visit a blog post
3. **Expected:** Floating button appears

**Test: Show on Pages**
1. Enable "Pages"
2. Visit a static page
3. **Expected:** Floating button appears

**Test: Show on Home Page**
1. Enable "Home Page"
2. Visit homepage
3. **Expected:** Floating button appears

### Exclude Pages by ID

**Setup:**
1. Go to **News Audio Pro > General > Display Settings**
2. Enter page IDs: `5, 12, 34`
3. Click "Save Changes"

**Testing:**
1. Visit page with ID 5
   - [ ] Floating button does NOT appear
2. Visit page with ID 12
   - [ ] Floating button does NOT appear
3. Visit page with different ID
   - [ ] Floating button DOES appear

### Exclude by URL Pattern

**Setup:**
1. Enter URL patterns (one per line):
   ```
   /cart/
   /checkout/
   /account/
   ```
2. Click "Save Changes"

**Testing:**
1. Visit `/cart/` page
   - [ ] Floating button does NOT appear
2. Visit `/checkout/` page
   - [ ] Floating button does NOT appear
3. Visit `/blog/` page
   - [ ] Floating button DOES appear

## 5. Admin Settings Testing

### TTS Engine Selector

**Test Dynamic Fields:**
1. Select "Web Speech API"
   - [ ] No API fields shown
2. Select "Google Cloud TTS"
   - [ ] Google TTS API Key field appears
3. Select "Amazon Polly"
   - [ ] AWS Access Key field appears
   - [ ] AWS Secret Key field appears
   - [ ] AWS Region dropdown appears

### Audio Settings

**Speech Speed:**
- [ ] Drag slider (0.5 to 2.0)
- [ ] Value updates in real-time
- [ ] Audio plays at selected speed

**Pitch:**
- [ ] Drag slider (0.5 to 2.0)
- [ ] Value updates in real-time
- [ ] Audio pitch changes

**Volume:**
- [ ] Drag slider (0 to 100)
- [ ] Value updates in real-time
- [ ] Audio volume changes

**Cache Duration:**
- [ ] Enter number of days (1-365)
- [ ] Old audio files cleaned after duration

## 6. Accessibility Testing

**Keyboard Navigation:**
- [ ] Tab through all controls
- [ ] Enter/Space to activate buttons
- [ ] Escape to close player
- [ ] Focus indicators visible

**Screen Readers:**
- [ ] All buttons have aria-label
- [ ] Content structure is semantic
- [ ] Announces player state changes

## 7. Performance Testing

**Page Load:**
- [ ] Floating button doesn't block page load
- [ ] Assets load asynchronously
- [ ] No console errors

**Memory:**
- [ ] No memory leaks after playing audio
- [ ] Audio stops when button hidden
- [ ] localStorage managed properly

## 8. Cross-Browser Testing

- [ ] Chrome (Windows/Mac/Linux)
- [ ] Firefox (Windows/Mac/Linux)
- [ ] Safari (Mac/iOS)
- [ ] Edge (Windows)
- [ ] Opera (Windows/Mac)
- [ ] Mobile browsers (iOS Safari, Chrome Mobile)

## 9. Security Testing

- [ ] API keys stored securely
- [ ] Nonce verification on AJAX requests
- [ ] Input sanitization
- [ ] Output escaping
- [ ] No XSS vulnerabilities
- [ ] No SQL injection vulnerabilities

## 10. License System Verification

**Localhost:**
- [ ] Test license auto-activates
- [ ] 90-day expiration shown
- [ ] Features accessible

**Live Server:**
- [ ] Purchase code required
- [ ] Valid code activates license
- [ ] Invalid code shows error
- [ ] License deactivation works

## Expected Results Summary

✅ **Working:**
- All 5 TTS engines functional
- Floating button appears on configured pages
- Play/Pause/Stop controls work
- Speed controls work
- Progress bar updates
- Hide forever option works
- Display settings respected
- Exclude pages/URLs work
- Dynamic API fields show/hide
- Mobile responsive
- License system works

## Troubleshooting

### Floating Button Not Appearing
1. Check license is active
2. Check display settings enabled
3. Check page not excluded
4. Check browser console for errors
5. Clear cache and localStorage

### Web Speech API Not Working
1. Check browser support
2. Check HTTPS (required for speech API)
3. Check microphone permissions (if needed)
4. Try different browser

### eSpeak Not Generating Audio
1. Verify eSpeak installed: `which espeak`
2. Install: `sudo apt-get install espeak`
3. Check server permissions
4. Check uploads directory writable

### API Keys Not Saving
1. Check WordPress settings API
2. Verify nonce
3. Check PHP errors
4. Clear cache

## Contact Support

**Email:** info.geniusplugtechnology@gmail.com  
**WhatsApp:** +880 1761 487193  
**Support Portal:** https://geniusplug.com/support/

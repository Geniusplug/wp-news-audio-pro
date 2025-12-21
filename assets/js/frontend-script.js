/**
 * Frontend Scripts
 * 
 * JavaScript for popup and audio player on the frontend
 * 
 * @package WP_News_Audio_Pro
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    var player = null;
    var audioUrl = null;
    
    $(document).ready(function() {
        
        // Check if popup should be shown
        if (shouldShowPopup()) {
            showPopup();
        }
        
        // Close popup
        $('.wnap-close').on('click', function() {
            hidePopup();
            saveDontShowPreference();
        });
        
        // Close on overlay click
        $('.wnap-popup-overlay').on('click', function(e) {
            if ($(e.target).is('.wnap-popup-overlay')) {
                hidePopup();
            }
        });
        
        // Close on ESC key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $('.wnap-popup-overlay').is(':visible')) {
                hidePopup();
            }
        });
        
        // Listen to Audio button
        $('.wnap-btn-audio').on('click', function() {
            var postId = $(this).data('post-id');
            loadAndPlayAudio(postId);
            hidePopup();
        });
        
        // Read Article button
        $('.wnap-btn-read').on('click', function() {
            hidePopup();
            saveDontShowPreference();
        });
        
        // Don't show again checkbox
        $('#wnap-dont-show').on('change', function() {
            if ($(this).is(':checked')) {
                localStorage.setItem('wnap_dont_show', 'true');
            } else {
                localStorage.removeItem('wnap_dont_show');
            }
        });
        
        // Close player
        $('.wnap-player-close').on('click', function() {
            hidePlayer();
        });
        
        // Minimize player
        $('.wnap-minimize-btn').on('click', function() {
            minimizePlayer();
        });
        
        // Restore minimized player
        $(document).on('click', '.wnap-player-minimized', function() {
            restorePlayer();
        });
    });
    
    /**
     * Check if popup should be shown
     */
    function shouldShowPopup() {
        // Check localStorage
        if (localStorage.getItem('wnap_dont_show') === 'true') {
            return false;
        }
        
        // Check if popup exists
        if ($('.wnap-popup-overlay').length === 0) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Show popup with animation
     */
    function showPopup() {
        var $overlay = $('.wnap-popup-overlay');
        
        // Delay for page load
        setTimeout(function() {
            $overlay.fadeIn(300, function() {
                $overlay.addClass('wnap-show');
            });
        }, 2000);
        
        // Alternative: Show on scroll (30%)
        var hasShown = false;
        $(window).on('scroll', function() {
            if (hasShown) return;
            
            var scrollPercent = ($(window).scrollTop() / ($(document).height() - $(window).height())) * 100;
            
            if (scrollPercent >= 30 && !$overlay.is(':visible')) {
                hasShown = true;
                $overlay.fadeIn(300, function() {
                    $overlay.addClass('wnap-show');
                });
            }
        });
    }
    
    /**
     * Hide popup
     */
    function hidePopup() {
        var $overlay = $('.wnap-popup-overlay');
        $overlay.removeClass('wnap-show');
        
        setTimeout(function() {
            $overlay.fadeOut(300);
        }, 300);
    }
    
    /**
     * Save don't show preference
     */
    function saveDontShowPreference() {
        if ($('#wnap-dont-show').is(':checked')) {
            localStorage.setItem('wnap_dont_show', 'true');
        }
    }
    
    /**
     * Load and play audio
     */
    function loadAndPlayAudio(postId) {
        showLoadingOverlay();
        
        // AJAX request to generate/get audio
        $.ajax({
            url: wnapFrontend.ajaxUrl,
            type: 'POST',
            data: {
                action: 'wnap_generate_audio',
                nonce: wnapFrontend.nonce,
                post_id: postId
            },
            success: function(response) {
                hideLoadingOverlay();
                
                if (response.success) {
                    audioUrl = response.data.audio_url;
                    initPlayer(audioUrl);
                    showPlayer();
                } else {
                    showNotification(response.data.message || wnapFrontend.strings.error, 'error');
                }
            },
            error: function() {
                hideLoadingOverlay();
                showNotification(wnapFrontend.strings.error || 'An error occurred', 'error');
            }
        });
    }
    
    /**
     * Initialize audio player with Plyr.js
     */
    function initPlayer(url) {
        var $player = $('#wnap-audio-player');
        
        // Set audio source
        $player.find('source').attr('src', url);
        $player[0].load();
        
        // Initialize Plyr if available
        if (typeof Plyr !== 'undefined') {
            player = new Plyr('#wnap-audio-player', {
                controls: ['play-large', 'play', 'progress', 'current-time', 'duration', 'mute', 'volume', 'settings'],
                settings: ['speed'],
                speed: { selected: 1, options: [0.5, 0.75, 1, 1.25, 1.5, 2] },
                volume: wnapFrontend.settings.volume ? wnapFrontend.settings.volume / 100 : 0.8
            });
            
            // Store player globally for keyboard shortcuts - use namespace to avoid conflicts
            window.WNAP = window.WNAP || {};
            window.WNAP.player = player;
            
            // Restore playback position
            var savedPosition = localStorage.getItem('wnap_playback_position_' + wnapFrontend.postId);
            if (savedPosition) {
                player.currentTime = parseFloat(savedPosition);
            }
            
            // Save playback position
            player.on('timeupdate', function() {
                localStorage.setItem('wnap_playback_position_' + wnapFrontend.postId, player.currentTime);
                updateTimeRemaining();
            });
            
            // Auto-play if enabled
            if (wnapFrontend.settings.auto_play) {
                player.play();
            }
            
            // Clear position on ended
            player.on('ended', function() {
                localStorage.removeItem('wnap_playback_position_' + wnapFrontend.postId);
            });
            
            // Update playing state for minimized view
            player.on('play', function() {
                $('#wnap-player-container').addClass('wnap-playing');
            });
            
            player.on('pause', function() {
                $('#wnap-player-container').removeClass('wnap-playing');
            });
        } else {
            // Fallback to native audio controls
            if (wnapFrontend.settings.auto_play) {
                $player[0].play();
            }
        }
    }
    
    /**
     * Update time remaining display
     */
    function updateTimeRemaining() {
        if (!player) return;
        
        var duration = player.duration;
        var currentTime = player.currentTime;
        var remaining = duration - currentTime;
        
        if (remaining > 0) {
            var minutes = Math.floor(remaining / 60);
            var seconds = Math.floor(remaining % 60);
            $('.wnap-time-remaining').text('⏱ ' + minutes + ':' + (seconds < 10 ? '0' : '') + seconds + ' remaining');
        }
    }
    
    /**
     * Show player
     */
    function showPlayer() {
        $('#wnap-player-container').fadeIn(300);
        
        // Trigger event for draggable initialization
        $(document).trigger('wnap-player-shown');
    }
    
    /**
     * Hide player
     */
    function hidePlayer() {
        $('#wnap-player-container').fadeOut(300);
        
        if (player) {
            player.pause();
        }
        
        $(document).trigger('wnap-player-hidden');
    }
    
    /**
     * Minimize player to FAB
     */
    function minimizePlayer() {
        $('#wnap-player-container').addClass('wnap-player-minimized');
        localStorage.setItem('wnap_player_minimized', 'true');
    }
    
    /**
     * Restore player from minimized state
     */
    function restorePlayer() {
        $('#wnap-player-container').removeClass('wnap-player-minimized');
        localStorage.removeItem('wnap_player_minimized');
    }
    
    /**
     * Show loading overlay
     */
    function showLoadingOverlay() {
        var $overlay = $('<div class="wnap-loading-overlay">' +
            '<div class="wnap-loading-spinner"></div>' +
            '<div class="wnap-loading-text">' + (wnapFrontend.strings.loading || 'Generating audio...') + '</div>' +
        '</div>');
        
        $('body').append($overlay);
        $overlay.fadeIn(300);
    }
    
    /**
     * Hide loading overlay
     */
    function hideLoadingOverlay() {
        $('.wnap-loading-overlay').fadeOut(300, function() {
            $(this).remove();
        });
    }
    
    // ===================================
    // FLOATING BUTTON FUNCTIONALITY
    // ===================================
    
    var speechSynthesis = window.speechSynthesis;
    var speechUtterance = null;
    var currentSpeed = 1.0;
    var audioContent = '';
    var isPlaying = false;
    
    /**
     * Initialize floating button
     */
    function initFloatingButton() {
        // Check if Web Speech API is supported
        if (!('speechSynthesis' in window)) {
            console.error('WNAP: Web Speech API not supported in this browser');
            // Hide button if not supported
            $('#wnapFloatingBtn').addClass('wnap-fab-hidden');
            return;
        }
        
        var $btn = $('#wnapFloatingBtn');
        
        // Check if button exists
        if ($btn.length === 0) {
            console.error('WNAP: Floating button not found in DOM');
            return;
        }
        
        // Check if button should be hidden forever
        if (localStorage.getItem('wnap_fab_hidden') === 'true') {
            console.log('WNAP: Floating button hidden by user preference');
            $btn.addClass('wnap-fab-hidden');
            return;
        }
        
        // Button is already visible by default - just log
        console.log('WNAP: Floating button initialized');
        
        // Closed state - Click to open AND start playing instantly
        $('.wnap-fab-closed').on('click', function() {
            openFloatingButton();
            // Start playing audio instantly
            playAudio();
        });
        
        // Close button
        $('.wnap-fab-close').on('click', function() {
            closeFloatingButton();
        });
        
        // Play button
        $('#wnapFabPlay').on('click', function() {
            playAudio();
        });
        
        // Pause button
        $('#wnapFabPause').on('click', function() {
            pauseAudio();
        });
        
        // Stop button
        $('#wnapFabStop').on('click', function() {
            stopAudio();
        });
        
        // Speed buttons
        $('.wnap-speed-btn').on('click', function() {
            var speed = parseFloat($(this).data('speed'));
            setSpeed(speed);
            $('.wnap-speed-btn').removeClass('active');
            $(this).addClass('active');
        });
        
        // Hide forever button
        $('.wnap-fab-hide').on('click', function() {
            localStorage.setItem('wnap_fab_hidden', 'true');
            $btn.addClass('wnap-fab-hidden');
        });
        
        // Load content for Web Speech API
        loadContentForSpeech();
    }
    
    /**
     * Open floating button
     */
    function openFloatingButton() {
        var $fabOpen = $('.wnap-fab-open');
        $('.wnap-fab-closed').fadeOut(200, function() {
            // Remove hidden class and fade in
            // Note: fadeIn() will set display to 'block' and animate opacity
            $fabOpen.removeClass('wnap-fab-hidden').hide().fadeIn(300);
        });
    }
    
    /**
     * Close floating button
     */
    function closeFloatingButton() {
        $('.wnap-fab-open').fadeOut(200, function() {
            $(this).addClass('wnap-fab-hidden');
            $('.wnap-fab-closed').fadeIn(300);
        });
    }
    
    /**
     * Load content for speech synthesis
     */
    function loadContentForSpeech() {
        // Get post content
        var content = '';
        
        // Try to get content from article/post body
        if ($('article .entry-content').length) {
            content = $('article .entry-content').text();
        } else if ($('.entry-content').length) {
            content = $('.entry-content').text();
        } else if ($('article').length) {
            content = $('article').text();
        } else if ($('.post-content').length) {
            content = $('.post-content').text();
        } else if ($('.content').length) {
            content = $('.content').text();
        }
        
        // Clean up content
        content = content.trim().substring(0, 5000); // Limit to 5000 characters
        audioContent = content;
    }
    
    /**
     * Play audio using Web Speech API
     */
    function playAudio() {
        if (!speechSynthesis) {
            console.error('WNAP: Web Speech API not supported in this browser');
            showNotification('Web Speech API not supported in this browser', 'error');
            return;
        }
        
        if (!audioContent) {
            loadContentForSpeech();
        }
        
        if (!audioContent) {
            console.error('WNAP: No content to read');
            showNotification('No content available to read', 'error');
            return;
        }
        
        // Resume if paused
        if (speechUtterance && speechSynthesis.paused) {
            speechSynthesis.resume();
            isPlaying = true;
            updatePlayPauseButtons();
            console.log('WNAP: Speech resumed');
            return;
        }
        
        // Create new utterance
        speechUtterance = new SpeechSynthesisUtterance(audioContent);
        
        // Set voice properties
        var settings = wnapFrontend.settings || {};
        var language = settings.default_language || 'en-US';
        
        speechUtterance.lang = language;
        speechUtterance.rate = currentSpeed;
        speechUtterance.pitch = settings.pitch || 1.0;
        speechUtterance.volume = (settings.volume || 80) / 100;
        
        console.log('WNAP: Starting speech synthesis with language: ' + language + ', speed: ' + currentSpeed);
        
        // Event handlers
        speechUtterance.onstart = function() {
            isPlaying = true;
            updatePlayPauseButtons();
            console.log('WNAP: Speech started');
        };
        
        speechUtterance.onend = function() {
            isPlaying = false;
            updatePlayPauseButtons();
            resetProgress();
            console.log('WNAP: Speech ended');
        };
        
        speechUtterance.onerror = function(event) {
            console.error('WNAP: Speech synthesis error:', event);
            isPlaying = false;
            updatePlayPauseButtons();
            // Show user-friendly error message
            var errorMsg = 'Unable to play audio. ';
            if (event.error === 'network') {
                errorMsg += 'Please check your internet connection.';
            } else if (event.error === 'not-allowed') {
                errorMsg += 'Please allow audio playback.';
            } else {
                errorMsg += 'Please try again.';
            }
            showNotification(errorMsg, 'error');
        };
        
        speechUtterance.onpause = function() {
            isPlaying = false;
            updatePlayPauseButtons();
            console.log('WNAP: Speech paused');
        };
        
        // Start speaking
        speechSynthesis.speak(speechUtterance);
        
        // Update progress (approximate)
        updateProgressBar();
    }
    
    /**
     * Pause audio
     */
    function pauseAudio() {
        if (speechSynthesis && speechUtterance) {
            speechSynthesis.pause();
            isPlaying = false;
            updatePlayPauseButtons();
        }
    }
    
    /**
     * Stop audio
     */
    function stopAudio() {
        if (speechSynthesis) {
            speechSynthesis.cancel();
            isPlaying = false;
            updatePlayPauseButtons();
            resetProgress();
        }
    }
    
    /**
     * Set playback speed
     */
    function setSpeed(speed) {
        currentSpeed = speed;
        
        // If currently playing, restart with new speed
        if (isPlaying && speechSynthesis && speechUtterance) {
            var wasPaused = speechSynthesis.paused;
            speechSynthesis.cancel();
            
            if (!wasPaused) {
                // Restart playback with new speed
                setTimeout(function() {
                    playAudio();
                }, 100);
            }
        }
    }
    
    /**
     * Update play/pause buttons
     */
    function updatePlayPauseButtons() {
        if (isPlaying) {
            $('#wnapFabPlay').addClass('wnap-fab-hidden');
            $('#wnapFabPause').removeClass('wnap-fab-hidden');
        } else {
            $('#wnapFabPlay').removeClass('wnap-fab-hidden');
            $('#wnapFabPause').addClass('wnap-fab-hidden');
        }
    }
    
    /**
     * Update progress bar (approximate for Web Speech API)
     */
    function updateProgressBar() {
        if (!isPlaying) return;
        
        // Estimate duration based on word count and speed
        var wordCount = audioContent.split(' ').length;
        var wordsPerMinute = 150 * currentSpeed; // Average speaking rate
        var estimatedDuration = (wordCount / wordsPerMinute) * 60; // in seconds
        
        var startTime = Date.now();
        var updateInterval = setInterval(function() {
            if (!isPlaying) {
                clearInterval(updateInterval);
                return;
            }
            
            var elapsed = (Date.now() - startTime) / 1000;
            var progress = Math.min((elapsed / estimatedDuration) * 100, 100);
            
            $('.wnap-progress-fill').css('width', progress + '%');
            
            var currentSeconds = Math.floor(elapsed);
            var totalSeconds = Math.floor(estimatedDuration);
            var currentMinutes = Math.floor(currentSeconds / 60);
            var currentSecs = currentSeconds % 60;
            var totalMinutes = Math.floor(totalSeconds / 60);
            var totalSecs = totalSeconds % 60;
            
            $('.wnap-time').text(
                currentMinutes + ':' + (currentSecs < 10 ? '0' : '') + currentSecs + 
                ' / ' + 
                totalMinutes + ':' + (totalSecs < 10 ? '0' : '') + totalSecs
            );
            
            if (elapsed >= estimatedDuration) {
                clearInterval(updateInterval);
            }
        }, 100);
    }
    
    /**
     * Reset progress bar
     */
    function resetProgress() {
        $('.wnap-progress-fill').css('width', '0%');
        $('.wnap-time').text('0:00 / 0:00');
    }
    
    /**
     * Show notification message
     */
    function showNotification(message, type) {
        type = type || 'info'; // info, success, error, warning
        
        // Remove existing notifications
        $('.wnap-notification').remove();
        
        // Create notification
        var $notification = $('<div class="wnap-notification wnap-notification-' + type + '">' +
            '<span class="wnap-notification-message">' + message + '</span>' +
            '<button class="wnap-notification-close" aria-label="Close">&times;</button>' +
        '</div>');
        
        // Add to body
        $('body').append($notification);
        
        // Show with animation
        setTimeout(function() {
            $notification.addClass('wnap-notification-show');
        }, 100);
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            hideNotification($notification);
        }, 5000);
        
        // Close button handler
        $notification.find('.wnap-notification-close').on('click', function() {
            hideNotification($notification);
        });
    }
    
    /**
     * Hide notification
     */
    function hideNotification($notification) {
        $notification.removeClass('wnap-notification-show');
        setTimeout(function() {
            $notification.remove();
        }, 300);
    }
    
    // Initialize floating button on page load
    if ($('#wnapFloatingBtn').length) {
        initFloatingButton();
    }
    
})(jQuery);

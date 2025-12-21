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
                    alert(response.data.message || wnapFrontend.strings.error);
                }
            },
            error: function() {
                hideLoadingOverlay();
                alert(wnapFrontend.strings.error || 'An error occurred');
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
    
})(jQuery);

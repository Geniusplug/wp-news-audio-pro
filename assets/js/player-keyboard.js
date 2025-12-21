/**
 * Player Keyboard Shortcuts
 * 
 * Keyboard shortcuts for audio player control
 * 
 * @package WP_News_Audio_Pro
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    /**
     * Keyboard Shortcuts Class
     */
    class KeyboardShortcuts {
        constructor(player) {
            this.player = player;
            this.plyrInstance = null;
            this.audioElement = null;
            this.isPlayerVisible = false;
            
            this.shortcuts = {
                'Space': this.togglePlay.bind(this),
                'ArrowLeft': this.rewind.bind(this),
                'ArrowRight': this.forward.bind(this),
                'ArrowUp': this.volumeUp.bind(this),
                'ArrowDown': this.volumeDown.bind(this),
                'KeyM': this.toggleMute.bind(this),
                'Escape': this.closePlayer.bind(this)
            };
            
            this.init();
        }
        
        /**
         * Initialize keyboard shortcuts
         */
        init() {
            $(document).on('keydown', (e) => {
                this.handleKeyPress(e);
            });
            
            // Listen for player visibility changes
            $(document).on('wnap-player-shown', () => {
                this.isPlayerVisible = true;
                this.setupPlayer();
            });
            
            $(document).on('wnap-player-hidden', () => {
                this.isPlayerVisible = false;
            });
        }
        
        /**
         * Setup player references
         */
        setupPlayer() {
            this.audioElement = document.getElementById('wnap-audio-player');
            
            // Check if Plyr is available
            if (typeof Plyr !== 'undefined' && this.audioElement) {
                // Wait for Plyr to be initialized
                setTimeout(() => {
                    if (window.WNAP && window.WNAP.player) {
                        this.plyrInstance = window.WNAP.player;
                    }
                }, 500);
            }
        }
        
        /**
         * Handle key press
         */
        handleKeyPress(e) {
            // Don't interfere when typing in input fields
            if ($(e.target).is('input, textarea, select')) {
                return;
            }
            
            // Only work when player is visible
            if (!this.isPlayerVisible) {
                return;
            }
            
            // Use e.key first for better cross-browser support, fallback to e.code
            const key = e.key || e.code;
            
            if (this.shortcuts[key]) {
                e.preventDefault();
                this.shortcuts[key]();
                
                // Show shortcut indicator
                this.showShortcutIndicator(key);
            }
        }
        
        /**
         * Toggle play/pause
         */
        togglePlay() {
            if (this.plyrInstance) {
                this.plyrInstance.togglePlay();
            } else if (this.audioElement) {
                if (this.audioElement.paused) {
                    this.audioElement.play();
                } else {
                    this.audioElement.pause();
                }
            }
        }
        
        /**
         * Rewind 10 seconds
         */
        rewind() {
            if (this.plyrInstance) {
                this.plyrInstance.rewind(10);
            } else if (this.audioElement) {
                this.audioElement.currentTime = Math.max(0, this.audioElement.currentTime - 10);
            }
        }
        
        /**
         * Forward 10 seconds
         */
        forward() {
            if (this.plyrInstance) {
                this.plyrInstance.forward(10);
            } else if (this.audioElement) {
                this.audioElement.currentTime = Math.min(
                    this.audioElement.duration,
                    this.audioElement.currentTime + 10
                );
            }
        }
        
        /**
         * Volume up
         */
        volumeUp() {
            if (this.plyrInstance) {
                const newVolume = Math.min(1, this.plyrInstance.volume + 0.1);
                this.plyrInstance.volume = newVolume;
            } else if (this.audioElement) {
                this.audioElement.volume = Math.min(1, this.audioElement.volume + 0.1);
            }
        }
        
        /**
         * Volume down
         */
        volumeDown() {
            if (this.plyrInstance) {
                const newVolume = Math.max(0, this.plyrInstance.volume - 0.1);
                this.plyrInstance.volume = newVolume;
            } else if (this.audioElement) {
                this.audioElement.volume = Math.max(0, this.audioElement.volume - 0.1);
            }
        }
        
        /**
         * Toggle mute
         */
        toggleMute() {
            if (this.plyrInstance) {
                this.plyrInstance.muted = !this.plyrInstance.muted;
            } else if (this.audioElement) {
                this.audioElement.muted = !this.audioElement.muted;
            }
        }
        
        /**
         * Close player
         */
        closePlayer() {
            $('#wnap-player-container').fadeOut(300);
            
            if (this.plyrInstance) {
                this.plyrInstance.pause();
            } else if (this.audioElement) {
                this.audioElement.pause();
            }
            
            $(document).trigger('wnap-player-hidden');
        }
        
        /**
         * Show shortcut indicator
         */
        showShortcutIndicator(key) {
            const labels = {
                'Space': 'Play/Pause',
                'ArrowLeft': '⏪ -10s',
                'ArrowRight': '⏩ +10s',
                'ArrowUp': '🔊 Volume Up',
                'ArrowDown': '🔉 Volume Down',
                'KeyM': '🔇 Mute',
                'Escape': '✕ Close'
            };
            
            const label = labels[key] || key;
            
            // Remove existing indicator
            $('.wnap-shortcut-indicator').remove();
            
            // Create new indicator
            const $indicator = $('<div class="wnap-shortcut-indicator"></div>');
            $indicator.text(label);
            
            // Append to player
            $('#wnap-player-container').append($indicator);
            
            // Animate and remove
            setTimeout(() => {
                $indicator.addClass('wnap-show');
            }, 10);
            
            setTimeout(() => {
                $indicator.removeClass('wnap-show');
                setTimeout(() => {
                    $indicator.remove();
                }, 300);
            }, 1000);
        }
        
        /**
         * Get help text
         */
        getHelpText() {
            return `
Keyboard Shortcuts:
-------------------
Space        Play/Pause
← Arrow      Rewind 10 seconds
→ Arrow      Forward 10 seconds
↑ Arrow      Volume up
↓ Arrow      Volume down
M            Mute/Unmute
Esc          Close player
            `.trim();
        }
    }
    
    // Initialize when document is ready
    $(document).ready(function() {
        new KeyboardShortcuts($('#wnap-player-container'));
    });
    
    // Expose to global scope
    window.WNAPKeyboardShortcuts = KeyboardShortcuts;
    
})(jQuery);

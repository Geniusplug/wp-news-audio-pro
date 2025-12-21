/**
 * Player Draggable
 * 
 * Makes the audio player draggable anywhere on screen
 * 
 * @package WP_News_Audio_Pro
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    /**
     * Draggable Player Class
     */
    class DraggablePlayer {
        constructor(element) {
            this.element = $(element);
            this.isDragging = false;
            this.currentX = 0;
            this.currentY = 0;
            this.initialX = 0;
            this.initialY = 0;
            this.xOffset = 0;
            this.yOffset = 0;
            
            this.init();
        }
        
        /**
         * Initialize draggable functionality
         */
        init() {
            // Load saved position
            this.loadPosition();
            
            // Add drag handle
            this.addDragHandle();
            
            // Bind events
            this.bindEvents();
        }
        
        /**
         * Add visual drag handle to player
         */
        addDragHandle() {
            const handle = $('<div class="wnap-drag-handle"></div>');
            handle.html('<span class="dashicons dashicons-move"></span>');
            this.element.prepend(handle);
            this.handle = handle;
        }
        
        /**
         * Bind drag events
         */
        bindEvents() {
            const that = this;
            
            // Mouse events
            this.handle.on('mousedown', (e) => {
                that.dragStart(e);
            });
            
            $(document).on('mousemove', (e) => {
                that.drag(e);
            });
            
            $(document).on('mouseup', () => {
                that.dragEnd();
            });
            
            // Touch events for mobile
            this.handle.on('touchstart', (e) => {
                that.dragStart(e.touches[0]);
            });
            
            $(document).on('touchmove', (e) => {
                if (that.isDragging) {
                    e.preventDefault();
                    that.drag(e.touches[0]);
                }
            });
            
            $(document).on('touchend', () => {
                that.dragEnd();
            });
        }
        
        /**
         * Start dragging
         */
        dragStart(e) {
            this.initialX = e.clientX - this.xOffset;
            this.initialY = e.clientY - this.yOffset;
            
            if (e.target === this.handle[0] || $(e.target).parent()[0] === this.handle[0]) {
                this.isDragging = true;
                this.element.addClass('wnap-dragging');
            }
        }
        
        /**
         * During drag
         */
        drag(e) {
            if (this.isDragging) {
                e.preventDefault();
                
                this.currentX = e.clientX - this.initialX;
                this.currentY = e.clientY - this.initialY;
                
                this.xOffset = this.currentX;
                this.yOffset = this.currentY;
                
                // Confine to viewport
                this.constrainToViewport();
                
                // Apply transform
                this.setTranslate(this.currentX, this.currentY);
            }
        }
        
        /**
         * End dragging
         */
        dragEnd() {
            if (this.isDragging) {
                this.initialX = this.currentX;
                this.initialY = this.currentY;
                
                this.isDragging = false;
                this.element.removeClass('wnap-dragging');
                
                // Save position
                this.savePosition();
            }
        }
        
        /**
         * Constrain player to viewport
         */
        constrainToViewport() {
            const rect = this.element[0].getBoundingClientRect();
            const viewportWidth = window.innerWidth;
            const viewportHeight = window.innerHeight;
            
            // Left boundary
            if (rect.left + this.currentX < 0) {
                this.currentX = -rect.left;
            }
            
            // Right boundary
            if (rect.right + this.currentX > viewportWidth) {
                this.currentX = viewportWidth - rect.right;
            }
            
            // Top boundary
            if (rect.top + this.currentY < 0) {
                this.currentY = -rect.top;
            }
            
            // Bottom boundary
            if (rect.bottom + this.currentY > viewportHeight) {
                this.currentY = viewportHeight - rect.bottom;
            }
        }
        
        /**
         * Set CSS transform
         */
        setTranslate(xPos, yPos) {
            this.element.css({
                'transform': `translate(${xPos}px, ${yPos}px)`,
                'transition': 'none'
            });
        }
        
        /**
         * Save position to localStorage
         */
        savePosition() {
            localStorage.setItem('wnap_player_position', JSON.stringify({
                x: this.currentX,
                y: this.currentY
            }));
        }
        
        /**
         * Load position from localStorage
         */
        loadPosition() {
            const saved = localStorage.getItem('wnap_player_position');
            
            if (saved) {
                try {
                    const position = JSON.parse(saved);
                    this.currentX = position.x || 0;
                    this.currentY = position.y || 0;
                    this.xOffset = this.currentX;
                    this.yOffset = this.currentY;
                    
                    // Apply saved position after a brief delay to ensure element is rendered
                    setTimeout(() => {
                        this.setTranslate(this.currentX, this.currentY);
                    }, 100);
                } catch (e) {
                    // Invalid JSON, ignore
                }
            }
        }
        
        /**
         * Reset to default position
         */
        reset() {
            this.currentX = 0;
            this.currentY = 0;
            this.xOffset = 0;
            this.yOffset = 0;
            
            this.element.css({
                'transform': 'translate(0, 0)',
                'transition': 'transform 0.3s cubic-bezier(0.4, 0, 0.2, 1)'
            });
            
            localStorage.removeItem('wnap_player_position');
        }
    }
    
    // Initialize when player is shown
    $(document).on('wnap-player-shown', function() {
        const $player = $('#wnap-player-container');
        
        if ($player.length && !$player.data('draggable-initialized')) {
            new DraggablePlayer($player[0]);
            $player.data('draggable-initialized', true);
        }
    });
    
    // Expose to global scope
    window.WNAPDraggablePlayer = DraggablePlayer;
    
})(jQuery);

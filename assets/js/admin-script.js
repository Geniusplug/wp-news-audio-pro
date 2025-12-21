/**
 * Admin Scripts
 * 
 * JavaScript for the WordPress admin panel
 * 
 * @package WP_News_Audio_Pro
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Update range slider values
        $('.wnap-range-slider').on('input', function() {
            var value = $(this).val();
            var suffix = '';
            
            if ($(this).attr('id') === 'speech_speed' || $(this).attr('id') === 'pitch') {
                suffix = 'x';
            } else if ($(this).attr('id') === 'volume') {
                suffix = '%';
            }
            
            $(this).next('.wnap-range-value').text(value + suffix);
        });
        
        // License activation
        $('.wnap-activate-license').on('click', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $form = $button.closest('.wnap-license-form');
            var $loader = $form.find('.wnap-license-loader');
            var $message = $('.wnap-license-message');
            var purchaseCode = $('#purchase_code').val().trim();
            
            if (!purchaseCode) {
                showMessage('error', wnapAdmin.strings.error || 'Please enter a purchase code');
                return;
            }
            
            // Show loader
            $button.prop('disabled', true);
            $loader.show();
            $message.hide();
            
            // AJAX request
            $.ajax({
                url: wnapAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wnap_activate_license',
                    nonce: wnapAdmin.nonce,
                    purchase_code: purchaseCode
                },
                success: function(response) {
                    if (response.success) {
                        showMessage('success', response.data.message);
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        var errorMessage = response.data.message || 'An error occurred. Please try again.';
                        var messageHtml = '<span class="wnap-error-icon">⚠️</span> ' + errorMessage;
                        
                        // Show buy button if needed
                        if (response.data.action === 'buy' && response.data.buy_url) {
                            messageHtml += '<br><br><a href="' + response.data.buy_url + '" target="_blank" class="button button-primary" style="margin-top: 10px;">Buy License</a>';
                        }
                        
                        showMessage('error', messageHtml);
                    }
                },
                error: function() {
                    showMessage('error', '⚠️ Connection error. Please check your internet connection and try again.');
                },
                complete: function() {
                    $button.prop('disabled', false);
                    $loader.hide();
                }
            });
        });
        
        // License deactivation
        $('.wnap-deactivate-license').on('click', function(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to deactivate your license?')) {
                return;
            }
            
            var $button = $(this);
            $button.prop('disabled', true);
            
            // AJAX request
            $.ajax({
                url: wnapAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wnap_deactivate_license',
                    nonce: wnapAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                },
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        });
        
        // Show message function
        function showMessage(type, message) {
            var $message = $('.wnap-license-message');
            $message
                .removeClass('success error')
                .addClass(type)
                .html(message) // Changed from .text() to .html() to support HTML content
                .fadeIn();
        }
        
        // Meta box audio generation
        $(document).on('click', '.wnap-generate-audio, .wnap-regenerate-audio', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var postId = $button.data('post-id');
            
            if (!postId) {
                return;
            }
            
            $button.prop('disabled', true).text('Generating...');
            
            // AJAX request
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wnap_generate_audio',
                    nonce: wnapAdmin.nonce,
                    post_id: postId
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || 'Error generating audio');
                        $button.prop('disabled', false).text('Generate Audio');
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                    $button.prop('disabled', false).text('Generate Audio');
                }
            });
        });
    });
    
})(jQuery);

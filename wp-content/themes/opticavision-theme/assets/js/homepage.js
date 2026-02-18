/**
 * OpticaVision Homepage JavaScript
 * Funcionalidades específicas para la página de inicio
 *
 * @package OpticaVision_Theme
 */

jQuery(document).ready(function($) {
    'use strict';

    /**
     * Homepage Carousel Enhancement
     */
    function initHomepageCarousels() {
        // Enhance product carousels with additional functionality
        $('.products-carousel').each(function() {
            var $carousel = $(this);
            
            // Add loading state
            $carousel.addClass('loading');
            
            // Remove loading state after images load
            $carousel.find('img').on('load', function() {
                $carousel.removeClass('loading');
            });
            
            // Auto-height adjustment
            $carousel.on('afterChange', function() {
                adjustCarouselHeight($carousel);
            });
        });
    }

    /**
     * Adjust carousel height based on content
     */
    function adjustCarouselHeight($carousel) {
        var maxHeight = 0;
        $carousel.find('.slick-active .product-card').each(function() {
            var height = $(this).outerHeight();
            if (height > maxHeight) {
                maxHeight = height;
            }
        });
        $carousel.find('.slick-track').css('height', maxHeight + 'px');
    }

    /**
     * Promotional Banners Animation
     */
    function initPromoBanners() {
        $('.promo-banners .promo-banner').each(function(index) {
            $(this).css({
                'animation-delay': (index * 0.2) + 's'
            }).addClass('animate-in');
        });

        // Hover effects
        $('.promo-banner').hover(
            function() {
                $(this).addClass('hovered');
            },
            function() {
                $(this).removeClass('hovered');
            }
        );
    }

    /**
     * Newsletter Form Enhancement
     */
    function initNewsletterForm() {
        var $form = $('.newsletter-form');
        var $input = $form.find('input[type="email"]');
        var $button = $form.find('button[type="submit"]');

        $form.on('submit', function(e) {
            e.preventDefault();
            
            var email = $input.val().trim();
            
            if (!isValidEmail(email)) {
                showNotification('Por favor, ingresa un email válido', 'error');
                return;
            }

            // Add loading state
            $button.addClass('loading').prop('disabled', true);
            
            // Simulate AJAX call (replace with actual implementation)
            setTimeout(function() {
                $button.removeClass('loading').prop('disabled', false);
                $input.val('');
                showNotification('¡Gracias por suscribirte!', 'success');
            }, 1500);
        });
    }

    /**
     * Validate email format
     */
    function isValidEmail(email) {
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    /**
     * Show notification
     */
    function showNotification(message, type) {
        var $notification = $('<div class="notification notification-' + type + '">' + message + '</div>');
        $('body').append($notification);
        
        setTimeout(function() {
            $notification.addClass('show');
        }, 100);
        
        setTimeout(function() {
            $notification.removeClass('show');
            setTimeout(function() {
                $notification.remove();
            }, 300);
        }, 3000);
    }

    /**
     * Smooth scroll for anchor links
     */
    function initSmoothScroll() {
        $('a[href^="#"]').on('click', function(e) {
            var target = $(this.getAttribute('href'));
            if (target.length) {
                e.preventDefault();
                $('html, body').animate({
                    scrollTop: target.offset().top - 100
                }, 800);
            }
        });
    }

    /**
     * Lazy loading for images
     */
    function initLazyLoading() {
        if ('IntersectionObserver' in window) {
            var imageObserver = new IntersectionObserver(function(entries, observer) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        var img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        imageObserver.unobserve(img);
                    }
                });
            });

            document.querySelectorAll('img[data-src]').forEach(function(img) {
                imageObserver.observe(img);
            });
        }
    }

    /**
     * Initialize all homepage functionality
     */
    function init() {
        initHomepageCarousels();
        initPromoBanners();
        initNewsletterForm();
        initSmoothScroll();
        initLazyLoading();
        
        // Log initialization
        if (window.console && console.log) {
            console.log('OpticaVision Homepage: Initialized');
        }
    }

    // Initialize when DOM is ready
    init();

    // Re-initialize on window resize (debounced)
    var resizeTimer;
    $(window).on('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            adjustCarouselHeight($('.products-carousel'));
        }, 250);
    });
});

/**
 * OpticaVision Theme - Main JavaScript
 * 
 * @package OpticaVision_Theme
 * @version 1.0.0
 */

(function($) {
    'use strict';

    // Theme object
    const OpticaVisionTheme = {
        
        // Initialize all functionality
        init: function() {
            this.setupMobileMenu();
            this.setupHeroSlider();
            this.setupSearch();
            this.setupCart();
            this.setupScrollEffects();
            this.setupLazyLoading();
            this.setupPerformanceOptimizations();
        },

        // Mobile menu functionality
        setupMobileMenu: function() {
            const toggle = document.querySelector('.mobile-menu-toggle');
            const nav = document.querySelector('.main-navigation');
            const body = document.body;
            
            if (!toggle || !nav) {
                console.log('Mobile menu elements not found');
                return;
            }

            console.log('Mobile menu setup: Elements found successfully');
            
            let menuOpen = false;

            // Mobile menu toggle - clean implementation
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Toggle state
                menuOpen = !menuOpen;
                
                console.log('Mobile menu clicked - New state:', menuOpen ? 'OPEN' : 'CLOSED');
                
                // Apply/remove classes
                if (menuOpen) {
                    nav.classList.add('mobile-active');
                    body.classList.add('mobile-menu-open');
                    toggle.setAttribute('aria-expanded', 'true');
                } else {
                    nav.classList.remove('mobile-active');
                    body.classList.remove('mobile-menu-open');
                    toggle.setAttribute('aria-expanded', 'false');
                }
            });

            // Close menu when clicking outside
            document.addEventListener('click', function(e) {
                if (menuOpen && !nav.contains(e.target) && !toggle.contains(e.target)) {
                    menuOpen = false;
                    nav.classList.remove('mobile-active');
                    body.classList.remove('mobile-menu-open');
                    toggle.setAttribute('aria-expanded', 'false');
                }
            });

            // Close menu on escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && menuOpen) {
                    menuOpen = false;
                    nav.classList.remove('mobile-active');
                    body.classList.remove('mobile-menu-open');
                    toggle.setAttribute('aria-expanded', 'false');
                    toggle.focus();
                }
            });
            
            // Handle megamenu submenu toggles in mobile
            this.setupMobileMegaMenuToggles();
        },
        
        // Mobile megamenu submenu functionality
        setupMobileMegaMenuToggles: function() {
            document.addEventListener('click', function(e) {
                // Only handle clicks when mobile menu is active
                const nav = document.querySelector('.main-navigation');
                if (!nav || !nav.classList.contains('mobile-active')) {
                    return;
                }
                
                // Check if clicked element is a megamenu parent link
                const megaMenuItem = e.target.closest('.has-megamenu > a');
                if (megaMenuItem) {
                    e.preventDefault();
                    
                    const parentItem = megaMenuItem.parentElement;
                    const isOpen = parentItem.classList.contains('submenu-open');
                    
                    // Close all other open submenus
                    const allMegaMenus = nav.querySelectorAll('.has-megamenu');
                    allMegaMenus.forEach(item => {
                        if (item !== parentItem) {
                            item.classList.remove('submenu-open');
                        }
                    });
                    
                    // Toggle current submenu
                    if (isOpen) {
                        parentItem.classList.remove('submenu-open');
                        console.log('Mobile megamenu closed');
                    } else {
                        parentItem.classList.add('submenu-open');
                        console.log('Mobile megamenu opened');
                    }
                }
            });
        },

        // Hero slider functionality
        setupHeroSlider: function() {
            const $slider = $('.hero-slider');
            const $slides = $('.hero-slide');
            const $indicators = $('.hero-indicator');
            
            if ($slides.length <= 1) return;

            let currentSlide = 0;
            let slideInterval;

            // Show specific slide
            function showSlide(index) {
                $slides.removeClass('active').eq(index).addClass('active');
                $indicators.removeClass('active').eq(index).addClass('active');
                currentSlide = index;
            }

            // Next slide
            function nextSlide() {
                const next = (currentSlide + 1) % $slides.length;
                showSlide(next);
            }

            // Previous slide
            function prevSlide() {
                const prev = (currentSlide - 1 + $slides.length) % $slides.length;
                showSlide(prev);
            }

            // Auto-advance slides
            function startSlideshow() {
                slideInterval = setInterval(nextSlide, 5000);
            }

            function stopSlideshow() {
                clearInterval(slideInterval);
            }

            // Indicator clicks
            $indicators.on('click', function() {
                const index = $(this).index();
                showSlide(index);
                stopSlideshow();
                startSlideshow();
            });

            // Keyboard navigation
            $slider.on('keydown', function(e) {
                if (e.keyCode === 37) { // Left arrow
                    prevSlide();
                    stopSlideshow();
                    startSlideshow();
                } else if (e.keyCode === 39) { // Right arrow
                    nextSlide();
                    stopSlideshow();
                    startSlideshow();
                }
            });

            // Pause on hover
            $slider.on('mouseenter', stopSlideshow)
                   .on('mouseleave', startSlideshow);

            // Start slideshow
            startSlideshow();
        },

        // Search functionality
        setupSearch: function() {
            const $searchToggle = $('.search-toggle');
            const $searchForm = $('.search-form');
            const $searchInput = $('.search-input');

            $searchToggle.on('click', function(e) {
                e.preventDefault();
                $searchForm.toggleClass('active');
                
                if ($searchForm.hasClass('active')) {
                    $searchInput.focus();
                }
            });

            // Close search on escape
            $searchInput.on('keydown', function(e) {
                if (e.keyCode === 27) {
                    $searchForm.removeClass('active');
                    $searchToggle.focus();
                }
            });
        },

        // Cart functionality
        setupCart: function() {
            const $cartToggle = $('.cart-toggle');
            const $cartDropdown = $('.cart-dropdown');

            $cartToggle.on('click', function(e) {
                e.preventDefault();
                $cartDropdown.toggleClass('active');
            });

            // Close cart dropdown when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.cart-toggle, .cart-dropdown').length) {
                    $cartDropdown.removeClass('active');
                }
            });

            // Update cart count via AJAX
            this.updateCartCount();
        },

        // Update cart count
        updateCartCount: function() {
            if (typeof wc_add_to_cart_params === 'undefined') return;

            $.ajax({
                url: opticavision_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'opticavision_get_cart_count',
                    nonce: opticavision_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('.cart-count').text(response.data.count);
                        
                        if (response.data.count > 0) {
                            $('.cart-count').show();
                        } else {
                            $('.cart-count').hide();
                        }
                    }
                }
            });
        },

        // Scroll effects
        setupScrollEffects: function() {
            const $header = $('.site-header');
            let lastScrollTop = 0;

            $(window).on('scroll', function() {
                const scrollTop = $(this).scrollTop();

                // Add/remove scrolled class
                if (scrollTop > 100) {
                    $header.addClass('scrolled');
                } else {
                    $header.removeClass('scrolled');
                }

                // Hide/show header on scroll
                if (scrollTop > lastScrollTop && scrollTop > 200) {
                    $header.addClass('header-hidden');
                } else {
                    $header.removeClass('header-hidden');
                }

                lastScrollTop = scrollTop;
            });

            // Smooth scroll for anchor links
            $('a[href^="#"]').on('click', function(e) {
                const target = $(this.getAttribute('href'));
                
                if (target.length) {
                    e.preventDefault();
                    $('html, body').animate({
                        scrollTop: target.offset().top - 100
                    }, 600);
                }
            });
        },

        // Lazy loading for images
        setupLazyLoading: function() {
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            img.src = img.dataset.src;
                            img.classList.remove('lazy');
                            imageObserver.unobserve(img);
                        }
                    });
                });

                document.querySelectorAll('img[data-src]').forEach(img => {
                    imageObserver.observe(img);
                });
            } else {
                // Fallback for older browsers
                $('img[data-src]').each(function() {
                    $(this).attr('src', $(this).data('src')).removeClass('lazy');
                });
            }
        },

        // Accessibility improvements
        setupAccessibility: function() {
            // Skip link functionality
            $('.skip-link').on('click', function(e) {
                const target = $($(this).attr('href'));
                if (target.length) {
                    target.attr('tabindex', '-1').focus();
                }
            });

            // Improve focus management
            $('a, button, input, textarea, select').on('focus', function() {
                $(this).addClass('focused');
            }).on('blur', function() {
                $(this).removeClass('focused');
            });

            // Announce dynamic content changes to screen readers
            this.setupAriaLive();
        },

        // Setup ARIA live regions
        setupAriaLive: function() {
            // Create live region for announcements
            if (!$('#aria-live-region').length) {
                $('body').append('<div id="aria-live-region" aria-live="polite" aria-atomic="true" class="sr-only"></div>');
            }
        },

        // Announce message to screen readers
        announceToScreenReader: function(message) {
            const $liveRegion = $('#aria-live-region');
            $liveRegion.text(message);
            
            // Clear after announcement
            setTimeout(() => {
                $liveRegion.empty();
            }, 1000);
        },

        // Performance optimizations
        setupPerformanceOptimizations: function() {
            // Debounce scroll events
            let scrollTimeout;
            $(window).on('scroll', function() {
                if (scrollTimeout) {
                    clearTimeout(scrollTimeout);
                }
                scrollTimeout = setTimeout(() => {
                    $(window).trigger('scroll.debounced');
                }, 16); // ~60fps
            });

            // Preload critical resources
            this.preloadCriticalResources();

            // Setup service worker if available
            this.setupServiceWorker();
        },

        // Preload critical resources
        preloadCriticalResources: function() {
            // Preload hero images
            $('.hero-slide').each(function() {
                const bgImage = $(this).css('background-image');
                if (bgImage && bgImage !== 'none') {
                    const img = new Image();
                    img.src = bgImage.slice(4, -1).replace(/"/g, "");
                }
            });
        },

        // Setup service worker - DISABLED (sw.js file not present)
        setupServiceWorker: function() {
            // Service Worker disabled - file not present
            // if ('serviceWorker' in navigator) {
            //     navigator.serviceWorker.register('/sw.js')
            //         .then(registration => {
            //             console.log('SW registered: ', registration);
            //         })
            //         .catch(registrationError => {
            //             console.log('SW registration failed: ', registrationError);
            //         });
            // }
        },

        // Utility functions
        utils: {
            // Throttle function
            throttle: function(func, limit) {
                let inThrottle;
                return function() {
                    const args = arguments;
                    const context = this;
                    if (!inThrottle) {
                        func.apply(context, args);
                        inThrottle = true;
                        setTimeout(() => inThrottle = false, limit);
                    }
                };
            },

            // Debounce function
            debounce: function(func, wait, immediate) {
                let timeout;
                return function() {
                    const context = this;
                    const args = arguments;
                    const later = function() {
                        timeout = null;
                        if (!immediate) func.apply(context, args);
                    };
                    const callNow = immediate && !timeout;
                    clearTimeout(timeout);
                    timeout = setTimeout(later, wait);
                    if (callNow) func.apply(context, args);
                };
            },

            // Check if element is in viewport
            isInViewport: function(element) {
                const rect = element.getBoundingClientRect();
                return (
                    rect.top >= 0 &&
                    rect.left >= 0 &&
                    rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
                    rect.right <= (window.innerWidth || document.documentElement.clientWidth)
                );
            }
        }
    };

    // Initialize when DOM is ready
    $(document).ready(function() {
        OpticaVisionTheme.init();
    });

    // Make theme object globally available
    window.OpticaVisionTheme = OpticaVisionTheme;

    // WooCommerce integration
    if (typeof wc_add_to_cart_params !== 'undefined') {
        // Update cart count when item is added
        $(document.body).on('added_to_cart', function() {
            OpticaVisionTheme.updateCartCount();
            OpticaVisionTheme.announceToScreenReader('Producto agregado al carrito');
        });

        // Update cart count when item is removed
        $(document.body).on('removed_from_cart', function() {
            OpticaVisionTheme.updateCartCount();
            OpticaVisionTheme.announceToScreenReader('Producto removido del carrito');
        });
    }

    // Handle AJAX errors gracefully
    $(document).ajaxError(function(event, jqXHR, ajaxSettings, thrownError) {
        console.error('AJAX Error:', thrownError);
        OpticaVisionTheme.announceToScreenReader('Error al cargar el contenido');
    });


    // Initialize everything when document is ready
    $(document).ready(function() {
        OpticaVisionTheme.init();
        console.log('OpticaVision Theme initialized successfully');
    });

})(jQuery);

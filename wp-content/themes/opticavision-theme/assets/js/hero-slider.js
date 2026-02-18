/**
 * Hero Slider Frontend JavaScript
 * Funcionalidad del slider hero en el frontend
 */

(function($) {
    'use strict';
    
    class OpticaVisionHeroSlider {
        constructor(element) {
            this.slider = $(element);
            this.container = this.slider.find('.hero-slider-container');
            this.slides = this.slider.find('.hero-slide');
            this.prevBtn = this.slider.find('.hero-slider-prev');
            this.nextBtn = this.slider.find('.hero-slider-next');
            this.dots = this.slider.find('.hero-slider-dot');
            
            this.currentSlide = 0;
            this.totalSlides = this.slides.length;
            this.autoplayTimer = null;
            this.isTransitioning = false;
            
            // Settings from data attributes
            this.settings = {
                autoplay: this.slider.data('autoplay') !== false,
                autoplayDelay: parseInt(this.slider.data('autoplay-delay')) || 5000,
                fade: this.slider.data('fade') !== false
            };
            
            this.init();
        }
        
        init() {
            if (this.totalSlides <= 1) {
                this.hideNavigation();
                return;
            }
            
            this.bindEvents();
            this.setupAccessibility();
            this.setupTouchGestures();
            this.setupKeyboardNavigation();
            this.setupLazyLoading();
            
            if (this.settings.autoplay && !this.prefersReducedMotion()) {
                this.startAutoplay();
            }
            
            // Preload next image
            this.preloadImage(1);
        }
        
        bindEvents() {
            // Navigation buttons
            this.prevBtn.on('click', (e) => {
                e.preventDefault();
                this.previousSlide();
            });
            
            this.nextBtn.on('click', (e) => {
                e.preventDefault();
                this.nextSlide();
            });
            
            // Dots navigation
            this.dots.on('click', (e) => {
                e.preventDefault();
                const index = $(e.currentTarget).data('slide');
                this.goToSlide(index);
            });
            
            // Pause autoplay on hover
            this.slider.on('mouseenter', () => {
                this.pauseAutoplay();
            });
            
            this.slider.on('mouseleave', () => {
                if (this.settings.autoplay && !this.prefersReducedMotion()) {
                    this.startAutoplay();
                }
            });
            
            // Pause on focus
            this.slider.on('focusin', () => {
                this.pauseAutoplay();
            });
            
            this.slider.on('focusout', () => {
                if (this.settings.autoplay && !this.prefersReducedMotion()) {
                    this.startAutoplay();
                }
            });
            
            // Pause when tab is not visible
            $(document).on('visibilitychange', () => {
                if (document.hidden) {
                    this.pauseAutoplay();
                } else if (this.settings.autoplay && !this.prefersReducedMotion()) {
                    this.startAutoplay();
                }
            });
        }
        
        setupAccessibility() {
            // Add ARIA attributes
            this.slider.attr({
                'role': 'region',
                'aria-label': 'Hero Image Slider',
                'aria-live': 'polite'
            });
            
            this.slides.each((index, slide) => {
                $(slide).attr({
                    'role': 'img',
                    'aria-hidden': index !== this.currentSlide,
                    'tabindex': index === this.currentSlide ? '0' : '-1'
                });
            });
            
            // Update navigation buttons
            this.updateAriaLabels();
        }
        
        setupTouchGestures() {
            let startX = 0;
            let startY = 0;
            let endX = 0;
            let endY = 0;
            
            this.container.on('touchstart', (e) => {
                const touch = e.originalEvent.touches[0];
                startX = touch.clientX;
                startY = touch.clientY;
            });
            
            this.container.on('touchmove', (e) => {
                // Prevent default to avoid scrolling while swiping
                if (Math.abs(startX - e.originalEvent.touches[0].clientX) > 10) {
                    e.preventDefault();
                }
            });
            
            this.container.on('touchend', (e) => {
                const touch = e.originalEvent.changedTouches[0];
                endX = touch.clientX;
                endY = touch.clientY;
                
                const deltaX = endX - startX;
                const deltaY = endY - startY;
                
                // Check if it's a horizontal swipe (not vertical scroll)
                if (Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > 50) {
                    if (deltaX > 0) {
                        this.previousSlide();
                    } else {
                        this.nextSlide();
                    }
                }
            });
        }
        
        setupKeyboardNavigation() {
            this.slider.on('keydown', (e) => {
                switch (e.key) {
                    case 'ArrowLeft':
                        e.preventDefault();
                        this.previousSlide();
                        break;
                    case 'ArrowRight':
                        e.preventDefault();
                        this.nextSlide();
                        break;
                    case 'Home':
                        e.preventDefault();
                        this.goToSlide(0);
                        break;
                    case 'End':
                        e.preventDefault();
                        this.goToSlide(this.totalSlides - 1);
                        break;
                }
            });
        }
        
        setupLazyLoading() {
            // Load images as they become active
            this.slides.each((index, slide) => {
                const $slide = $(slide);
                const $img = $slide.find('img[loading="lazy"]');
                
                if ($img.length && index <= 1) { // Load first two images immediately
                    this.loadImage($img);
                }
            });
        }
        
        loadImage($img) {
            if ($img.attr('data-loaded')) return;
            
            const img = new Image();
            img.onload = () => {
                $img.addClass('loaded').attr('data-loaded', 'true');
            };
            img.src = $img.attr('src');
        }
        
        nextSlide() {
            if (this.isTransitioning) return;
            
            const nextIndex = (this.currentSlide + 1) % this.totalSlides;
            this.goToSlide(nextIndex);
        }
        
        previousSlide() {
            if (this.isTransitioning) return;
            
            const prevIndex = this.currentSlide === 0 ? this.totalSlides - 1 : this.currentSlide - 1;
            this.goToSlide(prevIndex);
        }
        
        goToSlide(index) {
            if (this.isTransitioning || index === this.currentSlide) return;
            
            this.isTransitioning = true;
            
            // Preload next image
            this.preloadImage(index);
            
            // Update slides
            this.slides.removeClass('active').attr('aria-hidden', 'true').attr('tabindex', '-1');
            this.slides.eq(index).addClass('active').attr('aria-hidden', 'false').attr('tabindex', '0');
            
            // Update dots
            this.dots.removeClass('active');
            this.dots.eq(index).addClass('active');
            
            // Update current slide
            this.currentSlide = index;
            
            // Update accessibility
            this.updateAriaLabels();
            
            // Reset transition flag after animation
            setTimeout(() => {
                this.isTransitioning = false;
            }, this.settings.fade ? 800 : 500);
            
            // Restart autoplay
            if (this.settings.autoplay && !this.prefersReducedMotion()) {
                this.restartAutoplay();
            }
        }
        
        preloadImage(index) {
            if (index >= this.totalSlides) return;
            
            const $slide = this.slides.eq(index);
            const $img = $slide.find('img[loading="lazy"]');
            
            if ($img.length) {
                this.loadImage($img);
            }
        }
        
        startAutoplay() {
            this.pauseAutoplay();
            this.autoplayTimer = setInterval(() => {
                this.nextSlide();
            }, this.settings.autoplayDelay);
        }
        
        pauseAutoplay() {
            if (this.autoplayTimer) {
                clearInterval(this.autoplayTimer);
                this.autoplayTimer = null;
            }
        }
        
        restartAutoplay() {
            if (this.settings.autoplay && !this.prefersReducedMotion()) {
                this.startAutoplay();
            }
        }
        
        updateAriaLabels() {
            const current = this.currentSlide + 1;
            const total = this.totalSlides;
            
            this.slider.attr('aria-label', `Hero Image Slider, slide ${current} of ${total}`);
            
            this.prevBtn.attr('aria-label', `Go to previous slide, slide ${current === 1 ? total : current - 1} of ${total}`);
            this.nextBtn.attr('aria-label', `Go to next slide, slide ${current === total ? 1 : current + 1} of ${total}`);
            
            this.dots.each((index, dot) => {
                $(dot).attr('aria-label', `Go to slide ${index + 1} of ${total}${index === this.currentSlide ? ' (current)' : ''}`);
            });
        }
        
        hideNavigation() {
            this.prevBtn.hide();
            this.nextBtn.hide();
            this.dots.parent().hide();
        }
        
        prefersReducedMotion() {
            return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        }
        
        destroy() {
            this.pauseAutoplay();
            this.slider.off();
            this.container.off();
            this.prevBtn.off();
            this.nextBtn.off();
            this.dots.off();
        }
    }
    
    // Initialize sliders when DOM is ready
    $(document).ready(function() {
        $('.opticavision-hero-slider').each(function() {
            new OpticaVisionHeroSlider(this);
        });
    });
    
    // Reinitialize on AJAX content load (for compatibility)
    $(document).on('opticavision_content_loaded', function() {
        $('.opticavision-hero-slider').each(function() {
            if (!$(this).data('slider-initialized')) {
                new OpticaVisionHeroSlider(this);
                $(this).data('slider-initialized', true);
            }
        });
    });
    
})(jQuery);

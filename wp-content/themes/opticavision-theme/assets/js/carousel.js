/**
 * OpticaVision Theme - Carousel JavaScript
 * 
 * @package OpticaVision_Theme
 * @version 1.0.0
 */

(function($) {
    'use strict';

    // Carousel class
    class OpticaVisionCarousel {
        constructor(element) {
            this.carousel = $(element);
            this.track = this.carousel.find('.products-track, .brands-track');
            this.items = this.track.children();
            this.prevBtn = this.carousel.find('.carousel-prev');
            this.nextBtn = this.carousel.find('.carousel-next');
            
            this.currentIndex = 0;
            this.itemsToShow = this.getItemsToShow();
            this.itemWidth = this.getItemWidth();
            this.maxIndex = Math.max(0, this.items.length - this.itemsToShow);
            
            this.init();
        }

        init() {
            this.setupEventListeners();
            this.updateButtons();
            this.setupTouchEvents();
            this.setupKeyboardEvents();
            this.setupAutoplay();
            
            // Update on window resize
            $(window).on('resize.carousel', this.handleResize.bind(this));
        }

        setupEventListeners() {
            // Use infinite navigation for better UX
            this.prevBtn.on('click', this.goToPrevInfinite.bind(this));
            this.nextBtn.on('click', this.goToNextInfinite.bind(this));
        }

        setupTouchEvents() {
            let startX = 0;
            let currentX = 0;
            let isDragging = false;

            this.track.on('touchstart mousedown', (e) => {
                if (e.type === 'mousedown') {
                    e.preventDefault();
                }
                
                startX = e.type === 'touchstart' ? e.touches[0].clientX : e.clientX;
                isDragging = true;
                this.track.addClass('dragging');
            });

            $(document).on('touchmove mousemove', (e) => {
                if (!isDragging) return;
                
                currentX = e.type === 'touchmove' ? e.touches[0].clientX : e.clientX;
                const diff = startX - currentX;
                
                // Add some resistance
                if (Math.abs(diff) > 50) {
                    if (diff > 0 && this.currentIndex < this.maxIndex) {
                        this.goToNext();
                    } else if (diff < 0 && this.currentIndex > 0) {
                        this.goToPrev();
                    }
                    isDragging = false;
                    this.track.removeClass('dragging');
                }
            });

            $(document).on('touchend mouseup', () => {
                if (isDragging) {
                    isDragging = false;
                    this.track.removeClass('dragging');
                }
            });
        }

        setupKeyboardEvents() {
            this.carousel.on('keydown', (e) => {
                if (e.keyCode === 37) { // Left arrow
                    e.preventDefault();
                    this.goToPrev();
                } else if (e.keyCode === 39) { // Right arrow
                    e.preventDefault();
                    this.goToNext();
                }
            });
        }

        setupAutoplay() {
            if (this.carousel.data('autoplay') !== false) {
                this.startAutoplay();
                
                this.carousel.on('mouseenter', this.stopAutoplay.bind(this));
                this.carousel.on('mouseleave', this.startAutoplay.bind(this));
            }
        }

        startAutoplay() {
            this.stopAutoplay();
            this.autoplayInterval = setInterval(() => {
                this.goToNextInfinite();
            }, 5000);
        }

        stopAutoplay() {
            if (this.autoplayInterval) {
                clearInterval(this.autoplayInterval);
                this.autoplayInterval = null;
            }
        }

        getItemsToShow() {
            const containerWidth = this.carousel.width();
            
            if (containerWidth < 576) {
                return 1;
            } else if (containerWidth < 768) {
                return 2;
            } else if (containerWidth < 992) {
                return 3;
            } else {
                return 4;
            }
        }

        getItemWidth() {
            if (this.items.length === 0) return 0;
            
            const item = this.items.first();
            const itemWidth = item.outerWidth(true); // Incluye margin
            return itemWidth;
        }

        goToPrev() {
            if (this.currentIndex > 0) {
                this.currentIndex--;
                this.updatePosition();
                this.updateButtons();
                this.announceChange('previous');
            }
        }

        goToNext() {
            if (this.currentIndex < this.maxIndex) {
                this.currentIndex++;
                this.updatePosition();
                this.updateButtons();
                this.announceChange('next');
            }
        }

        goToNextInfinite() {
            this.currentIndex++;
            if (this.currentIndex > this.maxIndex) {
                this.currentIndex = 0;
            }
            this.updatePosition();
            this.updateButtons();
            this.announceChange('next');
        }

        goToPrevInfinite() {
            this.currentIndex--;
            if (this.currentIndex < 0) {
                this.currentIndex = this.maxIndex;
            }
            this.updatePosition();
            this.updateButtons();
            this.announceChange('previous');
        }

        goTo(index) {
            if (index >= 0 && index <= this.maxIndex) {
                this.currentIndex = index;
                this.updatePosition();
                this.updateButtons();
            }
        }

        updatePosition() {
            // Offset inicial para compensar el padding interno
            const baseOffset = 0;
            const translateX = baseOffset - (this.currentIndex * this.itemWidth);
            this.track.css('transform', `translateX(${translateX}px)`);
        }

        updateButtons() {
            // Infinite carousel never disables buttons
            this.prevBtn.prop('disabled', false);
            this.nextBtn.prop('disabled', false);
            
            // Update ARIA attributes for infinite navigation
            this.prevBtn.attr('aria-disabled', false);
            this.nextBtn.attr('aria-disabled', false);
            
            // Add visual feedback for current position
            this.prevBtn.toggleClass('active', this.items.length > this.itemsToShow);
            this.nextBtn.toggleClass('active', this.items.length > this.itemsToShow);
        }

        announceChange(direction) {
            // Announce to screen readers
            if (window.OpticaVisionTheme && window.OpticaVisionTheme.announceToScreenReader) {
                const message = direction === 'next' ? 
                    'Mostrando siguientes productos' : 
                    'Mostrando productos anteriores';
                window.OpticaVisionTheme.announceToScreenReader(message);
            }
        }

        handleResize() {
            // Debounce resize events
            clearTimeout(this.resizeTimeout);
            this.resizeTimeout = setTimeout(() => {
                this.itemsToShow = this.getItemsToShow();
                this.itemWidth = this.getItemWidth();
                this.maxIndex = Math.max(0, this.items.length - this.itemsToShow);
                
                // Adjust current index if needed
                if (this.currentIndex > this.maxIndex) {
                    this.currentIndex = this.maxIndex;
                }
                
                this.updatePosition();
                this.updateButtons();
            }, 250);
        }

        destroy() {
            this.stopAutoplay();
            $(window).off('resize.carousel');
            this.carousel.off();
            this.track.off();
            this.prevBtn.off();
            this.nextBtn.off();
        }
    }

    // Initialize carousels
    function initCarousels() {
        $('.products-carousel, .brands-carousel').each(function() {
            if (!$(this).data('carousel-initialized')) {
                new OpticaVisionCarousel(this);
                $(this).data('carousel-initialized', true);
            }
        });
    }

    // Product carousel specific functionality
    function initProductCarousels() {
        // Quick view functionality
        $(document).on('click', '.quick-view-btn', function(e) {
            e.preventDefault();
            const productId = $(this).data('product-id');
            openQuickView(productId);
        });

        // Add to cart functionality
        $(document).on('click', '.add-to-cart-carousel-btn', function(e) {
            e.preventDefault();
            const $button = $(this);
            const productId = $button.data('product-id');
            const originalText = $button.text();
            
            $button.text('Agregando...').prop('disabled', true);
            
            // Add to cart via AJAX
            $.ajax({
                url: wc_add_to_cart_params.wc_ajax_url.toString().replace('%%endpoint%%', 'add_to_cart'),
                type: 'POST',
                data: {
                    product_id: productId,
                    quantity: 1
                },
                success: function(response) {
                    if (response.error && response.product_url) {
                        window.location = response.product_url;
                        return;
                    }
                    
                    // Trigger cart update events
                    $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $button]);
                    
                    $button.text('¡Agregado!').addClass('added');
                    
                    setTimeout(() => {
                        $button.text(originalText).removeClass('added').prop('disabled', false);
                    }, 2000);
                },
                error: function() {
                    $button.text('Error').addClass('error');
                    setTimeout(() => {
                        $button.text(originalText).removeClass('error').prop('disabled', false);
                    }, 2000);
                }
            });
        });
    }

    // Quick view modal
    function openQuickView(productId) {
        // Create modal if it doesn't exist
        if (!$('#quick-view-modal').length) {
            $('body').append(`
                <div id="quick-view-modal" class="quick-view-modal">
                    <div class="quick-view-overlay"></div>
                    <div class="quick-view-content">
                        <button class="quick-view-close" aria-label="Cerrar vista rápida">&times;</button>
                        <div class="quick-view-body">
                            <div class="loading-spinner"></div>
                        </div>
                    </div>
                </div>
            `);
        }

        const $modal = $('#quick-view-modal');
        const $body = $('.quick-view-body');
        
        $modal.addClass('active');
        $('body').addClass('quick-view-open');
        
        // Load product content
        $.ajax({
            url: opticavision_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'opticavision_quick_view',
                product_id: productId,
                nonce: opticavision_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $body.html(response.data);
                } else {
                    $body.html('<p>Error al cargar el producto.</p>');
                }
            },
            error: function() {
                $body.html('<p>Error al cargar el producto.</p>');
            }
        });

        // Close modal events
        $modal.find('.quick-view-close, .quick-view-overlay').on('click', closeQuickView);
        $(document).on('keydown.quickview', function(e) {
            if (e.keyCode === 27) { // Escape key
                closeQuickView();
            }
        });
    }

    function closeQuickView() {
        $('#quick-view-modal').removeClass('active');
        $('body').removeClass('quick-view-open');
        $(document).off('keydown.quickview');
    }

    // Brands carousel specific functionality
    function initBrandsCarousel() {
        $('.brands-carousel .brand-item').on('click', function(e) {
            // Add click tracking or other brand-specific functionality
            const brandName = $(this).find('.brand-name').text() || $(this).find('img').attr('alt');
            
            // Track brand click (if analytics is available)
            if (typeof gtag !== 'undefined') {
                gtag('event', 'brand_click', {
                    'brand_name': brandName
                });
            }
        });
    }

    // Initialize everything when DOM is ready
    $(document).ready(function() {
        initCarousels();
        initProductCarousels();
        initBrandsCarousel();
        
        // Re-initialize carousels when new content is loaded via AJAX
        $(document).on('opticavision:content_loaded', initCarousels);
    });

    // Expose carousel class globally
    window.OpticaVisionCarousel = OpticaVisionCarousel;

})(jQuery);

// Quick view modal styles
const quickViewStyles = `
<style>
.quick-view-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 10000;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.quick-view-modal.active {
    opacity: 1;
    visibility: visible;
}

.quick-view-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
}

.quick-view-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    border-radius: 8px;
    max-width: 800px;
    max-height: 80vh;
    width: 90%;
    overflow-y: auto;
}

.quick-view-close {
    position: absolute;
    top: 15px;
    right: 15px;
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    z-index: 10001;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: background-color 0.3s ease;
}

.quick-view-close:hover {
    background-color: #f5f5f5;
}

.quick-view-body {
    padding: 2rem;
}

.loading-spinner {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 200px;
}

.loading-spinner::after {
    content: '';
    width: 40px;
    height: 40px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #1a2b88;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

body.quick-view-open {
    overflow: hidden;
}

/* Quick View Product Layout */
.quick-view-product {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    max-width: 800px;
    padding: 0;
}

.quick-view-images img {
    width: 100%;
    border-radius: 8px;
}

.quick-view-details {
    display: flex;
    flex-direction: column;
}

.quick-view-details .product-title {
    font-size: 16px;
    font-weight: 400;
    color: #333;
    margin: 0 0 0.75rem 0;
    line-height: 1.4;
    text-align: center;
}

.quick-view-details .product-price {
    font-size: 1.75rem;
    font-weight: 700;
    margin-bottom: 1.25rem;
    text-align: center;
}

.quick-view-details .product-price del {
    font-size: 1.25rem;
    color: #999;
    margin-right: 0.5rem;
}

.quick-view-details .product-price ins {
    text-decoration: none;
    color: #ED1B2E;
}

.quick-view-details .product-description {
    color: #666;
    font-size: 14px;
    line-height: 1.6;
    margin-bottom: 1.25rem;
    text-align: center;
}

.quick-view-details .product-meta {
    display: flex;
    justify-content: center;
    gap: 1.5rem;
    font-size: 14px;
    color: #666;
    margin-bottom: 1.5rem;
}

.quick-view-details .product-meta .meta-item {
    display: flex;
    gap: 0.35rem;
}

.quick-view-details .product-meta .meta-item strong {
    font-weight: 600;
    color: #333;
}

.quick-view-details .cart {
    margin-bottom: 1rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
}

.quick-view-details .quantity-wrapper {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.quick-view-details .quantity-wrapper label {
    font-weight: 600;
    color: #333;
    font-size: 14px;
    margin: 0;
}

.quick-view-details .quantity-input {
    width: 70px;
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    text-align: center;
    font-size: 14px;
}

.quick-view-details .single_add_to_cart_button {
    width: 100%;
    max-width: 400px;
    padding: 0.875rem 2rem;
    background-color: #ED1B2E;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 14px;
    font-weight: 700;
    letter-spacing: 0.5px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-transform: uppercase;
}

.quick-view-details .single_add_to_cart_button:hover {
    background-color: #c51d30;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(237, 27, 46, 0.3);
}

.quick-view-actions {
    margin-top: 1rem;
    text-align: center;
}

.view-full-details {
    color: #ED1B2E;
    text-decoration: none;
    font-weight: 500;
    font-size: 14px;
    transition: color 0.3s ease;
}

.view-full-details:hover {
    color: #c51d30;
    text-decoration: underline;
}

@media (max-width: 768px) {
    .quick-view-content {
        width: 95%;
        max-height: 90vh;
    }
    
    .quick-view-body {
        padding: 1rem;
    }
    
    .quick-view-product {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
}
</style>
`;

// Inject styles
if (!document.getElementById('quick-view-styles')) {
    const styleElement = document.createElement('div');
    styleElement.id = 'quick-view-styles';
    styleElement.innerHTML = quickViewStyles;
    document.head.appendChild(styleElement);
}

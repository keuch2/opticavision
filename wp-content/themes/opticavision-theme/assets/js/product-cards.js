/**
 * Product Cards JavaScript
 * Funcionalidades para las tarjetas de productos
 */

jQuery(function($) {
    'use strict';

    // Product Cards Handler
    const ProductCards = {
        
        init: function() {
            this.bindEvents();
            this.initLazyLoading();
        },

        bindEvents: function() {
            // Add to cart buttons
            $(document).on('click', '.btn-add-to-cart', this.handleAddToCart);
            
            // Quick view buttons
            $(document).on('click', '.btn-quick-view', this.handleQuickView);
            
            // Wishlist buttons
            $(document).on('click', '.btn-wishlist', this.handleWishlist);
            
            // Product card hover effects
            $(document).on('mouseenter', '.product-card', this.handleCardHover);
            $(document).on('mouseleave', '.product-card', this.handleCardLeave);
        },

        handleAddToCart: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const productId = $button.data('product-id');
            const quantity = $button.data('quantity') || 1;
            
            // If it's a link (variable products), follow the link
            if ($button.is('a')) {
                window.location.href = $button.attr('href');
                return;
            }
            
            // Show loading state
            const originalText = $button.text();
            $button.text('Agregando...').prop('disabled', true);
            
            // AJAX add to cart
            $.ajax({
                url: wc_add_to_cart_params.wc_ajax_url.toString().replace('%%endpoint%%', 'add_to_cart'),
                type: 'POST',
                data: {
                    product_id: productId,
                    quantity: quantity
                },
                success: function(response) {
                    if (response.error && response.product_url) {
                        window.location = response.product_url;
                        return;
                    }
                    
                    // Update cart fragments
                    if (response.fragments) {
                        $.each(response.fragments, function(key, value) {
                            $(key).replaceWith(value);
                        });
                    }
                    
                    // Show success message
                    ProductCards.showNotification('Producto agregado al carrito', 'success');
                    
                    // Trigger cart updated event
                    $('body').trigger('added_to_cart', [response.fragments, response.cart_hash, $button]);
                },
                error: function() {
                    ProductCards.showNotification('Error al agregar el producto', 'error');
                },
                complete: function() {
                    $button.text(originalText).prop('disabled', false);
                }
            });
        },

        handleQuickView: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const productId = $button.data('product-id');
            
            // Show loading state
            $button.addClass('loading');
            
            // Create modal if it doesn't exist
            if (!$('#quick-view-modal').length) {
                $('body').append(`
                    <div id="quick-view-modal" class="modal quick-view-modal">
                        <div class="modal-overlay"></div>
                        <div class="modal-content">
                            <button class="modal-close">&times;</button>
                            <div class="modal-body"></div>
                        </div>
                    </div>
                `);
            }
            
            const $modal = $('#quick-view-modal');
            const $modalBody = $modal.find('.modal-body');
            
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
                        $modalBody.html(response.data);
                        $modal.addClass('active');
                        $('body').addClass('modal-open');
                    } else {
                        ProductCards.showNotification('Error al cargar el producto', 'error');
                    }
                },
                error: function() {
                    ProductCards.showNotification('Error al cargar el producto', 'error');
                },
                complete: function() {
                    $button.removeClass('loading');
                }
            });
        },

        handleWishlist: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const productId = $button.data('product-id');
            
            // Toggle wishlist state
            const isInWishlist = $button.hasClass('in-wishlist');
            
            $.ajax({
                url: opticavision_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: isInWishlist ? 'remove_from_wishlist' : 'add_to_wishlist',
                    product_id: productId,
                    nonce: opticavision_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $button.toggleClass('in-wishlist');
                        const message = isInWishlist ? 
                            'Producto eliminado de favoritos' : 
                            'Producto agregado a favoritos';
                        ProductCards.showNotification(message, 'success');
                    } else {
                        ProductCards.showNotification(response.data || 'Error en la operación', 'error');
                    }
                },
                error: function() {
                    ProductCards.showNotification('Error en la operación', 'error');
                }
            });
        },

        handleCardHover: function() {
            $(this).addClass('hovered');
        },

        handleCardLeave: function() {
            $(this).removeClass('hovered');
        },

        initLazyLoading: function() {
            // Intersection Observer for lazy loading
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
            }
        },

        showNotification: function(message, type = 'info') {
            // Remove existing notifications
            $('.product-notification').remove();
            
            // Create notification
            const $notification = $(`
                <div class="product-notification ${type}">
                    <span class="notification-message">${message}</span>
                    <button class="notification-close">&times;</button>
                </div>
            `);
            
            // Add to page
            $('body').append($notification);
            
            // Show notification
            setTimeout(() => $notification.addClass('show'), 100);
            
            // Auto hide after 3 seconds
            setTimeout(() => {
                $notification.removeClass('show');
                setTimeout(() => $notification.remove(), 300);
            }, 3000);
            
            // Manual close
            $notification.find('.notification-close').on('click', function() {
                $notification.removeClass('show');
                setTimeout(() => $notification.remove(), 300);
            });
        }
    };

    // Modal Handler
    const ModalHandler = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Close modal events
            $(document).on('click', '.modal-close, .modal-overlay', this.closeModal);
            $(document).on('keydown', this.handleKeydown);
        },

        closeModal: function(e) {
            if (e.target === this || $(e.target).hasClass('modal-close')) {
                $('.modal.active').removeClass('active');
                $('body').removeClass('modal-open');
            }
        },

        handleKeydown: function(e) {
            if (e.keyCode === 27) { // ESC key
                $('.modal.active').removeClass('active');
                $('body').removeClass('modal-open');
            }
        }
    };

    // Initialize when DOM is ready
    $(document).ready(function() {
        ProductCards.init();
        ModalHandler.init();
    });

    // Reinitialize after AJAX content loads
    $(document).ajaxComplete(function() {
        ProductCards.initLazyLoading();
    });

    // Export for global access
    window.OpticaVisionProductCards = ProductCards;
});

<?php
/**
 * The Template for displaying all single products
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product.php.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package OpticaVision_Theme
 * @version 8.2.0
 */

defined('ABSPATH') || exit;

get_header();

while (have_posts()) :
    the_post();
    $product = wc_get_product(get_the_ID());
    
    // Get product data
    $product_id = $product->get_id();
    $product_name = $product->get_name();
    $product_price = $product->get_price();
    $regular_price = $product->get_regular_price();
    $sale_price = $product->get_sale_price();
    
    // Improved image handling with multiple fallbacks
    $product_image_url = '';
    $product_alt = '';
    
    // Try to get featured image
    $thumbnail_id = get_post_thumbnail_id($product_id);
    
    if ($thumbnail_id) {
        $product_image = wp_get_attachment_image_src($thumbnail_id, 'large');
        if ($product_image && isset($product_image[0])) {
            $product_image_url = $product_image[0];
            $product_alt = get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true);
        }
    }
    
    // If no featured image, try product gallery images
    if (!$product_image_url) {
        $gallery_image_ids = $product->get_gallery_image_ids();
        
        if (!empty($gallery_image_ids)) {
            $first_gallery_image = wp_get_attachment_image_src($gallery_image_ids[0], 'large');
            if ($first_gallery_image && isset($first_gallery_image[0])) {
                $product_image_url = $first_gallery_image[0];
                $product_alt = get_post_meta($gallery_image_ids[0], '_wp_attachment_image_alt', true);
            }
        }
    }
    
    // Final fallback to placeholder
    if (!$product_image_url) {
        $product_image_url = wc_placeholder_img_src('large');
        $product_alt = $product_name;
    }
    
    // Ensure alt text exists
    if (!$product_alt) {
        $product_alt = $product_name;
    }
    
    $stock_quantity = $product->get_stock_quantity();
    $is_in_stock = $product->is_in_stock();
    $product_description = $product->get_short_description();
    
    // Calculate discount percentage
    $discount_percentage = 0;
    if ($product->is_on_sale() && $regular_price && $sale_price) {
        $discount_percentage = round((($regular_price - $sale_price) / $regular_price) * 100);
    }
    
    ?>

    <div class="single-product-page">
        <div class="container">

            <div class="product-layout">
                <!-- Product Image -->
                <div class="product-image-section">
                    <div class="product-image-container">
                        <img src="<?php echo esc_url($product_image_url); ?>" 
                             alt="<?php echo esc_attr($product_alt); ?>" 
                             class="product-main-image"
                             loading="lazy"
                             onerror="this.src='<?php echo esc_js(wc_placeholder_img_src('large')); ?>'; this.onerror=null;"
                             onload="console.log('Product image loaded: <?php echo esc_js($product_image_url); ?>');"
                             style="display: block !important; visibility: visible !important; opacity: 1 !important; position: relative !important; z-index: 1 !important;">
                        
                        <?php if ($discount_percentage > 0): ?>
                            <div class="discount-badge">
                                <?php printf(__('Ahorra %d%%', 'opticavision-theme'), $discount_percentage); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Product Info -->
                <div class="product-info-section">
                    <!-- Product Title -->
                    <h1 class="product-title"><?php echo esc_html($product_name); ?></h1>

                    <!-- Product Rating -->
                    <div class="product-rating">
                        <?php
                        $rating_count = $product->get_rating_count();
                        $average_rating = $product->get_average_rating();
                        if ($rating_count > 0):
                        ?>
                            <div class="stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="star <?php echo $i <= $average_rating ? 'filled' : ''; ?>">★</span>
                                <?php endfor; ?>
                            </div>
                            <span class="rating-count">(<?php echo $rating_count; ?>)</span>
                        <?php endif; ?>
                    </div>

                    <!-- Product Price -->
                    <div class="product-price">
                        <?php if ($product->is_type('variable')): ?>
                            <!-- Variable product - use filtered price (excludes variations without stock/price) -->
                            <?php 
                            // Obtener variaciones disponibles con stock y precio
                            $variations = $product->get_available_variations();
                            $valid_prices = array();
                            
                            foreach ($variations as $variation_data) {
                                $variation_id = $variation_data['variation_id'];
                                $variation = wc_get_product($variation_id);
                                
                                if (!$variation || !$variation->is_in_stock()) {
                                    continue;
                                }
                                
                                $price = $variation->get_price();
                                if (empty($price) || $price <= 0) {
                                    continue;
                                }
                                
                                $valid_prices[] = floatval($price);
                            }
                            
                            if (!empty($valid_prices)) {
                                $min_price = min($valid_prices);
                                ?>
                                <span class="current-price"><?php _e('desde', 'opticavision-theme'); ?> <?php echo get_woocommerce_currency_symbol(); ?><?php echo number_format($min_price, 2); ?></span>
                            <?php } else { ?>
                                <span class="current-price"><?php _e('Sin stock disponible', 'opticavision-theme'); ?></span>
                            <?php } ?>
                        <?php elseif ($product->is_on_sale()): ?>
                            <span class="current-price"><?php echo get_woocommerce_currency_symbol(); ?><?php echo number_format($sale_price, 2); ?></span>
                            <span class="original-price"><?php echo get_woocommerce_currency_symbol(); ?><?php echo number_format($regular_price, 2); ?></span>
                        <?php else: ?>
                            <span class="current-price"><?php echo get_woocommerce_currency_symbol(); ?><?php echo number_format($product_price, 2); ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Product SKU -->
                    <?php if ($product->get_sku()): ?>
                        <div class="product-sku">
                            <span class="sku-label"><?php _e('SKU:', 'opticavision-theme'); ?></span>
                            <span class="sku-value"><?php echo esc_html($product->get_sku()); ?></span>
                        </div>
                    <?php endif; ?>

                    <!-- Product Description -->
                    <?php if ($product_description): ?>
                        <div class="product-description">
                            <?php echo wp_kses_post($product_description); ?>
                        </div>
                    <?php endif; ?>

                    <!-- Stock Status -->
                    <?php if (!$product->is_type('variable')): ?>
                        <div class="stock-status">
                            <?php if ($is_in_stock): ?>
                                <span class="in-stock">
                                    <span class="stock-indicator"></span>
                                    <?php 
                                    if ($stock_quantity) {
                                        printf(__('%d in stock', 'opticavision-theme'), $stock_quantity);
                                    } else {
                                        _e('In stock', 'opticavision-theme');
                                    }
                                    ?>
                                </span>
                            <?php else: ?>
                                <span class="out-of-stock"><?php _e('Out of stock', 'opticavision-theme'); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- WooCommerce Native Product Form -->
                    <?php if ($product->is_type('variable')): ?>
                        <?php woocommerce_variable_add_to_cart(); ?>
                    <?php else: ?>
                        <form class="cart" method="post" enctype='multipart/form-data'>
                            <div class="product-actions">
                                <div class="quantity-selector">
                                    <label class="screen-reader-text" for="quantity"><?php _e('Cantidad', 'opticavision-theme'); ?></label>
                                    <button type="button" class="quantity-btn minus">-</button>
                                    <input type="number" id="quantity" class="input-text qty text quantity-input" step="1" min="1" max="<?php echo $stock_quantity ?: 999; ?>" name="quantity" value="1" title="<?php _e('Cantidad', 'opticavision-theme'); ?>" size="4" placeholder="" inputmode="numeric" />
                                    <button type="button" class="quantity-btn plus">+</button>
                                </div>

                                <button type="submit" name="add-to-cart" value="<?php echo esc_attr($product_id); ?>" class="single_add_to_cart_button button alt add-to-cart-btn">
                                    <?php _e('Agregar al Carrito', 'opticavision-theme'); ?>
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>


    <script>
    jQuery(document).ready(function($) {
        // Enhanced image loading
        function handleImageLoad() {
            const $img = $('.product-main-image');
            const $container = $('.product-image-container');
            
            // Add loading class
            $container.addClass('loading');
            
            $img.on('load', function() {
                $container.removeClass('loading');
                $(this).animate({ opacity: 1 }, 300);
            });
            
            $img.on('error', function() {
                $container.removeClass('loading');
                console.warn('Product image failed to load, using fallback');
                // The onerror attribute will handle the fallback
            });
            
            // If image is already loaded (cached)
            if ($img[0] && $img[0].complete) {
                $container.removeClass('loading');
            }
        }
        
        // Initialize image handling
        handleImageLoad();
        
        // Initialize quantity selectors with + and - buttons
        function initQuantityButtons() {
            // Target both simple and variable product quantity inputs
            const selectors = [
                '.quantity input[type="number"]',
                'input[name="quantity"]',
                '.qty',
                '.variations_form .quantity input',
                '.woocommerce-variation-add-to-cart input[type="number"]'
            ].join(', ');
            
            $(selectors).each(function() {
                const $input = $(this);
                const $container = $input.closest('.quantity');
                
                // Skip if already initialized
                if ($input.siblings('.quantity-btn').length > 0 || $input.parent('.qty-wrapper').length > 0) {
                    return;
                }
                
                // Add CSS classes
                $input.addClass('quantity-input');
                
                // Create minus button
                const $minusBtn = $('<button type="button" class="quantity-btn minus qty-btn" aria-label="Disminuir cantidad">−</button>');
                
                // Create plus button  
                const $plusBtn = $('<button type="button" class="quantity-btn plus qty-btn" aria-label="Aumentar cantidad">+</button>');
                
                // Add buttons directly to quantity container
                $input.before($minusBtn);
                $input.after($plusBtn);
                
                console.log('✅ Quantity buttons added to:', $container[0]);
            });
        }
        
        // Initialize immediately
        initQuantityButtons();
        
        // Re-initialize after DOM changes
        setTimeout(initQuantityButtons, 500);
        
        // Re-initialize after WooCommerce variations events
        $(document).on('woocommerce_update_variation_values woocommerce_variation_has_changed found_variation', function() {
            setTimeout(initQuantityButtons, 200);
        });
        
        // Also try with MutationObserver for dynamic content
        if (typeof MutationObserver !== 'undefined') {
            const observer = new MutationObserver(function(mutations) {
                let shouldInit = false;
                mutations.forEach(function(mutation) {
                    if (mutation.addedNodes.length > 0) {
                        $(mutation.addedNodes).find('input[name="quantity"], .quantity input').each(function() {
                            shouldInit = true;
                        });
                    }
                });
                if (shouldInit) {
                    setTimeout(initQuantityButtons, 100);
                }
            });
            
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        }
        
        // Quantity selector functionality
        $(document).on('click', '.quantity-btn.plus, .qty-btn.plus', function(e) {
            e.preventDefault();
            const $input = $(this).siblings('.quantity-input, input[name="quantity"]');
            const currentVal = parseInt($input.val()) || 1;
            const maxVal = parseInt($input.attr('max')) || 999;
            if (currentVal < maxVal) {
                $input.val(currentVal + 1).trigger('change');
            }
        });

        $(document).on('click', '.quantity-btn.minus, .qty-btn.minus', function(e) {
            e.preventDefault();
            const $input = $(this).siblings('.quantity-input, input[name="quantity"]');
            const currentVal = parseInt($input.val()) || 1;
            const minVal = parseInt($input.attr('min')) || 1;
            if (currentVal > minVal) {
                $input.val(currentVal - 1).trigger('change');
            }
        });

        // Initialize WooCommerce variations functionality
        if (typeof wc_add_to_cart_variation_params !== 'undefined') {
            $('.variations_form').each(function() {
                $(this).wc_variation_form();
            });
        }
        
        // Handle variation found event (when user selects a variation)
        $('.variations_form').on('found_variation', function(event, variation) {
            console.log('✅ Variation selected:', variation.variation_id, 'Price:', variation.display_price_html);
            
            // Update button state - remove disabled state
            const $button = $(this).find('.single_add_to_cart_button');
            $button.removeClass('disabled wc-variation-is-unavailable wc-variation-selection-needed')
                   .prop('disabled', false)
                   .attr('disabled', false);
            
            // Update button text if needed
            if (variation.is_purchasable && variation.is_in_stock) {
                $button.text($button.data('original-text') || 'Agregar al Carrito');
            }
        });
        
        // Handle when no variation is selected
        $('.variations_form').on('reset_data', function() {
            console.log('❌ Variation cleared');
            const $button = $(this).find('.single_add_to_cart_button');
            
            // Store original text if not already stored
            if (!$button.data('original-text')) {
                $button.data('original-text', $button.text());
            }
            
            $button.addClass('disabled wc-variation-selection-needed')
                   .prop('disabled', true)
                   .attr('disabled', true);
        });
        
        // Handle invalid/unavailable variations
        $('.variations_form').on('reset_image', function() {
            console.log('⚠️ Variation unavailable');
        });

        // Track product view for analytics
        if (typeof gtag !== 'undefined') {
            gtag('event', 'view_item', {
                'currency': 'USD',
                'value': <?php echo $product->get_price() ? $product->get_price() : 0; ?>,
                'items': [{
                    'item_id': '<?php echo esc_js($product->get_sku() ? $product->get_sku() : $product->get_id()); ?>',
                    'item_name': '<?php echo esc_js($product->get_name()); ?>',
                    'category': '<?php echo esc_js(wp_strip_all_tags(wc_get_product_category_list($product->get_id()))); ?>',
                    'quantity': 1,
                    'price': <?php echo $product->get_price() ? $product->get_price() : 0; ?>
                }]
            });
        }
    });
    </script>

</div>

<?php 
// Ensure WooCommerce variation scripts are loaded
wp_enqueue_script('wc-add-to-cart-variation');
wp_enqueue_script('wc-single-product');

// Simple script to initialize WooCommerce variations
wc_enqueue_js( "
    jQuery(document).ready(function($) {
        if (typeof wc_add_to_cart_variation_params !== 'undefined') {
            $('.variations_form').each(function() {
                $(this).wc_variation_form();
                console.log('Variation form initialized');
            });
        }
        
        $('.variation-select').on('change', function() {
            var selectedValue = $(this).val();
            console.log('User selected: ' + selectedValue);
            
            setTimeout(function() {
                $('.variations_form').trigger('check_variations');
            }, 100);
        });
    });
" );

endwhile; // End of the loop

get_footer(); 
?>

<?php
/**
 * Product Card Functions
 * 
 * Funciones para generar tarjetas de productos con el diseño personalizado
 * 
 * @package OpticaVision_Theme
 * @version 1.0.0
 */

defined('ABSPATH') || exit;

/**
 * Genera una tarjeta de producto con el diseño personalizado
 */
function opticavision_product_card($product_id, $args = array()) {
    $product = wc_get_product($product_id);
    
    if (!$product) {
        return '';
    }
    
    $defaults = array(
        'show_badges' => true,
        'show_category' => true,
        'show_actions' => true,
        'image_size' => 'woocommerce_thumbnail',
        'class' => ''
    );
    
    $args = wp_parse_args($args, $defaults);
    
    ob_start();
    ?>
    <div class="product-card <?php echo esc_attr($args['class']); ?>" data-product-id="<?php echo esc_attr($product_id); ?>">
        <div class="product-image-container">
            <?php if ($args['show_badges']): ?>
                <div class="product-badges">
                    <?php echo opticavision_get_product_badges($product); ?>
                </div>
            <?php endif; ?>
            
            <a href="<?php echo esc_url($product->get_permalink()); ?>" class="product-image-link">
                <?php
                $image_id = $product->get_image_id();
                if ($image_id) {
                    echo wp_get_attachment_image($image_id, $args['image_size'], false, array(
                        'class' => 'product-image',
                        'alt' => $product->get_name(),
                        'loading' => 'lazy'
                    ));
                } else {
                    echo '<img src="' . wc_placeholder_img_src($args['image_size']) . '" alt="' . esc_attr($product->get_name()) . '" class="product-image" loading="lazy">';
                }
                ?>
            </a>
        </div>
        
        <div class="product-content">
            <?php if ($args['show_category']): ?>
                <div class="product-category">
                    <?php echo opticavision_get_product_category($product); ?>
                </div>
            <?php endif; ?>
            
            <h3 class="product-title">
                <a href="<?php echo esc_url($product->get_permalink()); ?>">
                    <?php echo esc_html($product->get_name()); ?>
                </a>
            </h3>
            
            <div class="product-price">
                <?php echo opticavision_get_product_price_html($product); ?>
            </div>
            
            <?php if ($args['show_actions']): ?>
                <div class="product-actions">
                    <?php echo opticavision_get_product_actions($product); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
    
    return ob_get_clean();
}

/**
 * Obtiene los badges del producto
 */
function opticavision_get_product_badges($product) {
    $badges = array();
    
    // Badge de oferta
    if ($product->is_on_sale()) {
        $regular_price = (float) $product->get_regular_price();
        $sale_price = (float) $product->get_sale_price();
        
        if ($regular_price > 0 && $sale_price > 0) {
            $discount = round((($regular_price - $sale_price) / $regular_price) * 100);
            $badges[] = '<span class="product-badge sale">-' . $discount . '%</span>';
        } else {
            $badges[] = '<span class="product-badge sale">' . __('Oferta', 'opticavision-theme') . '</span>';
        }
    }
    
    // Badge de nuevo
    $created_date = $product->get_date_created();
    if ($created_date) {
        $days_ago = (time() - $created_date->getTimestamp()) / DAY_IN_SECONDS;
        if ($days_ago <= 30) { // Productos nuevos últimos 30 días
            $badges[] = '<span class="product-badge new">' . __('Nuevo', 'opticavision-theme') . '</span>';
        }
    }
    
    // Badge de destacado
    if ($product->is_featured()) {
        $badges[] = '<span class="product-badge featured">' . __('Destacado', 'opticavision-theme') . '</span>';
    }
    
    // Badge de sin stock - SOLO para productos simples
    if (!$product->is_in_stock() && !$product->is_type('variable')) {
        $badges[] = '<span class="product-badge out-of-stock">' . __('Sin Stock', 'opticavision-theme') . '</span>';
    }
    
    return implode('', $badges);
}

/**
 * Obtiene la categoría principal del producto
 */
function opticavision_get_product_category($product) {
    $categories = get_the_terms($product->get_id(), 'product_cat');
    
    if ($categories && !is_wp_error($categories)) {
        $category = reset($categories);
        return esc_html($category->name);
    }
    
    return '';
}

/**
 * Obtiene el HTML del precio del producto
 * Para productos variables, solo considera variaciones con stock y precio válido
 */
function opticavision_get_product_price_html($product) {
    $price_html = '';
    
    if ($product->get_type() === 'variable') {
        // Obtener todas las variaciones y filtrar las que tienen stock y precio
        $children = $product->get_children();
        $valid_prices = array();
        
        foreach ($children as $child_id) {
            $variation = wc_get_product($child_id);
            
            if (!$variation) {
                continue;
            }
            
            // Solo considerar variaciones con stock
            if (!$variation->is_in_stock()) {
                continue;
            }
            
            // Solo considerar variaciones con precio válido
            $price = $variation->get_price();
            if (empty($price) || $price <= 0) {
                continue;
            }
            
            $valid_prices[] = floatval($price);
        }
        
        if (!empty($valid_prices)) {
            $min_price = min($valid_prices);
            $max_price = max($valid_prices);
            
            if ($min_price === $max_price) {
                $price_html = '<span class="price-current">' . wc_price($min_price) . '</span>';
            } else {
                $price_html = '<span class="price-from">' . __('Desde', 'opticavision-theme') . ' ' . wc_price($min_price) . '</span>';
            }
        } else {
            $price_html = '<span class="price-current">' . __('Sin stock disponible', 'opticavision-theme') . '</span>';
        }
    } else {
        if ($product->is_on_sale()) {
            $price_html = '<span class="price-current">' . wc_price($product->get_sale_price()) . '</span>';
            $price_html .= '<span class="price-original">' . wc_price($product->get_regular_price()) . '</span>';
        } else {
            $price_html = '<span class="price-current">' . wc_price($product->get_price()) . '</span>';
        }
    }
    
    return $price_html;
}

/**
 * Obtiene los botones de acción del producto
 */
function opticavision_get_product_actions($product) {
    $actions = '';
    
    // Botón de agregar al carrito
    if ($product->is_purchasable() && $product->is_in_stock()) {
        if ($product->get_type() === 'simple') {
            $actions .= sprintf(
                '<button type="button" class="btn-add-to-cart" data-product-id="%d" data-quantity="1">%s</button>',
                $product->get_id(),
                __('Agregar al Carrito', 'opticavision-theme')
            );
        } else {
            $actions .= sprintf(
                '<a href="%s" class="btn-add-to-cart">%s</a>',
                esc_url($product->get_permalink()),
                __('Ver Opciones', 'opticavision-theme')
            );
        }
    } else {
        $actions .= sprintf(
            '<a href="%s" class="btn-add-to-cart">%s</a>',
            esc_url($product->get_permalink()),
            __('Ver Producto', 'opticavision-theme')
        );
    }
    
    // Botón de vista rápida
    $actions .= sprintf(
        '<button type="button" class="btn-quick-view" data-product-id="%d" title="%s">
            <i class="fas fa-eye" aria-hidden="true"></i>
        </button>',
        $product->get_id(),
        __('Vista Rápida', 'opticavision-theme')
    );
    
    // Botón de wishlist
    
    
    return $actions;
}

/**
 * Genera una grilla de productos
 */
function opticavision_products_grid($products, $args = array()) {
    if (empty($products)) {
        return '';
    }
    
    $defaults = array(
        'columns' => 4,
        'class' => '',
        'show_badges' => true,
        'show_category' => true,
        'show_actions' => true
    );
    
    $args = wp_parse_args($args, $defaults);
    
    ob_start();
    ?>
    <div class="products-grid <?php echo esc_attr($args['class']); ?>">
        <?php foreach ($products as $product_id): ?>
            <?php echo opticavision_product_card($product_id, $args); ?>
        <?php endforeach; ?>
    </div>
    <?php
    
    return ob_get_clean();
}

/**
 * Hook para personalizar el loop de productos de WooCommerce
 */
function opticavision_woocommerce_product_loop_start() {
    echo '<div class="products-grid">';
}

function opticavision_woocommerce_product_loop_end() {
    echo '</div>';
}

// Hooks para WooCommerce
add_action('woocommerce_output_related_products_args', function($args) {
    $args['posts_per_page'] = 4;
    $args['columns'] = 4;
    return $args;
});

// Personalizar el contenido del loop de productos
function opticavision_customize_product_loop() {
    // Remover hooks por defecto
    remove_action('woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10);
    remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5);
    remove_action('woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10);
    remove_action('woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10);
    remove_action('woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10);
    remove_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5);
    remove_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10);
    remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10);
    
    // Agregar nuestro contenido personalizado
    add_action('woocommerce_before_shop_loop_item', 'opticavision_custom_product_card_start', 10);
    add_action('woocommerce_after_shop_loop_item', 'opticavision_custom_product_card_end', 20);
}

function opticavision_custom_product_card_start() {
    global $product;
    echo opticavision_product_card($product->get_id());
}

function opticavision_custom_product_card_end() {
    // Cerrar la tarjeta personalizada
}

// Aplicar personalización en páginas de productos
add_action('woocommerce_before_shop_loop', 'opticavision_customize_product_loop', 5);

<?php
/**
 * Cart Page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/cart.php.
 *
 * @package OpticaVision_Theme
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_cart' ); ?>

<div class="woocommerce-cart-form-container">
    <form class="woocommerce-cart-form" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
        <?php do_action( 'woocommerce_before_cart_table' ); ?>

        <table class="shop_table shop_table_responsive cart woocommerce-cart-table" cellspacing="0">
            <thead>
                <tr>
                    <th class="product-remove"><span class="screen-reader-text"><?php esc_html_e( 'Remove item', 'woocommerce' ); ?></span></th>
                    <th class="product-thumbnail"><span class="screen-reader-text"><?php esc_html_e( 'Thumbnail image', 'woocommerce' ); ?></span></th>
                    <th class="product-name"><?php esc_html_e( 'Product', 'woocommerce' ); ?></th>
                    <th class="product-price"><?php esc_html_e( 'Price', 'woocommerce' ); ?></th>
                    <th class="product-quantity"><?php esc_html_e( 'Quantity', 'woocommerce' ); ?></th>
                    <th class="product-subtotal"><?php esc_html_e( 'Subtotal', 'woocommerce' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php do_action( 'woocommerce_before_cart_contents' ); ?>

                <?php
                foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
                    $_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
                    $product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

                    if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
                        $product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
                        ?>
                        <tr class="woocommerce-cart-form__cart-item <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">

                            <td class="product-remove">
                                <?php
                                echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                    'woocommerce_cart_item_remove_link',
                                    sprintf(
                                        '<a href="%s" class="remove" aria-label="%s" data-product_id="%s" data-product_sku="%s"><i class="fas fa-times" aria-hidden="true"></i></a>',
                                        esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
                                        esc_html__( 'Remove this item', 'woocommerce' ),
                                        esc_attr( $product_id ),
                                        esc_attr( $_product->get_sku() )
                                    ),
                                    $cart_item_key
                                );
                                ?>
                            </td>

                            <td class="product-thumbnail">
                            <?php
                            $thumbnail = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key );

                            if ( ! $product_permalink ) {
                                echo $thumbnail; // PHPCS: XSS ok.
                            } else {
                                printf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $thumbnail ); // PHPCS: XSS ok.
                            }
                            ?>
                            </td>

                            <td class="product-name" data-title="<?php esc_attr_e( 'Product', 'woocommerce' ); ?>">
                            <?php
                            if ( ! $product_permalink ) {
                                echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key ) . '&nbsp;' );
                            } else {
                                echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', sprintf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $_product->get_name() ), $cart_item, $cart_item_key ) );
                            }

                            do_action( 'woocommerce_after_cart_item_name', $cart_item, $cart_item_key );

                            // Meta data.
                            echo wc_get_formatted_cart_item_data( $cart_item ); // PHPCS: XSS ok.

                            // Backorder notification.
                            if ( $_product->backorders_require_notification() && $_product->is_on_backorder( $cart_item['quantity'] ) ) {
                                echo wp_kses_post( apply_filters( 'woocommerce_cart_item_backorder_notification', '<p class="backorder_notification">' . esc_html__( 'Available on backorder', 'woocommerce' ) . '</p>', $product_id ) );
                            }
                            ?>
                            </td>

                            <td class="product-price" data-title="<?php esc_attr_e( 'Price', 'woocommerce' ); ?>">
                                <?php
                                    echo apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key ); // PHPCS: XSS ok.
                                ?>
                            </td>

                            <td class="product-quantity" data-title="<?php esc_attr_e( 'Quantity', 'woocommerce' ); ?>">
                            <?php
                            if ( $_product->is_sold_individually() ) {
                                $product_quantity = sprintf( '1 <input type="hidden" name="cart[%s][qty]" value="1" />', $cart_item_key );
                            } else {
                                $product_quantity = woocommerce_quantity_input(
                                    array(
                                        'input_name'   => "cart[{$cart_item_key}][qty]",
                                        'input_value'  => $cart_item['quantity'],
                                        'max_value'    => $_product->get_max_purchase_quantity(),
                                        'min_value'    => '0',
                                        'product_name' => $_product->get_name(),
                                    ),
                                    $_product,
                                    false
                                );
                            }

                            echo apply_filters( 'woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item ); // PHPCS: XSS ok.
                            ?>
                            </td>

                            <td class="product-subtotal" data-title="<?php esc_attr_e( 'Subtotal', 'woocommerce' ); ?>">
                                <?php
                                    echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); // PHPCS: XSS ok.
                                ?>
                            </td>
                        </tr>
                        <?php
                    }
                }
                ?>

                <?php do_action( 'woocommerce_cart_contents' ); ?>

                <tr>
                    <td colspan="6" class="actions">

                        <?php if ( wc_coupons_enabled() ) { ?>
                            <div class="coupon">
                                <label for="coupon_code"><?php esc_html_e( 'Coupon:', 'woocommerce' ); ?></label> 
                                <input type="text" name="coupon_code" class="input-text" id="coupon_code" value="" placeholder="<?php esc_attr_e( 'Coupon code', 'woocommerce' ); ?>" /> 
                                <button type="submit" class="button" name="apply_coupon" value="<?php esc_attr_e( 'Apply coupon', 'woocommerce' ); ?>">
                                    <i class="fas fa-tag" aria-hidden="true"></i>
                                    <?php esc_attr_e( 'Apply coupon', 'woocommerce' ); ?>
                                </button>
                                <?php do_action( 'woocommerce_cart_coupon' ); ?>
                            </div>
                        <?php } ?>

                        <button type="submit" class="button" name="update_cart" value="<?php esc_attr_e( 'Update cart', 'woocommerce' ); ?>">
                            <i class="fas fa-sync-alt" aria-hidden="true"></i>
                            <?php esc_html_e( 'Update cart', 'woocommerce' ); ?>
                        </button>

                        <?php do_action( 'woocommerce_cart_actions' ); ?>

                        <?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
                    </td>
                </tr>

                <?php do_action( 'woocommerce_after_cart_contents' ); ?>
            </tbody>
        </table>
        <?php do_action( 'woocommerce_after_cart_table' ); ?>
    </form>

    <?php do_action( 'woocommerce_before_cart_collaterals' ); ?>

    <div class="cart-collaterals">
        <?php
            /**
             * Cart collaterals hook.
             *
             * @hooked woocommerce_cross_sell_display
             * @hooked woocommerce_cart_totals - 10
             */
            do_action( 'woocommerce_cart_collaterals' );
        ?>
    </div>
</div>

<?php do_action( 'woocommerce_after_cart' ); ?>

<style>
/* Cart Page Styles */
.woocommerce-cart-form-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.shop_table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 30px;
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
}

.shop_table th,
.shop_table td {
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.shop_table th {
    font-weight: 600;
    color: #333;
}

.product-remove a {
    color: #dc3545;
    font-size: 18px;
    text-decoration: none;
}

.product-remove a:hover {
    color: #c82333;
}

.product-thumbnail img {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 4px;
}

.product-name a {
    color: #333;
    text-decoration: none;
    font-weight: 500;
}

.product-name a:hover {
    color: #dc3545;
}

.product-price,
.product-subtotal {
    font-weight: 600;
    color: #dc3545;
}

.quantity input {
    width: 60px;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    text-align: center;
}

.actions {
    background: #f8f9fa;
}

.coupon {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    margin-right: 20px;
}

.coupon input {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.button {
    background: #000000;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
}

.button:hover {
    background: #102064;
}

.cart-collaterals {
    margin-top: 30px;
}

/* Responsive */
@media (max-width: 768px) {
    .shop_table,
    .shop_table thead,
    .shop_table tbody,
    .shop_table th,
    .shop_table td,
    .shop_table tr {
        display: block;
    }
    
    .shop_table thead tr {
        position: absolute;
        top: -9999px;
        left: -9999px;
    }
    
    .shop_table tr {
        border: 1px solid #ccc;
        margin-bottom: 10px;
        padding: 10px;
        border-radius: 8px;
    }
    
    .shop_table td {
        border: none;
        position: relative;
        padding-left: 50%;
    }
    
    .shop_table td:before {
        content: attr(data-title) ": ";
        position: absolute;
        left: 6px;
        width: 45%;
        padding-right: 10px;
        white-space: nowrap;
        font-weight: 600;
    }
    
    .actions {
        text-align: center;
    }
    
    .coupon {
        display: block;
        margin-bottom: 15px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Manejar eliminación de productos del carrito con AJAX
    $('.woocommerce').on('click', 'a.remove', function(e) {
        e.preventDefault();
        
        var $thisbutton = $(this);
        var $row = $thisbutton.closest('tr.cart_item');
        
        // Confirmar eliminación
        if (!confirm('¿Estás seguro de que quieres eliminar este producto?')) {
            return false;
        }
        
        // Agregar clase de carga
        $row.css('opacity', '0.5');
        $thisbutton.css('opacity', '0.3');
        
        // Hacer request AJAX para eliminar
        $.ajax({
            type: 'GET',
            url: $thisbutton.attr('href'),
            dataType: 'html',
            success: function(response) {
                // Recargar fragmentos del carrito (mini-cart, totales, etc)
                $(document.body).trigger('wc_fragment_refresh');
                
                // Animar y eliminar la fila
                $row.fadeOut(300, function() {
                    $(this).remove();
                    
                    // Si no hay más productos, recargar la página para mostrar "carrito vacío"
                    if ($('.woocommerce-cart-form__cart-item').length === 0) {
                        window.location.reload();
                    }
                });
                
                // Actualizar totales
                $(document.body).trigger('update_checkout');
            },
            error: function() {
                alert('Error al eliminar el producto. Por favor, intenta nuevamente.');
                $row.css('opacity', '1');
                $thisbutton.css('opacity', '1');
            }
        });
        
        return false;
    });
});
</script>

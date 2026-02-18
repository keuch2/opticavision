<?php
/**
 * Empty cart page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/cart-empty.php.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.0.1
 */

defined( 'ABSPATH' ) || exit;

/*
 * Removemos el mensaje por defecto de WooCommerce para evitar duplicación
 * @hooked wc_empty_cart_message - 10
 */
// do_action( 'woocommerce_cart_is_empty' );

if ( wc_get_page_id( 'shop' ) > 0 ) : ?>
    <div class="empty-cart-container">
        <h2 class="empty-cart-title"><?php esc_html_e( 'Tu carrito está vacío', 'opticavision-theme' ); ?></h2>
        
        <p class="return-to-shop">
            <a class="button wc-backward" href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>">
                <?php
                    /**
                     * Filter "Return To Shop" text.
                     *
                     * @since 4.6.0
                     * @param string $default_text Default text.
                     */
                    echo esc_html( apply_filters( 'woocommerce_return_to_shop_text', __( 'Volver a la Tienda', 'opticavision-theme' ) ) );
                ?>
            </a>
        </p>
    </div>

    <style>
    .empty-cart-container {
        text-align: center;
        padding: 60px 20px;
        max-width: 500px;
        margin: 0 auto;
    }

    .empty-cart-icon {
        margin-bottom: 30px;
    }

    .empty-cart-icon svg {
        opacity: 0.3;
    }

    .empty-cart-title {
        font-size: 24px;
        margin-bottom: 15px;
        color: #333;
        font-weight: 600;
    }

    .empty-cart-message {
        font-size: 16px;
        color: #666;
        line-height: 1.6;
        margin-bottom: 30px;
    }

    .return-to-shop .button {
        background: #e53e3e;
        color: white;
        padding: 12px 30px;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 600;
        display: inline-block;
        transition: background 0.3s ease;
    }

    .return-to-shop .button:hover {
        background: #c53030;
        color: white;
    }
    </style>

<?php endif; ?>

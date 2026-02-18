<?php
/**
 * Proceed to checkout button
 *
 * Contains the markup for the proceed to checkout button on the cart.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/proceed-to-checkout-button.php.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>

<a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="checkout-button button alt wc-forward wp-element-button">
	<?php esc_html_e( 'Proceder al Pago', 'opticavision-theme' ); ?>
</a>

<style>
.checkout-button {
	width: 100% !important;
	background: #e53e3e !important;
	color: white !important;
	padding: 15px 30px !important;
	border: none !important;
	border-radius: 6px !important;
	font-size: 16px !important;
	font-weight: 600 !important;
	text-transform: uppercase !important;
	letter-spacing: 0.5px !important;
	cursor: pointer !important;
	transition: background 0.3s ease !important;
	text-decoration: none !important;
	display: block !important;
	text-align: center !important;
	box-shadow: 0 2px 4px rgba(229, 62, 62, 0.2) !important;
}

.checkout-button:hover {
	background: #c53030 !important;
	color: white !important;
	box-shadow: 0 4px 8px rgba(229, 62, 62, 0.3) !important;
	transform: translateY(-1px);
}

.checkout-button:focus {
	outline: 2px solid #e53e3e !important;
	outline-offset: 2px !important;
}

.checkout-button:active {
	transform: translateY(0);
	box-shadow: 0 1px 2px rgba(229, 62, 62, 0.2) !important;
}

@media (max-width: 768px) {
	.checkout-button {
		padding: 18px 30px !important;
		font-size: 16px !important;
	}
}
</style>

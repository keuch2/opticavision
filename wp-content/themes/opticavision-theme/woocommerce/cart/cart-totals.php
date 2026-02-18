<?php
/**
 * Cart totals
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/cart-totals.php.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 2.3.6
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="cart_totals <?php echo ( WC()->customer->has_calculated_shipping() ) ? 'calculated_shipping' : ''; ?>">

	<?php do_action( 'woocommerce_before_cart_totals' ); ?>

	<h2><?php esc_html_e( 'Resumen del Carrito', 'opticavision-theme' ); ?></h2>

	<table cellspacing="0" class="shop_table shop_table_responsive">

		<tr class="cart-subtotal">
			<th><?php esc_html_e( 'Subtotal', 'opticavision-theme' ); ?></th>
			<td data-title="<?php esc_attr_e( 'Subtotal', 'opticavision-theme' ); ?>"><?php wc_cart_totals_subtotal_html(); ?></td>
		</tr>

		<?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
			<tr class="cart-discount coupon-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
				<th><?php echo esc_html( __( 'Cupón:', 'opticavision-theme' ) . ' ' . $code ); ?></th>
				<td data-title="<?php echo esc_attr( __( 'Cupón:', 'opticavision-theme' ) . ' ' . $code ); ?>"><?php wc_cart_totals_coupon_html( $coupon ); ?></td>
			</tr>
		<?php endforeach; ?>

		<?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>

			<?php do_action( 'woocommerce_cart_totals_before_shipping' ); ?>

			<?php wc_cart_totals_shipping_html(); ?>

			<?php do_action( 'woocommerce_cart_totals_after_shipping' ); ?>

		<?php elseif ( WC()->cart->needs_shipping() && 'yes' === get_option( 'woocommerce_enable_shipping_calc' ) ) : ?>

			<tr class="shipping">
				<th><?php esc_html_e( 'Envío', 'opticavision-theme' ); ?></th>
				<td data-title="<?php esc_attr_e( 'Envío', 'opticavision-theme' ); ?>"><?php woocommerce_shipping_calculator(); ?></td>
			</tr>

		<?php endif; ?>

		<?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
			<tr class="fee">
				<th><?php echo esc_html( $fee->name ); ?></th>
				<td data-title="<?php echo esc_attr( $fee->name ); ?>"><?php wc_cart_totals_fee_html( $fee ); ?></td>
			</tr>
		<?php endforeach; ?>

		<?php
		if ( wc_tax_enabled() && ! WC()->cart->display_prices_including_tax() ) {
			$taxable_address = WC()->customer->get_taxable_address();
			$estimated_text  = '';

			if ( WC()->customer->is_customer_outside_base() && ! WC()->customer->has_calculated_shipping() ) {
				/* translators: %s location. */
				$estimated_text = sprintf( ' <small>' . esc_html__( '(estimado para %s)', 'opticavision-theme' ) . '</small>', WC()->countries->estimated_for_prefix( $taxable_address[0] ) . WC()->countries->countries[ $taxable_address[0] ] );
			}

			if ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) {
				foreach ( WC()->cart->get_tax_totals() as $code => $tax ) { ?>
					<tr class="tax-rate tax-rate-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
						<th><?php echo esc_html( $tax->label ) . $estimated_text; ?></th>
						<td data-title="<?php echo esc_attr( $tax->label ); ?>"><?php echo wp_kses_post( $tax->formatted_amount ); ?></td>
					</tr>
					<?php
				}
			} else { ?>
				<tr class="tax-total">
					<th><?php echo esc_html( WC()->countries->tax_or_vat() ) . $estimated_text; ?></th>
					<td data-title="<?php echo esc_attr( WC()->countries->tax_or_vat() ); ?>"><?php wc_cart_totals_taxes_total_html(); ?></td>
				</tr>
				<?php
			}
		}
		?>

		<?php do_action( 'woocommerce_cart_totals_before_order_total' ); ?>

		<tr class="order-total">
			<th><?php esc_html_e( 'Total', 'opticavision-theme' ); ?></th>
			<td data-title="<?php esc_attr_e( 'Total', 'opticavision-theme' ); ?>"><?php wc_cart_totals_order_total_html(); ?></td>
		</tr>

		<?php do_action( 'woocommerce_cart_totals_after_order_total' ); ?>

	</table>

	<div class="wc-proceed-to-checkout">
		<?php do_action( 'woocommerce_proceed_to_checkout' ); ?>
	</div>

	<?php do_action( 'woocommerce_after_cart_totals' ); ?>

</div>

<style>
.cart_totals {
}

.cart_totals h2 {
	margin-bottom: 20px;
	font-size: 20px;
	font-weight: 600;
	color: #333;
}

.cart_totals .shop_table {
	width: 100%;
	border-collapse: collapse;
	margin-bottom: 20px;
}

.cart_totals .shop_table th,
.cart_totals .shop_table td {
	padding: 12px 0;
	border-bottom: 1px solid #e9ecef;
	text-align: left;
}

.cart_totals .shop_table th {
	font-weight: 500;
	color: #666;
	width: 50%;
}

.cart_totals .shop_table td {
	font-weight: 600;
	color: #333;
	text-align: right;
}

.cart_totals .order-total th,
.cart_totals .order-total td {
	font-size: 18px;
	font-weight: 700;
	color: #333;
	border-bottom: none;
	border-top: 2px solid #dee2e6;
	padding-top: 15px;
}

.wc-proceed-to-checkout {
	margin-top: 20px;
}

.wc-proceed-to-checkout .checkout-button {
	width: 100%;
	background: #e53e3e;
	color: white;
	padding: 15px 30px;
	border: none;
	border-radius: 6px;
	font-size: 16px;
	font-weight: 600;
	text-transform: uppercase;
	letter-spacing: 0.5px;
	cursor: pointer;
	transition: background 0.3s ease;
	text-decoration: none;
	display: block;
	text-align: center;
}

.wc-proceed-to-checkout .checkout-button:hover {
	background: #c53030;
	color: white;
}

@media (max-width: 768px) {
	.cart_totals {
		padding: 20px;
	}
	
	.cart_totals .shop_table th,
	.cart_totals .shop_table td {
		display: block;
		width: 100%;
		text-align: left;
		padding: 8px 0;
	}
	
	.cart_totals .shop_table td {
		margin-bottom: 15px;
		font-size: 16px;
	}
	
	.cart_totals .shop_table td:before {
		content: attr(data-title) ": ";
		font-weight: 500;
		color: #666;
	}
}
</style>

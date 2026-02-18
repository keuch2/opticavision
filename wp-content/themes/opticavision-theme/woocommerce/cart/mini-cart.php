<?php
/**
 * Mini-cart
 *
 * Contains the markup for the mini-cart, used by the cart widget.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/mini-cart.php.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.9.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_mini_cart' ); ?>

<?php if ( ! WC()->cart->is_empty() ) : ?>

	<div class="mini-cart-header">
		<h3 class="mini-cart-title"><?php esc_html_e( 'Mi Carrito', 'opticavision-theme' ); ?></h3>
		<span class="mini-cart-count"><?php echo WC()->cart->get_cart_contents_count(); ?> <?php esc_html_e( 'artículos', 'opticavision-theme' ); ?></span>
	</div>

	<ul class="woocommerce-mini-cart cart_list product_list_widget <?php echo esc_attr( $args['list_class'] ); ?>">
		<?php
		do_action( 'woocommerce_before_mini_cart_contents' );

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
			$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

			if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_widget_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
				/**
				 * This filter is documented in woocommerce/templates/cart/cart.php.
				 *
				 * @since 2.1.0
				 */
				$product_name      = apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key );
				$thumbnail         = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key );
				$product_price     = apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key );
				$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
				?>
				<li class="woocommerce-mini-cart-item <?php echo esc_attr( apply_filters( 'woocommerce_mini_cart_item_class', 'mini_cart_item', $cart_item, $cart_item_key ) ); ?>">
					<div class="mini-cart-item-image">
						<?php if ( empty( $product_permalink ) ) : ?>
							<?php echo $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php else : ?>
							<a href="<?php echo esc_url( $product_permalink ); ?>">
								<?php echo $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</a>
						<?php endif; ?>
					</div>
					
					<div class="mini-cart-item-details">
						<div class="mini-cart-item-name">
							<?php if ( empty( $product_permalink ) ) : ?>
								<?php echo wp_kses_post( $product_name ); ?>
							<?php else : ?>
								<a href="<?php echo esc_url( $product_permalink ); ?>">
									<?php echo wp_kses_post( $product_name ); ?>
								</a>
							<?php endif; ?>
						</div>
						
						<div class="mini-cart-item-meta">
							<?php echo wc_get_formatted_cart_item_data( $cart_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
						
						<div class="mini-cart-item-price">
							<span class="quantity"><?php echo sprintf( '%s &times;', $cart_item['quantity'] ); ?></span>
							<span class="amount"><?php echo $product_price; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						</div>
					</div>
					
					<div class="mini-cart-item-remove">
						<?php
						echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							'woocommerce_cart_item_remove_link',
							sprintf(
								'<a href="%s" class="remove remove_from_cart_button" aria-label="%s" data-product_id="%s" data-cart_item_key="%s" data-product_sku="%s">&times;</a>',
								esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
								/* translators: %s is the product name */
								esc_attr( sprintf( __( 'Eliminar %s del carrito', 'opticavision-theme' ), wp_strip_all_tags( $product_name ) ) ),
								esc_attr( $product_id ),
								esc_attr( $cart_item_key ),
								esc_attr( $_product->get_sku() )
							),
							$cart_item_key
						);
						?>
					</div>
				</li>
				<?php
			}
		}

		do_action( 'woocommerce_mini_cart_contents' );
		?>
	</ul>

	<div class="mini-cart-footer">
		<div class="mini-cart-total">
			<strong><?php esc_html_e( 'Total:', 'opticavision-theme' ); ?> <?php echo WC()->cart->get_cart_subtotal(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
		</div>

		<div class="mini-cart-buttons">
			<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="button wc-forward mini-cart-view-cart">
				<?php esc_html_e( 'Ver Carrito', 'opticavision-theme' ); ?>
			</a>
			<a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="button checkout wc-forward mini-cart-checkout">
				<?php esc_html_e( 'Finalizar Compra', 'opticavision-theme' ); ?>
			</a>
		</div>
	</div>

<?php else : ?>

	<div class="mini-cart-empty">
		<div class="empty-cart-icon">
			<svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M7 4V2C7 1.45 7.45 1 8 1H16C16.55 1 17 1.45 17 2V4H20C20.55 4 21 4.45 21 5S20.55 6 20 6H19V19C19 20.1 18.1 21 17 21H7C5.9 21 5 20.1 5 19V6H4C3.45 6 3 5.55 3 5S3.45 4 4 4H7ZM9 3V4H15V3H9ZM7 6V19H17V6H7Z" fill="#ccc"/>
			</svg>
		</div>
		<p class="empty-message"><?php esc_html_e( 'Tu carrito está vacío.', 'opticavision-theme' ); ?></p>
		<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="button continue-shopping-mini">
			<?php esc_html_e( 'Ir de Compras', 'opticavision-theme' ); ?>
		</a>
	</div>

<?php endif; ?>

<?php do_action( 'woocommerce_after_mini_cart' ); ?>

<style>
.woocommerce-mini-cart {
	list-style: none;
	margin: 0;
	padding: 0;
	max-height: 400px;
	overflow-y: auto;
}

.mini-cart-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 15px 20px;
	border-bottom: 1px solid #e9ecef;
	background: #f8f9fa;
}

.mini-cart-title {
	font-size: 16px;
	font-weight: 600;
	margin: 0;
	color: #333;
}

.mini-cart-count {
	font-size: 12px;
	color: #666;
	background: #e9ecef;
	padding: 4px 8px;
	border-radius: 12px;
}

.woocommerce-mini-cart-item {
	display: flex;
	align-items: center;
	padding: 15px 20px;
	border-bottom: 1px solid #f0f0f0;
	transition: background-color 0.2s ease;
}

.woocommerce-mini-cart-item:hover {
	background: #f8f9fa;
}

.mini-cart-item-image {
	width: 50px;
	height: 50px;
	flex-shrink: 0;
	margin-right: 12px;
	border-radius: 4px;
	overflow: hidden;
}

.mini-cart-item-image img {
	width: 100%;
	height: 100%;
	object-fit: cover;
}

.mini-cart-item-details {
	flex: 1;
	min-width: 0;
}

.mini-cart-item-name {
	font-size: 14px;
	font-weight: 500;
	margin-bottom: 4px;
	line-height: 1.3;
}

.mini-cart-item-name a {
	color: #333;
	text-decoration: none;
}

.mini-cart-item-name a:hover {
	color: #e53e3e;
}

.mini-cart-item-meta {
	font-size: 12px;
	color: #666;
	margin-bottom: 4px;
}

.mini-cart-item-price {
	font-size: 14px;
	font-weight: 600;
	color: #333;
}

.mini-cart-item-price .quantity {
	color: #666;
	font-weight: normal;
}

.mini-cart-item-remove {
	margin-left: 10px;
}

.mini-cart-item-remove .remove {
	width: 20px;
	height: 20px;
	border-radius: 50%;
	background: #e9ecef;
	color: #666;
	text-decoration: none;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 14px;
	transition: all 0.2s ease;
}

.mini-cart-item-remove .remove:hover {
	background: #e53e3e;
	color: white;
}

.mini-cart-footer {
	padding: 20px;
	background: #f8f9fa;
	border-top: 1px solid #e9ecef;
}

.mini-cart-total {
	text-align: center;
	margin-bottom: 15px;
	font-size: 16px;
	color: #333;
}

.mini-cart-buttons {
	display: flex;
	gap: 10px;
}

.mini-cart-buttons .button {
	flex: 1;
	padding: 10px 15px;
	border-radius: 4px;
	text-align: center;
	text-decoration: none;
	font-size: 14px;
	font-weight: 600;
	transition: all 0.2s ease;
}

.mini-cart-view-cart {
	background: #6c757d;
	color: white;
}

.mini-cart-view-cart:hover {
	background: #545b62;
	color: white;
}

.mini-cart-checkout {
	background: #e53e3e;
	color: white;
}

.mini-cart-checkout:hover {
	background: #c53030;
	color: white;
}

/* Empty cart styles */
.mini-cart-empty {
	text-align: center;
	padding: 40px 20px;
}

.empty-cart-icon {
	margin-bottom: 15px;
	opacity: 0.3;
}

.empty-message {
	color: #666;
	margin-bottom: 20px;
	font-size: 14px;
}

.continue-shopping-mini {
	background: #e53e3e;
	color: white;
	padding: 10px 20px;
	border-radius: 4px;
	text-decoration: none;
	font-size: 14px;
	font-weight: 600;
	transition: background 0.2s ease;
}

.continue-shopping-mini:hover {
	background: #c53030;
	color: white;
}

/* Scrollbar styling */
.woocommerce-mini-cart::-webkit-scrollbar {
	width: 4px;
}

.woocommerce-mini-cart::-webkit-scrollbar-track {
	background: #f1f1f1;
}

.woocommerce-mini-cart::-webkit-scrollbar-thumb {
	background: #ccc;
	border-radius: 2px;
}

.woocommerce-mini-cart::-webkit-scrollbar-thumb:hover {
	background: #999;
}

@media (max-width: 768px) {
	.mini-cart-header {
		padding: 12px 15px;
	}
	
	.woocommerce-mini-cart-item {
		padding: 12px 15px;
	}
	
	.mini-cart-item-image {
		width: 40px;
		height: 40px;
		margin-right: 10px;
	}
	
	.mini-cart-footer {
		padding: 15px;
	}
	
	.mini-cart-buttons {
		flex-direction: column;
	}
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
	// Handle remove item from mini cart
	const miniCart = document.querySelector('.woocommerce-mini-cart');
	if (miniCart) {
		miniCart.addEventListener('click', function(e) {
			if (e.target.classList.contains('remove_from_cart_button')) {
				e.preventDefault();
				
				const removeLink = e.target;
				const cartItemKey = removeLink.dataset.cart_item_key;
				
				// Add loading state
				removeLink.style.opacity = '0.5';
				removeLink.style.pointerEvents = 'none';
				
				// Make AJAX request to remove item
				fetch('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded',
					},
					body: new URLSearchParams({
						action: 'woocommerce_remove_from_cart',
						cart_item_key: cartItemKey,
						_wpnonce: '<?php echo wp_create_nonce( 'woocommerce-cart' ); ?>'
					})
				})
				.then(response => response.json())
				.then(data => {
					if (data.success) {
						// Trigger cart update
						document.body.dispatchEvent(new Event('wc_fragment_refresh'));
					} else {
						// Restore button state on error
						removeLink.style.opacity = '1';
						removeLink.style.pointerEvents = 'auto';
					}
				})
				.catch(error => {
					console.error('Error:', error);
					removeLink.style.opacity = '1';
					removeLink.style.pointerEvents = 'auto';
				});
			}
		});
	}
});
</script>

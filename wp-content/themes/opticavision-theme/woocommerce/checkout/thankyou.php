<?php
/**
 * Thankyou page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/thankyou.php.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 8.1.0
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="thankyou-page-wrapper">
	<?php if ( $order ) : ?>
		
		<div class="thankyou-header">
			<div class="success-icon">
				<svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<circle cx="12" cy="12" r="10" fill="#10b981"/>
					<path d="M9 12l2 2 4-4" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</div>
			
			<?php if ( $order->has_status( 'failed' ) ) : ?>
				<h1 class="thankyou-title error"><?php esc_html_e( 'Oops! Algo salió mal con tu pedido', 'opticavision-theme' ); ?></h1>
				<p class="thankyou-message error"><?php esc_html_e( 'Lamentablemente, tu pedido no pudo ser procesado correctamente. Por favor, inténtalo de nuevo o contacta con nuestro equipo de soporte.', 'opticavision-theme' ); ?></p>
			<?php else : ?>
				<h1 class="thankyou-title"><?php esc_html_e( '¡Gracias por tu compra!', 'opticavision-theme' ); ?></h1>
				<p class="thankyou-message"><?php esc_html_e( 'Tu pedido ha sido recibido y está siendo procesado. Te enviaremos una confirmación por email con todos los detalles.', 'opticavision-theme' ); ?></p>
			<?php endif; ?>
		</div>

		<div class="order-details-wrapper">
			<div class="order-summary">
				<h2><?php esc_html_e( 'Detalles del Pedido', 'opticavision-theme' ); ?></h2>
				
				<div class="order-info-grid">
					<div class="order-info-item">
						<strong><?php esc_html_e( 'Número de Pedido:', 'opticavision-theme' ); ?></strong>
						<span><?php echo $order->get_order_number(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					</div>
					
					<div class="order-info-item">
						<strong><?php esc_html_e( 'Fecha:', 'opticavision-theme' ); ?></strong>
						<span><?php echo wc_format_datetime( $order->get_date_created() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					</div>
					
					<?php if ( is_user_logged_in() && $order->get_user_id() === get_current_user_id() && $order->get_billing_email() ) : ?>
						<div class="order-info-item">
							<strong><?php esc_html_e( 'Email:', 'opticavision-theme' ); ?></strong>
							<span><?php echo $order->get_billing_email(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						</div>
					<?php endif; ?>
					
					<div class="order-info-item">
						<strong><?php esc_html_e( 'Total:', 'opticavision-theme' ); ?></strong>
						<span class="order-total"><?php echo $order->get_formatted_order_total(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					</div>
					
					<?php if ( $order->get_payment_method_title() ) : ?>
						<div class="order-info-item">
							<strong><?php esc_html_e( 'Método de pago:', 'opticavision-theme' ); ?></strong>
							<span><?php echo wp_kses_post( $order->get_payment_method_title() ); ?></span>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<?php do_action( 'woocommerce_thankyou_order_received_text', $order ); ?>

			<?php do_action( 'woocommerce_thankyou', $order->get_id() ); ?>
		</div>

		<div class="thankyou-actions">
			<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="button continue-shopping">
				<?php esc_html_e( 'Continuar Comprando', 'opticavision-theme' ); ?>
			</a>
			
			<?php if ( is_user_logged_in() ) : ?>
				<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="button view-account">
					<?php esc_html_e( 'Ver Mi Cuenta', 'opticavision-theme' ); ?>
				</a>
			<?php endif; ?>
		</div>

	<?php else : ?>
		
		<div class="thankyou-header">
			<div class="success-icon">
				<svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<circle cx="12" cy="12" r="10" fill="#10b981"/>
					<path d="M9 12l2 2 4-4" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</div>
			<h1 class="thankyou-title"><?php esc_html_e( '¡Gracias por tu compra!', 'opticavision-theme' ); ?></h1>
			<p class="thankyou-message"><?php echo apply_filters( 'woocommerce_thankyou_order_received_text', esc_html__( 'Tu pedido ha sido recibido correctamente.', 'opticavision-theme' ), null ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
		</div>

		<div class="thankyou-actions">
			<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="button continue-shopping">
				<?php esc_html_e( 'Continuar Comprando', 'opticavision-theme' ); ?>
			</a>
		</div>

	<?php endif; ?>
</div>

<style>
.thankyou-page-wrapper {
	max-width: 800px;
	margin: 0 auto;
	padding: 40px 20px;
	text-align: center;
}

.thankyou-header {
	margin-bottom: 40px;
}

.success-icon {
	margin-bottom: 20px;
}

.thankyou-title {
	font-size: 32px;
	font-weight: 600;
	color: #333;
	margin-bottom: 15px;
}

.thankyou-title.error {
	color: #e53e3e;
}

.thankyou-message {
	font-size: 16px;
	color: #666;
	line-height: 1.6;
	max-width: 600px;
	margin: 0 auto;
}

.thankyou-message.error {
	color: #e53e3e;
}

.order-details-wrapper {
	background: #f8f9fa;
	border-radius: 8px;
	padding: 30px;
	margin-bottom: 30px;
	text-align: left;
}

.order-summary h2 {
	font-size: 22px;
	font-weight: 600;
	color: #333;
	margin-bottom: 20px;
	text-align: center;
}

.order-info-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
	gap: 15px;
}

.order-info-item {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 12px 0;
	border-bottom: 1px solid #e9ecef;
}

.order-info-item:last-child {
	border-bottom: none;
}

.order-info-item strong {
	color: #666;
	font-weight: 500;
}

.order-info-item span {
	color: #333;
	font-weight: 600;
}

.order-total {
	color: #e53e3e !important;
	font-size: 18px !important;
}

.thankyou-actions {
	display: flex;
	gap: 15px;
	justify-content: center;
	flex-wrap: wrap;
}

.thankyou-actions .button {
	padding: 12px 30px;
	border-radius: 6px;
	font-weight: 600;
	text-decoration: none;
	transition: all 0.3s ease;
}

.continue-shopping {
	background: #e53e3e;
	color: white;
}

.continue-shopping:hover {
	background: #c53030;
	color: white;
}

.view-account {
	background: #6c757d;
	color: white;
}

.view-account:hover {
	background: #545b62;
	color: white;
}

/* Order details styling */
.woocommerce-order-details {
	margin-top: 30px;
	background: #fff;
	border-radius: 8px;
	padding: 25px;
	border: 1px solid #e9ecef;
}

.woocommerce-order-details .woocommerce-table {
	width: 100%;
	border-collapse: collapse;
}

.woocommerce-order-details .woocommerce-table th,
.woocommerce-order-details .woocommerce-table td {
	padding: 12px;
	text-align: left;
	border-bottom: 1px solid #e9ecef;
}

.woocommerce-order-details .woocommerce-table th {
	background: #f8f9fa;
	font-weight: 600;
	color: #333;
}

@media (max-width: 768px) {
	.thankyou-page-wrapper {
		padding: 30px 15px;
	}
	
	.thankyou-title {
		font-size: 28px;
	}
	
	.order-details-wrapper {
		padding: 20px;
	}
	
	.order-info-grid {
		grid-template-columns: 1fr;
	}
	
	.order-info-item {
		flex-direction: column;
		align-items: flex-start;
		gap: 5px;
	}
	
	.thankyou-actions {
		flex-direction: column;
		align-items: center;
	}
	
	.thankyou-actions .button {
		width: 100%;
		max-width: 300px;
	}
}

@media (max-width: 480px) {
	.thankyou-title {
		font-size: 24px;
	}
	
	.order-details-wrapper {
		padding: 15px;
	}
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
	// Add animation to success icon
	const successIcon = document.querySelector('.success-icon svg');
	if (successIcon) {
		successIcon.style.opacity = '0';
		successIcon.style.transform = 'scale(0.5)';
		
		setTimeout(() => {
			successIcon.style.transition = 'all 0.5s ease';
			successIcon.style.opacity = '1';
			successIcon.style.transform = 'scale(1)';
		}, 200);
	}
	
	// Add some confetti effect (optional)
	if (window.location.search.includes('order-received')) {
		// You can add a confetti library here for celebration effect
		console.log('🎉 ¡Pedido completado exitosamente!');
	}
});
</script>

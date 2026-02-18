<?php
/**
 * Checkout Form - COPIADO DE WOOCOMMERCE ORIGINAL
 * Template override para OpticaVision con estructura original
 *
 * @package OpticaVision_Theme  
 * @version 9.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


do_action( 'woocommerce_before_checkout_form', $checkout );

// If checkout registration is disabled and not logged in, the user cannot checkout.
if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'Debes iniciar sesión para finalizar tu compra.', 'opticavision-theme' ) ) );
	return;
}

?>
<form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data" aria-label="<?php echo esc_attr__( 'Checkout', 'woocommerce' ); ?>">

	<?php if ( $checkout->get_checkout_fields() ) : ?>

		<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

		<div class="opticavision-checkout-grid">
			<!-- Columna Izquierda: Formularios -->
			<div class="checkout-forms-column">
				<!-- Detalles de Facturación -->
				<div class="checkout-section">
					<?php do_action( 'woocommerce_checkout_billing' ); ?>
				</div>

				<!-- Información Adicional -->
				<div class="checkout-section checkout-additional">
					<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>
				</div>
			</div>

			<!-- Columna Derecha: Resumen del Pedido -->
			<div class="checkout-order-column">
				<?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>
				
				<h3 id="order_review_heading"><?php esc_html_e( 'Tu Pedido', 'opticavision-theme' ); ?></h3>
				
				<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>

				<div id="order_review" class="woocommerce-checkout-review-order">
					<?php do_action( 'woocommerce_checkout_order_review' ); ?>
				</div>

				<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>
			</div>
		</div>

	<?php endif; ?>

</form>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>

<style>
/* ==================================
   CHECKOUT LIMPIO - OPTICAVISION
   ================================== */

/* Container Principal */
.woocommerce-checkout {
	margin: 0 auto;
	font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
	background: #ffffff;
}

/* Título Checkout */
.opticavision-checkout-header {
	text-align: center;
	margin-bottom: 30px;
}

.opticavision-checkout-header .page-title {
	font-size: 32px;
	font-weight: 700;
	color: #333;
	margin: 0;
	padding-bottom: 20px;
	border-bottom: 3px solid #e53e3e;
}

/* Layout Principal: 2 Columnas con Floats */
.opticavision-checkout-grid {
	width: 100%;
	overflow: hidden;
}

/* Columna de Formularios (izquierda) */
.checkout-forms-column {
	float: left;
	width: 50%;
	padding-right: 30px;
	background: #ffffff;
}

/* Columna del Pedido (derecha) */
.checkout-order-column {
	float: right;
	width: 50%;
	background: #ffffff;
	padding: 25px;
	border-radius: 4px;
	border: 1px solid #e0e0e0;
	position: sticky;
	top: 20px;
}

/* Títulos de Sección - Sin emojis, limpios */
.woocommerce-billing-fields h3,
.woocommerce-additional-fields h3 {
	font-size: 18px;
	font-weight: 700;
	color: #333;
	margin: 0 0 20px 0;
	padding: 0 0 12px 0;
	border-bottom: 2px solid #e53e3e;
}

#order_review_heading {
	font-size: 18px;
	font-weight: 700;
	color: #333;
	margin: 0 0 20px 0;
	padding: 0 0 12px 0;
	border-bottom: 2px solid #e53e3e;
}

/* Campos del Formulario */
.woocommerce-checkout .form-row {
	display: block !important;
	margin-bottom: 20px;
}

.woocommerce-checkout .form-row label {
	display: block !important;
	font-weight: 600;
	font-size: 14px;
	color: #34495e;
	margin-bottom: 8px;
	letter-spacing: 0.3px;
}

.woocommerce-checkout .form-row label .required {
	color: #e53e3e;
	font-weight: 700;
	margin-left: 3px;
}

/* Inputs, Selects y Textareas - Limpios */
.woocommerce-checkout .form-row input[type="text"],
.woocommerce-checkout .form-row input[type="email"],
.woocommerce-checkout .form-row input[type="tel"],
.woocommerce-checkout .form-row select,
.woocommerce-checkout .form-row textarea {
	width: 100%;
	padding: 10px 12px;
	border: 1px solid #ddd;
	border-radius: 4px;
	font-size: 14px;
	color: #333;
	background: #ffffff;
	transition: border-color 0.2s ease;
	font-family: inherit;
}

.woocommerce-checkout .form-row input[type="text"]:focus,
.woocommerce-checkout .form-row input[type="email"]:focus,
.woocommerce-checkout .form-row input[type="tel"]:focus,
.woocommerce-checkout .form-row select:focus,
.woocommerce-checkout .form-row textarea:focus {
	outline: none;
	border-color: #e53e3e;
	box-shadow: none;
}

/* Campos de 2 Columnas */
.woocommerce-checkout .form-row-first,
.woocommerce-checkout .form-row-last {
}

.woocommerce-checkout .form-row-last {
}

.woocommerce-checkout .form-row-wide {
	width: 100%;
	clear: both;
	float: none;
}

/* Limpiar floats */
.woocommerce-billing-fields:after,
.woocommerce-shipping-fields:after {
	content: "";
	display: table;
	clear: both;
}

/* Selects */
.woocommerce-checkout select {
	appearance: auto;
	cursor: pointer;
}

/* Resumen del Pedido */
#order_review {
	background: transparent;
	padding: 0;
	border: none;
	box-shadow: none;
}

/* Tabla de Productos - Limpia */
.woocommerce-checkout-review-order-table {
	width: 100%;
	margin-bottom: 20px;
	border-collapse: collapse;
}

.woocommerce-checkout-review-order-table thead th {
	background: #f9f9f9;
	padding: 12px 10px;
	font-weight: 600;
	color: #333;
	text-align: left;
	font-size: 13px;
}

.woocommerce-checkout-review-order-table tbody td {
	padding: 12px 10px;
	border-bottom: 1px solid #eee;
	font-size: 14px;
	color: #666;
}

.woocommerce-checkout-review-order-table tbody tr:last-child td {
	border-bottom: none;
}

.woocommerce-checkout-review-order-table tfoot th,
.woocommerce-checkout-review-order-table tfoot td {
	padding: 12px 10px;
	font-weight: 600;
	font-size: 14px;
	border-top: 1px solid #ddd;
}

.woocommerce-checkout-review-order-table .order-total th,
.woocommerce-checkout-review-order-table .order-total td {
	font-size: 18px;
	font-weight: 700;
	color: #e53e3e;
	background: #fff;
	padding: 15px 10px;
}

/* Métodos de Pago - Limpios */
#payment {
	background: #fff;
	padding: 20px 0;
	border: none;
	margin-top: 20px;
}

#payment .payment_methods {
	list-style: none;
	padding: 0;
	margin: 0 0 20px 0;
}

#payment .payment_method {
	background: #fafafa;
	border: 1px solid #ddd;
	border-radius: 4px;
	margin-bottom: 10px;
	overflow: hidden;
}

#payment .payment_method label {
	display: flex;
	align-items: center;
	padding: 12px 15px;
	cursor: pointer;
	font-weight: 500;
	font-size: 14px;
	color: #333;
	margin: 0;
}

#payment .payment_method input[type="radio"] {
	margin-right: 10px;
	cursor: pointer;
}

/* Ocultar el texto de privacidad que aparece sobre Bancard */
#payment .woocommerce-privacy-policy-text {
	display: none !important;
}

#payment .wc-terms-and-conditions {
	display: none !important;
}

/* Ocultar completamente el label de Bancard */
#payment .payment_method_bancard label {
	display: none !important;
}

/* Mostrar solo la imagen de Bancard */
#payment .payment_method_bancard {
	background: url('<?php echo get_template_directory_uri(); ?>/pago-seguro.png') no-repeat center center !important;
	background-size: 150px auto !important;
	min-height: 80px;
	padding: 15px !important;
}

#payment .payment_method_bancard input[type="radio"] {
	position: absolute;
	left: 15px;
	top: 50%;
	transform: translateY(-50%);
}

/* Botón Realizar Pedido - Reducido y debajo del bloque */
#place_order {
	width: 100%;
	background: #e53e3e;
	color: white;
	border: none;
	padding: 12px 30px;
	font-size: 15px;
	font-weight: 600;
	border-radius: 4px;
	cursor: pointer;
	transition: background 0.2s ease;
	text-transform: none;
	letter-spacing: normal;
	box-shadow: none;
	display: block;
	margin: 15px 0 0 0;
}

#place_order:hover {
	background: #c62828;
	box-shadow: none;
	transform: none;
}

#place_order:active {
	background: #b71c1c;
}

/* Notas del Pedido (Información Adicional) - Simplificada */
.woocommerce-additional-fields {
	background: #ffffff;
	padding: 0;
	border: none;
	box-shadow: none;
	margin-top: 30px;
}

.woocommerce-additional-fields textarea {
	min-height: 100px;
	resize: vertical;
}

/* Política de Privacidad */
.woocommerce-privacy-policy-text {
	background: #f9f9f9;
	padding: 15px;
	border-radius: 4px;
	border-left: 3px solid #e53e3e;
	font-size: 12px;
	color: #666;
	margin: 15px 0;
	line-height: 1.5;
}

/* Errores de Validación */
.woocommerce-invalid input,
.woocommerce-invalid select,
.woocommerce-invalid textarea {
	border-color: #dc3545 !important;
}

.woocommerce-error {
	background: #fff5f5;
	border-left: 3px solid #dc3545;
	padding: 12px 15px;
	border-radius: 4px;
	margin-bottom: 20px;
	color: #dc3545;
	font-size: 14px;
}

/* Loading State */
.woocommerce-checkout.processing {
	opacity: 0.6;
	pointer-events: none;
}

/* Ocultar campo de código postal completamente */
.woocommerce-checkout .form-row.hidden,
#billing_postcode_field,
#shipping_postcode_field {
	display: none !important;
	visibility: hidden !important;
	height: 0 !important;
	margin: 0 !important;
	padding: 0 !important;
}

/* Clearfix para asegurar que el footer no se distorsione */
.woocommerce-checkout:after,
.opticavision-checkout-grid:after,
.checkout-forms-column:after {
	content: "";
	display: table;
	clear: both;
}

/* Fix específico para body y #page */
body.woocommerce-checkout,
#page {
	overflow-x: hidden;
}

body.woocommerce-checkout:after,
#page:after {
	content: "";
	display: table;
	clear: both;
}

/* Asegurar que form termine con clearfix */
form.woocommerce-checkout:after {
	content: "";
	display: block;
	clear: both;
}

/* Responsive Design - Limpio */
@media (max-width: 1024px) {
	.checkout-forms-column,
	.checkout-order-column {
		float: none;
		width: 100%;
		padding-right: 0;
		margin-bottom: 30px;
	}
}

@media (max-width: 768px) {
	.woocommerce-checkout {
		padding: 20px 15px;
	}
	
	.checkout-order-column {
		padding: 20px;
	}
	
	.woocommerce-checkout .form-row-first,
	.woocommerce-checkout .form-row-last {
		width: 100%;
		float: none;
	}
	
	#place_order {
		width: 100%;
	}
}

@media (max-width: 480px) {
	.woocommerce-checkout {
		padding: 15px 10px;
		background: #ffffff;
	}
	
	.checkout-order-column {
		padding: 15px;
	}
	
	.woocommerce-checkout .form-row input,
	.woocommerce-checkout .form-row select,
	.woocommerce-checkout .form-row textarea {
		font-size: 16px; /* Evitar zoom en iOS */
	}
}
</style>

<script>
jQuery(function($) {
	// Aplicar imagen de Bancard al cargar la página
	var bancardLabel = $('.payment_method_bancard label');
	if (bancardLabel.length) {
		var imgUrl = '<?php echo get_template_directory_uri(); ?>/pago-seguro.png';
		bancardLabel.css({
			'background-image': 'url(' + imgUrl + ')',
			'background-repeat': 'no-repeat',
			'background-position': '15px center',
			'background-size': '150px auto',
			'padding-left': '180px',
			'min-height': '80px',
			'display': 'flex',
			'align-items': 'center'
		});
		// Ocultar cualquier imagen dentro del label
		bancardLabel.find('img').hide();
	}
});
</script>

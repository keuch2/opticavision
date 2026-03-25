<?php
/**
 * Plugin Name:       MCO Promociones WooCommerce
 * Plugin URI:        https://opticavision.com.py
 * Description:       Gestiona promociones de WooCommerce: descuentos por porcentaje aplicados masivamente a grupos de productos, con fechas de vigencia programables y reversión automática.
 * Version:           1.0.0
 * Author:            MCO / OpticaVision
 * Author URI:        https://opticavision.com.py
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       mco-promociones
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * WC requires at least: 7.0
 *
 * @package MCO_Promociones
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Constantes del plugin.
define( 'MCO_PROMO_VERSION', '1.0.0' );
define( 'MCO_PROMO_PLUGIN_FILE', __FILE__ );
define( 'MCO_PROMO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MCO_PROMO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Verifica que WooCommerce esté activo antes de inicializar el plugin.
 *
 * @return void
 */
function mco_promo_verificar_dependencias() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'mco_promo_aviso_woocommerce_requerido' );
		deactivate_plugins( plugin_basename( __FILE__ ) );
		return;
	}
}

/**
 * Muestra aviso de administración cuando WooCommerce no está activo.
 *
 * @return void
 */
function mco_promo_aviso_woocommerce_requerido() {
	echo '<div class="notice notice-error"><p>';
	echo esc_html__( 'MCO Promociones WooCommerce requiere que WooCommerce esté instalado y activo.', 'mco-promociones' );
	echo '</p></div>';
}

/**
 * Carga las clases del plugin.
 *
 * @return void
 */
function mco_promo_cargar_clases() {
	require_once MCO_PROMO_PLUGIN_DIR . 'includes/class-mco-promo-cpt.php';
	require_once MCO_PROMO_PLUGIN_DIR . 'includes/class-mco-promo-engine.php';
	require_once MCO_PROMO_PLUGIN_DIR . 'includes/class-mco-promo-scheduler.php';
	require_once MCO_PROMO_PLUGIN_DIR . 'includes/class-mco-promo-admin.php';
}

/**
 * Inicializa el plugin después de que WordPress y los plugins estén cargados.
 *
 * @return void
 */
function mco_promo_init() {
	mco_promo_verificar_dependencias();

	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	mco_promo_cargar_clases();

	// Inicializar CPT.
	$cpt = new MCO_Promo_CPT();
	$cpt->init();

	// Inicializar scheduler.
	$scheduler = new MCO_Promo_Scheduler();
	$scheduler->init();

	// Inicializar admin solo en el panel de administración.
	if ( is_admin() ) {
		$admin = new MCO_Promo_Admin();
		$admin->init();
	}

	// Registrar endpoint AJAX.
	add_action( 'wp_ajax_mco_promo_buscar_productos', 'mco_promo_ajax_buscar_productos' );
	add_action( 'wp_ajax_mco_promo_verificar_conflictos', 'mco_promo_ajax_verificar_conflictos' );
}
add_action( 'plugins_loaded', 'mco_promo_init' );

/**
 * Acciones al activar el plugin.
 *
 * @return void
 */
function mco_promo_activar() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die(
			esc_html__( 'MCO Promociones WooCommerce requiere que WooCommerce esté instalado y activo.', 'mco-promociones' ),
			esc_html__( 'Error de activación', 'mco-promociones' ),
			array( 'back_link' => true )
		);
	}

	// Registrar el cron al activar.
	require_once MCO_PROMO_PLUGIN_DIR . 'includes/class-mco-promo-scheduler.php';
	MCO_Promo_Scheduler::activar();

	// Forzar flush de rewrite rules para el CPT.
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'mco_promo_activar' );

/**
 * Acciones al desactivar el plugin.
 *
 * @return void
 */
function mco_promo_desactivar() {
	// Eliminar el cron al desactivar.
	require_once MCO_PROMO_PLUGIN_DIR . 'includes/class-mco-promo-scheduler.php';
	MCO_Promo_Scheduler::desactivar();

	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'mco_promo_desactivar' );

/**
 * Handler AJAX para búsqueda de productos.
 *
 * @return void
 */
function mco_promo_ajax_buscar_productos() {
	check_ajax_referer( 'mco_promo_nonce', 'nonce' );

	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_send_json_error( array( 'message' => __( 'No autorizado.', 'mco-promociones' ) ), 403 );
	}

	$busqueda = isset( $_POST['s'] ) ? sanitize_text_field( wp_unslash( $_POST['s'] ) ) : '';
	$pagina   = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
	$por_pagina = 20;

	$args = array(
		'status'       => 'publish',
		'type'         => array( 'simple', 'variable' ),
		'limit'        => $por_pagina,
		'page'         => $pagina,
		'return'       => 'objects',
	);

	if ( ! empty( $busqueda ) ) {
		$args['s'] = $busqueda;
	}

	$query    = new WC_Product_Query( $args );
	$products = $query->get_products();

	// Contar total para paginación.
	$args_count           = $args;
	$args_count['limit']  = -1;
	$args_count['return'] = 'ids';
	$query_count          = new WC_Product_Query( $args_count );
	$total_ids            = $query_count->get_products();
	$total_pages          = (int) ceil( count( $total_ids ) / $por_pagina );

	$resultado = array();
	foreach ( $products as $product ) {
		$tipo = $product->get_type();
		// Excluir agrupados y externos.
		if ( in_array( $tipo, array( 'grouped', 'external' ), true ) ) {
			continue;
		}

		$thumbnail_id  = $product->get_image_id();
		$thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url( $thumbnail_id, 'thumbnail' ) : wc_placeholder_img_src( 'thumbnail' );

		$resultado[] = array(
			'id'            => $product->get_id(),
			'name'          => $product->get_name(),
			'sku'           => $product->get_sku(),
			'regular_price' => $product->get_regular_price(),
			'thumbnail_url' => $thumbnail_url,
			'type'          => $tipo,
		);
	}

	wp_send_json_success(
		array(
			'products'    => $resultado,
			'total_pages' => $total_pages,
		)
	);
}

/**
 * Handler AJAX para verificar conflictos entre promociones.
 *
 * @return void
 */
function mco_promo_ajax_verificar_conflictos() {
	check_ajax_referer( 'mco_promo_nonce', 'nonce' );

	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_send_json_error( array( 'message' => __( 'No autorizado.', 'mco-promociones' ) ), 403 );
	}

	$producto_ids = isset( $_POST['producto_ids'] ) ? array_map( 'absint', (array) $_POST['producto_ids'] ) : array();
	$excluir_id   = isset( $_POST['excluir_promo_id'] ) ? absint( $_POST['excluir_promo_id'] ) : 0;

	if ( empty( $producto_ids ) ) {
		wp_send_json_success( array( 'conflictos' => array() ) );
	}

	require_once MCO_PROMO_PLUGIN_DIR . 'includes/class-mco-promo-engine.php';
	$engine     = new MCO_Promo_Engine();
	$conflictos = $engine->detectar_conflictos( $producto_ids, $excluir_id );

	wp_send_json_success( array( 'conflictos' => $conflictos ) );
}

<?php
/**
 * Registro del Custom Post Type para Promociones.
 *
 * @package MCO_Promociones
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clase MCO_Promo_CPT
 *
 * Registra el Custom Post Type `mco_promocion`.
 */
class MCO_Promo_CPT {

	/**
	 * Inicializa los hooks del CPT.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'init', array( $this, 'registrar_cpt' ) );
	}

	/**
	 * Registra el Custom Post Type mco_promocion.
	 *
	 * @return void
	 */
	public function registrar_cpt() {
		$labels = array(
			'name'               => _x( 'Promociones', 'post type general name', 'mco-promociones' ),
			'singular_name'      => _x( 'Promoción', 'post type singular name', 'mco-promociones' ),
			'menu_name'          => _x( 'Promociones', 'admin menu', 'mco-promociones' ),
			'name_admin_bar'     => _x( 'Promoción', 'add new on admin bar', 'mco-promociones' ),
			'add_new'            => _x( 'Agregar nueva', 'promotion', 'mco-promociones' ),
			'add_new_item'       => __( 'Agregar nueva Promoción', 'mco-promociones' ),
			'new_item'           => __( 'Nueva Promoción', 'mco-promociones' ),
			'edit_item'          => __( 'Editar Promoción', 'mco-promociones' ),
			'view_item'          => __( 'Ver Promoción', 'mco-promociones' ),
			'all_items'          => __( 'Todas las Promociones', 'mco-promociones' ),
			'search_items'       => __( 'Buscar Promociones', 'mco-promociones' ),
			'not_found'          => __( 'No se encontraron promociones.', 'mco-promociones' ),
			'not_found_in_trash' => __( 'No se encontraron promociones en la papelera.', 'mco-promociones' ),
		);

		$args = array(
			'labels'             => $labels,
			'description'        => __( 'Promociones de descuento para productos WooCommerce.', 'mco-promociones' ),
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => false, // La UI la manejamos manualmente.
			'show_in_menu'       => false,
			'query_var'          => false,
			'rewrite'            => false,
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title' ),
		);

		register_post_type( 'mco_promocion', $args );
	}
}

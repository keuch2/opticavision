<?php
/**
 * Lógica de la pantalla de administración del plugin.
 *
 * @package MCO_Promociones
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clase MCO_Promo_Admin
 *
 * Gestiona las pantallas de administración: listado, creación, edición,
 * acciones y log/historial de promociones.
 */
class MCO_Promo_Admin {

	/**
	 * Inicializa los hooks de administración.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'registrar_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'encolar_assets' ) );
		add_action( 'admin_post_mco_promo_guardar', array( $this, 'manejar_guardar' ) );
		add_action( 'admin_post_mco_promo_eliminar', array( $this, 'manejar_eliminar' ) );
		add_action( 'admin_post_mco_promo_aplicar', array( $this, 'manejar_aplicar' ) );
		add_action( 'admin_post_mco_promo_revertir', array( $this, 'manejar_revertir' ) );
		add_action( 'admin_post_mco_promo_limpiar_log', array( $this, 'manejar_limpiar_log' ) );
	}

	/**
	 * Registra el menú principal y submenús en el admin de WordPress.
	 *
	 * @return void
	 */
	public function registrar_menu() {
		add_menu_page(
			__( 'MCO Promociones', 'mco-promociones' ),
			__( 'Promociones', 'mco-promociones' ),
			'manage_woocommerce',
			'mco-promociones',
			array( $this, 'pagina_listado' ),
			'dashicons-tag',
			57
		);

		add_submenu_page(
			'mco-promociones',
			__( 'Todas las Promociones', 'mco-promociones' ),
			__( 'Todas las Promociones', 'mco-promociones' ),
			'manage_woocommerce',
			'mco-promociones',
			array( $this, 'pagina_listado' )
		);

		add_submenu_page(
			'mco-promociones',
			__( 'Nueva Promoción', 'mco-promociones' ),
			__( 'Nueva Promoción', 'mco-promociones' ),
			'manage_woocommerce',
			'mco-promociones-nueva',
			array( $this, 'pagina_formulario' )
		);

		add_submenu_page(
			'mco-promociones',
			__( 'Historial', 'mco-promociones' ),
			__( 'Historial', 'mco-promociones' ),
			'manage_woocommerce',
			'mco-promociones-historial',
			array( $this, 'pagina_historial' )
		);
	}

	/**
	 * Encola CSS y JS del plugin en las páginas del admin propias.
	 *
	 * @param string $hook_suffix Sufijo del hook de la página actual.
	 * @return void
	 */
	public function encolar_assets( $hook_suffix ) {
		$paginas_propias = array(
			'toplevel_page_mco-promociones',
			'promociones_page_mco-promociones-nueva',
			'promociones_page_mco-promociones-historial',
		);

		// También detectar cuando se está editando (parámetro edit en la URL de listado).
		$pagina_actual = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

		if ( ! in_array( $hook_suffix, $paginas_propias, true ) && 'mco-promociones' !== $pagina_actual ) {
			return;
		}

		wp_enqueue_style(
			'mco-promo-admin',
			MCO_PROMO_PLUGIN_URL . 'assets/css/mco-promo-admin.css',
			array(),
			MCO_PROMO_VERSION
		);

		wp_enqueue_script(
			'mco-promo-admin',
			MCO_PROMO_PLUGIN_URL . 'assets/js/mco-promo-admin.js',
			array( 'jquery' ),
			MCO_PROMO_VERSION,
			true
		);

		wp_localize_script(
			'mco-promo-admin',
			'mcoPromoData',
			array(
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'nonce'         => wp_create_nonce( 'mco_promo_nonce' ),
				'textBuscando'  => __( 'Buscando...', 'mco-promociones' ),
				'textSeleccionar' => __( 'Seleccionar', 'mco-promociones' ),
				'textRemover'   => __( 'Remover', 'mco-promociones' ),
				'textSeleccionados' => __( 'productos seleccionados', 'mco-promociones' ),
				'textSinResultados' => __( 'No se encontraron productos.', 'mco-promociones' ),
				'textConflicto' => __( 'Advertencia: los siguientes productos ya tienen una promoción activa:', 'mco-promociones' ),
			)
		);
	}

	/**
	 * Renderiza la página de listado de promociones.
	 *
	 * @return void
	 */
	public function pagina_listado() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'No tienes permisos para acceder a esta página.', 'mco-promociones' ) );
		}

		$promos = get_posts(
			array(
				'post_type'      => 'mco_promocion',
				'post_status'    => array( 'publish', 'draft' ),
				'posts_per_page' => -1,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		$ahora = current_time( 'Y-m-d H:i:s' );

		$mensaje = isset( $_GET['msg'] ) ? sanitize_text_field( wp_unslash( $_GET['msg'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		?>
		<div class="wrap mco-promo-wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Promociones WooCommerce', 'mco-promociones' ); ?></h1>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mco-promociones-nueva' ) ); ?>" class="page-title-action">
				<?php esc_html_e( 'Agregar nueva', 'mco-promociones' ); ?>
			</a>
			<hr class="wp-header-end">

			<?php if ( $mensaje ) : ?>
				<?php $this->mostrar_aviso_de_mensaje( $mensaje ); ?>
			<?php endif; ?>

			<table class="wp-list-table widefat fixed striped mco-promo-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Nombre', 'mco-promociones' ); ?></th>
						<th><?php esc_html_e( 'Descuento', 'mco-promociones' ); ?></th>
						<th><?php esc_html_e( 'Fecha inicio', 'mco-promociones' ); ?></th>
						<th><?php esc_html_e( 'Fecha fin', 'mco-promociones' ); ?></th>
						<th><?php esc_html_e( 'Estado', 'mco-promociones' ); ?></th>
						<th><?php esc_html_e( 'Selección', 'mco-promociones' ); ?></th>
						<th><?php esc_html_e( 'Acciones', 'mco-promociones' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $promos ) ) : ?>
						<tr>
							<td colspan="7"><?php esc_html_e( 'No hay promociones creadas aún.', 'mco-promociones' ); ?></td>
						</tr>
					<?php else : ?>
						<?php foreach ( $promos as $promo ) :
							$porcentaje     = get_post_meta( $promo->ID, '_mco_promo_porcentaje', true );
							$fecha_inicio   = get_post_meta( $promo->ID, '_mco_promo_fecha_inicio', true );
							$fecha_fin      = get_post_meta( $promo->ID, '_mco_promo_fecha_fin', true );
							$aplicada       = (bool) get_post_meta( $promo->ID, '_mco_promo_aplicada', true );
							$tipo_seleccion = get_post_meta( $promo->ID, '_mco_promo_tipo_seleccion', true );

							$estado = $this->calcular_estado( $fecha_inicio, $fecha_fin, $ahora );
							$nonce_acciones = wp_create_nonce( 'mco_promo_accion_' . $promo->ID );
						?>
						<tr>
							<td><strong><?php echo esc_html( $promo->post_title ); ?></strong></td>
							<td><?php echo esc_html( $porcentaje ); ?>%</td>
							<td><?php echo esc_html( $fecha_inicio ); ?></td>
							<td><?php echo esc_html( $fecha_fin ); ?></td>
							<td><?php $this->renderizar_badge_estado( $estado ); ?></td>
							<td><?php echo esc_html( $this->etiqueta_tipo_seleccion( $tipo_seleccion ) ); ?></td>
							<td class="mco-promo-acciones">
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=mco-promociones-nueva&edit=' . $promo->ID ) ); ?>"
								   class="button button-small">
									<?php esc_html_e( 'Editar', 'mco-promociones' ); ?>
								</a>

								<?php if ( ! $aplicada ) : ?>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
										<input type="hidden" name="action" value="mco_promo_aplicar">
										<input type="hidden" name="promo_id" value="<?php echo esc_attr( $promo->ID ); ?>">
										<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $nonce_acciones ); ?>">
										<button type="submit" class="button button-small button-primary">
											<?php esc_html_e( 'Aplicar ahora', 'mco-promociones' ); ?>
										</button>
									</form>
								<?php else : ?>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
										<input type="hidden" name="action" value="mco_promo_revertir">
										<input type="hidden" name="promo_id" value="<?php echo esc_attr( $promo->ID ); ?>">
										<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $nonce_acciones ); ?>">
										<button type="submit" class="button button-small">
											<?php esc_html_e( 'Revertir', 'mco-promociones' ); ?>
										</button>
									</form>
								<?php endif; ?>

								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;"
									  onsubmit="return confirm('<?php esc_attr_e( '¿Eliminar esta promoción?', 'mco-promociones' ); ?>')">
									<input type="hidden" name="action" value="mco_promo_eliminar">
									<input type="hidden" name="promo_id" value="<?php echo esc_attr( $promo->ID ); ?>">
									<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $nonce_acciones ); ?>">
									<button type="submit" class="button button-small button-link-delete">
										<?php esc_html_e( 'Eliminar', 'mco-promociones' ); ?>
									</button>
								</form>
							</td>
						</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Renderiza la página del formulario para crear o editar una promoción.
	 *
	 * @return void
	 */
	public function pagina_formulario() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'No tienes permisos para acceder a esta página.', 'mco-promociones' ) );
		}

		$edit_id = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		$es_edicion = $edit_id > 0;

		// Valores por defecto o valores existentes al editar.
		$titulo         = '';
		$porcentaje     = '';
		$fecha_inicio   = '';
		$fecha_fin      = '';
		$tipo_seleccion = 'todos';
		$categorias     = array();
		$productos      = array();

		if ( $es_edicion ) {
			$promo = get_post( $edit_id );

			if ( ! $promo || 'mco_promocion' !== $promo->post_type ) {
				wp_die( esc_html__( 'Promoción no encontrada.', 'mco-promociones' ) );
			}

			$titulo         = $promo->post_title;
			$porcentaje     = get_post_meta( $edit_id, '_mco_promo_porcentaje', true );
			$fecha_inicio   = get_post_meta( $edit_id, '_mco_promo_fecha_inicio', true );
			$fecha_fin      = get_post_meta( $edit_id, '_mco_promo_fecha_fin', true );
			$tipo_seleccion = get_post_meta( $edit_id, '_mco_promo_tipo_seleccion', true ) ?: 'todos';
			$categorias     = get_post_meta( $edit_id, '_mco_promo_categorias', true ) ?: array();
			$productos      = get_post_meta( $edit_id, '_mco_promo_productos', true ) ?: array();

			// Convertir fechas a formato datetime-local (Y-m-dTH:i).
			if ( $fecha_inicio ) {
				$fecha_inicio = str_replace( ' ', 'T', substr( $fecha_inicio, 0, 16 ) );
			}
			if ( $fecha_fin ) {
				$fecha_fin = str_replace( ' ', 'T', substr( $fecha_fin, 0, 16 ) );
			}
		}

		// Obtener todas las categorías de productos.
		$categorias_wc = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		// Obtener datos de productos ya seleccionados (para edición).
		$productos_seleccionados_data = array();
		if ( ! empty( $productos ) && 'manual' === $tipo_seleccion ) {
			foreach ( $productos as $pid ) {
				$pid     = absint( $pid );
				$product = wc_get_product( $pid );
				if ( $product ) {
					$thumbnail_id  = $product->get_image_id();
					$thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url( $thumbnail_id, 'thumbnail' ) : wc_placeholder_img_src( 'thumbnail' );
					$productos_seleccionados_data[] = array(
						'id'            => $pid,
						'name'          => $product->get_name(),
						'sku'           => $product->get_sku(),
						'regular_price' => $product->get_regular_price(),
						'thumbnail_url' => $thumbnail_url,
					);
				}
			}
		}

		$nonce_form = wp_create_nonce( 'mco_promo_guardar' );
		?>
		<div class="wrap mco-promo-wrap">
			<h1>
				<?php echo $es_edicion ? esc_html__( 'Editar Promoción', 'mco-promociones' ) : esc_html__( 'Nueva Promoción', 'mco-promociones' ); ?>
			</h1>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mco-promociones' ) ); ?>">
				&larr; <?php esc_html_e( 'Volver al listado', 'mco-promociones' ); ?>
			</a>

			<div id="mco-promo-aviso-conflicto" class="notice notice-warning" style="display:none;">
				<p><strong><?php esc_html_e( 'Advertencia:', 'mco-promociones' ); ?></strong>
				<span id="mco-promo-conflicto-texto"></span></p>
			</div>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="mco-promo-form">
				<input type="hidden" name="action" value="mco_promo_guardar">
				<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $nonce_form ); ?>">
				<?php if ( $es_edicion ) : ?>
					<input type="hidden" name="promo_id" value="<?php echo esc_attr( $edit_id ); ?>">
				<?php endif; ?>

				<div id="poststuff">
					<div id="post-body" class="metabox-holder columns-2">
						<div id="post-body-content">

							<!-- Datos generales -->
							<div class="postbox">
								<h2 class="hndle"><span><?php esc_html_e( 'Datos generales', 'mco-promociones' ); ?></span></h2>
								<div class="inside">
									<table class="form-table">
										<tr>
											<th><label for="mco_titulo"><?php esc_html_e( 'Nombre de la promoción', 'mco-promociones' ); ?> <span class="required">*</span></label></th>
											<td>
												<input type="text" id="mco_titulo" name="mco_titulo"
												       value="<?php echo esc_attr( $titulo ); ?>"
												       class="regular-text" required>
											</td>
										</tr>
										<tr>
											<th><label for="mco_porcentaje"><?php esc_html_e( 'Porcentaje de descuento (%)', 'mco-promociones' ); ?> <span class="required">*</span></label></th>
											<td>
												<input type="number" id="mco_porcentaje" name="mco_porcentaje"
												       value="<?php echo esc_attr( $porcentaje ); ?>"
												       min="0.01" max="99.99" step="0.01" class="small-text" required>
												<span class="description"><?php esc_html_e( 'Ej: 20 para 20% de descuento', 'mco-promociones' ); ?></span>
											</td>
										</tr>
										<tr>
											<th><label for="mco_fecha_inicio"><?php esc_html_e( 'Fecha y hora de inicio', 'mco-promociones' ); ?> <span class="required">*</span></label></th>
											<td>
												<input type="datetime-local" id="mco_fecha_inicio" name="mco_fecha_inicio"
												       value="<?php echo esc_attr( $fecha_inicio ); ?>" required>
											</td>
										</tr>
										<tr>
											<th><label for="mco_fecha_fin"><?php esc_html_e( 'Fecha y hora de fin', 'mco-promociones' ); ?> <span class="required">*</span></label></th>
											<td>
												<input type="datetime-local" id="mco_fecha_fin" name="mco_fecha_fin"
												       value="<?php echo esc_attr( $fecha_fin ); ?>" required>
												<span class="description"><?php esc_html_e( 'Debe ser posterior a la fecha de inicio', 'mco-promociones' ); ?></span>
											</td>
										</tr>
									</table>
								</div>
							</div>

							<!-- Selección de productos -->
							<div class="postbox">
								<h2 class="hndle"><span><?php esc_html_e( 'Selección de productos', 'mco-promociones' ); ?></span></h2>
								<div class="inside">

									<div class="mco-promo-tipo-seleccion">
										<label class="mco-radio-option <?php echo 'todos' === $tipo_seleccion ? 'active' : ''; ?>">
											<input type="radio" name="mco_tipo_seleccion" value="todos"
											       <?php checked( $tipo_seleccion, 'todos' ); ?>>
											<?php esc_html_e( 'Todos los productos', 'mco-promociones' ); ?>
										</label>
										<label class="mco-radio-option <?php echo 'categoria' === $tipo_seleccion ? 'active' : ''; ?>">
											<input type="radio" name="mco_tipo_seleccion" value="categoria"
											       <?php checked( $tipo_seleccion, 'categoria' ); ?>>
											<?php esc_html_e( 'Por categoría', 'mco-promociones' ); ?>
										</label>
										<label class="mco-radio-option <?php echo 'manual' === $tipo_seleccion ? 'active' : ''; ?>">
											<input type="radio" name="mco_tipo_seleccion" value="manual"
											       <?php checked( $tipo_seleccion, 'manual' ); ?>>
											<?php esc_html_e( 'Selección manual', 'mco-promociones' ); ?>
										</label>
									</div>

									<!-- Panel: Todos los productos -->
									<div id="mco-panel-todos" class="mco-panel"
									     style="<?php echo 'todos' !== $tipo_seleccion ? 'display:none;' : ''; ?>">
										<p class="description">
											<?php esc_html_e( 'El descuento se aplicará a todos los productos publicados de WooCommerce.', 'mco-promociones' ); ?>
										</p>
									</div>

									<!-- Panel: Por categoría -->
									<div id="mco-panel-categoria" class="mco-panel"
									     style="<?php echo 'categoria' !== $tipo_seleccion ? 'display:none;' : ''; ?>">
										<p class="description"><?php esc_html_e( 'Selecciona una o más categorías:', 'mco-promociones' ); ?></p>
										<div class="mco-categorias-grid">
											<?php if ( ! is_wp_error( $categorias_wc ) && ! empty( $categorias_wc ) ) : ?>
												<?php foreach ( $categorias_wc as $cat ) : ?>
													<label class="mco-cat-item">
														<input type="checkbox" name="mco_categorias[]"
														       value="<?php echo esc_attr( $cat->term_id ); ?>"
														       <?php checked( in_array( (string) $cat->term_id, array_map( 'strval', $categorias ), true ) ); ?>>
														<?php echo esc_html( $cat->name ); ?>
														<span class="count">(<?php echo esc_html( $cat->count ); ?>)</span>
													</label>
												<?php endforeach; ?>
											<?php else : ?>
												<p><?php esc_html_e( 'No hay categorías de productos.', 'mco-promociones' ); ?></p>
											<?php endif; ?>
										</div>
									</div>

									<!-- Panel: Selección manual -->
									<div id="mco-panel-manual" class="mco-panel"
									     style="<?php echo 'manual' !== $tipo_seleccion ? 'display:none;' : ''; ?>">

										<div class="mco-buscador">
											<input type="text" id="mco-buscar-producto"
											       placeholder="<?php esc_attr_e( 'Buscar por nombre o SKU...', 'mco-promociones' ); ?>"
											       class="regular-text">
											<span class="mco-spinner spinner" style="float:none;"></span>
										</div>

										<div id="mco-resultados-busqueda" class="mco-resultados"></div>

										<div id="mco-paginacion-busqueda" class="mco-paginacion"></div>

										<div id="mco-productos-seleccionados-area" class="mco-seleccionados-area">
											<div class="mco-seleccionados-header">
												<strong id="mco-contador-seleccionados">
													<?php printf( esc_html__( '%d productos seleccionados', 'mco-promociones' ), count( $productos ) ); ?>
												</strong>
												<button type="button" id="mco-limpiar-seleccion" class="button button-small">
													<?php esc_html_e( 'Limpiar selección', 'mco-promociones' ); ?>
												</button>
											</div>
											<div id="mco-lista-seleccionados"></div>
										</div>

										<!-- Hidden inputs para productos seleccionados -->
										<div id="mco-hidden-productos">
											<?php foreach ( $productos as $pid ) : ?>
												<input type="hidden" name="mco_productos[]" value="<?php echo esc_attr( $pid ); ?>">
											<?php endforeach; ?>
										</div>

										<!-- Datos JSON para inicializar JS -->
										<script type="application/json" id="mco-productos-iniciales">
											<?php echo wp_json_encode( $productos_seleccionados_data ); ?>
										</script>
									</div>

								</div>
							</div>

						</div><!-- #post-body-content -->

						<div id="postbox-container-1" class="postbox-container">
							<div class="postbox">
								<h2 class="hndle"><span><?php esc_html_e( 'Publicar', 'mco-promociones' ); ?></span></h2>
								<div class="inside">
									<div class="submitbox">
										<div id="publishing-action">
											<input type="submit" name="submit" id="publish" class="button button-primary button-large"
											       value="<?php echo $es_edicion ? esc_attr__( 'Actualizar promoción', 'mco-promociones' ) : esc_attr__( 'Guardar promoción', 'mco-promociones' ); ?>">
										</div>
										<?php if ( $es_edicion ) : ?>
											<div id="delete-action">
												<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=mco_promo_eliminar&promo_id=' . $edit_id ), 'mco_promo_accion_' . $edit_id ) ); ?>"
												   onclick="return confirm('<?php esc_attr_e( '¿Eliminar esta promoción?', 'mco-promociones' ); ?>')"
												   class="submitdelete">
													<?php esc_html_e( 'Eliminar promoción', 'mco-promociones' ); ?>
												</a>
											</div>
										<?php endif; ?>
									</div>
								</div>
							</div>
						</div>

					</div><!-- #post-body -->
				</div><!-- #poststuff -->
			</form>
		</div>
		<?php
	}

	/**
	 * Renderiza la página de historial/log.
	 *
	 * @return void
	 */
	public function pagina_historial() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'No tienes permisos para acceder a esta página.', 'mco-promociones' ) );
		}

		$log = get_option( 'mco_promo_log', array() );

		if ( ! is_array( $log ) ) {
			$log = array();
		}

		// Mostrar los últimos 200 eventos en orden inverso.
		$log     = array_reverse( $log );
		$log     = array_slice( $log, 0, 200 );
		$nonce   = wp_create_nonce( 'mco_promo_limpiar_log' );
		$mensaje = isset( $_GET['msg'] ) ? sanitize_text_field( wp_unslash( $_GET['msg'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		?>
		<div class="wrap mco-promo-wrap">
			<h1><?php esc_html_e( 'Historial de Promociones', 'mco-promociones' ); ?></h1>

			<?php if ( 'log_limpiado' === $mensaje ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Historial limpiado correctamente.', 'mco-promociones' ); ?></p>
				</div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-bottom:15px;">
				<input type="hidden" name="action" value="mco_promo_limpiar_log">
				<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $nonce ); ?>">
				<button type="submit" class="button"
				        onclick="return confirm('<?php esc_attr_e( '¿Limpiar todo el historial?', 'mco-promociones' ); ?>')">
					<?php esc_html_e( 'Limpiar historial', 'mco-promociones' ); ?>
				</button>
			</form>

			<?php if ( empty( $log ) ) : ?>
				<p><?php esc_html_e( 'El historial está vacío.', 'mco-promociones' ); ?></p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th style="width:180px;"><?php esc_html_e( 'Fecha/Hora', 'mco-promociones' ); ?></th>
							<th><?php esc_html_e( 'Promoción', 'mco-promociones' ); ?></th>
							<th style="width:120px;"><?php esc_html_e( 'Acción', 'mco-promociones' ); ?></th>
							<th><?php esc_html_e( 'Resultado', 'mco-promociones' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $log as $evento ) : ?>
							<tr>
								<td><?php echo esc_html( $evento['timestamp'] ?? '' ); ?></td>
								<td><?php echo esc_html( $evento['promo'] ?? 'ID ' . ( $evento['promo_id'] ?? '' ) ); ?></td>
								<td>
									<?php
									$accion = $evento['accion'] ?? '';
									$clase  = '';
									$label  = $accion;
									if ( 'aplicada' === $accion ) {
										$clase = 'mco-badge mco-badge-activa';
										$label = __( 'Aplicada', 'mco-promociones' );
									} elseif ( 'revertida' === $accion ) {
										$clase = 'mco-badge mco-badge-pendiente';
										$label = __( 'Revertida', 'mco-promociones' );
									} elseif ( 'error' === $accion ) {
										$clase = 'mco-badge mco-badge-vencida';
										$label = __( 'Error', 'mco-promociones' );
									} elseif ( 'omitido' === $accion ) {
										$clase = 'mco-badge';
										$label = __( 'Omitido', 'mco-promociones' );
									}
									echo '<span class="' . esc_attr( $clase ) . '">' . esc_html( $label ) . '</span>';
									?>
								</td>
								<td><?php echo esc_html( $evento['mensaje'] ?? '' ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Maneja el guardado (crear/editar) de una promoción.
	 *
	 * @return void
	 */
	public function manejar_guardar() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'No autorizado.', 'mco-promociones' ) );
		}

		check_admin_referer( 'mco_promo_guardar' );

		$promo_id       = isset( $_POST['promo_id'] ) ? absint( $_POST['promo_id'] ) : 0;
		$titulo         = isset( $_POST['mco_titulo'] ) ? sanitize_text_field( wp_unslash( $_POST['mco_titulo'] ) ) : '';
		$porcentaje     = isset( $_POST['mco_porcentaje'] ) ? floatval( $_POST['mco_porcentaje'] ) : 0;
		$fecha_inicio_raw = isset( $_POST['mco_fecha_inicio'] ) ? sanitize_text_field( wp_unslash( $_POST['mco_fecha_inicio'] ) ) : '';
		$fecha_fin_raw    = isset( $_POST['mco_fecha_fin'] ) ? sanitize_text_field( wp_unslash( $_POST['mco_fecha_fin'] ) ) : '';
		$tipo_seleccion = isset( $_POST['mco_tipo_seleccion'] ) ? sanitize_text_field( wp_unslash( $_POST['mco_tipo_seleccion'] ) ) : 'todos';

		// Convertir datetime-local a Y-m-d H:i:s.
		$fecha_inicio = str_replace( 'T', ' ', $fecha_inicio_raw ) . ':00';
		$fecha_fin    = str_replace( 'T', ' ', $fecha_fin_raw ) . ':00';

		// Sanitizar listas de IDs.
		$categorias = array();
		if ( isset( $_POST['mco_categorias'] ) && is_array( $_POST['mco_categorias'] ) ) {
			$categorias = array_map( 'absint', $_POST['mco_categorias'] );
			$categorias = array_filter( $categorias );
		}

		$productos = array();
		if ( isset( $_POST['mco_productos'] ) && is_array( $_POST['mco_productos'] ) ) {
			$productos = array_map( 'absint', $_POST['mco_productos'] );
			$productos = array_filter( $productos );
		}

		// Validaciones básicas.
		if ( empty( $titulo ) || $porcentaje <= 0 || $porcentaje >= 100 || empty( $fecha_inicio_raw ) || empty( $fecha_fin_raw ) ) {
			wp_safe_redirect( add_query_arg( 'msg', 'error_validacion', admin_url( 'admin.php?page=mco-promociones' ) ) );
			exit;
		}

		if ( $fecha_fin <= $fecha_inicio ) {
			wp_safe_redirect( add_query_arg( 'msg', 'error_fechas', admin_url( 'admin.php?page=mco-promociones' ) ) );
			exit;
		}

		// Crear o actualizar el post.
		if ( $promo_id > 0 ) {
			wp_update_post(
				array(
					'ID'          => $promo_id,
					'post_title'  => $titulo,
					'post_status' => 'publish',
					'post_type'   => 'mco_promocion',
				)
			);
			$nuevo_id = $promo_id;
		} else {
			$nuevo_id = wp_insert_post(
				array(
					'post_title'  => $titulo,
					'post_status' => 'publish',
					'post_type'   => 'mco_promocion',
				)
			);
		}

		if ( ! $nuevo_id || is_wp_error( $nuevo_id ) ) {
			wp_safe_redirect( add_query_arg( 'msg', 'error_guardar', admin_url( 'admin.php?page=mco-promociones' ) ) );
			exit;
		}

		// Guardar meta.
		update_post_meta( $nuevo_id, '_mco_promo_porcentaje', $porcentaje );
		update_post_meta( $nuevo_id, '_mco_promo_fecha_inicio', $fecha_inicio );
		update_post_meta( $nuevo_id, '_mco_promo_fecha_fin', $fecha_fin );
		update_post_meta( $nuevo_id, '_mco_promo_tipo_seleccion', $tipo_seleccion );
		update_post_meta( $nuevo_id, '_mco_promo_categorias', $categorias );
		update_post_meta( $nuevo_id, '_mco_promo_productos', $productos );

		wp_safe_redirect( add_query_arg( 'msg', 'guardada', admin_url( 'admin.php?page=mco-promociones' ) ) );
		exit;
	}

	/**
	 * Maneja la eliminación de una promoción.
	 *
	 * @return void
	 */
	public function manejar_eliminar() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'No autorizado.', 'mco-promociones' ) );
		}

		$promo_id = isset( $_REQUEST['promo_id'] ) ? absint( $_REQUEST['promo_id'] ) : 0;

		check_admin_referer( 'mco_promo_accion_' . $promo_id );

		if ( ! $promo_id ) {
			wp_safe_redirect( add_query_arg( 'msg', 'error', admin_url( 'admin.php?page=mco-promociones' ) ) );
			exit;
		}

		// Si la promoción está aplicada, revertir precios primero.
		$aplicada = (bool) get_post_meta( $promo_id, '_mco_promo_aplicada', true );
		if ( $aplicada ) {
			$engine = new MCO_Promo_Engine();
			$engine->revertir_promocion( $promo_id );
		}

		wp_delete_post( $promo_id, true );

		wp_safe_redirect( add_query_arg( 'msg', 'eliminada', admin_url( 'admin.php?page=mco-promociones' ) ) );
		exit;
	}

	/**
	 * Maneja la aplicación manual de una promoción.
	 *
	 * @return void
	 */
	public function manejar_aplicar() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'No autorizado.', 'mco-promociones' ) );
		}

		$promo_id = isset( $_POST['promo_id'] ) ? absint( $_POST['promo_id'] ) : 0;

		check_admin_referer( 'mco_promo_accion_' . $promo_id );

		if ( ! $promo_id ) {
			wp_safe_redirect( add_query_arg( 'msg', 'error', admin_url( 'admin.php?page=mco-promociones' ) ) );
			exit;
		}

		$engine  = new MCO_Promo_Engine();
		$result  = $engine->aplicar_promocion( $promo_id );

		$msg = $result ? 'aplicada' : 'error_aplicar';
		wp_safe_redirect( add_query_arg( 'msg', $msg, admin_url( 'admin.php?page=mco-promociones' ) ) );
		exit;
	}

	/**
	 * Maneja la reversión manual de una promoción.
	 *
	 * @return void
	 */
	public function manejar_revertir() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'No autorizado.', 'mco-promociones' ) );
		}

		$promo_id = isset( $_POST['promo_id'] ) ? absint( $_POST['promo_id'] ) : 0;

		check_admin_referer( 'mco_promo_accion_' . $promo_id );

		if ( ! $promo_id ) {
			wp_safe_redirect( add_query_arg( 'msg', 'error', admin_url( 'admin.php?page=mco-promociones' ) ) );
			exit;
		}

		$engine = new MCO_Promo_Engine();
		$result = $engine->revertir_promocion( $promo_id );

		$msg = $result ? 'revertida' : 'error_revertir';
		wp_safe_redirect( add_query_arg( 'msg', $msg, admin_url( 'admin.php?page=mco-promociones' ) ) );
		exit;
	}

	/**
	 * Maneja la limpieza del historial de log.
	 *
	 * @return void
	 */
	public function manejar_limpiar_log() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'No autorizado.', 'mco-promociones' ) );
		}

		check_admin_referer( 'mco_promo_limpiar_log' );

		delete_option( 'mco_promo_log' );

		wp_safe_redirect( add_query_arg( 'msg', 'log_limpiado', admin_url( 'admin.php?page=mco-promociones-historial' ) ) );
		exit;
	}

	/**
	 * Calcula el estado de una promoción en base a las fechas.
	 *
	 * @param string $fecha_inicio Fecha de inicio (Y-m-d H:i:s).
	 * @param string $fecha_fin    Fecha de fin (Y-m-d H:i:s).
	 * @param string $ahora        Fecha/hora actual (Y-m-d H:i:s).
	 * @return string 'pendiente', 'activa' o 'vencida'.
	 */
	private function calcular_estado( string $fecha_inicio, string $fecha_fin, string $ahora ): string {
		if ( $ahora >= $fecha_fin ) {
			return 'vencida';
		}
		if ( $ahora >= $fecha_inicio ) {
			return 'activa';
		}
		return 'pendiente';
	}

	/**
	 * Renderiza el badge HTML del estado de una promoción.
	 *
	 * @param string $estado Estado de la promoción.
	 * @return void
	 */
	private function renderizar_badge_estado( string $estado ) {
		$clase = 'mco-badge mco-badge-' . esc_attr( $estado );
		$labels = array(
			'pendiente' => __( 'Pendiente', 'mco-promociones' ),
			'activa'    => __( 'Activa', 'mco-promociones' ),
			'vencida'   => __( 'Vencida', 'mco-promociones' ),
		);
		$label = isset( $labels[ $estado ] ) ? $labels[ $estado ] : $estado;
		echo '<span class="' . esc_attr( $clase ) . '">' . esc_html( $label ) . '</span>';
	}

	/**
	 * Devuelve la etiqueta legible del tipo de selección.
	 *
	 * @param string $tipo Tipo de selección.
	 * @return string Etiqueta legible.
	 */
	private function etiqueta_tipo_seleccion( string $tipo ): string {
		$map = array(
			'todos'     => __( 'Todos los productos', 'mco-promociones' ),
			'categoria' => __( 'Por categoría', 'mco-promociones' ),
			'manual'    => __( 'Selección manual', 'mco-promociones' ),
		);
		return isset( $map[ $tipo ] ) ? $map[ $tipo ] : $tipo;
	}

	/**
	 * Muestra un aviso de administración según el código de mensaje recibido por GET.
	 *
	 * @param string $mensaje Código de mensaje.
	 * @return void
	 */
	private function mostrar_aviso_de_mensaje( string $mensaje ) {
		$avisos = array(
			'guardada'       => array( 'success', __( 'Promoción guardada correctamente.', 'mco-promociones' ) ),
			'eliminada'      => array( 'success', __( 'Promoción eliminada correctamente.', 'mco-promociones' ) ),
			'aplicada'       => array( 'success', __( 'Promoción aplicada correctamente.', 'mco-promociones' ) ),
			'revertida'      => array( 'success', __( 'Promoción revertida correctamente.', 'mco-promociones' ) ),
			'error'          => array( 'error', __( 'Ocurrió un error. Por favor, intenta nuevamente.', 'mco-promociones' ) ),
			'error_validacion' => array( 'error', __( 'Error de validación: revisa los campos del formulario.', 'mco-promociones' ) ),
			'error_fechas'   => array( 'error', __( 'Error: la fecha de fin debe ser posterior a la fecha de inicio.', 'mco-promociones' ) ),
			'error_guardar'  => array( 'error', __( 'Error al guardar la promoción.', 'mco-promociones' ) ),
			'error_aplicar'  => array( 'error', __( 'Error al aplicar la promoción.', 'mco-promociones' ) ),
			'error_revertir' => array( 'error', __( 'Error al revertir la promoción.', 'mco-promociones' ) ),
		);

		if ( ! isset( $avisos[ $mensaje ] ) ) {
			return;
		}

		list( $tipo, $texto ) = $avisos[ $mensaje ];
		echo '<div class="notice notice-' . esc_attr( $tipo ) . ' is-dismissible"><p>' . esc_html( $texto ) . '</p></div>';
	}
}

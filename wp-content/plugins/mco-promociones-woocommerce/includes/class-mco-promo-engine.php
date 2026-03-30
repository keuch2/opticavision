<?php
/**
 * Motor de aplicación y reversión de precios para Promociones.
 *
 * @package MCO_Promociones
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clase MCO_Promo_Engine
 *
 * Núcleo del plugin: aplica y revierte descuentos sobre productos WooCommerce.
 */
class MCO_Promo_Engine {

	/**
	 * Aplica los precios de descuento a todos los productos de una promoción.
	 *
	 * @param int $promo_id ID del post de la promoción.
	 * @return bool True si fue exitoso, false en caso de error.
	 */
	public function aplicar_promocion( int $promo_id ): bool {
		$porcentaje = (float) get_post_meta( $promo_id, '_mco_promo_porcentaje', true );

		if ( $porcentaje <= 0 || $porcentaje >= 100 ) {
			$this->log( $promo_id, 'error', 'Porcentaje inválido: ' . $porcentaje );
			return false;
		}

		$producto_ids = $this->resolver_productos( $promo_id );

		if ( empty( $producto_ids ) ) {
			$this->log( $promo_id, 'error', 'No se encontraron productos para la promoción.' );
			return false;
		}

		// Guardar snapshot de precios originales antes de modificar.
		$snapshot   = array();
		$parent_ids = array(); // IDs de padres de variaciones para sincronizar caché.
		foreach ( $producto_ids as $pid ) {
			$entry = array(
				'regular' => get_post_meta( $pid, '_regular_price', true ),
				'sale'    => get_post_meta( $pid, '_sale_price', true ),
			);
			// Si es una variación, guardar el ID del padre para poder sincronizarlo al revertir.
			if ( 'product_variation' === get_post_type( $pid ) ) {
				$parent_id          = wp_get_post_parent_id( $pid );
				$entry['parent_id'] = $parent_id;
				if ( $parent_id ) {
					$parent_ids[ $parent_id ] = true;
				}
			}
			$snapshot[ $pid ] = $entry;
		}
		update_post_meta( $promo_id, '_mco_promo_snapshot', $snapshot );

		$errores = 0;
		$aplicados = 0;

		foreach ( $producto_ids as $pid ) {
			$precio_regular = (float) get_post_meta( $pid, '_regular_price', true );

			if ( empty( $precio_regular ) || $precio_regular <= 0 ) {
				$this->log( $promo_id, 'omitido', 'Producto ID ' . $pid . ' omitido: precio regular vacío o 0.' );
				continue;
			}

			$precio_rebajado = $this->calcular_precio_rebajado( $precio_regular, $porcentaje );

			try {
				update_post_meta( $pid, '_sale_price', $precio_rebajado );
				update_post_meta( $pid, '_price', $precio_rebajado );
				wc_delete_product_transients( $pid );
				$aplicados++;
			} catch ( Exception $e ) {
				$errores++;
				$this->log( $promo_id, 'error', 'Error en producto ID ' . $pid . ': ' . $e->getMessage() );
			}
		}

		// Sincronizar los productos padre para que los precios mín/máx indexados se actualicen.
		if ( ! empty( $parent_ids ) ) {
			$this->sync_productos_padre( array_keys( $parent_ids ) );
		}

		update_post_meta( $promo_id, '_mco_promo_aplicada', true );
		$this->log( $promo_id, 'aplicada', "Promoción aplicada. Productos: {$aplicados}. Errores: {$errores}." );

		return true;
	}

	/**
	 * Revierte los precios originales de todos los productos de una promoción.
	 *
	 * @param int $promo_id ID del post de la promoción.
	 * @return bool True si fue exitoso, false en caso de error.
	 */
	public function revertir_promocion( int $promo_id ): bool {
		$snapshot = get_post_meta( $promo_id, '_mco_promo_snapshot', true );

		if ( empty( $snapshot ) || ! is_array( $snapshot ) ) {
			$this->log( $promo_id, 'error', 'No hay snapshot de precios para revertir.' );
			// Marcar como no aplicada de todas formas.
			update_post_meta( $promo_id, '_mco_promo_aplicada', false );
			return false;
		}

		$errores    = 0;
		$revertidos = 0;
		$parent_ids = array(); // IDs de padres de variaciones para sincronizar caché.

		foreach ( $snapshot as $pid => $precios ) {
			$pid = absint( $pid );
			if ( ! $pid ) {
				continue;
			}

			// Recolectar ID del padre si esta entrada corresponde a una variación.
			if ( isset( $precios['parent_id'] ) && $precios['parent_id'] ) {
				$parent_ids[ $precios['parent_id'] ] = true;
			}

			$precio_regular_original = isset( $precios['regular'] ) ? $precios['regular'] : '';
			$precio_sale_original    = isset( $precios['sale'] ) ? $precios['sale'] : '';

			try {
				// Restaurar precio regular.
				if ( '' !== $precio_regular_original ) {
					update_post_meta( $pid, '_regular_price', $precio_regular_original );
				}

				// Restaurar o eliminar precio de oferta.
				if ( '' === $precio_sale_original || null === $precio_sale_original ) {
					delete_post_meta( $pid, '_sale_price' );
					// El precio activo es el regular.
					update_post_meta( $pid, '_price', $precio_regular_original );
				} else {
					update_post_meta( $pid, '_sale_price', $precio_sale_original );
					// El precio activo es el de oferta (si existía antes).
					update_post_meta( $pid, '_price', $precio_sale_original );
				}

				wc_delete_product_transients( $pid );
				$revertidos++;
			} catch ( Exception $e ) {
				$errores++;
				$this->log( $promo_id, 'error', 'Error al revertir producto ID ' . $pid . ': ' . $e->getMessage() );
			}
		}

		// Sincronizar productos padre para recalcular precios min/max y limpiar transients.
		$padres_sync = 0;
		if ( ! empty( $parent_ids ) ) {
			$padres_sync = $this->sync_productos_padre( array_keys( $parent_ids ) );
		}

		update_post_meta( $promo_id, '_mco_promo_aplicada', false );
		$this->log( $promo_id, 'revertida', "Promoción revertida. Productos: {$revertidos}. Errores: {$errores}. Padres sincronizados: {$padres_sync}." );

		return true;
	}

	/**
	 * Obtiene el array de IDs de productos que corresponden a una promoción.
	 *
	 * Maneja los tres tipos de selección: todos, por categoría, manual.
	 * Para productos de tipo 'variable', procesa sus variaciones con precio propio.
	 *
	 * @param int $promo_id ID del post de la promoción.
	 * @return array Array de IDs de productos (simples o variaciones).
	 */
	private function resolver_productos( int $promo_id ): array {
		$tipo_seleccion = get_post_meta( $promo_id, '_mco_promo_tipo_seleccion', true );
		$ids_resueltos  = array();

		switch ( $tipo_seleccion ) {

			case 'todos':
				$ids_base = wc_get_products(
					array(
						'status' => 'publish',
						'limit'  => -1,
						'return' => 'ids',
						'type'   => array( 'simple', 'variable' ),
					)
				);
				$ids_resueltos = $this->expandir_variables( $ids_base );
				break;

			case 'categoria':
				$cat_ids = get_post_meta( $promo_id, '_mco_promo_categorias', true );
				if ( empty( $cat_ids ) || ! is_array( $cat_ids ) ) {
					break;
				}
				$cat_ids = array_map( 'absint', $cat_ids );
				$cat_ids = array_filter( $cat_ids );

				$ids_base = wc_get_products(
					array(
						'status'     => 'publish',
						'limit'      => -1,
						'return'     => 'ids',
						'type'       => array( 'simple', 'variable' ),
						'category'   => $this->obtener_slugs_categorias( $cat_ids ),
					)
				);
				$ids_resueltos = $this->expandir_variables( $ids_base );
				break;

			case 'manual':
				$producto_ids = get_post_meta( $promo_id, '_mco_promo_productos', true );
				if ( empty( $producto_ids ) || ! is_array( $producto_ids ) ) {
					break;
				}
				$producto_ids  = array_map( 'absint', $producto_ids );
				$producto_ids  = array_filter( $producto_ids );
				$ids_resueltos = $this->expandir_variables( $producto_ids );
				break;

			default:
				break;
		}

		return array_unique( $ids_resueltos );
	}

	/**
	 * Expande productos variables a sus variaciones con precio propio.
	 *
	 * @param array $ids Array de IDs de productos.
	 * @return array IDs procesados (simples intactos, variables reemplazados por variaciones).
	 */
	private function expandir_variables( array $ids ): array {
		$resultado = array();

		foreach ( $ids as $pid ) {
			$pid     = absint( $pid );
			$product = wc_get_product( $pid );

			if ( ! $product ) {
				continue;
			}

			$tipo = $product->get_type();

			if ( 'variable' === $tipo ) {
				// Obtener variaciones con precio regular propio.
				$variaciones = $product->get_children();
				foreach ( $variaciones as $var_id ) {
					$precio_regular = get_post_meta( $var_id, '_regular_price', true );
					if ( '' !== $precio_regular && (float) $precio_regular > 0 ) {
						$resultado[] = $var_id;
					}
				}
			} elseif ( ! in_array( $tipo, array( 'grouped', 'external', 'variable' ), true ) ) {
				$resultado[] = $pid;
			}
		}

		return $resultado;
	}

	/**
	 * Sincroniza los precios indexados de productos variables padres y limpia sus transients.
	 *
	 * Después de modificar precios en variaciones, WooCommerce mantiene valores
	 * cacheados de _min_variation_price/_max_variation_price en el padre. Este método
	 * fuerza el recálculo para que el catálogo refleje precios correctos.
	 *
	 * @param array $parent_ids Array de IDs de productos padre tipo variable.
	 * @return int Número de padres sincronizados.
	 */
	private function sync_productos_padre( array $parent_ids ): int {
		$sincronizados = 0;
		foreach ( $parent_ids as $parent_id ) {
			$parent_id = absint( $parent_id );
			if ( ! $parent_id ) {
				continue;
			}
			$product = wc_get_product( $parent_id );
			if ( ! $product || ! ( $product instanceof WC_Product_Variable ) ) {
				continue;
			}
			WC_Product_Variable::sync( $product );
			wc_delete_product_transients( $parent_id );
			$sincronizados++;
		}
		return $sincronizados;
	}

	/**
	 * Elimina todos los precios de oferta de todos los productos publicados.
	 *
	 * Acción de emergencia: vacía _sale_price y sincroniza _price = _regular_price
	 * para todos los productos simples y variaciones publicados, usando SQL directo
	 * para máxima performance. Luego sincroniza los productos padre variables.
	 *
	 * @return array Array con 'procesados' y 'padres_sync'.
	 */
	public function borrar_todos_los_descuentos(): array {
		global $wpdb;

		// Permitir hasta 5 minutos para catálogos grandes.
		set_time_limit( 300 );

		// 1. Obtener IDs de productos publicados que tienen _sale_price no vacío.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$ids_con_descuento = $wpdb->get_col(
			"SELECT DISTINCT pm.post_id
			 FROM {$wpdb->postmeta} pm
			 INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
			 WHERE pm.meta_key = '_sale_price'
			   AND pm.meta_value != ''
			   AND p.post_type IN ('product', 'product_variation')
			   AND p.post_status = 'publish'"
		);

		if ( empty( $ids_con_descuento ) ) {
			return array( 'procesados' => 0, 'padres_sync' => 0 );
		}

		$ids_safe = implode( ',', array_map( 'absint', $ids_con_descuento ) );

		// 2. Vaciar _sale_price.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query(
			"UPDATE {$wpdb->postmeta}
			 SET meta_value = ''
			 WHERE meta_key = '_sale_price'
			   AND post_id IN ({$ids_safe})"
		);

		// 3. Sincronizar _price = _regular_price usando JOIN.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query(
			"UPDATE {$wpdb->postmeta} pm
			 INNER JOIN {$wpdb->postmeta} pr ON pm.post_id = pr.post_id
			 SET pm.meta_value = pr.meta_value
			 WHERE pm.meta_key = '_price'
			   AND pr.meta_key = '_regular_price'
			   AND pm.post_id IN ({$ids_safe})"
		);

		$procesados = count( $ids_con_descuento );

		// 4. Invalidar transients de todos los afectados.
		foreach ( $ids_con_descuento as $pid ) {
			wc_delete_product_transients( absint( $pid ) );
		}

		// 5. Obtener IDs de padres variables y sincronizar precios indexados.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$parent_ids = $wpdb->get_col(
			"SELECT DISTINCT p.post_parent
			 FROM {$wpdb->posts} p
			 WHERE p.ID IN ({$ids_safe})
			   AND p.post_type = 'product_variation'
			   AND p.post_parent > 0"
		);

		$padres_sync = 0;
		if ( ! empty( $parent_ids ) ) {
			$padres_sync = $this->sync_productos_padre( $parent_ids );
		}

		$this->log( 0, 'descuentos_borrados', "Todos los descuentos eliminados. Productos procesados: {$procesados}. Padres sincronizados: {$padres_sync}." );

		return array( 'procesados' => $procesados, 'padres_sync' => $padres_sync );
	}

	/**
	 * Obtiene los slugs de categorías a partir de sus IDs.
	 *
	 * @param array $cat_ids Array de IDs de categorías.
	 * @return array Array de slugs de categorías.
	 */
	private function obtener_slugs_categorias( array $cat_ids ): array {
		$slugs = array();
		foreach ( $cat_ids as $cat_id ) {
			$term = get_term( $cat_id, 'product_cat' );
			if ( $term && ! is_wp_error( $term ) ) {
				$slugs[] = $term->slug;
			}
		}
		return $slugs;
	}

	/**
	 * Calcula el precio rebajado dado el precio regular y el porcentaje.
	 *
	 * @param float $precio_regular Precio regular del producto.
	 * @param float $porcentaje     Porcentaje de descuento (ej: 20 para 20%).
	 * @return float Precio con descuento, redondeado a 2 decimales.
	 */
	private function calcular_precio_rebajado( float $precio_regular, float $porcentaje ): float {
		return round( $precio_regular * ( 1 - $porcentaje / 100 ), 2 );
	}

	/**
	 * Detecta conflictos con otras promociones activas para un conjunto de productos.
	 *
	 * @param array $producto_ids    IDs de productos a verificar.
	 * @param int   $excluir_promo_id ID de promoción a excluir (para edición).
	 * @return array Array de productos con conflicto: [['id' => x, 'name' => y, 'promo' => z]].
	 */
	public function detectar_conflictos( array $producto_ids, int $excluir_promo_id = 0 ): array {
		$ahora     = current_time( 'Y-m-d H:i:s' );
		$conflictos = array();

		// Obtener todas las promociones publicadas y aplicadas.
		$promos = get_posts(
			array(
				'post_type'      => 'mco_promocion',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'   => '_mco_promo_aplicada',
						'value' => '1',
					),
				),
			)
		);

		foreach ( $promos as $promo_id ) {
			$promo_id = absint( $promo_id );

			if ( $promo_id === $excluir_promo_id ) {
				continue;
			}

			$fecha_fin = get_post_meta( $promo_id, '_mco_promo_fecha_fin', true );

			// Solo evaluar si la promoción aún no ha vencido.
			if ( $fecha_fin && $ahora >= $fecha_fin ) {
				continue;
			}

			$productos_promo = $this->resolver_productos( $promo_id );

			foreach ( $producto_ids as $pid ) {
				if ( in_array( $pid, $productos_promo, true ) ) {
					$producto = wc_get_product( $pid );
					$nombre   = $producto ? $producto->get_name() : 'ID ' . $pid;

					$conflictos[] = array(
						'id'    => $pid,
						'name'  => $nombre,
						'promo' => get_the_title( $promo_id ),
					);
				}
			}
		}

		return $conflictos;
	}

	/**
	 * Registra un evento en el log de la opción mco_promo_log.
	 *
	 * @param int    $promo_id ID de la promoción.
	 * @param string $accion   Tipo de acción (aplicada, revertida, error, omitido).
	 * @param string $mensaje  Descripción del evento.
	 * @return void
	 */
	private function log( int $promo_id, string $accion, string $mensaje ) {
		$log = get_option( 'mco_promo_log', array() );

		if ( ! is_array( $log ) ) {
			$log = array();
		}

		$log[] = array(
			'timestamp' => current_time( 'Y-m-d H:i:s' ),
			'promo_id'  => $promo_id,
			'promo'     => get_the_title( $promo_id ),
			'accion'    => $accion,
			'mensaje'   => $mensaje,
		);

		// Mantener solo los últimos 500 eventos.
		if ( count( $log ) > 500 ) {
			$log = array_slice( $log, -500 );
		}

		update_option( 'mco_promo_log', $log );
	}
}

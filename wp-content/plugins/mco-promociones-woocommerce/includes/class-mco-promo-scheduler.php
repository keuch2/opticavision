<?php
/**
 * Scheduler (Cron Jobs) para activación y expiración de Promociones.
 *
 * @package MCO_Promociones
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clase MCO_Promo_Scheduler
 *
 * Gestiona los cron jobs de WordPress para automatizar la aplicación
 * y reversión de promociones según sus fechas de vigencia.
 */
class MCO_Promo_Scheduler {

	/**
	 * Nombre del hook del cron.
	 *
	 * @var string
	 */
	const CRON_HOOK = 'mco_promo_verificar_cron';

	/**
	 * Nombre del intervalo personalizado.
	 *
	 * @var string
	 */
	const CRON_INTERVAL = 'mco_promo_cada_5min';

	/**
	 * Inicializa los hooks del scheduler.
	 *
	 * @return void
	 */
	public function init() {
		add_filter( 'cron_schedules', array( $this, 'agregar_intervalo' ) );
		add_action( self::CRON_HOOK, array( $this, 'verificar_promociones' ) );
	}

	/**
	 * Registra el intervalo de cron personalizado de 5 minutos.
	 *
	 * @param array $schedules Intervalos existentes de WordPress.
	 * @return array Intervalos con el nuevo agregado.
	 */
	public function agregar_intervalo( array $schedules ): array {
		$schedules[ self::CRON_INTERVAL ] = array(
			'interval' => 5 * MINUTE_IN_SECONDS,
			'display'  => __( 'Cada 5 minutos', 'mco-promociones' ),
		);
		return $schedules;
	}

	/**
	 * Activa el cron al activar el plugin.
	 *
	 * @return void
	 */
	public static function activar() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), self::CRON_INTERVAL, self::CRON_HOOK );
		}
	}

	/**
	 * Desactiva el cron al desactivar el plugin.
	 *
	 * @return void
	 */
	public static function desactivar() {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}
		wp_clear_scheduled_hook( self::CRON_HOOK );
	}

	/**
	 * Verifica todas las promociones activas y aplica/revierte según corresponda.
	 *
	 * Se ejecuta cada 5 minutos vía WordPress Cron.
	 *
	 * @return void
	 */
	public function verificar_promociones() {
		$ahora = current_time( 'Y-m-d H:i:s' );

		$promos = get_posts(
			array(
				'post_type'      => 'mco_promocion',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		if ( empty( $promos ) ) {
			return;
		}

		require_once MCO_PROMO_PLUGIN_DIR . 'includes/class-mco-promo-engine.php';
		$engine = new MCO_Promo_Engine();

		foreach ( $promos as $promo_id ) {
			$promo_id     = absint( $promo_id );
			$fecha_inicio = get_post_meta( $promo_id, '_mco_promo_fecha_inicio', true );
			$fecha_fin    = get_post_meta( $promo_id, '_mco_promo_fecha_fin', true );
			$aplicada     = (bool) get_post_meta( $promo_id, '_mco_promo_aplicada', true );

			if ( empty( $fecha_inicio ) || empty( $fecha_fin ) ) {
				continue;
			}

			// Caso 1: Dentro del período de vigencia y aún no aplicada → aplicar.
			if ( $ahora >= $fecha_inicio && $ahora < $fecha_fin && ! $aplicada ) {
				$engine->aplicar_promocion( $promo_id );
				continue;
			}

			// Caso 2: Ha vencido y estaba aplicada → revertir.
			if ( $ahora >= $fecha_fin && $aplicada ) {
				$engine->revertir_promocion( $promo_id );
				continue;
			}
		}
	}
}

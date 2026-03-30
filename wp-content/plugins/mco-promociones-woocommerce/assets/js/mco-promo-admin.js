/**
 * MCO Promociones WooCommerce - JavaScript de administración
 *
 * @package MCO_Promociones
 * @since   1.0.0
 */

/* global mcoPromoData */

jQuery( function ( $ ) {
	'use strict';

	// ----------------------------------------------------------------
	// Estado del módulo de selección manual
	// ----------------------------------------------------------------
	var selectedProducts = {}; // { id: { id, name, sku, regular_price, thumbnail_url } }
	var currentPage      = 1;
	var totalPages       = 1;
	var searchTimeout    = null;
	var currentSearch    = '';

	// ----------------------------------------------------------------
	// Inicialización: cargar productos seleccionados previos (edición)
	// ----------------------------------------------------------------
	function inicializar() {
		var iniciales = [];

		try {
			var $script = $( '#mco-productos-iniciales' );
			if ( $script.length ) {
				iniciales = JSON.parse( $script.text() );
			}
		} catch ( e ) {
			iniciales = [];
		}

		if ( iniciales && iniciales.length ) {
			$.each( iniciales, function ( i, product ) {
				selectedProducts[ product.id ] = product;
			} );
			renderizarSeleccionados();
		}
	}

	// ----------------------------------------------------------------
	// Cambio de tipo de selección (radio buttons)
	// ----------------------------------------------------------------
	$( 'input[name="mco_tipo_seleccion"]' ).on( 'change', function () {
		var valor = $( this ).val();

		$( '.mco-panel' ).hide();
		$( '#mco-panel-' + valor ).show();

		// Actualizar clases active en los labels.
		$( '.mco-radio-option' ).removeClass( 'active' );
		$( this ).closest( '.mco-radio-option' ).addClass( 'active' );
	} );

	// Inicializar estado activo de radio.
	$( 'input[name="mco_tipo_seleccion"]:checked' ).closest( '.mco-radio-option' ).addClass( 'active' );

	// ----------------------------------------------------------------
	// Seleccionar / deseleccionar todas las categorías
	// ----------------------------------------------------------------
	function actualizarContadorCategorias() {
		var total      = $( 'input[name="mco_categorias[]"]' ).length;
		var marcadas   = $( 'input[name="mco_categorias[]"]:checked' ).length;
		var $contador  = $( '#mco-contador-categorias' );
		if ( total > 0 ) {
			$contador.text( marcadas + ' / ' + total + ' seleccionadas' );
		}
	}

	$( '#mco-seleccionar-todas-cats' ).on( 'click', function () {
		$( 'input[name="mco_categorias[]"]' ).prop( 'checked', true );
		actualizarContadorCategorias();
	} );

	$( '#mco-deseleccionar-todas-cats' ).on( 'click', function () {
		$( 'input[name="mco_categorias[]"]' ).prop( 'checked', false );
		actualizarContadorCategorias();
	} );

	// Actualizar contador al cambiar cualquier checkbox de categoría.
	$( document ).on( 'change', 'input[name="mco_categorias[]"]', function () {
		actualizarContadorCategorias();
	} );

	// Inicializar contador al cargar.
	actualizarContadorCategorias();

	// ----------------------------------------------------------------
	// Búsqueda de productos (debounce 400ms)
	// ----------------------------------------------------------------
	$( '#mco-buscar-producto' ).on( 'input', function () {
		clearTimeout( searchTimeout );
		var s = $( this ).val();
		searchTimeout = setTimeout( function () {
			currentSearch = s;
			currentPage   = 1;
			buscarProductos();
		}, 400 );
	} );

	function buscarProductos() {
		var $spinner   = $( '.mco-spinner' );
		var $resultados = $( '#mco-resultados-busqueda' );
		var $paginacion = $( '#mco-paginacion-busqueda' );

		$spinner.addClass( 'visible' );
		$resultados.html( '<p style="padding:12px;">' + mcoPromoData.textBuscando + '</p>' );
		$paginacion.empty();

		$.post(
			mcoPromoData.ajaxUrl,
			{
				action : 'mco_promo_buscar_productos',
				nonce  : mcoPromoData.nonce,
				s      : currentSearch,
				page   : currentPage
			},
			function ( response ) {
				$spinner.removeClass( 'visible' );

				if ( ! response.success ) {
					$resultados.html( '<p style="padding:12px;color:#d63638;">' + mcoPromoData.textSinResultados + '</p>' );
					return;
				}

				var products   = response.data.products || [];
				totalPages     = response.data.total_pages || 1;

				if ( ! products.length ) {
					$resultados.html( '<p style="padding:12px;">' + mcoPromoData.textSinResultados + '</p>' );
					return;
				}

				renderizarResultados( products );
				renderizarPaginacion();
			}
		).fail( function () {
			$spinner.removeClass( 'visible' );
			$resultados.html( '<p style="padding:12px;color:#d63638;">Error de conexión.</p>' );
		} );
	}

	function renderizarResultados( products ) {
		var html = '<table>';
		html += '<thead><tr>';
		html += '<th></th><th>Producto</th><th>SKU</th><th>Precio</th><th></th>';
		html += '</tr></thead><tbody>';

		$.each( products, function ( i, p ) {
			var isSelected = !! selectedProducts[ p.id ];
			var imgHtml    = p.thumbnail_url
				? '<img src="' + escAttr( p.thumbnail_url ) + '" class="mco-resultado-thumb" alt="">'
				: '<span style="display:inline-block;width:40px;height:40px;background:#f0f0f1;border-radius:3px;"></span>';

			html += '<tr data-product-id="' + escAttr( p.id ) + '">';
			html += '<td>' + imgHtml + '</td>';
			html += '<td class="mco-resultado-nombre">' + escHtml( p.name ) + '</td>';
			html += '<td class="mco-resultado-sku">' + escHtml( p.sku || '—' ) + '</td>';
			html += '<td class="mco-resultado-precio">' + escHtml( p.regular_price || '—' ) + '</td>';
			html += '<td>';
			if ( isSelected ) {
				html += '<button type="button" class="button button-small mco-btn-deseleccionar" data-product=\'' + JSON.stringify( p ) + '\'>' +
					'&#10003; ' + escHtml( mcoPromoData.textSeleccionar ) + '</button>';
			} else {
				html += '<button type="button" class="button button-small button-primary mco-btn-seleccionar" data-product=\'' + JSON.stringify( p ) + '\'>' +
					escHtml( mcoPromoData.textSeleccionar ) + '</button>';
			}
			html += '</td>';
			html += '</tr>';
		} );

		html += '</tbody>';

		// Botón "Seleccionar todos los resultados"
		html += '</table>';
		html += '<div style="padding:8px 10px;border-top:1px solid #f0f0f1;">';
		html += '<button type="button" class="button mco-btn-seleccionar-todos" data-products=\'' + JSON.stringify( products ) + '\'>';
		html += 'Seleccionar todos los resultados</button>';
		html += '</div>';

		$( '#mco-resultados-busqueda' ).html( html );
	}

	function renderizarPaginacion() {
		if ( totalPages <= 1 ) {
			$( '#mco-paginacion-busqueda' ).empty();
			return;
		}

		var html = '';

		if ( currentPage > 1 ) {
			html += '<button type="button" class="button button-small mco-ir-pagina" data-page="' + ( currentPage - 1 ) + '">&laquo;</button>';
		}

		for ( var i = 1; i <= totalPages; i++ ) {
			var activeClass = ( i === currentPage ) ? ' active' : '';
			html += '<button type="button" class="button button-small mco-ir-pagina' + activeClass + '" data-page="' + i + '">' + i + '</button>';
		}

		if ( currentPage < totalPages ) {
			html += '<button type="button" class="button button-small mco-ir-pagina" data-page="' + ( currentPage + 1 ) + '">&raquo;</button>';
		}

		$( '#mco-paginacion-busqueda' ).html( html );
	}

	// ----------------------------------------------------------------
	// Eventos: seleccionar / deseleccionar producto individual
	// ----------------------------------------------------------------
	$( '#mco-resultados-busqueda' ).on( 'click', '.mco-btn-seleccionar', function () {
		var product = JSON.parse( $( this ).attr( 'data-product' ) );
		selectedProducts[ product.id ] = product;
		actualizarHiddenInputs();
		renderizarSeleccionados();
		// Actualizar botón en la tabla.
		$( this ).closest( 'tr' )
			.find( '.mco-btn-seleccionar' )
			.replaceWith(
				'<button type="button" class="button button-small mco-btn-deseleccionar" data-product=\'' +
				$( this ).attr( 'data-product' ) + '\'>&#10003; ' + escHtml( mcoPromoData.textSeleccionar ) + '</button>'
			);
		// Verificar conflictos.
		verificarConflictos();
	} );

	$( '#mco-resultados-busqueda' ).on( 'click', '.mco-btn-deseleccionar', function () {
		var product = JSON.parse( $( this ).attr( 'data-product' ) );
		delete selectedProducts[ product.id ];
		actualizarHiddenInputs();
		renderizarSeleccionados();
		$( this ).closest( 'tr' )
			.find( '.mco-btn-deseleccionar' )
			.replaceWith(
				'<button type="button" class="button button-small button-primary mco-btn-seleccionar" data-product=\'' +
				$( this ).attr( 'data-product' ) + '\'>' + escHtml( mcoPromoData.textSeleccionar ) + '</button>'
			);
	} );

	// ----------------------------------------------------------------
	// Seleccionar todos los resultados actuales
	// ----------------------------------------------------------------
	$( '#mco-resultados-busqueda' ).on( 'click', '.mco-btn-seleccionar-todos', function () {
		var products = JSON.parse( $( this ).attr( 'data-products' ) );
		$.each( products, function ( i, p ) {
			selectedProducts[ p.id ] = p;
		} );
		actualizarHiddenInputs();
		renderizarSeleccionados();
		// Re-renderizar resultados para mostrar estado actualizado.
		renderizarResultados( products );
		verificarConflictos();
	} );

	// ----------------------------------------------------------------
	// Paginación
	// ----------------------------------------------------------------
	$( '#mco-paginacion-busqueda' ).on( 'click', '.mco-ir-pagina', function () {
		currentPage = parseInt( $( this ).data( 'page' ), 10 );
		buscarProductos();
	} );

	// ----------------------------------------------------------------
	// Remover producto de la lista de seleccionados
	// ----------------------------------------------------------------
	$( '#mco-lista-seleccionados' ).on( 'click', '.mco-btn-remover', function () {
		var pid = $( this ).data( 'id' );
		delete selectedProducts[ pid ];
		actualizarHiddenInputs();
		renderizarSeleccionados();
		// Actualizar botón en resultados si están visibles.
		$( '#mco-resultados-busqueda tr[data-product-id="' + pid + '"] .mco-btn-deseleccionar' )
			.each( function () {
				var dataProduct = $( this ).attr( 'data-product' );
				$( this ).replaceWith(
					'<button type="button" class="button button-small button-primary mco-btn-seleccionar" data-product=\'' +
					dataProduct + '\'>' + escHtml( mcoPromoData.textSeleccionar ) + '</button>'
				);
			} );
	} );

	// ----------------------------------------------------------------
	// Limpiar selección
	// ----------------------------------------------------------------
	$( '#mco-limpiar-seleccion' ).on( 'click', function () {
		selectedProducts = {};
		actualizarHiddenInputs();
		renderizarSeleccionados();
		// Actualizar botones en resultados.
		$( '#mco-resultados-busqueda .mco-btn-deseleccionar' ).each( function () {
			var dataProduct = $( this ).attr( 'data-product' );
			$( this ).replaceWith(
				'<button type="button" class="button button-small button-primary mco-btn-seleccionar" data-product=\'' +
				dataProduct + '\'>' + escHtml( mcoPromoData.textSeleccionar ) + '</button>'
			);
		} );
	} );

	// ----------------------------------------------------------------
	// Renderizar lista de seleccionados
	// ----------------------------------------------------------------
	function renderizarSeleccionados() {
		var productos = Object.values( selectedProducts );
		var $lista    = $( '#mco-lista-seleccionados' );
		var $contador = $( '#mco-contador-seleccionados' );

		$contador.text( productos.length + ' ' + mcoPromoData.textSeleccionados );

		if ( ! productos.length ) {
			$lista.empty();
			return;
		}

		var html = '<table class="mco-lista-seleccionados-tabla"><tbody>';
		$.each( productos, function ( i, p ) {
			var imgHtml = p.thumbnail_url
				? '<img src="' + escAttr( p.thumbnail_url ) + '" class="mco-resultado-thumb" alt="" style="width:30px;height:30px;">'
				: '';

			html += '<tr>';
			html += '<td style="width:36px;">' + imgHtml + '</td>';
			html += '<td>' + escHtml( p.name ) + '</td>';
			html += '<td style="color:#787c82;font-size:12px;">' + escHtml( p.sku || '' ) + '</td>';
			html += '<td style="color:#787c82;font-size:12px;">' + escHtml( p.regular_price || '' ) + '</td>';
			html += '<td style="text-align:right;width:36px;"><button type="button" class="mco-btn-remover" data-id="' + escAttr( p.id ) + '" title="Remover">&times;</button></td>';
			html += '</tr>';
		} );
		html += '</tbody></table>';

		$lista.html( html );
	}

	// ----------------------------------------------------------------
	// Actualizar hidden inputs con la selección actual
	// ----------------------------------------------------------------
	function actualizarHiddenInputs() {
		var $container = $( '#mco-hidden-productos' );
		$container.empty();
		$.each( selectedProducts, function ( pid ) {
			$container.append( '<input type="hidden" name="mco_productos[]" value="' + escAttr( pid ) + '">' );
		} );
	}

	// ----------------------------------------------------------------
	// Verificar conflictos con otras promociones activas
	// ----------------------------------------------------------------
	function verificarConflictos() {
		var ids    = Object.keys( selectedProducts );
		var promoId = $( 'input[name="promo_id"]' ).val() || 0;

		if ( ! ids.length ) {
			$( '#mco-promo-aviso-conflicto' ).hide();
			return;
		}

		$.post(
			mcoPromoData.ajaxUrl,
			{
				action          : 'mco_promo_verificar_conflictos',
				nonce           : mcoPromoData.nonce,
				producto_ids    : ids,
				excluir_promo_id: promoId
			},
			function ( response ) {
				if ( ! response.success || ! response.data.conflictos.length ) {
					$( '#mco-promo-aviso-conflicto' ).hide();
					return;
				}

				var conflictos = response.data.conflictos;
				var nombres    = [];

				$.each( conflictos, function ( i, c ) {
					nombres.push( '"' + c.name + '" (' + c.promo + ')' );
				} );

				$( '#mco-promo-conflicto-texto' ).text( nombres.join( ', ' ) );
				$( '#mco-promo-aviso-conflicto' ).show();
			}
		);
	}

	// ----------------------------------------------------------------
	// Validaciones del formulario al enviar
	// ----------------------------------------------------------------
	$( '#mco-promo-form' ).on( 'submit', function ( e ) {
		var errores = [];
		var tipoSeleccion = $( 'input[name="mco_tipo_seleccion"]:checked' ).val();
		var fechaInicio   = $( '#mco_fecha_inicio' ).val();
		var fechaFin      = $( '#mco_fecha_fin' ).val();
		var porcentaje    = parseFloat( $( '#mco_porcentaje' ).val() );

		if ( ! $( '#mco_titulo' ).val().trim() ) {
			errores.push( 'El nombre de la promoción es requerido.' );
		}

		if ( isNaN( porcentaje ) || porcentaje <= 0 || porcentaje >= 100 ) {
			errores.push( 'El porcentaje debe ser entre 0.01 y 99.99.' );
		}

		if ( fechaInicio && fechaFin && fechaFin <= fechaInicio ) {
			errores.push( 'La fecha de fin debe ser posterior a la fecha de inicio.' );
		}

		if ( 'manual' === tipoSeleccion && ! Object.keys( selectedProducts ).length ) {
			errores.push( 'Debes seleccionar al menos un producto.' );
		}

		if ( 'categoria' === tipoSeleccion && ! $( 'input[name="mco_categorias[]"]:checked' ).length ) {
			errores.push( 'Debes seleccionar al menos una categoría.' );
		}

		if ( errores.length ) {
			e.preventDefault();
			alert( errores.join( '\n' ) );
		}
	} );

	// ----------------------------------------------------------------
	// Utilidades de escape
	// ----------------------------------------------------------------
	function escHtml( str ) {
		return String( str )
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			.replace( /"/g, '&quot;' )
			.replace( /'/g, '&#039;' );
	}

	function escAttr( str ) {
		return String( str )
			.replace( /&/g, '&amp;' )
			.replace( /"/g, '&quot;' )
			.replace( /'/g, '&#039;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' );
	}

	// ----------------------------------------------------------------
	// Ejecutar inicialización
	// ----------------------------------------------------------------
	inicializar();

	// ----------------------------------------------------------------
	// Overlay de progreso (spinner CSS puro, sin dependencias externas)
	// ----------------------------------------------------------------
	var $overlay = null;

	function mostrarOverlay( mensaje ) {
		if ( ! $overlay ) {
			$overlay = $(
				'<div id="mco-overlay">' +
				'<div class="mco-overlay-inner">' +
				'<div class="mco-css-spinner"></div>' +
				'<p class="mco-overlay-msg"></p>' +
				'</div>' +
				'</div>'
			);
			$( 'body' ).append( $overlay );
		}
		$overlay.find( '.mco-overlay-msg' ).text( mensaje );
		$overlay.addClass( 'visible' );
	}

	function ocultarOverlay() {
		if ( $overlay ) {
			$overlay.removeClass( 'visible' );
		}
	}

	function mostrarNoticeBanner( mensaje, tipo ) {
		var $notice = $(
			'<div class="notice notice-' + escAttr( tipo ) + ' is-dismissible mco-inline-notice">' +
			'<p>' + escHtml( mensaje ) + '</p>' +
			'</div>'
		);
		$( '.wp-heading-inline' ).closest( 'h1' ).after( $notice );
		setTimeout( function () {
			$notice.fadeOut( 400, function () { $( this ).remove(); } );
		}, 4000 );
	}

	// ----------------------------------------------------------------
	// AJAX: Interceptar formularios de Aplicar / Revertir
	// ----------------------------------------------------------------
	$( document ).on( 'submit', '.mco-form-accion', function ( e ) {
		e.preventDefault();

		var $form   = $( this );
		var promoId = $form.data( 'promo-id' );
		var nonce   = $form.data( 'nonce' );
		var accion  = $form.data( 'accion' );
		var $btn    = $form.find( 'button[type="submit"]' );

		var msgOverlay = ( 'aplicar' === accion )
			? mcoPromoData.textAplicando
			: mcoPromoData.textRevirtiendo;

		$btn.prop( 'disabled', true );
		mostrarOverlay( msgOverlay );

		$.post(
			mcoPromoData.ajaxUrl,
			{
				action   : 'mco_promo_' + accion + '_ajax',
				promo_id : promoId,
				nonce    : nonce
			},
			function ( response ) {
				ocultarOverlay();
				$btn.prop( 'disabled', false );

				if ( response.success ) {
					var textOk = ( 'aplicar' === accion )
						? mcoPromoData.textAplicada
						: mcoPromoData.textRevertida;
					mostrarNoticeBanner( textOk, 'success' );
					// Recargar para reflejar el nuevo estado de la fila.
					setTimeout( function () { window.location.reload(); }, 1200 );
				} else {
					var msg = ( response.data && response.data.message )
						? response.data.message
						: mcoPromoData.textError;
					mostrarNoticeBanner( msg, 'error' );
				}
			}
		).fail( function () {
			ocultarOverlay();
			$btn.prop( 'disabled', false );
			mostrarNoticeBanner( mcoPromoData.textError, 'error' );
		} );
	} );

	// ----------------------------------------------------------------
	// AJAX: Eliminar todos los descuentos
	// ----------------------------------------------------------------
	$( document ).on( 'click', '#mco-btn-borrar-todos', function ( e ) {
		e.preventDefault();

		if ( ! window.confirm( mcoPromoData.textConfirmBorrarTodos ) ) {
			return;
		}

		var $btn = $( this );
		$btn.prop( 'disabled', true );
		mostrarOverlay( mcoPromoData.textBorrandoTodos );

		$.post(
			mcoPromoData.ajaxUrl,
			{
				action : 'mco_promo_borrar_todos_ajax',
				nonce  : mcoPromoData.nonceBorrarTodos
			},
			function ( response ) {
				ocultarOverlay();
				$btn.prop( 'disabled', false );

				if ( response.success ) {
					var procesados = ( response.data && response.data.procesados ) ? response.data.procesados : 0;
					var textOk = mcoPromoData.textBorradosOk.replace( '{n}', procesados );
					mostrarNoticeBanner( textOk, 'success' );
					setTimeout( function () { window.location.reload(); }, 1800 );
				} else {
					mostrarNoticeBanner( mcoPromoData.textError, 'error' );
				}
			}
		).fail( function () {
			ocultarOverlay();
			$btn.prop( 'disabled', false );
			mostrarNoticeBanner( mcoPromoData.textError, 'error' );
		} );
	} );

} );

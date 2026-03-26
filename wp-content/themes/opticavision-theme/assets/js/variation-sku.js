/**
 * Variación SKU — actualiza el campo visible de SKU en tiempo real
 * al seleccionar una variación de producto.
 *
 * @package OpticaVision_Theme
 */
jQuery(function ($) {
    var $skuContainer = $('.product-sku');
    if (!$skuContainer.length) return;

    var $skuValue   = $skuContainer.find('.sku-value');
    var originalSku = $skuContainer.data('original-sku') || '';

    // Al encontrar una variación válida
    $('.variations_form').on('found_variation', function (event, variation) {
        if (variation.sku) {
            $skuValue.text(variation.sku);
            $skuContainer.show();
        } else if (originalSku) {
            $skuValue.text(originalSku);
            $skuContainer.show();
        } else {
            $skuContainer.hide();
        }
    });

    // Al limpiar la selección de variación
    $('.variations_form').on('reset_data', function () {
        if (originalSku) {
            $skuValue.text(originalSku);
            $skuContainer.show();
        } else {
            $skuValue.text('');
            $skuContainer.hide();
        }
    });
});

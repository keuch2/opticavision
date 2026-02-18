# WooCommerce AJAX Filters con Dropdowns

Plugin para filtrar productos de WooCommerce con filtros AJAX, incluyendo rango de precios y marcas.

## Descripción

Este plugin añade filtros AJAX para productos de WooCommerce con:
- Filtro de marcas con checkboxes
- Rango de precios con slider y campos de entrada
- Actualización en tiempo real de los productos mostrados
- Diseño responsive y accesible

## Instalación

1. Sube la carpeta `woo-ajax-filters` al directorio `/wp-content/plugins/`
2. Activa el plugin a través del menú 'Plugins' en WordPress
3. Utiliza los shortcodes en tus páginas

## Uso

### Shortcodes disponibles

El plugin proporciona dos shortcodes principales:

1. `[wc_ajax_filters]` - Muestra el panel de filtros con marcas y rango de precios
2. `[wc_ajax_filtered_products]` - Muestra la lista de productos que se actualizará según los filtros

### Ejemplo de uso

```php
<!-- Panel de filtros -->
<?php echo do_shortcode('[wc_ajax_filters]'); ?>

<!-- Lista de productos filtrados -->
<?php echo do_shortcode('[wc_ajax_filtered_products]'); ?>
```

### Página de ejemplo

El plugin incluye una plantilla de página de ejemplo en el tema hijo:
- `page-filtros.php` - Plantilla con layout de 2 columnas (filtros + productos)

Para usar esta plantilla:
1. Crea una nueva página en WordPress
2. En el panel de atributos de página, selecciona la plantilla "Página con Filtros AJAX"
3. Publica la página

## Personalización

### CSS

Puedes personalizar la apariencia de los filtros modificando el archivo:
`/wp-content/plugins/woo-ajax-filters/css/wc-ajax-filters.css`

### JavaScript

La lógica de los filtros se encuentra en:
`/wp-content/plugins/woo-ajax-filters/js/wc-ajax-filters.js`

## Estructura de archivos

```
woo-ajax-filters/
├── css/
│   └── wc-ajax-filters.css       # Estilos de los filtros
├── js/
│   ├── wc-ajax-filters.js        # Lógica principal de filtros
│   └── pagination-arrows.js      # Funcionalidad de paginación
├── woocommerce-ajax-filters.php  # Archivo principal del plugin
└── README.md                     # Esta documentación
```

## Integración con OpticaVision

Este plugin está integrado con el tema hijo de OpticaVision y utiliza la categoría "marcas" para mostrar las marcas disponibles como filtros. La función `wc_ajax_filters_get_brand_terms()` obtiene las subcategorías de la categoría "marcas" para mostrarlas como opciones de filtro.

## Solución de problemas

### Los checkboxes de marcas no aparecen

Verifica que:
1. Existe una categoría de producto con slug "marcas"
2. Esta categoría tiene subcategorías (las marcas)
3. Hay productos asignados a estas subcategorías

### Los productos no se actualizan al filtrar

Verifica que:
1. jQuery está cargado correctamente
2. No hay errores JavaScript en la consola del navegador
3. La URL de AJAX es correcta en la configuración del plugin

## Créditos

Desarrollado por Mister Co. para OpticaVision.

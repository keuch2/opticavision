=== MCO Promociones WooCommerce ===
Contributors: mco-opticavision
Tags: woocommerce, descuentos, promociones, precios, bulk discount
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
WC requires at least: 7.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Gestiona promociones de WooCommerce: descuentos por porcentaje aplicados masivamente a grupos de productos, con fechas de vigencia y reversión automática.

== Descripción ==

MCO Promociones WooCommerce permite crear y gestionar promociones de descuento para tu tienda WooCommerce de forma simple y eficiente.

**Características principales:**

* Crea promociones con porcentaje de descuento configurable
* Define fechas y horas de inicio y fin de la vigencia
* Tres modos de selección: todos los productos, por categoría, o selección manual
* Aplicación y reversión automática de precios según las fechas
* Snapshot de precios originales para reversión perfecta
* Búsqueda de productos en tiempo real (AJAX) para selección manual
* Detección de conflictos entre promociones activas
* Log completo de todas las acciones realizadas
* Compatible con productos simples y variaciones de productos variables

**Automatización:**

El plugin usa WordPress Cron (cada 5 minutos) para:
- Activar automáticamente las promociones al llegar su fecha de inicio
- Revertir automáticamente los precios al vencer la promoción

**Seguridad:**

* Validación de nonces en todos los formularios y peticiones AJAX
* Verificación de capacidades de usuario (manage_woocommerce)
* Sanitización de todos los inputs
* Escapado de todos los outputs

== Instalación ==

1. Sube el directorio `mco-promociones-woocommerce` al directorio `/wp-content/plugins/`
2. Activa el plugin desde el menú 'Plugins' en WordPress
3. Ve a **Promociones** en el menú de administración

== Uso ==

1. Ve a **Promociones > Nueva Promoción**
2. Completa el nombre, porcentaje de descuento y fechas de vigencia
3. Selecciona los productos (todos, por categoría, o manualmente)
4. Guarda la promoción
5. Los precios se aplicarán automáticamente en la fecha de inicio y se revertirán en la fecha de fin
6. También puedes aplicar o revertir manualmente desde el listado de promociones

== Preguntas frecuentes ==

= ¿Qué pasa si elimino una promoción mientras está activa? =
El plugin revertirá automáticamente los precios originales antes de eliminar la promoción.

= ¿El plugin modifica los precios de productos variables? =
No modifica directamente el producto variable, sino las variaciones individuales que tengan precio propio.

= ¿Con qué frecuencia se verifica la vigencia de las promociones? =
El cron se ejecuta cada 5 minutos. También puedes aplicar/revertir manualmente desde el listado.

== Registro de cambios ==

= 1.0.0 =
* Versión inicial del plugin

== Actualización ==

Actualiza a través del panel de Plugins de WordPress como cualquier otro plugin.

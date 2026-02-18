<?php
/**
 * Script temporal para configurar Checkout Block
 * 
 * Ejecutar una vez visitando: /wp-content/themes/opticavision-theme/setup-checkout-block.php
 * Luego BORRAR este archivo por seguridad
 */

// Cargar WordPress
require_once('../../../wp-load.php');

// Verificar que el usuario esté logueado y sea administrador
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    die('Acceso denegado. Debes ser administrador.');
}

echo '<h1>Configuración de WooCommerce Checkout Block</h1>';
echo '<style>body { font-family: Arial, sans-serif; padding: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; }</style>';

// 1. Verificar si existe la página de checkout
$checkout_page_id = wc_get_page_id('checkout');

if ($checkout_page_id > 0) {
    $checkout_page = get_post($checkout_page_id);
    echo '<p class="info">✓ Página de Checkout encontrada: ID ' . $checkout_page_id . '</p>';
    
    // Verificar si ya tiene el bloque
    if (has_block('woocommerce/checkout', $checkout_page)) {
        echo '<p class="success">✓ La página ya tiene el bloque de Checkout de WooCommerce</p>';
    } else {
        echo '<p class="info">→ Actualizando página de checkout con el bloque...</p>';
        
        // Actualizar el contenido con el bloque de checkout
        $new_content = '<!-- wp:woocommerce/checkout {"className":"wc-block-checkout"} /-->';
        
        $updated = wp_update_post(array(
            'ID' => $checkout_page_id,
            'post_content' => $new_content,
        ));
        
        if ($updated) {
            echo '<p class="success">✓ Página de checkout actualizada con WooCommerce Blocks</p>';
        } else {
            echo '<p class="error">✗ Error al actualizar la página</p>';
        }
    }
} else {
    echo '<p class="error">✗ No se encontró la página de checkout</p>';
    echo '<p>Por favor, ve a WooCommerce → Ajustes → Avanzado y configura las páginas de WooCommerce.</p>';
}

// 2. Verificar que los scripts estén registrados
echo '<h2>Verificación de Scripts</h2>';

global $wp_scripts;

if (isset($wp_scripts->registered['opticavision-checkout-block-integration'])) {
    echo '<p class="success">✓ Script de integración registrado</p>';
} else {
    echo '<p class="error">✗ Script de integración NO registrado</p>';
}

// 3. Verificar que la clase de integración existe
if (class_exists('OpticaVision_Checkout_Block_Integration')) {
    echo '<p class="success">✓ Clase de integración existe</p>';
} else {
    echo '<p class="error">✗ Clase de integración NO existe</p>';
}

// 4. Verificar archivos JavaScript
$js_file = get_template_directory() . '/assets/js/checkout-block-integration.js';
if (file_exists($js_file)) {
    echo '<p class="success">✓ Archivo JavaScript existe</p>';
} else {
    echo '<p class="error">✗ Archivo JavaScript NO existe: ' . $js_file . '</p>';
}

echo '<hr>';
echo '<h2>Próximos pasos:</h2>';
echo '<ol>';
echo '<li>Limpia la caché del navegador (Ctrl+Shift+R)</li>';
echo '<li>Ve a la página de Checkout: <a href="' . wc_get_checkout_url() . '" target="_blank">' . wc_get_checkout_url() . '</a></li>';
echo '<li>Verifica que aparezca el campo "Número de Cédula o RUC"</li>';
echo '<li><strong>IMPORTANTE: Borra este archivo (setup-checkout-block.php) por seguridad</strong></li>';
echo '</ol>';

echo '<hr>';
echo '<p><a href="' . admin_url() . '">← Volver al admin</a></p>';

<?php
/**
 * Custom AJAX endpoint for Safari compatibility
 * Safari bloquea cookies en admin-ajax.php, este endpoint NO requiere cookies
 */

// Deshabilitar output buffering de WordPress que pueda causar conflictos
define('DOING_AJAX', true);

// Cargar WordPress usando path absoluto
$wp_load_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
if (!file_exists($wp_load_path)) {
    header('Content-Type: application/json; charset=UTF-8');
    die(json_encode(['success' => false, 'message' => 'WordPress not found']));
}
require_once($wp_load_path);

// Verificar que sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    wp_send_json_error(['message' => 'Only POST requests allowed']);
}

// Verificar action
if (!isset($_POST['action']) || $_POST['action'] !== 'wc_ajax_filter') {
    wp_send_json_error(['message' => 'Invalid action']);
}

// Llamar la función del plugin
if (function_exists('wc_ajax_filter_products')) {
    error_log('[WC AJAX FILTER] Custom endpoint ejecutado - llamando a wc_ajax_filter_products()');
    wc_ajax_filter_products();
    // La función wc_ajax_filter_products() ya hace wp_die(), no necesitamos nada más aquí
} else {
    wp_send_json_error(['message' => 'Function not found']);
}

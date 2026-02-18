<?php
/**
 * OpticaVision Checkout Block Integration
 * 
 * Extiende el Checkout Block de WooCommerce con campos personalizados
 * 
 * @package OpticaVision_Theme
 * @version 1.0.0
 */

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

defined('ABSPATH') || exit;

/**
 * Clase para integrar campos personalizados con WooCommerce Blocks
 */
class OpticaVision_Checkout_Block_Integration implements IntegrationInterface {

    /**
     * Nombre de la integración
     */
    public function get_name() {
        return 'opticavision-checkout-fields';
    }

    /**
     * Inicializar la integración
     */
    public function initialize() {
        $this->register_main_integration();
        $this->register_editor_integration();
    }

    /**
     * Registrar script principal para el frontend
     */
    private function register_main_integration() {
        $script_path = '/assets/js/checkout-block-integration.js';
        $script_url = get_template_directory_uri() . $script_path;
        $script_file = get_template_directory() . $script_path;
        
        // Verificar que el archivo existe
        if (!file_exists($script_file)) {
            error_log('OpticaVision: checkout-block-integration.js no encontrado en ' . $script_file);
            return;
        }
        
        $script_asset_path = get_template_directory() . '/assets/js/checkout-block-integration.asset.php';
        
        $script_asset = file_exists($script_asset_path)
            ? require $script_asset_path
            : array(
                'dependencies' => array(
                    'wp-element',
                    'wp-i18n',
                    'wp-plugins',
                    'wc-blocks-checkout',
                    'wc-settings',
                ),
                'version' => filemtime($script_file),
            );

        wp_register_script(
            'opticavision-checkout-block-integration',
            $script_url,
            $script_asset['dependencies'],
            $script_asset['version'],
            true
        );
        
        // Encolar inmediatamente si estamos en checkout
        if (is_checkout()) {
            wp_enqueue_script('opticavision-checkout-block-integration');
        }
    }

    /**
     * Registrar script para el editor de bloques
     */
    private function register_editor_integration() {
        $script_path = '/assets/js/checkout-block-editor.js';
        $script_url = get_template_directory_uri() . $script_path;
        
        wp_register_script(
            'opticavision-checkout-block-editor',
            $script_url,
            array('wp-blocks', 'wp-element', 'wp-i18n'),
            filemtime(get_template_directory() . $script_path),
            true
        );
    }

    /**
     * Retornar handles de scripts para frontend
     */
    public function get_script_handles() {
        return array('opticavision-checkout-block-integration');
    }

    /**
     * Retornar handles de scripts para editor
     */
    public function get_editor_script_handles() {
        return array('opticavision-checkout-block-editor');
    }

    /**
     * Retornar datos para pasar al script
     */
    public function get_script_data() {
        return array(
            'fields' => array(
                'cedula_ruc' => array(
                    'label'       => __('Número de Cédula o RUC', 'opticavision-theme'),
                    'placeholder' => __('Ingrese su Cédula o RUC', 'opticavision-theme'),
                    'required'    => true,
                    'hidden'      => false,
                ),
            ),
            'labels' => array(
                'ciudad' => __('Ciudad', 'opticavision-theme'),
            ),
        );
    }
}

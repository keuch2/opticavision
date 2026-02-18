<?php
/**
 * Bancard Gateway Diagnostics
 * Herramienta para diagnosticar problemas con el gateway Bancard
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Bancard_Diagnostics {
    
    /**
     * Ejecuta diagnóstico completo del gateway Bancard
     */
    public static function run_diagnostics() {
        $results = array();
        
        // 1. Verificar WordPress y WooCommerce
        $results['wordpress'] = self::check_wordpress();
        $results['woocommerce'] = self::check_woocommerce();
        
        // 2. Verificar configuración del plugin
        $results['plugin'] = self::check_plugin();
        $results['gateway'] = self::check_gateway();
        
        // 3. Verificar configuración
        $results['settings'] = self::check_settings();
        
        // 4. Verificar carrito
        $results['cart'] = self::check_cart();
        
        // 5. Verificar disponibilidad
        $results['availability'] = self::check_availability();
        
        return $results;
    }
    
    /**
     * Verifica WordPress
     */
    private static function check_wordpress() {
        return array(
            'version' => get_bloginfo('version'),
            'debug' => defined('WP_DEBUG') ? WP_DEBUG : false,
            'memory_limit' => ini_get('memory_limit'),
            'status' => 'ok'
        );
    }
    
    /**
     * Verifica WooCommerce
     */
    private static function check_woocommerce() {
        if (!class_exists('WooCommerce')) {
            return array(
                'status' => 'error',
                'message' => 'WooCommerce no está activo'
            );
        }
        
        return array(
            'version' => WC_VERSION,
            'currency' => get_woocommerce_currency(),
            'cart_enabled' => WC()->cart ? true : false,
            'status' => 'ok'
        );
    }
    
    /**
     * Verifica plugin Bancard
     */
    private static function check_plugin() {
        if (!defined('WC_BANCARD_VERSION')) {
            return array(
                'status' => 'error',
                'message' => 'Plugin Bancard no cargado correctamente'
            );
        }
        
        return array(
            'version' => WC_BANCARD_VERSION,
            'plugin_dir' => WC_BANCARD_PLUGIN_DIR,
            'classes' => array(
                'WC_Gateway_Bancard' => class_exists('WC_Gateway_Bancard'),
                'WC_Bancard_API' => class_exists('WC_Bancard_API'),
                'WC_Bancard_Logger' => class_exists('WC_Bancard_Logger')
            ),
            'status' => 'ok'
        );
    }
    
    /**
     * Verifica gateway
     */
    private static function check_gateway() {
        if (!class_exists('WC_Gateway_Bancard')) {
            return array(
                'status' => 'error',
                'message' => 'Clase WC_Gateway_Bancard no encontrada'
            );
        }
        
        $gateway = new WC_Gateway_Bancard();
        
        // Verificar si está registrado
        $gateways = WC()->payment_gateways()->payment_gateways();
        $registered = isset($gateways['bancard']);
        
        return array(
            'id' => $gateway->id,
            'title' => $gateway->title,
            'registered' => $registered,
            'has_fields' => $gateway->has_fields,
            'supports' => $gateway->supports,
            'status' => $registered ? 'ok' : 'warning'
        );
    }
    
    /**
     * Verifica configuración
     */
    private static function check_settings() {
        if (!class_exists('WC_Gateway_Bancard')) {
            return array(
                'status' => 'error',
                'message' => 'Gateway no disponible'
            );
        }
        
        $gateway = new WC_Gateway_Bancard();
        $settings = wc_bancard_get_settings();
        
        $issues = array();
        
        // Verificar enabled
        $enabled = $gateway->get_option('enabled', 'no');
        if ($enabled !== 'yes') {
            $issues[] = 'Gateway está deshabilitado (enabled = ' . $enabled . ')';
        }
        
        // Verificar claves
        $public_key = isset($settings['public_key']) ? $settings['public_key'] : '';
        $private_key = isset($settings['private_key']) ? $settings['private_key'] : '';
        
        if (empty($public_key)) {
            $issues[] = 'Public Key está vacía';
        }
        
        if (empty($private_key)) {
            $issues[] = 'Private Key está vacía';
        }
        
        // Verificar moneda
        $store_currency = get_woocommerce_currency();
        if ($store_currency !== 'PYG') {
            $exchange_rate = wc_bancard_get_exchange_rate();
            if (empty($exchange_rate) || $exchange_rate <= 0) {
                $issues[] = 'Exchange rate necesario para moneda ' . $store_currency;
            }
        }
        
        return array(
            'enabled' => $enabled,
            'public_key' => !empty($public_key) ? 'Configurada' : 'Vacía',
            'private_key' => !empty($private_key) ? 'Configurada' : 'Vacía',
            'environment' => isset($settings['environment']) ? $settings['environment'] : 'production',
            'currency' => $store_currency,
            'exchange_rate' => $store_currency !== 'PYG' ? wc_bancard_get_exchange_rate() : 'N/A',
            'issues' => $issues,
            'status' => empty($issues) ? 'ok' : 'error'
        );
    }
    
    /**
     * Verifica carrito
     */
    private static function check_cart() {
        if (!WC()->cart) {
            return array(
                'status' => 'error',
                'message' => 'Carrito no disponible'
            );
        }
        
        $cart = WC()->cart;
        $is_empty = $cart->is_empty();
        $total = $cart->get_total();
        $count = $cart->get_cart_contents_count();
        
        $issues = array();
        
        if ($is_empty) {
            $issues[] = 'Carrito está vacío';
        }
        
        if (floatval(strip_tags($total)) <= 0) {
            $issues[] = 'Total del carrito es 0 o negativo';
        }
        
        return array(
            'empty' => $is_empty,
            'total' => strip_tags($total),
            'count' => $count,
            'needs_payment' => $cart->needs_payment(),
            'issues' => $issues,
            'status' => empty($issues) ? 'ok' : 'warning'
        );
    }
    
    /**
     * Verifica disponibilidad del gateway
     */
    private static function check_availability() {
        if (!class_exists('WC_Gateway_Bancard')) {
            return array(
                'status' => 'error',
                'message' => 'Gateway no disponible'
            );
        }
        
        $gateway = new WC_Gateway_Bancard();
        
        // Verificar is_available() directamente
        $is_available = $gateway->is_available();
        
        // Obtener gateways disponibles de WooCommerce
        $available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
        $wc_available = isset($available_gateways['bancard']);
        
        return array(
            'method_available' => $is_available,
            'wc_available' => $wc_available,
            'all_gateways' => array_keys($available_gateways),
            'status' => ($is_available && $wc_available) ? 'ok' : 'error'
        );
    }
    
    /**
     * Genera reporte HTML del diagnóstico
     */
    public static function generate_report() {
        $results = self::run_diagnostics();
        
        ob_start();
        ?>
        <div class="wrap">
            <h1>🔍 Diagnóstico Bancard Gateway</h1>
            
            <?php foreach ($results as $section => $data): ?>
                <div class="card" style="margin: 20px 0; padding: 20px;">
                    <h2><?php echo ucfirst($section); ?>
                        <span class="status-<?php echo $data['status']; ?>" style="
                            padding: 5px 10px; 
                            border-radius: 3px; 
                            font-size: 12px;
                            <?php if ($data['status'] === 'ok') echo 'background: #46b450; color: white;'; ?>
                            <?php if ($data['status'] === 'warning') echo 'background: #ffb900; color: white;'; ?>
                            <?php if ($data['status'] === 'error') echo 'background: #dc3232; color: white;'; ?>
                        ">
                            <?php echo strtoupper($data['status']); ?>
                        </span>
                    </h2>
                    
                    <?php if (isset($data['message'])): ?>
                        <p><strong>Mensaje:</strong> <?php echo esc_html($data['message']); ?></p>
                    <?php endif; ?>
                    
                    <?php if (isset($data['issues']) && !empty($data['issues'])): ?>
                        <h3>⚠️ Problemas encontrados:</h3>
                        <ul style="color: #dc3232;">
                            <?php foreach ($data['issues'] as $issue): ?>
                                <li><?php echo esc_html($issue); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    
                    <table class="widefat" style="margin-top: 10px;">
                        <?php foreach ($data as $key => $value): ?>
                            <?php if (in_array($key, ['status', 'message', 'issues'])) continue; ?>
                            <tr>
                                <td style="font-weight: bold; width: 200px;"><?php echo esc_html(ucwords(str_replace('_', ' ', $key))); ?></td>
                                <td><?php echo is_array($value) ? '<pre>' . esc_html(print_r($value, true)) . '</pre>' : esc_html($value); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            <?php endforeach; ?>
            
            <div style="background: #f1f1f1; padding: 20px; border-left: 4px solid #0073aa; margin-top: 30px;">
                <h3>📋 Checklist de Soluciones</h3>
                <ul>
                    <li><strong>Gateway deshabilitado:</strong> Ir a WooCommerce → Settings → Payments → Bancard → Habilitar</li>
                    <li><strong>Claves faltantes:</strong> Configurar Public Key y Private Key en settings</li>
                    <li><strong>Moneda diferente a PYG:</strong> Configurar Exchange Rate</li>
                    <li><strong>Carrito vacío:</strong> Agregar productos al carrito</li>
                    <li><strong>Errores de PHP:</strong> Revisar logs de error del servidor</li>
                </ul>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

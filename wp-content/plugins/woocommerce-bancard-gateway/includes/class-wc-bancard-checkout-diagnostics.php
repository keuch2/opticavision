<?php
/**
 * Diagnósticos específicos del checkout para Bancard
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Bancard_Checkout_Diagnostics {
    
    /**
     * Hook para debugging del checkout
     * DESACTIVADO: Interfería con WooCommerce Blocks
     */
    public static function init() {
        // DESACTIVADO - Causaba errores con WooCommerce Blocks
        return;
    }
    
    /**
     * Debug de gateways disponibles
     */
    public static function debug_available_gateways($gateways) {
        error_log('[BANCARD CHECKOUT DEBUG] Total gateways disponibles: ' . count($gateways));
        error_log('[BANCARD CHECKOUT DEBUG] Gateways: ' . implode(', ', array_keys($gateways)));
        
        if (isset($gateways['bancard'])) {
            error_log('[BANCARD CHECKOUT DEBUG] ✅ Bancard ESTÁ en gateways disponibles');
            $bancard = $gateways['bancard'];
            error_log('[BANCARD CHECKOUT DEBUG] Bancard title: ' . $bancard->title);
            error_log('[BANCARD CHECKOUT DEBUG] Bancard enabled: ' . $bancard->enabled);
        } else {
            error_log('[BANCARD CHECKOUT DEBUG] ❌ Bancard NO ESTÁ en gateways disponibles');
            
            // Verificar si existe la clase
            if (class_exists('WC_Gateway_Bancard')) {
                error_log('[BANCARD CHECKOUT DEBUG] Clase WC_Gateway_Bancard existe');
                $bancard_instance = new WC_Gateway_Bancard();
                error_log('[BANCARD CHECKOUT DEBUG] Bancard is_available(): ' . ($bancard_instance->is_available() ? 'true' : 'false'));
            } else {
                error_log('[BANCARD CHECKOUT DEBUG] Clase WC_Gateway_Bancard NO existe');
            }
        }
        
        return $gateways;
    }
    
    /**
     * Debug de inicialización del checkout
     */
    public static function debug_checkout_init() {
        if (!is_checkout()) return;
        
        error_log('[BANCARD CHECKOUT DEBUG] Checkout inicializado');
        
        // Verificar carrito
        if (WC()->cart) {
            error_log('[BANCARD CHECKOUT DEBUG] Cart total: ' . WC()->cart->get_total());
            error_log('[BANCARD CHECKOUT DEBUG] Cart needs payment: ' . (WC()->cart->needs_payment() ? 'yes' : 'no'));
            error_log('[BANCARD CHECKOUT DEBUG] Cart is empty: ' . (WC()->cart->is_empty() ? 'yes' : 'no'));
        }
        
        // Verificar customer
        if (WC()->customer) {
            error_log('[BANCARD CHECKOUT DEBUG] Customer country: ' . WC()->customer->get_billing_country());
            error_log('[BANCARD CHECKOUT DEBUG] Customer currency: ' . get_woocommerce_currency());
        }
        
        // Verificar todos los gateways registrados
        if (WC()->payment_gateways()) {
            $all_gateways = WC()->payment_gateways()->payment_gateways();
            error_log('[BANCARD CHECKOUT DEBUG] Todos los gateways registrados: ' . implode(', ', array_keys($all_gateways)));
        }
    }
    
    /**
     * Información de debug en el footer (solo si está en checkout)
     */
    public static function checkout_debug_info() {
        if (!is_checkout()) return;
        
        ?>
        <script>
        console.log('🔍 BANCARD CHECKOUT DEBUG');
        
        // Verificar si los gateways están siendo cargados
        jQuery(document).ready(function($) {
            console.log('Checkout jQuery ready');
            
            // Verificar formulario de pago
            var paymentMethods = $('.wc_payment_methods .wc_payment_method');
            console.log('Payment methods found:', paymentMethods.length);
            
            paymentMethods.each(function() {
                var method = $(this).find('input[type="radio"]').val();
                var label = $(this).find('label').text().trim();
                console.log('Payment method:', method, 'Label:', label);
            });
            
            // Verificar si existe Bancard específicamente
            var bancardMethod = $('.wc_payment_methods #payment_method_bancard');
            if (bancardMethod.length > 0) {
                console.log('✅ Bancard method found in DOM');
            } else {
                console.log('❌ Bancard method NOT found in DOM');
            }
            
            // Verificar errores de WooCommerce
            var wcErrors = $('.woocommerce-error, .woocommerce-message');
            if (wcErrors.length > 0) {
                console.log('WooCommerce notices:', wcErrors.text());
            }
        });
        </script>
        <?php
    }
    
    /**
     * Generar reporte completo del estado del checkout
     */
    public static function generate_checkout_report() {
        ob_start();
        
        echo "<h2>🔍 Diagnóstico del Checkout - Bancard</h2>";
        
        // Verificar si estamos en checkout
        if (!is_checkout()) {
            echo "<p><strong>⚠️ No estás en la página de checkout.</strong></p>";
            echo "<p><a href='" . wc_get_checkout_url() . "'>Ir al checkout</a></p>";
            return ob_get_clean();
        }
        
        // Información del carrito
        if (WC()->cart) {
            echo "<h3>🛒 Información del Carrito</h3>";
            echo "<ul>";
            echo "<li><strong>Total:</strong> " . WC()->cart->get_total() . "</li>";
            echo "<li><strong>Count:</strong> " . WC()->cart->get_cart_contents_count() . "</li>";
            echo "<li><strong>Needs payment:</strong> " . (WC()->cart->needs_payment() ? 'Yes' : 'No') . "</li>";
            echo "<li><strong>Is empty:</strong> " . (WC()->cart->is_empty() ? 'Yes' : 'No') . "</li>";
            echo "</ul>";
        }
        
        // Gateways disponibles
        $available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
        echo "<h3>💳 Gateways Disponibles (" . count($available_gateways) . ")</h3>";
        
        if (empty($available_gateways)) {
            echo "<p style='color: red;'><strong>❌ No hay gateways disponibles</strong></p>";
        } else {
            echo "<ul>";
            foreach ($available_gateways as $gateway_id => $gateway) {
                $is_bancard = $gateway_id === 'bancard' ? ' <strong>(ESTE ES BANCARD)</strong>' : '';
                echo "<li><strong>{$gateway_id}:</strong> {$gateway->title}{$is_bancard}</li>";
            }
            echo "</ul>";
        }
        
        // Verificar Bancard específicamente
        echo "<h3>🏦 Estado Específico de Bancard</h3>";
        if (class_exists('WC_Gateway_Bancard')) {
            $bancard = new WC_Gateway_Bancard();
            echo "<ul>";
            echo "<li><strong>Enabled:</strong> " . $bancard->enabled . "</li>";
            echo "<li><strong>Title:</strong> " . $bancard->title . "</li>";
            echo "<li><strong>is_available():</strong> " . ($bancard->is_available() ? 'TRUE' : 'FALSE') . "</li>";
            echo "</ul>";
            
            if (isset($available_gateways['bancard'])) {
                echo "<p style='color: green;'>✅ <strong>Bancard está en la lista de gateways disponibles</strong></p>";
            } else {
                echo "<p style='color: red;'>❌ <strong>Bancard NO está en la lista de gateways disponibles</strong></p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Clase WC_Gateway_Bancard no existe</p>";
        }
        
        // Información del sistema
        echo "<h3>⚙️ Información del Sistema</h3>";
        echo "<ul>";
        echo "<li><strong>WordPress:</strong> " . get_bloginfo('version') . "</li>";
        echo "<li><strong>WooCommerce:</strong> " . (defined('WC_VERSION') ? WC_VERSION : 'No instalado') . "</li>";
        echo "<li><strong>Theme:</strong> " . wp_get_theme()->get('Name') . "</li>";
        echo "<li><strong>Currency:</strong> " . get_woocommerce_currency() . "</li>";
        echo "<li><strong>Country:</strong> " . (WC()->customer ? WC()->customer->get_billing_country() : 'N/A') . "</li>";
        echo "</ul>";
        
        return ob_get_clean();
    }
}

// Inicializar
WC_Bancard_Checkout_Diagnostics::init();

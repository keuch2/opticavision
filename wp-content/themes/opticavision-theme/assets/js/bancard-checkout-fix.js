/**
 * Solución de emergencia para mostrar Bancard en el checkout
 * Este script fuerza la aparición de los métodos de pago cuando no aparecen naturalmente
 */

(function($) {
    'use strict';
    
    console.log('🔧 BANCARD CHECKOUT FIX: Iniciando solución de emergencia');
    
    function forceBancardDisplay() {
        console.log('🔧 BANCARD CHECKOUT FIX: Verificando métodos de pago...');
        
        // Verificar si ya existen métodos de pago
        var existingMethods = $('.wc_payment_methods .wc_payment_method');
        console.log('🔧 BANCARD CHECKOUT FIX: Métodos existentes:', existingMethods.length);
        
        if (existingMethods.length === 0) {
            console.log('🔧 BANCARD CHECKOUT FIX: No hay métodos de pago, inyectando Bancard...');
            
            // Buscar cualquier contenedor válido donde inyectar Bancard
            var targetContainer = null;
            
            // Intentar múltiples selectores en orden de preferencia
            var selectors = [
                '#order_review',
                '.woocommerce-checkout-review-order', 
                '#order_review_heading',
                '.checkout-page-wrapper .woocommerce-checkout',
                '.woocommerce-checkout',
                '.checkout',
                'form.checkout',
                'body.woocommerce-checkout'
            ];
            
            for (var i = 0; i < selectors.length; i++) {
                var container = $(selectors[i]);
                if (container.length > 0) {
                    targetContainer = container;
                    console.log('🔧 BANCARD CHECKOUT FIX: Contenedor encontrado:', selectors[i]);
                    break;
                }
            }
            
            if (targetContainer && targetContainer.length > 0) {
                // Crear la sección de métodos de pago
                var paymentHTML = `
                    <div id="payment" class="woocommerce-checkout-payment bancard-emergency-fix">
                        <h3 id="payment_heading" style="font-size: 22px; font-weight: 600; color: #333; margin: 20px 0;">Métodos de Pago</h3>
                        <ul class="wc_payment_methods payment_methods methods" style="list-style: none; padding: 0; margin: 0;">
                            <li class="wc_payment_method payment_method_bancard" style="background: #fff; border: 2px solid #e53e3e; border-radius: 8px; margin-bottom: 15px;">
                                <input id="payment_method_bancard" type="radio" class="input-radio" name="payment_method" value="bancard" checked="checked" style="margin: 0 10px 0 15px;" />
                                <label for="payment_method_bancard" style="display: block; padding: 15px 20px 15px 0; cursor: pointer; font-weight: 600; color: #333; background: #e53e3e; color: white; margin: 0; border-radius: 6px;">
                                    Bancard
                                </label>
                            </li>
                        </ul>
                        <div class="form-row place-order">
                            <button type="submit" class="button alt" name="woocommerce_checkout_place_order" id="place_order" value="Finalizar Compra" style="width: 100%; background: #e53e3e; color: white; border: none; padding: 15px 30px; font-size: 16px; font-weight: 600; border-radius: 8px; cursor: pointer; margin-top: 20px;">
                                Finalizar Compra
                            </button>
                            <input type="hidden" name="woocommerce-process-checkout-nonce" value="${$('[name="woocommerce-process-checkout-nonce"]').val() || ''}" />
                            <input type="hidden" name="_wp_http_referer" value="${$('[name="_wp_http_referer"]').val() || ''}" />
                        </div>
                    </div>
                `;
                
                // Inyectar después del order review
                orderReview.after(paymentHTML);
                
                console.log('✅ BANCARD CHECKOUT FIX: Bancard inyectado exitosamente');
                
                // Actualizar nuestro contador de debug
                setTimeout(function() {
                    var newMethods = $('.wc_payment_methods .wc_payment_method');
                    console.log('🔧 BANCARD CHECKOUT FIX: Payment methods found:', newMethods.length);
                    if (newMethods.length > 0) {
                        console.log('✅ BANCARD CHECKOUT FIX: Bancard method found in DOM');
                    }
                }, 100);
            } else {
                console.log('❌ BANCARD CHECKOUT FIX: No se encontró contenedor order_review');
            }
        } else {
            console.log('✅ BANCARD CHECKOUT FIX: Ya existen métodos de pago, no es necesario inyectar');
        }
    }
    
    // Ejecutar la función cuando el DOM esté listo
    $(document).ready(function() {
        console.log('🔧 BANCARD CHECKOUT FIX: DOM ready');
        
        // Verificar si estamos en el checkout
        if ($('body').hasClass('woocommerce-checkout')) {
            console.log('🔧 BANCARD CHECKOUT FIX: Estamos en el checkout');
            
            // Intentar múltiples veces para asegurar que funcione
            setTimeout(forceBancardDisplay, 500);
            setTimeout(forceBancardDisplay, 1000);
            setTimeout(forceBancardDisplay, 2000);
            
            // También escuchar cambios en el DOM por si WooCommerce actualiza dinámicamente
            if (window.MutationObserver) {
                var observer = new MutationObserver(function(mutations) {
                    var shouldCheck = false;
                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'childList') {
                            // Solo verificar si hay cambios significativos
                            if (mutation.target.classList && (
                                mutation.target.classList.contains('woocommerce-checkout-review-order') ||
                                mutation.target.id === 'order_review'
                            )) {
                                shouldCheck = true;
                            }
                        }
                    });
                    
                    if (shouldCheck) {
                        setTimeout(forceBancardDisplay, 100);
                    }
                });
                
                observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });
                
                console.log('🔧 BANCARD CHECKOUT FIX: Observer configurado');
            }
        } else {
            console.log('🔧 BANCARD CHECKOUT FIX: No estamos en el checkout, saltando');
        }
    });
    
})(jQuery);

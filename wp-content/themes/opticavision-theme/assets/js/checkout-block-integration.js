/**
 * OpticaVision Checkout Block Integration - Frontend
 * 
 * Agrega campos personalizados al Checkout Block de WooCommerce
 */

(function() {
    'use strict';
    
    // Esperar a que WooCommerce Blocks esté listo
    const initOpticaVisionCheckout = () => {
        if (!window.wc || !window.wc.blocksCheckout || !window.wp || !window.wp.plugins) {
            console.log('Esperando WooCommerce Blocks...');
            setTimeout(initOpticaVisionCheckout, 100);
            return;
        }
        
        console.log('WooCommerce Blocks disponible, iniciando integración...');

        const { registerCheckoutFilters } = window.wc.blocksCheckout;
        const { getSetting } = window.wc.wcSettings;
        const { __ } = window.wp.i18n;
        const { registerPlugin } = window.wp.plugins;
        const { createElement, useState } = window.wp.element;
        const { ExperimentalOrderMeta } = window.wc.blocksCheckout;

        // Obtener configuración del tema
        const opticavisionData = getSetting('opticavision-checkout-fields_data', {});

        /**
         * 1. Modificar campos del checkout
         */
        try {
            registerCheckoutFilters('opticavision-field-modifications', {
                additionalFields: (fields) => {
                    if (fields.billing_city) {
                        fields.billing_city.label = 'Ciudad';
                    }
                    if (fields.shipping_city) {
                        fields.shipping_city.label = 'Ciudad';
                    }
                    return fields;
                },
            });
        } catch (error) {
            console.error('Error registrando filtros:', error);
        }

        /**
         * 2. Ocultar código postal y arreglar labels con CSS
         */
        const style = document.createElement('style');
        style.textContent = `
            /* Ocultar código postal */
            .wc-block-components-address-form__postcode,
            .wc-block-components-text-input.wc-block-components-address-form__postcode {
                display: none !important;
            }
            
            /* Estilos para el campo Cédula/RUC */
            .wc-block-components-address-form__cedula_ruc {
                position: relative;
            }
            
            .wc-block-components-address-form__cedula_ruc .wc-block-components-text-input__label {
                position: absolute;
                top: 50%;
                left: 12px;
                transform: translateY(-50%);
                transition: all 0.2s ease;
                pointer-events: none;
                background: white;
                padding: 0 4px;
                color: #757575;
            }
            
            /* Label activo (cuando hay texto o el campo está enfocado) */
            .wc-block-components-address-form__cedula_ruc.is-active .wc-block-components-text-input__label,
            .wc-block-components-address-form__cedula_ruc .wc-block-components-text-input__input:focus + .wc-block-components-text-input__label,
            .wc-block-components-address-form__cedula_ruc .wc-block-components-text-input__input:not(:placeholder-shown) + .wc-block-components-text-input__label {
                top: 0;
                transform: translateY(-50%);
                font-size: 12px;
                color: #1e1e1e;
            }
        `;
        document.head.appendChild(style);
        
        // Limpiar el label del teléfono con JavaScript
        setTimeout(() => {
            const phoneLabel = document.querySelector('label[for*="phone"]');
            if (phoneLabel && phoneLabel.textContent.includes('opcional')) {
                phoneLabel.childNodes.forEach(node => {
                    if (node.nodeType === Node.TEXT_NODE && node.textContent.includes('opcional')) {
                        node.textContent = node.textContent.replace(/\s*\(opcional\)/gi, '');
                    }
                });
                console.log('✓ Label del teléfono limpiado');
            }
        }, 500);

        /**
         * 3. Campo Cédula/RUC con estilos nativos de WooCommerce Blocks
         */
        const CedulaRucField = () => {
            const [cedulaRuc, setCedulaRuc] = useState('');
            const [error, setError] = useState('');
            const [touched, setTouched] = useState(false);

            const validateCedulaRuc = (value) => {
                if (!value || value.trim() === '') {
                    return 'El Número de Cédula o RUC es obligatorio.';
                }
                const cleaned = value.replace(/[-\s]/g, '');
                if (cleaned.length < 6 || cleaned.length > 12) {
                    return 'El Número de Cédula o RUC debe contener entre 6 y 12 dígitos.';
                }
                if (!/^\d+$/.test(cleaned)) {
                    return 'El Número de Cédula o RUC solo debe contener números.';
                }
                return '';
            };

            const handleChange = (event) => {
                const value = event.target.value;
                
                // Actualizar el estado inmediatamente
                setCedulaRuc(value);
                
                // Agregar/quitar clase is-active según si hay texto
                const wrapper = event.target.closest('.wc-block-components-address-form__cedula_ruc');
                if (wrapper) {
                    if (value) {
                        wrapper.classList.add('is-active');
                    } else {
                        wrapper.classList.remove('is-active');
                    }
                }
                
                // Validar solo si el campo ya fue tocado
                if (touched) {
                    const validationError = validateCedulaRuc(value);
                    setError(validationError);
                }
                
                // Guardar en input hidden para que se envíe con el formulario
                const hiddenInput = document.getElementById('billing_cedula_ruc_hidden');
                if (hiddenInput) {
                    hiddenInput.value = value;
                }
            };

            const handleBlur = () => {
                setTouched(true);
                const validationError = validateCedulaRuc(cedulaRuc);
                setError(validationError);
            };
            
            const handleFocus = (e) => {
                e.target.parentElement.classList.add('is-active');
            };

            const hasError = touched && error;

            return createElement(
                'div',
                { 
                    className: `wc-block-components-text-input wc-block-components-address-form__cedula_ruc ${hasError ? 'has-error' : ''} ${cedulaRuc ? 'is-active' : ''}`,
                    id: 'billing-cedula_ruc-wrapper'
                },
                createElement('input', {
                    type: 'text',
                    id: 'billing_cedula_ruc',
                    className: 'wc-block-components-text-input__input',
                    value: cedulaRuc,
                    onChange: handleChange,
                    onBlur: handleBlur,
                    onFocus: handleFocus,
                    autoComplete: 'off',
                    'aria-required': 'true',
                    'aria-invalid': hasError ? 'true' : 'false',
                    'aria-describedby': hasError ? 'billing_cedula_ruc-error' : undefined,
                    placeholder: ' '
                }),
                createElement(
                    'label',
                    { 
                        htmlFor: 'billing_cedula_ruc',
                        className: 'wc-block-components-text-input__label'
                    },
                    'Número de Cédula o RUC',
                    createElement('span', { 
                        className: 'wc-block-components-text-input__required',
                        'aria-hidden': 'true'
                    }, '*')
                ),
                createElement('input', {
                    type: 'hidden',
                    id: 'billing_cedula_ruc_hidden',
                    name: 'billing_cedula_ruc',
                    value: cedulaRuc
                }),
                hasError && createElement(
                    'div',
                    { 
                        id: 'billing_cedula_ruc-error',
                        className: 'wc-block-components-validation-error',
                        role: 'alert'
                    },
                    createElement('p', null, error)
                )
            );
        };

        // Solo registrar el campo si estamos en la página de checkout (no en carrito)
        const isCheckoutPage = window.location.pathname.includes('/checkout');
        
        if (isCheckoutPage) {
            // Registrar el campo en el checkout SIN validación que bloquee el submit
            registerPlugin('opticavision-checkout-fields', {
                render: () => {
                    return createElement(
                        ExperimentalOrderMeta,
                        null,
                        createElement(CedulaRucField)
                    );
                },
                scope: 'woocommerce-checkout',
            });
            
            // Mover el campo después del teléfono cuando el DOM esté listo
            const moveField = () => {
                const cedulaField = document.querySelector('.wc-block-components-address-form__cedula_ruc');
                const phoneField = document.querySelector('[id*="phone"]')?.closest('.wc-block-components-text-input');
                
                if (cedulaField && phoneField && !cedulaField.dataset.moved) {
                    phoneField.parentNode.insertBefore(cedulaField, phoneField.nextSibling);
                    cedulaField.dataset.moved = 'true';
                    console.log('✓ Campo Cédula/RUC movido después del teléfono');
                } else if (!cedulaField || !phoneField) {
                    // Reintentar si los elementos aún no están en el DOM
                    setTimeout(moveField, 100);
                }
            };
            
            // Ejecutar inmediatamente y después de un pequeño delay
            setTimeout(moveField, 50);
        }
        
        // Actualizar el placeholder del teléfono para quitar "(opcional)"
        setTimeout(() => {
            const phoneInput = document.querySelector('input[id*="phone"]');
            if (phoneInput && phoneInput.placeholder.includes('opcional')) {
                phoneInput.placeholder = 'Teléfono';
                console.log('✓ Placeholder del teléfono actualizado');
            }
        }, 500);

        console.log('OpticaVision Checkout Block Integration loaded successfully');
    };

    // Iniciar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initOpticaVisionCheckout);
    } else {
        initOpticaVisionCheckout();
    }
})();

/**
 * OpticaVision reCAPTCHA v3 Frontend Script
 *
 * @package OpticaVision_Recaptcha
 */

(function($) {
    'use strict';
    
    // Wait for DOM ready
    $(document).ready(function() {
        
        // Check if reCAPTCHA is loaded
        if (typeof grecaptcha === 'undefined' || typeof opticavisionRecaptcha === 'undefined') {
            console.error('OpticaVision reCAPTCHA: Google reCAPTCHA not loaded');
            return;
        }
        
        const siteKey = opticavisionRecaptcha.siteKey;
        const forms = opticavisionRecaptcha.forms;
        
        /**
         * Execute reCAPTCHA for a form
         */
        function executeRecaptcha(form, action) {
            return new Promise(function(resolve, reject) {
                grecaptcha.ready(function() {
                    grecaptcha.execute(siteKey, { action: action }).then(function(token) {
                        // Add or update token field
                        let tokenField = form.find('input[name="g-recaptcha-response"]');
                        if (tokenField.length === 0) {
                            tokenField = $('<input>')
                                .attr('type', 'hidden')
                                .attr('name', 'g-recaptcha-response');
                            form.append(tokenField);
                        }
                        tokenField.val(token);
                        resolve(token);
                    }).catch(function(error) {
                        console.error('reCAPTCHA execution error:', error);
                        reject(error);
                    });
                });
            });
        }
        
        /**
         * Login form
         */
        if (forms.login === '1') {
            $('#loginform, form[name="loginform"]').on('submit', function(e) {
                const form = $(this);
                
                // Check if token already exists (prevent multiple executions)
                if (form.find('input[name="g-recaptcha-response"]').val()) {
                    return true;
                }
                
                e.preventDefault();
                
                executeRecaptcha(form, 'login').then(function() {
                    form.off('submit').submit();
                }).catch(function() {
                    alert('Error de seguridad. Por favor, recarga la página e intenta nuevamente.');
                });
                
                return false;
            });
        }
        
        /**
         * Registration form
         */
        if (forms.register === '1') {
            $('#registerform, form[name="registerform"], .woocommerce-form-register').on('submit', function(e) {
                const form = $(this);
                
                if (form.find('input[name="g-recaptcha-response"]').val()) {
                    return true;
                }
                
                e.preventDefault();
                
                executeRecaptcha(form, 'register').then(function() {
                    form.off('submit').submit();
                }).catch(function() {
                    alert('Error de seguridad. Por favor, recarga la página e intenta nuevamente.');
                });
                
                return false;
            });
        }
        
        /**
         * Comment form
         */
        if (forms.comment === '1') {
            $('#commentform').on('submit', function(e) {
                const form = $(this);
                
                if (form.find('input[name="g-recaptcha-response"]').val()) {
                    return true;
                }
                
                e.preventDefault();
                
                executeRecaptcha(form, 'comment').then(function() {
                    form.off('submit').submit();
                }).catch(function() {
                    alert('Error de seguridad. Por favor, recarga la página e intenta nuevamente.');
                });
                
                return false;
            });
        }
        
        /**
         * WooCommerce checkout
         */
        if (forms.wc_checkout === '1') {
            $(document.body).on('checkout_place_order', function() {
                const form = $('form.checkout');
                
                if (form.find('input[name="g-recaptcha-response"]').val()) {
                    return true;
                }
                
                executeRecaptcha(form, 'checkout').then(function() {
                    // Trigger checkout again
                    form.submit();
                });
                
                return false;
            });
        }
        
        /**
         * Contact Form 7
         */
        if (forms.contact === '1') {
            $('.wpcf7-form').each(function() {
                const form = $(this);
                
                form.on('submit', function(e) {
                    if (form.find('input[name="g-recaptcha-response"]').val()) {
                        return true;
                    }
                    
                    e.preventDefault();
                    
                    executeRecaptcha(form, 'contact').then(function() {
                        form.off('submit').submit();
                    }).catch(function() {
                        alert('Error de seguridad. Por favor, recarga la página e intenta nuevamente.');
                    });
                    
                    return false;
                });
            });
        }
        
        /**
         * Generic forms with data attribute
         */
        $('[data-recaptcha="true"]').on('submit', function(e) {
            const form = $(this);
            const action = form.data('recaptcha-action') || 'submit';
            
            if (form.find('input[name="g-recaptcha-response"]').val()) {
                return true;
            }
            
            e.preventDefault();
            
            executeRecaptcha(form, action).then(function() {
                form.off('submit').submit();
            }).catch(function() {
                alert('Error de seguridad. Por favor, recarga la página e intenta nuevamente.');
            });
            
            return false;
        });
        
        // Log successful initialization
        console.log('OpticaVision reCAPTCHA v3 initialized');
    });
    
})(jQuery);

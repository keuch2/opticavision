/**
 * OpticaVision Contact Form JavaScript
 * 
 * Handles form validation and AJAX submission for the contact page
 * 
 * @package OpticaVision_Theme
 * @since 1.0.0
 */

jQuery(document).ready(function($) {
    'use strict';

    // Contact form elements
    const $form = $('.contacto-form');
    const $submitButton = $('.btn-submit');
    const $messageContainer = $('.form-message');

    // Form validation patterns
    const patterns = {
        email: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
        phone: /^[\d\s\-\+\(\)]+$/,
        name: /^[a-zA-ZñÑáéíóúÁÉÍÓÚüÜ\s]{2,50}$/
    };

    // Initialize form functionality
    initContactForm();

    /**
     * Initialize contact form functionality
     */
    function initContactForm() {
        if ($form.length === 0) return;

        // Bind events
        $form.on('submit', handleFormSubmission);
        $form.find('.form-control').on('blur', validateField);
        $form.find('.form-control').on('input', clearFieldError);

        // Initialize real-time validation
        enableRealTimeValidation();
    }

    /**
     * Handle form submission
     */
    function handleFormSubmission(e) {
        e.preventDefault();

        // Clear previous messages
        clearMessages();

        // Validate form
        if (!validateForm()) {
            showErrorMessage(opticavision_contact.messages.required_fields);
            return;
        }

        // Show loading state
        setLoadingState(true);

        // Prepare form data
        const formData = {
            action: 'opticavision_contact_form',
            nonce: opticavision_contact.nonce,
            nombre: $('#nombre').val().trim(),
            email: $('#email').val().trim(),
            telefono: $('#telefono').val().trim(),
            area: $('#area').val(),
            mensaje: $('#mensaje').val().trim()
        };

        // Submit via AJAX
        $.ajax({
            url: opticavision_contact.ajax_url,
            type: 'POST',
            data: formData,
            timeout: 30000,
            success: function(response) {
                setLoadingState(false);
                
                if (response.success) {
                    showSuccessMessage(response.data.message || opticavision_contact.messages.success);
                    resetForm();
                    
                    // Log success for analytics
                    if (typeof gtag !== 'undefined') {
                        gtag('event', 'form_submit', {
                            event_category: 'Contact',
                            event_label: 'Contact Form'
                        });
                    }
                } else {
                    handleFormErrors(response.data);
                }
            },
            error: function(xhr, status, error) {
                setLoadingState(false);
                console.error('Contact form error:', status, error);
                showErrorMessage(opticavision_contact.messages.error);
            }
        });
    }

    /**
     * Validate entire form
     */
    function validateForm() {
        let isValid = true;
        const $fields = $form.find('.form-control[required]');

        $fields.each(function() {
            if (!validateField.call(this)) {
                isValid = false;
            }
        });

        return isValid;
    }

    /**
     * Validate individual field
     */
    function validateField() {
        const $field = $(this);
        const value = $field.val().trim();
        const fieldType = $field.attr('type') || $field.prop('tagName').toLowerCase();
        const fieldName = $field.attr('name');
        let isValid = true;
        let errorMessage = '';

        // Clear previous error
        clearFieldError.call(this);

        // Required field check
        if ($field.prop('required') && !value) {
            errorMessage = 'Este campo es requerido.';
            isValid = false;
        } else if (value) {
            // Type-specific validation
            switch (fieldName) {
                case 'nombre':
                    if (!patterns.name.test(value)) {
                        errorMessage = 'Por favor ingrese un nombre válido.';
                        isValid = false;
                    } else if (value.length < 2 || value.length > 50) {
                        errorMessage = 'El nombre debe tener entre 2 y 50 caracteres.';
                        isValid = false;
                    }
                    break;

                case 'email':
                    if (!patterns.email.test(value)) {
                        errorMessage = 'Por favor ingrese un email válido.';
                        isValid = false;
                    }
                    break;

                case 'telefono':
                    if (!patterns.phone.test(value)) {
                        errorMessage = 'Por favor ingrese un teléfono válido.';
                        isValid = false;
                    } else if (value.length < 6 || value.length > 20) {
                        errorMessage = 'El teléfono debe tener entre 6 y 20 caracteres.';
                        isValid = false;
                    }
                    break;

                case 'mensaje':
                    if (value.length < 10) {
                        errorMessage = 'El mensaje debe tener al menos 10 caracteres.';
                        isValid = false;
                    } else if (value.length > 1000) {
                        errorMessage = 'El mensaje no puede exceder los 1000 caracteres.';
                        isValid = false;
                    }
                    break;

                case 'area':
                    if (!value) {
                        errorMessage = 'Por favor seleccione un área.';
                        isValid = false;
                    }
                    break;
            }
        }

        // Show error if invalid
        if (!isValid) {
            showFieldError($field, errorMessage);
        }

        return isValid;
    }

    /**
     * Clear field error
     */
    function clearFieldError() {
        const $field = $(this);
        const $formGroup = $field.closest('.form-group');
        
        $field.removeClass('error');
        $formGroup.find('.field-error').remove();
    }

    /**
     * Show field error
     */
    function showFieldError($field, message) {
        const $formGroup = $field.closest('.form-group');
        
        $field.addClass('error');
        
        // Remove existing error message
        $formGroup.find('.field-error').remove();
        
        // Add new error message
        $formGroup.append(`<div class="field-error">${message}</div>`);
    }

    /**
     * Handle form errors from server response
     */
    function handleFormErrors(data) {
        if (data.errors && typeof data.errors === 'object') {
            // Show individual field errors
            Object.keys(data.errors).forEach(function(fieldName) {
                const $field = $form.find(`[name="${fieldName}"]`);
                if ($field.length) {
                    showFieldError($field, data.errors[fieldName]);
                }
            });
        }

        // Show general error message
        showErrorMessage(data.message || opticavision_contact.messages.error);
    }

    /**
     * Show success message
     */
    function showSuccessMessage(message) {
        const successHtml = `
            <div class="form-message form-success">
                <i class="fas fa-check-circle"></i>
                <div>
                    <h3>¡Mensaje enviado exitosamente!</h3>
                    <p>${message}</p>
                </div>
            </div>
        `;

        $form.before(successHtml);
        scrollToMessage();
    }

    /**
     * Show error message
     */
    function showErrorMessage(message) {
        const errorHtml = `
            <div class="form-message form-error">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <h3>Error al enviar el mensaje</h3>
                    <p>${message}</p>
                </div>
            </div>
        `;

        $form.before(errorHtml);
        scrollToMessage();
    }

    /**
     * Clear all messages
     */
    function clearMessages() {
        $('.form-message').remove();
        $('.field-error').remove();
        $form.find('.form-control').removeClass('error');
    }

    /**
     * Scroll to message
     */
    function scrollToMessage() {
        const $message = $('.form-message').last();
        if ($message.length) {
            $('html, body').animate({
                scrollTop: $message.offset().top - 100
            }, 500);
        }
    }

    /**
     * Set loading state
     */
    function setLoadingState(loading) {
        if (loading) {
            $submitButton
                .prop('disabled', true)
                .html('<i class="fas fa-spinner fa-spin"></i> ' + opticavision_contact.messages.sending);
            
            $form.addClass('submitting');
        } else {
            $submitButton
                .prop('disabled', false)
                .html('<i class="fas fa-paper-plane"></i> Enviar Mensaje');
            
            $form.removeClass('submitting');
        }
    }

    /**
     * Reset form
     */
    function resetForm() {
        $form[0].reset();
        clearMessages();
        $form.find('.form-control').removeClass('error');
    }

    /**
     * Enable real-time validation
     */
    function enableRealTimeValidation() {
        // Email field real-time validation
        $('#email').on('input', function() {
            const value = $(this).val().trim();
            if (value && patterns.email.test(value)) {
                $(this).addClass('valid');
            } else {
                $(this).removeClass('valid');
            }
        });

        // Phone field formatting
        $('#telefono').on('input', function() {
            let value = $(this).val().replace(/[^\d\s\-\+\(\)]/g, '');
            $(this).val(value);
        });

        // Message character counter
        const $mensaje = $('#mensaje');
        const $charCounter = $('<div class="char-counter">0/1000</div>');
        $mensaje.after($charCounter);

        $mensaje.on('input', function() {
            const length = $(this).val().length;
            $charCounter.text(`${length}/1000`);
            
            if (length > 1000) {
                $charCounter.addClass('over-limit');
            } else {
                $charCounter.removeClass('over-limit');
            }
        });
    }

    // Add CSS for form enhancements
    const formStyles = `
        <style>
        .form-control.error {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1) !important;
        }
        .form-control.valid {
            border-color: #28a745 !important;
        }
        .field-error {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        .field-error::before {
            content: "⚠";
            font-size: 0.75rem;
        }
        .contacto-form.submitting {
            opacity: 0.7;
            pointer-events: none;
        }
        .char-counter {
            font-size: 0.75rem;
            color: #666;
            text-align: right;
            margin-top: 0.25rem;
        }
        .char-counter.over-limit {
            color: #dc3545;
            font-weight: 600;
        }
        @media (max-width: 768px) {
            .form-message {
                margin: 0 -15px 1.5rem -15px;
            }
        }
        </style>
    `;

    $('head').append(formStyles);
});

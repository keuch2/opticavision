<?php
/**
 * Template Name: Contacto
 * 
 * Página de contacto corporativo con formulario funcional
 * 
 * @package OpticaVision_Theme
 * @since 1.0.0
 */

// Procesar el formulario si se ha enviado
$form_submitted = false;
$form_success = false;
$form_errors = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['opticavision_contact_nonce'])) {
    // Verificar nonce de seguridad
    if (wp_verify_nonce($_POST['opticavision_contact_nonce'], 'opticavision_contact_form')) {
        $form_submitted = true;
        
        // Validar reCAPTCHA v3
        $recaptcha_token = isset($_POST['g-recaptcha-response']) ? sanitize_text_field(wp_unslash($_POST['g-recaptcha-response'])) : '';
        
        if (class_exists('OpticaVision_Recaptcha_Validator')) {
            $recaptcha_result = OpticaVision_Recaptcha_Validator::validate($recaptcha_token, 'contact_form');
            
            if (is_wp_error($recaptcha_result)) {
                $form_errors[] = 'Error de verificación de seguridad. Por favor intente nuevamente.';
                
                // Log del error
                if (function_exists('optica_log_warning')) {
                    optica_log_warning('reCAPTCHA falló en formulario de contacto', array(
                        'error' => $recaptcha_result->get_error_message()
                    ));
                }
            }
        }
        
        // Sanitizar y validar datos
        $nombre = sanitize_text_field($_POST['nombre'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $telefono = sanitize_text_field($_POST['telefono'] ?? '');
        $area = sanitize_text_field($_POST['area'] ?? '');
        $mensaje = sanitize_textarea_field($_POST['mensaje'] ?? '');
        
        // Validaciones
        if (empty($nombre)) {
            $form_errors[] = 'El nombre completo es requerido.';
        }
        
        if (empty($email) || !is_email($email)) {
            $form_errors[] = 'Por favor ingrese un email válido.';
        }
        
        if (empty($telefono)) {
            $form_errors[] = 'El teléfono es requerido.';
        }
        
        if (empty($area)) {
            $form_errors[] = 'Por favor seleccione un área.';
        }
        
        if (empty($mensaje)) {
            $form_errors[] = 'El mensaje es requerido.';
        }
        
        // Si no hay errores, enviar el email
        if (empty($form_errors)) {
            $areas = array(
                'comercial' => 'Comercial',
                'marketing' => 'Marketing',
                'administracion' => 'Administración',
                'corporativo' => 'Corporativo'
            );
            
            $area_nombre = $areas[$area] ?? 'No especificado';
            
            // Configurar el email
            $admin_email = 'atc@opticavision.com.py'; // Email corporativo de atención al cliente
            $site_name = get_bloginfo('name');
            
            $subject = '[' . $site_name . '] Nuevo contacto desde el sitio web - Área: ' . $area_nombre;
            
            $message = "Se ha recibido un nuevo mensaje de contacto desde el sitio web.\n\n";
            $message .= "DATOS DEL CONTACTO:\n";
            $message .= "==================\n";
            $message .= "Nombre: " . $nombre . "\n";
            $message .= "Email: " . $email . "\n";
            $message .= "Teléfono: " . $telefono . "\n";
            $message .= "Área: " . $area_nombre . "\n";
            $message .= "Fecha: " . date('d/m/Y H:i:s') . "\n\n";
            $message .= "MENSAJE:\n";
            $message .= "========\n";
            $message .= $mensaje . "\n\n";
            $message .= "---\n";
            $message .= "Este mensaje fue enviado desde el formulario de contacto de " . home_url();
            
            // Headers del email
            $headers = array(
                'Content-Type: text/plain; charset=UTF-8',
                'From: ' . $site_name . ' <noreply@' . $_SERVER['HTTP_HOST'] . '>',
                'Reply-To: ' . $nombre . ' <' . $email . '>'
            );
            
            // Enviar el email
            $mail_sent = wp_mail($admin_email, $subject, $message, $headers);
            
            if ($mail_sent) {
                $form_success = true;
                
                // Loggear el envío exitoso
                if (function_exists('optica_log_info')) {
                    optica_log_info('Formulario de contacto enviado exitosamente', array(
                        'nombre' => $nombre,
                        'email' => $email,
                        'area' => $area_nombre
                    ));
                }
            } else {
                $form_errors[] = 'Hubo un error al enviar su mensaje. Por favor intente nuevamente o contáctenos directamente.';
                
                // Loggear el error
                if (function_exists('optica_log_error')) {
                    optica_log_error('Error al enviar formulario de contacto', array(
                        'email_destinatario' => $admin_email,
                        'nombre' => $nombre,
                        'email' => $email
                    ));
                }
            }
        }
    } else {
        $form_errors[] = 'Error de seguridad. Por favor recargue la página e intente nuevamente.';
    }
}

get_header(); ?>

<div class="contacto-page">
    <div class="container">
        <!-- Hero Section -->
        <div class="contacto-hero">
            <h1 class="contacto-title">Contacto</h1>
            <p class="contacto-subtitle">
                Estamos aquí para ayudarte. Contáctanos por cualquier consulta sobre nuestros productos y servicios.
            </p>
        </div>

        <div class="contacto-content">
            <!-- Información de Contacto -->
            <div class="contacto-info">
                <h2 class="section-title">Casa Central</h2>
                
                <div class="info-cards">
                    <div class="info-card">
                        <div class="info-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="info-content">
                            <h3>Dirección</h3>
                            <p>Palma 764 c/ Ayolas<br>Asunción, Paraguay</p>
                            <a href="https://maps.google.com/?q=Palma+764+Ayolas+Asuncion+Paraguay" target="_blank" class="info-link">
                                <i class="fas fa-external-link-alt"></i> Ver en Google Maps
                            </a>
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="info-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div class="info-content">
                            <h3>Teléfonos</h3>
                            <p>
                                <a href="tel:021441660">021-441-660 (RA)</a><br>
                                <a href="https://wa.me/595982506314" target="_blank" rel="noopener noreferrer">0982-506 314 (WhatsApp)</a>
                            </p>
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="info-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="info-content">
                            <h3>Horarios de Atención</h3>
                            <p>
                                <strong>Lunes a Viernes:</strong> 07:30 - 18:30<br>
                                <strong>Sábados:</strong> 07:30 - 12:00<br>
                                <strong>Domingos:</strong> Cerrado
                            </p>
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="info-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="info-content">
                            <h3>Email Corporativo</h3>
                            <p>
                                <a href="mailto:atc@opticavision.com.py">atc@opticavision.com.py</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Formulario de Contacto -->
            <div class="contacto-form-section">
                <h2 class="section-title">Envíanos un Mensaje</h2>
                
                <?php if ($form_submitted && $form_success): ?>
                    <div class="form-message form-success">
                        <i class="fas fa-check-circle"></i>
                        <div>
                            <h3>¡Mensaje enviado exitosamente!</h3>
                            <p>Gracias por contactarnos. Hemos recibido su mensaje y nos comunicaremos con usted a la brevedad.</p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($form_submitted && !empty($form_errors)): ?>
                    <div class="form-message form-error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div>
                            <h3>Error al enviar el mensaje</h3>
                            <ul>
                                <?php foreach ($form_errors as $error): ?>
                                    <li><?php echo esc_html($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>

                <form class="contacto-form" method="post" action="" data-recaptcha="true" data-recaptcha-action="contact_form">
                    <?php wp_nonce_field('opticavision_contact_form', 'opticavision_contact_nonce'); ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nombre">Nombre Completo *</label>
                            <input 
                                type="text" 
                                id="nombre" 
                                name="nombre" 
                                value="<?php echo esc_attr($_POST['nombre'] ?? ''); ?>" 
                                required
                                class="form-control"
                            >
                        </div>

                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                value="<?php echo esc_attr($_POST['email'] ?? ''); ?>" 
                                required
                                class="form-control"
                            >
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="telefono">Teléfono *</label>
                            <input 
                                type="tel" 
                                id="telefono" 
                                name="telefono" 
                                value="<?php echo esc_attr($_POST['telefono'] ?? ''); ?>" 
                                required
                                class="form-control"
                                placeholder="Ej: 021-441-660 o 0974-829-865"
                            >
                        </div>

                        <div class="form-group">
                            <label for="area">Área a contactar *</label>
                            <select id="area" name="area" required class="form-control">
                                <option value="">Seleccione un área</option>
                                <option value="comercial" <?php selected($_POST['area'] ?? '', 'comercial'); ?>>Comercial</option>
                                <option value="marketing" <?php selected($_POST['area'] ?? '', 'marketing'); ?>>Marketing</option>
                                <option value="administracion" <?php selected($_POST['area'] ?? '', 'administracion'); ?>>Administración</option>
                                <option value="corporativo" <?php selected($_POST['area'] ?? '', 'corporativo'); ?>>Corporativo</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="mensaje">Mensaje *</label>
                        <textarea 
                            id="mensaje" 
                            name="mensaje" 
                            rows="5" 
                            required
                            class="form-control"
                            placeholder="Por favor describa su consulta o mensaje..."
                        ><?php echo esc_textarea($_POST['mensaje'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-paper-plane"></i>
                            Enviar Mensaje
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Información Adicional -->
        <div class="contacto-additional">
            <div class="additional-info">
                <h3><i class="fas fa-info-circle"></i> ¿Necesita atención inmediata?</h3>
                <p>
                    Para consultas urgentes, puede contactarnos directamente por teléfono durante nuestros horarios de atención. 
                    También puede visitar cualquiera de nuestras <a href="<?php echo home_url('/sucursales'); ?>">sucursales</a> 
                    en Asunción y Gran Asunción.
                </p>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>

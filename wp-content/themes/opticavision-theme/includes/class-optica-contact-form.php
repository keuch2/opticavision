<?php
/**
 * Contact Form Handler Class
 *
 * Handles contact form submission, validation, and email sending
 * for OpticaVision theme
 *
 * @package OpticaVision_Theme
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

/**
 * OpticaVision_Contact_Form class.
 */
class OpticaVision_Contact_Form {

    /**
     * Form areas available for contact
     *
     * @var array
     */
    private $areas = array(
        'comercial' => 'Comercial',
        'marketing' => 'Marketing', 
        'administracion' => 'Administración',
        'corporativo' => 'Corporativo'
    );

    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_opticavision_contact_form', array($this, 'handle_ajax_submission'));
        add_action('wp_ajax_nopriv_opticavision_contact_form', array($this, 'handle_ajax_submission'));
    }

    /**
     * Enqueue contact form scripts and styles
     */
    public function enqueue_scripts() {
        if (is_page_template('page-contacto.php') || is_page('contacto')) {
            wp_enqueue_script(
                'opticavision-contact-form',
                get_template_directory_uri() . '/assets/js/contact-form.js',
                array('jquery'),
                '1.0.0',
                true
            );

            // Localize script for AJAX
            wp_localize_script('opticavision-contact-form', 'opticavision_contact', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('opticavision_contact_ajax'),
                'messages' => array(
                    'sending' => __('Enviando mensaje...', 'opticavision-theme'),
                    'success' => __('¡Mensaje enviado exitosamente!', 'opticavision-theme'),
                    'error' => __('Error al enviar el mensaje. Intente nuevamente.', 'opticavision-theme'),
                    'required_fields' => __('Por favor complete todos los campos requeridos.', 'opticavision-theme'),
                )
            ));
        }
    }

    /**
     * Handle AJAX form submission
     */
    public function handle_ajax_submission() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'opticavision_contact_ajax')) {
            wp_send_json_error(array(
                'message' => __('Error de seguridad. Recargue la página e intente nuevamente.', 'opticavision-theme')
            ));
        }

        // Process form data
        $result = $this->process_form_submission($_POST);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * Process form submission (both AJAX and traditional)
     *
     * @param array $data Form data
     * @return array Result array with success status and messages
     */
    public function process_form_submission($data) {
        $result = array(
            'success' => false,
            'message' => '',
            'errors' => array()
        );

        // Sanitize and validate data
        $form_data = $this->sanitize_form_data($data);
        $validation_errors = $this->validate_form_data($form_data);

        if (!empty($validation_errors)) {
            $result['errors'] = $validation_errors;
            $result['message'] = __('Por favor corrija los errores en el formulario.', 'opticavision-theme');
            return $result;
        }

        // Send email
        $email_sent = $this->send_contact_email($form_data);

        if ($email_sent) {
            $result['success'] = true;
            $result['message'] = __('Su mensaje ha sido enviado exitosamente. Nos comunicaremos con usted a la brevedad.', 'opticavision-theme');
            
            // Log successful submission
            if (function_exists('optica_log_info')) {
                optica_log_info('Formulario de contacto enviado exitosamente', array(
                    'nombre' => $form_data['nombre'],
                    'email' => $form_data['email'],
                    'area' => $this->areas[$form_data['area']] ?? 'No especificado',
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
                ));
            }
        } else {
            $result['message'] = __('Hubo un error al enviar su mensaje. Por favor intente nuevamente o contáctenos directamente.', 'opticavision-theme');
            
            // Log error
            if (function_exists('optica_log_error')) {
                optica_log_error('Error al enviar formulario de contacto', array(
                    'nombre' => $form_data['nombre'],
                    'email' => $form_data['email'],
                    'area' => $this->areas[$form_data['area']] ?? 'No especificado'
                ));
            }
        }

        return $result;
    }

    /**
     * Sanitize form data
     *
     * @param array $data Raw form data
     * @return array Sanitized data
     */
    private function sanitize_form_data($data) {
        return array(
            'nombre' => sanitize_text_field($data['nombre'] ?? ''),
            'email' => sanitize_email($data['email'] ?? ''),
            'telefono' => sanitize_text_field($data['telefono'] ?? ''),
            'area' => sanitize_text_field($data['area'] ?? ''),
            'mensaje' => sanitize_textarea_field($data['mensaje'] ?? '')
        );
    }

    /**
     * Validate form data
     *
     * @param array $data Sanitized form data
     * @return array Validation errors
     */
    private function validate_form_data($data) {
        $errors = array();

        if (empty($data['nombre'])) {
            $errors['nombre'] = __('El nombre completo es requerido.', 'opticavision-theme');
        }

        if (empty($data['email'])) {
            $errors['email'] = __('El email es requerido.', 'opticavision-theme');
        } elseif (!is_email($data['email'])) {
            $errors['email'] = __('Por favor ingrese un email válido.', 'opticavision-theme');
        }

        if (empty($data['telefono'])) {
            $errors['telefono'] = __('El teléfono es requerido.', 'opticavision-theme');
        }

        if (empty($data['area']) || !array_key_exists($data['area'], $this->areas)) {
            $errors['area'] = __('Por favor seleccione un área válida.', 'opticavision-theme');
        }

        if (empty($data['mensaje'])) {
            $errors['mensaje'] = __('El mensaje es requerido.', 'opticavision-theme');
        } elseif (strlen($data['mensaje']) < 10) {
            $errors['mensaje'] = __('El mensaje debe tener al menos 10 caracteres.', 'opticavision-theme');
        }

        return $errors;
    }

    /**
     * Send contact email
     *
     * @param array $data Validated form data
     * @return bool Success status
     */
    private function send_contact_email($data) {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        $area_nombre = $this->areas[$data['area']] ?? 'No especificado';

        // Email subject
        $subject = sprintf(
            __('[%s] Nuevo contacto desde el sitio web - Área: %s', 'opticavision-theme'),
            $site_name,
            $area_nombre
        );

        // Email message
        $message = $this->build_email_message($data, $area_nombre);

        // Email headers
        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            sprintf('From: %s <%s>', $site_name, $this->get_no_reply_email()),
            sprintf('Reply-To: %s <%s>', $data['nombre'], $data['email'])
        );

        // Send main notification email
        $main_email_sent = wp_mail($admin_email, $subject, $message, $headers);

        // Send area-specific email if configured
        $area_email = $this->get_area_email($data['area']);
        if ($area_email && $area_email !== $admin_email) {
            wp_mail($area_email, $subject, $message, $headers);
        }

        // Send confirmation email to user
        $this->send_confirmation_email($data);

        return $main_email_sent;
    }

    /**
     * Build email message content
     *
     * @param array $data Form data
     * @param string $area_nombre Area name
     * @return string Email message
     */
    private function build_email_message($data, $area_nombre) {
        $message = __("Se ha recibido un nuevo mensaje de contacto desde el sitio web.\n\n", 'opticavision-theme');
        $message .= __("DATOS DEL CONTACTO:\n", 'opticavision-theme');
        $message .= "==================\n";
        $message .= sprintf(__("Nombre: %s\n", 'opticavision-theme'), $data['nombre']);
        $message .= sprintf(__("Email: %s\n", 'opticavision-theme'), $data['email']);
        $message .= sprintf(__("Teléfono: %s\n", 'opticavision-theme'), $data['telefono']);
        $message .= sprintf(__("Área: %s\n", 'opticavision-theme'), $area_nombre);
        $message .= sprintf(__("Fecha: %s\n\n", 'opticavision-theme'), date_i18n('d/m/Y H:i:s'));
        $message .= __("MENSAJE:\n", 'opticavision-theme');
        $message .= "========\n";
        $message .= $data['mensaje'] . "\n\n";
        $message .= "---\n";
        $message .= sprintf(
            __("Este mensaje fue enviado desde el formulario de contacto de %s", 'opticavision-theme'),
            home_url()
        );

        return $message;
    }

    /**
     * Send confirmation email to user
     *
     * @param array $data Form data
     */
    private function send_confirmation_email($data) {
        $site_name = get_bloginfo('name');
        $subject = sprintf(__('[%s] Hemos recibido su mensaje', 'opticavision-theme'), $site_name);

        $message = sprintf(__("Estimado/a %s,\n\n", 'opticavision-theme'), $data['nombre']);
        $message .= __("Gracias por contactarnos. Hemos recibido su mensaje y nos comunicaremos con usted a la brevedad.\n\n", 'opticavision-theme');
        $message .= __("RESUMEN DE SU CONSULTA:\n", 'opticavision-theme');
        $message .= sprintf(__("Área: %s\n", 'opticavision-theme'), $this->areas[$data['area']] ?? 'No especificado');
        $message .= sprintf(__("Mensaje: %s\n\n", 'opticavision-theme'), $data['mensaje']);
        $message .= __("Atentamente,\n", 'opticavision-theme');
        $message .= sprintf(__("Equipo de %s\n", 'opticavision-theme'), $site_name);
        $message .= __("Palma 764 c/ Ayolas, Asunción\n", 'opticavision-theme');
        $message .= __("Tel: 021-441-660 | WhatsApp: 0982-506 314\n", 'opticavision-theme');

        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            sprintf('From: %s <%s>', $site_name, $this->get_no_reply_email())
        );

        wp_mail($data['email'], $subject, $message, $headers);
    }

    /**
     * Get no-reply email address
     *
     * @return string No-reply email
     */
    private function get_no_reply_email() {
        $domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return 'noreply@' . $domain;
    }

    /**
     * Get area-specific email address
     *
     * @param string $area Area key
     * @return string|null Area email or null
     */
    private function get_area_email($area) {
        $area_emails = array(
            'comercial' => get_option('opticavision_comercial_email', ''),
            'marketing' => get_option('opticavision_marketing_email', ''),
            'administracion' => get_option('opticavision_administracion_email', ''),
            'corporativo' => get_option('opticavision_corporativo_email', '')
        );

        return !empty($area_emails[$area]) ? $area_emails[$area] : null;
    }

    /**
     * Get available contact areas
     *
     * @return array Areas array
     */
    public function get_areas() {
        return $this->areas;
    }
}

// Initialize the contact form handler
new OpticaVision_Contact_Form();

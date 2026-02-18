<?php
/**
 * Frontend functionality for OpticaVision reCAPTCHA
 *
 * @package OpticaVision_Recaptcha
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class OpticaVision_Recaptcha_Frontend {
    
    /**
     * Forms configuration
     */
    private $forms = array();
    
    /**
     * Site key
     */
    private $site_key = '';
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->site_key = get_option('opticavision_recaptcha_site_key', '');
        $this->forms = get_option('opticavision_recaptcha_forms', array());
        
        // Enqueue scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Login form
        if ($this->is_form_enabled('login')) {
            add_action('login_enqueue_scripts', array($this, 'enqueue_scripts'));
            add_filter('authenticate', array($this, 'verify_login'), 30, 3);
        }
        
        // Registration form
        if ($this->is_form_enabled('register')) {
            add_filter('registration_errors', array($this, 'verify_register'), 10, 3);
        }
        
        // Comment form
        if ($this->is_form_enabled('comment')) {
            add_filter('preprocess_comment', array($this, 'verify_comment'));
        }
        
        // WooCommerce checkout
        if ($this->is_form_enabled('wc_checkout') && class_exists('WooCommerce')) {
            add_action('woocommerce_after_checkout_billing_form', array($this, 'add_recaptcha_field'));
            add_action('woocommerce_checkout_process', array($this, 'verify_checkout'));
        }
        
        // WooCommerce registration
        if ($this->is_form_enabled('wc_register') && class_exists('WooCommerce')) {
            add_action('woocommerce_register_form', array($this, 'add_recaptcha_field'));
            add_filter('woocommerce_registration_errors', array($this, 'verify_wc_register'), 10, 3);
        }
        
        // Contact Form 7
        if ($this->is_form_enabled('contact') && class_exists('WPCF7')) {
            add_filter('wpcf7_spam', array($this, 'verify_cf7'), 10, 2);
        }
    }
    
    /**
     * Check if form is enabled
     */
    private function is_form_enabled($form_key) {
        return isset($this->forms[$form_key]) && $this->forms[$form_key] === '1';
    }
    
    /**
     * Enqueue scripts
     */
    public function enqueue_scripts() {
        if (empty($this->site_key)) {
            return;
        }
        
        // Enqueue Google reCAPTCHA API
        wp_enqueue_script(
            'google-recaptcha',
            'https://www.google.com/recaptcha/api.js?render=' . $this->site_key,
            array(),
            null,
            true
        );
        
        // Enqueue our script
        wp_enqueue_script(
            'opticavision-recaptcha',
            OPTICAVISION_RECAPTCHA_PLUGIN_URL . 'assets/js/recaptcha.js',
            array('google-recaptcha'),
            OPTICAVISION_RECAPTCHA_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('opticavision-recaptcha', 'opticavisionRecaptcha', array(
            'siteKey' => $this->site_key,
            'forms' => $this->forms
        ));
    }
    
    /**
     * Add reCAPTCHA field
     */
    public function add_recaptcha_field() {
        echo '<div class="opticavision-recaptcha-field" data-form="wc_form"></div>';
    }
    
    /**
     * Verify login
     */
    public function verify_login($user, $username, $password) {
        // Skip for empty credentials
        if (empty($username) || empty($password)) {
            return $user;
        }
        
        // Skip if already an error
        if (is_wp_error($user)) {
            return $user;
        }
        
        $token = isset($_POST['g-recaptcha-response']) ? sanitize_text_field(wp_unslash($_POST['g-recaptcha-response'])) : '';
        $result = OpticaVision_Recaptcha_Validator::validate($token, 'login');
        
        if (is_wp_error($result)) {
            return new WP_Error('recaptcha_error', __('<strong>ERROR</strong>: Verificación de seguridad fallida. Por favor, intenta nuevamente.', 'opticavision-recaptcha'));
        }
        
        return $user;
    }
    
    /**
     * Verify registration
     */
    public function verify_register($errors, $sanitized_user_login, $user_email) {
        $token = isset($_POST['g-recaptcha-response']) ? sanitize_text_field(wp_unslash($_POST['g-recaptcha-response'])) : '';
        $result = OpticaVision_Recaptcha_Validator::validate($token, 'register');
        
        if (is_wp_error($result)) {
            $errors->add('recaptcha_error', __('<strong>ERROR</strong>: Verificación de seguridad fallida. Por favor, intenta nuevamente.', 'opticavision-recaptcha'));
        }
        
        return $errors;
    }
    
    /**
     * Verify comment
     */
    public function verify_comment($commentdata) {
        // Skip for logged in users with sufficient capabilities
        if (is_user_logged_in() && current_user_can('moderate_comments')) {
            return $commentdata;
        }
        
        $token = isset($_POST['g-recaptcha-response']) ? sanitize_text_field(wp_unslash($_POST['g-recaptcha-response'])) : '';
        $result = OpticaVision_Recaptcha_Validator::validate($token, 'comment');
        
        if (is_wp_error($result)) {
            wp_die(
                esc_html__('Verificación de seguridad fallida. Por favor, vuelve atrás e intenta nuevamente.', 'opticavision-recaptcha'),
                esc_html__('Error de Comentario', 'opticavision-recaptcha'),
                array('back_link' => true)
            );
        }
        
        return $commentdata;
    }
    
    /**
     * Verify WooCommerce checkout
     */
    public function verify_checkout() {
        $token = isset($_POST['g-recaptcha-response']) ? sanitize_text_field(wp_unslash($_POST['g-recaptcha-response'])) : '';
        $result = OpticaVision_Recaptcha_Validator::validate($token, 'checkout');
        
        if (is_wp_error($result)) {
            wc_add_notice(__('Verificación de seguridad fallida. Por favor, intenta nuevamente.', 'opticavision-recaptcha'), 'error');
        }
    }
    
    /**
     * Verify WooCommerce registration
     */
    public function verify_wc_register($errors, $username, $email) {
        $token = isset($_POST['g-recaptcha-response']) ? sanitize_text_field(wp_unslash($_POST['g-recaptcha-response'])) : '';
        $result = OpticaVision_Recaptcha_Validator::validate($token, 'register');
        
        if (is_wp_error($result)) {
            $errors->add('recaptcha_error', __('Verificación de seguridad fallida. Por favor, intenta nuevamente.', 'opticavision-recaptcha'));
        }
        
        return $errors;
    }
    
    /**
     * Verify Contact Form 7
     */
    public function verify_cf7($spam, $submission) {
        if ($spam) {
            return $spam;
        }
        
        $data = $submission->get_posted_data();
        $token = isset($data['g-recaptcha-response']) ? sanitize_text_field($data['g-recaptcha-response']) : '';
        $result = OpticaVision_Recaptcha_Validator::validate($token, 'contact');
        
        if (is_wp_error($result)) {
            $spam = true;
        }
        
        return $spam;
    }
}

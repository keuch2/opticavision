<?php
/**
 * reCAPTCHA Validator for OpticaVision
 *
 * @package OpticaVision_Recaptcha
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class OpticaVision_Recaptcha_Validator {
    
    /**
     * Google reCAPTCHA API endpoint
     */
    const API_URL = 'https://www.google.com/recaptcha/api/siteverify';
    
    /**
     * Validate reCAPTCHA token
     *
     * @param string $token reCAPTCHA token
     * @param string $action Expected action
     * @return array|WP_Error Validation result or error
     */
    public static function validate($token, $action = '') {
        if (empty($token)) {
            return new WP_Error('missing_token', __('Token de reCAPTCHA no proporcionado', 'opticavision-recaptcha'));
        }
        
        $secret_key = get_option('opticavision_recaptcha_secret_key', '');
        if (empty($secret_key)) {
            return new WP_Error('missing_secret', __('Secret Key de reCAPTCHA no configurada', 'opticavision-recaptcha'));
        }
        
        // Prepare request
        $args = array(
            'body' => array(
                'secret' => $secret_key,
                'response' => $token,
                'remoteip' => self::get_user_ip()
            ),
            'timeout' => 10,
            'sslverify' => true
        );
        
        // Make request to Google
        $response = wp_remote_post(self::API_URL, $args);
        
        if (is_wp_error($response)) {
            self::log_error('reCAPTCHA API request failed', $response->get_error_message());
            return new WP_Error('api_error', __('Error al verificar reCAPTCHA', 'opticavision-recaptcha'));
        }
        
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        
        if (!$result) {
            self::log_error('reCAPTCHA response invalid', $body);
            return new WP_Error('invalid_response', __('Respuesta inválida de reCAPTCHA', 'opticavision-recaptcha'));
        }
        
        // Check if verification was successful
        if (!isset($result['success']) || !$result['success']) {
            $error_codes = isset($result['error-codes']) ? implode(', ', $result['error-codes']) : 'unknown';
            self::log_error('reCAPTCHA verification failed', $error_codes);
            return new WP_Error('verification_failed', __('Verificación de reCAPTCHA fallida', 'opticavision-recaptcha'));
        }
        
        // Check action if provided
        if (!empty($action) && isset($result['action']) && $result['action'] !== $action) {
            self::log_error('reCAPTCHA action mismatch', sprintf('Expected: %s, Got: %s', $action, $result['action']));
            return new WP_Error('action_mismatch', __('Acción de reCAPTCHA no coincide', 'opticavision-recaptcha'));
        }
        
        // Check score threshold
        $threshold = floatval(get_option('opticavision_recaptcha_threshold', '0.5'));
        $score = isset($result['score']) ? floatval($result['score']) : 0;
        
        if ($score < $threshold) {
            self::log_warning('reCAPTCHA score below threshold', sprintf('Score: %s, Threshold: %s', $score, $threshold));
            return new WP_Error('low_score', __('Puntuación de reCAPTCHA demasiado baja', 'opticavision-recaptcha'));
        }
        
        // Log successful verification
        self::log_info('reCAPTCHA verification successful', sprintf('Score: %s, Action: %s', $score, $action));
        
        return array(
            'success' => true,
            'score' => $score,
            'action' => isset($result['action']) ? $result['action'] : '',
            'challenge_ts' => isset($result['challenge_ts']) ? $result['challenge_ts'] : '',
            'hostname' => isset($result['hostname']) ? $result['hostname'] : ''
        );
    }
    
    /**
     * Get user IP address
     *
     * @return string
     */
    private static function get_user_ip() {
        $ip_keys = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER)) {
                $ip = sanitize_text_field(wp_unslash($_SERVER[$key]));
                
                // Check for multiple IPs (proxies)
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return '0.0.0.0';
    }
    
    /**
     * Log error message
     */
    private static function log_error($message, $context = '') {
        if (function_exists('optica_log_error')) {
            optica_log_error($message . ($context ? ': ' . $context : ''), 'RECAPTCHA');
        } else {
            error_log('[OpticaVision reCAPTCHA] ERROR: ' . $message . ($context ? ': ' . $context : ''));
        }
    }
    
    /**
     * Log warning message
     */
    private static function log_warning($message, $context = '') {
        if (function_exists('optica_log_warning')) {
            optica_log_warning($message . ($context ? ': ' . $context : ''), 'RECAPTCHA');
        } else {
            error_log('[OpticaVision reCAPTCHA] WARNING: ' . $message . ($context ? ': ' . $context : ''));
        }
    }
    
    /**
     * Log info message
     */
    private static function log_info($message, $context = '') {
        if (function_exists('optica_log_info')) {
            optica_log_info($message . ($context ? ': ' . $context : ''), 'RECAPTCHA');
        }
    }
}

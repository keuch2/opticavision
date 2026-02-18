<?php
/**
 * Logger wrapper for OpticaVision Theme
 */

defined('ABSPATH') || exit;

if (!function_exists('opticavision_log')) {
    function opticavision_log($message, $level = 'info') {
        $level = strtolower($level);
        $valid_levels = array('debug', 'info', 'warning', 'error', 'performance');

        if (!in_array($level, $valid_levels, true)) {
            $level = 'info';
        }

        if (function_exists('optica_log_' . $level)) {
            call_user_func('optica_log_' . $level, '[THEME] ' . $message);
        } elseif (function_exists('optica_vision_logger')) {
            try {
                $logger = optica_vision_logger();
                if (method_exists($logger, 'log')) {
                    $logger->log($level, '[THEME] ' . $message);
                }
            } catch (Exception $e) {
                if (function_exists('error_log')) {
                    error_log('[OpticaVision Theme Logger] ' . $e->getMessage());
                }
            }
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[OpticaVision Theme] ' . strtoupper($level) . ': ' . $message);
            }
        }
    }
}

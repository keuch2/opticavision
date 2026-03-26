<?php
/**
 * Plugin Name: OpticaVision Product Tags
 * Plugin URI:  https://opticavision.com.py
 * Description: Sistema de etiquetas/badges personalizables por producto o categoría WooCommerce.
 * Version:     1.0.0
 * Author:      OpticaVision
 * Text Domain: opticavision-product-tags
 * Domain Path: /languages
 */

defined('ABSPATH') || exit;

define('OV_TAGS_VERSION', '1.0.0');
define('OV_TAGS_DIR',     plugin_dir_path(__FILE__));
define('OV_TAGS_URI',     plugin_dir_url(__FILE__));

require_once OV_TAGS_DIR . 'includes/class-admin.php';
require_once OV_TAGS_DIR . 'includes/class-frontend.php';

new OV_Tags_Admin();
new OV_Tags_Frontend();

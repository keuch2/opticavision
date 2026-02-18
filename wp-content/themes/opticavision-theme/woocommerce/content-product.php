<?php
/**
 * The template for displaying product content within loops
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/content-product.php.
 *
 * @package OpticaVision_Theme
 * @version 8.2.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

// Ensure visibility.
if ( empty( $product ) || ! $product->is_visible() ) {
	return;
}

// Use our custom product card
echo opticavision_product_card( $product->get_id(), array(
    'show_badges' => true,
    'show_category' => true,
    'show_actions' => true,
    'class' => 'woocommerce-product-card'
) );

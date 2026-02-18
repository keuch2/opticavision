<?php
/**
 * Main Stock Control Class
 *
 * Handles filtering of products based on stock availability
 *
 * @package OpticaVision_Stock_Control
 */

defined('ABSPATH') || exit;

/**
 * Optica_Vision_Stock_Control class
 */
class Optica_Vision_Stock_Control {

    /**
     * Plugin settings
     *
     * @var array
     */
    private $settings;

    /**
     * Initialize the class
     */
    public function init() {
        // Load settings
        $this->settings = get_option('optica_stock_control_settings', array(
            'hide_simple_out_of_stock' => 'no',
            'hide_variable_out_of_stock' => 'no',
            'hide_without_featured_image' => 'no'
        ));

        // Add filters
        add_filter('woocommerce_product_is_visible', array($this, 'filter_product_visibility'), 10, 2);
        add_filter('woocommerce_product_query_meta_query', array($this, 'filter_product_query'), 10, 2);
        add_action('pre_get_posts', array($this, 'filter_product_query_by_image'), 20);
        
        // Log initialization
        if (function_exists('optica_log_debug')) {
            optica_log_debug('OpticaVision Stock Control inicializado', array(
                'hide_simple_out_of_stock' => $this->settings['hide_simple_out_of_stock'],
                'hide_variable_out_of_stock' => $this->settings['hide_variable_out_of_stock'],
                'hide_without_featured_image' => $this->settings['hide_without_featured_image']
            ));
        }
    }

    /**
     * Filter product visibility based on stock settings
     *
     * @param bool $visible Current visibility status
     * @param int $product_id Product ID
     * @return bool Modified visibility status
     */
    public function filter_product_visibility($visible, $product_id) {
        // If already not visible, return
        if (!$visible) {
            return $visible;
        }

        $product = wc_get_product($product_id);
        
        if (!$product) {
            return $visible;
        }

        // Check simple products
        if ($product->is_type('simple') && $this->settings['hide_simple_out_of_stock'] === 'yes') {
            if (!$product->is_in_stock()) {
                if (function_exists('optica_log_debug')) {
                    optica_log_debug('Producto simple sin stock ocultado', array(
                        'product_id' => $product_id,
                        'product_name' => $product->get_name()
                    ));
                }
                return false;
            }
        }

        // Check variable products
        if ($product->is_type('variable') && $this->settings['hide_variable_out_of_stock'] === 'yes') {
            if (!$this->has_available_variations($product)) {
                if (function_exists('optica_log_debug')) {
                    optica_log_debug('Producto variable sin variaciones disponibles ocultado', array(
                        'product_id' => $product_id,
                        'product_name' => $product->get_name()
                    ));
                }
                return false;
            }
        }

        // Check products without featured image
        if ($this->settings['hide_without_featured_image'] === 'yes') {
            if (!has_post_thumbnail($product_id)) {
                if (function_exists('optica_log_debug')) {
                    optica_log_debug('Producto sin imagen destacada ocultado', array(
                        'product_id' => $product_id,
                        'product_name' => $product->get_name(),
                        'product_type' => $product->get_type()
                    ));
                }
                return false;
            }
        }

        return $visible;
    }

    /**
     * Filter product query to exclude out of stock products
     *
     * @param array $meta_query Current meta query
     * @param WC_Query $query WooCommerce query object
     * @return array Modified meta query
     */
    public function filter_product_query($meta_query, $query) {
        // NOTA: Este meta_query no distingue entre tipos de productos
        // La distinción entre productos simples y variables se hace en filter_product_visibility
        // que tiene acceso al objeto completo del producto
        
        // No aplicamos meta_query aquí porque no podemos filtrar por tipo de producto
        // eficientemente en el meta_query (el tipo es una taxonomía, no un meta field)
        
        return $meta_query;
    }

    /**
     * Filter product query to exclude products without featured image
     *
     * @param WP_Query $query WordPress query object
     */
    public function filter_product_query_by_image($query) {
        // Only apply to product queries on frontend
        if (is_admin()) {
            return;
        }

        // Only for WooCommerce product queries
        if (!isset($query->query_vars['post_type']) || $query->query_vars['post_type'] !== 'product') {
            return;
        }

        // Check if we should filter by featured image
        if ($this->settings['hide_without_featured_image'] === 'yes') {
            // Get existing meta query
            $meta_query = $query->get('meta_query');
            if (!is_array($meta_query)) {
                $meta_query = array();
            }

            // Add thumbnail ID exists check
            $meta_query[] = array(
                'key' => '_thumbnail_id',
                'compare' => 'EXISTS'
            );

            $query->set('meta_query', $meta_query);

            if (function_exists('optica_log_debug')) {
                optica_log_debug('Stock Control: Query modificada para excluir productos sin imagen destacada', array(
                    'is_main_query' => $query->is_main_query(),
                    'is_ajax' => wp_doing_ajax()
                ));
            }
        }
    }

    /**
     * Check if variable product has available variations
     *
     * @param WC_Product_Variable $product Variable product object
     * @return bool True if has available variations, false otherwise
     */
    private function has_available_variations($product) {
        $variations = $product->get_available_variations();
        
        if (empty($variations)) {
            return false;
        }

        // Check if at least one variation is in stock
        foreach ($variations as $variation) {
            $variation_obj = wc_get_product($variation['variation_id']);
            
            if ($variation_obj && $variation_obj->is_in_stock()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get current settings
     *
     * @return array Current settings
     */
    public function get_settings() {
        return $this->settings;
    }

    /**
     * Update settings
     *
     * @param array $new_settings New settings
     */
    public function update_settings($new_settings) {
        $this->settings = wp_parse_args($new_settings, $this->settings);
        update_option('optica_stock_control_settings', $this->settings);
        
        // Log update
        if (function_exists('optica_log_info')) {
            optica_log_info('Configuración de Stock Control actualizada', $this->settings);
        }
    }
}

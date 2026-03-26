<?php
/**
 * Frontend — renderizado de badges en loop y página de producto.
 *
 * Lógica de prioridad:
 *   1. Badge asignado directamente al producto (post meta _ov_badge_id).
 *   2. Badge de la categoría principal del producto (term meta ov_badge_id).
 *
 * @package OpticaVision_Product_Tags
 */

defined('ABSPATH') || exit;

class OV_Tags_Frontend {

    public function __construct() {
        // Loop de productos — se engancha al filtro del tema
        add_filter('opticavision_custom_badges', array($this, 'add_loop_badge'), 10, 2);

        // Página de producto singular — acción añadida al template del tema
        add_action('opticavision_single_product_badges', array($this, 'render_single_badge'));

        // Estilos frontend
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }

    /* ------------------------------------------------------------------
     * Estilos
     * ------------------------------------------------------------------ */

    public function enqueue_styles() {
        wp_enqueue_style(
            'ov-product-tags',
            OV_TAGS_URI . 'assets/css/product-tags.css',
            array(),
            OV_TAGS_VERSION
        );
    }

    /* ------------------------------------------------------------------
     * Loop de productos
     * ------------------------------------------------------------------ */

    /**
     * Se engancha a 'opticavision_custom_badges' en product-card-functions.php.
     * Recibe el array de badges ya generados y agrega el badge personalizado si corresponde.
     */
    public function add_loop_badge(array $badges, $product) {
        $badge_html = $this->get_badge_html($product);
        if ($badge_html) {
            $badges[] = $badge_html;
        }
        return $badges;
    }

    /* ------------------------------------------------------------------
     * Página de producto singular
     * ------------------------------------------------------------------ */

    public function render_single_badge($product) {
        $badge_html = $this->get_badge_html($product);
        if ($badge_html) {
            echo '<div class="ov-single-product-badge">' . $badge_html . '</div>';
        }
    }

    /* ------------------------------------------------------------------
     * Lógica de resolución de badge
     * ------------------------------------------------------------------ */

    /**
     * Retorna el HTML del badge para el producto dado o '' si no aplica ninguno.
     */
    private function get_badge_html($product) {
        $badges = OV_Tags_Admin::get_badges();
        if (empty($badges)) {
            return '';
        }

        $badge_id = $this->resolve_badge_id($product);
        if ($badge_id === null || !isset($badges[$badge_id])) {
            return '';
        }

        $badge = $badges[$badge_id];
        return sprintf(
            '<span class="product-badge ov-custom-badge" style="background:%s;color:%s;">%s</span>',
            esc_attr($badge['bg_color']),
            esc_attr($badge['text_color']),
            esc_html($badge['text'])
        );
    }

    /**
     * Determina qué badge_id aplicar al producto.
     * Devuelve null si no hay ninguno.
     */
    private function resolve_badge_id($product) {
        $product_id = $product->get_id();

        // 1. Badge individual del producto
        $product_badge = get_post_meta($product_id, '_ov_badge_id', true);
        if ($product_badge !== '') {
            return (int) $product_badge;
        }

        // 2. Badge de la categoría principal
        $terms = get_the_terms($product_id, 'product_cat');
        if (empty($terms) || is_wp_error($terms)) {
            return null;
        }

        // Categoría principal: la primera del array (ordenadas por ID ascendente en WP)
        $main_term    = reset($terms);
        $cat_badge_id = get_term_meta($main_term->term_id, 'ov_badge_id', true);
        if ($cat_badge_id !== '') {
            return (int) $cat_badge_id;
        }

        // Recorrer el resto de categorías si la principal no tiene badge
        foreach ($terms as $term) {
            $cat_badge_id = get_term_meta($term->term_id, 'ov_badge_id', true);
            if ($cat_badge_id !== '') {
                return (int) $cat_badge_id;
            }
        }

        return null;
    }
}

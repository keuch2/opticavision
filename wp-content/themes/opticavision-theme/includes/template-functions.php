<?php
/**
 * Template Functions for OpticaVision Theme
 *
 * @package OpticaVision_Theme
 */

defined('ABSPATH') || exit;

/**
 * Default menu fallback
 */
function opticavision_theme_default_menu() {
    echo '<ul class="nav-menu">';
    echo '<li><a href="' . esc_url(home_url('/')) . '">' . esc_html__('Inicio', 'opticavision-theme') . '</a></li>';
    
    if (function_exists('wc_get_page_permalink')) {
        echo '<li><a href="' . esc_url(wc_get_page_permalink('shop')) . '">' . esc_html__('Tienda', 'opticavision-theme') . '</a></li>';
    }
    
    echo '<li><a href="' . esc_url(get_permalink(get_page_by_path('sobre-nosotros'))) . '">' . esc_html__('Sobre Nosotros', 'opticavision-theme') . '</a></li>';
    echo '<li><a href="' . esc_url(get_permalink(get_page_by_path('contacto'))) . '">' . esc_html__('Contacto', 'opticavision-theme') . '</a></li>';
    echo '</ul>';
}

/**
 * Get product categories for navigation
 */
function opticavision_get_product_categories($args = array()) {
    $defaults = array(
        'taxonomy'   => 'product_cat',
        'hide_empty' => true,
        'parent'     => 0,
        'number'     => 10,
        'orderby'    => 'name',
        'order'      => 'ASC'
    );
    
    $args = wp_parse_args($args, $defaults);
    
    return get_terms($args);
}

/**
 * Get featured products
 */
function opticavision_get_featured_products($limit = 4) {
    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => $limit,
        'meta_query'     => array(
            array(
                'key'   => '_featured',
                'value' => 'yes'
            )
        )
    );
    
    return new WP_Query($args);
}

/**
 * Get products on sale
 */
function opticavision_get_sale_products($limit = 4) {
    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => $limit,
        'meta_query'     => array(
            array(
                'key'     => '_sale_price',
                'value'   => '',
                'compare' => '!='
            )
        )
    );
    
    return new WP_Query($args);
}

/**
 * Get latest products
 */
function opticavision_get_latest_products($limit = 4) {
    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => $limit,
        'orderby'        => 'date',
        'order'          => 'DESC'
    );
    
    return new WP_Query($args);
}

/**
 * Get products by category
 */
function opticavision_get_products_by_category($category_slug, $limit = 4) {
    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => $limit,
        'tax_query'      => array(
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => $category_slug
            )
        )
    );
    
    return new WP_Query($args);
}

/**
 * Format price with currency
 */
function opticavision_format_price($price, $currency = 'PYG') {
    if (function_exists('wc_price')) {
        return wc_price($price);
    }
    
    return number_format($price, 0, ',', '.') . ' ' . $currency;
}

/**
 * Get product badge
 */
function opticavision_get_product_badge($product) {
    if (!$product) {
        return '';
    }
    
    $badges = array();
    
    if ($product->is_on_sale()) {
        $badges[] = '<span class="product-badge sale-badge">' . esc_html__('Oferta', 'opticavision-theme') . '</span>';
    }
    
    if ($product->is_featured()) {
        $badges[] = '<span class="product-badge featured-badge">' . esc_html__('Destacado', 'opticavision-theme') . '</span>';
    }
    
    // Badge de sin stock - SOLO para productos simples
    if (!$product->is_in_stock() && !$product->is_type('variable')) {
        $badges[] = '<span class="product-badge out-of-stock-badge">' . esc_html__('Agotado', 'opticavision-theme') . '</span>';
    }
    
    return implode('', $badges);
}

/**
 * Get social sharing links
 */
function opticavision_get_social_sharing_links($url = '', $title = '') {
    if (empty($url)) {
        $url = get_permalink();
    }
    
    if (empty($title)) {
        $title = get_the_title();
    }
    
    $encoded_url = urlencode($url);
    $encoded_title = urlencode($title);
    
    $links = array(
        'facebook' => array(
            'url'   => 'https://www.facebook.com/sharer/sharer.php?u=' . $encoded_url,
            'label' => __('Compartir en Facebook', 'opticavision-theme'),
            'icon'  => 'facebook'
        ),
        'twitter' => array(
            'url'   => 'https://twitter.com/intent/tweet?url=' . $encoded_url . '&text=' . $encoded_title,
            'label' => __('Compartir en Twitter', 'opticavision-theme'),
            'icon'  => 'twitter'
        ),
        'whatsapp' => array(
            'url'   => 'https://wa.me/?text=' . $encoded_title . ' ' . $encoded_url,
            'label' => __('Compartir en WhatsApp', 'opticavision-theme'),
            'icon'  => 'whatsapp'
        )
    );
    
    return apply_filters('opticavision_social_sharing_links', $links, $url, $title);
}

/**
 * Render social sharing buttons
 */
function opticavision_social_sharing_buttons($url = '', $title = '') {
    $links = opticavision_get_social_sharing_links($url, $title);
    
    if (empty($links)) {
        return;
    }
    
    echo '<div class="social-sharing">';
    echo '<span class="sharing-label">' . esc_html__('Compartir:', 'opticavision-theme') . '</span>';
    echo '<div class="sharing-buttons">';
    
    foreach ($links as $platform => $link) {
        echo '<a href="' . esc_url($link['url']) . '" ';
        echo 'class="sharing-button sharing-' . esc_attr($platform) . '" ';
        echo 'target="_blank" rel="noopener noreferrer" ';
        echo 'aria-label="' . esc_attr($link['label']) . '">';
        echo '<span class="sr-only">' . esc_html($link['label']) . '</span>';
        echo '</a>';
    }
    
    echo '</div>';
    echo '</div>';
}

/**
 * Get reading time estimate
 */
function opticavision_get_reading_time($content = '') {
    if (empty($content)) {
        $content = get_the_content();
    }
    
    $word_count = str_word_count(strip_tags($content));
    $reading_time = ceil($word_count / 200); // 200 words per minute
    
    return $reading_time;
}

/**
 * Render reading time
 */
function opticavision_reading_time($content = '') {
    $reading_time = opticavision_get_reading_time($content);
    
    if ($reading_time > 0) {
        printf(
            '<span class="reading-time">%s</span>',
            sprintf(
                esc_html(_n('%d minuto de lectura', '%d minutos de lectura', $reading_time, 'opticavision-theme')),
                $reading_time
            )
        );
    }
}

/**
 * Get excerpt with custom length
 */
function opticavision_get_excerpt($length = 20, $more = '...') {
    $excerpt = get_the_excerpt();
    
    if (empty($excerpt)) {
        $excerpt = get_the_content();
    }
    
    $excerpt = wp_strip_all_tags($excerpt);
    $words = explode(' ', $excerpt);
    
    if (count($words) > $length) {
        $words = array_slice($words, 0, $length);
        $excerpt = implode(' ', $words) . $more;
    }
    
    return $excerpt;
}

/**
 * Check if page has sidebar
 */
function opticavision_has_sidebar() {
    $sidebar_position = get_theme_mod('sidebar_position', 'right');
    
    if ($sidebar_position === 'none') {
        return false;
    }
    
    // Don't show sidebar on certain pages
    if (is_front_page() || is_page_template('page-fullwidth.php')) {
        return false;
    }
    
    // Don't show sidebar on WooCommerce pages except single product
    if (function_exists('is_woocommerce') && is_woocommerce() && !is_product()) {
        return false;
    }
    
    return is_active_sidebar('sidebar-1');
}

/**
 * Get sidebar position
 */
function opticavision_get_sidebar_position() {
    return get_theme_mod('sidebar_position', 'right');
}

/**
 * Get container classes
 */
function opticavision_get_container_classes() {
    $classes = array('site-container');
    
    if (opticavision_has_sidebar()) {
        $classes[] = 'has-sidebar';
        $classes[] = 'sidebar-' . opticavision_get_sidebar_position();
    } else {
        $classes[] = 'no-sidebar';
    }
    
    return implode(' ', $classes);
}

/**
 * Get content classes
 */
function opticavision_get_content_classes() {
    $classes = array('site-content');
    
    if (opticavision_has_sidebar()) {
        $classes[] = 'has-sidebar';
    } else {
        $classes[] = 'full-width';
    }
    
    return implode(' ', $classes);
}

/**
 * Render pagination
 */
function opticavision_pagination($query = null) {
    global $wp_query;
    
    if (!$query) {
        $query = $wp_query;
    }
    
    $total_pages = $query->max_num_pages;
    
    if ($total_pages <= 1) {
        return;
    }
    
    $current_page = max(1, get_query_var('paged'));
    
    echo '<nav class="pagination-nav" role="navigation" aria-label="' . esc_attr__('Navegación de páginas', 'opticavision-theme') . '">';
    echo '<div class="pagination">';
    
    // Previous page link
    if ($current_page > 1) {
        echo '<a href="' . esc_url(get_pagenum_link($current_page - 1)) . '" class="pagination-prev">';
        echo '<span class="sr-only">' . esc_html__('Página anterior', 'opticavision-theme') . '</span>';
        echo '&laquo;';
        echo '</a>';
    }
    
    // Page numbers
    for ($i = 1; $i <= $total_pages; $i++) {
        if ($i === $current_page) {
            echo '<span class="pagination-current" aria-current="page">' . $i . '</span>';
        } else {
            echo '<a href="' . esc_url(get_pagenum_link($i)) . '" class="pagination-number">' . $i . '</a>';
        }
    }
    
    // Next page link
    if ($current_page < $total_pages) {
        echo '<a href="' . esc_url(get_pagenum_link($current_page + 1)) . '" class="pagination-next">';
        echo '<span class="sr-only">' . esc_html__('Página siguiente', 'opticavision-theme') . '</span>';
        echo '&raquo;';
        echo '</a>';
    }
    
    echo '</div>';
    echo '</nav>';
}

/**
 * Get theme option with fallback
 */
function opticavision_get_option($option_name, $default = '') {
    return get_theme_mod($option_name, $default);
}

/**
 * Check if theme feature is enabled
 */
function opticavision_is_feature_enabled($feature) {
    $enabled_features = opticavision_get_option('enabled_features', array());
    return in_array($feature, $enabled_features);
}

/**
 * Get asset URL with version
 */
function opticavision_get_asset_url($asset_path) {
    $url = OPTICAVISION_THEME_URI . '/assets/' . ltrim($asset_path, '/');
    
    if (opticavision_is_dev_mode()) {
        $url = add_query_arg('v', time(), $url);
    } else {
        $url = add_query_arg('v', opticavision_get_theme_version(), $url);
    }
    
    return $url;
}

/**
 * Render loading spinner
 */
function opticavision_loading_spinner($class = '') {
    echo '<div class="loading-spinner ' . esc_attr($class) . '">';
    echo '<div class="spinner"></div>';
    echo '</div>';
}

/**
 * Get placeholder image URL
 */
function opticavision_get_placeholder_image($size = 'medium') {
    if (function_exists('wc_placeholder_img_src')) {
        return wc_placeholder_img_src($size);
    }
    
    return OPTICAVISION_THEME_URI . '/assets/images/placeholder.png';
}

/**
 * Sanitize HTML classes
 */
function opticavision_sanitize_html_classes($classes) {
    if (is_array($classes)) {
        $classes = implode(' ', $classes);
    }
    
    return sanitize_html_class($classes);
}

/**
 * Get current URL
 */
function opticavision_get_current_url() {
    return home_url(add_query_arg(array(), $GLOBALS['wp']->request));
}

/**
 * Check if mobile device
 */
function opticavision_is_mobile() {
    return wp_is_mobile();
}

/**
 * Get browser class
 */
function opticavision_get_browser_class() {
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $browser_class = '';
    
    if (strpos($user_agent, 'Chrome') !== false) {
        $browser_class = 'chrome';
    } elseif (strpos($user_agent, 'Firefox') !== false) {
        $browser_class = 'firefox';
    } elseif (strpos($user_agent, 'Safari') !== false) {
        $browser_class = 'safari';
    } elseif (strpos($user_agent, 'Edge') !== false) {
        $browser_class = 'edge';
    }
    
    return $browser_class;
}

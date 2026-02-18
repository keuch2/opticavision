<?php
/**
 * Breadcrumbs functionality for OpticaVision Theme
 *
 * @package OpticaVision_Theme
 */

defined('ABSPATH') || exit;

class OpticaVision_Breadcrumbs {

    /**
     * Constructor
     */
    public function __construct() {
        // No automatic hooks - breadcrumbs are called manually in templates
    }

    /**
     * Generate and display breadcrumbs
     */
    public static function display($args = array()) {
        $defaults = array(
            'home_text' => __('Inicio', 'opticavision-theme'),
            'separator' => '/',
            'show_current' => true,
            'before_current' => '<span class="current">',
            'after_current' => '</span>',
            'class' => 'opticavision-breadcrumbs',
            'container' => 'nav',
            'schema' => true
        );

        $args = wp_parse_args($args, $defaults);
        
        // Don't show on homepage
        if (is_front_page()) {
            return;
        }

        $breadcrumbs = self::generate_breadcrumbs($args);
        
        if (empty($breadcrumbs)) {
            return;
        }

        self::render_breadcrumbs($breadcrumbs, $args);
    }

    /**
     * Generate breadcrumb items
     */
    private static function generate_breadcrumbs($args) {
        global $post, $wp_query;
        
        $breadcrumbs = array();
        
        // Home link
        $breadcrumbs[] = array(
            'title' => $args['home_text'],
            'url' => home_url('/'),
            'current' => false
        );

        // WooCommerce pages
        if (function_exists('is_woocommerce') && is_woocommerce()) {
            return self::generate_woocommerce_breadcrumbs($breadcrumbs, $args);
        }

        // Blog pages
        if (is_home() && !is_front_page()) {
            $blog_page = get_option('page_for_posts');
            if ($blog_page) {
                $breadcrumbs[] = array(
                    'title' => get_the_title($blog_page),
                    'url' => get_permalink($blog_page),
                    'current' => true
                );
            }
        }
        // Single post
        elseif (is_single() && !is_attachment()) {
            // Add category for posts
            if (get_post_type() === 'post') {
                $categories = get_the_category();
                if (!empty($categories)) {
                    $category = $categories[0];
                    $breadcrumbs[] = array(
                        'title' => $category->name,
                        'url' => get_category_link($category->term_id),
                        'current' => false
                    );
                }
            }
            
            // Add current post
            if ($args['show_current']) {
                $breadcrumbs[] = array(
                    'title' => get_the_title(),
                    'url' => '',
                    'current' => true
                );
            }
        }
        // Page
        elseif (is_page()) {
            // Add parent pages
            if ($post->post_parent) {
                $parent_ids = array_reverse(get_post_ancestors($post->ID));
                foreach ($parent_ids as $parent_id) {
                    $breadcrumbs[] = array(
                        'title' => get_the_title($parent_id),
                        'url' => get_permalink($parent_id),
                        'current' => false
                    );
                }
            }
            
            // Add current page
            if ($args['show_current']) {
                $breadcrumbs[] = array(
                    'title' => get_the_title(),
                    'url' => '',
                    'current' => true
                );
            }
        }
        // Category archive
        elseif (is_category()) {
            $category = get_queried_object();
            
            // Add parent categories
            if ($category->parent) {
                $parent_cats = array_reverse(get_ancestors($category->term_id, 'category'));
                foreach ($parent_cats as $parent_id) {
                    $parent = get_category($parent_id);
                    $breadcrumbs[] = array(
                        'title' => $parent->name,
                        'url' => get_category_link($parent->term_id),
                        'current' => false
                    );
                }
            }
            
            // Add current category
            if ($args['show_current']) {
                $breadcrumbs[] = array(
                    'title' => $category->name,
                    'url' => '',
                    'current' => true
                );
            }
        }
        // Tag archive
        elseif (is_tag()) {
            $tag = get_queried_object();
            if ($args['show_current']) {
                $breadcrumbs[] = array(
                    'title' => sprintf(__('Etiqueta: %s', 'opticavision-theme'), $tag->name),
                    'url' => '',
                    'current' => true
                );
            }
        }
        // Author archive
        elseif (is_author()) {
            $author = get_queried_object();
            if ($args['show_current']) {
                $breadcrumbs[] = array(
                    'title' => sprintf(__('Autor: %s', 'opticavision-theme'), $author->display_name),
                    'url' => '',
                    'current' => true
                );
            }
        }
        // Date archive
        elseif (is_date()) {
            if (is_year()) {
                if ($args['show_current']) {
                    $breadcrumbs[] = array(
                        'title' => get_the_date('Y'),
                        'url' => '',
                        'current' => true
                    );
                }
            } elseif (is_month()) {
                $breadcrumbs[] = array(
                    'title' => get_the_date('Y'),
                    'url' => get_year_link(get_the_date('Y')),
                    'current' => false
                );
                
                if ($args['show_current']) {
                    $breadcrumbs[] = array(
                        'title' => get_the_date('F'),
                        'url' => '',
                        'current' => true
                    );
                }
            } elseif (is_day()) {
                $breadcrumbs[] = array(
                    'title' => get_the_date('Y'),
                    'url' => get_year_link(get_the_date('Y')),
                    'current' => false
                );
                
                $breadcrumbs[] = array(
                    'title' => get_the_date('F'),
                    'url' => get_month_link(get_the_date('Y'), get_the_date('m')),
                    'current' => false
                );
                
                if ($args['show_current']) {
                    $breadcrumbs[] = array(
                        'title' => get_the_date('j'),
                        'url' => '',
                        'current' => true
                    );
                }
            }
        }
        // Search results
        elseif (is_search()) {
            if ($args['show_current']) {
                $breadcrumbs[] = array(
                    'title' => sprintf(__('Resultados de búsqueda para: %s', 'opticavision-theme'), get_search_query()),
                    'url' => '',
                    'current' => true
                );
            }
        }
        // 404 page
        elseif (is_404()) {
            if ($args['show_current']) {
                $breadcrumbs[] = array(
                    'title' => __('Página no encontrada', 'opticavision-theme'),
                    'url' => '',
                    'current' => true
                );
            }
        }

        return apply_filters('opticavision_breadcrumbs', $breadcrumbs, $args);
    }

    /**
     * Generate WooCommerce specific breadcrumbs
     */
    private static function generate_woocommerce_breadcrumbs($breadcrumbs, $args) {
        // Shop page
        $shop_page_id = wc_get_page_id('shop');
        if ($shop_page_id && !is_shop()) {
            $breadcrumbs[] = array(
                'title' => get_the_title($shop_page_id),
                'url' => get_permalink($shop_page_id),
                'current' => false
            );
        }

        if (is_product_category()) {
            $category = get_queried_object();
            
            // Add parent categories
            if ($category->parent) {
                $parent_cats = array_reverse(get_ancestors($category->term_id, 'product_cat'));
                foreach ($parent_cats as $parent_id) {
                    $parent = get_term($parent_id, 'product_cat');
                    $breadcrumbs[] = array(
                        'title' => $parent->name,
                        'url' => get_term_link($parent),
                        'current' => false
                    );
                }
            }
            
            // Add current category
            if ($args['show_current']) {
                $breadcrumbs[] = array(
                    'title' => $category->name,
                    'url' => '',
                    'current' => true
                );
            }
        }
        elseif (is_product_tag()) {
            $tag = get_queried_object();
            if ($args['show_current']) {
                $breadcrumbs[] = array(
                    'title' => sprintf(__('Productos etiquetados "%s"', 'opticavision-theme'), $tag->name),
                    'url' => '',
                    'current' => true
                );
            }
        }
        elseif (is_product()) {
            global $post;
            
            // Add product categories
            $categories = wp_get_post_terms($post->ID, 'product_cat');
            if (!empty($categories)) {
                $category = $categories[0];
                
                // Add parent categories
                if ($category->parent) {
                    $parent_cats = array_reverse(get_ancestors($category->term_id, 'product_cat'));
                    foreach ($parent_cats as $parent_id) {
                        $parent = get_term($parent_id, 'product_cat');
                        $breadcrumbs[] = array(
                            'title' => $parent->name,
                            'url' => get_term_link($parent),
                            'current' => false
                        );
                    }
                }
                
                // Add current category
                $breadcrumbs[] = array(
                    'title' => $category->name,
                    'url' => get_term_link($category),
                    'current' => false
                );
            }
            
            // Add current product
            if ($args['show_current']) {
                $breadcrumbs[] = array(
                    'title' => get_the_title(),
                    'url' => '',
                    'current' => true
                );
            }
        }
        elseif (is_cart()) {
            if ($args['show_current']) {
                $breadcrumbs[] = array(
                    'title' => __('Carrito', 'opticavision-theme'),
                    'url' => '',
                    'current' => true
                );
            }
        }
        elseif (is_checkout()) {
            $breadcrumbs[] = array(
                'title' => __('Carrito', 'opticavision-theme'),
                'url' => wc_get_cart_url(),
                'current' => false
            );
            
            if ($args['show_current']) {
                $breadcrumbs[] = array(
                    'title' => __('Checkout', 'opticavision-theme'),
                    'url' => '',
                    'current' => true
                );
            }
        }
        elseif (is_account_page()) {
            if ($args['show_current']) {
                $breadcrumbs[] = array(
                    'title' => __('Mi Cuenta', 'opticavision-theme'),
                    'url' => '',
                    'current' => true
                );
            }
        }
        elseif (is_shop()) {
            if ($args['show_current']) {
                $breadcrumbs[] = array(
                    'title' => get_the_title($shop_page_id),
                    'url' => '',
                    'current' => true
                );
            }
        }

        return $breadcrumbs;
    }

    /**
     * Render breadcrumbs HTML
     */
    private static function render_breadcrumbs($breadcrumbs, $args) {
        if (empty($breadcrumbs)) {
            return;
        }

        $schema_markup = '';
        if ($args['schema']) {
            $schema_markup = ' itemscope itemtype="https://schema.org/BreadcrumbList"';
        }

        echo '<' . esc_attr($args['container']) . ' class="' . esc_attr($args['class']) . '"' . $schema_markup . '>';
        echo '<ol class="breadcrumb-list">';

        $position = 1;
        $total_items = count($breadcrumbs);

        foreach ($breadcrumbs as $index => $crumb) {
            $is_last = ($index === $total_items - 1);
            $item_schema = '';
            
            if ($args['schema']) {
                $item_schema = ' itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem"';
            }

            echo '<li class="breadcrumb-item' . ($crumb['current'] ? ' current' : '') . '"' . $item_schema . '>';

            if ($args['schema']) {
                echo '<meta itemprop="position" content="' . esc_attr($position) . '">';
            }

            if (!empty($crumb['url']) && !$crumb['current']) {
                if ($args['schema']) {
                    echo '<a href="' . esc_url($crumb['url']) . '" itemprop="item">';
                    echo '<span itemprop="name">' . esc_html($crumb['title']) . '</span>';
                    echo '</a>';
                } else {
                    echo '<a href="' . esc_url($crumb['url']) . '">' . esc_html($crumb['title']) . '</a>';
                }
            } else {
                if ($crumb['current'] && $args['show_current']) {
                    echo $args['before_current'];
                    if ($args['schema']) {
                        echo '<span itemprop="name">' . esc_html($crumb['title']) . '</span>';
                    } else {
                        echo esc_html($crumb['title']);
                    }
                    echo $args['after_current'];
                } else {
                    if ($args['schema']) {
                        echo '<span itemprop="name">' . esc_html($crumb['title']) . '</span>';
                    } else {
                        echo esc_html($crumb['title']);
                    }
                }
            }

            // Add separator (except for last item)
            if (!$is_last) {
                echo '<span class="separator">' . esc_html($args['separator']) . '</span>';
            }

            echo '</li>';
            $position++;
        }

        echo '</ol>';
        echo '</' . esc_attr($args['container']) . '>';
    }

    /**
     * Get breadcrumbs as array (for use in other contexts)
     */
    public static function get_breadcrumbs($args = array()) {
        $defaults = array(
            'home_text' => __('Inicio', 'opticavision-theme'),
            'show_current' => true
        );

        $args = wp_parse_args($args, $defaults);
        
        if (is_front_page()) {
            return array();
        }

        return self::generate_breadcrumbs($args);
    }
}

/**
 * Helper function to display breadcrumbs
 */
function opticavision_breadcrumbs($args = array()) {
    OpticaVision_Breadcrumbs::display($args);
}

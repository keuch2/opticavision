<?php
/**
 * The Template for displaying product archives, including the main shop page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/archive-product.php.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package OpticaVision_Theme
 * @version 8.2.0
 */

defined('ABSPATH') || exit;

get_header();

/**
 * Hook: woocommerce_before_main_content.
 *
 * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
 * @hooked woocommerce_breadcrumb - 20
 */
do_action('woocommerce_before_main_content');

?>
<div class="opticavision-shop-container">
    <div class="opticavision-shop-content">
        <aside class="opticavision-filters-sidebar" aria-label="<?php esc_attr_e('Filtros de productos', 'opticavision-theme'); ?>">
            <?php
            $filter_start_time = microtime(true);
            if (function_exists('opticavision_log')) {
                opticavision_log(sprintf('[SHOP TEMPLATE] Renderizando filtros para %s', esc_url_raw(add_query_arg([]))), 'debug');
            }
            if (shortcode_exists('wc_ajax_filters')) {
                if (function_exists('opticavision_log')) {
                    opticavision_log('[SHOP TEMPLATE] Ejecutando shortcode wc_ajax_filters', 'debug');
                }
                echo do_shortcode('[wc_ajax_filters]');
                if (function_exists('opticavision_log')) {
                    opticavision_log(sprintf('[SHOP TEMPLATE] Shortcode wc_ajax_filters finalizado en %.2f ms', (microtime(true) - $filter_start_time) * 1000), 'debug');
                }
            } else {
                ?>
                <div class="basic-filters">
                    <h2><?php esc_html_e('Filtros de productos', 'opticavision-theme'); ?></h2>
                    <?php
                    wp_list_categories(
                        array(
                            'taxonomy'   => 'product_cat',
                            'hide_empty' => true,
                            'title_li'   => '',
                        )
                    );
                    ?>
                </div>
                <?php
                if (function_exists('opticavision_log')) {
                    opticavision_log(sprintf('[SHOP TEMPLATE] Fallback de filtros ejecutado en %.2f ms', (microtime(true) - $filter_start_time) * 1000), 'debug');
                }
            }
            ?>
        </aside>

        <div class="opticavision-products-main">
            <header class="woocommerce-products-header">
                <?php if (apply_filters('woocommerce_show_page_title', true)) : ?>
                    <h1 class="woocommerce-products-header__title page-title"><?php woocommerce_page_title(); ?></h1>
                <?php endif; ?>

                <?php
                /**
                 * Hook: woocommerce_archive_description.
                 *
                 * @hooked woocommerce_taxonomy_archive_description - 10
                 * @hooked woocommerce_product_archive_description - 10
                 */
                do_action('woocommerce_archive_description');
                ?>

                <div class="shop-toolbar">
                    <?php
                    /**
                     * Hook: woocommerce_before_shop_loop.
                     *
                     * @hooked woocommerce_output_all_notices - 10
                     * @hooked woocommerce_result_count - 20
                     * @hooked woocommerce_catalog_ordering - 30
                     */
                    do_action('woocommerce_before_shop_loop');
                    ?>
                </div>
            </header>

            <?php
            $products_start_time = microtime(true);
            if (woocommerce_product_loop()) {
                if (shortcode_exists('wc_ajax_filtered_products')) {
                    if (function_exists('opticavision_log')) {
                        opticavision_log('[SHOP TEMPLATE] Ejecutando shortcode wc_ajax_filtered_products', 'debug');
                    }
                    echo do_shortcode('[wc_ajax_filtered_products]');
                    if (function_exists('opticavision_log')) {
                        opticavision_log(sprintf('[SHOP TEMPLATE] Shortcode wc_ajax_filtered_products finalizado en %.2f ms', (microtime(true) - $products_start_time) * 1000), 'debug');
                    }
                } else {
                    if (function_exists('opticavision_log')) {
                        opticavision_log('[SHOP TEMPLATE] Ejecutando loop estándar de WooCommerce', 'debug');
                    }
                    woocommerce_product_loop_start();

                    while (have_posts()) {
                        the_post();

                        /**
                         * Hook: woocommerce_shop_loop.
                         */
                        do_action('woocommerce_shop_loop');

                        wc_get_template_part('content', 'product');
                    }

                    woocommerce_product_loop_end();

                    /**
                     * Hook: woocommerce_after_shop_loop.
                     *
                     * @hooked woocommerce_pagination - 10
                     */
                    do_action('woocommerce_after_shop_loop');
                    if (function_exists('opticavision_log')) {
                        opticavision_log(sprintf('[SHOP TEMPLATE] Loop estándar completado en %.2f ms', (microtime(true) - $products_start_time) * 1000), 'debug');
                    }
                }
            } else {
                /**
                 * Hook: woocommerce_no_products_found.
                 *
                 * @hooked wc_no_products_found - 10
                 */
                do_action('woocommerce_no_products_found');
                if (function_exists('opticavision_log')) {
                    opticavision_log(sprintf('[SHOP TEMPLATE] No se encontraron productos. Tiempo transcurrido %.2f ms', (microtime(true) - $products_start_time) * 1000), 'debug');
                }
            }
            ?>
        </div>
    </div>
</div>

<?php
/**
 * Hook: woocommerce_after_main_content.
 *
 * @hooked woocommerce_output_content_wrapper_end - 10 (outputs closing divs for the content)
 */
do_action('woocommerce_after_main_content');

get_footer();

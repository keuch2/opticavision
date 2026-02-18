<?php
/**
 * The template for displaying search results pages
 *
 * @package OpticaVision_Theme
 */

get_header();

// Log template usage if logger is available
if (function_exists('opticavision_log')) {
    opticavision_log('[THEME] Cargando search.php personalizado');
}

$search_query = get_search_query();
// ALWAYS treat searches as product searches
$is_product_search = true;

?>

<main id="primary" class="site-main search-results">
    <div class="search-container">
        
        <?php if ($is_product_search) : ?>
            <!-- Product Search Results - Simple Display -->
            <div class="search-results-simple">
                <header class="search-header">
                    <h1 class="search-title">
                        <?php
                        printf(
                            esc_html__('Resultados de búsqueda para: "%s"', 'opticavision-theme'),
                            '<span class="search-term">' . esc_html($search_query) . '</span>'
                        );
                        ?>
                    </h1>
                </header>

                <div class="products-grid">
                    <?php
                    // Create a new query for products with the search term
                    $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
                    $product_query = new WP_Query(array(
                        'post_type' => 'product',
                        's' => $search_query,
                        'posts_per_page' => 24,
                        'paged' => $paged,
                        'post_status' => 'publish'
                    ));
                    
                    
                    if ($product_query->have_posts()) :
                        woocommerce_product_loop_start();
                        
                        while ($product_query->have_posts()) : $product_query->the_post();
                            wc_get_template_part('content', 'product');
                        endwhile;
                        
                        woocommerce_product_loop_end();
                        
                        // Pagination
                        if ($product_query->max_num_pages > 1) :
                            echo '<nav class="woocommerce-pagination">';
                            echo paginate_links(array(
                                'total' => $product_query->max_num_pages,
                                'current' => $paged,
                                'prev_text' => '&larr;',
                                'next_text' => '&rarr;',
                            ));
                            echo '</nav>';
                        endif;
                        
                        wp_reset_postdata();
                    else :
                        ?>
                        <p class="woocommerce-info"><?php esc_html_e('No se encontraron productos que coincidan con tu búsqueda.', 'opticavision-theme'); ?></p>
                        <?php
                    endif;
                    ?>
                </div>
                    
                    <!-- Fallback content if no results (handled by AJAX plugin) -->
                    <div class="no-results-fallback" style="display:none;">
                        <div class="search-suggestions">
                            <h3><?php esc_html_e('Sugerencias de búsqueda:', 'opticavision-theme'); ?></h3>
                            <ul>
                                <li><?php esc_html_e('Verifica la ortografía de las palabras', 'opticavision-theme'); ?></li>
                                <li><?php esc_html_e('Intenta con términos más generales', 'opticavision-theme'); ?></li>
                                <li><?php esc_html_e('Usa menos palabras en tu búsqueda', 'opticavision-theme'); ?></li>
                                <li><?php esc_html_e('Prueba con sinónimos o términos relacionados', 'opticavision-theme'); ?></li>
                            </ul>
                            
                            <!-- Popular searches -->
                            <div class="popular-searches">
                                <h4><?php esc_html_e('Búsquedas populares:', 'opticavision-theme'); ?></h4>
                                <div class="popular-search-tags">
                                    <a href="<?php echo esc_url(home_url('/?s=ray+ban&post_type=product')); ?>" class="search-tag">Ray-Ban</a>
                                    <a href="<?php echo esc_url(home_url('/?s=lentes+de+contacto&post_type=product')); ?>" class="search-tag">Lentes de Contacto</a>
                                    <a href="<?php echo esc_url(home_url('/?s=oakley&post_type=product')); ?>" class="search-tag">Oakley</a>
                                    <a href="<?php echo esc_url(home_url('/?s=lentes+de+sol&post_type=product')); ?>" class="search-tag">Lentes de Sol</a>
                                    <a href="<?php echo esc_url(home_url('/?s=armazones&post_type=product')); ?>" class="search-tag">Armazones</a>
                                </div>
                            </div>
                        </div>
                        
                    <div class="no-results-content-fallback" style="display:none;">
                        <!-- Alternative content suggestions -->
                        <div class="no-results-content">
                            <div class="alternative-suggestions">
                                <h3><?php esc_html_e('Explora nuestras categorías:', 'opticavision-theme'); ?></h3>
                                <div class="category-grid">
                                    <?php
                                    $featured_categories = get_terms(array(
                                        'taxonomy' => 'product_cat',
                                        'hide_empty' => true,
                                        'parent' => 0,
                                        'number' => 6
                                    ));
                                    
                                    if (!empty($featured_categories) && !is_wp_error($featured_categories)) :
                                        foreach ($featured_categories as $category) :
                                            $thumbnail_id = get_term_meta($category->term_id, 'thumbnail_id', true);
                                            $image_url = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'medium') : '';
                                            ?>
                                            <a href="<?php echo esc_url(get_term_link($category)); ?>" class="category-card">
                                                <?php if ($image_url) : ?>
                                                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($category->name); ?>">
                                                <?php endif; ?>
                                                <h4><?php echo esc_html($category->name); ?></h4>
                                                <span class="product-count"><?php echo esc_html($category->count); ?> productos</span>
                                            </a>
                                        <?php endforeach;
                                    endif;
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
            
        <?php if (!$is_product_search) : ?>
            <!-- General Content Search Results -->
            <div class="general-search-content">
                <header class="search-header">
                    <h1 class="search-title">
                        <?php
                        printf(
                            esc_html__('Resultados de búsqueda para: "%s"', 'opticavision-theme'),
                            '<span class="search-term">' . esc_html($search_query) . '</span>'
                        );
                        ?>
                    </h1>
                    
                    <div class="search-info">
                        <?php
                        global $wp_query;
                        $total_results = $wp_query->found_posts;
                        
                        if ($total_results > 0) {
                            printf(
                                esc_html(_n('Se encontró %d resultado', 'Se encontraron %d resultados', $total_results, 'opticavision-theme')),
                                $total_results
                            );
                        } else {
                            esc_html_e('No se encontraron resultados', 'opticavision-theme');
                        }
                        ?>
                    </div>
                </header>

                <div class="search-results-content">
                    <?php if (have_posts()) : ?>
                        <div class="search-results-list">
                            <?php while (have_posts()) : the_post(); ?>
                                <article id="post-<?php the_ID(); ?>" <?php post_class('search-result-item'); ?>>
                                    <?php if (has_post_thumbnail()) : ?>
                                        <div class="search-result-thumbnail">
                                            <a href="<?php the_permalink(); ?>">
                                                <?php the_post_thumbnail('medium'); ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="search-result-content">
                                        <header class="search-result-header">
                                            <h2 class="search-result-title">
                                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                            </h2>
                                            <div class="search-result-meta">
                                                <span class="post-type"><?php echo esc_html(get_post_type_object(get_post_type())->labels->singular_name); ?></span>
                                                <time datetime="<?php echo get_the_date('c'); ?>">
                                                    <?php echo get_the_date(); ?>
                                                </time>
                                            </div>
                                        </header>
                                        
                                        <div class="search-result-excerpt">
                                            <?php the_excerpt(); ?>
                                        </div>
                                        
                                        <footer class="search-result-footer">
                                            <a href="<?php the_permalink(); ?>" class="read-more">
                                                <?php esc_html_e('Leer más', 'opticavision-theme'); ?>
                                            </a>
                                        </footer>
                                    </div>
                                </article>
                            <?php endwhile; ?>
                        </div>
                        
                        <?php
                        the_posts_pagination(array(
                            'mid_size'  => 2,
                            'prev_text' => __('&laquo; Anterior', 'opticavision-theme'),
                            'next_text' => __('Siguiente &raquo;', 'opticavision-theme'),
                        ));
                        ?>
                        
                    <?php else : ?>
                        <div class="no-results">
                            <p><?php esc_html_e('Lo sentimos, pero no se encontraron resultados para tu búsqueda. Intenta con diferentes palabras clave.', 'opticavision-theme'); ?></p>
                            
                            <!-- Search form -->
                            <div class="search-again">
                                <?php get_search_form(); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<style>
/* Search Results Styles */
.search-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 20px;
}

.search-header {
    margin-bottom: 2rem;
    text-align: center;
}

.search-title {
    font-size: 2.5rem;
    font-weight: 600;
    color: var(--secondary-color);
    margin-bottom: 1rem;
    line-height: 1.2;
}

.search-term {
    color: var(--primary-color);
    font-style: italic;
}

.search-info {
    font-size: 1.125rem;
    color: #666;
    margin-bottom: 1.5rem;
}

/* Product Search Layout */
.opticavision-search-content {
    display: flex;
    gap: 2rem;
    align-items: flex-start;
}

.opticavision-filters-sidebar {
    width: 300px;
    flex-shrink: 0;
    position: sticky;
    top: 100px;
}

.opticavision-products-main {
    flex: 1;
    min-width: 0;
}

/* Search Suggestions */
.search-suggestions {
    background-color: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    border-left: 4px solid var(--primary-color);
}

.search-suggestions h3 {
    color: var(--secondary-color);
    margin-bottom: 1rem;
    font-size: 1.25rem;
}

.search-suggestions ul {
    list-style: none;
    padding: 0;
    margin: 0 0 1.5rem 0;
}

.search-suggestions li {
    padding: 0.5rem 0;
    color: #666;
    position: relative;
    padding-left: 1.5rem;
}

.search-suggestions li::before {
    content: '•';
    color: var(--primary-color);
    position: absolute;
    left: 0;
    font-weight: bold;
}

.popular-searches h4 {
    color: var(--secondary-color);
    margin-bottom: 0.75rem;
    font-size: 1.125rem;
}

.popular-search-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.search-tag {
    display: inline-block;
    padding: 0.375rem 0.75rem;
    background-color: white;
    color: var(--primary-color);
    text-decoration: none;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.3s ease;
    border: 1px solid #e6e6e6;
}

.search-tag:hover {
    background-color: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
    transform: translateY(-1px);
}

/* Shop Toolbar */
.shop-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 0;
    border-top: 1px solid #e6e6e6;
    border-bottom: 1px solid #e6e6e6;
    margin-bottom: 2rem;
}

.results-count {
    color: #666;
    font-size: 0.875rem;
    font-weight: 500;
}

/* Category Grid */
.category-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-top: 1rem;
}

.category-card {
    display: block;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    text-decoration: none;
    color: inherit;
}

.category-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
}

.category-card img {
    width: 100%;
    height: 150px;
    object-fit: cover;
}

.category-card h4 {
    padding: 1rem 1rem 0.5rem;
    margin: 0;
    color: var(--secondary-color);
    font-size: 1.125rem;
}

.category-card .product-count {
    padding: 0 1rem 1rem;
    color: #666;
    font-size: 0.875rem;
}

/* General Search Results */
.search-results-list {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.search-result-item {
    display: flex;
    gap: 1.5rem;
    padding: 1.5rem;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    transition: box-shadow 0.3s ease;
}

.search-result-item:hover {
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
}

.search-result-thumbnail {
    flex-shrink: 0;
    width: 150px;
}

.search-result-thumbnail img {
    width: 100%;
    height: 100px;
    object-fit: cover;
    border-radius: 4px;
}

.search-result-content {
    flex: 1;
}

.search-result-title {
    margin: 0 0 0.5rem 0;
    font-size: 1.25rem;
    line-height: 1.3;
}

.search-result-title a {
    color: var(--secondary-color);
    text-decoration: none;
    transition: color 0.3s ease;
}

.search-result-title a:hover {
    color: var(--primary-color);
}

.search-result-meta {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
    font-size: 0.875rem;
    color: #666;
}

.search-result-excerpt {
    color: #666;
    line-height: 1.6;
    margin-bottom: 1rem;
}

.read-more {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 500;
    transition: color 0.3s ease;
}

.read-more:hover {
    color: var(--secondary-color);
}

/* No Results */
.no-results {
    text-align: center;
    padding: 3rem 0;
}

.no-results h2 {
    color: var(--secondary-color);
    margin-bottom: 1rem;
}

.search-again {
    margin-top: 2rem;
    width: 100%;
    max-width: 800px;
    margin-left: auto;
    margin-right: auto;
    padding: 0 20px;
}

/* Search Form Styles */
.search-again .search-form,
.search-again form[role="search"] {
    display: flex;
    gap: 0.75rem;
    align-items: stretch;
    justify-content: center;
    width: 100%;
}

.search-again .search-field,
.search-again input[type="search"] {
    flex: 1;
    min-width: 0;
    padding: 1rem 1.5rem;
    font-size: 1.0625rem;
    border: 2px solid #e6e6e6;
    border-radius: 50px;
    outline: none;
    transition: all 0.3s ease;
    font-family: inherit;
}

.search-again .search-field:focus,
.search-again input[type="search"]:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(237, 27, 46, 0.1);
}

.search-again .search-submit,
.search-again button[type="submit"] {
    padding: 1rem 2.5rem;
    background-color: var(--primary-color);
    color: white;
    border: none;
    border-radius: 50px;
    font-size: 1.0625rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    font-family: inherit;
    white-space: nowrap;
    flex-shrink: 0;
}

.search-again .search-submit:hover,
.search-again button[type="submit"]:hover {
    background-color: #c51d30;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(237, 27, 46, 0.3);
}

.search-again .search-submit:active,
.search-again button[type="submit"]:active {
    transform: translateY(0);
}

/* Mobile Styles */
@media (max-width: 1024px) {
    .opticavision-filters-sidebar {
        position: static;
        top: auto;
    }
}

@media (max-width: 768px) {
    .search-container {
        padding: 1rem 15px;
    }
    
    .opticavision-search-content {
        flex-direction: column;
        gap: 1rem;
    }
    
    .opticavision-filters-sidebar {
        width: 100%;
        order: -1;
    }
    
    .search-title {
        font-size: 2rem;
    }
    
    .shop-toolbar {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .category-grid {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1rem;
    }
    
    .search-result-item {
        flex-direction: column;
        gap: 1rem;
    }
    
    .search-result-thumbnail {
        width: 100%;
    }
    
    .search-result-thumbnail img {
        height: 150px;
    }
}

@media (max-width: 576px) {
    .search-title {
        font-size: 1.75rem;
    }
    
    .category-grid {
        grid-template-columns: 1fr 1fr;
    }
    
    .popular-search-tags {
        flex-direction: column;
    }
}
</style>

<script>
// Pass search query to JavaScript for filtering
jQuery(document).ready(function($) {
    $('body').attr('data-search-query', '<?php echo esc_js($search_query); ?>');
    
    // Track search
    if (typeof gtag !== 'undefined') {
        gtag('event', 'search', {
            'search_term': '<?php echo esc_js($search_query); ?>'
        });
    }
});
</script>

<?php get_footer(); ?>

<?php
/**
 * The main template file
 *
 * @package OpticaVision_Theme
 */

get_header(); ?>

<main id="primary" class="site-main">
    <div class="container">
        <?php if (have_posts()) : ?>
            <div class="posts-grid">
                <?php while (have_posts()) : the_post(); ?>
                    <article id="post-<?php the_ID(); ?>" <?php post_class('post-card'); ?>>
                        <?php if (has_post_thumbnail()) : ?>
                            <div class="post-thumbnail">
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_post_thumbnail('medium'); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <div class="post-content">
                            <header class="post-header">
                                <h2 class="post-title">
                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </h2>
                                <div class="post-meta">
                                    <time datetime="<?php echo get_the_date('c'); ?>">
                                        <?php echo get_the_date(); ?>
                                    </time>
                                </div>
                            </header>
                            
                            <div class="post-excerpt">
                                <?php the_excerpt(); ?>
                            </div>
                            
                            <footer class="post-footer">
                                <a href="<?php the_permalink(); ?>" class="btn btn-outline">
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
            <div class="no-posts">
                <h1><?php esc_html_e('No se encontraron publicaciones', 'opticavision-theme'); ?></h1>
                <p><?php esc_html_e('No hay contenido disponible en este momento.', 'opticavision-theme'); ?></p>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php
get_sidebar();
get_footer();

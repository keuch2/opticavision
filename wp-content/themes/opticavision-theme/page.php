<?php
/**
 * The template for displaying all pages
 *
 * @package OpticaVision_Theme
 */

get_header(); ?>

<div class="page-content">
    <div class="container">
        <?php while (have_posts()) : the_post(); ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class('page-article'); ?>>
                <header class="page-header">
                    <h1 class="page-title"><?php the_title(); ?></h1>
                </header>

                <div class="page-content-wrapper">
                    <?php
                    the_content();
                    
                    wp_link_pages(array(
                        'before' => '<div class="page-links">' . esc_html__('Pages:', 'opticavision-theme'),
                        'after'  => '</div>',
                    ));
                    ?>
                </div>

                <?php if (comments_open() || get_comments_number()) : ?>
                    <div class="page-comments">
                        <?php comments_template(); ?>
                    </div>
                <?php endif; ?>
            </article>
        <?php endwhile; ?>
    </div>
</div>

<style>
.page-content {
    padding: 40px 0;
    min-height: 60vh;
}

.page-article {
    max-width: 1200px;
    margin: 0 auto;
}

.page-header {
    margin-bottom: 30px;
    text-align: center;
}

.page-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: #333;
    margin: 0;
}

.page-content-wrapper {
    background: #fff;
    padding: 40px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    line-height: 1.6;
}

.page-content-wrapper h1,
.page-content-wrapper h2,
.page-content-wrapper h3,
.page-content-wrapper h4,
.page-content-wrapper h5,
.page-content-wrapper h6 {
    color: #333;
    margin-top: 2em;
    margin-bottom: 1em;
}

.page-content-wrapper h1 {
    font-size: 2rem;
}

.page-content-wrapper h2 {
    font-size: 1.75rem;
}

.page-content-wrapper h3 {
    font-size: 1.5rem;
}

.page-content-wrapper p {
    margin-bottom: 1.5em;
}

.page-content-wrapper ul,
.page-content-wrapper ol {
    margin-bottom: 1.5em;
    padding-left: 2em;
}

.page-content-wrapper li {
    margin-bottom: 0.5em;
}

.page-links {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #eee;
    text-align: center;
}

.page-links a {
    display: inline-block;
    padding: 8px 16px;
    margin: 0 4px;
    background: #1a2b88;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    transition: background 0.3s ease;
}

.page-links a:hover {
    background: #102064;
}

.page-comments {
    margin-top: 40px;
    padding-top: 40px;
    border-top: 1px solid #eee;
}

@media (max-width: 768px) {
    .page-content {
        padding: 20px 0;
    }
    
    .page-title {
        font-size: 2rem;
    }
    
    .page-content-wrapper {
        padding: 20px;
        margin: 0 20px;
    }
}
</style>

<?php get_footer(); ?>

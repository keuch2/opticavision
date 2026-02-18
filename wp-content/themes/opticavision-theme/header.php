<?php
/**
 * The header for our theme - OpticaVision Style
 *
 * @package OpticaVision_Theme
 */
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    
    <?php wp_head(); ?>
    <!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-4D12KHJC0H"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-4D12KHJC0H');
</script>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link sr-only" href="#primary"><?php esc_html_e('Saltar al contenido principal', 'opticavision-theme'); ?></a>

<div id="page" class="site">
    <header id="masthead" class="opticavision-header">
        <!-- Main Header -->
        <div class="opticavision-main-header">
            <div class="container">
                <div class="opticavision-header-content">
                    <!-- Logo -->
                    <div class="opticavision-logo">
                        <?php
                        if (has_custom_logo()) {
                            the_custom_logo();
                        } else {
                            ?>
                            <a href="<?php echo esc_url(home_url('/')); ?>" rel="home" class="logo-link">
                                <img src="<?php echo get_template_directory_uri(); ?>/assets/images/logo-opticavision2025.png" alt="OpticaVision" class="site-logo">
                            </a>
                            <?php
                        }
                        ?>
                    </div>

                    <!-- Search Bar -->
                    <div class="opticavision-search-container">
                        <form role="search" method="get" class="opticavision-search-form" action="<?php echo esc_url(home_url('/')); ?>">
                            <div class="search-input-wrapper">
                                <input type="search" 
                                       class="opticavision-search-input" 
                                       placeholder="<?php esc_attr_e('Buscar en toda la tienda...', 'opticavision-theme'); ?>" 
                                       value="<?php echo get_search_query(); ?>" 
                                       name="s" 
                                       title="<?php esc_attr_e('Buscar productos', 'opticavision-theme'); ?>" />
                                <button type="submit" class="opticavision-search-btn">
                                    <i class="fas fa-search" aria-hidden="true"></i>
                                    <span class="search-text"><?php esc_html_e('BUSCAR', 'opticavision-theme'); ?></span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Header Actions -->
                    <div class="opticavision-header-actions">
                        <!-- Mi Cuenta -->
                        <div class="account-icon">
                            <a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>" class="account-link" aria-label="<?php esc_attr_e('Mi cuenta', 'opticavision-theme'); ?>">
                                <i class="fas fa-user" aria-hidden="true"></i>
                                <span class="account-text"><?php esc_html_e('Mi Cuenta', 'opticavision-theme'); ?></span>
                            </a>
                        </div>

                        <!-- Cart -->
                        <?php if (function_exists('is_woocommerce') && class_exists('WooCommerce')) : ?>
                            <div class="opticavision-cart-icon">
                                <a href="<?php echo esc_url(wc_get_cart_url()); ?>" class="cart-link" aria-label="<?php esc_attr_e('Ver carrito', 'opticavision-theme'); ?>">
                                    <i class="fas fa-shopping-cart" aria-hidden="true"></i>
                                    <?php
                                    $cart_count = WC()->cart->get_cart_contents_count();
                                    if ($cart_count > 0) {
                                        echo '<span class="cart-count">' . esc_html($cart_count) . '</span>';
                                    } else {
                                        echo '<span class="cart-count" style="display: none;">0</span>';
                                    }
                                    ?>
                                </a>
                            </div>
                        <?php endif; ?>

                        <!-- Mobile Menu Toggle -->
                        <button class="mobile-menu-toggle" aria-controls="primary-menu" aria-expanded="false">
                            <span class="sr-only"><?php esc_html_e('Menú', 'opticavision-theme'); ?></span>
                            <i class="fas fa-bars" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Bar -->
        <div class="opticavision-navigation-bar">
            <div class="container">
                <nav class="main-navigation" role="navigation" aria-label="<?php esc_attr_e('Menú principal', 'opticavision-theme'); ?>">
                    <?php
                    // Debug: Check if menu exists
                    $menu_name = 'primary';
                    $locations = get_nav_menu_locations();
                    
                    if (isset($locations[$menu_name])) {
                        echo '<!-- Menu location found: ' . $menu_name . ' -->';
                        $menu = wp_get_nav_menu_object($locations[$menu_name]);
                        if ($menu) {
                            echo '<!-- Menu object exists: ' . esc_html($menu->name) . ' -->';
                        } else {
                            echo '<!-- Menu object is NULL -->';
                        }
                    } else {
                        echo '<!-- Menu location NOT found: ' . $menu_name . ' -->';
                    }
                    
                    // Try to display menu
                    $menu_output = wp_nav_menu(array(
                        'theme_location' => 'primary',
                        'menu_id'        => 'primary-menu',
                        'menu_class'     => 'opticavision-menu',
                        'container'      => false,
                        'echo'           => false,
                        'fallback_cb'    => false,
                    ));
                    
                    if ($menu_output) {
                        echo $menu_output;
                        echo '<!-- Menu rendered successfully -->';
                    } else {
                        echo '<!-- Menu output is EMPTY -->';
                        // Fallback: show a simple menu
                        echo '<ul class="opticavision-menu">';
                        echo '<li><a href="' . home_url() . '">Inicio</a></li>';
                        echo '<li><a href="' . get_permalink(wc_get_page_id('shop')) . '">Tienda</a></li>';
                        echo '</ul>';
                    }
                    ?>
                </nav>
            </div>
        </div>
    </header>

    <!-- Mobile Search Form (Global - All Pages) -->
    <div class="opticavision-mobile-search-global">
        <div class="container">
            <form role="search" method="get" class="mobile-search-form-global" action="<?php echo esc_url(home_url('/')); ?>">
                <div class="search-form-wrapper-global">
                    <input type="search" 
                           class="search-field-global" 
                           placeholder="<?php echo esc_attr_x('Buscar productos...', 'placeholder', 'opticavision-theme'); ?>" 
                           value="<?php echo get_search_query(); ?>" 
                           name="s" 
                           required />
                    <input type="hidden" name="post_type" value="product" />
                    <button type="submit" class="search-submit-global">
                        <i class="fa fa-search"></i>
                        <span class="screen-reader-text"><?php echo esc_html_x('Buscar', 'submit button', 'opticavision-theme'); ?></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="content" class="site-content">


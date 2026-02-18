<?php
/**
 * AJAX Handlers for OpticaVision Theme
 *
 * @package OpticaVision_Theme
 */

defined('ABSPATH') || exit;

/**
 * AJAX handler for loading more products
 */
function opticavision_load_more_products() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'opticavision_nonce')) {
        wp_die(__('Error de seguridad', 'opticavision-theme'));
    }

    $page = absint($_POST['page']);
    $posts_per_page = absint($_POST['posts_per_page']);
    $category = sanitize_text_field($_POST['category']);
    $orderby = sanitize_text_field($_POST['orderby']);

    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => $posts_per_page,
        'paged'          => $page,
        'post_status'    => 'publish',
        'orderby'        => $orderby,
        'order'          => 'DESC'
    );

    if (!empty($category)) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => $category
            )
        );
    }

    $query = new WP_Query($args);
    
    if (!$query->have_posts()) {
        wp_send_json_error(__('No se encontraron más productos.', 'opticavision-theme'));
    }

    ob_start();
    
    while ($query->have_posts()) {
        $query->the_post();
        $product = wc_get_product(get_the_ID());
        
        if ($product && $product->is_visible()) {
            wc_get_template_part('content', 'product');
        }
    }
    
    wp_reset_postdata();
    
    $html = ob_get_clean();
    
    wp_send_json_success(array(
        'html' => $html,
        'has_more' => $query->max_num_pages > $page
    ));
}
add_action('wp_ajax_opticavision_load_more_products', 'opticavision_load_more_products');
add_action('wp_ajax_nopriv_opticavision_load_more_products', 'opticavision_load_more_products');

/**
 * AJAX handler for search suggestions
 */
function opticavision_search_suggestions() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'opticavision_nonce')) {
        wp_die(__('Error de seguridad', 'opticavision-theme'));
    }

    $query = sanitize_text_field($_POST['query']);
    
    if (strlen($query) < 3) {
        wp_send_json_error(__('La búsqueda debe tener al menos 3 caracteres.', 'opticavision-theme'));
    }

    // Search products
    $products_args = array(
        'post_type'      => 'product',
        'posts_per_page' => 5,
        's'              => $query,
        'post_status'    => 'publish'
    );
    
    $products_query = new WP_Query($products_args);
    $suggestions = array();

    if ($products_query->have_posts()) {
        while ($products_query->have_posts()) {
            $products_query->the_post();
            $product = wc_get_product(get_the_ID());
            
            if ($product && $product->is_visible()) {
                $suggestions[] = array(
                    'id'    => $product->get_id(),
                    'title' => $product->get_name(),
                    'url'   => get_permalink($product->get_id()),
                    'price' => $product->get_price_html(),
                    'image' => wp_get_attachment_image_url(get_post_thumbnail_id($product->get_id()), 'thumbnail'),
                    'type'  => 'product'
                );
            }
        }
        wp_reset_postdata();
    }

    // Search categories
    $categories = get_terms(array(
        'taxonomy'   => 'product_cat',
        'name__like' => $query,
        'number'     => 3,
        'hide_empty' => true
    ));

    if (!is_wp_error($categories) && !empty($categories)) {
        foreach ($categories as $category) {
            $suggestions[] = array(
                'id'    => $category->term_id,
                'title' => $category->name,
                'url'   => get_term_link($category),
                'count' => $category->count,
                'type'  => 'category'
            );
        }
    }

    wp_send_json_success($suggestions);
}
add_action('wp_ajax_opticavision_search_suggestions', 'opticavision_search_suggestions');
add_action('wp_ajax_nopriv_opticavision_search_suggestions', 'opticavision_search_suggestions');

/**
 * AJAX handler for adding product to wishlist
 */
function opticavision_add_to_wishlist() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'opticavision_nonce')) {
        wp_die(__('Error de seguridad', 'opticavision-theme'));
    }

    $product_id = absint($_POST['product_id']);
    $user_id = get_current_user_id();

    if (!$user_id) {
        wp_send_json_error(__('Debes iniciar sesión para agregar productos a tu lista de deseos.', 'opticavision-theme'));
    }

    $product = wc_get_product($product_id);
    
    if (!$product) {
        wp_send_json_error(__('Producto no encontrado.', 'opticavision-theme'));
    }

    // Get user wishlist
    $wishlist = get_user_meta($user_id, 'opticavision_wishlist', true);
    
    if (!is_array($wishlist)) {
        $wishlist = array();
    }

    // Check if product is already in wishlist
    if (in_array($product_id, $wishlist)) {
        wp_send_json_error(__('El producto ya está en tu lista de deseos.', 'opticavision-theme'));
    }

    // Add product to wishlist
    $wishlist[] = $product_id;
    update_user_meta($user_id, 'opticavision_wishlist', $wishlist);

    // Log action if logger is available
    if (function_exists('opticavision_log')) {
        opticavision_log('Producto agregado a wishlist: ' . $product->get_name() . ' (ID: ' . $product_id . ') por usuario: ' . $user_id);
    }

    wp_send_json_success(array(
        'message' => __('Producto agregado a tu lista de deseos.', 'opticavision-theme'),
        'count'   => count($wishlist)
    ));
}
add_action('wp_ajax_opticavision_add_to_wishlist', 'opticavision_add_to_wishlist');

/**
 * AJAX handler for removing product from wishlist
 */
function opticavision_remove_from_wishlist() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'opticavision_nonce')) {
        wp_die(__('Error de seguridad', 'opticavision-theme'));
    }

    $product_id = absint($_POST['product_id']);
    $user_id = get_current_user_id();

    if (!$user_id) {
        wp_send_json_error(__('Debes iniciar sesión.', 'opticavision-theme'));
    }

    // Get user wishlist
    $wishlist = get_user_meta($user_id, 'opticavision_wishlist', true);
    
    if (!is_array($wishlist)) {
        $wishlist = array();
    }

    // Remove product from wishlist
    $wishlist = array_diff($wishlist, array($product_id));
    update_user_meta($user_id, 'opticavision_wishlist', $wishlist);

    wp_send_json_success(array(
        'message' => __('Producto removido de tu lista de deseos.', 'opticavision-theme'),
        'count'   => count($wishlist)
    ));
}
add_action('wp_ajax_opticavision_remove_from_wishlist', 'opticavision_remove_from_wishlist');

/**
 * AJAX handler for getting wishlist count
 */
function opticavision_get_wishlist_count() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'opticavision_nonce')) {
        wp_die(__('Error de seguridad', 'opticavision-theme'));
    }

    $user_id = get_current_user_id();

    if (!$user_id) {
        wp_send_json_success(array('count' => 0));
    }

    $wishlist = get_user_meta($user_id, 'opticavision_wishlist', true);
    $count = is_array($wishlist) ? count($wishlist) : 0;

    wp_send_json_success(array('count' => $count));
}
add_action('wp_ajax_opticavision_get_wishlist_count', 'opticavision_get_wishlist_count');

/**
 * AJAX handler for contact form submission
 */
function opticavision_contact_form_submit() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'opticavision_nonce')) {
        wp_die(__('Error de seguridad', 'opticavision-theme'));
    }

    // Sanitize form data
    $name = sanitize_text_field($_POST['name']);
    $email = sanitize_email($_POST['email']);
    $phone = sanitize_text_field($_POST['phone']);
    $subject = sanitize_text_field($_POST['subject']);
    $message = sanitize_textarea_field($_POST['message']);

    // Validate required fields
    if (empty($name) || empty($email) || empty($message)) {
        wp_send_json_error(__('Por favor completa todos los campos requeridos.', 'opticavision-theme'));
    }

    if (!is_email($email)) {
        wp_send_json_error(__('Por favor ingresa un email válido.', 'opticavision-theme'));
    }

    // Prepare email
    $to = get_option('admin_email');
    $email_subject = '[OpticaVision] ' . $subject;
    $email_message = "Nombre: {$name}\n";
    $email_message .= "Email: {$email}\n";
    $email_message .= "Teléfono: {$phone}\n\n";
    $email_message .= "Mensaje:\n{$message}";

    $headers = array(
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
        'Reply-To: ' . $name . ' <' . $email . '>'
    );

    // Send email
    $sent = wp_mail($to, $email_subject, $email_message, $headers);

    if ($sent) {
        // Log contact form submission
        if (function_exists('opticavision_log')) {
            opticavision_log('Formulario de contacto enviado por: ' . $name . ' (' . $email . ')');
        }

        wp_send_json_success(__('Tu mensaje ha sido enviado correctamente. Te contactaremos pronto.', 'opticavision-theme'));
    } else {
        wp_send_json_error(__('Hubo un error al enviar tu mensaje. Por favor inténtalo de nuevo.', 'opticavision-theme'));
    }
}
add_action('wp_ajax_opticavision_contact_form_submit', 'opticavision_contact_form_submit');
add_action('wp_ajax_nopriv_opticavision_contact_form_submit', 'opticavision_contact_form_submit');

/**
 * AJAX handler for updating user preferences
 */
function opticavision_update_user_preferences() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'opticavision_nonce')) {
        wp_die(__('Error de seguridad', 'opticavision-theme'));
    }

    $user_id = get_current_user_id();

    if (!$user_id) {
        wp_send_json_error(__('Debes iniciar sesión.', 'opticavision-theme'));
    }

    $preferences = array();
    
    // Sanitize preferences
    if (isset($_POST['newsletter'])) {
        $preferences['newsletter'] = wp_validate_boolean($_POST['newsletter']);
    }
    
    if (isset($_POST['notifications'])) {
        $preferences['notifications'] = wp_validate_boolean($_POST['notifications']);
    }
    
    if (isset($_POST['currency'])) {
        $preferences['currency'] = sanitize_text_field($_POST['currency']);
    }

    // Update user preferences
    update_user_meta($user_id, 'opticavision_preferences', $preferences);

    wp_send_json_success(__('Preferencias actualizadas correctamente.', 'opticavision-theme'));
}
add_action('wp_ajax_opticavision_update_user_preferences', 'opticavision_update_user_preferences');

/**
 * AJAX handler for product comparison
 */
function opticavision_add_to_compare() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'opticavision_nonce')) {
        wp_die(__('Error de seguridad', 'opticavision-theme'));
    }

    $product_id = absint($_POST['product_id']);
    
    if (!$product_id) {
        wp_send_json_error(__('ID de producto inválido.', 'opticavision-theme'));
    }

    $product = wc_get_product($product_id);
    
    if (!$product) {
        wp_send_json_error(__('Producto no encontrado.', 'opticavision-theme'));
    }

    // Get compare list from session
    if (!session_id()) {
        session_start();
    }

    if (!isset($_SESSION['opticavision_compare'])) {
        $_SESSION['opticavision_compare'] = array();
    }

    $compare_list = $_SESSION['opticavision_compare'];

    // Check if product is already in compare list
    if (in_array($product_id, $compare_list)) {
        wp_send_json_error(__('El producto ya está en tu lista de comparación.', 'opticavision-theme'));
    }

    // Limit to 4 products
    if (count($compare_list) >= 4) {
        wp_send_json_error(__('Solo puedes comparar hasta 4 productos a la vez.', 'opticavision-theme'));
    }

    // Add product to compare list
    $compare_list[] = $product_id;
    $_SESSION['opticavision_compare'] = $compare_list;

    wp_send_json_success(array(
        'message' => __('Producto agregado a la comparación.', 'opticavision-theme'),
        'count'   => count($compare_list)
    ));
}
add_action('wp_ajax_opticavision_add_to_compare', 'opticavision_add_to_compare');
add_action('wp_ajax_nopriv_opticavision_add_to_compare', 'opticavision_add_to_compare');

/**
 * AJAX handler for removing product from comparison
 */
function opticavision_remove_from_compare() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'opticavision_nonce')) {
        wp_die(__('Error de seguridad', 'opticavision-theme'));
    }

    $product_id = absint($_POST['product_id']);

    // Get compare list from session
    if (!session_id()) {
        session_start();
    }

    if (!isset($_SESSION['opticavision_compare'])) {
        $_SESSION['opticavision_compare'] = array();
    }

    $compare_list = $_SESSION['opticavision_compare'];

    // Remove product from compare list
    $compare_list = array_diff($compare_list, array($product_id));
    $_SESSION['opticavision_compare'] = array_values($compare_list);

    wp_send_json_success(array(
        'message' => __('Producto removido de la comparación.', 'opticavision-theme'),
        'count'   => count($compare_list)
    ));
}
add_action('wp_ajax_opticavision_remove_from_compare', 'opticavision_remove_from_compare');
add_action('wp_ajax_nopriv_opticavision_remove_from_compare', 'opticavision_remove_from_compare');

/**
 * AJAX handler for getting compare count
 */
function opticavision_get_compare_count() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'opticavision_nonce')) {
        wp_die(__('Error de seguridad', 'opticavision-theme'));
    }

    // Get compare list from session
    if (!session_id()) {
        session_start();
    }

    $count = isset($_SESSION['opticavision_compare']) ? count($_SESSION['opticavision_compare']) : 0;

    wp_send_json_success(array('count' => $count));
}
add_action('wp_ajax_opticavision_get_compare_count', 'opticavision_get_compare_count');
add_action('wp_ajax_nopriv_opticavision_get_compare_count', 'opticavision_get_compare_count');

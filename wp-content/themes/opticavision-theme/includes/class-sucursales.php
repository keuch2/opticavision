<?php
/**
 * Sucursales Custom Post Type
 *
 * @package OpticaVision_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

class OpticaVision_Sucursales {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
        add_filter('manage_sucursal_posts_columns', array($this, 'set_custom_columns'));
        add_action('manage_sucursal_posts_custom_column', array($this, 'custom_column_content'), 10, 2);
    }

    /**
     * Register Sucursales Custom Post Type
     */
    public function register_post_type() {
        $labels = array(
            'name'                  => _x('Sucursales', 'Post Type General Name', 'opticavision-theme'),
            'singular_name'         => _x('Sucursal', 'Post Type Singular Name', 'opticavision-theme'),
            'menu_name'             => __('Sucursales', 'opticavision-theme'),
            'name_admin_bar'        => __('Sucursal', 'opticavision-theme'),
            'archives'              => __('Archivo de Sucursales', 'opticavision-theme'),
            'attributes'            => __('Atributos de Sucursal', 'opticavision-theme'),
            'parent_item_colon'     => __('Sucursal Padre:', 'opticavision-theme'),
            'all_items'             => __('Todas las Sucursales', 'opticavision-theme'),
            'add_new_item'          => __('Agregar Nueva Sucursal', 'opticavision-theme'),
            'add_new'               => __('Agregar Nueva', 'opticavision-theme'),
            'new_item'              => __('Nueva Sucursal', 'opticavision-theme'),
            'edit_item'             => __('Editar Sucursal', 'opticavision-theme'),
            'update_item'           => __('Actualizar Sucursal', 'opticavision-theme'),
            'view_item'             => __('Ver Sucursal', 'opticavision-theme'),
            'view_items'            => __('Ver Sucursales', 'opticavision-theme'),
            'search_items'          => __('Buscar Sucursal', 'opticavision-theme'),
            'not_found'             => __('No encontrado', 'opticavision-theme'),
            'not_found_in_trash'    => __('No encontrado en papelera', 'opticavision-theme'),
            'featured_image'        => __('Imagen de la Sucursal', 'opticavision-theme'),
            'set_featured_image'    => __('Establecer imagen de sucursal', 'opticavision-theme'),
            'remove_featured_image' => __('Remover imagen de sucursal', 'opticavision-theme'),
            'use_featured_image'    => __('Usar como imagen de sucursal', 'opticavision-theme'),
            'insert_into_item'      => __('Insertar en sucursal', 'opticavision-theme'),
            'uploaded_to_this_item' => __('Subido a esta sucursal', 'opticavision-theme'),
            'items_list'            => __('Lista de sucursales', 'opticavision-theme'),
            'items_list_navigation' => __('Navegación de lista de sucursales', 'opticavision-theme'),
            'filter_items_list'     => __('Filtrar lista de sucursales', 'opticavision-theme'),
        );

        $args = array(
            'label'                 => __('Sucursal', 'opticavision-theme'),
            'description'           => __('Sucursales de OpticaVision', 'opticavision-theme'),
            'labels'                => $labels,
            'supports'              => array('title', 'editor', 'thumbnail', 'excerpt'),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 25,
            'menu_icon'             => 'dashicons-store',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => false,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
            'show_in_rest'          => true,
        );

        register_post_type('sucursal', $args);
    }

    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'sucursal_details',
            __('Detalles de la Sucursal', 'opticavision-theme'),
            array($this, 'meta_box_callback'),
            'sucursal',
            'normal',
            'high'
        );
    }

    /**
     * Meta box callback
     */
    public function meta_box_callback($post) {
        wp_nonce_field('sucursal_meta_box', 'sucursal_meta_box_nonce');

        $address = get_post_meta($post->ID, '_sucursal_address', true);
        $phone = get_post_meta($post->ID, '_sucursal_phone', true);
        $schedule = get_post_meta($post->ID, '_sucursal_schedule', true);
        $maps_url = get_post_meta($post->ID, '_sucursal_maps_url', true);
        $city = get_post_meta($post->ID, '_sucursal_city', true);
        $order = get_post_meta($post->ID, '_sucursal_order', true);

        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="sucursal_city"><?php _e('Ciudad', 'opticavision-theme'); ?></label>
                </th>
                <td>
                    <input type="text" id="sucursal_city" name="sucursal_city" value="<?php echo esc_attr($city); ?>" class="regular-text" />
                    <p class="description"><?php _e('Nombre de la ciudad (ej: New York, Los Angeles)', 'opticavision-theme'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="sucursal_address"><?php _e('Dirección', 'opticavision-theme'); ?></label>
                </th>
                <td>
                    <textarea id="sucursal_address" name="sucursal_address" rows="3" class="large-text"><?php echo esc_textarea($address); ?></textarea>
                    <p class="description"><?php _e('Dirección completa de la sucursal', 'opticavision-theme'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="sucursal_phone"><?php _e('Teléfono', 'opticavision-theme'); ?></label>
                </th>
                <td>
                    <input type="text" id="sucursal_phone" name="sucursal_phone" value="<?php echo esc_attr($phone); ?>" class="regular-text" />
                    <p class="description"><?php _e('Número de teléfono de la sucursal', 'opticavision-theme'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="sucursal_schedule"><?php _e('Horarios', 'opticavision-theme'); ?></label>
                </th>
                <td>
                    <input type="text" id="sucursal_schedule" name="sucursal_schedule" value="<?php echo esc_attr($schedule); ?>" class="regular-text" />
                    <p class="description"><?php _e('Horarios de atención (ej: Mon-Sat: 10am-8pm)', 'opticavision-theme'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="sucursal_maps_url"><?php _e('URL de Google Maps', 'opticavision-theme'); ?></label>
                </th>
                <td>
                    <input type="url" id="sucursal_maps_url" name="sucursal_maps_url" value="<?php echo esc_attr($maps_url); ?>" class="large-text" />
                    <p class="description"><?php _e('URL de Google Maps para direcciones', 'opticavision-theme'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="sucursal_order"><?php _e('Orden de visualización', 'opticavision-theme'); ?></label>
                </th>
                <td>
                    <input type="number" id="sucursal_order" name="sucursal_order" value="<?php echo esc_attr($order ? $order : 0); ?>" class="small-text" min="0" />
                    <p class="description"><?php _e('Orden en que aparece en la página (0 = primero)', 'opticavision-theme'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Save meta boxes
     */
    public function save_meta_boxes($post_id) {
        if (!isset($_POST['sucursal_meta_box_nonce'])) {
            return;
        }

        if (!wp_verify_nonce($_POST['sucursal_meta_box_nonce'], 'sucursal_meta_box')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (isset($_POST['post_type']) && 'sucursal' == $_POST['post_type']) {
            if (!current_user_can('edit_page', $post_id)) {
                return;
            }
        } else {
            if (!current_user_can('edit_post', $post_id)) {
                return;
            }
        }

        $fields = array('sucursal_city', 'sucursal_address', 'sucursal_phone', 'sucursal_schedule', 'sucursal_maps_url', 'sucursal_order');

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                $value = sanitize_text_field($_POST[$field]);
                if ($field === 'sucursal_address') {
                    $value = sanitize_textarea_field($_POST[$field]);
                } elseif ($field === 'sucursal_maps_url') {
                    $value = esc_url_raw($_POST[$field]);
                } elseif ($field === 'sucursal_order') {
                    $value = absint($_POST[$field]);
                }
                update_post_meta($post_id, '_' . $field, $value);
            }
        }
    }

    /**
     * Set custom columns for admin list
     */
    public function set_custom_columns($columns) {
        unset($columns['date']);
        $columns['city'] = __('Ciudad', 'opticavision-theme');
        $columns['address'] = __('Dirección', 'opticavision-theme');
        $columns['phone'] = __('Teléfono', 'opticavision-theme');
        $columns['order'] = __('Orden', 'opticavision-theme');
        $columns['date'] = __('Fecha', 'opticavision-theme');
        return $columns;
    }

    /**
     * Custom column content
     */
    public function custom_column_content($column, $post_id) {
        switch ($column) {
            case 'city':
                echo esc_html(get_post_meta($post_id, '_sucursal_city', true));
                break;
            case 'address':
                echo esc_html(get_post_meta($post_id, '_sucursal_address', true));
                break;
            case 'phone':
                echo esc_html(get_post_meta($post_id, '_sucursal_phone', true));
                break;
            case 'order':
                echo esc_html(get_post_meta($post_id, '_sucursal_order', true));
                break;
        }
    }

    /**
     * Get all sucursales ordered by custom order
     */
    public static function get_sucursales() {
        $args = array(
            'post_type' => 'sucursal',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_key' => '_sucursal_order',
            'orderby' => 'meta_value_num',
            'order' => 'ASC'
        );

        return get_posts($args);
    }
}

// Initialize the class
new OpticaVision_Sucursales();

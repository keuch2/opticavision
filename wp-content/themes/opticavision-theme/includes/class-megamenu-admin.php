<?php
/**
 * OpticaVision Megamenu Admin
 * Agrega campos personalizados al administrador de menús
 *
 * @package OpticaVision_Theme
 */

class OpticaVision_Megamenu_Admin {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_nav_menu_item_custom_fields', array($this, 'add_custom_fields'), 10, 4);
        add_action('wp_update_nav_menu_item', array($this, 'save_custom_fields'), 10, 3);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * Agregar campos personalizados al editor de menús
     */
    public function add_custom_fields($item_id, $item, $depth, $args) {
        $megamenu_enabled = get_post_meta($item_id, '_menu_item_megamenu', true);
        $megamenu_columns = get_post_meta($item_id, '_menu_item_megamenu_columns', true) ?: 4;
        $megamenu_width = get_post_meta($item_id, '_menu_item_megamenu_width', true) ?: 'auto';
        $megamenu_image = get_post_meta($item_id, '_menu_item_megamenu_image', true);
        $megamenu_description = get_post_meta($item_id, '_menu_item_megamenu_description', true);
        ?>
        <div class="opticavision-megamenu-settings" style="margin: 10px 0; padding: 10px; border: 1px solid #ddd; background: #f9f9f9;">
            <h4 style="margin: 0 0 10px 0;"><?php _e('Configuración Megamenú', 'opticavision-theme'); ?></h4>
            
            <!-- Habilitar Megamenú -->
            <p class="field-megamenu-enable description description-wide">
                <label for="edit-menu-item-megamenu-<?php echo $item_id; ?>">
                    <input type="checkbox" 
                           id="edit-menu-item-megamenu-<?php echo $item_id; ?>" 
                           name="menu-item-megamenu[<?php echo $item_id; ?>]" 
                           value="1" 
                           <?php checked($megamenu_enabled, 1); ?> />
                    <?php _e('Habilitar Megamenú', 'opticavision-theme'); ?>
                </label>
            </p>

            <div class="megamenu-options" style="<?php echo $megamenu_enabled ? '' : 'display: none;'; ?>">
                <!-- Número de Columnas -->
                <p class="field-megamenu-columns description description-thin">
                    <label for="edit-menu-item-megamenu-columns-<?php echo $item_id; ?>">
                        <?php _e('Columnas', 'opticavision-theme'); ?><br />
                        <select id="edit-menu-item-megamenu-columns-<?php echo $item_id; ?>" 
                                name="menu-item-megamenu-columns[<?php echo $item_id; ?>]">
                            <option value="2" <?php selected($megamenu_columns, 2); ?>>2</option>
                            <option value="3" <?php selected($megamenu_columns, 3); ?>>3</option>
                            <option value="4" <?php selected($megamenu_columns, 4); ?>>4</option>
                            <option value="5" <?php selected($megamenu_columns, 5); ?>>5</option>
                            <option value="6" <?php selected($megamenu_columns, 6); ?>>6</option>
                        </select>
                    </label>
                </p>

                <!-- Ancho del Megamenú -->
                <p class="field-megamenu-width description description-thin">
                    <label for="edit-menu-item-megamenu-width-<?php echo $item_id; ?>">
                        <?php _e('Ancho', 'opticavision-theme'); ?><br />
                        <select id="edit-menu-item-megamenu-width-<?php echo $item_id; ?>" 
                                name="menu-item-megamenu-width[<?php echo $item_id; ?>]">
                            <option value="auto" <?php selected($megamenu_width, 'auto'); ?>><?php _e('Automático', 'opticavision-theme'); ?></option>
                            <option value="container" <?php selected($megamenu_width, 'container'); ?>><?php _e('Ancho del contenedor', 'opticavision-theme'); ?></option>
                            <option value="full" <?php selected($megamenu_width, 'full'); ?>><?php _e('Ancho completo', 'opticavision-theme'); ?></option>
                        </select>
                    </label>
                </p>

                <!-- Imagen del Item -->
                <p class="field-megamenu-image description description-wide">
                    <label for="edit-menu-item-megamenu-image-<?php echo $item_id; ?>">
                        <?php _e('Imagen del Item (URL)', 'opticavision-theme'); ?><br />
                        <input type="url" 
                               id="edit-menu-item-megamenu-image-<?php echo $item_id; ?>" 
                               name="menu-item-megamenu-image[<?php echo $item_id; ?>]" 
                               value="<?php echo esc_attr($megamenu_image); ?>" 
                               class="widefat" />
                        <small><?php _e('URL de imagen para mostrar en este item del megamenú', 'opticavision-theme'); ?></small>
                    </label>
                </p>

                <!-- Descripción del Item -->
                <p class="field-megamenu-description description description-wide">
                    <label for="edit-menu-item-megamenu-description-<?php echo $item_id; ?>">
                        <?php _e('Descripción', 'opticavision-theme'); ?><br />
                        <textarea id="edit-menu-item-megamenu-description-<?php echo $item_id; ?>" 
                                  name="menu-item-megamenu-description[<?php echo $item_id; ?>]" 
                                  class="widefat" 
                                  rows="3"><?php echo esc_textarea($megamenu_description); ?></textarea>
                        <small><?php _e('Descripción opcional para mostrar debajo del título', 'opticavision-theme'); ?></small>
                    </label>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Guardar campos personalizados
     */
    public function save_custom_fields($menu_id, $menu_item_db_id, $args) {
        // Megamenú habilitado
        $megamenu_enabled = isset($_POST['menu-item-megamenu'][$menu_item_db_id]) ? 1 : 0;
        update_post_meta($menu_item_db_id, '_menu_item_megamenu', $megamenu_enabled);

        // Número de columnas
        if (isset($_POST['menu-item-megamenu-columns'][$menu_item_db_id])) {
            $columns = absint($_POST['menu-item-megamenu-columns'][$menu_item_db_id]);
            update_post_meta($menu_item_db_id, '_menu_item_megamenu_columns', $columns);
        }

        // Ancho del megamenú
        if (isset($_POST['menu-item-megamenu-width'][$menu_item_db_id])) {
            $width = sanitize_text_field($_POST['menu-item-megamenu-width'][$menu_item_db_id]);
            update_post_meta($menu_item_db_id, '_menu_item_megamenu_width', $width);
        }

        // Imagen
        if (isset($_POST['menu-item-megamenu-image'][$menu_item_db_id])) {
            $image = esc_url_raw($_POST['menu-item-megamenu-image'][$menu_item_db_id]);
            update_post_meta($menu_item_db_id, '_menu_item_megamenu_image', $image);
        }

        // Descripción
        if (isset($_POST['menu-item-megamenu-description'][$menu_item_db_id])) {
            $description = wp_kses_post($_POST['menu-item-megamenu-description'][$menu_item_db_id]);
            update_post_meta($menu_item_db_id, '_menu_item_megamenu_description', $description);
        }
    }

    /**
     * Encolar scripts del admin
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'nav-menus.php') {
            return;
        }

        wp_add_inline_script('nav-menu', '
            jQuery(document).ready(function($) {
                // Toggle megamenu options
                $(document).on("change", "input[name*=\'menu-item-megamenu\']", function() {
                    var $checkbox = $(this);
                    var $options = $checkbox.closest(".opticavision-megamenu-settings").find(".megamenu-options");
                    
                    if ($checkbox.is(":checked")) {
                        $options.slideDown();
                    } else {
                        $options.slideUp();
                    }
                });

                // Handle new menu items
                $(document).on("click", ".item-edit", function() {
                    setTimeout(function() {
                        $("input[name*=\'menu-item-megamenu\']:checked").each(function() {
                            $(this).closest(".opticavision-megamenu-settings").find(".megamenu-options").show();
                        });
                    }, 100);
                });
            });
        ');
    }
}

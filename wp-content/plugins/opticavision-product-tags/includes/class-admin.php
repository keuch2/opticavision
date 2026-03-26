<?php
/**
 * Administración de etiquetas/badges de productos.
 *
 * @package OpticaVision_Product_Tags
 */

defined('ABSPATH') || exit;

class OV_Tags_Admin {

    /** Clave de la opción donde se guarda el catálogo de badges. */
    const OPTION_KEY = 'ov_product_badges';

    public function __construct() {
        add_action('admin_menu',                            array($this, 'register_menu'));
        add_action('admin_init',                            array($this, 'handle_form'));
        add_action('add_meta_boxes',                        array($this, 'register_metabox'));
        add_action('save_post_product',                     array($this, 'save_product_badge'), 10, 2);
        add_action('woocommerce_product_cat_add_form_fields',  array($this, 'category_add_fields'));
        add_action('woocommerce_product_cat_edit_form_fields', array($this, 'category_edit_fields'), 10, 2);
        add_action('created_product_cat',                   array($this, 'save_category_badge'));
        add_action('edited_product_cat',                    array($this, 'save_category_badge'));
        add_action('admin_enqueue_scripts',                 array($this, 'enqueue_assets'));
    }

    /* ------------------------------------------------------------------
     * Menú en WooCommerce
     * ------------------------------------------------------------------ */

    public function register_menu() {
        add_submenu_page(
            'woocommerce',
            __('Etiquetas de Productos', 'opticavision-product-tags'),
            __('Etiquetas', 'opticavision-product-tags'),
            'manage_woocommerce',
            'ov-product-tags',
            array($this, 'render_page')
        );
    }

    /* ------------------------------------------------------------------
     * Assets
     * ------------------------------------------------------------------ */

    public function enqueue_assets($hook) {
        $allowed_hooks = array('woocommerce_page_ov-product-tags', 'post.php', 'post-new.php', 'edit-tags.php', 'term.php');
        if (!in_array($hook, $allowed_hooks, true)) return;

        wp_enqueue_style(
            'ov-tags-admin',
            OV_TAGS_URI . 'assets/css/product-tags.css',
            array(),
            OV_TAGS_VERSION
        );
        wp_enqueue_script(
            'ov-tags-admin-js',
            OV_TAGS_URI . 'assets/js/product-tags-admin.js',
            array('jquery', 'wp-color-picker'),
            OV_TAGS_VERSION,
            true
        );
        wp_enqueue_style('wp-color-picker');
    }

    /* ------------------------------------------------------------------
     * Página de gestión de badges
     * ------------------------------------------------------------------ */

    public function render_page() {
        $badges = $this->get_badges();
        $edit   = isset($_GET['edit_id']) ? absint($_GET['edit_id']) : null;
        $editing = null;
        if ($edit !== null && isset($badges[$edit])) {
            $editing = $badges[$edit];
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Catálogo de Etiquetas de Productos', 'opticavision-product-tags'); ?></h1>

            <?php if (isset($_GET['saved'])): ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Cambios guardados.', 'opticavision-product-tags'); ?></p></div>
            <?php endif; ?>
            <?php if (isset($_GET['deleted'])): ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Etiqueta eliminada.', 'opticavision-product-tags'); ?></p></div>
            <?php endif; ?>

            <div style="display:flex;gap:2rem;align-items:flex-start;margin-top:1rem;">
                <!-- Formulario -->
                <div style="flex:0 0 340px;">
                    <div class="postbox">
                        <h2 class="hndle" style="padding:12px 16px;">
                            <?php echo $editing ? esc_html__('Editar Etiqueta', 'opticavision-product-tags') : esc_html__('Nueva Etiqueta', 'opticavision-product-tags'); ?>
                        </h2>
                        <div class="inside">
                            <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=ov-product-tags')); ?>">
                                <?php wp_nonce_field('ov_tags_save', 'ov_tags_nonce'); ?>
                                <?php if ($editing !== null): ?>
                                    <input type="hidden" name="badge_id" value="<?php echo esc_attr($edit); ?>">
                                <?php endif; ?>
                                <table class="form-table" style="margin:0;">
                                    <tr>
                                        <th><label for="badge_text"><?php esc_html_e('Texto', 'opticavision-product-tags'); ?></label></th>
                                        <td><input type="text" id="badge_text" name="badge_text" class="regular-text"
                                                   value="<?php echo $editing ? esc_attr($editing['text']) : ''; ?>" required></td>
                                    </tr>
                                    <tr>
                                        <th><label for="badge_bg"><?php esc_html_e('Color fondo', 'opticavision-product-tags'); ?></label></th>
                                        <td><input type="text" id="badge_bg" name="badge_bg" class="ov-color-picker"
                                                   value="<?php echo $editing ? esc_attr($editing['bg_color']) : '#27ae60'; ?>"></td>
                                    </tr>
                                    <tr>
                                        <th><label for="badge_color"><?php esc_html_e('Color texto', 'opticavision-product-tags'); ?></label></th>
                                        <td><input type="text" id="badge_color" name="badge_color" class="ov-color-picker"
                                                   value="<?php echo $editing ? esc_attr($editing['text_color']) : '#ffffff'; ?>"></td>
                                    </tr>
                                </table>
                                <p style="margin-top:1rem;">
                                    <input type="submit" name="ov_save_badge" class="button button-primary"
                                           value="<?php echo $editing ? esc_attr__('Actualizar', 'opticavision-product-tags') : esc_attr__('Agregar Etiqueta', 'opticavision-product-tags'); ?>">
                                    <?php if ($editing): ?>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=ov-product-tags')); ?>" class="button" style="margin-left:8px;">
                                            <?php esc_html_e('Cancelar', 'opticavision-product-tags'); ?>
                                        </a>
                                    <?php endif; ?>
                                </p>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Listado de badges -->
                <div style="flex:1;">
                    <?php if (!empty($badges)): ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Vista previa', 'opticavision-product-tags'); ?></th>
                                <th><?php esc_html_e('Texto', 'opticavision-product-tags'); ?></th>
                                <th><?php esc_html_e('Fondo', 'opticavision-product-tags'); ?></th>
                                <th><?php esc_html_e('Texto color', 'opticavision-product-tags'); ?></th>
                                <th><?php esc_html_e('Acciones', 'opticavision-product-tags'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($badges as $id => $badge): ?>
                            <tr>
                                <td>
                                    <span class="ov-badge-preview" style="background:<?php echo esc_attr($badge['bg_color']); ?>;color:<?php echo esc_attr($badge['text_color']); ?>;">
                                        <?php echo esc_html($badge['text']); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($badge['text']); ?></td>
                                <td><?php echo esc_html($badge['bg_color']); ?></td>
                                <td><?php echo esc_html($badge['text_color']); ?></td>
                                <td>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=ov-product-tags&edit_id=' . $id)); ?>">
                                        <?php esc_html_e('Editar', 'opticavision-product-tags'); ?>
                                    </a>
                                    &nbsp;|&nbsp;
                                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=ov-product-tags&delete_id=' . $id), 'ov_delete_badge_' . $id)); ?>"
                                       onclick="return confirm('<?php esc_attr_e('¿Eliminar esta etiqueta?', 'opticavision-product-tags'); ?>');"
                                       style="color:#a00;">
                                        <?php esc_html_e('Eliminar', 'opticavision-product-tags'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                        <p><?php esc_html_e('No hay etiquetas creadas todavía. Agrega una usando el formulario.', 'opticavision-product-tags'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    /* ------------------------------------------------------------------
     * Procesar formulario (guardar / eliminar)
     * ------------------------------------------------------------------ */

    public function handle_form() {
        // Eliminar
        if (isset($_GET['delete_id'], $_GET['_wpnonce'])) {
            $id = absint($_GET['delete_id']);
            if (wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'ov_delete_badge_' . $id)) {
                $badges = $this->get_badges();
                unset($badges[$id]);
                update_option(self::OPTION_KEY, $badges);
                wp_redirect(admin_url('admin.php?page=ov-product-tags&deleted=1'));
                exit;
            }
        }

        // Guardar / actualizar
        if (!isset($_POST['ov_save_badge'], $_POST['ov_tags_nonce'])) return;
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ov_tags_nonce'])), 'ov_tags_save')) return;
        if (!current_user_can('manage_woocommerce')) return;

        $badges    = $this->get_badges();
        $text      = sanitize_text_field(wp_unslash($_POST['badge_text'] ?? ''));
        $bg_color  = sanitize_hex_color(wp_unslash($_POST['badge_bg'] ?? '#27ae60')) ?: '#27ae60';
        $txt_color = sanitize_hex_color(wp_unslash($_POST['badge_color'] ?? '#ffffff')) ?: '#ffffff';

        if (empty($text)) return;

        $entry = array('text' => $text, 'bg_color' => $bg_color, 'text_color' => $txt_color);

        if (isset($_POST['badge_id'])) {
            $id = absint($_POST['badge_id']);
            if (isset($badges[$id])) {
                $badges[$id] = $entry;
            }
        } else {
            $badges[] = $entry;
        }

        update_option(self::OPTION_KEY, $badges);
        wp_redirect(admin_url('admin.php?page=ov-product-tags&saved=1'));
        exit;
    }

    /* ------------------------------------------------------------------
     * Metabox en producto
     * ------------------------------------------------------------------ */

    public function register_metabox() {
        add_meta_box(
            'ov_product_badge',
            __('Etiqueta/Badge del producto', 'opticavision-product-tags'),
            array($this, 'render_metabox'),
            'product',
            'side',
            'default'
        );
    }

    public function render_metabox($post) {
        wp_nonce_field('ov_product_badge_save', 'ov_product_badge_nonce');
        $saved  = get_post_meta($post->ID, '_ov_badge_id', true);
        $badges = $this->get_badges();
        ?>
        <p>
            <label for="ov_badge_id"><?php esc_html_e('Asignar etiqueta:', 'opticavision-product-tags'); ?></label><br>
            <select id="ov_badge_id" name="ov_badge_id" style="width:100%;margin-top:4px;">
                <option value=""><?php esc_html_e('— Sin etiqueta —', 'opticavision-product-tags'); ?></option>
                <?php foreach ($badges as $id => $badge): ?>
                    <option value="<?php echo esc_attr($id); ?>" <?php selected($saved, (string) $id); ?>
                        data-bg="<?php echo esc_attr($badge['bg_color']); ?>"
                        data-color="<?php echo esc_attr($badge['text_color']); ?>">
                        <?php echo esc_html($badge['text']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <?php
        if (!empty($badges)) {
            echo '<p class="description">' . esc_html__('Esta etiqueta tiene prioridad sobre la de la categoría.', 'opticavision-product-tags') . '</p>';
        } else {
            echo '<p class="description"><a href="' . esc_url(admin_url('admin.php?page=ov-product-tags')) . '">' . esc_html__('Crear etiquetas', 'opticavision-product-tags') . '</a></p>';
        }
    }

    public function save_product_badge($post_id, $post) {
        if (!isset($_POST['ov_product_badge_nonce'])) return;
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ov_product_badge_nonce'])), 'ov_product_badge_save')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $value = isset($_POST['ov_badge_id']) ? sanitize_text_field(wp_unslash($_POST['ov_badge_id'])) : '';
        if ($value === '') {
            delete_post_meta($post_id, '_ov_badge_id');
        } else {
            update_post_meta($post_id, '_ov_badge_id', $value);
        }
    }

    /* ------------------------------------------------------------------
     * Campos en taxonomía de categoría WooCommerce
     * ------------------------------------------------------------------ */

    public function category_add_fields() {
        $badges = $this->get_badges();
        if (empty($badges)) return;
        ?>
        <div class="form-field">
            <label for="ov_cat_badge_id"><?php esc_html_e('Etiqueta/Badge de la categoría', 'opticavision-product-tags'); ?></label>
            <select id="ov_cat_badge_id" name="ov_cat_badge_id">
                <option value=""><?php esc_html_e('— Sin etiqueta —', 'opticavision-product-tags'); ?></option>
                <?php foreach ($badges as $id => $badge): ?>
                    <option value="<?php echo esc_attr($id); ?>"><?php echo esc_html($badge['text']); ?></option>
                <?php endforeach; ?>
            </select>
            <p><?php esc_html_e('Los productos de esta categoría heredarán esta etiqueta si no tienen una propia.', 'opticavision-product-tags'); ?></p>
        </div>
        <?php
    }

    public function category_edit_fields($term) {
        $badges = $this->get_badges();
        $saved  = get_term_meta($term->term_id, 'ov_badge_id', true);
        ?>
        <tr class="form-field">
            <th><label for="ov_cat_badge_id"><?php esc_html_e('Etiqueta/Badge', 'opticavision-product-tags'); ?></label></th>
            <td>
                <select id="ov_cat_badge_id" name="ov_cat_badge_id">
                    <option value=""><?php esc_html_e('— Sin etiqueta —', 'opticavision-product-tags'); ?></option>
                    <?php foreach ($badges as $id => $badge): ?>
                        <option value="<?php echo esc_attr($id); ?>" <?php selected($saved, (string) $id); ?>>
                            <?php echo esc_html($badge['text']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php esc_html_e('Los productos de esta categoría heredarán esta etiqueta si no tienen una propia.', 'opticavision-product-tags'); ?></p>
            </td>
        </tr>
        <?php
    }

    public function save_category_badge($term_id) {
        if (!isset($_POST['ov_cat_badge_id'])) return;
        $value = sanitize_text_field(wp_unslash($_POST['ov_cat_badge_id']));
        if ($value === '') {
            delete_term_meta($term_id, 'ov_badge_id');
        } else {
            update_term_meta($term_id, 'ov_badge_id', $value);
        }
    }

    /* ------------------------------------------------------------------
     * Helper: obtener catálogo de badges
     * ------------------------------------------------------------------ */

    public static function get_badges() {
        $badges = get_option(self::OPTION_KEY, array());
        return is_array($badges) ? $badges : array();
    }
}

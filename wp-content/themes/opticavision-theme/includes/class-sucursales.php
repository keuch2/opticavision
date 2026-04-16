<?php
/**
 * Sucursales - Meta box repeater en la página
 *
 * @package OpticaVision_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

class OpticaVision_Sucursales {

    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post_page', array($this, 'save_meta'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    /**
     * Registra el meta box en páginas con template page-sucursales.php
     */
    public function add_meta_box() {
        add_meta_box(
            'sucursales_items',
            __('Sucursales', 'opticavision-theme'),
            array($this, 'render_meta_box'),
            'page',
            'normal',
            'high'
        );
    }

    /**
     * Encola scripts/estilos del admin en el editor de páginas
     */
    public function enqueue_admin_assets($hook) {
        global $post;
        if (!in_array($hook, array('post.php', 'post-new.php'), true)) {
            return;
        }
        if (!$post || $post->post_type !== 'page') {
            return;
        }
        wp_enqueue_media();
        wp_enqueue_script('jquery-ui-sortable');
        wp_add_inline_style('wp-admin', $this->admin_css());
        wp_add_inline_script('jquery-ui-sortable', $this->admin_js());
    }

    /**
     * Renderiza el meta box repeater
     */
    public function render_meta_box($post) {
        wp_nonce_field('sucursales_save', 'sucursales_nonce');

        $items = get_post_meta($post->ID, '_sucursales_items', true);
        if (!is_array($items)) {
            $items = array();
        }
        ?>
        <div id="sucursales-repeater">
            <?php foreach ($items as $index => $item) : ?>
                <?php $this->render_row($index, $item); ?>
            <?php endforeach; ?>
        </div>

        <div id="sucursal-row-template" style="display:none;">
            <?php $this->render_row('__IDX__', array()); ?>
        </div>

        <p>
            <button type="button" id="sucursales-add-row" class="button button-primary">
                <?php esc_html_e('+ Agregar Sucursal', 'opticavision-theme'); ?>
            </button>
        </p>

        <?php $this->render_delivery_section($post); ?>
        <?php
    }

    /**
     * Renderiza la sección de Servicio de Delivery (fuera del repeater)
     */
    private function render_delivery_section($post) {
        $delivery   = get_post_meta($post->ID, '_sucursales_delivery', true);
        if (!is_array($delivery)) {
            $delivery = array();
        }
        $dir      = isset($delivery['direccion'])   ? $delivery['direccion']   : '';
        $tel      = isset($delivery['telefono'])    ? $delivery['telefono']    : '';
        $horario  = isset($delivery['horario'])     ? $delivery['horario']     : '';
        $wa_num   = isset($delivery['whatsapp'])    ? $delivery['whatsapp']    : '';
        $wa_msg   = isset($delivery['wa_mensaje'])  ? $delivery['wa_mensaje']  : '';
        $img_id   = isset($delivery['imagen_id'])   ? intval($delivery['imagen_id']) : 0;
        $img_src  = $img_id ? wp_get_attachment_image_url($img_id, 'thumbnail') : '';
        ?>
        <hr style="margin: 20px 0;" />
        <h3 style="margin-bottom:12px;">🚚 <?php esc_html_e('Servicio de Delivery', 'opticavision-theme'); ?></h3>
        <table class="form-table sucursal-form-table">
            <tr>
                <th><label><?php esc_html_e('Imagen', 'opticavision-theme'); ?></label></th>
                <td>
                    <div class="sucursal-image-wrap" id="delivery-image-wrap">
                        <div class="sucursal-image-preview">
                            <?php if ($img_src) : ?>
                                <img src="<?php echo esc_url($img_src); ?>" alt="" />
                            <?php endif; ?>
                        </div>
                        <input type="hidden" name="sucursales_delivery[imagen_id]" id="delivery-imagen-id" value="<?php echo esc_attr($img_id ?: ''); ?>" />
                        <button type="button" class="button" id="delivery-select-image"><?php esc_html_e('Seleccionar imagen', 'opticavision-theme'); ?></button>
                        <button type="button" class="button" id="delivery-remove-image"<?php echo $img_id ? '' : ' style="display:none"'; ?>><?php esc_html_e('Quitar', 'opticavision-theme'); ?></button>
                    </div>
                </td>
            </tr>
            <tr>
                <th><label><?php esc_html_e('Dirección', 'opticavision-theme'); ?></label></th>
                <td>
                    <input type="text" name="sucursales_delivery[direccion]" value="<?php echo esc_attr($dir); ?>" class="regular-text"
                           placeholder="<?php esc_attr_e('Ej: Palma 764 c/ Ayolas', 'opticavision-theme'); ?>" />
                </td>
            </tr>
            <tr>
                <th><label><?php esc_html_e('Teléfono', 'opticavision-theme'); ?></label></th>
                <td>
                    <input type="text" name="sucursales_delivery[telefono]" value="<?php echo esc_attr($tel); ?>" class="regular-text"
                           placeholder="<?php esc_attr_e('Ej: 0982-506 314', 'opticavision-theme'); ?>" />
                </td>
            </tr>
            <tr>
                <th><label><?php esc_html_e('Horario', 'opticavision-theme'); ?></label></th>
                <td>
                    <input type="text" name="sucursales_delivery[horario]" value="<?php echo esc_attr($horario); ?>" class="large-text"
                           placeholder="<?php esc_attr_e('Ej: Lun-Vie: 8:00-18:00, Sáb: 8:00-15:00', 'opticavision-theme'); ?>" />
                </td>
            </tr>
            <tr>
                <th><label><?php esc_html_e('Número WhatsApp', 'opticavision-theme'); ?></label></th>
                <td>
                    <input type="text" name="sucursales_delivery[whatsapp]" value="<?php echo esc_attr($wa_num); ?>" class="regular-text"
                           placeholder="<?php esc_attr_e('Ej: 595982506314 (sin + ni espacios)', 'opticavision-theme'); ?>" />
                    <p class="description"><?php esc_html_e('Código de país + número, sin espacios. Ej: 595982506314', 'opticavision-theme'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label><?php esc_html_e('Mensaje WhatsApp', 'opticavision-theme'); ?></label></th>
                <td>
                    <input type="text" name="sucursales_delivery[wa_mensaje]" value="<?php echo esc_attr($wa_msg); ?>" class="large-text"
                           placeholder="<?php esc_attr_e('Ej: Hola! Me gustaría solicitar un delivery.', 'opticavision-theme'); ?>" />
                    <p class="description"><?php esc_html_e('Texto pre-cargado al abrir WhatsApp (opcional).', 'opticavision-theme'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Renderiza una fila del repeater
     */
    private function render_row($index, $item) {
        $nombre    = isset($item['nombre'])    ? $item['nombre']    : '';
        $direccion = isset($item['direccion']) ? $item['direccion'] : '';
        $tel1      = isset($item['telefono1']) ? $item['telefono1'] : '';
        $tel2      = isset($item['telefono2']) ? $item['telefono2'] : '';
        $horario   = isset($item['horario'])   ? $item['horario']   : '';
        $maps_url  = isset($item['maps_url'])  ? $item['maps_url']  : '';
        $img_id    = isset($item['imagen_id']) ? intval($item['imagen_id']) : 0;
        $img_src   = $img_id ? wp_get_attachment_image_url($img_id, 'thumbnail') : '';
        $row_num   = is_numeric($index) ? intval($index) + 1 : '?';
        $prefix    = "sucursales_items[{$index}]";
        ?>
        <div class="sucursal-row">
            <div class="sucursal-row-header">
                <span class="sucursal-drag-handle dashicons dashicons-menu" title="<?php esc_attr_e('Arrastrar para ordenar', 'opticavision-theme'); ?>"></span>
                <strong class="sucursal-row-title">
                    <?php echo esc_html($nombre ? $nombre : sprintf(__('Sucursal #%d', 'opticavision-theme'), $row_num)); ?>
                </strong>
                <span class="sucursal-row-actions">
                    <button type="button" class="button sucursal-toggle-row"><?php esc_html_e('▲ Colapsar', 'opticavision-theme'); ?></button>
                    <button type="button" class="button button-link-delete sucursal-remove-row"><?php esc_html_e('✕ Eliminar', 'opticavision-theme'); ?></button>
                </span>
            </div>
            <div class="sucursal-row-body">
                <table class="form-table sucursal-form-table">
                    <tr>
                        <th><label><?php esc_html_e('Nombre', 'opticavision-theme'); ?></label></th>
                        <td>
                            <input type="text"
                                   name="<?php echo esc_attr($prefix); ?>[nombre]"
                                   value="<?php echo esc_attr($nombre); ?>"
                                   class="regular-text sucursal-nombre-input"
                                   placeholder="<?php esc_attr_e('Ej: Casa Central', 'opticavision-theme'); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e('Imagen', 'opticavision-theme'); ?></label></th>
                        <td>
                            <div class="sucursal-image-wrap">
                                <div class="sucursal-image-preview">
                                    <?php if ($img_src) : ?>
                                        <img src="<?php echo esc_url($img_src); ?>" alt="" />
                                    <?php endif; ?>
                                </div>
                                <input type="hidden" name="<?php echo esc_attr($prefix); ?>[imagen_id]" class="sucursal-imagen-id" value="<?php echo esc_attr($img_id ?: ''); ?>" />
                                <button type="button" class="button sucursal-select-image"><?php esc_html_e('Seleccionar imagen', 'opticavision-theme'); ?></button>
                                <button type="button" class="button sucursal-remove-image"<?php echo $img_id ? '' : ' style="display:none"'; ?>><?php esc_html_e('Quitar', 'opticavision-theme'); ?></button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e('Dirección', 'opticavision-theme'); ?></label></th>
                        <td>
                            <input type="text"
                                   name="<?php echo esc_attr($prefix); ?>[direccion]"
                                   value="<?php echo esc_attr($direccion); ?>"
                                   class="regular-text"
                                   placeholder="<?php esc_attr_e('Ej: Palma 764 c/ Ayolas', 'opticavision-theme'); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e('Teléfono principal', 'opticavision-theme'); ?></label></th>
                        <td>
                            <input type="text"
                                   name="<?php echo esc_attr($prefix); ?>[telefono1]"
                                   value="<?php echo esc_attr($tel1); ?>"
                                   class="regular-text"
                                   placeholder="<?php esc_attr_e('Ej: 021-441-660 (RA)', 'opticavision-theme'); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e('Teléfono secundario', 'opticavision-theme'); ?></label></th>
                        <td>
                            <input type="text"
                                   name="<?php echo esc_attr($prefix); ?>[telefono2]"
                                   value="<?php echo esc_attr($tel2); ?>"
                                   class="regular-text"
                                   placeholder="<?php esc_attr_e('Opcional', 'opticavision-theme'); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e('Horario', 'opticavision-theme'); ?></label></th>
                        <td>
                            <input type="text"
                                   name="<?php echo esc_attr($prefix); ?>[horario]"
                                   value="<?php echo esc_attr($horario); ?>"
                                   class="large-text"
                                   placeholder="<?php esc_attr_e('Ej: Lun-Vie: 8:00-18:30 | Sáb: 8:30-12:30', 'opticavision-theme'); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e('URL Google Maps', 'opticavision-theme'); ?></label></th>
                        <td>
                            <input type="url"
                                   name="<?php echo esc_attr($prefix); ?>[maps_url]"
                                   value="<?php echo esc_attr($maps_url); ?>"
                                   class="large-text"
                                   placeholder="https://maps.google.com/..." />
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <?php
    }

    /**
     * Guarda los meta fields
     */
    public function save_meta($post_id) {
        if (!isset($_POST['sucursales_nonce'])) {
            return;
        }
        if (!wp_verify_nonce($_POST['sucursales_nonce'], 'sucursales_save')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_page', $post_id)) {
            return;
        }

        $items = array();

        if (!empty($_POST['sucursales_items']) && is_array($_POST['sucursales_items'])) {
            foreach ($_POST['sucursales_items'] as $raw) {
                $item = array(
                    'nombre'    => sanitize_text_field($raw['nombre']    ?? ''),
                    'direccion' => sanitize_text_field($raw['direccion'] ?? ''),
                    'telefono1' => sanitize_text_field($raw['telefono1'] ?? ''),
                    'telefono2' => sanitize_text_field($raw['telefono2'] ?? ''),
                    'horario'   => sanitize_text_field($raw['horario']   ?? ''),
                    'maps_url'  => esc_url_raw($raw['maps_url']          ?? ''),
                    'imagen_id' => absint($raw['imagen_id']              ?? 0),
                );
                // Sólo guardar filas que tengan al menos nombre
                if ($item['nombre'] !== '') {
                    $items[] = $item;
                }
            }
        }

        update_post_meta($post_id, '_sucursales_items', $items);

        // Guardar datos de Delivery
        $delivery = array(
            'imagen_id'  => absint($_POST['sucursales_delivery']['imagen_id']  ?? 0),
            'direccion'  => sanitize_text_field($_POST['sucursales_delivery']['direccion']  ?? ''),
            'telefono'   => sanitize_text_field($_POST['sucursales_delivery']['telefono']   ?? ''),
            'horario'    => sanitize_text_field($_POST['sucursales_delivery']['horario']    ?? ''),
            'whatsapp'   => sanitize_text_field($_POST['sucursales_delivery']['whatsapp']   ?? ''),
            'wa_mensaje' => sanitize_text_field($_POST['sucursales_delivery']['wa_mensaje'] ?? ''),
        );
        update_post_meta($post_id, '_sucursales_delivery', $delivery);
    }

    /**
     * Devuelve los items de la página de sucursales
     */
    public static function get_items($page_id) {
        $items = get_post_meta($page_id, '_sucursales_items', true);
        return is_array($items) ? $items : array();
    }

    /**
     * Devuelve los datos del Servicio de Delivery
     */
    public static function get_delivery($page_id) {
        $delivery = get_post_meta($page_id, '_sucursales_delivery', true);
        return is_array($delivery) ? $delivery : array();
    }

    /**
     * CSS inline para el admin
     */
    private function admin_css() {
        return '
        #sucursales-repeater { margin-bottom: 10px; }
        .sucursal-row {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .sucursal-row-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            background: #f6f7f7;
            border-bottom: 1px solid #ddd;
            border-radius: 4px 4px 0 0;
            cursor: default;
        }
        .sucursal-drag-handle {
            cursor: grab;
            color: #999;
            font-size: 18px;
            flex-shrink: 0;
        }
        .sucursal-drag-handle:active { cursor: grabbing; }
        .sucursal-row-title { flex: 1; font-size: 14px; }
        .sucursal-row-actions { display: flex; gap: 6px; flex-shrink: 0; }
        .sucursal-row-body { padding: 0 14px 4px; }
        .sucursal-form-table th { width: 160px; padding: 10px 10px 10px 0; font-weight: 500; }
        .sucursal-image-preview { margin-bottom: 6px; }
        .sucursal-image-preview img { max-width: 120px; height: auto; border-radius: 3px; border: 1px solid #ddd; display: block; }
        .sucursal-image-wrap { display: flex; flex-direction: column; gap: 6px; }
        #sucursal-row-template { display: none !important; }
        #sucursales-repeater.ui-sortable-helper { box-shadow: 0 4px 16px rgba(0,0,0,.15); }
        ';
    }

    /**
     * JS inline para el admin (repeater + media uploader + sortable)
     */
    private function admin_js() {
        return <<<'JS'
jQuery(function($) {
    var repeater = $('#sucursales-repeater');
    var template = $('#sucursal-row-template').html();

    // --- Renumerar índices y títulos ---
    function reindex() {
        repeater.find('.sucursal-row').each(function(i) {
            var row = $(this);
            // Renombrar todos los campos name="sucursales_items[X][...]"
            row.find('[name]').each(function() {
                var name = $(this).attr('name');
                $(this).attr('name', name.replace(/sucursales_items\[([^\]]+)\]/, 'sucursales_items[' + i + ']'));
            });
            // Actualizar título si está vacío
            var nombreVal = row.find('.sucursal-nombre-input').val();
            var title = nombreVal || 'Sucursal #' + (i + 1);
            row.find('.sucursal-row-title').text(title);
        });
    }

    // --- Agregar fila ---
    $('#sucursales-add-row').on('click', function() {
        var count = repeater.find('.sucursal-row').length;
        var newRow = $(template.replace(/sucursales_items\[__IDX__\]/g, 'sucursales_items[' + count + ']'));
        repeater.append(newRow);
        newRow.find('.sucursal-row-title').text('Sucursal #' + (count + 1));
        initRow(newRow);
        reindex();
    });

    // --- Eliminar fila ---
    repeater.on('click', '.sucursal-remove-row', function() {
        if (!confirm('¿Eliminar esta sucursal?')) return;
        $(this).closest('.sucursal-row').remove();
        reindex();
    });

    // --- Colapsar/expandir ---
    repeater.on('click', '.sucursal-toggle-row', function() {
        var btn = $(this);
        var body = btn.closest('.sucursal-row').find('.sucursal-row-body');
        if (body.is(':visible')) {
            body.hide();
            btn.text('▼ Expandir');
        } else {
            body.show();
            btn.text('▲ Colapsar');
        }
    });

    // --- Actualizar título al escribir nombre ---
    repeater.on('input', '.sucursal-nombre-input', function() {
        var row = $(this).closest('.sucursal-row');
        var val = $(this).val();
        var idx = repeater.find('.sucursal-row').index(row);
        row.find('.sucursal-row-title').text(val || 'Sucursal #' + (idx + 1));
    });

    // --- Media uploader ---
    function initRow(row) {
        row.find('.sucursal-select-image').on('click', function() {
            var btn = $(this);
            var wrap = btn.closest('.sucursal-image-wrap');
            var frame = wp.media({
                title: 'Seleccionar imagen de sucursal',
                button: { text: 'Usar esta imagen' },
                multiple: false
            });
            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                wrap.find('.sucursal-imagen-id').val(attachment.id);
                wrap.find('.sucursal-image-preview').html('<img src="' + attachment.url + '" alt="" />');
                wrap.find('.sucursal-remove-image').show();
            });
            frame.open();
        });

        row.find('.sucursal-remove-image').on('click', function() {
            var wrap = $(this).closest('.sucursal-image-wrap');
            wrap.find('.sucursal-imagen-id').val('');
            wrap.find('.sucursal-image-preview').html('');
            $(this).hide();
        });
    }

    // Inicializar filas existentes
    repeater.find('.sucursal-row').each(function() {
        initRow($(this));
    });

    // --- Sortable ---
    repeater.sortable({
        handle: '.sucursal-drag-handle',
        placeholder: 'sucursal-sortable-placeholder',
        tolerance: 'pointer',
        update: function() { reindex(); }
    });

    // --- Media uploader para Delivery ---
    $('#delivery-select-image').on('click', function() {
        var frame = wp.media({
            title: 'Seleccionar imagen de Delivery',
            button: { text: 'Usar esta imagen' },
            multiple: false
        });
        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            $('#delivery-imagen-id').val(attachment.id);
            $('#delivery-image-wrap .sucursal-image-preview').html('<img src="' + attachment.url + '" alt="" />');
            $('#delivery-remove-image').show();
        });
        frame.open();
    });

    $('#delivery-remove-image').on('click', function() {
        $('#delivery-imagen-id').val('');
        $('#delivery-image-wrap .sucursal-image-preview').html('');
        $(this).hide();
    });
});
JS;
    }
}

new OpticaVision_Sucursales();

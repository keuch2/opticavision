<?php
/**
 * Discount badge tiers — admin and renderer.
 *
 * @package OpticaVision_Theme
 */

defined('ABSPATH') || exit;

class OpticaVision_Discount_Badges {

    const OPTION_KEY = 'opticavision_discount_tiers';
    const NONCE_ACTION = 'opticavision_discount_tiers_save';
    const PAGE_SLUG = 'ov-discount-badges';

    public function __construct() {
        add_action('admin_menu', array($this, 'register_menu'));
        add_action('admin_init', array($this, 'handle_form'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    /* ------------------------------------------------------------------
     * Defaults & storage
     * ------------------------------------------------------------------ */

    public static function default_tiers() {
        return array(
            array('min' => 50, 'label' => '-%d%%', 'bg' => '#000000', 'fg' => '#ffffff'),
            array('min' => 40, 'label' => '-%d%%', 'bg' => '#6c757d', 'fg' => '#ffffff'),
            array('min' => 30, 'label' => '-%d%%', 'bg' => '#e74c3c', 'fg' => '#ffffff'),
            array('min' => 25, 'label' => '-%d%%', 'bg' => '#ffc107', 'fg' => '#1a1a1a'),
            array('min' => 20, 'label' => '-%d%%', 'bg' => '#3498db', 'fg' => '#ffffff'),
            array('min' => 15, 'label' => '-%d%%', 'bg' => '#27ae60', 'fg' => '#ffffff'),
        );
    }

    /**
     * Tiers sorted by `min` descending. Falls back to defaults if not set.
     */
    public static function get_tiers() {
        $stored = get_option(self::OPTION_KEY, null);
        $tiers = is_array($stored) && !empty($stored) ? $stored : self::default_tiers();

        $clean = array();
        foreach ($tiers as $tier) {
            if (!is_array($tier)) continue;
            $min = isset($tier['min']) ? max(0, min(100, (int) $tier['min'])) : 0;
            $label = isset($tier['label']) ? (string) $tier['label'] : '-%d%%';
            $bg = isset($tier['bg']) ? self::sanitize_hex($tier['bg'], '#e74c3c') : '#e74c3c';
            $fg = isset($tier['fg']) ? self::sanitize_hex($tier['fg'], '#ffffff') : '#ffffff';
            $clean[] = array('min' => $min, 'label' => $label, 'bg' => $bg, 'fg' => $fg);
        }

        usort($clean, function ($a, $b) {
            return $b['min'] - $a['min'];
        });

        return $clean;
    }

    /**
     * Return the tier matching a discount percentage, or null if none matches.
     */
    public static function get_tier_for_discount($discount_percentage) {
        $pct = (int) $discount_percentage;
        if ($pct <= 0) return null;

        foreach (self::get_tiers() as $tier) {
            if ($pct >= $tier['min']) {
                return $tier;
            }
        }
        return null;
    }

    /* ------------------------------------------------------------------
     * Discount calculation
     * ------------------------------------------------------------------ */

    /**
     * Calcula el porcentaje de descuento de un producto.
     * Para productos variables, recorre las variaciones y devuelve el descuento máximo.
     *
     * @param WC_Product $product
     * @return int Porcentaje entero (0 si no hay descuento).
     */
    public static function calculate_discount_percentage($product) {
        if (!$product || !is_a($product, 'WC_Product')) {
            return 0;
        }

        if ($product->is_type('variable')) {
            $max_discount = 0;
            $children = method_exists($product, 'get_visible_children') ? $product->get_visible_children() : $product->get_children();
            foreach ($children as $child_id) {
                $child = wc_get_product($child_id);
                if (!$child) continue;
                $reg  = (float) $child->get_regular_price();
                $sale = (float) $child->get_sale_price();
                if ($reg > 0 && $sale > 0 && $sale < $reg) {
                    $disc = (int) round((($reg - $sale) / $reg) * 100);
                    if ($disc > $max_discount) $max_discount = $disc;
                }
            }
            return $max_discount;
        }

        $reg  = (float) $product->get_regular_price();
        $sale = (float) $product->get_sale_price();
        if ($reg > 0 && $sale > 0 && $sale < $reg) {
            return (int) round((($reg - $sale) / $reg) * 100);
        }

        return 0;
    }

    /* ------------------------------------------------------------------
     * Public renderers (used by templates)
     * ------------------------------------------------------------------ */

    /**
     * Badge for product cards (listings). Returns HTML string or empty string.
     * Si hay descuento pero no hay tier que matchee, usa estilo default.
     */
    public static function render_card_badge($discount_percentage) {
        $pct = (int) $discount_percentage;
        if ($pct <= 0) return '';

        $tier = self::get_tier_for_discount($pct);
        if ($tier) {
            $text  = self::format_label($tier['label'], $pct);
            $style = sprintf('background:%s;color:%s;', esc_attr($tier['bg']), esc_attr($tier['fg']));
        } else {
            $text  = sprintf('-%d%%', $pct);
            $style = 'background:#e74c3c;color:#ffffff;';
        }

        return sprintf(
            '<span class="product-badge sale" style="%s">%s</span>',
            $style,
            esc_html($text)
        );
    }

    /**
     * Badge for the single product page. Returns HTML string or empty string.
     */
    public static function render_single_badge($discount_percentage) {
        $pct = (int) $discount_percentage;
        if ($pct <= 0) return '';

        $tier = self::get_tier_for_discount($pct);
        if ($tier) {
            $text  = self::format_label($tier['label'], $pct);
            $style = sprintf('background:%s;color:%s;', esc_attr($tier['bg']), esc_attr($tier['fg']));
        } else {
            $text  = sprintf('-%d%%', $pct);
            $style = 'background:#e74c3c;color:#ffffff;';
        }

        return sprintf(
            '<div class="discount-badge" style="%s">%s</div>',
            $style,
            esc_html($text)
        );
    }

    private static function format_label($template, $pct) {
        $template = (string) $template;
        if (strpos($template, '%d') !== false || strpos($template, '%%') !== false) {
            return sprintf($template, $pct);
        }
        return $template;
    }

    /* ------------------------------------------------------------------
     * Admin menu + assets
     * ------------------------------------------------------------------ */

    public function register_menu() {
        add_submenu_page(
            'woocommerce',
            __('Etiquetas de Descuento', 'opticavision-theme'),
            __('Etiquetas de Descuento', 'opticavision-theme'),
            'manage_woocommerce',
            self::PAGE_SLUG,
            array($this, 'render_page')
        );
    }

    public function enqueue_admin_assets($hook) {
        if ($hook !== 'woocommerce_page_' . self::PAGE_SLUG) return;
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
    }

    /* ------------------------------------------------------------------
     * Form handling
     * ------------------------------------------------------------------ */

    public function handle_form() {
        if (!isset($_POST['ov_discount_tiers_submit'])) return;
        if (!current_user_can('manage_woocommerce')) return;

        check_admin_referer(self::NONCE_ACTION);

        // Reset to defaults
        if (isset($_POST['ov_reset_defaults'])) {
            delete_option(self::OPTION_KEY);
            wp_safe_redirect(add_query_arg(
                array('page' => self::PAGE_SLUG, 'updated' => 'reset'),
                admin_url('admin.php')
            ));
            exit;
        }

        $raw = isset($_POST['tiers']) && is_array($_POST['tiers']) ? wp_unslash($_POST['tiers']) : array();
        $clean = array();
        foreach ($raw as $row) {
            if (!is_array($row)) continue;
            $min = isset($row['min']) && $row['min'] !== '' ? (int) $row['min'] : null;
            if ($min === null) continue;
            $min = max(0, min(100, $min));

            $clean[] = array(
                'min'   => $min,
                'label' => isset($row['label']) ? sanitize_text_field($row['label']) : '-%d%%',
                'bg'    => isset($row['bg']) ? self::sanitize_hex($row['bg'], '#e74c3c') : '#e74c3c',
                'fg'    => isset($row['fg']) ? self::sanitize_hex($row['fg'], '#ffffff') : '#ffffff',
            );
        }

        usort($clean, function ($a, $b) {
            return $b['min'] - $a['min'];
        });

        update_option(self::OPTION_KEY, $clean);

        wp_safe_redirect(add_query_arg(
            array('page' => self::PAGE_SLUG, 'updated' => '1'),
            admin_url('admin.php')
        ));
        exit;
    }

    private static function sanitize_hex($value, $fallback) {
        $value = is_string($value) ? trim($value) : '';
        if (preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $value)) {
            return strtolower($value);
        }
        return $fallback;
    }

    /* ------------------------------------------------------------------
     * Admin page
     * ------------------------------------------------------------------ */

    public function render_page() {
        $tiers = self::get_tiers();
        $updated = isset($_GET['updated']) ? sanitize_text_field($_GET['updated']) : '';
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Etiquetas de Descuento', 'opticavision-theme'); ?></h1>

            <?php if ($updated === '1'): ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Cambios guardados.', 'opticavision-theme'); ?></p></div>
            <?php elseif ($updated === 'reset'): ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Tiers restaurados a los valores por defecto.', 'opticavision-theme'); ?></p></div>
            <?php endif; ?>

            <p>
                <?php esc_html_e('Define rangos de descuento y el color de la etiqueta para cada uno. Cada tier se aplica desde su porcentaje mínimo en adelante; el sistema elige el tier más alto que el descuento del producto cumpla.', 'opticavision-theme'); ?>
                <br>
                <?php
                printf(
                    /* translators: %s: example placeholder */
                    esc_html__('En el campo "Texto" podés usar %s como marcador para el porcentaje (ej: "-%%d%%%%" muestra "-25%%").', 'opticavision-theme'),
                    '<code>%d</code>'
                );
                ?>
            </p>

            <form method="post" action="">
                <?php wp_nonce_field(self::NONCE_ACTION); ?>

                <table class="widefat striped" id="ov-tiers-table" style="max-width:900px;">
                    <thead>
                        <tr>
                            <th style="width:130px;"><?php esc_html_e('% mínimo', 'opticavision-theme'); ?></th>
                            <th><?php esc_html_e('Texto del badge', 'opticavision-theme'); ?></th>
                            <th style="width:160px;"><?php esc_html_e('Color de fondo', 'opticavision-theme'); ?></th>
                            <th style="width:160px;"><?php esc_html_e('Color de texto', 'opticavision-theme'); ?></th>
                            <th style="width:200px;"><?php esc_html_e('Vista previa', 'opticavision-theme'); ?></th>
                            <th style="width:60px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tiers as $i => $t):
                            $sample = self::format_label($t['label'], max(1, (int) $t['min']));
                            ?>
                            <tr>
                                <td><input type="number" name="tiers[<?php echo $i; ?>][min]" value="<?php echo esc_attr($t['min']); ?>" min="0" max="100" class="small-text" required></td>
                                <td><input type="text" name="tiers[<?php echo $i; ?>][label]" value="<?php echo esc_attr($t['label']); ?>" class="regular-text"></td>
                                <td><input type="text" name="tiers[<?php echo $i; ?>][bg]" value="<?php echo esc_attr($t['bg']); ?>" class="ov-color-field" data-default-color="<?php echo esc_attr($t['bg']); ?>"></td>
                                <td><input type="text" name="tiers[<?php echo $i; ?>][fg]" value="<?php echo esc_attr($t['fg']); ?>" class="ov-color-field" data-default-color="<?php echo esc_attr($t['fg']); ?>"></td>
                                <td><span class="ov-badge-preview" style="display:inline-block;padding:6px 12px;border-radius:4px;font-weight:600;background:<?php echo esc_attr($t['bg']); ?>;color:<?php echo esc_attr($t['fg']); ?>;"><?php echo esc_html($sample); ?></span></td>
                                <td><button type="button" class="button-link ov-remove-row" style="color:#a00;"><?php esc_html_e('Eliminar', 'opticavision-theme'); ?></button></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <p style="margin-top:12px;">
                    <button type="button" class="button" id="ov-add-row"><?php esc_html_e('Agregar tier', 'opticavision-theme'); ?></button>
                </p>

                <p class="submit">
                    <button type="submit" name="ov_discount_tiers_submit" class="button button-primary"><?php esc_html_e('Guardar cambios', 'opticavision-theme'); ?></button>
                    <button type="submit" name="ov_reset_defaults" value="1" class="button" onclick="return confirm('<?php echo esc_js(__('¿Restaurar los tiers por defecto? Se perderán los cambios actuales.', 'opticavision-theme')); ?>');"><?php esc_html_e('Restaurar valores por defecto', 'opticavision-theme'); ?></button>
                    <input type="hidden" name="ov_discount_tiers_submit" value="1">
                </p>
            </form>
        </div>

        <script>
        (function($){
            function initColor($scope){
                $scope.find('.ov-color-field').each(function(){
                    if ($(this).hasClass('wp-color-picker')) return;
                    $(this).wpColorPicker({
                        change: function(){
                            updatePreview($(this).closest('tr'));
                        }
                    });
                });
            }
            function updatePreview($row){
                setTimeout(function(){
                    var bg = $row.find('input[name$="[bg]"]').val();
                    var fg = $row.find('input[name$="[fg]"]').val();
                    var label = $row.find('input[name$="[label]"]').val() || '-%d%%';
                    var min = parseInt($row.find('input[name$="[min]"]').val(), 10) || 0;
                    var text = label.replace('%d', Math.max(1, min)).replace(/%%/g, '%');
                    $row.find('.ov-badge-preview').css({background: bg, color: fg}).text(text);
                }, 50);
            }

            $(function(){
                initColor($(document));

                $(document).on('input change', '#ov-tiers-table input', function(){
                    updatePreview($(this).closest('tr'));
                });

                $('#ov-add-row').on('click', function(){
                    var idx = Date.now();
                    var $row = $('<tr>' +
                        '<td><input type="number" name="tiers['+idx+'][min]" value="10" min="0" max="100" class="small-text" required></td>' +
                        '<td><input type="text" name="tiers['+idx+'][label]" value="-%d%%" class="regular-text"></td>' +
                        '<td><input type="text" name="tiers['+idx+'][bg]" value="#e74c3c" class="ov-color-field"></td>' +
                        '<td><input type="text" name="tiers['+idx+'][fg]" value="#ffffff" class="ov-color-field"></td>' +
                        '<td><span class="ov-badge-preview" style="display:inline-block;padding:6px 12px;border-radius:4px;font-weight:600;background:#e74c3c;color:#fff;">-10%</span></td>' +
                        '<td><button type="button" class="button-link ov-remove-row" style="color:#a00;"><?php echo esc_js(__('Eliminar', 'opticavision-theme')); ?></button></td>' +
                    '</tr>');
                    $('#ov-tiers-table tbody').append($row);
                    initColor($row);
                });

                $(document).on('click', '.ov-remove-row', function(){
                    $(this).closest('tr').remove();
                });
            });
        })(jQuery);
        </script>
        <?php
    }
}

new OpticaVision_Discount_Badges();

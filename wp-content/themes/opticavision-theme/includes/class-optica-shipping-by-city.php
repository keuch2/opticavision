<?php
/**
 * Sistema de Envío por Ciudad para Paraguay
 * 
 * Permite configurar costos de envío específicos por ciudad
 * ya que WooCommerce solo permite por país/estado.
 *
 * @package OpticaVision
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

// Verificar que WooCommerce esté activo
if (!class_exists('WC_Shipping_Method')) {
    return;
}

class OpticaVision_Shipping_By_City extends WC_Shipping_Method {
    
    /**
     * Constructor
     */
    public function __construct($instance_id = 0) {
        $this->id                 = 'optica_vision_city_shipping';
        $this->instance_id        = absint($instance_id);
        $this->method_title       = __('Envío por Ciudad', 'opticavision-theme');
        $this->method_description = __('Configura costos de envío específicos para cada ciudad de Paraguay', 'opticavision-theme');
        $this->supports           = array(
            'shipping-zones',
            'instance-settings',
            'instance-settings-modal',
        );
        
        $this->init();
    }
    
    /**
     * Inicializar configuración
     */
    public function init() {
        // Cargar configuración
        $this->init_form_fields();
        $this->init_settings();
        
        // Definir propiedades
        $this->enabled = $this->get_option('enabled');
        $this->title   = $this->get_option('title');
        
        // Guardar configuración
        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
    }
    
    /**
     * Campos de configuración
     */
    public function init_form_fields() {
        $this->instance_form_fields = array(
            'enabled' => array(
                'title'   => __('Habilitar/Deshabilitar', 'opticavision-theme'),
                'type'    => 'checkbox',
                'label'   => __('Habilitar este método de envío', 'opticavision-theme'),
                'default' => 'yes',
            ),
            'title' => array(
                'title'       => __('Título del Método', 'opticavision-theme'),
                'type'        => 'text',
                'description' => __('Título que verá el cliente en el checkout', 'opticavision-theme'),
                'default'     => __('Envío a Domicilio', 'opticavision-theme'),
                'desc_tip'    => true,
            ),
            'free_shipping_threshold' => array(
                'title'       => __('Envío Gratis desde', 'opticavision-theme'),
                'type'        => 'number',
                'description' => __('Monto mínimo para envío gratis (dejar en 0 para desactivar)', 'opticavision-theme'),
                'default'     => '0',
                'desc_tip'    => true,
                'custom_attributes' => array(
                    'min'  => '0',
                    'step' => '1',
                ),
            ),
        );
    }
    
    /**
     * Calcular costo de envío
     */
    public function calculate_shipping($package = array()) {
        // Obtener ciudad de destino
        $city = isset($package['destination']['city']) ? sanitize_text_field($package['destination']['city']) : '';
        
        if (empty($city)) {
            return;
        }
        
        // Normalizar nombre de ciudad
        $city = $this->normalize_city_name($city);
        
        // Obtener costo de envío para esta ciudad
        $shipping_cost = $this->get_city_shipping_cost($city);
        
        // Verificar envío gratis
        $free_threshold = floatval($this->get_option('free_shipping_threshold', 0));
        $cart_total = WC()->cart->get_subtotal();
        
        if ($free_threshold > 0 && $cart_total >= $free_threshold) {
            $shipping_cost = 0;
        }
        
        // Agregar tarifa de envío
        $rate = array(
            'id'    => $this->get_rate_id(),
            'label' => $this->title,
            'cost'  => $shipping_cost,
            'meta_data' => array(
                'ciudad' => $city,
            ),
        );
        
        $this->add_rate($rate);
    }
    
    /**
     * Normalizar nombre de ciudad
     */
    private function normalize_city_name($city) {
        // Convertir a minúsculas y quitar acentos
        $city = strtolower($city);
        $city = remove_accents($city);
        $city = trim($city);
        
        // Normalizar nombres comunes
        $replacements = array(
            'asuncion' => 'asuncion',
            'ciudad del este' => 'ciudad_del_este',
            'cde' => 'ciudad_del_este',
            'encarnacion' => 'encarnacion',
            'fernando de la mora' => 'fernando_de_la_mora',
            'san lorenzo' => 'san_lorenzo',
            'lambare' => 'lambare',
            'luque' => 'luque',
            'capiata' => 'capiata',
            'limpio' => 'limpio',
            'nemby' => 'nemby',
            'villa elisa' => 'villa_elisa',
            'mariano roque alonso' => 'mariano_roque_alonso',
        );
        
        foreach ($replacements as $search => $replace) {
            if (strpos($city, $search) !== false) {
                return $replace;
            }
        }
        
        return str_replace(' ', '_', $city);
    }
    
    /**
     * Obtener costo de envío por ciudad
     */
    private function get_city_shipping_cost($city) {
        // Obtener configuración de precios por ciudad
        $city_prices = get_option('optica_vision_city_shipping_prices', array());
        
        // Si existe precio específico para esta ciudad
        if (isset($city_prices[$city])) {
            return floatval($city_prices[$city]);
        }
        
        // Precio por defecto para ciudades no configuradas
        $default_price = get_option('optica_vision_default_shipping_price', 25000);
        return floatval($default_price);
    }
}

/**
 * Registrar método de envío personalizado
 */
add_filter('woocommerce_shipping_methods', 'optica_vision_register_city_shipping_method');
function optica_vision_register_city_shipping_method($methods) {
    $methods['optica_vision_city_shipping'] = 'OpticaVision_Shipping_By_City';
    return $methods;
}

/**
 * Página de administración para configurar precios por ciudad
 */
add_action('admin_menu', 'optica_vision_add_shipping_cities_menu');
function optica_vision_add_shipping_cities_menu() {
    add_submenu_page(
        'woocommerce',
        __('Envío por Ciudad', 'opticavision-theme'),
        __('Envío por Ciudad', 'opticavision-theme'),
        'manage_woocommerce',
        'optica-shipping-cities',
        'optica_vision_render_shipping_cities_page'
    );
}

/**
 * Renderizar página de configuración
 */
function optica_vision_render_shipping_cities_page() {
    // Guardar configuración
    if (isset($_POST['optica_save_city_prices']) && check_admin_referer('optica_city_prices_nonce')) {
        $city_prices = array();
        
        if (isset($_POST['city_name']) && is_array($_POST['city_name'])) {
            foreach ($_POST['city_name'] as $index => $city_name) {
                $city_name = sanitize_text_field($city_name);
                $city_price = isset($_POST['city_price'][$index]) ? floatval($_POST['city_price'][$index]) : 0;
                
                if (!empty($city_name) && $city_price >= 0) {
                    $city_key = str_replace(' ', '_', strtolower(remove_accents($city_name)));
                    $city_prices[$city_key] = $city_price;
                }
            }
        }
        
        update_option('optica_vision_city_shipping_prices', $city_prices);
        
        // Guardar precio por defecto
        if (isset($_POST['default_shipping_price'])) {
            update_option('optica_vision_default_shipping_price', floatval($_POST['default_shipping_price']));
        }
        
        echo '<div class="notice notice-success"><p>' . __('Configuración guardada correctamente', 'opticavision-theme') . '</p></div>';
    }
    
    // Obtener configuración actual
    $city_prices = get_option('optica_vision_city_shipping_prices', array());
    $default_price = get_option('optica_vision_default_shipping_price', 25000);
    
    // Si no hay ciudades configuradas, mostrar las principales como sugerencia
    $show_suggestions = empty($city_prices);
    
    // Ciudades principales de Paraguay (solo como sugerencia inicial)
    $suggested_cities = array(
        'asuncion' => 'Asunción',
        'ciudad_del_este' => 'Ciudad del Este',
        'encarnacion' => 'Encarnación',
        'fernando_de_la_mora' => 'Fernando de la Mora',
        'san_lorenzo' => 'San Lorenzo',
        'lambare' => 'Lambaré',
        'luque' => 'Luque',
        'capiata' => 'Capiatá',
        'limpio' => 'Limpio',
        'nemby' => 'Ñemby',
        'villa_elisa' => 'Villa Elisa',
        'mariano_roque_alonso' => 'Mariano Roque Alonso',
    );
    
    ?>
    <div class="wrap">
        <h1><?php _e('Configuración de Envío por Ciudad', 'opticavision-theme'); ?></h1>
        <p><?php _e('Configure los costos de envío para cada ciudad de Paraguay. Las ciudades no configuradas usarán el precio por defecto.', 'opticavision-theme'); ?></p>
        
        <form method="post" action="">
            <?php wp_nonce_field('optica_city_prices_nonce'); ?>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 50%;"><?php _e('Ciudad', 'opticavision-theme'); ?></th>
                        <th style="width: 30%;"><?php _e('Costo de Envío (Gs.)', 'opticavision-theme'); ?></th>
                        <th style="width: 20%;"><?php _e('Acciones', 'opticavision-theme'); ?></th>
                    </tr>
                </thead>
                <tbody id="city-prices-list">
                    <?php
                    // Si no hay ciudades configuradas, mostrar sugerencias
                    if ($show_suggestions) {
                        foreach ($suggested_cities as $city_key => $city_name) {
                            ?>
                            <tr>
                                <td>
                                    <input type="text" name="city_name[]" value="<?php echo esc_attr($city_name); ?>" 
                                           class="regular-text" />
                                </td>
                                <td>
                                    <input type="number" name="city_price[]" value="" 
                                           min="0" step="1" class="regular-text" 
                                           placeholder="<?php echo esc_attr($default_price); ?>" />
                                </td>
                                <td>
                                    <button type="button" class="button button-small remove-city-row" title="Eliminar ciudad">
                                        <span class="dashicons dashicons-trash" style="color: #b32d2e;"></span>
                                    </button>
                                </td>
                            </tr>
                            <?php
                        }
                    } else {
                        // Mostrar solo las ciudades guardadas
                        foreach ($city_prices as $city_key => $price) {
                            // Obtener nombre legible de la ciudad
                            $city_name = isset($suggested_cities[$city_key]) 
                                ? $suggested_cities[$city_key] 
                                : ucwords(str_replace('_', ' ', $city_key));
                            ?>
                            <tr>
                                <td>
                                    <input type="text" name="city_name[]" value="<?php echo esc_attr($city_name); ?>" 
                                           class="regular-text" />
                                </td>
                                <td>
                                    <input type="number" name="city_price[]" value="<?php echo esc_attr($price); ?>" 
                                           min="0" step="1" class="regular-text" 
                                           placeholder="<?php echo esc_attr($default_price); ?>" />
                                </td>
                                <td>
                                    <button type="button" class="button button-small remove-city-row" title="Eliminar ciudad">
                                        <span class="dashicons dashicons-trash" style="color: #b32d2e;"></span>
                                    </button>
                                </td>
                            </tr>
                            <?php
                        }
                    }
                    ?>
                </tbody>
            </table>
            
            <p>
                <button type="button" id="add-city-row" class="button">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php _e('Agregar Ciudad', 'opticavision-theme'); ?>
                </button>
            </p>
            
            <hr style="margin: 30px 0;" />
            
            <div style="background: #f0f6fc; border-left: 4px solid #2271b1; padding: 20px; margin-bottom: 20px;">
                <h2 style="margin-top: 0;"><?php _e('⚙️ Configuración General', 'opticavision-theme'); ?></h2>
                <table class="form-table" style="background: white; padding: 15px; border-radius: 4px;">
                    <tr>
                        <th scope="row" style="width: 300px;">
                            <label for="default_shipping_price">
                                <strong><?php _e('💰 Precio por Defecto (Resto del País)', 'opticavision-theme'); ?></strong>
                            </label>
                        </th>
                        <td>
                            <input type="number" id="default_shipping_price" name="default_shipping_price" 
                                   value="<?php echo esc_attr($default_price); ?>" 
                                   min="0" step="1" class="regular-text" 
                                   style="font-size: 16px; padding: 8px;" />
                            <span style="margin-left: 10px; color: #666;">Gs.</span>
                            <p class="description" style="margin-top: 10px; font-size: 13px;">
                                <?php _e('Este precio se aplicará automáticamente a todas las ciudades que NO estén configuradas en la lista superior.', 'opticavision-theme'); ?>
                                <br>
                                <strong><?php _e('Ejemplo:', 'opticavision-theme'); ?></strong> 
                                <?php _e('Si un cliente selecciona "Pedro Juan Caballero" y no está en la lista, se cobrará este monto.', 'opticavision-theme'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <p class="submit">
                <input type="submit" name="optica_save_city_prices" class="button button-primary" 
                       value="<?php _e('Guardar Configuración', 'opticavision-theme'); ?>" />
            </p>
        </form>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Agregar nueva fila
        $('#add-city-row').on('click', function() {
            var newRow = '<tr>' +
                '<td><input type="text" name="city_name[]" class="regular-text" placeholder="Nombre de la ciudad" /></td>' +
                '<td><input type="number" name="city_price[]" min="0" step="1" class="regular-text" placeholder="<?php echo esc_js($default_price); ?>" /></td>' +
                '<td><button type="button" class="button remove-city-row"><span class="dashicons dashicons-trash"></span></button></td>' +
                '</tr>';
            $('#city-prices-list').append(newRow);
        });
        
        // Eliminar fila
        $(document).on('click', '.remove-city-row', function() {
            $(this).closest('tr').remove();
        });
    });
    </script>
    
    <style>
    .wp-list-table input[type="text"],
    .wp-list-table input[type="number"] {
        width: 100%;
    }
    .remove-city-row {
        color: #b32d2e;
    }
    .remove-city-row:hover {
        color: #dc3232;
    }
    </style>
    <?php
}

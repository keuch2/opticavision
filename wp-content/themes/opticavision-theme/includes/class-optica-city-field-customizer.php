<?php
/**
 * Personalizador del Campo de Ciudad
 * 
 * Convierte el campo de ciudad en un selector con las ciudades configuradas
 * y permite que el usuario seleccione "Otra" para escribir una ciudad personalizada.
 *
 * @package OpticaVision
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

class OpticaVision_City_Field_Customizer {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Modificar campos del checkout
        add_filter('woocommerce_checkout_fields', array($this, 'customize_city_field'), 20);
        add_filter('woocommerce_checkout_fields', array($this, 'customize_state_field'), 25);
        
        // Encolar JavaScript para manejar el campo "Otra" y departamento
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Actualizar ciudad y estado en el carrito cuando cambia
        add_action('woocommerce_checkout_update_order_review', array($this, 'update_city_on_checkout'));
    }
    
    /**
     * Personalizar campo de ciudad
     */
    public function customize_city_field($fields) {
        // Obtener ciudades configuradas
        $city_prices = get_option('optica_vision_city_shipping_prices', array());
        
        // Ciudades sugeridas para el mapeo de nombres
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
        
        // Crear opciones para el selector
        $city_options = array('' => 'Selecciona tu ciudad');
        
        foreach ($city_prices as $city_key => $price) {
            // Obtener nombre legible
            $city_name = isset($suggested_cities[$city_key]) 
                ? $suggested_cities[$city_key] 
                : ucwords(str_replace('_', ' ', $city_key));
            
            $city_options[$city_key] = $city_name;
        }
        
        // Agregar opción "Otra"
        $city_options['otra'] = 'Otra ciudad';
        
        // Modificar campo de ciudad de facturación
        if (isset($fields['billing']['billing_city'])) {
            $fields['billing']['billing_city'] = array(
                'type'        => 'select',
                'label'       => __('Ciudad', 'opticavision-theme'),
                'required'    => true,
                'class'       => array('form-row-wide', 'update_totals_on_change'),
                'priority'    => 70,
                'options'     => $city_options,
            );
        }
        
        // Agregar campo de texto para "Otra ciudad"
        $fields['billing']['billing_city_other'] = array(
            'type'        => 'text',
            'label'       => __('Escribe tu ciudad', 'opticavision-theme'),
            'placeholder' => __('Nombre de tu ciudad', 'opticavision-theme'),
            'required'    => false,
            'class'       => array('form-row-wide', 'hidden', 'city-other-field'),
            'priority'    => 71,
            'clear'       => true,
        );
        
        // Modificar campo de ciudad de envío
        if (isset($fields['shipping']['shipping_city'])) {
            $fields['shipping']['shipping_city'] = array(
                'type'        => 'select',
                'label'       => __('Ciudad', 'opticavision-theme'),
                'required'    => true,
                'class'       => array('form-row-wide', 'update_totals_on_change'),
                'priority'    => 70,
                'options'     => $city_options,
            );
        }
        
        // Agregar campo de texto para "Otra ciudad" en envío
        $fields['shipping']['shipping_city_other'] = array(
            'type'        => 'text',
            'label'       => __('Escribe tu ciudad', 'opticavision-theme'),
            'placeholder' => __('Nombre de tu ciudad', 'opticavision-theme'),
            'required'    => false,
            'class'       => array('form-row-wide', 'hidden', 'city-other-field'),
            'priority'    => 71,
            'clear'       => true,
        );
        
        return $fields;
    }
    
    /**
     * Personalizar campo de departamento/estado
     */
    public function customize_state_field($fields) {
        // Departamentos de Paraguay
        $paraguay_states = array(
            '' => 'Selecciona tu departamento',
            'Asunción' => 'Asunción',
            'Central' => 'Central',
            'Alto Paraguay' => 'Alto Paraguay',
            'Alto Paraná' => 'Alto Paraná',
            'Amambay' => 'Amambay',
            'Boquerón' => 'Boquerón',
            'Caaguazú' => 'Caaguazú',
            'Caazapá' => 'Caazapá',
            'Canindeyú' => 'Canindeyú',
            'Concepción' => 'Concepción',
            'Cordillera' => 'Cordillera',
            'Guairá' => 'Guairá',
            'Itapúa' => 'Itapúa',
            'Misiones' => 'Misiones',
            'Ñeembucú' => 'Ñeembucú',
            'Paraguarí' => 'Paraguarí',
            'Presidente Hayes' => 'Presidente Hayes',
            'San Pedro' => 'San Pedro',
        );
        
        // Modificar campo de estado/departamento de facturación (oculto por defecto)
        if (isset($fields['billing']['billing_state'])) {
            $fields['billing']['billing_state'] = array(
                'type'        => 'select',
                'label'       => __('Departamento', 'opticavision-theme'),
                'required'    => true,
                'class'       => array('form-row-wide', 'update_totals_on_change', 'hidden', 'state-field-auto'),
                'priority'    => 60,
                'options'     => $paraguay_states,
            );
        }
        
        // Modificar campo de estado/departamento de envío (oculto por defecto)
        if (isset($fields['shipping']['shipping_state'])) {
            $fields['shipping']['shipping_state'] = array(
                'type'        => 'select',
                'label'       => __('Departamento', 'opticavision-theme'),
                'required'    => true,
                'class'       => array('form-row-wide', 'update_totals_on_change', 'hidden', 'state-field-auto'),
                'priority'    => 60,
                'options'     => $paraguay_states,
            );
        }
        
        // Hacer el teléfono obligatorio
        if (isset($fields['billing']['billing_phone'])) {
            $fields['billing']['billing_phone']['required'] = true;
        }
        
        return $fields;
    }
    
    /**
     * Encolar scripts
     */
    public function enqueue_scripts() {
        if (is_checkout()) {
            wp_add_inline_script('wc-checkout', "
                jQuery(document).ready(function($) {
                    // Mapeo de ciudades a departamentos
                    var cityToDepartment = {
                        'asuncion': 'Asunción',
                        'ciudad_del_este': 'Alto Paraná',
                        'encarnacion': 'Itapúa',
                        'fernando_de_la_mora': 'Central',
                        'san_lorenzo': 'Central',
                        'lambare': 'Central',
                        'luque': 'Central',
                        'capiata': 'Central',
                        'limpio': 'Central',
                        'nemby': 'Central',
                        'villa_elisa': 'Central',
                        'mariano_roque_alonso': 'Central'
                    };
                    
                    // Función para actualizar departamento según ciudad
                    function updateDepartmentByCity(cityValue, stateField) {
                        var hiddenFieldId = stateField.attr('id') + '_auto_hidden';
                        
                        if (cityValue && cityValue !== 'otra' && cityToDepartment[cityValue]) {
                            var department = cityToDepartment[cityValue];
                            
                            // Actualizar el valor del select
                            stateField.val(department);
                            
                            // Ocultar el campo visible
                            stateField.closest('.form-row').addClass('hidden').hide();
                            
                            // Crear campo hidden para enviar el valor
                            $('#' + hiddenFieldId).remove();
                            stateField.after('<input type=\"hidden\" id=\"' + hiddenFieldId + '\" name=\"' + stateField.attr('name') + '\" value=\"' + department + '\" />');
                            
                        } else if (cityValue === 'otra') {
                            // Eliminar campo hidden
                            $('#' + hiddenFieldId).remove();
                            
                            // Mostrar el campo de departamento para que el usuario lo seleccione
                            stateField.closest('.form-row').removeClass('hidden').show();
                        }
                    }
                    
                    // Función para mostrar/ocultar campo 'Otra ciudad' y departamento
                    function toggleCityOtherField(selectField, otherField, stateField) {
                        if (selectField.val() === 'otra') {
                            // Mostrar campo de texto para otra ciudad
                            otherField.closest('.form-row').removeClass('hidden').show();
                            otherField.prop('required', true);
                            
                            // Mostrar selector de departamento
                            stateField.closest('.form-row').removeClass('hidden').show();
                        } else {
                            // Ocultar campo de texto
                            otherField.closest('.form-row').addClass('hidden').hide();
                            otherField.prop('required', false);
                            otherField.val('');
                            
                            // Actualizar departamento automáticamente y ocultarlo
                            updateDepartmentByCity(selectField.val(), stateField);
                        }
                    }
                    
                    // Billing city and state
                    var billingCity = $('#billing_city');
                    var billingCityOther = $('#billing_city_other');
                    var billingState = $('#billing_state');
                    
                    if (billingCity.length && billingCityOther.length && billingState.length) {
                        // Inicializar
                        toggleCityOtherField(billingCity, billingCityOther, billingState);
                        
                        billingCity.on('change', function() {
                            toggleCityOtherField(billingCity, billingCityOther, billingState);
                            $('body').trigger('update_checkout');
                        });
                        
                        billingCityOther.on('blur', function() {
                            if (billingCity.val() === 'otra' && $(this).val()) {
                                $('body').trigger('update_checkout');
                            }
                        });
                    }
                    
                    // Shipping city and state
                    var shippingCity = $('#shipping_city');
                    var shippingCityOther = $('#shipping_city_other');
                    var shippingState = $('#shipping_state');
                    
                    if (shippingCity.length && shippingCityOther.length && shippingState.length) {
                        // Inicializar
                        toggleCityOtherField(shippingCity, shippingCityOther, shippingState);
                        
                        shippingCity.on('change', function() {
                            toggleCityOtherField(shippingCity, shippingCityOther, shippingState);
                            $('body').trigger('update_checkout');
                        });
                        
                        shippingCityOther.on('blur', function() {
                            if (shippingCity.val() === 'otra' && $(this).val()) {
                                $('body').trigger('update_checkout');
                            }
                        });
                    }
                });
            ");
            
            // CSS para ocultar el campo
            wp_add_inline_style('woocommerce-general', "
                .city-other-field.hidden {
                    display: none !important;
                }
            ");
        }
    }
    
    /**
     * Actualizar ciudad cuando se actualiza el checkout
     */
    public function update_city_on_checkout($post_data) {
        parse_str($post_data, $data);
        
        // Billing city
        if (isset($data['billing_city']) && $data['billing_city'] === 'otra' && !empty($data['billing_city_other'])) {
            WC()->customer->set_billing_city(sanitize_text_field($data['billing_city_other']));
        } elseif (isset($data['billing_city']) && $data['billing_city'] !== 'otra') {
            // Convertir el key a nombre legible
            $city_key = sanitize_text_field($data['billing_city']);
            $city_name = $this->get_city_display_name($city_key);
            WC()->customer->set_billing_city($city_name);
        }
        
        // Shipping city
        if (isset($data['shipping_city']) && $data['shipping_city'] === 'otra' && !empty($data['shipping_city_other'])) {
            WC()->customer->set_shipping_city(sanitize_text_field($data['shipping_city_other']));
        } elseif (isset($data['shipping_city']) && $data['shipping_city'] !== 'otra') {
            $city_key = sanitize_text_field($data['shipping_city']);
            $city_name = $this->get_city_display_name($city_key);
            WC()->customer->set_shipping_city($city_name);
        }
    }
    
    /**
     * Obtener nombre de ciudad para mostrar
     */
    private function get_city_display_name($city_key) {
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
        
        return isset($suggested_cities[$city_key]) 
            ? $suggested_cities[$city_key] 
            : ucwords(str_replace('_', ' ', $city_key));
    }
}

// Inicializar
new OpticaVision_City_Field_Customizer();

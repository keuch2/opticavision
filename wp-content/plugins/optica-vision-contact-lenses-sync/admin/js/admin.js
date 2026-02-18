jQuery(document).ready(function($) {
    console.log('[CL SYNC JS] Script loaded and ready');
    
    // Get nonce value
    var nonce = $('#optica_vision_cl_nonce').val();
    console.log('[CL SYNC JS] Nonce value:', nonce ? 'Found' : 'NOT FOUND');
    console.log('[CL SYNC JS] Ajax URL:', optica_vision_cl_ajax ? optica_vision_cl_ajax.ajax_url : 'NOT FOUND');
    
    // Log container
    var $logContainer = $('#sync-logs');
    
    /**
     * Add log message to the container
     */
    function addLog(message, type = 'info') {
        var timestamp = new Date().toLocaleTimeString();
        var logClass = 'log-' + type;
        var icon = type === 'error' ? '❌' : type === 'success' ? '✅' : 'ℹ️';
        
        $logContainer.append(
            '<div class="' + logClass + '" style="margin-bottom: 5px; padding: 5px; border-left: 3px solid ' + 
            (type === 'error' ? '#dc3232' : type === 'success' ? '#46b450' : '#0073aa') + 
            '; background: #f7f7f7;">' +
            '<span style="color: #666; font-size: 0.9em;">[' + timestamp + ']</span> ' +
            icon + ' ' + message +
            '</div>'
        );
        
        // Auto scroll to bottom
        $logContainer.scrollTop($logContainer[0].scrollHeight);
    }
    
    /**
     * Clear logs
     */
    function clearLogs() {
        $logContainer.html('<p>Los registros aparecerán aquí durante la operación.</p>');
    }
    
    /**
     * Show loading state for button
     */
    function setButtonLoading(button, loading) {
        if (loading) {
            button.prop('disabled', true).data('original-text', button.text()).text('Cargando...');
        } else {
            button.prop('disabled', false).text(button.data('original-text'));
        }
    }
    
    /**
     * Test connection
     */
    $('#test-connection').click(function() {
        var $btn = $(this);
        setButtonLoading($btn, true);
        clearLogs();
        addLog('Probando conexión con la API...');
        
        $.post(optica_vision_cl_ajax.ajax_url, {
            action: 'optica_vision_cl_test_connection',
            nonce: nonce
        }, function(response) {
            setButtonLoading($btn, false);
            
            if (response.success) {
                addLog('✅ Conexión exitosa', 'success');
                addLog('📊 Datos de muestra recibidos: ' + response.data.total_items + ' elementos');
                
                if (response.data.sample_data && response.data.sample_data.length > 0) {
                    addLog('🔍 Primer elemento: ' + JSON.stringify(response.data.sample_data[0], null, 2));
                }
            } else {
                addLog('❌ Error de conexión: ' + response.data, 'error');
            }
        }).fail(function() {
            setButtonLoading($btn, false);
            addLog('❌ Error de comunicación con el servidor', 'error');
        });
    });
    
    /**
     * Connect to API
     */
    $('#connect-api').click(function() {
        var $btn = $(this);
        setButtonLoading($btn, true);
        clearLogs();
        addLog('Conectando a la API...');
        
        $.post(optica_vision_cl_ajax.ajax_url, {
            action: 'optica_vision_cl_connect',
            nonce: nonce
        }, function(response) {
            setButtonLoading($btn, false);
            
            if (response.success) {
                addLog('✅ ' + response.data.message, 'success');
                location.reload(); // Reload to update connection status
            } else {
                addLog('❌ Error de conexión: ' + response.data, 'error');
            }
        }).fail(function() {
            setButtonLoading($btn, false);
            addLog('❌ Error de comunicación con el servidor', 'error');
        });
    });
    
    /**
     * Force reconnect
     */
    $('#force-reconnect').click(function() {
        var $btn = $(this);
        setButtonLoading($btn, true);
        clearLogs();
        addLog('Forzando reconexión...');
        
        $.post(optica_vision_cl_ajax.ajax_url, {
            action: 'optica_vision_cl_force_reconnect',
            nonce: nonce
        }, function(response) {
            setButtonLoading($btn, false);
            
            if (response.success) {
                addLog('✅ ' + response.data.message, 'success');
                location.reload(); // Reload to update connection status
            } else {
                addLog('❌ Error de reconexión: ' + response.data, 'error');
            }
        }).fail(function() {
            setButtonLoading($btn, false);
            addLog('❌ Error de comunicación con el servidor', 'error');
        });
    });
    
    /**
     * Get products from API
     */
    $('#get-products').click(function() {
        var $btn = $(this);
        setButtonLoading($btn, true);
        clearLogs();
        addLog('Obteniendo datos de lentes de contacto...');
        
        $.post(optica_vision_cl_ajax.ajax_url, {
            action: 'optica_vision_cl_get_products',
            nonce: nonce
        }, function(response) {
            setButtonLoading($btn, false);
            
            if (response.success) {
                addLog('✅ ' + response.data.message, 'success');
                addLog('📊 Total de productos: ' + response.data.total_count);
                
                if (response.data.grouping_info) {
                    var info = response.data.grouping_info;
                    addLog('📈 Análisis de agrupación:');
                    addLog('   • Grupos estimados: ' + info.estimated_groups);
                    addLog('   • Promedio de variaciones por grupo: ' + Math.round(info.average_variations_per_group * 100) / 100);
                    addLog('   • Marcas encontradas: ' + Object.keys(info.brands).join(', '));
                }
                
                if (response.data.sample_data && response.data.sample_data.length > 0) {
                    addLog('🔍 Muestra de productos:');
                    response.data.sample_data.slice(0, 3).forEach(function(item, index) {
                        addLog('   ' + (index + 1) + '. ' + item.marca + ' - ' + item.graduacion + ' (' + item.precio + ')');
                    });
                }
            } else {
                addLog('❌ Error: ' + response.data, 'error');
            }
        }).fail(function() {
            setButtonLoading($btn, false);
            addLog('❌ Error de comunicación con el servidor', 'error');
        });
    });
    
    /**
     * Sync products using batch processing
     */
    $('#sync-products').click(function() {
        var $btn = $(this);
        setButtonLoading($btn, true);
        clearLogs();
        addLog('🔄 Iniciando sincronización de lentes de contacto...');
        addLog('⏳ Obteniendo datos de la API...');
        
        // Step 1: Initialize batch sync
        $.ajax({
            url: optica_vision_cl_ajax.ajax_url,
            type: 'POST',
            timeout: 120000, // 2 minutes for API fetch
            data: {
                action: 'optica_vision_cl_sync_products',
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    addLog('✅ ' + response.data.message, 'success');
                    addLog('📦 Total de items: ' + response.data.total_items);
                    addLog('📁 Grupos a procesar: ' + response.data.total_groups);
                    addLog('⏳ Procesando en lotes...');
                    
                    // Start batch processing
                    processBatch(response.data.batch_id, 0, response.data.total_groups, $btn);
                } else {
                    setButtonLoading($btn, false);
                    addLog('❌ Error: ' + response.data, 'error');
                }
            },
            error: function(xhr, status, error) {
                setButtonLoading($btn, false);
                if (status === 'timeout') {
                    addLog('❌ Timeout al obtener datos de la API.', 'error');
                } else {
                    addLog('❌ Error de comunicación: ' + error, 'error');
                }
            }
        });
    });
    
    /**
     * Process a batch of products
     */
    function processBatch(batchId, offset, total, $btn) {
        $.ajax({
            url: optica_vision_cl_ajax.ajax_url,
            type: 'POST',
            timeout: 90000, // 90 seconds per batch (1 product)
            data: {
                action: 'optica_vision_cl_sync_batch',
                nonce: nonce,
                batch_id: batchId,
                offset: offset,
                batch_size: 1 // Process 1 product at a time to avoid timeout
            },
            success: function(response) {
                if (response.success) {
                    if (response.data.done) {
                        // All done!
                        setButtonLoading($btn, false);
                        addLog('✅ ' + response.data.message, 'success');
                        
                        var stats = response.data.stats;
                        addLog('📊 Estadísticas de sincronización:');
                        addLog('   • Productos creados: ' + (stats.created || 0));
                        addLog('   • Productos actualizados: ' + (stats.updated || 0));
                        addLog('   • Variaciones procesadas: ' + (stats.variations || 0));
                        addLog('   • Errores: ' + (stats.errors || 0));
                        
                        if (stats.errors > 0) {
                            addLog('⚠️ Se encontraron errores. Revisa los logs del servidor.', 'error');
                        }
                        
                        addLog('🎉 Sincronización completada exitosamente', 'success');
                        
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        // Continue processing
                        addLog('📦 ' + response.data.message);
                        
                        // Process next batch
                        processBatch(batchId, response.data.processed, total, $btn);
                    }
                } else {
                    setButtonLoading($btn, false);
                    addLog('❌ Error en lote: ' + response.data, 'error');
                }
            },
            error: function(xhr, status, error) {
                setButtonLoading($btn, false);
                if (status === 'timeout') {
                    addLog('❌ Timeout procesando lote. Intenta de nuevo.', 'error');
                } else {
                    addLog('❌ Error de comunicación: ' + error, 'error');
                }
            }
        });
    }
    
    /**
     * Delete products
     */
    $('#delete-products').click(function() {
        var $btn = $(this);
        
        if (!confirm('¿Estás seguro de que quieres eliminar todos los lentes de contacto sincronizados?\n\nEsta acción no se puede deshacer.')) {
            return;
        }
        
        setButtonLoading($btn, true);
        clearLogs();
        addLog('🗑️ Eliminando productos sincronizados...');
        
        $.post(optica_vision_cl_ajax.ajax_url, {
            action: 'optica_vision_cl_delete_products',
            nonce: nonce
        }, function(response) {
            setButtonLoading($btn, false);
            
            if (response.success) {
                addLog('✅ ' + response.data.message, 'success');
                addLog('📊 Productos eliminados: ' + response.data.deleted_count);
                
                if (response.data.errors > 0) {
                    addLog('⚠️ Errores durante la eliminación: ' + response.data.errors, 'error');
                }
                
                // Reload page after a delay
                setTimeout(function() {
                    location.reload();
                }, 1500);
                
            } else {
                addLog('❌ Error eliminando productos: ' + response.data, 'error');
            }
        }).fail(function() {
            setButtonLoading($btn, false);
            addLog('❌ Error de comunicación durante la eliminación', 'error');
        });
    });
    
    /**
     * Save API settings
     */
    $('#api-settings-form').submit(function(e) {
        e.preventDefault();
        
        var formData = {
            api_url: $('input[name="api_url"]').val(),
            api_username: $('input[name="api_username"]').val(),
            api_password: $('input[name="api_password"]').val()
        };
        
        clearLogs();
        addLog('💾 Guardando configuración...');
        
        // Save each setting individually
        $.post(ajaxurl, {
            action: 'update_option',
            option_name: 'optica_vision_cl_api_url',
            option_value: formData.api_url,
            _wpnonce: nonce
        }).done(function() {
            return $.post(ajaxurl, {
                action: 'update_option',
                option_name: 'optica_vision_cl_api_username',
                option_value: formData.api_username,
                _wpnonce: nonce
            });
        }).done(function() {
            return $.post(ajaxurl, {
                action: 'update_option',
                option_name: 'optica_vision_cl_api_password',
                option_value: formData.api_password,
                _wpnonce: nonce
            });
        }).done(function() {
            addLog('✅ Configuración guardada exitosamente', 'success');
            addLog('🔄 Recarga la página para aplicar los cambios');
        }).fail(function() {
            addLog('❌ Error guardando la configuración', 'error');
        });
    });
    
    /**
     * Debug attributes
     */
    $('#debug-attributes').click(function() {
        var $btn = $(this);
        setButtonLoading($btn, true);
        clearLogs();
        addLog('🔧 Ejecutando debug de atributos...');
        
        $.post(optica_vision_cl_ajax.ajax_url, {
            action: 'optica_vision_cl_debug_attributes',
            nonce: nonce
        }, function(response) {
            setButtonLoading($btn, false);
            
            if (response.success) {
                addLog('✅ ' + response.data.message, 'success');
                
                var debug = response.data.debug_info;
                
                // WooCommerce functions
                addLog('🔍 Funciones WooCommerce disponibles:');
                Object.keys(debug.woocommerce_functions).forEach(function(func) {
                    var status = debug.woocommerce_functions[func] ? '✅' : '❌';
                    addLog('   ' + status + ' ' + func);
                });
                
                // Prescription taxonomy
                if (debug.prescription_taxonomy) {
                    addLog('📋 Taxonomía de graduación:');
                    addLog('   Nombre: ' + debug.prescription_taxonomy.name);
                    addLog('   Existe: ' + (debug.prescription_taxonomy.exists ? '✅' : '❌'));
                }
                
                // All attributes
                if (debug.all_attributes) {
                    addLog('📊 Atributos WooCommerce (' + debug.all_attributes.length + '):');
                    debug.all_attributes.forEach(function(attr, index) {
                        if (index < 5) { // Show first 5
                            addLog('   • ' + attr.label + ' (' + attr.name + ') - Tipo: ' + attr.type);
                        }
                    });
                    if (debug.all_attributes.length > 5) {
                        addLog('   ... y ' + (debug.all_attributes.length - 5) + ' más');
                    }
                }
                
                // Prescription terms
                if (debug.prescription_terms) {
                    addLog('🏷️ Términos de graduación (' + debug.prescription_terms.length + '):');
                    debug.prescription_terms.slice(0, 10).forEach(function(term) {
                        addLog('   • ' + term.name + ' (slug: ' + term.slug + ', count: ' + term.count + ')');
                    });
                    if (debug.prescription_terms.length > 10) {
                        addLog('   ... y ' + (debug.prescription_terms.length - 10) + ' más');
                    }
                }
                
                // Attribute creation result
                addLog('🔧 Resultado de creación de atributo: ' + (debug.attribute_creation_attempt ? '✅' : '❌'));
                
                // Existing products
                if (debug.existing_cl_products) {
                    addLog('📦 Productos de lentes de contacto existentes (' + debug.existing_cl_products.length + '):');
                    debug.existing_cl_products.forEach(function(product) {
                        addLog('   • ' + product.name + ' (Tipo: ' + product.type + ', SKU: ' + product.sku + ')');
                        if (product.variations_count) {
                            addLog('     Variaciones: ' + product.variations_count);
                            if (product.sample_variations && product.sample_variations.length > 0) {
                                addLog('     Muestra de variaciones:');
                                product.sample_variations.forEach(function(variation) {
                                    addLog('       - SKU: ' + variation.sku + ', Precio: ' + variation.price);
                                    if (variation.attributes) {
                                        addLog('       - Atributos: ' + JSON.stringify(variation.attributes));
                                    }
                                });
                            }
                        }
                    });
                }
                
            } else {
                addLog('❌ Error en debug: ' + response.data, 'error');
            }
        }).fail(function() {
            setButtonLoading($btn, false);
            addLog('❌ Error de comunicación durante debug', 'error');
        });
    });
    
    /**
     * Auto-refresh connection status every 30 seconds
     */
    setInterval(function() {
        $.post(optica_vision_cl_ajax.ajax_url, {
            action: 'optica_vision_cl_check_connection',
            nonce: nonce
        }, function(response) {
            if (response.success) {
                var $status = $('#connection-status');
                if (response.data.connected) {
                    $status.html('<div class="notice notice-success inline"><p>✅ Conectado a la API</p></div>');
                } else {
                    $status.html('<div class="notice notice-warning inline"><p>⚠️ No conectado a la API</p></div>');
                }
            }
        });
    }, 30000);
}); 
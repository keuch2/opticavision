jQuery(document).ready(function($) {
    'use strict';
    
    // Estado global de la aplicación
    const appState = {
        isConnected: false,
        isSyncing: false,
        isScheduledSync: false,
        syncStats: {
            total: 0,
            processed: 0,
            created: 0,
            updated: 0,
            errors: 0
        },
        products: [],
        categories: new Set(),
        lastSync: null
    };
    
    // Elementos de la interfaz
    const $connectBtn = $('#optica-connect-btn');
    const $loadProductsBtn = $('#load-products-btn');
    const $syncProductsBtn = $('#sync-products-btn');
    const $toggleScheduledSyncBtn = $('#toggle-scheduled-sync');
    const $syncInterval = $('#sync-interval');
    const $apiInfo = $('#api-info');
    const $productsStats = $('#products-stats');
    const $totalProducts = $('#total-products');
    const $totalCategories = $('#total-categories');
    const $loadProgress = $('#load-progress');
    const $syncProgress = $('#sync-progress');
    const $syncResults = $('#sync-results');
    const $logsContainer = $('#sync-logs');
    const $scheduledStatus = $('#scheduled-status');
    const $scheduledSyncStatus = $('#scheduled-sync-status');
    
    // Enhanced AJAX function with nonce verification
    function makeSecureAjaxRequest(action, data = {}, successCallback = null, errorCallback = null) {
        // Add nonce to data
        data.action = action;
        data.nonce = optica_vision_vars.nonce;
        
        $.ajax({
            url: optica_vision_vars.ajaxurl,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    if (successCallback) successCallback(response.data);
                } else {
                    const errorMsg = response.data || 'Unknown error occurred';
                    if (errorCallback) {
                        errorCallback(errorMsg);
                    } else {
                        showNotice(errorMsg, 'error');
                    }
                }
            },
            error: function(xhr, status, error) {
                const errorMsg = 'Network error: ' + error;
                if (errorCallback) {
                    errorCallback(errorMsg);
                } else {
                    showNotice(errorMsg, 'error');
                }
            }
        });
    }
    
    // Show admin notice
    function showNotice(message, type = 'success') {
        const noticeClass = type === 'error' ? 'notice-error' : 'notice-success';
        const notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
        $('.wrap h1').after(notice);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            notice.fadeOut();
        }, 5000);
    }
    
    // API Settings Form
    $('#api-settings-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            action: 'optica_vision_save_settings',
            api_url: $('input[name="api_url"]').val(),
            api_username: $('input[name="api_username"]').val(),
            api_password: $('input[name="api_password"]').val(),
            optica_vision_settings_nonce: $('input[name="optica_vision_settings_nonce"]').val()
        };
        
        const submitBtn = $(this).find('input[type="submit"]');
        const originalText = submitBtn.val();
        submitBtn.val('Guardando...').prop('disabled', true);
        
        $.ajax({
            url: optica_vision_vars.ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showNotice('Configuración guardada exitosamente');
                    // Clear connection status to force reconnection
                    updateConnectionStatus(false);
                } else {
                    showNotice(response.data || 'Error al guardar configuración', 'error');
                }
            },
            error: function() {
                showNotice('Error de red al guardar configuración', 'error');
            },
            complete: function() {
                submitBtn.val(originalText).prop('disabled', false);
            }
        });
    });
    
    // Connection Management
    $('#optica-connect-btn').on('click', function() {
        const btn = $(this);
        const originalText = btn.text();
        const isConnected = btn.hasClass('button-secondary');
        
        if (isConnected) {
            // Disconnect
            btn.text('Desconectando...').prop('disabled', true);
            // Clear token and update UI
            localStorage.removeItem('optica_vision_token');
            updateConnectionStatus(false);
            btn.text('Conectar a la API').removeClass('button-secondary').addClass('button-primary').prop('disabled', false);
            return;
        }
        
        // Connect
        btn.text('Conectando...').prop('disabled', true);
        
        makeSecureAjaxRequest('optica_vision_connect', {}, 
            function(response) {
                showNotice('Conectado exitosamente a la API');
                updateConnectionStatus(true);
                btn.text('Desconectar de la API').removeClass('button-primary').addClass('button-secondary');
            },
            function(error) {
                btn.text(originalText);
                showNotice('Error de conexión: ' + error, 'error');
            }
        );
        
        btn.prop('disabled', false);
    });
    
    // Force Reconnect button
    $('#force-reconnect-btn').on('click', function() {
        const btn = $(this);
        const originalText = btn.text();
        
        btn.text('Reconectando...').prop('disabled', true);
        
        makeSecureAjaxRequest('optica_vision_force_reconnect', {},
            function(response) {
                updateConnectionStatus(true);
                showNotice(response.message);
                btn.text(originalText).prop('disabled', false);
            },
            function(error) {
                showNotice('Error al reconectar: ' + error, 'error');
                btn.text(originalText).prop('disabled', false);
            }
        );
    });
    
    // Update connection status UI
    function updateConnectionStatus(connected) {
        const statusElement = $('.api-status');
        const infoContainer = $('#api-info');
        const forceReconnectBtn = $('#force-reconnect-btn');
        
        if (connected) {
            statusElement.removeClass('status-disconnected').addClass('status-connected').text('Conectado');
            infoContainer.show();
            forceReconnectBtn.show();
            loadSyncLogs();
            loadBackups();
        } else {
            statusElement.removeClass('status-connected').addClass('status-disconnected').text('Desconectado');
            infoContainer.hide();
            forceReconnectBtn.hide();
        }
    }
    
    // Test Connection
    $('#test-connection-btn').on('click', function() {
        const btn = $(this);
        const originalText = btn.text();
        
        btn.text('Probando conexión...').prop('disabled', true);
        
        makeSecureAjaxRequest('optica_vision_test_connection', {},
            function(response) {
                showNotice('Prueba de conexión exitosa');
                console.log('Sample data:', response.sample_data);
            },
            function(error) {
                showNotice('Error en prueba de conexión: ' + error, 'error');
            }
        );
        
        btn.text(originalText).prop('disabled', false);
    });
    
    // Product Sync
    $('#sync-products-btn').on('click', function() {
        const btn = $(this);
        const originalText = btn.text();
        const progressContainer = $('#sync-progress');
        const resultsContainer = $('#sync-results');
        
        btn.text('Sincronizando...').prop('disabled', true);
        progressContainer.show();
        resultsContainer.hide();
        
        // Simulate progress (real progress would come from server-sent events)
        let progress = 0;
        const progressInterval = setInterval(() => {
            progress += Math.random() * 15;
            if (progress > 90) progress = 90;
            updateProgress(progressContainer, progress);
        }, 500);
        
        makeSecureAjaxRequest('optica_vision_sync_products', {},
            function(response) {
                clearInterval(progressInterval);
                updateProgress(progressContainer, 100);
                
                const stats = response.stats;
                const resultHtml = `
                    <div class="sync-results">
                        <h4>Sincronización Completada</h4>
                        <ul>
                            <li>Productos creados: <strong>${stats.created || 0}</strong></li>
                            <li>Productos actualizados: <strong>${stats.updated || 0}</strong></li>
                            <li>Productos omitidos: <strong>${stats.skipped || 0}</strong></li>
                            <li>Errores: <strong>${stats.errors || 0}</strong></li>
                        </ul>
                    </div>
                `;
                
                resultsContainer.html(resultHtml).show();
                showNotice(response.message);
                loadSyncLogs(); // Refresh logs
            },
            function(error) {
                clearInterval(progressInterval);
                progressContainer.hide();
                showNotice('Error en sincronización: ' + error, 'error');
            }
        );
        
        btn.text(originalText).prop('disabled', false);
    });
    
    // Update progress bar
    function updateProgress(container, percentage) {
        const progressBar = container.find('.progress-bar');
        const progressText = container.find('.progress-text');
        
        progressBar.css('width', percentage + '%');
        progressText.text(Math.round(percentage) + '%');
    }
    
    // Load Sync Logs
    function loadSyncLogs() {
        const logsContainer = $('#sync-logs');
        
        // Only load if logs container exists
        if (logsContainer.length === 0) {
            return;
        }
        
        makeSecureAjaxRequest('optica_vision_get_sync_logs', {},
            function(logs) {
                if (logs.length === 0) {
                    logsContainer.html('<p>No hay registros disponibles.</p>');
                    return;
                }
                
                let logHtml = '';
                logs.forEach(function(log) {
                    const levelClass = log.level === 'error' ? 'log-error' : 
                                      log.level === 'success' ? 'log-success' : 'log-info';
                    
                    logHtml += `
                        <div class="log-entry ${levelClass}">
                            <span class="log-time">${log.formatted_time}</span>
                            <span class="log-level">[${log.level.toUpperCase()}]</span>
                            <span class="log-message">${log.message}</span>
                        </div>
                    `;
                });
                
                logsContainer.html(logHtml);
            },
            function(error) {
                logsContainer.html('<p>Error al cargar registros: ' + error + '</p>');
            }
        );
    }
    
    // Refresh logs button
    $('#refresh-logs-btn').on('click', function() {
        loadSyncLogs();
        showNotice('Registros actualizados');
    });
    
    // Load Backups
    function loadBackups() {
        const backupContainer = $('#backup-list');
        
        // Only load if backup container exists
        if (backupContainer.length === 0) {
            return;
        }
        
        makeSecureAjaxRequest('optica_vision_get_backups', {},
            function(backups) {
                if (backups.length === 0) {
                    backupContainer.html('<p>No hay respaldos disponibles.</p>');
                    return;
                }
                
                let backupHtml = '<div class="backup-list">';
                backups.forEach(function(backup) {
                    backupHtml += `
                        <div class="backup-item" style="padding: 10px; border: 1px solid #ddd; margin: 5px 0;">
                            <strong>Respaldo del ${backup.formatted_date}</strong><br>
                            <small>Productos: ${backup.product_count}</small><br>
                            <button class="button restore-backup-btn" data-key="${backup.key}">Restaurar</button>
                        </div>
                    `;
                });
                backupHtml += '</div>';
                
                backupContainer.html(backupHtml);
            },
            function(error) {
                backupContainer.html('<p>Error al cargar respaldos: ' + error + '</p>');
            }
        );
    }
    
    // Restore Backup
    $(document).on('click', '.restore-backup-btn', function() {
        const btn = $(this);
        const backupKey = btn.data('key');
        
        if (!confirm('¿Está seguro de que desea restaurar este respaldo? Esta acción sobrescribirá los productos actuales.')) {
            return;
        }
        
        const originalText = btn.text();
        btn.text('Restaurando...').prop('disabled', true);
        
        makeSecureAjaxRequest('optica_vision_restore_backup', { backup_key: backupKey },
            function(response) {
                showNotice(response.message);
                loadSyncLogs(); // Refresh logs
            },
            function(error) {
                showNotice('Error al restaurar respaldo: ' + error, 'error');
            }
        );
        
        btn.text(originalText).prop('disabled', false);
    });
    
    // Load Products button
    $(document).on('click', '#load-products-btn', function() {
        const btn = $(this);
        const originalText = btn.text();
        const progressContainer = $('#load-progress');
        const statsContainer = $('#products-stats');
        
        console.log('Load Products button clicked');
        
        btn.text('Cargando...').prop('disabled', true);
        progressContainer.show();
        
        console.log('About to make AJAX request for get_products');
        console.log('AJAX URL:', optica_vision_vars.ajaxurl);
        console.log('Nonce:', optica_vision_vars.nonce);
        makeSecureAjaxRequest('optica_vision_get_products', { limit: 50 },
            function(response) {
                console.log('Products loaded successfully:', response);
                
                // Products loaded successfully
                const products = response;
                const categories = new Set();
                
                // Extract categories from products
                products.forEach(function(product) {
                    if (product.marca) {
                        categories.add(product.marca);
                    }
                    // Extract type from description (first 2 characters)
                    const tipo = product.descripcion ? product.descripcion.substring(0, 2) : '';
                    if (tipo) {
                        categories.add(tipo);
                    }
                });
                
                // Update stats
                $('#total-products').text(products.length);
                $('#total-categories').text(categories.size);
                
                // Show stats container
                statsContainer.show();
                
                showNotice(`Se cargaron ${products.length} productos y ${categories.size} categorías`);
                console.log('Updated stats - Products:', products.length, 'Categories:', categories.size);
                
                btn.text(originalText).prop('disabled', false);
                progressContainer.hide();
            },
            function(error) {
                console.error('Load products error:', error);
                showNotice('Error al cargar productos: ' + error, 'error');
                
                btn.text(originalText).prop('disabled', false);
                progressContainer.hide();
            }
        );
    });
    
    // Debug: Check if load products button exists
    console.log('Load products button exists:', $('#load-products-btn').length > 0);
    console.log('API info container:', $('#api-info').length > 0);
    
    // Check connection status on page load
    makeSecureAjaxRequest('optica_vision_check_connection', {},
        function(response) {
            updateConnectionStatus(response.connected);
        },
        function(error) {
            // If check connection fails, assume disconnected
            updateConnectionStatus(false);
            console.log('Connection check failed:', error);
        }
    );
    
    // Auto-refresh logs every 30 seconds during active operations
    let autoRefreshInterval;
    
    function startAutoRefresh() {
        autoRefreshInterval = setInterval(loadSyncLogs, 30000);
    }
    
    function stopAutoRefresh() {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
        }
    }
    
    // Start auto-refresh when page loads
    startAutoRefresh();
    
    // Stop auto-refresh when user is inactive for 5 minutes
    let inactivityTimer;
    function resetInactivityTimer() {
        clearTimeout(inactivityTimer);
        inactivityTimer = setTimeout(stopAutoRefresh, 300000); // 5 minutes
    }
    
    $(document).on('click keypress', resetInactivityTimer);
    resetInactivityTimer();
});

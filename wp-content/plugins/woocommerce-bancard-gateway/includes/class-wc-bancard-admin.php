<?php
/**
 * Bancard Admin Panel
 * Página de administración para diagnósticos y configuración
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Bancard_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Añade menú de administración
     */
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __('Bancard Diagnostics', 'wc-bancard'),
            __('Bancard Diagnostics', 'wc-bancard'),
            'manage_woocommerce',
            'bancard-diagnostics',
            array($this, 'diagnostics_page')
        );
    }
    
    /**
     * Encola scripts de administración
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'bancard-diagnostics') === false) {
            return;
        }
        
        wp_enqueue_style('bancard-admin', WC_BANCARD_PLUGIN_URL . 'assets/admin.css', array(), WC_BANCARD_VERSION);
    }
    
    /**
     * Página de diagnósticos
     */
    public function diagnostics_page() {
        // Verificar permisos
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('No tienes permisos para acceder a esta página.', 'wc-bancard'));
        }
        
        // Incluir clase de diagnósticos
        require_once WC_BANCARD_PLUGIN_DIR . 'includes/class-wc-bancard-diagnostics.php';
        
        // Manejar acciones
        if (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'run_diagnostics':
                    $this->handle_run_diagnostics();
                    break;
                case 'clear_logs':
                    $this->handle_clear_logs();
                    break;
            }
        }
        
        // Mostrar página
        ?>
        <div class="wrap">
            <h1>🔧 Bancard Gateway - Diagnósticos</h1>
            
            <div class="card" style="max-width: none; margin: 20px 0;">
                <h2>Panel de Control</h2>
                <p>Utiliza estas herramientas para diagnosticar problemas con el gateway Bancard.</p>
                
                <p>
                    <a href="<?php echo admin_url('admin.php?page=bancard-diagnostics&action=run_diagnostics'); ?>" 
                       class="button button-primary">🔍 Ejecutar Diagnóstico Completo</a>
                    
                    <a href="<?php echo admin_url('admin.php?page=bancard-diagnostics&action=clear_logs'); ?>" 
                       class="button button-secondary" 
                       onclick="return confirm('¿Estás seguro de que quieres limpiar los logs?')">🗑️ Limpiar Logs</a>
                    
                    <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=checkout&section=bancard'); ?>" 
                       class="button">⚙️ Configuración Gateway</a>
                </p>
            </div>
            
            <?php if (isset($_GET['action']) && $_GET['action'] === 'run_diagnostics'): ?>
                <?php echo WC_Bancard_Diagnostics::generate_report(); ?>
            <?php endif; ?>
            
            <div class="card" style="max-width: none; margin: 20px 0;">
                <h2>📋 Checklist Manual de Verificación</h2>
                
                <h3>1. ✅ Verificaciones Básicas</h3>
                <ul class="ul-disc">
                    <li><strong>Plugin activo:</strong> Verificar en Plugins que "WooCommerce Bancard Gateway" está activado</li>
                    <li><strong>WooCommerce activo:</strong> WooCommerce debe estar instalado y activado</li>
                    <li><strong>Gateway habilitado:</strong> WooCommerce → Settings → Payments → Bancard → Enable</li>
                </ul>
                
                <h3>2. 🔐 Configuración de Credenciales</h3>
                <ul class="ul-disc">
                    <li><strong>Public Key:</strong> Debe estar configurada (proporcionada por Bancard)</li>
                    <li><strong>Private Key:</strong> Debe estar configurada (proporcionada por Bancard)</li>
                    <li><strong>Environment:</strong> Production (para producción) o Sandbox (para pruebas)</li>
                </ul>
                
                <h3>3. 💱 Configuración de Moneda</h3>
                <ul class="ul-disc">
                    <li><strong>Moneda PYG:</strong> Si la tienda está en Guaraníes (PYG), no necesita exchange rate</li>
                    <li><strong>Otra moneda:</strong> Si está en USD, EUR, etc., debe configurar Exchange Rate</li>
                    <li><strong>Exchange Rate:</strong> Tasa de conversión a Guaraníes (ej: 7000 para USD a PYG)</li>
                </ul>
                
                <h3>4. 🛒 Verificaciones de Carrito</h3>
                <ul class="ul-disc">
                    <li><strong>Carrito no vacío:</strong> Debe haber productos en el carrito</li>
                    <li><strong>Total > 0:</strong> El total del carrito debe ser mayor a cero</li>
                    <li><strong>Productos válidos:</strong> Los productos deben estar publicados y disponibles</li>
                </ul>
                
                <h3>5. 🐛 Debugging Avanzado</h3>
                <ul class="ul-disc">
                    <li><strong>Error logs:</strong> Revisar wp-content/debug.log por errores</li>
                    <li><strong>Browser console:</strong> Revisar consola del navegador por errores JS</li>
                    <li><strong>Network tab:</strong> Verificar llamadas AJAX al checkout</li>
                </ul>
            </div>
            
            <div class="notice notice-info">
                <h3>🔍 Información de Debug</h3>
                <p>El plugin incluye logging extenso que se puede revisar en los logs de WordPress. 
                   Los logs con prefijo <code>[BANCARD DEBUG]</code> muestran información detallada del proceso.</p>
                
                <p><strong>Cómo acceder a los logs:</strong></p>
                <ul>
                    <li>Habilitar <code>WP_DEBUG</code> y <code>WP_DEBUG_LOG</code> en wp-config.php</li>
                    <li>Revisar <code>wp-content/debug.log</code></li>
                    <li>O usar plugin como "WP Log Viewer" para ver logs desde admin</li>
                </ul>
            </div>
        </div>
        
        <style>
        .ul-disc {
            list-style-type: disc;
            margin-left: 2em;
        }
        .card h3 {
            margin-top: 20px;
            color: #23282d;
        }
        .notice h3 {
            margin-top: 0;
        }
        .status-ok {
            background: #46b450 !important;
            color: white !important;
        }
        .status-warning {
            background: #ffb900 !important;
            color: white !important;
        }
        .status-error {
            background: #dc3232 !important;
            color: white !important;
        }
        </style>
        <?php
    }
    
    /**
     * Maneja ejecución de diagnósticos
     */
    private function handle_run_diagnostics() {
        // Los diagnósticos se ejecutan automáticamente al mostrar la página
        // No necesitamos hacer nada especial aquí
    }
    
    /**
     * Maneja limpieza de logs
     */
    private function handle_clear_logs() {
        // Limpiar logs de WordPress
        $log_file = WP_CONTENT_DIR . '/debug.log';
        if (file_exists($log_file)) {
            file_put_contents($log_file, '');
        }
        
        // Mostrar mensaje de éxito
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible"><p>Logs limpiados exitosamente.</p></div>';
        });
    }
}

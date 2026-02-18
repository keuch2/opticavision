# Optica Vision Contact Lenses Sync

Plugin especializado para sincronizar lentes de contacto desde la API de Optica Vision hacia WooCommerce, creando productos variables con variaciones por graduación.

## Características Principales

### 🎯 Funcionalidad Especializada
- **Productos Variables**: Agrupa lentes de contacto por marca y tipo base
- **Variaciones por Graduación**: Cada graduación se convierte en una variación del producto
- **Gestión Individual**: Precio e inventario independiente por variación
- **Sincronización Inteligente**: Actualiza existentes o crea nuevos según corresponda

### 📊 Estructura de Datos

#### API Endpoint
- **URL**: `mercaderias_web_lc`
- **Tipo**: Array directo de productos (no wrapped en objeto)
- **Cantidad**: ~241 productos de lentes de contacto

#### Estructura de Producto API
```json
{
  "tipo": "LC",
  "codigo": "1584",
  "marca": "Biofinity", 
  "descripcion": "Biofinity Simple visión Lentes de Co ME 8.60 14.0 INCOLORO",
  "graduacion": "-00.25",
  "precio": 390000,
  "existencia": "3.00"
}
```

#### Resultado en WooCommerce

**Producto Variable Creado:**
- **Nombre**: "Biofinity Biofinity"
- **SKU**: "CL_biofinity_biofinity"
- **Tipo**: Variable Product
- **Categorías**: Lentes de Contacto > Biofinity

**Variaciones:**
- Atributo: `Graduación` (prescription)
- Valores: -00.25, -00.50, -00.75, etc.
- Cada variación tiene precio y stock individual

## 🔧 Instalación y Configuración

### Requisitos
- WordPress 5.0+
- WooCommerce 5.0+
- PHP 7.4+

### Instalación
1. Subir el plugin a `/wp-content/plugins/optica-vision-contact-lenses-sync/`
2. Activar desde el panel de WordPress
3. Ir a **CL Sync** en el menú del admin

### Configuración Inicial
1. **API Settings**: Configurar URL, usuario y contraseña
2. **Conexión**: Probar y establecer conexión
3. **Sincronización**: Ejecutar primera sincronización

## 📋 Uso del Plugin

### Panel de Administración
Accesible desde **CL Sync** en el menú principal de WordPress:

#### Secciones Disponibles:
1. **Configuración de API**
   - URL de API
   - Credenciales de usuario
   - Guardado de configuración

2. **Estado de Conexión**
   - Indicador visual de conexión
   - Botones de prueba y reconexión
   - Auto-refresh cada 30 segundos

3. **Sincronización**
   - Botón de sincronización principal
   - Previsualización de datos de API
   - Eliminación de productos sincronizados

4. **Registro de Actividades**
   - Logs en tiempo real
   - Historial de operaciones
   - Estadísticas detalladas

### Proceso de Sincronización

#### 1. Agrupación Inteligente
```php
// Ejemplo de agrupación
$products = [
    ["marca" => "Biofinity", "graduacion" => "-00.25", "codigo" => "1584"],
    ["marca" => "Biofinity", "graduacion" => "-00.50", "codigo" => "1585"],
    // ...
];

// Resultado: 1 producto variable con 2+ variaciones
```

#### 2. Creación de Producto Variable
- **Base SKU**: `CL_marca_descripcion_limpia`
- **Nombre**: Marca + Descripción base
- **Atributos**: Graduación como atributo variable
- **Categorías**: Lentes de Contacto > [Marca]

#### 3. Creación de Variaciones
- **SKU Individual**: Código original del API
- **Atributo**: Valor de graduación
- **Precio**: Precio individual del API
- **Stock**: Existencia individual

### Estadísticas y Seguimiento

#### Métricas de Sincronización:
- ✅ **Productos creados**: Nuevos productos variables
- 🔄 **Productos actualizados**: Productos existentes modificados  
- 📊 **Variaciones procesadas**: Total de variaciones creadas/actualizadas
- ❌ **Errores**: Fallos durante el proceso

#### Última Sincronización:
- Timestamp de ejecución
- Estadísticas completas
- Total de grupos procesados

## 🛠️ Funciones Técnicas

### Clases Principales

#### `Optica_Vision_CL_API`
- Conexión al endpoint `mercaderias_web_lc`
- Autenticación JWT
- Manejo de errores y reconexión

#### `Optica_Vision_CL_Product_Sync`
- Agrupación de productos por base
- Creación de productos variables
- Gestión de variaciones y atributos

#### `Optica_Vision_CL_Admin`
- Interfaz de administración
- Handlers AJAX
- Configuración y logs

### Hooks y Filtros
```php
// Programación de sincronización
add_action('optica_vision_cl_scheduled_sync', 'run_sync');

// Activación del plugin
register_activation_hook(__FILE__, 'optica_vision_cl_sync_activate');
```

### Metadatos de Tracking
```php
// Productos sincronizados
'_optica_vision_cl_sync' => true
'_optica_vision_cl_last_sync' => timestamp
'_optica_vision_cl_raw_data' => json_data
```

## 🔍 Solución de Problemas

### Problemas Comunes

#### "No conectado a la API"
- Verificar credenciales en configuración
- Probar conexión manual
- Revisar logs del servidor

#### "Error de sincronización"
- Verificar permisos de WordPress
- Revisar logs de error en PHP
- Comprobar espacio disponible

#### "Variaciones no se crean"
- Verificar atributo "Graduación" existe
- Comprobar permisos de WooCommerce
- Revisar estructura de datos del API

### Debug y Logs
- **WordPress Error Log**: `/wp-content/debug.log`
- **Plugin Logs**: Opción `optica_vision_cl_sync_logs`
- **Test Script**: `/test-cl-api.php`

### Test de Conexión
```bash
# URL de prueba
curl http://tu-sitio.com/wp-content/plugins/optica-vision-contact-lenses-sync/test-cl-api.php
```

## 📦 Estructura de Archivos

```
optica-vision-contact-lenses-sync/
├── optica-vision-contact-lenses-sync.php    # Plugin principal
├── includes/
│   ├── class-optica-vision-cl-api.php       # API handler
│   └── class-optica-vision-cl-product-sync.php # Sincronización
├── admin/
│   ├── class-optica-vision-cl-admin.php     # Admin interface
│   └── js/admin.js                          # JavaScript admin
├── test-cl-api.php                          # Script de prueba
└── README.md                                # Documentación
```

## 🚀 Características Avanzadas

### Agrupación Inteligente
- Limpieza automática de descripciones
- Detección de productos base
- Manejo de variantes por marca

### Gestión de Atributos
- Creación automática del atributo "Graduación"
- Términos de graduación dinámicos
- Vinculación correcta con variaciones

### Sincronización Eficiente
- Procesamiento por lotes
- Actualización incremental
- Manejo de memoria optimizado

## 📄 Licencia

Plugin desarrollado por Mister Co. para Optica Vision.

---

**Versión**: 1.0.0  
**Compatibilidad**: WordPress 5.0+, WooCommerce 5.0+  
**Autor**: Mister Co. 
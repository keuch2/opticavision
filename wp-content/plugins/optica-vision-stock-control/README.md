# OpticaVision Stock Control

Plugin de WordPress para controlar la visibilidad de productos WooCommerce según su disponibilidad de stock.

## Descripción

Este plugin permite a los administradores de OpticaVision controlar qué productos se muestran en la tienda basándose en su disponibilidad de stock:

- **Productos Simples**: Ocultar productos simples que no tengan stock disponible
- **Productos Variables**: Ocultar productos variables que no tengan ninguna variación con stock disponible

## Características

✓ Control granular de visibilidad por tipo de producto  
✓ Interfaz de administración intuitiva  
✓ Estadísticas en tiempo real de productos afectados  
✓ Integración con el sistema de logging de OpticaVision  
✓ Aplicación automática en toda la tienda (búsquedas, categorías, listados)  
✓ Sin impacto en el rendimiento  
✓ Compatible con caché y sistemas de optimización  

## Requisitos

- WordPress 5.8 o superior
- WooCommerce 5.0 o superior
- PHP 7.4 o superior

## Instalación

1. Sube la carpeta `optica-vision-stock-control` al directorio `/wp-content/plugins/`
2. Activa el plugin desde el menú 'Plugins' en WordPress
3. Ve a **WooCommerce > Control de Stock** para configurar el plugin

## Configuración

### Panel de Administración

Accede a la configuración en: **WooCommerce > Control de Stock**

#### Opciones Disponibles:

**1. Productos Simples sin Stock**
- Activa esta opción para ocultar productos simples que no tengan stock
- Los productos permanecen en la base de datos, solo se ocultan del frontend

**2. Productos Variables sin Variaciones**
- Activa esta opción para ocultar productos variables que no tengan ninguna variación con stock
- El plugin verifica todas las variaciones antes de ocultar el producto padre

### Estadísticas

El panel muestra en tiempo real:
- Número de productos simples sin stock
- Número de productos variables sin variaciones disponibles

## Funcionamiento Técnico

### Filtros Aplicados

El plugin utiliza los siguientes filtros de WooCommerce:

1. `woocommerce_product_is_visible` - Controla la visibilidad individual de productos
2. `woocommerce_product_query_meta_query` - Optimiza las queries de productos

### Integración con OpticaVision

El plugin se integra con el sistema de logging existente:

```php
// Logs de debug cuando se oculta un producto
optica_log_debug('Producto simple sin stock ocultado', $data);

// Logs de información cuando se actualizan settings
optica_log_info('Configuración de Stock Control actualizada', $settings);
```

## Casos de Uso

### Caso 1: Ocultar Productos Simples Sin Stock
```
Configuración:
☑ Ocultar productos simples que no tengan stock
☐ Ocultar productos variables sin variaciones

Resultado:
- Armazón Ray-Ban sin stock → OCULTO
- Lentes de contacto sin stock → OCULTO
- Producto variable con variaciones disponibles → VISIBLE
```

### Caso 2: Ocultar Productos Variables Sin Disponibilidad
```
Configuración:
☐ Ocultar productos simples que no tengan stock
☑ Ocultar productos variables sin variaciones

Resultado:
- Producto variable con todas las variaciones sin stock → OCULTO
- Producto variable con al menos 1 variación con stock → VISIBLE
- Productos simples → NO AFECTADOS
```

### Caso 3: Control Total
```
Configuración:
☑ Ocultar productos simples que no tengan stock
☑ Ocultar productos variables sin variaciones

Resultado:
Solo se muestran productos con stock disponible (simples o variables)
```

## Preguntas Frecuentes

**¿Los productos se eliminan de la base de datos?**
No, los productos permanecen intactos. Solo se ocultan del frontend.

**¿Afecta al rendimiento del sitio?**
No, el plugin utiliza filtros nativos de WooCommerce optimizados.

**¿Es compatible con caché?**
Sí, es completamente compatible con sistemas de caché como WP Super Cache, W3 Total Cache, etc.

**¿Puedo desactivar temporalmente el plugin?**
Sí, simplemente desactiva los checkboxes en la configuración o desactiva el plugin completo.

**¿Se integra con otros plugins de OpticaVision?**
Sí, utiliza el sistema de logging centralizado y respeta todas las convenciones del proyecto.

## Soporte

Para soporte técnico, contacta al equipo de desarrollo de OpticaVision.

## Changelog

### 1.0.0 - 2024-11-04
- Versión inicial
- Control de productos simples sin stock
- Control de productos variables sin variaciones
- Panel de administración con estadísticas
- Integración con sistema de logging OpticaVision

## Créditos

Desarrollado por el equipo de OpticaVision
Compatible con WooCommerce y el ecosistema OpticaVision

## Licencia

Uso exclusivo de OpticaVision

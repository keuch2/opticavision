# OpticaVision Theme

Tema personalizado y optimizado para Óptica Visión - Reemplazo completo del tema Divi con funcionalidades avanzadas de WooCommerce.

## 📋 Descripción

El **OpticaVision Theme** es un tema WordPress completamente personalizado diseñado específicamente para Óptica Visión. Este tema reemplaza completamente el tema Divi existente, proporcionando una solución optimizada, modular y escalable que incorpora todas las funcionalidades previamente implementadas y añade nuevas características avanzadas.

## ✨ Características Principales

### 🏠 Homepage Completa
- **Hero Slider**: Slider full-width con imágenes dinámicas y contenido personalizable
- **Carrusel de Marcas**: Integración con el sistema de marcas existente (55 subcategorías)
- **Banners Promocionales**: Dos columnas con contenido dinámico
- **Carruseles de Productos**: 
  - Últimos productos
  - Lentes de contacto
  - Lentes de sol
  - Armazones
- **Newsletter**: Sección de suscripción con AJAX

### 🛍️ Integración WooCommerce Completa
- **Páginas de Lista**: Shop, categorías y búsqueda con filtros AJAX
- **Página de Producto**: Galería optimizada, variaciones y quick view
- **Carrito y Checkout**: Flujo optimizado y moderno
- **Sistema de Filtros**: Integración completa con `woo-ajax-filters`

### 🎨 Diseño y UX
- **Responsive Design**: Mobile-first con breakpoints optimizados
- **Accesibilidad**: ARIA labels, navegación por teclado, screen readers
- **Performance**: Lazy loading, preload crítico, optimizaciones avanzadas
- **SEO**: Meta tags, structured data, breadcrumbs

### 🔧 Funcionalidades Técnicas
- **Sistema de Carruseles**: Clase avanzada con touch, teclado y autoplay
- **AJAX Avanzado**: Quick view, add to cart, filtros en tiempo real
- **Customizer**: Panel completo de personalización
- **Breadcrumbs**: Sistema completo con soporte WooCommerce
- **Logging**: Integración con sistema existente OpticaVision_Logger

## 📁 Estructura del Proyecto

```
opticavision-theme/
├── assets/
│   ├── css/
│   │   ├── main.css              # Estilos principales del homepage
│   │   └── woocommerce.css       # Estilos específicos de WooCommerce
│   ├── js/
│   │   ├── main.js               # JavaScript principal del theme
│   │   └── carousel.js           # Sistema de carruseles avanzado
│   └── images/                   # Imágenes del theme
├── includes/
│   ├── class-theme-setup.php     # Configuración principal del theme
│   ├── class-woocommerce.php     # Integración WooCommerce
│   ├── class-carousel.php        # Sistema de carruseles
│   ├── class-breadcrumbs.php     # Sistema de breadcrumbs
│   ├── class-customizer.php      # Configuración del customizer
│   ├── template-functions.php    # Funciones de template
│   └── ajax-handlers.php         # Manejadores AJAX
├── woocommerce/
│   ├── archive-product.php       # Página principal de productos
│   ├── taxonomy-product_cat.php  # Páginas de categorías
│   └── single-product.php        # Página de producto individual
├── templates/                    # Templates adicionales
├── functions.php                 # Funciones principales del theme
├── style.css                     # Hoja de estilos principal
├── index.php                     # Template principal
├── front-page.php               # Homepage
├── header.php                    # Header del theme
├── footer.php                    # Footer del theme
├── search.php                    # Página de búsqueda
└── README.md                     # Este archivo
```

## 🚀 Instalación

### Requisitos
- WordPress 6.0+
- PHP 7.4+
- WooCommerce 7.0+
- Plugin `woo-ajax-filters` (incluido en el proyecto)

### Pasos de Instalación

1. **Backup del sitio actual**
   ```bash
   # Crear backup completo antes de proceder
   ```

2. **Subir el theme**
   ```bash
   # Subir la carpeta opticavision-theme a wp-content/themes/
   ```

3. **Activar el theme**
   - Ir a Apariencia > Temas
   - Activar "OpticaVision Theme"

4. **Configurar menús**
   - Ir a Apariencia > Menús
   - Asignar menús a las ubicaciones correspondientes

5. **Configurar widgets**
   - Ir a Apariencia > Widgets
   - Configurar las áreas de widgets del footer

## ⚙️ Configuración

### Customizer
El theme incluye un panel completo de personalización:

- **Apariencia > Personalizar > OpticaVision Theme**
  - Hero Slider: Configurar slides del hero principal
  - Marcas Destacadas: Personalizar sección de marcas
  - Banners Promocionales: Configurar banners
  - Colores: Personalizar paleta de colores
  - Tipografía: Seleccionar fuentes
  - Diseño: Configurar layout y contenedores
  - Footer: Personalizar footer y copyright

### Menús Requeridos
- **Primary**: Menú principal del header
- **Footer**: Menú del footer
- **Mobile**: Menú móvil (opcional)

### Widgets Areas
- **Sidebar Principal**: Barra lateral
- **Footer Columna 1-4**: Cuatro áreas de widgets en el footer

## 🔌 Integración con Plugins Existentes

### woo-ajax-filters
El theme está completamente integrado con el plugin de filtros AJAX:
- Shortcode `[wc_ajax_filters]` en páginas de lista
- Shortcode `[wc_ajax_filtered_products]` para productos
- Modal móvil completamente funcional
- Filtros por marca, precio y categorías

### Plugins OpticaVision
Compatible con todos los plugins personalizados existentes:
- `optica-vision-api-sync`
- `optica-vision-contact-lenses-sync`
- `optica-vision-fallback`
- `optica-vision-image-matcher`

### Sistema de Marcas
Integración completa con el sistema de marcas existente:
- Función `ovc_get_marcas_subcategories()`
- Mega menú de marcas (mantenido del theme anterior)
- Carrusel de marcas en homepage

## 🎯 Shortcodes Disponibles

### Carrusel de Productos
```php
[opticavision_products_carousel type="latest" limit="8"]
[opticavision_products_carousel type="contact_lenses" limit="6"]
[opticavision_products_carousel type="sunglasses" limit="6"]
[opticavision_products_carousel type="frames" limit="6"]
```

**Parámetros:**
- `type`: latest, featured, on_sale, contact_lenses, sunglasses, frames
- `limit`: Número de productos a mostrar
- `category`: Slug de categoría específica
- `class`: Clases CSS adicionales

## 🛠️ Desarrollo

### Estructura de Clases
El theme utiliza una arquitectura orientada a objetos:

```php
// Configuración principal
OpticaVision_Theme_Setup

// Integración WooCommerce
OpticaVision_WooCommerce

// Sistema de carruseles
OpticaVision_Carousel

// Breadcrumbs
OpticaVision_Breadcrumbs

// Customizer
OpticaVision_Customizer
```

### Hooks Disponibles
```php
// Filtros
apply_filters('opticavision_breadcrumbs', $breadcrumbs, $args);
apply_filters('opticavision_carousel_products', $products, $type);

// Acciones
do_action('opticavision_before_header');
do_action('opticavision_after_footer');
```

### AJAX Endpoints
```javascript
// Obtener contador del carrito
opticavision_get_cart_count

// Vista rápida de producto
opticavision_quick_view

// Suscripción newsletter
opticavision_newsletter_signup

// Cargar productos del carrusel
opticavision_load_carousel_products
```

## 📱 Responsive Design

### Breakpoints
```css
/* Mobile First */
@media (max-width: 576px)  { /* Mobile */ }
@media (max-width: 768px)  { /* Tablet */ }
@media (max-width: 992px)  { /* Desktop Small */ }
@media (max-width: 1200px) { /* Desktop */ }
```

### Características Móviles
- Menú hamburguesa con overlay
- Carruseles con touch gestures
- Filtros en modal móvil
- Imágenes optimizadas
- Lazy loading

## 🔍 SEO y Performance

### SEO Features
- Meta tags automáticos
- Open Graph tags
- Twitter Cards
- Structured data (JSON-LD)
- Breadcrumbs con schema markup
- Sitemap XML compatible

### Performance Optimizations
- Lazy loading de imágenes
- Preload de recursos críticos
- Minificación de assets
- Async/defer de scripts
- Service worker preparado
- Cache-friendly

## 🔐 Seguridad

### Medidas Implementadas
- Sanitización de todos los inputs
- Nonces en AJAX requests
- Escape de outputs
- Headers de seguridad
- Validación de permisos
- Rate limiting en AJAX

## 🧪 Testing

### Checklist de Testing
- [ ] Homepage carga correctamente
- [ ] Carruseles funcionan en desktop y móvil
- [ ] Filtros AJAX funcionan en todas las páginas
- [ ] Carrito y checkout funcionan
- [ ] Breadcrumbs aparecen en páginas interiores
- [ ] Responsive design en todos los breakpoints
- [ ] Accesibilidad con screen readers
- [ ] Performance (< 3s load time)
- [ ] SEO (meta tags, structured data)

### Herramientas Recomendadas
- Google PageSpeed Insights
- GTmetrix
- WAVE (Web Accessibility Evaluator)
- Google Search Console
- Browser DevTools

## 🔄 Migración desde Divi

### Pasos de Migración
1. **Backup completo** del sitio actual
2. **Exportar configuraciones** del customizer Divi
3. **Activar nuevo theme**
4. **Configurar menús** y widgets
5. **Personalizar** via customizer
6. **Testing completo**
7. **Go live**

### Funcionalidades Mantenidas
- Sistema de marcas con mega menú
- Integración con plugins existentes
- Carrito dinámico con contador
- Sistema de logging
- Compatibilidad con API sync

### Funcionalidades Mejoradas
- Performance optimizada
- SEO mejorado
- Accesibilidad completa
- Mobile experience
- Código limpio y mantenible

## 📞 Soporte

### Logging
El theme utiliza el sistema de logging existente:
```php
// Usar funciones existentes si están disponibles
if (function_exists('opticavision_log')) {
    opticavision_log('[THEME] Mensaje de log');
}
```

### Debug Mode
```php
// Activar debug en wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### Logs Ubicación
- Theme logs: `wp-content/optica-vision-logs/optica-vision.log`
- WordPress logs: `wp-content/debug.log`

## 📄 Licencia

Este theme es propiedad de Óptica Visión y está licenciado bajo GPL v2 o posterior.

## 🤝 Contribución

Para contribuir al desarrollo del theme:

1. Seguir las convenciones de naming del proyecto
2. Usar el sistema de logging existente
3. Mantener compatibilidad con plugins existentes
4. Documentar cambios importantes
5. Testing completo antes de deployment

## 📚 Recursos Adicionales

### Documentación Relacionada
- [Sistema de Filtros AJAX](../plugins/woo-ajax-filters/README.md)
- [Convenciones del Proyecto](../optica-vision-child/AGENTS.md)
- [Sistema de Marcas](../optica-vision-child/README-marcas-dinamicas.md)

### Links Útiles
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [WooCommerce Developer Documentation](https://woocommerce.github.io/code-reference/)
- [Web Accessibility Guidelines](https://www.w3.org/WAI/WCAG21/quickref/)

---

**Versión:** 1.0.0  
**Última actualización:** Enero 2025  
**Desarrollado por:** OpticaVision Development Team

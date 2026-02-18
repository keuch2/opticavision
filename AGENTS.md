# OpticaVision — Guía para Agentes (AGENTS.md)

Este documento define cómo contribuir de forma segura y consistente al proyecto OpticaVision. El repositorio es un sitio WordPress con WooCommerce y tema personalizado completo, con varios plugins personalizados.

## Descripción del Proyecto

**OpticaVision** es una plataforma e-commerce profesional para la venta de productos ópticos (lentes de contacto, armazones, lentes de sol, accesorios) desarrollada sobre WordPress + WooCommerce.

### Características Principales:
- **E-commerce completo**: Catálogo de productos con +55 marcas internacionales organizadas alfabéticamente
- **Tema personalizado**: `opticavision-theme` - Tema profesional optimizado que reemplaza completamente Divi
- **Sincronización automática**: Integración con API externa para actualización de productos y stock en tiempo real
- **Sistema de filtros avanzado**: Filtros AJAX con dropdowns de precios, cuotas, categorías jerárquicas
- **Pasarela de pagos**: Integración completa con Bancard (Paraguay) usando API 0.3
- **Gestión de imágenes**: Sistema automático de asignación de imágenes por SKU
- **Mega menú de marcas**: Navegación alfabética con 55+ subcategorías de marcas
- **Sistema de logging**: Logging integrado para debugging y monitoreo de operaciones
- **Responsive design**: Mobile-first, optimizado para todos los dispositivos
- **SEO optimizado**: Structured data, breadcrumbs, meta tags dinámicos
- **Páginas corporativas**: Contacto, Sucursales, Nosotros, Marcas con diseño personalizado

### Stack Tecnológico:
- **CMS**: WordPress 6.0+ con PHP 7.4+
- **E-commerce**: WooCommerce con extensiones personalizadas
- **Frontend**: HTML5, CSS3 (Custom Properties), JavaScript ES6+, jQuery
- **Tipografía**: Fira Sans (Google Fonts), Font Awesome 6.4.0
- **API**: Integración REST con sistema externo de inventario
- **Pagos**: Bancard Gateway con confirmación webhook y sistema de rollback
- **Servidor**: Apache con MySQL 8.4

## Propósito de esta Guía
- Alinear a los agentes con la estructura, convenciones y prácticas seguras del proyecto
- Evitar cambios en core, mantener compatibilidad y facilitar el soporte
- Establecer flujos de trabajo eficientes entre los miembros del equipo de desarrollo

## Equipo de Desarrollo

### 🏗️ Agent 1: Backend Developer & WordPress Architect (Lead)
**Especialización**: Desarrollo PHP, WordPress Core, WooCommerce, Arquitectura de Plugins

**Responsabilidades**:
- Desarrollo y mantenimiento de plugins personalizados
- Arquitectura y organización de clases en `includes/`
- Integración con APIs externas (optica-vision-api-sync)
- Optimización de queries y performance de base de datos
- Sistema de logging y debugging
- Manejo de hooks y filtros de WordPress/WooCommerce
- Seguridad: sanitización, validación, nonces, prepared statements
- Code review de implementaciones backend
- Configuración y troubleshooting del servidor

**Archivos principales**:
- `functions.php` (bootstrap y registro de hooks)
- Plugins: `optica-vision-api-sync`, `optica-vision-fallback`, `optica-vision-contact-lenses-sync`, `optica-vision-image-matcher`, `woocommerce-bancard-gateway`
- Clases del tema: `includes/class-optica-*.php`

### 🎨 Agent 2: Frontend & UI/UX Developer
**Especialización**: CSS3, JavaScript, Responsive Design, User Experience, Accesibilidad

**Responsabilidades**:
- Diseño y desarrollo de interfaces de usuario
- Implementación de layouts responsive (mobile-first)
- Desarrollo de componentes interactivos (carruseles, modales, filtros)
- Optimización de CSS y JavaScript
- Animaciones y transiciones suaves
- Accesibilidad web (ARIA, navegación por teclado)
- Testing cross-browser y cross-device
- Integración de assets (fonts, iconos, imágenes)
- Templates de WordPress y WooCommerce
- Hero sliders, mega menús, navigation

**Archivos principales**:
- `assets/css/` (estilos del tema)
- `assets/js/` (JavaScript del tema)
- `style.css` (CSS principal con Custom Properties)
- Templates: `header.php`, `footer.php`, `front-page.php`, páginas personalizadas
- `woocommerce/` (templates de WooCommerce)
- Plugin woo-ajax-filters: `css/` y `js/`

### 🔍 Agent 3: Code Reviewer & Problem Solver
**Especialización**: Debugging, Testing, Code Quality, Resolución de Problemas

**Responsabilidades**:
- Code review exhaustivo de todos los cambios
- Identificación proactiva de bugs y problemas potenciales
- Debugging de issues complejos usando logs y herramientas de desarrollo
- Testing manual de funcionalidades (checklist completo)
- Validación de estándares de código (WPCS, seguridad, performance)
- Análisis de debug.log y logs personalizados
- Optimización de código existente
- Documentación de issues y soluciones
- Verificación de compatibilidad con WordPress/WooCommerce
- Testing de integraciones (API, pagos, sincronización)

**Herramientas y archivos**:
- `wp-content/debug.log` (logs de WordPress)
- `wp-content/optica-vision-logs/optica-vision.log` (logs personalizados)
- Browser DevTools (Console, Network, Performance)
- WordPress debugging tools
- Checklist de testing en `README.md`

## Flujo de Trabajo del Equipo

### Para Nuevas Funcionalidades:
1. **Backend Developer**: Analiza requerimientos, diseña arquitectura, implementa lógica PHP
2. **Frontend Developer**: Diseña UI/UX, implementa templates y assets
3. **Code Reviewer**: Revisa código, identifica problemas, sugiere mejoras
4. **Backend Developer**: Integra feedback y ajusta implementación
5. **Code Reviewer**: Testing completo y validación final

### Para Bug Fixes:
1. **Code Reviewer**: Identifica y reproduce el bug, analiza logs
2. **Backend/Frontend Developer**: Implementa fix según el área afectada
3. **Code Reviewer**: Verifica la solución y realiza regression testing

### Para Optimizaciones:
1. **Code Reviewer**: Identifica áreas de mejora (performance, seguridad, código)
2. **Backend/Frontend Developer**: Implementa optimizaciones
3. **Code Reviewer**: Valida impacto y ausencia de side effects

## Alcance del Repo
- CMS: WordPress + WooCommerce + tema personalizado OpticaVision.
- Tema principal: `wp-content/themes/opticavision-theme` (tema completo y autónomo).
- Plugins personalizados en `wp-content/plugins/`:
  - `optica-vision-api-sync` (sincronización con API externa)
  - `optica-vision-image-matcher` (asigna imágenes por SKU)
  - `optica-vision-contact-lenses-sync`, `optica-vision-fallback` y otros específicos.
  - `woo-ajax-filters` (sistema de filtros AJAX para WooCommerce)
  - `woocommerce-bancard-gateway` (sistema de pagos Bancard)
- No modificar: core de WordPress, WooCommerce (carpeta `wp-admin`, `wp-includes`, plugins de terceros).

## Dónde Cambiar Código
- Tema principal: nuevas funcionalidades de UI/UX, navegación, plantillas, shortcodes, filtros/hooks → en `opticavision-theme`.
  - Lógica PHP: `includes/` (clases prefijadas), funciones en `functions.php` solo para bootstrap/registro de hooks.
  - Frontend: `assets/css/` y `assets/js/`; encolar con `wp_enqueue_*` y versionar assets.
  - Templates: `woocommerce/` para overrides de WooCommerce, templates de página personalizados.
- Plugins personalizados: integraciones, procesos backend o tareas específicas (sync, procesamiento masivo, admin UI).
  - Mantener cada plugin autocontenido (archivo principal + `includes/` y/o `admin/`).

## Estándares de Código
- PHP >= 7.4. Seguir WordPress Coding Standards (WPCS) y buenas prácticas de WooCommerce.
- Prefijos y nombres:
  - Tema: funciones `optica_vision_*`, clases `OpticaVision_*`, archivos `class-optica-*.php`.
  - Plugins: clases `Optica_Vision_*` o prefijo del plugin; textdomain del plugin (ver archivo principal).
- Internacionalización: envolver strings en `__()`, `esc_html__()`; textdomains usados:
  - Tema: `opticavision-theme`
  - Plugins: `optica-vision-api-sync`, `optica-vision-image-matcher`, etc.
- Seguridad: siempre sanitizar inputs (`sanitize_text_field`, `absint`, `wp_unslash`), validar nonces en AJAX, escapar salida (`esc_html`, `esc_url`, `wp_kses_post`).
- Hooks > overrides: priorizar `add_action`/`add_filter` frente a editar plantillas de WooCommerce.

## Patrones Arquitectónicos
- Organización en clases bajo `includes/` con responsabilidades claras.
- Usar helpers globales existentes cuando apliquen:
  - Logger: `optica_vision_logger()` y helpers `optica_log_*()` del tema.
  - Filtro de categorías: `OpticaVision_Category_Filter` + shortcodes en `class-optica-filter-shortcodes.php`.
  - Marcas: `OpticaVision_Marcas_Manager` y helper `ovc_get_marcas_subcategories()`.
- Evitar acoplar UI con lógica: PHP para datos, vistas/render en funciones separadas cuando sea posible.

## WooCommerce y Tema Personalizado
- Respetar APIs de WooCommerce (`wc_get_products`, `wc_get_page_permalink`, fragments/cart, etc.).
- El tema `opticavision-theme` es completamente autónomo y maneja toda la funcionalidad directamente.
- Usar templates de WooCommerce en `woocommerce/` para personalizar páginas de productos, categorías, etc.

## Logging y Depuración
- Sistema de logging integrado en el tema principal `opticavision-theme`.
  - Helpers: `optica_log_debug|info|warning|error|performance`.
  - Ruta de logs: `wp-content/optica-vision-logs/optica-vision.log` (protegido por `.htaccess`).
- No loguear credenciales, tokens, ni datos sensibles. Redactar si es imprescindible.
- `WP_DEBUG` puede estar activo; `wp-content/debug.log` existe. Mantener mensajes claros y accionables.

## Integraciones/API (optica-vision-api-sync)
- Usar `wp_remote_*`, timeouts razonables y manejo de errores con `WP_Error`.
- Reintentos y 401: el plugin ya contempla refresco de token. No duplicar lógica; extenderla si hace falta.
- Configuración por `get_option`/`update_option`. No hardcodear secretos en el repo.

## Frontend
- Encolar assets con dependencias y versiones; evitar choques con jQuery (usar closure `jQuery(function($){...})`).
- Accesibilidad: roles/aria en navegación y mega-menús; mantener soporte teclado.
- Strings visibles al usuario en español y envueltos para i18n.

## Testing y QA
- Revisar documentación del tema principal `opticavision-theme`:
  - README.md (instalación y configuración)
  - Documentación de funcionalidades específicas
  - Checklist de testing integrado
- Pruebas manuales clave:
  - Filtros AJAX: funcionamiento en tiempo real, paginación, búsquedas.
  - Carrito y fragmentos AJAX; contador dinámico.
  - Mega-menús y navegación móvil (toques, overlay, accesibilidad).
  - Homepage: carruseles, hero slider, secciones responsive.
  - Templates WooCommerce: productos, categorías, checkout.
  - Plugins: subida masiva de imágenes por SKU; procesamiento de imágenes existentes; sync de API.

## Convenciones de Nombres y Estructura
- Archivos de clase: `includes/class-optica-<feature>.php`.
- Shortcodes en una clase dedicada; AJAX en métodos con nonces únicos y `wp_ajax_{action}`/`wp_ajax_nopriv_{action}`.
- Filtros y acciones: documentar con comentarios breves (propósito y prioridad si difiere del default).

## Checklist antes de subir cambios
- No se toca core de WordPress/WooCommerce ni plugins de terceros.
- Se sanitiza todo input y se escapa toda salida (especialmente en admin y AJAX).
- Strings preparados para traducción con el textdomain `opticavision-theme`.
- Logs: sin datos sensibles, mensajes útiles; usar el logger del proyecto.
- Performance: bucles sobre productos paginados; sin queries N+1 evidentes.
- Assets: CSS/JS encolados correctamente con versiones y dependencias.
- Testing: verificar funcionalidad en desktop y móvil.

## No Hacer
- No editar `wp-admin`, `wp-includes`, ni plugins de terceros.
- No introducir dependencias con red externa sin aprobación.
- No almacenar claves o contraseñas en el repo.
- No usar el tema hijo `optica-vision-child` (descontinuado).

---

Nota sobre `.windsurfrules`: el archivo solo contiene el encabezado "OpticaVision WordPress Project Rules". Este AGENTS.md establece las reglas prácticas basadas en la estructura y código actual del proyecto.

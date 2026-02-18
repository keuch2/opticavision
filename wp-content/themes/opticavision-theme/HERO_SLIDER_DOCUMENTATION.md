# Hero Slider System - OpticaVision Theme

## Descripción General

El sistema de Hero Slider de OpticaVision es una solución completa para gestionar sliders de imágenes responsivos en la homepage. Permite configurar imágenes diferentes para desktop y móvil, con una interfaz de administración intuitiva y funcionalidades avanzadas.

## Características Principales

### ✅ **Imágenes Responsivas**
- **Imagen Desktop**: Optimizada para pantallas grandes (>768px)
- **Imagen Mobile**: Optimizada para dispositivos móviles (≤768px)
- **Elemento `<picture>`**: Cambio automático según el breakpoint
- **Lazy Loading**: Carga optimizada de imágenes

### ✅ **Sistema de Administración Profesional**
- **Página dedicada**: Apariencia > Hero Slider
- **Drag & Drop**: Reordenamiento de slides
- **Media Library**: Integración completa con WordPress
- **Vista previa**: Thumbnails de imágenes seleccionadas
- **AJAX**: Guardado sin recargar página

### ✅ **Funcionalidades Avanzadas**
- **Enlaces opcionales**: URLs con target configurable
- **Estados activo/inactivo**: Control de visibilidad
- **Texto alternativo**: Accesibilidad completa
- **Autoplay configurable**: Con controles de pausa
- **Navegación completa**: Flechas y dots

### ✅ **Accesibilidad y Performance**
- **ARIA labels**: Navegación accesible
- **Teclado**: Soporte completo de navegación
- **Touch gestures**: Swipe en móviles
- **Reduced motion**: Respeta preferencias del usuario
- **Preload**: Carga inteligente de imágenes

## Instalación y Configuración

### 1. **Acceso al Panel de Administración**

```
WordPress Admin > Apariencia > Hero Slider
```

### 2. **Agregar Nuevo Slide**

1. Haz clic en **"Agregar Nuevo Slide"**
2. Selecciona **imagen para desktop** (requerida)
3. Selecciona **imagen para móvil** (opcional, usa desktop si no se especifica)
4. Configura **opciones adicionales**:
   - ✅ Slide activo (visible en frontend)
   - 🔗 URL de enlace (opcional)
   - 🎯 Abrir en nueva pestaña
   - 📝 Texto alternativo (accesibilidad)

### 3. **Gestión de Slides**

- **Reordenar**: Arrastra el ícono ☰ para cambiar el orden
- **Expandir/Contraer**: Clic en el header del slide
- **Eliminar**: Botón 🗑️ (con confirmación)
- **Guardar**: Botón "Guardar Cambios" (guarda todos los slides)

## Uso en Templates

### **Shortcode Básico**
```php
echo do_shortcode('[opticavision_hero_slider]');
```

### **Shortcode con Parámetros**
```php
echo do_shortcode('[opticavision_hero_slider autoplay="true" autoplay_delay="5000" show_dots="true" show_arrows="true" fade_effect="true"]');
```

### **Función PHP Directa**
```php
$slider = new OpticaVision_Hero_Slider();
echo $slider->display_slider(array(
    'autoplay' => true,
    'autoplay_delay' => 5000,
    'show_dots' => true,
    'show_arrows' => true,
    'fade_effect' => true
));
```

### **En Templates de Tema**
```php
// En front-page.php, index.php, etc.
if (function_exists('opticavision_hero_slider')) {
    echo do_shortcode('[opticavision_hero_slider]');
}
```

## Parámetros del Shortcode

| Parámetro | Tipo | Defecto | Descripción |
|-----------|------|---------|-------------|
| `autoplay` | boolean | `true` | Activar reproducción automática |
| `autoplay_delay` | integer | `5000` | Tiempo entre slides (ms) |
| `show_dots` | boolean | `true` | Mostrar indicadores de navegación |
| `show_arrows` | boolean | `true` | Mostrar flechas de navegación |
| `fade_effect` | boolean | `true` | Efecto de transición fade |

## Estructura HTML Generada

```html
<div class="opticavision-hero-slider" 
     data-autoplay="true" 
     data-autoplay-delay="5000" 
     data-fade="true">
    
    <div class="hero-slider-container">
        <div class="hero-slide active">
            <a href="URL_OPCIONAL" class="hero-slide-link">
                <picture class="hero-slide-image">
                    <source media="(max-width: 768px)" srcset="mobile-image.jpg">
                    <img src="desktop-image.jpg" alt="Texto alternativo" loading="eager">
                </picture>
            </a>
        </div>
        <!-- Más slides... -->
    </div>
    
    <!-- Navegación -->
    <button class="hero-slider-prev" aria-label="Slide anterior">←</button>
    <button class="hero-slider-next" aria-label="Siguiente slide">→</button>
    
    <!-- Indicadores -->
    <div class="hero-slider-dots">
        <button class="hero-slider-dot active" data-slide="0"></button>
        <!-- Más dots... -->
    </div>
</div>
```

## Estilos CSS Personalizables

### **Variables CSS Disponibles**
```css
.opticavision-hero-slider {
    --slider-height: 60vh;
    --slider-min-height: 400px;
    --slider-max-height: 800px;
    --arrow-size: 50px;
    --dot-size: 12px;
    --transition-duration: 0.8s;
}
```

### **Breakpoints Responsivos**
- **Desktop**: > 1024px (altura: 60vh)
- **Tablet**: 769px - 1024px (altura: 50vh)
- **Mobile**: ≤ 768px (altura: 40vh)
- **Small Mobile**: ≤ 480px (altura: 35vh, sin flechas)

### **Personalización de Estilos**
```css
/* Cambiar altura del slider */
.opticavision-hero-slider {
    height: 70vh;
    max-height: 900px;
}

/* Personalizar flechas */
.hero-slider-prev,
.hero-slider-next {
    background: rgba(255, 0, 0, 0.8);
    width: 60px;
    height: 60px;
}

/* Personalizar dots */
.hero-slider-dot {
    width: 15px;
    height: 15px;
    background: #ff0000;
}
```

## JavaScript API

### **Eventos Disponibles**
```javascript
// Cuando se cambia de slide
$(document).on('opticavision_slide_changed', function(e, slideIndex) {
    console.log('Slide actual:', slideIndex);
});

// Cuando se inicia autoplay
$(document).on('opticavision_autoplay_started', function() {
    console.log('Autoplay iniciado');
});

// Cuando se pausa autoplay
$(document).on('opticavision_autoplay_paused', function() {
    console.log('Autoplay pausado');
});
```

### **Control Programático**
```javascript
// Acceder a la instancia del slider
const slider = $('.opticavision-hero-slider').data('slider-instance');

// Ir a slide específico
slider.goToSlide(2);

// Siguiente slide
slider.nextSlide();

// Slide anterior
slider.previousSlide();

// Pausar autoplay
slider.pauseAutoplay();

// Iniciar autoplay
slider.startAutoplay();
```

## Base de Datos

### **Opción de WordPress**
```php
// Obtener slides
$slides = get_option('opticavision_hero_slides', array());

// Estructura de cada slide
$slide = array(
    'desktop_image' => 123,        // ID de attachment
    'mobile_image' => 124,         // ID de attachment (opcional)
    'active' => true,              // boolean
    'link_url' => 'https://...',   // string (opcional)
    'link_new_tab' => true,        // boolean
    'alt_text' => 'Descripción'    // string
);
```

### **Funciones de Acceso**
```php
// Obtener slides activos
$slider = new OpticaVision_Hero_Slider();
$active_slides = array_filter($slider->get_slides(), function($slide) {
    return !empty($slide['active']);
});

// Verificar si hay slides configurados
$has_slides = !empty(get_option('opticavision_hero_slides'));
```

## Hooks y Filtros

### **Filtros Disponibles**
```php
// Modificar slides antes de mostrar
add_filter('opticavision_hero_slides', function($slides) {
    // Modificar $slides array
    return $slides;
});

// Modificar atributos del shortcode
add_filter('opticavision_hero_slider_atts', function($atts) {
    $atts['autoplay_delay'] = 3000; // Cambiar delay
    return $atts;
});

// Modificar HTML del slider
add_filter('opticavision_hero_slider_html', function($html, $slides, $atts) {
    // Modificar HTML generado
    return $html;
}, 10, 3);
```

### **Acciones Disponibles**
```php
// Antes de mostrar el slider
add_action('opticavision_before_hero_slider', function($slides) {
    // Código personalizado
});

// Después de mostrar el slider
add_action('opticavision_after_hero_slider', function($slides) {
    // Código personalizado
});

// Cuando se guardan los slides
add_action('opticavision_hero_slides_saved', function($slides) {
    // Limpiar cache, etc.
});
```

## Optimización y Performance

### **Lazy Loading**
- Primera imagen: `loading="eager"` (carga inmediata)
- Resto de imágenes: `loading="lazy"` (carga diferida)
- Preload de la siguiente imagen al cambiar slide

### **Responsive Images**
- Uso de `<picture>` element para diferentes breakpoints
- Imágenes optimizadas según el dispositivo
- Fallback automático si no hay imagen móvil

### **JavaScript Optimizado**
- Inicialización solo cuando es necesario
- Event delegation para mejor performance
- Cleanup automático de timers y eventos
- Soporte para `prefers-reduced-motion`

## Troubleshooting

### **Problemas Comunes**

#### **Las imágenes no se muestran**
```php
// Verificar que las imágenes existen
$slides = get_option('opticavision_hero_slides');
foreach ($slides as $slide) {
    $desktop_img = wp_get_attachment_image_url($slide['desktop_image'], 'full');
    if (!$desktop_img) {
        echo "Imagen desktop no encontrada: " . $slide['desktop_image'];
    }
}
```

#### **El slider no funciona en móvil**
- Verificar que jQuery está cargado
- Comprobar errores de JavaScript en consola
- Verificar que los assets CSS/JS están encolados

#### **Autoplay no se pausa**
- Verificar configuración de `prefers-reduced-motion`
- Comprobar que los eventos de hover/focus funcionan
- Verificar que no hay conflictos con otros scripts

### **Debugging**
```php
// Activar logging del slider
add_filter('opticavision_hero_slider_debug', '__return_true');

// Ver logs en wp-content/optica-vision-logs/optica-vision.log
```

## Migración y Backup

### **Exportar Configuración**
```php
$slides = get_option('opticavision_hero_slides');
file_put_contents('hero-slider-backup.json', json_encode($slides, JSON_PRETTY_PRINT));
```

### **Importar Configuración**
```php
$backup = json_decode(file_get_contents('hero-slider-backup.json'), true);
update_option('opticavision_hero_slides', $backup);
```

### **Migración de Imágenes**
```php
// Función helper para migrar IDs de imágenes
function migrate_hero_slider_images($old_site_url, $new_site_url) {
    $slides = get_option('opticavision_hero_slides');
    foreach ($slides as &$slide) {
        // Lógica de migración de attachment IDs
    }
    update_option('opticavision_hero_slides', $slides);
}
```

## Soporte y Mantenimiento

### **Logs del Sistema**
- Ubicación: `wp-content/optica-vision-logs/optica-vision.log`
- Prefijo: `[THEME] Hero slider`
- Eventos registrados: guardado, errores, carga de slides

### **Compatibilidad**
- **WordPress**: 5.0+
- **PHP**: 7.4+
- **Navegadores**: IE11+, Chrome, Firefox, Safari, Edge
- **Dispositivos**: Desktop, Tablet, Mobile

### **Actualizaciones Futuras**
- Mantener compatibilidad con estructura de datos actual
- Documentar cambios en este archivo
- Crear scripts de migración si es necesario

---

**Desarrollado para OpticaVision Theme**  
**Versión**: 1.0.0  
**Última actualización**: 2025-01-24

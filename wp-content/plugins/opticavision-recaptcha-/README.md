# OpticaVision reCAPTCHA v3

Plugin de protección con Google reCAPTCHA v3 para todos los formularios del sitio OpticaVision.

## Características

- ✅ **Protección invisible**: reCAPTCHA v3 funciona en segundo plano sin interrumpir al usuario
- ✅ **Integración completa**: Login, Registro, Comentarios, Checkout, Contact Form 7
- ✅ **WooCommerce**: Soporte completo para checkout y registro de WooCommerce
- ✅ **Configuración flexible**: Activa/desactiva protección por formulario
- ✅ **Umbral personalizable**: Ajusta el nivel de seguridad (0.0 - 1.0)
- ✅ **Logging integrado**: Se integra con el sistema de logs de OpticaVision
- ✅ **Panel de administración**: Interfaz limpia y fácil de usar

## Requisitos

- WordPress 6.0+
- PHP 7.4+
- Cuenta de Google reCAPTCHA v3
- OpticaVision Theme (para logging integrado)

## Instalación

1. El plugin ya está instalado en `/wp-content/plugins/opticavision-recaptcha/`
2. Activa el plugin desde **Plugins → Plugins instalados**
3. Configura las claves API en **Ajustes → reCAPTCHA**

## Configuración

### 1. Obtener Claves de Google reCAPTCHA

1. Ve a [Google reCAPTCHA Admin](https://www.google.com/recaptcha/admin)
2. Crea un nuevo sitio con las siguientes opciones:
   - **Tipo**: reCAPTCHA v3
   - **Dominios**: `opticavision.com.py`, `www.opticavision.com.py`, `localhost` (para desarrollo)
3. Copia la **Site Key** y **Secret Key**

### 2. Configurar el Plugin

1. Ve a **Ajustes → reCAPTCHA**
2. Pega las claves:
   - **Site Key**: Clave pública para el frontend
   - **Secret Key**: Clave privada para el backend
3. Ajusta el **Umbral de Score** (recomendado: 0.5)
   - 0.0 = Menos estricto (permite más usuarios)
   - 1.0 = Más estricto (bloquea más usuarios)
4. Selecciona qué formularios proteger
5. Guarda los cambios

## Formularios Soportados

### WordPress Core
- ✅ **Login** (`/wp-login.php`)
- ✅ **Registro** (`/wp-login.php?action=register`)
- ✅ **Comentarios** (todos los posts)

### WooCommerce
- ✅ **Checkout** (proceso de compra)
- ✅ **Registro de cuenta** (`/my-account/`)

### Plugins de Terceros
- ✅ **Contact Form 7** (todos los formularios)

### Formularios Personalizados
Puedes proteger cualquier formulario agregando el atributo `data-recaptcha="true"`:

```html
<form data-recaptcha="true" data-recaptcha-action="custom_action">
    <!-- Campos del formulario -->
</form>
```

## Estructura de Archivos

```
opticavision-recaptcha/
├── opticavision-recaptcha.php    # Archivo principal del plugin
├── includes/
│   ├── class-admin.php            # Administración y configuración
│   ├── class-frontend.php         # Integración frontend
│   └── class-validator.php        # Validación de tokens
├── assets/
│   ├── js/
│   │   └── recaptcha.js          # JavaScript del frontend
│   └── css/
│       └── admin.css              # Estilos del admin
└── README.md                      # Este archivo
```

## Uso

El plugin funciona automáticamente una vez configurado. No requiere cambios en el código.

### Verificación Manual en PHP

Si necesitas verificar reCAPTCHA en código personalizado:

```php
<?php
// Obtener token del formulario
$token = isset($_POST['g-recaptcha-response']) ? sanitize_text_field(wp_unslash($_POST['g-recaptcha-response'])) : '';

// Validar
$result = OpticaVision_Recaptcha_Validator::validate($token, 'custom_action');

if (is_wp_error($result)) {
    // Error: token inválido o score bajo
    $error_message = $result->get_error_message();
} else {
    // Éxito: el usuario es humano
    $score = $result['score']; // 0.0 - 1.0
}
?>
```

### JavaScript Personalizado

Ejecutar reCAPTCHA manualmente:

```javascript
grecaptcha.ready(function() {
    grecaptcha.execute('YOUR_SITE_KEY', {action: 'custom_action'}).then(function(token) {
        // Agregar token al formulario
        document.getElementById('g-recaptcha-response').value = token;
    });
});
```

## Logging

El plugin se integra con el sistema de logging de OpticaVision:

```
/wp-content/optica-vision-logs/optica-vision.log
```

Los logs incluyen:
- ✅ Activación/Desactivación del plugin
- ✅ Verificaciones exitosas (con score)
- ⚠️ Advertencias de score bajo
- ❌ Errores de validación
- ❌ Problemas con la API de Google

## Debugging

### Verificar Configuración

1. Ve a **Ajustes → reCAPTCHA**
2. Verifica que el estado sea: **✓ Configurado correctamente**

### Console Logs (Frontend)

Abre la consola del navegador (F12):

```
OpticaVision reCAPTCHA v3 initialized
```

### Logs del Servidor

Revisa `/wp-content/optica-vision-logs/optica-vision.log`:

```
[2025-01-04 10:30:15] INFO [RECAPTCHA] reCAPTCHA verification successful: Score: 0.9, Action: login
[2025-01-04 10:31:22] WARNING [RECAPTCHA] reCAPTCHA score below threshold: Score: 0.3, Threshold: 0.5
[2025-01-04 10:32:10] ERROR [RECAPTCHA] reCAPTCHA verification failed: missing-input-response
```

## Troubleshooting

### El reCAPTCHA no aparece

1. Verifica que las claves estén configuradas correctamente
2. Revisa la consola del navegador en busca de errores
3. Confirma que el formulario esté habilitado en la configuración

### "Verificación fallida" constante

1. Verifica que la **Secret Key** sea correcta
2. Revisa que el dominio esté en la lista de Google reCAPTCHA
3. Ajusta el umbral de score a un valor más bajo (ej: 0.3)

### Usuarios legítimos bloqueados

1. Reduce el umbral de score (de 0.5 a 0.3)
2. Revisa los logs para ver los scores que están recibiendo
3. Considera deshabilitar reCAPTCHA temporalmente

### Error "missing-input-secret"

- La **Secret Key** no está configurada o es incorrecta

### Error "invalid-input-secret"

- La **Secret Key** no es válida para este sitio

### Error "timeout-or-duplicate"

- El token ya fue usado o expiró (recarga la página)

## Seguridad

- ✅ Todas las entradas se sanitizan con `sanitize_text_field()`
- ✅ Las salidas se escapan con `esc_html()`, `esc_attr()`, `esc_url()`
- ✅ Comunicación HTTPS con la API de Google
- ✅ Las claves nunca se exponen en el frontend
- ✅ Los logs no incluyen datos sensibles

## Performance

- Carga asíncrona del script de Google reCAPTCHA
- Sin impacto en la velocidad de carga de la página
- Validación en segundo plano
- Caché de scripts con versionado

## Compatibilidad

| Plugin/Tema | Versión | Estado |
|-------------|---------|--------|
| WordPress | 6.0+ | ✅ Compatible |
| WooCommerce | 7.0+ | ✅ Compatible |
| Contact Form 7 | 5.0+ | ✅ Compatible |
| OpticaVision Theme | 1.0+ | ✅ Integrado |

## Changelog

### v1.0.0 (2025-01-04)
- ✨ Lanzamiento inicial
- ✅ Soporte para Login, Registro, Comentarios
- ✅ Integración completa con WooCommerce
- ✅ Soporte para Contact Form 7
- ✅ Logging integrado con OpticaVision
- ✅ Panel de administración completo
- ✅ Umbral de score configurable

## Soporte

Para problemas o consultas:

1. Revisa este README
2. Consulta los logs en `/wp-content/optica-vision-logs/`
3. Contacta al equipo de desarrollo de OpticaVision

## Créditos

- **Desarrollado por**: OpticaVision Development Team
- **Powered by**: Google reCAPTCHA v3
- **Versión**: 1.0.0
- **Licencia**: GPL v2 or later

---

**© 2025 OpticaVision. Todos los derechos reservados.**

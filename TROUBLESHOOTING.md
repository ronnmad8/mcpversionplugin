# Troubleshooting - Menú no Aparece

## Problema: El menú "JSON Versiones" no aparece en el admin

### Soluciones Implementadas

1. **Múltiples Intentos de Registro**
   - El plugin intenta registrar el menú en `admin_menu` con prioridad 10
   - Tiene un fallback en `admin_init` con prioridad 1
   - Tiene un verificador final en `admin_menu` con prioridad 999

2. **Fallback a Menú Principal**
   - Si no se puede añadir en "Herramientas", se añade como menú principal
   - Icono: `dashicons-update`
   - Posición: 30 (después de otros menús estándar)

3. **Verificación de Clase**
   - Verifica que la clase existe antes de intentar crear el menú
   - Manejo de errores para no romper la web

## 🔍 Verificación Manual

### 1. Verificar que el plugin está activo
- Ve a **Plugins > Plugins instalados**
- Busca "JSON Version Manager"
- Debe estar **Activado**

### 2. Verificar permisos
- Asegúrate de estar logueado como administrador
- El menú requiere permisos `manage_options`

### 3. Buscar el menú en diferentes ubicaciones

**Opción 1: En Herramientas**
- Ve a **Herramientas** en el menú lateral
- Busca "JSON Versiones" como submenú

**Opción 2: Menú Principal**
- Si no aparece en Herramientas, busca "JSON Versiones" directamente en el menú lateral
- Debería tener un icono de actualización

### 4. Verificar errores

Activa `WP_DEBUG` en `wp-config.php`:
```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
```

Luego revisa `wp-content/debug.log` para errores relacionados con "JSON Version Manager".

### 5. Desactivar y Reactivar

1. Ve a **Plugins > Plugins instalados**
2. Desactiva "JSON Version Manager"
3. Actívalo de nuevo
4. Recarga la página de admin

### 6. Verificar conflictos con otros plugins

1. Desactiva temporalmente otros plugins
2. Verifica si el menú aparece
3. Si aparece, reactiva los plugins uno por uno para encontrar el conflicto

## 🛠️ Solución Rápida

Si el menú no aparece, puedes acceder directamente a:
```
https://tudominio.com/wp-admin/tools.php?page=json-version-manager
```

O si está en el menú principal:
```
https://tudominio.com/wp-admin/admin.php?page=json-version-manager
```

## 📝 Debug

Añade esto temporalmente a `wp-config.php` para ver qué está pasando:

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

Luego revisa los logs en `wp-content/debug.log`.

## 🔧 Si Nada Funciona

1. Verifica que todos los archivos del plugin están presentes
2. Verifica permisos de archivos (644 para archivos, 755 para directorios)
3. Revisa los logs de PHP del servidor
4. Contacta con soporte con los logs de error


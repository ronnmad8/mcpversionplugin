# Solución: Menú no Aparece en el Lateral

## 🔧 Cambios Implementados

Se han añadido múltiples mecanismos para asegurar que el menú aparezca:

### 1. Múltiples Hooks y Prioridades

El menú ahora se intenta registrar en:
- `admin_menu` con prioridad 5
- `admin_menu` con prioridad 10  
- `admin_menu` con prioridad 15
- `admin_menu` con prioridad 25
- `admin_menu` con prioridad 999 (verificador final)

### 2. Inicialización Directa

Se añade el menú directamente en el archivo principal del plugin, no solo en la clase.

### 3. Verificación de Existencia

Antes de añadir, verifica si ya existe para evitar duplicados.

## 📍 Dónde Buscar el Menú

### Opción 1: En Herramientas (Tools)
1. Ve al menú lateral de WordPress
2. Busca **"Herramientas"** (Tools)
3. Haz clic para expandir
4. Deberías ver **"JSON Versiones"**

### Opción 2: Menú Principal
Si no aparece en Herramientas, busca directamente:
- **"JSON Versiones"** en el menú lateral principal
- Debería tener un icono de actualización (dashicons-update)

## 🔍 Verificación Manual

### Paso 1: Verificar que el Plugin está Activo
1. Ve a **Plugins > Plugins instalados**
2. Busca "JSON Version Manager"
3. Debe estar **Activado** (no solo instalado)

### Paso 2: Desactivar y Reactivar
1. Desactiva el plugin
2. Actívalo de nuevo
3. Recarga la página de admin (Ctrl+F5 o Cmd+Shift+R)

### Paso 3: Acceso Directo
Si el menú no aparece, puedes acceder directamente a:
```
https://tudominio.com/wp-admin/tools.php?page=json-version-manager
```

O si está en el menú principal:
```
https://tudominio.com/wp-admin/admin.php?page=json-version-manager
```

## 🐛 Debug

Si aún no aparece, activa el debug:

1. Edita `wp-config.php`:
```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
```

2. Revisa `wp-content/debug.log` para errores relacionados con "JSON Version Manager"

3. Verifica que no hay conflictos con otros plugins:
   - Desactiva temporalmente otros plugins
   - Verifica si el menú aparece
   - Si aparece, reactiva los plugins uno por uno

## ✅ Verificación Rápida

Ejecuta esto en la consola del navegador (F12) en la página de admin:

```javascript
// Verificar si el menú existe
console.log('Buscando menú JSON Versiones...');
const menuItems = document.querySelectorAll('a[href*="json-version-manager"]');
console.log('Encontrados:', menuItems.length);
menuItems.forEach(item => console.log(item.textContent, item.href));
```

## 📝 Notas

- El menú requiere permisos de administrador (`manage_options`)
- Si usas un tema personalizado, puede estar ocultando menús
- Algunos plugins de seguridad pueden bloquear la creación de menús

## 🔄 Si Nada Funciona

1. Verifica que todos los archivos están presentes
2. Verifica permisos de archivos (644 para archivos, 755 para directorios)
3. Revisa los logs de PHP del servidor
4. Prueba en un WordPress limpio para descartar conflictos


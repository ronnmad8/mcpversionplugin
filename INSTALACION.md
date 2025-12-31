# Guía de Instalación - JSON Version Manager

## 📦 Instalación Rápida

### Paso 1: Subir el Plugin

1. Comprime la carpeta `json_version_plugin` en un archivo ZIP
2. Ve a **Plugins > Añadir nuevo > Subir plugin** en WordPress
3. Selecciona el archivo ZIP y haz clic en **Instalar ahora**
4. Activa el plugin

### Paso 2: Acceder a la Administración

1. Ve a **Herramientas > JSON Versiones** en el admin de WordPress
2. Verás el formulario con todos los campos del JSON

### Paso 3: Configurar el JSON

1. Edita los campos que necesites:
   - **Versión del Plugin**: Versión actual (ej: `1.0.0`)
   - **Versión del Adaptador**: Versión del adaptador STDIO
   - **Versión Mínima del Adaptador**: Versión mínima requerida
   - **URL de Descarga**: URL donde se puede descargar el adaptador
   - **Changelog**: HTML con los cambios

2. Haz clic en **Guardar JSON**

## 🌐 Configurar URL Pública

### Opción 1: URL Directa (Más Simple)

El JSON estará disponible en:
```
https://tudominio.com/wp-content/plugins/json_version_plugin/mcp-metadata.json
```

### Opción 2: URL Personalizada (Recomendado)

Si quieres que esté en `https://renekay.com/api/mcp-metadata.json`:

#### Con .htaccess (Apache)

Añade esto a tu `.htaccess` en la raíz de WordPress:

```apache
# Redirigir /api/mcp-metadata.json al plugin
RewriteRule ^api/mcp-metadata\.json$ /wp-content/plugins/json_version_plugin/mcp-metadata.json [L]
```

#### Con Nginx

Añade esto a tu configuración de Nginx:

```nginx
location /api/mcp-metadata.json {
    alias /ruta/a/wordpress/wp-content/plugins/json_version_plugin/mcp-metadata.json;
    default_type application/json;
}
```

## ✅ Verificar que Funciona

1. Abre en el navegador: `https://tudominio.com/wp-content/plugins/json_version_plugin/mcp-metadata.json`
2. Debe mostrar el JSON formateado
3. Verifica que el Content-Type sea `application/json`

## 🔧 Permisos del Archivo

Asegúrate de que el archivo tenga permisos de escritura:

```bash
chmod 644 wp-content/plugins/json_version_plugin/mcp-metadata.json
chmod 755 wp-content/plugins/json_version_plugin/
```

## 📝 Actualizar el Plugin MCP Stream

En el plugin MCP Stream WordPress, asegúrate de que la URL del JSON apunte a tu dominio:

```php
// En includes/updates/class-version-checker.php
const API_URL = 'https://renekay.com/api/mcp-metadata.json';
```

O si usas la URL directa:

```php
const API_URL = 'https://renekay.com/wp-content/plugins/json_version_plugin/mcp-metadata.json';
```

## 🎯 Uso Diario

### Actualizar Versión

1. Ve a **Herramientas > JSON Versiones**
2. Cambia los campos de versión
3. Actualiza el changelog
4. Guarda

Los usuarios recibirán notificaciones automáticamente.

## 🐛 Problemas Comunes

### El JSON no se guarda

- Verifica permisos: `chmod 644 mcp-metadata.json`
- Revisa logs de PHP
- Asegúrate de completar todos los campos requeridos

### El JSON no es accesible

- Verifica que el archivo existe
- Comprueba permisos (644)
- Revisa configuración del servidor web
- Prueba acceder directamente a la URL

### Error 404 al acceder al JSON

- Verifica la ruta del archivo
- Comprueba la configuración de rewrite rules
- Asegúrate de que el plugin esté activo

## 📚 Más Información

Ver `README.md` para documentación completa del plugin.


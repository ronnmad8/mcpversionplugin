# JSON Version Manager

Plugin de WordPress para gestionar el archivo JSON de versiones de MCP Stream WordPress desde el admin de WordPress.

## 📋 Características

- ✅ Interfaz de administración simple y clara
- ✅ Edición de todos los campos del JSON
- ✅ Vista previa en tiempo real
- ✅ Guardado automático del archivo JSON
- ✅ Servicio del JSON públicamente
- ✅ Editor de changelog con HTML
- ✅ Información de versión actual
- ✅ Manejo robusto de errores
- ✅ Compatible con Elementor

## 🚀 Instalación

1. Sube la carpeta `json_version_plugin` a `/wp-content/plugins/`
2. Activa el plugin desde el panel de administración de WordPress
3. Ve a `Herramientas > JSON Versiones`

## 📝 Uso

### Acceder a la Administración

1. Ve a **Herramientas > JSON Versiones** en el admin de WordPress
2. Edita los campos que necesites cambiar
3. Haz clic en **Guardar JSON**

### Campos Principales

- **Versión del Plugin**: Versión actual del plugin WordPress
- **Versión del Adaptador**: Versión actual del adaptador STDIO
- **Versión Mínima del Adaptador**: Versión mínima requerida (fuerza actualización si la aumentas)
- **URL de Descarga**: URL donde se puede descargar el adaptador
- **Changelog**: HTML con los cambios de la versión

### URL del JSON

El JSON se sirve automáticamente en:
```
https://tudominio.com/wp-content/plugins/json_version_plugin/mcp-metadata.json
```

## 🔧 Requisitos

- WordPress 6.4 o superior
- PHP 8.0 o superior

## 🔒 Seguridad

- Solo usuarios con permisos `manage_options` pueden editar el JSON
- Validación de nonces en todas las acciones
- Sanitización de todos los campos de entrada
- El JSON se sirve públicamente pero es de solo lectura
- Manejo robusto de errores que no rompe la web
- Compatible con Elementor y otros page builders

## 🧪 Testing

```bash
composer install
vendor/bin/phpunit
```

## 📚 Documentación

- [Guía de Instalación](INSTALACION.md)
- [Manejo de Errores](docs/ERROR_HANDLING.md)
- [Formato del JSON](docs/JSON_FORMAT.md)

## 📄 Licencia

GPL-2.0-or-later

## 👤 Autor

BY360 - https://by360.es

## 🔗 Repositorio

https://github.com/ronnmad8/mcpversionplugin

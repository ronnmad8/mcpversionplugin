# Guía de Reinstalación - json_version_plugin

## ✅ Verificación Pre-Reinstalación

Antes de reinstalar, verifica:

1. ✅ **Tests pasan**: 43 tests, 129 assertions - Todos OK
2. ✅ **Sin errores de linter**: Verificado
3. ✅ **Hook de activación**: Configurado correctamente
4. ✅ **Versión actual**: 1.0.3

## 🔄 Proceso de Reinstalación

### Opción 1: Desactivar y Reactivar (Recomendado)

1. Ve a **Plugins** en el admin de WordPress
2. **Desactiva** "JSON Version Manager"
3. **Activa** nuevamente "JSON Version Manager"
4. Verifica que el menú aparece en **Herramientas > JSON Versiones**

### Opción 2: Desinstalar y Reinstalar (Limpio)

1. Ve a **Plugins** en el admin de WordPress
2. **Desactiva** "JSON Version Manager"
3. **Elimina** el plugin (esto eliminará opciones y archivos)
4. **Sube** nuevamente la carpeta `json_version_plugin` a `/wp-content/plugins/`
5. **Activa** el plugin
6. El archivo `mcp-metadata.json` se creará automáticamente con valores por defecto

### Opción 3: Reinstalación Manual (Avanzado)

Si quieres mantener tus datos:

1. **Haz backup** de:
   - `/wp-content/plugins/json_version_plugin/mcp-metadata.json`
   - Opciones de WordPress: `jvm_json_data`, `jvm_valid_licenses`, `jvm_license_activations`

2. **Desactiva** el plugin

3. **Reemplaza** los archivos del plugin

4. **Activa** el plugin

5. **Restaura** el backup del JSON si es necesario

## 📋 Qué se Crea en la Activación

Al activar el plugin, se crea automáticamente:

- ✅ Archivo `mcp-metadata.json` con valores por defecto
- ✅ Opciones de WordPress para almacenar datos
- ✅ Menú en **Herramientas > JSON Versiones**

## 🔍 Verificación Post-Reinstalación

Después de reinstalar, verifica:

1. ✅ **Menú visible**: Herramientas > JSON Versiones
2. ✅ **Archivo JSON existe**: `/wp-content/plugins/json_version_plugin/mcp-metadata.json`
3. ✅ **API REST funciona**: `/wp-json/jvm/v1/verify`
4. ✅ **JSON se sirve**: `/wp-content/plugins/json_version_plugin/mcp-metadata.json`

## ⚠️ Notas Importantes

- **No se pierden datos**: Las opciones de WordPress se mantienen al desactivar/reactivar
- **JSON se mantiene**: El archivo `mcp-metadata.json` NO se elimina al desactivar
- **Licencias se mantienen**: Las licencias configuradas se conservan

## 🐛 Si Algo Sale Mal

1. Revisa los logs de WordPress (`wp-content/debug.log` si `WP_DEBUG` está activo)
2. Verifica permisos de archivos (el plugin necesita escribir en su directorio)
3. Asegúrate de que PHP 8.0+ está instalado
4. Verifica que WordPress 6.4+ está instalado


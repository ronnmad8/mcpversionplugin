# API de Licencias - json_version_plugin

## 📋 Descripción

El plugin `json_version_plugin` ahora gestiona el endpoint `/api/verify` para verificar licencias del plugin `mcp-stream-wp`.

## 🔗 Endpoint

```
POST /wp-json/jvm/v1/verify
```

### Parámetros

- `license_key` (requerido): Clave de licencia a verificar
- `site_url` (opcional): URL del sitio que solicita la verificación
- `plugin` (opcional): Nombre del plugin (por defecto: `mcp-stream-wp`)

### Respuesta Exitosa

```json
{
    "license": "valid",
    "expires": 1735689600,
    "customer": "Nombre del Cliente",
    "activations": 1,
    "site_url": "https://ejemplo.com"
}
```

### Respuesta de Error

```json
{
    "code": "invalid_license",
    "message": "Invalid license key",
    "data": {
        "status": 403
    }
}
```

## 🎛️ Gestión desde el Admin

### Añadir Licencia

1. Ve a **Herramientas > JSON Versiones**
2. Desplázate a la sección **"Gestión de Licencias"**
3. Completa el formulario:
   - **Clave de Licencia**: La clave única de la licencia
   - **Cliente**: Nombre del cliente (opcional)
   - **Expira**: Fecha de expiración (opcional)
   - **Máx. Activaciones**: Número máximo de sitios (por defecto: 1)
4. Haz clic en **"Añadir Licencia"**

### Ver Licencias

La tabla muestra:
- Clave de licencia (parcialmente oculta)
- Cliente
- Fecha de expiración
- Activaciones actuales / Máximo permitido
- Botón para eliminar

### Eliminar Licencia

1. Busca la licencia en la tabla
2. Haz clic en **"Eliminar"**
3. Confirma la eliminación

## ⚙️ Configuración en mcp-stream-wp

El plugin `mcp-stream-wp` se configura automáticamente para usar este endpoint si `json_version_plugin` está activo.

Si `json_version_plugin` está activo:
- Usa: `https://tudominio.com/wp-json/jvm/v1/verify`

Si `json_version_plugin` NO está activo:
- Usa: `https://licenses.renekay.com/api/verify` (servidor externo)

## 🔒 Seguridad

- Solo usuarios con `manage_options` pueden gestionar licencias
- Las licencias se validan con nonces
- El endpoint es público pero valida internamente
- Se registran las activaciones por sitio

## 📝 Almacenamiento

Las licencias se guardan en:
- `jvm_valid_licenses`: Array de licencias válidas
- `jvm_license_activations`: Array de activaciones por licencia

## 🎯 Ventajas

1. **Centralización**: Todo en un solo plugin
2. **Simplicidad**: No necesitas servidor externo
3. **Control**: Gestionas las licencias desde WordPress
4. **Integración**: Funciona automáticamente con `mcp-stream-wp`


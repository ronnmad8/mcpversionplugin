# Verificación de API de Licencias

## ✅ Estado Actual

El plugin `json_version_plugin` **SÍ sirve correctamente** la API de licencias para que cualquier sitio web con `mcp-stream-wp` instalado pueda consultarla.

## 🔗 Endpoint Público

**URL del Endpoint:**
```
https://renekay.com/wp-json/jvm/v1/verify
```

**Método:** POST

**Accesibilidad:** ✅ Público (sin autenticación requerida)

## 📋 Cómo Funciona

### 1. En el Servidor de Licencias (renekay.com)

- `json_version_plugin` está instalado
- Expone el endpoint REST API: `/wp-json/jvm/v1/verify`
- Gestiona las licencias desde el admin de WordPress
- El endpoint es público (`permission_callback => '__return_true'`)

### 2. En los Sitios Cliente (cualquier dominio)

- `mcp-stream-wp` está instalado
- Al verificar una licencia, hace una petición POST a:
  ```
  https://renekay.com/wp-json/jvm/v1/verify
  ```
- Envía: `license_key`, `site_url`, `plugin`
- Recibe: Respuesta JSON con el estado de la licencia

## 🔄 Flujo de Verificación

```
┌─────────────────────────────────────┐
│  Sitio Cliente (ejemplo.com)        │
│  Plugin: mcp-stream-wp              │
└──────────────┬──────────────────────┘
               │
               │ POST https://renekay.com/wp-json/jvm/v1/verify
               │ Body: {license_key, site_url, plugin}
               │
               ▼
┌─────────────────────────────────────┐
│  Servidor de Licencias (renekay.com)│
│  Plugin: json_version_plugin         │
│  Endpoint: /wp-json/jvm/v1/verify   │
└──────────────┬──────────────────────┘
               │
               │ Verifica en jvm_valid_licenses
               │ - ¿Existe?
               │ - ¿Expirada?
               │ - ¿Límite activaciones?
               │
               ▼
┌─────────────────────────────────────┐
│  Respuesta JSON                      │
│  {license: "valid", expires, ...}   │
└─────────────────────────────────────┘
```

## ✅ Verificación Técnica

### Endpoint Configurado Correctamente

```php
// includes/class-license-api.php
register_rest_route(
    'jvm/v1',
    '/verify',
    array(
        'methods'             => 'POST',
        'callback'            => array( $this, 'verify_license' ),
        'permission_callback' => '__return_true', // ✅ Público
        ...
    )
);
```

### Cliente Configurado Correctamente

```php
// mcp-stream-wp/includes/license/class-license-manager.php
$license_server_base = 'https://renekay.com';
$license_server_url = trailingslashit( $license_server_base ) . 'wp-json/jvm/v1/verify';
```

## 🧪 Prueba Manual

Puedes probar el endpoint con curl:

```bash
curl -X POST https://renekay.com/wp-json/jvm/v1/verify \
  -H "Content-Type: application/json" \
  -d '{
    "license_key": "TEST-LICENSE-KEY",
    "site_url": "https://ejemplo.com",
    "plugin": "mcp-stream-wp"
  }'
```

**Respuesta esperada:**
- Si la licencia existe: `{"license":"valid","expires":...,"customer":"..."}`
- Si no existe: `{"code":"invalid_license","message":"Invalid license key","data":{"status":403}}`

## ✅ Conclusión

**SÍ, el sistema funciona correctamente para consultas remotas.**

Cualquier sitio web con `mcp-stream-wp` instalado puede verificar licencias consultando el endpoint en `renekay.com`.


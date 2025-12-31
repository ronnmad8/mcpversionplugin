# Acceso Directo al Plugin

Si el menú no aparece en el lateral, puedes acceder directamente usando estas URLs:

## 🔗 URLs de Acceso Directo

### Opción 1: Desde Herramientas
```
https://tudominio.com/wp-admin/tools.php?page=json-version-manager
```

### Opción 2: Desde Menú Principal
```
https://tudominio.com/wp-admin/admin.php?page=json-version-manager
```

## 📝 Añadir Enlace Manual en el Menú

Si quieres añadir un enlace manual en el menú lateral, puedes usar este código en `functions.php` de tu tema (o en un plugin de código personalizado):

```php
add_action( 'admin_menu', function() {
    add_management_page(
        'JSON Version Manager',
        'JSON Versiones',
        'manage_options',
        'json-version-manager',
        function() {
            // Redirigir a la página del plugin
            wp_redirect( admin_url( 'tools.php?page=json-version-manager' ) );
            exit;
        }
    );
}, 999 );
```

## 🔍 Verificar que el Plugin Funciona

1. Accede directamente a: `https://tudominio.com/wp-admin/tools.php?page=json-version-manager`
2. Si la página carga correctamente, el plugin funciona
3. El problema es solo de visualización del menú

## 🛠️ Solución Temporal

Mientras se resuelve el problema del menú, puedes:
1. Guardar la URL como favorito
2. Añadir un enlace manual en el menú usando el código de arriba
3. Usar un plugin de menús personalizados


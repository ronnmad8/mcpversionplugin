# Instrucciones: Cómo Ver el Menú JSON Versiones

## 🔍 Dónde Buscar el Menú

El menú **"JSON Versiones"** puede aparecer en dos lugares:

### 1. En el Menú Lateral Principal (Más Probable)
Busca directamente en el menú lateral de WordPress:
- **"JSON Versiones"** con un icono de actualización (flechas circulares)
- Debería estar después de "Usuarios" y antes de "Herramientas"
- O puede estar al final del menú lateral

### 2. En Herramientas (Tools)
1. Ve al menú lateral
2. Busca **"Herramientas"** (Tools) - tiene un icono de llave inglesa
3. Haz clic para expandir
4. Busca **"JSON Versiones"** en el submenú

## 🚀 Acceso Directo (Funciona Siempre)

Si no encuentras el menú, puedes acceder directamente usando esta URL:

```
https://tudominio.com/wp-admin/tools.php?page=json-version-manager
```

O si está en el menú principal:

```
https://tudominio.com/wp-admin/admin.php?page=json-version-manager
```

**Reemplaza `tudominio.com` con tu dominio real.**

## ✅ Pasos para Verificar

1. **Desactiva y Reactiva el Plugin**
   - Ve a Plugins > Plugins instalados
   - Desactiva "JSON Version Manager"
   - Actívalo de nuevo
   - Recarga la página (Ctrl+F5)

2. **Busca en el Menú Lateral**
   - Busca "JSON Versiones" directamente en el menú lateral
   - Debería tener un icono de actualización

3. **Usa el Acceso Directo**
   - Copia la URL de arriba
   - Pégala en el navegador
   - Debería cargar la página del plugin

## 🔧 Si Aún No Aparece

### Verificar Permisos
- Asegúrate de estar logueado como **Administrador**
- El menú requiere permisos `manage_options`

### Verificar Conflictos
1. Desactiva temporalmente otros plugins
2. Verifica si el menú aparece
3. Si aparece, reactiva los plugins uno por uno para encontrar el conflicto

### Verificar Tema
- Algunos temas personalizados pueden ocultar menús
- Prueba cambiando temporalmente a un tema por defecto (Twenty Twenty-Four)

## 📝 Nota Importante

El plugin está configurado para aparecer **SIEMPRE** como menú principal si no puede añadirse en Herramientas. Esto significa que deberías ver **"JSON Versiones"** directamente en el menú lateral principal.

Si no lo ves, usa el acceso directo con la URL proporcionada arriba.


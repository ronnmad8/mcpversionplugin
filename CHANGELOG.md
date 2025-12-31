# Changelog

## 1.0.3 - 2024-12-31

### Fixed
- Corrección de warnings de constantes duplicadas en tests
- Mejoras en la definición condicional de constantes para evitar conflictos

### Changed
- Actualización de versión en bootstrap de tests para mantener consistencia

## 1.0.2 - 2024-12-31

### Added
- Sistema completo de gestión de licencias con API REST
- Endpoint `/wp-json/jvm/v1/verify` para verificación de licencias
- Interfaz de administración para gestionar licencias válidas
- Tests unitarios completos para el sistema de licencias (12 tests, 26 assertions)
- Documentación de API de licencias (`LICENSE_API.md`, `VERIFICACION_API.md`)

### Changed
- Integración automática con `mcp-stream-wp` para usar el endpoint de licencias
- Mejoras en el sistema de verificación de licencias remotas

### Fixed
- Corrección en la detección del servidor de licencias para consultas remotas
- Mejoras en el manejo de activaciones de licencias

## 1.0.1 - 2024-12-29

### Fixed
- Corrección del problema de menús duplicados en el admin de WordPress
- Implementación de sistema robusto de registro único de menú

## 1.0.0 - 2024-12-28

### Added
- Versión inicial del plugin
- Gestión de archivo JSON de versiones desde el admin
- Interfaz de administración completa
- Tests unitarios básicos


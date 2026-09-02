# Arquitectura actual

## Resumen

La aplicacion usa Laravel 11 como capa web y de orquestacion sobre tablas existentes de un sistema legacy. El panel privado administra usuarios, mantenimientos, tecnicos, reportes, configuracion, notificaciones y evidencias PDF.

## Flujo general

1. Las rutas publicas reciben login y logout.
2. `AuthController` consulta `usuarios_sistema`, valida `password_hash`, `estado` y conserva la sesion `usuario_autenticado`.
3. Las rutas del panel pasan por `legacy.auth` y `can:access-panel`.
4. `PanelController` recibe solicitudes del panel, valida entradas y coordina servicios legacy.
5. Los servicios y repositorios consultan o actualizan las tablas legacy.
6. Las vistas Blade renderizan la informacion preparada por el controlador.

## Capas

### Controllers

`AuthController` maneja autenticacion. `PanelController` concentra las entradas HTTP del panel y coordina vistas y acciones operativas.

### Services

`app/Services/Legacy` contiene reglas de negocio, consultas especializadas, normalizacion, calculo de estados, periodicidad, notificaciones, resumenes y auditoria de mantenimientos. `app/Services/Diagnostics` contiene auditorias y snapshots de lectura controlada. `SemestreService` y servicios relacionados resuelven reglas transversales del periodo.

### Repositories legacy

Los repositorios estan implementados dentro de `app/Services/Legacy` y usan principalmente Query Builder sobre el esquema existente. Entre ellos estan `MantenimientoRepository`, `UsuarioRepository` y `MantenimientoEventoRepository`.

### Models

`app/Models/User.php` representa el usuario autenticable de Laravel y esta ligado a `usuarios_sistema`. La operacion sobre usuarios, mantenimientos, areas y tecnicos se realiza principalmente mediante repositorios y Query Builder, no mediante modelos Eloquent dedicados para cada tabla legacy.

### Middleware y autorizacion

`EnsureLegacyAuth` integra la sesion legacy con Laravel. El panel requiere `legacy.auth` y `can:access-panel`; la autorizacion se apoya en el Gate y los roles legacy.

### Vistas y frontend

Las vistas estan en `resources/views`, con parciales y componentes reutilizables para navegacion, topbar, filtros, estados y acciones. La interfaz usa Blade y assets compilados con Laravel Mix.

### Commands

`app/Console/Commands` contiene comandos de auditoria de base de datos, snapshots, normalizacion de seriales, reparacion de nombres y sincronizacion de resumenes.

### Tests

`tests/Feature` cubre autenticacion, autorizacion, rutas, panel, flujos de mantenimiento, reportes, notificaciones, comandos e infraestructura. `tests/Unit` cubre servicios, normalizadores, mapeos y reglas aisladas.

## Restricciones arquitectonicas

- El esquema legacy es la fuente existente para usuarios, mantenimientos, areas y tecnicos.
- Las consultas legacy no deben trasladarse a Blade.
- Los cambios que afecten varias tablas deben ser atomicos.
- Las decisiones de migracion progresiva se documentan en `docs/legacy/` y `docs/adr/`.

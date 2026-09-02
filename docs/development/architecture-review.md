# Revision de arquitectura

## Estado actual

Laravel 11 funciona como capa web sobre un esquema legacy. `routes/web.php` define rutas publicas y del panel. `AuthController` maneja autenticacion y `PanelController` coordina entradas HTTP del panel. Los servicios en `app/Services/Legacy` contienen consultas y reglas del dominio. `User` representa la autenticacion sobre `usuarios_sistema`.

El panel usa `EnsureLegacyAuth`, `legacy.auth` y `can:access-panel`. Las vistas Blade se organizan por modulo y parciales. No se encontraron carpetas activas de Events, Jobs o Policies dedicadas; la auditoria tecnica usa `MantenimientoEventoRepository`.

## Responsabilidades observadas

| Capa | Responsabilidad comprobada |
|---|---|
| Routes | Mapeo de URLs, middleware y nombres |
| Controllers | Validacion HTTP, coordinacion y respuestas |
| Services | Reglas de estados, fechas, resumenes, notificaciones y diagnosticos |
| Repositories legacy | Consultas y persistencia sobre tablas existentes |
| Models | Autenticacion Laravel sobre `usuarios_sistema` |
| Middleware | Sincronizacion de autenticacion legacy |
| Blade | Render de datos preparados y formularios |
| Feature tests | Flujos HTTP, servicios y persistencia |
| Unit tests | Reglas y transformaciones aisladas |

## Hallazgos priorizados

### Alto: orquestacion concentrada

Estado actual: `PanelController` coordina muchos modulos y acciones.

Riesgo: aumenta acoplamiento y superficie de regresion.

Mejora futura: separar casos de uso solo al modificar un flujo, preservando rutas existentes.

### Alto: dominio legacy en servicios grandes

Estado actual: `MantenimientoRepository` y `GestionEstadoMantenimientoService` combinan consultas, compatibilidad, estados y transacciones.

Riesgo: las responsabilidades son dificiles de aislar.

Mejora futura: extracciones incrementales con pruebas de regresion.

### Medio: esquema parcialmente modelado

Estado actual: la operacion usa Query Builder y campos legacy; no hay modelos Eloquent dedicados para cada tabla operativa.

Riesgo: contratos de columnas y relaciones dependen del codigo y migraciones.

Mejora futura: mantener un diccionario verificado por tabla y ADRs para cambios.

## Conclusion

La arquitectura funciona, pero mantiene un nucleo legacy concentrado. La evolucion segura es incremental, con pruebas previas y compatibilidad explicita.

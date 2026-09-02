# Auditoria de calidad de codigo

## Alcance

Revision estatica de `app/`, `routes/`, `resources/views/`, `database/` y `tests/`. No se modifico codigo ni se ejecutaron operaciones sobre datos.

## Estado actual

- La aplicacion separa controllers, middleware, servicios legacy, diagnosticos y pruebas.
- La persistencia legacy usa principalmente Query Builder.
- `mantenimiento_eventos` ofrece bitacora tecnica estructurada.
- Feature tests cubren autenticacion, autorizacion, panel, mantenimiento, reportes, notificaciones, comandos e infraestructura.
- Unit tests cubren reglas de estado, normalizadores, mapeos y seguridad de URLs.

## Hallazgos

### Alto: clases con responsabilidades amplias

Estado actual: `PanelController` tiene 1239 lineas, `MantenimientoRepository` 2327 y `GestionEstadoMantenimientoService` 1362.

Riesgo: cambios locales pueden afectar flujos no relacionados y dificultan revisar transacciones y reglas.

Mejora futura: extraer casos de uso de forma incremental, con pruebas de caracterizacion antes de mover logica.

### Medio: acceso legacy distribuido

Estado actual: varios servicios usan directamente `DB::table` para tablas legacy.

Riesgo: reglas de nombres, estados y relaciones pueden repetirse o divergir.

Mejora futura: definir contratos de repositorio por agregado solo cuando exista una necesidad concreta.

### Medio: compatibilidad basada en texto

Estado actual: algunos flujos reconocen marcas antiguas en `observaciones` para mantener registros historicos operables.

Riesgo: cambios de texto pueden romper deteccion de historial.

Mejora futura: migrar consumidores a eventos estructurados conservando fallback mientras exista deuda historica.

### Bajo: contratos incompletamente documentados

Estado actual: no hay un contrato formal para todas las respuestas de servicios ni para cada alias de estado legacy.

Riesgo: consumidores internos pueden depender de arrays o strings no documentados.

Mejora futura: documentar contratos al tocar cada servicio.

## Seguridad evidente

Estado actual: login con `Hash::check`, validacion de hash, regeneracion de sesion, CSRF, rate limiting y Gate de panel.

Riesgo: la autorizacion observada es principalmente a nivel de panel; no se evidencian permisos granulares por modulo.

Mejora futura: revisar autorizacion por accion antes de agregar perfiles nuevos.

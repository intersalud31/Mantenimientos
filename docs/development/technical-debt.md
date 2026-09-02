# Deuda tecnica

## Alta

### Dependencia de textos legacy

Estado actual: ciertos flujos reconocen marcas antiguas dentro de `mantenimientos.observaciones`, mientras los nuevos registran `mantenimiento_eventos`.

Riesgo: la bitacora queda repartida entre texto y estructura; modificar textos puede afectar reversibilidad.

Mejora futura: migrar consumidores a eventos y retirar fallback solo con cobertura historica suficiente.

### Clases de gran tamaño

Estado actual: `MantenimientoRepository`, `GestionEstadoMantenimientoService`, `PanelController` y `Bd3SnapshotService` concentran muchas lineas y casos.

Riesgo: revisiones lentas y regresiones indirectas.

Mejora futura: extraer por caso de uso, sin refactor masivo.

## Media

### Contratos de estado distribuidos

Estado actual: `MantenimientoEstados`, servicios de estado y consultas legacy interpretan estados y aliases.

Riesgo: una nueva condicion puede no reflejarse en todos los consumidores.

Mejora futura: documentar una matriz de estados y centralizar nuevas reglas gradualmente.

### Resumenes derivados

Estado actual: `usuarios` contiene campos de resumen que se sincronizan desde servicios y comandos.

Riesgo: una escritura que omita sincronizacion puede dejar valores desactualizados.

Mejora futura: documentar invariantes y ampliar pruebas de consistencia.

### Esquema legacy parcialmente conocido

Estado actual: las migraciones agregan compatibilidad puntual y la aplicacion consulta tablas existentes.

Riesgo: columnas, indices o foreign keys pueden fallar por datos huerfanos o variaciones del esquema.

Mejora futura: auditar antes de cada cambio y mantener snapshots autorizados.

## Baja

### Documentacion operativa incompleta

Estado actual: hay comandos de auditoria y snapshot, pero no una guia unica para diagnostico local, XAMPP, backups y restauracion.

Riesgo: respuestas operativas inconsistentes ante fallos de infraestructura.

Mejora futura: documentar procedimientos seguros sin incluir secretos ni datos reales.

### Cobertura no funcional limitada

Estado actual: las pruebas cubren logica y respuestas del panel; no demuestran todos los navegadores, responsive, carga o una base productiva.

Riesgo: regresiones fuera del entorno de pruebas.

Mejora futura: agregar validaciones de navegador y procedimientos controlados.

## Prioridad general

La deuda mas urgente es la coexistencia de bitacora textual y estructurada, seguida por la concentracion de logica en clases grandes. Ambas requieren una evolucion incremental con ADRs y pruebas de regresion.

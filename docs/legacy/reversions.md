# Reversiones legacy

## Implementacion actual

El flujo directo de reversion se encuentra en `MantenimientoRepository` y se invoca mediante `revertirAccionesPorCodigoActivo`. El usuario se resuelve por `codigo_activo` y se procesa una o mas acciones, con limite de cantidad entre 1 y 10.

## Seleccion reversible

- `buscarUltimoProgramadoReversible` busca mantenimientos del usuario en estados programables y ordena por `updated_at` e `id`.
- `buscarUltimoRealizadoReversible` busca mantenimientos en estados realizados con el mismo criterio temporal.
- Se prioriza la accion mas reciente entre programada y realizada.
- `reversiblePorEvento` consulta el ultimo evento del mantenimiento.
- Si no existe un evento compatible, se conserva el fallback por marcas antiguas en `observaciones`, incluyendo programacion, ejecucion y reversiones historicas.

## Transiciones

### Realizado a programado

La reversion calcula la fecha siguiente, elimina el pendiente automatico relacionado cuando corresponde, cambia el mantenimiento a `programado` y limpia `fecha_ejecucion`.

### Programado a pendiente

La reversion cambia el mantenimiento a `pendiente` y conserva sus demas datos, incluyendo la observacion humana.

## Eventos

Cada reversion registra un evento `revertido` mediante `MantenimientoEventoRepository`. El evento incluye snapshots antes y despues, y metadata en el bloque `reversion`:

- `tipo_reversion`;
- `codigo_activo`;
- `usuario_id`;
- `origen_reversibilidad`;
- `estado_nuevo`;
- `pendiente_automatico` cuando aplica.

La descripcion identifica la transicion y deja constancia de que las observaciones manuales no fueron modificadas.

## Atomicidad

La operacion esta envuelta en `DB::transaction`. Si falla el registro del evento, se revierte el cambio de estado, la eliminacion del pendiente automatico y cualquier otra escritura realizada dentro de la transaccion.

## Compatibilidad historica

Los registros antiguos pueden no tener filas en `mantenimiento_eventos`. Por eso la seleccion conserva las busquedas por textos legacy en `observaciones`. Esas marcas no se eliminan ni se agregan nuevas marcas en el flujo migrado.

La compatibilidad no implica que todos los servicios hayan abandonado las dependencias textuales; cada consumidor debe migrarse de forma independiente y probarse.

## Alcance conocido

Este documento describe la reversion legacy directa. No define cambios para `GestionEstadoMantenimientoService`, Dashboard, No exigibles, notificaciones ni otros flujos de cancelacion o normalizacion.

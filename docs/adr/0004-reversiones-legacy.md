# ADR 0004: Migracion progresiva de reversiones legacy

- Estado: aceptada e implementada parcialmente
- Alcance: reversion legacy directa por codigo activo

## Problema

El flujo historico de reversion modificaba el estado del mantenimiento y agregaba marcas tecnicas a `mantenimientos.observaciones`. Esto mezclaba comentarios humanos con bitacora, podia hacer crecer la columna y dificultaba distinguir la fuente real de la reversibilidad.

Ademas, existen registros historicos que no tienen eventos y solo conservan las marcas antiguas en `observaciones`.

## Alternativas consideradas

1. Mantener la concatenacion en `observaciones`.
2. Eliminar inmediatamente el fallback textual historico.
3. Migrar todo el historial antiguo antes de cambiar el flujo.
4. Registrar la trazabilidad en `mantenimiento_eventos` y conservar compatibilidad temporal.

## Decision

Se adopta la alternativa 4:

- `observaciones` conserva comentarios humanos y datos historicos.
- Las reversiones nuevas registran un evento `revertido`.
- El evento guarda snapshots y metadata de contexto.
- La seleccion prioriza eventos y mantiene fallback por observaciones legacy.
- El cambio de mantenimiento y el registro del evento ocurren en una transaccion atomica.
- No se migran ni eliminan datos historicos en esta fase.

## Consecuencias positivas

- Se evita agregar nuevas marcas tecnicas a `observaciones`.
- Se conserva la informacion humana existente.
- La auditoria obtiene un registro estructurado por transicion.
- Un fallo de auditoria no deja una reversion parcial.
- Los registros antiguos siguen siendo operables.

## Consecuencias y riesgos

- Durante la migracion coexistiran eventos estructurados y marcas textuales.
- El fallback por observaciones debe conservarse hasta migrar los consumidores historicos.
- El ultimo evento es actualmente la referencia para algunas decisiones; futuras reversiones pueden requerir metadata mas explicita para evitar ambiguedades.
- La identificacion del pendiente automatico continua dependiendo de criterios legacy y debe revisarse en una fase separada.

## Fuera de alcance

No se modifican `GestionEstadoMantenimientoService`, Dashboard, No exigibles, notificaciones, otros flujos de cancelacion o normalizacion, migraciones, esquema ni datos historicos.

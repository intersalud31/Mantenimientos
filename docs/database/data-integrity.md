# Integridad de datos

## Protecciones actuales

- Las operaciones que cambian varias filas usan `DB::transaction` en repositorios y servicios relevantes.
- Programacion, ejecucion y reversion registran eventos tecnicos cuando el flujo ya esta migrado.
- La reversion exige que el mantenimiento sea elegible por estado, fechas, eventos o fallback historico.
- Los comandos de auditoria y snapshot verifican la base objetivo y ofrecen modos de lectura o confirmacion.
- Las pruebas Feature validan persistencia, rollback, estados, fechas y ausencia de duplicados en flujos importantes.

## Legacy e historicos

No se debe asumir integridad referencial completa. Puede haber estados alias, registros antiguos sin eventos, datos incompletos o relaciones sin foreign key. Los historicos no deben limpiarse o reclasificarse sin una auditoria previa.

## Cambios masivos

Antes de una operacion masiva:

1. Confirmar la base y el entorno.
2. Generar o verificar un respaldo autorizado.
3. Ejecutar una auditoria de conteos y excepciones.
4. Probar en una base aislada.
5. Usar transacciones por unidad segura o lotes controlados.
6. Registrar resultado, errores y posibilidad de rollback.

## Migraciones

Las migraciones existentes son de compatibilidad con el esquema legacy. Una nueva migracion debe justificar compatibilidad, verificar existencia previa y evaluar datos huerfanos. No se deben modificar migraciones historicas para corregir el estado actual.

## Riesgos conocidos

- Concatenar informacion tecnica en columnas de comentario puede provocar truncamientos.
- Cambiar estados sin registrar el evento correspondiente puede dejar trazabilidad incompleta.
- Una foreign key nueva puede fallar por datos legacy existentes.
- Los resumenes derivados de `usuarios` pueden quedar desactualizados si una escritura omite la sincronizacion.
- Las pruebas actuales usan una base de pruebas aislada; eso no sustituye una auditoria de datos reales.

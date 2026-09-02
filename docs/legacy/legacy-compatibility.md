# Compatibilidad Laravel y legacy

## Coexistencia

Laravel proporciona rutas, middleware, controladores, vistas, validacion y pruebas. La informacion operativa continua en tablas legacy y se accede principalmente desde servicios y repositorios en `app/Services/Legacy`.

## Tablas principales conocidas

- `usuarios_sistema`: credenciales, estado y rol de autenticacion.
- `usuarios`: usuarios o equipos asociados al mantenimiento.
- `mantenimientos`: historial, fechas, estados, responsables, observaciones y evidencias.
- `areas`: catalogo de areas.
- `tecnicos`: responsables disponibles.
- `mantenimiento_eventos`: bitacora tecnica de transiciones implementadas progresivamente.

La estructura completa y sus restricciones deben verificarse contra la base activa antes de cambios de esquema.

## Reglas de compatibilidad

- Conservar la autenticacion basada en `usuarios_sistema`.
- Mantener la sesion `usuario_autenticado` y los middleware `legacy.auth` y `can:access-panel`.
- No asumir que todas las relaciones legacy tienen foreign keys o datos completos.
- Usar consultas compatibles con los valores y estados existentes.
- Preservar rutas y parametros legacy cuando tengan consumidores existentes.
- Mantener fallbacks para registros historicos mientras existan marcas o estructuras antiguas.
- No eliminar ni normalizar datos historicos sin auditoria, respaldo y autorizacion.
- Validar cambios de esquema contra registros huerfanos, indices y tipos reales.

## Observaciones y bitacora

`mantenimientos.observaciones` debe conservar comentarios humanos. Las transiciones tecnicas nuevas deben registrarse en `mantenimiento_eventos`, sin borrar marcas antiguas ni asumir que todos los historicos ya tienen eventos.

## Riesgos

- Registros antiguos pueden depender de textos legacy.
- Los estados pueden tener aliases historicos.
- Fechas y periodicidad pueden depender del semestre vigente.
- Una foreign key nueva puede fallar ante datos legacy huerfanos.
- Los cambios parciales entre `mantenimientos` y `mantenimiento_eventos` pueden perder trazabilidad; deben usar transacciones.
- Las evidencias PDF dependen de `Storage::disk('public')` y del enlace `storage:link`.

## Procedimiento seguro

Antes de modificar el esquema o datos:

1. Auditar estructura y conteos en la base correcta.
2. Revisar rutas, servicios, repositorios y pruebas consumidoras.
3. Crear un plan de rollback y un ADR si cambia una decision arquitectonica.
4. Obtener autorizacion cuando la operacion afecte datos reales o esquema.
5. Validar en entorno aislado y ejecutar la suite relevante.

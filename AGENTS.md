# AGENTS.md

## Contexto

Proyecto Laravel 11 con integracion a un esquema legacy. La autenticacion usa `usuarios_sistema` y el panel esta protegido por `legacy.auth` y `can:access-panel`.

La arquitectura detallada, la compatibilidad legacy, la seguridad y las decisiones tecnicas estan documentadas en `docs/`.

## Reglas obligatorias

- Lee este archivo y la documentacion aplicable antes de modificar codigo.
- Audita rutas, controlador, servicio, repositorio, vista y pruebas relacionadas antes de editar.
- Haz cambios minimos y localizados; no refactorices fuera del alcance.
- Conserva los cambios existentes del usuario y revisa `git status --short` antes de trabajar.
- No borres ni sobrescribas codigo, datos, dumps, backups o carpetas de rescate sin autorizacion.
- No abras ni reproduzcas secretos de `.env`, `.env.testing` o copias de respaldo.
- No modifiques datos reales, migraciones, seeders o rollbacks sin autorizacion expresa cuando la tarea los requiera.
- No hagas `git add`, commit ni push sin autorizacion.
- Si el alcance entra en conflicto con cambios pendientes, deten el trabajo y consulta.

## Convenciones de implementacion

- Controllers coordinan solicitudes; la logica de negocio pertenece a Services.
- Las consultas y escrituras sobre el esquema legacy pertenecen a `app/Services/Legacy`.
- Blade no debe contener consultas ni calculos de negocio.
- Mantiene `usuarios_sistema`, `password_hash`, `estado`, `rol` y la sesion `usuario_autenticado` en cambios de autenticacion.
- Conserva `legacy.auth`, `can:access-panel` y la proteccion CSRF.
- Usa transacciones cuando una operacion cambie varias tablas o requiera auditoria atomica.
- Conserva comentarios humanos en `mantenimientos.observaciones`; registra trazabilidad tecnica mediante `mantenimiento_eventos`.
- Para evidencias PDF usa `Storage::disk('public')` y conserva la expectativa de `storage:link`.
- Mantiene colores, tipografia, componentes y patrones visuales existentes en cambios de UI.

## Validacion

- Ejecuta primero la prueba mas cercana al cambio.
- Si cambias auth, rutas, panel, servicios compartidos o migraciones, ejecuta los Feature tests relacionados.
- Si cambias logica reusable, ejecuta tambien los Unit tests correspondientes.
- Ejecuta `php artisan test` completo cuando el cambio atraviese varias capas.
- Revisa sintaxis, diff y estado del repositorio antes de entregar.
- No cierres una tarea con fallos provocados por la intervencion.

## Documentacion

Consulta segun el alcance:

- Arquitectura: `docs/architecture/overview.md`
- Compatibilidad legacy: `docs/legacy/legacy-compatibility.md`
- Reversiones: `docs/legacy/reversions.md`
- Autenticacion: `docs/security/authentication.md`
- Autorizacion: `docs/security/authorization.md`
- Datos sensibles: `docs/security/sensitive-data.md`
- Esquema e integridad: `docs/database/`
- Pruebas: `docs/testing/`
- Decisiones: `docs/adr/`

Actualiza o crea un ADR cuando una tarea cambie autenticacion, estados, bitacora, esquema, compatibilidad legacy o una transaccion critica.

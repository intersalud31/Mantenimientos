# Auditoria tecnica de seguridad

## Alcance y metodo

Auditoria estatica de autenticacion, autorizacion, middleware, validaciones, archivos, datos sensibles, errores y riesgos OWASP aplicables. Se revisaron `AGENTS.md`, `docs/security/`, `docs/architecture/`, `docs/development/`, rutas, controllers, middleware, providers, modelo User, configuracion de errores y vistas relacionadas. No se modifico codigo ni se consultaron datos reales.

La severidad indica prioridad de revision, no confirma explotabilidad en produccion cuando depende de configuracion o infraestructura no visible.

## Resumen

No se identifico un hallazgo critico comprobado en el codigo revisado. Los riesgos principales son de autorizacion granular, proteccion de evidencias, configuracion de errores y consistencia de archivos cargados.

## Hallazgos

### Alto: autorizacion granular no evidenciada

Hallazgo comprobado: todas las rutas privadas del panel usan `legacy.auth` y `can:access-panel`; el Gate acepta los roles `admin`, `administrador` y `superadmin`. No se encontraron Policies ni Gates separados por modulo o registro.

Evidencia: `routes/web.php`, `app/Providers/AuthServiceProvider.php`, `app/Http/Kernel.php` y `app/Http/Middleware/EnsureLegacyAuth.php`.

Riesgo: cualquier usuario que supere el Gate puede alcanzar las acciones administrativas disponibles, incluidas modificaciones de usuarios, mantenimientos, reversiones y evidencias. El impacto depende de que los tres roles deban tener exactamente el mismo alcance.

Recomendacion futura: definir una matriz de permisos por accion y agregar autorizacion especifica solo si el modelo operativo requiere separacion de privilegios. No asumir que debe cambiarse el comportamiento actual sin aprobacion.

### Alto: evidencias en disco publico

Hallazgo comprobado: las evidencias se almacenan en el disco `public` y se sirven mediante `Storage::disk('public')`. La ruta de descarga esta protegida por el grupo del panel, pero no se evidencio un control de autorizacion adicional por mantenimiento.

Evidencia: `PanelController::evidenciaPdf`, `marcarProgramadoComoHecho`, `gestionarEstadoMantenimiento`, `config/filesystems.php` y las vistas de carga PDF.

Riesgo: cualquier administrador autorizado puede solicitar una evidencia si conoce su identificador. Si el enlace publico o la configuracion del servidor exponen directamente el directorio, el control de Laravel podria quedar evadido.

Recomendacion futura: verificar la configuracion real del servidor y evaluar almacenamiento privado con descarga autorizada. Mantener `Storage::disk('public')` hasta aprobar un cambio compatible.

### Alto: archivos potencialmente huerfanos tras excepciones

Hallazgo comprobado: en algunos flujos el PDF se almacena antes de invocar la operacion de negocio y se elimina cuando el resultado devuelve `ok = false`. No se observa una limpieza equivalente para toda excepcion inesperada posterior a `storeAs`.

Evidencia: `PanelController::marcarProgramadoComoHecho` y `gestionarEstadoMantenimiento`.

Riesgo: pueden quedar archivos sin registro en base de datos, aumentando consumo y dificultando auditoria o eliminacion segura.

Recomendacion futura: encapsular la escritura de archivo y la operacion de negocio con una estrategia de compensacion probada para excepciones. No eliminar archivos automaticamente sin un inventario y politica de retencion.

### Medio: configuracion de errores depende del entorno

Hallazgo comprobado: `config/app.php` obtiene `debug` de `APP_DEBUG` y `config/logging.php` usa nivel `debug` por defecto. El handler no define una politica adicional de exposicion.

Evidencia: `config/app.php`, `config/logging.php` y `app/Exceptions/Handler.php`.

Riesgo: si un entorno no productivo o productivo tiene debug activo, las excepciones pueden revelar rutas internas, consultas, nombres de tablas y datos de contexto. Los logs detallados tambien pueden contener informacion sensible.

Recomendacion futura: verificar despliegues con `APP_DEBUG=false`, revisar niveles de log y aplicar redaccion y retencion. No se inspeccionaron archivos `.env` por contener secretos.

### Medio: validacion de observaciones sin limite uniforme

Hallazgo comprobado: varios formularios validan `observaciones` como `nullable|string` sin un maximo visible en el controller, mientras el esquema legacy tiene restricciones conocidas para esa columna.

Evidencia: validaciones de `PanelController` y documentacion de compatibilidad y datos legacy.

Riesgo: entradas extensas pueden provocar errores de persistencia o denegacion funcional, como el error historico de truncamiento. No implica por si solo una inyeccion SQL porque Query Builder usa bindings en las consultas observadas.

Recomendacion futura: definir limites alineados con el esquema y separar comentarios humanos de bitacora tecnica. Cualquier truncamiento debe ser explicito y no silencioso.

### Medio: dependencia de compatibilidad textual

Hallazgo comprobado: ciertos flujos consultan marcas legacy dentro de `mantenimientos.observaciones` para resolver reversiones historicas.

Evidencia: `MantenimientoRepository` y `docs/legacy/reversions.md`.

Riesgo: modificar o normalizar textos puede alterar decisiones de negocio y permitir clasificaciones inesperadas. El riesgo es de integridad y autorizacion de operacion, no se comprobo explotacion remota.

Recomendacion futura: migrar consumidores a `mantenimiento_eventos` gradualmente, conservar fallback hasta tener cobertura historica y probar cada transicion.

### Bajo: mensajes y codificacion de errores

Hallazgo comprobado: algunos mensajes de error se devuelven al usuario y el codigo contiene textos con problemas de codificacion visibles en ciertos archivos o salidas.

Evidencia: `AuthController`, `PanelController` y vistas Blade.

Riesgo: principalmente confusion operativa; en ciertos casos mensajes demasiado especificos pueden ayudar al reconocimiento de infraestructura, aunque no se comprobo que expongan secretos.

Recomendacion futura: normalizar UTF-8 y mantener mensajes generales para usuarios, dejando detalles tecnicos en logs controlados.

## Controles observados

- Login con validacion de entrada, normalizacion de correo y `Hash::check`.
- Rechazo de hashes no reconocidos y usuarios inactivos.
- Regeneracion de sesion tras login.
- Invalidacion de sesion y regeneracion de CSRF en logout.
- Rate limiting por correo/IP.
- Middleware `legacy.auth` y Gate `access-panel` para el panel.
- Proteccion CSRF en formularios POST.
- Validacion de PDF por archivo, MIME y tamano maximo de 5120 KB en los flujos revisados.
- Uso de `basename` para el nombre original del archivo antes de almacenarlo.
- Uso de Query Builder y parametros enlazados en las consultas revisadas.
- Ocultamiento de `password_hash` en el modelo User.

## Riesgos OWASP aplicables

| Categoria | Estado observado | Riesgo residual |
|---|---|---|
| Control de acceso | Gate global de panel | Falta de granularidad por accion o registro |
| Inyeccion | Query Builder y bindings observados | Revisar cualquier SQL raw nuevo |
| Identidad y autenticacion | Hash, sesion regenerada y rate limiting | Dependencia del esquema legacy y configuracion de sesion |
| Datos sensibles | Campos ocultos y reglas documentadas | Logs, dumps y evidencias requieren control operativo |
| Configuracion insegura | Debug depende de entorno | Verificar despliegue y valores reales |
| Integridad de archivos | MIME, tamano y nombre saneado | Posibles huerfanos y exposicion del disco publico |
| Integridad transaccional | Transacciones en operaciones criticas | Archivos no participan directamente en transacciones DB |
| Registro y monitoreo | `report()` y logs Laravel | No se evidencio politica de retencion/redaccion |

## No comprobado

Esta auditoria no confirma configuracion real de `.env`, permisos del sistema operativo, reglas del servidor web, HTTPS, cookies seguras, backups, firewall, antivirus, dependencias vulnerables ni exposicion directa de `public/storage`. Deben verificarse en un entorno autorizado sin revelar secretos.

## Prioridades futuras

1. Confirmar `APP_DEBUG=false` y la proteccion real de evidencias.
2. Decidir si se requieren permisos granulares.
3. Resolver limpieza compensatoria de archivos cargados.
4. Completar la separacion de bitacora tecnica y observaciones.
5. Definir politica de logs, retencion y redaccion.

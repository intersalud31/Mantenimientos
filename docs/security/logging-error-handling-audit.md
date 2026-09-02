# Auditoria de logging y manejo de errores

## Alcance y metodo

Esta auditoria revisa la configuracion versionada y el codigo de manejo de
excepciones, mensajes y archivos. No se inspeccionaron `.env`, `.env.testing`
ni sus copias, por lo que no se puede afirmar cual es la configuracion efectiva
del entorno en ejecucion. No se modificaron codigo, configuracion, base de
datos, migraciones ni pruebas.

## Flujo actual comprobado

### Logging y excepciones de Laravel

- `config/logging.php` define `stack` como canal predeterminado y este utiliza
  el canal `single`.
- `single` escribe en `storage/logs/laravel.log` y toma el nivel desde
  `LOG_LEVEL`, con valor por defecto `debug`.
- Tambien existe un canal `daily` con retencion configurada de 14 dias, pero
  no se comprobo que sea el canal activo.
- `config/app.php` obtiene `debug` desde `APP_DEBUG` y usa `false` como valor
  por defecto.
- `app/Exceptions/Handler.php` protege del flash de excepciones los campos
  `current_password`, `password` y `password_confirmation`. No declara
  exclusiones en `dontReport` y el callback `reportable` no agrega filtrado ni
  redaccion visible en el codigo.

### Controllers y mensajes al usuario

- `AuthController` captura errores de consulta durante el login y devuelve un
  mensaje generico. Cuando falla la actualizacion de `ultimo_acceso`, llama a
  `report($exception)` sin mostrar la excepcion al usuario.
- `PanelController` valida entradas con la validacion de Laravel y comunica
  resultados mediante `panel_error` y `panel_message`. Las operaciones de
  archivos y acciones del panel devuelven mensajes funcionales, no trazas SQL.
- La vista de login y las vistas del panel imprimen mensajes de sesion y
  errores de validacion con interpolacion Blade escapada (`{{ ... }}`).
- Las rutas del panel estan protegidas por `legacy.auth` y `can:access-panel`;
  el reporte de errores no sustituye esas comprobaciones de autorizacion.

### Services, repositories y comandos

- `GestionEstadoMantenimientoService` y otros servicios legacy usan
  transacciones para operaciones de negocio y convierten varias excepciones
  en respuestas `ok=false` con un mensaje funcional.
- `MantenimientoRepository` concentra consultas y acciones legacy; sus
  respuestas de error se consumen desde el controller. La auditoria no
  encontro en los archivos revisados un canal de logging estructurado propio
  para cada transicion.
- `BaseDatosAuditoriaCommand` captura `Throwable` y muestra un mensaje generico
  en consola. `Bd3SnapshotCommand` tambien comunica fallos de forma controlada.

### Archivos y errores compensatorios

`PanelController` almacena evidencias en el disco `public`, comprueba su
existencia al servirlas y elimina archivos cuando una operacion de negocio
devuelve fallo. El detalle de posibles archivos huerfanos, exposicion publica y
falta de reconciliacion esta documentado en
`docs/security/file-lifecycle-audit.md` y no se repite aqui.

## Hallazgos

### Medio - La configuracion efectiva de desarrollo/produccion no es auditable desde el repositorio

**Comportamiento actual:** `APP_DEBUG` y `LOG_LEVEL` dependen del entorno; los
valores por defecto son `false` y `debug`, respectivamente. No se inspecciono
`.env` por restriccion de seguridad y no hay evidencia en el codigo revisado
que permita confirmar el valor efectivo ni si existe cache de configuracion.

**Riesgo:** si un entorno productivo mantiene `APP_DEBUG=true` o un nivel de
logging demasiado detallado, una excepcion puede revelar trazas, rutas internas,
consultas o contexto sensible. El riesgo es condicional, no una fuga confirmada.

**Mejora futura:** verificar fuera del repositorio la configuracion operativa,
mantener `APP_DEBUG=false` en produccion, definir niveles por entorno y revisar
la politica de cache y despliegue sin registrar secretos.

### Medio - No existe una politica explicita de redaccion y retencion para logs

**Comportamiento actual:** el canal activo por configuracion apunta a un archivo
local y el `Handler` no incorpora redaccion adicional. El canal `daily` tiene
14 dias, pero no se comprobo que este seleccionado. No se identifico una
politica documentada de clasificacion, acceso, rotacion o eliminacion de logs.

**Riesgo:** excepciones reportadas por `report()` y errores no controlados
podrian contener identificadores, rutas de archivos, datos de consultas o
contexto de usuarios. La ausencia de redaccion aumenta el impacto si el log es
leido por personal no autorizado o se conserva demasiado tiempo.

**Mejora futura:** definir canales por entorno, retencion, permisos del archivo,
alertas y una politica de datos sensibles; evitar incluir credenciales,
tokens, contrasenas, contenido de PDFs u observaciones completas.

### Bajo - `dontReport` no limita excepciones

**Comportamiento actual:** `Handler` deja `dontReport` vacio, por lo que las
excepciones no excluidas por otro mecanismo pueden ser reportadas por el
handler estandar.

**Riesgo:** puede generar ruido y registrar excepciones esperables con datos
de contexto innecesarios. No se comprobo una fuga concreta causada por esta
configuracion.

**Mejora futura:** clasificar excepciones esperables y decidir explicitamente
cuales se reportan, sin ocultar fallos de seguridad o integridad.

### Medio - Captura amplia puede ocultar causa tecnica

**Comportamiento actual:** varios servicios capturan `Throwable` y retornan
mensajes funcionales o `ok=false`; `SemestreService` retorna `false` ante
errores de parseo. Esto evita mostrar trazas, pero no todos esos caminos
registran la causa tecnica de forma visible en el codigo revisado.

**Riesgo:** fallos persistentes pueden quedar reducidos a mensajes genericos y
ser dificiles de diagnosticar, especialmente en transiciones legacy. El riesgo
principal es mantenibilidad y deteccion tardia, no una alteracion confirmada de
datos.

**Mejora futura:** conservar el mensaje seguro para el usuario y registrar un
evento tecnico contextualizado, con identificadores no sensibles y excepcion
encadenada, respetando transacciones y evitando duplicar reportes.

### Bajo - Mensajes de error funcionales dependen de consistencia manual

**Comportamiento actual:** controllers y vistas usan distintas claves de sesion
(`error_login`, `panel_error`, `panel_message`) para presentar resultados.

**Riesgo:** una nueva vista o accion podria imprimir accidentalmente un mensaje
tecnico sin el mismo control aplicado en las rutas revisadas.

**Mejora futura:** documentar y estandarizar las categorias de mensajes, sin
mezclarlas con excepciones ni exponer detalles internos.

## Controles positivos observados

- El login usa mensajes genericos para credenciales invalidas y fallos de base
  de datos.
- Los campos de contrasena estan excluidos del flash de excepciones.
- El formulario de login usa validacion y las rutas POST tienen throttling.
- Las rutas del panel conservan middleware de autenticacion y autorizacion.
- Las vistas revisadas usan salida Blade escapada para errores y mensajes.
- Se usan consultas parametrizadas mediante Query Builder en el login.

## Relacion con riesgos OWASP

- **A05 Security Misconfiguration:** riesgo condicional por valores efectivos de
  `APP_DEBUG` y `LOG_LEVEL` no verificables desde el repositorio.
- **A09 Security Logging and Monitoring Failures:** ausencia comprobada de una
  politica explicita de redaccion, retencion y monitoreo.
- **A07 Identification and Authentication Failures:** existen controles
  positivos de validacion, throttling y mensajes genericos; no se modificaron ni
  se audita aqui su diseño completo.
- **A01 Broken Access Control:** las rutas del panel tienen middleware, pero la
  correcta autorizacion por registro de evidencias queda como riesgo separado
  en la auditoria de almacenamiento.
- No se observó en esta revisión una evidencia directa de inyeccion causada por
  el manejo de logs; esto no sustituye una prueba de seguridad integral.

## Limitaciones y pendientes

No se verificaron valores reales de entorno, permisos del sistema operativo
sobre `storage/logs`, configuracion del servidor web, centralizacion externa de
logs, rotacion real ni monitoreo en produccion. Tampoco se reprodujeron fallos
ni se inspeccionaron datos reales.

## Archivos revisados

- `AGENTS.md`
- `config/logging.php`
- `config/app.php`
- `app/Exceptions/Handler.php`
- `app/Http/Controllers/AuthController.php`
- `app/Http/Controllers/PanelController.php`
- `app/Http/Middleware/EnsureLegacyAuth.php`
- `app/Providers/AuthServiceProvider.php`
- `app/Console/Commands/BaseDatosAuditoriaCommand.php`
- `app/Console/Commands/Bd3SnapshotCommand.php`
- `app/Services/SemestreService.php`
- `app/Services/Legacy/GestionEstadoMantenimientoService.php`
- `app/Services/Legacy/MantenimientoRepository.php`
- `resources/views/auth/login.blade.php`
- vistas y parciales del panel que muestran errores de validacion o sesion
- `docs/security/security-audit.md`
- `docs/security/sensitive-data.md`
- `docs/security/file-lifecycle-audit.md`
- `docs/architecture/`

## Validaciones realizadas

- Lectura focalizada de configuracion, handler, controllers, services,
  comandos y vistas.
- Busqueda de `catch`, `report`, `Throwable`, `APP_DEBUG`, `LOG_LEVEL`, mensajes
  de sesion y operaciones de almacenamiento.
- No se ejecutaron pruebas ni comandos que modifiquen datos, porque esta tarea
  es exclusivamente de auditoria.
- No se abrieron archivos `.env` ni equivalentes.
- No hubo cambios productivos, de configuracion, base de datos, migraciones ni
  pruebas.
- No se ejecutaron `git add`, `git commit` ni `git push`.

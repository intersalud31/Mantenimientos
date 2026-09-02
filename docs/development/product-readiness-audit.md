# Auditoria integral de madurez tecnica

## Resumen ejecutivo

La aplicacion es funcional y tiene controles importantes de autenticacion,
autorizacion, transacciones y pruebas Feature/Unit. Sin embargo, su madurez
para crecimiento profesional es **parcial**: el nucleo operativo depende de un
esquema legacy parcialmente versionado y concentra reglas en clases grandes.

No se identifico un bloqueador critico comprobado de seguridad en esta
auditoria. La preparacion para produccion no puede certificarse completamente
porque no se verificaron valores reales de entorno, servidor, permisos
operativos, carga ni una base productiva.

## Matriz de madurez

| Area | Estado | Riesgo |
|---|---|---|
| Arquitectura | Parcial | Alto |
| Calidad de codigo | Parcial | Alto |
| Seguridad | Parcial con controles positivos | Medio/Alto |
| Base de datos | Legacy parcialmente documentada | Alto |
| Escalabilidad | No validada bajo carga | Medio/Alto |
| UX y frontend | Funcional, cobertura no exhaustiva | Medio |
| Testing | Cobertura funcional relevante | Medio |
| DevOps y mantenimiento | Parcialmente documentado | Medio/Alto |
| Documentacion | Buena base, pendiente operativa | Medio |

## Hallazgos

### Nucleo operativo concentrado

**Severidad:** Alto

**Evidencia:** `PanelController.php` tiene 1239 lineas,
`MantenimientoRepository.php` 2327 y
`GestionEstadoMantenimientoService.php` 1362. Concentran multiples casos de
uso, consultas, transiciones, archivos y presentacion.

**Archivos relacionados:** `app/Http/Controllers/PanelController.php`,
`app/Services/Legacy/MantenimientoRepository.php`,
`app/Services/Legacy/GestionEstadoMantenimientoService.php`.

**Impacto:** aumenta la superficie de regresion y dificulta pruebas, revisiones
y cambios aislados.

**Recomendacion futura:** extraer casos de uso incrementalmente, empezando por
flujos con transacciones y evidencias, despues de crear pruebas de
caracterizacion. No realizar un refactor masivo.

### Esquema operativo legacy parcialmente versionado

**Severidad:** Alto

**Evidencia:** el codigo consulta directamente `usuarios`, `mantenimientos`,
`areas`, `usuarios_sistema`, `tecnicos` y `configuracion_sistema`, pero las
migraciones no definen completamente todas esas tablas.

**Archivos relacionados:** `database/migrations/`,
`app/Services/Legacy/`, `docs/database/schema-migration-review.md`.

**Impacto:** instalaciones diferentes pueden tener columnas, indices,
constraints o tipos distintos; `migrate` por si solo no reproduce todo el
esquema operativo.

**Recomendacion futura:** mantener un inventario verificado por entorno,
compararlo con migraciones y documentar divergencias antes de cualquier cambio.

### Bitacora textual y estructurada coexistente

**Severidad:** Alto

**Evidencia:** `mantenimiento_eventos` se usa para flujos nuevos, mientras
existen fallbacks y escrituras legacy relacionadas con marcas en
`mantenimientos.observaciones`.

**Archivos relacionados:** `MantenimientoRepository.php`,
`MantenimientoEventoRepository.php`, `TextoLegacyNormalizer.php`,
`docs/legacy/reversions.md`.

**Impacto:** cambios en textos o limites de observaciones pueden afectar
reversibilidad e integridad historica; aumenta el riesgo de truncamiento y
reglas divergentes.

**Recomendacion futura:** completar la migracion progresiva de consumidores a
eventos, conservando fallback historico hasta contar con cobertura suficiente.

### Autorizacion principalmente a nivel de panel

**Severidad:** Medio/Alto

**Evidencia:** las rutas del panel usan `legacy.auth` y `can:access-panel`, y el
Gate acepta roles administrativos normalizados. No se comprobaron Policies ni
permisos granulares por modulo o registro.

**Archivos relacionados:** `routes/web.php`, `EnsureLegacyAuth.php`,
`AuthServiceProvider.php`, `docs/security/authorization.md`.

**Impacto:** cualquier rol con acceso al panel puede potencialmente alcanzar
las acciones expuestas por esas rutas, sujeto a validaciones de cada flujo.
No se asume que esto contradiga reglas de negocio no documentadas.

**Recomendacion futura:** definir una matriz de permisos por accion antes de
agregar roles o exponer operaciones mas sensibles.

### Riesgo condicional de configuracion de errores y logs

**Severidad:** Medio/Alto

**Evidencia:** `APP_DEBUG` depende del entorno y usa `false` por defecto;
`LOG_LEVEL` usa `debug` por defecto; `Handler` no define redaccion adicional ni
exclusiones de reporte.

**Archivos relacionados:** `config/app.php`, `config/logging.php`,
`app/Exceptions/Handler.php`, `docs/security/logging-error-handling-audit.md`.

**Impacto:** si el entorno productivo tiene debug o logging excesivo, pueden
exponerse trazas, rutas o contexto sensible.

**Recomendacion futura:** verificar configuracion fuera del repositorio,
mantener debug desactivado en produccion y definir retencion, permisos y
redaccion. Los valores efectivos no fueron comprobados porque no se abrieron
`.env`.

### Evidencias PDF publicas y ciclo de vida incompleto

**Severidad:** Medio/Alto

**Evidencia:** se usa `Storage::disk('public')`; el controller verifica la ruta
y elimina archivos ante algunos resultados fallidos. No se comprobo
reconciliacion automatica de huerfanos ni autorizacion por registro en la
descarga.

**Archivos relacionados:** `PanelController.php`, `MantenimientoRepository.php`,
`config/filesystems.php`, `docs/security/evidence-storage-audit.md`.

**Impacto:** posible exposicion de documentos si se conoce una URL y acumulacion
de archivos sin relacion o registros sin archivo.

**Recomendacion futura:** definir autorizacion por evidencia, ciclo de vida,
retencion y reconciliacion antes de aumentar volumen.

### Consultas repetidas y reglas distribuidas

**Severidad:** Medio

**Evidencia:** existen pares `contar...`/`listar...`, variantes de notificaciones
y servicios/comandos que consultan las mismas tablas y reglas de periodo.

**Impacto:** conteos y listados pueden divergir; con mas datos pueden aparecer
problemas de rendimiento y consultas costosas.

**Recomendacion futura:** unificar constructores de consulta y medir consultas
criticas antes de optimizar o pasar procesos a Jobs.

### Escalabilidad no validada

**Severidad:** Medio

**Evidencia:** reportes, notificaciones y normalizaciones realizan consultas y
procesos sin evidencia de pruebas de carga, profiling o Jobs dedicados. No se
encontraron carpetas activas de Jobs en la auditoria.

**Impacto:** crecimiento de mantenimientos, eventos y PDFs puede aumentar
tiempos de respuesta, memoria y tamano de almacenamiento.

**Recomendacion futura:** establecer volumen objetivo, medir consultas e
incorporar Jobs solo para tareas idempotentes y justificadas.

### Cobertura funcional relevante pero no completa

**Severidad:** Medio

**Evidencia:** hay pruebas de autenticacion, autorizacion, panel, transiciones,
reportes, notificaciones, comandos, eventos e infraestructura. No demuestran
por si solas responsive real, carga, navegadores, produccion o todos los casos
de archivo y autorizacion por registro.

**Impacto:** regresiones no funcionales o de entorno pueden llegar a usuarios
aunque la suite funcional pase.

**Recomendacion futura:** agregar pruebas de seguridad de archivos, contratos
de datos, navegadores y rendimiento de forma priorizada.

### Documentacion operativa incompleta

**Severidad:** Bajo/Medio

**Evidencia:** existe documentacion extensa de arquitectura, seguridad, legacy,
base de datos, desarrollo y pruebas, pero no una guia unica comprobada para
despliegue, backups, restauracion, XAMPP, permisos de logs y respuesta a
incidentes.

**Impacto:** respuestas operativas inconsistentes y mayor dependencia del
conocimiento local.

**Recomendacion futura:** crear procedimientos operativos sin secretos ni
datos reales, con responsables y pasos de verificacion.

## Bloqueadores para cierre del producto

Estos puntos deben resolverse o aceptarse formalmente antes de declarar una
version lista para produccion:

- Confirmar en el entorno objetivo `APP_DEBUG=false`, logging apropiado,
  permisos y retencion de logs.
- Verificar esquema real, indices, engines y foreign keys de las tablas legacy.
- Definir control de acceso y ciclo de vida de evidencias PDF.
- Ejecutar la suite completa y resolver cualquier fallo provocado por la
  intervencion actual.
- Validar backups y restauracion en un entorno aislado.
- Definir criterios de volumen y observar consultas/reportes criticos.
- Confirmar que la coexistencia de observaciones y eventos no produce nuevas
  escrituras tecnicas en observaciones en los flujos aprobados.

No se comprobo que alguno de estos puntos este incumplido en produccion; son
criterios pendientes de verificacion o aceptacion.

## Mejoras futuras

- Separacion progresiva de controllers, repositories y servicios grandes.
- DTOs para previews, transiciones y resultados.
- Modelos Eloquent selectivos para tablas estables, sin migracion total.
- Matriz de estados y permisos por accion.
- Pruebas de integridad y reconciliacion de evidencias.
- Profiling, indices verificados y Jobs idempotentes cuando el volumen lo
  justifique.
- Estandarizacion de logging, redaccion y monitoreo.
- Procedimientos de despliegue, backups, restauracion y respuesta operativa.

## Prioridad recomendada

### P0 - Antes de produccion

- Verificar configuracion efectiva de entorno y exposicion de errores.
- Verificar backup/restauracion y base de datos objetivo.
- Revisar acceso a evidencias y permisos de almacenamiento.
- Ejecutar suite relevante y completa sin fallos.
- Confirmar integridad transaccional en estados/eventos.

### P1 - Despues del cierre

- Reducir concentracion de `PanelController`, `MantenimientoRepository` y
  `GestionEstadoMantenimientoService` por etapas.
- Completar migracion de bitacora tecnica.
- Consolidar consultas de conteo/listado y reglas de periodo.
- Ampliar autorizacion granular si el negocio la requiere.
- Agregar pruebas de archivos, seguridad por registro y rendimiento.

### P2 - Mejora futura

- Modelos Eloquent selectivos y relaciones formales donde el esquema lo
  permita.
- Jobs para procesos medidos como costosos.
- Observabilidad centralizada y procedimientos operativos completos.
- Pruebas cross-browser, responsive automatizadas y carga.

## Documentacion consultada

- `AGENTS.md`
- `docs/architecture/`
- `docs/security/`
- `docs/database/`
- `docs/development/`
- `docs/testing/`
- `docs/legacy/`
- `docs/adr/`

## Validaciones realizadas

- Auditoria estatica de estructura, clases, rutas, configuracion versionada,
  migraciones, servicios, frontend y pruebas.
- Inventario de clases grandes y ausencia comprobada de Jobs/Policies dedicados
  activos en las carpetas revisadas.
- Revision del estado del repositorio antes de documentar; se conservaron los
  cambios pendientes existentes.
- Se intento listar rutas; la version instalada de Artisan no soporta la
  opcion `--columns`, por lo que no se uso esa opcion para concluir hallazgos.
- No se abrieron `.env`, `.env.testing` ni copias sensibles.
- No se modifico codigo productivo, configuracion, BD, migraciones ni pruebas.
- No se ejecutaron `git add`, `git commit` ni `git push`.

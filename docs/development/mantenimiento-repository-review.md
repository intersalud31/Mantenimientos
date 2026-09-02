# Revision de `MantenimientoRepository`

## Alcance

Auditoria estatica y de solo lectura de
`app/Services/Legacy/MantenimientoRepository.php`. El archivo contiene 2327
lineas. Se contrasto con la arquitectura, compatibilidad legacy, reversiones,
calidad y deuda tecnica documentadas. No se modificaron codigo, BD,
migraciones ni pruebas.

## Estado actual comprobado

### Responsabilidad general

La clase actua como una fachada de acceso y operacion sobre el esquema legacy.
Consulta y escribe principalmente con `DB::table`, transforma filas a arrays,
aplica reglas de filtros/periodos y coordina algunos servicios auxiliares. Su
alcance actual incluye mantenimientos, usuarios relacionados, tecnicos,
resumenes, notificaciones, evidencias, estados, sincronizaciones y eventos.

### Metodos agrupados por funcionalidad

#### Tecnicos y consultas basicas

- `registrarTecnico`: inserta un tecnico activo y devuelve resultado funcional.
- `listarTecnicosActivos`: lista tecnicos activos ordenados por nombre.
- `obtenerNombreTecnicoPorId`: resuelve un tecnico activo.
- `obtenerMantenimientoPorId`: obtiene datos basicos de un mantenimiento.
- `obtenerEvidenciaPorMantenimientoId`: obtiene la ruta y datos necesarios de
  una evidencia.

#### Resumen, Dashboard y reportes

- `obtenerResumenSemestralActual`: calcula usuarios activos, base del periodo,
  no exigibles y conteos operativos semestrales.
- `consultaBaseFasePorPeriodo`, `contarBaseFasePorPeriodo` y
  `listarBaseFasePorPeriodo`: construyen y presentan la base de fase.
- `contarPendientesDashboardPorPeriodo` y
  `listarPendientesDashboardPorPeriodo`: consultan pendientes del Dashboard.
- `listarActividadReciente`: lista actividad reciente del periodo.
- `consultaNoExigiblesPorPeriodo`, `contarNoExigiblesPorPeriodo` y
  `listarNoExigiblesPorPeriodo`: consultan y filtran la clasificacion de no
  exigibles.
- `contarEquiposNuevosSinMantenimiento` y
  `listarEquiposNuevosSinMantenimiento`: consultan equipos nuevos sin
  mantenimiento.
- `resolverSituacionBaseFase`, `resolverMotivoNoExigible` y helpers SQL
  aplican etiquetas derivadas para la presentacion del reporte.

#### Programacion y pendientes

- `contarProximosPorPeriodo` y `listarProximosPorPeriodo`: consultan
  mantenimientos pendientes listos para programar.
- `contarProximosParaNotificacion`: cuenta proximos para notificaciones.
- `sincronizarPendientesProximosPorPeriodo`: crea, conserva y cancela
  pendientes semestrales dentro de una transaccion.
- `asegurarPendienteSemestral` y `materializarPendienteSemestral`: materializan
  el pendiente de un usuario y normalizan duplicados relacionados.
- `marcarComoProgramado`: implementacion legacy de programacion que agrega una
  marca textual en `observaciones`.
- `programarDesdeProximos`: flujo migrado que cambia el estado, conserva la
  observacion humana y registra evento tecnico.
- `calcularProximaSemestralDesde`, `rangoPeriodo`,
  `asegurarPendienteSemestral` y consultas auxiliares soportan periodicidad y
  limites del periodo.

#### Ejecucion

- `contarPorEstado` y `listarPorEstado`: consultas paginadas por estado,
  incluyendo datos de responsable, observacion y evidencia.
- `contarProgramadosHoy`: cuenta programaciones del dia.
- `marcarProgramadoComoHechoYGenerarSiguiente`: flujo anterior de ejecucion y
  materializacion del siguiente ciclo.
- `marcarProgramadoComoHechoDesdeProgramados`: flujo vigente; actualiza el
  mantenimiento, conserva la observacion humana, guarda evidencia y registra
  evento tecnico dentro de una transaccion.
- `construirObservacionRealizado`: conserva una ruta legacy que puede crear la
  marca textual de hecho; su uso debe interpretarse junto con el flujo que la
  invoca.

#### Estados y reversion

- `revertirAccionesPorCodigoActivo`: resuelve un usuario por codigo y procesa
  hasta diez acciones reversibles dentro de una transaccion.
- `revertirUnaAccionInterna`: ejecuta la transicion individual y registra el
  evento `revertido`.
- `buscarUltimoProgramadoReversible`: busca el ultimo programado elegible.
- `buscarUltimoRealizadoReversible`: busca el ultimo realizado elegible.
- `buscarPendienteAutomatico`: localiza el pendiente automatico asociado.
- `reversiblePorEvento`: prioriza el historial estructurado cuando existe.
- `esProgramadoReversible` y `esRealizadoReversible`: mantienen reglas de
  compatibilidad basadas en marcas antiguas de `observaciones`.
- `datosContextoReversion`, `origenReversibilidad` y capturadores de datos
  preparan snapshots y metadata para `mantenimiento_eventos`.

#### Notificaciones

- `contarNotificacionesVencidas`, `contarNotificacionesProximas` y
  `contarNotificacionesHoy`: exponen conteos operativos.
- `listarNotificacionesVencidas`, `listarNotificacionesProximas` y
  `listarNotificacionesHoy`: exponen listados limitados.
- `consultarNotificacionesVencidas`, `consultarNotificacionesProximas` y
  `consultarNotificacionesHoy`: implementan las consultas y sus reglas de
  fechas/estado.

#### Normalizacion, estados visuales y evidencias

- `normalizarPendientesDuplicados`: cancela duplicados dentro de una
  transaccion y conserva marcas legacy.
- `hidratarEvidenciaPublica`: comprueba el disco `public` y agrega URL de
  evidencia cuando el archivo existe.
- `hidratarEstadoVisual`: agrega etiquetas/clases derivadas para la UI.
- `normalizarRutaEvidencia`, `normalizarObservacionHumana` y
  `normalizarValorEvento`: normalizan valores de persistencia o salida.
- `aplicarBusquedaDashboardUsuarios`, `aplicarBusquedaNoExigibles` y
  `aplicarFiltroMotivoNoExigible`: aplican filtros de consulta.

## Dependencias externas

La clase inyecta:

- `UsuarioMantenimientoResumenService`, para resumenes derivados.
- `TextoLegacyNormalizer`, para normalizar observaciones y marcas antiguas.
- `SemestreService`, para periodos.
- `PeriodicidadMantenimientoService`, para dias habiles/periodicidad.
- `EstadoMantenimientoVigenteService`, para resolver estado vigente.
- `MantenimientoEventoRepository`, para eventos tecnicos.

Ademas usa `DB`, `Storage`, `Carbon`, `DateTimeImmutable` y `DateInterval`. La
persistencia depende de tablas legacy como `mantenimientos`, `usuarios`,
`tecnicos`, `areas` y `mantenimiento_eventos`, sin modelos Eloquent dedicados
para el agregado de mantenimiento.

## Responsabilidades que corresponden al Repository

### Estado actual

Corresponden al repository las consultas SQL/Query Builder, proyecciones,
filtros, ordenamientos, inserciones y actualizaciones necesarias para el
esquema legacy. Tambien es coherente que encapsule nombres de tablas, aliases,
compatibilidad con columnas opcionales y conversion de filas a estructuras
consumibles.

### Limite recomendado

El repository no deberia decidir el flujo HTTP, construir mensajes para
usuarios ni conocer vistas. Tampoco deberia mezclar en una misma clase todas
las consultas del Dashboard, notificaciones, tecnicos y transiciones si pueden
separarse sin duplicar SQL.

## Logica candidata a Services futuros

Estas son propuestas no implementadas:

- `ProgramarMantenimientoService`: validar el caso de uso, actualizar estado,
  asegurar pendiente/evento y coordinar la transaccion.
- `EjecutarMantenimientoService`: coordinar ejecucion, evidencia, siguiente
  pendiente y evento.
- `ReversionMantenimientoService`: decidir la accion reversible, preparar
  snapshots y coordinar rollback/evento.
- `MantenimientoPeriodoService`: agrupar reglas de semestre, pendientes,
  fechas y dias habiles.
- `MantenimientoNotificationService`: separar reglas de vencimiento, proximos
  y hoy de las consultas de bajo nivel.
- `MantenimientoClassificationService`: encapsular la clasificacion de base,
  no exigibles y equipos nuevos cuando exista una definicion cerrada del
  universo.

El repository podria ser invocado por estos servicios, manteniendo sus
contratos actuales durante una migracion progresiva.

## Problemas encontrados

### Alto - Clase con demasiados agregados y casos de uso

**Estado actual:** 2327 lineas y metodos para tecnicos, reportes,
notificaciones, programacion, ejecucion, reversion, evidencias y normalizacion.

**Riesgo:** alta superficie de regresion y dificultad para garantizar que un
cambio en un flujo no afecte otro.

**Mejora futura:** separar por caso de uso o agregado en extracciones pequenas,
con pruebas de caracterizacion y sin cambiar rutas ni contratos inicialmente.

### Alto - Reglas de negocio dentro de consultas y persistencia

**Estado actual:** metodos de consulta calculan situaciones, periodos,
exigibilidad, estados visuales y relaciones entre pendientes y ejecuciones.

**Riesgo:** la misma regla puede divergir entre Dashboard, listados,
notificaciones y acciones.

**Mejora futura:** mover decisiones de dominio a services, dejando en
repositories la obtencion de datos necesarios.

### Alto - Compatibilidad textual en `observaciones`

**Estado actual:** reversiones y parte de la normalizacion reconocen marcas
legacy en `observaciones`; otros flujos nuevos usan `mantenimiento_eventos`.

**Riesgo:** modificar textos, normalizadores o limites de columna puede romper
la identificacion historica. La bitacora continua repartida entre texto y
estructura.

**Mejora futura:** priorizar eventos estructurados y conservar fallback textual
hasta que exista cobertura historica suficiente.

### Medio - Escrituras automaticas legacy aun presentes

**Estado actual:** `sincronizarPendientesProximosPorPeriodo`,
`normalizarPendientesDuplicados`, `asegurarPendienteSemestral` y
`marcarComoProgramado` contienen concatenaciones o marcas automaticas en
`observaciones`.

**Riesgo:** pueden reintroducir el crecimiento que causo `SQLSTATE[22001]` y
mezclar comentario humano con bitacora.

**Mejora futura:** auditar cada caller y migrar esos flujos individualmente a
eventos, sin truncar ni borrar historicos.

### Medio - Transacciones y manejo de errores heterogeneos

**Estado actual:** algunos flujos usan `DB::transaction`, mientras varios
metodos capturan `Throwable` y devuelven arrays genericos. Hay diferencias
entre operaciones legacy y pilotos migrados.

**Riesgo:** una falla puede ser dificil de diagnosticar y los contratos de
error no son uniformes; una extraccion descuidada podria perder atomicidad.

**Mejora futura:** documentar contratos, mantener transaccion sobre cambio y
evento, y probar fallos de cada caso de uso antes de separar.

### Medio - Consultas duplicadas por conteo y listado

**Estado actual:** numerosos pares `contar...`/`listar...` reconstruyen filtros
similares, y existen variantes de notificaciones y estados.

**Riesgo:** un ajuste de filtros puede actualizar una consulta y olvidar la
otra, produciendo totales y listados inconsistentes.

**Mejora futura:** extraer constructores de consulta internos o repositorios
enfocados, con una fuente comun de condiciones.

### Bajo - Acoplamiento a presentacion

**Estado actual:** el repository agrega etiquetas, clases CSS, mensajes de
situacion y URLs publicas de evidencia a las filas.

**Riesgo:** cambios visuales requieren revisar persistencia; otros consumidores
pueden quedar acoplados a nombres de UI.

**Mejora futura:** devolver datos de dominio normalizados y dejar clases/labels
en presenters o view models, manteniendo compatibilidad temporal.

## Acoplamientos legacy relevantes

- Nombres y columnas de `usuarios`, `mantenimientos`, `tecnicos` y `areas`.
- Estados y aliases definidos por `MantenimientoEstados` y servicios de estado.
- Fechas semestrales y reglas de periodicidad.
- Registros incompletos o sin foreign keys confiables.
- Marcas historicas en `observaciones`.
- Rutas de evidencia almacenadas y servidas mediante disco `public`.
- Campos resumen derivados en `usuarios` que pueden requerir sincronizacion.
- Contratos de arrays consumidos por controllers, vistas y pruebas.

## Propuesta futura de separacion sin implementar

1. Congelar el comportamiento actual con pruebas para cada metodo publico usado.
2. Inventariar callers antes de mover o renombrar cualquier metodo.
3. Extraer primero reversiones, porque ya tienen eventos y una frontera
   transaccional identificable.
4. Extraer luego programacion y ejecucion, conservando eventos y observaciones
   humanas.
5. Separar consultas de notificaciones y Dashboard por lectura, sin duplicar
   reglas de periodo.
6. Dejar un repository legacy de compatibilidad mientras todos los callers
   migran gradualmente.
7. Eliminar metodos obsoletos solo con evidencia de que no tienen referencias,
   pruebas de regresion y autorizacion.
8. Registrar en ADR cualquier cambio de contrato, estados, bitacora o
   transaccion critica.

## Referencias y archivos revisados

- `AGENTS.md`
- `app/Services/Legacy/MantenimientoRepository.php`
- `app/Services/Legacy/MantenimientoEventoRepository.php`
- `app/Services/Legacy/UsuarioMantenimientoResumenService.php`
- `app/Services/Legacy/TextoLegacyNormalizer.php`
- `app/Services/Legacy/SemestreService.php`
- `app/Services/Legacy/PeriodicidadMantenimientoService.php`
- `app/Services/Legacy/EstadoMantenimientoVigenteService.php`
- `app/Http/Controllers/PanelController.php`
- `docs/architecture/overview.md`
- `docs/legacy/legacy-compatibility.md`
- `docs/legacy/reversions.md`
- `docs/development/code-quality.md`
- `docs/development/architecture-review.md`
- `docs/development/technical-debt.md`
- documentacion relacionada de `docs/security/`

## Validaciones realizadas

- Conteo de lineas y lectura focalizada del repository.
- Inventario de metodos, dependencias, consultas, transacciones, eventos,
  observaciones y almacenamiento.
- Contraste con documentacion arquitectonica y legacy existente.
- No se modificaron archivos productivos, BD, migraciones ni pruebas.
- No se ejecutaron `git add`, `git commit` ni `git push`.

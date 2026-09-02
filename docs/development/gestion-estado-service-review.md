# Revision de `GestionEstadoMantenimientoService`

## Alcance

Auditoria estatica y de solo lectura de
`app/Services/Legacy/GestionEstadoMantenimientoService.php`. El archivo tiene
1362 lineas. No se modificaron codigo, base de datos, migraciones ni pruebas.

## 1. Estado actual comprobado

### Responsabilidad actual

El servicio implementa el caso de uso de gestion manual del estado vigente de
un usuario/equipo. Resuelve el usuario por codigo activo, determina el estado
del periodo actual, construye una previsualizacion, valida que la solicitud no
haya cambiado y aplica una transicion permitida. Tambien sincroniza el resumen
del usuario y verifica el estado final.

No es exclusivamente un servicio de estado: tambien coordina materializacion
de pendientes, actualizacion de mantenimientos, tecnicos, observaciones,
evidencias y bitacora tecnica.

### Metodos agrupados por funcion

#### Analisis y previsualizacion

- `analizarUsuarioPorCodigoActivo`: resuelve usuario, periodo, estado vigente y
  las tres opciones de destino.
- `previsualizarPorCodigoActivo`: valida destino, obtiene estado actual y
  devuelve la evaluacion/previsualizacion sin persistir.
- `evaluarTransicion`: enruta cada combinacion de estado actual y destino.
- `construirOpcionBase`, `construirPreview` y helpers de resumen preparan el
  contrato visual y el hash de previsualizacion.
- `agregarAdvertenciaFechaEjecucionAnteriorAProgramacion` agrega metadata
  cuando las fechas requieren confirmacion.

#### Ejecucion y control de integridad

- `ejecutarPorCodigoActivo`: ejecuta la transicion dentro de `DB::transaction`.
  Recalcula usuario, estado y preview bajo la operacion, compara el hash con
  `hash_equals`, aplica el cambio, sincroniza resumen y verifica el estado final.
- `normalizarMotivo`, validadores internos y `fechaPerteneceAlPeriodo`
  comprueban datos de dominio.
- Si una excepcion ocurre, el metodo captura `Throwable` y devuelve
  `ok=false` con un mensaje.

#### Reglas de transicion permitidas

Las constantes de destino son `no_programado`, `programado` y `ejecutado`.
El dispatch actual contempla:

- no programado -> programado;
- no programado -> ejecutado;
- programado -> no programado;
- programado -> ejecutado;
- ejecutado -> programado;
- ejecutado -> no programado.

Cada evaluador valida disponibilidad, fechas, tecnico, pendiente asociado,
evidencia requerida y metadata antes de construir el preview.

#### Aplicacion de transiciones

- `aplicarTransicion` selecciona la aplicacion especifica.
- `aplicarNoProgramadoAProgramado` materializa/resuelve pendiente, actualiza
  fecha y registra bitacora.
- `aplicarNoProgramadoAEjecutado` materializa pendiente, aplica ejecucion,
  tecnico, fecha y evidencia, y registra bitacora.
- `aplicarProgramadoANoProgramado` devuelve el mantenimiento a pendiente y
  registra bitacora.
- `aplicarProgramadoAEjecutado` ejecuta el mantenimiento y registra bitacora.
- `aplicarEjecutadoAProgramado` restaura programacion, limpia ejecucion,
  conserva/gestiona el pendiente automatico y registra bitacora.
- `aplicarEjecutadoANoProgramado` reutiliza la transicion a programado y luego
  la lleva a no programado, registrando el cambio final.

#### Pendientes y datos auxiliares

- `resolverPendienteProgramable` busca o prepara el pendiente del periodo.
- `resolverOMaterializarPendienteDesdePreview` materializa el registro cuando
  el preview lo requiere.
- Los metodos de resumen, usuario, periodo, estado y fecha preparan metadata
  para la evaluacion y los eventos.

#### Presentacion y bitacora

- `anexarBitacoraGestion` registra el evento tecnico mediante el repository de
  eventos.
- Los builders de datos antes/despues y helpers de metadata construyen
  snapshots para la auditoria.
- El flujo vigente mantiene `observaciones` como texto humano; los mensajes de
  bitacora tecnica no deben depender de concatenaciones en esa columna.

### Dependencias

El servicio inyecta:

- `SemestreService`, para el periodo vigente.
- `EstadoMantenimientoVigenteService`, para resolver el estado operativo.
- `UsuarioMantenimientoResumenService`, para sincronizar resumenes derivados.
- `PeriodicidadMantenimientoService`, para fechas habiles y periodicidad.
- `TextoLegacyNormalizer`, para observaciones y compatibilidad textual.
- `MantenimientoRepository`, para operaciones legacy y datos de mantenimiento.

Usa `DB` directamente para la transaccion y algunas consultas/actualizaciones,
ademas de `Carbon`, `RuntimeException` y estructuras de preview en arrays.
Aunque `MantenimientoEventoRepository` existe en la arquitectura, la
dependencia directa observada en este servicio es principalmente a traves de
`MantenimientoRepository` para la persistencia de las transiciones y su
bitacora.

### Relacion con estados y reversiones

El servicio define las transiciones manuales de configuracion entre los tres
estados vigentes. La reversion legacy directa esta documentada como flujo de
`MantenimientoRepository` y no debe confundirse con este caso de uso. Ambos
usan estados comunes y pueden afectar pendientes, pero tienen contratos y
entradas distintas.

El servicio usa el estado vigente del periodo y no solo el ultimo registro
fisico. Por ello una extraccion debe conservar prioridades, fechas del periodo,
registros anteriores y aliases de estados legacy.

### Observaciones y eventos

En las transiciones de ejecucion se reciben observaciones y se normalizan como
comentario humano. La bitacora tecnica se agrega mediante el flujo de eventos,
con motivo, administrador, estado anterior/destino y datos de contexto. El
servicio tambien mantiene compatibilidad mediante `TextoLegacyNormalizer`, por
lo que no debe eliminarse la lectura de historicos sin auditoria separada.

### Transacciones

`ejecutarPorCodigoActivo` envuelve en `DB::transaction` la resolucion bajo
bloqueo cuando corresponde, aplicacion de transicion, materializacion o
eliminacion de pendientes, escritura de mantenimiento, evento, sincronizacion
del resumen y validacion final. Si una operacion lanza una excepcion, Laravel
revierte la transaccion y el metodo retorna un resultado de error.

La carga fisica del PDF ocurre en `PanelController` antes de invocar el
servicio; el controller elimina el archivo si el resultado es fallido. Por
tanto la atomicidad de base de datos y la compensacion del archivo estan en
capas distintas.

## 2. Problemas encontrados

### Alto - Servicio de 1362 lineas con multiples responsabilidades

**Estado actual:** combina seleccion de estado, preview, validacion de dominio,
persistencia, pendientes, resumen, eventos y metadata de presentacion.

**Riesgo:** alta superficie de regresion al cambiar una transicion; resulta
dificil verificar cada contrato de forma aislada.

**Mejora futura:** extraer casos de uso o estrategias por transicion de forma
incremental, conservando primero el contrato publico.

### Alto - Dependencia de arrays como contrato interno

**Estado actual:** preview, metadata, usuario, periodo, mantenimiento y
resultados se transportan como arrays con claves string.

**Riesgo:** claves faltantes o cambios de nombre pueden producir fallos tardios
o transiciones incorrectas sin ayuda del tipado.

**Mejora futura:** introducir DTOs/objetos de valor gradualmente, solo despues
de fijar las claves con pruebas de caracterizacion.

### Alto - Atomicidad de BD separada del almacenamiento fisico

**Estado actual:** la transaccion cubre las escrituras de BD/eventos, pero el
PDF se almacena desde el controller antes de ejecutar el servicio.

**Riesgo:** una excepcion inesperada o fallo fuera del resultado controlado
puede dejar un archivo sin registro o un registro sin archivo.

**Mejora futura:** definir un caso de uso de evidencia con compensacion robusta
e idempotente, sin cambiar el flujo hasta tener pruebas de fallos.

### Medio - Reglas de transicion distribuidas en metodos grandes

**Estado actual:** cada evaluador y aplicador contiene condiciones de fechas,
pendientes, estados y evidencia.

**Riesgo:** una nueva regla puede actualizar evaluacion pero no aplicacion, o
viceversa.

**Mejora futura:** modelar cada transicion como estrategia/handler con contrato
comun y pruebas de matriz estado-origen/destino.

### Medio - Captura amplia de `Throwable`

**Estado actual:** `ejecutarPorCodigoActivo` devuelve el mensaje de la
excepcion en `ok=false`.

**Riesgo:** el contrato es simple para el controller, pero puede exponer
detalles internos si una excepcion inesperada contiene informacion tecnica.

**Mejora futura:** separar mensajes seguros de detalles tecnicos registrados,
sin perder el rollback.

### Medio - Acoplamiento a estado vigente y periodo actual

**Estado actual:** el servicio reconstruye el estado usando el periodo actual,
prioridades y datos historicos.

**Riesgo:** pruebas o ejecuciones fuera del periodo esperado pueden producir un
destino distinto al que el usuario veia en la pantalla.

**Mejora futura:** recibir un contexto de periodo explicito y verificar
consistencia, manteniendo compatibilidad con el endpoint actual.

### Medio - Compatibilidad textual legacy

**Estado actual:** el normalizador y repositorios relacionados aun soportan
marcas historicas en `observaciones`, mientras eventos estructurados cubren
flujos nuevos.

**Riesgo:** retirar textos o cambiar su formato puede hacer que registros
historicos dejen de ser identificables.

**Mejora futura:** mantener fallback de lectura y migrar consumidores de forma
progresiva a `mantenimiento_eventos`.

## 3. Mejoras futuras

1. Crear una matriz de caracterizacion para las seis transiciones y sus
   condiciones de bloqueo.
2. Separar evaluacion pura de aplicacion persistente, sin duplicar consultas.
3. Extraer servicios pequenos para pendientes, evidencia y auditoria tecnica.
4. Mantener `GestionEstadoMantenimientoService` como fachada compatible
   mientras los callers se migran.
5. Definir DTOs para preview, transicion, metadata y resultado.
6. Garantizar que cada cambio de estado y evento se confirme atomically dentro
   de la misma transaccion.
7. Mantener comentarios humanos intactos y evitar nuevas marcas automaticas en
   `observaciones`.
8. Documentar cualquier cambio de estados, bitacora o transaccion mediante ADR.

## Archivos revisados

- `AGENTS.md`
- `app/Services/Legacy/GestionEstadoMantenimientoService.php`
- `app/Services/Legacy/MantenimientoRepository.php`
- `app/Services/Legacy/MantenimientoEventoRepository.php`
- `app/Services/Legacy/EstadoMantenimientoVigenteService.php`
- `app/Services/Legacy/UsuarioMantenimientoResumenService.php`
- `app/Services/Legacy/PeriodicidadMantenimientoService.php`
- `app/Services/Legacy/TextoLegacyNormalizer.php`
- `app/Http/Controllers/PanelController.php`
- `docs/architecture/overview.md`
- `docs/legacy/reversions.md`
- `docs/development/mantenimiento-repository-review.md`
- documentacion relacionada de `docs/security/`

## Validaciones realizadas

- Conteo de lineas y lectura focalizada del servicio.
- Inventario de metodos, transiciones, dependencias, transacciones,
  observaciones y eventos.
- Contraste con repository, servicio de estado, controller y documentacion
  existente.
- No se modificaron archivos productivos, BD, migraciones ni pruebas.
- No se ejecutaron `git add`, `git commit` ni `git push`.

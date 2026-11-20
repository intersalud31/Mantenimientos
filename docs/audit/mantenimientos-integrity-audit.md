# Auditoria integral del modulo de mantenimientos

## Resumen ejecutivo

Auditoria estatica y de solo lectura realizada el 2026-09-10 sobre el modulo y la base local activa. Se revisaron ciclo de vida, datos, fechas, evidencias, vencimientos, arquitectura, pruebas y riesgos de crecimiento. No se modificaron registros, codigo productivo, migraciones, pruebas ni evidencias.

La operacion principal es funcional y las transiciones migradas usan transacciones y eventos. Los riesgos prioritarios son: sincronizacion que escribe al abrir Proximos, reglas de fecha distintas entre flujos, evidencias legacy faltantes y trazabilidad historica incompleta.

## Alcance

- `PanelController`, `MantenimientoRepository`, `GestionEstadoMantenimientoService`, `EstadoMantenimientoVigenteService`, `NotificationSummaryService` y servicios de periodo/eventos.
- Vistas y JavaScript de Proximos, Programados, Hechos y notificaciones.
- Tablas locales: `usuarios`, `mantenimientos`, `tecnicos`, `mantenimiento_eventos`, `areas` y `configuracion_sistema`.
- Pruebas Feature/Unit, migraciones y documentacion existente.

La tabla `empresas` no existe en la base local auditada; por tanto no fue posible analizarla.

## Arquitectura actual encontrada

`PanelController` coordina vistas, validaciones HTTP, carga de PDFs y acciones de Proximos, Programados, Hechos, Configuracion y Reversiones. `MantenimientoRepository` concentra consultas y escrituras legacy de pendientes, programacion, ejecucion, reversiones, reportes, vencimientos, notificaciones, tecnicos y evidencias. `GestionEstadoMantenimientoService` implementa otra ruta de estados con previsualizacion, hash, validacion y transaccion.

`mantenimiento_eventos` registra los flujos migrados. Los registros historicos y varios metodos legacy aun dependen de marcas textuales en `observaciones`.

## Flujo del mantenimiento

Estados fisicos observados: `pendiente`, `programado`, `realizado` y `cancelado`. El catalogo tambien soporta aliases legacy `reprogramado` y `hecho`; no se encontraron filas actuales con esos aliases.

```text
Usuario activo con proxima fecha
  -> sincronizacion de Proximos -> pendiente

pendiente -> Programar desde Proximos -> programado
programado -> Marcar como hecho -> realizado + tecnico + fecha + PDF + evento
realizado -> Reversion -> programado, con ajuste de pendiente automatico
programado -> Reversion -> pendiente
pendiente duplicado o cubierto por programacion -> sincronizacion -> cancelado
```

Configuracion usa los estados operativos `no_programado`, `programado` y `ejecutado`, traducidos a las filas fisicas anteriores. Sus transiciones se validan con el periodo y un hash de previsualizacion.

## Auditoria de datos

| Medicion | Resultado | Interpretacion |
|---|---:|---|
| Mantenimientos | 165 | Universo auditado. |
| Usuarios | 63 | Sin nombres nulos o vacios. |
| Tecnicos | 3 | El responsable se persiste como texto. |
| Eventos | 27 | Bitacora progresiva. |
| Mantenimientos sin usuario existente | 0 | Sin huerfanos referenciales detectados. |
| Estados fuera del catalogo | 0 | Sin valores desconocidos detectados. |
| Codigos activos duplicados | 0 | Sin duplicados no vacios detectados. |
| Eventos sin mantenimiento | 0 | Sin eventos huerfanos detectados. |

`mantenimientos` no tiene `tecnico_id`; usa `tecnico_responsable`. Hay 44 realizados sin responsable. Esto es una brecha de calidad y hace imposible validar tecnico inexistente como FK, pero no demuestra una relacion rota.

Hay 35 usuarios con dos o mas pendientes en fechas distintas. No son duplicados confirmados: no existen duplicados de `usuario_id + pendiente_semestre_key` ni pendientes sin esa llave. Las fechas abarcan ciclos o semestres diferentes.

## Auditoria de estados y fechas

| Regla | Resultado |
|---|---:|
| Realizado sin `fecha_ejecucion` | 0 |
| Programado sin `fecha_programada` | 0 |
| Cancelado con ejecucion | 0 |
| Ejecucion futura | 0 |
| Programado vencido sin ejecucion | 1 |
| Ejecucion anterior a programacion | 12 |

Los 12 casos de ejecucion anterior a programacion son todos realizados. Corresponden a registros tardios y son compatibles con el flujo que acepta una ejecucion pasada; no deben corregirse como fechas imposibles sin validacion funcional. Existe una diferencia de politica: Configuracion exige confirmacion para esa relacion de fechas y Programados la permite directamente.

El unico programado vencido sin ejecucion tiene fecha 2026-08-03. Es consistente con una tarea pendiente de registrar; no hay evidencia de que se presente como realizado.

## Auditoria de evidencias

La ejecucion actual de `evidencias:auditar` encontro 19 referencias: 17 archivos existentes, 2 referencias faltantes y 4 archivos no referenciados. Las referencias faltantes corresponden a mantenimientos 65 y 73, con rutas legacy `publico/uploads/constancias/...`.

Los no referenciados incluyen `.gitignore` y tres PDFs. No se deben borrar ni reasociar sin conciliacion individual. El mantenimiento 73 permanece pendiente y conserva una referencia a un PDF faltante, lo que confirma una inconsistencia archivo-registro.

La carga nueva usa `Storage::disk('public')`, validacion de PDF/tamano y compensacion ante fallos. Los historicos mantienen rutas heterogeneas y no disponen de metadata estructurada de hash, version o reemplazo.

## Auditoria de vencimientos

La pantalla "Mantenimientos vencidos" se basa en `usuarios.proxima_fecha_mantenimiento`, usuario activo y fecha anterior a hoy; excluye cancelados con igual fecha y usa `EstadoMantenimientoVigenteService` para excluir ejecutados vigentes. No consulta simplemente todos los programados vencidos.

En la fecha auditada devolvio 6 vencidos, 2 proximos y 0 mantenimientos para hoy. Los vencidos mostraron estado vigente `no_programado`; no se encontro un realizado dentro de esa lista.

Hay dos fuentes relacionadas: el resumen en `usuarios` y las filas de `mantenimientos`. Por ello un programado historico vencido no equivale automaticamente a un usuario vencido en la pantalla. Este contrato debe conservarse y probarse ante cambios de resumen o de programacion atrasada.

## Auditoria de codigo y escalabilidad

- `listarProximosPorPeriodo` llama `sincronizarPendientesProximosPorPeriodo`; abrir Proximos puede insertar, actualizar o cancelar pendientes dentro de una transaccion.
- Proximos permite fechas pasadas habiles. `GestionEstadoMantenimientoService::validarFechaProgramada` aun rechaza fechas anteriores a hoy y fuera del periodo visual.
- `NotificationSummaryService::resumen` retorna inmediatamente `obtenerResumen`; su bloque posterior es inalcanzable.
- Metodos legacy aun concatenan marcas tecnicas en `observaciones`.
- La sincronizacion itera usuarios y emite varias consultas/escrituras por usuario. Notificaciones construye listas completas y luego las recorta en memoria. No se midio una falla con el volumen actual, pero son puntos de crecimiento.

## Auditoria de pruebas

Las pruebas existentes cubren autenticacion, autorizacion, programacion, ejecucion, reversiones, PDFs, transacciones, estados vigentes, periodicidad, Dashboard, notificaciones y comandos de diagnostico.

La ultima suite observada tuvo 177 pruebas exitosas y un fallo preexistente en `PanelActionFlowTest::test_reversion_prioriza_codigo_activo_cuando_esta_disponible`. No se atribuye a esta auditoria.

Faltan pruebas de navegador del calendario, una matriz que compare Proximos y Configuracion bajo una misma politica de programacion atrasada y pruebas que demuestren que consultar Proximos no causa cambios inesperados.

## Riesgos encontrados

| Prioridad | Problema | Impacto | Evidencia | Recomendacion |
|---|---|---|---|---|
| P0 | Evidencias sin archivo | Dos constancias historicas no son accesibles. | Mantenimientos 65 y 73; 2 de 19 referencias faltantes. | Conciliar con respaldos o fuente documental antes de cambiar rutas. |
| P1 | Lectura de Proximos escribe datos | Una pantalla puede modificar pendientes y observaciones. | `listarProximosPorPeriodo` invoca sincronizacion. | Separar sincronizacion de lectura, con pruebas de caracterizacion. |
| P1 | Regla de fecha distinta por modulo | La misma programacion acepta o rechaza fechas segun la ruta. | Proximos acepta pasado; Configuracion lo rechaza. | Definir una regla unica y probar ambas rutas antes de unificar. |
| P1 | Responsable sin integridad referencial | No se valida tecnico inexistente y hay 44 realizados sin responsable. | Campo texto `tecnico_responsable`. | Definir requisito historico antes de migrar datos o agregar relaciones. |
| P1 | Eventos historicos incompletos | Trazabilidad parcial de realizados antiguos. | 53 realizados no tienen evento; eventos huerfanos: 0. | Mantener fallback legacy y definir necesidad de migracion historica. |
| P2 | Politica distinta para ejecucion anterior | Diferentes controles segun flujo. | 12 casos; Configuracion confirma, Programados no. | Acordar politica y cubrirla con pruebas comunes. |
| P2 | Marcas tecnicas en observaciones legacy | Riesgo de crecimiento y dependencia textual. | Metodos legacy y normalizacion. | Migrar por flujo a eventos, sin borrar historicos. |
| P2 | Consultas iterativas y postprocesamiento PHP | Riesgo de degradacion con crecimiento. | Sincronizacion por usuario y listas en memoria. | Medir consultas, indices reales y volumen antes de optimizar. |
| P3 | Codigo inalcanzable | Dificulta mantenimiento sin fallo demostrado. | Retorno inmediato en `NotificationSummaryService::resumen`. | Eliminar o recuperar solo con prueba de contrato. |

## Plan recomendado de correccion

### Correcciones rapidas

1. Investigar los dos PDFs faltantes sin limpieza automatica.
2. Resolver el fallo preexistente de reversion en una tarea aislada.
3. Acordar la regla de programacion atrasada entre Proximos y Configuracion.

### Correcciones estructurales

1. Convertir la sincronizacion de pendientes en una accion explicita, idempotente y auditada, separada de las lecturas.
2. Definir el contrato historico de tecnico responsable y eventos antes de introducir claves o migraciones.
3. Completar la separacion entre comentarios humanos y eventos tecnicos en metodos legacy restantes.

### Mejoras futuras

1. Medir consultas e indices reales con datos representativos.
2. Separar casos de uso de programacion, ejecucion, reversion, vencimientos y notificaciones de `MantenimientoRepository`.
3. Agregar pruebas browser, de carga y de coherencia entre resumen, estados vigentes y notificaciones.

## Archivos a revisar en una segunda fase

- `app/Services/Legacy/MantenimientoRepository.php`
- `app/Services/Legacy/GestionEstadoMantenimientoService.php`
- `app/Services/Legacy/NotificationSummaryService.php`
- `app/Http/Controllers/PanelController.php`
- `tests/Feature/PanelActionFlowTest.php`
- `tests/Feature/GestionEstadoMantenimientoServiceTest.php`
- `tests/Feature/MantenimientoRepositoryMaterializacionPendienteTest.php`

## Validacion y limites

- Solo se ejecutaron consultas de lectura y el comando de auditoria de evidencias.
- No hubo `UPDATE`, `DELETE`, `INSERT`, migraciones, comandos de reparacion ni cambios de datos.
- No se modificaron codigo productivo, pruebas, evidencias ni respaldos.
- Los cambios pendientes previos del arbol fueron preservados.
- No se ejecutaron `git add`, `git commit` ni `git push`.

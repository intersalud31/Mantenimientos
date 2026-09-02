# Plan de estabilizacion del producto

## 1. Objetivo del plan

Llevar Mantenimientos-Laravel a una version funcional, verificable y segura
antes de realizar mejoras arquitectonicas mayores. Este plan prioriza la
operacion, la integridad de datos, la seguridad basica y la confianza de las
pruebas. No pretende reescribir el nucleo legacy ni resolver toda la deuda
tecnica en una sola fase.

## 2. Estado actual del producto

### Estable

- Autenticacion basada en `usuarios_sistema`, `password_hash`, estado, rol y
  sesion legacy documentada.
- Rutas del panel protegidas por `legacy.auth` y `can:access-panel`.
- Flujos principales de programacion, ejecucion, estados y reversion cubiertos
  por pruebas funcionales relevantes.
- Bitacora `mantenimiento_eventos` implementada progresivamente para flujos
  nuevos y reversiones.
- Documentacion tecnica de arquitectura, seguridad, base de datos, legacy,
  desarrollo y pruebas disponible.

### Requiere atencion

- Ciclo de vida y autorizacion de evidencias PDF.
- Configuracion efectiva de `APP_DEBUG`, niveles, retencion y permisos de logs.
- Diferencias entre esquema legacy real y migraciones versionadas.
- Coexistencia historica entre marcas en `observaciones` y eventos.
- Clases grandes, consultas repetidas y reglas distribuidas.
- Pruebas de carga, navegadores, responsive y entorno real.
- Procedimientos operativos de backup, restauracion y despliegue.

### Bloquea cierre hasta verificar o aceptar formalmente

- Backup y restauracion comprobados en un entorno aislado.
- Configuracion de errores y logs validada para el entorno objetivo.
- Integridad real de tablas, indices, engines y foreign keys legacy.
- Acceso seguro a evidencias y manejo de archivos huerfanos.
- Suite relevante y completa ejecutada sin fallos provocados por el cierre.

Estos puntos son criterios de cierre pendientes de verificacion; la auditoria
no afirma que todos esten incumplidos en produccion.

## 3. Prioridades de estabilizacion

### P0 - Bloqueadores antes de version estable

- Verificar backup y restauracion sin tocar la base productiva.
- Confirmar `APP_DEBUG=false`, logging apropiado, rotacion, retencion y acceso.
- Auditar esquema legacy real antes de cualquier cambio estructural.
- Validar que cambios de estado y eventos sean atomicos.
- Validar programacion, ejecucion, reversion, evidencias, autenticacion y
  autorizacion con la suite relevante.
- Revisar descarga de evidencias, relacion archivo-mantenimiento y limpieza en
  errores.
- Confirmar que los flujos migrados no agreguen nuevas marcas tecnicas a
  `observaciones` y que los historicos sigan siendo legibles.

### P1 - Mejoras necesarias despues de estabilizar

- Reducir gradualmente responsabilidades de `PanelController`,
  `MantenimientoRepository` y `GestionEstadoMantenimientoService`.
- Consolidar consultas repetidas de conteo/listado y reglas de periodo.
- Ampliar pruebas de archivos, seguridad por registro, contratos y errores.
- Completar la migracion progresiva de bitacora tecnica.
- Documentar despliegue, rollback, monitoreo y respuesta operativa.

### P2 - Evolucion futura

- Modelos Eloquent selectivos para tablas estables.
- Relaciones formales cuando el esquema real lo permita.
- Permisos mas granulares si el negocio los define.
- Profiling, optimizacion e indices basados en mediciones.
- Jobs idempotentes para tareas que demuestren necesidad de procesamiento
  diferido.
- Observabilidad centralizada y pruebas cross-browser/carga.

## 4. Matriz de trabajo

| Prioridad | Problema | Impacto | Accion recomendada | Dependencias | Estado |
|---|---|---|---|---|---|
| P0 | Backup/restauracion no comprobados en auditoria | Perdida de informacion y recuperacion incierta | Probar restauracion en entorno aislado | Acceso operativo y respaldo seguro | Pendiente |
| P0 | `APP_DEBUG` y logging efectivos no verificados | Exposicion de errores o datos sensibles | Validar configuracion del entorno objetivo sin exponer secretos | Operaciones y despliegue | Pendiente |
| P0 | Esquema legacy parcialmente versionado | Fallos de integridad o despliegue no reproducible | Comparar esquema real con migraciones | Base aislada y snapshot autorizado | Pendiente |
| P0 | Evidencias en disco publico y ciclo de vida incompleto | Exposicion, huerfanos o registros sin archivo | Verificar autorizacion, retencion y reconciliacion | Politica de archivos y pruebas | Pendiente |
| P0 | Estados, eventos y resumen deben validarse como unidad | Datos inconsistentes o perdida de trazabilidad | Ejecutar pruebas de transaccion y rollback | Base de prueba y suite Feature | Pendiente |
| P0 | Flujos criticos requieren regresion completa | Interrupcion operativa | Ejecutar suite relevante y completa | Entorno de pruebas estable | Pendiente |
| P1 | Clases principales sobredimensionadas | Regresiones y mantenimiento costoso | Extraer por caso de uso gradualmente | Pruebas de caracterizacion | No iniciado |
| P1 | Consultas y reglas de periodo repetidas | Totales inconsistentes y menor rendimiento | Consolidar sin duplicar SQL | Medicion y contratos actuales | No iniciado |
| P1 | Cobertura no funcional limitada | Fallos en movil, carga o navegador | Ampliar pruebas priorizadas | Herramientas de validacion | No iniciado |
| P1 | Procedimientos operativos incompletos | Respuesta lenta ante incidentes | Documentar despliegue, backup y recuperacion | Responsables operativos | No iniciado |
| P2 | Modelo operativo no formalizado | Evolucion de esquema mas dificil | Evaluar modelos selectivos | Auditoria de integridad | No iniciado |
| P2 | Permisos granulares no definidos | Acceso excesivo potencial | Diseñar matriz por accion si el negocio lo requiere | Requisitos aprobados | No iniciado |
| P2 | Escalabilidad no medida | Degradacion con volumen | Medir antes de introducir Jobs o indices | Datos representativos | No iniciado |

## 5. Orden recomendado de ejecucion

1. Congelar el alcance funcional y registrar el estado del arbol y del entorno.
2. Crear o verificar respaldo y restauracion en una base aislada.
3. Verificar configuracion de errores, logs, almacenamiento y permisos sin
   exponer secretos.
4. Auditar esquema, indices, engines, foreign keys y datos huerfanos.
5. Ejecutar pruebas de autenticacion, autorizacion, estados, programacion,
   ejecucion, reversion, eventos y evidencias.
6. Corregir solo riesgos P0 confirmados y repetir las pruebas afectadas.
7. Declarar version estable cuando se cumplan los criterios de cierre o exista
   aceptacion formal de cada riesgo residual.
8. Atacar deuda P1 mediante cambios pequenos y medidos.
9. Planificar evolucion P2 solo despues de estabilizar el producto.

El principio rector es: primero riesgos operativos e integridad; despues
calidad y mantenibilidad; finalmente evolucion arquitectonica.

## 6. Criterios para declarar version estable

- Login correcto, credenciales invalidas y usuarios inactivos verificados.
- Rutas y autorizacion del panel verificadas.
- Programacion, ejecucion y reversion funcionan sin cambios parciales.
- Eventos tecnicos se registran junto con los cambios que auditan.
- Observaciones humanas no se pierden ni reciben nuevas marcas indebidas.
- Evidencias PDF validan tipo, acceso, relacion y comportamiento ante errores.
- Suite Feature/Unit relevante y suite completa pasan sin fallos provocados.
- No existen errores Blade, rutas rotas ni migraciones no autorizadas.
- Esquema objetivo, indices y constraints son conocidos y compatibles.
- `APP_DEBUG` y logging estan validados para el entorno de despliegue.
- Backup y restauracion fueron probados en entorno aislado.
- Procedimientos de despliegue, rollback y respuesta basica estan documentados.
- Riesgos residuales tienen responsable, impacto y aceptacion formal.

## 7. Reglas para futuras modificaciones

- Hacer cambios pequenos, localizados y reversibles.
- Auditar callers, rutas, servicios, repositorios, vistas y pruebas antes de
  editar.
- Ejecutar primero la prueba mas cercana y luego la suite que corresponda.
- No cambiar autenticacion, middleware, estados, bitacora o esquema sin revisar
  compatibilidad legacy y transacciones.
- No hacer refactors masivos durante el cierre del producto.
- No truncar, borrar ni migrar historicos sin auditoria, respaldo y autorizacion.
- Mantener comentarios humanos en `observaciones` y trazabilidad tecnica en
  `mantenimiento_eventos`.
- Usar `Storage::disk('public')` respetando el ciclo de vida documentado de
  evidencias.
- Actualizar documentacion y crear ADR cuando cambie una decision critica.
- No exponer secretos, credenciales, datos reales ni trazas al usuario.

## 8. Exclusiones de esta fase

- Reescritura completa del sistema.
- Migracion total a Eloquent.
- Reemplazo completo del esquema legacy.
- Rediseño total de permisos.
- Refactor masivo de controllers, services o repositories.
- Creacion indiscriminada de Jobs, eventos o nuevas tablas.
- Nuevas categorias de negocio, estados o cambios no solicitados.
- Rediseño visual completo del panel.

## Documentos utilizados

- `docs/development/product-readiness-audit.md`
- `docs/development/code-quality.md`
- `docs/development/architecture-review.md`
- `docs/development/technical-debt.md`
- `docs/development/panel-controller-review.md`
- `docs/development/mantenimiento-repository-review.md`
- `docs/development/gestion-estado-service-review.md`
- `docs/security/`
- `docs/database/`
- `docs/testing/`
- `docs/legacy/`
- `docs/architecture/`
- `docs/adr/`

## Validaciones realizadas

- Revision del estado actual del repositorio antes de crear el plan.
- Lectura de la auditoria de madurez, seguridad de evidencias/logs, esquema y
  estrategia de pruebas.
- No se modifico codigo productivo, configuracion, base de datos, migraciones
  ni pruebas.
- No se ejecutaron `git add`, `git commit` ni `git push`.

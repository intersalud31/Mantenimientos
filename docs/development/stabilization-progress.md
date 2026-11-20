# Tablero de estabilizacion del producto

## 1. Objetivo

Este documento es el control central del proceso de estabilizacion de
Mantenimientos-Laravel. Su finalidad es seguir riesgos operativos, validar el
cierre de una version funcional y mantener separadas las tareas de estabilidad
de las mejoras arquitectonicas futuras.

Los estados reflejan la evidencia disponible en la documentacion; no implican
que una tarea haya sido implementada si no existe validacion registrada.

## 2. Estado actual del sistema

### Confirmado

- Autenticacion sobre `usuarios_sistema`, con `password_hash`, roles, estado y
  sesion legacy.
- Proteccion del panel mediante `legacy.auth` y `can:access-panel`.
- Flujos de programacion, ejecucion, estados y reversion con pruebas
  funcionales relevantes.
- Bitacora `mantenimiento_eventos` implementada progresivamente.
- Documentacion de arquitectura, seguridad, base de datos, desarrollo,
  testing, legacy y ADRs.

### Pendiente de validar

- Backup y restauracion confiables en un entorno aislado.
- Configuracion efectiva de `APP_DEBUG`, logging, retencion y permisos.
- Esquema real legacy, indices, engines, constraints y foreign keys.
- Integridad completa entre evidencias PDF y mantenimientos.
- Ausencia de archivos huerfanos y registros sin archivo.
- Suite completa y validaciones no funcionales en entornos objetivo.
- Consistencia total entre observaciones humanas y eventos tecnicos.

### Mejora futura

- Separacion progresiva de clases grandes.
- Consolidacion de consultas y reglas distribuidas.
- Modelos Eloquent selectivos.
- Permisos granulares si los requisitos de negocio los definen.
- Medicion de rendimiento, Jobs idempotentes y observabilidad centralizada.

## 3. Plan de estabilizacion por fases

| Prioridad | Tema | Objetivo | Estado | Evidencia |
|---|---|---|---|---|
| P0 | Evidencias PDF | Garantizar integridad entre archivos almacenados y registros | Pendiente | `docs/security/file-lifecycle-audit.md` |
| P0 | Backup y restauracion | Confirmar recuperacion confiable sin afectar produccion | Pendiente | `docs/development/product-readiness-audit.md` |
| P0 | Configuracion de produccion | Validar errores, logs y exposicion de informacion | Pendiente | `docs/security/logging-error-handling-audit.md` |
| P0 | Integridad de mantenimientos | Validar estados, transiciones, eventos y consistencia | Pendiente | `docs/testing/testing-strategy.md`, `docs/legacy/reversions.md` |
| P0 | Regresion funcional | Confirmar flujos criticos y suite sin fallos provocados | Pendiente | `docs/testing/` |
| P1 | Clases grandes | Reducir riesgo de mantenimiento sin refactor masivo | No iniciado | `docs/development/panel-controller-review.md`, `mantenimiento-repository-review.md` |
| P1 | Deuda tecnica | Reducir dependencias textuales y reglas distribuidas | No iniciado | `docs/development/technical-debt.md` |
| P1 | Pruebas | Ampliar cobertura de archivos, errores y no funcional | No iniciado | `docs/testing/feature-coverage.md` |
| P1 | Compatibilidad legacy | Migrar accesos de forma progresiva sin romper historicos | No iniciado | `docs/legacy/legacy-compatibility.md` |
| P2 | Permisos granulares | Evaluar control por accion o registro si el negocio lo requiere | No iniciado | `docs/security/authorization.md` |
| P2 | Modelos | Evaluar modelos para dominios estables | No iniciado | `docs/development/models-data-access-review.md` |
| P2 | Escalabilidad | Medir y optimizar con evidencia de volumen | No iniciado | `docs/database/schema-migration-review.md` |
| P2 | Optimizacion | Mejorar consultas, indices o Jobs cuando las mediciones lo justifiquen | No iniciado | `docs/development/product-readiness-audit.md` |

## 4. Reglas de ejecucion

- Hacer cambios pequenos, localizados y reversibles.
- Trabajar una modificacion importante a la vez.
- Auditar rutas, callers, servicios, repositorios, vistas y pruebas antes de
  editar.
- Ejecutar pruebas antes y despues de cada cambio relevante.
- No refactorizar durante la estabilizacion si no resuelve un riesgo confirmado.
- Mantener compatibilidad con tablas, estados, historicos y middleware legacy.
- No modificar datos reales, migraciones o esquema sin autorizacion y respaldo.
- Mantener observaciones humanas separadas de la bitacora tecnica.
- Actualizar documentacion y ADRs cuando cambie una decision critica.

## 5. Criterios de cierre de estabilizacion

- [ ] Flujos criticos de autenticacion, panel, usuarios, programacion,
  ejecucion, reversion, reportes y configuracion validados.
- [ ] Pruebas relevantes y suite completa pasan sin fallos provocados.
- [ ] Riesgos P0 confirmados resueltos o aceptados formalmente.
- [ ] Esquema objetivo, indices y constraints conocidos.
- [ ] Backup y restauracion comprobados en entorno aislado.
- [ ] `APP_DEBUG`, logging, permisos y retencion validados para despliegue.
- [ ] Evidencias PDF verificadas y su ciclo de vida documentado.
- [ ] Integridad de estados, eventos, resumenes y observaciones validada.
- [ ] Documentacion y ADRs actualizados.
- [ ] Riesgos residuales tienen responsable, impacto y aceptacion.
- [ ] Sistema listo para evolucion sin mezclar el cierre con un refactor mayor.

## 6. Proximo paso recomendado

La primera tarea recomendada es una **auditoria y fortalecimiento del ciclo de
vida de evidencias PDF**. Debe comenzar con lectura y verificacion en un
entorno seguro, cubriendo autorizacion, relacion archivo-mantenimiento,
errores, huerfanos, registros sin archivo y respaldo antes de implementar
cambios.

## Referencias

- `docs/development/product-stabilization-plan.md`
- `docs/development/product-readiness-audit.md`
- `docs/security/`
- `docs/database/`
- `docs/testing/`
- `docs/legacy/`
- `docs/architecture/`
- `docs/adr/`

## Validaciones realizadas

- Revision de los documentos de madurez, estabilizacion, seguridad de archivos,
  ciclo de vida y estrategia de pruebas.
- Revision del estado del repositorio antes de crear el tablero.
- No se modifico codigo productivo, configuracion, base de datos, migraciones ni
  pruebas.
- No se ejecutaron `git add`, `git commit` ni `git push`.

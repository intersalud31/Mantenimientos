# Plan final de remediacion de evidencias PDF

## Alcance

Este documento define el siguiente paso seguro para las inconsistencias de evidencias detectadas en el entorno local. Es un plan: no aplica cambios a la base de datos, al almacenamiento ni a rutas historicas.

Fuentes revisadas: `evidence-audit-results.md`, `evidence-reconciliation-report.md`, `evidence-backup-review.md`, `evidence-recovery-investigation.md`, `evidence-hardening-plan.md`, `evidence-hardening-implementation.md`, `PanelController`, `MantenimientoRepository`, `AuditarEvidenciasCommand`, rutas, vistas y pruebas de evidencias.

## Estado actual comprobado

### Flujo vigente

1. `PanelController` valida los PDF con `mimetypes:application/pdf` y un limite de 5120 KB.
2. Los flujos de ejecucion guardan archivos mediante `Storage::disk('public')->storeAs()` bajo `constancias/{mantenimiento_id}` o `constancias/gestion/{codigo_activo}`.
3. El repository recibe la ruta relativa y persiste la referencia en `mantenimientos.evidencia_pdf` dentro de la operacion de mantenimiento.
4. El controlador conserva temporalmente la ruta creada y la elimina como compensacion si la operacion posterior falla, sin eliminar una ruta que ya existia antes de la solicitud.
5. La ruta protegida `panel.evidencia.pdf` consulta la referencia, comprueba su existencia en el disco `public` y responde o descarga el PDF. Si falta, redirige con error.
6. `MantenimientoRepository::hidratarEvidenciaPublica()` normaliza la ruta, comprueba su existencia y expone disponibilidad y URL para los listados.

No se encontro una eliminacion funcional ordinaria de evidencias historicas. La eliminacion comprobada es exclusivamente compensatoria para un archivo creado por una solicitud fallida.

### Herramienta de auditoria actual

`php artisan evidencias:auditar` es de solo lectura. Compara las referencias no vacias de `mantenimientos.evidencia_pdf` con los archivos de `Storage::disk('public')` y emite JSON con referencias existentes, faltantes y archivos sin referencia.

Limitaciones comprobadas:

- No distingue rutas legacy de rutas vigentes.
- Incluye artefactos no PDF, como `.gitignore`, entre los huerfanos brutos.
- No informa referencias duplicadas ni candidatos por nombre o ID.
- No calcula hash, MIME ni fechas de archivo.
- No modifica archivos ni datos.

## Casos bajo conciliacion

| Caso | Evidencia comprobada | Clasificacion | Decision actual |
| --- | --- | --- | --- |
| Mantenimiento 65 | Referencia `publico/uploads/constancias/ACTA DE MANTENIMIENTO GERENCIA.pdf`. No existe `public/uploads` ni `publico/uploads` en el proyecto, y no se encontro copia con ese nombre en `storage/app/public`, `storage/app/backups`, `db-rescue-20260731` ni respaldos integrales locales revisados. | No encontrado | Conservar la ruta; requiere fuente externa o respaldo no disponible localmente. |
| Mantenimiento 73 | Referencia `publico/uploads/constancias/pc viviana.pdf`. No existe la ubicacion legacy y no se encontro copia con ese nombre en las mismas ubicaciones. | No encontrado | Conservar la ruta; requiere fuente externa o respaldo no disponible localmente. |
| PDF candidato 175 | Existe `constancias/constancia_175_cd8bd59c-7dbb-4c01-b6cf-a27a6d371ab1.pdf` (2,083,358 bytes; 2026-07-30). El mantenimiento 175 ya referencia `constancias/175/GA-TI-F01 ACTA DE MANTIMIENTO.pdf` (230,492 bytes; 2026-08-28). | Posible | Conservar ambos archivos; no reasignar sin verificar contenido o fuente funcional. |
| PDF candidato 177 | Existe `constancias/constancia_177_dc4f65a5-3ca0-45d7-8a18-5599c553b0dc.pdf` (322,883 bytes; 2026-07-30). El mantenimiento 177 ya referencia `constancias/constancia_177_4f4bfc94-57ea-4d86-af8d-5fe4f3fbe118.pdf` (68,157 bytes; 2026-08-18). | Posible | Conservar ambos archivos; no reasignar sin verificar contenido o fuente funcional. |

Las copias de respaldo observadas de los candidatos 175 y 177 provienen del respaldo integral del almacenamiento. No prueban una asociacion funcional independiente. Ningun caso tiene evidencia suficiente para ser recuperado o reasignado automaticamente.

## Problemas encontrados

### Alto - Referencias legacy sin archivo local

**Evidencia:** los mantenimientos 65 y 73 apuntan a `publico/uploads`, una convencion inexistente en el proyecto actual. Las busquedas locales y en respaldos disponibles no hallaron los nombres exactos.

**Impacto:** las evidencias no pueden consultarse ni descargarse. Cambiar sus rutas sin disponer del PDF perderia la unica referencia historica disponible.

**Decision segura:** conservar los valores actuales hasta obtener el archivo desde una fuente externa verificable o una decision funcional documentada.

### Medio - Archivos fisicos sin relacion confirmada

**Evidencia:** dos nombres incluyen los IDs 175 y 177, pero esos mantenimientos ya tienen referencias distintas y existentes. El nombre, la fecha y el tamano no acreditan que el archivo sea la evidencia vigente.

**Impacto:** una reasignacion por heuristica podria asociar un documento incorrecto y ocultar una evidencia valida.

**Decision segura:** tratarlos como candidatos conservados, no como asociaciones confirmadas.

### Medio - Auditoria insuficiente para clasificar inconsistencias

**Evidencia:** el comando detecta ausencias y falta de referencia, pero no separa artefactos de almacenamiento, rutas legacy ni duplicados de referencia.

**Impacto:** los conteos requieren interpretacion manual y no priorizan consistentemente los casos que requieren revision funcional.

## Plan de correccion propuesto

### P0 - Antes de cualquier correccion de datos

1. Solicitar al responsable funcional o al origen documental los PDFs de 65 y 73. La coincidencia debe basarse al menos en mantenimiento, usuario, fecha o acta identificable, nunca solo en un nombre de archivo.
2. Solicitar autorizacion separada para inspeccionar en solo lectura los candidatos 175 y 177, o contrastarlos contra una fuente funcional.
3. Si se confirma un archivo, crear primero un respaldo del registro y del archivo involucrado, y preparar una operacion unica, revisable y transaccional para actualizar la referencia. Esta operacion requiere autorizacion posterior.
4. Si no aparece una fuente para 65 o 73, conservar la ruta historica y registrar una decision funcional de evidencia no recuperable; no vaciar la ruta para aparentar una solucion.

### P1 - Mejora acotada de la auditoria

| Archivo | Cambio minimo | Impacto | Riesgo | Pruebas necesarias |
| --- | --- | --- | --- | --- |
| `app/Console/Commands/AuditarEvidenciasCommand.php` | Clasificar referencias como vigente, legacy o fuera de convencion, sin reescribirlas. | Prioriza rutas incompatibles con el disco actual. | Bajo: cambia el informe. | Ruta `constancias`, ruta `publico/uploads`, barras invertidas. |
| `app/Console/Commands/AuditarEvidenciasCommand.php` | Separar PDFs candidatos de artefactos no PDF. | Evita contar `.gitignore` como evidencia huerfana. | Bajo. | PDF huerfano, archivo auxiliar, almacenamiento vacio. |
| `app/Console/Commands/AuditarEvidenciasCommand.php` | Informar referencias duplicadas normalizadas y candidatos por ID solo como `posibles`. | Visibiliza inconsistencias sin inferir asociaciones. | Bajo/medio: las heuristicas deben rotularse como no confirmadas. | Dos referencias a una ruta, nombre con ID, nombre sin ID. |
| `tests/Feature/AuditarEvidenciasCommandTest.php` | Extender la cobertura de clasificaciones y comprobar que no ejecuta escrituras. | Protege el caracter diagnostico. | Bajo. | Casos anteriores y BD/disco intactos. |

El hash, MIME detectado y fechas de archivo son mejoras futuras: pueden ayudar a investigar, pero no deben convertirse en un mecanismo de reasignacion automatica.

### P2 - Evolucion futura

- Estandarizar rutas nuevas sin reescribir rutas historicas no verificadas.
- Evaluar metadata estructurada, versionado y retencion solo con una decision de esquema y operacion respaldada por ADR.
- Integrar respaldo y restauracion conjunta de BD y `storage/app/public`, verificando referencias tras cada prueba de recuperacion.

## Riesgos y compatibilidad legacy

- Migrar una ruta legacy no es seguro si el PDF no esta disponible y confirmado.
- BD y filesystem no forman una transaccion atomica; la compensacion vigente protege fallos de la solicitud, no archivos perdidos historicamente.
- Normalizar rutas para leer o auditar no autoriza reescribirlas.
- Conservar los candidatos 175 y 177 evita una perdida irreversible mientras se toma una decision funcional.

## Pruebas requeridas antes de implementar P1

- Auditoria de referencia existente, faltante y legacy.
- PDF fisico sin referencia y archivo auxiliar no PDF reportados por separado.
- Dos mantenimientos con la misma ruta normalizada.
- Candidato cuyo nombre contiene un ID marcado como posible, no confirmado.
- Garantia de que el comando no crea, borra, mueve ni actualiza archivos o registros.
- Regresion de descarga para evidencia existente y referencia sin archivo.

## Acciones que requieren autorizacion posterior

- Abrir o inspeccionar contenido de los PDFs 175 y 177.
- Recuperar, copiar o restaurar un PDF desde una fuente externa.
- Actualizar `mantenimientos.evidencia_pdf`.
- Mover, renombrar, reasignar o eliminar evidencias.
- Implementar los cambios P1 del comando y sus pruebas.

## Validacion de esta fase

Esta fase fue diagnostica. No se modificaron codigo productivo, base de datos, migraciones, pruebas ni archivos de evidencia. No se eliminaron, movieron, renombraron ni reasignaron PDFs. No se ejecutaron `git add`, `git commit` ni `git push`.

# Informe de conciliacion de evidencias PDF

## Resumen ejecutivo

La revision se realizo el 2026-09-03 en el entorno local de
Mantenimientos-Laravel, usando consultas de solo lectura y metadata del
sistema de archivos. Se revisaron dos referencias faltantes y dos PDFs sin
referencia exacta.

Resultado de la revision controlada:

- Las referencias de los mantenimientos 65 y 73 no tienen archivo en la ruta auditada.
- No se encontraron copias con los nombres exactos de esas referencias en las
  ubicaciones locales conocidas del workspace.
- Los PDFs asociados por nombre a 175 y 177 no coinciden con las rutas actualmente almacenadas.
- No se pudo confirmar la pertenencia funcional de ninguno de los dos PDFs huerfanos sin abrirlos ni consultar respaldos externos.

## Caso mantenimiento 65

### Evidencia encontrada

- ID: 65.
- Usuario relacionado: ID 34, Maria Fernanda Arias.
- Codigo activo: 567.
- Area: GERENCIA.
- Estado: realizado.
- Fecha programada y ejecutada: 2026-06-30.
- Tecnico responsable: Giuliano.
- Ruta registrada: publico/uploads/constancias/ACTA DE MANTENIMIENTO GERENCIA.pdf.
- Archivo en el disco auditado: no encontrado.
- Eventos asociados en mantenimiento_eventos: ninguno.
- Observaciones: Hecho. y marca legacy Hecho desde modulo Programados.

### Hipotesis y certeza

La ruta parece pertenecer a una ubicacion o convencion legacy anterior a la
ruta actual constancias/, pero no es comprobable desde los datos revisados.
El archivo pudo haberse movido, eliminado o haber quedado en un respaldo.

**Clasificacion:** Requiere busqueda externa/respaldo.
**Nivel de certeza:** confirmado que falta en el disco auditado; causa no identificada.

La busqueda local por nombre exacto no encontró una copia en el workspace.
Existen archivos de respaldo en el proyecto, pero no fueron abiertos ni
inspeccionados por las restricciones de seguridad del proyecto. La existencia
de una copia en esos respaldos es no comprobable en esta fase.

## Caso mantenimiento 73

### Evidencia encontrada

- ID: 73.
- Usuario relacionado: ID 38, Sandra Milena Velasco.
- Codigo activo: 544.
- Area: OUTSOURCING.
- Estado: pendiente.
- Fecha programada: 2026-08-11.
- Fecha de ejecucion: nula.
- Tecnico responsable: Giuliano.
- Ruta registrada: publico/uploads/constancias/pc viviana.pdf.
- Archivo en el disco auditado: no encontrado.
- Eventos asociados en mantenimiento_eventos: ninguno.
- Observaciones: comentario humano hecho y marcas legacy de hecho y dos reversiones.

### Hipotesis y certeza

La ruta usa la misma ubicacion legacy observada en el caso 65 y el nombre no
coincide con el usuario ni con el codigo activo del registro. Esto puede
indicar una referencia histórica incorrecta, un archivo movido o una
asociacion antigua, pero no es comprobable solo con los datos actuales.

**Clasificacion:** Requiere busqueda externa/respaldo.
**Nivel de certeza:** confirmado que falta en el disco auditado; relacion original y causa no identificadas.

La busqueda local por nombre exacto no encontró una copia en el workspace.
La existencia de una copia en respaldos es no comprobable porque esos archivos
no fueron abiertos.

## PDF huerfano 175

### Informacion encontrada

- Ruta física: constancias/constancia_175_cd8bd59c-7dbb-4c01-b6cf-a27a6d371ab1.pdf.
- Tamano: 2,083,358 bytes.
- Creacion y ultima modificacion: 2026-07-30 08:34:48.
- Ultimo acceso observado: 2026-08-31 09:37:14.
- Nombre: contiene el ID de mantenimiento 175.
- Mantenimiento 175 actual: usuario Nelly Perez, codigo 670, CAAS, estado realizado, ejecutado el 2026-08-20.
- Ruta actualmente registrada en 175: constancias/175/GA-TI-F01 ACTA DE MANTENIMIENTO.pdf.
- Eventos asociados consultados: ninguno.

### Posible relacion y decision

El ID en el nombre y la fecha de creación sugieren una relación posible con
el mantenimiento 175. No es una relación confirmada porque la ruta registrada
es diferente y no se inspeccionó el contenido del PDF.

**Clasificacion:** Requiere decision funcional.
**Decision pendiente:** confirmar mediante revisión autorizada del contenido,
respaldo o responsable del mantenimiento si es una versión anterior o la evidencia correcta.

La relación por ID en el nombre se clasifica como **posible**, no confirmada.
La metadata confirma creación y modificación el 2026-07-30, pero no permite
determinar el contenido ni si reemplaza la evidencia actualmente registrada.

## PDF huerfano 177

### Informacion encontrada

- Ruta física: constancias/constancia_177_dc4f65a5-3ca0-45d7-8a18-5599c553b0dc.pdf.
- Tamano: 322,883 bytes.
- Creacion y ultima modificacion: 2026-07-30 11:14:16.
- Ultimo acceso observado: 2026-08-31 09:37:14.
- Nombre: contiene el ID de mantenimiento 177.
- Mantenimiento 177 actual: usuario Islena Romero, codigo 806, GESTION HUMANA, estado realizado, ejecutado el 2026-08-13.
- Ruta actualmente registrada en 177: constancias/constancia_177_4f4bfc94-57ea-4d86-af8d-5fe4f3fbe118.pdf.
- Eventos asociados consultados: ninguno.

### Posible relacion y decision

El ID en el nombre y la fecha de creación sugieren una relación posible con
el mantenimiento 177. No es una relación confirmada porque la ruta registrada
es diferente y no se inspeccionó el contenido del PDF.

**Clasificacion:** Requiere decision funcional.
**Decision pendiente:** confirmar mediante revisión autorizada del contenido,
respaldo o responsable del mantenimiento si es una versión anterior o la evidencia correcta.

La relación por ID en el nombre se clasifica como **posible**, no confirmada.
La metadata confirma creación y modificación el 2026-07-30, pero no permite
determinar el contenido ni si reemplaza la evidencia actualmente registrada.

## Recomendacion final

### P0 - Antes de produccion

- Buscar las dos referencias faltantes en respaldos o ubicaciones legacy conocidas antes de modificar registros.
- No reasignar los PDFs 175 y 177 solo por el nombre; obtener confirmación funcional o documental.
- Conservar copia de seguridad y registrar cualquier decisión de reasociación.
- Autorizar separadamente cualquier inspección de backups o del contenido de
  PDFs si se requiere confirmar pertenencia.

### P1 - Mejoras de la herramienta de auditoria

- Reportar por separado archivos no PDF como .gitignore.
- Añadir metadata de creación y modificación al reporte de archivos físicos.
- Generar candidatos de coincidencia por ID sin presentarlos como relaciones confirmadas.

### P2 - Evolución futura del ciclo de vida

- Definir versionado y reemplazo de evidencias.
- Asociar metadata técnica de archivo, como tamaño, hash y fecha de carga, sin reemplazar el vínculo funcional con el mantenimiento.
- Establecer un procedimiento periódico de conciliación con trazabilidad.

## Limites de la investigación

No se abrieron ni modificaron los PDFs. La pertenencia de los archivos
huerfanos, la existencia de copias en respaldos y el significado de las rutas
legacy son **no comprobables** con la información disponible en esta auditoria.

La revision local solo consultó nombres y metadata de archivos; no abrió ni
modificó los PDFs. No hubo cambios de código, base de datos, migraciones,
pruebas ni archivos de evidencia. No se ejecutaron deletes, updates, limpieza,
reasignaciones, git add, git commit ni git push.

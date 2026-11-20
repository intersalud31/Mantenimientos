# Investigacion de recuperacion de evidencias PDF

## Casos revisados

La revision fue de solo lectura. Se inspeccionaron nombres, rutas, tamaños y
fechas de modificacion; no se abrieron, movieron, copiaron, renombraron ni
reasignaron PDFs.

Ubicaciones revisadas:

- `storage/app/public` del proyecto.
- `db-rescue-20260731`.
- respaldos integrales disponibles bajo `C:\xampp\backups\Mantenimientos-Laravel`.
- documentacion previa de conciliacion y auditoria.

## Evidencias encontradas

### Mantenimiento 175

- Ruta buscada por nombre: `constancias/constancia_175_cd8bd59c-7dbb-4c01-b6cf-a27a6d371ab1.pdf`.
- Coincidencia encontrada en `storage/app/public/constancias/`.
- Tamaño: 2.083.358 bytes.
- Fecha de modificacion: 2026-07-30 08:34:48.
- El registro 175 mantiene actualmente otra ruta: `constancias/175/GA-TI-F01 ACTA DE MANTENIMIENTO.pdf`.
- Esa segunda evidencia tambien existe, con 230.492 bytes y fecha 2026-08-28 12:43:37.

Clasificacion: **posible**. El nombre contiene el ID, pero existen dos
archivos candidatos y no se inspecciono contenido ni se comparo hash con una
fuente funcional autorizada.

### Mantenimiento 177

- Ruta buscada por nombre: `constancias/constancia_177_dc4f65a5-3ca0-45d7-8a18-5599c553b0dc.pdf`.
- Coincidencia encontrada en `storage/app/public/constancias/`.
- Tamaño: 322.883 bytes.
- Fecha de modificacion: 2026-07-30 11:14:16.
- El registro 177 mantiene actualmente otra ruta: `constancias/constancia_177_4f4bfc94-57ea-4d86-af8d-5fe4f3fbe118.pdf`.
- La ruta registrada tambien existe, con 68.157 bytes y fecha 2026-08-18 15:55:54.

Clasificacion: **posible**. El nombre contiene el ID, pero la existencia de
otra evidencia vigente impide confirmar que sea una version anterior o el
archivo correcto sin revisión documental.

## Evidencias no encontradas

### Mantenimiento 65

- Archivo buscado: `publico/uploads/constancias/ACTA DE MANTENIMIENTO GERENCIA.pdf`.
- No encontrado en `storage/app/public`.
- No encontrado en `db-rescue-20260731`.
- No encontrado en los respaldos integrales revisados.

Clasificacion: **no determinado**. No se puede concluir que sea irrecuperable
porque podrían existir respaldos externos o ubicaciones legacy no disponibles.

### Mantenimiento 73

- Archivo buscado: `publico/uploads/constancias/pc viviana.pdf`.
- No encontrado en `storage/app/public`.
- No encontrado en `db-rescue-20260731`.
- No encontrado en los respaldos integrales revisados.

Clasificacion: **no determinado**. La recuperación no es comprobable con las
ubicaciones locales revisadas.

## Posibles recuperaciones

- 175: existe una coincidencia por nombre y otra evidencia actualmente
  registrada; requiere validación funcional antes de cualquier asociación.
- 177: existe una coincidencia por nombre y otra evidencia actualmente
  registrada; requiere validación funcional antes de cualquier asociación.
- 65 y 73: no hay copia local identificable en las ubicaciones revisadas.

No se encontró evidencia suficiente para clasificar ningún caso como
**confirmado** o **recuperable sin revisión**.

## Decisiones pendientes

1. Confirmar con el responsable funcional cuál archivo corresponde a 175 y
   177, idealmente comparando contenido en una revisión autorizada o una
   fuente documental confiable.
2. Buscar respaldos externos para 65 y 73, sin modificar las rutas actuales.
3. Definir si los archivos alternos son versiones históricas que deben
   conservarse o documentos no relacionados.
4. Autorizar por separado cualquier copia, restauracion, reasignacion o
   eliminación.

## Recomendación final

### Hechos comprobados

- 65 y 73 conservan rutas sin archivo local encontrado.
- 175 y 177 tienen archivos físicos con nombres que contienen sus IDs.
- 175 y 177 también tienen rutas actualmente registradas hacia otros PDFs.

### Hipótesis

Los archivos alternos de 175 y 177 podrían ser versiones anteriores,
duplicados o evidencias no relacionadas. El nombre y el tamaño no prueban la
pertenencia.

### Próximo paso seguro

Realizar una revisión funcional o documental de 175 y 177. Para 65 y 73,
consultar respaldos externos autorizados. Mientras tanto no modificar BD,
archivos ni referencias.

## Validacion

- Se revisaron únicamente metadatos y rutas.
- Se ejecutó `git diff --check`.
- No hubo cambios en BD, código, migraciones ni pruebas.
- No hubo movimiento, copia, eliminación, apertura ni reasignación de PDFs.
- No se ejecutó `git add`, commit ni push.

# Revision controlada de respaldos de evidencias

## Alcance y restricciones

La revision se realizo el 2026-09-03 en el workspace de Mantenimientos-Laravel.
Se consultaron ubicaciones, extensiones, tamaños y fechas de archivos. No se
abrieron dumps SQL ni copias de respaldo, no se restauro información y no se
inspecciono el contenido binario de ningún PDF.

## Respaldos encontrados

### storage/app/backups

Se encontraron cuatro archivos con extension .sql, con fechas del 30/07/2026
y tamaños aproximados entre 58 KB y 66 KB. Por nombre aparentan ser respaldos
de la base de datos de mantenimientos.

### db-rescue-20260731

Se encontraron archivos .sql de rescate y estructura, con fecha del
31/07/2026. Incluyen mantenimientos.sql, mantenimientos_bd4_auditoria.sql,
mantenimientos_limpia_estudio.sql y otros respaldos de bases diferentes.

### Alcance

Todos los respaldos identificados son dumps SQL. No se encontró ningún archivo
con extension PDF dentro de las ubicaciones de respaldo revisadas por nombre
y extensión. No es comprobable si un dump SQL contiene una copia textual de
una ruta o registro histórico sin abrirlo, acción que queda fuera de esta
revisión por las restricciones de seguridad.

## Mantenimiento 65

- **Estado:** no encontrado en archivos locales identificables.
- **Referencia:** publico/uploads/constancias/ACTA DE MANTENIMIENTO GERENCIA.pdf.
- **Búsqueda:** no existe una copia PDF con ese nombre en el workspace.
- **Respaldos:** hay dumps SQL disponibles, pero no fueron abiertos ni consultados.
- **Certeza:** confirmado que no hay copia PDF identificable en las ubicaciones
  locales revisadas; la existencia dentro de un dump o respaldo externo es no
  comprobable.
- **Clasificación:** pendiente de búsqueda autorizada en respaldo.

## Mantenimiento 73

- **Estado:** no encontrado en archivos locales identificables.
- **Referencia:** publico/uploads/constancias/pc viviana.pdf.
- **Búsqueda:** no existe una copia PDF con ese nombre en el workspace.
- **Respaldos:** hay dumps SQL disponibles, pero no fueron abiertos ni consultados.
- **Certeza:** confirmado que no hay copia PDF identificable en las ubicaciones
  locales revisadas; la existencia dentro de un dump o respaldo externo es no
  comprobable.
- **Clasificación:** pendiente de búsqueda autorizada en respaldo.

## Mantenimiento 175

- **Posible coincidencia:** existe el archivo
  storage/app/public/constancias/constancia_175_cd8bd59c-7dbb-4c01-b6cf-a27a6d371ab1.pdf.
- **Metadata:** 2,083,358 bytes; creación y modificación
  30/07/2026 08:34:48.
- **Comparación:** el mantenimiento 175 registra actualmente
  constancias/175/GA-TI-F01 ACTA DE MANTENIMIENTO.pdf, una ruta diferente.
- **Certeza:** posible por el ID incluido en el nombre, no confirmado por
  contenido ni por respaldo.
- **Clasificación:** posible; requiere decisión funcional.

## Mantenimiento 177

- **Posible coincidencia:** existe el archivo
  storage/app/public/constancias/constancia_177_dc4f65a5-3ca0-45d7-8a18-5599c553b0dc.pdf.
- **Metadata:** 322,883 bytes; creación y modificación
  30/07/2026 11:14:16.
- **Comparación:** el mantenimiento 177 registra actualmente
  constancias/constancia_177_4f4bfc94-57ea-4d86-af8d-5fe4f3fbe118.pdf, una
  ruta diferente.
- **Certeza:** posible por el ID incluido en el nombre, no confirmado por
  contenido ni por respaldo.
- **Clasificación:** posible; requiere decisión funcional.

## Recomendación

### Casos recuperables

No hay un caso confirmado como recuperable. Los PDFs de 175 y 177 son
candidatos posibles, pero no deben reasignarse solo por el nombre.

### Casos pendientes

- Mantenimientos 65 y 73: buscar en respaldos con autorización y procedimiento
  de recuperación, sin modificar la BD original.
- Mantenimientos 175 y 177: confirmar con el responsable funcional o mediante
  inspección autorizada del contenido y comparación controlada.

### Casos no recuperables

Ningún caso puede clasificarse como no recuperable con la evidencia disponible.

## Acciones que requieren autorización

- Abrir o consultar dumps SQL de respaldo.
- Inspeccionar el contenido de los PDFs candidatos.
- Restaurar un respaldo en un entorno aislado.
- Copiar, mover o reasignar archivos.
- Actualizar rutas en la base de datos.

## Confirmación

No hubo cambios de código, base de datos, migraciones, pruebas ni archivos
originales de evidencia. No hubo restauraciones, copias, movimientos,
renombrados ni reasignaciones.

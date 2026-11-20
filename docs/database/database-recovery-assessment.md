# Evaluacion de recuperacion de base de datos

## Resumen ejecutivo

La conexion efectiva de Laravel apunta a MySQL en `127.0.0.1:3306`, base
`mantenimientos`. La base activa no coincide con la restauracion aislada del
dump de julio: conserva `mantenimiento_eventos` y datos con actividad de
septiembre, mientras la restauracion aislada contiene solo 14 tablas y no
contiene esa tabla.

El incidente del 4 de septiembre confirma un riesgo de afectacion potencial,
porque el dump incluia `USE mantenimientos` y operaciones `DROP TABLE`/`CREATE
TABLE`. Sin un snapshot inmediatamente anterior no es posible probar qué
tablas, si alguna, fueron reemplazadas. La evidencia disponible no permite
clasificar la base actual como reemplazada completamente por el backup.

## Bases encontradas

El inventario de MySQL encontro, entre otras bases, las siguientes:

| Base | Tablas | Tamaño aproximado | Estado probable |
| --- | ---: | ---: | --- |
| `mantenimientos` | 15 | 720 KB | Base activa de Laravel |
| `mantenimientos_restore_audit_20260904` | No comprobable en esta consulta | No comprobado | Destino de prueba anterior, documentado como vacio |
| `mantenimientos_restore_execution_20260904` | 14 | 656 KB | Restauracion aislada parcial de julio |
| `mantenimientos_bd4_auditoria` | 19 | 896 KB | Base de auditoria vacia observada |
| `mantenimientos_esquema_bd4_pruebas` | 19 | 896 KB | Base de pruebas vacia observada |
| `mantenimientos_limpia_estudio` | 19 | 960 KB | Base de estudio vacia observada |
| `mantenimientos_normalizacion_pruebas` | 19 | 928 KB | Base de pruebas con datos distintos |

El inventario incluyo otras bases no relacionadas con este sistema. No se
modifico ni elimino ninguna.

## Evidencia tecnica

### Conexion Laravel

`php artisan about` y la configuracion efectiva reportaron driver `mysql`.
La inspeccion segura de configuracion confirmo:

- driver: `mysql`;
- host: `127.0.0.1`;
- puerto: `3306`;
- base: `mantenimientos`.

No se expusieron contraseñas ni archivos de entorno.

### Base activa

| Tabla | Registros | ID maximo | Ultima actualizacion observada |
| --- | ---: | ---: | --- |
| `usuarios` | 60 | 508 | 2026-09-04 12:11:00 |
| `mantenimientos` | 155 | 835 | 2026-09-04 11:13:50 |
| `usuarios_sistema` | 2 | 2 | 2026-09-04 10:50:07 |
| `tecnicos` | 3 | 3 | 2026-06-09 16:39:25 |
| `mantenimiento_eventos` | 9 | 9 | tabla sin `UPDATE_TIME` disponible |

Los eventos activos son posteriores al dump historico y la pantalla actual
muestra los nombres UTF-8 corregidos. Esto demuestra actividad posterior, no
una prueba completa de que ninguna tabla haya sido reemplazada antes.

### Restauracion aislada

`mantenimientos_restore_execution_20260904` contiene 60 usuarios, 155
mantenimientos, 2 usuarios de sistema y 3 tecnicos, con ultimas
actualizaciones del 30/07/2026. No contiene `mantenimiento_eventos`.

La base activa y esa base aislada comparten conteos basicos, pero difieren en
fecha, eventos y esquema. Los conteos por sí solos no identifican una
restauracion como origen.

## Comparacion de datos

### Usuarios

- Activa: 60 registros, ultima actualizacion 04/09/2026.
- Restauracion aislada: 60 registros, ultima actualizacion 30/07/2026.
- La activa contiene los nombres corregidos a UTF-8.

### Mantenimientos

- Activa: 155 registros, ID maximo 835, actualizacion observada 04/09/2026.
- Restauracion aislada: 155 registros, ID maximo 835, actualizacion observada
  30/07/2026.
- No se encontro evidencia de diferencia de conteo, pero no se hizo una
  comparacion fila por fila completa.

### Eventos

- Activa: 9 eventos.
- Restauracion aislada: tabla ausente.
- La tabla activa registra eventos de septiembre, posteriores al backup.

### Backup historico

El archivo `storage/app/backups/mantenimientos_actual_20260730_114546.sql`
corresponde al 30/07/2026 11:45:46, tiene 58.512 bytes y restaura una version
parcial de 14 tablas. No incluye `mantenimiento_eventos` ni `empresas`. Fue
analizado sin importarlo en esta auditoria.

La documentacion del incidente confirma que el dump contiene `USE
mantenimientos`, `DROP TABLE` y `CREATE TABLE`. No se volvio a ejecutar.

## Riesgo identificado

### Alto - destino interno del dump

Una importacion del dump historico puede ignorar la base indicada por el
cliente y dirigir operaciones a `mantenimientos`. El riesgo esta confirmado
por el contenido del archivo y el procedimiento documentado.

### Alto - estado previo no demostrable

No existe un snapshot tomado inmediatamente antes del incidente. Por ello no
puede confirmarse si hubo reemplazo o perdida de registros en tablas legacy.

### Medio - pruebas en bases no uniformes

Existen varias bases de auditoria y prueba con esquemas y datos diferentes.
Esto aumenta el riesgo de validar una base distinta de la que usa la
aplicacion.

### Medio - pruebas potencialmente escritora

Los timestamps recientes de la base activa demuestran actividad, pero no
permiten atribuir cada escritura a una prueba, comando o usuario sin logs de
auditoria del motor.

## Estado actual

### Clasificacion: Estado B - cambios parciales o historial no completamente demostrable

La clasificacion mas prudente es **Estado B**:

- Laravel usa la base `mantenimientos` comprobada.
- La base activa conserva tablas y eventos posteriores al dump.
- Los conteos principales coinciden con la restauracion aislada.
- No existe comparacion fila por fila ni snapshot previo.
- No se puede afirmar reemplazo total ni integridad historica completa.

No hay evidencia suficiente para Estado C. Tampoco hay evidencia suficiente
para certificar Estado A.

## Opciones de recuperacion

1. **No restaurar y conservar la base activa:** opcion recomendada mientras no
   exista diferencia comprobada. Requiere snapshot actual y comparacion
   detallada.
2. **Restaurar en instancia MySQL independiente:** opcion segura para probar
   un backup, usando una copia de trabajo validada y sin `USE` hacia la base
   activa.
3. **Recuperacion selectiva:** solo despues de identificar filas o tablas
   concretas con evidencia de perdida y contar con aprobacion.
4. **Restauracion completa:** no recomendada con el dump actual, porque es
   parcial y su destino interno es peligroso.

## Recomendacion

1. No importar nuevamente el dump historico en esta instancia.
2. Mantener la base activa sin cambios de recuperacion mientras se compara.
3. Crear un snapshot verificable del estado actual antes de otra operación.
4. Comparar hashes, IDs, fechas y filas entre la base activa y el backup
   restaurado, sin asumir que los conteos bastan.
5. Usar una instancia MySQL separada para futuras restauraciones.
6. No ejecutar migraciones ni reparaciones de datos hasta identificar una
   diferencia concreta y autorizarla.

## Acciones prohibidas hasta confirmar

- importar el dump historico sobre esta instancia;
- ejecutar `DROP`, `CREATE`, `SOURCE`, `UPDATE`, `DELETE` o migraciones de
  recuperacion;
- eliminar las bases de auditoria sin autorizacion;
- sustituir registros actuales por datos del backup solo por coincidencia de
  conteos;
- ejecutar pruebas que escriban sobre la base activa.

## Validacion y alcance

- Se ejecutaron consultas de lectura, `php artisan about`, configuración
  efectiva no sensible y análisis estático de los dumps.
- No hubo restauraciones ni cambios de BD.
- No hubo migraciones ni modificaciones de código.
- No se eliminaron archivos ni bases.
- No se ejecutaron `git add`, commit ni push.

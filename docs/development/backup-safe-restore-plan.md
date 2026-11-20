# Plan seguro de restauracion de backups

## Objetivo

Definir un procedimiento reproducible para probar restauraciones sin poner en
riesgo la base activa, los backups originales ni las evidencias PDF de
Mantenimientos-Laravel.

Este documento es preventivo. No modifica backups, no ejecuta restauraciones y
no sustituye una autorizacion operativa para recuperar datos.

## Riesgo actual identificado

Los dumps SQL disponibles pueden contener instrucciones internas como:

- `USE mantenimientos`;
- `DROP TABLE`;
- `CREATE TABLE`;
- `INSERT`.

Una opcion de destino del cliente MySQL no garantiza el aislamiento si el
archivo cambia la base activa mediante `USE`. Las sentencias destructivas
pueden reemplazar tablas si se ejecutan en la instancia equivocada.

Este riesgo fue comprobado durante la prueba documentada en
`backup-restore-incident.md`. La base aislada creada en esa prueba quedo sin
tablas y la restauracion no se considero valida.

## Requisitos antes de restaurar

- Autorizacion expresa para crear y modificar un entorno de prueba.
- Identificacion del backup por ruta, fecha, tamano, hash y origen conocido.
- Snapshot o huella de la base activa antes de cualquier prueba.
- Confirmacion de que el destino no contiene la base activa.
- Instancia MySQL/MariaDB separada, contenedor o servidor aislado; otra base
  dentro de la misma instancia activa no es suficiente.
- Espacio disponible para BD, archivos temporales y evidencias.
- Procedimiento de rollback del entorno de prueba.
- Inventario de `storage/app/public` y de las rutas en
  `mantenimientos.evidencia_pdf`.
- No usar credenciales productivas en comandos reutilizables ni exponerlas en
  logs.

## Preparacion del entorno aislado

1. Seleccionar una instancia MySQL/MariaDB separada de la instancia usada por
   la aplicacion local.
2. Crear una base con nombre exclusivo de prueba y verificar mediante
   `SELECT DATABASE()` que el cliente apunta a ella.
3. Confirmar que la base no contiene tablas de produccion ni conexiones de la
   aplicacion activa.
4. Desactivar conexiones de la aplicacion al entorno de prueba mientras se
   importa.
5. Preparar un directorio temporal fuera de las rutas originales para la
   copia de trabajo del dump.
6. Preparar un almacenamiento temporal para las evidencias, sin reemplazar
   `storage/app/public` real.

## Validacion previa del archivo SQL

La validacion se debe hacer sobre una copia de trabajo, sin editar ni
sobrescribir el backup original. Debe registrarse el resultado y abortar si
aparecen destinos o instrucciones no autorizadas.

### `USE DATABASE`

- Buscar todas las sentencias `USE`.
- Confirmar que no apuntan a `mantenimientos` ni a otra base activa.
- Si existe un `USE` fijo, no importar directamente.
- Neutralizarlo solo mediante un proceso controlado sobre la copia temporal,
  dejando intacto el original y revisando el resultado antes de importar.

### `DROP DATABASE`

- Buscar `DROP DATABASE` y variantes equivalentes.
- Tratar cualquier coincidencia como criterio de aborto salvo autorizacion
  especifica en una instancia desechable.
- Nunca permitir que apunte a la base activa.

### `DROP TABLE`

- Inventariar las tablas afectadas.
- Confirmar que todas pertenecen al destino aislado.
- Verificar que no se trata de una instancia compartida con datos reales.
- Registrar si el dump reemplaza tablas legacy necesarias.

### `CREATE TABLE`

- Inventariar tablas, columnas, tipos, indices y restricciones creadas.
- Comparar las tablas criticas esperadas: `usuarios_sistema`, `usuarios`,
  `mantenimientos`, `mantenimiento_eventos`, `tecnicos` y `areas`.
- Identificar diferencias frente al esquema documentado.

### `INSERT`

- Confirmar tablas destino y volumen aproximado.
- No asumir que los datos corresponden a la base activa por el nombre del
  archivo.
- Verificar fechas, rangos de IDs y presencia de datos criticos despues de la
  importacion.

### Procedimientos y triggers

- Buscar `CREATE PROCEDURE`, `CREATE FUNCTION`, `CREATE TRIGGER`, `EVENT` y
  bloques `DELIMITER`.
- Revisar si escriben en otras bases, crean datos automaticamente o dependen
  de usuarios y permisos del servidor.
- Abortar si no se puede determinar su alcance o si ejecutan operaciones fuera
  del esquema aislado.

## Procedimiento seguro propuesto

1. **Crear copia de trabajo:** duplicar el archivo en un directorio temporal y
   calcular su hash; conservar el original sin modificaciones.
2. **Validar contenido:** revisar destinos, operaciones destructivas, tablas,
   procedimientos y triggers antes de abrir una conexion de importacion.
3. **Preparar base aislada:** usar una instancia separada, nombre de prueba y
   credenciales no productivas; comprobar el destino con una consulta.
4. **Restaurar controladamente:** importar solo la copia validada y capturar
   salida, errores, fecha, destino y codigo de retorno.
5. **Validar BD:** comprobar tablas, estructura, conteos, rangos de IDs,
   relaciones logicas y datos criticos.
6. **Validar archivos asociados:** restaurar una copia temporal de
   `storage/app/public`, comparar rutas de `evidencia_pdf`, detectar faltantes
   y huerfanos y ejecutar `evidencias:auditar` en modo lectura.
7. **Cerrar la prueba:** guardar resultados y hashes, desconectar el entorno
   aislado y conservar o eliminar sus temporales solo con autorizacion.

## Criterios de exito

- La base activa no recibio conexiones de importacion.
- El destino comprobado coincide con la base aislada.
- El importador termina sin errores.
- Existen las tablas criticas requeridas por el codigo.
- Los conteos y rangos esperados quedan documentados.
- Las rutas de evidencia tienen correspondencia con archivos temporales.
- No aparecen huerfanos no explicados.
- No se ejecutan procedimientos o triggers fuera del alcance aprobado.
- El backup original conserva su hash y metadata.
- Existe evidencia reproducible de la prueba y de su resultado.

## Criterios para abortar

- El dump contiene `USE mantenimientos` o un destino no autorizado.
- Aparece `DROP DATABASE` sin autorizacion especifica.
- El cliente no permite comprobar la base efectiva.
- La instancia aislada comparte datos o conexiones con la base activa.
- Se detectan errores SQL, tablas criticas faltantes o conteos incoherentes.
- Hay procedimientos, funciones o triggers cuyo alcance no puede determinarse.
- La restauracion intenta escribir fuera del destino temporal.
- No existe respaldo temporal de archivos antes de validar evidencias.
- Se requiere detener servicios o modificar permisos sin autorizacion.
- Cualquier paso amenaza backups, datos reales o `storage/app/public` activo.

## Riesgos pendientes

### Hechos comprobados

- Los backups actuales pueden incluir destinos SQL fijos y operaciones de
  reemplazo.
- No existe una restauracion valida demostrada.
- No existe backup comprobado de evidencias PDF.
- Otra base dentro de la misma instancia no aisló el dump anterior.

### Recomendaciones futuras

- Implementar una instancia dedicada para pruebas de restauracion.
- Automatizar inspeccion de sentencias peligrosas antes de importar.
- Generar hashes e inventarios de BD y almacenamiento.
- Probar periodicamente restauracion conjunta de BD, PDFs y configuracion
  operativa segura.
- Documentar retencion, responsables, RPO/RTO y procedimiento de emergencia.
- Mantener la limpieza y cualquier recuperacion como operaciones autorizadas y
  separadas de las pruebas.

## Validacion del documento

- Se creo unicamente documentacion.
- `git diff --check` debe ejecutarse desde
  `C:\xampp\htdocs\Mantenimientos-Laravel`.
- No se editaron backups SQL.
- No se ejecuto ninguna restauracion.
- No se modifico la BD.
- No se modificaron codigo, migraciones ni pruebas.
- No se ejecutaron `git add`, `git commit` ni `git push`.

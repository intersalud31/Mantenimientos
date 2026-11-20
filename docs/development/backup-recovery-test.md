# Prueba controlada de restauracion de backup

## Estado de la prueba

La prueba fue abortada por un problema de seguridad del dump seleccionado.
No debe considerarse una prueba valida de recuperacion aislada.

Durante la preparacion se creo la base de destino
`mantenimientos_restore_audit_20260904` y se selecciono el archivo:

`storage/app/backups/mantenimientos_actual_20260730_114546.sql`

El archivo tiene aproximadamente 58 KB y fecha 30/07/2026 11:45. Su motor
esperado es MySQL/MariaDB por el formato SQL y las sentencias observadas.

## Backup utilizado

El backup fue seleccionado por ser el archivo `mantenimientos_actual` mas
reciente dentro de `storage/app/backups`. No se modifico ni elimino el archivo
original.

El dump contiene una sentencia `USE mantenimientos`, ademas de operaciones
`DROP TABLE` y `CREATE TABLE`. Esto hace que una opcion `-D` del cliente no sea
suficiente para aislar la restauracion.

## Procedimiento ejecutado

1. Se verifico que el proyecto esperado existe en
   `C:\xampp\htdocs\Mantenimientos-Laravel`.
2. Se verifico que MySQL respondia en `127.0.0.1:3306`.
3. Se creo la base separada `mantenimientos_restore_audit_20260904`.
4. Se intento importar el dump usando esa base como destino.
5. La importacion termino con codigo de salida cero.
6. Una comprobacion posterior mostro que la base aislada tenia cero tablas.
7. La inspeccion controlada de sentencias del dump revelo `USE
   mantenimientos`; por eso la prueba se detuvo y no se ejecutaron mas
   escrituras.

## Resultado BD

### Destino aislado

No se pudo restaurar el backup en el destino aislado: la base
`mantenimientos_restore_audit_20260904` quedo sin tablas.

### Base activa

La sentencia `USE mantenimientos` confirma un riesgo de escritura sobre la
base local activa durante la importacion. Una consulta posterior encontro en
la base activa:

- `mantenimientos`: 155 registros.
- `usuarios`: 60 registros.
- `usuarios_sistema`: 2 registros.
- `tecnicos`: 3 registros.
- `mantenimiento_eventos`: tabla existente.
- ultimo `updated_at` observado en `mantenimientos`: 2026-07-30 11:14:16.

Estos datos no permiten reconstruir con certeza el estado anterior a la
importacion. No se afirma que los registros sean correctos ni que hayan sido
alterados. Se requiere una comparacion autorizada contra un respaldo previo o
un snapshot confiable antes de cualquier recuperacion.

## Resultado archivos

No se ejecuto ninguna restauracion de archivos. No se encontro ni se intento
importar un backup de `storage/app/public` o de evidencias PDF. La
correspondencia entre rutas `mantenimientos.evidencia_pdf` y archivos fisicos
no fue validada porque la restauracion aislada no llego a existir.

## Problemas encontrados

### Critico - Dump con destino fijo

El dump contiene `USE mantenimientos`, por lo que puede ignorar la base
indicada al cliente MySQL. Ademas contiene operaciones destructivas para las
tablas listadas. No debe volver a importarse directamente.

### Alto - Prueba no aislada

La base de destino quedo vacia y no existe evidencia suficiente para asegurar
que la base activa conserve exactamente el estado previo. La prueba no puede
clasificarse como exitosa.

### Pendiente de verificacion

- Comparar conteos, maximos, timestamps y huellas de tablas activas contra un
  respaldo autorizado anterior a la importacion.
- Determinar si el backup corresponde al esquema y datos actuales.
- Verificar la existencia e integridad de `mantenimiento_eventos` y demas
  tablas modificadas por el dump.
- Confirmar con el responsable de la base si se requiere restauracion de
  recuperacion.

## Capacidad real de recuperacion

No demostrada.

El proyecto tiene dumps SQL y herramientas de auditoria, pero esta prueba no
demostro una restauracion segura. Tampoco existe un backup comprobado de PDFs
ni una recuperacion conjunta de BD y `storage/app/public`.

## Procedimiento seguro recomendado

Antes de repetir la prueba se debe:

1. Crear una instancia o servidor MySQL aislado, no solo una base en la misma
   instancia activa.
2. Validar el contenido del dump en un entorno desechable y neutralizar su
   `USE` mediante un procedimiento controlado, sin modificar el backup
   original.
3. Confirmar que todas las operaciones SQL apunten exclusivamente al destino
   aislado.
4. Importar y verificar tablas y conteos criticos.
5. Respaldar tambien `storage/app/public` y comparar las rutas de evidencia.
6. Registrar hashes, fecha, origen y resultado de cada componente.

La repeticion requiere autorizacion explicita sobre el entorno de prueba y,
si se determina que la base activa fue afectada, un plan de recuperacion
aprobado. No se debe ejecutar `DROP`, `CREATE` o `SOURCE` sobre la base activa.

## Recomendaciones futuras

- **P0:** detener el uso directo de este dump hasta confirmar el estado de la
  base activa y generar una copia de seguridad autorizada.
- **P0:** establecer una instancia aislada real para restauraciones de prueba.
- **P0:** verificar backup conjunto de BD y evidencias PDF.
- **P1:** automatizar validacion de destino y rechazo de dumps con `USE` hacia
  una base productiva.
- **P1:** documentar conteos, hashes y criterios de exito de restauracion.
- **P2:** automatizar pruebas periodicas de recuperacion y monitoreo de
  respaldos.

## Validacion y restricciones

- No se restauraron archivos PDF.
- No se movieron ni eliminaron backups originales.
- No se modifico codigo, migraciones ni pruebas.
- No se ejecutaron comandos de limpieza.
- No se ejecutaron `git add`, `git commit` ni `git push`.

La base aislada creada para la prueba permanece pendiente de limpieza
autorizada; no se ejecutara un `DROP DATABASE` sin instruccion expresa.

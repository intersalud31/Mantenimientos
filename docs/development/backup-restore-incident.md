# Incidente de prueba de restauracion de backup

## Contexto

Durante una prueba controlada de recuperacion del proyecto
Mantenimientos-Laravel se intento importar un dump SQL en una base separada.
El objetivo era validar restaurabilidad sin afectar la base local activa
`mantenimientos`.

El archivo seleccionado fue:

`storage/app/backups/mantenimientos_actual_20260730_114546.sql`

La base aislada creada para la prueba fue
`mantenimientos_restore_audit_20260904`.

## Que ocurrio

Se ejecuto el cliente MySQL indicando la base aislada como destino. La
importacion termino con codigo de salida cero, pero una verificacion posterior
mostro que la base aislada tenia cero tablas.

Una inspeccion controlada de las sentencias del dump revelo que el archivo
incluye:

- `USE mantenimientos`;
- operaciones `DROP TABLE`;
- operaciones `CREATE TABLE`.

Por tanto, la base indicada como destino no garantizaba el aislamiento: el
propio dump podia cambiar el contexto hacia la base activa. La prueba se
detuvo inmediatamente despues de detectar esta condicion.

## Causa tecnica

El destino real de un dump MySQL no depende unicamente de la opcion `-D` o de
la base seleccionada al iniciar el cliente. Una sentencia `USE` incluida en el
archivo puede cambiar la base activa durante la importacion.

En este caso, el dump fue generado con un destino fijo y no estaba preparado
para ser importado de forma segura en otra base dentro de la misma instancia.

## Riesgo identificado

### Hecho comprobado

- El dump contiene `USE mantenimientos`.
- La base aislada quedo sin tablas.
- El dump contiene sentencias de reemplazo de tablas.
- La instancia MySQL respondia en `127.0.0.1:3306`.

### Riesgo

Una importacion de este archivo podia ejecutar reemplazos sobre la base local
`mantenimientos`, aunque el operador hubiera indicado otra base como destino.

### No confirmado

No existe un snapshot inmediato anterior a la importacion. Por ello no se
puede confirmar si las tablas de la base activa fueron reemplazadas, ni si sus
datos cambiaron, solo comparando el estado posterior.

## Acciones realizadas

- Se verifico la ruta del proyecto correcta.
- Se reviso la metadata del backup sin modificarlo.
- Se creo la base aislada para la prueba.
- Se intento la importacion.
- Se comprobo que la base aislada quedo vacia.
- Se inspeccionaron de forma controlada nombres de sentencias del dump.
- Se detuvo la prueba al detectar `USE mantenimientos`.
- Se consultaron tablas y conteos de la base activa en modo lectura.
- Se documento el estado posterior en
  `database-integrity-after-restore-test.md`.

## Acciones no realizadas

- No se hizo una restauracion completa valida.
- No se ejecuto una segunda importacion.
- No se elimino la base activa.
- No se eliminaron ni movieron backups originales.
- No se restauraron evidencias PDF.
- No se ejecutaron migraciones.
- No se modifico codigo ni pruebas.
- No se ejecuto limpieza automatica.
- No se hizo `DROP DATABASE` sobre la base aislada.

## Estado actual

La base activa conserva las tablas criticas observadas en la verificacion
posterior, incluyendo `mantenimientos`, `usuarios_sistema`,
`mantenimiento_eventos` y `tecnicos`. Tambien se observaron eventos de
septiembre en `mantenimiento_eventos`, posteriores a la fecha del dump.

Esto demuestra que la aplicacion conserva estructuras y actividad posterior,
pero no certifica que todos los registros y tablas tengan exactamente el mismo
estado que antes del incidente.

La base aislada `mantenimientos_restore_audit_20260904` permanece vacia y
pendiente de limpieza autorizada.

## Lecciones aprendidas

1. Un destino indicado en la linea de comandos no es suficiente si el dump
   contiene `USE`.
2. Los dumps con `DROP TABLE` deben tratarse como operaciones potencialmente
   destructivas.
3. Una prueba de restauracion necesita un servidor o instancia MySQL aislada,
   no solo otra base en la misma instancia activa.
4. Debe existir un snapshot o huella previa antes de probar restauraciones.
5. BD y evidencias PDF deben probarse como componentes coordinados.
6. El codigo de salida cero del cliente no demuestra una recuperacion segura ni
   un destino correcto.

## Reglas para futuras restauraciones

- Nunca importar directamente un dump no inspeccionado en una instancia que
  contenga datos activos.
- Revisar `USE`, `DROP`, `CREATE`, `TRUNCATE` e instrucciones de destino antes
  de importar.
- Usar una instancia MySQL separada o un contenedor aislado y verificable.
- Confirmar mediante consulta que el destino aislado es la base efectiva antes
  de importar.
- Crear un snapshot previo y conservarlo sin sobrescribirlo.
- Trabajar con una copia temporal del dump; no editar el backup original.
- Validar tablas, conteos, rangos, huellas y relaciones despues de restaurar.
- Restaurar y comprobar tambien `storage/app/public` y las rutas de evidencia.
- No limpiar, reasignar ni eliminar archivos hasta cerrar la conciliacion.
- Registrar responsable, fecha, origen, destino y resultado de cada prueba.

## Validacion y alcance

- Se creo unicamente este documento.
- `git diff --check` fue ejecutado desde
  `C:\xampp\htdocs\Mantenimientos-Laravel`.
- No se realizaron restauraciones adicionales.
- No se modificaron datos intencionalmente durante esta documentacion.
- No se modificaron codigo, BD, migraciones, backups ni pruebas.
- No se ejecutaron `git add`, `git commit` ni `git push`.

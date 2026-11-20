# Ejecucion de correccion UTF-8

## Resumen

La correccion se aplico el 4 de septiembre de 2026 sobre la base activa
`mantenimientos`, despues de validar el listado mediante `--dry-run`.

## Respaldo previo

Se creo un dump de la tabla `usuarios` sin sobrescribir respaldos existentes:

`C:\xampp\backups\Mantenimientos-Laravel\user-correction-20260904_01\usuarios.sql`

El archivo tiene 13.988 bytes y fue generado antes de cualquier escritura.
Tambien se guardaron las salidas del dry-run previo, la aplicacion y el
dry-run posterior en el mismo directorio.

## Cambios aplicados

El dry-run previo propuso exactamente 23 registros, correspondientes a los
IDs auditados. Se ejecuto:

`php artisan usuarios:corregir-nombres --apply`

El comando reporto 23 cambios aplicados. Solo se actualizo la columna
`usuarios.nombre`; no se modificaron estados, relaciones ni estructura.

## Validacion posterior

Se ejecuto nuevamente:

`php artisan usuarios:corregir-nombres --dry-run`

Resultado: `0` cambios pendientes.

Una consulta independiente sobre la base confirmo `0` nombres que contengan
el patron literal `??`.

La prueba unitaria `UsuarioNombreRepairServiceTest` paso con 24 pruebas y 24
aserciones.

## Suite completa

Se ejecuto:

`php artisan test --do-not-cache-result`

Resultado observado: 165 pruebas pasaron y 9 fallaron (956 aserciones). Los
fallos corresponden a permisos denegados del entorno local al escribir
`storage/logs/laravel.log` y crear directorios bajo
`storage/framework/testing`, incluyendo pruebas de evidencias PDF. No hay
evidencia de que esos fallos provengan de la correccion de nombres.

## Integridad y alcance

- Los 23 registros propuestos fueron los unicos aplicados.
- No hubo cambios de esquema ni migraciones.
- No se modificaron respaldos existentes; solo se creo el respaldo previo
  indicado.
- No se modificaron archivos PDF.
- No se ejecutaron comandos adicionales de reparacion.

## Pendiente

Debe resolverse por separado el bloqueo de permisos del entorno de pruebas y
repetirse la suite completa. No se recomienda ejecutar otra correccion de
nombres: el dry-run ya confirma que no quedan cambios pendientes.

# Endurecimiento implementado de evidencias PDF

## Objetivo del cambio

El problema identificado era que un PDF podia guardarse correctamente en el
filesystem y que una operacion posterior de BD o de negocio podia fallar. En
ese escenario, el archivo quedaba sin referencia en
`mantenimientos.evidencia_pdf` y se convertia en un posible archivo huerfano.

El cambio busca compensar ese fallo sin alterar el esquema legacy, las rutas
existentes ni el comportamiento exitoso del sistema.

## Estado anterior

En los flujos de ejecucion y gestion de estados, `PanelController` recibia el
PDF, validaba su tipo y tamano, saneaba el nombre con `basename()` y lo
guardaba mediante `storeAs` en el disco `public`.

Despues enviaba la ruta relativa al repository o servicio responsable de
actualizar el mantenimiento y registrar los eventos correspondientes.

El codigo ya eliminaba el archivo cuando la operacion devolvia un resultado
`ok=false`, pero no garantizaba la limpieza cuando una excepcion inesperada se
producía despues de `storeAs`. La transaccion de BD tampoco podia revertir un
archivo del filesystem.

## Implementacion realizada

### Archivos modificados

- `app/Http/Controllers/PanelController.php`
- `tests/Feature/PanelActionFlowTest.php`

### Comportamiento nuevo

Se protegieron los flujos que almacenan evidencias desde:

- Programados, mediante `marcarProgramadoComoHecho`.
- Gestion de estados, mediante `previsualizarGestionEstado` y
  `gestionarEstadoMantenimiento`.

Cada flujo conserva temporalmente la ruta generada y controla tanto los
resultados funcionales fallidos como los `Throwable` ocurridos despues del
almacenamiento.

Ante una excepcion:

1. Se intenta eliminar unicamente el archivo creado en la solicitud actual.
2. Se conserva la redireccion y el mensaje funcional del modulo.
3. La excepcion se reporta mediante el mecanismo existente.
4. Una falla secundaria durante la limpieza no reemplaza el error original.

Antes de almacenar se comprueba si la ruta destino ya existia. Si existia, la
compensacion no la elimina. Esto evita borrar una evidencia previa durante el
manejo del error.

La implementacion mantiene:

- `mantenimientos.evidencia_pdf` como referencia relativa.
- `Storage::disk('public')` y las rutas historicas.
- Las transacciones existentes de BD y eventos.
- Estados, observaciones humanas y compatibilidad legacy.

## Pruebas realizadas

Se agrego una prueba para verificar que una excepcion inesperada despues de
guardar el PDF elimina el archivo nuevo:

- `test_excepcion_inesperada_despues_de_guardar_pdf_elimina_archivo_nuevo`.

La cobertura existente tambien valida carga correcta, asociacion, rollback
controlado, validacion de PDF, consulta, descarga y archivo faltante.

Resultados:

- `php artisan test tests/Feature/PanelActionFlowTest.php --do-not-cache-result`:
  45 pruebas exitosas, 306 assertions.
- `php artisan test --do-not-cache-result`: 173 pruebas exitosas, 1016
  assertions, 0 fallos.
- `php -l app/Http/Controllers/PanelController.php`: sin errores de sintaxis.
- `php -l tests/Feature/PanelActionFlowTest.php`: sin errores de sintaxis.
- `git diff --check`: correcto.

Las pruebas se ejecutaron con `LOG_CHANNEL=null` como variable temporal del
proceso debido al bloqueo local previamente identificado en
`storage/logs/laravel.log`. No se modifico la configuracion de logging.

## Riesgos pendientes

### P1

- Los nombres originales pueden repetirse.
- `storeAs` puede sobrescribir potencialmente una ruta existente dependiendo
  del adapter y del nombre recibido.
- No existe metadata independiente comprobada para hash, tamano, MIME,
  version, cargador o fecha de carga.
- La BD y el filesystem no forman una transaccion atomica real.
- Persisten referencias historicas cuya integridad depende de auditorias y
  respaldos separados.

### P2

- Evaluar almacenamiento privado con autorizacion por evidencia.
- Incorporar versionado formal de archivos.
- Definir hash y metadata estructurada.
- Establecer retencion, archivado, monitoreo de capacidad y restauracion
  conjunta.
- Evaluar escaneo de seguridad para archivos segun el entorno operativo.

Estos puntos no fueron implementados en este cambio.

## Decisiones tomadas

- No se creo una tabla nueva.
- No se cambio el disco ni la organizacion del almacenamiento.
- No se modificaron registros historicos ni archivos existentes.
- No se modifico `mantenimientos.observaciones`.
- No se cambiaron estados ni reglas de negocio.
- Se priorizo la compatibilidad legacy sobre una refactorizacion amplia.
- La compensacion del filesystem se mantuvo localizada en los controllers que
  ya coordinaban la carga.

## Proximo paso recomendado

La siguiente prioridad es establecer un proceso confiable de backup y
restauracion conjunta para la base de datos y las evidencias PDF. Debe
verificarse mediante restauraciones controladas antes de considerar resuelto
el riesgo de recuperacion operativa.

## Validacion y alcance

- Solo se creo documentacion en este cierre.
- No hubo cambios de codigo productivo.
- No hubo cambios de BD.
- No hubo migraciones.
- No hubo cambios en pruebas.
- No se modificaron PDFs ni backups.
- No se ejecutaron `git add`, `git commit` ni `git push`.

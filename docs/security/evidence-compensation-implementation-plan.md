# Plan de implementacion P0: compensacion de evidencias PDF

## Alcance

Este documento propone el cambio minimo para limpiar un PDF creado cuando una
operacion posterior lanza una excepcion. No implementa cambios y no propone
nuevas tablas, migraciones ni cambio de almacenamiento.

## 1. Metodos exactos a modificar

El punto comun a modificar es `app/Http/Controllers/PanelController.php`:

- `marcarProgramadoComoHecho`.
- `previsualizarGestionEstado`.
- `gestionarEstadoMantenimiento`.

Los metodos usan `storeAs` con el disco `public` y luego llaman a repositorios
o servicios que actualizan la BD y registran eventos. No se propone modificar
`MantenimientoRepository.php` ni los servicios de estados en este P0, porque
ya contienen las transacciones legacy y el objetivo es compensar el recurso
filesystem desde el controller.

## 2. Codigo actual relevante resumido

### Programados

`marcarProgramadoComoHecho` valida el archivo, obtiene un nombre saneado con
`basename()`, guarda en `constancias/{mantenimiento_id}/{nombre}` y llama a
`MantenimientoRepository::marcarProgramadoComoHechoDesdeProgramados`.

Actualmente, si el resultado retorna `ok=false`, ejecuta
`Storage::disk('public')->delete($rutaRelativa)`. Si `storeAs`, el repository o
la respuesta posterior lanza un `Throwable`, no existe un bloque local que
garantice esa limpieza antes de propagar o convertir el error.

### Gestion de estados

`previsualizarGestionEstado` primero calcula una previsualizacion. Si el
destino es ejecutado, valida y guarda en
`constancias/gestion/{codigo_activo}/{nombre}` antes de llamar a
`ReversionEstadoMantenimientoService::ejecutarPorCodigoActivo`.

`gestionarEstadoMantenimiento` repite el almacenamiento en la misma ruta
basada en codigo activo y llama al mismo servicio. Ambos metodos eliminan la
ruta cuando la respuesta es `ok=false`, pero no garantizan limpieza ante una
excepcion inesperada.

### Transacciones

El repository de ejecucion y el servicio de gestion usan `DB::transaction`
para los cambios de BD y eventos. Esa transaccion revierte la BD, pero no
puede deshacer automaticamente un archivo ya escrito en
`storage/app/public`.

## 3. Cambio minimo recomendado

Para cada uno de los tres metodos:

1. Mantener `$rutaRelativa` inicializada antes de cualquier almacenamiento.
2. Conservar sin cambios las validaciones, rutas, nombres, parametros,
   redirecciones y mensajes actuales.
3. Encerrar en un `try/catch` solamente el almacenamiento y la llamada de
   negocio que dependen de ese archivo.
4. En el bloque de fallo, si `$rutaRelativa` no esta vacia, borrar unicamente
   esa ruta creada durante la solicitud.
5. Si ocurre un `Throwable`, registrar el error con el mecanismo existente,
   intentar la limpieza de forma defensiva y devolver el mensaje funcional
   seguro ya utilizado por el modulo.
6. Si la limpieza tambien falla, no reemplazar el error original por el error
   secundario; debe quedar registrado para diagnostico.

La compensacion debe ser idempotente: intentar borrar una ruta inexistente no
debe producir una segunda falla funcional. Nunca debe borrarse la ruta previa
del mantenimiento ni una ruta construida a partir de datos distintos de la
solicitud actual.

No se debe intentar simular atomicidad entre BD y filesystem. La garantia
resultante es: rollback transaccional de BD mas compensacion best-effort del
archivo nuevo, con auditoria posterior para detectar fallos de limpieza.

## 4. Como evitar archivos huerfanos

- Conservar la ruta creada en una variable local durante todo el flujo.
- Limpiar tanto el retorno funcional `ok=false` como la excepcion inesperada.
- No limpiar si el archivo nunca llego a crearse.
- No limpiar archivos anteriores que compartan nombre.
- Ejecutar despues `php artisan evidencias:auditar` en modo solo lectura.
- Mantener la auditoria para los casos en que Windows, permisos o bloqueos
  impidan la eliminacion.

El P0 no corrige huerfanos historicos ni elimina archivos existentes.

## 5. Riesgos

### Bajo

- El cambio queda localizado en los tres puntos que ya usan `storeAs`.
- Se conservan el disco, las rutas, el contrato de respuesta y la
  compatibilidad con registros legacy.

### Medio

- Capturar `Throwable` puede modificar el mensaje visible si no se conserva
  exactamente la redireccion actual.
- La limpieza puede fallar por permisos o bloqueo del archivo; debe tratarse
  como error secundario.
- Las pruebas con `Storage::fake('public')` no reproducen todos los fallos del
  filesystem real de Windows.

### Alto

- Borrar una ruta existente en lugar de la ruta creada en la solicitud puede
  causar perdida de evidencia.
- Mover el almacenamiento a otro servicio o cambiar nombres en este paso
  romperia el alcance y podria afectar referencias historicas.
- Dar por garantizada la atomicidad de BD y filesystem ocultaria inconsistencias
  ante fallos de infraestructura.

## 6. Pruebas despues de implementar

Ejecutar primero:

`php artisan test tests/Feature/PanelActionFlowTest.php`

Y despues:

`php artisan test`

Casos requeridos:

- carga exitosa desde Programados: PDF existente, ruta asociada y datos
  persistidos;
- carga exitosa desde Gestion de estados con destino ejecutado;
- resultado `ok=false` despues de `storeAs`: archivo nuevo eliminado y datos
  anteriores conservados;
- excepcion del repository o servicio despues de `storeAs`: archivo nuevo
  eliminado y respuesta segura;
- excepcion al registrar el evento: rollback de BD y archivo eliminado;
- observaciones humanas no modificadas por la compensacion;
- ruta faltante en consulta: redireccion y mensaje actual;
- archivo anterior con el mismo nombre: no borrar una evidencia que no fue
  creada por la solicitud actual;
- comando `evidencias:auditar` sin escrituras.

No se deben cambiar expectativas para hacer pasar la prueba; si un caso revela
que el contrato actual no permite compensacion segura, debe detenerse la
implementacion y documentarse.

## 7. Compatibilidad legacy

- Mantener `mantenimientos.evidencia_pdf` como referencia relativa.
- Mantener `Storage::disk('public')` y `storage:link` en esta fase.
- No modificar rutas historicas, observaciones ni eventos existentes.
- No cambiar estados ni reglas de transicion.
- Dejar la transaccion de BD en los repositorios/servicios actuales.
- Aplicar la limpieza solo al archivo nuevo de la solicitud que fallo.

## 8. Archivos revisados

- `AGENTS.md`
- `app/Http/Controllers/PanelController.php`
- `app/Services/Legacy/MantenimientoRepository.php`
- `app/Services/Legacy/GestionEstadoMantenimientoService.php`
- `app/Services/Legacy/ReversionEstadoMantenimientoService.php`
- `app/Console/Commands/AuditarEvidenciasCommand.php`
- `config/filesystems.php`
- `routes/web.php`
- `tests/Feature/PanelActionFlowTest.php`
- `tests/Feature/GestionEstadoMantenimientoServiceTest.php`
- `tests/Feature/AuditarEvidenciasCommandTest.php`
- `docs/security/evidence-compensation-design.md`
- `docs/security/evidence-hardening-plan.md`

## Validacion de la auditoria

- Se localizaron todas las apariciones actuales de `store`, `storeAs`,
  `evidencia_pdf` y borrado de evidencias en los componentes revisados.
- Se revisaron los bloques de transaccion y captura de excepciones relacionados.
- Solo se creo este documento de plan.
- No hubo cambios de codigo productivo.
- No hubo cambios de pruebas.
- No hubo cambios de BD ni migraciones.
- No se ejecutaron `git add`, `git commit` ni `git push`.

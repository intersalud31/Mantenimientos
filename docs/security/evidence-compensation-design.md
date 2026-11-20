# Diseno de compensacion de evidencias PDF

## Alcance

Documento de diseno previo a la implementacion. Se revisaron los flujos de
evidencias actuales sin modificar codigo, pruebas, base de datos ni
migraciones.

## Flujo actual

### Marcar programado como hecho

1. `PanelController::marcarProgramadoComoHecho` valida mantenimiento, fecha,
   tecnico, observaciones y PDF mediante `mimetypes:application/pdf` y
   `max:5120`.
2. El nombre original se reduce con `basename()` y el archivo se guarda con
   `storeAs` en `constancias/{mantenimiento_id}` sobre el disco `public`.
3. El controller pasa la ruta al repository.
4. `MantenimientoRepository::marcarProgramadoComoHechoDesdeProgramados` usa
   una transaccion de BD para actualizar el mantenimiento, registrar el evento
   tecnico y generar el siguiente pendiente.
5. Si el resultado funcional es fallido, el controller intenta borrar la ruta
   recien creada y redirige conservando el contexto.

### Gestion de estados

1. `PanelController::gestionarEstadoMantenimiento` valida los datos de
   gestion y resuelve el codigo activo.
2. Cuando el destino es ejecutado, exige un PDF con las mismas reglas y lo
   guarda en `constancias/gestion/{codigo_activo}` sobre `public`.
3. El controller envia la ruta al servicio de estados, que coordina la
   operacion legacy y la persistencia correspondiente.
4. Si la respuesta devuelve `ok=false`, el controller intenta eliminar el
   archivo recien creado.

El mismo patron aparece en el flujo relacionado de previsualizacion y
ejecucion de gestion de estados. No se comprobo otro flujo activo que escriba
PDF fuera de estos puntos del `PanelController`.

## Puntos de fallo posibles

### Errores controlados

- Validacion rechazada antes de `storeAs`: no se espera archivo creado por el
  flujo.
- Tecnico o usuario no valido antes de almacenar: se retorna sin escribir.
- Resultado de negocio `ok=false` despues de almacenar: existe borrado
  compensatorio explicito en los controllers.
- Archivo faltante durante lectura: `evidenciaPdf` informa el problema y no
  intenta reconstruirlo.

### Excepciones inesperadas

- Excepcion de `storeAs`, del servicio, del repository o de la respuesta puede
  interrumpir el flujo antes de ejecutar el borrado compensatorio del
  controller.
- La transaccion de BD puede revertir cambios de tablas y eventos, pero no
  revierte automaticamente el archivo del filesystem.
- Un fallo despues de escribir y antes de guardar `evidencia_pdf` puede dejar
  un PDF sin referencia.
- Un fallo despues de reemplazar un nombre existente puede afectar la
  evidencia anterior segun el comportamiento del adapter; esto no esta
  protegido por versionado.

## Diseno propuesto

### Cambio minimo

**Archivo principal:** `app/Http/Controllers/PanelController.php`.

**Metodos:** `marcarProgramadoComoHecho` y
`gestionarEstadoMantenimiento`.

1. Inicializar una variable de ruta creada vacia.
2. Ejecutar almacenamiento y llamada de negocio dentro de un bloque que pueda
   capturar `Throwable`.
3. Si el resultado no es exitoso o ocurre una excepcion, eliminar solamente la
   ruta creada en esta solicitud, si no esta vacia.
4. Mantener la respuesta actual de redireccion y mensaje para no cambiar el
   contrato UX.
5. Registrar la excepcion mediante el mecanismo existente sin mostrar detalles
   sensibles al usuario.
6. No eliminar el archivo anterior ni modificar registros historicos.

El borrado debe ser defensivo: una falla al limpiar no debe ocultar el error
original. Debe conservarse la transaccion existente del repository/servicio;
la compensacion del filesystem es complementaria y no se presenta como una
transaccion atomica real.

### Limites del cambio

- No cambiar `mantenimientos.evidencia_pdf` ni su tipo.
- No cambiar el disco `public` en esta fase.
- No generar nombres nuevos ni migrar rutas historicas en este piloto.
- No centralizar todos los flujos en un servicio nuevo hasta tener pruebas de
  caracterizacion.

## Estrategia de pruebas

Las pruebas deben agregarse en `tests/Feature/PanelActionFlowTest.php` o en
la suite de servicio ya existente, sin cambiar expectativas para ocultar
errores.

### Casos necesarios

- Carga exitosa: el PDF existe, la ruta se guarda y el mantenimiento queda
  actualizado.
- Fallo funcional despues de `storeAs`: el archivo nuevo se elimina, el
  registro conserva sus datos anteriores y la respuesta mantiene el contexto.
- Excepcion inesperada despues de `storeAs`: el archivo nuevo se elimina, la
  BD no queda parcialmente actualizada y el usuario recibe un error funcional
  seguro.
- Fallo al registrar evento: se revierte la transaccion de BD y se elimina el
  PDF creado.
- Observacion humana existente: permanece intacta cuando la operacion falla o
  cuando el flujo no requiere reemplazarla.
- Nombre igual al de un archivo anterior: debe definirse y probarse la
  politica antes de implementar reemplazos; no debe borrarse el archivo
  anterior por accidente.
- Ambos flujos: `programados/hecho` y `configuracion/gestionar` deben tener
  cobertura equivalente.

## Riesgos

### Bajo

- Mantener las rutas y mensajes actuales limita el impacto funcional.
- El cambio es localizado en los puntos que ya realizan `storeAs` y `delete`.

### Medio

- Capturar excepciones puede alterar el resultado visible si no se conserva
  el contrato actual de redireccion y mensajes.
- La limpieza puede fallar por permisos, bloqueo del archivo o interrupcion
  del proceso; debe registrarse sin encadenar una segunda excepcion.
- Los tests con `Storage::fake('public')` no representan todos los fallos de
  Windows o del adapter real.

### Alto

- Si se intenta hacer una transaccion unica entre BD y filesystem, se puede
  introducir una falsa garantia de atomicidad.
- Si se borra una ruta existente en vez de solo la creada en la solicitud,
  podria perderse una evidencia valida.
- Cambiar nombres o almacenamiento en el mismo paso podria romper rutas
  historicas y exceder el alcance de esta estabilizacion.

## Orden seguro de implementacion futura

1. Congelar los casos actuales con pruebas de exito y rollback.
2. Aplicar la compensacion solo en los dos metodos que almacenan evidencias.
3. Ejecutar primero `php artisan test tests/Feature/PanelActionFlowTest.php` y
   despues `php artisan test`.
4. Ejecutar `php artisan evidencias:auditar` en modo lectura para comprobar que
   no aumentan los huerfanos.
5. Evaluar en una fase separada autorizacion por registro, disco privado,
   nombres unicos y metadata.

## Archivos revisados

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
- `docs/security/evidence-hardening-plan.md`
- `docs/security/evidence-storage-audit.md`
- `docs/security/file-lifecycle-audit.md`

## Validacion de esta fase

- Se realizo lectura estatica focalizada de cargas, persistencia,
  compensaciones, transacciones y pruebas.
- Se verificaron todas las apariciones actuales de `store`, `storeAs`,
  `evidencia_pdf` y borrado de evidencias en los componentes revisados.
- Se creo unicamente esta documentacion.
- No hubo cambios de codigo, pruebas, BD ni migraciones.
- No se ejecutaron `git add`, `git commit` ni `git push`.

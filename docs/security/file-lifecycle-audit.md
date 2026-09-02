# Auditoria del ciclo de vida de archivos

## Alcance

Revision estatica de los puntos donde se cargan, relacionan, consultan y eliminan evidencias PDF. No se inspeccionaron archivos reales ni se ejecuto limpieza.

## Flujo actual comprobado

### Carga desde Programados

`PanelController::marcarProgramadoComoHecho` valida `constancia_pdf` como archivo, MIME `application/pdf` y maximo de 5120 KB. Obtiene el nombre original, aplica `basename()` y almacena el archivo con `storeAs` en:

```text
constancias/{mantenimiento_id}/{nombre_original}
```

Despues entrega la ruta relativa a `MantenimientoRepository::marcarProgramadoComoHechoDesdeProgramados`, que la guarda en `mantenimientos.evidencia_pdf` y registra el evento tecnico correspondiente.

### Carga desde Gestion de estados

`PanelController::gestionarEstadoMantenimiento` exige PDF cuando el estado destino es ejecutado. Valida MIME y tamano, y almacena en:

```text
constancias/gestion/{codigo_activo}/{nombre_original}
```

La ruta se entrega al servicio de gestion de estados para asociarla con el mantenimiento resultante.

### Almacenamiento

Ambos flujos usan `Storage::disk('public')`. El disco apunta a `storage/app/public` y el proyecto espera el enlace `public/storage` mediante `storage:link`.

### Relacion archivo-mantenimiento

La relacion se guarda como una ruta relativa en el campo `mantenimientos.evidencia_pdf`. No existe una tabla independiente de archivos ni metadata separada de nombre original, MIME, tamano, hash o fecha de carga.

### Consulta y descarga

La ruta `GET /panel/evidencia/{mantenimientoId}` esta dentro de `legacy.auth` y `can:access-panel`. El controller consulta la ruta asociada al mantenimiento, verifica que exista en el disco publico y usa `response()` para visualizarla o `download()` para descargarla.

### Eliminacion

Si la operacion de negocio devuelve `ok = false` despues de almacenar el archivo, los controllers intentan eliminar la ruta mediante `Storage::disk('public')->delete()`.

Tambien se eliminan rutas anteriores en algunos flujos de actualizacion de usuario o mantenimiento cuando el codigo reemplaza una evidencia. No se encontro un proceso general de limpieza, expiracion, reconciliacion o borrado por lote.

## Riesgos encontrados

### Alto: almacenamiento publico

Estado actual: las evidencias se almacenan en el disco `public` y el enlace puede quedar bajo `public/storage`.

Riesgo: si el servidor permite acceso directo al enlace, el control de middleware puede ser evadido. Incluso con la ruta Laravel protegida, cualquier administrador con un ID valido puede solicitar la evidencia.

Impacto: exposicion de documentos operativos o datos personales.

Mejora futura: evaluar disco privado y descarga autorizada por mantenimiento. No implementado.

### Alto: autorizacion sin control por registro

Estado actual: la descarga comprueba autenticacion y acceso al panel, pero no una autorizacion adicional del mantenimiento solicitado.

Riesgo: un administrador puede consultar evidencias de otros registros si conoce o adivina el identificador.

Impacto: acceso mas amplio del necesario según el modelo de privilegios que se defina.

Mejora futura: aplicar una Policy o regla de acceso por registro si el negocio requiere segmentacion.

### Medio: archivos huerfanos ante excepciones

Estado actual: el archivo se almacena antes de completar toda la operacion de base de datos. Se elimina cuando el resultado devuelve error, pero no se comprobo una limpieza equivalente para toda excepcion inesperada.

Riesgo: puede quedar un archivo sin ruta persistida en `mantenimientos.evidencia_pdf`.

Impacto: consumo de almacenamiento y documentos imposibles de rastrear desde la aplicacion.

Mejora futura: usar compensacion garantizada, registro temporal o un proceso de reconciliacion probado.

### Medio: registros sin archivo

Estado actual: un mantenimiento puede tener `evidencia_pdf` vacio, una ruta cuyo archivo ya no existe o una ruta modificada externamente. No existe un proceso documentado que los detecte automaticamente.

Riesgo: la interfaz puede mostrar una evidencia no disponible o un estado ejecutado sin documento accesible.

Impacto: perdida de trazabilidad y cumplimiento operativo.

Mejora futura: comando de auditoria de solo lectura que compare rutas de base con el disco antes de cualquier limpieza.

### Medio: nombre original reutilizado

Estado actual: se conserva el nombre original saneado con `basename()`, pero no se agrega un identificador unico visible en la ruta.

Riesgo: cargas repetidas con el mismo nombre pueden sobrescribir o reemplazar un archivo dependiendo del comportamiento del adapter y del metodo `storeAs`.

Impacto: perdida o confusion entre evidencias si se reutiliza una ruta.

Mejora futura: nombres generados por la aplicacion y metadata del nombre original, previo analisis de compatibilidad historica.

### Bajo: metadata limitada

Estado actual: la base guarda principalmente la ruta relativa; no se conserva de forma separada tamaño, hash, MIME confirmado, usuario de carga o fecha de carga del archivo.

Riesgo: auditorias, deduplicacion y deteccion de alteraciones son limitadas.

Mejora futura: documentar y añadir metadata solo mediante un cambio de esquema aprobado.

## Casos de inconsistencia posibles

| Caso | Como puede ocurrir | Evidencia disponible |
|---|---|---|
| Archivo huerfano | Falla inesperada despues de `storeAs` | No hay registro asociado |
| Registro sin archivo | Se elimina manualmente el archivo o falla el almacenamiento despues de una escritura | `evidencia_pdf` apunta a ruta inexistente |
| Archivo reemplazado | Se reutiliza nombre y directorio | La ruta no identifica version |
| Evidencia inaccesible | Falta `storage:link` o cambia el disco | `Storage::exists()` devuelve falso |
| Ruta historica incompatible | Se mueve el archivo fuera de `storage/app/public` | El campo conserva una ruta que ya no resuelve |
| Mantenimiento ejecutado sin PDF | Se crea o actualiza por un flujo que no exige evidencia | Depende de las validaciones del flujo concreto |

## Procesos manuales y automaticos

Comprobado:

- Carga manual desde formularios del panel.
- Visualizacion y descarga manual desde la vista de hechos.
- Eliminacion compensatoria en errores devueltos por algunas operaciones.
- Eliminacion de rutas en operaciones puntuales de actualizacion.

No encontrado:

- Job de limpieza.
- Scheduler de reconciliacion.
- Comando dedicado de archivos huerfanos.
- Versionado de evidencias.
- Politica de retencion automatica.

## Recomendacion futura

1. Auditar en modo lectura la correspondencia entre `evidencia_pdf` y archivos existentes.
2. Definir si las evidencias requieren almacenamiento privado.
3. Definir autorizacion por mantenimiento.
4. Diseñar nombres unicos y metadata sin romper rutas historicas.
5. Implementar compensacion y pruebas para excepciones.
6. Crear una politica de retencion y borrado aprobada.

No se recomienda ejecutar limpieza automatica hasta contar con inventario, respaldo y reglas aprobadas.

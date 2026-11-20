# Plan de endurecimiento de evidencias PDF

## Alcance

Auditoria previa a cualquier mejora del flujo de evidencias. El contenido se
basa en el codigo y documentos disponibles. No se modificaron codigo, base de
datos, migraciones ni pruebas; tampoco se inspeccionaron archivos PDF reales ni
se ejecuto limpieza.

## Estado actual

### Flujo comprobado

1. `PanelController` recibe el archivo desde formularios del panel.
2. Los flujos de Programados y Gestion de estados validan archivo PDF, MIME
   `application/pdf` y tamano maximo de 5120 KB.
3. Se sanea el nombre original con `basename()`.
4. Se guarda en el disco `public`, bajo rutas relativas como:
   - `constancias/{mantenimiento_id}/{nombre}`;
   - `constancias/gestion/{codigo_activo}/{nombre}`.
5. La ruta relativa se guarda en `mantenimientos.evidencia_pdf` durante la
   operacion de negocio.
6. El repository comprueba existencia y construye URL publica para listados.
7. `PanelController::evidenciaPdf` comprueba la ruta en el disco y responde
   visualizacion o descarga.
8. Si la operacion devuelve fallo, algunos controllers intentan borrar el
   archivo previamente almacenado.

La relacion es una ruta en `mantenimientos`; no existe una tabla independiente
de archivos ni metadata separada comprobada para MIME, tamano, hash, usuario o
fecha de carga.

### Controles existentes

- Validacion de archivo, MIME y tamano en los flujos revisados.
- `basename()` para evitar componentes de ruta del nombre original.
- Middleware `legacy.auth` y `can:access-panel` en la ruta de descarga.
- Comprobacion de existencia antes de servir un archivo.
- Eliminacion compensatoria en algunos resultados funcionales fallidos.
- Uso consistente de `Storage::disk('public')` y expectativa de `storage:link`.

## Riesgos encontrados

### Alto - Almacenamiento publico

**Evidencia:** `config/filesystems.php` configura el disco `public` en
`storage/app/public`, y las evidencias se sirven mediante URL/response.

**Impacto:** un enlace directo o una configuracion permisiva del servidor puede
evadir el middleware. La ruta Laravel tampoco aplica autorizacion adicional por
registro.

**Recomendacion futura:** verificar el servidor y evaluar disco privado con
descarga autorizada, manteniendo compatibilidad con rutas historicas durante la
transicion.

### Alto - Autorizacion solo a nivel de panel

**Evidencia:** la descarga exige `legacy.auth` y `can:access-panel`, pero no se
comprobo Policy o regla adicional sobre el mantenimiento solicitado.

**Impacto:** un usuario autorizado al panel podria consultar evidencias de
otros registros si conoce o adivina el ID.

**Recomendacion futura:** definir autorizacion por mantenimiento/evidencia si
los requisitos de negocio requieren segmentacion.

### Medio - Archivos huerfanos tras excepciones

**Evidencia:** `storeAs` ocurre antes de completar toda la persistencia; el
borrado se ejecuta ante algunos retornos `ok=false`, pero no se comprobo una
compensacion equivalente para toda excepcion inesperada.

**Impacto:** archivos sin registro, consumo creciente y dificultad para
identificar que puede eliminarse con seguridad.

**Recomendacion futura:** compensacion garantizada o reconciliacion de solo
lectura antes de habilitar limpieza automatica.

### Medio - Registros sin archivo

**Evidencia:** `evidencia_pdf` puede estar vacio, apuntar a una ruta inexistente
o quedar desactualizado si el almacenamiento cambia. No existe un proceso
general de reconciliacion documentado.

**Impacto:** mantenimientos ejecutados sin evidencia accesible y perdida de
trazabilidad operativa.

**Recomendacion futura:** comando o reporte de auditoria que compare base y
disco, sin borrar automaticamente.

### Medio - Reemplazos y nombres repetidos

**Evidencia:** `storeAs` usa el nombre original dentro de un directorio basado
en mantenimiento o codigo, sin metadata de version comprobada.

**Impacto:** cargas repetidas pueden reemplazar/confundir evidencias segun el
adapter, y no existe una version explicita del archivo.

**Recomendacion futura:** nombres generados por aplicacion y metadata del nombre
original, preservando resolucion de rutas historicas.

### Medio - Metadata insuficiente para auditoria

**Evidencia:** el registro principal conserva la ruta, no un contrato separado
de hash, tamano, MIME confirmado, cargador o fecha de carga.

**Impacto:** limitada deteccion de alteraciones, deduplicacion y reconstruccion
del historial.

**Recomendacion futura:** evaluar metadata adicional solo mediante cambio de
esquema aprobado y compatible.

### Bajo - Validacion de MIME no equivale a inspeccion completa de contenido

**Evidencia:** los controllers validan `mimetypes:application/pdf` y extension
de entrada, pero no se comprobó antivirus, escaneo de contenido o politica de
PDF malicioso.

**Impacto:** riesgo residual de archivos peligrosos si la infraestructura los
abre o sirve sin controles adicionales.

**Recomendacion futura:** definir controles de seguridad de archivos según el
entorno y el nivel de exposición, sin asumir que sean necesarios sin evidencia.

## Integridad y escalabilidad

### Integridad

La base de datos y el almacenamiento fisico no participan en una misma
transaccion atomica. El controller intenta compensar fallos funcionales, pero
una excepcion inesperada puede dejar estados parciales. Tampoco se encontro
versionado ni proceso automatico para huerfanos, rutas rotas o reemplazos.

### Escalabilidad

El crecimiento de PDFs ocurre en un disco local organizado por mantenimiento o
codigo activo. No se comprobo cuota, retencion, archivado, almacenamiento
externo, deduplicacion ni monitoreo de espacio. Con mas ejecuciones, la
ausencia de metadata y reconciliacion dificultaria costos, limpieza y auditoria.

## Pruebas faltantes

- Rechazo de extension/MIME/tamano no permitido.
- Carga correcta y asociacion con el mantenimiento esperado.
- Descarga autorizada y rechazo de usuario no autenticado.
- Verificacion de que no se puede acceder a otro registro por ID sin permiso.
- Nombre original con rutas, caracteres especiales y nombres repetidos.
- Dos cargas consecutivas sin perdida o asociacion incorrecta.
- Fallo de persistencia despues de `storeAs` y limpieza del archivo.
- Excepcion inesperada despues de almacenar el archivo.
- Registro con ruta inexistente y archivo sin registro.
- Mantenimiento ejecutado con evidencia obligatoria ausente.
- Reemplazo de evidencia y conservacion de la referencia correcta.
- Falta de `storage:link` y respuesta segura al archivo no disponible.
- Conservacion de observaciones humanas y evento tecnico asociado.
- Pruebas de volumen y espacio si se define un objetivo de crecimiento.

## Plan de endurecimiento

## Auditoria focalizada por componente

### PanelController

**Estado actual comprobado:** `marcarProgramadoComoHecho` valida el PDF,
construye `constancias/{mantenimiento_id}/{nombre}` y lo guarda en el disco
`public` antes de llamar al repository. `gestionarEstadoMantenimiento` repite
el patron para el destino ejecutado, usando
`constancias/gestion/{codigo_activo}/{nombre}`. Ambos usan `basename()` y
`mimetypes:application/pdf`, con limite de 5120 KB.

**Riesgo:** la carga y la persistencia legacy estan coordinadas desde el
controller y no comparten una transaccion de filesystem. La compensacion
actual elimina el archivo ante ciertos resultados `ok=false`, pero no es una
garantia documentada para excepciones inesperadas.

**Pruebas requeridas:** rechazo de archivo invalido, limite de tamano, nombre
con separadores, fallo funcional posterior a `storeAs` y excepcion inesperada.

### MantenimientoRepository y servicios de estado

**Estado actual comprobado:** el repository recibe la ruta ya almacenada,
actualiza `mantenimientos.evidencia_pdf` y, en el flujo vigente de ejecucion,
coordina el cambio de estado, evento tecnico y datos de evidencia dentro de
`DB::transaction`. El servicio de gestion de estados recibe una ruta opcional
cuando el destino es ejecutado y delega la operacion al repository/servicio
correspondiente.

**Riesgo:** una transaccion de BD no puede deshacer por si sola un archivo ya
escrito. Puede existir un archivo huerfano si falla una excepcion despues de
la escritura y antes de la compensacion del controller.

**Pruebas requeridas:** atomicidad de BD y evento, limpieza ante fallo de
persistencia, conservacion de observaciones humanas y asociacion con el ID
correcto.

### Consulta y descarga

**Estado actual comprobado:** `evidenciaPdf` obtiene la ruta por ID, verifica
que no este vacia y que el archivo exista en el disco `public`; luego responde
inline o descarga. La ruta esta protegida por `legacy.auth` y
`can:access-panel`.

**Riesgo:** no se comprobo Policy, Gate por mantenimiento ni validacion de que
el usuario autorizado pueda ver ese registro concreto. Ademas, el disco
publico permite una posible exposicion directa si el enlace simbolico y el
servidor lo permiten.

**Pruebas requeridas:** acceso no autenticado, acceso sin permiso de panel,
acceso cruzado entre IDs y comportamiento con ruta registrada pero archivo
faltante.

### Auditoria operativa

**Estado actual comprobado:** `evidencias:auditar` compara referencias de
`mantenimientos.evidencia_pdf` contra los archivos del disco `public` en modo
solo lectura y reporta existentes, faltantes y huerfanos. La auditoria previa
encontro 14 referencias, 12 archivos existentes, 2 faltantes y 3 archivos sin
referencia, incluyendo un archivo de control del almacenamiento.

**Riesgo:** el escaneo fisico puede clasificar archivos auxiliares como
huerfanos y no constituye por si mismo una politica segura de eliminacion.
Los resultados conocidos tambien muestran que la BD y los PDFs no tienen una
recuperacion conjunta documentada.

**Pruebas requeridas:** comando sin escrituras, rutas faltantes, PDFs
huerfanos, archivos auxiliares y almacenamiento vacio.

## Cambio minimo de mayor impacto

El cambio minimo con mayor impacto seria formalizar una unica garantia de
compensacion alrededor de cada flujo que escribe un PDF: conservar la ruta
creada, ejecutar la operacion de negocio y eliminarla ante cualquier fallo
capturable, sin borrar el archivo anterior ni truncar datos. Debe acompanarse
con pruebas de excepcion y con la auditoria de correspondencia en modo lectura.

Esto reduce la generacion de huerfanos sin cambiar el esquema legacy ni mover
todavia el almacenamiento. No resuelve por si solo la exposicion publica ni la
autorizacion por registro; esas son decisiones separadas de seguridad.

## Matriz de cambios recomendados

| Prioridad | Archivo afectado | Cambio recomendado | Impacto | Riesgo | Pruebas necesarias |
| --- | --- | --- | --- | --- | --- |
| P0 | `PanelController.php` | Garantizar compensacion ante todos los fallos posteriores a la escritura | Reduce huerfanos y estados parciales observables | Medio: tocar ambos flujos puede cambiar contratos de error | Fallo funcional, excepcion inesperada, archivo anterior intacto |
| P0 | `PanelController.php`, `routes/web.php` | Verificar autorizacion efectiva de consulta y descarga antes de produccion | Reduce exposicion de evidencias | Alto si se cambia sin conocer roles reales | No autenticado, sin permiso, acceso cruzado por ID |
| P0 | `tests/Feature/PanelActionFlowTest.php` | Cubrir validacion, asociacion, fallo y descarga | Congela el comportamiento actual antes de endurecer | Bajo | Casos de carga, descarga y rollback |
| P0 | `app/Console/Commands/AuditarEvidenciasCommand.php` | Mantener auditoria en solo lectura y distinguir auxiliares | Permite detectar inconsistencias sin destruir informacion | Bajo | BD/disco consistentes, faltantes y huerfanos |
| P1 | `config/filesystems.php`, controlador y documentacion operativa | Evaluar disco privado o politica formal de exposicion publica | Reduce acceso directo | Alto: requiere coordinar servidor y rutas historicas | Acceso autenticado, enlaces historicos, descarga |
| P1 | `MantenimientoRepository.php` | Definir contrato comun de ruta y metadata sin cambiar esquema todavia | Reduce duplicacion y asociaciones ambiguas | Medio: repository grande y legacy | Dos cargas, nombres repetidos, estados concurrentes |
| P2 | Esquema y almacenamiento | Metadata, hash, versionado, retencion y almacenamiento externo | Mejora auditoria y escalabilidad | Alto: requiere decision de esquema y operacion | Integridad, restauracion, volumen |


### P0 - Antes de produccion

- Verificar autorizacion real sobre descarga y exposicion directa de
  `public/storage`.
- Ejecutar auditoria de correspondencia entre `evidencia_pdf` y archivos, sin
  limpieza automatica.
- Probar backup/restauracion incluyendo evidencias y base de datos.
- Confirmar atomicidad logica de cada flujo y compensacion ante errores.
- Ejecutar pruebas de validacion, acceso, asociacion y fallos parciales.
- Confirmar limites de almacenamiento, permisos del directorio y configuracion
  de servidor.

### P1 - Despues de estabilizar

- Definir disco privado o una politica formal de exposicion publica.
- Implementar autorizacion por registro si aplica al negocio.
- Generar nombres unicos y preservar nombre original como metadata.
- Crear reconciliacion de archivos y registros con modo reporte antes de borrar.
- Documentar retencion, reemplazo, restauracion y respuesta a archivos rotos.
- Mejorar compensacion cuando la escritura fisica y la base no puedan ser
  atomicas.

### P2 - Mejoras futuras

- Metadata estructurada y hash de evidencias.
- Almacenamiento externo o archivado medido por volumen.
- Antivirus/escaneo y pipeline de archivos si el riesgo lo justifica.
- Deduplicacion, cuotas, metricas y alertas de capacidad.
- Versionado formal de evidencias si el negocio lo requiere.

## Documentos revisados

- `AGENTS.md`
- `docs/security/file-lifecycle-audit.md`
- `docs/security/security-audit.md`
- `docs/development/product-stabilization-plan.md`
- `docs/development/stabilization-progress.md`
- `config/filesystems.php`
- `app/Http/Controllers/PanelController.php`
- `app/Services/Legacy/MantenimientoRepository.php`
- `routes/web.php`
- vistas Blade de carga y consulta de evidencias

## Validaciones realizadas

- Busqueda estatica de carga, validacion, asociacion, descarga y eliminacion.
- Contraste con auditorias de seguridad, ciclo de vida y estabilizacion.
- No se inspeccionaron archivos PDF reales ni se ejecutaron procesos de limpieza.
- No hubo cambios de codigo, BD, migraciones ni pruebas.
- No se ejecutaron `git add`, `git commit` ni `git push`.

# Revision de `PanelController`

## Alcance

Revision de solo lectura de `app/Http/Controllers/PanelController.php`, sus
dependencias directas y la documentacion tecnica existente. No se realizaron
refactorizaciones ni cambios de codigo, base de datos, migraciones o pruebas.

## Estado actual comprobado

### Responsabilidades actuales

`PanelController` coordina practicamente todo el panel administrativo:

- Redireccion inicial de `/panel` y conservacion de parametros de contexto.
- Renderizado del Dashboard/reporte general y sus detalles paginados.
- Renderizado de notificaciones vencidas, proximas y del dia.
- Renderizado de proximos, programados y ejecutados.
- Descarga o visualizacion de evidencias PDF.
- Listado, creacion, actualizacion, cambio de estado y eliminacion de usuarios.
- Listado y creacion de tecnicos.
- Busqueda y resolucion de usuarios para configuracion.
- Previsualizacion y ejecucion de cambios de estado.
- Delegacion de la accion legacy de reversion.
- Validacion de solicitudes, carga/eliminacion compensatoria de archivos y
  mensajes flash.

### Metodos y proposito

| Metodo | Proposito actual |
|---|---|
| `__construct` | Inyecta `PanelNavigationService` y agrega `can:access-panel`. |
| `index` | Resuelve apartado legacy y redirige al dashboard conservando query. |
| `reportes` | Carga resumen semestral y actividad reciente. |
| `reportesPendientes` | Pagina y prepara detalle de pendientes del Dashboard. |
| `reportesBaseFase` | Pagina y prepara equipos a gestionar. |
| `reportesEquiposNuevos` | Pagina, filtra y presenta no exigibles/equipos nuevos. |
| `notificacionesVencidos` | Obtiene datos del servicio y adapta filas a la vista. |
| `notificacionesProximos` | Obtiene datos del servicio y adapta filas a la vista. |
| `notificacionesHoy` | Obtiene datos del servicio y adapta filas a la vista. |
| `configuracion` | Resuelve busqueda, analisis de reversion y tecnicos para la vista. |
| `buscarUsuarioConfiguracion` | Valida busqueda y devuelve HTML parcial en JSON. |
| `proximos` | Lista mantenimientos pendientes de programar. |
| `programados` | Lista mantenimientos programados. |
| `hechos` | Lista mantenimientos realizados. |
| `evidenciaPdf` | Verifica ruta en disco `public` y sirve/descarga el PDF. |
| `usuarios` | Prepara listado, filtros y datos auxiliares de usuarios. |
| `crearUsuario` | Valida y delega la creacion de usuario. |
| `cambiarEstadoUsuario` | Valida y delega el cambio de estado de usuario. |
| `actualizarUsuario` | Valida y delega la actualizacion de usuario. |
| `eliminarUsuario` | Valida y delega la eliminacion de usuario. |
| `programarProximo` | Valida fecha, dia habil y delega programacion. |
| `marcarProgramadoComoHecho` | Valida datos/PDF y delega ejecucion. |
| `crearTecnico` | Valida y delega creacion de tecnico. |
| `previsualizarGestionEstado` | Valida, resuelve usuario, almacena PDF y ejecuta el cambio actual. |
| `gestionarEstadoMantenimiento` | Valida y delega gestion de estado con preview. |
| `revertirUltimaAccion` | Alias/delegacion hacia `gestionarEstadoMantenimiento`. |
| `resolverCodigoActivoConfiguracion` | Resuelve codigo forzado o busqueda textual. |
| `debeBuscarUsuarioConfiguracion` | Decide si una busqueda tiene longitud minima. |
| `resolverCoincidenciaAutomaticaConfiguracion` | Selecciona coincidencia exacta por codigo o nombre. |
| `vistaNotificaciones` | Fabrica los datos comunes de la vista de notificaciones. |
| `formatearFechaTabla` | Formatea fechas para tablas y conserva valor si no puede parsear. |
| `descripcionPeriodoGestion` | Construye descripcion textual del semestre. |
| `redirectConContexto` | Redirige a URL interna segura o ruta fallback. |
| `esUrlInternaSegura` | Valida que `return_url` sea una URL interna. |

### Dependencias directas

El controller usa `MantenimientoRepository`, `UsuarioRepository`,
`NotificationSummaryService`, `NoExigibleReasonService`,
`ReversionEstadoMantenimientoService`, `PeriodicidadMantenimientoService`,
`OrigenEquipoPresenter`, `PanelNavigationService` y `SemestreService`.

Tambien depende de `Request`, validacion Laravel, `Storage` para evidencias,
`Carbon`, autenticacion de Laravel, sesion `usuario_autenticado` y las vistas
Blade del panel. Las rutas estan protegidas por `legacy.auth` y
`can:access-panel`, ademas del middleware aplicado en el constructor.

## Logica que debe permanecer en Controller

Como coordinacion HTTP, es razonable mantener en el controller:

- Recibir `Request` y devolver `View`, `RedirectResponse` o `JsonResponse`.
- Invocar validacion de entrada y conservar nombres de campos actuales.
- Elegir la vista y preparar un DTO/arreglo de presentacion pequeno.
- Traducir resultado de servicio a mensaje flash y redireccion.
- Conservar filtros, paginacion y contexto de navegacion recibidos.
- Delegar la autorizacion al middleware y no duplicarla aqui.
- Aplicar controles estrictamente HTTP, como validar `return_url` interna.

Estas responsabilidades no deben convertirse en consultas ni reglas de
negocio dentro de Blade.

## Logica candidata a Service

### Estado actual

Los servicios ya existentes contienen buena parte de la logica de negocio:
periodos, notificaciones, reversiones y transiciones de estado.

### Candidatos futuros

- Un servicio de caso de uso para cada accion operativa completa: programar,
  marcar como hecho y gestionar/revertir estado. Debe coordinar transaccion,
  evento y evidencia sin duplicar reglas.
- Un servicio de preparacion de Dashboard que agrupe resumen, actividad y
  detalles, si la pantalla continua creciendo.
- Un servicio de presentacion de notificaciones para evitar que el controller
  repita tres `array_map` casi equivalentes.
- Un servicio de evidencia que centralice validacion de ruta, almacenamiento,
  respuesta y compensacion de archivos, respetando `Storage::disk('public')`.
- Un objeto o servicio de contexto del panel para pagina, tamano, busqueda,
  periodo y retorno.

Estas son propuestas, no implementaciones actuales. Deben hacerse por pasos y
con pruebas antes de mover logica.

## Logica candidata a Repository

### Estado actual

Las consultas legacy de usuarios y mantenimientos ya estan concentradas en
`app/Services/Legacy/MantenimientoRepository.php` y
`UsuarioRepository.php`. El controller no deberia absorber nuevas consultas.

### Candidatos futuros

- Cualquier consulta que aun aparezca implicitamente en adaptadores de
  presentacion o que deba combinar varios listados del Dashboard.
- Consultas especificas de actividad, pendientes, base de fase y no exigibles
  si se requiere separarlas por agregado sin duplicar SQL.
- Consultas de evidencia asociadas a mantenimiento, manteniendo control de
  acceso fuera de la vista.

La extraccion no debe crear repositorios paralelos con la misma consulta. Debe
reutilizar los metodos legacy y conservar nombres/semantica hasta contar con
pruebas equivalentes.

## Problemas encontrados

### Alto - Controller con responsabilidades demasiado amplias

**Estado actual:** un solo archivo concentra lectura de ocho areas funcionales,
acciones de escritura, archivos, navegacion y adaptacion de vistas.

**Riesgo:** cambios en un modulo pueden producir regresiones en otro; la
prueba y revision de cada accion se vuelve costosa.

**Mejora futura:** separar por casos de uso o controllers enfocados, empezando
por flujos con transacciones y archivos, sin cambiar rutas publicas.

### Medio - Duplicacion de paginacion y contexto

**Estado actual:** varios metodos repiten lectura de pagina/tamano, limites
permitidos, calculo de total de paginas, offset y armado de `periodoQuery`.

**Riesgo:** reglas divergentes entre modulos y mayor probabilidad de errores al
ajustar filtros responsive o parametros de periodo.

**Mejora futura:** extraer un objeto/servicio de consulta del panel y cubrirlo
con pruebas de limites, manteniendo la interfaz de las rutas.

### Medio - Duplicacion de carga y limpieza de PDF

**Estado actual:** `marcarProgramadoComoHecho`,
`previsualizarGestionEstado` y `gestionarEstadoMantenimiento` repiten nombre,
`storeAs` en disco `public` y eliminacion ante resultado fallido.

**Riesgo:** diferencias futuras en rutas, nombres, excepciones o limpieza
pueden generar archivos huerfanos o registros sin evidencia.

**Mejora futura:** caso de uso/servicio de evidencia con compensacion
centralizada y pruebas de fallo; no moverlo sin revisar las transacciones.

### Medio - Validacion de negocio mezclada con HTTP

**Estado actual:** el controller valida formato, fechas, tecnico y PDF, y
ademas decide mensajes y contexto. Parte de las reglas de transicion queda en
servicios, pero hay validaciones previas de dominio en el controller.

**Riesgo:** las mismas reglas pueden variar entre endpoints y ser dificiles de
reutilizar en comandos o procesos futuros.

**Mejora futura:** Form Requests para forma de entrada y servicios para reglas
de dominio, solo despues de fijar comportamiento con Feature tests.

### Bajo - Presentacion HTML desde endpoint JSON

**Estado actual:** `buscarUsuarioConfiguracion` renderiza el parcial Blade y lo
devuelve dentro de JSON.

**Riesgo:** acopla el contrato AJAX a una vista concreta y dificulta migrar
presentacion, aunque no es por si mismo un fallo de seguridad.

**Mejora futura:** mantener el contrato actual por compatibilidad y evaluar un
payload de datos versionado cuando exista una necesidad real.

### Bajo - Metodo legado de alias y codigo inalcanzable aparente

**Estado actual:** `revertirUltimaAccion` delega a un metodo de gestion de
estado; en el flujo de previsualizacion se observa un retorno posterior al
retorno principal, por lo que ese bloque no se ejecuta en la ruta normal.

**Riesgo:** aumenta ambiguedad sobre contratos antiguos y puede confundir
futuras correcciones. No se elimina porque requiere revisar rutas y pruebas.

**Mejora futura:** confirmar referencias, documentar compatibilidad y limpiar
solo con pruebas y autorizacion explicita.

## Propuesta futura de separacion

1. Congelar contratos actuales con Feature tests por endpoint y por flujo.
2. Mantener `PanelController` como adaptador HTTP delgado.
3. Extraer primero un caso de uso para evidencia/transicion, porque combina
   cambios de estado, archivos y auditoria.
4. Extraer despues preparadores de Dashboard y notificaciones, reutilizando
   repositorios existentes.
5. Centralizar contexto/paginacion sin cambiar nombres de query ni rutas.
6. Separar controllers por area solo cuando las pruebas demuestren que el
   comportamiento no cambia.
7. Actualizar documentacion y, si la separacion cambia una decision
   arquitectonica, registrar un ADR.

## Riesgos de una refactorizacion futura

- Cambiar el orden de validacion puede alterar mensajes y pruebas existentes.
- Mover almacenamiento fuera del controller puede romper la compensacion de
  archivos si no se conserva la transaccion logica.
- Cambiar el armado de contexto puede perder filtros, semestre o `return_url`.
- Separar rutas sin alias puede romper enlaces del sidebar y pruebas de
  navegacion.
- Duplicar SQL durante una extraccion puede producir indicadores inconsistentes.
- Reubicar reversiones sin mantener compatibilidad legacy puede afectar datos
  historicos y eventos.

## Referencias y archivos revisados

- `AGENTS.md`
- `app/Http/Controllers/PanelController.php`
- `app/Http/Controllers/AuthController.php`
- `app/Http/Middleware/EnsureLegacyAuth.php`
- `app/Services/Legacy/MantenimientoRepository.php`
- `app/Services/Legacy/UsuarioRepository.php`
- `app/Services/Legacy/NotificationSummaryService.php`
- `app/Services/Legacy/ReversionEstadoMantenimientoService.php`
- `app/Services/Legacy/GestionEstadoMantenimientoService.php`
- `app/Services/Legacy/PanelNavigationService.php`
- `app/Services/Legacy/PeriodicidadMantenimientoService.php`
- `app/Services/SemestreService.php`
- `routes/web.php`
- `docs/architecture/overview.md`
- `docs/development/code-quality.md`
- `docs/development/architecture-review.md`
- `docs/development/technical-debt.md`
- documentacion de `docs/security/`

## Validaciones realizadas

- Lectura de estructura, dependencias, firmas de metodos y llamadas relevantes.
- Busqueda de validaciones, repositorios, servicios, almacenamiento,
  redirecciones y respuestas del controller.
- No se modificaron archivos productivos, migraciones, base de datos ni pruebas.
- No se ejecutaron `git add`, `git commit` ni `git push`.

# Cobertura Feature actual

## Autenticacion y autorizacion

- `AuthSecurityTest`: login con hash seguro, credenciales incorrectas, hash plano, usuario inactivo, acceso administrativo y rechazo de usuario no autorizado.
- `PanelAuthorizationTest`: acceso de administrador y bloqueo de acciones para usuarios sin permisos.
- `ExampleTest`: redirecciones, contexto de parametros, sesiones, locale y escenarios iniciales del panel.

## Panel y mantenimiento

- `PanelActionFlowTest`: vistas del panel, usuarios, programacion, ejecucion, PDFs, reversiones, transacciones, busquedas y acciones CRUD.
- `EstadoMantenimientoVigentePanelTest`: etiquetas y estados mostrados en usuarios, proximos, programados y hechos.
- `ConfiguracionLayoutTest`: configuracion, busqueda, tecnicos y destinos de estado.
- `MantenimientoRepositoryMaterializacionPendienteTest`: reutilizacion, creacion, limites, duplicados y rollback de pendientes.
- `GestionEstadoMantenimientoServiceTest`: transiciones entre estados, restricciones, confirmaciones actuales, automaticos y periodicidad.

## Dashboard, reportes y notificaciones

- `DashboardIndicadoresAuditablesTest`: indicadores, enlaces auditables, grafico, equipos a gestionar, no exigibles y pendientes.
- `ReportesEquiposNuevosTest`: reclasificacion y totales del reporte de equipos nuevos.
- `NotificationSummaryTopbarTest`: conteos reales, presencia en pantallas e exclusion de ejecutados y cancelados.

## Infraestructura y comandos

- `MantenimientoEventosInfrastructureTest`: existencia, estructura y registro basico de eventos.
- `BaseDatosAuditoriaCommandTest`: lecturas, confirmacion de base, transacciones y errores del comando.
- `Bd3SnapshotProtectionTest`: base aislada y generacion de snapshot.
- `MantenimientoResumenesCommandTest`: auditoria y reconstruccion de resumenes.

## Limitaciones observadas

La cobertura Feature protege los flujos principales conocidos, pero no sustituye una prueba de navegador responsive, una prueba de carga ni una auditoria de datos productivos. Los nombres y escenarios descritos corresponden a los archivos existentes y no implican cobertura de comportamientos no listados.

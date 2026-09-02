# Esquema legacy identificado

Este documento resume solo tablas y campos comprobables mediante consultas, migraciones y servicios actuales. No sustituye una inspeccion del esquema real.

## `usuarios_sistema`

Proposito: credenciales y autorizacion de usuarios del sistema.

Componentes: `User`, `AuthController`, `LegacyAuthBridge`, `AuthServiceProvider` y pruebas de autenticacion.

Campos conocidos: `id`, `nombre`, `correo`, `password_hash`, `rol`, `estado`, `ultimo_acceso`.

Restricciones: `estado = 1` permite login; el rol debe ser uno de los roles administrativos aceptados.

## `usuarios`

Proposito: inventario de usuarios o equipos sujetos a mantenimiento.

Componentes: `UsuarioRepository`, `MantenimientoRepository`, servicios de resumen, estado vigente, Dashboard y pruebas del panel.

Campos conocidos: `id`, `nombre`, `codigo_activo`, `serial_equipo`, `tipo_equipo`, `area_id`, `estado_usuario`, fechas de mantenimiento y campos de resumen derivados.

Restricciones: `estado_usuario` se utiliza para distinguir activos e inactivos; el codigo activo participa en busquedas y reversiones.

## `mantenimientos`

Proposito: historial y estado operativo de mantenimientos.

Componentes: `MantenimientoRepository`, `GestionEstadoMantenimientoService`, `ReversionEstadoMantenimientoService`, `UsuarioRepository`, `PanelController`, notificaciones, reportes y pruebas Feature/Unit.

Campos conocidos: `id`, `usuario_id`, `fecha_programada`, `hora_programada`, `estado`, `fecha_ejecucion`, `tecnico_responsable`, `observaciones`, `evidencia_pdf`, `created_at`, `updated_at`.

Restricciones: los estados y aliases legacy se resuelven mediante `MantenimientoEstados`; fechas y periodicidad dependen del semestre y de servicios especializados.

## `mantenimiento_eventos`

Proposito: bitacora tecnica estructurada de transiciones.

Componentes: `MantenimientoEventoRepository`, flujos de programacion, ejecucion y reversion, y `MantenimientoEventosInfrastructureTest`.

Campos conocidos: `id`, `mantenimiento_id`, `tipo_evento`, `descripcion`, `datos_antes`, `datos_despues`, `usuario_sistema_id`, `ip`, `created_at`.

Restricciones: los datos antes/despues se almacenan como JSON serializado; la compatibilidad historica exige conservar fallbacks textuales en ciertos flujos.

## `areas`

Proposito: catalogo de areas.

Componentes: consultas de `UsuarioRepository`, reportes y vistas de usuarios.

Campo conocido: `id` y nombre de area se utilizan en joins. El resto del esquema no se documenta aqui por no estar confirmado de forma uniforme.

## `tecnicos`

Proposito: tecnicos disponibles y responsables de mantenimientos.

Componentes: `UsuarioRepository`, `MantenimientoRepository`, configuracion, ejecucion y pruebas.

Campos conocidos: `id`, `nombre`, `estado`, `created_at`, `updated_at`.

## Otras tablas

El codigo tambien referencia `configuracion_sistema` para configuracion de periodicidad y auditorias. No se documentan aqui tablas de empresas u otras estructuras no confirmadas como parte del flujo actual.

## Advertencia

El esquema es legacy. Antes de agregar columnas, indices o foreign keys debe verificarse la base activa, sus huerfanos, tipos reales y migraciones ya aplicadas.

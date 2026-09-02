# Datos sensibles y proteccion

## Archivos sensibles

- `.env` y `.env.testing` pueden contener credenciales y configuracion local.
- `.env.bak`, dumps SQL y archivos de rescate pueden contener datos reales o credenciales.
- `storage/logs` puede contener errores, rutas, identificadores y contexto de solicitudes.
- El almacenamiento publico puede contener evidencias PDF de mantenimientos.

## Informacion que no debe exponerse

- Contrasenas, `password_hash`, tokens y claves de aplicacion.
- Credenciales de base de datos.
- Dumps completos o datos reales de usuarios y mantenimientos.
- Evidencias PDF fuera de los controles previstos.
- IPs, trazas y datos personales innecesarios en respuestas o logs.

## Practicas existentes

- `User` oculta `password_hash` y `remember_token` en serializacion.
- El login usa `Hash::check` y rechaza hashes no seguros.
- Las rutas privadas usan autenticacion y autorizacion.
- Los formularios POST usan CSRF de Laravel.
- Las evidencias usan `Storage::disk('public')` y el proyecto espera `storage:link`.
- Existen comandos de auditoria y snapshot con validaciones para evitar operar sobre una base no confirmada.

## Reglas operativas

- No abrir ni reproducir secretos de archivos de entorno.
- No publicar backups, dumps ni logs.
- No ejecutar comandos destructivos sobre datos o carpetas de rescate.
- Auditar y confirmar la base antes de comandos que inspeccionen o modifiquen datos.
- Redactar o minimizar datos sensibles en diagnósticos y reportes.

## Pendientes

No se evidencio una politica de retencion o redaccion automatica de logs. Tampoco se documenta un control de acceso adicional para cada PDF; cualquier mejora debe analizarse separadamente.

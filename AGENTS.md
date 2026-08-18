# AGENTS.md

## Proyecto
Aplicación web en Laravel 11 para gestión de mantenimientos con integración legacy. La autenticación real usa la tabla `usuarios_sistema` y el panel vive detrás de `legacy.auth` y `can:access-panel`.

## Estructura clave
- `app/Http/Controllers`: `AuthController` y `PanelController`.
- `app/Services/Legacy`: repositorios, normalización y lógica de negocio sobre la base legacy.
- `app/Services/Diagnostics`: comandos y auditorías de solo lectura.
- `app/Models/User.php`: modelo autenticable ligado a `usuarios_sistema`.
- `routes/web.php`: rutas públicas, panel y acciones sensibles.
- `resources/views/panel`: vistas del panel y parciales asociados.
- `database/migrations`: ajustes de compatibilidad sobre el esquema existente.
- `tests/Feature` y `tests/Unit`: pruebas de autorización, flujos del panel, comandos y servicios.

## Reglas de trabajo
- Haz cambios mínimos y localizados; evita refactors amplios si no son necesarios.
- Respeta la arquitectura legacy: usuarios, mantenimientos, áreas y técnicos ya existen en la base.
- En autenticación, conserva `usuarios_sistema`, `password_hash`, `estado`, `rol` y la sesión `usuario_autenticado`.
- En vistas del panel, actualiza también los parciales relacionados y las pruebas que validan texto renderizado.
- Para archivos PDF de evidencia, usa `Storage::disk('public')` y conserva la expectativa de `storage:link`.
- No cambies migraciones, seeders o esquema salvo que la tarea lo requiera y esté claramente justificada.

## Protección de trabajo existente
- No sobrescribas cambios del usuario.
- No elimines ni descartes archivos, dumps, backups ni carpetas de rescate.
- No abras ni reproduzcas secretos de `.env`, `.env.testing` o copias `.bak`.
- No hagas `git add`, commit ni push sin autorización expresa.
- No uses comandos destructivos como `git reset --hard`, `git checkout --`, `rm -rf` o equivalentes.

## Comandos confirmados
- Instalar PHP: `composer install`
- Instalar frontend: `npm install`
- Ejecutar tests: `php artisan test`
- Ver rutas: `php artisan route:list`
- Limpiar cachés: `php artisan config:clear`, `php artisan cache:clear`, `php artisan view:clear`
- Enlazar storage: `php artisan storage:link`
- Frontend: `npm run dev`, `npm run watch`, `npm run prod`
- Levantar local: `php artisan serve`
- Comandos del proyecto:
  - `php artisan mantenimientos:resumenes [--usuario=] [--all] [--chunk=] [--apply]`
  - `php artisan mantenimientos:auditoria --database=<nombre>`
  - `php artisan bd3:snapshot [--output=] [--force]`
  - `php artisan usuarios:corregir-nombres [--dry-run]`

## Validación mínima
- Después de cambiar código, ejecuta la prueba más cercana al área tocada.
- Si cambias autenticación, rutas, panel o servicios compartidos, corre la suite relevante de `tests/Feature`.
- Si el cambio afecta lógica compartida, modelos o servicios, añade o ejecuta pruebas de `tests/Unit`.
- Corre `php artisan test` completo cuando el cambio toque auth, panel, migraciones, comandos compartidos o varias capas a la vez.
- Confirma siempre que no queden fallos en la salida de tests antes de cerrar la tarea.

## Cuándo detenerse
- Si la tarea implica borrar datos, tocar producción o modificar la base real, detente y consulta.
- Si necesitas una migración, seeder o rollback, pide autorización antes de ejecutarlo.
- Si una validación depende de credenciales, secretos o datos reales, no los expongas y detente si faltan.
- Si aparece un cambio no relacionado en el árbol, no lo toques sin indicación explícita.

## Criterio de terminado
- Solo quedan cambios intencionales en el alcance pedido.
- Las pruebas relevantes pasan.
- No se modificaron datos reales ni archivos sensibles fuera del alcance.
- El informe final es breve e incluye qué se cambió, qué se validó y qué quedó pendiente, si aplica.

## Diseño UI
- Mantener consistencia visual entre todos los módulos.
- Antes de crear nuevos componentes visuales, buscar patrones existentes.
- No crear estilos aislados para un único modal si existe un componente equivalente.
- Respetar tipografía, colores, botones, tarjetas y espaciados definidos en la aplicación.
- Los modales nuevos deben seguir el patrón visual del modal de referencia del sistema.
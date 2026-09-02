# Autenticacion actual

## Fuente de usuarios

La autenticacion utiliza el modelo `App\Models\User`, configurado con la tabla legacy `usuarios_sistema`. El campo de contrasena real es `password_hash`; el modelo adapta este campo mediante `getAuthPassword()`.

## Flujo de login

`AuthController@store` valida `correo` y `password`, normaliza el correo a minusculas y consulta `usuarios_sistema`. Solo continua si el usuario existe, tiene `estado = 1` y `password_hash` contiene un hash reconocido por PHP. La contrasena se verifica con `Hash::check`.

Despues de autenticar, Laravel regenera la sesion y se guarda `usuario_autenticado` mediante `LegacyAuthBridge`. Tambien se intenta actualizar `ultimo_acceso`; un fallo de esa actualizacion se reporta sin cancelar el login.

## Logout

`AuthController@destroy` cierra Laravel Auth, elimina `usuario_autenticado`, invalida la sesion y regenera el token CSRF.

## Middleware y rutas

`EnsureLegacyAuth` sincroniza la sesion legacy con el usuario autenticado de Laravel. Si no puede reconstruir la autenticacion, redirige al login.

Las rutas privadas del panel usan `legacy.auth` y `can:access-panel`. El formulario de login usa limites por correo/IP definidos en `AuthServiceProvider`.

## Componentes relacionados

- `app/Models/User.php`
- `app/Http/Controllers/AuthController.php`
- `app/Http/Middleware/EnsureLegacyAuth.php`
- `app/Support/LegacyAuthBridge.php`
- `app/Providers/AuthServiceProvider.php`
- `routes/web.php`

## Riesgos y restricciones

- `usuarios_sistema` es una dependencia legacy y no debe sustituirse sin un plan aprobado.
- No debe exponerse `password_hash` en respuestas, vistas ni logs.
- El usuario debe permanecer activo para iniciar sesion.
- Los cambios deben conservar la sesion `usuario_autenticado` y la regeneracion de sesion y CSRF.
- La politica actual rechaza contrasenas planas; esto esta protegido por pruebas.
- No esta documentado en el codigo un mecanismo de recuperacion de contrasena; no se debe asumir que existe.

# Autorizacion actual

## Mecanismo

El acceso al panel se controla con el Gate `access-panel`, definido en `AuthServiceProvider`. El Gate recibe `App\Models\User` y acepta los roles normalizados:

- `admin`
- `administrador`
- `superadmin`

Las rutas del panel aplican simultaneamente `legacy.auth` y `can:access-panel`.

## Alcance por modulo

El codigo actual protege con el mismo Gate las rutas de:

- Dashboard o reportes.
- Usuarios.
- Proximos.
- Programados.
- Hechos.
- Configuracion.
- Notificaciones.
- Evidencias y acciones de mantenimiento.

No se identificaron Gates separados por modulo ni una estructura activa de Policies para estas operaciones.

## Validacion

Primero se valida la autenticacion mediante `EnsureLegacyAuth`. Despues Laravel ejecuta `can:access-panel`. Las acciones POST permanecen dentro del grupo protegido y usan la proteccion CSRF de Laravel.

## Limitaciones conocidas

- La autorizacion es de nivel panel; no existe evidencia de permisos granulares por accion o modulo.
- El rol se lee desde la tabla legacy y se normaliza a minusculas.
- El documento no asume permisos adicionales que no esten representados por Gates, middleware o controladores.
- Cualquier cambio de roles debe cubrir `AuthSecurityTest` y `PanelAuthorizationTest`.

# Mantenimientos Laravel

Aplicacion web en Laravel para gestionar mantenimientos de equipos con enfoque legacy. El sistema autentica usuarios desde la tabla `usuarios_sistema` y administra el ciclo de mantenimiento desde un panel privado con secciones de reportes, proximos, programados, hechos, usuarios y configuracion.

## Resumen del proyecto

Este proyecto no usa el modelo `users` de Laravel para iniciar sesion en produccion. La autenticacion real se hace contra una base legacy y el panel opera sobre tablas ya existentes del sistema:

- `usuarios_sistema`: credenciales y roles de acceso.
- `usuarios`: inventario de equipos o activos asociados a mantenimiento.
- `mantenimientos`: historial y estado de cada mantenimiento.
- `areas`: catalogo de areas.
- `tecnicos`: responsables o tecnicos asignados.

La aplicacion tambien conserva compatibilidad con rutas legacy mediante `?apartado=...`.

## Requisitos

- PHP `^8.1`
- Composer
- Node.js y npm
- MySQL o MariaDB
- Extensiones PHP comunes para Laravel 11
- XAMPP o un entorno similar, si vas a ejecutarlo en local

## Stack

- Laravel `^11.0`
- Sanctum `^4.0`
- PHPUnit `^10.5`
- Laravel Mix `^6`
- Guzzle `^7`

## Funcionalidades principales

- Login y logout con bloqueo por intentos.
- Acceso al panel solo para usuarios con rol admin.
- Vista de reportes semestrales.
- Lista de proximos mantenimientos con paginacion y filtros.
- Lista de mantenimientos programados.
- Lista de mantenimientos realizados.
- CRUD de usuarios del inventario.
- Registro de tecnicos.
- Carga y visualizacion de PDF de evidencia.
- Reversion de ultimas acciones por codigo activo.
- Compatibilidad con navegacion legacy por `apartado`.

## Instalacion

1. Clona el repositorio.
2. Instala dependencias de PHP:

```powershell
composer install
```

3. Instala dependencias de frontend:

```powershell
npm install
```

4. Crea el archivo de entorno:

```powershell
copy .env.example .env
```

5. Genera la clave de la aplicacion:

```powershell
php artisan key:generate
```

6. Configura la conexion a base de datos en `.env`.
7. Ejecuta migraciones:

```powershell
php artisan migrate
```

8. Crea el enlace publico para archivos de evidencia:

```powershell
php artisan storage:link
```

9. Compila los assets:

```powershell
npm run dev
```

10. Inicia la aplicacion:

```powershell
php artisan serve
```

## Configuracion `.env`

Valores importantes a revisar:

```env
APP_NAME=Mantenimientos
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tu_base_de_datos
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_password
```

## Dependencias de PHP

Definidas en `composer.json`:

- `laravel/framework`
- `laravel/sanctum`
- `laravel/tinker`
- `guzzlehttp/guzzle`

Dependencias de desarrollo:

- `fakerphp/faker`
- `laravel/sail`
- `mockery/mockery`
- `nunomaduro/collision`
- `phpunit/phpunit`
- `spatie/laravel-ignition`

## Dependencias de frontend

Definidas en `package.json`:

- `axios`
- `laravel-mix`
- `lodash`
- `postcss`

Scripts utiles:

```powershell
npm run dev
npm run watch
npm run prod
```

## Autenticacion y permisos

- El login consulta `usuarios_sistema`.
- El password se valida contra `password_hash`.
- El usuario debe tener `estado = 1`.
- El acceso al panel requiere un rol admin.
- Roles aceptados para entrar al panel:
  - `admin`
  - `administrador`
  - `superadmin`

## Modulos del panel

### Login

- Ruta: `/login`
- Login con correo y contraseña.
- Limite de intentos por correo e IP.

### Panel principal

- Ruta: `/panel`
- Redirecciona a las vistas del panel segun `apartado` cuando aplique.

### Reportes

- Ruta: `/panel/reportes`
- Resume el estado semestral de mantenimientos.

### Proximos

- Ruta: `/panel/proximos`
- Permite listar y programar mantenimientos proximos.
- Solo acepta fechas de lunes a viernes al programar.

### Programados

- Ruta: `/panel/programados`
- Permite marcar un mantenimiento como realizado.
- Sube constancia en PDF.
- Genera el siguiente mantenimiento automaticamente.

### Hechos

- Ruta: `/panel/hechos`
- Muestra los mantenimientos ejecutados y sus evidencias.

### Usuarios

- Ruta: `/panel/usuarios`
- Permite crear, editar, activar o desactivar y eliminar usuarios del inventario.

### Configuracion

- Ruta: `/panel/configuracion`
- Permite registrar tecnicos y revertir acciones recientes por codigo activo.

## Rutas principales

```php
/          -> redirige a login o panel
/login     -> formulario de autenticacion
/logout    -> cierre de sesion
/panel     -> panel principal
/panel/reportes
/panel/usuarios
/panel/configuracion
/panel/proximos
/panel/programados
/panel/hechos
/panel/evidencia/{mantenimientoId}
```

## Base de datos legacy

La aplicacion asume que ya existen las tablas principales del sistema legacy. En particular:

- `usuarios_sistema` para autenticacion.
- `usuarios` para activos/equipos.
- `mantenimientos` para el historial.
- `areas` para catalogacion.
- `tecnicos` para responsables.

Las migraciones incluidas aqui agregan compatibilidad y campos nuevos, pero no reconstruyen toda la base legacy desde cero.

### Campos relevantes esperados

En `usuarios_sistema`:

- `id`
- `nombre`
- `correo`
- `password_hash`
- `rol`
- `estado`
- `ultimo_acceso`

En `usuarios` y `mantenimientos` se usan campos como:

- `codigo_activo`
- `serial_equipo`
- `tipo_equipo`
- `area_id`
- `estado_usuario`
- `proxima_fecha_mantenimiento`
- `ultima_fecha_mantenimiento`
- `ultima_fecha_ejecucion`
- `ultimo_mantenimiento_id`
- `ultimo_mantenimiento_estado`
- `fecha_programada`
- `fecha_ejecucion`
- `tecnico_responsable`
- `evidencia_pdf`

## Pruebas

Ejecutar la suite:

```powershell
php artisan test
```

Las pruebas actuales cubren, entre otras cosas:

- autorizacion del panel
- flujo de programados a hechos
- descarga y visualizacion de evidencia PDF
- CRUD de usuarios
- reversiones por codigo activo
- busquedas por serial, area y codigo activo

## Notas operativas

- Si el archivo PDF de evidencia se guarda en `storage/app/public`, recuerda ejecutar `php artisan storage:link`.
- El modulo de reportes usa periodos semestrales por defecto.
- La busqueda del panel esta pensada para datos legacy y normaliza texto cuando hace falta.
- Si cambias vistas o assets, recompila con `npm run dev` o `npm run prod`.

## Estructura general

- `app/Http/Controllers`: controladores del login y panel.
- `app/Services/Legacy`: repositorios y reglas de negocio sobre tablas legacy.
- `app/Services/SemestreService.php`: calculo de periodos semestrales.
- `resources/views/panel`: vistas del panel.
- `routes/web.php`: rutas publicas y protegidas.
- `tests/Feature`: pruebas de integracion del panel.

## Comandos utiles

```powershell
php artisan route:list
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan test
```

## License

MIT.

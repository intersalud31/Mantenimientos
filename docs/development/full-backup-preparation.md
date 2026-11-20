# Preparacion de backup integral operativo

## Objetivo

Definir los componentes, exclusiones y validaciones necesarios para preparar
manualmente un backup integral de Mantenimientos-Laravel sin crear aun dumps,
copias ni archivos comprimidos.

## Estado actual

Se comprobó que el proyecto contiene dumps SQL locales, codigo, dependencias,
storage y archivos de evidencias. Tambien se comprobó que los dumps historicos
pueden ser parciales y contener instrucciones `USE` con un destino fijo.

Tamaños observados en el workspace:

| Componente | Tamano aproximado |
| --- | ---: |
| Codigo y carpetas de aplicacion, sin dependencias | 1.3 MB |
| `storage/app/public` | 5.15 MB |
| `storage/app/backups` | 0.24 MB |
| `vendor` | 40.34 MB |
| `node_modules` | 69.61 MB |

Los tamaños son orientativos y pueden cambiar mientras la aplicacion se usa.
No existe evidencia actual de un backup conjunto de BD y evidencias PDF.

## Componentes a respaldar

### Aplicacion

- `app/`.
- `bootstrap/` necesario para la instalacion, excluyendo cache generado.
- `config/` versionado, sin secretos.
- `database/` incluyendo migraciones y estructura versionada.
- `public/` excepto enlaces o artefactos temporales.
- `resources/`.
- `routes/`.
- `tests/` si se requiere reconstruir la linea base.
- `composer.json`, `composer.lock`, `package.json` y lockfile si existe.
- `AGENTS.md` y `docs/` para reconstruccion y operacion.

### Base de datos

El dump debe incluir estructura y datos completos de la base activa, incluyendo
como minimo:

- `usuarios_sistema`.
- `usuarios`.
- `mantenimientos`.
- `mantenimiento_eventos`.
- `tecnicos`.
- `areas`.
- `configuracion_sistema`.
- roles, permisos y tablas relacionadas.
- cualquier tabla legacy realmente usada por el codigo.

Debe registrarse el nombre de la base, fecha, servidor, motor, version,
conteos, hash del dump y resultado de una prueba de restauracion aislada.

### Archivos operativos

- `storage/app/public` completo.
- Evidencias PDF y otros archivos generados necesarios para la operacion.
- Inventario de rutas, tamaños, fechas y hashes.
- Instrucciones para reconstruir `public/storage`; el enlace simbolico no debe
  tratarse como una copia independiente.

### Dependencias

`vendor` ocupa aproximadamente 40.34 MB y `node_modules` 69.61 MB. La copia
integral puede excluirlos si el entorno de recuperacion tiene acceso a los
gestores de paquetes y conserva los lockfiles. Si la recuperacion debe operar
sin internet o sin repositorios disponibles, deben respaldarse como artefactos
separados y verificarse sus versiones.

## Componentes a excluir

- `.env`, `.env.*`, `.env.backup` y `.env.bak` del paquete general; las
  variables deben custodiarse mediante un mecanismo seguro separado.
- `storage/logs/` generados localmente, salvo que exista una necesidad de
  investigacion autorizada.
- `storage/framework/cache/`, `storage/framework/sessions/` y
  `storage/framework/views/`.
- `storage/framework/testing/` y `.phpunit.cache/`.
- `bootstrap/cache/` generado.
- `node_modules/` y `vendor/` cuando puedan reconstruirse de forma fiable con
  lockfiles; de lo contrario, respaldarlos fuera del paquete principal.
- `public/hot` y artefactos de desarrollo frontend.
- El enlace `public/storage`, que debe recrearse con `storage:link`.
- Archivos temporales, dumps de prueba y archivos de rescate no identificados.
- Backups antiguos dentro de `storage/app/backups` en el nuevo paquete
  operativo, para evitar mezclar historicos o generar copias recursivas. Deben
  conservarse como artefactos separados y con inventario propio.

No debe excluirse `storage/app/public` por contener archivos generados: ahi se
encuentran las evidencias que la BD referencia mediante
`mantenimientos.evidencia_pdf`.

## Procedimiento propuesto

### P0 - Preparacion obligatoria

1. Confirmar la base activa, servidor, puerto y usuario operativo sin exponer
   credenciales.
2. Obtener espacio libre suficiente para dump, inventario y copia de storage.
3. Crear un directorio de destino protegido fuera de las carpetas de la
   aplicacion activa.
4. Generar el dump completo de BD usando una herramienta MySQL/MariaDB
   compatible, registrando salida y errores.
5. Confirmar que el dump incluye las tablas actuales y que no contiene un
   destino fijo peligroso como `USE mantenimientos`.
6. Copiar `storage/app/public` sin alterar los archivos originales.
7. Generar inventario y hashes de archivos y del dump.
8. Guardar codigo y lockfiles en el conjunto de recuperacion.

La herramienta exacta para generar el dump queda pendiente de confirmar segun
el motor y las credenciales del entorno. No se debe reutilizar directamente
un dump historico con `DROP TABLE` o `USE` no validado.

### P1 - Validaciones posteriores

- Comparar conteos y rangos de tablas criticas.
- Confirmar que `mantenimiento_eventos` esta incluido.
- Comparar cada ruta de `evidencia_pdf` con el inventario de storage.
- Verificar que no haya archivos faltantes o huerfanos no explicados.
- Revisar permisos y acceso restringido del conjunto.
- Copiar el resultado a una ubicacion externa controlada.
- Ejecutar restauracion en una instancia separada, nunca en la base activa.

### P2 - Operacion futura

- Automatizar el proceso con identificacion de origen y destino.
- Generar retencion, hashes, alertas y reportes.
- Programar pruebas periodicas de recuperacion.
- Medir espacio y crecimiento de evidencias.

## Tamaño esperado y riesgos de espacio

Con el estado observado, el conjunto minimo de aplicacion y evidencias supera
aproximadamente 6.5 MB sin contar la base de datos, compresion ni dependencias.
Una copia con `vendor` y `node_modules` supera aproximadamente 116 MB antes de
agregar dump y metadatos.

El tamaño real del dump depende de datos, estructura, eventos e historial. Debe
medirse antes de copiar y debe existir margen para una copia temporal y una
restauracion aislada. No se debe liberar espacio eliminando PDFs, backups o
datos de aplicacion sin auditoria y autorizacion.

## Orden correcto de respaldo

1. Confirmar origen y destino del backup.
2. Registrar metadata y espacio disponible.
3. Generar dump consistente de la base activa.
4. Verificar el dump y calcular hash.
5. Copiar `storage/app/public` e inventariar archivos.
6. Comparar referencias de BD con archivos.
7. Copiar codigo, lockfiles y documentacion.
8. Proteger el conjunto y crear una copia externa.
9. Validar restauracion en entorno aislado.

## Dependencias necesarias

- Cliente compatible `mysqldump` o herramienta equivalente del motor real.
- Acceso autorizado a la base activa.
- Espacio local y externo suficiente.
- Herramienta de hash.
- Permisos de lectura sobre el proyecto y `storage/app/public`.
- Entorno MySQL/MariaDB separado para la prueba.
- Gestores Composer y npm, o artefactos respaldados si no hay conectividad.

No se deben abrir ni copiar secretos de `.env` dentro del paquete documental o
del repositorio. Su disponibilidad para recuperacion debe gestionarse por el
responsable del entorno.

## Validacion y estado

- Se revisaron tamaños de carpetas, `.gitignore`, composer, comandos y
  documentacion de backup.
- No se creo ningun dump.
- No se copiaron evidencias.
- No se comprimieron archivos.
- No se modifico la BD.
- No se modifico codigo ni migraciones.
- No se ejecutaron `git add`, `git commit` ni `git push`.
- `git diff --check` debe ejecutarse desde
  `C:\xampp\htdocs\Mantenimientos-Laravel`.

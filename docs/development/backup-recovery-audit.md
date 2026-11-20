# Auditoria de backup y recuperacion del sistema

## Objetivo

Determinar, mediante revision de solo lectura, que componentes del proyecto
cuentan con respaldos identificables y que tan comprobable es la recuperacion
del sistema. No se abrieron dumps, no se restauraron respaldos y no se
modificaron archivos, datos ni configuracion.

## Estado actual comprobado

El proyecto tiene codigo fuente, estructura Laravel, migraciones y
documentacion disponibles en el workspace. Esto permite reconstruir una parte
del codigo si el repositorio o una copia del proyecto permanece disponible,
pero no demuestra por si solo que exista un backup operativo externo ni que el
despliegue pueda reproducirse completamente.

Se identificaron respaldos SQL por nombre, extension, tamano y fecha. No se
abrio su contenido, por lo que no es comprobable el esquema, la base de datos
de origen ni la restaurabilidad de cada archivo.

Tambien existen herramientas de diagnostico:

- `mantenimientos:auditoria --database=<nombre>` realiza auditoria de lectura.
- `bd3:snapshot` genera una fotografia logica de una base aislada.
- `evidencias:auditar` compara referencias de evidencias con el disco fisico.

Estas herramientas no constituyen un proceso de backup restaurable completo.

## Backups encontrados

### `storage/app/backups`

Se encontraron cuatro archivos SQL con fecha del 30/07/2026:

| Archivo | Tamano aproximado | Tipo | Origen posible |
| --- | ---: | --- | --- |
| `mantenimientos_20260730_081800.sql` | 65 KB | SQL | Respaldo local de mantenimientos |
| `mantenimientos_20260730_081822_restore.sql` | 65 KB | SQL | Archivo asociado por nombre a una restauracion local |
| `mantenimientos_actual_20260730_095826.sql` | 65 KB | SQL | Respaldo local de una base actual |
| `mantenimientos_actual_20260730_114546.sql` | 58 KB | SQL | Respaldo local de una base actual |

El origen real, alcance, integridad y restaurabilidad son no comprobables sin
inspeccion autorizada del contenido y una prueba aislada.

### `db-rescue-20260731`

Se encontraron 13 archivos SQL con fecha del 31/07/2026. Incluyen nombres
relacionados con mantenimientos, esquemas, auditoria, pruebas y otras bases.
Tambien hay archivos de varios tamanos, incluyendo dumps grandes de otras
bases. Por sus nombres no puede confirmarse que todos correspondan a la base
activa de Mantenimientos-Laravel.

Esta carpeta debe considerarse material sensible de rescate. No se consulto
su contenido ni se intento restaurarla.

## Componentes recuperables

### Comprobable

- **Codigo fuente:** esta presente en el workspace y puede ser respaldado por
  el control de versiones, pero no se comprobo una copia externa operativa.
- **Esquema versionado parcial:** existen migraciones, aunque la documentacion
  previa identifica dependencia con tablas legacy cuyo estado real puede
  diferir.
- **Base de datos:** existen dumps SQL identificables, pero no se comprobo que
  alguno sea el respaldo correcto ni que pueda restaurarse.
- **Diagnostico:** existen comandos para auditar y generar snapshots logicos.

### No comprobable como recuperacion completa

- Un despliegue reproducible con versiones exactas de PHP, extensiones,
  Composer, Node y XAMPP.
- Variables de entorno y credenciales necesarias para conectar la aplicacion.
- Configuracion del servidor web, enlaces simbolicos y permisos NTFS.
- Restauracion conjunta de BD, codigo y almacenamiento.

## Componentes no comprobados o faltantes

### Evidencias PDF

No se identificaron archivos PDF dentro de las ubicaciones de respaldo
revisadas. Las evidencias viven en `storage/app/public` y se relacionan con
la BD mediante `mantenimientos.evidencia_pdf`. No existe evidencia comprobada
de que ese directorio sea incluido en los respaldos SQL.

Por tanto, es posible recuperar una ruta en BD sin recuperar el archivo, o un
archivo sin su referencia correspondiente.

### Configuracion sensible

No se inspeccionaron archivos `.env` ni copias equivalentes por seguridad. No
es comprobable que exista un respaldo seguro de variables de entorno,
credenciales, claves o configuracion de produccion.

### Procedimiento de restauracion

No se encontro un comando o procedimiento automatizado que restaure la base,
el codigo y `storage/app/public` como una unidad. Tampoco se comprobo una
restauracion exitosa en un entorno aislado.

## Riesgos actuales

### Confirmados

- Hay dumps SQL locales, pero su alcance y restaurabilidad no fueron
  comprobados.
- No hay un backup de PDFs identificable en las ubicaciones revisadas.
- Las herramientas existentes son de auditoria o snapshot diagnostico, no de
  recuperacion integral.
- La relacion entre rutas de evidencia y archivos fisicos puede quedar
  incompleta al restaurar solo la BD.

### Potenciales

- Perdida de evidencias ante dano del disco local.
- Restauracion sobre una base equivocada o desactualizada.
- Inconsistencia entre datos, archivos y codigo desplegado.
- Exposicion de dumps SQL si las carpetas no tienen control operativo.
- Imposibilidad de cumplir tiempos de recuperacion por falta de procedimiento,
  retencion, copia externa y pruebas.

## Recomendaciones

### P0 - Necesario para garantizar recuperacion

- Inventariar formalmente el respaldo correcto de la base activa, sin borrar
  ni mover los archivos existentes.
- Crear un backup coordinado de la base y `storage/app/public`.
- Ejecutar una restauracion autorizada en un entorno aislado.
- Verificar que las rutas de `mantenimientos.evidencia_pdf` correspondan a los
  archivos recuperados.
- Definir ubicacion protegida, responsable, frecuencia, retencion y control
  de acceso de los respaldos.
- Mantener `evidencias:auditar` como verificacion posterior, sin limpieza
  automatica.

### P1 - Mejoras posteriores

- Automatizar backups con identificacion de base, fecha, resultado y hashes.
- Documentar paso a paso la recuperacion y los criterios de exito.
- Probar periodicamente restauracion de BD, archivos y configuracion segura.
- Establecer copias fuera del equipo local y monitoreo de fallos.

### P2 - Automatizacion futura

- Integrar backup versionado de evidencias con inventario de archivos.
- Definir objetivos formales de tiempo de recuperacion y perdida maxima
  aceptable.
- Automatizar alertas de capacidad, antiguedad e integridad.
- Evaluar almacenamiento redundante o externo para PDFs.

## Archivos y fuentes revisados

- `AGENTS.md`
- `app/Console/Commands/BaseDatosAuditoriaCommand.php`
- `app/Console/Commands/Bd3SnapshotCommand.php`
- `app/Console/Commands/AuditarEvidenciasCommand.php`
- `app/Services/Diagnostics/BaseDatosAuditoriaService.php`
- `app/Services/Diagnostics/Bd3SnapshotService.php`
- `config/filesystems.php`
- `README.md`
- `storage/app/backups` mediante metadata
- `db-rescue-20260731` mediante metadata
- `docs/security/backup-recovery-audit.md`
- `docs/security/evidence-backup-review.md`
- `docs/security/evidence-audit-results.md`
- `docs/security/evidence-reconciliation-report.md`
- `docs/security/file-lifecycle-audit.md`
- `docs/development/stabilization-progress.md`

## Validaciones realizadas

- Revision de ubicacion, extension, tamano y fecha de archivos SQL.
- Busqueda estatica de comandos y servicios de backup, snapshot y
  restauracion.
- Contraste con documentacion existente.
- `git diff --check`.
- No se abrieron respaldos ni secretos.
- No se ejecutaron restauraciones, migraciones, limpiezas ni comandos
  destructivos.
- No hubo cambios de codigo, BD, migraciones, pruebas ni respaldos.
- No se ejecutaron `git add`, `git commit` ni `git push`.

# Revision de esquema y migraciones

## Alcance

Auditoria estatica de `database/migrations` y de las tablas referenciadas por
el codigo. No se consulto la base para alterar datos, no se ejecutaron
migraciones y no se modificaron archivos productivos.

## 1. Estado actual comprobado

### Migraciones presentes

El directorio contiene migraciones Laravel estandar para `users`,
`password_resets`, `failed_jobs` y `personal_access_tokens`, ademas de ajustes
de compatibilidad para el esquema legacy:

- columnas de resumen en `usuarios`;
- fecha base de mantenimiento en `usuarios`;
- soporte formalizado para `tecnicos`, `tipo_equipo`,
  `tecnico_responsable` y `evidencia_pdf`;
- creacion/garantia de `mantenimiento_eventos`;
- una migracion historica relacionada con tablas legacy que elimina y vuelve a
  crear tablas consideradas vacias/no usadas.

### Tablas principales referenciadas por el codigo

| Tabla | Uso comprobado | Definicion completa en migraciones del proyecto |
|---|---|---|
| `usuarios_sistema` | autenticacion, roles, estado y ultimo acceso | no comprobada |
| `usuarios` | usuarios/equipos operativos, areas, seriales, resumen y fechas | solo ajustes parciales |
| `mantenimientos` | estados, fechas, observaciones, tecnicos, evidencias y relaciones | solo ajustes parciales |
| `tecnicos` | responsables activos | soporte condicional |
| `areas` | catalogo y joins de area | no comprobada |
| `mantenimiento_eventos` | bitacora tecnica | garantia y referencia historica |
| `configuracion_sistema` | periodicidad/configuracion | no comprobada |
| `auditoria_sistema` | estructura historica/diagnostica en migracion | no comprobado como usado por el dominio actual |
| `notificaciones`, `notificaciones_inapp`, `notificacion_queue` | definidas en migracion historica | no comprobadas como usadas por el flujo actual |

La ausencia de una definicion completa en `database/migrations` no implica que
la tabla no exista: son tablas legacy y su esquema real debe verificarse en una
base aislada o mediante una auditoria autorizada.

### Foreign keys encontradas

Las migraciones declaran foreign keys para estructuras creadas o formalizadas,
incluyendo relaciones de notificaciones y `mantenimiento_eventos` hacia
`mantenimientos.id`. Sin embargo, varias tablas operativas legacy no tienen su
esquema completo declarado en este repositorio, por lo que no puede afirmarse
que sus relaciones reales esten igualmente protegidas.

La migracion de garantia de `mantenimiento_eventos` condiciona su creacion a
que la tabla no exista y agrega la relacion hacia `mantenimientos` cuando la
tabla destino esta disponible. Esto mejora el contrato de la bitacora nueva,
pero no verifica todos los datos legacy existentes.

### Indices encontrados

Se observan indices y unicos en tablas Laravel estandar, por ejemplo emails,
UUIDs y tokens. En estructuras auxiliares se declaran indices para:

- `mantenimiento_eventos.mantenimiento_id`;
- `mantenimiento_eventos (mantenimiento_id, created_at)`;
- `mantenimiento_eventos (tipo_evento, created_at)`;
- auditoria por tabla/registro y accion/fecha;
- notificaciones por estado/canal, usuario/fecha y reintento;
- tecnicos por nombre y estado.

No se puede garantizar desde las migraciones que las tablas legacy principales
(`usuarios`, `mantenimientos`, `areas`, `usuarios_sistema`) tengan todos los
indices que sus consultas actuales necesitan.

## 2. Diferencias entre migraciones y esquema legacy

- Laravel mantiene tablas propias historicas aunque la autenticacion real usa
  `usuarios_sistema`.
- `usuarios`, `mantenimientos`, `areas` y `usuarios_sistema` son dependencias
  legacy cuya creacion completa no esta representada en las migraciones
  actuales.
- Las migraciones agregan columnas condicionalmente para convivir con bases
  existentes, en lugar de reconstruir el esquema.
- Algunas migraciones antiguas describen tablas que fueron consideradas no
  usadas y ejecutan operaciones de eliminacion/creacion; esto introduce riesgo
  historico si se aplican sobre una base equivocada.
- La bitacora nueva tiene una garantia de esquema, pero su FK puede depender de
  que `mantenimientos` exista y sea compatible en el momento de ejecutar.

## 3. Riesgos identificados

### Alto - Esquema operativo parcialmente fuera de migraciones

**Estado actual:** el codigo depende de tablas y columnas cuya definicion
completa no esta versionada aqui.

**Riesgo:** una base con variaciones, columnas ausentes, tipos incompatibles o
datos huerfanos puede fallar en runtime y no ser reproducible solo con
`migrate`.

**Mejora futura:** mantener un inventario verificado por entorno y snapshots
autorizados, sin convertir automaticamente toda la base legacy en migraciones.

### Alto - Migraciones historicas con operaciones destructivas

**Estado actual:** existe una migracion que ejecuta `dropIfExists` sobre tablas
legacy consideradas vacias/no usadas antes de crear estructuras.

**Riesgo:** ejecutarla contra una base distinta de la esperada podria borrar
tablas o datos. El riesgo depende del historial de migraciones y del entorno.

**Mejora futura:** bloquear migraciones destructivas con revision explicita,
base aislada, respaldo y verificacion de nombre de conexion.

### Alto - Foreign keys nuevas sobre datos legacy

**Estado actual:** `mantenimiento_eventos` puede tener FK hacia
`mantenimientos`, mientras el esquema legacy completo no esta comprobado en el
repositorio.

**Riesgo:** datos huerfanos, tipos distintos o engine incompatible pueden hacer
fallar la migracion o impedir inserciones posteriores.

**Mejora futura:** auditar huerfanos, tipos, engines e indices antes de reforzar
constraints adicionales.

### Medio - Indices reales no verificables estaticamente

**Estado actual:** solo se conocen los indices declarados por migraciones; las
tablas legacy no tienen un diccionario completo versionado.

**Riesgo:** consultas de periodos, estados y joins pueden degradarse o depender
de indices existentes solo en una instalacion.

**Mejora futura:** obtener `information_schema` en una auditoria autorizada y
documentar indices observados sin cambiar la base automaticamente.

### Medio - Migraciones condicionales pueden ocultar divergencias

**Estado actual:** varias usan `hasTable`/`hasColumn` para aplicar cambios solo
si falta una estructura.

**Riesgo:** dos bases pueden terminar con columnas o constraints diferentes sin
que el historial de migraciones lo evidencie claramente.

**Mejora futura:** agregar comprobaciones de compatibilidad y reportes de
resultado por entorno antes de cambios de esquema.

### Medio - Duplicidad de tablas Laravel y legacy

**Estado actual:** existe la tabla Laravel estandar `users`, pero el modelo de
autenticacion usa `usuarios_sistema`.

**Riesgo:** un desarrollador puede consultar la tabla equivocada y generar
comportamiento inconsistente o crear datos en un sistema no utilizado.

**Mejora futura:** documentar claramente tablas activas y evitar nuevas
referencias a `users` salvo que exista un caso comprobado.

### Bajo - Convenciones de nombres y timestamps variables

**Estado actual:** las tablas legacy usan nombres y columnas propias, mientras
las tablas Laravel siguen convenciones del framework.

**Riesgo:** modelos o migraciones futuras pueden asumir convenciones incorrectas
de claves, timestamps o tipos.

**Mejora futura:** documentar contratos por tabla antes de crear modelos o
constraints.

## 4. Tablas candidatas a evolucion futura

Sin implementar cambios ni recomendar una migracion completa:

- `mantenimiento_eventos`: candidata prioritaria por tener contrato estructurado
  y uso tecnico claro.
- `mantenimientos`: candidata para formalizacion gradual cuando estados,
  fechas, evidencias y datos historicos esten auditados.
- `usuarios`: candidata para documentacion/representacion propia, separada de
  `User` y `usuarios_sistema`.
- `tecnicos` y `areas`: candidatas de bajo alcance si se confirma su esquema y
  relaciones reales.
- `configuracion_sistema`: debe permanecer legacy mientras periodicidad y
  valores actuales no tengan contrato completo.

Las tablas de notificaciones y auditoria solo deben evolucionar despues de
confirmar que son parte del flujo actual y que no son restos de una migracion
historica.

## 5. Mejoras futuras, no implementadas

1. Generar un inventario de tablas, columnas, indices, engines y foreign keys
   desde una base aislada autorizada.
2. Comparar ese inventario con migraciones y detectar divergencias.
3. Establecer una politica para migraciones legacy destructivas.
4. Mantener migraciones nuevas idempotentes cuando deban convivir con tablas
   existentes.
5. Agregar pruebas de estructura para tablas nuevas, como ya existe para
   `mantenimiento_eventos`.
6. Documentar rollback y compatibilidad antes de agregar constraints.
7. Evolucionar una tabla o agregado por vez, preservando datos historicos.

No se recomienda reconstruir el esquema legacy ni ejecutar una migracion total
sin auditoria de datos, respaldo y autorizacion expresa.

## Archivos revisados

- `AGENTS.md`
- `database/migrations/2014_10_12_000000_create_users_table.php`
- `database/migrations/2014_10_12_100000_create_password_resets_table.php`
- `database/migrations/2019_08_19_000000_create_failed_jobs_table.php`
- `database/migrations/2019_12_14_000001_create_personal_access_tokens_table.php`
- migraciones de compatibilidad de `usuarios`, `mantenimientos` y `tecnicos`
- `database/migrations/2026_07_30_000002_drop_unused_empty_legacy_tables.php`
- `database/migrations/2026_09_01_000001_ensure_mantenimiento_eventos_table_exists.php`
- `app/Services/Legacy/` para tablas y columnas referenciadas
- `app/Http/Controllers/`
- `app/Models/User.php`
- `app/Console/Commands/`
- `app/Services/Diagnostics/`
- documentacion de `docs/architecture/`, `docs/database/`, `docs/legacy/` y
  `docs/development/`

## Validaciones realizadas

- Inventario estatico de migraciones, tablas, indices y foreign keys declaradas.
- Busqueda de tablas referenciadas mediante `DB::table()` y Query Builder.
- Comparacion documental entre migraciones y arquitectura legacy.
- No se ejecuto ninguna migracion.
- No se modificaron codigo, BD, migraciones ni pruebas.
- No se ejecutaron `git add`, `git commit` ni `git push`.

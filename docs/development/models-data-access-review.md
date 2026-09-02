# Revision de modelos y acceso a datos

## Alcance

Auditoria estatica de `app/Models`, servicios, clases equivalentes a
repositories, usos de Query Builder y migraciones como referencia. No se
modificaron codigo, base de datos, migraciones ni pruebas.

## 1. Estado actual comprobado: modelos

### `App\Models\User`

- **Tabla:** `usuarios_sistema`.
- **Responsabilidad:** modelo autenticable de Laravel.
- **Uso:** autenticacion, sesion y operaciones relacionadas con el usuario
  autenticado.
- **Configuracion comprobada:** `fillable` para datos de usuario, `hidden` para
  `password_hash` y `remember_token`, casts para estado y fechas, y
  `getAuthPassword()` adaptado al campo legacy `password_hash`.
- **Relaciones:** no declara relaciones Eloquent.

No se encontraron otros archivos de modelos activos en `app/Models`. No hay
modelos Eloquent comprobados para `usuarios`, `mantenimientos`, `tecnicos`,
`areas` o `mantenimiento_eventos`.

## 2. Estrategia actual de acceso a datos

### Acceso moderno comprobado

El modelo `User` usa Eloquent como requisito de autenticacion de Laravel sobre
la tabla legacy `usuarios_sistema`. Esta es la unica superficie Eloquent
identificada.

### Acceso legacy comprobado

La mayor parte del sistema usa `DB::table()` y Query Builder desde los
servicios legacy, repositories equivalentes, comandos y servicios de
diagnostico. Los accesos cubren `usuarios`, `mantenimientos`, `tecnicos`,
`areas`, `mantenimiento_eventos`, `usuarios_sistema` y
`configuracion_sistema`.

Las consultas usan joins, subconsultas, `selectRaw`, agregaciones,
actualizaciones e inserciones directamente contra el esquema existente.

### Acceso mixto

El flujo HTTP combina Eloquent/Auth para el usuario autenticado con Query
Builder para datos operativos. Esto es acceso mixto a nivel de aplicacion,
no relaciones Eloquent entre modelos.

## 3. Relaciones actuales

### Relaciones Eloquent

No se comprobaron `hasMany`, `belongsTo`, `hasOne`, `belongsToMany` ni
relaciones polimorficas en el modelo existente.

### Relaciones manuales mediante joins

| Origen logico | Destino | Campo o condicion | Implementacion |
|---|---|---|---|
| `mantenimientos` | `usuarios` | `mantenimientos.usuario_id = usuarios.id` | joins y subconsultas |
| `usuarios` | `areas` | `usuarios.area_id = areas.id` | `leftJoin` para area |
| `mantenimientos` | `tecnicos` | identificador/nombre segun columnas legacy | consultas de resolucion |
| `mantenimientos` | `mantenimiento_eventos` | `mantenimiento_eventos.mantenimiento_id = mantenimientos.id` | repository de eventos y reversiones |
| `usuarios_sistema` | Auth/sesion | `usuarios_sistema.id` | Eloquent/Auth y middleware |

Estas relaciones son logicas y estan implementadas en SQL. La existencia de
foreign keys no se asume desde el codigo y debe verificarse en el esquema real.

### Relaciones implicitas por IDs

Los servicios intercambian `usuario_id`, `mantenimiento_id`, `tecnico_id` y
`area_id` como enteros. La integridad se valida principalmente con consultas,
`exists`, filtros y joins, no mediante objetos relacionados.

## 4. Dependencias principales

- `PanelController` coordina repositories/services y vistas.
- `MantenimientoRepository` concentra gran parte de lectura y escritura de
  mantenimientos, usuarios relacionados, tecnicos, reportes, notificaciones,
  eventos y evidencias.
- `UsuarioRepository` concentra operaciones de usuarios, areas, tecnicos y
  datos derivados.
- `MantenimientoEventoRepository` persiste y consulta eventos tecnicos.
- Servicios de estado, resumen, periodicidad y diagnostico tambien usan
  consultas directas para sus reglas especificas.
- Los comandos consultan tablas legacy para auditorias, normalizaciones y
  sincronizaciones.

## 5. Riesgos identificados

### Alto - Dependencia extensa de nombres y columnas legacy

**Estado actual:** servicios y comandos usan literalmente tablas y columnas como
`usuarios`, `mantenimientos`, `estado_usuario`, `fecha_programada` y
`observaciones`.

**Riesgo:** un cambio de esquema, alias o tipo puede afectar muchos puntos sin
un contrato central; variaciones legacy pueden fallar en runtime.

**Mejora futura:** mantener inventario verificado y encapsular accesos por
agregado gradualmente, sin cambiar la fuente legacy de forma masiva.

### Alto - Modelo operativo inexistente para mantenimientos

**Estado actual:** no hay modelos Eloquent para mantenimientos, usuarios
operativos, tecnicos, areas o eventos.

**Riesgo:** relaciones, casts y contratos se expresan como arrays y strings,
dificultando refactors y deteccion temprana de errores.

**Mejora futura:** evaluar modelos solo para tablas estables y con contratos
conocidos, manteniendo Query Builder donde la compatibilidad lo exija.

### Alto - Repository concentrado y acceso distribuido

**Estado actual:** `MantenimientoRepository` tiene 2327 lineas, mientras otros
servicios y comandos tambien consultan las mismas tablas.

**Riesgo:** filtros, joins y reglas pueden duplicarse y producir resultados
inconsistentes.

**Mejora futura:** definir contratos por agregado y migrar callers por etapas,
sin crear repositories paralelos con SQL duplicado.

### Medio - Relaciones logicas sin FK comprobada

**Estado actual:** las asociaciones dependen de IDs y joins; el codigo no
permite afirmar que exista una FK para cada relacion.

**Riesgo:** registros huerfanos o referencias invalidas pueden ser tratados de
forma distinta por cada consulta.

**Mejora futura:** auditar integridad real antes de agregar constraints.

### Medio - Reglas de negocio mezcladas con consultas

**Estado actual:** repositories y services calculan estados, clasificaciones,
periodos, duplicados y notificaciones durante consultas o mapeo de filas.

**Riesgo:** una regla puede divergir entre Dashboard, listados, comandos y
acciones.

**Mejora futura:** separar consulta, dominio y presentacion con pruebas de
caracterizacion.

### Medio - Arrays como frontera entre capas

**Estado actual:** services y controllers intercambian arrays con claves como
`estado`, `usuario_id`, `fecha_programada` y metadata.

**Riesgo:** errores de claves o tipos aparecen tarde y no existe un contrato
tipado central para cada agregado.

**Mejora futura:** introducir DTOs u objetos de valor en los flujos que se
modifiquen, conservando compatibilidad.

### Bajo - Acceso mixto puede confundir responsabilidades

**Estado actual:** Auth usa Eloquent y el dominio operativo usa Query Builder.

**Riesgo:** futuros cambios pueden aplicar convenciones Eloquent a tablas
legacy no modeladas o duplicar la representacion del usuario.

**Mejora futura:** documentar que `User` representa solo la identidad
autenticable y `usuarios` el usuario/equipo operativo legacy.

## 6. Candidatos para evolucion futura

Estas opciones no estan implementadas ni autorizan una migracion:

- `mantenimientos`: candidato principal para modelo Eloquent o gateway tipado
  si columnas y estados se estabilizan.
- `mantenimiento_eventos`: candidato para modelo/repository enfocado por su
  contrato estructurado.
- `usuarios`: modelo separado de `User`, porque representa el usuario/equipo
  operativo y no la identidad de login.
- `tecnicos` y `areas`: modelos simples si se confirma su esquema y ciclo de
  vida.
- `usuarios_sistema`: debe permanecer compatible con `User`, `password_hash`,
  `estado`, `rol` y la sesion actual.

Las relaciones futuras deben formalizarse solo despues de comprobar huerfanos,
tipos, nombres y foreign keys reales. Diagnosticos y tablas inestables pueden
permanecer en Query Builder.

## 7. Estrategia progresiva recomendada

### Fase 1: compatibilidad actual

- Mantener `User` y Query Builder.
- Documentar tablas, columnas, estados y relaciones logicas.
- Crear pruebas de caracterizacion antes de mover un flujo.

### Fase 2: dominios estables

- Seleccionar una tabla con contrato estable, preferiblemente eventos o
  tecnicos.
- Crear una representacion tipada sin modificar rutas ni esquema.
- Comparar resultados con el acceso legacy.

### Fase 3: reemplazo controlado

- Migrar un caller por vez.
- Mantener fallback legacy para historicos o datos incompletos.
- Validar transacciones, estados, conteos y rendimiento.

### Fase 4: consolidacion

- Retirar accesos duplicados solo sin callers pendientes.
- Formalizar relaciones unicamente si la integridad real lo permite.
- Registrar cambios arquitectonicos en ADR.

No se recomienda una migracion completa a Eloquent: la arquitectura depende
de compatibilidad con tablas legacy, datos historicos y reglas de estado.

## Referencias y archivos revisados

- `AGENTS.md`
- `app/Models/User.php`
- `app/Services/Legacy/`
- `app/Services/Diagnostics/`
- `app/Console/Commands/`
- `app/Http/Controllers/AuthController.php`
- `app/Http/Controllers/PanelController.php`
- `database/migrations/` como referencia, sin modificar
- `docs/architecture/`
- `docs/database/`
- `docs/development/`
- `docs/legacy/`
- `docs/security/`

## Validaciones realizadas

- Inventario de modelos y comprobacion de ausencia de `app/Repositories`.
- Busqueda de `DB::table`, Query Builder, relaciones Eloquent, tablas y
  columnas legacy.
- Lectura del modelo autenticable y contraste con documentacion existente.
- Revision de migraciones unicamente como referencia estructural.
- No se modificaron codigo, BD, migraciones ni pruebas.
- No se ejecutaron `git add`, `git commit` ni `git push`.

# Ejecucion de restauracion segura en entorno aislado

## Backup utilizado

| Dato | Valor |
| --- | --- |
| Archivo original | `storage/app/backups/mantenimientos_actual_20260730_114546.sql` |
| Fecha del archivo | 30/07/2026 11:45:46 |
| Tamano | 58,512 bytes, aproximadamente 58 KB |
| Motor esperado | MySQL/MariaDB |
| Hash SHA-256 original | `F01DCBD9DF739624448458177BF72BFFE076052EC0DC7E5C4CBE3E5A5D866E90` |

El archivo original no fue editado. Se creo una copia temporal en
`C:\Temp\MantenimientosRestoreExecution20260904\backup.sql`.

## Preparacion

Antes de importar se reviso la metadata y las sentencias estructurales del
backup. Se confirmo:

- contenia `USE mantenimientos`;
- contenia `DROP TABLE`, `CREATE TABLE` e `INSERT`;
- no se detectaron `DROP DATABASE`, procedimientos, funciones, triggers ni
  eventos en la validacion realizada.

La copia temporal reemplazo unicamente su destino `USE` por
`mantenimientos_restore_execution_20260904`. El hash de la copia fue distinto,
como era esperado tras esa neutralizacion. El original permanecio intacto.

## Procedimiento ejecutado

1. Se verifico que MySQL respondia en `127.0.0.1:3306`.
2. Se creo la base separada `mantenimientos_restore_execution_20260904`.
3. Se valido que la copia de trabajo apuntara unicamente a esa base.
4. Se importo la copia temporal en la base aislada.
5. El cliente MySQL termino con codigo de salida cero.
6. Se consultaron tablas, estructura y conteos solo en la base aislada.
7. Se consulto la base activa unicamente para confirmar sus conteos actuales;
   no se ejecuto ninguna nueva restauracion sobre ella.

## Resultado de restauracion

La restauracion de la copia neutralizada fue exitosa para el componente SQL:

- Se crearon 14 tablas.
- No se produjeron errores del cliente MySQL.
- La base activa `mantenimientos` no fue usada como destino en esta ejecucion.

Tablas restauradas observadas:

`areas`, `configuracion_sistema`, `failed_jobs`, `mantenimientos`,
`migrations`, `password_resets`, `permisos`, `personal_access_tokens`,
`rol_permisos`, `roles`, `tecnicos`, `users`, `usuarios` y
`usuarios_sistema`.

## Validacion de datos

| Tabla o dato | Resultado aislado |
| --- | ---: |
| `usuarios_sistema` | 2 registros |
| `usuarios` | 60 registros |
| `mantenimientos` | 155 registros |
| `tecnicos` | 3 registros |
| `areas` | 10 registros |
| `mantenimientos` con evidencia registrada | 9 |
| `mantenimientos` sin evidencia registrada | 146 |
| Máximo ID de `mantenimientos` | 835 |
| Última actualización observada | 2026-07-30 11:14:16 |

### Tablas críticas ausentes

- `mantenimiento_eventos`: no fue creada por este backup.
- `empresas`: no fue creada por este backup.

Esto demuestra que el dump restaura una version parcial o anterior del esquema
actual documentado. No es suficiente por si solo para reconstruir la version
actual completa de la aplicacion.

## Validacion de archivos

No existe un backup de `storage/app/public` asociado a esta prueba. Por tanto:

- no se restauraron evidencias PDF;
- no se pudo comparar el almacenamiento restaurado con las rutas de BD;
- no se pudo validar la recuperacion conjunta de datos y archivos.

El almacenamiento actual del proyecto existe y contiene 15 archivos, 14 de
ellos con extension PDF. Esos archivos pertenecen al entorno actual y no se
consideran resultado de esta restauracion. El enlace `public/storage` no fue
detectado en la verificacion realizada.

El backup aislado contiene 9 rutas no vacias en `mantenimientos.evidencia_pdf`,
pero no existe un storage restaurado para comprobarlas fisicamente.

## Problemas encontrados

### Critico - Destino fijo del dump original

El original incluye `USE mantenimientos`. No debe importarse directamente en
una instancia que contenga datos activos.

### Alto - Esquema incompleto para la aplicacion actual

No incluye `mantenimiento_eventos` ni `empresas`. La restauracion SQL fue
exitosa, pero no representa todo el esquema actualmente usado.

### Alto - Evidencias no respaldadas

No se encontro un backup de PDFs ni de `storage/app/public`. La capacidad de
recuperar evidencias no fue demostrada.

### Medio - Restauracion en la misma instancia MySQL

El destino fue una base separada en la instancia local, con el `USE` del dump
neutralizado en una copia. El procedimiento fue seguro para esta ejecución,
pero una instancia independiente seguiria siendo mas segura para futuras
pruebas.

## Capacidad real de recuperacion

### Comprobada

- El SQL puede restaurarse en una base aislada cuando se controla su destino.
- Las tablas incluidas y sus datos basicos pueden consultarse despues de la
  importacion.
- El backup original puede conservarse sin editar mientras se usa una copia de
  trabajo.

### No comprobada

- Recuperacion completa del esquema actual.
- Recuperacion de `mantenimiento_eventos`.
- Recuperacion de empresas, si fueran requeridas por el entorno actual.
- Recuperacion de evidencias PDF.
- Recuperacion conjunta y consistente de BD, archivos y configuracion.
- Restauracion en una instancia limpia independiente.

## Recomendaciones futuras

- Generar backups completos que incluyan todas las tablas usadas actualmente.
- Incluir `storage/app/public` o un almacenamiento equivalente de evidencias.
- Automatizar la validacion y neutralizacion de destinos SQL antes de importar.
- Preferir una instancia MySQL aislada, no solo otra base en el mismo servidor.
- Comparar estructura, conteos, hashes y rutas de evidencia despues de cada
  prueba.
- Mantener la base `mantenimientos_restore_execution_20260904` pendiente de
  limpieza autorizada; no ejecutar `DROP DATABASE` sin aprobacion.

## Archivos y entornos afectados

- Backup original: solo lectura, no modificado.
- Copia temporal: creada y neutralizada fuera del proyecto.
- Base aislada: `mantenimientos_restore_execution_20260904`.
- Base activa: solo consultada despues de la prueba; no fue destino de esta
  ejecucion segura.
- Archivos PDF actuales: no modificados.

## Validacion final

- La restauracion aislada SQL termino correctamente.
- No se restauraron archivos PDF.
- No se modificaron backups originales.
- No se modifico codigo.
- No se modificaron migraciones.
- No se ejecuto `git add`, `commit` ni `push`.

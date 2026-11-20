# Auditoria de corrupcion de caracteres

## Alcance

Auditoria de solo lectura realizada sobre la base `mantenimientos` y el
codigo que normaliza nombres. No se ejecutaron `UPDATE`, comandos de
reparacion, migraciones ni cambios de codigo.

## Evidencia encontrada

La tabla `usuarios` contiene 23 nombres con el texto literal `??`. La
consulta hexadecimal confirma bytes `3F3F`, por lo que no se trata de una
fuente, CSS o interpretacion del navegador: los caracteres originales fueron
reemplazados por signos de interrogacion en los datos almacenados.

Ejemplos comprobados:

- `Diana Guti??rrez`
- `Santiago Hern??ndez`
- `M??nica Villafa??e`
- `Ana Ximena Guti??rrez`

No se encontraron coincidencias de `??`, `Ã` o `Â` en `areas.nombre`,
`tecnicos.nombre`, `mantenimientos.observaciones` ni
`mantenimientos.tecnico_responsable` mediante la consulta realizada.

Todas las columnas textuales revisadas usan `utf8mb4` y
`utf8mb4_unicode_ci`; la configuracion Laravel tambien declara `utf8mb4`.

## Tablas afectadas

### `usuarios.nombre`

Es la unica columna con registros afectados confirmados en esta auditoria.
Los 23 IDs encontrados son:

`2, 3, 6, 7, 8, 9, 10, 12, 18, 19, 20, 21, 23, 25, 26, 30, 32, 34, 37, 39, 41, 42, 50`.

El servicio `UsuarioNombreRepairService` contiene un catalogo de correcciones
para parte de estos valores, pero varias salidas propuestas son mojibake,
por ejemplo `Santiago HernÃ¡ndez`, no `Santiago Hernández`. Esto demuestra un
riesgo en la reparacion propuesta, no una correccion aplicada por esta
auditoria.

## Riesgo

### Alto - perdida de informacion en nombres

Los bytes `3F3F` no permiten recuperar automaticamente cada caracter
original. Una reparacion masiva basada solo en conversiones de charset puede
producir nuevos nombres incorrectos.

### Medio - catalogo de reparacion incorrecto

Si se ejecuta el servicio actual sin revisar sus valores destino, puede
convertir nombres con `??` en texto `Ã...`, empeorando la calidad de los datos.

### Bajo - alcance no confirmado fuera de columnas consultadas

La auditoria cubrio las tablas y columnas textuales principales identificadas
en el esquema actual. No constituye prueba de que no existan casos en otras
columnas o tablas no incluidas en las consultas focalizadas.

## Campos recuperables

Los nombres pueden ser recuperables de forma controlada cuando exista una
fuente externa confiable, como un inventario, documento o respaldo que
contenga el nombre correcto. Los ejemplos permiten inferir algunas formas
probables, pero esas inferencias requieren validacion humana antes de
escribirlas.

## Campos que requieren validacion humana

Requieren validacion individual los 23 nombres afectados, especialmente los
casos con mas de una pareja `??`, nombres propios ambiguos y valores de
prueba como `Port??til Pruebas` o `Recepci??n`. No se autoriza inferir ni
aplicar correcciones solo por similitud textual.

## Estrategia recomendada

### Hechos comprobados

1. La corrupcion esta almacenada en `usuarios.nombre`.
2. La conexion y las tablas usan una codificacion Unicode compatible.
3. El servicio actual contiene destinos de reparacion que deben revisarse.

### Inferencias

El origen probable es una importacion o escritura historica con charset
incorrecto, pero no puede determinarse el proceso exacto solo con los datos
actuales.

### Pendientes

1. Ejecutar solo un inventario/dry-run del catalogo revisado.
2. Comparar cada nombre con una fuente autorizada.
3. Generar backup verificable antes de cualquier escritura.
4. Corregir primero el catalogo destino y probarlo.
5. Auditar nuevamente todas las columnas textuales despues de una eventual
   correccion autorizada.

No se modificaron registros durante esta tarea.

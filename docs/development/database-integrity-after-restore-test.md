# Verificacion de integridad posterior a prueba de restauracion

## Contexto del incidente

Se intento probar el backup
`storage/app/backups/mantenimientos_actual_20260730_114546.sql` en la base
aislada `mantenimientos_restore_audit_20260904`.

El dump contiene `USE mantenimientos`, junto con `DROP TABLE` y `CREATE TABLE`.
Por esa razon, la opcion de destino del cliente MySQL no fue suficiente para
aislar la operacion. La base aislada quedo sin tablas y la prueba se detuvo al
detectar el riesgo.

## Verificaciones realizadas

Todas las consultas siguientes fueron de solo lectura sobre la base activa
`mantenimientos`:

- listado de tablas existentes;
- conteos y rangos de IDs de tablas criticas;
- columnas y tipos de tablas criticas mediante `information_schema`;
- conteos de tablas funcionales auxiliares;
- tipos y fechas de eventos registrados;
- fechas maximas de actualizacion disponibles.

No se ejecutaron restauraciones adicionales, SQL destructivo, migraciones,
updates, deletes ni eliminacion de bases.

## Estado encontrado

### Tablas criticas

| Tabla | Estado observado | Conteo | Rango de ID |
| --- | --- | ---: | --- |
| `usuarios_sistema` | Existe | 2 | 1 - 2 |
| `mantenimientos` | Existe | 155 | 1 - 835 |
| `mantenimiento_eventos` | Existe | 9 | 1 - 9 |
| `usuarios` | Existe | 60 | No calculado en esta consulta |
| `tecnicos` | Existe | 3 | 1 - 3 |
| `empresas` | No existe | No aplica | No aplica |

Tablas auxiliares observadas: `areas`, `configuracion_sistema`, `roles`,
`permisos`, `rol_permisos`, `migrations`, `failed_jobs`, `users`,
`password_resets` y `personal_access_tokens`.

### Estructura relevante

Se observaron, entre otros, los siguientes campos:

- `mantenimientos`: `usuario_id`, fechas, `estado`, `observaciones`,
  `evidencia_pdf` y `pendiente_semestre_key`.
- `mantenimiento_eventos`: `mantenimiento_id`, `tipo_evento`,
  `descripcion`, `datos_antes`, `datos_despues`, `usuario_sistema_id`, `ip` y
  `created_at`.
- `usuarios_sistema`: credenciales legacy, `rol` y `estado`.

Los tipos actuales observados son compatibles con la aplicacion documentada,
pero una consulta de estructura no demuestra que cada indice, constraint o
dato historico coincida con un estado previo.

### Actividad observada

- `mantenimientos`: ultima actualizacion observada, 2026-07-30 11:14:16.
- `usuarios`: ultima actualizacion observada, 2026-07-30 11:14:16.
- `usuarios_sistema`: ultima actualizacion observada, 2026-07-30 09:13:24.
- `mantenimiento_eventos`: 7 eventos `programado` y 2 eventos `realizado`.
- Los eventos observados tienen fechas entre 2026-09-01 y 2026-09-02.

La existencia de eventos de septiembre muestra actividad posterior a la fecha
del dump y es una señal de que la tabla de eventos no fue reemplazada por ese
archivo, porque no aparece en las tablas identificadas del dump. No prueba,
por si sola, que las demas tablas no hayan sido reescritas.

## Evidencia de afectacion o no afectacion

### Afectacion potencial confirmada

La afectacion potencial esta confirmada por el propio contenido del dump y el
comando de importacion: `USE mantenimientos` dirige las sentencias a la base
activa. El dump incluye operaciones de reemplazo de tablas legacy.

### No afectacion no comprobable

No hay un snapshot previo de la base activa tomado inmediatamente antes de la
prueba. Por ello no es posible demostrar mediante los conteos actuales que no
hubo reemplazo o perdida de cambios locales.

Los conteos actuales y las tablas criticas disponibles indican que la base
continua operativa, pero no permiten certificar integridad historica ni
comparar cada registro con el estado anterior.

### Destino aislado

`mantenimientos_restore_audit_20260904` tiene cero tablas. No contiene una
restauracion utilizable y permanece pendiente de limpieza autorizada.

## Riesgos pendientes

- El dump seleccionado no es seguro para importar directamente en una
  instancia que contenga la base activa.
- Podrian haberse reemplazado tablas incluidas en el dump, aunque los datos
  actuales parezcan coherentes.
- `empresas` no existe en la base activa; no debe asumirse que sea necesaria
  sin confirmar el esquema real usado por la aplicacion.
- No se comprobo backup de `storage/app/public` ni de evidencias PDF.
- No existe comparacion de hashes, conteos previos ni inventario completo
  anterior a la prueba.
- La base aislada creada requiere limpieza autorizada.

## Recomendaciones

1. No volver a importar este dump directamente en la instancia activa.
2. Obtener o generar una copia de seguridad autorizada del estado actual antes
   de cualquier recuperacion.
3. Comparar la base activa con respaldos mediante un procedimiento de solo
   lectura y, si procede, restaurar en una instancia MySQL separada, no solo
   en otra base del mismo servidor.
4. Repetir la prueba usando una copia temporal del dump cuyo destino haya sido
   neutralizado en memoria o mediante un proceso controlado, sin modificar el
   archivo original.
5. Respaldar conjuntamente la base y `storage/app/public` y comprobar las
   rutas de `mantenimientos.evidencia_pdf`.
6. Autorizar por separado cualquier restauracion o limpieza de la base
   `mantenimientos_restore_audit_20260904`.

## Validacion y restricciones

- No hubo cambios de BD durante esta verificacion.
- No hubo restauraciones adicionales.
- No hubo migraciones.
- No hubo cambios de codigo.
- No se alteraron backups originales.
- No se ejecutaron `git add`, `git commit` ni `git push`.

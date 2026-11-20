# Conciliacion del estado de la base de datos

## Estado actual confirmado

Laravel usa actualmente la conexión MySQL de `127.0.0.1:3306` y la base
`mantenimientos`. No se expusieron contraseñas ni archivos de entorno.

La base activa contiene:

- 60 usuarios, IDs 1 a 508.
- 155 mantenimientos, IDs 1 a 835.
- 9 registros en `mantenimiento_eventos`.
- 3 técnicos.
- nombres UTF-8 corregidos en los registros auditados.

La corrección de nombres del 4 de septiembre actualizó 23 registros y una
consulta posterior confirmó que no quedan nombres con `??`.

## Bases encontradas

Se identificaron las siguientes bases relacionadas:

| Base | Tablas | Estado observado |
| --- | ---: | --- |
| `mantenimientos` | 15 | Base usada por Laravel |
| `mantenimientos_restore_audit_20260904` | Pendiente de verificación | Destino de prueba anterior, documentado como vacío |
| `mantenimientos_restore_execution_20260904` | 14 | Restauración aislada del backup del 30/07 |
| `mantenimientos_bd4_auditoria` | 19 | Base de auditoría vacía observada |
| `mantenimientos_esquema_bd4_pruebas` | 19 | Base de pruebas vacía observada |
| `mantenimientos_limpia_estudio` | 19 | Base de estudio vacía observada |
| `mantenimientos_normalizacion_pruebas` | 19 | Base de pruebas con datos diferentes |

La restauración aislada no contiene `mantenimiento_eventos`; la base activa
sí contiene esa tabla y nueve eventos.

## Diferencias encontradas

El dump `mantenimientos_actual_20260730_114546.sql` corresponde al 30/07/2026
y contiene 14 tablas. No contiene `mantenimiento_eventos` ni `empresas`.

Comparación básica:

| Dato | Base activa | Restauración aislada |
| --- | ---: | ---: |
| Usuarios | 60 | 60 |
| Mantenimientos | 155 | 155 |
| Usuarios de sistema | 2 | 2 |
| Técnicos | 3 | 3 |
| Eventos | 9 | Tabla ausente |
| Última actualización de usuarios | 2026-09-04 12:11:00 | 2026-07-30 11:14:16 |
| Última actualización de mantenimientos | 2026-09-04 11:13:50 | 2026-07-30 11:14:16 |

La base activa tiene dos mantenimientos con actualización posterior al dump:
IDs 181 y 202. Los eventos activos también son posteriores al backup.

## Usuarios investigados

Los registros consultados son diferentes, no el mismo registro en versiones
alternas:

| ID | Nombre actual | Código activo | Serial | Área | Creado | Actualizado |
| ---: | --- | ---: | --- | ---: | --- | --- |
| 4 | Kevin Hernandez | 479 | M1N0CX05H22502E | 1 | 2026-04-22 11:03:59 | 2026-09-04 12:11:00 |
| 25 | María del Pilar Ibarra Rojas | 574 | System Serial Number6 | 6 | 2026-04-22 11:03:59 | 2026-09-04 12:11:00 |
| 41 | María Del Pilar Ibarra Rojas 2 | 670 | NBNOCX075279483 | 6 | 2026-04-22 11:03:59 | 2026-09-04 11:45:50 |

Los tres IDs, códigos y seriales son distintos. Por lo tanto, la evidencia
actual indica registros diferentes y una posible duplicidad nominal entre 25
y 41, no una sustitución comprobada de María por Kevin.

Kevin también existe en el dump histórico. María aparece allí con el texto
corrupto `Mar??a del Pilar Ibarra Rojas`, mientras que el valor actual fue
corregido a UTF-8. Esto confirma una corrección de caracteres, no una prueba
de pérdida del registro.

## Evidencia de pérdida o no pérdida

### Confirmado

- Laravel apunta a `mantenimientos`, no a una base de restauración.
- Kevin, María y María 2 tienen IDs distintos.
- Kevin ya existía en el backup histórico.
- La base activa conserva datos posteriores al backup: eventos y
  actualizaciones posteriores.
- El backup histórico no contiene la tabla actual de eventos.

### No confirmado

- No existe snapshot inmediato anterior al incidente.
- No se hizo comparación completa fila por fila de todas las tablas.
- No puede determinarse si alguna tabla fue temporalmente reemplazada durante
  la importación riesgosa.
- No puede comprobarse el origen del recuerdo de que el ID de María hubiera
  sido renombrado a Kevin.

### Interpretación prudente

No hay evidencia actual de reemplazo total por el backup antiguo. La hipótesis
con mayor soporte es que Kevin y los dos registros de María coexistían, y que
la confusión corresponde a una duplicidad nominal o a un cambio manual no
documentado en otro momento. La posibilidad de afectación parcial histórica
permanece abierta por la ausencia de un snapshot previo.

## Recomendación

1. No restaurar ni copiar datos desde el dump histórico.
2. Conservar la base activa como fuente actual mientras no aparezca evidencia
   concreta de pérdida.
3. Si se requiere investigar el cambio de nombre recordado, buscar un backup
   o documento externo que registre el estado anterior del ID 4 o del ID 25.
4. Comparar filas completas solo mediante un procedimiento de lectura y
   snapshots autorizados.
5. Mantener separadas las bases de pruebas para evitar confundir sus datos
   con los de Laravel.

## Validaciones y restricciones

- Se usaron `SELECT`, inventario de `information_schema`, lectura de metadata
  y comparación estática del dump.
- No hubo restauraciones, migraciones, `UPDATE`, `DELETE` ni cambios de datos.
- No se eliminaron archivos ni bases.
- No se modificó código.
- Se ejecutó `git diff --check`.
- No se ejecutó `git add`, commit ni push.

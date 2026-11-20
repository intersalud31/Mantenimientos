# Auditoria de integridad de datos

## Alcance y metodo

Auditoria de solo lectura sobre la base `mantenimientos`, el codigo legacy y
`php artisan evidencias:auditar`. No se ejecutaron `UPDATE`, `DELETE`,
migraciones ni correcciones automaticas.

## Hechos comprobados

### Usuarios

| Validacion | Cantidad | Resultado |
| --- | ---: | --- |
| Total | 60 | Confirmado |
| Nombre vacio o nulo | 0 | No detectado |
| Codigo activo vacio | 0 | No detectado |
| Estado de usuario nulo o fuera de 0/1 | 0 | No detectado |
| Nombres duplicados normalizados | 0 | No detectado |
| Codigos activos duplicados | 0 | No detectado |

### Mantenimientos

| Validacion | Cantidad | Resultado |
| --- | ---: | --- |
| Total | 155 | Confirmado |
| Usuario inexistente | 0 | No detectado |
| Usuario nulo | 0 | No detectado |
| Fecha programada nula o cero | 0 | No detectado |
| Ejecucion anterior a programacion | No obtenido | Requiere consulta especifica |
| Ejecucion futura | No obtenido | Requiere consulta especifica |
| Duplicado por usuario y fecha programada | 0 grupos | No detectado |
| Duplicado logico completo | 0 grupos | No detectado |

Los estados almacenados son: `pendiente` (88), `programado` (7), `realizado`
(54) y `cancelado` (6). El codigo reconoce `realizado` y `hecho` como estados
visibles de ejecucion, y `programado` y `reprogramado` como programados.

La tabla no tiene columna `tecnico_id`; la responsabilidad se almacena en
`tecnico_responsable`. Por ello no se reporta una FK de tecnico inexistente.

Se observaron 44 mantenimientos realizados sin `tecnico_responsable`. Esto es
un hallazgo operativo potencial, pero su obligatoriedad funcional no puede
confirmarse solo con el esquema.

### Eventos

- Total de eventos: 9.
- Tipos: 7 `programado`, 2 `realizado`.
- Eventos sin mantenimiento relacionado: 0.
- Eventos con tipo vacio: 0.
- Eventos con fecha futura: 0.
- Mantenimientos sin evento: 148.

La cifra de mantenimientos sin evento incluye historicos y pendientes; no es
correcto asumir que todos debian tener bitacora, porque la migracion de
eventos fue progresiva.

### Evidencias

`php artisan evidencias:auditar` reporto:

- 9 rutas registradas.
- 7 archivos existentes.
- 2 rutas faltantes: mantenimientos 65 y 73.
- 8 archivos fisicos sin referencia clara.
- Se incluyo un `.gitignore` entre los archivos sin referencia, por lo que el
  conteo bruto de huerfanos requiere depuracion antes de una decision.

## Riesgos identificados

### Alto - evidencias faltantes

**Tabla/campo:** `mantenimientos.evidencia_pdf`.

**Cantidad:** 2 confirmadas por la herramienta.

**Impacto:** el sistema conserva una referencia que no permite descargar el
archivo esperado. No debe reasignarse ningun PDF sin evidencia documental.

### Alto - mantenimientos ejecutados sin responsable

**Tabla/campo:** `mantenimientos.tecnico_responsable`.

**Cantidad:** 44.

**Ejemplo:** mantenimiento 66, estado `realizado`, fecha de ejecucion
`2026-01-15`, responsable nulo.

**Impacto:** perdida de trazabilidad operativa si el responsable es requerido
para auditoria. La regla funcional debe confirmarse antes de corregir.

### Medio - historial de eventos incompleto

**Tabla:** `mantenimiento_eventos`.

**Cantidad:** 148 mantenimientos sin eventos.

**Impacto:** limita la auditoria de historicos. La ausencia puede ser normal
para registros anteriores a la migracion progresiva.

### Medio - archivos fisicos sin referencia

**Cantidad:** 8 resultados brutos, con al menos un archivo de control que no es
una evidencia PDF.

**Impacto:** riesgo de almacenamiento sin ciclo de vida claro y de
reasignaciones incorrectas.

### Medio - validaciones de fechas incompletas en esta corrida

**Estado:** no se obtuvo cantidad para ejecuciones anteriores a programacion
ni ejecuciones futuras porque la consulta inicial intento usar una columna
inexistente (`tecnico_id`) y se detuvo antes de esas expresiones. No se debe
interpretar como cero.

## Hipotesis

- Los 148 mantenimientos sin eventos probablemente incluyen historicos creados
  antes de `mantenimiento_eventos`; debe comprobarse por fechas y flujo de
  origen.
- Los 44 responsables nulos pueden corresponder a datos historicos, no
  necesariamente a una violacion actual.
- Algunos archivos huerfanos pueden ser versiones antiguas o evidencias
  asociadas a registros cuyo nombre/ruta cambio.

Estas hipotesis no autorizan cambios de datos.

## Puntos que requieren decision humana

1. Definir si todo `realizado` debe tener responsable.
2. Definir el universo historico que debe tener eventos.
3. Conciliar individualmente las evidencias 65 y 73.
4. Revisar los PDFs huerfanos antes de conservar, archivar o eliminar.
5. Ejecutar consultas de fechas corregidas y documentar sus resultados.

## Recomendaciones

### Critico

- No ejecutar limpieza automatica de evidencias ni correcciones masivas.
- Mantener backup verificable antes de cualquier escritura.

### Alto

- Resolver mediante investigacion documental las dos rutas faltantes.
- Definir y probar una regla para responsables de mantenimientos ejecutados.
- Completar la auditoria de fechas usando los nombres reales del esquema.

### Medio

- Separar archivos de control de los huerfanos reportados.
- Crear pruebas de integridad para usuario, estado, fechas, eventos y
  evidencia.

### Bajo

- Automatizar reportes periodicos de calidad sin convertirlos en procesos de
  correccion automatica.

## Validacion final

- Solo se ejecutaron consultas `SELECT` y el comando de auditoria de
  evidencias.
- `git diff --check` fue ejecutado.
- No hubo cambios en BD, migraciones, codigo ni datos.
- No se ejecutaron `git add`, commit ni push.

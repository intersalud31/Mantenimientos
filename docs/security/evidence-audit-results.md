# Resultados de auditoria de evidencias PDF

## Resumen ejecutivo

- **Fecha de ejecucion:** 2026-09-02.
- **Ambiente:** entorno local actual de Mantenimientos-Laravel, mediante el comando evidencias:auditar.
- **Objetivo:** conocer la correspondencia entre las rutas registradas en mantenimientos.evidencia_pdf y los archivos disponibles en el disco public, sin modificar datos ni archivos.

## Resultados

| Indicador | Resultado |
| --- | ---: |
| Referencias de evidencia registradas | 14 |
| Archivos registrados existentes | 12 |
| Archivos registrados faltantes | 2 |
| Archivos fisicos sin referencia | 3 |
| Artefactos no PDF entre los no referenciados | 1 (.gitignore) |
| Posibles PDFs huerfanos | 2 |

### Referencias faltantes

No se encontraron en el disco public:

- Mantenimiento 65: publico/uploads/constancias/ACTA DE MANTENIMIENTO GERENCIA.pdf
- Mantenimiento 73: publico/uploads/constancias/pc viviana.pdf

Estas rutas requieren verificacion manual antes de corregir registros o
reubicar archivos. La auditoria no modifica ninguna referencia.

### Posibles PDFs huerfanos

El disco contiene archivos que no coinciden con ninguna ruta registrada:

- constancias/constancia_175_cd8bd59c-7dbb-4c01-b6cf-a27a6d371ab1.pdf
  (2,083,358 bytes)
- constancias/constancia_177_dc4f65a5-3ca0-45d7-8a18-5599c553b0dc.pdf
  (322,883 bytes)

Tambien fue reportado .gitignore (14 bytes). No es una evidencia PDF y debe
considerarse un artefacto del almacenamiento, no un archivo huerfano de
mantenimiento.

## Hallazgos

### Inconsistencias confirmadas

1. Existen dos registros de mantenimiento con una ruta de evidencia que no
   corresponde a un archivo encontrado en el disco auditado.
2. Existen dos archivos PDF sin correspondencia exacta en
   mantenimientos.evidencia_pdf.
3. El disco auditado incluye un archivo .gitignore dentro del conjunto
   recorrido por la herramienta; por tanto, el conteo bruto de huerfanos
   incluye un artefacto que no es evidencia.

### Riesgos potenciales

- Una ruta faltante puede impedir consultar o descargar la evidencia asociada.
- Un PDF huerfano puede ocupar espacio sin estar disponible desde el sistema.
- Los nombres de archivos con espacios y rutas legacy distintas (publico/uploads
  frente a constancias) pueden dificultar la reconciliacion automatica.
- No es posible determinar solo con esta auditoria si los PDFs huerfanos
  pertenecen a otro registro, son versiones reemplazadas o deben conservarse.

### Casos que requieren revision manual

- Confirmar si las evidencias de los mantenimientos 65 y 73 existen en una
  ubicacion legacy o en un respaldo.
- Confirmar la pertenencia de los PDFs asociados a los mantenimientos 175 y
  177 mediante nombre, fecha, contenido o respaldo operativo.
- Revisar si los archivos huerfanos son versiones anteriores generadas durante
  reemplazos de evidencia.
- Verificar si publico/uploads/constancias corresponde a una ruta historica que
  ya no utiliza el disco actual.

## Recomendaciones

### P0 - Antes de produccion

- Confirmar mediante respaldo y revision funcional las dos referencias
  faltantes antes de realizar cualquier correccion.
- Definir una politica de autorizacion y trazabilidad para reasociar o retirar
  archivos; no ejecutar limpieza automatica con estos resultados.
- Asegurar que las pruebas de carga, asociacion, consulta y descarga cubran
  tanto archivos existentes como referencias faltantes.

### P1 - Despues de estabilizar

- Mejorar la auditoria para excluir artefactos conocidos como .gitignore y
  clasificar unicamente archivos PDF como evidencias potenciales.
- Incorporar una revision controlada de archivos huerfanos y referencias
  faltantes, con registro de cada decision.
- Estandarizar progresivamente las rutas nuevas sin reescribir historicos
  hasta confirmar su existencia y compatibilidad.

### P2 - Evolucion futura

- Automatizar reportes periodicos de integridad archivo-registro.
- Diseñar un ciclo de vida explicito para reemplazo, versionado y retencion de
  evidencias.
- Evaluar almacenamiento privado o acceso autorizado si la exposicion publica
  del disco no resulta adecuada para el entorno de produccion.

## Alcance y limites

La ejecucion fue de solo lectura. No se eliminaron archivos, no se actualizaron
registros, no se ejecutaron migraciones y no se modificaron pruebas ni codigo
productivo durante la auditoria.

# Auditoria de backup y recuperacion

## Alcance

Auditoria estatica y de metadata realizada el 2026-09-03 sobre el proyecto
Mantenimientos-Laravel. No se abrieron respaldos, no se ejecutaron
restauraciones y no se modificaron archivos, base de datos ni configuración.

## Estado actual

### Comprobado

- Existen archivos SQL en storage/app/backups y db-rescue-20260731.
- Existe un comando de auditoria de base de datos:
  mantenimientos:auditoria --database=<nombre>.
- Existe un snapshot lógico de una base aislada: bd3:snapshot.
- Existe la auditoria específica de evidencias: evidencias:auditar.
- Las evidencias se almacenan en storage/app/public mediante el disco public.
- El proyecto documenta el uso de php artisan storage:link para exponer
  public/storage.

### No comprobado

- No se encontró un comando propio que genere un backup completo de la base de datos de producción.
- No se encontró un comando o proceso que incluya storage/app/public en un backup.
- No se encontró un procedimiento automatizado de restauración.
- No se comprobó que los dumps SQL sean restaurables ni que correspondan a la base actualmente activa.
- No se comprobó retención, frecuencia, cifrado, copia externa ni monitoreo de los respaldos.

## Base de datos

### Mecanismos encontrados

BaseDatosAuditoriaCommand y BaseDatosAuditoriaService realizan lecturas
controladas de la base confirmada. El servicio obtiene conteos y huellas
SHA-256 de tablas principales dentro de una transacción de lectura; no genera
un dump restaurable.

Bd3SnapshotCommand genera una fotografía lógica JSON de la base aislada
mantenimientos_normalizacion_pruebas y la guarda en storage/app/diagnostics.
Incluye inventario de esquema, fotografía funcional y huellas lógicas, pero
está diseñado para diagnóstico de una base de pruebas, no como backup
operativo de producción.

### Ubicaciones observadas

- storage/app/backups: cuatro archivos SQL fechados el 30/07/2026.
- db-rescue-20260731: archivos SQL de rescate y estructuras fechados el
  31/07/2026.

Los nombres, extensiones, tamaños y fechas fueron consultados sin abrir el
contenido. Su alcance exacto y restaurabilidad son no comprobables.

### Riesgos

- Los dumps pueden quedar desactualizados frente a la base activa.
- No existe evidencia documentada de una restauración de prueba.
- Los nombres indican bases o propósitos distintos y podrían generar una
  restauración sobre el destino equivocado.
- Los snapshots lógicos no sustituyen un backup restaurable completo.

## Archivos y evidencias

### Estado comprobado

Las evidencias se relacionan con mantenimientos mediante la ruta almacenada en
mantenimientos.evidencia_pdf. Los archivos físicos están en el disco public,
bajo storage/app/public, y pueden quedar accesibles mediante public/storage.

La auditoria evidencias:auditar compara referencias de BD contra archivos del
disco y reporta faltantes y posibles huérfanos. Es una herramienta de
diagnóstico de solo lectura; no crea copias ni recupera archivos.

### Respaldo encontrado

No se encontraron archivos PDF identificables en las ubicaciones de respaldo
revisadas. Los respaldos identificados son SQL y no contienen, según su tipo y
nombre, una copia física del directorio de evidencias.

No existe recuperación de evidencias documentada ni un proceso comprobado que
empaquete simultáneamente la base de datos y storage/app/public.

### Riesgo de recuperación parcial

La base puede conservar una ruta mientras el PDF ya no existe, o puede existir
un PDF sin referencia en la base. Restaurar solo un dump SQL no recuperaría
automáticamente el archivo físico; restaurar solo storage tampoco garantiza
la referencia correcta en la base.

## Riesgos

### Confirmados

- No hay respaldo PDF identificable en las ubicaciones revisadas.
- El proceso de backup de evidencias no está implementado ni documentado.
- La herramienta de auditoria detectó referencias faltantes y posibles PDFs huérfanos.
- Los comandos existentes son de auditoria o diagnóstico, no de recuperación integral.

### Potenciales

- Pérdida de evidencias ante daño del disco local.
- Restauración inconsistente entre BD y archivos.
- Uso de un dump incorrecto o desactualizado.
- Exposición o pérdida de respaldos SQL si no tienen controles operativos adecuados.
- Imposibilidad de cumplir un objetivo de recuperación por falta de pruebas, retención y procedimiento documentado.

## Recomendaciones futuras

### P0 - Antes de producción

- Definir y ejecutar, en un entorno aislado, una prueba autorizada de restauración de la base y de las evidencias.
- Establecer un backup coordinado de la base y storage/app/public.
- Confirmar ubicación, permisos, retención y acceso restringido de los respaldos.
- Verificar que una restauración conserve la relación entre mantenimientos.evidencia_pdf y los archivos físicos.
- Mantener la auditoria de evidencias en modo lectura antes de cualquier limpieza.

### P1 - Mejoras posteriores

- Automatizar backups con identificación de base, fecha, integridad y resultado.
- Incluir hashes o inventarios de archivos para detectar cambios.
- Documentar responsables, frecuencia, retención y pasos de recuperación.
- Probar periódicamente escenarios de archivo faltante, BD faltante y restauración parcial.

### P2 - Evolución futura

- Evaluar almacenamiento externo o redundante para evidencias.
- Incorporar versionado, cifrado y replicación según el entorno.
- Definir objetivos formales de tiempo de recuperación y pérdida máxima aceptable.
- Integrar monitoreo de capacidad y alertas de fallos de backup.

## Archivos y documentación revisados

- AGENTS.md
- app/Console/Commands/BaseDatosAuditoriaCommand.php
- app/Console/Commands/Bd3SnapshotCommand.php
- app/Console/Commands/AuditarEvidenciasCommand.php
- app/Services/Diagnostics/BaseDatosAuditoriaService.php
- app/Services/Diagnostics/Bd3SnapshotService.php
- config/filesystems.php
- README.md
- docs/security/evidence-hardening-plan.md
- docs/security/evidence-audit-results.md
- docs/security/evidence-reconciliation-report.md
- docs/security/evidence-backup-review.md
- docs/security/file-lifecycle-audit.md
- docs/development/stabilization-progress.md
- metadata de storage/app/backups y db-rescue-20260731

## Validaciones realizadas

- Búsqueda estática de comandos, servicios y referencias de backup, restauración, snapshot y almacenamiento.
- Revisión de ubicaciones y metadata de respaldos sin abrirlos.
- git diff --check.
- No se ejecutaron restauraciones, limpiezas ni comandos destructivos.
- No hubo cambios de código, base de datos, migraciones, pruebas, archivos ni respaldos.
- No se ejecutaron git add, git commit ni git push.

# Estrategia de backup integral

## Objetivo

Definir la cobertura minima necesaria para recuperar Mantenimientos-Laravel de
forma consistente, incluyendo codigo, esquema y datos de base de datos,
configuracion operativa segura y evidencias PDF.

Esta es una estrategia documental. No crea scripts, no modifica respaldos, no
automatiza procesos y no ejecuta restauraciones.

## Componentes que deben respaldarse

### Aplicacion

- Codigo fuente del proyecto.
- `composer.json` y `composer.lock`.
- `package.json` y archivos de frontend si participan en el despliegue.
- Migraciones y configuracion versionada.
- Versiones requeridas de PHP, extensiones, Composer, Node y servidor web.
- Enlaces, permisos y estructura necesaria de `storage` y `bootstrap/cache`.
- Variables de entorno mediante un mecanismo seguro separado; no deben
  publicarse ni incluirse en dumps o repositorios.

No se deben respaldar secretos en texto plano dentro del repositorio ni en
documentacion.

### Base de datos

El backup debe incluir estructura y datos de todas las tablas usadas por la
aplicacion actual, no solo las incluidas en el dump probado. Como minimo debe
verificarse la cobertura de:

- `usuarios_sistema`.
- `usuarios`.
- `mantenimientos`.
- `mantenimiento_eventos`.
- `tecnicos`.
- `areas`.
- `configuracion_sistema`.
- roles, permisos y tablas de relacion.
- tablas legacy adicionales referenciadas por el codigo.

Debe conservarse:

- estructura de tablas;
- tipos, indices y restricciones aplicables;
- datos historicos;
- estados y fechas de mantenimientos;
- eventos tecnicos;
- referencias de `mantenimientos.evidencia_pdf`.

El dump actual probado recupero 14 tablas, pero no incluyo
`mantenimiento_eventos` ni `empresas`; por ello no es un backup integral del
estado actual documentado.

### Archivos

- `storage/app/public` completo.
- Evidencias PDF referenciadas por `mantenimientos.evidencia_pdf`.
- Archivos generados que la aplicacion necesite para operar.
- Enlaces simbolicos o instrucciones para reconstruir `public/storage`.

El inventario debe incluir ruta relativa, tamano, fecha, hash y relacion con el
mantenimiento cuando sea posible. No se deben mezclar archivos temporales,
logs o cache con evidencias sin una politica explicita.

## Estado actual

### Existe

- Varios dumps SQL locales en `storage/app/backups` y `db-rescue-20260731`.
- Un dump probado parcialmente en una base aislada.
- Comandos de auditoria y snapshot diagnostico.
- Codigo, migraciones y documentacion en el workspace.
- Almacenamiento local `storage/app/public`.

### Falta o no esta comprobado

- Backup integral de todas las tablas actuales.
- Inclusion comprobada de `mantenimiento_eventos`.
- Inclusion comprobada de evidencias PDF.
- Relacion validada entre rutas de BD y archivos recuperados.
- Backup seguro y recuperable de variables de entorno.
- Frecuencia, retencion, copia externa y cifrado operativos.
- Restauracion integral validada en una instancia independiente.

## Riesgos actuales

### Alto

- Recuperar solo el SQL puede dejar mantenimientos con rutas de evidencia sin
  archivo fisico.
- Un dump con `USE mantenimientos`, `DROP TABLE` o `CREATE TABLE` puede afectar
  una base activa si se importa sin aislamiento real.
- Un backup parcial puede omitir eventos y romper la trazabilidad tecnica.

### Medio

- Los archivos SQL pueden estar desactualizados frente al estado actual.
- No hay evidencia comprobada de retencion, copia externa o restauraciones
  periodicas.
- La configuracion necesaria para reconstruir el entorno puede no estar
  disponible de forma segura.

### Bajo

- Los comandos de auditoria ayudan a verificar consistencia, pero no sustituyen
  un backup ni una restauracion.

## Estrategia recomendada

### Frecuencia

La frecuencia debe definirse segun el volumen de cambios y la perdida maxima
aceptable del negocio. Como linea minima a aprobar:

- backup de BD al menos diario;
- backup de evidencias y archivos al menos diario o incremental segun volumen;
- backup completo antes de cambios de esquema, despliegues importantes o
  tareas de mantenimiento;
- prueba de restauracion periodica en entorno aislado.

La frecuencia final queda pendiente de aprobacion operativa.

### Orden de respaldo

1. Registrar fecha, origen, instancia y destino.
2. Generar backup consistente de la base de datos.
3. Generar inventario y copia de `storage/app/public`.
4. Guardar hashes y metadatos de ambos componentes.
5. Verificar que cada ruta de `evidencia_pdf` tenga archivo correspondiente.
6. Proteger el conjunto con acceso restringido y copia fuera del equipo local.

### Validacion

- Confirmar que el dump no apunta a una base activa.
- Revisar `USE`, `DROP DATABASE`, `DROP TABLE`, `CREATE TABLE`, procedimientos,
  funciones y triggers.
- Verificar codigo de salida y errores del proceso.
- Comparar tablas, conteos, rangos, huellas y eventos criticos.
- Comparar inventario de PDFs, hashes y rutas.
- Ejecutar restauracion en instancia aislada, nunca sobre la base operativa.

### Retencion

Debe aprobarse una politica que defina:

- cantidad de copias diarias, semanales y mensuales;
- plazo de conservacion;
- ubicacion primaria y secundaria;
- cifrado y control de acceso;
- procedimiento de eliminacion autorizada;
- responsable de revisar resultados y fallos.

No existe actualmente evidencia suficiente para afirmar una politica vigente.

## Procedimiento de recuperacion esperado

1. **Restaurar BD:** usar una instancia aislada, importar una copia validada y
   confirmar el destino efectivo, tablas, conteos y estructura.
2. **Restaurar archivos:** recuperar `storage/app/public` en la ubicacion
   temporal correspondiente, sin mezclarlo con archivos existentes.
3. **Configurar aplicacion:** instalar dependencias, configurar variables de
   entorno mediante un canal seguro, crear enlaces de storage y verificar
   permisos.
4. **Validar integridad:** comprobar rutas `evidencia_pdf`, PDFs existentes,
   eventos, estados, usuarios, tecnicos y funcionamiento de rutas criticas.
5. **Validar operacion:** ejecutar pruebas automatizadas y verificaciones
   funcionales antes de habilitar usuarios.
6. **Registrar resultado:** conservar fecha, backup usado, hashes, incidencias,
   diferencias y autorizacion del cierre.

## Mejoras futuras

### P0 - Necesario para recuperacion confiable

- Definir un backup completo de BD y `storage/app/public`.
- Incluir y comprobar `mantenimiento_eventos` y todas las tablas actuales.
- Restaurar en una instancia realmente aislada.
- Validar rutas de evidencias contra archivos fisicos.
- Proteger respaldos con acceso restringido y copia externa.
- Ejecutar y documentar una prueba integral de restauracion.

### P1 - Mejoras posteriores

- Incorporar hashes, inventario y verificacion automatica de integridad.
- Formalizar retencion, responsables, frecuencia y criterios de exito.
- Documentar configuracion segura y reconstruccion del entorno.
- Probar escenarios de perdida parcial de BD, archivos o configuracion.

### P2 - Automatizacion

- Automatizar backups coordinados y reportes de resultado.
- Automatizar alertas de antiguedad, espacio e integridad.
- Evaluar almacenamiento redundante o externo.
- Definir y medir RPO/RTO.
- Automatizar pruebas periodicas de recuperacion sin tocar produccion.

## Validacion y alcance

- Documento basado en auditorias y prueba de restauracion parcial existentes.
- Solo se creo documentacion.
- `git diff --check` debe ejecutarse desde el repositorio
  `C:\xampp\htdocs\Mantenimientos-Laravel`.
- No hubo cambios de BD.
- No hubo cambios de codigo.
- No hubo migraciones.
- No se ejecutaron restauraciones.
- No se modificaron backups originales.
- No se ejecutaron `git add`, `commit` ni `push`.

# Auditoria de pruebas fallidas

## Resumen

- Fecha de ejecucion: 2026-09-03.
- Suite ejecutada: php artisan test.
- Pruebas ejecutadas: 169.
- Exitosas: 162.
- Fallidas: 7.
- PHPUnit tambien reporto advertencias de permisos al escribir .phpunit.cache/test-results.

Los fallos se concentran en tests/Feature/PanelActionFlowTest.php. No se
modificaron tests, código, configuración, base de datos ni migraciones durante
esta auditoria.

## Matriz de fallos

| Test | Archivo | Error observado | Clasificacion |
| --- | --- | --- | --- |
| test_pdf_de_evidencia_se_puede_ver_y_descargar | tests/Feature/PanelActionFlowTest.php:42 | Se esperaba HTTP 200 y se recibió 302 al consultar la evidencia. | Test/contrato de autorización o entorno |
| test_permite_marcar_como_hecho_con_fecha_pasada | tests/Feature/PanelActionFlowTest.php:1559 | No se pudo abrir storage/logs/laravel.log y falló la creación del directorio de testing del disco público. | Problema de entorno |
| test_programado_hecho_sube_pdf_y_redirecciona_con_contexto | tests/Feature/PanelActionFlowTest.php:49 | No se pudo escribir el log ni crear el directorio de testing del disco público. | Problema de entorno |
| test_exige_confirmacion_si_la_ejecucion_es_anterior_a_la_programacion | tests/Feature/PanelActionFlowTest.php:1694 | No se pudo escribir el log ni crear el directorio de testing del disco público. | Problema de entorno |
| test_marcar_como_hecho_desde_programados_registra_evento_y_conserva_observacion_humana | tests/Feature/PanelActionFlowTest.php:2095 | No se pudo escribir el log ni crear el directorio de testing del disco público. | Problema de entorno |
| test_marcar_como_hecho_desde_programados_con_observacion_cerca_del_limite_no_la_altera | tests/Feature/PanelActionFlowTest.php:2229 | No se pudo escribir el log ni crear el directorio de testing del disco público. | Problema de entorno |
| test_marcar_como_hecho_desde_programados_revierte_todo_si_falla_el_evento | tests/Feature/PanelActionFlowTest.php:2313 | No se pudo escribir el log ni crear el directorio de testing del disco público. | Problema de entorno |

## Análisis

### Fallo de consulta de evidencia

Estado comprobado: el test autentica un usuario, falsifica el disco public,
crea constancias/evidencia.pdf y espera que la ruta protegida devuelva el PDF.
La respuesta actual es 302 en vez de 200.

Causa probable: el controller redirige cuando no encuentra la evidencia o
cuando no se cumple el contexto de autorización. La salida disponible no
incluye el destino de la redirección, por lo que no es posible afirmar si se
trata de una ruta no visible para el middleware, un mock no resuelto o una
discrepancia entre el fake de almacenamiento y el disco que consulta el
controller.

Clasificación: test/contrato de autorización o entorno, pendiente de aislar.
No debe cambiarse la expectativa sin identificar primero el motivo exacto del
302.

Riesgo: medio. Corregir el controller sin aislar la causa podría debilitar la
protección de evidencias; cambiar el test podría ocultar una regresión.

### Seis fallos de escritura

Estado comprobado: todos fallan al ejecutar flujos que almacenan un PDF o
registran una excepción. La salida reporta:

- storage/logs/laravel.log sin permisos para append;
- imposibilidad de crear directorios bajo
  storage/framework/testing/disks/public/constancias;
- advertencia adicional al escribir .phpunit.cache/test-results.

Causa probable: permisos de escritura del usuario que ejecuta PHP sobre
storage/logs, storage/framework/testing y .phpunit.cache. El patrón es común a
los seis fallos y no apunta, por sí solo, a una regresión de lógica.

Clasificación: problema de entorno.

Riesgo: alto para la validación. Mientras el entorno no pueda escribir, los
tests de ejecución y evidencias no ofrecen una señal confiable sobre el código
productivo. No se debe modificar la lógica para compensar este fallo.

## Fallos que deben corregirse antes de continuar

1. Restablecer permisos de escritura del entorno de pruebas para logs,
   directorios temporales de Storage y caché PHPUnit, sin tocar datos de
   producción.
2. Reejecutar los seis tests afectados de forma aislada.
3. Aislar el 302 de la prueba de evidencia revisando el destino de redirección
   y la visibilidad del fake del disco.
4. Solo después decidir si existe un defecto productivo o un test desactualizado.

## Fallos que no pertenecen al código productivo

Con la evidencia actual, los seis fallos de escritura pertenecen al entorno de
ejecución. El warning de .phpunit.cache también es ambiental.

El 302 no puede clasificarse todavía como defecto productivo: requiere
observar el motivo de la redirección antes de modificar controller o test.

## Plan recomendado

### P0

- Corregir permisos del entorno de pruebas para storage y .phpunit.cache.
- Repetir los seis tests de carga y ejecución.
- Investigar el destino exacto del 302 en la prueba de evidencia.

### P1

- Ejecutar PanelActionFlowTest completo en un entorno reproducible.
- Añadir una comprobación explícita del motivo de redirección si el 302 persiste.
- Verificar que los tests de evidencias usen el mismo disco que consulta el controller.

### P2

- Documentar permisos mínimos de desarrollo y CI.
- Configurar directorios temporales de pruebas fuera de rutas compartidas.
- Incorporar una validación previa de capacidad de escritura antes de la suite.

## Validaciones realizadas

- Suite completa: 162 exitosas y 7 fallidas.
- Revisión de PanelActionFlowTest.php, PanelController::evidenciaPdf, rutas del
  panel y configuración de almacenamiento.
- No se modificaron código, pruebas, BD, migraciones ni configuración.
- No se ejecutaron git add, commit ni push.

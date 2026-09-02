# Estrategia de pruebas actual

## Organizacion

`tests/Feature` valida comportamiento a traves de rutas, controladores, servicios y persistencia. `tests/Unit` valida reglas y transformaciones aisladas.

## Feature tests

Se usan para:

- autenticacion, sesion y autorizacion;
- renderizado y navegacion del panel;
- programacion, ejecucion y reversion;
- estados vigentes, periodicidad y resumenes;
- usuarios y tecnicos;
- reportes y notificaciones;
- comandos y estructura de `mantenimiento_eventos`.

## Unit tests

Se usan para:

- resolucion de estados vigentes;
- mapeo de autenticacion legacy;
- presentacion de origen de equipos;
- seguridad de URLs internas;
- normalizacion de seriales;
- reparacion de nombres.

## Flujo recomendado

1. Ejecutar la prueba mas cercana al archivo modificado.
2. Ejecutar las Feature tests del modulo si cambia una ruta, controlador o servicio compartido.
3. Ejecutar Unit tests si cambia una regla reutilizable.
4. Ejecutar la suite completa cuando el cambio cruza autenticacion, panel, migraciones, base o servicios compartidos.
5. Revisar que no haya fallos, skips inesperados ni regresiones en el diff.

## Funcionalidades criticas protegidas

- Login correcto, credenciales invalidas, usuarios inactivos y hashes inseguros.
- Acceso administrativo al panel y rechazo de usuarios sin autorizacion.
- Programacion, ejecucion, evidencias PDF y rollback.
- Reversion legacy con eventos, observaciones humanas y compatibilidad historica.
- Resolucion de estados por semestre y periodicidad.
- Auditorias de base y snapshots sin escritura indebida.

## Limitaciones

La cobertura no demuestra por si sola comportamiento visual en todos los navegadores, disponibilidad de XAMPP ni integridad de una base productiva. Esas validaciones requieren procedimientos operativos separados.

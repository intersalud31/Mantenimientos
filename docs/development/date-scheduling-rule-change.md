# Cambio de regla para programacion de fechas

## Problema original

El modulo Proximos impedia programar un mantenimiento con una fecha anterior
al dia actual. Esto impedia registrar correctamente una programacion cuando
el trabajo ya se habia realizado y se registraba de forma tardia.

## Regla anterior

La fecha de programacion tenia un minimo igual a la fecha actual en la vista
y en el calendario. El controlador tambien rechazaba la solicitud antes de
invocar el repositorio.

## Regla nueva

Una programacion puede usar una fecha pasada, actual o futura. La fecha debe
seguir siendo valida y habil: no se admiten fines de semana ni festivos.

La ejecucion conserva su regla independiente: no puede registrarse con una
fecha futura. Los estados, la transaccion de programacion, los eventos y las
observaciones humanas no se modifican.

## Archivos modificados

- `app/Http/Controllers/PanelController.php`
- `resources/views/panel/mantenimientos.blade.php`
- `resources/views/panel/partials/dashboard-modern-styles.blade.php`
- `tests/Feature/PanelActionFlowTest.php`

## Calendario

Los encabezados de los calendarios de Programacion y Ejecucion ahora usan
abreviaturas legibles: Lun, Mar, Mie, Jue, Vie, Sab y Dom. El ajuste de tamano
de fuente evita que esos textos compriman la cuadricula en pantallas pequenas.

## Pruebas y riesgos evaluados

Las pruebas funcionales cubren fechas pasadas y actuales validas, fechas no
habiles, festivos, ejecucion pasada y ejecucion futura. La programacion futura
sigue cubierta por el flujo funcional que registra el evento tecnico.

Permitir programaciones pasadas puede mostrar un mantenimiento como vencido
si se programa y no se marca despues como realizado. Ese resultado refleja el
estado persistido y no cambia calculos, reportes ni historicos existentes.

## Decisiones tecnicas

Se retiro solamente la restriccion de fecha minima de la programacion. No se
crearon migraciones ni se modificaron datos, tablas, eventos, estados,
notificaciones, Dashboard o calculos semestrales.

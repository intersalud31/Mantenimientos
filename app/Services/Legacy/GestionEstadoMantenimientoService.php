<?php

namespace App\Services\Legacy;

use App\Services\SemestreService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class GestionEstadoMantenimientoService
{
    public const DESTINO_NO_PROGRAMADO = EstadoMantenimientoVigenteService::NO_PROGRAMADO;
    public const DESTINO_PROGRAMADO = EstadoMantenimientoVigenteService::PROGRAMADO;
    public const DESTINO_EJECUTADO = EstadoMantenimientoVigenteService::EJECUTADO;

    private const MOTIVO_MIN = 5;
    private const MOTIVO_MAX = 500;

    public function __construct(
        private readonly SemestreService $semestreService,
        private readonly EstadoMantenimientoVigenteService $estadoVigenteService,
        private readonly UsuarioMantenimientoResumenService $resumenService,
        private readonly PeriodicidadMantenimientoService $periodicidadService,
        private readonly TextoLegacyNormalizer $textoNormalizer,
        private readonly MantenimientoRepository $mantenimientoRepository
    ) {
    }

    public function analizarUsuarioPorCodigoActivo(int $codigoActivo): array
    {
        $usuario = $this->buscarUsuarioPorCodigoActivo($codigoActivo);
        if ($usuario === null) {
            return ['ok' => false, 'mensaje' => 'No existe un usuario con ese codigo activo.'];
        }

        $periodo = $this->semestreService->actual();
        $estadoActual = $this->estadoVigenteService->resolverParaUsuario((int) $usuario['id'], $periodo);
        $estadoActualValor = (string) ($estadoActual['estado'] ?? '');

        $destinos = [];
        foreach ([self::DESTINO_NO_PROGRAMADO, self::DESTINO_PROGRAMADO, self::DESTINO_EJECUTADO] as $destino) {
            $destinos[] = $this->construirOpcionBase($estadoActualValor, $destino);
        }

        return [
            'ok' => true,
            'mensaje' => null,
            'usuario' => $this->resumirUsuario($usuario),
            'periodo' => $this->resumirPeriodo($periodo),
            'estado_actual' => $this->resumirEstado($estadoActualValor),
            'destinos' => $destinos,
        ];
    }

    public function previsualizarPorCodigoActivo(int $codigoActivo, string $estadoDestino, array $datos = []): array
    {
        $estadoDestino = trim($estadoDestino);
        if (!in_array($estadoDestino, [self::DESTINO_NO_PROGRAMADO, self::DESTINO_PROGRAMADO, self::DESTINO_EJECUTADO], true)) {
            return ['ok' => false, 'mensaje' => 'Selecciona un estado de destino valido.'];
        }

        $usuario = $this->buscarUsuarioPorCodigoActivo($codigoActivo);
        if ($usuario === null) {
            return ['ok' => false, 'mensaje' => 'No existe un usuario con ese codigo activo.'];
        }

        $periodo = $this->semestreService->actual();
        $estadoActual = $this->estadoVigenteService->resolverParaUsuario((int) $usuario['id'], $periodo);
        $estadoActualValor = (string) ($estadoActual['estado'] ?? '');

        $evaluacion = $this->evaluarTransicion($usuario, $periodo, $estadoActualValor, $estadoDestino, $datos, false);

        if (!($evaluacion['permitido'] ?? false)) {
            return [
                'ok' => false,
                'mensaje' => (string) ($evaluacion['motivo_bloqueo'] ?? 'No fue posible generar la previsualizacion.'),
                'destino' => $evaluacion,
            ];
        }

        return [
            'ok' => true,
            'mensaje' => null,
            'preview' => $evaluacion['preview'] ?? null,
            'destino' => $evaluacion,
        ];
    }

    public function ejecutarPorCodigoActivo(
        int $codigoActivo,
        string $estadoDestino,
        array $datos,
        string $motivo,
        string $previewHash,
        ?array $administrador = null
    ): array {
        $estadoDestino = trim($estadoDestino);
        $previewHash = trim($previewHash);
        $motivoNormalizado = $this->normalizarMotivo($motivo);

        if (!in_array($estadoDestino, [self::DESTINO_NO_PROGRAMADO, self::DESTINO_PROGRAMADO, self::DESTINO_EJECUTADO], true)) {
            return ['ok' => false, 'mensaje' => 'El estado de destino solicitado no es valido.'];
        }

        if ($previewHash === '') {
            return ['ok' => false, 'mensaje' => 'Debes generar una previsualizacion valida antes de confirmar el cambio.'];
        }

        if ($motivoNormalizado === null) {
            return ['ok' => false, 'mensaje' => 'Debes ingresar un motivo valido para la gestion de estados.'];
        }

        try {
            return DB::transaction(function () use ($codigoActivo, $estadoDestino, $datos, $motivoNormalizado, $previewHash, $administrador) {
                $usuario = $this->buscarUsuarioPorCodigoActivo($codigoActivo, true);
                if ($usuario === null) {
                    throw new RuntimeException('No existe un usuario con ese codigo activo.');
                }

                $periodo = $this->semestreService->actual();
                $estadoActual = $this->estadoVigenteService->resolverParaUsuario((int) $usuario['id'], $periodo);
                $estadoActualValor = (string) ($estadoActual['estado'] ?? '');
                $evaluacion = $this->evaluarTransicion($usuario, $periodo, $estadoActualValor, $estadoDestino, $datos, true);

                if (!($evaluacion['permitido'] ?? false)) {
                    throw new RuntimeException((string) ($evaluacion['motivo_bloqueo'] ?? 'La transicion solicitada no esta disponible.'));
                }

                $preview = $evaluacion['preview'] ?? null;
                if (!is_array($preview) || trim((string) ($preview['hash'] ?? '')) === '') {
                    throw new RuntimeException('No fue posible reconstruir la previsualizacion del cambio de estado.');
                }

                if (!hash_equals((string) $preview['hash'], $previewHash)) {
                    throw new RuntimeException('Los datos cambiaron despues de la previsualizacion. Recarga la pantalla antes de continuar.');
                }

                if (($preview['metadata']['requiere_confirmacion_fecha_ejecucion_antes_programacion'] ?? false)
                    && !filter_var($datos['confirmar_ejecucion_antes_programacion'] ?? false, FILTER_VALIDATE_BOOL)) {
                    throw new RuntimeException('La fecha de ejecucion es anterior a la fecha programada. Confirma que la informacion sea correcta antes de continuar.');
                }

                $this->aplicarTransicion($preview, $datos, $motivoNormalizado, $administrador);

                $resumen = $this->resumenService->sincronizarResumenUsuario((int) $usuario['id'], $periodo);
                if ($resumen === []) {
                    throw new RuntimeException('No fue posible recalcular el resumen del usuario afectado.');
                }

                $estadoFinal = $this->estadoVigenteService->resolverParaUsuario((int) $usuario['id'], $periodo);
                $fechaEjecucionDestino = trim((string) ($preview['metadata']['fecha_ejecucion_destino'] ?? ''));
                $debeCoincidirEstadoFinal = $estadoDestino !== self::DESTINO_EJECUTADO
                    || $this->fechaPerteneceAlPeriodo($fechaEjecucionDestino, $periodo);

                if ($debeCoincidirEstadoFinal && ($estadoFinal['estado'] ?? null) !== $estadoDestino) {
                    throw new RuntimeException('El estado final no coincide con el destino solicitado. Se revirtio toda la operacion.');
                }

                return [
                    'ok' => true,
                    'mensaje' => 'Gestion de estados aplicada correctamente hacia ' . $this->etiquetaEstado($estadoDestino) . '.',
                    'preview' => $preview,
                    'estado_final' => $this->resumirEstado($estadoDestino),
                ];
            });
        } catch (\Throwable $e) {
            return ['ok' => false, 'mensaje' => $e->getMessage()];
        }
    }

    private function evaluarTransicion(array $usuario, array $periodo, string $estadoActual, string $estadoDestino, array $datos, bool $lock): array
    {
        $opcion = $this->construirOpcionBase($estadoActual, $estadoDestino);

        if ((int) ($usuario['estado_usuario'] ?? 0) !== 1) {
            $opcion['motivo_bloqueo'] = 'La gestion de estados no esta disponible para usuarios inactivos.';
            return $opcion;
        }

        if ($estadoActual === '') {
            $opcion['motivo_bloqueo'] = 'El usuario no tiene un estado vigente gestionable en el semestre actual.';
            return $opcion;
        }

        if ($estadoActual === $estadoDestino) {
            $opcion['motivo_bloqueo'] = 'No puedes seleccionar el mismo estado actual como destino.';
            return $opcion;
        }

        return match ($estadoActual . '->' . $estadoDestino) {
            EstadoMantenimientoVigenteService::NO_PROGRAMADO . '->' . self::DESTINO_PROGRAMADO => $this->evaluarNoProgramadoAProgramado($usuario, $periodo, $opcion, $datos, $lock),
            EstadoMantenimientoVigenteService::NO_PROGRAMADO . '->' . self::DESTINO_EJECUTADO => $this->evaluarNoProgramadoAEjecutado($usuario, $periodo, $opcion, $datos, $lock),
            EstadoMantenimientoVigenteService::PROGRAMADO . '->' . self::DESTINO_NO_PROGRAMADO => $this->evaluarProgramadoANoProgramado($usuario, $periodo, $opcion, $lock),
            EstadoMantenimientoVigenteService::PROGRAMADO . '->' . self::DESTINO_EJECUTADO => $this->evaluarProgramadoAEjecutado($usuario, $periodo, $opcion, $datos, $lock),
            EstadoMantenimientoVigenteService::EJECUTADO . '->' . self::DESTINO_PROGRAMADO => $this->evaluarEjecutadoAProgramado($usuario, $periodo, $opcion, $lock),
            EstadoMantenimientoVigenteService::EJECUTADO . '->' . self::DESTINO_NO_PROGRAMADO => $this->evaluarEjecutadoANoProgramado($usuario, $periodo, $opcion, $lock),
            default => tap($opcion, function (array &$item): void {
                $item['motivo_bloqueo'] = 'La transicion solicitada no esta disponible para el estado actual.';
            }),
        };
    }

    private function evaluarNoProgramadoAProgramado(array $usuario, array $periodo, array $opcion, array $datos, bool $lock): array
    {
        $fechaProgramada = $this->validarFechaProgramada($datos['fecha_programada'] ?? null, $periodo);
        if ($fechaProgramada === null) {
            $opcion['motivo_bloqueo'] = 'Debes indicar una fecha programada valida y habil dentro del semestre vigente.';
            return $opcion;
        }

        $pendiente = $this->resolverPendienteProgramable((int) $usuario['id'], $periodo, $lock);
        if (!($pendiente['ok'] ?? false)) {
            $opcion['motivo_bloqueo'] = (string) ($pendiente['mensaje'] ?? 'No fue posible identificar el mantenimiento pendiente del semestre.');
            return $opcion;
        }

        $preview = $this->construirPreview(
            $usuario,
            $periodo,
            EstadoMantenimientoVigenteService::NO_PROGRAMADO,
            self::DESTINO_PROGRAMADO,
            $pendiente['mantenimiento_objetivo'] ?? [],
            $this->resumenDatosIngresados($datos, false),
            $pendiente['registros_creados'] ?? [],
            [[
                'tabla' => 'mantenimientos',
                'id' => (int) (($pendiente['mantenimiento_objetivo']['id'] ?? 0)),
                'descripcion' => 'Programar mantenimiento pendiente usando el flujo actual de Proximos.',
                'cambios' => [
                    'estado' => MantenimientoEstados::PROGRAMADO,
                    'fecha_programada' => $fechaProgramada,
                ],
            ]],
            [],
            [[
                'tabla' => 'usuarios',
                'id' => (int) $usuario['id'],
                'descripcion' => 'Sincronizar resumen del usuario para el semestre vigente.',
            ]],
            'El mantenimiento dejara de estar pendiente y pasara a Programado dentro del semestre vigente.',
            [
                'mantenimiento_objetivo' => $this->datosFingerprintFila($pendiente['mantenimiento_objetivo'] ?? []),
                'materializacion' => $pendiente['materializacion'] ?? null,
                'fecha_programada_destino' => $fechaProgramada,
            ]
        );

        $preview['metadata']['materializar_pendiente'] = (bool) ($pendiente['materializacion'] ?? false);
        $preview['metadata']['fecha_programada_destino'] = $fechaProgramada;

        $opcion['permitido'] = true;
        $opcion['preview'] = $preview;

        return $opcion;
    }

    private function evaluarNoProgramadoAEjecutado(array $usuario, array $periodo, array $opcion, array $datos, bool $lock): array
    {
        $fechaProgramada = $this->validarFechaProgramada($datos['fecha_programada'] ?? null, $periodo);
        if ($fechaProgramada === null) {
            $opcion['motivo_bloqueo'] = 'Debes indicar una fecha programada valida y habil para programar antes de ejecutar.';
            return $opcion;
        }

        $fechaEjecucion = $this->validarFechaEjecucion($datos['fecha_ejecucion'] ?? null, $periodo);
        if ($fechaEjecucion === null) {
            $opcion['motivo_bloqueo'] = 'Debes indicar una fecha de ejecucion valida que no sea posterior a hoy.';
            return $opcion;
        }

        $tecnico = $this->resolverTecnico((int) ($datos['tecnico_id'] ?? 0));
        if ($tecnico === null) {
            $opcion['motivo_bloqueo'] = 'Debes seleccionar un tecnico o responsable valido.';
            return $opcion;
        }

        $pendiente = $this->resolverPendienteProgramable((int) $usuario['id'], $periodo, $lock);
        if (!($pendiente['ok'] ?? false)) {
            $opcion['motivo_bloqueo'] = (string) ($pendiente['mensaje'] ?? 'No fue posible identificar el mantenimiento pendiente del semestre.');
            return $opcion;
        }

        $simulacionSiguiente = $this->simularPendienteSemestral((int) $usuario['id'], $fechaEjecucion, $lock);

        $preview = $this->construirPreview(
            $usuario,
            $periodo,
            EstadoMantenimientoVigenteService::NO_PROGRAMADO,
            self::DESTINO_EJECUTADO,
            $pendiente['mantenimiento_objetivo'] ?? [],
            $this->resumenDatosIngresados($datos, true),
            $pendiente['registros_creados'] ?? [],
            array_merge(
                [[
                    'tabla' => 'mantenimientos',
                    'id' => (int) (($pendiente['mantenimiento_objetivo']['id'] ?? 0)),
                    'descripcion' => 'Programar y luego marcar como ejecutado el mantenimiento del semestre vigente.',
                    'cambios' => [
                        'estado' => MantenimientoEstados::REALIZADO,
                        'fecha_programada' => $fechaProgramada,
                        'fecha_ejecucion' => $fechaEjecucion,
                        'tecnico_responsable' => $tecnico,
                    ],
                ]],
                $simulacionSiguiente['actualizados'] ?? []
            ),
            $simulacionSiguiente['eliminados'] ?? [],
            [[
                'tabla' => 'usuarios',
                'id' => (int) $usuario['id'],
                'descripcion' => 'Sincronizar resumen del usuario para el semestre vigente.',
            ]],
            'El sistema programara el mantenimiento pendiente, lo marcara como Ejecutado y dejara preparado el siguiente ciclo sin duplicados.',
            [
                'mantenimiento_objetivo' => $this->datosFingerprintFila($pendiente['mantenimiento_objetivo'] ?? []),
                'materializacion' => $pendiente['materializacion'] ?? null,
                'fecha_programada_destino' => $fechaProgramada,
                'fecha_ejecucion_destino' => $fechaEjecucion,
                'tecnico_id' => (int) ($datos['tecnico_id'] ?? 0),
                'tecnico_nombre' => $tecnico,
                'pendiente_siguiente' => $simulacionSiguiente['fingerprint'] ?? null,
            ]
        );

        $preview['metadata']['materializar_pendiente'] = (bool) ($pendiente['materializacion'] ?? false);
        $preview['metadata']['fecha_programada_destino'] = $fechaProgramada;
        $preview['metadata']['fecha_ejecucion_destino'] = $fechaEjecucion;
        $preview['metadata']['tecnico_id'] = (int) ($datos['tecnico_id'] ?? 0);
        $preview['metadata']['tecnico_nombre'] = $tecnico;
        $preview['metadata']['requiere_constancia_pdf'] = true;
        $this->agregarAdvertenciaFechaEjecucionAnteriorAProgramacion($preview, $fechaProgramada, $fechaEjecucion);

        $opcion['permitido'] = true;
        $opcion['preview'] = $preview;

        return $opcion;
    }

    private function evaluarProgramadoANoProgramado(array $usuario, array $periodo, array $opcion, bool $lock): array
    {
        $candidatos = $this->buscarCandidatosProgramadosReversibles((int) $usuario['id'], $periodo, $lock);
        if (count($candidatos) !== 1) {
            $opcion['motivo_bloqueo'] = count($candidatos) === 0
                ? 'No existe un mantenimiento programado reversible para el semestre vigente.'
                : 'Se encontraron varios mantenimientos programados reversibles en el semestre vigente.';
            return $opcion;
        }

        $objetivo = $candidatos[0];
        if (trim((string) ($objetivo['estado'] ?? '')) === MantenimientoEstados::REPROGRAMADO) {
            $opcion['motivo_bloqueo'] = 'Los mantenimientos reprogramados no se pueden regresar a No programado desde esta pantalla.';
            return $opcion;
        }

        $preview = $this->construirPreview(
            $usuario,
            $periodo,
            EstadoMantenimientoVigenteService::PROGRAMADO,
            self::DESTINO_NO_PROGRAMADO,
            $objetivo,
            $this->resumenDatosIngresados([], false),
            [],
            [[
                'tabla' => 'mantenimientos',
                'id' => (int) $objetivo['id'],
                'descripcion' => 'Actualizar mantenimiento programado a pendiente sin borrar los demas campos actuales.',
                'cambios' => [
                    'estado' => MantenimientoEstados::PENDIENTE,
                ],
            ]],
            [],
            [[
                'tabla' => 'usuarios',
                'id' => (int) $usuario['id'],
                'descripcion' => 'Sincronizar resumen del usuario para el semestre vigente.',
            ]],
            'El mantenimiento dejara de aparecer en Programados y volvera a No programado en el semestre vigente.',
            [
                'mantenimiento_objetivo' => $this->datosFingerprintFila($objetivo),
            ]
        );

        $opcion['permitido'] = true;
        $opcion['preview'] = $preview;

        return $opcion;
    }

    private function evaluarProgramadoAEjecutado(array $usuario, array $periodo, array $opcion, array $datos, bool $lock): array
    {
        $fechaEjecucion = $this->validarFechaEjecucion($datos['fecha_ejecucion'] ?? null, $periodo);
        if ($fechaEjecucion === null) {
            $opcion['motivo_bloqueo'] = 'Debes indicar una fecha de ejecucion valida que no sea posterior a hoy.';
            return $opcion;
        }

        $tecnico = $this->resolverTecnico((int) ($datos['tecnico_id'] ?? 0));
        if ($tecnico === null) {
            $opcion['motivo_bloqueo'] = 'Debes seleccionar un tecnico o responsable valido.';
            return $opcion;
        }

        $candidatos = $this->buscarCandidatosProgramadosParaEjecucion((int) $usuario['id'], $periodo, $lock);
        if (count($candidatos) !== 1) {
            $opcion['motivo_bloqueo'] = count($candidatos) === 0
                ? 'No existe un mantenimiento programado ejecutable para el semestre vigente.'
                : 'Se encontraron varios mantenimientos programados en el semestre vigente y la operacion no es inequivoca.';
            return $opcion;
        }

        $objetivo = $candidatos[0];
        $simulacionSiguiente = $this->simularPendienteSemestral((int) $usuario['id'], $fechaEjecucion, $lock);

        $preview = $this->construirPreview(
            $usuario,
            $periodo,
            EstadoMantenimientoVigenteService::PROGRAMADO,
            self::DESTINO_EJECUTADO,
            $objetivo,
            $this->resumenDatosIngresados($datos, true),
            [],
            array_merge(
                [[
                    'tabla' => 'mantenimientos',
                    'id' => (int) $objetivo['id'],
                    'descripcion' => 'Marcar mantenimiento programado como ejecutado usando el flujo actual de Programados.',
                    'cambios' => [
                        'estado' => MantenimientoEstados::REALIZADO,
                        'fecha_ejecucion' => $fechaEjecucion,
                        'tecnico_responsable' => $tecnico,
                    ],
                ]],
                $simulacionSiguiente['actualizados'] ?? []
            ),
            $simulacionSiguiente['eliminados'] ?? [],
            [[
                'tabla' => 'usuarios',
                'id' => (int) $usuario['id'],
                'descripcion' => 'Sincronizar resumen del usuario para el semestre vigente.',
            ]],
            'El mantenimiento pasara a Ejecutado y el siguiente pendiente del ciclo quedara sincronizado sin duplicados.',
            [
                'mantenimiento_objetivo' => $this->datosFingerprintFila($objetivo),
                'fecha_ejecucion_destino' => $fechaEjecucion,
                'tecnico_id' => (int) ($datos['tecnico_id'] ?? 0),
                'tecnico_nombre' => $tecnico,
                'pendiente_siguiente' => $simulacionSiguiente['fingerprint'] ?? null,
            ]
        );

        $preview['metadata']['fecha_ejecucion_destino'] = $fechaEjecucion;
        $preview['metadata']['tecnico_id'] = (int) ($datos['tecnico_id'] ?? 0);
        $preview['metadata']['tecnico_nombre'] = $tecnico;
        $preview['metadata']['requiere_constancia_pdf'] = true;
        $this->agregarAdvertenciaFechaEjecucionAnteriorAProgramacion($preview, (string) ($objetivo['fecha_programada'] ?? ''), $fechaEjecucion);

        $opcion['permitido'] = true;
        $opcion['preview'] = $preview;

        return $opcion;
    }

    private function evaluarEjecutadoAProgramado(array $usuario, array $periodo, array $opcion, bool $lock): array
    {
        $candidatos = $this->buscarCandidatosEjecutados((int) $usuario['id'], $periodo, $lock);
        if (count($candidatos) !== 1) {
            $opcion['motivo_bloqueo'] = count($candidatos) === 0
                ? 'No existe un mantenimiento ejecutado reversible para el semestre vigente.'
                : 'Se encontraron varios mantenimientos ejecutados reversibles en el semestre vigente.';
            return $opcion;
        }

        $objetivo = $candidatos[0];
        $fechaEjecucion = trim((string) ($objetivo['fecha_ejecucion'] ?? ''));
        if ($fechaEjecucion === '') {
            $opcion['motivo_bloqueo'] = 'El mantenimiento ejecutado no tiene fecha de ejecucion valida.';
            return $opcion;
        }

        $siguienteFecha = $this->periodicidadService->fechaProximaDesde(
            $fechaEjecucion,
            $this->periodicidadService->cicloMantenimientoMeses()
        );

        if ($siguienteFecha === null) {
            $opcion['motivo_bloqueo'] = 'No fue posible calcular el pendiente automatico asociado al siguiente ciclo.';
            return $opcion;
        }

        $periodoSiguiente = $this->semestreService->semestreDeFecha($siguienteFecha);
        $pendientesAuto = $this->buscarPendientesAutomaticosDelPeriodo((int) $usuario['id'], $periodoSiguiente, $lock);
        if (count($pendientesAuto) !== 1) {
            $opcion['motivo_bloqueo'] = count($pendientesAuto) === 0
                ? 'Falta el pendiente automatico requerido para regresar de Ejecutado a Programado.'
                : 'Hay mas de un pendiente automatico candidato para el siguiente ciclo.';
            return $opcion;
        }

        $pendiente = $pendientesAuto[0];
        if (
            trim((string) ($pendiente['fecha_programada'] ?? '')) !== $siguienteFecha
            || trim((string) ($pendiente['fecha_ejecucion'] ?? '')) !== ''
            || trim((string) ($pendiente['tecnico_responsable'] ?? '')) !== ''
            || trim((string) ($pendiente['evidencia_pdf'] ?? '')) !== ''
            || trim((string) ($pendiente['estado'] ?? '')) !== MantenimientoEstados::PENDIENTE
        ) {
            $opcion['motivo_bloqueo'] = 'El pendiente automatico relacionado fue alterado y no puede revertirse de forma segura.';
            return $opcion;
        }

        $preview = $this->construirPreview(
            $usuario,
            $periodo,
            EstadoMantenimientoVigenteService::EJECUTADO,
            self::DESTINO_PROGRAMADO,
            $objetivo,
            $this->resumenDatosIngresados([], false),
            [],
            [[
                'tabla' => 'mantenimientos',
                'id' => (int) $objetivo['id'],
                'descripcion' => 'Actualizar mantenimiento ejecutado a programado y limpiar fecha de ejecucion.',
                'cambios' => [
                    'estado' => MantenimientoEstados::PROGRAMADO,
                    'fecha_ejecucion' => null,
                ],
            ]],
            [[
                'tabla' => 'mantenimientos',
                'id' => (int) $pendiente['id'],
                'descripcion' => 'Eliminar pendiente automatico del ciclo siguiente.',
                'fecha_programada' => (string) ($pendiente['fecha_programada'] ?? ''),
            ]],
            [[
                'tabla' => 'usuarios',
                'id' => (int) $usuario['id'],
                'descripcion' => 'Sincronizar resumen del usuario para el semestre vigente.',
            ]],
            'El mantenimiento volvera a Programado y se eliminara solo el pendiente automatico generado para el siguiente ciclo.',
            [
                'mantenimiento_objetivo' => $this->datosFingerprintFila($objetivo),
                'pendiente_automatico' => $this->datosFingerprintFila($pendiente),
            ]
        );

        $opcion['permitido'] = true;
        $opcion['preview'] = $preview;

        return $opcion;
    }

    private function evaluarEjecutadoANoProgramado(array $usuario, array $periodo, array $opcion, bool $lock): array
    {
        $evaluacion = $this->evaluarEjecutadoAProgramado($usuario, $periodo, $opcion, $lock);
        if (!($evaluacion['permitido'] ?? false)) {
            return $evaluacion;
        }

        $previewProgramado = $evaluacion['preview'] ?? [];
        $objetivo = $previewProgramado['mantenimiento_objetivo'] ?? [];

        $preview = $this->construirPreview(
            $usuario,
            $periodo,
            EstadoMantenimientoVigenteService::EJECUTADO,
            self::DESTINO_NO_PROGRAMADO,
            $objetivo,
            $this->resumenDatosIngresados([], false),
            [],
            [[
                'tabla' => 'mantenimientos',
                'id' => (int) ($objetivo['id'] ?? 0),
                'descripcion' => 'Regresar mantenimiento ejecutado a programado y despues a pendiente dentro de la misma transaccion.',
                'cambios' => [
                    'estado' => MantenimientoEstados::PENDIENTE,
                    'fecha_ejecucion' => null,
                ],
            ]],
            array_values(array_filter([
                $previewProgramado['pendiente_automatico'] ?? null,
            ], static fn ($item) => is_array($item))),
            [[
                'tabla' => 'usuarios',
                'id' => (int) $usuario['id'],
                'descripcion' => 'Sincronizar resumen del usuario para el semestre vigente.',
            ]],
            'El mantenimiento regresara a No programado y se eliminara unicamente el pendiente automatico del ciclo siguiente.',
            [
                'mantenimiento_objetivo' => $this->datosFingerprintFila($objetivo),
                'pendiente_automatico' => isset($previewProgramado['pendiente_automatico']) && is_array($previewProgramado['pendiente_automatico'])
                    ? $previewProgramado['pendiente_automatico']
                    : null,
            ]
        );

        $opcion['permitido'] = true;
        $opcion['preview'] = $preview;

        return $opcion;
    }

    private function aplicarTransicion(array $preview, array $datos, string $motivo, ?array $administrador): void
    {
        $estadoActual = (string) ($preview['estado_actual']['valor'] ?? '');
        $estadoDestino = (string) ($preview['estado_destino']['valor'] ?? '');

        match ($estadoActual . '->' . $estadoDestino) {
            EstadoMantenimientoVigenteService::NO_PROGRAMADO . '->' . self::DESTINO_PROGRAMADO => $this->aplicarNoProgramadoAProgramado($preview, $motivo, $administrador),
            EstadoMantenimientoVigenteService::NO_PROGRAMADO . '->' . self::DESTINO_EJECUTADO => $this->aplicarNoProgramadoAEjecutado($preview, $datos, $motivo, $administrador),
            EstadoMantenimientoVigenteService::PROGRAMADO . '->' . self::DESTINO_NO_PROGRAMADO => $this->aplicarProgramadoANoProgramado($preview, $motivo, $administrador),
            EstadoMantenimientoVigenteService::PROGRAMADO . '->' . self::DESTINO_EJECUTADO => $this->aplicarProgramadoAEjecutado($preview, $datos, $motivo, $administrador),
            EstadoMantenimientoVigenteService::EJECUTADO . '->' . self::DESTINO_PROGRAMADO => $this->aplicarEjecutadoAProgramado($preview, $motivo, $administrador),
            EstadoMantenimientoVigenteService::EJECUTADO . '->' . self::DESTINO_NO_PROGRAMADO => $this->aplicarEjecutadoANoProgramado($preview, $motivo, $administrador),
            default => throw new RuntimeException('La transicion solicitada no esta implementada.'),
        };
    }

    private function aplicarNoProgramadoAProgramado(array $preview, string $motivo, ?array $administrador): void
    {
        $mantenimientoId = $this->resolverOMaterializarPendienteDesdePreview($preview);
        $fechaProgramada = (string) ($preview['metadata']['fecha_programada_destino'] ?? '');

        $resultado = $this->mantenimientoRepository->marcarComoProgramado($mantenimientoId, $fechaProgramada);
        if (!($resultado['ok'] ?? false)) {
            throw new RuntimeException((string) ($resultado['mensaje'] ?? 'No fue posible programar el mantenimiento.'));
        }

        $this->anexarBitacoraGestion(
            $mantenimientoId,
            EstadoMantenimientoVigenteService::NO_PROGRAMADO,
            self::DESTINO_PROGRAMADO,
            $motivo,
            $administrador,
            $preview['periodo'] ?? []
        );
    }

    private function aplicarNoProgramadoAEjecutado(array $preview, array $datos, string $motivo, ?array $administrador): void
    {
        $mantenimientoId = $this->resolverOMaterializarPendienteDesdePreview($preview);
        $fechaProgramada = (string) ($preview['metadata']['fecha_programada_destino'] ?? '');
        $fechaEjecucion = (string) ($preview['metadata']['fecha_ejecucion_destino'] ?? '');
        $tecnicoNombre = (string) ($preview['metadata']['tecnico_nombre'] ?? '');
        $rutaConstancia = trim((string) ($datos['constancia_pdf_path'] ?? ''));

        if ($rutaConstancia === '') {
            throw new RuntimeException('Debes adjuntar la constancia PDF obligatoria para confirmar la ejecucion.');
        }

        $resultadoProgramar = $this->mantenimientoRepository->marcarComoProgramado($mantenimientoId, $fechaProgramada);
        if (!($resultadoProgramar['ok'] ?? false)) {
            throw new RuntimeException((string) ($resultadoProgramar['mensaje'] ?? 'No fue posible programar el mantenimiento antes de ejecutarlo.'));
        }

        $resultadoHecho = $this->mantenimientoRepository->marcarProgramadoComoHechoYGenerarSiguiente(
            $mantenimientoId,
            $fechaEjecucion,
            $tecnicoNombre,
            $this->construirObservacionGestion(
                (string) ($datos['observaciones'] ?? ''),
                EstadoMantenimientoVigenteService::NO_PROGRAMADO,
                self::DESTINO_EJECUTADO,
                $motivo,
                $administrador,
                $preview['periodo'] ?? []
            ),
            $rutaConstancia
        );

        if (!($resultadoHecho['ok'] ?? false)) {
            throw new RuntimeException((string) ($resultadoHecho['mensaje'] ?? 'No fue posible ejecutar el mantenimiento.'));
        }
    }

    private function aplicarProgramadoANoProgramado(array $preview, string $motivo, ?array $administrador): void
    {
        $mantenimientoId = (int) (($preview['mantenimiento_objetivo']['id'] ?? 0));
        if ($mantenimientoId <= 0) {
            throw new RuntimeException('No fue posible identificar el mantenimiento programado a revertir.');
        }

        DB::table('mantenimientos')
            ->where('id', $mantenimientoId)
            ->update([
                'estado' => MantenimientoEstados::PENDIENTE,
                'updated_at' => now(),
            ]);

        $this->anexarBitacoraGestion(
            $mantenimientoId,
            EstadoMantenimientoVigenteService::PROGRAMADO,
            self::DESTINO_NO_PROGRAMADO,
            $motivo,
            $administrador,
            $preview['periodo'] ?? []
        );
    }

    private function aplicarProgramadoAEjecutado(array $preview, array $datos, string $motivo, ?array $administrador): void
    {
        $mantenimientoId = (int) (($preview['mantenimiento_objetivo']['id'] ?? 0));
        if ($mantenimientoId <= 0) {
            throw new RuntimeException('No fue posible identificar el mantenimiento programado a ejecutar.');
        }

        $fechaEjecucion = (string) ($preview['metadata']['fecha_ejecucion_destino'] ?? '');
        $tecnicoNombre = (string) ($preview['metadata']['tecnico_nombre'] ?? '');
        $rutaConstancia = trim((string) ($datos['constancia_pdf_path'] ?? ''));

        if ($rutaConstancia === '') {
            throw new RuntimeException('Debes adjuntar la constancia PDF obligatoria para confirmar la ejecucion.');
        }

        $resultado = $this->mantenimientoRepository->marcarProgramadoComoHechoYGenerarSiguiente(
            $mantenimientoId,
            $fechaEjecucion,
            $tecnicoNombre,
            $this->construirObservacionGestion(
                (string) ($datos['observaciones'] ?? ''),
                EstadoMantenimientoVigenteService::PROGRAMADO,
                self::DESTINO_EJECUTADO,
                $motivo,
                $administrador,
                $preview['periodo'] ?? []
            ),
            $rutaConstancia
        );

        if (!($resultado['ok'] ?? false)) {
            throw new RuntimeException((string) ($resultado['mensaje'] ?? 'No fue posible ejecutar el mantenimiento programado.'));
        }
    }

    private function aplicarEjecutadoAProgramado(array $preview, string $motivo, ?array $administrador): void
    {
        $mantenimientoId = (int) (($preview['mantenimiento_objetivo']['id'] ?? 0));
        $pendienteId = (int) (($preview['pendiente_automatico']['id'] ?? 0));

        DB::table('mantenimientos')
            ->where('id', $mantenimientoId)
            ->update([
                'estado' => MantenimientoEstados::PROGRAMADO,
                'fecha_ejecucion' => null,
                'updated_at' => now(),
            ]);

        $this->anexarBitacoraGestion(
            $mantenimientoId,
            EstadoMantenimientoVigenteService::EJECUTADO,
            self::DESTINO_PROGRAMADO,
            $motivo,
            $administrador,
            $preview['periodo'] ?? []
        );

        DB::table('mantenimientos')
            ->where('id', $pendienteId)
            ->delete();
    }

    private function aplicarEjecutadoANoProgramado(array $preview, string $motivo, ?array $administrador): void
    {
        $this->aplicarEjecutadoAProgramado($preview, $motivo, $administrador);

        $mantenimientoId = (int) (($preview['mantenimiento_objetivo']['id'] ?? 0));
        DB::table('mantenimientos')
            ->where('id', $mantenimientoId)
            ->update([
                'estado' => MantenimientoEstados::PENDIENTE,
                'updated_at' => now(),
            ]);

        $this->anexarBitacoraGestion(
            $mantenimientoId,
            EstadoMantenimientoVigenteService::PROGRAMADO,
            self::DESTINO_NO_PROGRAMADO,
            $motivo,
            $administrador,
            $preview['periodo'] ?? []
        );
    }

    private function resolverPendienteProgramable(int $usuarioId, array $periodo, bool $lock): array
    {
        $pendientes = $this->buscarPendientesDelPeriodo($usuarioId, $periodo, $lock);
        if (count($pendientes) > 1) {
            return ['ok' => false, 'mensaje' => 'Se encontraron varios mantenimientos pendientes en el semestre vigente y la operacion no es inequivoca.'];
        }

        if (count($pendientes) === 1) {
            return [
                'ok' => true,
                'materializacion' => false,
                'mantenimiento_objetivo' => $pendientes[0],
                'registros_creados' => [],
            ];
        }

        $usuario = $this->buscarUsuarioPorId($usuarioId, $lock);
        $proximaFecha = trim((string) ($usuario['proxima_fecha_mantenimiento'] ?? ''));

        if ($proximaFecha === '' || $proximaFecha < (string) $periodo['inicio'] || $proximaFecha > (string) $periodo['fin']) {
            return ['ok' => false, 'mensaje' => 'No existe una fila pendiente materializable para el semestre vigente.'];
        }

        return [
            'ok' => true,
            'materializacion' => true,
            'mantenimiento_objetivo' => [
                'id' => 0,
                'usuario_id' => $usuarioId,
                'estado' => MantenimientoEstados::PENDIENTE,
                'fecha_programada' => $proximaFecha,
                'fecha_ejecucion' => null,
                'observaciones' => 'Pendiente materializable desde proxima_fecha_mantenimiento.',
                'updated_at' => '',
            ],
            'registros_creados' => [[
                'tabla' => 'mantenimientos',
                'id' => null,
                'descripcion' => 'Materializar un mantenimiento pendiente del semestre vigente a partir de la proxima fecha del usuario.',
                'cambios' => [
                    'estado' => MantenimientoEstados::PENDIENTE,
                    'fecha_programada' => $proximaFecha,
                ],
            ]],
        ];
    }

    private function resolverOMaterializarPendienteDesdePreview(array $preview): int
    {
        $mantenimientoId = (int) (($preview['mantenimiento_objetivo']['id'] ?? 0));
        if ($mantenimientoId > 0 && !($preview['metadata']['materializar_pendiente'] ?? false)) {
            return $mantenimientoId;
        }

        $usuarioId = (int) (($preview['usuario']['id'] ?? 0));
        $fechaProgramada = trim((string) ($preview['mantenimiento_objetivo']['fecha_programada'] ?? ''));
        $nuevoId = $this->mantenimientoRepository->materializarPendienteSemestral($usuarioId, $fechaProgramada, 'Materializado desde Gestion de estados.');

        if ($nuevoId === null || $nuevoId <= 0) {
            throw new RuntimeException('No fue posible materializar el mantenimiento pendiente requerido para esta transicion.');
        }

        return $nuevoId;
    }

    private function simularPendienteSemestral(int $usuarioId, string $fechaEjecucion, bool $lock): array
    {
        $siguiente = $this->periodicidadService->fechaProximaDesde(
            $fechaEjecucion,
            $this->periodicidadService->cicloMantenimientoMeses()
        );

        if ($siguiente === null) {
            throw new RuntimeException('No fue posible calcular el siguiente mantenimiento del ciclo.');
        }

        $periodo = $this->semestreService->semestreDeFecha($siguiente);
        $query = DB::table('mantenimientos')
            ->select('id', 'usuario_id', 'estado', 'fecha_programada', 'fecha_ejecucion', 'tecnico_responsable', 'evidencia_pdf', 'observaciones', 'updated_at')
            ->where('usuario_id', $usuarioId)
            ->where('estado', MantenimientoEstados::PENDIENTE)
            ->whereBetween('fecha_programada', [(string) $periodo['inicio'], (string) $periodo['fin']])
            ->orderBy('fecha_programada')
            ->orderBy('id');

        if ($lock) {
            $query->lockForUpdate();
        }

        $pendientes = $query->get()->map(static fn ($fila) => (array) $fila)->all();
        if ($pendientes === []) {
            return [
                'actualizados' => [],
                'eliminados' => [],
                'fingerprint' => [
                    'accion' => 'crear',
                    'fecha_programada' => $siguiente,
                ],
            ];
        }

        $principal = array_shift($pendientes);

        return [
            'actualizados' => [[
                'tabla' => 'mantenimientos',
                'id' => (int) ($principal['id'] ?? 0),
                'descripcion' => 'Actualizar o reutilizar el pendiente del siguiente ciclo.',
                'cambios' => [
                    'estado' => MantenimientoEstados::PENDIENTE,
                    'fecha_programada' => $siguiente,
                ],
            ]],
            'eliminados' => array_map(static function (array $duplicado): array {
                return [
                    'tabla' => 'mantenimientos',
                    'id' => (int) ($duplicado['id'] ?? 0),
                    'descripcion' => 'Cancelar pendiente duplicado del mismo semestre siguiente.',
                ];
            }, $pendientes),
            'fingerprint' => [
                'accion' => 'actualizar',
                'principal' => $this->datosFingerprintFila($principal),
                'duplicados' => array_map(fn (array $fila) => $this->datosFingerprintFila($fila), $pendientes),
                'fecha_programada' => $siguiente,
            ],
        ];
    }

    private function construirOpcionBase(string $estadoActual, string $estadoDestino): array
    {
        return [
            'estado_destino' => $estadoDestino,
            'estado_destino_etiqueta' => $this->etiquetaEstado($estadoDestino),
            'descripcion' => $this->descripcionDestino($estadoActual, $estadoDestino),
            'permitido' => false,
            'motivo_bloqueo' => null,
            'preview' => null,
            'es_estado_actual' => $estadoActual !== '' && $estadoActual === $estadoDestino,
        ];
    }

    private function descripcionDestino(string $estadoActual, string $estadoDestino): string
    {
        return match ($estadoActual . '->' . $estadoDestino) {
            EstadoMantenimientoVigenteService::NO_PROGRAMADO . '->' . self::DESTINO_PROGRAMADO => 'Programa el mantenimiento vigente usando el flujo actual de Proximos.',
            EstadoMantenimientoVigenteService::NO_PROGRAMADO . '->' . self::DESTINO_EJECUTADO => 'Programa y ejecuta el mantenimiento vigente dentro de una sola operacion controlada.',
            EstadoMantenimientoVigenteService::PROGRAMADO . '->' . self::DESTINO_NO_PROGRAMADO => 'Regresa el mantenimiento programado al estado pendiente del semestre actual.',
            EstadoMantenimientoVigenteService::PROGRAMADO . '->' . self::DESTINO_EJECUTADO => 'Marca el mantenimiento vigente como ejecutado y prepara el siguiente ciclo.',
            EstadoMantenimientoVigenteService::EJECUTADO . '->' . self::DESTINO_PROGRAMADO => 'Revierte la ejecucion y elimina solo el pendiente automatico relacionado.',
            EstadoMantenimientoVigenteService::EJECUTADO . '->' . self::DESTINO_NO_PROGRAMADO => 'Revierte la ejecucion y despues regresa el mantenimiento a pendiente.',
            default => 'Gestiona el estado del mantenimiento vigente.',
        };
    }

    private function construirPreview(
        array $usuario,
        array $periodo,
        string $estadoActual,
        string $estadoDestino,
        array $objetivo,
        array $datosIngresados,
        array $registrosCreados,
        array $registrosActualizados,
        array $registrosEliminados,
        array $resumenesRecalculados,
        string $consecuenciaFuncional,
        array $fingerprintExtra = []
    ): array {
        $preview = [
            'usuario' => $this->resumirUsuario($usuario),
            'periodo' => $this->resumirPeriodo($periodo),
            'estado_actual' => $this->resumirEstado($estadoActual),
            'estado_destino' => $this->resumirEstado($estadoDestino),
            'mantenimiento_objetivo' => [
                'id' => (int) ($objetivo['id'] ?? 0),
                'estado_original' => (string) ($objetivo['estado'] ?? ''),
                'fecha_programada' => $this->normalizarFechaTexto($objetivo['fecha_programada'] ?? null),
                'fecha_ejecucion' => $this->normalizarFechaTexto($objetivo['fecha_ejecucion'] ?? null),
                'updated_at' => (string) ($objetivo['updated_at'] ?? ''),
            ],
            'datos_ingresados' => $datosIngresados,
            'registros_creados' => $registrosCreados,
            'registros_actualizados' => $registrosActualizados,
            'registros_eliminados' => $registrosEliminados,
            'resumenes_recalculados' => $resumenesRecalculados,
            'consecuencia_funcional' => $consecuenciaFuncional,
            'advertencias' => [],
            'metadata' => [],
        ];

        if (isset($fingerprintExtra['pendiente_automatico']) && is_array($fingerprintExtra['pendiente_automatico'])) {
            $preview['pendiente_automatico'] = [
                'id' => (int) ($fingerprintExtra['pendiente_automatico']['id'] ?? 0),
                'fecha_programada' => $this->normalizarFechaTexto($fingerprintExtra['pendiente_automatico']['fecha_programada'] ?? null),
                'updated_at' => (string) ($fingerprintExtra['pendiente_automatico']['updated_at'] ?? ''),
            ];
        }

        $fingerprint = [
            'usuario_id' => (int) ($usuario['id'] ?? 0),
            'codigo_activo' => (int) ($usuario['codigo_activo'] ?? 0),
            'periodo' => [
                'anio' => (int) ($periodo['anio'] ?? 0),
                'semestre' => (int) ($periodo['semestre'] ?? 0),
            ],
            'estado_actual' => $estadoActual,
            'estado_destino' => $estadoDestino,
            'mantenimiento_objetivo' => $fingerprintExtra['mantenimiento_objetivo'] ?? $this->datosFingerprintFila($objetivo),
            'pendiente_automatico' => $fingerprintExtra['pendiente_automatico'] ?? null,
            'materializacion' => $fingerprintExtra['materializacion'] ?? null,
            'fecha_programada_destino' => $fingerprintExtra['fecha_programada_destino'] ?? null,
            'fecha_ejecucion_destino' => $fingerprintExtra['fecha_ejecucion_destino'] ?? null,
            'tecnico_id' => $fingerprintExtra['tecnico_id'] ?? null,
            'tecnico_nombre' => $fingerprintExtra['tecnico_nombre'] ?? null,
            'pendiente_siguiente' => $fingerprintExtra['pendiente_siguiente'] ?? null,
        ];

        $preview['hash'] = hash('sha256', json_encode($fingerprint, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

        return $preview;
    }

    private function resumenDatosIngresados(array $datos, bool $incluyeEjecucion): array
    {
        $items = [];
        $fechaProgramada = trim((string) ($datos['fecha_programada'] ?? ''));
        if ($fechaProgramada !== '') {
            $items[] = ['campo' => 'Fecha programada', 'valor' => $fechaProgramada];
        }

        if ($incluyeEjecucion) {
            $fechaEjecucion = trim((string) ($datos['fecha_ejecucion'] ?? ''));
            if ($fechaEjecucion !== '') {
                $items[] = ['campo' => 'Fecha de ejecucion', 'valor' => $fechaEjecucion];
            }

            $tecnicoId = (int) ($datos['tecnico_id'] ?? 0);
            if ($tecnicoId > 0) {
                $tecnico = $this->resolverTecnico($tecnicoId);
                if ($tecnico !== null) {
                    $items[] = ['campo' => 'Tecnico responsable', 'valor' => $tecnico];
                }
            }

            $observaciones = $this->textoNormalizer->observacion((string) ($datos['observaciones'] ?? ''));
            if ($observaciones !== '') {
                $items[] = ['campo' => 'Observaciones', 'valor' => $observaciones];
            }

            $items[] = ['campo' => 'Constancia PDF', 'valor' => 'Se adjuntara al confirmar la ejecucion.'];
        }

        return $items;
    }

    private function buscarUsuarioPorCodigoActivo(int $codigoActivo, bool $lock = false): ?array
    {
        if ($codigoActivo <= 0) {
            return null;
        }

        $query = DB::table('usuarios')
            ->select('id', 'nombre', 'codigo_activo', 'estado_usuario', 'tipo_equipo', 'serial_equipo', 'proxima_fecha_mantenimiento')
            ->where('codigo_activo', $codigoActivo)
            ->limit(1);

        if ($lock) {
            $query->lockForUpdate();
        }

        $fila = $query->first();
        return $fila ? (array) $fila : null;
    }

    private function buscarUsuarioPorId(int $usuarioId, bool $lock = false): ?array
    {
        if ($usuarioId <= 0) {
            return null;
        }

        $query = DB::table('usuarios')
            ->select('id', 'nombre', 'codigo_activo', 'estado_usuario', 'tipo_equipo', 'serial_equipo', 'proxima_fecha_mantenimiento')
            ->where('id', $usuarioId)
            ->limit(1);

        if ($lock) {
            $query->lockForUpdate();
        }

        $fila = $query->first();
        return $fila ? (array) $fila : null;
    }

    private function buscarPendientesDelPeriodo(int $usuarioId, array $periodo, bool $lock = false): array
    {
        $query = DB::table('mantenimientos')
            ->select('id', 'usuario_id', 'estado', 'fecha_programada', 'fecha_ejecucion', 'tecnico_responsable', 'evidencia_pdf', 'observaciones', 'updated_at')
            ->where('usuario_id', $usuarioId)
            ->where('estado', MantenimientoEstados::PENDIENTE)
            ->whereBetween('fecha_programada', [(string) $periodo['inicio'], (string) $periodo['fin']])
            ->orderBy('fecha_programada')
            ->orderBy('id');

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->get()->map(static fn ($fila) => (array) $fila)->all();
    }

    private function buscarCandidatosProgramadosParaEjecucion(int $usuarioId, array $periodo, bool $lock = false): array
    {
        $query = DB::table('mantenimientos')
            ->select('id', 'usuario_id', 'estado', 'fecha_programada', 'fecha_ejecucion', 'tecnico_responsable', 'evidencia_pdf', 'observaciones', 'updated_at')
            ->where('usuario_id', $usuarioId)
            ->whereIn('estado', MantenimientoEstados::estadosVisiblesEnProgramados())
            ->whereBetween('fecha_programada', [(string) $periodo['inicio'], (string) $periodo['fin']])
            ->orderBy('fecha_programada')
            ->orderBy('id');

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->get()->map(static fn ($fila) => (array) $fila)->all();
    }

    private function buscarCandidatosProgramadosReversibles(int $usuarioId, array $periodo, bool $lock = false): array
    {
        $query = DB::table('mantenimientos')
            ->select('id', 'usuario_id', 'estado', 'fecha_programada', 'fecha_ejecucion', 'tecnico_responsable', 'evidencia_pdf', 'observaciones', 'updated_at')
            ->where('usuario_id', $usuarioId)
            ->whereIn('estado', MantenimientoEstados::estadosReversiblesComoProgramado())
            ->whereBetween('fecha_programada', [(string) $periodo['inicio'], (string) $periodo['fin']])
            ->where(function ($subquery): void {
                $subquery->where('observaciones', 'like', '%Programado desde modulo Proximos.%')
                    ->orWhere('observaciones', 'like', '%Hecho desde modulo Programados.%')
                    ->orWhere('observaciones', 'like', '%Revertido desde modulo Reversiones (HECHO).%');
            })
            ->orderBy('fecha_programada')
            ->orderBy('id');

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->get()->map(static fn ($fila) => (array) $fila)->all();
    }

    private function buscarCandidatosEjecutados(int $usuarioId, array $periodo, bool $lock = false): array
    {
        $query = DB::table('mantenimientos')
            ->select('id', 'usuario_id', 'estado', 'fecha_programada', 'fecha_ejecucion', 'tecnico_responsable', 'evidencia_pdf', 'observaciones', 'updated_at')
            ->where('usuario_id', $usuarioId)
            ->whereIn('estado', MantenimientoEstados::estadosVisiblesEnHechos())
            ->whereNotNull('fecha_ejecucion')
            ->whereBetween('fecha_ejecucion', [(string) $periodo['inicio'], (string) $periodo['fin']])
            ->where('observaciones', 'like', '%Hecho desde modulo Programados.%')
            ->orderBy('fecha_ejecucion')
            ->orderBy('id');

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->get()->map(static fn ($fila) => (array) $fila)->all();
    }

    private function buscarPendientesAutomaticosDelPeriodo(int $usuarioId, array $periodo, bool $lock = false): array
    {
        $query = DB::table('mantenimientos')
            ->select('id', 'usuario_id', 'estado', 'fecha_programada', 'fecha_ejecucion', 'tecnico_responsable', 'evidencia_pdf', 'observaciones', 'updated_at')
            ->where('usuario_id', $usuarioId)
            ->where('estado', MantenimientoEstados::PENDIENTE)
            ->whereBetween('fecha_programada', [(string) $periodo['inicio'], (string) $periodo['fin']])
            ->where('observaciones', 'like', 'Generado automatico despues de marcar Hecho.%')
            ->orderBy('fecha_programada')
            ->orderBy('id');

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->get()->map(static fn ($fila) => (array) $fila)->all();
    }

    private function resolverTecnico(int $tecnicoId): ?string
    {
        if ($tecnicoId <= 0) {
            return null;
        }

        $nombre = $this->mantenimientoRepository->obtenerNombreTecnicoPorId($tecnicoId);
        return $nombre !== null ? trim($nombre) : null;
    }

    private function construirObservacionGestion(string $observaciones, string $estadoAnterior, string $estadoDestino, string $motivo, ?array $administrador, array $periodo): string
    {
        return $this->textoNormalizer->anadirMarcaSistema(
            $observaciones,
            $this->bitacoraGestion($estadoAnterior, $estadoDestino, $motivo, $administrador, $periodo)
        );
    }

    private function anexarBitacoraGestion(int $mantenimientoId, string $estadoAnterior, string $estadoDestino, string $motivo, ?array $administrador, array $periodo): void
    {
        $observacionesActuales = DB::table('mantenimientos')->where('id', $mantenimientoId)->value('observaciones');

        DB::table('mantenimientos')
            ->where('id', $mantenimientoId)
            ->update([
                'observaciones' => $this->textoNormalizer->anadirMarcaSistema(
                    is_string($observacionesActuales) ? $observacionesActuales : null,
                    $this->bitacoraGestion($estadoAnterior, $estadoDestino, $motivo, $administrador, $periodo)
                ),
                'updated_at' => now(),
            ]);
    }

    private function bitacoraGestion(string $estadoAnterior, string $estadoDestino, string $motivo, ?array $administrador, array $periodo): string
    {
        $adminId = (int) ($administrador['id'] ?? 0);
        $adminNombre = trim((string) ($administrador['nombre'] ?? 'Administrador'));

        return sprintf(
            'Gestion de estados [%s] %s -> %s | Semestre: %d-S%d | Admin: %s%s | Motivo: %s',
            now()->format('Y-m-d H:i:s'),
            $this->etiquetaEstado($estadoAnterior),
            $this->etiquetaEstado($estadoDestino),
            (int) ($periodo['anio'] ?? 0),
            (int) ($periodo['semestre'] ?? 0),
            $adminNombre,
            $adminId > 0 ? ' (#' . $adminId . ')' : '',
            $motivo
        );
    }

    private function resumirUsuario(array $usuario): array
    {
        return [
            'id' => (int) ($usuario['id'] ?? 0),
            'nombre' => (string) ($usuario['nombre'] ?? ''),
            'codigo_activo' => (int) ($usuario['codigo_activo'] ?? 0),
            'tipo_equipo' => (string) ($usuario['tipo_equipo'] ?? ''),
            'serial_equipo' => (string) ($usuario['serial_equipo'] ?? ''),
            'estado_usuario' => (int) ($usuario['estado_usuario'] ?? 0),
            'activo' => (int) ($usuario['estado_usuario'] ?? 0) === 1,
        ];
    }

    private function resumirPeriodo(array $periodo): array
    {
        return [
            'anio' => (int) ($periodo['anio'] ?? 0),
            'semestre' => (int) ($periodo['semestre'] ?? 0),
            'etiqueta' => (string) ($periodo['etiqueta'] ?? ''),
            'inicio' => (string) ($periodo['inicio'] ?? ''),
            'fin' => (string) ($periodo['fin'] ?? ''),
        ];
    }

    private function resumirEstado(?string $estado): array
    {
        return [
            'valor' => $estado,
            'etiqueta' => $this->etiquetaEstado($estado),
        ];
    }

    private function etiquetaEstado(?string $estado): string
    {
        return match ($estado) {
            EstadoMantenimientoVigenteService::NO_PROGRAMADO => 'No programado',
            EstadoMantenimientoVigenteService::PROGRAMADO => 'Programado',
            EstadoMantenimientoVigenteService::EJECUTADO => 'Ejecutado',
            default => 'Sin estado',
        };
    }

    private function normalizarMotivo(string $motivo): ?string
    {
        $motivo = $this->textoNormalizer->texto($motivo, '');
        if ($motivo === '') {
            return null;
        }

        $longitud = mb_strlen($motivo);
        if ($longitud < self::MOTIVO_MIN || $longitud > self::MOTIVO_MAX) {
            return null;
        }

        return $motivo;
    }

    private function normalizarFechaTexto(mixed $fecha): ?string
    {
        $fecha = trim((string) $fecha);
        return $fecha === '' ? null : $fecha;
    }

    private function validarFechaProgramada(mixed $fecha, array $periodo): ?string
    {
        $fecha = trim((string) $fecha);
        if (!$this->esFechaValida($fecha)) {
            return null;
        }

        if ($fecha < now()->startOfDay()->format('Y-m-d')) {
            return null;
        }

        if ($fecha < (string) $periodo['inicio'] || $fecha > (string) $periodo['fin']) {
            return null;
        }

        if (!$this->periodicidadService->esDiaHabil($fecha)) {
            return null;
        }

        return $fecha;
    }

    private function validarFechaEjecucion(mixed $fecha, array $periodo): ?string
    {
        $fecha = trim((string) $fecha);
        if (!$this->esFechaValida($fecha)) {
            return null;
        }

        $hoy = Carbon::today(config('app.timezone'))->format('Y-m-d');
        if ($fecha > $hoy) {
            return null;
        }

        return $fecha;
    }

    private function agregarAdvertenciaFechaEjecucionAnteriorAProgramacion(array &$preview, ?string $fechaProgramada, ?string $fechaEjecucion): void
    {
        $fechaProgramada = trim((string) $fechaProgramada);
        $fechaEjecucion = trim((string) $fechaEjecucion);

        if ($fechaProgramada === '' || $fechaEjecucion === '' || $fechaEjecucion >= $fechaProgramada) {
            return;
        }

        $preview['advertencias'][] = 'La fecha de ejecucion es anterior a la fecha programada. Confirma que la informacion sea correcta.';
        $preview['metadata']['requiere_confirmacion_fecha_ejecucion_antes_programacion'] = true;
    }

    private function esFechaValida(string $fecha): bool
    {
        $dt = \DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
        return $dt !== false && $dt->format('Y-m-d') === $fecha;
    }

    private function fechaPerteneceAlPeriodo(?string $fecha, array $periodo): bool
    {
        $fecha = trim((string) $fecha);

        return $fecha !== ''
            && $this->esFechaValida($fecha)
            && $fecha >= (string) ($periodo['inicio'] ?? '')
            && $fecha <= (string) ($periodo['fin'] ?? '');
    }

    private function datosFingerprintFila(array $fila): array
    {
        return [
            'id' => (int) ($fila['id'] ?? 0),
            'estado' => (string) ($fila['estado'] ?? ''),
            'fecha_programada' => $this->normalizarFechaTexto($fila['fecha_programada'] ?? null),
            'fecha_ejecucion' => $this->normalizarFechaTexto($fila['fecha_ejecucion'] ?? null),
            'tecnico_responsable' => trim((string) ($fila['tecnico_responsable'] ?? '')),
            'evidencia_pdf' => trim((string) ($fila['evidencia_pdf'] ?? '')),
            'updated_at' => (string) ($fila['updated_at'] ?? ''),
        ];
    }
}

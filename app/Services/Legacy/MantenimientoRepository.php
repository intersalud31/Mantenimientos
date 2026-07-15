<?php

namespace App\Services\Legacy;

use App\Services\SemestreService;
use DateInterval;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;


class MantenimientoRepository
{
    public function __construct(
        private readonly UsuarioMantenimientoResumenService $resumenService,
        private readonly TextoLegacyNormalizer $textoNormalizer,
        private readonly SemestreService $semestreService
    )
    {
    }

    public function registrarTecnico(string $nombre): array
    {
        $nombre = trim($nombre);
        if ($nombre === '') {
            return ['ok' => false, 'mensaje' => 'Debes ingresar el nombre del tecnico o responsable.'];
        }

        try {
            DB::table('tecnicos')->insert([
                'nombre' => $nombre,
                'estado' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            return ['ok' => false, 'mensaje' => 'No fue posible registrar el tecnico. Verifica si ya existe con ese nombre.'];
        }

        return ['ok' => true, 'mensaje' => 'Tecnico registrado correctamente.'];
    }

    public function listarTecnicosActivos(): array
    {
        return DB::table('tecnicos')
            ->select('id', 'nombre', 'estado')
            ->where('estado', 1)
            ->orderBy('nombre')
            ->get()
            ->map(static fn ($row) => (array) $row)
            ->all();
    }

    public function obtenerEvidenciaPorMantenimientoId(int $mantenimientoId): ?array
    {
        $fila = DB::table('mantenimientos')
            ->select('id', 'usuario_id', 'estado', 'evidencia_pdf')
            ->where('id', $mantenimientoId)
            ->first();

        return $fila ? (array) $fila : null;
    }

    public function obtenerNombreTecnicoPorId(int $tecnicoId): ?string
    {
        $nombre = DB::table('tecnicos')
            ->where('id', $tecnicoId)
            ->where('estado', 1)
            ->value('nombre');

        $nombre = is_string($nombre) ? trim($nombre) : '';

        return $nombre === '' ? null : $nombre;
    }

    public function obtenerResumenSemestralActual(?array $periodo = null): array
    {
        $periodo = $periodo ?? $this->semestreService->actual();
        $anio = (int) ($periodo['anio'] ?? (int) (new DateTimeImmutable('today'))->format('Y'));
        $fase = (int) ($periodo['semestre'] ?? 1);
        $inicio = (string) ($periodo['inicio'] ?? sprintf('%04d-01-01', $anio));
        $fin = (string) ($periodo['fin'] ?? sprintf('%04d-06-30', $anio));

        $totalUsuarios = (int) DB::table('usuarios')->where('estado_usuario', 1)->count();

        $totalBaseFase = (int) DB::table('usuarios as u')
            ->where('u.estado_usuario', 1)
            ->whereExists(function ($query) use ($inicio, $fin): void {
                $query->select(DB::raw(1))
                    ->from('mantenimientos as m')
                    ->whereColumn('m.usuario_id', 'u.id')
                    ->whereRaw('COALESCE(m.fecha_ejecucion, m.fecha_programada) IS NOT NULL')
                    ->whereRaw('COALESCE(m.fecha_ejecucion, m.fecha_programada) BETWEEN ? AND ?', [$inicio . ' 00:00:00', $fin . ' 23:59:59']);
            })
            ->count();

        $totalRealizados = (int) DB::table('usuarios as u')
            ->join('mantenimientos as m', 'm.usuario_id', '=', 'u.id')
            ->where('u.estado_usuario', 1)
            ->whereIn('m.estado', MantenimientoEstados::estadosVisiblesEnHechos())
            ->whereNotNull('m.fecha_ejecucion')
            ->whereBetween('m.fecha_ejecucion', [$inicio, $fin])
            ->whereExists(function ($query) use ($inicio, $fin): void {
                $query->select(DB::raw(1))
                    ->from('mantenimientos as mh')
                    ->whereColumn('mh.usuario_id', 'u.id')
                    ->whereRaw('COALESCE(mh.fecha_ejecucion, mh.fecha_programada) IS NOT NULL')
                    ->whereRaw('COALESCE(mh.fecha_ejecucion, mh.fecha_programada) BETWEEN ? AND ?', [$inicio . ' 00:00:00', $fin . ' 23:59:59']);
            })
            ->distinct('u.id')
            ->count('u.id');

        $totalProgramados = (int) DB::table('usuarios as u')
            ->join('mantenimientos as m', 'm.usuario_id', '=', 'u.id')
            ->where('u.estado_usuario', 1)
            ->whereIn('m.estado', MantenimientoEstados::estadosVisiblesEnProgramados())
            ->whereBetween('m.fecha_programada', [$inicio, $fin])
            ->whereExists(function ($query) use ($inicio, $fin): void {
                $query->select(DB::raw(1))
                    ->from('mantenimientos as mh')
                    ->whereColumn('mh.usuario_id', 'u.id')
                    ->whereRaw('COALESCE(mh.fecha_ejecucion, mh.fecha_programada) IS NOT NULL')
                    ->whereRaw('COALESCE(mh.fecha_ejecucion, mh.fecha_programada) BETWEEN ? AND ?', [$inicio . ' 00:00:00', $fin . ' 23:59:59']);
            })
            ->whereNotIn('u.id', function ($query) use ($inicio, $fin): void {
                $query->select('m2.usuario_id')
                    ->from('mantenimientos as m2')
                    ->whereIn('m2.estado', MantenimientoEstados::estadosVisiblesEnHechos())
                    ->whereNotNull('m2.fecha_ejecucion')
                    ->whereBetween('m2.fecha_ejecucion', [$inicio, $fin])
                    ->distinct();
            })
            ->distinct('u.id')
            ->count('u.id');

        $totalPendientes = max(0, $totalBaseFase - $totalRealizados);
        $indiceCumplimiento = $totalBaseFase > 0 ? (int) round(($totalRealizados / $totalBaseFase) * 100) : 0;

        return [
            'fase' => $fase,
            'fase_nombre' => $fase === 1 ? 'Primer semestre' : 'Segundo semestre',
            'periodo_label' => sprintf('Semestre %d de %d', $fase, $anio),
            'anio' => $anio,
            'inicio' => $inicio,
            'fin' => $fin,
            'total_usuarios' => $totalUsuarios,
            'total_base_fase' => $totalBaseFase,
            'total_no_exigibles' => max(0, $totalUsuarios - $totalBaseFase),
            'total_realizados' => $totalRealizados,
            'total_programados' => $totalProgramados,
            'total_pendientes' => $totalPendientes,
            'indice_cumplimiento' => $indiceCumplimiento,
        ];
    }

    public function listarActividadReciente(int $limite = 5, ?array $periodo = null): array
    {
        $actividad = [];
        foreach ($this->listarPorEstado('programado', $limite, 0, '', $periodo) as $fila) {
            $actividad[] = [
                'tipo' => 'Programado',
                'estado' => (string) ($fila['estado_normalizado'] ?? 'programado'),
                'nombre' => (string) ($fila['nombre'] ?? ''),
                'area_nombre' => (string) ($fila['area_nombre'] ?? 'SIN AREA'),
                'fecha' => (string) ($fila['proxima_fecha_programada'] ?? ''),
            ];
        }
        foreach ($this->listarPorEstado('realizado', $limite, 0, '', $periodo, true) as $fila) {
            $actividad[] = [
                'tipo' => 'Realizado',
                'estado' => (string) ($fila['estado_normalizado'] ?? 'realizado'),
                'nombre' => (string) ($fila['nombre'] ?? ''),
                'area_nombre' => (string) ($fila['area_nombre'] ?? 'SIN AREA'),
                'fecha' => (string) (($fila['fecha_ejecucion'] ?? '') ?: ($fila['proxima_fecha_programada'] ?? '')),
            ];
        }

        usort($actividad, static function (array $a, array $b): int {
            return strcmp((string) ($b['fecha'] ?? ''), (string) ($a['fecha'] ?? ''));
        });

        return array_slice($actividad, 0, $limite);
    }

    public function listarPorEstado(string $estado, int $limite, int $desplazamiento, string $busqueda = '', ?array $periodo = null, bool $filtrarRealizadosPorPeriodo = false, ?array $periodoVisual = null): array
    {
        $estado = MantenimientoEstados::normalizar($estado);
        $periodo = $periodo ?? $this->semestreService->actual();
        $inicio = (string) ($periodo['inicio'] ?? '');
        $fin = (string) ($periodo['fin'] ?? '');

        $query = DB::table('usuarios as u')
            ->join('mantenimientos as m', 'm.usuario_id', '=', 'u.id')
            ->leftJoin('areas as a', 'a.id', '=', 'u.area_id')
            ->whereIn('m.estado', MantenimientoEstados::estadosParaConsulta($estado))
            ->where('u.estado_usuario', 1)
            ->select([
                'm.id as mantenimiento_id',
                'u.id as usuario_id',
                'm.estado',
                'u.nombre',
                'u.codigo_activo',
                'u.tipo_equipo',
                DB::raw("COALESCE(a.nombre, 'SIN AREA') AS area_nombre"),
                'm.fecha_programada as proxima_fecha_programada',
                'm.fecha_ejecucion',
                'm.observaciones',
                'm.tecnico_responsable',
            'm.evidencia_pdf',
            ]);

        if ($estado === 'pendiente') {
            if ($inicio !== '' && $fin !== '') {
                $query->whereBetween('m.fecha_programada', [$inicio, $fin]);
            }

            $query->whereRaw(
                "NOT EXISTS (
                    SELECT 1
                    FROM mantenimientos mpend2
                    WHERE mpend2.usuario_id = u.id
                      AND mpend2.estado = 'pendiente'
                      AND (
                        mpend2.fecha_programada < m.fecha_programada
                        OR (
                            mpend2.fecha_programada = m.fecha_programada
                            AND mpend2.id > m.id
                        )
                        )
                )"
            );

            $query->addSelect([
                DB::raw("(
                    SELECT MAX(COALESCE(m2.fecha_ejecucion, m2.fecha_programada))
                    FROM mantenimientos m2
                    WHERE m2.usuario_id = u.id
                      AND m2.estado = 'realizado'
                      AND COALESCE(m2.fecha_ejecucion, m2.fecha_programada) BETWEEN '{$inicio} 00:00:00' AND '{$fin} 23:59:59'
                ) AS ultima_fecha_realizada"),
                DB::raw("COALESCE((
                    SELECT MAX(COALESCE(m3.fecha_ejecucion, m3.fecha_programada))
                    FROM mantenimientos m3
                    WHERE m3.usuario_id = u.id
                      AND m3.id <> m.id
                      AND m3.fecha_programada <= m.fecha_programada
                      AND COALESCE(m3.fecha_ejecucion, m3.fecha_programada) BETWEEN '{$inicio} 00:00:00' AND '{$fin} 23:59:59'
                ), m.fecha_programada) AS ultima_fecha_programada"),
            ]);
        }

        if ($estado === 'programado' && $inicio !== '' && $fin !== '') {
            $query->whereBetween('m.fecha_programada', [$inicio, $fin]);
        }

        $busqueda = trim($busqueda);
        if ($busqueda !== '') {
            if (ctype_digit($busqueda)) {
                $query->where('u.codigo_activo', (int) $busqueda);
            } else {
                $query->where('u.nombre', 'like', '%' . $busqueda . '%');
            }
        }

        if ($estado === 'realizado' && $filtrarRealizadosPorPeriodo && $inicio !== '' && $fin !== '') {
            $query->whereBetween('m.fecha_ejecucion', [$inicio, $fin]);
        }

        if ($estado === 'realizado') {
            $periodoVisual = $periodoVisual ?? null;
            if (is_array($periodoVisual) && !empty($periodoVisual['inicio']) && !empty($periodoVisual['fin'])) {
                $inicioVisual = (string) $periodoVisual['inicio'];
                $finVisual = (string) $periodoVisual['fin'];
                $query->orderByRaw(
                    "CASE
                        WHEN COALESCE(m.fecha_ejecucion, m.fecha_programada) BETWEEN '{$inicioVisual} 00:00:00' AND '{$finVisual} 23:59:59' THEN 0
                        ELSE 1
                    END"
                );
            }

            $query->orderByRaw('COALESCE(m.fecha_ejecucion, m.fecha_programada) DESC')
                ->orderBy('u.nombre');
        } elseif ($estado === 'pendiente') {
            $query->orderByRaw('m.fecha_programada ASC, u.nombre ASC');
        } else {
            $query->orderBy('m.fecha_programada');
        }

        if ($estado !== 'pendiente') {
            $query->skip($desplazamiento)->take($limite);
        }

        $filas = $query->get()->map(static fn ($row) => (array) $row)->all();

        foreach ($filas as &$fila) {
            $this->hidratarEvidenciaPublica($fila);
            $this->hidratarEstadoVisual($fila);
        }
        unset($fila);

        if ($estado === 'pendiente') {
            foreach ($filas as &$fila) {
                $fila['observaciones'] = $this->textoNormalizer->observacion((string) ($fila['observaciones'] ?? ''));

                $ultimaFechaReal = trim((string) ($fila['ultima_fecha_realizada'] ?? ''));
                if ($ultimaFechaReal === '') {
                    $fila['ultima_fecha_programada'] = null;
                    $tipoEquipo = trim((string) ($fila['tipo_equipo'] ?? 'normal'));
                    $fila['ultimo_mantenimiento_texto'] = $tipoEquipo === 'nuevo' ? 'Equipo nuevo' : 'Sin historial';
                    continue;
                }

                $fila['ultima_fecha_programada'] = $ultimaFechaReal;
                $fila['ultimo_mantenimiento_texto'] = '';

                $ultima = (string) ($fila['ultima_fecha_programada'] ?? '');
                if ($ultima !== '') {
                    $fila['proxima_fecha_programada'] = $this->calcularProximaSemestralDesde($ultima);
                }
            }
            unset($fila);

            usort($filas, static function (array $a, array $b): int {
                $fechaA = (string) ($a['proxima_fecha_programada'] ?? '');
                $fechaB = (string) ($b['proxima_fecha_programada'] ?? '');
                if ($fechaA === $fechaB) {
                    return strcmp((string) ($a['nombre'] ?? ''), (string) ($b['nombre'] ?? ''));
                }

                return strcmp($fechaA, $fechaB);
            });

            return array_slice($filas, $desplazamiento, $limite);
        }

        if ($estado === 'realizado') {
            foreach ($filas as &$fila) {
                $fila['observaciones'] = $this->textoNormalizer->observacion((string) ($fila['observaciones'] ?? ''));
            }
            unset($fila);
        }

        return $filas;
    }

    private function hidratarEvidenciaPublica(array &$fila): void
    {
        $rutaRelativa = $this->normalizarRutaEvidencia((string) ($fila['evidencia_pdf'] ?? ''));
        $fila['evidencia_pdf'] = $rutaRelativa;
        $fila['evidencia_nombre'] = $rutaRelativa !== ''
            ? basename(str_replace('\\', '/', $rutaRelativa))
            : '';
        $fila['evidencia_disponible'] = false;
        $fila['evidencia_url'] = '';

        if ($rutaRelativa === '') {
            return;
        }

        if (!Storage::disk('public')->exists($rutaRelativa)) {
            return;
        }

        $fila['evidencia_disponible'] = true;
        $fila['evidencia_url'] = Storage::disk('public')->url($rutaRelativa);
    }

    private function hidratarEstadoVisual(array &$fila): void
    {
        $estadoRaw = trim((string) ($fila['estado'] ?? ''));
        $estadoCanonico = MantenimientoEstados::normalizar($estadoRaw);
        $estadoEtiqueta = MantenimientoEstados::etiqueta($estadoRaw !== '' ? $estadoRaw : $estadoCanonico);

        $fila['estado_normalizado'] = $estadoCanonico !== '' ? $estadoCanonico : $estadoRaw;
        $fila['estado_etiqueta'] = $estadoEtiqueta;
        $fila['estado_badge_clase'] = MantenimientoEstados::badgeClase($estadoRaw !== '' ? $estadoRaw : $estadoCanonico);
    }

    private function normalizarRutaEvidencia(string $rutaRelativa): string
    {
        return ltrim(trim($rutaRelativa), "/\\");
    }

    public function contarPorEstado(string $estado, string $busqueda = '', ?array $periodo = null, bool $filtrarRealizadosPorPeriodo = false): int
    {
        $estado = MantenimientoEstados::normalizar($estado);
        $periodo = $periodo ?? $this->semestreService->actual();
        $inicio = (string) ($periodo['inicio'] ?? '');
        $fin = (string) ($periodo['fin'] ?? '');

        $query = DB::table('usuarios as u')
            ->join('mantenimientos as m', 'm.usuario_id', '=', 'u.id')
            ->whereIn('m.estado', MantenimientoEstados::estadosParaConsulta($estado))
            ->where('u.estado_usuario', 1);

        if ($estado === 'pendiente') {
            if ($inicio !== '' && $fin !== '') {
                $query->whereBetween('m.fecha_programada', [$inicio, $fin]);
            }

            $query->whereRaw(
                "NOT EXISTS (
                    SELECT 1
                    FROM mantenimientos mpend2
                    WHERE mpend2.usuario_id = u.id
                      AND mpend2.estado = 'pendiente'
                      AND (
                        mpend2.fecha_programada < m.fecha_programada
                        OR (
                            mpend2.fecha_programada = m.fecha_programada
                            AND mpend2.id > m.id
                        )
                      )
                )"
            );
        }

        if ($estado === 'programado' && $inicio !== '' && $fin !== '') {
            $query->whereBetween('m.fecha_programada', [$inicio, $fin]);
        }

        if ($estado === 'realizado' && $filtrarRealizadosPorPeriodo && $inicio !== '' && $fin !== '') {
            $query->whereBetween('m.fecha_ejecucion', [$inicio, $fin]);
        }

        $busqueda = trim($busqueda);
        if ($busqueda !== '') {
            if (ctype_digit($busqueda)) {
                $query->where('u.codigo_activo', (int) $busqueda);
            } else {
                $query->where('u.nombre', 'like', '%' . $busqueda . '%');
            }
        }

        return (int) $query->distinct('u.id')->count('u.id');
    }

    public function normalizarPendientesDuplicados(): int
    {
        $usuarios = DB::table('mantenimientos')
            ->select('usuario_id')
            ->where('estado', MantenimientoEstados::PENDIENTE)
            ->groupBy('usuario_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('usuario_id')
            ->all();

        if ($usuarios === []) {
            return 0;
        }

        return DB::transaction(function () use ($usuarios): int {
            $cancelados = 0;
            $usuariosAfectados = [];

            foreach ($usuarios as $usuarioId) {
                $pendientes = DB::table('mantenimientos')
                    ->select('id')
                    ->where('usuario_id', (int) $usuarioId)
                    ->where('estado', MantenimientoEstados::PENDIENTE)
                    ->orderBy('fecha_programada')
                    ->orderBy('id')
                    ->pluck('id')
                    ->all();

                if (count($pendientes) <= 1) {
                    continue;
                }

                array_shift($pendientes);

                foreach ($pendientes as $idCancelar) {
                    DB::table('mantenimientos')
                        ->where('id', (int) $idCancelar)
                        ->update([
                            'estado' => MantenimientoEstados::CANCELADO,
                            'observaciones' => DB::raw("CONCAT(COALESCE(observaciones, ''), ' | Cancelado automaticamente por normalizacion de pendientes duplicados.')"),
                            'updated_at' => now(),
                        ]);
                    $cancelados++;
                    $usuariosAfectados[(int) $usuarioId] = true;
                }
            }

            foreach (array_keys($usuariosAfectados) as $usuarioId) {
                $this->resumenService->sincronizarResumenUsuario((int) $usuarioId);
            }

            return $cancelados;
        });
    }

    public function marcarComoProgramado(int $mantenimientoId, string $fechaProgramada): array
    {
        if ($mantenimientoId <= 0) {
            return ['ok' => false, 'mensaje' => 'Identificador de mantenimiento invalido.'];
        }

        return DB::transaction(function () use ($mantenimientoId, $fechaProgramada) {
            $registro = DB::table('mantenimientos as m')
                ->join('usuarios as u', 'u.id', '=', 'm.usuario_id')
                ->where('m.id', $mantenimientoId)
                ->whereIn('m.estado', MantenimientoEstados::estadosProgramables())
                ->where('u.estado_usuario', 1)
                ->select('m.id', 'm.usuario_id')
                ->first();

            if (!$registro) {
                return ['ok' => false, 'mensaje' => 'No fue posible programar el mantenimiento.'];
            }

            DB::table('mantenimientos')
                ->where('id', $mantenimientoId)
                ->update([
                    'estado' => MantenimientoEstados::PROGRAMADO,
                    'fecha_programada' => $fechaProgramada,
                    'observaciones' => DB::raw("CONCAT(COALESCE(observaciones, ''), ' | Programado desde modulo Proximos.')"),
                    'updated_at' => now(),
                ]);

            $this->resumenService->sincronizarResumenUsuario(
                (int) $registro->usuario_id,
                $this->semestreService->semestreDeFecha($fechaProgramada)
            );

            return ['ok' => true, 'mensaje' => 'Mantenimiento programado correctamente.'];
        });
    }

    private function calcularProximaSemestralDesde(string $fechaBase): string
    {
        $fecha = DateTimeImmutable::createFromFormat('Y-m-d', $fechaBase);
        if ($fecha === false || $fecha->format('Y-m-d') !== $fechaBase) {
            return $fechaBase;
        }

        $proxima = $fecha->add(new DateInterval('P6M'));
        while ((int) $proxima->format('N') >= 6) {
            $proxima = $proxima->add(new DateInterval('P1D'));
        }

        return $proxima->format('Y-m-d');
    }

    public function marcarProgramadoComoHechoYGenerarSiguiente(
        int $mantenimientoId,
        string $fechaEjecucion,
        string $tecnicoResponsable,
        string $observaciones,
        string $evidenciaPdf
    ): array {
        return DB::transaction(function () use (
            $mantenimientoId,
            $fechaEjecucion,
            $tecnicoResponsable,
            $observaciones,
            $evidenciaPdf
        ) {
            $registro = DB::table('mantenimientos as m')
                ->join('usuarios as u', 'u.id', '=', 'm.usuario_id')
                ->where('m.id', $mantenimientoId)
                ->where('u.estado_usuario', 1)
                ->select('m.id', 'm.usuario_id', 'm.estado')
                ->first();

            $estadoRegistro = $registro ? MantenimientoEstados::normalizar((string) $registro->estado) : '';
            if (!$registro || !in_array($estadoRegistro, MantenimientoEstados::estadosVisiblesEnProgramados(), true)) {
                return ['ok' => false, 'mensaje' => 'No fue posible marcar como hecho.'];
            }

            $observacionFinal = $this->construirObservacionRealizado($observaciones);

            DB::table('mantenimientos')
                ->where('id', $mantenimientoId)
                ->update([
                    'estado' => MantenimientoEstados::REALIZADO,
                    'fecha_ejecucion' => $fechaEjecucion,
                    'tecnico_responsable' => trim($tecnicoResponsable),
                    'observaciones' => $observacionFinal,
                    'evidencia_pdf' => trim($evidenciaPdf),
                    'updated_at' => now(),
                ]);

            $siguiente = (new DateTimeImmutable($fechaEjecucion))->add(new DateInterval('P6M'));
            while ((int) $siguiente->format('N') >= 6) {
                $siguiente = $siguiente->add(new DateInterval('P1D'));
            }

            DB::table('mantenimientos')->insert([
                'usuario_id' => (int) $registro->usuario_id,
                'fecha_programada' => $siguiente->format('Y-m-d'),
                'hora_programada' => null,
                'estado' => MantenimientoEstados::PENDIENTE,
                'fecha_ejecucion' => null,
                'observaciones' => 'Generado automatico despues de marcar Hecho.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->resumenService->sincronizarResumenUsuario(
                (int) $registro->usuario_id,
                $this->semestreService->semestreDeFecha($fechaEjecucion)
            );

            return [
                'ok' => true,
                'mensaje' => 'Mantenimiento marcado como hecho. Se registro la ejecucion y se genero el siguiente mantenimiento a 6 meses.',
            ];
        });
    }

    public function revertirAccionesPorCodigoActivo(int $codigoActivo, int $cantidad): array
    {
        $cantidad = max(1, min(10, $cantidad));

        return DB::transaction(function () use ($codigoActivo, $cantidad) {
            $usuarioId = $this->obtenerUsuarioIdPorCodigoActivo($codigoActivo);
            if ($usuarioId === null) {
                return ['ok' => false, 'mensaje' => 'No existe un usuario con ese codigo activo.'];
            }

            $reversionesAplicadas = 0;
            for ($i = 0; $i < $cantidad; $i++) {
                $accionRevertida = $this->revertirUnaAccionInterna($usuarioId);
                if ($accionRevertida === null) {
                    break;
                }
                $reversionesAplicadas++;

                $periodo = is_array($accionRevertida['periodo'] ?? null) ? $accionRevertida['periodo'] : null;
                $this->resumenService->sincronizarResumenUsuario($usuarioId, $periodo);
            }

            if ($reversionesAplicadas === 0) {
                return ['ok' => false, 'mensaje' => 'No hay acciones reversibles para este usuario.'];
            }

            if ($reversionesAplicadas < $cantidad) {
                return [
                    'ok' => true,
                    'mensaje' => 'Se revirtieron ' . $reversionesAplicadas . ' accion(es). No habia mas acciones reversibles.',
                ];
            }

            return ['ok' => true, 'mensaje' => 'Se revirtieron ' . $reversionesAplicadas . ' accion(es) correctamente.'];
        });
    }

    private function obtenerUsuarioIdPorCodigoActivo(int $codigoActivo): ?int
    {
        $fila = DB::table('usuarios')
            ->select('id')
            ->where('codigo_activo', $codigoActivo)
            ->first();

        return $fila ? (int) $fila->id : null;
    }

    private function revertirUnaAccionInterna(int $usuarioId): ?array
    {
        $ultimoProgramado = $this->buscarUltimoProgramadoReversible($usuarioId);
        $ultimoRealizado = $this->buscarUltimoRealizadoReversible($usuarioId);

        $tsProgramado = is_array($ultimoProgramado) ? strtotime((string) $ultimoProgramado['updated_at']) : false;
        $tsRealizado = is_array($ultimoRealizado) ? strtotime((string) $ultimoRealizado['updated_at']) : false;

        if ($tsProgramado === false && $tsRealizado === false) {
            return null;
        }

        if ($tsRealizado !== false && ($tsProgramado === false || $tsRealizado >= $tsProgramado)) {
            $pendienteAuto = $this->buscarPendienteAutomatico($usuarioId);
            if (!is_array($pendienteAuto)) {
                return null;
            }

            DB::table('mantenimientos')
                ->where('id', (int) $pendienteAuto['id'])
                ->delete();

            $actualizado = DB::table('mantenimientos')
                ->where('id', (int) $ultimoRealizado['id'])
                ->update([
                    'estado' => MantenimientoEstados::PROGRAMADO,
                    'fecha_ejecucion' => null,
                    'observaciones' => DB::raw("CONCAT(COALESCE(observaciones, ''), ' | Revertido desde modulo Reversiones (HECHO).')"),
                    'updated_at' => now(),
                ]);

            if ($actualizado <= 0) {
                return null;
            }

            $fechaReferencia = trim((string) ($ultimoRealizado['fecha_ejecucion'] ?? $ultimoRealizado['fecha_programada'] ?? ''));

            return [
                'tipo' => 'HECHO',
                'periodo' => $fechaReferencia !== '' ? $this->semestreService->semestreDeFecha($fechaReferencia) : $this->semestreService->actual(),
            ];
        }

        $actualizado = DB::table('mantenimientos')
            ->where('id', (int) $ultimoProgramado['id'])
            ->update([
                'estado' => MantenimientoEstados::PENDIENTE,
                'observaciones' => DB::raw("CONCAT(COALESCE(observaciones, ''), ' | Revertido desde modulo Reversiones (PROGRAMAR).')"),
                'updated_at' => now(),
            ]);

        if ($actualizado <= 0) {
            return null;
        }

        $fechaReferencia = trim((string) ($ultimoProgramado['fecha_programada'] ?? ''));

        return [
            'tipo' => 'PROGRAMAR',
            'periodo' => $fechaReferencia !== '' ? $this->semestreService->semestreDeFecha($fechaReferencia) : $this->semestreService->actual(),
        ];
    }

    private function buscarUltimoProgramadoReversible(int $usuarioId): ?array
    {
        $fila = DB::table('mantenimientos')
            ->select('id', 'updated_at', 'fecha_programada')
            ->where('usuario_id', $usuarioId)
            ->whereIn('estado', MantenimientoEstados::estadosReversiblesComoProgramado())
            ->where(function ($query): void {
                    $query->where('observaciones', 'like', '%Programado desde modulo Proximos.%')
                    ->orWhere('observaciones', 'like', '%Hecho desde modulo Programados.%')
                    ->orWhere('observaciones', 'like', '%Revertido desde modulo Reversiones (HECHO).%');
            })
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();

        return $fila ? (array) $fila : null;
    }

    private function buscarUltimoRealizadoReversible(int $usuarioId): ?array
    {
        $fila = DB::table('mantenimientos')
            ->select('id', 'updated_at', 'fecha_programada', 'fecha_ejecucion')
            ->where('usuario_id', $usuarioId)
            ->whereIn('estado', MantenimientoEstados::estadosReversiblesComoHecho())
            ->where('observaciones', 'like', '%Hecho desde modulo Programados.%')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();

        return $fila ? (array) $fila : null;
    }

    private function buscarPendienteAutomatico(int $usuarioId): ?array
    {
        $fila = DB::table('mantenimientos')
            ->select('id')
            ->where('usuario_id', $usuarioId)
            ->whereIn('estado', MantenimientoEstados::estadosVisiblesEnProximos())
            ->where('observaciones', 'like', 'Generado automatico despues de marcar Hecho.%')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        return $fila ? (array) $fila : null;
    }

    private function construirObservacionRealizado(string $observaciones): ?string
    {
        return $this->textoNormalizer->anadirMarcaSistema($observaciones, 'Hecho desde modulo Programados.');
    }

}

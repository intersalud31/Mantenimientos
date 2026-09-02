<?php

namespace App\Services\Legacy;

use App\Services\SemestreService;
use DateInterval;
use DateTimeImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;


class MantenimientoRepository
{
    public function __construct(
        private readonly UsuarioMantenimientoResumenService $resumenService,
        private readonly TextoLegacyNormalizer $textoNormalizer,
        private readonly SemestreService $semestreService,
        private readonly PeriodicidadMantenimientoService $periodicidadService,
        private readonly EstadoMantenimientoVigenteService $estadoVigenteService,
        private readonly MantenimientoEventoRepository $eventoRepository
    ) {
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

    public function obtenerMantenimientoPorId(int $mantenimientoId): ?array
    {
        $fila = DB::table('mantenimientos')
            ->select('id', 'usuario_id', 'estado', 'fecha_programada', 'fecha_ejecucion', 'updated_at')
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
        $totalBaseFase = $this->contarBaseFasePorPeriodo('', $periodo);

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

        $totalNoExigibles = $this->contarNoExigiblesPorPeriodo('', $periodo);
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
            'total_no_exigibles' => $totalNoExigibles,
            'total_realizados' => $totalRealizados,
            'total_programados' => $totalProgramados,
            'total_pendientes' => $totalPendientes,
            'indice_cumplimiento' => $indiceCumplimiento,
        ];
    }

    /**
     * @return array{0:\Illuminate\Database\Query\Builder,1:string,2:string}
     */
    private function consultaBaseFasePorPeriodo(?array $periodo = null): array
    {
        $periodo = $periodo ?? $this->semestreService->actual();
        [$inicio, $fin] = $this->rangoPeriodo($periodo);

        $query = DB::table('usuarios as u')
            ->leftJoin('areas as a', 'a.id', '=', 'u.area_id')
            ->where('u.estado_usuario', 1)
            ->where(function ($subquery) use ($inicio, $fin): void {
                $subquery
                    ->whereExists($this->consultaActividadSemestralPorUsuario('u', $inicio, $fin))
                    ->orWhereExists($this->consultaPendienteAnteriorAbierto('u', $inicio));
            });

        return [$query, $inicio, $fin];
    }

    private function aplicarBusquedaDashboardUsuarios($query, string $busqueda): void
    {
        $busqueda = trim($busqueda);
        if ($busqueda === '') {
            return;
        }

        $busquedaNormalizada = mb_strtolower($busqueda, 'UTF-8');
        $query->where(function ($subquery) use ($busqueda, $busquedaNormalizada): void {
            $subquery
                ->whereRaw('LOWER(u.nombre) LIKE ?', ['%' . $busquedaNormalizada . '%'])
                ->orWhereRaw("LOWER(COALESCE(a.nombre, 'SIN AREA')) LIKE ?", ['%' . $busquedaNormalizada . '%'])
                ->orWhereRaw('LOWER(COALESCE(u.serial_equipo, \'\')) LIKE ?', ['%' . $busquedaNormalizada . '%']);

            if (ctype_digit($busqueda)) {
                $subquery->orWhereRaw('CAST(u.codigo_activo AS CHAR) LIKE ?', ['%' . $busqueda . '%']);
            }
        });
    }

    public function contarBaseFasePorPeriodo(string $busqueda = '', ?array $periodo = null): int
    {
        [$query] = $this->consultaBaseFasePorPeriodo($periodo);
        $this->aplicarBusquedaDashboardUsuarios($query, $busqueda);

        return (int) $query->distinct('u.id')->count('u.id');
    }

    public function listarBaseFasePorPeriodo(int $limite, int $desplazamiento, string $busqueda = '', ?array $periodo = null): array
    {
        [$query, $inicio, $fin] = $this->consultaBaseFasePorPeriodo($periodo);

        $query->select([
            'u.id as usuario_id',
            'u.nombre',
            'u.codigo_activo',
            'u.serial_equipo',
            'u.tipo_equipo',
            'u.proxima_fecha_mantenimiento',
            DB::raw("COALESCE(a.nombre, 'SIN AREA') AS area_nombre"),
            DB::raw("(
                SELECT MAX(COALESCE(m.fecha_ejecucion, m.fecha_programada))
                FROM mantenimientos as m
                WHERE m.usuario_id = u.id
                  AND COALESCE(m.fecha_ejecucion, m.fecha_programada) BETWEEN '{$inicio} 00:00:00' AND '{$fin} 23:59:59'
            ) AS fecha_actividad_periodo"),
            DB::raw($this->sqlPendienteAnteriorAbierto('u', $inicio) . ' AS es_pendiente_anterior'),
        ]);

        $this->aplicarBusquedaDashboardUsuarios($query, $busqueda);

        $filas = $query
            ->orderBy('u.nombre')
            ->skip($desplazamiento)
            ->take($limite)
            ->get()
            ->map(static fn ($row) => (array) $row)
            ->all();

        $filas = $this->estadoVigenteService->anexarEstadoVigente($filas, $periodo, 'usuario_id');

        foreach ($filas as &$fila) {
            $situacion = $this->resolverSituacionBaseFase($fila);
            $fila['situacion_etiqueta'] = $situacion['etiqueta'];
            $fila['situacion_badge_clase'] = $situacion['clase'];
        }
        unset($fila);

        return $filas;
    }

    public function contarPendientesDashboardPorPeriodo(string $busqueda = '', ?array $periodo = null): int
    {
        [$query, $inicio, $fin] = $this->consultaBaseFasePorPeriodo($periodo);

        $query->whereNotExists(function ($subquery) use ($inicio, $fin): void {
            $subquery->select(DB::raw(1))
                ->from('mantenimientos as m')
                ->whereColumn('m.usuario_id', 'u.id')
                ->whereIn('m.estado', MantenimientoEstados::estadosVisiblesEnHechos())
                ->whereNotNull('m.fecha_ejecucion')
                ->whereBetween('m.fecha_ejecucion', [$inicio, $fin]);
        });

        $this->aplicarBusquedaDashboardUsuarios($query, $busqueda);

        return (int) $query->distinct('u.id')->count('u.id');
    }

    public function listarPendientesDashboardPorPeriodo(int $limite, int $desplazamiento, string $busqueda = '', ?array $periodo = null): array
    {
        [$query, $inicio, $fin] = $this->consultaBaseFasePorPeriodo($periodo);

        $query->whereNotExists(function ($subquery) use ($inicio, $fin): void {
            $subquery->select(DB::raw(1))
                ->from('mantenimientos as m')
                ->whereColumn('m.usuario_id', 'u.id')
                ->whereIn('m.estado', MantenimientoEstados::estadosVisiblesEnHechos())
                ->whereNotNull('m.fecha_ejecucion')
                ->whereBetween('m.fecha_ejecucion', [$inicio, $fin]);
        });

        $query->select([
            'u.id as usuario_id',
            'u.nombre',
            'u.codigo_activo',
            'u.serial_equipo',
            'u.tipo_equipo',
            'u.proxima_fecha_mantenimiento',
            DB::raw("COALESCE(a.nombre, 'SIN AREA') AS area_nombre"),
            DB::raw($this->sqlPendienteAnteriorAbierto('u', $inicio) . ' AS es_pendiente_anterior'),
        ]);

        $this->aplicarBusquedaDashboardUsuarios($query, $busqueda);

        $filas = $query
            ->orderBy('u.proxima_fecha_mantenimiento')
            ->orderBy('u.nombre')
            ->skip($desplazamiento)
            ->take($limite)
            ->get()
            ->map(static fn ($row) => (array) $row)
            ->all();

        $filas = $this->estadoVigenteService->anexarEstadoVigente($filas, $periodo, 'usuario_id');

        foreach ($filas as &$fila) {
            $estadoVigente = (string) ($fila['estado_vigente'] ?? '');
            $esPendienteAnterior = (int) ($fila['es_pendiente_anterior'] ?? 0) === 1;

            if ($esPendienteAnterior) {
                $fila['motivo_pendiente'] = 'Pendiente anterior sin cierre posterior.';
            } elseif ($estadoVigente === EstadoMantenimientoVigenteService::PROGRAMADO) {
                $fila['motivo_pendiente'] = 'Programado vigente sin ejecución en el semestre.';
            } else {
                $fila['motivo_pendiente'] = 'Pendiente del semestre sin ejecución registrada.';
            }
        }
        unset($fila);

        return $filas;
    }

    private function sqlPendienteAnteriorAbierto(string $aliasUsuario, string $inicio): string
    {
        $estadosAbiertos = implode("','", $this->estadosAbiertosPendienteAnterior());

        return "CASE
            WHEN EXISTS (
                SELECT 1
                FROM mantenimientos as pendiente_anterior
                WHERE pendiente_anterior.usuario_id = {$aliasUsuario}.id
                  AND pendiente_anterior.estado IN ('{$estadosAbiertos}')
                  AND pendiente_anterior.fecha_programada < '{$inicio}'
                  AND NOT EXISTS (
                    SELECT 1
                    FROM mantenimientos as cierre_ciclo
                    WHERE cierre_ciclo.usuario_id = {$aliasUsuario}.id
                      AND cierre_ciclo.estado IN ('" . implode("','", MantenimientoEstados::estadosVisiblesEnHechos()) . "')
                      AND cierre_ciclo.fecha_ejecucion IS NOT NULL
                      AND cierre_ciclo.fecha_ejecucion >= pendiente_anterior.fecha_programada
                  )
            ) THEN 1 ELSE 0 END";
    }

    /**
     * @param  array<string, mixed>  $fila
     * @return array{etiqueta:string,clase:string}
     */
    private function resolverSituacionBaseFase(array $fila): array
    {
        $estadoVigente = trim((string) ($fila['estado_vigente'] ?? ''));
        $proximaFecha = trim((string) ($fila['proxima_fecha_mantenimiento'] ?? ''));
        $esPendienteAnterior = (int) ($fila['es_pendiente_anterior'] ?? 0) === 1;

        if ($estadoVigente === EstadoMantenimientoVigenteService::EJECUTADO) {
            return [
                'etiqueta' => 'Mantenimiento realizado',
                'clase' => 'estado-tabla-exito',
            ];
        }

        if ($esPendienteAnterior) {
            return [
                'etiqueta' => 'Pendiente de periodo anterior',
                'clase' => 'estado-tabla-alerta',
            ];
        }

        if ($estadoVigente === EstadoMantenimientoVigenteService::PROGRAMADO) {
            return [
                'etiqueta' => 'Programado para el ' . $this->formatearFechaSituacion($proximaFecha),
                'clase' => 'estado-tabla-info',
            ];
        }

        if ($proximaFecha === '') {
            return [
                'etiqueta' => 'Requiere programación',
                'clase' => 'estado-tabla-neutral',
            ];
        }

        try {
            $fechaMantenimiento = Carbon::parse($proximaFecha)->startOfDay();
        } catch (\Throwable) {
            return [
                'etiqueta' => 'Requiere programación',
                'clase' => 'estado-tabla-neutral',
            ];
        }

        $hoy = today()->startOfDay();

        if ($fechaMantenimiento->equalTo($hoy)) {
            return [
                'etiqueta' => 'Requiere atención hoy',
                'clase' => 'estado-tabla-alerta',
            ];
        }

        if ($fechaMantenimiento->lessThan($hoy)) {
            $diasVencido = $fechaMantenimiento->diffInDays($hoy);

            return [
                'etiqueta' => 'Vencido hace ' . $diasVencido . ' día' . ($diasVencido === 1 ? '' : 's'),
                'clase' => 'estado-tabla-alerta',
            ];
        }

        return [
            'etiqueta' => 'Requiere programación',
            'clase' => 'estado-tabla-neutral',
        ];
    }

    private function formatearFechaSituacion(string $valor): string
    {
        $valor = trim($valor);
        if ($valor === '') {
            return 'sin fecha';
        }

        try {
            return Carbon::parse($valor)->format('d/m/Y');
        } catch (\Throwable) {
            return $valor;
        }
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

    /**
     * @return array{0:\Illuminate\Database\Query\Builder,1:string,2:string}
     */
    private function consultaActividadSemestralPorUsuario(string $aliasUsuario, string $inicio, string $fin): \Closure
    {
        return static function ($query) use ($aliasUsuario, $inicio, $fin): void {
            $query->select(DB::raw(1))
                ->from('mantenimientos as m')
                ->whereColumn('m.usuario_id', $aliasUsuario . '.id')
                ->whereRaw('COALESCE(m.fecha_ejecucion, m.fecha_programada) IS NOT NULL')
                ->whereRaw('COALESCE(m.fecha_ejecucion, m.fecha_programada) BETWEEN ? AND ?', [$inicio . ' 00:00:00', $fin . ' 23:59:59']);
        };
    }

    /**
     * @return array{0:\Illuminate\Database\Query\Builder,1:string,2:string}
     */
    private function consultaNoExigiblesPorPeriodo(?array $periodo = null): array
    {
        $periodo = $periodo ?? $this->semestreService->actual();
        [$inicio, $fin] = $this->rangoPeriodo($periodo);

        $query = DB::table('usuarios as u')
            ->leftJoin('areas as a', 'a.id', '=', 'u.area_id')
            ->where('u.estado_usuario', 1)
            ->whereNotNull('u.proxima_fecha_mantenimiento')
            ->where('u.proxima_fecha_mantenimiento', '>', $fin)
            ->whereNotExists($this->consultaActividadSemestralPorUsuario('u', $inicio, $fin))
            ->whereNotExists($this->consultaPendienteAnteriorAbierto('u', $inicio));

        return [$query, $inicio, $fin];
    }

    private function aplicarBusquedaNoExigibles($query, string $busqueda): void
    {
        $busqueda = trim($busqueda);
        if ($busqueda === '') {
            return;
        }

        $busquedaNormalizada = mb_strtolower($busqueda, 'UTF-8');
        $query->where(function ($subquery) use ($busqueda, $busquedaNormalizada): void {
            $subquery
                ->whereRaw('LOWER(u.nombre) LIKE ?', ['%' . $busquedaNormalizada . '%'])
                ->orWhereRaw("LOWER(COALESCE(a.nombre, 'SIN AREA')) LIKE ?", ['%' . $busquedaNormalizada . '%'])
                ->orWhereRaw('CAST(u.codigo_activo AS CHAR) LIKE ?', ['%' . $busqueda . '%']);
        });
    }

    private function aplicarFiltroMotivoNoExigible($query, string $motivo): void
    {
        $motivo = trim($motivo);

        if ($motivo === NoExigibleReasonService::EQUIPO_NUEVO) {
            $query->where('u.tipo_equipo', 'nuevo');
            return;
        }

        if ($motivo === NoExigibleReasonService::FUERA_PERIODO) {
            $query->where(function ($subquery): void {
                $subquery->where('u.tipo_equipo', '<>', 'nuevo')
                    ->orWhereNull('u.tipo_equipo');
            });
        }
    }

    private function resolverMotivoNoExigible(array $fila): string
    {
        return trim((string) ($fila['tipo_equipo'] ?? '')) === 'nuevo'
            ? NoExigibleReasonService::EQUIPO_NUEVO
            : NoExigibleReasonService::FUERA_PERIODO;
    }

    /**
     * @return array{0:\Illuminate\Database\Query\Builder,1:string,2:string}
     */
    private function consultaEquiposNuevosSinMantenimiento(?array $periodo = null): array
    {
        $periodo = $periodo ?? $this->semestreService->actual();
        [$inicio, $fin] = $this->rangoPeriodo($periodo);

        $query = DB::table('usuarios as u')
            ->leftJoin('areas as a', 'a.id', '=', 'u.area_id')
            ->where('u.estado_usuario', 1)
            ->where('u.tipo_equipo', 'nuevo')
            ->whereNotNull('u.proxima_fecha_mantenimiento');

        if ($inicio !== '' && $fin !== '') {
            $query->where(function ($subquery) use ($inicio, $fin): void {
                $subquery->where('u.proxima_fecha_mantenimiento', '<', $inicio)
                    ->orWhere('u.proxima_fecha_mantenimiento', '>', $fin);
            });
        }

        return [$query, $inicio, $fin];
    }

    public function contarNoExigiblesPorPeriodo(string $busqueda = '', ?array $periodo = null, string $motivo = ''): int
    {
        [$query] = $this->consultaNoExigiblesPorPeriodo($periodo);
        $this->aplicarBusquedaNoExigibles($query, $busqueda);
        $this->aplicarFiltroMotivoNoExigible($query, $motivo);

        return (int) $query->count('u.id');
    }

    public function listarNoExigiblesPorPeriodo(
        int $limite,
        int $desplazamiento,
        string $busqueda = '',
        ?array $periodo = null,
        string $motivo = ''
    ): array {
        [$query] = $this->consultaNoExigiblesPorPeriodo($periodo);

        $query->select([
            'u.id as usuario_id',
            'u.nombre',
            'u.codigo_activo',
            'u.tipo_equipo',
            'u.estado_usuario',
            DB::raw("COALESCE(a.nombre, 'SIN AREA') AS area_nombre"),
            'u.fecha_ultimo_mantenimiento_base',
            'u.proxima_fecha_mantenimiento',
        ]);

        $this->aplicarBusquedaNoExigibles($query, $busqueda);
        $this->aplicarFiltroMotivoNoExigible($query, $motivo);

        $query->orderBy('u.proxima_fecha_mantenimiento')
            ->orderBy('u.nombre')
            ->skip($desplazamiento)
            ->take($limite);

        return $query->get()->map(function ($row): array {
            $fila = (array) $row;
            $fila['motivo_codigo'] = $this->resolverMotivoNoExigible($fila);

            return $fila;
        })->all();
    }

    public function contarEquiposNuevosSinMantenimiento(string $busqueda = '', ?array $periodo = null): int
    {
        [$query] = $this->consultaEquiposNuevosSinMantenimiento($periodo);

        $busqueda = trim($busqueda);
        if ($busqueda !== '') {
            $busquedaNormalizada = mb_strtolower($busqueda, 'UTF-8');
            $query->where(function ($subquery) use ($busqueda, $busquedaNormalizada): void {
                $subquery
                    ->whereRaw('LOWER(u.nombre) LIKE ?', ['%' . $busquedaNormalizada . '%'])
                    ->orWhereRaw("LOWER(COALESCE(a.nombre, 'SIN AREA')) LIKE ?", ['%' . $busquedaNormalizada . '%'])
                    ->orWhereRaw('CAST(u.codigo_activo AS CHAR) LIKE ?', ['%' . $busqueda . '%']);
            });
        }

        return (int) $query->count('u.id');
    }

    public function listarEquiposNuevosSinMantenimiento(int $limite, int $desplazamiento, string $busqueda = '', ?array $periodo = null): array
    {
        [$query] = $this->consultaEquiposNuevosSinMantenimiento($periodo);

        $query->select([
            'u.id as usuario_id',
            'u.nombre',
            'u.codigo_activo',
            'u.tipo_equipo',
            DB::raw("COALESCE(a.nombre, 'SIN AREA') AS area_nombre"),
            'u.fecha_ultimo_mantenimiento_base',
            'u.proxima_fecha_mantenimiento',
        ]);

        $busqueda = trim($busqueda);
        if ($busqueda !== '') {
            $busquedaNormalizada = mb_strtolower($busqueda, 'UTF-8');
            $query->where(function ($subquery) use ($busqueda, $busquedaNormalizada): void {
                $subquery
                    ->whereRaw('LOWER(u.nombre) LIKE ?', ['%' . $busquedaNormalizada . '%'])
                    ->orWhereRaw("LOWER(COALESCE(a.nombre, 'SIN AREA')) LIKE ?", ['%' . $busquedaNormalizada . '%'])
                    ->orWhereRaw('CAST(u.codigo_activo AS CHAR) LIKE ?', ['%' . $busqueda . '%']);
            });
        }

        $query->orderBy('u.proxima_fecha_mantenimiento')
            ->orderBy('u.nombre')
            ->skip($desplazamiento)
            ->take($limite);

        return $query->get()->map(static fn ($row) => (array) $row)->all();
    }



    private function consultaPendienteAnteriorAbierto(string $aliasUsuario, string $inicio): \Closure
    {
        $estadosAbiertos = $this->estadosAbiertosPendienteAnterior();

        return static function ($query) use ($aliasUsuario, $inicio, $estadosAbiertos): void {
            $query->select(DB::raw(1))
                ->from('mantenimientos as pendiente_anterior')
                ->whereColumn('pendiente_anterior.usuario_id', $aliasUsuario . '.id')
                ->whereIn('pendiente_anterior.estado', $estadosAbiertos)
                ->where('pendiente_anterior.fecha_programada', '<', $inicio)
                ->whereNotExists(function ($cierre) use ($aliasUsuario): void {
                    $cierre->select(DB::raw(1))
                        ->from('mantenimientos as cierre_ciclo')
                        ->whereColumn('cierre_ciclo.usuario_id', $aliasUsuario . '.id')
                        ->whereIn('cierre_ciclo.estado', MantenimientoEstados::estadosVisiblesEnHechos())
                        ->whereNotNull('cierre_ciclo.fecha_ejecucion')
                        ->whereColumn('cierre_ciclo.fecha_ejecucion', '>=', 'pendiente_anterior.fecha_programada');
                });
        };
    }

    private function aplicarCondicionExigibleEnProximos($query, string $inicio, string $fin): void
    {
        $query->whereBetween('u.proxima_fecha_mantenimiento', [$inicio, $fin]);
    }

    /**
     * @return array<int, string>
     */
    private function estadosAbiertosPendienteAnterior(): array
    {
        return array_values(array_unique(array_merge(
            MantenimientoEstados::estadosVisiblesEnProximos(),
            MantenimientoEstados::estadosVisiblesEnProgramados()
        )));
    }

    public function contarProximosPorPeriodo(string $busqueda = '', ?array $periodo = null): int
    {
        $this->sincronizarPendientesProximosPorPeriodo($periodo);

        $periodo = $periodo ?? $this->semestreService->actual();
        [$inicio, $fin] = $this->rangoPeriodo($periodo);

        $query = DB::table('usuarios as u')
            ->leftJoin('areas as a', 'a.id', '=', 'u.area_id')
            ->where('u.estado_usuario', 1)
            ->whereNotNull('u.proxima_fecha_mantenimiento');

        $this->aplicarCondicionExigibleEnProximos($query, $inicio, $fin);

        $query->whereNotExists(function ($subquery) use ($inicio, $fin): void {
            $subquery->select(DB::raw(1))
                ->from('mantenimientos as bloqueador')
                ->whereColumn('bloqueador.usuario_id', 'u.id')
                ->whereIn('bloqueador.estado', MantenimientoEstados::estadosVisiblesEnProgramados())
                ->where(function ($query) use ($inicio, $fin): void {
                    $query->whereBetween('bloqueador.fecha_programada', [$inicio, $fin])
                        ->orWhereBetween('bloqueador.fecha_ejecucion', [$inicio, $fin]);
                });
        });

        $busqueda = trim($busqueda);
        if ($busqueda !== '') {
            $busquedaNormalizada = mb_strtolower($busqueda, 'UTF-8');
            $query->where(function ($subquery) use ($busqueda, $busquedaNormalizada): void {
                $subquery
                    ->whereRaw('LOWER(u.nombre) LIKE ?', ['%' . $busquedaNormalizada . '%'])
                    ->orWhereRaw("LOWER(COALESCE(a.nombre, 'SIN AREA')) LIKE ?", ['%' . $busquedaNormalizada . '%'])
                    ->orWhereRaw('LOWER(u.serial_equipo) LIKE ?', ['%' . $busquedaNormalizada . '%'])
                    ->orWhereRaw('CAST(u.codigo_activo AS CHAR) LIKE ?', ['%' . $busqueda . '%']);
            });
        }

        return (int) $query->count('u.id');
    }

    public function contarProximosParaNotificacion(?array $periodo = null): int
    {
        $periodo = $periodo ?? $this->semestreService->actual();
        [$inicio, $fin] = $this->rangoPeriodo($periodo);

        $query = DB::table('usuarios as u')
            ->leftJoin('areas as a', 'a.id', '=', 'u.area_id')
            ->where('u.estado_usuario', 1)
            ->whereNotNull('u.proxima_fecha_mantenimiento')
            ->whereBetween('u.proxima_fecha_mantenimiento', [$inicio, $fin])
            ->whereNotExists(function ($subquery) use ($inicio, $fin): void {
                $subquery->select(DB::raw(1))
                    ->from('mantenimientos as bloqueador')
                    ->whereColumn('bloqueador.usuario_id', 'u.id')
                    ->whereIn('bloqueador.estado', MantenimientoEstados::estadosVisiblesEnProgramados())
                    ->where(function ($query) use ($inicio, $fin): void {
                        $query->whereBetween('bloqueador.fecha_programada', [$inicio, $fin])
                            ->orWhereBetween('bloqueador.fecha_ejecucion', [$inicio, $fin]);
                    });
            });

        return (int) $query->count('u.id');
    }

    public function listarProximosPorPeriodo(int $limite, int $desplazamiento, string $busqueda = '', ?array $periodo = null): array
    {
        $this->sincronizarPendientesProximosPorPeriodo($periodo);

        $periodo = $periodo ?? $this->semestreService->actual();
        [$inicio, $fin] = $this->rangoPeriodo($periodo);

        $query = DB::table('usuarios as u')
            ->leftJoin('areas as a', 'a.id', '=', 'u.area_id')
            ->leftJoin('mantenimientos as m', function ($join): void {
                $join->on('m.usuario_id', '=', 'u.id')
                    ->where('m.estado', MantenimientoEstados::PENDIENTE)
                    ->whereColumn('m.fecha_programada', 'u.proxima_fecha_mantenimiento');
            })
            ->where('u.estado_usuario', 1)
            ->whereNotNull('u.proxima_fecha_mantenimiento');

        $this->aplicarCondicionExigibleEnProximos($query, $inicio, $fin);

        $query->whereNotExists(function ($subquery) use ($inicio, $fin): void {
            $subquery->select(DB::raw(1))
                ->from('mantenimientos as bloqueador')
                ->whereColumn('bloqueador.usuario_id', 'u.id')
                ->whereIn('bloqueador.estado', MantenimientoEstados::estadosVisiblesEnProgramados())
                ->where(function ($query) use ($inicio, $fin): void {
                    $query->whereBetween('bloqueador.fecha_programada', [$inicio, $fin])
                        ->orWhereBetween('bloqueador.fecha_ejecucion', [$inicio, $fin]);
                });
        })
            ->select([
                'm.id as mantenimiento_id',
                'u.id as usuario_id',
                DB::raw("COALESCE(m.estado, '" . MantenimientoEstados::PENDIENTE . "') AS estado"),
                'u.nombre',
                'u.codigo_activo',
                'u.tipo_equipo',
                DB::raw("COALESCE(a.nombre, 'SIN AREA') AS area_nombre"),
                'u.ultima_fecha_mantenimiento as ultima_fecha_programada',
                'u.proxima_fecha_mantenimiento as proxima_fecha_programada',
                'm.fecha_ejecucion',
                'm.observaciones',
                'm.tecnico_responsable',
                'm.evidencia_pdf',
                DB::raw($this->sqlPendienteAnteriorAbierto('u', $inicio) . ' AS es_vencido_anterior'),
            ]);

        $busqueda = trim($busqueda);
        if ($busqueda !== '') {
            $busquedaNormalizada = mb_strtolower($busqueda, 'UTF-8');
            $query->where(function ($subquery) use ($busqueda, $busquedaNormalizada): void {
                $subquery
                    ->whereRaw('LOWER(u.nombre) LIKE ?', ['%' . $busquedaNormalizada . '%'])
                    ->orWhereRaw("LOWER(COALESCE(a.nombre, 'SIN AREA')) LIKE ?", ['%' . $busquedaNormalizada . '%'])
                    ->orWhereRaw('LOWER(u.serial_equipo) LIKE ?', ['%' . $busquedaNormalizada . '%'])
                    ->orWhereRaw('CAST(u.codigo_activo AS CHAR) LIKE ?', ['%' . $busqueda . '%']);
            });
        }

        $query->orderBy('u.proxima_fecha_mantenimiento')
            ->orderBy('u.nombre')
            ->skip($desplazamiento)
            ->take($limite);

        $filas = $query->get()->map(static fn ($row) => (array) $row)->all();

        $filas = $this->estadoVigenteService->anexarEstadoVigente($filas, $periodo, 'usuario_id');

        foreach ($filas as &$fila) {
            $this->hidratarEvidenciaPublica($fila);
            $this->hidratarEstadoVisual($fila);
            $fila['observaciones'] = $this->textoNormalizer->observacion((string) ($fila['observaciones'] ?? ''));

            $ultimaFecha = trim((string) ($fila['ultima_fecha_programada'] ?? ''));
            if ($ultimaFecha === '') {
                $tipoEquipo = trim((string) ($fila['tipo_equipo'] ?? 'normal'));
                $fila['ultimo_mantenimiento_texto'] = $tipoEquipo === 'nuevo' ? 'Equipo nuevo' : 'Sin historial';
            } else {
                $fila['ultimo_mantenimiento_texto'] = '';
            }

            if ((int) ($fila['es_vencido_anterior'] ?? 0) == 1) {
                $fila['pendiente_anterior_etiqueta'] = 'Pendiente anterior';
                $fila['pendiente_anterior_clase'] = 'estado-tabla-alerta';
            }
        }
        unset($fila);

        return $filas;
    }

    public function sincronizarPendientesProximosPorPeriodo(?array $periodo = null): int
    {
        $periodo = $periodo ?? $this->semestreService->actual();
        [$inicio, $fin] = $this->rangoPeriodo($periodo);

        $usuarios = DB::table('usuarios as u')
            ->select('u.id')
            ->where('u.estado_usuario', 1)
            ->whereNotNull('u.proxima_fecha_mantenimiento')
            ->whereBetween('u.proxima_fecha_mantenimiento', [$inicio, $fin])
            ->orderBy('u.proxima_fecha_mantenimiento')
            ->orderBy('u.nombre')
            ->pluck('id')
            ->all();

        if ($usuarios === []) {
            return 0;
        }

        return DB::transaction(function () use ($usuarios, $periodo, $inicio, $fin): int {
            $cambios = 0;

            foreach ($usuarios as $usuarioId) {
                $usuarioId = (int) $usuarioId;
                $this->resumenService->sincronizarResumenUsuario($usuarioId, $periodo);

                $proximaFecha = trim((string) (DB::table('usuarios')->where('id', $usuarioId)->value('proxima_fecha_mantenimiento') ?? ''));
                if ($proximaFecha === '') {
                    continue;
                }

                $hayProgramado = DB::table('mantenimientos')
                    ->where('usuario_id', $usuarioId)
                    ->whereIn('estado', MantenimientoEstados::estadosVisiblesEnProgramados())
                    ->where(function ($query) use ($inicio, $fin): void {
                        $query->whereBetween('fecha_programada', [$inicio, $fin])
                            ->orWhereBetween('fecha_ejecucion', [$inicio, $fin]);
                    })
                    ->exists();

                $pendientes = DB::table('mantenimientos')
                    ->select('id', 'fecha_programada')
                    ->where('usuario_id', $usuarioId)
                    ->where('estado', MantenimientoEstados::PENDIENTE)
                    ->whereBetween('fecha_programada', [$inicio, $fin])
                    ->orderBy('fecha_programada')
                    ->orderBy('id')
                    ->get();

                if ($hayProgramado) {
                    foreach ($pendientes as $pendiente) {
                        DB::table('mantenimientos')
                            ->where('id', (int) $pendiente->id)
                            ->update([
                                'estado' => MantenimientoEstados::CANCELADO,
                                'observaciones' => DB::raw("CONCAT(COALESCE(observaciones, ''), ' | Cancelado automaticamente porque ya existe una programacion activa para este periodo.')"),
                                'updated_at' => now(),
                            ]);
                        $cambios++;
                    }

                    continue;
                }

                if ($pendientes->isEmpty()) {
                    DB::table('mantenimientos')->insert([
                        'usuario_id' => $usuarioId,
                        'fecha_programada' => $proximaFecha,
                        'hora_programada' => null,
                        'estado' => MantenimientoEstados::PENDIENTE,
                        'fecha_ejecucion' => null,
                        'observaciones' => 'Generado automaticamente desde la sincronizacion del modulo Proximos.',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $cambios++;
                    continue;
                }

                $principal = $pendientes->firstWhere('fecha_programada', $proximaFecha);
                if ($principal) {
                    $pendientes = $pendientes->reject(static fn ($pendiente) => (int) $pendiente->id === (int) $principal->id)->values();
                } else {
                    $principal = $pendientes->shift();
                }

                if ($principal && trim((string) ($principal->fecha_programada ?? '')) !== $proximaFecha) {
                    DB::table('mantenimientos')
                        ->where('id', (int) $principal->id)
                        ->update([
                            'fecha_programada' => $proximaFecha,
                            'updated_at' => now(),
                        ]);
                    $cambios++;
                }

                foreach ($pendientes as $duplicado) {
                    DB::table('mantenimientos')
                        ->where('id', (int) $duplicado->id)
                        ->update([
                            'estado' => MantenimientoEstados::CANCELADO,
                            'observaciones' => DB::raw("CONCAT(COALESCE(observaciones, ''), ' | Cancelado automaticamente por duplicidad en Proximos.')"),
                            'updated_at' => now(),
                        ]);
                    $cambios++;
                }
            }

            return $cambios;
        });
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
            $query->where(function ($subquery) use ($inicio, $fin): void {
                $subquery->whereBetween('m.fecha_ejecucion', [$inicio, $fin])
                    ->orWhereBetween('m.fecha_programada', [$inicio, $fin]);
            });
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

        $filas = $this->estadoVigenteService->anexarEstadoVigente($filas, $periodo, 'usuario_id');

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
            $query->where(function ($subquery) use ($inicio, $fin): void {
                $subquery->whereBetween('m.fecha_ejecucion', [$inicio, $fin])
                    ->orWhereBetween('m.fecha_programada', [$inicio, $fin]);
            });
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

    public function contarProgramadosHoy(): int
    {
        $hoy = today()->toDateString();

        $query = DB::table('usuarios as u')
            ->join('mantenimientos as m', 'm.usuario_id', '=', 'u.id')
            ->where('u.estado_usuario', 1)
            ->whereIn('m.estado', MantenimientoEstados::estadosVisiblesEnProgramados())
            ->whereDate('m.fecha_programada', $hoy)
            ->whereNull('m.fecha_ejecucion');

        return (int) $query->distinct('u.id')->count('u.id');
    }

    public function contarNotificacionesVencidas(?array $periodo = null): int
    {
        return count($this->consultarNotificacionesVencidas($periodo));
    }

    public function contarNotificacionesProximas(?array $periodo = null): int
    {
        return count($this->consultarNotificacionesProximas($periodo));
    }

    public function contarNotificacionesHoy(): int
    {
        return count($this->consultarNotificacionesHoy());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listarNotificacionesVencidas(int $limite = 100, int $desplazamiento = 0, ?array $periodo = null): array
    {
        return array_slice($this->consultarNotificacionesVencidas($periodo), $desplazamiento, $limite);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listarNotificacionesProximas(int $limite = 100, int $desplazamiento = 0, ?array $periodo = null): array
    {
        return array_slice($this->consultarNotificacionesProximas($periodo), $desplazamiento, $limite);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listarNotificacionesHoy(int $limite = 100, int $desplazamiento = 0): array
    {
        return array_slice($this->consultarNotificacionesHoy(), $desplazamiento, $limite);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function consultarNotificacionesVencidas(?array $periodo = null): array
    {
        $periodo = $periodo ?? $this->semestreService->actual();
        $hoy = today()->toDateString();

        $filas = DB::table('usuarios as u')
            ->leftJoin('areas as a', 'a.id', '=', 'u.area_id')
            ->where('u.estado_usuario', 1)
            ->whereNotNull('u.proxima_fecha_mantenimiento')
            ->whereDate('u.proxima_fecha_mantenimiento', '<', $hoy)
            ->whereNotExists(function ($query): void {
                $query->from('mantenimientos as cancelado')
                    ->whereColumn('cancelado.usuario_id', 'u.id')
                    ->where('cancelado.estado', MantenimientoEstados::CANCELADO)
                    ->whereColumn('cancelado.fecha_programada', 'u.proxima_fecha_mantenimiento');
            })
            ->orderBy('u.proxima_fecha_mantenimiento')
            ->orderBy('u.nombre')
            ->select([
                'u.id as usuario_id',
                'u.nombre',
                'u.codigo_activo',
                'u.tipo_equipo',
                DB::raw("COALESCE(a.nombre, 'SIN AREA') AS area_nombre"),
                'u.proxima_fecha_mantenimiento as fecha_limite',
            ])
            ->get()
            ->map(static fn ($row) => (array) $row)
            ->all();

        $filas = $this->estadoVigenteService->anexarEstadoVigente($filas, $periodo, 'usuario_id');

        return array_values(array_filter(array_map(function (array $fila) use ($hoy): ?array {
            if (($fila['estado_vigente'] ?? null) === EstadoMantenimientoVigenteService::EJECUTADO) {
                return null;
            }

            $fechaLimite = (string) ($fila['fecha_limite'] ?? '');
            $fila['dias_atraso'] = $fechaLimite !== ''
                ? max(0, today()->diffInDays($fechaLimite, false) * -1)
                : 0;
            $fila['estado_actual'] = (string) ($fila['estado_vigente_etiqueta'] ?? 'No programado');
            $fila['estado_actual_clase'] = (string) ($fila['estado_vigente_badge_clase'] ?? 'estado-tabla-alerta');
            $fila['fecha_referencia'] = $fechaLimite;
            $fila['hoy'] = $hoy;

            return $fila;
        }, $filas)));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function consultarNotificacionesProximas(?array $periodo = null): array
    {
        $periodo = $periodo ?? $this->semestreService->actual();
        $hoy = today()->toDateString();
        $finRango = today()->addDays(7)->toDateString();

        $filas = DB::table('usuarios as u')
            ->leftJoin('areas as a', 'a.id', '=', 'u.area_id')
            ->where('u.estado_usuario', 1)
            ->whereNotNull('u.proxima_fecha_mantenimiento')
            ->whereBetween('u.proxima_fecha_mantenimiento', [$hoy, $finRango])
            ->whereNotExists(function ($query): void {
                $query->from('mantenimientos as cancelado')
                    ->whereColumn('cancelado.usuario_id', 'u.id')
                    ->where('cancelado.estado', MantenimientoEstados::CANCELADO)
                    ->whereColumn('cancelado.fecha_programada', 'u.proxima_fecha_mantenimiento');
            })
            ->orderBy('u.proxima_fecha_mantenimiento')
            ->orderBy('u.nombre')
            ->select([
                'u.id as usuario_id',
                'u.nombre',
                'u.codigo_activo',
                'u.tipo_equipo',
                DB::raw("COALESCE(a.nombre, 'SIN AREA') AS area_nombre"),
                'u.proxima_fecha_mantenimiento as fecha_programada',
            ])
            ->get()
            ->map(static fn ($row) => (array) $row)
            ->all();

        $filas = $this->estadoVigenteService->anexarEstadoVigente($filas, $periodo, 'usuario_id');

        return array_values(array_filter(array_map(function (array $fila): ?array {
            if (($fila['estado_vigente'] ?? null) === EstadoMantenimientoVigenteService::EJECUTADO) {
                return null;
            }

            $fechaProgramada = (string) ($fila['fecha_programada'] ?? '');
            $fila['dias_restantes'] = $fechaProgramada !== ''
                ? max(0, today()->diffInDays($fechaProgramada, false))
                : 0;
            $fila['fecha_referencia'] = $fechaProgramada;

            return $fila;
        }, $filas)));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function consultarNotificacionesHoy(): array
    {
        $hoy = today()->toDateString();

        return DB::table('mantenimientos as m')
            ->join('usuarios as u', 'u.id', '=', 'm.usuario_id')
            ->leftJoin('areas as a', 'a.id', '=', 'u.area_id')
            ->where('u.estado_usuario', 1)
            ->where('m.estado', MantenimientoEstados::PROGRAMADO)
            ->whereDate('m.fecha_programada', $hoy)
            ->whereNull('m.fecha_ejecucion')
            ->orderBy('m.hora_programada')
            ->orderBy('u.nombre')
            ->select([
                'm.id as mantenimiento_id',
                'u.id as usuario_id',
                'u.nombre',
                'u.codigo_activo',
                'u.tipo_equipo',
                DB::raw("COALESCE(a.nombre, 'SIN AREA') AS area_nombre"),
                'm.fecha_programada',
                'm.hora_programada',
                'm.tecnico_responsable',
            ])
            ->get()
            ->map(static function ($row): array {
                $fila = (array) $row;
                $fila['tecnico_responsable'] = trim((string) ($fila['tecnico_responsable'] ?? '')) ?: 'No registrado';

                return $fila;
            })
            ->all();
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

    public function programarDesdeProximos(
        int $mantenimientoId,
        string $fechaProgramada,
        ?int $usuarioSistemaId = null,
        ?string $ip = null
    ): array {
        if ($mantenimientoId <= 0) {
            return ['ok' => false, 'mensaje' => 'Identificador de mantenimiento invalido.'];
        }

        try {
            return DB::transaction(function () use ($mantenimientoId, $fechaProgramada, $usuarioSistemaId, $ip) {
                $registro = DB::table('mantenimientos as m')
                    ->join('usuarios as u', 'u.id', '=', 'm.usuario_id')
                    ->where('m.id', $mantenimientoId)
                    ->whereIn('m.estado', MantenimientoEstados::estadosProgramables())
                    ->where('u.estado_usuario', 1)
                    ->select(
                        'm.id',
                        'm.usuario_id',
                        'm.estado',
                        'm.fecha_programada',
                        'm.fecha_ejecucion',
                        'm.tecnico_responsable',
                        'm.observaciones'
                    )
                    ->first();

                if (!$registro) {
                    return ['ok' => false, 'mensaje' => 'No fue posible programar el mantenimiento.'];
                }

                $datosAntes = $this->capturarDatosEventoProgramacion($registro);

                DB::table('mantenimientos')
                    ->where('id', $mantenimientoId)
                    ->update([
                        'estado' => MantenimientoEstados::PROGRAMADO,
                        'fecha_programada' => $fechaProgramada,
                        'updated_at' => now(),
                    ]);

                $datosDespues = [
                    'estado' => MantenimientoEstados::PROGRAMADO,
                    'fecha_programada' => $fechaProgramada,
                    'fecha_ejecucion' => null,
                    'tecnico_responsable' => trim((string) ($registro->tecnico_responsable ?? '')) ?: null,
                    'observaciones' => is_string($registro->observaciones) && trim($registro->observaciones) !== ''
                        ? $registro->observaciones
                        : null,
                ];

                $this->eventoRepository->registrarEvento(
                    (int) $registro->id,
                    MantenimientoEstados::PROGRAMADO,
                    'Se programo el mantenimiento desde Proximos sin modificar las observaciones manuales.',
                    $datosAntes,
                    $datosDespues,
                    $usuarioSistemaId,
                    $ip
                );

                $this->resumenService->sincronizarResumenUsuario(
                    (int) $registro->usuario_id,
                    $this->semestreService->semestreDeFecha($fechaProgramada)
                );

                return ['ok' => true, 'mensaje' => 'Mantenimiento programado correctamente.'];
            });
        } catch (\Throwable) {
            return ['ok' => false, 'mensaje' => 'No fue posible programar el mantenimiento.'];
        }
    }

    private function calcularProximaSemestralDesde(string $fechaBase): string
    {
        $fecha = DateTimeImmutable::createFromFormat('Y-m-d', $fechaBase);
        if ($fecha === false || $fecha->format('Y-m-d') !== $fechaBase) {
            return $fechaBase;
        }

        return $this->periodicidadService->fechaProximaDesde(
            $fechaBase,
            $this->periodicidadService->cicloMantenimientoMeses()
        ) ?? $fechaBase;
    }

    /**
     * @return array{0:string,1:string}
     */
    private function rangoPeriodo(?array $periodo): array
    {
        $periodo = $periodo ?? $this->semestreService->actual();

        $inicio = (string) ($periodo['inicio'] ?? '');
        $fin = (string) ($periodo['fin'] ?? '');

        if ($inicio === '' || $fin === '') {
            $actual = $this->semestreService->actual();
            $inicio = (string) ($actual['inicio'] ?? '');
            $fin = (string) ($actual['fin'] ?? '');
        }

        return [$inicio, $fin];
    }

    private function asegurarPendienteSemestral(int $usuarioId, string $fechaProgramada, string $observaciones): ?int
    {
        $fechaValida = DateTimeImmutable::createFromFormat('Y-m-d', $fechaProgramada);
        if ($usuarioId <= 0 || $fechaValida === false || $fechaValida->format('Y-m-d') !== $fechaProgramada) {
            return null;
        }

        $periodo = $this->semestreService->semestreDeFecha($fechaProgramada);
        [$inicio, $fin] = $this->rangoPeriodo($periodo);

        $pendientes = DB::table('mantenimientos')
            ->select('id', 'observaciones', 'fecha_programada')
            ->where('usuario_id', $usuarioId)
            ->where('estado', MantenimientoEstados::PENDIENTE)
            ->whereBetween('fecha_programada', [$inicio, $fin])
            ->orderBy('fecha_programada')
            ->orderBy('id')
            ->get();

        if ($pendientes->isEmpty()) {
            return (int) DB::table('mantenimientos')->insertGetId([
                'usuario_id' => $usuarioId,
                'fecha_programada' => $fechaProgramada,
                'hora_programada' => null,
                'estado' => MantenimientoEstados::PENDIENTE,
                'fecha_ejecucion' => null,
                'observaciones' => $observaciones,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $principal = $pendientes->shift();
        if ($principal) {
            DB::table('mantenimientos')
                ->where('id', (int) $principal->id)
                ->update([
                    'fecha_programada' => $fechaProgramada,
                    'estado' => MantenimientoEstados::PENDIENTE,
                    'observaciones' => trim((string) ($principal->observaciones ?? '')) === ''
                        ? $observaciones
                        : (string) $principal->observaciones,
                    'updated_at' => now(),
                ]);
        }

        foreach ($pendientes as $duplicado) {
            DB::table('mantenimientos')
                ->where('id', (int) $duplicado->id)
                ->update([
                    'estado' => MantenimientoEstados::CANCELADO,
                    'observaciones' => DB::raw("CONCAT(COALESCE(observaciones, ''), ' | Cancelado automaticamente por sincronizacion semestral.')"),
                    'updated_at' => now(),
                ]);
        }

        return $principal ? (int) $principal->id : null;
    }

    public function materializarPendienteSemestral(int $usuarioId, string $fechaProgramada, string $observaciones): ?int
    {
        return DB::transaction(function () use ($usuarioId, $fechaProgramada, $observaciones): ?int {
            return $this->asegurarPendienteSemestral($usuarioId, $fechaProgramada, $observaciones);
        });
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

            $siguiente = $this->periodicidadService->fechaProximaDesde(
                $fechaEjecucion,
                $this->periodicidadService->cicloMantenimientoMeses()
            );
            if ($siguiente === null) {
                return ['ok' => false, 'mensaje' => 'No fue posible calcular la siguiente fecha de mantenimiento.'];
            }

            $this->asegurarPendienteSemestral(
                (int) $registro->usuario_id,
                $siguiente,
                'Generado automatico despues de marcar Hecho.'
            );

            $this->resumenService->sincronizarResumenUsuario(
                (int) $registro->usuario_id,
                $this->semestreService->semestreDeFecha($fechaEjecucion)
            );

            return [
                'ok' => true,
                'mensaje' => 'Mantenimiento marcado como hecho. Se registro la ejecucion y se genero el siguiente mantenimiento a ' . $this->periodicidadService->etiquetaMeses($this->periodicidadService->cicloMantenimientoMeses()) . '.',
            ];
        });
    }

    public function marcarProgramadoComoHechoDesdeProgramados(
        int $mantenimientoId,
        string $fechaEjecucion,
        string $tecnicoResponsable,
        string $observaciones,
        string $evidenciaPdf,
        ?int $usuarioSistemaId = null,
        ?string $ip = null
    ): array {
        try {
            return DB::transaction(function () use (
                $mantenimientoId,
                $fechaEjecucion,
                $tecnicoResponsable,
                $observaciones,
                $evidenciaPdf,
                $usuarioSistemaId,
                $ip
            ) {
                $registro = DB::table('mantenimientos as m')
                    ->join('usuarios as u', 'u.id', '=', 'm.usuario_id')
                    ->where('m.id', $mantenimientoId)
                    ->where('u.estado_usuario', 1)
                    ->select(
                        'm.id',
                        'm.usuario_id',
                        'm.estado',
                        'm.fecha_programada',
                        'm.fecha_ejecucion',
                        'm.tecnico_responsable',
                        'm.observaciones',
                        'm.evidencia_pdf'
                    )
                    ->first();

                $estadoRegistro = $registro ? MantenimientoEstados::normalizar((string) $registro->estado) : '';
                if (!$registro || !in_array($estadoRegistro, MantenimientoEstados::estadosVisiblesEnProgramados(), true)) {
                    return ['ok' => false, 'mensaje' => 'No fue posible marcar como hecho.'];
                }

                $observacionHumana = $this->normalizarObservacionHumana($observaciones);
                $datosAntes = $this->capturarDatosEventoHecho($registro);

                DB::table('mantenimientos')
                    ->where('id', $mantenimientoId)
                    ->update([
                        'estado' => MantenimientoEstados::REALIZADO,
                        'fecha_ejecucion' => $fechaEjecucion,
                        'tecnico_responsable' => trim($tecnicoResponsable),
                        'observaciones' => $observacionHumana,
                        'evidencia_pdf' => trim($evidenciaPdf),
                        'updated_at' => now(),
                    ]);

                $datosDespues = [
                    'estado' => MantenimientoEstados::REALIZADO,
                    'fecha_programada' => trim((string) ($registro->fecha_programada ?? '')) ?: null,
                    'fecha_ejecucion' => $fechaEjecucion,
                    'tecnico_responsable' => trim($tecnicoResponsable) !== '' ? trim($tecnicoResponsable) : null,
                    'observaciones' => $observacionHumana,
                    'evidencia_pdf' => trim($evidenciaPdf) !== '' ? trim($evidenciaPdf) : null,
                ];

                $this->eventoRepository->registrarEvento(
                    (int) $registro->id,
                    'realizado',
                    'Se marco el mantenimiento como ejecutado desde Programados sin modificar las observaciones manuales.',
                    $datosAntes,
                    $datosDespues,
                    $usuarioSistemaId,
                    $ip
                );

                $siguiente = $this->periodicidadService->fechaProximaDesde(
                    $fechaEjecucion,
                    $this->periodicidadService->cicloMantenimientoMeses()
                );
                if ($siguiente === null) {
                    return ['ok' => false, 'mensaje' => 'No fue posible calcular la siguiente fecha de mantenimiento.'];
                }

                $this->asegurarPendienteSemestral(
                    (int) $registro->usuario_id,
                    $siguiente,
                    'Generado automatico despues de marcar Hecho.'
                );

                $this->resumenService->sincronizarResumenUsuario(
                    (int) $registro->usuario_id,
                    $this->semestreService->semestreDeFecha($fechaEjecucion)
                );

                return [
                    'ok' => true,
                    'mensaje' => 'Mantenimiento marcado como hecho. Se registro la ejecucion y se genero el siguiente mantenimiento a ' . $this->periodicidadService->etiquetaMeses($this->periodicidadService->cicloMantenimientoMeses()) . '.',
                ];
            });
        } catch (\Throwable) {
            return ['ok' => false, 'mensaje' => 'No fue posible marcar como hecho.'];
        }
    }

    public function revertirAccionesPorCodigoActivo(
        int $codigoActivo,
        int $cantidad,
        ?int $usuarioSistemaId = null,
        ?string $ip = null
    ): array
    {
        $cantidad = max(1, min(10, $cantidad));

        try {
            return DB::transaction(function () use ($codigoActivo, $cantidad, $usuarioSistemaId, $ip) {
                $usuarioId = $this->obtenerUsuarioIdPorCodigoActivo($codigoActivo);
                if ($usuarioId === null) {
                    return ['ok' => false, 'mensaje' => 'No existe un usuario con ese codigo activo.'];
                }

                $reversionesAplicadas = 0;
                for ($i = 0; $i < $cantidad; $i++) {
                    $accionRevertida = $this->revertirUnaAccionInterna($usuarioId, $usuarioSistemaId, $ip, $codigoActivo);
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
        } catch (\Throwable) {
            return ['ok' => false, 'mensaje' => 'No fue posible revertir la accion.'];
        }
    }

    private function obtenerUsuarioIdPorCodigoActivo(int $codigoActivo): ?int
    {
        $fila = DB::table('usuarios')
            ->select('id')
            ->where('codigo_activo', $codigoActivo)
            ->first();

        return $fila ? (int) $fila->id : null;
    }

    private function revertirUnaAccionInterna(
        int $usuarioId,
        ?int $usuarioSistemaId = null,
        ?string $ip = null,
        ?int $codigoActivo = null
    ): ?array
    {
        $ultimoProgramado = $this->buscarUltimoProgramadoReversible($usuarioId);
        $ultimoRealizado = $this->buscarUltimoRealizadoReversible($usuarioId);

        $tsProgramado = is_array($ultimoProgramado) ? strtotime((string) $ultimoProgramado['updated_at']) : false;
        $tsRealizado = is_array($ultimoRealizado) ? strtotime((string) $ultimoRealizado['updated_at']) : false;

        if ($tsProgramado === false && $tsRealizado === false) {
            return null;
        }

        if ($tsRealizado !== false && ($tsProgramado === false || $tsRealizado >= $tsProgramado)) {
            $fechaReferencia = trim((string) ($ultimoRealizado['fecha_ejecucion'] ?? $ultimoRealizado['fecha_programada'] ?? ''));
            $fechaSiguiente = $fechaReferencia !== ''
                ? $this->periodicidadService->fechaProximaDesde($fechaReferencia, $this->periodicidadService->cicloMantenimientoMeses())
                : null;

            if ($fechaSiguiente === null) {
                return null;
            }

            $pendienteAuto = $this->buscarPendienteAutomatico($usuarioId, $fechaSiguiente);
            if (!is_array($pendienteAuto)) {
                return null;
            }

            $datosAntes = $this->capturarDatosEventoHecho($ultimoRealizado);
            $datosAntes['pendiente_automatico'] = $this->capturarDatosEventoPendienteAutomatico($pendienteAuto);
            $datosAntes['reversion'] = $this->datosContextoReversion(
                'HECHO', $usuarioId, $codigoActivo,
                $this->origenReversibilidad((int) $ultimoRealizado['id'], ['realizado'])
            );

            DB::table('mantenimientos')
                ->where('id', (int) $pendienteAuto['id'])
                ->delete();

            $actualizado = DB::table('mantenimientos')
                ->where('id', (int) $ultimoRealizado['id'])
                ->update([
                    'estado' => MantenimientoEstados::PROGRAMADO,
                    'fecha_ejecucion' => null,
                    'updated_at' => now(),
                ]);

            if ($actualizado <= 0) {
                return null;
            }

            $registroActualizado = DB::table('mantenimientos')
                ->select('id', 'estado', 'fecha_programada', 'fecha_ejecucion', 'tecnico_responsable', 'observaciones', 'evidencia_pdf')
                ->where('id', (int) $ultimoRealizado['id'])
                ->first();

            $datosDespues = $registroActualizado ? $this->capturarDatosEventoHecho($registroActualizado) : [];
            $datosDespues['pendiente_automatico'] = $this->capturarDatosEventoPendienteAutomatico($pendienteAuto);
            $datosDespues['reversion'] = $this->datosContextoReversion(
                'HECHO', $usuarioId, $codigoActivo,
                $this->origenReversibilidad((int) $ultimoRealizado['id'], ['realizado']), 'programado'
            );

            $this->eventoRepository->registrarEvento(
                (int) $ultimoRealizado['id'],
                'revertido',
                'Se revirtio la ejecucion del mantenimiento y se restauro el estado Programado sin modificar las observaciones manuales.',
                $datosAntes,
                $datosDespues,
                $usuarioSistemaId,
                $ip
            );

            return [
                'tipo' => 'HECHO',
                'periodo' => $fechaReferencia !== '' ? $this->semestreService->semestreDeFecha($fechaReferencia) : $this->semestreService->actual(),
            ];
        }

        $datosAntes = $this->capturarDatosEventoHecho($ultimoProgramado);
        $datosAntes['reversion'] = $this->datosContextoReversion(
            'PROGRAMAR', $usuarioId, $codigoActivo,
            $this->origenReversibilidad((int) $ultimoProgramado['id'], ['programado', 'revertido'])
        );

        $actualizado = DB::table('mantenimientos')
            ->where('id', (int) $ultimoProgramado['id'])
            ->update([
                'estado' => MantenimientoEstados::PENDIENTE,
                'updated_at' => now(),
            ]);

        if ($actualizado <= 0) {
            return null;
        }

        $registroActualizado = DB::table('mantenimientos')
            ->select('id', 'estado', 'fecha_programada', 'fecha_ejecucion', 'tecnico_responsable', 'observaciones', 'evidencia_pdf')
            ->where('id', (int) $ultimoProgramado['id'])
            ->first();

        $datosDespues = $registroActualizado ? $this->capturarDatosEventoHecho($registroActualizado) : [];
        $datosDespues['reversion'] = $this->datosContextoReversion(
            'PROGRAMAR', $usuarioId, $codigoActivo,
            $this->origenReversibilidad((int) $ultimoProgramado['id'], ['programado', 'revertido']), 'pendiente'
        );

        $this->eventoRepository->registrarEvento(
            (int) $ultimoProgramado['id'],
            'revertido',
            'Se revirtio la programacion del mantenimiento y se restauro el estado Pendiente sin modificar las observaciones manuales.',
            $datosAntes,
            $datosDespues,
            $usuarioSistemaId,
            $ip
        );

        $fechaReferencia = trim((string) ($ultimoProgramado['fecha_programada'] ?? ''));

        return [
            'tipo' => 'PROGRAMAR',
            'periodo' => $fechaReferencia !== '' ? $this->semestreService->semestreDeFecha($fechaReferencia) : $this->semestreService->actual(),
        ];
    }

    private function datosContextoReversion(
        string $tipoReversion,
        int $usuarioId,
        ?int $codigoActivo,
        string $origenReversibilidad,
        ?string $estadoNuevo = null
    ): array {
        return array_filter([
            'tipo_reversion' => $tipoReversion,
            'codigo_activo' => $codigoActivo,
            'usuario_id' => $usuarioId,
            'origen_reversibilidad' => $origenReversibilidad,
            'estado_nuevo' => $estadoNuevo,
        ], static fn ($valor) => $valor !== null);
    }

    private function origenReversibilidad(int $mantenimientoId, array $tiposEvento): string
    {
        return $this->reversiblePorEvento($mantenimientoId, $tiposEvento)
            ? 'evento'
            : 'observaciones_legacy';
    }

    private function buscarUltimoProgramadoReversible(int $usuarioId): ?array
    {
        $filas = DB::table('mantenimientos')
            ->select('id', 'updated_at', 'fecha_programada', 'observaciones', 'estado', 'fecha_ejecucion', 'tecnico_responsable', 'evidencia_pdf')
            ->where('usuario_id', $usuarioId)
            ->whereIn('estado', MantenimientoEstados::estadosReversiblesComoProgramado())
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();

        foreach ($filas as $fila) {
            $registro = (array) $fila;
            if ($this->esProgramadoReversible($registro)) {
                return $registro;
            }
        }

        return null;
    }

    private function buscarUltimoRealizadoReversible(int $usuarioId): ?array
    {
        $filas = DB::table('mantenimientos')
            ->select('id', 'updated_at', 'fecha_programada', 'fecha_ejecucion', 'observaciones', 'estado', 'tecnico_responsable', 'evidencia_pdf')
            ->where('usuario_id', $usuarioId)
            ->whereIn('estado', MantenimientoEstados::estadosReversiblesComoHecho())
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();

        foreach ($filas as $fila) {
            $registro = (array) $fila;
            if ($this->esRealizadoReversible($registro)) {
                return $registro;
            }
        }

        return null;
    }

    private function buscarPendienteAutomatico(int $usuarioId, ?string $fechaProgramada = null): ?array
    {
        $query = DB::table('mantenimientos')
            ->select('id', 'usuario_id', 'fecha_programada', 'estado', 'fecha_ejecucion', 'tecnico_responsable', 'observaciones', 'evidencia_pdf', 'created_at', 'updated_at')
            ->where('usuario_id', $usuarioId)
            ->whereIn('estado', MantenimientoEstados::estadosVisiblesEnProximos())
            ->whereNull('fecha_ejecucion')
            ->where(function ($subquery): void {
                $subquery
                    ->whereNull('tecnico_responsable')
                    ->orWhereRaw("TRIM(COALESCE(tecnico_responsable, '')) = ''");
            })
            ->where(function ($subquery): void {
                $subquery
                    ->whereNull('evidencia_pdf')
                    ->orWhereRaw("TRIM(COALESCE(evidencia_pdf, '')) = ''");
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        $fechaProgramada = trim((string) $fechaProgramada);
        if ($fechaProgramada !== '') {
            $query->whereDate('fecha_programada', $fechaProgramada);
        }

        $fila = $query->first();

        return $fila ? (array) $fila : null;
    }

    private function construirObservacionRealizado(string $observaciones): ?string
    {
        return $this->textoNormalizer->anadirMarcaSistema($observaciones, 'Hecho desde modulo Programados.');
    }

    private function normalizarObservacionHumana(?string $observaciones): ?string
    {
        if ($observaciones === null) {
            return null;
        }

        return trim($observaciones) !== '' ? $observaciones : null;
    }

    /**
     * @param array<string, mixed> $registro
     */
    private function esProgramadoReversible(array $registro): bool
    {
        if ($this->reversiblePorEvento((int) ($registro['id'] ?? 0), ['programado', 'revertido'])) {
            return true;
        }

        $observaciones = (string) ($registro['observaciones'] ?? '');
        $marcasLegacy = [
            'Programado desde modulo Proximos.',
            'Hecho desde modulo Programados.',
            'Revertido desde modulo Reversiones (HECHO).',
        ];

        foreach ($marcasLegacy as $marca) {
            if ($observaciones !== '' && str_contains($observaciones, $marca)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $registro
     */
    private function esRealizadoReversible(array $registro): bool
    {
        if ($this->reversiblePorEvento((int) ($registro['id'] ?? 0), ['realizado'])) {
            return true;
        }

        $observaciones = (string) ($registro['observaciones'] ?? '');

        return $observaciones !== '' && str_contains($observaciones, 'Hecho desde modulo Programados.');
    }

    /**
     * @param array<int, string> $tiposEventoValidos
     */
    private function reversiblePorEvento(int $mantenimientoId, array $tiposEventoValidos): bool
    {
        $evento = $this->eventoRepository->obtenerUltimoEventoPorMantenimiento($mantenimientoId);
        if (!is_array($evento)) {
            return false;
        }

        $tipoEvento = trim((string) ($evento['tipo_evento'] ?? ''));
        return $tipoEvento !== '' && in_array($tipoEvento, $tiposEventoValidos, true);
    }

    /**
     * @param  object|array<string, mixed>  $registro
     * @return array<string, mixed>
     */
    private function capturarDatosEventoProgramacion(object|array $registro): array
    {
        $estado = '';
        $fechaProgramada = null;
        $fechaEjecucion = null;
        $tecnicoResponsable = null;
        $observaciones = null;

        if (is_object($registro)) {
            $estado = (string) ($registro->estado ?? '');
            $fechaProgramada = $registro->fecha_programada ?? null;
            $fechaEjecucion = $registro->fecha_ejecucion ?? null;
            $tecnicoResponsable = $registro->tecnico_responsable ?? null;
            $observaciones = $registro->observaciones ?? null;
        } else {
            $estado = (string) ($registro['estado'] ?? '');
            $fechaProgramada = $registro['fecha_programada'] ?? null;
            $fechaEjecucion = $registro['fecha_ejecucion'] ?? null;
            $tecnicoResponsable = $registro['tecnico_responsable'] ?? null;
            $observaciones = $registro['observaciones'] ?? null;
        }

        $observaciones = is_string($observaciones) && trim($observaciones) !== '' ? $observaciones : null;

        return [
            'estado' => $estado,
            'fecha_programada' => $this->normalizarValorEvento($fechaProgramada),
            'fecha_ejecucion' => $this->normalizarValorEvento($fechaEjecucion),
            'tecnico_responsable' => $this->normalizarValorEvento($tecnicoResponsable),
            'observaciones' => $observaciones,
            'evidencia_pdf' => is_object($registro)
                ? ($registro->evidencia_pdf ?? null)
                : ($registro['evidencia_pdf'] ?? null),
        ];
    }

    /**
     * @param  object|array<string, mixed>  $registro
     * @return array<string, mixed>
     */
    private function capturarDatosEventoHecho(object|array $registro): array
    {
        $estado = '';
        $fechaProgramada = null;
        $fechaEjecucion = null;
        $tecnicoResponsable = null;
        $observaciones = null;
        $evidenciaPdf = null;

        if (is_object($registro)) {
            $estado = (string) ($registro->estado ?? '');
            $fechaProgramada = $registro->fecha_programada ?? null;
            $fechaEjecucion = $registro->fecha_ejecucion ?? null;
            $tecnicoResponsable = $registro->tecnico_responsable ?? null;
            $observaciones = $registro->observaciones ?? null;
            $evidenciaPdf = $registro->evidencia_pdf ?? null;
        } else {
            $estado = (string) ($registro['estado'] ?? '');
            $fechaProgramada = $registro['fecha_programada'] ?? null;
            $fechaEjecucion = $registro['fecha_ejecucion'] ?? null;
            $tecnicoResponsable = $registro['tecnico_responsable'] ?? null;
            $observaciones = $registro['observaciones'] ?? null;
            $evidenciaPdf = $registro['evidencia_pdf'] ?? null;
        }

        $observaciones = is_string($observaciones) && trim($observaciones) !== '' ? $observaciones : null;
        $tecnicoResponsable = is_string($tecnicoResponsable) && trim($tecnicoResponsable) !== '' ? $tecnicoResponsable : null;
        $evidenciaPdf = is_string($evidenciaPdf) && trim($evidenciaPdf) !== '' ? $evidenciaPdf : null;

        return [
            'estado' => $estado,
            'fecha_programada' => $fechaProgramada,
            'fecha_ejecucion' => $fechaEjecucion,
            'tecnico_responsable' => $tecnicoResponsable,
            'observaciones' => $observaciones,
            'evidencia_pdf' => $evidenciaPdf,
        ];
    }

    /**
     * @param  object|array<string, mixed>  $registro
     * @return array<string, mixed>
     */
    private function capturarDatosEventoPendienteAutomatico(object|array $registro): array
    {
        $id = 0;
        $estado = '';
        $fechaProgramada = null;
        $fechaEjecucion = null;
        $tecnicoResponsable = null;
        $observaciones = null;
        $evidenciaPdf = null;

        if (is_object($registro)) {
            $id = (int) ($registro->id ?? 0);
            $estado = (string) ($registro->estado ?? '');
            $fechaProgramada = $registro->fecha_programada ?? null;
            $fechaEjecucion = $registro->fecha_ejecucion ?? null;
            $tecnicoResponsable = $registro->tecnico_responsable ?? null;
            $observaciones = $registro->observaciones ?? null;
            $evidenciaPdf = $registro->evidencia_pdf ?? null;
        } else {
            $id = (int) ($registro['id'] ?? 0);
            $estado = (string) ($registro['estado'] ?? '');
            $fechaProgramada = $registro['fecha_programada'] ?? null;
            $fechaEjecucion = $registro['fecha_ejecucion'] ?? null;
            $tecnicoResponsable = $registro['tecnico_responsable'] ?? null;
            $observaciones = $registro['observaciones'] ?? null;
            $evidenciaPdf = $registro['evidencia_pdf'] ?? null;
        }

        $observaciones = is_string($observaciones) && trim($observaciones) !== '' ? $observaciones : null;
        $tecnicoResponsable = is_string($tecnicoResponsable) && trim($tecnicoResponsable) !== '' ? $tecnicoResponsable : null;
        $evidenciaPdf = is_string($evidenciaPdf) && trim($evidenciaPdf) !== '' ? $evidenciaPdf : null;

        return [
            'id' => $id,
            'estado' => $estado,
            'fecha_programada' => $fechaProgramada,
            'fecha_ejecucion' => $fechaEjecucion,
            'tecnico_responsable' => $tecnicoResponsable,
            'observaciones' => $observaciones,
            'evidencia_pdf' => $evidenciaPdf,
        ];
    }

    private function normalizarValorEvento(mixed $valor): ?string
    {
        $valor = trim((string) $valor);

        return $valor === '' ? null : $valor;
    }

}


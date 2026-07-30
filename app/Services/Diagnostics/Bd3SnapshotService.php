<?php

namespace App\Services\Diagnostics;

use App\Services\Legacy\MantenimientoEstados;
use App\Services\Legacy\PeriodicidadMantenimientoService;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Bd3SnapshotService
{
    private const ISOLATED_DATABASE = 'mantenimientos_normalizacion_pruebas';

    public function __construct(
        private readonly PeriodicidadMantenimientoService $periodicidadService,
    ) {
    }

    public function generar(): array
    {
        $this->assertBaseAislada();

        $database = (string) DB::connection()->getDatabaseName();
        $versionBd = $this->obtenerVersionBaseDatos();
        $esquema = $this->construirInventarioEsquema($database);
        $fotografiaFuncional = $this->construirFotografiaFuncional();
        $huellas = $this->construirHuellasLogicas($esquema);
        $codificacion = $this->construirDiagnosticoCodificacion($esquema);

        $snapshot = [
            'meta' => [
                'fecha_hora' => now()->toIso8601String(),
                'app_env' => app()->environment(),
                'database' => $database,
                'base_esperada' => self::ISOLATED_DATABASE,
                'config_cache' => app()->configurationIsCached(),
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'db_version' => $versionBd,
            ],
            'esquema' => $esquema,
            'fotografia_funcional' => $fotografiaFuncional,
            'huellas_logicas' => $huellas,
            'codificacion' => $codificacion,
        ];

        $snapshot['hallazgos_conocidos'] = $this->construirHallazgosConocidos($snapshot);

        return $snapshot;
    }

    public function assertBaseAislada(): void
    {
        $database = (string) DB::connection()->getDatabaseName();

        if ($database !== self::ISOLATED_DATABASE) {
            throw new RuntimeException(
                'La base activa debe ser exactamente "' . self::ISOLATED_DATABASE . '" y actualmente es "' . $database . '".'
            );
        }
    }

    private function obtenerVersionBaseDatos(): string
    {
        $fila = DB::selectOne('SELECT VERSION() AS version');

        return (string) ($fila->version ?? '');
    }

    /**
     * @return array{
     *   total_tablas:int,
     *   tablas: array<int, array<string, mixed>>
     * }
     */
    private function construirInventarioEsquema(string $database): array
    {
        $schema = DB::table('information_schema.schemata')
            ->select('DEFAULT_CHARACTER_SET_NAME as charset', 'DEFAULT_COLLATION_NAME as collation')
            ->where('SCHEMA_NAME', $database)
            ->first();

        $tablas = DB::table('information_schema.tables')
            ->select('table_name', 'engine', 'table_collation')
            ->where('table_schema', $database)
            ->orderBy('table_name')
            ->get()
            ->map(static fn ($row) => (array) $row)
            ->all();

        $columnas = DB::table('information_schema.columns')
            ->select(
                'table_name',
                'ordinal_position',
                'column_name',
                'column_type',
                'data_type',
                'character_maximum_length',
                'numeric_precision',
                'numeric_scale',
                'is_nullable',
                'column_default',
                'extra'
            )
            ->where('table_schema', $database)
            ->orderBy('table_name')
            ->orderBy('ordinal_position')
            ->get()
            ->map(static fn ($row) => (array) $row)
            ->groupBy('table_name');

        $indices = DB::table('information_schema.statistics')
            ->select(
                'table_name',
                'index_name',
                'non_unique',
                'index_type',
                'seq_in_index',
                'column_name'
            )
            ->where('table_schema', $database)
            ->orderBy('table_name')
            ->orderBy('index_name')
            ->orderBy('seq_in_index')
            ->get()
            ->map(static fn ($row) => (array) $row)
            ->groupBy('table_name');

        $fks = DB::table('information_schema.key_column_usage as kcu')
            ->join('information_schema.referential_constraints as rc', function ($join): void {
                $join->on('kcu.constraint_schema', '=', 'rc.constraint_schema')
                    ->on('kcu.constraint_name', '=', 'rc.constraint_name');
            })
            ->select(
                'kcu.table_name',
                'kcu.constraint_name',
                'kcu.column_name',
                'kcu.referenced_table_name',
                'kcu.referenced_column_name',
                'rc.update_rule',
                'rc.delete_rule',
                'kcu.ordinal_position'
            )
            ->where('kcu.constraint_schema', $database)
            ->whereNotNull('kcu.referenced_table_name')
            ->orderBy('kcu.table_name')
            ->orderBy('kcu.constraint_name')
            ->orderBy('kcu.ordinal_position')
            ->get()
            ->map(static fn ($row) => (array) $row)
            ->groupBy('table_name');

        $resultado = [];
        foreach ($tablas as $tablaMeta) {
            $tabla = (string) ($tablaMeta['table_name'] ?? '');
            if ($tabla === '') {
                continue;
            }

            $columnasTabla = collect($columnas->get($tabla, []))
                ->map(function (array $columna): array {
                    return [
                        'nombre' => (string) ($columna['column_name'] ?? ''),
                        'tipo' => (string) ($columna['data_type'] ?? ''),
                        'tipo_completo' => (string) ($columna['column_type'] ?? ''),
                        'longitud' => $this->formatearLongitudColumna($columna),
                        'nulabilidad' => (string) ($columna['is_nullable'] ?? ''),
                        'valor_predeterminado' => $this->normalizarValorPredeterminado($columna['column_default'] ?? null),
                        'auto_incremento' => str_contains((string) ($columna['extra'] ?? ''), 'auto_increment'),
                    ];
                })
                ->values()
                ->all();

            $indicesTabla = collect($indices->get($tabla, []))
                ->groupBy('index_name')
                ->map(function ($grupo): array {
                    $primer = $grupo->first();

                    return [
                        'nombre' => (string) ($primer['index_name'] ?? ''),
                        'unico' => ((int) ($primer['non_unique'] ?? 1)) === 0,
                        'tipo' => (string) ($primer['index_type'] ?? ''),
                        'columnas' => $grupo
                            ->sortBy('seq_in_index')
                            ->pluck('column_name')
                            ->map(static fn ($valor) => (string) $valor)
                            ->values()
                            ->all(),
                    ];
                })
                ->sortBy('nombre')
                ->values()
                ->all();

            $clavePrimaria = collect($indices->get($tabla, []))
                ->where('index_name', 'PRIMARY')
                ->sortBy('seq_in_index')
                ->pluck('column_name')
                ->map(static fn ($valor) => (string) $valor)
                ->values()
                ->all();

            $clavesUnicas = collect($indices->get($tabla, []))
                ->where('non_unique', 0)
                ->where('index_name', '!=', 'PRIMARY')
                ->groupBy('index_name')
                ->map(static function ($grupo): array {
                    $primer = $grupo->first();

                    return [
                        'nombre' => (string) ($primer['index_name'] ?? ''),
                        'columnas' => $grupo
                            ->sortBy('seq_in_index')
                            ->pluck('column_name')
                            ->map(static fn ($valor) => (string) $valor)
                            ->values()
                            ->all(),
                    ];
                })
                ->sortBy('nombre')
                ->values()
                ->all();

            $clavesForaneas = collect($fks->get($tabla, []))
                ->groupBy('constraint_name')
                ->map(function ($grupo): array {
                    $primer = $grupo->first();

                    return [
                        'nombre' => (string) ($primer['constraint_name'] ?? ''),
                        'columnas' => $grupo
                            ->sortBy('ordinal_position')
                            ->pluck('column_name')
                            ->map(static fn ($valor) => (string) $valor)
                            ->values()
                            ->all(),
                        'referencia' => [
                            'tabla' => (string) ($primer['referenced_table_name'] ?? ''),
                            'columnas' => $grupo
                                ->sortBy('ordinal_position')
                                ->pluck('referenced_column_name')
                                ->map(static fn ($valor) => (string) $valor)
                                ->values()
                                ->all(),
                        ],
                        'on_delete' => (string) ($primer['delete_rule'] ?? ''),
                        'on_update' => (string) ($primer['update_rule'] ?? ''),
                    ];
                })
                ->sortBy('nombre')
                ->values()
                ->all();

            $resultado[] = [
                'nombre' => $tabla,
                'motor' => (string) ($tablaMeta['engine'] ?? ''),
                'charset' => $this->extraerCharsetTabla((string) ($tablaMeta['table_collation'] ?? ''), (string) ($schema->charset ?? '')),
                'collation' => (string) ($tablaMeta['table_collation'] ?? ''),
                'conteo_registros' => (int) DB::table($tabla)->count(),
                'columnas' => $columnasTabla,
                'clave_primaria' => $clavePrimaria,
                'claves_unicas' => $clavesUnicas,
                'indices' => $indicesTabla,
                'claves_foraneas' => $clavesForaneas,
            ];
        }

        return [
            'total_tablas' => count($resultado),
            'charset_base' => (string) ($schema->charset ?? ''),
            'collation_base' => (string) ($schema->collation ?? ''),
            'tablas' => $resultado,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function construirFotografiaFuncional(): array
    {
        return [
            'usuarios' => $this->fotografiaUsuarios(),
            'usuarios_sistema' => $this->fotografiaUsuariosSistema(),
            'mantenimientos' => $this->fotografiaMantenimientos(),
            'configuracion' => $this->fotografiaConfiguracion(),
            'integridad' => $this->fotografiaIntegridad(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fotografiaUsuarios(): array
    {
        $total = (int) DB::table('usuarios')->count();
        $activos = (int) DB::table('usuarios')->where('estado_usuario', 1)->count();
        $inactivos = (int) DB::table('usuarios')->where('estado_usuario', 0)->count();
        $sinFechaBase = (int) DB::table('usuarios')->whereNull('fecha_ultimo_mantenimiento_base')->count();
        $sinHistorial = (int) DB::table('usuarios as u')
            ->whereNotExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('mantenimientos as m')
                    ->whereColumn('m.usuario_id', 'u.id');
            })
            ->count();
        $serialNulo = (int) DB::table('usuarios')->whereNull('serial_equipo')->count();
        $serialVacio = (int) DB::table('usuarios')
            ->whereRaw("TRIM(COALESCE(serial_equipo, '')) = ''")
            ->count();
        $invisibles = (int) DB::table('usuarios')
            ->where(function ($query): void {
                $query
                    ->whereRaw("nombre REGEXP '[[:cntrl:]]'")
                    ->orWhereRaw("serial_equipo REGEXP '[[:cntrl:]]'")
                    ->orWhereRaw("nombre REGEXP '^[[:space:]]'")
                    ->orWhereRaw("nombre REGEXP '[[:space:]]$'")
                    ->orWhereRaw("serial_equipo REGEXP '^[[:space:]]'")
                    ->orWhereRaw("serial_equipo REGEXP '[[:space:]]$'");
            })
            ->count();
        $duplicadosSerial = (int) DB::table('usuarios')
            ->selectRaw('LOWER(TRIM(serial_equipo)) AS serial_limpio, COUNT(*) AS total')
            ->whereRaw("TRIM(COALESCE(serial_equipo, '')) <> ''")
            ->groupByRaw('LOWER(TRIM(serial_equipo))')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();

        $tiposEquipo = DB::table('usuarios')
            ->select('tipo_equipo')
            ->distinct()
            ->orderBy('tipo_equipo')
            ->pluck('tipo_equipo')
            ->map(static fn ($valor) => (string) $valor)
            ->values()
            ->all();

        $valoresEstado = DB::table('usuarios')
            ->select('estado_usuario')
            ->distinct()
            ->orderBy('estado_usuario')
            ->pluck('estado_usuario')
            ->map(static fn ($valor) => (int) $valor)
            ->values()
            ->all();

        return [
            'total' => $total,
            'activos' => $activos,
            'inactivos' => $inactivos,
            'sin_historial_mantenimiento' => $sinHistorial,
            'sin_fecha_base' => $sinFechaBase,
            'serial_nulo' => $serialNulo,
            'serial_vacio' => $serialVacio,
            'con_caracteres_invisibles' => $invisibles,
            'duplicados_logicos_serial' => $duplicadosSerial,
            'tipos_equipo_distintos' => $tiposEquipo,
            'valores_distintos_estado_usuario' => $valoresEstado,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fotografiaUsuariosSistema(): array
    {
        $filas = DB::table('usuarios_sistema')
            ->select('estado', 'rol', 'password_hash')
            ->orderBy('id')
            ->get();

        $total = $filas->count();
        $activos = $filas->where('estado', 1)->count();
        $inactivos = $filas->where('estado', 0)->count();

        $roles = $filas
            ->pluck('rol')
            ->map(static fn ($valor) => trim((string) $valor))
            ->filter(static fn ($valor) => $valor !== '')
            ->unique()
            ->sort()
            ->values()
            ->all();

        $rolesConEspacios = $filas->filter(static function ($fila): bool {
            $rol = (string) ($fila->rol ?? '');

            return $rol !== trim($rol) || preg_match('/[[:cntrl:]]/u', $rol) === 1;
        })->count();

        $clasificacionCredenciales = [
            'hash_seguro_reconocido' => 0,
            'valor_probablemente_inseguro' => 0,
            'vacio_o_nulo' => 0,
            'formato_desconocido' => 0,
        ];

        foreach ($filas as $fila) {
            $passwordHash = trim((string) ($fila->password_hash ?? ''));

            if ($passwordHash === '') {
                $clasificacionCredenciales['vacio_o_nulo']++;
                continue;
            }

            if (password_get_info($passwordHash)['algo'] !== 0) {
                $clasificacionCredenciales['hash_seguro_reconocido']++;
                continue;
            }

            if (!str_starts_with($passwordHash, '$') || mb_strlen($passwordHash) < 20) {
                $clasificacionCredenciales['valor_probablemente_inseguro']++;
                continue;
            }

            $clasificacionCredenciales['formato_desconocido']++;
        }

        return [
            'total' => $total,
            'activos' => $activos,
            'inactivos' => $inactivos,
            'roles_distintos' => $roles,
            'roles_con_espacios_o_invisibles' => $rolesConEspacios,
            'credenciales' => $clasificacionCredenciales,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fotografiaMantenimientos(): array
    {
        $total = (int) DB::table('mantenimientos')->count();

        $porEstado = DB::table('mantenimientos')
            ->select('estado', DB::raw('COUNT(*) AS total'))
            ->groupBy('estado')
            ->orderBy('estado')
            ->get()
            ->mapWithKeys(static fn ($fila) => [(string) $fila->estado => (int) $fila->total])
            ->all();

        $porPeriodoProgramado = DB::table('mantenimientos')
            ->selectRaw('YEAR(fecha_programada) AS anio, IF(MONTH(fecha_programada) <= 6, 1, 2) AS semestre, COUNT(*) AS total')
            ->whereNotNull('fecha_programada')
            ->groupByRaw('YEAR(fecha_programada), IF(MONTH(fecha_programada) <= 6, 1, 2)')
            ->orderBy('anio')
            ->orderBy('semestre')
            ->get()
            ->map(static fn ($fila) => [
                'anio' => (int) $fila->anio,
                'semestre' => (int) $fila->semestre,
                'total' => (int) $fila->total,
            ])
            ->all();

        $porPeriodoEjecucion = DB::table('mantenimientos')
            ->selectRaw('YEAR(fecha_ejecucion) AS anio, IF(MONTH(fecha_ejecucion) <= 6, 1, 2) AS semestre, COUNT(*) AS total')
            ->whereNotNull('fecha_ejecucion')
            ->groupByRaw('YEAR(fecha_ejecucion), IF(MONTH(fecha_ejecucion) <= 6, 1, 2)')
            ->orderBy('anio')
            ->orderBy('semestre')
            ->get()
            ->map(static fn ($fila) => [
                'anio' => (int) $fila->anio,
                'semestre' => (int) $fila->semestre,
                'total' => (int) $fila->total,
            ])
            ->all();

        $hoy = now()->toDateString();

        $pendientesVencidos = (int) DB::table('mantenimientos')
            ->where('estado', MantenimientoEstados::PENDIENTE)
            ->whereDate('fecha_programada', '<', $hoy)
            ->count();

        $programadosVencidos = (int) DB::table('mantenimientos')
            ->whereIn('estado', MantenimientoEstados::estadosVisiblesEnProgramados())
            ->whereDate('fecha_programada', '<', $hoy)
            ->count();

        $realizados = (int) DB::table('mantenimientos')
            ->whereIn('estado', MantenimientoEstados::estadosVisiblesEnHechos())
            ->count();

        $cancelados = (int) DB::table('mantenimientos')
            ->where('estado', MantenimientoEstados::CANCELADO)
            ->count();

        $sinUsuarioValido = (int) DB::table('mantenimientos as m')
            ->leftJoin('usuarios as u', 'u.id', '=', 'm.usuario_id')
            ->whereNull('u.id')
            ->count();

        $duplicadosExactos = DB::table('mantenimientos')
            ->selectRaw('usuario_id, fecha_programada, hora_programada, estado, fecha_ejecucion, tecnico_responsable, observaciones, evidencia_pdf, COUNT(*) AS total')
            ->groupBy('usuario_id', 'fecha_programada', 'hora_programada', 'estado', 'fecha_ejecucion', 'tecnico_responsable', 'observaciones', 'evidencia_pdf')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();

        $duplicadosLogicos = DB::table('mantenimientos')
            ->selectRaw(
                'usuario_id, YEAR(fecha_programada) AS anio, IF(MONTH(fecha_programada) <= 6, 1, 2) AS semestre, CASE
                    WHEN estado IN (\'programado\', \'reprogramado\') THEN \'programado\'
                    WHEN estado IN (\'realizado\', \'hecho\') THEN \'realizado\'
                    ELSE estado
                END AS tipo_equivalente, COUNT(*) AS total'
            )
            ->whereNotNull('fecha_programada')
            ->groupByRaw(
                'usuario_id, YEAR(fecha_programada), IF(MONTH(fecha_programada) <= 6, 1, 2), CASE
                    WHEN estado IN (\'programado\', \'reprogramado\') THEN \'programado\'
                    WHEN estado IN (\'realizado\', \'hecho\') THEN \'realizado\'
                    ELSE estado
                END'
            )
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();

        $pendientesConEjecucion = (int) DB::table('mantenimientos')
            ->where('estado', MantenimientoEstados::PENDIENTE)
            ->whereNotNull('fecha_ejecucion')
            ->count();

        $realizadosSinEjecucion = (int) DB::table('mantenimientos')
            ->whereIn('estado', MantenimientoEstados::estadosVisiblesEnHechos())
            ->whereNull('fecha_ejecucion')
            ->count();

        $programadosSinFecha = (int) DB::table('mantenimientos')
            ->whereIn('estado', MantenimientoEstados::estadosVisiblesEnProgramados())
            ->whereNull('fecha_programada')
            ->count();

        $ejecucionAntesProgramacion = (int) DB::table('mantenimientos')
            ->whereNotNull('fecha_programada')
            ->whereNotNull('fecha_ejecucion')
            ->whereColumn('fecha_ejecucion', '<', 'fecha_programada')
            ->count();

        $ejecucionFutura = (int) DB::table('mantenimientos')
            ->whereNotNull('fecha_ejecucion')
            ->whereDate('fecha_ejecucion', '>', $hoy)
            ->count();

        $inconsistenciasPeriodo = (int) DB::table('mantenimientos')
            ->whereNotNull('fecha_programada')
            ->whereNotNull('fecha_ejecucion')
            ->whereRaw('YEAR(fecha_programada) <> YEAR(fecha_ejecucion) OR IF(MONTH(fecha_programada) <= 6, 1, 2) <> IF(MONTH(fecha_ejecucion) <= 6, 1, 2)')
            ->count();

        $fechasProgramacion = DB::table('mantenimientos')
            ->whereNotNull('fecha_programada')
            ->selectRaw('MIN(fecha_programada) AS minima, MAX(fecha_programada) AS maxima')
            ->first();

        $fechasEjecucion = DB::table('mantenimientos')
            ->whereNotNull('fecha_ejecucion')
            ->selectRaw('MIN(fecha_ejecucion) AS minima, MAX(fecha_ejecucion) AS maxima')
            ->first();

        return [
            'total' => $total,
            'por_estado' => $porEstado,
            'por_periodo_programado' => $porPeriodoProgramado,
            'por_periodo_ejecucion' => $porPeriodoEjecucion,
            'pendientes_vencidos' => $pendientesVencidos,
            'programados_vencidos' => $programadosVencidos,
            'realizados' => $realizados,
            'cancelados' => $cancelados,
            'sin_usuario_valido' => $sinUsuarioValido,
            'duplicados_exactos' => $duplicadosExactos,
            'duplicados_logicos' => $duplicadosLogicos,
            'pendientes_con_ejecucion' => $pendientesConEjecucion,
            'realizados_sin_ejecucion' => $realizadosSinEjecucion,
            'programados_sin_fecha_programada' => $programadosSinFecha,
            'ejecucion_antes_de_programacion' => $ejecucionAntesProgramacion,
            'ejecucion_futura' => $ejecucionFutura,
            'inconsistencias_periodo' => $inconsistenciasPeriodo,
            'fecha_programada_minima' => $this->normalizarFechaHuella($fechasProgramacion->minima ?? null),
            'fecha_programada_maxima' => $this->normalizarFechaHuella($fechasProgramacion->maxima ?? null),
            'fecha_ejecucion_minima' => $this->normalizarFechaHuella($fechasEjecucion->minima ?? null),
            'fecha_ejecucion_maxima' => $this->normalizarFechaHuella($fechasEjecucion->maxima ?? null),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fotografiaConfiguracion(): array
    {
        $filas = DB::table('configuracion_sistema')
            ->select('id', 'clave', 'valor', 'tipo', 'descripcion', 'actualizado_por')
            ->orderBy('clave')
            ->orderBy('id')
            ->get();

        $clavesDuplicadas = $filas
            ->groupBy(static fn ($fila) => trim((string) ($fila->clave ?? '')))
            ->filter(static fn ($grupo, $clave) => $clave !== '' && $grupo->count() > 1)
            ->count();

        $clavesNulasOVacias = $filas
            ->filter(static fn ($fila) => trim((string) ($fila->clave ?? '')) === '')
            ->count();

        $valoresPorClave = [];
        foreach ([
            'ciclo_mantenimiento_meses' => ['min' => 1, 'max' => 120],
            'primer_mantenimiento_nuevo_meses' => ['min' => 1, 'max' => 120],
            'recordatorio_dias_antes' => ['min' => 1, 'max' => 365],
        ] as $clave => $limites) {
            $registro = $filas->firstWhere('clave', $clave);
            $valor = $registro ? trim((string) ($registro->valor ?? '')) : '';
            $tipo = $registro ? (string) ($registro->tipo ?? '') : '';
            $esEnteroValido = $tipo === 'entero' && filter_var($valor, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE) !== null;
            $numero = $esEnteroValido ? (int) $valor : null;

            $valoresPorClave[$clave] = [
                'valor' => $valor === '' ? null : $valor,
                'tipo' => $tipo === '' ? null : $tipo,
                'valido' => $numero !== null && $numero >= $limites['min'] && $numero <= $limites['max'],
                'fuera_de_rango_o_invalido' => $numero === null || $numero < $limites['min'] || $numero > $limites['max'],
            ];
        }

        $valoresNulosOVacios = $filas
            ->filter(static fn ($fila) => trim((string) ($fila->valor ?? '')) === '')
            ->count();

        return [
            'total_claves' => $filas->count(),
            'claves_duplicadas' => $clavesDuplicadas,
            'claves_nulas_o_vacias' => $clavesNulasOVacias,
            'valores_nulos_o_vacios' => $valoresNulosOVacios,
            'claves' => $valoresPorClave,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fotografiaIntegridad(): array
    {
        $orphans = [];

        if (DB::table('information_schema.table_constraints')
            ->where('constraint_schema', DB::connection()->getDatabaseName())
            ->where('constraint_type', 'FOREIGN KEY')
            ->exists()) {
            $foreignKeys = DB::table('information_schema.key_column_usage as kcu')
                ->join('information_schema.referential_constraints as rc', function ($join): void {
                    $join->on('kcu.constraint_schema', '=', 'rc.constraint_schema')
                        ->on('kcu.constraint_name', '=', 'rc.constraint_name');
                })
                ->select(
                    'kcu.table_name',
                    'kcu.column_name',
                    'kcu.referenced_table_name',
                    'kcu.referenced_column_name',
                    'kcu.constraint_name'
                )
                ->where('kcu.constraint_schema', DB::connection()->getDatabaseName())
                ->whereNotNull('kcu.referenced_table_name')
                ->orderBy('kcu.table_name')
                ->orderBy('kcu.constraint_name')
                ->orderBy('kcu.ordinal_position')
                ->get();

            foreach ($foreignKeys as $fk) {
                $tabla = (string) $fk->table_name;
                $columna = (string) $fk->column_name;
                $tablaRef = (string) $fk->referenced_table_name;
                $columnaRef = (string) $fk->referenced_column_name;
                $constraint = (string) $fk->constraint_name;

                $conteo = DB::table($tabla . ' as t')
                    ->leftJoin($tablaRef . ' as r', "t.$columna", '=', "r.$columnaRef")
                    ->whereNotNull("t.$columna")
                    ->whereNull("r.$columnaRef")
                    ->count();

                $orphans[] = [
                    'constraint' => $constraint,
                    'tabla' => $tabla,
                    'columna' => $columna,
                    'referencia' => $tablaRef . '.' . $columnaRef,
                    'conteo' => $conteo,
                ];
            }
        }

        $tiposIncompatibles = [];
        foreach ($orphans as $orphan) {
            $tiposIncompatibles[] = [
                'constraint' => $orphan['constraint'],
                'compatible' => true,
            ];
        }

        return [
            'huérfanos' => $orphans,
            'total_huerfanos' => array_sum(array_map(static fn (array $fk): int => (int) ($fk['conteo'] ?? 0), $orphans)),
            'tipos_incompatibles' => $tiposIncompatibles,
            'duplicados_propuesta_unica' => [
                'usuarios.codigo_activo' => $this->countDuplicatesByExpression('usuarios', 'codigo_activo'),
                'usuarios.serial_equipo' => $this->countDuplicatesByExpression('usuarios', 'LOWER(TRIM(serial_equipo))', true),
                'usuarios_sistema.correo' => $this->countDuplicatesByExpression('usuarios_sistema', 'LOWER(TRIM(correo))', true),
                'configuracion_sistema.clave' => $this->countDuplicatesByExpression('configuracion_sistema', 'LOWER(TRIM(clave))', true),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function construirHuellasLogicas(array $esquema): array
    {
        $tablasCriticas = [
            'usuarios' => [
                'strict_exclude' => [],
                'functional_exclude' => ['created_at', 'updated_at', 'mantenimiento_resumen_actualizado_at'],
            ],
            'usuarios_sistema' => [
                'strict_exclude' => [],
                'functional_exclude' => ['created_at', 'updated_at', 'ultimo_acceso'],
            ],
            'mantenimientos' => [
                'strict_exclude' => [],
                'functional_exclude' => ['created_at', 'updated_at'],
            ],
            'configuracion_sistema' => [
                'strict_exclude' => [],
                'functional_exclude' => [],
            ],
        ];

        $columnasPorTabla = [];
        foreach ($esquema['tablas'] as $tabla) {
            $columnasPorTabla[$tabla['nombre']] = array_column($tabla['columnas'], 'nombre');
        }

        $huellas = [];
        foreach ($tablasCriticas as $tabla => $regla) {
            $huellas[$tabla] = [
                'strict' => $this->calcularHuellaTabla(
                    $tabla,
                    $columnasPorTabla[$tabla] ?? [],
                    $regla['strict_exclude']
                ),
                'functional' => $this->calcularHuellaTabla(
                    $tabla,
                    $columnasPorTabla[$tabla] ?? [],
                    $regla['functional_exclude']
                ),
                'exclusiones_funcionales' => $regla['functional_exclude'],
            ];
        }

        return $huellas;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function construirDiagnosticoCodificacion(array $esquema): array
    {
        $resultado = [];

        foreach ($esquema['tablas'] as $tabla) {
            $nombreTabla = (string) $tabla['nombre'];
            foreach ($tabla['columnas'] as $columna) {
                $tipo = strtolower((string) ($columna['tipo'] ?? ''));
                if (!in_array($tipo, ['char', 'varchar', 'text', 'tinytext', 'mediumtext', 'longtext', 'enum', 'set'], true)) {
                    continue;
                }

                $nombreColumna = (string) ($columna['nombre'] ?? '');
                $conteo = $this->countSuspiciousTextRows($nombreTabla, $nombreColumna);
                if ($conteo <= 0) {
                    continue;
                }

                $resultado[] = [
                    'tabla' => $nombreTabla,
                    'columna' => $nombreColumna,
                    'conteo' => $conteo,
                ];
            }
        }

        usort($resultado, static function (array $a, array $b): int {
            return [$a['tabla'], $a['columna']] <=> [$b['tabla'], $b['columna']];
        });

        return $resultado;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function construirHallazgosConocidos(array $snapshot): array
    {
        $hallazgos = [];

        $usuarios = $snapshot['fotografia_funcional']['usuarios'] ?? [];
        if (($usuarios['duplicados_logicos_serial'] ?? 0) > 0) {
            $hallazgos[] = 'Existen seriales duplicados al normalizar TRIM y mayúsculas/minúsculas.';
        }
        if (($usuarios['sin_historial_mantenimiento'] ?? 0) > 0) {
            $hallazgos[] = 'Hay usuarios sin historial de mantenimiento.';
        }

        $mantenimientos = $snapshot['fotografia_funcional']['mantenimientos'] ?? [];
        if (($mantenimientos['duplicados_exactos'] ?? 0) > 0) {
            $hallazgos[] = 'Se detectaron duplicados exactos en mantenimientos.';
        }
        if (($mantenimientos['duplicados_logicos'] ?? 0) > 0) {
            $hallazgos[] = 'Se detectaron duplicados lógicos en mantenimientos por usuario y periodo.';
        }
        if (($mantenimientos['ejecucion_antes_de_programacion'] ?? 0) > 0) {
            $hallazgos[] = 'Existen mantenimientos ejecutados antes de su fecha programada.';
        }

        $integridad = $snapshot['fotografia_funcional']['integridad'] ?? [];
        if (($integridad['total_huerfanos'] ?? 0) > 0) {
            $hallazgos[] = 'Existen registros huérfanos en relaciones verificadas.';
        }

        $config = $snapshot['fotografia_funcional']['configuracion'] ?? [];
        foreach (($config['claves'] ?? []) as $clave => $detalle) {
            if (($detalle['fuera_de_rango_o_invalido'] ?? false) === true) {
                $hallazgos[] = 'La clave de configuración "' . $clave . '" está ausente, inválida o fuera de rango.';
            }
        }

        if ($hallazgos === []) {
            $hallazgos[] = 'No se detectaron hallazgos bloqueantes en la base aislada.';
        }

        return $hallazgos;
    }

    private function countDuplicatesByExpression(string $tabla, string $expresion, bool $usarExpresionBruta = false): int
    {
        $consulta = DB::table($tabla)
            ->selectRaw(($usarExpresionBruta ? $expresion : 'LOWER(TRIM(' . $expresion . '))') . ' AS clave, COUNT(*) AS total');

        if (!$usarExpresionBruta) {
            $consulta->whereRaw('TRIM(COALESCE(' . $expresion . ', "")) <> ""');
        } else {
            $consulta->whereRaw('TRIM(COALESCE(' . str_replace('LOWER(TRIM(', '', str_replace('))', '', $expresion)) . ', "")) <> ""');
        }

        $consulta->groupByRaw($usarExpresionBruta ? $expresion : 'LOWER(TRIM(' . $expresion . '))')
            ->havingRaw('COUNT(*) > 1');

        return (int) $consulta->get()->count();
    }

    private function countSuspiciousTextRows(string $tabla, string $columna): int
    {
        $expresion = '`' . $columna . '`';

        return (int) DB::table($tabla)
            ->where(function ($query) use ($expresion): void {
                $query
                    ->whereRaw($expresion . " LIKE '%Ãƒ%'")
                    ->orWhereRaw($expresion . " LIKE '%Ã‚%'")
                    ->orWhereRaw($expresion . " LIKE '%Ã¢%'")
                    ->orWhereRaw($expresion . " LIKE '%ï¿½%'")
                    ->orWhereRaw($expresion . " LIKE '%Ã¯Â¿Â½%'")
                    ->orWhereRaw($expresion . " REGEXP '[[:cntrl:]]'")
                    ->orWhereRaw($expresion . " REGEXP '^[[:space:]]'")
                    ->orWhereRaw($expresion . " REGEXP '[[:space:]]$'");
            })
            ->count();
    }

    private function calcularHuellaTabla(string $tabla, array $columnasOrdenadas, array $columnasExcluir): array
    {
        $columnas = array_values(array_filter(
            $columnasOrdenadas,
            static fn (string $columna) => !in_array($columna, $columnasExcluir, true)
        ));

        $query = DB::table($tabla);
        $orden = $this->obtenerColumnasOrdenacion($tabla);
        if ($orden === []) {
            $orden = $columnas !== [] ? [$columnas[0]] : ['id'];
        }

        foreach ($orden as $columnaOrden) {
            $query->orderBy($columnaOrden);
        }

        $filas = $query->get()->map(static fn ($row) => (array) $row)->all();
        $normalizadas = [];
        foreach ($filas as $fila) {
            $registro = [];
            foreach ($columnas as $columna) {
                $meta = $this->metaColumna($tabla, $columna);
                $registro[$columna] = $this->normalizarValorHuella($fila[$columna] ?? null, $meta);
            }
            $normalizadas[] = $registro;
        }

        $json = json_encode($normalizadas, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR);

        return [
            'algoritmo' => 'sha256(json_normalizado_filas_ordenadas_por_pk)',
            'columnas_incluidas' => $columnas,
            'columnas_excluidas' => array_values($columnasExcluir),
            'conteo_registros' => count($normalizadas),
            'sha256' => hash('sha256', $json),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function obtenerColumnasOrdenacion(string $tabla): array
    {
        $columnas = DB::table('information_schema.statistics')
            ->select('column_name', 'seq_in_index')
            ->where('table_schema', DB::connection()->getDatabaseName())
            ->where('table_name', $tabla)
            ->where('index_name', 'PRIMARY')
            ->orderBy('seq_in_index')
            ->get()
            ->pluck('column_name')
            ->map(static fn ($valor) => (string) $valor)
            ->values()
            ->all();

        return $columnas;
    }

    /**
     * @return array<string, mixed>
     */
    private function metaColumna(string $tabla, string $columna): array
    {
        $fila = DB::table('information_schema.columns')
            ->select('data_type', 'column_type')
            ->where('table_schema', DB::connection()->getDatabaseName())
            ->where('table_name', $tabla)
            ->where('column_name', $columna)
            ->first();

        return $fila ? (array) $fila : [];
    }

    private function normalizarValorHuella(mixed $valor, array $meta): mixed
    {
        if ($valor === null) {
            return '__NULL__';
        }

        $tipo = strtolower((string) ($meta['data_type'] ?? ''));

        if (is_bool($valor)) {
            return $valor ? 1 : 0;
        }

        if (in_array($tipo, ['tinyint', 'smallint', 'mediumint', 'int', 'bigint'], true)) {
            return (int) $valor;
        }

        if (in_array($tipo, ['decimal', 'numeric', 'float', 'double'], true)) {
            $numero = (string) $valor;
            $numero = rtrim(rtrim($numero, '0'), '.');

            return $numero === '' ? '0' : $numero;
        }

        if (in_array($tipo, ['date', 'datetime', 'timestamp', 'time'], true)) {
            return $this->normalizarFechaHuella($valor);
        }

        return (string) $valor;
    }

    private function normalizarFechaHuella(mixed $valor): string
    {
        $valor = trim((string) $valor);
        if ($valor === '') {
            return '__NULL__';
        }

        $fecha = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $valor)
            ?: DateTimeImmutable::createFromFormat('Y-m-d', $valor)
            ?: DateTimeImmutable::createFromFormat('H:i:s', $valor);

        if ($fecha === false) {
            return $valor;
        }

        if (strlen($valor) === 10) {
            return $fecha->format('Y-m-d');
        }

        if (strlen($valor) === 8) {
            return $fecha->format('H:i:s');
        }

        return $fecha->format('Y-m-d H:i:s');
    }

    private function formatearLongitudColumna(array $columna): ?int
    {
        $longitud = $columna['character_maximum_length'] ?? null;
        if ($longitud !== null) {
            return (int) $longitud;
        }

        $tipoCompleto = (string) ($columna['column_type'] ?? '');
        if (preg_match('/\((\d+)\)/', $tipoCompleto, $match) === 1) {
            return (int) $match[1];
        }

        return null;
    }

    private function normalizarValorPredeterminado(mixed $valor): mixed
    {
        if ($valor === null) {
            return null;
        }

        return is_string($valor) ? trim($valor) : $valor;
    }

    private function extraerCharsetTabla(string $collation, string $fallback): string
    {
        if ($collation === '') {
            return $fallback;
        }

        $pos = strpos($collation, '_');
        if ($pos === false) {
            return $collation;
        }

        return substr($collation, 0, $pos);
    }
}

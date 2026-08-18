<?php

namespace App\Http\Controllers;

use App\Services\Legacy\MantenimientoRepository;
use App\Services\Legacy\OrigenEquipoPresenter;
use App\Services\Legacy\PeriodicidadMantenimientoService;
use App\Services\Legacy\PanelNavigationService;
use App\Services\Legacy\ReversionEstadoMantenimientoService;
use App\Services\Legacy\UsuarioRepository;
use App\Services\SemestreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PanelController extends Controller
{
    public function __construct(private readonly PanelNavigationService $navigation)
    {
        $this->middleware('can:access-panel');
    }

    /**
     * @return View|RedirectResponse
     */
    public function index(Request $request)
    {
        $apartado = trim((string) $request->query('apartado', ''));
        if ($apartado !== '') {
            $ruta = $this->navigation->resolverRutaLegacyApartado($apartado);
            if ($ruta !== null) {
                $query = $this->navigation->querySinApartado($request->query());
                return redirect()->to($this->navigation->urlConQuery(route($ruta), $query));
            }
        }

        return view('panel.index', [
            'usuario' => session('usuario_autenticado'),
        ]);
    }

    public function reportes(Request $request, MantenimientoRepository $repo, SemestreService $semestreService): View
    {
        $periodo = $semestreService->desdeRequest($request);

        return view('panel.reportes', [
            'usuario' => session('usuario_autenticado'),
            'periodoConsulta' => $periodo,
            'resumenSemestral' => $repo->obtenerResumenSemestralActual($periodo),
            'actividadReciente' => $repo->listarActividadReciente(5, $periodo),
        ]);
    }

    public function configuracion(
        Request $request,
        UsuarioRepository $usuarioRepo,
        MantenimientoRepository $repo,
        ReversionEstadoMantenimientoService $reversionService
    ): View
    {
        $busquedaUsuario = trim((string) $request->query('busqueda_usuario', ''));
        $codigoActivo = max(0, (int) $request->query('codigo_activo', 0));
        $estadoDestino = trim((string) $request->query('estado_destino', ''));
        $usuarioResuelto = null;
        $reversionAnalisis = null;
        $reversionPreview = session('gestion_preview');
        $configSearchState = 'initial';
        $configSearchMatches = [];
        $consultaUsuario = $codigoActivo > 0 ? (string) $codigoActivo : $busquedaUsuario;
        if ($consultaUsuario !== '') {
            $resultadoBusqueda = $usuarioRepo->resolverCodigoActivoPorBusqueda($consultaUsuario);
            if (($resultadoBusqueda['ok'] ?? false) === true) {
                $usuarioResuelto = $resultadoBusqueda;
                $reversionAnalisis = $reversionService->analizarUsuarioPorCodigoActivo((int) ($resultadoBusqueda['codigo_activo'] ?? 0));
                $configSearchState = 'resolved';
            } else {
                $configSearchState = 'no_results';
            }
        }

        return view('panel.configuracion', [
            'usuario' => session('usuario_autenticado'),
            'busquedaUsuario' => $busquedaUsuario,
            'codigoActivo' => $codigoActivo,
            'estadoDestino' => $estadoDestino,
            'usuarioResuelto' => $usuarioResuelto,
            'reversionAnalisis' => $reversionAnalisis,
            'reversionPreview' => is_array($reversionPreview) ? $reversionPreview : null,
            'configSearchState' => $configSearchState,
            'configSearchMatches' => $configSearchMatches,
            'tecnicos' => $repo->listarTecnicosActivos(),
        ]);
    }

    public function buscarUsuarioConfiguracion(
        Request $request,
        UsuarioRepository $usuarioRepo,
        MantenimientoRepository $repo,
        ReversionEstadoMantenimientoService $reversionService
    ): JsonResponse
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'codigo_activo' => ['nullable', 'integer', 'min:1'],
        ]);

        $busquedaUsuario = trim((string) ($data['q'] ?? ''));
        $codigoActivo = (int) ($data['codigo_activo'] ?? 0);
        $tecnicos = $repo->listarTecnicosActivos();
        $usuarioResuelto = null;
        $reversionAnalisis = null;
        $configSearchState = 'initial';
        $configSearchMatches = [];

        if ($codigoActivo > 0) {
            $resultadoBusqueda = $usuarioRepo->resolverCodigoActivoPorBusqueda((string) $codigoActivo);
            if (($resultadoBusqueda['ok'] ?? false) === true) {
                $usuarioResuelto = $resultadoBusqueda;
                $reversionAnalisis = $reversionService->analizarUsuarioPorCodigoActivo((int) ($resultadoBusqueda['codigo_activo'] ?? 0));
                $configSearchState = 'resolved';
            } else {
                $configSearchState = 'no_results';
            }
        } elseif ($this->debeBuscarUsuarioConfiguracion($busquedaUsuario)) {
            $configSearchMatches = $usuarioRepo->buscarCoincidenciasConfiguracion($busquedaUsuario, 8);

            if ($configSearchMatches === []) {
                $configSearchState = 'no_results';
            } else {
                $coincidenciaAutomatica = $this->resolverCoincidenciaAutomaticaConfiguracion($busquedaUsuario, $configSearchMatches);

                if ($coincidenciaAutomatica !== null) {
                    $resultadoBusqueda = $usuarioRepo->resolverCodigoActivoPorBusqueda((string) $coincidenciaAutomatica);
                    if (($resultadoBusqueda['ok'] ?? false) === true) {
                        $usuarioResuelto = $resultadoBusqueda;
                        $reversionAnalisis = $reversionService->analizarUsuarioPorCodigoActivo((int) ($resultadoBusqueda['codigo_activo'] ?? 0));
                        $configSearchState = 'resolved';
                    } else {
                        $configSearchState = 'no_results';
                    }
                } else {
                    $configSearchState = 'multiple';
                }
            }
        }

        $html = view('panel.partials.configuracion-gestion-estado-body', [
            'busquedaUsuario' => $busquedaUsuario,
            'estadoDestino' => '',
            'usuarioResuelto' => $usuarioResuelto,
            'reversionAnalisis' => $reversionAnalisis,
            'reversionPreview' => null,
            'tecnicos' => $tecnicos,
            'configSearchState' => $configSearchState,
            'configSearchMatches' => $configSearchMatches,
        ])->render();

        return response()->json([
            'ok' => true,
            'mode' => $configSearchState,
            'html' => $html,
            'query' => $busquedaUsuario,
            'codigo_activo' => $usuarioResuelto['codigo_activo'] ?? $codigoActivo,
            'matches_count' => count($configSearchMatches),
        ]);
    }

    public function proximos(Request $request, MantenimientoRepository $repo, SemestreService $semestreService, PeriodicidadMantenimientoService $periodicidadService): View
    {
        $periodo = $semestreService->desdeRequest($request);
        $anio = (int) ($periodo['anio'] ?? (int) date('Y'));

        $busqueda = trim((string) $request->query('q_proximos', ''));
        $pagina = max(1, (int) $request->query('proximos_pagina', 1));
        $porPagina = (int) $request->query('proximos_por_pagina', 10);
        if (!in_array($porPagina, [10, 25, 50], true)) {
            $porPagina = 10;
        }

        $total = $repo->contarProximosPorPeriodo($busqueda, $periodo);
        $totalPaginas = $this->navigation->totalPaginas($total, $porPagina);
        if ($pagina > $totalPaginas) {
            $pagina = $totalPaginas;
        }

        $offset = $this->navigation->offset($pagina, $porPagina);

        return view('panel.mantenimientos', [
            'usuario' => session('usuario_autenticado'),
            'modo' => 'proximos',
            'titulo' => 'Próximos',
            'subtitulo' => 'Mantenimientos pendientes listos para programar.',
            'periodoConsulta' => $periodo,
            'items' => $repo->listarProximosPorPeriodo($porPagina, $offset, $busqueda, $periodo),
            'busqueda' => $busqueda,
            'pagina' => $pagina,
            'porPagina' => $porPagina,
            'total' => $total,
            'totalPaginas' => $totalPaginas,
            'tecnicos' => $repo->listarTecnicosActivos(),
            'festivosCalendario' => $periodicidadService->festivosEntre($anio - 10, $anio + 10),
        ]);
    }

    public function programados(Request $request, MantenimientoRepository $repo, SemestreService $semestreService, PeriodicidadMantenimientoService $periodicidadService): View
    {
        $periodo = $semestreService->desdeRequest($request);
        $anio = (int) ($periodo['anio'] ?? (int) date('Y'));

        $busqueda = trim((string) $request->query('q_programados', ''));
        $pagina = max(1, (int) $request->query('programados_pagina', 1));
        $porPagina = (int) $request->query('programados_por_pagina', 10);
        if (!in_array($porPagina, [10, 25, 50], true)) {
            $porPagina = 10;
        }

        $total = $repo->contarPorEstado('programado', $busqueda, $periodo);
        $totalPaginas = $this->navigation->totalPaginas($total, $porPagina);
        if ($pagina > $totalPaginas) {
            $pagina = $totalPaginas;
        }

        $offset = $this->navigation->offset($pagina, $porPagina);

        return view('panel.mantenimientos', [
            'usuario' => session('usuario_autenticado'),
            'modo' => 'programados',
            'titulo' => 'Programados',
            'subtitulo' => 'Mantenimientos ya agendados para marcar como hechos.',
            'periodoConsulta' => $periodo,
            'items' => $repo->listarPorEstado('programado', $porPagina, $offset, $busqueda, $periodo),
            'busqueda' => $busqueda,
            'pagina' => $pagina,
            'porPagina' => $porPagina,
            'total' => $total,
            'totalPaginas' => $totalPaginas,
            'tecnicos' => $repo->listarTecnicosActivos(),
            'festivosCalendario' => $periodicidadService->festivosEntre($anio - 10, $anio + 10),
        ]);
    }

    public function hechos(Request $request, MantenimientoRepository $repo, SemestreService $semestreService): View
    {
        $periodo = $semestreService->desdeRequest($request);
        $busqueda = trim((string) $request->query('q_realizados', ''));
        $pagina = max(1, (int) $request->query('realizados_pagina', 1));
        $porPagina = (int) $request->query('realizados_por_pagina', 10);
        if (!in_array($porPagina, [10, 25, 50], true)) {
            $porPagina = 10;
        }

        $total = $repo->contarPorEstado('realizado', $busqueda, $periodo, true);
        $totalPaginas = $this->navigation->totalPaginas($total, $porPagina);
        if ($pagina > $totalPaginas) {
            $pagina = $totalPaginas;
        }

        $offset = $this->navigation->offset($pagina, $porPagina);

        return view('panel.hechos', [
            'usuario' => session('usuario_autenticado'),
            'titulo' => 'Hechos',
            'subtitulo' => 'Mantenimientos ejecutados con constancia y técnico responsable.',
            'periodoConsulta' => $periodo,
            'items' => $repo->listarPorEstado('realizado', $porPagina, $offset, $busqueda, $periodo, true, $periodo),
            'busqueda' => $busqueda,
            'pagina' => $pagina,
            'porPagina' => $porPagina,
            'total' => $total,
            'totalPaginas' => $totalPaginas,
            'tecnicos' => $repo->listarTecnicosActivos(),
        ]);
    }

    public function evidenciaPdf(Request $request, MantenimientoRepository $repo, int $mantenimientoId)
    {
        $registro = $repo->obtenerEvidenciaPorMantenimientoId($mantenimientoId);
        $rutaRelativa = trim((string) ($registro['evidencia_pdf'] ?? ''));

        if ($rutaRelativa === '') {
            return $this->redirectConContexto($request, 'panel.hechos')->with('panel_error', 'La evidencia no está disponible para este mantenimiento.');
        }

        $rutaRelativa = ltrim($rutaRelativa, "/\\");
        $disco = Storage::disk('public');
        if (!$disco->exists($rutaRelativa)) {
            return $this->redirectConContexto($request, 'panel.hechos')->with('panel_error', 'El archivo de evidencia ya no existe en el servidor.');
        }

        $nombreArchivo = basename(str_replace('\\', '/', $rutaRelativa));

        if ($request->boolean('download')) {
            return $disco->download($rutaRelativa, $nombreArchivo, [
                'Content-Type' => 'application/pdf',
            ]);
        }

        return $disco->response($rutaRelativa, $nombreArchivo, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function usuarios(Request $request, UsuarioRepository $repo, OrigenEquipoPresenter $origenEquipoPresenter): View
    {
        $busqueda = trim((string) $request->query('q_usuarios', ''));
        $editarUsuarioId = (int) $request->query('editar_usuario_id', 0);
        $usuarioEdicion = $editarUsuarioId > 0 ? $repo->obtenerPorId($editarUsuarioId) : null;

        return view('panel.usuarios', [
            'usuario' => session('usuario_autenticado'),
            'usuarios' => $repo->listarTodos($busqueda),
            'areas' => $repo->listarAreasActivas(),
            'busqueda' => $busqueda,
            'nuevoUsuario' => (int) $request->query('nuevo_usuario', 0) === 1,
            'usuarioEdicion' => $usuarioEdicion,
            'editarUsuarioId' => $editarUsuarioId,
            'origenEquipoPresenter' => $origenEquipoPresenter,
            'origenEquipoOptions' => $origenEquipoPresenter->options(),
        ]);
    }

    public function crearUsuario(Request $request, UsuarioRepository $repo): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'codigo_activo' => ['required', 'integer', 'min:1'],
            'serial_equipo' => ['required', 'string', 'max:150'],
            'area_id' => ['nullable', 'integer', 'min:1'],
            'tipo_equipo' => ['required', 'in:normal,nuevo'],
            'fecha_ultimo_mantenimiento_base' => ['nullable', 'date_format:Y-m-d'],
            'filtro_area_id' => ['nullable', 'integer', 'min:0'],
            'filtro_q_usuarios' => ['nullable', 'string'],
            'form_context' => ['nullable', 'string'],
        ]);

        if (($data['tipo_equipo'] ?? 'normal') === 'normal' && empty($data['fecha_ultimo_mantenimiento_base'])) {
            throw ValidationException::withMessages([
                'fecha_ultimo_mantenimiento_base' => 'La fecha de último mantenimiento es obligatoria para equipos de inventario base.',
            ]);
        }

        $resultado = $repo->crearUsuario(
            $data['nombre'],
            (int) $data['codigo_activo'],
            $data['serial_equipo'],
            !empty($data['area_id']) ? (int) $data['area_id'] : null,
            $data['tipo_equipo'],
            $data['fecha_ultimo_mantenimiento_base'] ?? null
        );

        return $this->redirectConContexto($request, 'panel.usuarios', [
            'area_id' => (int) ($data['filtro_area_id'] ?? 0),
            'q_usuarios' => (string) ($data['filtro_q_usuarios'] ?? ''),
            'nuevo_usuario' => 1,
        ])->with(
            $resultado['ok'] ? 'panel_message' : 'panel_error',
            $resultado['mensaje'] ?? 'No se pudo crear el usuario.'
        );
    }

    public function cambiarEstadoUsuario(Request $request, UsuarioRepository $repo): RedirectResponse
    {
        $data = $request->validate([
            'usuario_id' => ['required', 'integer', 'min:1'],
            'nuevo_estado' => ['required', 'in:0,1'],
            'filtro_area_id' => ['nullable', 'integer', 'min:0'],
            'filtro_q_usuarios' => ['nullable', 'string'],
        ]);

        $ok = $repo->cambiarEstadoUsuario((int) $data['usuario_id'], (string) $data['nuevo_estado'] === '1');

        return $this->redirectConContexto($request, 'panel.usuarios', [
            'area_id' => (int) ($data['filtro_area_id'] ?? 0),
            'q_usuarios' => (string) ($data['filtro_q_usuarios'] ?? ''),
        ])->with(
            $ok ? 'panel_message' : 'panel_error',
            $ok ? 'Estado de usuario actualizado.' : 'No se pudo cambiar el estado del usuario.'
        );
    }

    public function actualizarUsuario(Request $request, UsuarioRepository $repo): RedirectResponse
    {
        $data = $request->validate([
            'usuario_id' => ['required', 'integer', 'min:1'],
            'nombre' => ['required', 'string', 'max:150'],
            'codigo_activo' => ['required', 'integer', 'min:1'],
            'serial_equipo' => ['required', 'string', 'max:150'],
            'area_id' => ['nullable', 'integer', 'min:1'],
            'tipo_equipo' => ['required', 'in:normal,nuevo'],
            'fecha_ultimo_mantenimiento_base' => ['nullable', 'date_format:Y-m-d'],
            'filtro_area_id' => ['nullable', 'integer', 'min:0'],
            'filtro_q_usuarios' => ['nullable', 'string'],
            'editar_usuario_id' => ['nullable', 'integer', 'min:0'],
            'form_context' => ['nullable', 'string'],
        ]);

        $usuarioActual = $repo->obtenerPorId((int) $data['usuario_id']);
        $fechaBaseFueEnviada = $request->has('fecha_ultimo_mantenimiento_base');
        $fechaBaseAlmacenada = trim((string) ($usuarioActual['fecha_ultimo_mantenimiento_base'] ?? ''));
        $fechaUltimaResumen = trim((string) ($usuarioActual['ultima_fecha_mantenimiento'] ?? ''));
        $fechaBaseEnviada = trim((string) ($data['fecha_ultimo_mantenimiento_base'] ?? ''));
        $fechaBaseActual = $fechaBaseFueEnviada
            ? $fechaBaseEnviada
            : $fechaBaseAlmacenada;
        $fechaBaseCambioReal = $fechaBaseFueEnviada && $fechaBaseEnviada !== $fechaBaseAlmacenada;

        $actualizarFechaBase = $fechaBaseFueEnviada;
        if ($fechaBaseAlmacenada === '' && $fechaUltimaResumen !== '' && $fechaBaseEnviada === $fechaUltimaResumen) {
            $actualizarFechaBase = false;
        }

        if (
            ($data['tipo_equipo'] ?? 'normal') === 'normal'
            && empty($fechaBaseActual)
            && $fechaUltimaResumen === ''
            && !$repo->usuarioTieneHistorialReal((int) $data['usuario_id'])
        ) {
            throw ValidationException::withMessages([
                'fecha_ultimo_mantenimiento_base' => 'La fecha de último mantenimiento es obligatoria mientras este equipo de inventario base no tenga historial real.',
            ]);
        }

        $ok = $repo->actualizarUsuario(
            (int) $data['usuario_id'],
            $data['nombre'],
            (int) $data['codigo_activo'],
            $data['serial_equipo'],
            !empty($data['area_id']) ? (int) $data['area_id'] : null,
            $data['tipo_equipo'],
            $data['fecha_ultimo_mantenimiento_base'] ?? null,
            $actualizarFechaBase,
            $fechaBaseCambioReal
        );

        return $this->redirectConContexto($request, 'panel.usuarios', [
            'area_id' => (int) ($data['filtro_area_id'] ?? 0),
            'q_usuarios' => (string) ($data['filtro_q_usuarios'] ?? ''),
        ])->with(
            $ok ? 'panel_message' : 'panel_error',
            $ok ? 'Usuario actualizado correctamente.' : 'No se pudo actualizar el usuario.'
        );
    }

    public function eliminarUsuario(Request $request, UsuarioRepository $repo): RedirectResponse
    {
        $data = $request->validate([
            'usuario_id' => ['required', 'integer', 'min:1'],
            'filtro_area_id' => ['nullable', 'integer', 'min:0'],
            'filtro_q_usuarios' => ['nullable', 'string'],
        ]);

        $resultado = $repo->eliminarUsuarioSeguro((int) $data['usuario_id']);

        return $this->redirectConContexto($request, 'panel.usuarios', [
            'area_id' => (int) ($data['filtro_area_id'] ?? 0),
            'q_usuarios' => (string) ($data['filtro_q_usuarios'] ?? ''),
        ])->with(
            ($resultado['ok'] ?? false) ? 'panel_message' : 'panel_error',
            (string) ($resultado['mensaje'] ?? 'No se pudo completar la operación.')
        );
    }

    public function programarProximo(Request $request, MantenimientoRepository $repo, PeriodicidadMantenimientoService $periodicidadService): RedirectResponse
    {
        $data = $request->validate([
            'mantenimiento_id' => ['required', 'integer', 'min:1'],
            'fecha_programada' => ['required', 'date_format:Y-m-d'],
        ]);

        $contexto = [
            'q_proximos' => (string) $request->input('q_proximos', ''),
            'proximos_pagina' => max(1, (int) $request->input('proximos_pagina', 1)),
            'proximos_por_pagina' => in_array((int) $request->input('proximos_por_pagina', 10), [10, 25, 50], true)
                ? (int) $request->input('proximos_por_pagina', 10)
                : 10,
        ];

        $fecha = \DateTimeImmutable::createFromFormat('!Y-m-d', $data['fecha_programada']);
        $hoy = new \DateTimeImmutable('today');
        if ($fecha === false || $fecha < $hoy) {
            return $this->redirectConContexto($request, 'panel.proximos', $contexto)->with('panel_error', 'La fecha programada no puede ser anterior a hoy.');
        }

        if (!$periodicidadService->esDiaHabil($data['fecha_programada'])) {
            return $this->redirectConContexto($request, 'panel.proximos', $contexto)->with('panel_error', 'La fecha programada debe ser un dia habil.');
        }

        $resultado = $repo->marcarComoProgramado((int) $data['mantenimiento_id'], $data['fecha_programada']);

        return $this->redirectConContexto($request, 'panel.proximos', $contexto)->with(
            $resultado['ok'] ? 'panel_message' : 'panel_error',
            $resultado['mensaje'] ?? 'No fue posible completar la acción.'
        );
    }

    public function marcarProgramadoComoHecho(Request $request, MantenimientoRepository $repo, PeriodicidadMantenimientoService $periodicidadService): RedirectResponse
    {
        $contexto = [
            'q_programados' => (string) $request->input('q_programados', ''),
            'programados_pagina' => max(1, (int) $request->input('programados_pagina', 1)),
            'programados_por_pagina' => in_array((int) $request->input('programados_por_pagina', 10), [10, 25, 50], true)
                ? (int) $request->input('programados_por_pagina', 10)
                : 10,
        ];

        $data = $request->validate([
            'mantenimiento_id' => ['required', 'integer', 'min:1'],
            'fecha_ejecucion' => ['required', 'date_format:Y-m-d'],
            'tecnico_id' => ['required', 'integer', 'min:1'],
            'observaciones' => ['nullable', 'string'],
            'confirmar_ejecucion_antes_programacion' => ['nullable', 'boolean'],
            'constancia_pdf' => ['required', 'file', 'mimetypes:application/pdf', 'max:5120'],
        ]);

        $fechaEjecucion = \DateTimeImmutable::createFromFormat('!Y-m-d', $data['fecha_ejecucion']);
        $hoy = Carbon::today(config('app.timezone'));
        if ($fechaEjecucion === false || $fechaEjecucion > $hoy) {
            return $this->redirectConContexto($request, 'panel.programados', $contexto)->with('panel_error', 'La fecha de ejecucion no puede ser posterior a hoy.');
        }

        $tecnico = $repo->obtenerNombreTecnicoPorId((int) $data['tecnico_id']);
        if ($tecnico === null) {
            return $this->redirectConContexto($request, 'panel.programados', $contexto)->with('panel_error', 'Debes seleccionar un técnico o responsable válido.');
        }

        $mantenimiento = $repo->obtenerMantenimientoPorId((int) $data['mantenimiento_id']);
        $fechaProgramada = trim((string) ($mantenimiento['fecha_programada'] ?? ''));
        if ($fechaProgramada !== '' && $data['fecha_ejecucion'] < $fechaProgramada && !$request->boolean('confirmar_ejecucion_antes_programacion')) {
            return $this->redirectConContexto($request, 'panel.programados', $contexto)
                ->withInput($request->except(['constancia_pdf']))
                ->with('panel_error', 'La fecha de ejecucion es anterior a la fecha programada. Confirma que la informacion sea correcta.');
        }

        /** @var UploadedFile $pdf */
        $pdf = $request->file('constancia_pdf');
        $nombreArchivo = basename((string) $pdf->getClientOriginalName());
        if ($nombreArchivo === '') {
            $nombreArchivo = 'constancia.pdf';
        }

        $rutaRelativa = $pdf->storeAs(
            'constancias/' . (int) $data['mantenimiento_id'],
            $nombreArchivo,
            'public'
        );

        $resultado = $repo->marcarProgramadoComoHechoYGenerarSiguiente(
            (int) $data['mantenimiento_id'],
            $data['fecha_ejecucion'],
            $tecnico,
            (string) ($data['observaciones'] ?? ''),
            $rutaRelativa
        );

        if (!($resultado['ok'] ?? false)) {
            Storage::disk('public')->delete($rutaRelativa);

            return $this->redirectConContexto($request, 'panel.programados', $contexto)->with('panel_error', $resultado['mensaje'] ?? 'No fue posible marcar como hecho.');
        }

        return $this->redirectConContexto($request, 'panel.programados', $contexto)->with('panel_message', $resultado['mensaje'] ?? 'Mantenimiento marcado como hecho.');
    }

    public function crearTecnico(Request $request, MantenimientoRepository $repo): RedirectResponse
    {
        $data = $request->validate([
            'nombre_tecnico' => ['required', 'string', 'max:150'],
        ]);

        $resultado = $repo->registrarTecnico($data['nombre_tecnico']);

        return $this->redirectConContexto($request, 'panel.configuracion', [
            'busqueda_usuario' => (string) ($request->input('busqueda_usuario', '')),
            'codigo_activo' => (int) ($request->input('codigo_activo', 0)),
            'estado_destino' => (string) ($request->input('estado_destino', '')),
        ])->with(
            $resultado['ok'] ? 'panel_message' : 'panel_error',
            $resultado['mensaje'] ?? 'No fue posible registrar el técnico.'
        );
    }

    public function previsualizarGestionEstado(
        Request $request,
        UsuarioRepository $usuarioRepo,
        ReversionEstadoMantenimientoService $reversionService
    ): RedirectResponse
    {
        $data = $request->validate([
            'busqueda_usuario' => ['required', 'string', 'max:150'],
            'estado_destino' => ['required', 'string', 'max:50'],
            'codigo_activo' => ['nullable', 'integer', 'min:1'],
            'fecha_programada' => ['nullable', 'date_format:Y-m-d'],
            'fecha_ejecucion' => ['nullable', 'date_format:Y-m-d'],
            'tecnico_id' => ['nullable', 'integer', 'min:1'],
            'observaciones' => ['nullable', 'string'],
            'confirmar_ejecucion_antes_programacion' => ['nullable', 'boolean'],
        ]);

        $resolucion = $this->resolverCodigoActivoConfiguracion($usuarioRepo, $data);

        if (($resolucion['ok'] ?? false) !== true) {
            return $this->redirectConContexto($request, 'panel.configuracion', [
                'busqueda_usuario' => $data['busqueda_usuario'],
                'estado_destino' => (string) $data['estado_destino'],
            ])->with('panel_error', $resolucion['mensaje'] ?? 'No se pudo identificar el usuario.');
        }

        $codigoActivo = (int) ($resolucion['codigo_activo'] ?? 0);
        if ($codigoActivo <= 0) {
            return $this->redirectConContexto($request, 'panel.configuracion', [
                'busqueda_usuario' => $data['busqueda_usuario'],
                'estado_destino' => (string) $data['estado_destino'],
            ])->with('panel_error', 'No se encontró un código activo válido para el usuario.');
        }

        $resultado = $reversionService->previsualizarPorCodigoActivo(
            $codigoActivo,
            (string) $data['estado_destino'],
            [
                'fecha_programada' => (string) ($data['fecha_programada'] ?? ''),
                'fecha_ejecucion' => (string) ($data['fecha_ejecucion'] ?? ''),
                'tecnico_id' => (int) ($data['tecnico_id'] ?? 0),
                'observaciones' => (string) ($data['observaciones'] ?? ''),
            ]
        );

        return $this->redirectConContexto($request, 'panel.configuracion', [
            'busqueda_usuario' => $data['busqueda_usuario'],
            'codigo_activo' => $codigoActivo,
            'estado_destino' => (string) $data['estado_destino'],
        ])
            ->withInput($request->except(['_token']))
            ->with(
                ($resultado['ok'] ?? false) ? 'gestion_preview' : 'panel_error',
                ($resultado['ok'] ?? false) ? ($resultado['preview'] ?? null) : ($resultado['mensaje'] ?? 'No fue posible generar la previsualización.')
            );
    }

    public function gestionarEstadoMantenimiento(
        Request $request,
        UsuarioRepository $usuarioRepo,
        ReversionEstadoMantenimientoService $reversionService
    ): RedirectResponse {
        $data = $request->validate([
            'busqueda_usuario' => ['required', 'string', 'max:150'],
            'estado_destino' => ['required', 'string', 'max:50'],
            'codigo_activo' => ['nullable', 'integer', 'min:1'],
            'fecha_programada' => ['nullable', 'date_format:Y-m-d'],
            'fecha_ejecucion' => ['nullable', 'date_format:Y-m-d'],
            'tecnico_id' => ['nullable', 'integer', 'min:1'],
            'observaciones' => ['nullable', 'string'],
            'confirmar_ejecucion_antes_programacion' => ['nullable', 'boolean'],
            'motivo' => ['required', 'string', 'min:5', 'max:500'],
            'preview_hash' => ['required', 'string', 'min:20'],
            'constancia_pdf' => ['nullable', 'file', 'mimetypes:application/pdf', 'max:5120'],
        ]);

        $resolucion = $this->resolverCodigoActivoConfiguracion($usuarioRepo, $data);

        if (($resolucion['ok'] ?? false) !== true) {
            return $this->redirectConContexto($request, 'panel.configuracion', [
                'busqueda_usuario' => $data['busqueda_usuario'],
                'estado_destino' => (string) $data['estado_destino'],
            ])->with('panel_error', $resolucion['mensaje'] ?? 'No se pudo identificar el usuario.');
        }

        $codigoActivo = (int) ($resolucion['codigo_activo'] ?? 0);
        if ($codigoActivo <= 0) {
            return $this->redirectConContexto($request, 'panel.configuracion', [
                'busqueda_usuario' => $data['busqueda_usuario'],
                'estado_destino' => (string) $data['estado_destino'],
            ])->with('panel_error', 'No se encontró un código activo válido para el usuario.');
        }

        $rutaRelativa = '';
        if ((string) $data['estado_destino'] === \App\Services\Legacy\EstadoMantenimientoVigenteService::EJECUTADO) {
            $request->validate([
                'constancia_pdf' => ['required', 'file', 'mimetypes:application/pdf', 'max:5120'],
            ]);

            /** @var UploadedFile $pdf */
            $pdf = $request->file('constancia_pdf');
            $nombreArchivo = basename((string) $pdf->getClientOriginalName());
            if ($nombreArchivo === '') {
                $nombreArchivo = 'constancia.pdf';
            }

            $rutaRelativa = $pdf->storeAs(
                'constancias/gestion/' . $codigoActivo,
                $nombreArchivo,
                'public'
            );
        }

        $resultado = $reversionService->ejecutarPorCodigoActivo(
            $codigoActivo,
            (string) $data['estado_destino'],
            [
                'fecha_programada' => (string) ($data['fecha_programada'] ?? ''),
                'fecha_ejecucion' => (string) ($data['fecha_ejecucion'] ?? ''),
                'tecnico_id' => (int) ($data['tecnico_id'] ?? 0),
                'observaciones' => (string) ($data['observaciones'] ?? ''),
                'confirmar_ejecucion_antes_programacion' => $request->boolean('confirmar_ejecucion_antes_programacion'),
                'constancia_pdf_path' => $rutaRelativa,
            ],
            (string) $data['motivo'],
            (string) $data['preview_hash'],
            [
                'id' => (int) (auth()->user()?->id ?? 0),
                'nombre' => (string) (auth()->user()?->nombre ?? 'Administrador'),
            ]
        );

        if (!($resultado['ok'] ?? false) && $rutaRelativa !== '') {
            Storage::disk('public')->delete($rutaRelativa);
        }

        return $this->redirectConContexto($request, 'panel.configuracion', [
            'busqueda_usuario' => $data['busqueda_usuario'],
            'codigo_activo' => $codigoActivo,
        ])->with(
            ($resultado['ok'] ?? false) ? 'panel_message' : 'panel_error',
            $resultado['mensaje'] ?? 'No fue posible gestionar el estado del mantenimiento.'
        );
    }

    public function revertirUltimaAccion(Request $request, UsuarioRepository $usuarioRepo, ReversionEstadoMantenimientoService $reversionService): RedirectResponse
    {
        return $this->gestionarEstadoMantenimiento($request, $usuarioRepo, $reversionService);
    }

    private function resolverCodigoActivoConfiguracion(UsuarioRepository $usuarioRepo, array $data): ?array
    {
        $codigoActivoForzado = (int) ($data['codigo_activo'] ?? 0);
        $resolucion = null;
        if ($codigoActivoForzado > 0) {
            $resolucion = $usuarioRepo->resolverCodigoActivoPorBusqueda((string) $codigoActivoForzado);
        }

        if (($resolucion['ok'] ?? false) !== true) {
            $resolucion = $usuarioRepo->resolverCodigoActivoPorBusqueda((string) ($data['busqueda_usuario'] ?? ''));
        }

        return $resolucion;
    }

    private function debeBuscarUsuarioConfiguracion(string $busqueda): bool
    {
        $busqueda = trim($busqueda);
        if ($busqueda === '') {
            return false;
        }

        if (ctype_digit($busqueda)) {
            return strlen($busqueda) >= 3;
        }

        return mb_strlen($busqueda, 'UTF-8') >= 2;
    }

    private function resolverCoincidenciaAutomaticaConfiguracion(string $busqueda, array $coincidencias): ?int
    {
        $busqueda = trim($busqueda);
        if ($busqueda === '' || $coincidencias === []) {
            return null;
        }

        if (ctype_digit($busqueda)) {
            foreach ($coincidencias as $coincidencia) {
                if ((int) ($coincidencia['codigo_activo'] ?? 0) === (int) $busqueda) {
                    return (int) $coincidencia['codigo_activo'];
                }
            }
        }

        $normalizado = mb_strtolower($busqueda, 'UTF-8');
        $exactas = array_values(array_filter($coincidencias, static function (array $coincidencia) use ($normalizado): bool {
            return mb_strtolower((string) ($coincidencia['nombre'] ?? ''), 'UTF-8') === $normalizado;
        }));

        return count($exactas) === 1
            ? (int) ($exactas[0]['codigo_activo'] ?? 0)
            : null;
    }

    private function redirectConContexto(Request $request, string $rutaFallback, array $parametrosFallback = []): RedirectResponse
    {
        $returnUrl = trim((string) $request->input('return_url', ''));
        if ($this->esUrlInternaSegura($returnUrl)) {
            return redirect()->to($returnUrl);
        }

        return redirect()->route($rutaFallback, $parametrosFallback);
    }

    private function esUrlInternaSegura(string $url): bool
    {
        $url = trim($url);
        if ($url === '') {
            return false;
        }

        if (!str_starts_with($url, '/')) {
            return false;
        }

        if (str_starts_with($url, '//')) {
            return false;
        }

        if (isset($url[1]) && ($url[1] === '/' || $url[1] === '\\')) {
            return false;
        }

        $parsed = parse_url($url);
        if (!is_array($parsed)) {
            return false;
        }

        if (!empty($parsed['scheme']) || !empty($parsed['host']) || !empty($parsed['user']) || !empty($parsed['pass'])) {
            return false;
        }

        $path = (string) ($parsed['path'] ?? '');
        if ($path === '' || !str_starts_with($path, '/')) {
            return false;
        }

        return true;
    }

}

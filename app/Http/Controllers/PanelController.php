<?php

namespace App\Http\Controllers;

use App\Services\Legacy\MantenimientoRepository;
use App\Services\Legacy\PanelNavigationService;
use App\Services\Legacy\UsuarioRepository;
use App\Services\SemestreService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
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

    public function configuracion(Request $request, UsuarioRepository $usuarioRepo, MantenimientoRepository $repo): View
    {
        $busquedaUsuario = trim((string) $request->query('busqueda_usuario', ''));
        $cantidadReversiones = max(1, min(10, (int) $request->query('cantidad_reversiones', 1)));
        $usuarioResuelto = null;
        if ($busquedaUsuario !== '') {
            $resultadoBusqueda = $usuarioRepo->resolverCodigoActivoPorBusqueda($busquedaUsuario);
            if (($resultadoBusqueda['ok'] ?? false) === true) {
                $usuarioResuelto = $resultadoBusqueda;
            }
        }

        return view('panel.configuracion', [
            'usuario' => session('usuario_autenticado'),
            'busquedaUsuario' => $busquedaUsuario,
            'cantidadReversiones' => $cantidadReversiones,
            'usuarioResuelto' => $usuarioResuelto,
            'tecnicos' => $repo->listarTecnicosActivos(),
        ]);
    }

    public function proximos(Request $request, MantenimientoRepository $repo, SemestreService $semestreService): View
    {
        $periodo = $semestreService->desdeRequest($request);

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
        ]);
    }

    public function programados(Request $request, MantenimientoRepository $repo, SemestreService $semestreService): View
    {
        $periodo = $semestreService->desdeRequest($request);

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

    public function usuarios(Request $request, UsuarioRepository $repo): View
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
            'filtro_area_id' => ['nullable', 'integer', 'min:0'],
            'filtro_q_usuarios' => ['nullable', 'string'],
        ]);

        $resultado = $repo->crearUsuario(
            $data['nombre'],
            (int) $data['codigo_activo'],
            $data['serial_equipo'],
            !empty($data['area_id']) ? (int) $data['area_id'] : null,
            $data['tipo_equipo']
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
            'filtro_area_id' => ['nullable', 'integer', 'min:0'],
            'filtro_q_usuarios' => ['nullable', 'string'],
            'editar_usuario_id' => ['nullable', 'integer', 'min:0'],
        ]);

        $ok = $repo->actualizarUsuario(
            (int) $data['usuario_id'],
            $data['nombre'],
            (int) $data['codigo_activo'],
            $data['serial_equipo'],
            !empty($data['area_id']) ? (int) $data['area_id'] : null,
            $data['tipo_equipo']
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

    public function programarProximo(Request $request, MantenimientoRepository $repo): RedirectResponse
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

        $diaSemana = $fecha ? (int) $fecha->format('N') : 0;
        if ($diaSemana < 1 || $diaSemana > 5) {
            return $this->redirectConContexto($request, 'panel.proximos', $contexto)->with('panel_error', 'Debes seleccionar una fecha válida de lunes a viernes.');
        }

        $resultado = $repo->marcarComoProgramado((int) $data['mantenimiento_id'], $data['fecha_programada']);

        return $this->redirectConContexto($request, 'panel.proximos', $contexto)->with(
            $resultado['ok'] ? 'panel_message' : 'panel_error',
            $resultado['mensaje'] ?? 'No fue posible completar la acción.'
        );
    }

    public function marcarProgramadoComoHecho(Request $request, MantenimientoRepository $repo): RedirectResponse
    {
        $data = $request->validate([
            'mantenimiento_id' => ['required', 'integer', 'min:1'],
            'fecha_ejecucion' => ['required', 'date_format:Y-m-d'],
            'tecnico_id' => ['required', 'integer', 'min:1'],
            'observaciones' => ['nullable', 'string'],
            'constancia_pdf' => ['required', 'file', 'mimetypes:application/pdf', 'max:5120'],
        ]);

        $contexto = [
            'q_programados' => (string) $request->input('q_programados', ''),
            'programados_pagina' => max(1, (int) $request->input('programados_pagina', 1)),
            'programados_por_pagina' => in_array((int) $request->input('programados_por_pagina', 10), [10, 25, 50], true)
                ? (int) $request->input('programados_por_pagina', 10)
                : 10,
        ];

        $tecnico = $repo->obtenerNombreTecnicoPorId((int) $data['tecnico_id']);
        if ($tecnico === null) {
            return $this->redirectConContexto($request, 'panel.programados', $contexto)->with('panel_error', 'Debes seleccionar un técnico o responsable válido.');
        }

        /** @var UploadedFile $pdf */
        $pdf = $request->file('constancia_pdf');
        $nombreArchivo = sprintf(
            'constancia_%d_%s.pdf',
            (int) $data['mantenimiento_id'],
            Str::uuid()->toString()
        );
        $rutaRelativa = $pdf->storeAs('constancias', $nombreArchivo, 'public');

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
            'cantidad_reversiones' => (int) ($request->input('cantidad_reversiones', 1)),
        ])->with(
            $resultado['ok'] ? 'panel_message' : 'panel_error',
            $resultado['mensaje'] ?? 'No fue posible registrar el técnico.'
        );
    }

    public function revertirUltimaAccion(Request $request, UsuarioRepository $usuarioRepo, MantenimientoRepository $repo): RedirectResponse
    {
        $data = $request->validate([
            'busqueda_usuario' => ['required', 'string', 'max:150'],
            'cantidad_reversiones' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        $resolucion = $usuarioRepo->resolverCodigoActivoPorBusqueda($data['busqueda_usuario']);
        if (($resolucion['ok'] ?? false) !== true) {
            return $this->redirectConContexto($request, 'panel.configuracion', [
                'busqueda_usuario' => $data['busqueda_usuario'],
                'cantidad_reversiones' => (int) $data['cantidad_reversiones'],
            ])->with('panel_error', $resolucion['mensaje'] ?? 'No se pudo identificar el usuario.');
        }

        $codigoActivo = (int) ($resolucion['codigo_activo'] ?? 0);
        if ($codigoActivo <= 0) {
            return $this->redirectConContexto($request, 'panel.configuracion', [
                'busqueda_usuario' => $data['busqueda_usuario'],
                'cantidad_reversiones' => (int) $data['cantidad_reversiones'],
            ])->with('panel_error', 'No se encontró un código activo válido para el usuario.');
        }

        $resultado = $repo->revertirAccionesPorCodigoActivo($codigoActivo, (int) $data['cantidad_reversiones']);

        return $this->redirectConContexto($request, 'panel.configuracion', [
            'busqueda_usuario' => $data['busqueda_usuario'],
            'cantidad_reversiones' => (int) $data['cantidad_reversiones'],
        ])
            ->with(
                ($resultado['ok'] ?? false) ? 'panel_message' : 'panel_error',
                $resultado['mensaje'] ?? 'No fue posible revertir la acción.'
            );
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




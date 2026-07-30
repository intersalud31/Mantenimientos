<?php

namespace App\Http\Controllers;

use App\Support\LegacyAuthBridge;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (auth()->check()) {
            return redirect()->route('panel');
        }

        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'correo' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $correo = mb_strtolower(trim($data['correo']));
        try {
            $usuario = DB::table('usuarios_sistema')
                ->select('id', 'nombre', 'correo', 'password_hash', 'rol', 'estado')
                ->whereRaw('LOWER(correo) = ?', [$correo])
                ->first();
        } catch (QueryException) {
            return back()
                ->withInput($request->only('correo'))
                ->with('error_login', 'La base de datos no respondió. Reinicia MySQL en XAMPP e intenta nuevamente.');
        }

        if (!$usuario || (int) $usuario->estado !== 1) {
            return $this->falloLogin($request);
        }

        $passwordHash = (string) ($usuario->password_hash ?? '');

        if (
            !$this->esHashSeguro($passwordHash)
            || !Hash::check((string) $data['password'], $passwordHash)
        ) {
            return $this->falloLogin($request);
        }

        if (!Auth::loginUsingId((int) $usuario->id)) {
            return back()
                ->withInput($request->only('correo'))
                ->with('error_login', 'No fue posible iniciar sesión. Intenta nuevamente.');
        }

        $request->session()->regenerate();
        session([
            'usuario_autenticado' => LegacyAuthBridge::payloadFromUser(Auth::user()),
        ]);

        try {
            DB::table('usuarios_sistema')
                ->where('id', $usuario->id)
                ->update(['ultimo_acceso' => now()]);
        } catch (QueryException $exception) {
            report($exception);
        }

        return redirect()->route('panel');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        session()->forget('usuario_autenticado');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login.form');
    }

    private function falloLogin(Request $request): RedirectResponse
    {
        return back()
            ->withInput($request->only('correo'))
            ->with('error_login', 'Credenciales inválidas o usuario inactivo.');
    }

    private function esHashSeguro(string $passwordHash): bool
    {
        return password_get_info($passwordHash)['algo'] !== 0;
    }
}

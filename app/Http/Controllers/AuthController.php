<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Contracts\View\View;
use App\Support\LegacyAuthBridge;

class AuthController extends Controller
{
    public function create()
    {
        if (session()->has('usuario_autenticado') || auth()->check()) {
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
        $usuario = DB::table('usuarios_sistema')
            ->select('id', 'nombre', 'correo', 'password_hash', 'rol', 'estado')
            ->whereRaw('LOWER(correo) = ?', [$correo])
            ->first();

        if (!$usuario || (int) $usuario->estado !== 1 || !Hash::check($data['password'], (string) $usuario->password_hash)) {
            return back()
                ->withInput($request->only('correo'))
                ->with('error_login', 'Credenciales inválidas o usuario inactivo.');
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

        DB::table('usuarios_sistema')
            ->where('id', $usuario->id)
            ->update(['ultimo_acceso' => now()]);

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
}

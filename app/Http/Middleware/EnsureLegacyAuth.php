<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Support\LegacyAuthBridge;

class EnsureLegacyAuth
{
    public function handle(Request $request, Closure $next)
    {
        $legacyUser = session('usuario_autenticado');
        $authUser = Auth::user();

        if ($authUser === null && is_array($legacyUser)) {
            $legacyId = (int) ($legacyUser['id'] ?? 0);

            if ($legacyId <= 0 || !Auth::loginUsingId($legacyId)) {
                session()->forget('usuario_autenticado');
                return redirect()->route('login.form');
            }

            $authUser = Auth::user();
        }

        if ($authUser !== null) {
            session([
                'usuario_autenticado' => LegacyAuthBridge::payloadFromUser($authUser),
            ]);

            return $next($request);
        }

        if (!is_array($legacyUser)) {
            return redirect()->route('login.form');
        }

        return $next($request);
    }
}

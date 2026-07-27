<?php

namespace App\Support;

class LegacyAuthBridge
{
    /**
     * Convierte un usuario autenticado de Laravel al payload legacy usado por
     * la aplicación original.
     *
     * @param  object  $user
     * @return array<string, mixed>
     */
    public static function payloadFromUser(object $user): array
    {
        $identifier = method_exists($user, 'getAuthIdentifier')
            ? $user->getAuthIdentifier()
            : ($user->id ?? 0);

        return [
            'id' => (int) $identifier,
            'nombre' => (string) ($user->nombre ?? $user->name ?? ''),
            'correo' => (string) ($user->correo ?? $user->email ?? ''),
            'rol' => (string) ($user->rol ?? ''),
        ];
    }
}
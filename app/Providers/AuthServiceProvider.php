<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        RateLimiter::for('login', function (Request $request): array {
            $correo = mb_strtolower(trim((string) $request->input('correo', '')));
            $ip = (string) $request->ip();

            return [
                Limit::perMinute(5)->by($correo !== '' ? $correo . '|' . $ip : $ip),
            ];
        });

        RateLimiter::for('login-ip', function (Request $request): array {
            return [
                Limit::perMinute(15)->by((string) $request->ip()),
            ];
        });

        Gate::define('access-panel', function (User $user): bool {
            return $this->esAdministrador($user);
        });
    }

    private function esAdministrador(User $user): bool
    {
        $rol = strtolower(trim((string) ($user->rol ?? '')));

        return in_array($rol, ['admin', 'administrador', 'superadmin'], true);
    }
}

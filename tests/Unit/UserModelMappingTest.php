<?php

namespace Tests\Unit;

use App\Models\User;
use Tests\TestCase;

class UserModelMappingTest extends TestCase
{
    public function test_user_model_exposes_legacy_auth_fields(): void
    {
        $usuario = new User();
        $usuario->setRawAttributes([
            'nombre' => 'Ana Gómez',
            'correo' => 'ana@example.com',
            'password_hash' => 'hash-de-prueba',
        ], true);

        $this->assertSame('Ana Gómez', $usuario->name);
        $this->assertSame('ana@example.com', $usuario->email);
        $this->assertSame('hash-de-prueba', $usuario->getAuthPassword());
    }
}

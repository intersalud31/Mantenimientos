<?php

namespace Tests\Unit;

use App\Support\LegacyAuthBridge;
use Tests\TestCase;

class LegacyAuthBridgeTest extends TestCase
{
    public function test_payload_from_user_maps_legacy_fields_correctly(): void
    {
        $usuario = (object) [
            'id' => 7,
            'nombre' => 'María Pérez',
            'correo' => 'maria@example.com',
            'rol' => 'admin',
        ];

        $this->assertSame([
            'id' => 7,
            'nombre' => 'María Pérez',
            'correo' => 'maria@example.com',
            'rol' => 'admin',
        ], LegacyAuthBridge::payloadFromUser($usuario));
    }
}

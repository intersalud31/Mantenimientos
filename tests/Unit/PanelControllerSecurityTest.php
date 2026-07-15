<?php

namespace Tests\Unit;

use App\Http\Controllers\PanelController;
use ReflectionMethod;
use Tests\TestCase;

class PanelControllerSecurityTest extends TestCase
{
    public function test_return_url_interna_relativa_es_permitida(): void
    {
        $this->assertTrue($this->evaluarUrl('/panel/hechos'));
    }

    public function test_return_url_con_doble_barra_es_bloqueada(): void
    {
        $this->assertFalse($this->evaluarUrl('//google.com'));
    }

    public function test_return_url_absoluta_con_protocolo_es_bloqueada(): void
    {
        $this->assertFalse($this->evaluarUrl('https://dominio.com/panel'));
    }

    private function evaluarUrl(string $url): bool
    {
        $controller = new PanelController();
        $method = new ReflectionMethod($controller, 'esUrlInternaSegura');
        $method->setAccessible(true);

        return (bool) $method->invoke($controller, $url);
    }
}

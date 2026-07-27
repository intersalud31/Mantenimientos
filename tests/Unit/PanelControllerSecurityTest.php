<?php

namespace Tests\Unit;

use App\Http\Controllers\PanelController;
use App\Services\Legacy\PanelNavigationService;
use Tests\TestCase;

class PanelControllerSecurityTest extends TestCase
{
    public function test_return_url_interna_relativa_es_permitida(): void
    {
        $this->assertTrue($this->evaluarUrl('/panel/hechos'));
    }

    public function test_return_url_con_doble_barra_es_bloqueada(): void
    {
        $this->assertFalse($this->evaluarUrl('//sitio-malicioso.com'));
    }

    public function test_return_url_absoluta_con_protocolo_es_bloqueada(): void
    {
        $this->assertFalse($this->evaluarUrl('https://dominio.com/panel'));
    }

    public function test_return_url_javascript_es_bloqueada(): void
    {
        $this->assertFalse($this->evaluarUrl('javascript:alert(1)'));
    }

    public function test_return_url_data_uri_es_bloqueada(): void
    {
        $this->assertFalse($this->evaluarUrl('data:text/plain,malicioso'));
    }

    private function evaluarUrl(string $url): bool
    {
        $controller = new PanelController(app(PanelNavigationService::class));
        $callable = function (string $valor): bool {
            return $this->esUrlInternaSegura($valor);
        };

        $bound = $callable->bindTo($controller, PanelController::class);

        return (bool) $bound($url);
    }
}

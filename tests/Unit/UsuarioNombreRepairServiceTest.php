<?php

namespace Tests\Unit;

use App\Services\Legacy\UsuarioNombreRepairService;
use Tests\TestCase;

class UsuarioNombreRepairServiceTest extends TestCase
{
    /**
     * @dataProvider correccionesProvider
     */
    public function test_repara_nombres_legados_corruptos(string $entrada, string $esperado): void
    {
        $service = new UsuarioNombreRepairService();

        $this->assertSame($esperado, $service->reparar($entrada));
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function correccionesProvider(): array
    {
        return [
            ['Santiago Hern??ndez', 'Santiago Hernández'],
            ['Francy Elena S??nchez', 'Francy Elena Sánchez'],
            ['Stephany Garc??a', 'Stephany García'],
            ['M??nica Villafa??e', 'Mónica Villafañe'],
            ['Maria Fernanda Garc??a', 'María Fernanda García'],
            ['M??nica Luc??a Monta??o Cuenu', 'Mónica Lucía Montaño Cuenu'],
            ['Julian S??nchez', 'Julián Sánchez'],
            ['Diana Guti??rrez', 'Diana Gutiérrez'],
            ['Port??til Pruebas', 'Portátil Pruebas'],
            ['Recepci??n', 'Recepción'],
            ['Mar??a Paula Monta??o', 'María Paula Montaño'],
            ['Ver??nica D??az', 'Verónica Díaz'],
            ['Bibiana Rend??n', 'Bibiana Rendón'],
            ['Mar??a del Pilar Ibarra Rojas', 'María del Pilar Ibarra Rojas'],
            ['Facturaci??n', 'Facturación'],
            ['Jos?? Rodr??guez', 'José Rodríguez'],
            ['Damariz ??lvarez Chicu??', 'Damariz Álvarez Chicué'],
            ['Mar??a Fernanda Arias', 'María Fernanda Arias'],
            ['Natalia Rodr??guez', 'Natalia Rodríguez'],
            ['Vanessa Rodr??guez', 'Vanessa Rodríguez'],
            ['Mar??a Del Pilar Ibarra Rojas 2', 'María Del Pilar Ibarra Rojas 2'],
            ['DA??ADO', 'DAÑADO'],
            ['Lorena Brigitt G??mez', 'Lorena Brigitt Gómez'],
        ];
    }
}

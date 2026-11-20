<?php

namespace App\Services\Legacy;

class UsuarioNombreRepairService
{
    /** @var array<string, string> */
    private const CORRECCIONES = [
        'Santiago Hern??ndez' => 'Santiago Hernández',
        'Francy Elena S??nchez' => 'Francy Elena Sánchez',
        'Stephany Garc??a' => 'Stephany García',
        'M??nica Villafa??e' => 'Mónica Villafañe',
        'Maria Fernanda Garc??a' => 'Maria Fernanda García',
        'M??nica Luc??a Monta??o Cuenu' => 'Mónica Lucía Montaño Cuenu',
        'Julian S??nchez' => 'Julian Sánchez',
        'Diana Guti??rrez' => 'Diana Gutiérrez',
        'Port??til Pruebas' => 'Portátil Pruebas',
        'Recepci??n' => 'Recepción',
        'Mar??a Paula Monta??o' => 'María Paula Montaño',
        'Ver??nica D??az' => 'Verónica Díaz',
        'Bibiana Rend??n' => 'Bibiana Rendón',
        'Mar??a del Pilar Ibarra Rojas' => 'María del Pilar Ibarra Rojas',
        'Facturaci??n' => 'Facturación',
        'Jos?? Rodr??guez' => 'José Rodríguez',
        'Damariz ??lvarez Chicu??' => 'Damariz Álvarez Chicué',
        'Mar??a Fernanda Arias' => 'María Fernanda Arias',
        'Natalia Rodr??guez' => 'Natalia Rodríguez',
        'Vanessa Rodr??guez' => 'Vanessa Rodríguez',
        'Mar??a Del Pilar Ibarra Rojas 2' => 'María Del Pilar Ibarra Rojas 2',
        'DA??ADO' => 'DAÑADO',
        'Lorena Brigitt G??mez' => 'Lorena Brigitt Gómez',
        'Ana Ximena Guti??rrez' => 'Ana Ximena Gutiérrez',
    ];

    public function reparar(string $nombre): string
    {
        return self::CORRECCIONES[$nombre] ?? $nombre;
    }
}

<?php

namespace Tests\Unit;

use App\Services\Legacy\OrigenEquipoPresenter;
use Tests\TestCase;

class OrigenEquipoPresenterTest extends TestCase
{
    public function test_presenta_las_etiquetas_visibles_desde_los_valores_internos(): void
    {
        $presenter = app(OrigenEquipoPresenter::class);

        $this->assertSame('Origen', $presenter->fieldLabel());
        $this->assertSame('Inventario base', $presenter->label('normal'));
        $this->assertSame('Inventario base', $presenter->label('viejo'));
        $this->assertSame('Adquisición posterior', $presenter->label('nuevo'));
    }

    public function test_entrega_opciones_de_formulario_con_valores_internos_intactos(): void
    {
        $presenter = app(OrigenEquipoPresenter::class);

        $this->assertSame([
            ['value' => 'normal', 'label' => 'Inventario base'],
            ['value' => 'nuevo', 'label' => 'Adquisición posterior'],
        ], $presenter->options());
    }

    public function test_responde_de_forma_segura_ante_valores_desconocidos_o_vacios(): void
    {
        $presenter = app(OrigenEquipoPresenter::class);

        $this->assertSame('Sin origen', $presenter->label(null));
        $this->assertSame('Sin origen', $presenter->label(''));
        $this->assertSame('Sin origen', $presenter->label('legacy'));
        $this->assertSame('normal', $presenter->badgeVariant('legacy'));
        $this->assertSame('normal', $presenter->optionValue('viejo'));
    }
}

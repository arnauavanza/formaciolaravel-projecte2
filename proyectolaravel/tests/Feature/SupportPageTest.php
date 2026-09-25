<?php

namespace Tests\Feature;

use Tests\TestCase;

class SupportPageTest extends TestCase
{
    public function test_support_page_is_available(): void
    {
        $this->get('/support')
            ->assertOk()
            ->assertSee('Gestor de incidencias')
            ->assertSee('Iniciar sesión')
            ->assertSee('Nuevo ticket')
            ->assertSee('Comentarios')
            ->assertSee('Adjuntar archivo')
            ->assertSee('Descargar PDF');
    }
}

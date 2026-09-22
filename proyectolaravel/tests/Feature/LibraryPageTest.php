<?php

namespace Tests\Feature;

use Tests\TestCase;

class LibraryPageTest extends TestCase
{
    public function test_library_page_is_available(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('library-app')
            ->assertSee('Gestiona tu biblioteca');
    }
}

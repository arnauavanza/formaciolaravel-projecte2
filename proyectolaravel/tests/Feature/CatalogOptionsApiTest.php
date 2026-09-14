<?php

namespace Tests\Feature;

use App\Models\Genre;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogOptionsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_genres_can_be_listed_for_form_options(): void
    {
        Genre::factory()->create(['name' => 'Novela']);
        Genre::factory()->create(['name' => 'Historia']);

        $this->getJson('/api/genres')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Historia')
            ->assertJsonPath('data.1.name', 'Novela');
    }

    public function test_members_can_be_listed_for_loan_filters(): void
    {
        Member::factory()->create(['name' => 'Zoe']);
        Member::factory()->create(['name' => 'Ana']);

        $this->getJson('/api/members')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Ana')
            ->assertJsonPath('data.1.name', 'Zoe');
    }
}

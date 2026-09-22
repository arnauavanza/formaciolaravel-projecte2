<?php

namespace Tests\Feature;

use App\Models\Author;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authors_can_be_listed(): void
    {
        Author::factory()->count(2)->create();

        $response = $this->getJson('/api/authors');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'links',
                'meta',
            ]);
    }

    public function test_authors_can_be_paginated(): void
    {
        Author::factory()->count(2)->create();

        $this->getJson('/api/authors?per_page=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.per_page', 1);
    }

    public function test_author_list_per_page_is_validated(): void
    {
        $this->getJson('/api/authors?per_page=0')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page']);
    }

    public function test_author_can_be_created(): void
    {
        $response = $this->postJson('/api/authors', [
            'name' => 'Miguel de Cervantes',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.name', 'Miguel de Cervantes');

        $this->assertDatabaseHas('authors', [
            'name' => 'Miguel de Cervantes',
        ]);
    }

    public function test_author_name_is_required(): void
    {
        $response = $this->postJson('/api/authors', []);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_author_can_be_shown(): void
    {
        $author = Author::factory()->create();

        $this->getJson("/api/authors/{$author->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $author->id);
    }

    public function test_author_can_be_updated(): void
    {
        $author = Author::factory()->create();

        $this->patchJson("/api/authors/{$author->id}", [
            'name' => 'Nombre actualizado',
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Nombre actualizado');

        $this->assertDatabaseHas('authors', [
            'id' => $author->id,
            'name' => 'Nombre actualizado',
        ]);
    }

    public function test_author_without_loans_can_be_deleted(): void
    {
        $author = Author::factory()->create();

        $this->deleteJson("/api/authors/{$author->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('authors', [
            'id' => $author->id,
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_members_can_be_listed(): void
    {
        Member::factory()->create(['name' => 'Zoe']);
        Member::factory()->create(['name' => 'Ana']);

        $this->getJson('/api/members')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Ana')
            ->assertJsonPath('data.1.name', 'Zoe');
    }

    public function test_member_can_be_created(): void
    {
        $this->postJson('/api/members', [
            'name' => 'Ana García',
            'phone' => '600123123',
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Ana García')
            ->assertJsonPath('data.phone', '600123123');

        $this->assertDatabaseHas('members', [
            'name' => 'Ana García',
            'phone' => '600123123',
        ]);
    }

    public function test_member_name_is_required(): void
    {
        $this->postJson('/api/members', [
            'phone' => '600123123',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_member_can_be_shown(): void
    {
        $member = Member::factory()->create();

        $this->getJson("/api/members/{$member->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $member->id);
    }

    public function test_member_can_be_updated(): void
    {
        $member = Member::factory()->create();

        $this->patchJson("/api/members/{$member->id}", [
            'name' => 'Nombre actualizado',
            'phone' => '611222333',
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Nombre actualizado')
            ->assertJsonPath('data.phone', '611222333');

        $this->assertDatabaseHas('members', [
            'id' => $member->id,
            'name' => 'Nombre actualizado',
            'phone' => '611222333',
        ]);
    }

    public function test_member_without_loans_can_be_deleted(): void
    {
        $member = Member::factory()->create();

        $this->deleteJson("/api/members/{$member->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('members', [
            'id' => $member->id,
        ]);
    }

    public function test_member_with_loans_cannot_be_deleted(): void
    {
        $member = Member::factory()->create();

        Loan::factory()->create([
            'member_id' => $member->id,
        ]);

        $this->deleteJson("/api/members/{$member->id}")
            ->assertStatus(409);

        $this->assertDatabaseHas('members', [
            'id' => $member->id,
        ]);
    }
}

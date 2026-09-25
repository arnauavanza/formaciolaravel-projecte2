<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AttachmentApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_customer_can_upload_an_attachment(): void
    {
        Storage::fake('local');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $ticket = Ticket::factory()
            ->for($customer, 'customer')
            ->create();

        $comment = $ticket->comments()->create([
            'user_id' => $customer->id,
            'body' => 'Please see the attached file.',
        ]);

        $this->authenticate($customer);

        $response = $this->post(
            "/api/comments/{$comment->id}/attachments",
            [
                'file' => UploadedFile::fake()->create(
                    'manual.pdf',
                    100,
                    'application/pdf',
                ),
            ],
        );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'data.original_name',
                'manual.pdf'
            );

        $attachment = $comment->attachments()->first();

        Storage::disk('local')->assertExists(
            $attachment->path
        );
    }

    public function test_customer_cannot_upload_to_another_users_comment(): void
    {
        Storage::fake('local');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $otherTicket = Ticket::factory()->create();

        $comment = $otherTicket->comments()->create([
            'user_id' => $otherTicket->customer_id,
            'body' => 'Private comment.',
        ]);

        $this->authenticate($customer);

        $this->post(
            "/api/comments/{$comment->id}/attachments",
            [
                'file' => UploadedFile::fake()->create(
                    'private.pdf',
                    100,
                    'application/pdf',
                ),
            ],
        )
            ->assertForbidden();
    }

    public function test_participant_cannot_upload_to_another_participants_comment(): void
    {
        Storage::fake('local');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $agent = User::factory()->create();
        $agent->assignRole('agent');

        $ticket = Ticket::factory()
            ->for($customer, 'customer')
            ->for($agent, 'agent')
            ->create();

        $comment = $ticket->comments()->create([
            'user_id' => $customer->id,
            'body' => 'Customer comment.',
        ]);

        $this->authenticate($agent);

        $this->post(
            "/api/comments/{$comment->id}/attachments",
            [
                'file' => UploadedFile::fake()->create(
                    'agent.pdf',
                    100,
                    'application/pdf',
                ),
            ],
        )
            ->assertForbidden();
    }

    public function test_attachment_can_be_downloaded(): void
    {
        Storage::fake('local');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $ticket = Ticket::factory()
            ->for($customer, 'customer')
            ->create();

        $comment = $ticket->comments()->create([
            'user_id' => $customer->id,
            'body' => 'Download this file.',
        ]);

        $this->authenticate($customer);

        $this->post(
            "/api/comments/{$comment->id}/attachments",
            [
                'file' => UploadedFile::fake()->create(
                    'document.pdf',
                    100,
                    'application/pdf',
                ),
            ],
        )->assertCreated();

        $attachment = $comment->attachments()->first();

        $this->get(
            "/api/comments/{$comment->id}/attachments/{$attachment->id}/download"
        )
            ->assertOk()
            ->assertHeader(
                'content-type',
                'application/pdf'
            );
    }

    private function authenticate(User $user): void
    {
        Sanctum::actingAs($user, [
            'tickets.read',
            'comments.create',
        ]);
    }
}

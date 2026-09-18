<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('comments', 'ticket_id')) {
            Schema::table('comments', function (Blueprint $table): void {
                $table->foreignId('ticket_id')
                    ->after('id')
                    ->constrained('tickets')
                    ->cascadeOnDelete();
            });
        }

        if (! Schema::hasColumn('comments', 'user_id')) {
            Schema::table('comments', function (Blueprint $table): void {
                $table->foreignId('user_id')
                    ->after('ticket_id')
                    ->constrained('users')
                    ->restrictOnDelete();
            });
        }

        if (! Schema::hasColumn('comments', 'body')) {
            Schema::table('comments', function (Blueprint $table): void {
                $table->text('body')->after('user_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('comments', 'ticket_id')) {
            Schema::table('comments', function (Blueprint $table): void {
                $table->dropForeign(['ticket_id']);
                $table->dropColumn('ticket_id');
            });
        }

        if (Schema::hasColumn('comments', 'user_id')) {
            Schema::table('comments', function (Blueprint $table): void {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            });
        }

        if (Schema::hasColumn('comments', 'body')) {
            Schema::table('comments', function (Blueprint $table): void {
                $table->dropColumn('body');
            });
        }
    }
};

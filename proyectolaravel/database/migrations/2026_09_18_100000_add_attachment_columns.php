<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('attachments', 'comment_id')) {
            Schema::table('attachments', function (Blueprint $table): void {
                $table->foreignId('comment_id')
                    ->after('id')
                    ->constrained('comments')
                    ->cascadeOnDelete();
            });
        }

        if (! Schema::hasColumn('attachments', 'disk')) {
            Schema::table('attachments', function (Blueprint $table): void {
                $table->string('disk')
                    ->default('local')
                    ->after('comment_id');
            });
        }

        if (! Schema::hasColumn('attachments', 'path')) {
            Schema::table('attachments', function (Blueprint $table): void {
                $table->string('path')->after('disk');
            });
        }

        if (! Schema::hasColumn('attachments', 'original_name')) {
            Schema::table('attachments', function (Blueprint $table): void {
                $table->string('original_name')->after('path');
            });
        }

        if (! Schema::hasColumn('attachments', 'mime_type')) {
            Schema::table('attachments', function (Blueprint $table): void {
                $table->string('mime_type')
                    ->nullable()
                    ->after('original_name');
            });
        }

        if (! Schema::hasColumn('attachments', 'size')) {
            Schema::table('attachments', function (Blueprint $table): void {
                $table->unsignedBigInteger('size')->after('mime_type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('attachments', 'comment_id')) {
            Schema::table('attachments', function (Blueprint $table): void {
                $table->dropForeign(['comment_id']);
                $table->dropColumn('comment_id');
            });
        }

        foreach ([
            'disk',
            'path',
            'original_name',
            'mime_type',
            'size',
        ] as $column) {
            if (Schema::hasColumn('attachments', $column)) {
                Schema::table('attachments', function (Blueprint $table) use ($column): void {
                    $table->dropColumn($column);
                });
            }
        }
    }
};

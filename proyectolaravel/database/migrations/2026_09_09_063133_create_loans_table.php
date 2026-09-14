<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->restrictOnDelete();
            $table->foreignId('book_id')->constrained()->restrictOnDelete();
            $table->timestamp('borrowed_at');
            $table->timestamp('due_at');
            $table->timestamp('returned_at')->nullable();
            $table->timestamps();

            $table->index('returned_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};

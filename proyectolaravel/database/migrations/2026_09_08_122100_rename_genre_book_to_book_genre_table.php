<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('genre_book') && ! Schema::hasTable('book_genre')) {
            Schema::rename('genre_book', 'book_genre');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('book_genre') && ! Schema::hasTable('genre_book')) {
            Schema::rename('book_genre', 'genre_book');
        }
    }
};

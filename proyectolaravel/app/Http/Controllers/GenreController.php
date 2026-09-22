<?php

namespace App\Http\Controllers;

use App\Http\Resources\GenreResource;
use App\Models\Genre;

class GenreController extends Controller
{
    public function index()
    {
        $genres = Genre::query()
            ->orderBy('name')
            ->get();

        return GenreResource::collection($genres);
    }
}

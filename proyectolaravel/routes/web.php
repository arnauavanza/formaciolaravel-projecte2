<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'library')->name('library');
Route::view('/support', 'support')->name('support');

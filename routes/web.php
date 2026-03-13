<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'index')->name('home');

require __DIR__.'/settings.php';

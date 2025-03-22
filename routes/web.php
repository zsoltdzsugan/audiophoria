<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::view('/', 'home');

Volt::route('/rooms/{room}', 'pages.rooms.show')->name('rooms.show');

require __DIR__ . '/auth.php';

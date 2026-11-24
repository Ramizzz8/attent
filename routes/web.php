<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified', 'empresa.panel'])->group(function () {
    Route::inertia('panel', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';

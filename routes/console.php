<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cierra cada noche las incidencias resueltas hace más de 7 días.
Schedule::command('attent:cerrar-resueltos')->dailyAt('03:00');

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Datos de demostración: una empresa por sector con incidencias en todos los estados.
 * Credenciales: admin@<slug>.test / empleado1@<slug>.test / empleado2@<slug>.test, contraseña "password".
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Al cargar la demo no se envían emails ni avisos en tiempo real (Reverb puede no estar en marcha aún).
        config(['queue.default' => 'sync', 'mail.default' => 'array', 'broadcasting.default' => 'null']);

        try {
            $this->call([TallerDemoSeeder::class, FincasDemoSeeder::class, SoporteItDemoSeeder::class]);
        } finally {
            Carbon::setTestNow();
        }
    }
}

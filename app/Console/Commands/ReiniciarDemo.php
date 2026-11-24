<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

#[Signature('atiende:reiniciar-demo')]
#[Description('Devuelve la demo pública a su estado inicial (datos y archivos subidos)')]
class ReiniciarDemo extends Command
{
    public function handle(): int
    {
        // Borra TODOS los datos: solo se permite en la demo o fuera de producción.
        if (app()->isProduction() && ! config('atiende.demo')) {
            $this->error('Solo se puede reiniciar con DEMO_MODE activo.');

            return self::FAILURE;
        }

        $this->call('migrate', ['--force' => true]);

        // Vacía todas las tablas (menos el registro de migraciones) y vuelve a sembrar la demo.
        Schema::withoutForeignKeyConstraints(function () {
            foreach (Schema::getTableListing(schemaQualified: false) as $tabla) {
                if ($tabla !== 'migrations') {
                    DB::table($tabla)->delete();
                }
            }
        });
        $this->call('db:seed', ['--force' => true]);

        Storage::disk('local')->deleteDirectory('adjuntos');
        Storage::disk('public')->deleteDirectory('logos');

        $this->info('Demo reiniciada.');

        return self::SUCCESS;
    }
}

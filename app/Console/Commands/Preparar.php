<?php

namespace App\Console\Commands;

use App\Models\Empresa;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('atiende:preparar')]
#[Description('Aplica las migraciones y, si la base de datos está vacía, carga los datos de demostración')]
class Preparar extends Command
{
    public function handle(): int
    {
        $this->call('migrate', ['--force' => true]);

        if (Empresa::count() === 0) {
            $this->call('db:seed', ['--force' => true]);
        }

        return self::SUCCESS;
    }
}

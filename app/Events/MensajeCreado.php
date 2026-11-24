<?php

namespace App\Events;

use App\Models\Mensaje;
use Illuminate\Foundation\Events\Dispatchable;

class MensajeCreado
{
    use Dispatchable;

    public function __construct(public Mensaje $mensaje) {}
}

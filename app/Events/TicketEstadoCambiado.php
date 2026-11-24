<?php

namespace App\Events;

use App\Models\Cliente;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class TicketEstadoCambiado
{
    use Dispatchable;

    public function __construct(
        public Ticket $ticket,
        public bool $sistema,
        public User|Cliente|null $actor = null,
        public ?string $origen = null,
    ) {}
}

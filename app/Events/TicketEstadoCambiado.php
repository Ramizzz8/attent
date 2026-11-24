<?php

namespace App\Events;

use App\Models\Ticket;
use Illuminate\Foundation\Events\Dispatchable;

class TicketEstadoCambiado
{
    use Dispatchable;

    public function __construct(public Ticket $ticket, public bool $sistema) {}
}

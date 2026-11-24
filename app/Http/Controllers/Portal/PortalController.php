<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use Inertia\Inertia;
use Inertia\Response;

class PortalController extends Controller
{
    public function inicio(Empresa $empresa): Response
    {
        return Inertia::render('portal/inicio');
    }

    public function pendiente(Empresa $empresa): Response
    {
        return Inertia::render('portal/pendiente', ['enlaceDemo' => session('enlaceDemo')]);
    }
}

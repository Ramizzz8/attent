<?php

namespace App\Taller;

use App\Models\Presupuesto;
use App\Models\Ticket;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

final class PdfPresupuesto
{
    /** Genera el PDF de la versión pedida del presupuesto (404 si no existe). */
    public static function descargar(Ticket $ticket, int $version): Response
    {
        /** @var Presupuesto $presupuesto */
        $presupuesto = $ticket->presupuestos()->where('version', $version)->with('lineas')->firstOrFail();
        $empresa = $ticket->empresa;

        // El logo se incrusta como data URI: DomPDF no necesita acceder a la red.
        $logo = null;
        if ($empresa->logo_path && Storage::disk('public')->exists($empresa->logo_path)) {
            $logo = 'data:'.Storage::disk('public')->mimeType($empresa->logo_path).';base64,'
                .base64_encode(Storage::disk('public')->get($empresa->logo_path));
        }

        return Pdf::loadView('pdf.presupuesto', compact('ticket', 'presupuesto', 'empresa', 'logo'))
            ->download("presupuesto-{$ticket->numero}-v{$version}.pdf");
    }
}

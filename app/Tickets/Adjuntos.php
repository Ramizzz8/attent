<?php

namespace App\Tickets;

use App\Models\Adjunto;
use App\Models\Mensaje;
use App\Models\Ticket;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Archivos adjuntos: reglas comunes, guardado en disco privado y descarga. */
final class Adjuntos
{
    /** @return array<string, list<string>> */
    public static function reglas(): array
    {
        return [
            'adjuntos' => ['nullable', 'array', 'max:5'],
            // "mimes" comprueba el contenido real del archivo, no solo la extensión: un SVG o un .exe renombrado no pasa.
            'adjuntos.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ];
    }

    /** @param list<UploadedFile> $archivos */
    public static function guardar(Ticket $ticket, ?Mensaje $mensaje, array $archivos): void
    {
        foreach ($archivos as $archivo) {
            $ruta = $archivo->store("adjuntos/{$ticket->empresa_id}/{$ticket->id}", 'local');

            Adjunto::create([
                'ticket_id' => $ticket->id,
                'mensaje_id' => $mensaje?->id,
                'ruta' => $ruta,
                'nombre' => mb_substr($archivo->getClientOriginalName(), 0, 200),
                'mime' => $archivo->getMimeType() ?? 'application/octet-stream',
                'tamano' => $archivo->getSize(),
            ]);
        }
    }

    public static function descargar(Adjunto $adjunto): StreamedResponse
    {
        return Storage::disk('local')->response($adjunto->ruta, $adjunto->nombre, [
            'Content-Type' => $adjunto->mime,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}

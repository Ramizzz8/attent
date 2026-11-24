<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Presupuesto #{{ $ticket->numero }}-v{{ $presupuesto->version }}</title>
    <style>
        @page { margin: 32px 40px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        .cabecera { border-bottom: 3px solid {{ $empresa->color_primario }}; padding-bottom: 10px; margin-bottom: 18px; }
        .cabecera table { width: 100%; }
        .empresa { font-size: 18px; font-weight: bold; color: {{ $empresa->color_primario }}; }
        h1 { font-size: 15px; margin: 0 0 4px; }
        .gris { color: #6b7280; }
        .bloques { width: 100%; margin-bottom: 16px; }
        .bloques td { width: 50%; vertical-align: top; padding: 8px; background: #f3f4f6; }
        table.lineas { width: 100%; border-collapse: collapse; }
        table.lineas th { text-align: left; border-bottom: 1px solid #d1d5db; padding: 6px 4px; font-size: 10px; color: #6b7280; }
        table.lineas td { border-bottom: 1px solid #f3f4f6; padding: 6px 4px; }
        .num, table.lineas .num { text-align: right; }
        .totales { width: 45%; margin-left: 55%; margin-top: 12px; border-collapse: collapse; }
        .totales td { padding: 4px; }
        .total td { font-weight: bold; font-size: 13px; border-top: 2px solid #1f2937; }
        .pie { margin-top: 30px; font-size: 9px; color: #6b7280; }
        .estado { display: inline-block; padding: 2px 8px; border: 1px solid #6b7280; border-radius: 10px; font-size: 10px; }
    </style>
</head>
<body>
    <div class="cabecera">
        <table>
            <tr>
                <td>
                    @if ($logo)
                        <img src="{{ $logo }}" style="height: 40px;" alt="">
                    @endif
                    <div class="empresa">{{ $empresa->nombre }}</div>
                </td>
                <td class="num">
                    <h1>Presupuesto de reparación</h1>
                    <div class="gris">Incidencia #{{ $ticket->numero }} · versión {{ $presupuesto->version }}</div>
                    <div class="gris">{{ $presupuesto->created_at->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}</div>
                    <span class="estado">{{ ['pendiente' => 'Pendiente de aprobación', 'aprobado' => 'Aprobado', 'rechazado' => 'Rechazado'][$presupuesto->estado] }}</span>
                </td>
            </tr>
        </table>
    </div>

    <table class="bloques">
        <tr>
            <td>
                <strong>Cliente</strong><br>
                {{ $ticket->cliente->nombre }}<br>
                <span class="gris">{{ $ticket->cliente->email }}@if ($ticket->cliente->telefono) · {{ $ticket->cliente->telefono }}@endif</span>
            </td>
            <td>
                <strong>Vehículo</strong><br>
                @if ($ticket->vehiculo)
                    {{ $ticket->vehiculo->matricula }} · {{ trim($ticket->vehiculo->marca.' '.$ticket->vehiculo->modelo) }}<br>
                    @if ($ticket->vehiculo->km)<span class="gris">{{ number_format($ticket->vehiculo->km, 0, ',', '.') }} km</span>@endif
                @else
                    {{ $ticket->datos['matricula'] ?? '—' }}
                @endif
            </td>
        </tr>
    </table>

    <p><strong>Motivo:</strong> {{ $ticket->asunto }}</p>

    <table class="lineas">
        <thead>
            <tr>
                <th>Concepto</th>
                <th>Tipo</th>
                <th class="num">Cantidad</th>
                <th class="num">Precio</th>
                <th class="num">Importe</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($presupuesto->lineas as $l)
                <tr>
                    <td>{{ $l->concepto }}</td>
                    <td>{{ $l->tipo === 'pieza' ? 'Pieza' : 'Mano de obra' }}</td>
                    <td class="num">{{ number_format((float) $l->cantidad, 2, ',', '.') }}</td>
                    <td class="num">{{ number_format((float) $l->precio_unitario, 2, ',', '.') }} €</td>
                    <td class="num">{{ number_format((float) $l->cantidad * (float) $l->precio_unitario, 2, ',', '.') }} €</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totales">
        <tr><td>Base imponible</td><td class="num">{{ number_format($presupuesto->base(), 2, ',', '.') }} €</td></tr>
        <tr><td>IVA ({{ rtrim(rtrim(number_format((float) $presupuesto->iva_pct, 2, ',', ''), '0'), ',') }} %)</td><td class="num">{{ number_format($presupuesto->iva(), 2, ',', '.') }} €</td></tr>
        <tr class="total"><td>Total</td><td class="num">{{ number_format($presupuesto->total(), 2, ',', '.') }} €</td></tr>
    </table>

    <p class="pie">
        Presupuesto válido durante 15 días. No se iniciará ninguna reparación sin la aprobación del cliente.
        Puede aprobarlo o rechazarlo desde {{ url('/'.$empresa->slug) }}.
    </p>
</body>
</html>

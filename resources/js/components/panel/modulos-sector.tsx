import { usePage } from '@inertiajs/react';
import type { ComponentProps } from 'react';
import { ModuloFincasPanel } from '@/components/sectores/fincas/modulo-panel';
import { ModuloSoporteItPanel } from '@/components/sectores/soporte-it/modulo-panel';
import { ModuloTallerPanel } from '@/components/sectores/taller/modulo-panel';
import type { TicketPanel } from '@/pages/panel/ticket';

/** Paneles propios de cada sector dentro del detalle del ticket (presupuestos, proveedor, triaje…). */
export function ModulosSector({ ticket }: { ticket: TicketPanel }) {
    const sector = (usePage().props.empresaPanel as { sector: string } | null)?.sector;

    switch (sector) {
        case 'taller':
            return <ModuloTallerPanel {...(ticket as unknown as ComponentProps<typeof ModuloTallerPanel>)} numero={ticket.numero} />;
        case 'fincas':
            return <ModuloFincasPanel {...(ticket as unknown as ComponentProps<typeof ModuloFincasPanel>)} numero={ticket.numero} />;
        case 'soporte_it':
            return <ModuloSoporteItPanel {...(ticket as unknown as ComponentProps<typeof ModuloSoporteItPanel>)} numero={ticket.numero} />;
        default:
            return null;
    }
}

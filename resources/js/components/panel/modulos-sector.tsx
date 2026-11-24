import { usePage } from '@inertiajs/react';
import { ModuloTallerPanel } from '@/components/sectores/taller/modulo-panel';
import type { PresupuestoVista } from '@/components/sectores/taller/presupuesto';
import type { TicketPanel } from '@/pages/panel/ticket';

/** Paneles propios de cada sector dentro del detalle del ticket (presupuestos, proveedor, triaje…). */
export function ModulosSector({ ticket }: { ticket: TicketPanel }) {
    const sector = (usePage().props.empresaPanel as { sector: string } | null)?.sector;

    switch (sector) {
        case 'taller':
            return (
                <ModuloTallerPanel
                    numero={ticket.numero}
                    vehiculo={ticket.vehiculo as Parameters<typeof ModuloTallerPanel>[0]['vehiculo']}
                    presupuestos={(ticket.presupuestos as PresupuestoVista[]) ?? []}
                    puedePresupuestar={Boolean(ticket.puedePresupuestar)}
                />
            );
        default:
            return null;
    }
}

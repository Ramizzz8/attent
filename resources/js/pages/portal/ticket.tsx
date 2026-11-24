import { Head } from '@inertiajs/react';
import { Conversacion, ListaAdjuntos } from '@/components/conversacion';
import type { AdjuntoResumen, MensajeVista } from '@/components/conversacion';
import { EstadoPill, fecha } from '@/components/portal/estado-pill';
import { PresupuestoPortal } from '@/components/sectores/taller/presupuesto-portal';
import type { PresupuestoVista } from '@/components/sectores/taller/presupuesto';
import { usePortalUrl } from '@/hooks/use-portal';

export type TicketPortal = {
    id: number;
    numero: number;
    asunto: string;
    descripcion: string;
    categoria: string;
    estado: string;
    estadoEtiqueta: string;
    datos: Record<string, string | number>;
    creado: string;
    historial: { estado: string; fecha: string }[];
    adjuntos: AdjuntoResumen[];
    mensajes: MensajeVista[];
    presupuesto?: PresupuestoVista | null;
};

export default function Ticket({ ticket }: { ticket: TicketPortal }) {
    const url = usePortalUrl();

    return (
        <>
            <Head title={`Incidencia #${ticket.numero}`} />
            <div className="space-y-6">
                <section className="rounded-xl border bg-background p-6 shadow-sm">
                    <div className="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p className="text-sm text-muted-foreground">
                                Incidencia #{ticket.numero} · {ticket.categoria} · {fecha(ticket.creado)}
                            </p>
                            <h1 className="mt-1 text-2xl font-semibold">{ticket.asunto}</h1>
                        </div>
                        <EstadoPill>{ticket.estadoEtiqueta}</EstadoPill>
                    </div>
                    <p className="mt-4 whitespace-pre-line">{ticket.descripcion}</p>
                    {Object.keys(ticket.datos).length > 0 && (
                        <dl className="mt-4 grid grid-cols-2 gap-2 text-sm sm:grid-cols-4">
                            {Object.entries(ticket.datos).map(([k, v]) => (
                                <div key={k}>
                                    <dt className="text-muted-foreground capitalize">{k}</dt>
                                    <dd className="font-medium">{v}</dd>
                                </div>
                            ))}
                        </dl>
                    )}
                    <ListaAdjuntos adjuntos={ticket.adjuntos} urlBase={url('adjuntos')} />
                </section>

                {ticket.presupuesto && <PresupuestoPortal numero={ticket.numero} presupuesto={ticket.presupuesto} />}

                <Conversacion
                    mensajes={ticket.mensajes}
                    urlEnviar={url(`tickets/${ticket.numero}/mensajes`)}
                    urlAdjuntos={url('adjuntos')}
                    lado="cliente"
                />

                {ticket.historial.length > 0 && (
                    <section className="rounded-xl border bg-background p-6 shadow-sm">
                        <h2 className="mb-3 font-semibold">Seguimiento</h2>
                        <ol className="space-y-2 border-l pl-4">
                            {ticket.historial.map((h, i) => (
                                <li key={i} className="text-sm">
                                    <span className="font-medium">{h.estado}</span>{' '}
                                    <span className="text-muted-foreground">· {fecha(h.fecha)}</span>
                                </li>
                            ))}
                        </ol>
                    </section>
                )}
            </div>
        </>
    );
}

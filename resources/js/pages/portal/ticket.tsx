import { Head } from '@inertiajs/react';
import { EstadoPill, fecha } from '@/components/portal/estado-pill';

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
};

export default function Ticket({ ticket }: { ticket: TicketPortal }) {
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
                </section>

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

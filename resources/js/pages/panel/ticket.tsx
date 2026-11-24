import { Head, router, usePage } from '@inertiajs/react';
import { ArrowLeft, Undo2 } from 'lucide-react';
import { useState } from 'react';
import { Conversacion, ListaAdjuntos } from '@/components/conversacion';
import type { AdjuntoResumen, MensajeVista } from '@/components/conversacion';
import InputError from '@/components/input-error';
import { ModulosSector } from '@/components/panel/modulos-sector';
import { EstadoBadge, PrioridadBadge, relativo, SlaBadge } from '@/components/panel/insignias';
import { claseSelect } from '@/components/portal/campo';
import { fecha } from '@/components/portal/estado-pill';
import { Estrellas } from '@/components/portal/valorar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useAvisosPanel } from '@/hooks/use-avisos-panel';
import { dashboard } from '@/routes';

export type TicketPanel = {
    id: number;
    numero: number;
    asunto: string;
    descripcion: string;
    categoria: string;
    estado: string;
    estadoEtiqueta: string;
    prioridad: string;
    datos: Record<string, string | number>;
    verificado: boolean;
    creado: string;
    sla: string | null;
    slaVence: string | null;
    primeraRespuestaVence: string | null;
    primeraRespuesta: string | null;
    cliente: { nombre: string; email: string; telefono: string | null };
    asignadoId: number | null;
    valoracion: number | null;
    valoracionComentario: string | null;
    adjuntos: AdjuntoResumen[];
    mensajes: MensajeVista[];
    historial: { id: number; tipo: string; de: string | null; a: string | null; motivo: string | null; datos: Record<string, unknown> | null; actor: string; fecha: string }[];
    [modulo: string]: unknown;
};

type Destino = { estado: string; etiqueta: string; retroceso: boolean };

function Tarjeta({ titulo, children }: { titulo: string; children: React.ReactNode }) {
    return (
        <section className="rounded-xl border bg-background p-4">
            <h2 className="mb-3 text-sm font-semibold">{titulo}</h2>
            {children}
        </section>
    );
}

function describirEvento(e: TicketPanel['historial'][number]): string {
    switch (e.tipo) {
        case 'creado':
            return 'Incidencia creada';
        case 'verificado':
            return 'Email del cliente confirmado';
        case 'estado':
            return `${e.de} → ${e.a}`;
        case 'asignado':
            return e.datos?.a ? `Asignada a ${e.datos.a}` : 'Asignación retirada';
        case 'prioridad':
            return `Prioridad: ${e.datos?.de ?? ''} → ${e.datos?.a ?? ''}`;
        case 'reabierto':
            return 'Reabierta por el cliente';
        case 'proveedor':
            return `Asignada al proveedor ${e.datos?.proveedor ?? ''}`;
        default:
            return e.tipo;
    }
}

export default function TicketPanelPage({ ticket, destinos, empleados }: { ticket: TicketPanel; destinos: Destino[]; empleados: { id: number; name: string }[] }) {
    const { errors } = usePage().props as { errors: Record<string, string> };
    const [motivo, setMotivo] = useState('');
    const [pidiendoMotivo, setPidiendoMotivo] = useState<Destino | null>(null);
    const base = `/panel/tickets/${ticket.numero}`;

    // Tiempo real: si otro empleado o el cliente cambia este ticket, se refresca solo.
    useAvisosPanel((aviso) => {
        if (aviso.numero === ticket.numero) {
            router.reload({ only: ['ticket', 'destinos'] });
        }
    });

    function cambiarEstado(d: Destino, conMotivo?: string) {
        if (d.retroceso && !conMotivo) {
            setPidiendoMotivo(d);

            return;
        }
        router.post(`${base}/estado`, { destino: d.estado, motivo: conMotivo ?? null }, { preserveScroll: true, onSuccess: () => setPidiendoMotivo(null) });
    }

    return (
        <>
            <Head title={`#${ticket.numero} ${ticket.asunto}`} />
            <div className="grid gap-4 p-4 lg:grid-cols-[1fr_320px]">
                <div className="space-y-4">
                    <button type="button" onClick={() => history.back()} className="flex items-center gap-1 text-sm text-muted-foreground hover:underline">
                        <ArrowLeft className="h-4 w-4" /> Volver
                    </button>
                    <section className="rounded-xl border bg-background p-5">
                        <div className="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    #{ticket.numero} · {ticket.categoria} · {fecha(ticket.creado)}
                                </p>
                                <h1 className="text-xl font-semibold">{ticket.asunto}</h1>
                            </div>
                            <div className="flex gap-2">
                                <PrioridadBadge prioridad={ticket.prioridad} />
                                <EstadoBadge>{ticket.estadoEtiqueta}</EstadoBadge>
                            </div>
                        </div>
                        {!ticket.verificado && (
                            <p className="mt-3 rounded bg-amber-50 p-2 text-sm text-amber-800 dark:bg-amber-950 dark:text-amber-200">
                                El cliente aún no ha confirmado su email.
                            </p>
                        )}
                        <p className="mt-3 whitespace-pre-line">{ticket.descripcion}</p>
                        {Object.keys(ticket.datos).length > 0 && (
                            <dl className="mt-3 grid grid-cols-2 gap-2 text-sm sm:grid-cols-4">
                                {Object.entries(ticket.datos).map(([k, v]) => (
                                    <div key={k}>
                                        <dt className="text-muted-foreground capitalize">{k}</dt>
                                        <dd className="font-medium">{v}</dd>
                                    </div>
                                ))}
                            </dl>
                        )}
                        <ListaAdjuntos adjuntos={ticket.adjuntos} urlBase="/panel/adjuntos" />
                    </section>

                    <ModulosSector ticket={ticket} />

                    <Conversacion
                        mensajes={ticket.mensajes}
                        urlEnviar={`${base}/mensajes`}
                        urlAdjuntos="/panel/adjuntos"
                        permiteInterno
                        lado="equipo"
                    />
                </div>

                <aside className="space-y-4">
                    <Tarjeta titulo="Estado">
                        <p className="mb-3">
                            <EstadoBadge>{ticket.estadoEtiqueta}</EstadoBadge>
                        </p>
                        <div className="flex flex-wrap gap-2">
                            {destinos.map((d) => (
                                <Button key={d.estado} size="sm" variant={d.retroceso ? 'outline' : 'default'} onClick={() => cambiarEstado(d)}>
                                    {d.retroceso && <Undo2 className="h-3 w-3" />}
                                    {d.etiqueta}
                                </Button>
                            ))}
                            {destinos.length === 0 && <p className="text-sm text-muted-foreground">Sin acciones disponibles.</p>}
                        </div>
                        {pidiendoMotivo && (
                            <div className="mt-3 space-y-2">
                                <Input placeholder="Motivo para volver atrás" value={motivo} onChange={(e) => setMotivo(e.target.value)} />
                                <div className="flex gap-2">
                                    <Button size="sm" disabled={!motivo.trim()} onClick={() => cambiarEstado(pidiendoMotivo, motivo)}>
                                        Volver a «{pidiendoMotivo.etiqueta}»
                                    </Button>
                                    <Button size="sm" variant="ghost" onClick={() => setPidiendoMotivo(null)}>
                                        Cancelar
                                    </Button>
                                </div>
                            </div>
                        )}
                        <InputError message={errors.destino} className="mt-2" />
                    </Tarjeta>

                    <Tarjeta titulo="Asignación">
                        <select
                            aria-label="Asignar a"
                            className={claseSelect}
                            value={ticket.asignadoId ?? ''}
                            onChange={(e) => router.post(`${base}/asignar`, { user_id: e.target.value || null }, { preserveScroll: true })}
                        >
                            <option value="">Sin asignar</option>
                            {empleados.map((u) => (
                                <option key={u.id} value={u.id}>
                                    {u.name}
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.user_id} className="mt-2" />
                    </Tarjeta>

                    <Tarjeta titulo="Plazo (SLA)">
                        <div className="space-y-1 text-sm">
                            <p>
                                <SlaBadge sla={ticket.sla} /> {ticket.slaVence && <span className="text-muted-foreground">vence {relativo(ticket.slaVence)}</span>}
                            </p>
                            {ticket.primeraRespuestaVence && (
                                <p className="text-muted-foreground">
                                    Primera respuesta: {ticket.primeraRespuesta ? `dada ${relativo(ticket.primeraRespuesta)}` : `vence ${relativo(ticket.primeraRespuestaVence)}`}
                                </p>
                            )}
                        </div>
                    </Tarjeta>

                    {ticket.valoracion && (
                        <Tarjeta titulo="Valoración del cliente">
                            <Estrellas nota={ticket.valoracion} />
                            {ticket.valoracionComentario && <p className="mt-2 text-sm italic">«{ticket.valoracionComentario}»</p>}
                        </Tarjeta>
                    )}

                    <Tarjeta titulo="Cliente">
                        <p className="text-sm font-medium">{ticket.cliente.nombre}</p>
                        <p className="text-sm text-muted-foreground">{ticket.cliente.email}</p>
                        {ticket.cliente.telefono && <p className="text-sm text-muted-foreground">{ticket.cliente.telefono}</p>}
                    </Tarjeta>

                    <Tarjeta titulo="Historial">
                        <ol className="space-y-2 text-sm">
                            {ticket.historial.map((e) => (
                                <li key={e.id}>
                                    <p>{describirEvento(e)}</p>
                                    {e.motivo && <p className="text-xs italic">«{e.motivo}»</p>}
                                    <p className="text-xs text-muted-foreground">
                                        {e.actor} · {fecha(e.fecha)}
                                    </p>
                                </li>
                            ))}
                        </ol>
                    </Tarjeta>
                </aside>
            </div>
        </>
    );
}

TicketPanelPage.layout = { breadcrumbs: [{ title: 'Bandeja', href: dashboard() }] };

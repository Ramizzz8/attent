import { Head, useForm, usePage } from '@inertiajs/react';
import type { CSSProperties, FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Campo, claseTextarea } from '@/components/portal/campo';
import { fecha } from '@/components/portal/estado-pill';
import { Input } from '@/components/ui/input';

type Props = {
    empresa: { nombre: string; color: string };
    proveedor: string;
    ticket: {
        numero: number;
        asunto: string;
        descripcion: string;
        categoria: string;
        estado: string;
        estadoEtiqueta: string;
        edificio: string;
        direccion: string;
        zona: string | null;
        vivienda: string | null;
        contacto: { nombre: string; telefono: string | null };
        visita: string | null;
    };
    acciones: { visita: string | null; resolver: string | null };
};

function Boton({ children, disabled }: { children: string; disabled?: boolean }) {
    return (
        <button type="submit" disabled={disabled} className="rounded-md px-4 py-2 text-sm font-medium text-white disabled:opacity-50" style={{ background: 'var(--marca)' }}>
            {children}
        </button>
    );
}

/** Página del proveedor externo (sin cuenta, con enlace firmado). Se pinta sin layout del panel. */
export default function TicketProveedor({ empresa, proveedor, ticket, acciones }: Props) {
    const { flash } = usePage().props as { flash?: { status?: string } };
    const visita = useForm({ fecha: '' });
    const resolver = useForm<{ comentario: string; adjuntos: File[] }>({ comentario: '', adjuntos: [] });

    function programar(e: FormEvent) {
        e.preventDefault();
        if (acciones.visita) visita.post(acciones.visita, { preserveScroll: true });
    }

    function resolverIncidencia(e: FormEvent) {
        e.preventDefault();
        if (acciones.resolver) resolver.post(acciones.resolver, { forceFormData: true, preserveScroll: true });
    }

    return (
        <div className="min-h-screen bg-muted/30" style={{ '--marca': empresa.color } as CSSProperties}>
            <Head title={`Incidencia #${ticket.numero}`} />
            <header className="border-b bg-background">
                <div className="mx-auto max-w-2xl px-4 py-3">
                    <p className="font-semibold">{empresa.nombre}</p>
                    <p className="text-xs text-muted-foreground">Acceso para {proveedor}</p>
                </div>
            </header>
            <main className="mx-auto max-w-2xl space-y-4 px-4 py-6">
                {flash?.status && <p className="rounded-md bg-green-50 p-3 text-sm text-green-800">{flash.status}</p>}
                <section className="rounded-xl border bg-background p-5">
                    <p className="text-sm text-muted-foreground">
                        #{ticket.numero} · {ticket.categoria} · {ticket.estadoEtiqueta}
                    </p>
                    <h1 className="text-xl font-semibold">{ticket.asunto}</h1>
                    <p className="mt-2 whitespace-pre-line">{ticket.descripcion}</p>
                    <dl className="mt-4 grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <dt className="text-muted-foreground">Edificio</dt>
                            <dd className="font-medium">{ticket.edificio}</dd>
                            <dd className="text-xs text-muted-foreground">{ticket.direccion}</dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">Zona</dt>
                            <dd className="font-medium">{ticket.zona ?? '—'}</dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">Contacto</dt>
                            <dd className="font-medium">
                                {ticket.contacto.nombre}
                                {ticket.vivienda ? ` (${ticket.vivienda})` : ''}
                            </dd>
                            {ticket.contacto.telefono && <dd className="text-xs">{ticket.contacto.telefono}</dd>}
                        </div>
                        {ticket.visita && (
                            <div>
                                <dt className="text-muted-foreground">Visita programada</dt>
                                <dd className="font-medium">{fecha(ticket.visita)}</dd>
                            </div>
                        )}
                    </dl>
                </section>

                {acciones.visita && (
                    <form onSubmit={programar} className="space-y-3 rounded-xl border bg-background p-5">
                        <h2 className="font-semibold">Programar visita</h2>
                        <Campo id="fecha" etiqueta="Fecha y hora" error={visita.errors.fecha}>
                            <Input id="fecha" type="datetime-local" value={visita.data.fecha} onChange={(e) => visita.setData('fecha', e.target.value)} required />
                        </Campo>
                        <Boton disabled={visita.processing}>Guardar visita</Boton>
                    </form>
                )}

                {acciones.resolver && (
                    <form onSubmit={resolverIncidencia} className="space-y-3 rounded-xl border bg-background p-5">
                        <h2 className="font-semibold">Marcar como resuelta</h2>
                        <Campo id="comentario" etiqueta="¿Qué se ha hecho?" error={resolver.errors.comentario}>
                            <textarea id="comentario" className={claseTextarea} value={resolver.data.comentario} onChange={(e) => resolver.setData('comentario', e.target.value)} required />
                        </Campo>
                        <Input type="file" accept=".jpg,.jpeg,.png,.webp,.pdf" multiple aria-label="Foto del trabajo" onChange={(e) => resolver.setData('adjuntos', Array.from(e.target.files ?? []))} />
                        <InputError message={(resolver.errors as Record<string, string>)['adjuntos.0']} />
                        <Boton disabled={resolver.processing}>Marcar como resuelta</Boton>
                    </form>
                )}

                {!acciones.visita && !acciones.resolver && <p className="text-center text-sm text-muted-foreground">Esta incidencia ya está {ticket.estadoEtiqueta.toLowerCase()}. ¡Gracias!</p>}
            </main>
        </div>
    );
}

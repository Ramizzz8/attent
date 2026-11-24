import { Head, Link } from '@inertiajs/react';
import { Copy } from 'lucide-react';
import { EstadoBadge, PrioridadBadge } from '@/components/panel/insignias';
import { fecha } from '@/components/portal/estado-pill';

type Props = {
    equipo: { id: number; codigo: string; tipo: string; ubicacion: string; notas: string | null; enlace: string };
    tickets: { numero: number; asunto: string; estado: string; prioridad: string; creado: string }[];
};

export default function EquipoPage({ equipo, tickets }: Props) {
    return (
        <>
            <Head title={`Equipo ${equipo.codigo}`} />
            <div className="space-y-4 p-4">
                <section className="rounded-xl border bg-background p-5">
                    <h1 className="text-2xl font-semibold">{equipo.codigo}</h1>
                    <p className="text-muted-foreground">
                        {equipo.tipo} · {equipo.ubicacion}
                    </p>
                    {equipo.notas && <p className="mt-2 text-sm">{equipo.notas}</p>}
                    <button
                        type="button"
                        onClick={() => navigator.clipboard?.writeText(equipo.enlace)}
                        className="mt-3 inline-flex items-center gap-1 text-xs text-muted-foreground hover:underline"
                        title={equipo.enlace}
                    >
                        <Copy className="h-3 w-3" /> Copiar enlace para reportar incidencias de este equipo
                    </button>
                </section>
                <section className="rounded-xl border bg-background p-5">
                    <h2 className="mb-3 font-semibold">Historial de incidencias</h2>
                    {tickets.length === 0 ? (
                        <p className="text-sm text-muted-foreground">Este equipo no ha tenido incidencias.</p>
                    ) : (
                        <ul className="divide-y text-sm">
                            {tickets.map((t) => (
                                <li key={t.numero} className="flex flex-wrap items-center justify-between gap-2 py-2">
                                    <Link href={`/panel/tickets/${t.numero}`} className="hover:underline">
                                        #{t.numero} {t.asunto}
                                    </Link>
                                    <span className="flex items-center gap-2">
                                        <PrioridadBadge prioridad={t.prioridad} />
                                        <EstadoBadge>{t.estado}</EstadoBadge>
                                        <span className="text-xs text-muted-foreground">{fecha(t.creado, false)}</span>
                                    </span>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
            </div>
        </>
    );
}

EquipoPage.layout = { breadcrumbs: [{ title: 'Equipos', href: '/panel/equipos' }] };

import { Head, Link } from '@inertiajs/react';
import { Inbox } from 'lucide-react';
import { EstadoVacio } from '@/components/estado-vacio';
import { EstadoPill, fecha } from '@/components/portal/estado-pill';
import { usePortalUrl } from '@/hooks/use-portal';

type Fila = { numero: number; asunto: string; estado: string; creado: string; actualizado: string };

export default function MisTickets({ tickets }: { tickets: Fila[] }) {
    const url = usePortalUrl();

    return (
        <>
            <Head title="Mis incidencias" />
            <h1 className="mb-4 text-2xl font-semibold">Mis incidencias</h1>
            {tickets.length === 0 ? (
                <div className="rounded-xl border bg-background">
                    <EstadoVacio
                        icono={Inbox}
                        titulo="Todavía no tienes incidencias"
                        texto="Cuando abras una, aquí verás en qué estado está y cada respuesta del equipo."
                        accion={
                            <Link href={url('nueva')} className="inline-flex rounded-full px-5 py-2.5 text-sm font-medium text-white" style={{ background: 'var(--marca)' }}>
                                Abrir una incidencia
                            </Link>
                        }
                    />
                </div>
            ) : (
                <ul className="divide-y rounded-xl border bg-background shadow-sm">
                    {tickets.map((t) => (
                        <li key={t.numero}>
                            <Link href={url(`tickets/${t.numero}`)} className="flex items-center justify-between gap-4 p-4 hover:bg-muted/50">
                                <div>
                                    <p className="font-medium">
                                        <span className="text-muted-foreground">#{t.numero}</span> {t.asunto}
                                    </p>
                                    <p className="text-xs text-muted-foreground">Actualizada el {fecha(t.actualizado)}</p>
                                </div>
                                <EstadoPill>{t.estado}</EstadoPill>
                            </Link>
                        </li>
                    ))}
                </ul>
            )}
        </>
    );
}

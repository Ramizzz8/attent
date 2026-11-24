import { Head, Link } from '@inertiajs/react';
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
                <p className="rounded-xl border bg-background p-6 text-muted-foreground">Todavía no tienes incidencias.</p>
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

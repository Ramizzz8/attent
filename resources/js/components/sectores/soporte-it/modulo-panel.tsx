import { Link, router } from '@inertiajs/react';
import { Monitor } from 'lucide-react';
import { claseSelect } from '@/components/portal/campo';

const IMPACTO: Record<string, string> = { individual: 'Una persona', departamento: 'Departamento o aula', todos: 'Todo el centro' };

type Props = {
    numero: number;
    impacto: string | null;
    urgencia: string | null;
    equipo: { id: number; codigo: string; tipo: string; ubicacion: string; incidencias: number } | null;
};

export function ModuloSoporteItPanel({ numero, impacto, urgencia, equipo }: Props) {
    return (
        <section className="grid gap-4 rounded-xl border bg-background p-5 sm:grid-cols-2">
            <div className="space-y-1 text-sm">
                <p className="flex items-center gap-2 font-semibold">
                    <Monitor className="h-4 w-4" /> Equipo
                </p>
                {equipo ? (
                    <>
                        <Link href={`/panel/equipos/${equipo.id}`} className="font-medium hover:underline">
                            {equipo.codigo}
                        </Link>
                        <p className="text-muted-foreground">
                            {equipo.tipo} · {equipo.ubicacion}
                        </p>
                        <p className="text-xs text-muted-foreground">{equipo.incidencias} incidencias en total</p>
                    </>
                ) : (
                    <p className="text-muted-foreground">Sin equipo asociado</p>
                )}
            </div>
            <div className="space-y-2 text-sm">
                <p className="font-semibold">Triaje</p>
                <p>
                    Impacto: <span className="font-medium">{impacto ? IMPACTO[impacto] : '—'}</span>
                </p>
                <label className="grid gap-1">
                    Urgencia
                    <select
                        className={claseSelect}
                        value={urgencia ?? ''}
                        onChange={(e) => router.post(`/panel/tickets/${numero}/triaje`, { urgencia: e.target.value }, { preserveScroll: true })}
                    >
                        <option value="baja">Baja</option>
                        <option value="media">Media</option>
                        <option value="alta">Alta</option>
                    </select>
                </label>
                <p className="text-xs text-muted-foreground">La prioridad y el plazo se recalculan con la matriz impacto × urgencia.</p>
            </div>
        </section>
    );
}

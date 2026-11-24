import { Check } from 'lucide-react';
import { cn } from '@/lib/utils';

export type PasoProgreso = { estado: string; etiqueta: string; situacion: 'hecho' | 'actual' | 'pendiente' };

/** Barra de progreso de la incidencia: lo hecho, dónde está ahora y lo que falta. */
export function Progreso({ pasos }: { pasos: PasoProgreso[] }) {
    return (
        <ol className="flex flex-wrap gap-y-3 text-sm" aria-label="Avance de la incidencia">
            {pasos.map((p, i) => (
                <li key={p.estado} className="flex items-center" aria-current={p.situacion === 'actual' ? 'step' : undefined}>
                    <span
                        className={cn(
                            'inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 font-medium',
                            p.situacion === 'hecho' && 'bg-muted text-muted-foreground',
                            p.situacion === 'actual' && 'text-white',
                            p.situacion === 'pendiente' && 'text-muted-foreground',
                        )}
                        style={p.situacion === 'actual' ? { background: 'var(--marca)' } : undefined}
                    >
                        {p.situacion === 'hecho' && <Check className="size-3.5" aria-hidden="true" />}
                        {p.etiqueta}
                        <span className="sr-only">{p.situacion === 'hecho' ? ' (hecho)' : p.situacion === 'actual' ? ' (estado actual)' : ' (pendiente)'}</span>
                    </span>
                    {i < pasos.length - 1 && <span className="mx-1 text-muted-foreground" aria-hidden="true">→</span>}
                </li>
            ))}
        </ol>
    );
}

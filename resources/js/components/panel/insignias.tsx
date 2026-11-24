import { cn } from '@/lib/utils';

const PRIORIDAD: Record<string, { texto: string; clase: string }> = {
    baja: { texto: 'Baja', clase: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300' },
    media: { texto: 'Media', clase: 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300' },
    alta: { texto: 'Alta', clase: 'bg-orange-100 text-orange-800 dark:bg-orange-950 dark:text-orange-300' },
    urgente: { texto: 'Urgente', clase: 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300' },
};

const SLA: Record<string, { texto: string; clase: string }> = {
    ok: { texto: 'En plazo', clase: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' },
    en_riesgo: { texto: 'En riesgo', clase: 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' },
    vencido: { texto: 'Vencido', clase: 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300' },
    pausado: { texto: 'Pausado', clase: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300' },
    cumplido: { texto: 'Cumplido', clase: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' },
    incumplido: { texto: 'Incumplido', clase: 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300' },
};

function Pastilla({ texto, clase }: { texto: string; clase: string }) {
    return <span className={cn('inline-flex rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap', clase)}>{texto}</span>;
}

export function PrioridadBadge({ prioridad }: { prioridad: string }) {
    return <Pastilla {...(PRIORIDAD[prioridad] ?? { texto: prioridad, clase: 'bg-muted' })} />;
}

export function SlaBadge({ sla }: { sla: string | null }) {
    if (!sla) return <span className="text-xs text-muted-foreground">—</span>;

    return <Pastilla {...(SLA[sla] ?? { texto: sla, clase: 'bg-muted' })} />;
}

export function EstadoBadge({ children }: { children: string }) {
    return <Pastilla texto={children} clase="border bg-background" />;
}

/** "en 3 h", "hace 20 min"… */
export function relativo(iso: string | null): string {
    if (!iso) return '';
    const minutos = Math.round((new Date(iso).getTime() - Date.now()) / 60000);
    const abs = Math.abs(minutos);
    const texto = abs < 60 ? `${abs} min` : abs < 48 * 60 ? `${Math.round(abs / 60)} h` : `${Math.round(abs / 1440)} días`;

    return minutos >= 0 ? `en ${texto}` : `hace ${texto}`;
}

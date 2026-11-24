import { Head, Link, router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { claseSelect } from '@/components/portal/campo';
import { EstadoBadge, PrioridadBadge, relativo, SlaBadge } from '@/components/panel/insignias';
import { Input } from '@/components/ui/input';
import { useAvisosPanel } from '@/hooks/use-avisos-panel';
import { toast } from 'sonner';
import { dashboard } from '@/routes';
import type { Opcion } from '@/types/portal';

type Fila = {
    numero: number;
    asunto: string;
    cliente: string;
    estado: string;
    estadoEtiqueta: string;
    prioridad: string;
    categoria: string;
    asignado: string | null;
    sla: string | null;
    slaVence: string | null;
    creado: string;
    extra?: string | null;
};

type Paginado<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    total: number;
};

type Filtros = Partial<Record<'estado' | 'prioridad' | 'asignado' | 'categoria' | 'sla' | 'q' | 'edificio_id', string>>;

type Opciones = {
    estados: Opcion[];
    categorias: Opcion[];
    prioridades: Opcion[];
    empleados: Opcion[];
    edificios?: Opcion[];
};

export default function Bandeja({ tickets, filtros, opciones }: { tickets: Paginado<Fila>; filtros: Filtros; opciones: Opciones }) {
    const [q, setQ] = useState(filtros.q ?? '');

    // Tiempo real: recarga la lista cuando cambia cualquier ticket de la empresa.
    useAvisosPanel((aviso) => {
        router.reload({ only: ['tickets'] });
        if (aviso.tipo === 'creado') {
            toast(`Nueva incidencia #${aviso.numero}`, { description: aviso.asunto });
        }
    });

    function filtrar(cambios: Filtros) {
        const nuevos = { ...filtros, ...cambios };
        Object.keys(nuevos).forEach((k) => !nuevos[k as keyof Filtros] && delete nuevos[k as keyof Filtros]);
        router.get('/panel', nuevos, { preserveState: true, preserveScroll: true, replace: true });
    }

    function buscar(e: FormEvent) {
        e.preventDefault();
        filtrar({ q });
    }

    const selector = (clave: keyof Filtros, etiqueta: string, items: Opcion[], vacio = 'Todos') => (
        <label className="grid gap-1 text-xs text-muted-foreground">
            {etiqueta}
            <select className={claseSelect} value={filtros[clave] ?? ''} onChange={(e) => filtrar({ [clave]: e.target.value })}>
                <option value="">{vacio}</option>
                {items.map((o) => (
                    <option key={o.valor} value={o.valor}>
                        {o.etiqueta}
                    </option>
                ))}
            </select>
        </label>
    );

    return (
        <>
            <Head title="Bandeja" />
            <div className="space-y-4 p-4">
                <div className="flex flex-wrap items-end justify-between gap-3">
                    <h1 className="text-2xl font-semibold">Bandeja</h1>
                    <form onSubmit={buscar} className="relative w-full max-w-sm">
                        <Search className="absolute top-2.5 left-2.5 h-4 w-4 text-muted-foreground" />
                        <Input className="pl-8" placeholder="Buscar por número, asunto, cliente…" value={q} onChange={(e) => setQ(e.target.value)} />
                    </form>
                </div>

                <div className="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-6">
                    {selector('estado', 'Estado', [{ valor: 'todos', etiqueta: 'Todos' }, ...opciones.estados], 'Abiertos')}
                    {selector('asignado', 'Asignado', [{ valor: 'yo', etiqueta: 'Mis tickets' }, { valor: 'sin_asignar', etiqueta: 'Sin asignar' }, ...opciones.empleados])}
                    {selector('prioridad', 'Prioridad', opciones.prioridades)}
                    {selector('categoria', 'Categoría', opciones.categorias)}
                    {selector('sla', 'Plazo', [
                        { valor: 'en_riesgo', etiqueta: 'En riesgo' },
                        { valor: 'vencido', etiqueta: 'Vencido' },
                    ])}
                    {opciones.edificios && selector('edificio_id', 'Edificio', opciones.edificios)}
                </div>

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left text-xs text-muted-foreground">
                            <tr>
                                <th className="p-3">#</th>
                                <th className="p-3">Incidencia</th>
                                <th className="p-3">Estado</th>
                                <th className="p-3">Prioridad</th>
                                <th className="p-3">Asignado</th>
                                <th className="p-3">Plazo</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {tickets.data.length === 0 && (
                                <tr>
                                    <td colSpan={6} className="p-6 text-center text-muted-foreground">
                                        No hay incidencias con estos filtros.
                                    </td>
                                </tr>
                            )}
                            {tickets.data.map((t) => (
                                <tr key={t.numero} className="hover:bg-muted/40">
                                    <td className="p-3 text-muted-foreground">{t.numero}</td>
                                    <td className="p-3">
                                        <Link href={`/panel/tickets/${t.numero}`} className="font-medium hover:underline">
                                            {t.asunto}
                                        </Link>
                                        <p className="text-xs text-muted-foreground">
                                            {t.cliente} · {t.categoria}
                                            {t.extra ? ` · ${t.extra}` : ''}
                                        </p>
                                    </td>
                                    <td className="p-3">
                                        <EstadoBadge>{t.estadoEtiqueta}</EstadoBadge>
                                    </td>
                                    <td className="p-3">
                                        <PrioridadBadge prioridad={t.prioridad} />
                                    </td>
                                    <td className="p-3">{t.asignado ?? <span className="text-muted-foreground">Sin asignar</span>}</td>
                                    <td className="p-3">
                                        <SlaBadge sla={t.sla} />
                                        {t.slaVence && (t.sla === 'ok' || t.sla === 'en_riesgo' || t.sla === 'vencido') && (
                                            <p className="mt-0.5 text-xs text-muted-foreground">{relativo(t.slaVence)}</p>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                {tickets.links.length > 3 && (
                    <nav className="flex flex-wrap gap-1">
                        {tickets.links.map((l, i) =>
                            l.url ? (
                                <Link
                                    key={i}
                                    href={l.url}
                                    preserveState
                                    className={`rounded border px-3 py-1 text-sm ${l.active ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'}`}
                                    dangerouslySetInnerHTML={{ __html: l.label }}
                                />
                            ) : (
                                <span key={i} className="px-3 py-1 text-sm text-muted-foreground" dangerouslySetInnerHTML={{ __html: l.label }} />
                            ),
                        )}
                    </nav>
                )}
            </div>
        </>
    );
}

Bandeja.layout = { breadcrumbs: [{ title: 'Bandeja', href: dashboard() }] };

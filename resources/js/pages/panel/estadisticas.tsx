import { Head, router } from '@inertiajs/react';
import { claseSelect } from '@/components/portal/campo';
import { Input } from '@/components/ui/input';
import type { Opcion } from '@/types/portal';

type Props = {
    filtros: { desde: string; hasta: string; edificio_id: number | null };
    resumen: {
        total: number;
        abiertos: number;
        terminados: number;
        slaCumplido: number | null;
        horasMedias: number | null;
        satisfaccion: number | null;
        valoraciones: number;
    };
    porEmpleado: { nombre: string; total: number; abiertos: number }[];
    porCategoria: { etiqueta: string; total: number }[];
    porPrioridad: { etiqueta: string; total: number }[];
    porSemana: { semana: string; total: number }[];
    opciones: { edificios?: Opcion[] };
};

/** Cifra destacada: el número manda, la etiqueta explica. */
function Cifra({ valor, etiqueta, detalle }: { valor: string; etiqueta: string; detalle?: string }) {
    return (
        <div className="rounded-xl border bg-background p-4">
            <p className="text-sm text-muted-foreground">{etiqueta}</p>
            <p className="mt-1 text-3xl font-semibold tabular-nums">{valor}</p>
            {detalle && <p className="mt-1 text-xs text-muted-foreground">{detalle}</p>}
        </div>
    );
}

/** Barras horizontales de una sola serie: un color, valor escrito al lado, tooltip al pasar el ratón. */
function BarrasHorizontales({ titulo, filas }: { titulo: string; filas: { etiqueta: string; total: number; detalle?: string }[] }) {
    const max = Math.max(1, ...filas.map((f) => f.total));

    return (
        <section className="rounded-xl border bg-background p-4">
            <h2 className="mb-3 text-sm font-semibold">{titulo}</h2>
            {filas.length === 0 && <p className="text-sm text-muted-foreground">Sin datos en este periodo.</p>}
            <ul className="space-y-2">
                {filas.map((f) => (
                    <li key={f.etiqueta} className="grid grid-cols-[minmax(0,40%)_minmax(2rem,1fr)_2.5rem] items-center gap-3 text-sm" title={`${f.etiqueta}: ${f.total}${f.detalle ? ` (${f.detalle})` : ''}`}>
                        <span className="truncate text-muted-foreground">{f.etiqueta}</span>
                        <span className="block h-3 rounded-r bg-muted/40">
                            <span className="block h-3 rounded-r" style={{ width: `${(f.total / max) * 100}%`, background: 'var(--grafica, #4f46e5)' }} />
                        </span>
                        <span className="text-right tabular-nums">{f.total}</span>
                    </li>
                ))}
            </ul>
        </section>
    );
}

/** Columnas por semana (una serie, eje base en cero). */
function Semanas({ datos }: { datos: Props['porSemana'] }) {
    const max = Math.max(1, ...datos.map((d) => d.total));

    return (
        <section className="rounded-xl border bg-background p-4">
            <h2 className="mb-3 text-sm font-semibold">Incidencias recibidas por semana</h2>
            {datos.length === 0 ? (
                <p className="text-sm text-muted-foreground">Sin datos en este periodo.</p>
            ) : (
                <div className="flex h-40 items-end gap-[2px] border-b">
                    {datos.map((d) => (
                        <div key={d.semana} className="group relative flex h-full flex-1 flex-col justify-end" title={`Semana del ${new Date(d.semana).toLocaleDateString('es-ES')}: ${d.total}`}>
                            <span className="mb-1 text-center text-xs tabular-nums text-muted-foreground">{d.total}</span>
                            <span className="block rounded-t" style={{ height: `${(d.total / max) * 80}%`, background: 'var(--grafica, #4f46e5)' }} />
                        </div>
                    ))}
                </div>
            )}
            <div className="mt-1 flex gap-[2px] text-[10px] text-muted-foreground">
                {datos.map((d) => (
                    <span key={d.semana} className="flex-1 truncate text-center">
                        {new Date(d.semana).toLocaleDateString('es-ES', { day: 'numeric', month: 'short' })}
                    </span>
                ))}
            </div>
        </section>
    );
}

export default function Estadisticas({ filtros, resumen, porEmpleado, porCategoria, porPrioridad, porSemana, opciones }: Props) {
    const filtrar = (cambios: Partial<Props['filtros']>) =>
        router.get('/panel/estadisticas', { ...filtros, ...cambios }, { preserveState: true, preserveScroll: true, replace: true });

    return (
        <>
            <Head title="Estadísticas" />
            <div className="space-y-4 p-4">
                <div className="flex flex-wrap items-end justify-between gap-3">
                    <h1 className="text-2xl font-semibold">Estadísticas</h1>
                    <div className="flex flex-wrap items-end gap-2">
                        <label className="grid gap-1 text-xs text-muted-foreground">
                            Desde
                            <Input type="date" value={filtros.desde} onChange={(e) => filtrar({ desde: e.target.value })} />
                        </label>
                        <label className="grid gap-1 text-xs text-muted-foreground">
                            Hasta
                            <Input type="date" value={filtros.hasta} onChange={(e) => filtrar({ hasta: e.target.value })} />
                        </label>
                        {opciones.edificios && (
                            <label className="grid gap-1 text-xs text-muted-foreground">
                                Edificio
                                <select className={claseSelect} value={filtros.edificio_id ?? ''} onChange={(e) => filtrar({ edificio_id: e.target.value ? Number(e.target.value) : null })}>
                                    <option value="">Todos</option>
                                    {opciones.edificios.map((o) => (
                                        <option key={o.valor} value={o.valor}>
                                            {o.etiqueta}
                                        </option>
                                    ))}
                                </select>
                            </label>
                        )}
                    </div>
                </div>

                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <Cifra etiqueta="Incidencias recibidas" valor={String(resumen.total)} detalle={`${resumen.abiertos} abiertas · ${resumen.terminados} terminadas`} />
                    <Cifra etiqueta="Plazo cumplido" valor={resumen.slaCumplido === null ? '—' : `${resumen.slaCumplido} %`} detalle="de las que ya llegaron al objetivo" />
                    <Cifra etiqueta="Tiempo medio de respuesta" valor={resumen.horasMedias === null ? '—' : `${resumen.horasMedias} h`} detalle="hasta el estado objetivo del sector" />
                    <Cifra etiqueta="Satisfacción" valor={resumen.satisfaccion === null ? '—' : `${resumen.satisfaccion} / 5`} detalle={`${resumen.valoraciones} valoraciones`} />
                </div>

                <Semanas datos={porSemana} />

                <div className="grid gap-4 lg:grid-cols-3">
                    <BarrasHorizontales titulo="Por empleado" filas={porEmpleado.map((e) => ({ etiqueta: e.nombre, total: e.total, detalle: `${e.abiertos} abiertas` }))} />
                    <BarrasHorizontales titulo="Por categoría" filas={porCategoria} />
                    <BarrasHorizontales titulo="Por prioridad" filas={porPrioridad} />
                </div>
            </div>
        </>
    );
}

Estadisticas.layout = { breadcrumbs: [{ title: 'Estadísticas', href: '/panel/estadisticas' }] };

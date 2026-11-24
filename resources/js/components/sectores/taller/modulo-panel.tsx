import { useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { fecha } from '@/components/portal/estado-pill';
import { TablaPresupuesto, euros } from '@/components/sectores/taller/presupuesto';
import type { PresupuestoVista } from '@/components/sectores/taller/presupuesto';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type Vehiculo = {
    matricula: string;
    marca: string | null;
    modelo: string | null;
    km: number | null;
    historial: { numero: number; asunto: string; estado: string; fecha: string }[];
};

type Linea = { tipo: 'pieza' | 'mano_obra'; concepto: string; cantidad: number; precio_unitario: number };

function EditorPresupuesto({ numero }: { numero: number }) {
    const form = useForm<{ iva_pct: number; lineas: Linea[] }>({
        iva_pct: 21,
        lineas: [{ tipo: 'pieza', concepto: '', cantidad: 1, precio_unitario: 0 }],
    });
    const errores = form.errors as Record<string, string>;
    const base = form.data.lineas.reduce((s, l) => s + Number(l.cantidad) * Number(l.precio_unitario), 0);
    const total = base * (1 + Number(form.data.iva_pct) / 100);

    const cambiar = (i: number, campo: keyof Linea, valor: string) =>
        form.setData(
            'lineas',
            form.data.lineas.map((l, j) => (j === i ? { ...l, [campo]: campo === 'concepto' || campo === 'tipo' ? valor : Number(valor) } : l)),
        );

    function enviar(e: FormEvent) {
        e.preventDefault();
        form.post(`/panel/tickets/${numero}/presupuestos`, { preserveScroll: true });
    }

    return (
        <form onSubmit={enviar} className="space-y-3 rounded-lg border border-dashed p-3">
            <p className="text-sm font-medium">Nuevo presupuesto</p>
            {form.data.lineas.map((l, i) => (
                <div key={i} className="grid grid-cols-[110px_1fr_70px_100px_32px] items-start gap-2">
                    <select aria-label="Tipo" className="h-9 rounded-md border bg-transparent px-2 text-sm" value={l.tipo} onChange={(e) => cambiar(i, 'tipo', e.target.value)}>
                        <option value="pieza">Pieza</option>
                        <option value="mano_obra">Mano de obra</option>
                    </select>
                    <div>
                        <Input aria-label="Concepto" placeholder="Concepto" value={l.concepto} onChange={(e) => cambiar(i, 'concepto', e.target.value)} />
                        <InputError message={errores[`lineas.${i}.concepto`]} />
                    </div>
                    <Input aria-label="Cantidad" type="number" step="0.01" min="0" value={l.cantidad} onChange={(e) => cambiar(i, 'cantidad', e.target.value)} />
                    <Input aria-label="Precio" type="number" step="0.01" min="0" value={l.precio_unitario} onChange={(e) => cambiar(i, 'precio_unitario', e.target.value)} />
                    <Button
                        type="button"
                        size="icon"
                        variant="ghost"
                        aria-label="Quitar línea"
                        disabled={form.data.lineas.length === 1}
                        onClick={() => form.setData('lineas', form.data.lineas.filter((_, j) => j !== i))}
                    >
                        <Trash2 className="h-4 w-4" />
                    </Button>
                </div>
            ))}
            <div className="flex flex-wrap items-center justify-between gap-2">
                <Button
                    type="button"
                    size="sm"
                    variant="outline"
                    onClick={() => form.setData('lineas', [...form.data.lineas, { tipo: 'mano_obra', concepto: '', cantidad: 1, precio_unitario: 0 }])}
                >
                    <Plus className="h-4 w-4" /> Añadir línea
                </Button>
                <label className="flex items-center gap-2 text-sm">
                    IVA %
                    <Input type="number" className="w-20" value={form.data.iva_pct} onChange={(e) => form.setData('iva_pct', Number(e.target.value))} />
                </label>
            </div>
            <div className="flex items-center justify-between">
                <p className="text-sm">
                    Total con IVA: <span className="font-semibold">{euros(total)}</span>
                </p>
                <Button type="submit" size="sm" disabled={form.processing}>
                    Enviar al cliente
                </Button>
            </div>
            <InputError message={errores.lineas} />
        </form>
    );
}

export function ModuloTallerPanel({ numero, vehiculo, presupuestos, puedePresupuestar }: { numero: number; vehiculo: Vehiculo | null; presupuestos: PresupuestoVista[]; puedePresupuestar: boolean }) {
    return (
        <>
            {vehiculo && (
                <section className="rounded-xl border bg-background p-5">
                    <h2 className="mb-2 font-semibold">Vehículo {vehiculo.matricula}</h2>
                    <p className="text-sm text-muted-foreground">
                        {[vehiculo.marca, vehiculo.modelo].filter(Boolean).join(' ') || 'Sin marca ni modelo'}
                        {vehiculo.km ? ` · ${vehiculo.km.toLocaleString('es-ES')} km` : ''}
                    </p>
                    {vehiculo.historial.length > 0 && (
                        <div className="mt-3">
                            <p className="text-xs font-medium text-muted-foreground">Reparaciones anteriores</p>
                            <ul className="mt-1 space-y-1 text-sm">
                                {vehiculo.historial.map((h) => (
                                    <li key={h.numero}>
                                        <a href={`/panel/tickets/${h.numero}`} className="hover:underline">
                                            #{h.numero} {h.asunto}
                                        </a>{' '}
                                        <span className="text-xs text-muted-foreground">
                                            · {h.estado} · {fecha(h.fecha, false)}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}
                </section>
            )}

            <section className="space-y-4 rounded-xl border bg-background p-5">
                <h2 className="font-semibold">Presupuestos</h2>
                {presupuestos.length === 0 && !puedePresupuestar && <p className="text-sm text-muted-foreground">Pasa el ticket a «En diagnóstico» para preparar el presupuesto.</p>}
                {puedePresupuestar && <EditorPresupuesto numero={numero} />}
                {presupuestos.map((p) => (
                    <TablaPresupuesto key={p.id} p={p} urlPdf={`/panel/tickets/${numero}/presupuestos/${p.version}/pdf`} />
                ))}
            </section>
        </>
    );
}

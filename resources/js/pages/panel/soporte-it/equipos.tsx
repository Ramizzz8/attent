import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { MonitorX } from 'lucide-react';
import { EstadoVacio } from '@/components/estado-vacio';
import type { FormEvent } from 'react';
import { Campo, claseSelect } from '@/components/portal/campo';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { Opcion } from '@/types/portal';

type Equipo = { id: number; codigo: string; tipo: string; ubicacion: string; incidencias: number };

function NuevoEquipo({ tipos }: { tipos: Opcion[] }) {
    const form = useForm({ codigo: '', tipo: 'sobremesa', ubicacion: '', notas: '' });

    function enviar(e: FormEvent) {
        e.preventDefault();
        form.post('/panel/equipos', { preserveScroll: true, onSuccess: () => form.reset() });
    }

    return (
        <form onSubmit={enviar} className="grid gap-3 rounded-xl border bg-background p-4 sm:grid-cols-4">
            <Campo id="codigo" etiqueta="Código" error={form.errors.codigo}>
                <Input id="codigo" className="uppercase" value={form.data.codigo} onChange={(e) => form.setData('codigo', e.target.value)} required />
            </Campo>
            <Campo id="tipo" etiqueta="Tipo" error={form.errors.tipo}>
                <select id="tipo" className={claseSelect} value={form.data.tipo} onChange={(e) => form.setData('tipo', e.target.value)}>
                    {tipos.map((t) => (
                        <option key={t.valor} value={t.valor}>
                            {t.etiqueta}
                        </option>
                    ))}
                </select>
            </Campo>
            <Campo id="ubicacion" etiqueta="Ubicación" error={form.errors.ubicacion}>
                <Input id="ubicacion" value={form.data.ubicacion} onChange={(e) => form.setData('ubicacion', e.target.value)} required />
            </Campo>
            <div className="flex items-end">
                <Button type="submit" disabled={form.processing} className="w-full">
                    Añadir equipo
                </Button>
            </div>
        </form>
    );
}

export default function Equipos({ equipos, tipos }: { equipos: Equipo[]; tipos: Opcion[] }) {
    const { empresaPanel } = usePage().props as { empresaPanel?: { esAdmin: boolean } };

    return (
        <>
            <Head title="Inventario de equipos" />
            <div className="space-y-4 p-4">
                <h1 className="text-2xl font-semibold">Inventario de equipos</h1>
                {empresaPanel?.esAdmin && <NuevoEquipo tipos={tipos} />}
                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left text-xs text-muted-foreground">
                            <tr>
                                <th className="p-3">Código</th>
                                <th className="p-3">Tipo</th>
                                <th className="p-3">Ubicación</th>
                                <th className="p-3 text-right">Incidencias</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {equipos.map((e) => (
                                <tr key={e.id} className="hover:bg-muted/40">
                                    <td className="p-3">
                                        <Link href={`/panel/equipos/${e.id}`} className="font-medium hover:underline">
                                            {e.codigo}
                                        </Link>
                                    </td>
                                    <td className="p-3">{e.tipo}</td>
                                    <td className="p-3">{e.ubicacion}</td>
                                    <td className="p-3 text-right">{e.incidencias}</td>
                                </tr>
                            ))}
                            {equipos.length === 0 && (
                                <tr>
                                    <td colSpan={4}>
                                        <EstadoVacio
                                            icono={MonitorX}
                                            titulo="Aún no hay equipos en el inventario"
                                            texto="Da de alta los equipos con su código: así quien avisa puede indicar cuál falla y verás su historial."
                                        />
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}

Equipos.layout = { breadcrumbs: [{ title: 'Equipos', href: '/panel/equipos' }] };

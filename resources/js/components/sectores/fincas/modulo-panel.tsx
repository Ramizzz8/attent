import { router, usePage } from '@inertiajs/react';
import { Building2, Users, Wrench } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { claseSelect } from '@/components/portal/campo';
import { fecha } from '@/components/portal/estado-pill';
import { Button } from '@/components/ui/button';

type Props = {
    numero: number;
    edificio: string | null;
    zona: string | null;
    afectados: number;
    afectadosNombres: string[];
    proveedor: { id: number; nombre: string; oficio: string; telefono: string | null; email: string } | null;
    visita: string | null;
    proveedoresDisponibles: { id: number; nombre: string; oficio: string }[];
    puedeAsignarProveedor: boolean;
};

export function ModuloFincasPanel(p: Props) {
    const { errors } = usePage().props as { errors: Record<string, string> };
    const [elegido, setElegido] = useState<string>(p.proveedor ? String(p.proveedor.id) : '');

    return (
        <section className="grid gap-4 rounded-xl border bg-background p-5 sm:grid-cols-2">
            <div className="space-y-2 text-sm">
                <p className="flex items-center gap-2 font-semibold">
                    <Building2 className="h-4 w-4" /> {p.edificio ?? 'Sin edificio'}
                </p>
                <p className="text-muted-foreground">Zona: {p.zona ?? '—'}</p>
                <p className="flex items-center gap-2">
                    <Users className="h-4 w-4" /> {p.afectados + 1} {p.afectados === 0 ? 'vecino afectado' : 'vecinos afectados'}
                </p>
                {p.afectadosNombres.length > 0 && <p className="text-xs text-muted-foreground">También: {p.afectadosNombres.join(', ')}</p>}
            </div>
            <div className="space-y-2 text-sm">
                <p className="flex items-center gap-2 font-semibold">
                    <Wrench className="h-4 w-4" /> Proveedor
                </p>
                {p.proveedor ? (
                    <div>
                        <p>
                            {p.proveedor.nombre} <span className="text-muted-foreground">({p.proveedor.oficio})</span>
                        </p>
                        <p className="text-xs text-muted-foreground">{[p.proveedor.telefono, p.proveedor.email].filter(Boolean).join(' · ')}</p>
                        {p.visita && <p className="mt-1">Visita: {fecha(p.visita)}</p>}
                    </div>
                ) : (
                    <p className="text-muted-foreground">Sin asignar</p>
                )}
                {p.puedeAsignarProveedor && (
                    <div className="flex gap-2">
                        <select aria-label="Proveedor" className={claseSelect} value={elegido} onChange={(e) => setElegido(e.target.value)}>
                            <option value="">Elige proveedor</option>
                            {p.proveedoresDisponibles.map((pr) => (
                                <option key={pr.id} value={pr.id}>
                                    {pr.nombre} · {pr.oficio}
                                </option>
                            ))}
                        </select>
                        <Button
                            size="sm"
                            disabled={!elegido || elegido === String(p.proveedor?.id ?? '')}
                            onClick={() => router.post(`/panel/tickets/${p.numero}/proveedor`, { proveedor_id: Number(elegido) }, { preserveScroll: true })}
                        >
                            Asignar
                        </Button>
                    </div>
                )}
                {p.proveedoresDisponibles.length === 0 && <p className="text-xs text-muted-foreground">Ningún proveedor atiende este edificio todavía.</p>}
                <InputError message={errors.proveedor_id} />
            </div>
        </section>
    );
}

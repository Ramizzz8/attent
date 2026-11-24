import { Head, useForm, usePage } from '@inertiajs/react';
import { Building2, Copy, Star } from 'lucide-react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Campo, claseTextarea } from '@/components/portal/campo';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type Edificio = {
    id: number;
    nombre: string;
    direccion: string;
    enlace: string;
    zonas: { id: number; nombre: string; prioridadAlta: boolean }[];
    proveedores: string[];
};
type Proveedor = { id: number; nombre: string; oficio: string; email: string; telefono: string | null; edificios: number[] };

function NuevoEdificio() {
    const form = useForm({ nombre: '', direccion: '', zonas: 'Ascensor*\nPortal\nGaraje\nPiscina\nTejado' });

    function enviar(e: FormEvent) {
        e.preventDefault();
        form.post('/panel/edificios', { preserveScroll: true, onSuccess: () => form.reset() });
    }

    return (
        <form onSubmit={enviar} className="space-y-3 rounded-xl border bg-background p-4">
            <h2 className="font-semibold">Nuevo edificio</h2>
            <Campo id="e-nombre" etiqueta="Nombre" error={form.errors.nombre}>
                <Input id="e-nombre" value={form.data.nombre} onChange={(e) => form.setData('nombre', e.target.value)} required />
            </Campo>
            <Campo id="e-direccion" etiqueta="Dirección" error={form.errors.direccion}>
                <Input id="e-direccion" value={form.data.direccion} onChange={(e) => form.setData('direccion', e.target.value)} required />
            </Campo>
            <Campo id="e-zonas" etiqueta="Zonas comunes" ayuda="Una por línea. Termina con * las prioritarias (empiezan en prioridad alta)." error={form.errors.zonas}>
                <textarea id="e-zonas" className={claseTextarea} value={form.data.zonas} onChange={(e) => form.setData('zonas', e.target.value)} />
            </Campo>
            <Button type="submit" disabled={form.processing}>
                Crear edificio
            </Button>
        </form>
    );
}

function NuevoProveedor({ edificios }: { edificios: Edificio[] }) {
    const form = useForm<{ nombre: string; oficio: string; email: string; telefono: string; edificios: number[] }>({
        nombre: '',
        oficio: '',
        email: '',
        telefono: '',
        edificios: [],
    });
    const errores = form.errors as Record<string, string>;

    function enviar(e: FormEvent) {
        e.preventDefault();
        form.post('/panel/proveedores', { preserveScroll: true, onSuccess: () => form.reset() });
    }

    return (
        <form onSubmit={enviar} className="space-y-3 rounded-xl border bg-background p-4">
            <h2 className="font-semibold">Nuevo proveedor</h2>
            <div className="grid gap-3 sm:grid-cols-2">
                <Campo id="p-nombre" etiqueta="Nombre" error={errores.nombre}>
                    <Input id="p-nombre" value={form.data.nombre} onChange={(e) => form.setData('nombre', e.target.value)} required />
                </Campo>
                <Campo id="p-oficio" etiqueta="Oficio" error={errores.oficio}>
                    <Input id="p-oficio" placeholder="Fontanero, ascensorista…" value={form.data.oficio} onChange={(e) => form.setData('oficio', e.target.value)} required />
                </Campo>
                <Campo id="p-email" etiqueta="Email" error={errores.email}>
                    <Input id="p-email" type="email" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} required />
                </Campo>
                <Campo id="p-telefono" etiqueta="Teléfono" error={errores.telefono}>
                    <Input id="p-telefono" value={form.data.telefono} onChange={(e) => form.setData('telefono', e.target.value)} />
                </Campo>
            </div>
            <fieldset>
                <legend className="mb-1 text-sm font-medium">Edificios que atiende</legend>
                <div className="flex flex-wrap gap-3">
                    {edificios.map((e) => (
                        <label key={e.id} className="flex items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                checked={form.data.edificios.includes(e.id)}
                                onChange={(ev) =>
                                    form.setData('edificios', ev.target.checked ? [...form.data.edificios, e.id] : form.data.edificios.filter((id) => id !== e.id))
                                }
                            />
                            {e.nombre}
                        </label>
                    ))}
                </div>
                <InputError message={errores.edificios ?? Object.entries(errores).find(([k]) => k.startsWith('edificios.'))?.[1]} />
            </fieldset>
            <Button type="submit" disabled={form.processing}>
                Crear proveedor
            </Button>
        </form>
    );
}

export default function Edificios({ edificios, proveedores }: { edificios: Edificio[]; proveedores: Proveedor[] }) {
    const { flash } = usePage().props as { flash?: { status?: string } };

    return (
        <>
            <Head title="Edificios y proveedores" />
            <div className="space-y-6 p-4">
                <h1 className="text-2xl font-semibold">Edificios y proveedores</h1>
                {flash?.status && <p className="rounded-md bg-green-50 p-3 text-sm text-green-800 dark:bg-green-950 dark:text-green-200">{flash.status}</p>}
                <div className="grid gap-4 lg:grid-cols-2">
                    {edificios.map((e) => (
                        <article key={e.id} className="rounded-xl border bg-background p-4">
                            <h2 className="flex items-center gap-2 font-semibold">
                                <Building2 className="h-4 w-4" /> {e.nombre}
                            </h2>
                            <p className="text-sm text-muted-foreground">{e.direccion}</p>
                            <div className="mt-3 flex flex-wrap gap-1">
                                {e.zonas.map((z) => (
                                    <span key={z.id} className="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-xs">
                                        {z.prioridadAlta && <Star className="h-3 w-3 fill-current text-amber-500" />}
                                        {z.nombre}
                                    </span>
                                ))}
                            </div>
                            <p className="mt-3 text-xs text-muted-foreground">Proveedores: {e.proveedores.join(', ') || 'ninguno'}</p>
                            <button
                                type="button"
                                onClick={() => navigator.clipboard?.writeText(e.enlace)}
                                className="mt-3 inline-flex items-center gap-1 text-xs text-muted-foreground hover:underline"
                                title={e.enlace}
                            >
                                <Copy className="h-3 w-3" /> Copiar enlace para los vecinos
                            </button>
                        </article>
                    ))}
                </div>

                <section className="rounded-xl border bg-background p-4">
                    <h2 className="mb-3 font-semibold">Proveedores</h2>
                    <ul className="divide-y text-sm">
                        {proveedores.map((p) => (
                            <li key={p.id} className="py-2">
                                <span className="font-medium">{p.nombre}</span> · {p.oficio} · <span className="text-muted-foreground">{p.email}</span>
                                <span className="ml-2 text-xs text-muted-foreground">({p.edificios.length} edificios)</span>
                            </li>
                        ))}
                        {proveedores.length === 0 && <li className="py-2 text-muted-foreground">Aún no hay proveedores.</li>}
                    </ul>
                </section>

                <div className="grid gap-4 lg:grid-cols-2">
                    <NuevoEdificio />
                    <NuevoProveedor edificios={edificios} />
                </div>
            </div>
        </>
    );
}

Edificios.layout = { breadcrumbs: [{ title: 'Edificios y proveedores', href: '/panel/edificios' }] };

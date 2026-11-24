import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { Campo, claseTextarea } from '@/components/portal/campo';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type Props = {
    empresa: { nombre: string; slug: string; sector: string; color_primario: string; texto_portal: string; logo: string | null; urlPortal: string };
    sla: { prioridad: string; etiqueta: string; minutos: number }[];
    status?: string;
};

export default function Ajustes({ empresa, sla, status }: Props) {
    const form = useForm<{ nombre: string; color_primario: string; texto_portal: string; logo: File | null; sla_minutos: Record<string, number> }>({
        nombre: empresa.nombre,
        color_primario: empresa.color_primario,
        texto_portal: empresa.texto_portal,
        logo: null,
        sla_minutos: Object.fromEntries(sla.map((s) => [s.prioridad, s.minutos])),
    });
    const errores = form.errors as Record<string, string>;

    function guardar(e: FormEvent) {
        e.preventDefault();
        form.post('/panel/ajustes', { forceFormData: true, preserveScroll: true });
    }

    return (
        <>
            <Head title="Ajustes" />
            <form onSubmit={guardar} className="max-w-3xl space-y-6 p-4">
                <h1 className="text-2xl font-semibold">Ajustes de la empresa</h1>
                {status && <p className="rounded-md bg-green-50 p-3 text-sm text-green-800 dark:bg-green-950 dark:text-green-200">{status}</p>}

                <section className="space-y-4 rounded-xl border bg-background p-4">
                    <h2 className="font-semibold">Portal de clientes</h2>
                    <p className="text-sm text-muted-foreground">
                        Sector: {empresa.sector} · Dirección pública:{' '}
                        <a href={empresa.urlPortal} target="_blank" rel="noreferrer" className="underline">
                            {empresa.urlPortal}
                        </a>
                    </p>
                    <Campo id="nombre" etiqueta="Nombre visible" error={errores.nombre}>
                        <Input id="nombre" value={form.data.nombre} onChange={(e) => form.setData('nombre', e.target.value)} required />
                    </Campo>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <Campo id="color" etiqueta="Color de marca" error={errores.color_primario}>
                            <div className="flex gap-2">
                                <input
                                    type="color"
                                    aria-label="Selector de color"
                                    value={/^#[0-9a-f]{6}$/i.test(form.data.color_primario) ? form.data.color_primario : '#000000'}
                                    onChange={(e) => form.setData('color_primario', e.target.value)}
                                    className="h-9 w-12 rounded border"
                                />
                                <Input id="color" value={form.data.color_primario} onChange={(e) => form.setData('color_primario', e.target.value)} />
                            </div>
                        </Campo>
                        <Campo id="logo" etiqueta="Logo (PNG, JPG o WEBP, máx. 1 MB)" error={errores.logo}>
                            <div className="flex items-center gap-3">
                                {empresa.logo && <img src={empresa.logo} alt="Logo actual" className="h-9 w-9 rounded object-contain" />}
                                <Input id="logo" type="file" accept=".png,.jpg,.jpeg,.webp" onChange={(e) => form.setData('logo', e.target.files?.[0] ?? null)} />
                            </div>
                        </Campo>
                    </div>
                    <Campo id="texto" etiqueta="Texto de bienvenida (opcional)" error={errores.texto_portal}>
                        <textarea id="texto" className={claseTextarea} maxLength={300} value={form.data.texto_portal} onChange={(e) => form.setData('texto_portal', e.target.value)} />
                    </Campo>
                </section>

                <section className="space-y-4 rounded-xl border bg-background p-4">
                    <h2 className="font-semibold">Plazos de resolución (SLA)</h2>
                    <p className="text-sm text-muted-foreground">Minutos por prioridad. Se aplican a las incidencias nuevas.</p>
                    <div className="grid gap-4 sm:grid-cols-4">
                        {sla.map((s) => (
                            <Campo key={s.prioridad} id={`sla-${s.prioridad}`} etiqueta={s.etiqueta} error={errores[`sla_minutos.${s.prioridad}`]}>
                                <Input
                                    id={`sla-${s.prioridad}`}
                                    type="number"
                                    min={30}
                                    max={43200}
                                    value={form.data.sla_minutos[s.prioridad]}
                                    onChange={(e) => form.setData('sla_minutos', { ...form.data.sla_minutos, [s.prioridad]: Number(e.target.value) })}
                                />
                            </Campo>
                        ))}
                    </div>
                </section>

                <Button type="submit" disabled={form.processing}>
                    Guardar ajustes
                </Button>
            </form>
        </>
    );
}

Ajustes.layout = { breadcrumbs: [{ title: 'Ajustes', href: '/panel/ajustes' }] };

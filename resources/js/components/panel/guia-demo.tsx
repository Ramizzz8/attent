import { Link, usePage } from '@inertiajs/react';
import { ArrowRight, X } from 'lucide-react';
import { useEffect, useState } from 'react';

type Tarea = { texto: string; enlace: string };

/** Primeros pasos de la demo: tres tareas sugeridas. Se cierra y se recuerda en este navegador. */
export function GuiaDemo() {
    const { guiaDemo, empresaPanel } = usePage().props as { guiaDemo?: Tarea[] | null; empresaPanel?: { slug: string } | null };
    const clave = `attent-guia-cerrada-${empresaPanel?.slug ?? ''}`;
    const [cerrada, setCerrada] = useState(true);

    useEffect(() => setCerrada(localStorage.getItem(clave) === '1'), [clave]);

    if (!guiaDemo || cerrada) return null;

    return (
        <section aria-labelledby="guia-demo-titulo" className="relative rounded-xl bg-accent p-5 text-accent-foreground">
            <button
                type="button"
                aria-label="Cerrar la guía"
                onClick={() => {
                    localStorage.setItem(clave, '1');
                    setCerrada(true);
                }}
                className="absolute top-3 right-3 rounded-full p-1.5 hover:bg-background/60"
            >
                <X className="size-4" />
            </button>
            <h2 id="guia-demo-titulo" className="font-semibold">
                Primeros pasos en la demo
            </h2>
            <p className="mt-1 text-sm">Tres cosas para probar en un par de minutos. Los datos se reinician cada noche.</p>
            <ol className="mt-4 grid gap-2 md:grid-cols-3">
                {guiaDemo.map((t) => (
                    <li key={t.texto}>
                        <Link
                            href={t.enlace}
                            className="group flex h-full items-center justify-between gap-3 rounded-lg bg-background px-4 py-3 text-sm font-medium text-foreground transition hover:ring-2 hover:ring-primary/40"
                        >
                            {t.texto}
                            <ArrowRight className="size-4 shrink-0 text-primary transition group-hover:translate-x-0.5" />
                        </Link>
                    </li>
                ))}
            </ol>
        </section>
    );
}

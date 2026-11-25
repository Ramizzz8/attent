import { Head, Link } from '@inertiajs/react';
import { ArrowRight, ListChecks, PencilLine } from 'lucide-react';
import { usePortal } from '@/hooks/use-portal';

const TEXTO_POR_SECTOR = {
    taller: 'Cuéntanos qué le pasa a tu vehículo y sigue la reparación desde aquí: diagnóstico, presupuesto y aviso cuando esté listo.',
    fincas: 'Avisa de cualquier avería o problema en las zonas comunes de tu edificio y sigue cómo se resuelve.',
    soporte_it: 'Reporta problemas con tu equipo, la red o tus cuentas y sigue su resolución.',
};

const PASOS = [
    { titulo: 'Cuéntanos qué pasa', texto: 'Un formulario corto: dónde, qué y, si quieres, una foto.' },
    { titulo: 'Confirma tu email', texto: 'Te llega un enlace. Sin contraseñas ni registros.' },
    { titulo: 'Síguelo desde aquí', texto: 'Verás cada paso y te avisaremos cuando haya novedades.' },
];

const foco = 'focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none';

export default function Inicio() {
    const { empresa, cliente } = usePortal();
    const base = `/${empresa.slug}`;

    return (
        <>
            <Head title={empresa.nombre} />
            <section className="rounded-xl border bg-background p-6 shadow-sm sm:p-8">
                <h1 className="text-3xl font-semibold tracking-tight">¿En qué podemos ayudarte?</h1>
                <p className="mt-3 max-w-xl text-muted-foreground">{empresa.texto ?? TEXTO_POR_SECTOR[empresa.sector]}</p>

                <div className="mt-8 grid gap-4 sm:grid-cols-2">
                    <Link
                        href={`${base}/nueva`}
                        className={`group flex items-center gap-4 rounded-xl p-5 text-white shadow-sm transition hover:brightness-110 ${foco}`}
                        style={{ background: 'var(--marca)' }}
                    >
                        <PencilLine className="size-8 shrink-0" aria-hidden="true" />
                        <span className="flex-1">
                            <span className="block text-lg font-semibold">Abrir una incidencia</span>
                            <span className="block text-sm text-white/85">Avísanos de un problema nuevo.</span>
                        </span>
                        <ArrowRight className="size-5 shrink-0 transition group-hover:translate-x-1" aria-hidden="true" />
                    </Link>
                    <Link
                        href={cliente ? `${base}/mis-tickets` : `${base}/acceder`}
                        className={`group flex items-center gap-4 rounded-xl border p-5 transition hover:bg-muted ${foco}`}
                    >
                        <ListChecks className="size-8 shrink-0" style={{ color: 'var(--marca)' }} aria-hidden="true" />
                        <span className="flex-1">
                            <span className="block text-lg font-semibold">Ver mis incidencias</span>
                            <span className="block text-sm text-muted-foreground">
                                {cliente ? 'Mira en qué punto está cada una.' : 'Te mandamos un enlace para entrar.'}
                            </span>
                        </span>
                        <ArrowRight className="size-5 shrink-0 text-muted-foreground transition group-hover:translate-x-1" aria-hidden="true" />
                    </Link>
                </div>
            </section>

            <section aria-labelledby="como-funciona" className="mt-6 rounded-xl border bg-background p-6 shadow-sm sm:p-8">
                <h2 id="como-funciona" className="text-lg font-semibold">
                    Cómo funciona
                </h2>
                <ol className="mt-5 grid gap-6 sm:grid-cols-3">
                    {PASOS.map((p, i) => (
                        <li key={p.titulo} className="flex gap-3">
                            <span
                                className="flex size-8 shrink-0 items-center justify-center rounded-full text-sm font-semibold text-white"
                                style={{ background: 'var(--marca)' }}
                                aria-hidden="true"
                            >
                                {i + 1}
                            </span>
                            <span>
                                <span className="block font-medium">{p.titulo}</span>
                                <span className="block text-sm text-muted-foreground">{p.texto}</span>
                            </span>
                        </li>
                    ))}
                </ol>
            </section>
        </>
    );
}

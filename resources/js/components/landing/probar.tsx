import { ArrowRight, LogIn } from 'lucide-react';
import type { AccesoDemo, Portal } from '@/components/landing/datos';

/** Pruébalo en dos minutos: como cliente (portales) o como equipo (acceso de un clic). */
export function Probar({ portales, accesosDemo }: { portales: Portal[]; accesosDemo: AccesoDemo[] }) {
    const token = typeof document !== 'undefined' ? (document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '') : '';

    return (
        <section id="probar" className="scroll-mt-20 bg-primary text-primary-foreground">
            <div className="mx-auto max-w-7xl px-5 py-28 sm:px-8 sm:py-36">
                <h2 className="revelar max-w-3xl text-[clamp(2rem,4vw,3.4rem)] leading-[1.02] font-semibold tracking-[-0.03em]">Pruébalo en dos minutos.</h2>
                <p className="revelar mt-5 max-w-2xl text-lg leading-relaxed text-primary-foreground/90">
                    Tres empresas de ejemplo con incidencias en todos sus estados. Los datos se reinician cada noche, así que puedes tocarlo todo.
                </p>

                <div className="mt-14 grid gap-12 lg:grid-cols-2">
                    <div className="revelar">
                        <h3 className="text-[clamp(1.5rem,2.4vw,2.1rem)] leading-tight font-semibold tracking-[-0.02em]">Como cliente</h3>
                        <p className="mt-2 text-base text-primary-foreground/90">Abre una incidencia en el portal de una de las empresas y síguela.</p>
                        <ul className="mt-6 space-y-3">
                            {portales.map((p) => (
                                <li key={p.slug}>
                                    <a
                                        href={`/${p.slug}`}
                                        className="group flex items-center justify-between rounded-2xl bg-white/10 px-5 py-4 text-lg font-medium transition hover:bg-white/20 focus-visible:outline-2 focus-visible:outline-white"
                                    >
                                        {p.nombre}
                                        <ArrowRight className="size-5 transition group-hover:translate-x-1" />
                                    </a>
                                </li>
                            ))}
                        </ul>
                    </div>
                    <div className="revelar">
                        <h3 className="text-[clamp(1.5rem,2.4vw,2.1rem)] leading-tight font-semibold tracking-[-0.02em]">Como equipo</h3>
                        <p className="mt-2 text-base text-primary-foreground/90">Entra al panel de la empresa con un clic, sin escribir contraseñas.</p>
                        <div className="mt-6 space-y-3">
                            {accesosDemo.map((a) => (
                                <form key={a.slug} method="post" action={`/demo/entrar/${a.slug}`}>
                                    <input type="hidden" name="_token" value={token} />
                                    <button
                                        type="submit"
                                        className="group flex w-full items-center justify-between rounded-2xl bg-white px-5 py-4 text-left text-lg font-medium text-primary transition hover:bg-white/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white active:scale-[0.99]"
                                    >
                                        {a.etiqueta}
                                        <LogIn className="size-5 transition group-hover:translate-x-1" />
                                    </button>
                                </form>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}

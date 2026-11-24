import { ArrowRight } from 'lucide-react';
import { useState } from 'react';
import { SECTORES } from '@/components/landing/datos';
import type { SectorLanding } from '@/components/landing/datos';
import { cn } from '@/lib/utils';

/**
 * Portada. Lo "vivo" de la página: el visitante elige su sector y ve la bandeja real de ese
 * sector y el recorrido que sigue cada incidencia en él. Sin JavaScript se ve el taller.
 */
export function Portada() {
    const [sector, setSector] = useState<SectorLanding>('taller');
    const s = SECTORES[sector];

    return (
        <section className="mx-auto max-w-7xl px-5 pt-16 pb-8 sm:px-8 sm:pt-24">
            <h1 className="revelar max-w-6xl text-[clamp(2.2rem,6.4vw,5.6rem)] leading-[0.98] font-semibold tracking-[-0.035em]">
                Incidencias a la manera de tu sector.
            </h1>
            <div className="mt-8 grid items-end gap-8 lg:grid-cols-[1.3fr_1fr]">
                <p className="revelar max-w-2xl text-lg leading-relaxed text-foreground">
                    ATTENT es el sistema de incidencias para talleres, administradores de fincas y equipos de soporte. Tus clientes avisan sin
                    contraseña; tu equipo ve qué es urgente, qué plazo vence y qué toca después.
                </p>
                <div className="revelar lg:justify-self-end">
                    <div className="flex flex-wrap gap-3">
                        <a
                            href="#probar"
                            className="inline-flex items-center gap-2 rounded-full bg-primary px-6 py-3 font-medium text-primary-foreground transition hover:bg-primary/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary active:scale-[0.98]"
                        >
                            Probar la demo <ArrowRight className="size-4" />
                        </a>
                        <a
                            href="#como-funciona"
                            className="inline-flex items-center rounded-full bg-secondary px-6 py-3 font-medium text-secondary-foreground transition hover:bg-accent active:scale-[0.98]"
                        >
                            Ver cómo funciona
                        </a>
                    </div>
                </div>
            </div>

            <div className="revelar mt-14">
                <div role="tablist" aria-label="Elige tu sector" className="flex flex-wrap gap-2">
                    {(Object.keys(SECTORES) as SectorLanding[]).map((clave) => (
                        <button
                            key={clave}
                            role="tab"
                            type="button"
                            aria-selected={sector === clave}
                            aria-controls="portada-captura"
                            onClick={() => setSector(clave)}
                            className={cn(
                                'inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-medium transition active:scale-[0.97]',
                                sector === clave ? 'bg-foreground text-background' : 'bg-muted text-foreground hover:bg-accent',
                            )}
                        >
                            <span className="size-2.5 rounded-full" style={{ background: SECTORES[clave].color }} aria-hidden="true" />
                            {SECTORES[clave].nombre}
                        </button>
                    ))}
                </div>

                <p className="mt-5 text-base text-foreground">
                    <span className="text-muted-foreground">Cada incidencia de {s.nombre.toLowerCase()} recorre: </span>
                    {s.recorrido.map((estado, i) => (
                        <span key={estado}>
                            <span className="font-medium">{estado}</span>
                            {i < s.recorrido.length - 1 && <span className="px-1.5 text-muted-foreground" aria-hidden="true">→</span>}
                        </span>
                    ))}
                </p>

                <div id="portada-captura" role="tabpanel" className="mt-6 overflow-hidden rounded-2xl bg-muted p-2 sm:p-3" style={{ outline: `3px solid ${s.color}`, outlineOffset: '-3px' }}>
                    <img key={sector} src={s.captura} alt={s.alt} width={1600} height={1000} className="cambio-captura w-full rounded-xl" />
                </div>
            </div>
        </section>
    );
}

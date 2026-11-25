import type { ReactNode } from 'react';

/** Sección numerada del formulario del portal: «1 · Tus datos», «2 · Dónde y qué»… */
export function PasoFormulario({ numero, titulo, texto, children }: { numero: number; titulo: string; texto?: string; children: ReactNode }) {
    return (
        <section aria-labelledby={`paso-${numero}`} className="grid gap-5 border-t pt-6">
            <div className="flex items-start gap-3">
                <span
                    className="flex size-8 shrink-0 items-center justify-center rounded-full text-sm font-semibold text-white"
                    style={{ background: 'var(--marca)' }}
                    aria-hidden="true"
                >
                    {numero}
                </span>
                <div>
                    <h2 id={`paso-${numero}`} className="text-lg font-semibold">
                        <span className="sr-only">Paso {numero}: </span>
                        {titulo}
                    </h2>
                    {texto && <p className="text-sm text-muted-foreground">{texto}</p>}
                </div>
            </div>
            {children}
        </section>
    );
}

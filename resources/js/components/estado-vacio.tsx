import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';

/** Pantalla o lista vacía que explica qué pasa y qué se puede hacer. */
export function EstadoVacio({ icono: Icono, titulo, texto, accion }: { icono: LucideIcon; titulo: string; texto: string; accion?: ReactNode }) {
    return (
        <div className="flex flex-col items-center px-6 py-12 text-center">
            <span className="flex size-12 items-center justify-center rounded-full bg-accent text-primary">
                <Icono className="size-6" aria-hidden="true" />
            </span>
            <p className="mt-4 font-semibold">{titulo}</p>
            <p className="mt-1 max-w-sm text-sm text-muted-foreground">{texto}</p>
            {accion && <div className="mt-5">{accion}</div>}
        </div>
    );
}

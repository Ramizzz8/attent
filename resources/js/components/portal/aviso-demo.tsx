import { FlaskConical } from 'lucide-react';

/** Modo demostración: en lugar de enviar el email, se enseña el enlace mágico. */
export function AvisoDemo({ enlace, texto }: { enlace: string | null | undefined; texto: string }) {
    if (!enlace) return null;

    return (
        <div className="mt-6 rounded-lg border border-dashed border-amber-400 bg-amber-50 p-4 text-left text-sm dark:bg-amber-950/40">
            <p className="flex items-center gap-2 font-medium">
                <FlaskConical className="h-4 w-4" /> Modo demostración
            </p>
            <p className="mt-1 text-muted-foreground">En esta demo no se envían emails. Este es el enlace que recibirías por correo:</p>
            <a href={enlace} className="mt-3 inline-flex rounded-md px-4 py-2 font-medium text-white" style={{ background: 'var(--marca, #4f46e5)' }}>
                {texto}
            </a>
        </div>
    );
}

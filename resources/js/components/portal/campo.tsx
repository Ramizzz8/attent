import type { ReactNode } from 'react';
import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';

/** Etiqueta + control + error de validación. */
export function Campo({
    id,
    etiqueta,
    error,
    ayuda,
    obligatorio,
    children,
}: {
    id: string;
    etiqueta: string;
    error?: string;
    ayuda?: string;
    obligatorio?: boolean;
    children: ReactNode;
}) {
    return (
        <div className="grid gap-1.5">
            <Label htmlFor={id}>
                {etiqueta}
                {obligatorio && (
                    <>
                        <span className="text-destructive" aria-hidden="true">
                            *
                        </span>
                        <span className="sr-only"> (obligatorio)</span>
                    </>
                )}
            </Label>
            {children}
            {ayuda && <p className="text-xs text-muted-foreground">{ayuda}</p>}
            <InputError message={error} />
        </div>
    );
}

export const claseSelect =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 outline-none';

export const claseTextarea =
    'min-h-28 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 outline-none';

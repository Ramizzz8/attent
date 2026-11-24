import type { ButtonHTMLAttributes } from 'react';
import { cn } from '@/lib/utils';

/** Botón principal del portal con el color de la empresa. */
export function BotonMarca({ className, ...props }: ButtonHTMLAttributes<HTMLButtonElement>) {
    return (
        <button
            {...props}
            className={cn(
                'inline-flex items-center justify-center rounded-md px-5 py-2.5 text-sm font-medium text-white shadow-sm transition hover:opacity-90 disabled:opacity-50',
                className,
            )}
            style={{ background: 'var(--marca)' }}
        />
    );
}

export function enlaceMarca(): string {
    return 'inline-flex items-center justify-center rounded-md px-5 py-2.5 text-sm font-medium text-white shadow-sm transition hover:opacity-90';
}

import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';

/**
 * Avisos emergentes tras una acción: confirmación ("flash.exito") o
 * acción bloqueada en la demo pública ("errors.demo").
 */
export function AvisosFlash() {
    const { flash, errors } = usePage().props as { flash?: { exito?: string | null }; errors?: Record<string, string> };
    const exito = flash?.exito;
    const demo = errors?.demo;

    useEffect(() => {
        if (exito) toast.success(exito);
    }, [exito]);

    useEffect(() => {
        if (demo) toast.warning(demo);
    }, [demo]);

    return null;
}

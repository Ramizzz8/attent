import { usePage } from '@inertiajs/react';
import type { Portal } from '@/types/portal';

/** Datos de la empresa y del cliente del portal actual (compartidos por el middleware). */
export function usePortal(): Portal {
    return usePage().props.portal as Portal;
}

/** Construye una URL del portal: url('nueva') → /talleres-ruiz/nueva */
export function usePortalUrl(): (ruta?: string) => string {
    const { empresa } = usePortal();

    return (ruta = '') => `/${empresa.slug}${ruta ? `/${ruta}` : ''}`;
}

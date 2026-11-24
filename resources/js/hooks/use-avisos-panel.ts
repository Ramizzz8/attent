import { usePage } from '@inertiajs/react';
import { useEcho } from '@laravel/echo-react';

export type AvisoTicket = { numero: number; asunto: string; tipo: 'creado' | 'estado' | 'mensaje' | 'asignado' | 'prioridad' };

/** Escucha el canal privado de la empresa (Reverb) y avisa de cambios en sus tickets. */
export function useAvisosPanel(alRecibir: (aviso: AvisoTicket) => void): void {
    const empresa = usePage().props.empresaPanel as { id: number } | null;

    useEcho<AvisoTicket>(`empresa.${empresa?.id ?? 0}`, '.ticket.actualizado', alRecibir, [empresa?.id]);
}

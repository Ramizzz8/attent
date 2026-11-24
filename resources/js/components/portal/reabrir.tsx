import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { claseTextarea } from '@/components/portal/campo';
import { usePortalUrl } from '@/hooks/use-portal';

/** "¿Sigue sin funcionar?": el cliente reabre la incidencia en los 7 días siguientes a resolverla. */
export function Reabrir({ numero }: { numero: number }) {
    const url = usePortalUrl();
    const [abierto, setAbierto] = useState(false);
    const form = useForm({ motivo: '' });

    function enviar(e: FormEvent) {
        e.preventDefault();
        form.post(url(`tickets/${numero}/reabrir`), { preserveScroll: true });
    }

    if (!abierto) {
        return (
            <div className="flex flex-wrap items-center justify-between gap-3 rounded-xl border bg-background p-4 text-sm">
                <span>¿El problema sigue sin resolverse?</span>
                <button type="button" onClick={() => setAbierto(true)} className="rounded-md border px-4 py-2 font-medium hover:bg-muted">
                    Reabrir incidencia
                </button>
            </div>
        );
    }

    return (
        <form onSubmit={enviar} className="space-y-3 rounded-xl border bg-background p-4">
            <label htmlFor="motivo" className="text-sm font-medium">
                Cuéntanos qué sigue pasando
            </label>
            <textarea id="motivo" className={claseTextarea} value={form.data.motivo} onChange={(e) => form.setData('motivo', e.target.value)} required />
            <InputError message={form.errors.motivo} />
            <button type="submit" disabled={form.processing} className="rounded-md px-4 py-2 text-sm font-medium text-white disabled:opacity-50" style={{ background: 'var(--marca)' }}>
                Reabrir
            </button>
        </form>
    );
}

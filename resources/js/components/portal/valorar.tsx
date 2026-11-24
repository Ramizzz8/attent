import { useForm } from '@inertiajs/react';
import { Star } from 'lucide-react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { claseTextarea } from '@/components/portal/campo';
import { usePortalUrl } from '@/hooks/use-portal';

export function Estrellas({ nota }: { nota: number }) {
    return (
        <span className="inline-flex" aria-label={`${nota} de 5`}>
            {[1, 2, 3, 4, 5].map((n) => (
                <Star key={n} className={`h-4 w-4 ${n <= nota ? 'fill-amber-400 text-amber-400' : 'text-muted-foreground'}`} />
            ))}
        </span>
    );
}

/** El cliente valora la atención (1 a 5) una vez resuelta la incidencia. */
export function Valorar({ numero }: { numero: number }) {
    const url = usePortalUrl();
    const form = useForm({ valoracion: 0, comentario: '' });

    function enviar(e: FormEvent) {
        e.preventDefault();
        form.post(url(`tickets/${numero}/valoracion`), { preserveScroll: true });
    }

    return (
        <form onSubmit={enviar} className="space-y-3 rounded-xl border bg-background p-5">
            <h2 className="font-semibold">¿Qué tal te hemos atendido?</h2>
            <div className="flex gap-1" role="radiogroup" aria-label="Valoración">
                {[1, 2, 3, 4, 5].map((n) => (
                    <button
                        key={n}
                        type="button"
                        role="radio"
                        aria-checked={form.data.valoracion === n}
                        aria-label={`${n} estrellas`}
                        onClick={() => form.setData('valoracion', n)}
                    >
                        <Star className={`h-7 w-7 ${n <= form.data.valoracion ? 'fill-amber-400 text-amber-400' : 'text-muted-foreground'}`} />
                    </button>
                ))}
            </div>
            <textarea
                aria-label="Comentario opcional"
                placeholder="Comentario (opcional)"
                className={claseTextarea}
                maxLength={500}
                value={form.data.comentario}
                onChange={(e) => form.setData('comentario', e.target.value)}
            />
            <InputError message={form.errors.valoracion} />
            <button
                type="submit"
                disabled={form.processing || form.data.valoracion === 0}
                className="rounded-md px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
                style={{ background: 'var(--marca)' }}
            >
                Enviar valoración
            </button>
        </form>
    );
}

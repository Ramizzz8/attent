import { useForm } from '@inertiajs/react';
import { Lock, Paperclip } from 'lucide-react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { claseTextarea } from '@/components/portal/campo';
import { fecha } from '@/components/portal/estado-pill';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

export type AdjuntoResumen = { id: number; nombre: string; mime: string; tamano: number };

export type MensajeVista = {
    id: number;
    cuerpo: string;
    delEquipo: boolean;
    autor: string | null;
    fecha: string;
    interno?: boolean;
    adjuntos: AdjuntoResumen[];
};

export function ListaAdjuntos({ adjuntos, urlBase }: { adjuntos: AdjuntoResumen[]; urlBase: string }) {
    if (adjuntos.length === 0) return null;

    return (
        <ul className="mt-2 flex flex-wrap gap-2">
            {adjuntos.map((a) => (
                <li key={a.id}>
                    <a
                        href={`${urlBase}/${a.id}`}
                        target="_blank"
                        rel="noreferrer"
                        className="inline-flex items-center gap-1 rounded border bg-background px-2 py-1 text-xs hover:bg-muted"
                    >
                        <Paperclip className="h-3 w-3" />
                        {a.nombre}
                    </a>
                </li>
            ))}
        </ul>
    );
}

/**
 * Hilo de mensajes de un ticket con formulario de respuesta.
 * En el panel (permiteInterno) el empleado puede marcar la respuesta como nota interna.
 */
export function Conversacion({
    mensajes,
    urlEnviar,
    urlAdjuntos,
    permiteInterno = false,
    lado,
}: {
    mensajes: MensajeVista[];
    urlEnviar: string;
    urlAdjuntos: string;
    permiteInterno?: boolean;
    lado: 'cliente' | 'equipo';
}) {
    const form = useForm<{ cuerpo: string; interno: boolean; adjuntos: File[] }>({ cuerpo: '', interno: false, adjuntos: [] });
    const errores = form.errors as Record<string, string>;

    function enviar(e: FormEvent) {
        e.preventDefault();
        form.post(urlEnviar, { forceFormData: true, preserveScroll: true, onSuccess: () => form.reset() });
    }

    return (
        <section className="rounded-xl border bg-background p-6 shadow-sm">
            <h2 className="mb-4 font-semibold">Conversación</h2>
            {mensajes.length === 0 && <p className="mb-4 text-sm text-muted-foreground">Aún no hay mensajes.</p>}
            <ol className="space-y-3">
                {mensajes.map((m) => {
                    const propio = lado === 'equipo' ? m.delEquipo : !m.delEquipo;

                    return (
                        <li key={m.id} className={cn('flex', propio ? 'justify-end' : 'justify-start')}>
                            <div
                                className={cn(
                                    'max-w-[85%] rounded-lg border p-3 text-sm',
                                    m.interno ? 'border-amber-300 bg-amber-50 dark:border-amber-800 dark:bg-amber-950/40' : propio ? 'bg-muted' : 'bg-background',
                                )}
                            >
                                <p className="mb-1 flex items-center gap-1 text-xs text-muted-foreground">
                                    {m.interno && <Lock className="h-3 w-3" />}
                                    <span className="font-medium">{m.autor}</span> · {fecha(m.fecha)}
                                    {m.interno && ' · nota interna'}
                                </p>
                                <p className="whitespace-pre-line">{m.cuerpo}</p>
                                <ListaAdjuntos adjuntos={m.adjuntos} urlBase={urlAdjuntos} />
                            </div>
                        </li>
                    );
                })}
            </ol>

            <form onSubmit={enviar} className="mt-5 space-y-3 border-t pt-4">
                <textarea
                    aria-label="Escribe un mensaje"
                    placeholder={form.data.interno ? 'Nota interna (el cliente no la verá)' : 'Escribe un mensaje'}
                    className={claseTextarea}
                    value={form.data.cuerpo}
                    onChange={(e) => form.setData('cuerpo', e.target.value)}
                    required
                />
                <InputError message={errores.cuerpo} />
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div className="flex flex-wrap items-center gap-3">
                        <Input
                            type="file"
                            multiple
                            accept=".jpg,.jpeg,.png,.webp,.pdf"
                            className="max-w-64"
                            aria-label="Adjuntar archivos"
                            onChange={(e) => form.setData('adjuntos', Array.from(e.target.files ?? []))}
                        />
                        {permiteInterno && (
                            <label className="flex items-center gap-2 text-sm">
                                <input type="checkbox" checked={form.data.interno} onChange={(e) => form.setData('interno', e.target.checked)} />
                                Nota interna
                            </label>
                        )}
                    </div>
                    <button
                        type="submit"
                        disabled={form.processing}
                        className="rounded-md px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
                        style={{ background: 'var(--marca, #2563eb)' }}
                    >
                        Enviar
                    </button>
                </div>
                <InputError message={errores.adjuntos ?? Object.entries(errores).find(([k]) => k.startsWith('adjuntos.'))?.[1]} />
            </form>
        </section>
    );
}

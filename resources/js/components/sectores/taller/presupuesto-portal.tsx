import { useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { claseTextarea } from '@/components/portal/campo';
import { TablaPresupuesto } from '@/components/sectores/taller/presupuesto';
import type { PresupuestoVista } from '@/components/sectores/taller/presupuesto';
import { usePortalUrl } from '@/hooks/use-portal';

/** El cliente ve el presupuesto y lo aprueba o rechaza. */
export function PresupuestoPortal({ numero, presupuesto }: { numero: number; presupuesto: PresupuestoVista }) {
    const url = usePortalUrl();
    const form = useForm({ aprueba: true, comentario: '' });
    const errores = form.errors as Record<string, string>;

    function responder(aprueba: boolean) {
        form.transform((d) => ({ ...d, aprueba }));
        form.post(url(`tickets/${numero}/presupuesto`), { preserveScroll: true });
    }

    return (
        <section className="space-y-4 rounded-xl border-2 bg-background p-6 shadow-sm" style={{ borderColor: presupuesto.puedeResponder ? 'var(--marca)' : undefined }}>
            <h2 className="font-semibold">Presupuesto de la reparación</h2>
            <TablaPresupuesto p={presupuesto} urlPdf={url(`tickets/${numero}/presupuesto/${presupuesto.version}/pdf`)} />
            {presupuesto.puedeResponder && (
                <div className="space-y-3 border-t pt-4">
                    <p className="text-sm">No empezaremos la reparación hasta que lo apruebes.</p>
                    <textarea
                        aria-label="Comentario opcional"
                        placeholder="Comentario (opcional)"
                        className={claseTextarea}
                        value={form.data.comentario}
                        onChange={(e) => form.setData('comentario', e.target.value)}
                    />
                    <div className="flex flex-wrap gap-3">
                        <button
                            type="button"
                            disabled={form.processing}
                            onClick={() => responder(true)}
                            className="rounded-md px-5 py-2.5 text-sm font-medium text-white disabled:opacity-50"
                            style={{ background: 'var(--marca)' }}
                        >
                            Aprobar presupuesto
                        </button>
                        <button type="button" disabled={form.processing} onClick={() => responder(false)} className="rounded-md border px-5 py-2.5 text-sm font-medium hover:bg-muted">
                            Rechazar
                        </button>
                    </div>
                    <InputError message={errores.presupuesto} />
                </div>
            )}
        </section>
    );
}

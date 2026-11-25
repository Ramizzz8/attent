import { router } from '@inertiajs/react';
import { Users } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Campo, claseSelect } from '@/components/portal/campo';
import { setDato } from '@/components/sectores/campos-sector';
import type { PropsCamposSector } from '@/components/sectores/campos-sector';
import { Input } from '@/components/ui/input';
import { usePortal, usePortalUrl } from '@/hooks/use-portal';

type Edificio = { id: number; nombre: string; direccion: string; zonas: { id: number; nombre: string }[] };
type Duplicado = { numero: number; asunto: string; estado: string; creado: string; afectados: number };

/** Aviso "esta incidencia ya existe" con botón para sumarse en lugar de duplicarla. */
function AvisoDuplicado({ duplicado, nombre, email }: { duplicado: Duplicado; nombre: string; email: string }) {
    const { cliente } = usePortal();
    const url = usePortalUrl();
    const [enviando, setEnviando] = useState(false);

    function sumarme() {
        setEnviando(true);
        router.post(url(`tickets/${duplicado.numero}/afectado`), cliente ? {} : { nombre, email }, { onFinish: () => setEnviando(false) });
    }

    return (
        <div className="rounded-lg border-2 border-amber-300 bg-amber-50 p-4 text-sm dark:border-amber-700 dark:bg-amber-950/40">
            <p className="flex items-center gap-2 font-medium">
                <Users className="h-4 w-4" /> Ya hay una incidencia abierta igual en tu edificio
            </p>
            <p className="mt-1">
                «{duplicado.asunto}» · {duplicado.estado} · {duplicado.afectados} {duplicado.afectados === 1 ? 'vecino afectado' : 'vecinos afectados'}
            </p>
            <p className="mt-1 text-muted-foreground">Si es lo mismo, súmate: te avisaremos de cómo avanza y ayudas a que se priorice.</p>
            {!cliente && (!nombre || !email) && <p className="mt-2 text-xs">Rellena tu nombre y email arriba para sumarte.</p>}
            <button
                type="button"
                disabled={enviando || (!cliente && (!nombre || !email))}
                onClick={sumarme}
                className="mt-3 rounded-md px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
                style={{ background: 'var(--marca)' }}
            >
                A mí también me afecta
            </button>
        </div>
    );
}

export function CamposFincas({ form, extra }: PropsCamposSector) {
    const errores = form.errors as Record<string, string>;
    const edificios = (extra.edificios as Edificio[]) ?? [];
    const url = usePortalUrl();
    const edificio = edificios.find((e) => e.id === Number(form.data.edificio_id));
    const [duplicado, setDuplicado] = useState<Duplicado | null>(null);

    useEffect(() => {
        if (!form.data.edificio_id || !form.data.zona_id || !form.data.categoria) {
            setDuplicado(null);

            return;
        }
        const params = new URLSearchParams({
            edificio_id: String(form.data.edificio_id),
            zona_id: String(form.data.zona_id),
            categoria: form.data.categoria,
        });
        const control = new AbortController();
        fetch(`${url('duplicados')}?${params}`, { headers: { Accept: 'application/json' }, signal: control.signal })
            .then((r) => r.json())
            .then((r) => setDuplicado(r.ticket))
            .catch(() => undefined);

        return () => control.abort();
    }, [form.data.edificio_id, form.data.zona_id, form.data.categoria]); // eslint-disable-line react-hooks/exhaustive-deps

    return (
        <>
            <fieldset className="grid gap-4 sm:grid-cols-3">
                <legend className="mb-2 text-sm font-medium">¿Dónde es?</legend>
                <Campo id="edificio" etiqueta="Edificio" obligatorio error={errores.edificio_id}>
                    <select
                        id="edificio"
                        className={claseSelect}
                        value={form.data.edificio_id ?? ''}
                        onChange={(e) => form.setData({ ...form.data, edificio_id: e.target.value ? Number(e.target.value) : '', zona_id: '' })}
                        required
                    >
                        <option value="">Elige tu edificio</option>
                        {edificios.map((e) => (
                            <option key={e.id} value={e.id}>
                                {e.nombre}
                            </option>
                        ))}
                    </select>
                </Campo>
                <Campo id="zona" etiqueta="Zona" obligatorio error={errores.zona_id}>
                    <select
                        id="zona"
                        className={claseSelect}
                        value={form.data.zona_id ?? ''}
                        onChange={(e) => form.setData('zona_id', e.target.value ? Number(e.target.value) : '')}
                        disabled={!edificio}
                        required
                    >
                        <option value="">Elige la zona</option>
                        {edificio?.zonas.map((z) => (
                            <option key={z.id} value={z.id}>
                                {z.nombre}
                            </option>
                        ))}
                    </select>
                </Campo>
                <Campo id="vivienda" etiqueta="Tu piso y puerta" obligatorio error={errores['datos.vivienda']}>
                    <Input id="vivienda" value={String(form.data.datos.vivienda ?? '')} onChange={(e) => setDato(form, 'vivienda', e.target.value)} placeholder="3.º B" required />
                </Campo>
            </fieldset>
            {duplicado && <AvisoDuplicado duplicado={duplicado} nombre={form.data.nombre} email={form.data.email} />}
        </>
    );
}

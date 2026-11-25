import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { BotonMarca } from '@/components/portal/boton-marca';
import { Campo, claseTextarea } from '@/components/portal/campo';
import { PasoFormulario } from '@/components/portal/paso-formulario';
import { CamposSector } from '@/components/sectores/campos-sector';
import type { FormularioTicket } from '@/components/sectores/campos-sector';
import { Input } from '@/components/ui/input';
import { usePortal } from '@/hooks/use-portal';
import { cn } from '@/lib/utils';
import type { Opcion } from '@/types/portal';

export default function Nueva({ categorias, ...extra }: { categorias: Opcion[] } & Record<string, unknown>) {
    const { empresa, cliente } = usePortal();
    const form = useForm<FormularioTicket>({
        nombre: '',
        email: '',
        telefono: '',
        asunto: '',
        descripcion: '',
        categoria: '',
        impacto: '',
        datos: {},
        ...((extra.valoresIniciales as Partial<FormularioTicket>) ?? {}),
    });
    const errores = form.errors as Record<string, string>;
    const primero = cliente ? 0 : 1;

    function enviar(e: FormEvent) {
        e.preventDefault();
        form.post(`/${empresa.slug}/tickets`, { forceFormData: true });
    }

    return (
        <>
            <Head title="Nueva incidencia" />
            <form onSubmit={enviar} className="space-y-6 rounded-xl border bg-background p-6 shadow-sm">
                <div>
                    <h1 className="text-2xl font-semibold">Nueva incidencia</h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Rellena {cliente ? 'los dos pasos' : 'los tres pasos'}. Los campos marcados con <span className="text-destructive">*</span> son
                        obligatorios.
                    </p>
                </div>

                {!cliente && (
                    <PasoFormulario numero={1} titulo="Tus datos" texto="Para avisarte de cada novedad.">
                        <div className="grid gap-4 sm:grid-cols-2">
                        <Campo id="nombre" etiqueta="Nombre" obligatorio error={errores.nombre}>
                            <Input id="nombre" value={form.data.nombre} onChange={(e) => form.setData('nombre', e.target.value)} required />
                        </Campo>
                        <Campo id="email" etiqueta="Email" obligatorio error={errores.email} ayuda="Te enviaremos un enlace para confirmar.">
                            <Input id="email" type="email" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} required />
                        </Campo>
                        <Campo id="telefono" etiqueta="Teléfono (opcional)" error={errores.telefono}>
                            <Input id="telefono" value={form.data.telefono} onChange={(e) => form.setData('telefono', e.target.value)} />
                        </Campo>
                        </div>
                    </PasoFormulario>
                )}

                <PasoFormulario numero={primero + 1} titulo="Dónde y qué" texto="Así llega directa a la persona adecuada.">
                <CamposSector sector={empresa.sector} form={form} extra={extra} />

                <Campo id="categoria" etiqueta="Tipo de problema" obligatorio error={errores.categoria}>
                    <div className="flex flex-wrap gap-2">
                        {categorias.map((c) => (
                            <button
                                key={c.valor}
                                type="button"
                                onClick={() => form.setData('categoria', c.valor)}
                                className={cn(
                                    'rounded-full border px-4 py-1.5 text-sm transition',
                                    form.data.categoria === c.valor ? 'border-transparent text-white' : 'hover:bg-muted',
                                )}
                                style={form.data.categoria === c.valor ? { background: 'var(--marca)' } : undefined}
                            >
                                {c.etiqueta}
                            </button>
                        ))}
                    </div>
                </Campo>
                </PasoFormulario>

                <PasoFormulario numero={primero + 2} titulo="Descripción y fotos" texto="Cuanto más detalle, antes lo resolvemos.">
                <Campo id="asunto" etiqueta="Resumen" obligatorio error={errores.asunto}>
                    <Input id="asunto" maxLength={150} value={form.data.asunto} onChange={(e) => form.setData('asunto', e.target.value)} required />
                </Campo>

                <Campo id="descripcion" etiqueta="Describe el problema" obligatorio error={errores.descripcion}>
                    <textarea
                        id="descripcion"
                        className={claseTextarea}
                        value={form.data.descripcion}
                        onChange={(e) => form.setData('descripcion', e.target.value)}
                        required
                    />
                </Campo>

                <Campo
                    id="adjuntos"
                    etiqueta="Fotos o documentos (opcional)"
                    error={errores.adjuntos ?? Object.entries(errores).find(([k]) => k.startsWith('adjuntos.'))?.[1]}
                    ayuda="JPG, PNG, WEBP o PDF. Máximo 5 archivos de 5 MB."
                >
                    <Input
                        id="adjuntos"
                        type="file"
                        multiple
                        accept=".jpg,.jpeg,.png,.webp,.pdf"
                        onChange={(e) => form.setData('adjuntos', Array.from(e.target.files ?? []))}
                    />
                </Campo>
                </PasoFormulario>

                <div className="flex justify-end border-t pt-6">
                    <BotonMarca type="submit" disabled={form.processing}>
                        {form.processing ? 'Enviando…' : 'Enviar incidencia'}
                    </BotonMarca>
                </div>
            </form>
        </>
    );
}

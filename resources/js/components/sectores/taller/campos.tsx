import { Campo } from '@/components/portal/campo';
import { setDato } from '@/components/sectores/campos-sector';
import type { PropsCamposSector } from '@/components/sectores/campos-sector';
import { Input } from '@/components/ui/input';

export function CamposTaller({ form }: PropsCamposSector) {
    const errores = form.errors as Record<string, string>;
    const d = form.data.datos;

    return (
        <fieldset className="grid gap-4 sm:grid-cols-2">
            <legend className="mb-2 text-sm font-medium">Tu vehículo</legend>
            <Campo id="matricula" etiqueta="Matrícula" obligatorio error={errores['datos.matricula']}>
                <Input
                    id="matricula"
                    className="uppercase"
                    value={String(d.matricula ?? '')}
                    onChange={(e) => setDato(form, 'matricula', e.target.value)}
                    placeholder="1234 ABC"
                    required
                />
            </Campo>
            <Campo id="km" etiqueta="Kilómetros (aprox.)" error={errores['datos.km']}>
                <Input id="km" type="number" min={0} value={String(d.km ?? '')} onChange={(e) => setDato(form, 'km', e.target.value)} />
            </Campo>
            <Campo id="marca" etiqueta="Marca" error={errores['datos.marca']}>
                <Input id="marca" value={String(d.marca ?? '')} onChange={(e) => setDato(form, 'marca', e.target.value)} />
            </Campo>
            <Campo id="modelo" etiqueta="Modelo" error={errores['datos.modelo']}>
                <Input id="modelo" value={String(d.modelo ?? '')} onChange={(e) => setDato(form, 'modelo', e.target.value)} />
            </Campo>
        </fieldset>
    );
}

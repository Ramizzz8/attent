import { Campo } from '@/components/portal/campo';
import { setDato } from '@/components/sectores/campos-sector';
import type { PropsCamposSector } from '@/components/sectores/campos-sector';
import { Input } from '@/components/ui/input';

export function CamposFincas({ form }: PropsCamposSector) {
    const errores = form.errors as Record<string, string>;

    return (
        <fieldset className="grid gap-4 sm:grid-cols-2">
            <legend className="mb-2 text-sm font-medium text-muted-foreground">Tu vivienda</legend>
            <Campo id="vivienda" etiqueta="Piso y puerta" error={errores['datos.vivienda']}>
                <Input
                    id="vivienda"
                    value={String(form.data.datos.vivienda ?? '')}
                    onChange={(e) => setDato(form, 'vivienda', e.target.value)}
                    placeholder="3.º B"
                    required
                />
            </Campo>
        </fieldset>
    );
}

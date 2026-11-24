import { Campo, claseSelect } from '@/components/portal/campo';
import type { PropsCamposSector } from '@/components/sectores/campos-sector';

const IMPACTOS = [
    { valor: 'individual', etiqueta: 'Solo me afecta a mí' },
    { valor: 'departamento', etiqueta: 'Afecta a mi departamento o aula' },
    { valor: 'todos', etiqueta: 'Afecta a todo el centro' },
];

export function CamposSoporteIt({ form }: PropsCamposSector) {
    const errores = form.errors as Record<string, string>;

    return (
        <fieldset className="grid gap-4 sm:grid-cols-2">
            <legend className="mb-2 text-sm font-medium text-muted-foreground">Alcance</legend>
            <Campo id="impacto" etiqueta="¿A quién afecta?" error={errores.impacto}>
                <select id="impacto" className={claseSelect} value={form.data.impacto} onChange={(e) => form.setData('impacto', e.target.value)} required>
                    <option value="">Elige una opción</option>
                    {IMPACTOS.map((i) => (
                        <option key={i.valor} value={i.valor}>
                            {i.etiqueta}
                        </option>
                    ))}
                </select>
            </Campo>
        </fieldset>
    );
}

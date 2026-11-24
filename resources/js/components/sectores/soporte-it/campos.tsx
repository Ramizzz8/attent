import { Campo, claseSelect } from '@/components/portal/campo';
import type { PropsCamposSector } from '@/components/sectores/campos-sector';
import { Input } from '@/components/ui/input';

const IMPACTOS = [
    { valor: 'individual', etiqueta: 'Solo me afecta a mí' },
    { valor: 'departamento', etiqueta: 'Afecta a mi departamento o aula' },
    { valor: 'todos', etiqueta: 'Afecta a todo el centro' },
];

type Equipo = { codigo: string; descripcion: string };

export function CamposSoporteIt({ form, extra }: PropsCamposSector) {
    const errores = form.errors as Record<string, string>;
    const equipos = (extra.equipos as Equipo[]) ?? [];
    const elegido = equipos.find((e) => e.codigo === (form.data.equipo_codigo ?? '').toUpperCase());

    return (
        <fieldset className="grid gap-4 sm:grid-cols-2">
            <legend className="mb-2 text-sm font-medium text-muted-foreground">Equipo y alcance</legend>
            <Campo
                id="equipo"
                etiqueta="Código del equipo (opcional)"
                error={errores.equipo_codigo}
                ayuda={elegido ? elegido.descripcion : 'Está en la etiqueta del equipo. Déjalo vacío si es de cuentas o red.'}
            >
                <Input
                    id="equipo"
                    list="lista-equipos"
                    className="uppercase"
                    placeholder="PRY-012"
                    value={form.data.equipo_codigo ?? ''}
                    onChange={(e) => form.setData('equipo_codigo', e.target.value)}
                />
                <datalist id="lista-equipos">
                    {equipos.map((e) => (
                        <option key={e.codigo} value={e.codigo}>
                            {e.descripcion}
                        </option>
                    ))}
                </datalist>
            </Campo>
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

import type { InertiaFormProps } from '@inertiajs/react';
import { CamposFincas } from '@/components/sectores/fincas/campos';
import { CamposSoporteIt } from '@/components/sectores/soporte-it/campos';
import { CamposTaller } from '@/components/sectores/taller/campos';
import type { SectorClave } from '@/types/portal';

export type FormularioTicket = {
    nombre: string;
    email: string;
    telefono: string;
    asunto: string;
    descripcion: string;
    categoria: string;
    impacto: string;
    edificio_id?: number | '';
    zona_id?: number | '';
    equipo_codigo?: string;
    vehiculo_id?: number | '';
    adjuntos?: File[];
    datos: Record<string, string | number>;
};

export type PropsCamposSector = {
    form: InertiaFormProps<FormularioTicket>;
    extra: Record<string, unknown>;
};

/** Campos del formulario del portal que dependen del sector de la empresa. */
export function CamposSector({ sector, ...props }: { sector: SectorClave } & PropsCamposSector) {
    switch (sector) {
        case 'taller':
            return <CamposTaller {...props} />;
        case 'fincas':
            return <CamposFincas {...props} />;
        case 'soporte_it':
            return <CamposSoporteIt {...props} />;
    }
}

export function setDato(form: PropsCamposSector['form'], clave: string, valor: string | number) {
    form.setData('datos', { ...form.data.datos, [clave]: valor });
}

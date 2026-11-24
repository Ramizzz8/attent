export type SectorClave = 'taller' | 'fincas' | 'soporte_it';

export type PortalEmpresa = {
    nombre: string;
    slug: string;
    sector: SectorClave;
    color: string;
    logo: string | null;
    texto: string | null;
};

export type PortalCliente = {
    nombre: string;
    email: string;
    tienePassword: boolean;
};

export type Portal = {
    empresa: PortalEmpresa;
    cliente: PortalCliente | null;
};

export type Opcion = { valor: string; etiqueta: string };

export type SectorLanding = 'taller' | 'fincas' | 'soporte_it';

/** Contenido de la landing por sector: textos, captura real y recorrido de estados (los del propio motor). */
export const SECTORES: Record<
    SectorLanding,
    { nombre: string; color: string; captura: string; alt: string; recorrido: string[] }
> = {
    taller: {
        nombre: 'Talleres',
        color: '#dc2626',
        captura: '/images/landing/bandeja-taller.webp',
        alt: 'Bandeja de un taller en ATTENT: incidencias con su matrícula, estado, prioridad y plazo',
        recorrido: ['Recibido', 'En diagnóstico', 'Presupuesto enviado', 'En reparación', 'Listo para recoger', 'Entregado'],
    },
    fincas: {
        nombre: 'Administradores de fincas',
        color: '#0d9488',
        captura: '/images/landing/bandeja-fincas.webp',
        alt: 'Bandeja de un administrador de fincas en ATTENT: incidencias por edificio y zona común',
        recorrido: ['Reportada', 'En revisión', 'Asignada a proveedor', 'Visita programada', 'Resuelta'],
    },
    soporte_it: {
        nombre: 'Soporte informático',
        color: '#4f46e5',
        captura: '/images/landing/bandeja-soporte.webp',
        alt: 'Bandeja de un equipo de soporte en ATTENT: incidencias por equipo con su prioridad y plazo',
        recorrido: ['Nuevo', 'Asignado', 'En curso', 'Resuelto', 'Cerrado'],
    },
};

export type Portal = { nombre: string; sector: SectorLanding; slug: string; color: string };
export type AccesoDemo = { sector: SectorLanding; etiqueta: string; slug: string };

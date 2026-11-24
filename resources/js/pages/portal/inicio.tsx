import { Head, Link } from '@inertiajs/react';
import { enlaceMarca } from '@/components/portal/boton-marca';
import { usePortal } from '@/hooks/use-portal';

const TEXTO_POR_SECTOR = {
    taller: 'Cuéntanos qué le pasa a tu vehículo y sigue la reparación desde aquí: diagnóstico, presupuesto y aviso cuando esté listo.',
    fincas: 'Avisa de cualquier avería o problema en las zonas comunes de tu edificio y sigue cómo se resuelve.',
    soporte_it: 'Reporta problemas con tu equipo, la red o tus cuentas y sigue su resolución.',
};

export default function Inicio() {
    const { empresa, cliente } = usePortal();
    const base = `/${empresa.slug}`;

    return (
        <>
            <Head title={empresa.nombre} />
            <section className="rounded-xl border bg-background p-8 text-center shadow-sm">
                <h1 className="text-3xl font-semibold tracking-tight">¿En qué podemos ayudarte?</h1>
                <p className="mx-auto mt-3 max-w-lg text-muted-foreground">{empresa.texto ?? TEXTO_POR_SECTOR[empresa.sector]}</p>
                <div className="mt-6 flex flex-wrap justify-center gap-3">
                    <Link href={`${base}/nueva`} className={enlaceMarca()} style={{ background: 'var(--marca)' }}>
                        Abrir una incidencia
                    </Link>
                    <Link
                        href={cliente ? `${base}/mis-tickets` : `${base}/acceder`}
                        className="inline-flex items-center rounded-md border px-5 py-2.5 text-sm font-medium hover:bg-muted"
                    >
                        Ver mis incidencias
                    </Link>
                </div>
                <p className="mt-6 text-xs text-muted-foreground">No necesitas contraseña: te enviaremos un enlace a tu email.</p>
            </section>
        </>
    );
}

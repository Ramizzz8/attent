import { Head, Link, usePage } from '@inertiajs/react';
import { Building2, Car, Monitor } from 'lucide-react';
import { dashboard, login } from '@/routes';

type Portal = { nombre: string; sector: string; slug: string; color: string };

const ICONO: Record<string, typeof Car> = { taller: Car, fincas: Building2, soporte_it: Monitor };

const DESCRIPCION: Record<string, string> = {
    taller: 'Presupuestos que el cliente aprueba desde el portal y vehículos con historial.',
    fincas: 'Varios edificios, aviso de incidencias repetidas y proveedores externos sin cuenta.',
    soporte_it: 'Inventario de equipos, matriz impacto × urgencia y plazos de primera respuesta.',
};

export default function Welcome({ portales = [] }: { portales?: Portal[] }) {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="ATTENT · incidencias por sector" />
            <div className="min-h-screen bg-background px-6 py-16 text-foreground">
                <div className="mx-auto max-w-4xl">
                    <h1 className="text-4xl font-semibold tracking-tight sm:text-5xl">ATTENT</h1>
                    <p className="mt-4 max-w-2xl text-lg text-muted-foreground">
                        Gestión de incidencias para empresas en la que cada sector funciona a su manera. Los clientes abren y siguen sus incidencias sin
                        contraseña; el equipo las gestiona con plazos, notas internas y avisos en tiempo real.
                    </p>
                    <div className="mt-6 flex flex-wrap gap-3">
                        <Link href={auth.user ? dashboard() : login()} className="rounded-md bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground">
                            {auth.user ? 'Ir al panel' : 'Entrar al panel del equipo'}
                        </Link>
                    </div>

                    {portales.length > 0 && (
                        <section className="mt-14">
                            <h2 className="text-sm font-semibold tracking-wide text-muted-foreground uppercase">Prueba el portal de clientes</h2>
                            <div className="mt-4 grid gap-4 sm:grid-cols-3">
                                {portales.map((p) => {
                                    const Icono = ICONO[p.sector] ?? Building2;

                                    return (
                                        <a key={p.slug} href={`/${p.slug}`} className="group rounded-xl border p-5 transition hover:shadow-md">
                                            <Icono className="h-6 w-6" style={{ color: p.color }} />
                                            <p className="mt-3 font-semibold group-hover:underline">{p.nombre}</p>
                                            <p className="mt-1 text-sm text-muted-foreground">{DESCRIPCION[p.sector]}</p>
                                        </a>
                                    );
                                })}
                            </div>
                        </section>
                    )}
                </div>
            </div>
        </>
    );
}

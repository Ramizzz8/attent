import { Head, Link, usePage } from '@inertiajs/react';
import { dashboard, login } from '@/routes';

export default function Welcome() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Atiende" />
            <div className="flex min-h-screen flex-col items-center justify-center gap-6 bg-background p-6 text-foreground">
                <h1 className="text-4xl font-semibold tracking-tight">Atiende</h1>
                <p className="max-w-md text-center text-muted-foreground">
                    Gestión de incidencias para talleres, administradores de fincas y equipos de soporte informático.
                </p>
                <Link
                    href={auth.user ? dashboard() : login()}
                    className="rounded-md bg-primary px-5 py-2 text-sm font-medium text-primary-foreground"
                >
                    {auth.user ? 'Ir al panel' : 'Acceso empleados'}
                </Link>
            </div>
        </>
    );
}

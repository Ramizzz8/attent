import { Head, Link, usePage } from '@inertiajs/react';
import { LinkIcon } from 'lucide-react';
import type { Portal } from '@/types/portal';

export default function EnlaceCaducado() {
    const portal = usePage().props.portal as Portal | null;

    return (
        <>
            <Head title="Enlace caducado" />
            <section className="w-full rounded-xl border bg-background p-8 text-center shadow-sm">
                <LinkIcon className="mx-auto h-10 w-10 text-muted-foreground" />
                <h1 className="mt-4 text-2xl font-semibold">Este enlace ha caducado</h1>
                <p className="mx-auto mt-2 max-w-md text-muted-foreground">
                    Por seguridad, los enlaces de acceso solo sirven durante un tiempo limitado. Pide uno nuevo y te lo enviaremos a tu email.
                </p>
                {portal && (
                    <Link
                        href={`/${portal.empresa.slug}/acceder`}
                        className="mt-6 inline-flex rounded-md px-5 py-2.5 text-sm font-medium text-white"
                        style={{ background: portal.empresa.color }}
                    >
                        Pedir un enlace nuevo
                    </Link>
                )}
            </section>
        </>
    );
}

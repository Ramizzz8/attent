import { Head } from '@inertiajs/react';
import { MailCheck } from 'lucide-react';

export default function Pendiente() {
    return (
        <>
            <Head title="Revisa tu email" />
            <section className="rounded-xl border bg-background p-8 text-center shadow-sm">
                <MailCheck className="mx-auto h-12 w-12" style={{ color: 'var(--marca)' }} />
                <h1 className="mt-4 text-2xl font-semibold">Revisa tu email</h1>
                <p className="mx-auto mt-2 max-w-md text-muted-foreground">
                    Te hemos enviado un enlace para confirmar tu incidencia. En cuanto lo pulses, llegará a nuestro equipo y podrás seguirla
                    desde aquí. El enlace caduca en 24 horas.
                </p>
            </section>
        </>
    );
}

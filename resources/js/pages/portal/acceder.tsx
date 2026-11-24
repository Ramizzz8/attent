import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { AvisoDemo } from '@/components/portal/aviso-demo';
import { BotonMarca } from '@/components/portal/boton-marca';
import { Campo } from '@/components/portal/campo';
import { Input } from '@/components/ui/input';
import { usePortalUrl } from '@/hooks/use-portal';

export default function Acceder({ status, enlaceDemo }: { status?: string; enlaceDemo?: string | null }) {
    const url = usePortalUrl();
    const [conPassword, setConPassword] = useState(false);
    const form = useForm({ email: '', password: '' });

    function enviar(e: FormEvent) {
        e.preventDefault();
        form.post(conPassword ? url('login') : url('acceder'), { preserveScroll: true });
    }

    return (
        <>
            <Head title="Acceder" />
            <form onSubmit={enviar} className="mx-auto max-w-md space-y-5 rounded-xl border bg-background p-6 shadow-sm">
                <h1 className="text-2xl font-semibold">Ver mis incidencias</h1>
                <p className="text-sm text-muted-foreground">
                    {conPassword ? 'Entra con tu email y contraseña.' : 'Escribe tu email y te enviaremos un enlace para entrar, sin contraseña.'}
                </p>
                {status && <p className="rounded-md bg-green-50 p-3 text-sm text-green-800 dark:bg-green-950 dark:text-green-200">{status}</p>}
                <AvisoDemo enlace={enlaceDemo} texto="Entrar (enlace del email)" />
                <Campo id="email" etiqueta="Email" error={form.errors.email}>
                    <Input id="email" type="email" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} required />
                </Campo>
                {conPassword && (
                    <Campo id="password" etiqueta="Contraseña" error={form.errors.password}>
                        <Input id="password" type="password" value={form.data.password} onChange={(e) => form.setData('password', e.target.value)} required />
                    </Campo>
                )}
                <BotonMarca type="submit" className="w-full" disabled={form.processing}>
                    {conPassword ? 'Entrar' : 'Enviarme el enlace'}
                </BotonMarca>
                <button type="button" className="w-full text-center text-sm text-muted-foreground hover:underline" onClick={() => setConPassword(!conPassword)}>
                    {conPassword ? 'Prefiero recibir un enlace por email' : 'Tengo contraseña'}
                </button>
            </form>
        </>
    );
}

import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { BotonMarca } from '@/components/portal/boton-marca';
import { Campo } from '@/components/portal/campo';
import { Input } from '@/components/ui/input';
import { usePortal, usePortalUrl } from '@/hooks/use-portal';

export default function Cuenta({ status }: { status?: string }) {
    const { cliente } = usePortal();
    const url = usePortalUrl();
    const form = useForm({ password: '', password_confirmation: '' });

    function enviar(e: FormEvent) {
        e.preventDefault();
        form.post(url('cuenta/password'), { onSuccess: () => form.reset() });
    }

    return (
        <>
            <Head title="Mi cuenta" />
            <form onSubmit={enviar} className="mx-auto max-w-md space-y-5 rounded-xl border bg-background p-6 shadow-sm">
                <h1 className="text-2xl font-semibold">Mi cuenta</h1>
                <p className="text-sm text-muted-foreground">
                    {cliente?.nombre} · {cliente?.email}
                </p>
                <p className="text-sm">
                    {cliente?.tienePassword
                        ? 'Ya tienes contraseña. Puedes cambiarla aquí.'
                        : 'Opcional: crea una contraseña para entrar sin esperar el enlace por email.'}
                </p>
                {status && <p className="rounded-md bg-green-50 p-3 text-sm text-green-800 dark:bg-green-950 dark:text-green-200">{status}</p>}
                <Campo id="password" etiqueta="Nueva contraseña" error={form.errors.password}>
                    <Input id="password" type="password" value={form.data.password} onChange={(e) => form.setData('password', e.target.value)} required />
                </Campo>
                <Campo id="password_confirmation" etiqueta="Repite la contraseña">
                    <Input
                        id="password_confirmation"
                        type="password"
                        value={form.data.password_confirmation}
                        onChange={(e) => form.setData('password_confirmation', e.target.value)}
                        required
                    />
                </Campo>
                <BotonMarca type="submit" className="w-full" disabled={form.processing}>
                    Guardar contraseña
                </BotonMarca>
            </form>
        </>
    );
}

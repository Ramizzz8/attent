import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import PasskeyVerify from '@/components/passkey-verify';

type Props = {
    status?: string;
    canResetPassword: boolean;
    cuentasDemo?: { empresa: string; email: string; portal: string }[] | null;
};

export default function Login({ status, canResetPassword, cuentasDemo }: Props) {
    return (
        <>
            <Head title="Entrar" />

            <PasskeyVerify />

            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-6">
                            <div className="grid gap-2">
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="email"
                                    placeholder="tu@email.com"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <div className="flex items-center">
                                    <Label htmlFor="password">Contraseña</Label>
                                    {canResetPassword && (
                                        <TextLink
                                            href={request()}
                                            className="ml-auto text-sm"
                                            tabIndex={5}
                                        >
                                            ¿Has olvidado la contraseña?
                                        </TextLink>
                                    )}
                                </div>
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    required
                                    tabIndex={2}
                                    autoComplete="current-password"
                                    placeholder="Contraseña"
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="flex items-center space-x-3">
                                <Checkbox
                                    id="remember"
                                    name="remember"
                                    tabIndex={3}
                                />
                                <Label htmlFor="remember">Recordarme</Label>
                            </div>

                            <Button
                                type="submit"
                                className="mt-4 w-full"
                                tabIndex={4}
                                disabled={processing}
                                data-test="login-button"
                            >
                                {processing && <Spinner />}
                                Entrar
                            </Button>
                        </div>
                    </>
                )}
            </Form>

            {status && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">
                    {status}
                </div>
            )}

            {cuentasDemo && (
                <div className="mt-6 rounded-xl bg-muted p-4 text-sm">
                    <p className="font-medium">Demo: entra sin escribir nada</p>
                    <p className="mb-3 text-muted-foreground">Cada botón abre el panel de una empresa de ejemplo.</p>
                    <div className="grid gap-2">
                        {cuentasDemo.map((c) => (
                            <form key={c.email} method="post" action={`/demo/entrar${c.portal}`}>
                                <input
                                    type="hidden"
                                    name="_token"
                                    value={typeof document !== 'undefined' ? (document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '') : ''}
                                />
                                <Button type="submit" variant="outline" className="w-full justify-between">
                                    {c.empresa}
                                    <span aria-hidden="true">→</span>
                                </Button>
                            </form>
                        ))}
                    </div>
                    <p className="mt-3 text-xs text-muted-foreground">
                        O con email y contraseña: {cuentasDemo[0].email} · <code>password</code>
                    </p>
                </div>
            )}
        </>
    );
}

Login.layout = {
    title: 'Accede a tu panel',
    description: 'Introduce tu email y contraseña para entrar',
};

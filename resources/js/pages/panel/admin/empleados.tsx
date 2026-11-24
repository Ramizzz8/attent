import { Head, router, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Campo, claseSelect } from '@/components/portal/campo';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type Empleado = { id: number; nombre: string; email: string; rol: 'admin' | 'empleado'; soyYo: boolean };

export default function Empleados({ empleados, status }: { empleados: Empleado[]; status?: string }) {
    const { errors } = usePage().props as { errors: Record<string, string> };
    const form = useForm({ name: '', email: '', rol: 'empleado' });

    function crear(e: FormEvent) {
        e.preventDefault();
        form.post('/panel/empleados', { preserveScroll: true, onSuccess: () => form.reset() });
    }

    return (
        <>
            <Head title="Empleados" />
            <div className="space-y-4 p-4">
                <h1 className="text-2xl font-semibold">Empleados</h1>
                {status && <p className="rounded-md bg-green-50 p-3 text-sm text-green-800 dark:bg-green-950 dark:text-green-200">{status}</p>}
                <InputError message={errors.rol ?? errors.empleado} />

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left text-xs text-muted-foreground">
                            <tr>
                                <th className="p-3">Nombre</th>
                                <th className="p-3">Email</th>
                                <th className="p-3">Rol</th>
                                <th className="p-3" />
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {empleados.map((u) => (
                                <tr key={u.id}>
                                    <td className="p-3 font-medium">
                                        {u.nombre} {u.soyYo && <span className="text-xs text-muted-foreground">(tú)</span>}
                                    </td>
                                    <td className="p-3">{u.email}</td>
                                    <td className="p-3">
                                        <select
                                            aria-label={`Rol de ${u.nombre}`}
                                            className={claseSelect}
                                            value={u.rol}
                                            onChange={(e) => router.patch(`/panel/empleados/${u.id}`, { rol: e.target.value }, { preserveScroll: true })}
                                        >
                                            <option value="empleado">Empleado</option>
                                            <option value="admin">Administrador</option>
                                        </select>
                                    </td>
                                    <td className="p-3 text-right">
                                        {!u.soyYo && (
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                onClick={() => confirm(`¿Dar de baja a ${u.nombre}?`) && router.delete(`/panel/empleados/${u.id}`, { preserveScroll: true })}
                                            >
                                                Dar de baja
                                            </Button>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <form onSubmit={crear} className="grid gap-3 rounded-xl border bg-background p-4 sm:grid-cols-4">
                    <Campo id="name" etiqueta="Nombre" error={form.errors.name}>
                        <Input id="name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} required />
                    </Campo>
                    <Campo id="email" etiqueta="Email" error={form.errors.email}>
                        <Input id="email" type="email" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} required />
                    </Campo>
                    <Campo id="rol" etiqueta="Rol" error={form.errors.rol}>
                        <select id="rol" className={claseSelect} value={form.data.rol} onChange={(e) => form.setData('rol', e.target.value)}>
                            <option value="empleado">Empleado</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </Campo>
                    <div className="flex items-end">
                        <Button type="submit" className="w-full" disabled={form.processing}>
                            Invitar empleado
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

Empleados.layout = { breadcrumbs: [{ title: 'Empleados', href: '/panel/empleados' }] };

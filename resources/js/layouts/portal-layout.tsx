import { Link, router, usePage } from '@inertiajs/react';
import type { CSSProperties, ReactNode } from 'react';
import { AvisosFlash } from '@/components/avisos-flash';
import type { Portal } from '@/types/portal';

/**
 * Layout del portal del cliente: lleva el logo, el nombre y el color de la empresa.
 * El color se expone como --marca para botones y acentos.
 */
export default function PortalLayout({ children }: { children: ReactNode }) {
    const portal = usePage().props.portal as Portal | null;

    if (!portal) {
        return <main className="mx-auto flex min-h-screen max-w-xl items-center p-6">{children}</main>;
    }

    const { empresa, cliente } = portal;
    const base = `/${empresa.slug}`;

    return (
        <div className="min-h-screen bg-muted/30" style={{ '--marca': empresa.color } as CSSProperties}>
            <header className="border-b bg-background">
                <div className="mx-auto flex max-w-3xl flex-wrap items-center justify-between gap-3 px-4 py-3">
                    <Link href={base} className="flex items-center gap-3">
                        {empresa.logo ? (
                            <img src={empresa.logo} alt="" className="h-9 w-9 rounded object-contain" />
                        ) : (
                            <span
                                className="flex h-9 w-9 items-center justify-center rounded font-semibold text-white"
                                style={{ background: 'var(--marca)' }}
                            >
                                {empresa.nombre.charAt(0)}
                            </span>
                        )}
                        <span className="font-semibold">{empresa.nombre}</span>
                    </Link>
                    <nav className="flex items-center gap-4 text-sm">
                        <Link href={`${base}/nueva`} className="hover:underline">
                            Nueva incidencia
                        </Link>
                        {cliente ? (
                            <>
                                <Link href={`${base}/mis-tickets`} className="hover:underline">
                                    Mis incidencias
                                </Link>
                                <Link href={`${base}/cuenta`} className="hover:underline">
                                    Mi cuenta
                                </Link>
                                <button
                                    type="button"
                                    className="text-muted-foreground hover:underline"
                                    onClick={() => router.post(`${base}/salir`)}
                                >
                                    Salir
                                </button>
                            </>
                        ) : (
                            <Link href={`${base}/acceder`} className="hover:underline">
                                Acceder
                            </Link>
                        )}
                    </nav>
                </div>
            </header>
            <AvisosFlash />
            <main className="mx-auto max-w-3xl px-4 py-8">{children}</main>
            <footer className="pb-8 text-center text-xs text-muted-foreground">Gestionado con ATTENT</footer>
        </div>
    );
}

import { Link } from '@inertiajs/react';
import { Wordmark } from '@/components/wordmark';
import { login } from '@/routes';

export function Cabecera({ conSesion }: { conSesion: boolean }) {
    return (
        <header className="sticky top-0 z-30 bg-background/95">
            <nav aria-label="Principal" className="mx-auto flex max-w-7xl items-center justify-between gap-4 px-5 py-4 sm:px-8">
                <a href="/" aria-label="ATTENT, inicio">
                    <Wordmark />
                </a>
                <div className="hidden items-center gap-7 text-sm font-medium md:flex">
                    <a href="#sectores" className="hover:text-primary">Sectores</a>
                    <a href="#como-funciona" className="hover:text-primary">Cómo funciona</a>
                    <a href="#funciones" className="hover:text-primary">Funciones</a>
                </div>
                <div className="flex items-center gap-2">
                    <Link href={conSesion ? '/panel' : login()} className="hidden rounded-full px-4 py-2 text-sm font-medium hover:bg-muted sm:inline-flex">
                        {conSesion ? 'Ir al panel' : 'Acceso equipo'}
                    </Link>
                    <a href="#probar" className="rounded-full bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition hover:bg-primary/90 active:scale-[0.97]">
                        Probar la demo
                    </a>
                </div>
            </nav>
        </header>
    );
}

export function Pie() {
    return (
        <footer className="mx-auto max-w-7xl px-5 pt-24 pb-10 sm:px-8">
            <p className="text-[clamp(4rem,17vw,15rem)] leading-[0.8] font-semibold tracking-[-0.05em] text-foreground/10 select-none" aria-hidden="true">
                ATTENT
            </p>
            <div className="mt-8 flex flex-wrap items-center justify-between gap-4 text-sm text-muted-foreground">
                <p>ATTENT · Demo con empresas y datos ficticios que se reinician cada noche.</p>
                <a href="https://github.com/Ramizzz8/attent" className="font-medium text-foreground hover:text-primary" rel="noreferrer">
                    Código en GitHub
                </a>
            </div>
        </footer>
    );
}

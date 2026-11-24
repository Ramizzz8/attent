import { Head, usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { Cabecera, Pie } from '@/components/landing/cabecera-pie';
import { ComoFunciona } from '@/components/landing/como-funciona';
import type { AccesoDemo, Portal } from '@/components/landing/datos';
import { Funciones } from '@/components/landing/funciones';
import { Portada } from '@/components/landing/portada';
import { Probar } from '@/components/landing/probar';
import { Sectores } from '@/components/landing/sectores';

/** Entradas escalonadas al hacer scroll. Sin JavaScript (o con movimiento reducido) todo se ve desde el principio. */
function useRevelar() {
    useEffect(() => {
        const raiz = document.documentElement;
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || !('IntersectionObserver' in window)) {
            return;
        }
        raiz.classList.add('con-revelar');
        const observador = new IntersectionObserver(
            (entradas) =>
                entradas.forEach((e) => {
                    if (e.isIntersecting) {
                        e.target.classList.add('visible');
                        observador.unobserve(e.target);
                    }
                }),
            { rootMargin: '0px 0px -8% 0px' },
        );
        document.querySelectorAll('.revelar').forEach((el, i) => {
            (el as HTMLElement).style.setProperty('--retraso', `${(i % 4) * 75}ms`);
            observador.observe(el);
        });

        return () => {
            observador.disconnect();
            raiz.classList.remove('con-revelar');
        };
    }, []);
}

export default function Welcome({ portales = [], accesosDemo = [] }: { portales?: Portal[]; accesosDemo?: AccesoDemo[] }) {
    const { auth } = usePage().props;
    useRevelar();

    return (
        <>
            <Head title="ATTENT · incidencias a la manera de tu sector" />
            <div className="min-h-screen bg-background text-foreground">
                <Cabecera conSesion={Boolean(auth.user)} />
                <main>
                    <Portada />
                    <Sectores portales={portales} />
                    <ComoFunciona />
                    <Funciones />
                    {(portales.length > 0 || accesosDemo.length > 0) && <Probar portales={portales} accesosDemo={accesosDemo} />}
                </main>
                <Pie />
            </div>
        </>
    );
}

import { ArrowRight } from 'lucide-react';
import type { Portal } from '@/components/landing/datos';
import { cn } from '@/lib/utils';

const FILAS = [
    {
        sector: 'taller',
        titulo: 'Talleres: nada se repara sin el sí del cliente.',
        texto: 'El cliente deja su coche y su matrícula desde el móvil. Tú preparas el presupuesto por líneas, con IVA, y él lo aprueba o lo rechaza desde su enlace. ATTENT no deja pasar a "En reparación" sin esa aprobación.',
        puntos: ['Historial de cada vehículo por matrícula', 'Presupuestos por versiones y en PDF', 'El plazo de diagnóstico se pausa mientras esperas la respuesta'],
        imagen: '/images/landing/presupuesto.webp',
        alt: 'Presupuesto de una reparación tal como lo ve el cliente, con su total y los botones para aprobarlo o rechazarlo',
    },
    {
        sector: 'fincas',
        titulo: 'Fincas: un aviso por avería, no veinte.',
        texto: 'Cuando un vecino va a avisar de algo que ya está reportado en su edificio, ATTENT se lo dice y le ofrece sumarse. Con tres vecinos afectados la prioridad sube; con seis, es urgente.',
        puntos: ['Varios edificios con sus zonas comunes', 'El proveedor programa la visita y la cierra con un enlace, sin cuenta', 'Todos los afectados reciben cada novedad'],
        imagen: '/images/landing/duplicado.webp',
        alt: 'Formulario del portal de vecinos avisando de que ya hay una incidencia abierta igual en el ascensor, con seis vecinos afectados',
    },
    {
        sector: 'soporte_it',
        titulo: 'Soporte: primero lo que para a más gente.',
        texto: 'Quien avisa indica a cuántos afecta; el técnico fija la urgencia y ATTENT calcula la prioridad y los dos plazos: el de primera respuesta y el de resolución.',
        puntos: ['Inventario de equipos con su historial de averías', 'Matriz de impacto y urgencia', 'Se cierra sola si el usuario no la reabre en 7 días'],
        imagen: '/images/landing/triaje.webp',
        alt: 'Detalle de una incidencia de soporte con el equipo afectado, el triaje de urgencia y los plazos',
    },
] as const;

export function Sectores({ portales }: { portales: Portal[] }) {
    return (
        <section id="sectores" className="mx-auto max-w-7xl scroll-mt-20 px-5 py-28 sm:px-8 sm:py-40">
            <h2 className="revelar max-w-3xl text-[clamp(2rem,4vw,3.4rem)] leading-[1.02] font-semibold tracking-[-0.03em]">Un sistema, tres formas de trabajar.</h2>
            <div className="mt-16 space-y-24 sm:mt-24 sm:space-y-36">
                {FILAS.map((fila, i) => {
                    const portal = portales.find((p) => p.sector === fila.sector);

                    return (
                        <article key={fila.sector} className="grid items-center gap-10 lg:grid-cols-2 lg:gap-16">
                            <div className={cn('revelar', i % 2 === 1 && 'lg:order-2')}>
                                <h3 className="text-[clamp(1.5rem,2.4vw,2.1rem)] leading-tight font-semibold tracking-[-0.02em]">{fila.titulo}</h3>
                                <p className="mt-5 text-lg leading-relaxed">{fila.texto}</p>
                                <ul className="mt-6 space-y-2 text-base">
                                    {fila.puntos.map((p) => (
                                        <li key={p} className="flex gap-3">
                                            <span className="mt-2.5 size-2 shrink-0 rounded-full" style={{ background: portal?.color ?? 'currentColor' }} aria-hidden="true" />
                                            {p}
                                        </li>
                                    ))}
                                </ul>
                                {portal && (
                                    <a href={`/${portal.slug}`} className="group mt-8 inline-flex items-center gap-2 font-medium text-primary hover:underline">
                                        Ver el portal de clientes de {portal.nombre}
                                        <ArrowRight className="size-4 transition group-hover:translate-x-1" />
                                    </a>
                                )}
                            </div>
                            <div className="revelar overflow-hidden rounded-2xl bg-muted p-2 sm:p-3">
                                <img src={fila.imagen} alt={fila.alt} loading="lazy" className="w-full rounded-xl" />
                            </div>
                        </article>
                    );
                })}
            </div>
        </section>
    );
}

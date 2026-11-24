const PASOS = [
    {
        titulo: 'El cliente avisa',
        texto: 'Desde el portal de tu empresa, con tu logo y tu color, en un minuto y con fotos. Confirma con un enlace que le llega al email: sin contraseñas que recordar.',
    },
    {
        titulo: 'Tu equipo lo resuelve',
        texto: 'La bandeja ordena por el plazo que vence antes. Cada incidencia muestra su siguiente paso, se asigna con un clic y avisa al momento si algo cambia.',
    },
    {
        titulo: 'El cliente lo sigue',
        texto: 'Ve cada cambio de estado, responde, aprueba el presupuesto y, al final, valora la atención. Si el problema vuelve, la reabre durante 7 días.',
    },
];

export function ComoFunciona() {
    return (
        <section id="como-funciona" className="scroll-mt-20 bg-secondary">
            <div className="mx-auto max-w-7xl px-5 py-28 sm:px-8 sm:py-36">
                <div className="grid gap-12 lg:grid-cols-[1fr_1.4fr] lg:gap-20">
                    <div className="revelar">
                        <h2 className="text-[clamp(2rem,4vw,3.4rem)] leading-[1.02] font-semibold tracking-[-0.03em]">Del aviso a la solución.</h2>
                        <img
                            src="/images/landing/detalle-fincas.webp"
                            alt="Detalle de una incidencia en el panel: estado, siguiente paso, proveedor asignado, vecinos afectados e historial"
                            loading="lazy"
                            className="mt-10 hidden w-full rounded-xl lg:block"
                        />
                    </div>
                    <ol className="space-y-12 self-center">
                        {PASOS.map((paso) => (
                            <li key={paso.titulo} className="revelar">
                                <h3 className="text-[clamp(1.5rem,2.4vw,2.1rem)] leading-tight font-semibold tracking-[-0.02em]">{paso.titulo}</h3>
                                <p className="mt-3 max-w-xl text-lg leading-relaxed">{paso.texto}</p>
                            </li>
                        ))}
                    </ol>
                </div>
            </div>
        </section>
    );
}

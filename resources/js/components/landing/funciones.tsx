const FUNCIONES = [
    ['Plazos que se pausan', 'El reloj se detiene mientras esperas al cliente o las piezas, así el plazo mide solo lo que depende de ti.'],
    ['Avisos al momento', 'Si entra una incidencia o un compañero la cambia, el panel se actualiza solo y te avisa.'],
    ['Presupuestos en PDF', 'Por líneas, con IVA y por versiones. El cliente los aprueba desde su enlace.'],
    ['Proveedores sin cuenta', 'Reciben un enlace seguro para programar la visita y cerrar el trabajo con una foto.'],
    ['Adjuntos revisados', 'Fotos y PDF de hasta 5 MB; nada de ejecutables ni archivos que se hagan pasar por imágenes.'],
    ['Estadísticas claras', 'Plazo cumplido, tiempo medio, carga por empleado y satisfacción, por semanas.'],
];

export function Funciones() {
    return (
        <section id="funciones" className="mx-auto max-w-7xl scroll-mt-20 px-5 py-28 sm:px-8 sm:py-40">
            <h2 className="revelar max-w-3xl text-[clamp(2rem,4vw,3.4rem)] leading-[1.02] font-semibold tracking-[-0.03em]">Lo que tu equipo deja de hacer a mano.</h2>
            <div className="mt-14 grid gap-14 lg:grid-cols-2 lg:gap-20">
                <dl className="grid gap-x-10 gap-y-9 sm:grid-cols-2">
                    {FUNCIONES.map(([titulo, texto]) => (
                        <div key={titulo} className="revelar">
                            <dt className="text-lg font-semibold">{titulo}</dt>
                            <dd className="mt-2 text-base leading-relaxed">{texto}</dd>
                        </div>
                    ))}
                </dl>
                <div className="revelar self-start overflow-hidden rounded-2xl bg-muted p-2 sm:p-3">
                    <img
                        src="/images/landing/estadisticas.webp"
                        alt="Estadísticas de una empresa en ATTENT: incidencias recibidas, plazo cumplido, tiempo medio, satisfacción e incidencias por semana"
                        loading="lazy"
                        className="w-full rounded-xl"
                    />
                </div>
            </div>
        </section>
    );
}

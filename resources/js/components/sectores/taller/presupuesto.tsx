export type LineaPresupuesto = { tipo: string; concepto: string; cantidad: number; precio: number; importe: number };

export type PresupuestoVista = {
    id: number;
    version: number;
    estado: 'pendiente' | 'aprobado' | 'rechazado';
    ivaPct: number;
    base: number;
    iva: number;
    total: number;
    comentario: string | null;
    respondido: string | null;
    creado: string;
    lineas: LineaPresupuesto[];
    puedeResponder?: boolean;
};

export const euros = (n: number) => n.toLocaleString('es-ES', { style: 'currency', currency: 'EUR' });

const ESTADO: Record<string, string> = { pendiente: 'Pendiente de respuesta', aprobado: 'Aprobado', rechazado: 'Rechazado' };

/** Tabla de un presupuesto con totales (la usan el panel y el portal). */
export function TablaPresupuesto({ p }: { p: PresupuestoVista }) {
    return (
        <div className="space-y-2">
            <p className="text-sm text-muted-foreground">
                Versión {p.version} · {ESTADO[p.estado]}
                {p.comentario && <span className="italic"> · «{p.comentario}»</span>}
            </p>
            <table className="w-full text-sm">
                <thead className="text-left text-xs text-muted-foreground">
                    <tr>
                        <th className="py-1">Concepto</th>
                        <th className="py-1 text-right">Cant.</th>
                        <th className="py-1 text-right">Precio</th>
                        <th className="py-1 text-right">Importe</th>
                    </tr>
                </thead>
                <tbody className="divide-y">
                    {p.lineas.map((l, i) => (
                        <tr key={i}>
                            <td className="py-1">
                                {l.concepto} <span className="text-xs text-muted-foreground">({l.tipo === 'pieza' ? 'pieza' : 'mano de obra'})</span>
                            </td>
                            <td className="py-1 text-right">{l.cantidad}</td>
                            <td className="py-1 text-right">{euros(l.precio)}</td>
                            <td className="py-1 text-right">{euros(l.importe)}</td>
                        </tr>
                    ))}
                </tbody>
                <tfoot className="text-right">
                    <tr>
                        <td colSpan={3} className="pt-2 text-muted-foreground">
                            Base imponible
                        </td>
                        <td className="pt-2">{euros(p.base)}</td>
                    </tr>
                    <tr>
                        <td colSpan={3} className="text-muted-foreground">
                            IVA ({p.ivaPct} %)
                        </td>
                        <td>{euros(p.iva)}</td>
                    </tr>
                    <tr className="font-semibold">
                        <td colSpan={3}>Total</td>
                        <td>{euros(p.total)}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    );
}

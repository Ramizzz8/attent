export function EstadoPill({ children }: { children: string }) {
    return (
        <span className="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium" style={{ borderColor: 'var(--marca)', color: 'var(--marca)' }}>
            {children}
        </span>
    );
}

export function fecha(iso: string, conHora = true): string {
    return new Date(iso).toLocaleString('es-ES', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        ...(conHora ? { hour: '2-digit', minute: '2-digit' } : {}),
    });
}

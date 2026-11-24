import { usePage } from '@inertiajs/react';

import AppLogoIcon from '@/components/app-logo-icon';

export default function AppLogo() {
    const { name, empresaPanel } = usePage().props as { name: string; empresaPanel?: { nombre: string } | null };

    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground">
                <AppLogoIcon className="size-5 fill-current text-white dark:text-black" />
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-tight font-semibold">
                    {empresaPanel?.nombre ?? name}
                </span>
                {empresaPanel && <span className="truncate text-xs text-muted-foreground">{name}</span>}
            </div>
        </>
    );
}

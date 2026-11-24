import AppLogoIcon from '@/components/app-logo-icon';
import { cn } from '@/lib/utils';

/** Logotipo completo: marca + "ATTENT". */
export function Wordmark({ className, tono = 'oscuro' }: { className?: string; tono?: 'oscuro' | 'claro' }) {
    return (
        <span className={cn('inline-flex items-center gap-2 font-semibold tracking-[0.18em]', tono === 'claro' ? 'text-white' : 'text-foreground', className)}>
            <span className="flex size-7 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                <AppLogoIcon className="size-4" />
            </span>
            ATTENT
        </span>
    );
}

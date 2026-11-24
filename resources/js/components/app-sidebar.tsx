import { Link } from '@inertiajs/react';
import { BarChart3, Building2, ExternalLink, Inbox, Monitor, Settings, Users } from 'lucide-react';
import { usePage } from '@inertiajs/react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

type EmpresaPanel = { nombre: string; slug: string; color: string; sector: string; esAdmin: boolean } | null;

/** Menú del panel. Los módulos de cada sector y de administración se añaden según la empresa. */
export function menuPanel(empresa: EmpresaPanel): NavItem[] {
    const items: NavItem[] = [{ title: 'Bandeja', href: dashboard(), icon: Inbox }];
    const admin: NavItem[] = empresa?.esAdmin
        ? [
              { title: 'Estadísticas', href: '/panel/estadisticas', icon: BarChart3 },
              { title: 'Empleados', href: '/panel/empleados', icon: Users },
              { title: 'Ajustes', href: '/panel/ajustes', icon: Settings },
          ]
        : [];

    return [...items, ...menuSector(empresa), ...admin];
}

function menuSector(empresa: EmpresaPanel): NavItem[] {
    switch (empresa?.sector) {
        case 'fincas':
            return empresa.esAdmin ? [{ title: 'Edificios y proveedores', href: '/panel/edificios', icon: Building2 }] : [];
        case 'soporte_it':
            return [{ title: 'Equipos', href: '/panel/equipos', icon: Monitor }];
        default:
            return [];
    }
}

export function AppSidebar() {
    const empresa = usePage().props.empresaPanel as EmpresaPanel;
    const mainNavItems = menuPanel(empresa);
    const footerNavItems: NavItem[] = empresa ? [{ title: 'Ver portal de clientes', href: `/${empresa.slug}`, icon: ExternalLink }] : [];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}

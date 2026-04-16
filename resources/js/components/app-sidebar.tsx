import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { Building2, CreditCard, LayoutGrid, MapPinned, ShieldCheck, Ticket, Wallet } from 'lucide-react';
import AppLogo from './app-logo';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        url: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Packages',
        url: '/packages',
        icon: CreditCard,
    },
    {
        title: 'Tenants',
        url: '/tenants',
        icon: Building2,
    },
    {
        title: 'Branches',
        url: '/branches',
        icon: MapPinned,
    },
    {
        title: 'Vouchers',
        url: '/vouchers',
        icon: Ticket,
    },
    {
        title: 'Transactions',
        url: '/transactions',
        icon: Wallet,
    },
];

const footerNavItems: NavItem[] = [];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset" className="md:p-3">
            <SidebarHeader className="gap-3 px-3 py-3">
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild className="h-14 rounded-2xl px-3">
                            <Link href="/dashboard" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
                <div className="border-sidebar-border/80 bg-sidebar-accent/70 text-sidebar-foreground rounded-2xl border px-3 py-3 text-sm group-data-[collapsible=icon]:hidden">
                    <div className="text-sidebar-foreground/60 flex items-center gap-2 text-xs font-semibold tracking-[0.24em] uppercase">
                        <ShieldCheck className="size-3.5" />
                        Platform State
                    </div>
                    <p className="text-sidebar-foreground mt-2 font-medium">MVP foundation is live</p>
                    <p className="text-sidebar-foreground/65 mt-1 text-xs leading-5">
                        Tenancy, vouchers, sessions, transactions, and revenue allocation are already wired into the real app.
                    </p>
                </div>
            </SidebarHeader>

            <SidebarContent className="px-1">
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter className="px-3 pb-3">
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}

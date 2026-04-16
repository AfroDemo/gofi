import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';

export function NavMain({ items = [] }: { items: NavItem[] }) {
    const page = usePage();
    return (
        <SidebarGroup className="px-2 py-1">
            <SidebarGroupLabel className="text-sidebar-foreground/55 px-3 text-[11px] font-semibold tracking-[0.24em] uppercase">
                Operations
            </SidebarGroupLabel>
            <SidebarMenu>
                {items.map((item) => (
                    <SidebarMenuItem key={item.title}>
                        <SidebarMenuButton
                            asChild
                            isActive={item.url === page.url}
                            className="data-[active=true]:bg-sidebar-primary/16 data-[active=true]:text-sidebar-primary h-11 rounded-xl px-3 font-medium data-[active=true]:shadow-[inset_0_0_0_1px_rgba(96,165,250,0.18)]"
                        >
                            <Link href={item.url} prefetch>
                                {item.icon && <item.icon />}
                                <span>{item.title}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                ))}
            </SidebarMenu>
        </SidebarGroup>
    );
}

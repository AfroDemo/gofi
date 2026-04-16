import { SidebarProvider } from '@/components/ui/sidebar';
import { useState } from 'react';

interface AppShellProps {
    children: React.ReactNode;
    variant?: 'header' | 'sidebar';
}

export function AppShell({ children, variant = 'header' }: AppShellProps) {
    const [isOpen, setIsOpen] = useState(() => (typeof window !== 'undefined' ? localStorage.getItem('sidebar') !== 'false' : true));

    const handleSidebarChange = (open: boolean) => {
        setIsOpen(open);

        if (typeof window !== 'undefined') {
            localStorage.setItem('sidebar', String(open));
        }
    };

    if (variant === 'header') {
        return <div className="flex min-h-screen w-full flex-col">{children}</div>;
    }

    return (
        <SidebarProvider
            defaultOpen={isOpen}
            open={isOpen}
            onOpenChange={handleSidebarChange}
            className="bg-[radial-gradient(circle_at_top_left,rgba(37,99,235,0.08),transparent_26%),radial-gradient(circle_at_top_right,rgba(13,148,136,0.08),transparent_24%)]"
        >
            {children}
        </SidebarProvider>
    );
}

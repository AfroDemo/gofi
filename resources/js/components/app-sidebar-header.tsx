import { Breadcrumbs } from '@/components/breadcrumbs';
import { Badge } from '@/components/ui/badge';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { type BreadcrumbItem as BreadcrumbItemType } from '@/types';

export function AppSidebarHeader({ breadcrumbs = [] }: { breadcrumbs?: BreadcrumbItemType[] }) {
    const currentTitle = breadcrumbs.at(-1)?.title ?? 'Workspace';

    return (
        <header className="border-border/70 bg-background/85 sticky top-0 z-20 border-b px-4 backdrop-blur-xl transition-[width,height] ease-linear md:px-6">
            <div className="flex min-h-18 items-center justify-between gap-4">
                <div className="flex min-w-0 items-center gap-3">
                    <SidebarTrigger className="border-border/70 bg-card text-foreground size-9 rounded-xl border shadow-sm" />
                    <div className="min-w-0">
                        <p className="text-muted-foreground text-[11px] font-semibold tracking-[0.24em] uppercase">Go-Fi Workspace</p>
                        <h1 className="text-foreground truncate text-lg font-semibold">{currentTitle}</h1>
                    </div>
                </div>
                <div className="hidden items-center gap-2 md:flex">
                    <Badge variant="secondary" className="rounded-full px-3 py-1 text-[11px] tracking-[0.18em] uppercase">
                        Live Foundation
                    </Badge>
                    <span className="text-muted-foreground text-xs">Built for hotspot operators and resellers.</span>
                </div>
            </div>
            {breadcrumbs.length > 1 && (
                <div className="border-border/60 border-t py-3">
                    <Breadcrumbs breadcrumbs={breadcrumbs} />
                </div>
            )}
        </header>
    );
}

import { Badge } from '@/components/ui/badge';
import { Card } from '@/components/ui/card';

interface Viewer {
    scope: 'platform' | 'tenant';
    name: string;
    role: string;
}

interface OpsPageHeaderProps {
    title: string;
    description: string;
    viewer: Viewer;
}

export function OpsPageHeader({ title, description, viewer }: OpsPageHeaderProps) {
    const scopeLabel = viewer.scope === 'platform' ? 'Platform admin view' : 'Tenant operations view';

    return (
        <Card className="border-border/70 from-primary/8 via-background rounded-2xl border bg-linear-to-br to-cyan-500/8 p-6">
            <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div className="space-y-2">
                    <Badge variant="secondary" className="w-fit rounded-full px-3 py-1">
                        {scopeLabel}
                    </Badge>
                    <div>
                        <h1 className="text-3xl font-semibold tracking-tight">{title}</h1>
                        <p className="text-muted-foreground mt-1 max-w-3xl text-sm">{description}</p>
                    </div>
                </div>
                <div className="space-y-2">
                    <Badge variant="outline" className="w-fit rounded-full px-3 py-1 capitalize">
                        {viewer.role.replaceAll('_', ' ')}
                    </Badge>
                    <p className="text-muted-foreground text-sm md:text-right">{viewer.name}</p>
                </div>
            </div>
        </Card>
    );
}

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { type LucideIcon } from 'lucide-react';

interface OpsStatCardProps {
    label: string;
    value: string;
    hint: string;
    icon: LucideIcon;
}

export function OpsStatCard({ label, value, hint, icon: Icon }: OpsStatCardProps) {
    return (
        <Card className="border-border/70">
            <CardHeader className="flex flex-row items-start justify-between space-y-0 pb-3">
                <div>
                    <CardDescription>{label}</CardDescription>
                    <CardTitle className="mt-2 text-2xl">{value}</CardTitle>
                </div>
                <div className="bg-primary/10 text-primary rounded-lg p-2">
                    <Icon className="h-5 w-5" />
                </div>
            </CardHeader>
            <CardContent>
                <p className="text-muted-foreground text-sm">{hint}</p>
            </CardContent>
        </Card>
    );
}

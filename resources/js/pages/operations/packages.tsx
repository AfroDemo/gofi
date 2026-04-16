import { OpsPageHeader } from '@/components/ops/ops-page-header';
import { OpsStatCard } from '@/components/ops/ops-stat-card';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { formatDataLimit, formatMinutes, formatMoney, formatSpeed } from '@/lib/formatters';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { BadgeDollarSign, Boxes, MapPinned, RadioTower, TicketPercent } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Packages', href: '/packages' },
];

interface Viewer {
    scope: 'platform' | 'tenant';
    name: string;
    role: string;
    currency?: string | null;
}

interface Summary {
    total: number;
    active: number;
    voucher_profiles: number;
    branch_coverage: number;
    successful_revenue: number;
}

interface TypeMixRow {
    type: string;
    count: number;
}

interface PackageRow {
    id: number;
    name: string;
    description: string | null;
    tenant: string | null;
    branch: string | null;
    location: string | null;
    type: string;
    price: number;
    currency: string | null;
    duration_minutes: number | null;
    data_limit_mb: number | null;
    speed_limit_kbps: number | null;
    is_active: boolean;
    voucher_profiles_count: number;
    successful_transactions_count: number;
    successful_revenue: number;
}

interface PackagesPageProps {
    viewer: Viewer;
    summary: Summary;
    typeMix: TypeMixRow[];
    packages: PackageRow[];
}

export default function Packages({ viewer, summary, typeMix, packages }: PackagesPageProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Packages" />
            <div className="flex flex-1 flex-col gap-6 rounded-xl p-4">
                <OpsPageHeader
                    title="Access Packages"
                    description="Real package inventory from the live Go-Fi domain model. This is where duration, data cap, speed profile, branch availability, and sales performance begin to converge."
                    viewer={viewer}
                />

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <OpsStatCard
                        label="Total packages"
                        value={summary.total.toString()}
                        hint="All saleable package definitions in scope."
                        icon={Boxes}
                    />
                    <OpsStatCard
                        label="Active packages"
                        value={summary.active.toString()}
                        hint="Currently enabled for checkout or voucher sale."
                        icon={RadioTower}
                    />
                    <OpsStatCard
                        label="Voucher profiles"
                        value={summary.voucher_profiles.toString()}
                        hint="Print-ready or agent-sold voucher configurations."
                        icon={TicketPercent}
                    />
                    <OpsStatCard
                        label="Branch coverage"
                        value={summary.branch_coverage.toString()}
                        hint="Branches currently mapped to at least one package."
                        icon={MapPinned}
                    />
                    <OpsStatCard
                        label="Successful revenue"
                        value={formatMoney(summary.successful_revenue, viewer.currency)}
                        hint="Confirmed sales currently tied to the scoped tenants."
                        icon={BadgeDollarSign}
                    />
                </section>

                <section className="grid gap-4 xl:grid-cols-[1.2fr_0.8fr]">
                    <Card className="border-border/70">
                        <CardHeader>
                            <CardTitle>Package lineup</CardTitle>
                            <CardDescription>Read-only for now, but already backed by real package, branch, and sales records.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {packages.map((item) => (
                                <div key={item.id} className="border-border/60 rounded-2xl border px-4 py-4">
                                    <div className="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                        <div className="space-y-2">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <p className="font-semibold">{item.name}</p>
                                                <Badge variant={item.is_active ? 'default' : 'outline'}>
                                                    {item.is_active ? 'active' : 'inactive'}
                                                </Badge>
                                                <Badge variant="outline" className="capitalize">
                                                    {item.type}
                                                </Badge>
                                            </div>
                                            <p className="text-muted-foreground text-sm">{item.description ?? 'No package description added yet.'}</p>
                                            <p className="text-muted-foreground text-sm">
                                                {[item.tenant, item.branch, item.location].filter(Boolean).join(' • ') || 'Unassigned scope'}
                                            </p>
                                        </div>
                                        <div className="text-left xl:text-right">
                                            <p className="text-lg font-semibold">{formatMoney(item.price, item.currency)}</p>
                                            <p className="text-muted-foreground text-sm">
                                                {formatMoney(item.successful_revenue, item.currency)} confirmed revenue
                                            </p>
                                        </div>
                                    </div>

                                    <div className="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-5">
                                        <div className="bg-muted/45 rounded-xl px-3 py-3">
                                            <p className="text-muted-foreground text-xs uppercase">Duration</p>
                                            <p className="mt-2 font-medium">{formatMinutes(item.duration_minutes)}</p>
                                        </div>
                                        <div className="bg-muted/45 rounded-xl px-3 py-3">
                                            <p className="text-muted-foreground text-xs uppercase">Data cap</p>
                                            <p className="mt-2 font-medium">{formatDataLimit(item.data_limit_mb)}</p>
                                        </div>
                                        <div className="bg-muted/45 rounded-xl px-3 py-3">
                                            <p className="text-muted-foreground text-xs uppercase">Speed limit</p>
                                            <p className="mt-2 font-medium">{formatSpeed(item.speed_limit_kbps)}</p>
                                        </div>
                                        <div className="bg-muted/45 rounded-xl px-3 py-3">
                                            <p className="text-muted-foreground text-xs uppercase">Voucher profiles</p>
                                            <p className="mt-2 font-medium">{item.voucher_profiles_count}</p>
                                        </div>
                                        <div className="bg-muted/45 rounded-xl px-3 py-3">
                                            <p className="text-muted-foreground text-xs uppercase">Successful sales</p>
                                            <p className="mt-2 font-medium">{item.successful_transactions_count}</p>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </CardContent>
                    </Card>

                    <Card className="border-border/70">
                        <CardHeader>
                            <CardTitle>Type mix</CardTitle>
                            <CardDescription>
                                This helps us see whether the catalog leans more toward time-based, capped, or hybrid access.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {typeMix.map((item) => (
                                <div key={item.type} className="border-border/60 flex items-center justify-between rounded-xl border px-4 py-3">
                                    <div>
                                        <p className="font-medium capitalize">{item.type}</p>
                                        <p className="text-muted-foreground text-sm">Package design pattern in current scope.</p>
                                    </div>
                                    <Badge variant="outline">{item.count}</Badge>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                </section>
            </div>
        </AppLayout>
    );
}

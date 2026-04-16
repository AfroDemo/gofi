import { OpsFilters } from '@/components/ops/ops-filters';
import { OpsPageHeader } from '@/components/ops/ops-page-header';
import { OpsStatCard } from '@/components/ops/ops-stat-card';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { formatDataLimit, formatDateTime, formatMinutes, formatMoney } from '@/lib/formatters';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { CircleCheckBig, Clock3, PencilLine, Plus, RectangleEllipsis, Ticket, TicketSlash, WandSparkles } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Vouchers', href: '/vouchers' },
];

interface Viewer {
    scope: 'platform' | 'tenant';
    name: string;
    role: string;
}

interface Summary {
    total: number;
    unused: number;
    used: number;
    expired: number;
    profiles: number;
}

interface VoucherProfileRow {
    id: number;
    name: string;
    tenant: string | null;
    branch: string | null;
    package: string | null;
    price: number;
    currency: string | null;
    duration_minutes: number | null;
    data_limit_mb: number | null;
    expires_in_days: number | null;
    total_count: number;
    unused_count: number;
    used_count: number;
    expired_count: number;
    is_active: boolean;
}

interface VoucherRow {
    id: number;
    code: string;
    status: string;
    tenant: string | null;
    branch: string | null;
    package: string | null;
    profile: string | null;
    created_by: string | null;
    locked_mac_address: string | null;
    redeemed_at: string | null;
    expires_at: string | null;
    created_at: string | null;
}

interface VouchersPageProps {
    viewer: Viewer;
    filters: {
        search: string;
        status: string;
    };
    summary: Summary;
    profiles: VoucherProfileRow[];
    vouchers: VoucherRow[];
}

const statusTone: Record<string, string> = {
    unused: 'bg-cyan-500/10 text-cyan-700 dark:text-cyan-300',
    active: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
    used: 'bg-slate-500/10 text-slate-700 dark:text-slate-300',
    expired: 'bg-rose-500/10 text-rose-700 dark:text-rose-300',
    cancelled: 'bg-amber-500/10 text-amber-700 dark:text-amber-300',
};

export default function Vouchers({ viewer, filters, summary, profiles, vouchers }: VouchersPageProps) {
    const { flash } = usePage<SharedData>().props;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Vouchers" />
            <div className="flex flex-1 flex-col gap-6 rounded-xl p-4">
                <OpsPageHeader
                    title="Voucher Operations"
                    description="Voucher inventory is one of the most practical offline-friendly flows in Go-Fi. These screens already read from the real voucher, voucher profile, package, and branch records."
                    viewer={viewer}
                />

                {flash?.success && (
                    <Alert className="border-emerald-500/30 bg-emerald-500/5 text-emerald-900 dark:text-emerald-100">
                        <CircleCheckBig className="size-4" />
                        <AlertTitle>Voucher workflow updated</AlertTitle>
                        <AlertDescription>{flash.success}</AlertDescription>
                    </Alert>
                )}

                <div className="flex justify-end">
                    <Button asChild className="rounded-xl">
                        <Link href={route('voucher-profiles.create')}>
                            <Plus className="size-4" />
                            New voucher profile
                        </Link>
                    </Button>
                </div>

                <OpsFilters
                    search={filters.search}
                    searchPlaceholder="Search voucher code, profile, package, branch, or operator"
                    values={{ status: filters.status }}
                    fields={[
                        {
                            key: 'status',
                            label: 'Status',
                            placeholder: 'All statuses',
                            options: [
                                { label: 'All statuses', value: 'all' },
                                { label: 'Unused', value: 'unused' },
                                { label: 'Active', value: 'active' },
                                { label: 'Used', value: 'used' },
                                { label: 'Expired', value: 'expired' },
                                { label: 'Cancelled', value: 'cancelled' },
                            ],
                        },
                    ]}
                    resultLabel={`${vouchers.length} voucher matches in the current workspace.`}
                />

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <OpsStatCard label="Total vouchers" value={summary.total.toString()} hint="All generated voucher codes in scope." icon={Ticket} />
                    <OpsStatCard
                        label="Unused"
                        value={summary.unused.toString()}
                        hint="Inventory still available for sale or print."
                        icon={RectangleEllipsis}
                    />
                    <OpsStatCard label="Used" value={summary.used.toString()} hint="Codes already redeemed by customers." icon={CircleCheckBig} />
                    <OpsStatCard
                        label="Expired"
                        value={summary.expired.toString()}
                        hint="Inventory that needs renewal or cleanup."
                        icon={TicketSlash}
                    />
                    <OpsStatCard
                        label="Profiles"
                        value={summary.profiles.toString()}
                        hint="Voucher templates tied to sellable packages."
                        icon={Clock3}
                    />
                </section>

                <section className="grid gap-4 xl:grid-cols-[1fr_1fr]">
                    <Card className="border-border/70">
                        <CardHeader>
                            <CardTitle>Voucher profiles</CardTitle>
                            <CardDescription>These are the reusable templates that shape printed or agent-issued voucher stock.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {profiles.length === 0 && (
                                <div className="border-border/60 text-muted-foreground rounded-2xl border border-dashed px-4 py-8 text-center text-sm">
                                    No voucher profiles are tied to the current filter result.
                                </div>
                            )}
                            {profiles.map((profile) => (
                                <div key={profile.id} className="border-border/60 rounded-2xl border px-4 py-4">
                                    <div className="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                        <div className="space-y-2">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <p className="font-semibold">{profile.name}</p>
                                                <Badge variant={profile.is_active ? 'default' : 'outline'}>
                                                    {profile.is_active ? 'active' : 'inactive'}
                                                </Badge>
                                            </div>
                                            <p className="text-muted-foreground text-sm">
                                                {[profile.tenant, profile.branch, profile.package].filter(Boolean).join(' • ') ||
                                                    'Unassigned profile'}
                                            </p>
                                        </div>
                                        <div className="text-left xl:text-right">
                                            <p className="text-lg font-semibold">{formatMoney(profile.price, profile.currency)}</p>
                                            <p className="text-muted-foreground text-sm">{profile.total_count} vouchers created</p>
                                            <div className="mt-3 flex flex-wrap gap-2 xl:justify-end">
                                                <Button asChild variant="outline" size="sm" className="rounded-lg">
                                                    <Link href={route('voucher-profiles.edit', profile.id)}>
                                                        <PencilLine className="size-4" />
                                                        Edit
                                                    </Link>
                                                </Button>
                                                <Button asChild size="sm" className="rounded-lg">
                                                    <Link href={route('voucher-batches.create', profile.id)}>
                                                        <WandSparkles className="size-4" />
                                                        Generate batch
                                                    </Link>
                                                </Button>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="mt-4 grid gap-3 md:grid-cols-3 xl:grid-cols-4">
                                        <div className="bg-muted/45 rounded-xl px-3 py-3">
                                            <p className="text-muted-foreground text-xs uppercase">Duration</p>
                                            <p className="mt-2 font-medium">{formatMinutes(profile.duration_minutes)}</p>
                                        </div>
                                        <div className="bg-muted/45 rounded-xl px-3 py-3">
                                            <p className="text-muted-foreground text-xs uppercase">Data cap</p>
                                            <p className="mt-2 font-medium">{formatDataLimit(profile.data_limit_mb)}</p>
                                        </div>
                                        <div className="bg-muted/45 rounded-xl px-3 py-3">
                                            <p className="text-muted-foreground text-xs uppercase">Expiry window</p>
                                            <p className="mt-2 font-medium">
                                                {profile.expires_in_days ? `${profile.expires_in_days} days` : 'Manual'}
                                            </p>
                                        </div>
                                        <div className="bg-muted/45 rounded-xl px-3 py-3">
                                            <p className="text-muted-foreground text-xs uppercase">Inventory</p>
                                            <p className="mt-2 font-medium">
                                                {profile.unused_count} unused / {profile.used_count} used / {profile.expired_count} expired
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </CardContent>
                    </Card>

                    <Card className="border-border/70">
                        <CardHeader>
                            <CardTitle>Voucher inventory</CardTitle>
                            <CardDescription>Recent voucher codes and their current lifecycle status.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {vouchers.length === 0 && (
                                <div className="border-border/60 text-muted-foreground rounded-2xl border border-dashed px-4 py-8 text-center text-sm">
                                    No vouchers matched the current filters.
                                </div>
                            )}
                            {vouchers.map((voucher) => (
                                <div key={voucher.id} className="border-border/60 rounded-2xl border px-4 py-4">
                                    <div className="flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between">
                                        <div className="space-y-2">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <p className="font-semibold">{voucher.code}</p>
                                                <Badge variant="outline" className={statusTone[voucher.status] ?? ''}>
                                                    {voucher.status}
                                                </Badge>
                                            </div>
                                            <p className="text-muted-foreground text-sm">
                                                {[voucher.profile, voucher.package, voucher.branch, voucher.tenant].filter(Boolean).join(' • ') ||
                                                    'Unassigned voucher'}
                                            </p>
                                            <p className="text-muted-foreground text-sm">Issued by {voucher.created_by ?? 'Unknown operator'}</p>
                                        </div>
                                        <div className="space-y-1 text-left text-sm xl:text-right">
                                            <p>{voucher.locked_mac_address ? `Locked to ${voucher.locked_mac_address}` : 'Not MAC-locked yet'}</p>
                                            <p className="text-muted-foreground">Redeemed: {formatDateTime(voucher.redeemed_at)}</p>
                                            <p className="text-muted-foreground">Expires: {formatDateTime(voucher.expires_at)}</p>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                </section>
            </div>
        </AppLayout>
    );
}

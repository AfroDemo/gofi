import { OpsFilters } from '@/components/ops/ops-filters';
import { OpsPageHeader } from '@/components/ops/ops-page-header';
import { OpsStatCard } from '@/components/ops/ops-stat-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { formatDataLimit, formatDateTime, formatMinutes } from '@/lib/formatters';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Activity, Clock3, DatabaseZap, TimerOff, Wifi } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Sessions', href: '/sessions' },
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
    pending: number;
    expired: number;
    data_in_use_mb: number;
}

interface SessionRow {
    id: number;
    tenant: string | null;
    branch: string | null;
    location: string | null;
    package: string | null;
    voucher: string | null;
    transaction_reference: string | null;
    status: string;
    mac_address: string;
    ip_address: string | null;
    duration_minutes: number | null;
    data_limit_mb: number | null;
    data_used_mb: number;
    started_at: string | null;
    expires_at: string | null;
    ended_at: string | null;
}

interface SessionsPageProps {
    viewer: Viewer;
    filters: {
        search: string;
        status: string;
    };
    summary: Summary;
    sessions: SessionRow[];
}

const tone: Record<string, string> = {
    active: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
    pending: 'bg-amber-500/10 text-amber-700 dark:text-amber-300',
    expired: 'bg-slate-500/10 text-slate-700 dark:text-slate-300',
    terminated: 'bg-rose-500/10 text-rose-700 dark:text-rose-300',
};

export default function Sessions({ viewer, filters, summary, sessions }: SessionsPageProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Sessions" />
            <div className="flex flex-1 flex-col gap-6 rounded-xl p-4">
                <OpsPageHeader
                    title="Session Operations"
                    description="Sessions connect payment and voucher activity to actual customer access. This view helps operators inspect active usage, expiry patterns, and where fulfilment has already happened."
                    viewer={viewer}
                />

                <OpsFilters
                    search={filters.search}
                    searchPlaceholder="Search branch, package, voucher, transaction, IP, or MAC address"
                    values={{ status: filters.status }}
                    fields={[
                        {
                            key: 'status',
                            label: 'Status',
                            placeholder: 'All statuses',
                            options: [
                                { label: 'All statuses', value: 'all' },
                                { label: 'Active', value: 'active' },
                                { label: 'Pending', value: 'pending' },
                                { label: 'Expired', value: 'expired' },
                                { label: 'Terminated', value: 'terminated' },
                            ],
                        },
                    ]}
                    resultLabel={`${sessions.length} session matches in the current workspace.`}
                />

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <OpsStatCard label="Total sessions" value={summary.total.toString()} hint="Customer access records in current scope." icon={Wifi} />
                    <OpsStatCard label="Active" value={summary.active.toString()} hint="Customers currently authorized on the hotspot." icon={Activity} />
                    <OpsStatCard label="Pending" value={summary.pending.toString()} hint="Sessions waiting on fulfilment or follow-up." icon={Clock3} />
                    <OpsStatCard label="Expired" value={summary.expired.toString()} hint="Sessions that have already run out." icon={TimerOff} />
                    <OpsStatCard label="Data in use" value={formatDataLimit(summary.data_in_use_mb)} hint="Observed usage across visible sessions." icon={DatabaseZap} />
                </section>

                <Card className="border-border/70">
                    <CardHeader>
                        <CardTitle>Session list</CardTitle>
                        <CardDescription>Track customer access state, linked transaction evidence, and expiry timing in one place.</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {sessions.length === 0 && (
                            <div className="border-border/60 text-muted-foreground rounded-2xl border border-dashed px-4 py-8 text-center text-sm">
                                No sessions matched the current filters.
                            </div>
                        )}
                        {sessions.map((session) => (
                            <div key={session.id} className="border-border/60 rounded-2xl border px-4 py-4">
                                <div className="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                    <div className="space-y-2">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <p className="font-semibold">{session.package ?? 'Unlinked package'}</p>
                                            <Badge variant="outline" className={tone[session.status] ?? ''}>
                                                {session.status}
                                            </Badge>
                                            {session.voucher && <Badge variant="outline">{session.voucher}</Badge>}
                                        </div>
                                        <p className="text-muted-foreground text-sm">
                                            {[session.tenant, session.branch, session.location].filter(Boolean).join(' • ') || 'Branch context unavailable'}
                                        </p>
                                        <p className="text-muted-foreground text-sm">
                                            {session.transaction_reference ? `Transaction ${session.transaction_reference}` : 'No transaction linked'}
                                            {session.ip_address ? ` • ${session.ip_address}` : ''}
                                            {session.mac_address ? ` • ${session.mac_address}` : ''}
                                        </p>
                                    </div>

                                    <div className="text-left xl:text-right">
                                        <p className="text-sm font-medium">Usage</p>
                                        <p className="text-muted-foreground text-sm">
                                            {formatDataLimit(session.data_used_mb)} used
                                            {session.data_limit_mb ? ` of ${formatDataLimit(session.data_limit_mb)}` : ''}
                                        </p>
                                        <Button asChild variant="outline" size="sm" className="mt-3 rounded-lg">
                                            <Link href={route('sessions.show', session.id)}>Open detail</Link>
                                        </Button>
                                    </div>
                                </div>

                                <div className="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                                    <div className="bg-muted/45 rounded-xl px-3 py-3">
                                        <p className="text-muted-foreground text-xs uppercase">Started</p>
                                        <p className="mt-2 font-medium">{formatDateTime(session.started_at)}</p>
                                    </div>
                                    <div className="bg-muted/45 rounded-xl px-3 py-3">
                                        <p className="text-muted-foreground text-xs uppercase">Expires</p>
                                        <p className="mt-2 font-medium">{formatDateTime(session.expires_at)}</p>
                                    </div>
                                    <div className="bg-muted/45 rounded-xl px-3 py-3">
                                        <p className="text-muted-foreground text-xs uppercase">Duration</p>
                                        <p className="mt-2 font-medium">{formatMinutes(session.duration_minutes)}</p>
                                    </div>
                                    <div className="bg-muted/45 rounded-xl px-3 py-3">
                                        <p className="text-muted-foreground text-xs uppercase">Data cap</p>
                                        <p className="mt-2 font-medium">{formatDataLimit(session.data_limit_mb)}</p>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

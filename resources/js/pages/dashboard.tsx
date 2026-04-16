import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Activity, AlertTriangle, ArrowRight, Building2, Clock3, Cpu, Landmark, Router, ShieldAlert, Wallet, Wrench, type LucideIcon } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

interface Viewer {
    scope: 'platform' | 'tenant';
    name: string;
    role: string;
}

interface Summary {
    revenue: number;
    active_sessions: number;
    tenants: number;
    branches: number;
    pending_payouts: number;
    pending_transactions: number;
    escalations: number;
}

interface RevenueRow {
    name: string;
    location: string | null;
    revenue: number;
    active_sessions: number;
    kind: 'tenant' | 'branch';
}

interface DashboardTransaction {
    id: number;
    reference: string;
    tenant: string | null;
    branch: string | null;
    amount: number;
    source: string;
    status: string;
    created_at: string | null;
}

interface ActiveSession {
    id: number;
    mac_address: string;
    ip_address: string | null;
    branch: string | null;
    package: string | null;
    expires_at: string | null;
    data_used_mb: number;
}

interface DeviceStatus {
    online: number;
    offline: number;
    provisioning: number;
}

interface EscalationSummary {
    total: number;
    unavailable_branches: number;
    open_incidents: number;
    payment_followups: number;
}

interface EscalationItem {
    type: 'branch_availability' | 'device_incident' | 'payment_followup';
    level: string;
    title: string;
    description: string;
    tenant: string | null;
    branch: string | null;
    occurred_at: string | null;
    href: string;
    action_label: string;
}

interface Escalations {
    summary: EscalationSummary;
    items: EscalationItem[];
}

interface MyFollowUpSummary {
    total: number;
    branches: number;
    devices: number;
    transactions: number;
    awaiting_acknowledgement: number;
    acknowledged: number;
}

interface MyFollowUpItem {
    type: 'branch' | 'device' | 'transaction';
    title: string;
    description: string;
    tenant: string | null;
    branch: string | null;
    assigned_at: string | null;
    acknowledged_at: string | null;
    href: string;
}

interface MyFollowUps {
    summary: MyFollowUpSummary;
    items: MyFollowUpItem[];
    queue_links: {
        branches: string;
        devices: string;
        transactions: string;
    };
}

interface DashboardProps {
    viewer: Viewer;
    summary: Summary;
    deviceStatus: DeviceStatus;
    escalations: Escalations;
    myFollowUps: MyFollowUps;
    revenueRows: RevenueRow[];
    recentTransactions: DashboardTransaction[];
    activeSessions: ActiveSession[];
}

const currency = new Intl.NumberFormat('en-TZ', {
    style: 'currency',
    currency: 'TZS',
    maximumFractionDigits: 0,
});

const dateTime = new Intl.DateTimeFormat('en-GB', {
    day: '2-digit',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
});

const statusTone: Record<string, string> = {
    successful: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
    active: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
    pending: 'bg-amber-500/10 text-amber-700 dark:text-amber-300',
    failed: 'bg-rose-500/10 text-rose-700 dark:text-rose-300',
    expired: 'bg-slate-500/10 text-slate-700 dark:text-slate-300',
    voucher: 'bg-cyan-500/10 text-cyan-700 dark:text-cyan-300',
    mobile_money: 'bg-indigo-500/10 text-indigo-700 dark:text-indigo-300',
};

const escalationTone: Record<string, string> = {
    critical: 'bg-rose-500/10 text-rose-700 dark:text-rose-300',
    high: 'bg-orange-500/10 text-orange-700 dark:text-orange-300',
    medium: 'bg-amber-500/10 text-amber-700 dark:text-amber-300',
    low: 'bg-sky-500/10 text-sky-700 dark:text-sky-300',
};

const escalationTypeIcon: Record<EscalationItem['type'], LucideIcon> = {
    branch_availability: ShieldAlert,
    device_incident: Wrench,
    payment_followup: AlertTriangle,
};

const escalationTypeLabel: Record<EscalationItem['type'], string> = {
    branch_availability: 'Branch availability',
    device_incident: 'Device incident',
    payment_followup: 'Payment follow-up',
};

const followUpTypeTone: Record<MyFollowUpItem['type'], string> = {
    branch: 'bg-amber-500/10 text-amber-700 dark:text-amber-300',
    device: 'bg-rose-500/10 text-rose-700 dark:text-rose-300',
    transaction: 'bg-indigo-500/10 text-indigo-700 dark:text-indigo-300',
};

function StatCard({ label, value, hint, icon: Icon }: { label: string; value: string; hint: string; icon: LucideIcon }) {
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

export default function Dashboard({ viewer, summary, deviceStatus, escalations, myFollowUps, revenueRows, recentTransactions, activeSessions }: DashboardProps) {
    const scopeLabel = viewer.scope === 'platform' ? 'Platform admin view' : 'Tenant operations view';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex flex-1 flex-col gap-6 rounded-xl p-4">
                <section className="border-border/70 from-primary/8 via-background rounded-2xl border bg-linear-to-br to-cyan-500/8 p-6">
                    <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div className="space-y-2">
                            <Badge variant="secondary" className="w-fit rounded-full px-3 py-1">
                                {scopeLabel}
                            </Badge>
                            <div>
                                <h1 className="text-3xl font-semibold tracking-tight">{viewer.name}</h1>
                                <p className="text-muted-foreground mt-1 max-w-2xl text-sm">
                                    Core platform basics are now in place: tenancy, packages, vouchers, transactions, sessions, revenue allocation,
                                    and seeded operational data.
                                </p>
                            </div>
                        </div>
                        <Badge variant="outline" className="w-fit rounded-full px-3 py-1 capitalize">
                            {viewer.role.replace('_', ' ')}
                        </Badge>
                    </div>
                </section>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <StatCard
                        label="Confirmed revenue"
                        value={currency.format(summary.revenue)}
                        hint="Successful package sales across the current scope."
                        icon={Wallet}
                    />
                    <StatCard
                        label="Active sessions"
                        value={summary.active_sessions.toString()}
                        hint="Customers currently online through paid or voucher access."
                        icon={Activity}
                    />
                    <StatCard
                        label={viewer.scope === 'platform' ? 'Active tenants' : 'Branches'}
                        value={(viewer.scope === 'platform' ? summary.tenants : summary.branches).toString()}
                        hint={viewer.scope === 'platform' ? 'Resellers operating on the platform.' : 'Operational hotspot points in this tenant.'}
                        icon={Building2}
                    />
                    <StatCard
                        label="Pending payouts"
                        value={currency.format(summary.pending_payouts)}
                        hint="Outstanding reseller settlements still waiting for processing."
                        icon={Landmark}
                    />
                    <StatCard
                        label="Pending transactions"
                        value={summary.pending_transactions.toString()}
                        hint="Payment attempts still waiting for callback confirmation."
                        icon={Clock3}
                    />
                    <StatCard
                        label="Open escalations"
                        value={summary.escalations.toString()}
                        hint="Branch downtime, hardware issues, and payment follow-ups that need attention."
                        icon={AlertTriangle}
                    />
                    <StatCard
                        label="Online devices"
                        value={deviceStatus.online.toString()}
                        hint={`${deviceStatus.offline} offline, ${deviceStatus.provisioning} provisioning.`}
                        icon={Router}
                    />
                </section>

                <section className="grid gap-4 xl:grid-cols-[0.8fr_1.2fr]">
                    <Card className="border-border/70">
                        <CardHeader>
                            <CardTitle>Escalation summary</CardTitle>
                            <CardDescription>Real operational issues currently rolled up across the active workspace scope.</CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-3 sm:grid-cols-3 xl:grid-cols-1">
                            {[
                                ['Unavailable branches', escalations.summary.unavailable_branches, 'text-amber-600'],
                                ['Open incidents', escalations.summary.open_incidents, 'text-rose-600'],
                                ['Payment follow-ups', escalations.summary.payment_followups, 'text-indigo-600'],
                            ].map(([label, count, tone]) => (
                                <div key={label} className="border-border/60 rounded-xl border px-4 py-3">
                                    <p className="text-muted-foreground text-sm">{label}</p>
                                    <p className={`mt-2 text-3xl font-semibold ${tone}`}>{count}</p>
                                </div>
                            ))}
                        </CardContent>
                    </Card>

                    <Card className="border-border/70">
                        <CardHeader>
                            <CardTitle>Escalation queue</CardTitle>
                            <CardDescription>Start here when branch availability, hardware health, or payment fulfilment needs operator follow-up.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {escalations.items.length > 0 ? (
                                escalations.items.map((item) => {
                                    const Icon = escalationTypeIcon[item.type];

                                    return (
                                        <div key={`${item.type}-${item.href}-${item.occurred_at ?? 'now'}`} className="border-border/60 rounded-xl border px-4 py-3">
                                            <div className="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                                <div className="space-y-2">
                                                    <div className="flex flex-wrap items-center gap-2">
                                                        <div className="bg-primary/10 text-primary rounded-lg p-2">
                                                            <Icon className="h-4 w-4" />
                                                        </div>
                                                        <p className="font-medium">{item.title}</p>
                                                        <Badge variant="outline" className={escalationTone[item.level] ?? escalationTone.medium}>
                                                            {item.level}
                                                        </Badge>
                                                        <Badge variant="outline">{escalationTypeLabel[item.type]}</Badge>
                                                    </div>
                                                    <p className="text-muted-foreground text-sm">{item.description}</p>
                                                    <p className="text-muted-foreground text-sm">
                                                        {[item.branch, item.tenant, item.occurred_at ? dateTime.format(new Date(item.occurred_at)) : null]
                                                            .filter(Boolean)
                                                            .join(' • ')}
                                                    </p>
                                                </div>
                                                <Link
                                                    href={item.href}
                                                    className="text-primary inline-flex items-center gap-2 text-sm font-medium hover:underline"
                                                >
                                                    {item.action_label}
                                                    <ArrowRight className="h-4 w-4" />
                                                </Link>
                                            </div>
                                        </div>
                                    );
                                })
                            ) : (
                                <div className="border-border/60 rounded-xl border border-dashed px-4 py-8 text-center">
                                    <p className="font-medium">No escalations are open right now.</p>
                                    <p className="text-muted-foreground mt-2 text-sm">
                                        Branches, devices, and payment follow-ups are currently clear for this workspace scope.
                                    </p>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </section>

                <section className="grid gap-4 xl:grid-cols-[0.8fr_1.2fr]">
                    <Card className="border-border/70">
                        <CardHeader>
                            <CardTitle>My follow-ups</CardTitle>
                            <CardDescription>Work currently assigned to you across branches, devices, and payment investigations.</CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-3 sm:grid-cols-3 xl:grid-cols-1">
                            {[
                                ['Total assigned', myFollowUps.summary.total, 'text-primary'],
                                ['Waiting for acknowledgement', myFollowUps.summary.awaiting_acknowledgement, 'text-amber-600'],
                                ['Acknowledged', myFollowUps.summary.acknowledged, 'text-emerald-600'],
                                ['Device follow-ups', myFollowUps.summary.devices, 'text-rose-600'],
                                ['Transaction follow-ups', myFollowUps.summary.transactions, 'text-indigo-600'],
                            ].map(([label, count, tone]) => (
                                <div key={label} className="border-border/60 rounded-xl border px-4 py-3">
                                    <p className="text-muted-foreground text-sm">{label}</p>
                                    <p className={`mt-2 text-3xl font-semibold ${tone}`}>{count}</p>
                                </div>
                            ))}
                        </CardContent>
                    </Card>

                    <Card className="border-border/70">
                        <CardHeader>
                            <CardTitle>Assigned queue</CardTitle>
                            <CardDescription>Jump straight into the items that currently belong to you.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {myFollowUps.items.length > 0 ? (
                                <>
                                    {myFollowUps.items.map((item) => (
                                        <div key={`${item.type}-${item.href}-${item.assigned_at ?? 'now'}`} className="border-border/60 rounded-xl border px-4 py-3">
                                            <div className="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                                <div className="space-y-2">
                                                    <div className="flex flex-wrap items-center gap-2">
                                                        <p className="font-medium">{item.title}</p>
                                                        <Badge variant="outline" className={followUpTypeTone[item.type]}>
                                                            {item.type}
                                                        </Badge>
                                                        <Badge
                                                            variant="outline"
                                                            className={
                                                                item.acknowledged_at
                                                                    ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'
                                                                    : 'bg-amber-500/10 text-amber-700 dark:text-amber-300'
                                                            }
                                                        >
                                                            {item.acknowledged_at ? 'Acknowledged' : 'Awaiting acknowledgement'}
                                                        </Badge>
                                                    </div>
                                                    <p className="text-muted-foreground text-sm">{item.description}</p>
                                                    <p className="text-muted-foreground text-sm">
                                                        {[
                                                            item.branch,
                                                            item.tenant,
                                                            item.assigned_at ? `Assigned ${dateTime.format(new Date(item.assigned_at))}` : null,
                                                            item.acknowledged_at ? `Acknowledged ${dateTime.format(new Date(item.acknowledged_at))}` : null,
                                                        ]
                                                            .filter(Boolean)
                                                            .join(' • ')}
                                                    </p>
                                                </div>
                                                <Link
                                                    href={item.href}
                                                    className="text-primary inline-flex items-center gap-2 text-sm font-medium hover:underline"
                                                >
                                                    Open follow-up
                                                    <ArrowRight className="h-4 w-4" />
                                                </Link>
                                            </div>
                                        </div>
                                    ))}

                                    <div className="flex flex-wrap gap-2">
                                        <Link href={myFollowUps.queue_links.branches} className="text-primary text-sm font-medium hover:underline">
                                            Branch queue
                                        </Link>
                                        <Link href={myFollowUps.queue_links.devices} className="text-primary text-sm font-medium hover:underline">
                                            Device queue
                                        </Link>
                                        <Link href={myFollowUps.queue_links.transactions} className="text-primary text-sm font-medium hover:underline">
                                            Transaction queue
                                        </Link>
                                    </div>
                                </>
                            ) : (
                                <div className="border-border/60 rounded-xl border border-dashed px-4 py-8 text-center">
                                    <p className="font-medium">Nothing is assigned to you right now.</p>
                                    <p className="text-muted-foreground mt-2 text-sm">
                                        When you take ownership of a branch, device, or transaction follow-up, it will appear here.
                                    </p>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </section>

                <section className="grid gap-4 xl:grid-cols-[1.15fr_0.85fr]">
                    <Card className="border-border/70">
                        <CardHeader>
                            <CardTitle>{viewer.scope === 'platform' ? 'Tenant performance' : 'Branch performance'}</CardTitle>
                            <CardDescription>
                                Revenue and live session pressure for the highest-activity {viewer.scope === 'platform' ? 'tenants' : 'branches'}.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {revenueRows.map((row) => (
                                <div key={row.name} className="border-border/60 flex items-center justify-between rounded-xl border px-4 py-3">
                                    <div className="space-y-1">
                                        <div className="flex items-center gap-2">
                                            <p className="font-medium">{row.name}</p>
                                            <Badge variant="outline" className="capitalize">
                                                {row.kind}
                                            </Badge>
                                        </div>
                                        <p className="text-muted-foreground text-sm">{row.location ?? 'Location not set'}</p>
                                    </div>
                                    <div className="text-right">
                                        <p className="font-semibold">{currency.format(row.revenue)}</p>
                                        <p className="text-muted-foreground text-sm">{row.active_sessions} active sessions</p>
                                    </div>
                                </div>
                            ))}
                        </CardContent>
                    </Card>

                    <Card className="border-border/70">
                        <CardHeader>
                            <CardTitle>Device status</CardTitle>
                            <CardDescription>Router integration remains stubbed, but operational status is tracked now.</CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-3 sm:grid-cols-3 xl:grid-cols-1">
                            {[
                                ['Online', deviceStatus.online, 'text-emerald-600'],
                                ['Offline', deviceStatus.offline, 'text-rose-600'],
                                ['Provisioning', deviceStatus.provisioning, 'text-amber-600'],
                            ].map(([label, count, tone]) => (
                                <div key={label} className="border-border/60 rounded-xl border px-4 py-3">
                                    <p className="text-muted-foreground text-sm">{label}</p>
                                    <p className={`mt-2 text-3xl font-semibold ${tone}`}>{count}</p>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                </section>

                <section className="grid gap-4 xl:grid-cols-2">
                    <Card className="border-border/70">
                        <CardHeader>
                            <CardTitle>Recent transactions</CardTitle>
                            <CardDescription>Latest sales across mobile money and voucher flows.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {recentTransactions.map((transaction) => (
                                <div key={transaction.id} className="border-border/60 rounded-xl border px-4 py-3">
                                    <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                        <div className="space-y-1">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <p className="font-medium">{transaction.reference}</p>
                                                <Badge variant="outline" className={statusTone[transaction.source] ?? ''}>
                                                    {transaction.source.replace('_', ' ')}
                                                </Badge>
                                                <Badge variant="outline" className={statusTone[transaction.status] ?? ''}>
                                                    {transaction.status}
                                                </Badge>
                                            </div>
                                            <p className="text-muted-foreground text-sm">
                                                {transaction.branch ?? transaction.tenant ?? 'Unassigned'} •{' '}
                                                {transaction.created_at ? dateTime.format(new Date(transaction.created_at)) : 'No timestamp'}
                                            </p>
                                        </div>
                                        <p className="font-semibold">{currency.format(transaction.amount)}</p>
                                    </div>
                                </div>
                            ))}
                        </CardContent>
                    </Card>

                    <Card className="border-border/70">
                        <CardHeader>
                            <CardTitle>Active customer sessions</CardTitle>
                            <CardDescription>Basic visibility into who is online before router hooks are added.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {activeSessions.map((session) => (
                                <div key={session.id} className="border-border/60 rounded-xl border px-4 py-3">
                                    <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                        <div className="space-y-1">
                                            <div className="flex items-center gap-2">
                                                <Cpu className="text-primary h-4 w-4" />
                                                <p className="font-medium">{session.mac_address}</p>
                                            </div>
                                            <p className="text-muted-foreground text-sm">
                                                {session.branch ?? 'Unknown branch'} • {session.package ?? 'Open package'}
                                            </p>
                                            <p className="text-muted-foreground text-sm">{session.ip_address ?? 'No IP captured yet'}</p>
                                        </div>
                                        <div className="text-right">
                                            <p className="font-semibold">{session.data_used_mb} MB used</p>
                                            <p className="text-muted-foreground text-sm">
                                                Expires {session.expires_at ? dateTime.format(new Date(session.expires_at)) : 'not scheduled'}
                                            </p>
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

import InputError from '@/components/input-error';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { OpsPageHeader } from '@/components/ops/ops-page-header';
import { OpsStatCard } from '@/components/ops/ops-stat-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { formatDataLimit, formatDateTime, formatMoney } from '@/lib/formatters';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { Activity, ArrowLeft, CircleAlert, Clock3, RadioTower, Router } from 'lucide-react';
import { FormEvent } from 'react';

interface Viewer {
    scope: 'platform' | 'tenant';
    name: string;
    role: string;
    currency?: string | null;
}

interface DeviceDetail {
    id: number;
    name: string;
    identifier: string;
    status: string;
    integration_driver: string;
    ip_address: string | null;
    last_seen_at: string | null;
    last_seen_age_minutes: number | null;
    status_note: string;
    metadata: Record<string, unknown> | null;
    tenant: string | null;
    branch: {
        name: string | null;
        code: string | null;
        location: string | null;
        address: string | null;
        manager_name: string | null;
        manager_email: string | null;
    };
}

interface BranchOverview {
    device_count: number;
    online_devices: number;
    active_sessions: number;
    pending_transactions: number;
    stale_pending_transactions: number;
    active_packages: number;
    open_incidents: number;
}

interface RecentSession {
    id: number;
    package: string | null;
    status: string;
    transaction_reference: string | null;
    started_at: string | null;
    expires_at: string | null;
    data_used_mb: number;
}

interface RecentTransaction {
    id: number;
    reference: string;
    status: string;
    source: string;
    amount: number;
    currency: string | null;
    package: string | null;
    provider_reference: string | null;
    created_at: string | null;
}

interface IncidentOption {
    value: string;
    label: string;
}

interface IncidentRow {
    id: number;
    title: string;
    details: string | null;
    severity: string;
    status: string;
    opened_at: string | null;
    resolved_at: string | null;
    resolution_notes: string | null;
    reporter: {
        name: string;
        email: string;
    } | null;
    resolver: {
        name: string;
        email: string;
    } | null;
    can_resolve: boolean;
}

interface DeviceShowProps {
    viewer: Viewer;
    device: DeviceDetail;
    branch_overview: BranchOverview;
    recent_sessions: RecentSession[];
    recent_transactions: RecentTransaction[];
    incident_options: IncidentOption[];
    incidents: IncidentRow[];
}

const tone: Record<string, string> = {
    online: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
    offline: 'bg-rose-500/10 text-rose-700 dark:text-rose-300',
    provisioning: 'bg-amber-500/10 text-amber-700 dark:text-amber-300',
    active: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
    pending: 'bg-amber-500/10 text-amber-700 dark:text-amber-300',
    expired: 'bg-slate-500/10 text-slate-700 dark:text-slate-300',
    terminated: 'bg-rose-500/10 text-rose-700 dark:text-rose-300',
    successful: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
    failed: 'bg-rose-500/10 text-rose-700 dark:text-rose-300',
    cancelled: 'bg-slate-500/10 text-slate-700 dark:text-slate-300',
    open: 'bg-amber-500/10 text-amber-700 dark:text-amber-300',
    resolved: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
    low: 'bg-slate-500/10 text-slate-700 dark:text-slate-300',
    medium: 'bg-sky-500/10 text-sky-700 dark:text-sky-300',
    high: 'bg-orange-500/10 text-orange-700 dark:text-orange-300',
    critical: 'bg-rose-500/10 text-rose-700 dark:text-rose-300',
};

function ResolveIncidentForm({ deviceId, incidentId }: { deviceId: number; incidentId: number }) {
    const form = useForm({
        resolution_notes: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(route('devices.incidents.resolve', [deviceId, incidentId]));
    };

    return (
        <form onSubmit={submit} className="mt-4 space-y-3">
            <div className="space-y-2">
                <Label htmlFor={`resolution_notes_${incidentId}`}>Resolution notes</Label>
                <textarea
                    id={`resolution_notes_${incidentId}`}
                    value={form.data.resolution_notes}
                    onChange={(event) => form.setData('resolution_notes', event.target.value)}
                    placeholder="Explain what fixed the issue or why the incident is now considered resolved."
                    className="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex min-h-24 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-hidden"
                />
                <InputError message={form.errors.resolution_notes} />
            </div>
            <Button type="submit" variant="outline" disabled={form.processing} className="rounded-xl">
                Resolve incident
            </Button>
        </form>
    );
}

export default function DeviceShow({
    viewer,
    device,
    branch_overview,
    recent_sessions,
    recent_transactions,
    incident_options,
    incidents,
}: DeviceShowProps) {
    const { flash } = usePage<SharedData>().props;
    const incidentForm = useForm({
        title: '',
        severity: incident_options[1]?.value ?? incident_options[0]?.value ?? 'medium',
        details: '',
    });
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Devices', href: '/devices' },
        { title: device.identifier, href: `/devices/${device.id}` },
    ];

    const submitIncident = (event: FormEvent) => {
        event.preventDefault();
        incidentForm.post(route('devices.incidents.store', device.id));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={device.identifier} />
            <div className="flex flex-1 flex-col gap-6 rounded-xl p-4">
                {flash?.success && (
                    <Alert className="border-emerald-500/25 bg-emerald-500/8 text-emerald-900 dark:text-emerald-100">
                        <CircleAlert className="size-4" />
                        <AlertTitle>Device workflow updated</AlertTitle>
                        <AlertDescription>{flash.success}</AlertDescription>
                    </Alert>
                )}

                {flash?.error && (
                    <Alert variant="destructive">
                        <CircleAlert className="size-4" />
                        <AlertTitle>Device action issue</AlertTitle>
                        <AlertDescription>{flash.error}</AlertDescription>
                    </Alert>
                )}

                <div className="flex justify-start">
                    <Link
                        href={route('devices.index')}
                        className="border-border/70 bg-background text-foreground inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-medium"
                    >
                        <ArrowLeft className="size-4" />
                        Back to devices
                    </Link>
                </div>

                <OpsPageHeader
                    title={device.name}
                    description="Device detail helps operators connect branch hardware state to nearby sessions and payment activity before escalating to router-level intervention."
                    viewer={viewer}
                />

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <OpsStatCard label="Branch devices" value={branch_overview.device_count.toString()} hint="Hardware records at this branch." icon={Router} />
                    <OpsStatCard label="Online on branch" value={branch_overview.online_devices.toString()} hint="Devices currently reporting in." icon={RadioTower} />
                    <OpsStatCard label="Active sessions" value={branch_overview.active_sessions.toString()} hint="Customer access currently live at this branch." icon={Activity} />
                    <OpsStatCard
                        label="Pending payments"
                        value={branch_overview.pending_transactions.toString()}
                        hint="Transactions still waiting for confirmation."
                        icon={Clock3}
                    />
                    <OpsStatCard
                        label="Open incidents"
                        value={branch_overview.open_incidents.toString()}
                        hint="Operational issues currently attached to this device."
                        icon={CircleAlert}
                    />
                </section>

                <section className="grid gap-4 xl:grid-cols-[1.05fr_0.95fr]">
                    <Card className="border-border/70">
                        <CardHeader>
                            <CardTitle>Device summary</CardTitle>
                            <CardDescription>Identity, branch ownership, and current operating state.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex flex-wrap items-center gap-2">
                                <Badge variant="outline" className={tone[device.status] ?? ''}>
                                    {device.status}
                                </Badge>
                                <Badge variant="outline">{device.identifier}</Badge>
                                {device.branch.code && <Badge variant="outline">{device.branch.code}</Badge>}
                            </div>

                            <div className="border-border/60 rounded-2xl border px-4 py-4">
                                <p className="font-medium">Operational note</p>
                                <p className="text-muted-foreground mt-2 text-sm leading-6">{device.status_note}</p>
                            </div>

                            <div className="grid gap-3 md:grid-cols-2">
                                {[
                                    ['Tenant', device.tenant],
                                    ['Branch', device.branch.name],
                                    ['Location', device.branch.location],
                                    ['Address', device.branch.address],
                                    ['Integration driver', device.integration_driver],
                                    ['IP address', device.ip_address],
                                    ['Last seen', formatDateTime(device.last_seen_at)],
                                    [
                                        'Freshness',
                                        device.last_seen_age_minutes !== null ? `${device.last_seen_age_minutes} min ago` : 'No heartbeat recorded',
                                    ],
                                    ['Branch manager', device.branch.manager_name],
                                    ['Support contact', device.branch.manager_email],
                                ].map(([label, value]) => (
                                    <div key={label} className="bg-muted/45 rounded-xl px-3 py-3">
                                        <p className="text-muted-foreground text-xs uppercase">{label}</p>
                                        <p className="mt-2 font-medium">{value || 'Not available'}</p>
                                    </div>
                                ))}
                            </div>

                            {device.metadata && (
                                <div className="space-y-2">
                                    <p className="font-medium">Captured metadata</p>
                                    <pre className="bg-muted/45 overflow-x-auto rounded-xl p-3 text-xs leading-6">
                                        {JSON.stringify(device.metadata, null, 2)}
                                    </pre>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card className="border-border/70">
                        <CardHeader>
                            <CardTitle>Branch impact snapshot</CardTitle>
                            <CardDescription>Quick signals to show whether this hardware issue is likely affecting nearby sales or access.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid gap-3 sm:grid-cols-2">
                                <div className="bg-muted/45 rounded-xl px-3 py-3">
                                    <p className="text-muted-foreground text-xs uppercase">Pending transactions</p>
                                    <p className="mt-2 text-2xl font-semibold">{branch_overview.pending_transactions}</p>
                                </div>
                                <div className="bg-muted/45 rounded-xl px-3 py-3">
                                    <p className="text-muted-foreground text-xs uppercase">Stale pending</p>
                                    <p className="mt-2 text-2xl font-semibold">{branch_overview.stale_pending_transactions}</p>
                                </div>
                                <div className="bg-muted/45 rounded-xl px-3 py-3">
                                    <p className="text-muted-foreground text-xs uppercase">Active packages</p>
                                    <p className="mt-2 text-2xl font-semibold">{branch_overview.active_packages}</p>
                                </div>
                                <div className="bg-muted/45 rounded-xl px-3 py-3">
                                    <p className="text-muted-foreground text-xs uppercase">Open incidents</p>
                                    <p className="mt-2 text-2xl font-semibold">{branch_overview.open_incidents}</p>
                                </div>
                            </div>

                            <div className="border-border/60 rounded-2xl border px-4 py-4">
                                <div className="flex items-start gap-3">
                                    <CircleAlert className="text-muted-foreground mt-0.5 size-4" />
                                    <div>
                                        <p className="font-medium">Operator guidance</p>
                                        <p className="text-muted-foreground mt-2 text-sm leading-6">
                                            {device.status === 'offline'
                                                ? 'If this branch also has pending payments or active sessions nearby, investigate the router path before treating failures as checkout-only issues.'
                                                : device.status === 'provisioning'
                                                  ? 'This branch is still ramping up. Verify packages and payment flows are not pointing customers to a device that is not fully live yet.'
                                                  : 'The device looks healthy. If customers still fail to gain access, inspect session fulfilment, open incidents, and transaction confirmation next.'}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </section>

                <section className="grid gap-4 xl:grid-cols-[1fr_1fr]">
                    <Card className="border-border/70">
                        <CardHeader>
                            <CardTitle>Device incidents</CardTitle>
                            <CardDescription>Track hardware issues in a way that is safe, auditable, and useful before direct router control is implemented.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <form onSubmit={submitIncident} className="space-y-4">
                                <div className="grid gap-4 md:grid-cols-[1fr_180px]">
                                    <div className="space-y-2">
                                        <Label htmlFor="incident_title">Incident title</Label>
                                        <input
                                            id="incident_title"
                                            value={incidentForm.data.title}
                                            onChange={(event) => incidentForm.setData('title', event.target.value)}
                                            placeholder="Router unreachable, power instability, uplink issue"
                                            className="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-hidden"
                                        />
                                        <InputError message={incidentForm.errors.title} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="incident_severity">Severity</Label>
                                        <select
                                            id="incident_severity"
                                            value={incidentForm.data.severity}
                                            onChange={(event) => incidentForm.setData('severity', event.target.value)}
                                            className="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-hidden"
                                        >
                                            {incident_options.map((option) => (
                                                <option key={option.value} value={option.value}>
                                                    {option.label}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={incidentForm.errors.severity} />
                                    </div>
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="incident_details">Details</Label>
                                    <textarea
                                        id="incident_details"
                                        value={incidentForm.data.details}
                                        onChange={(event) => incidentForm.setData('details', event.target.value)}
                                        placeholder="Capture what the branch staff or operator observed so this issue is useful later."
                                        className="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex min-h-28 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-hidden"
                                    />
                                    <InputError message={incidentForm.errors.details} />
                                </div>
                                <Button type="submit" disabled={incidentForm.processing} className="rounded-xl">
                                    Log incident
                                </Button>
                            </form>

                            {incidents.length === 0 && (
                                <div className="border-border/60 text-muted-foreground rounded-2xl border border-dashed px-4 py-8 text-center text-sm">
                                    No incidents have been logged for this device yet.
                                </div>
                            )}

                            {incidents.map((incident) => (
                                <div key={incident.id} className="border-border/60 rounded-2xl border px-4 py-4">
                                    <div className="flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between">
                                        <div className="space-y-2">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <p className="font-medium">{incident.title}</p>
                                                <Badge variant="outline" className={tone[incident.status] ?? ''}>
                                                    {incident.status}
                                                </Badge>
                                                <Badge variant="outline" className={tone[incident.severity] ?? ''}>
                                                    {incident.severity}
                                                </Badge>
                                            </div>
                                            <p className="text-muted-foreground text-sm">
                                                Reported {formatDateTime(incident.opened_at)} by {incident.reporter?.name || 'Unknown operator'}
                                            </p>
                                            {incident.details && <p className="text-muted-foreground text-sm leading-6">{incident.details}</p>}
                                        </div>
                                        {incident.resolved_at && (
                                            <div className="text-left xl:text-right">
                                                <p className="text-sm font-medium">Resolved</p>
                                                <p className="text-muted-foreground text-sm">{formatDateTime(incident.resolved_at)}</p>
                                            </div>
                                        )}
                                    </div>

                                    {incident.resolution_notes && (
                                        <div className="bg-muted/45 mt-4 rounded-xl px-3 py-3">
                                            <p className="text-muted-foreground text-xs uppercase">Resolution</p>
                                            <p className="mt-2 text-sm leading-6">{incident.resolution_notes}</p>
                                            <p className="text-muted-foreground mt-2 text-sm">
                                                Resolved by {incident.resolver?.name || 'Unknown operator'} • {incident.resolver?.email || 'No contact recorded'}
                                            </p>
                                        </div>
                                    )}

                                    {incident.can_resolve && <ResolveIncidentForm deviceId={device.id} incidentId={incident.id} />}
                                </div>
                            ))}
                        </CardContent>
                    </Card>

                    <Card className="border-border/70">
                        <CardHeader>
                            <CardTitle>Recent branch sessions</CardTitle>
                            <CardDescription>Nearby fulfilment evidence that may help explain customer-facing issues.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {recent_sessions.length === 0 && (
                                <div className="border-border/60 text-muted-foreground rounded-2xl border border-dashed px-4 py-8 text-center text-sm">
                                    No recent sessions were found for this branch.
                                </div>
                            )}
                            {recent_sessions.map((session) => (
                                <div key={session.id} className="border-border/60 rounded-2xl border px-4 py-4">
                                    <div className="flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between">
                                        <div className="space-y-2">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <p className="font-medium">{session.package ?? 'Package unavailable'}</p>
                                                <Badge variant="outline" className={tone[session.status] ?? ''}>
                                                    {session.status}
                                                </Badge>
                                            </div>
                                            <p className="text-muted-foreground text-sm">
                                                {session.transaction_reference ? `Transaction ${session.transaction_reference}` : 'No transaction reference'}
                                            </p>
                                            <p className="text-muted-foreground text-sm">
                                                Started {formatDateTime(session.started_at)} • Expires {formatDateTime(session.expires_at)}
                                            </p>
                                        </div>
                                        <div className="flex flex-col items-start gap-2 xl:items-end">
                                            <p className="text-muted-foreground text-sm">{formatDataLimit(session.data_used_mb)} used</p>
                                            <Button asChild variant="outline" size="sm" className="rounded-lg">
                                                <Link href={route('sessions.show', session.id)}>Open session</Link>
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                </section>

                <Card className="border-border/70">
                    <CardHeader>
                        <CardTitle>Recent branch transactions</CardTitle>
                        <CardDescription>Payment context around this device’s branch, useful when outages and pending payments happen together.</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {recent_transactions.length === 0 && (
                            <div className="border-border/60 text-muted-foreground rounded-2xl border border-dashed px-4 py-8 text-center text-sm">
                                No recent transactions were found for this branch.
                            </div>
                        )}
                        {recent_transactions.map((transaction) => (
                            <div key={transaction.id} className="border-border/60 rounded-2xl border px-4 py-4">
                                <div className="flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between">
                                    <div className="space-y-2">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <p className="font-medium">{transaction.reference}</p>
                                            <Badge variant="outline" className={tone[transaction.status] ?? ''}>
                                                {transaction.status}
                                            </Badge>
                                            <Badge variant="outline">{transaction.source.replaceAll('_', ' ')}</Badge>
                                        </div>
                                        <p className="text-muted-foreground text-sm">
                                            {transaction.package ?? 'Package unavailable'}
                                            {transaction.provider_reference ? ` • ${transaction.provider_reference}` : ''}
                                        </p>
                                        <p className="text-muted-foreground text-sm">{formatDateTime(transaction.created_at)}</p>
                                    </div>
                                    <div className="flex flex-col items-start gap-2 xl:items-end">
                                        <p className="font-medium">{formatMoney(transaction.amount, transaction.currency)}</p>
                                        <Button asChild variant="outline" size="sm" className="rounded-lg">
                                            <Link href={route('transactions.show', transaction.id)}>Open transaction</Link>
                                        </Button>
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

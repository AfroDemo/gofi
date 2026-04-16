import InputError from '@/components/input-error';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { FollowUpNotesPanel, type FollowUpNoteRow } from '@/components/ops/follow-up-notes-panel';
import { OpsPageHeader } from '@/components/ops/ops-page-header';
import { OpsStatCard } from '@/components/ops/ops-stat-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { formatDateTime, formatMoney } from '@/lib/formatters';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { Activity, ArrowLeft, CircleAlert, Clock3, MapPinned, Router } from 'lucide-react';
import { FormEvent } from 'react';

interface Viewer {
    scope: 'platform' | 'tenant';
    name: string;
    role: string;
    currency?: string | null;
}

interface BranchDetail {
    id: number;
    tenant: string | null;
    name: string;
    code: string;
    status: string;
    status_note: string;
    location: string | null;
    address: string | null;
    currency: string | null;
    manager: {
        name: string | null;
        email: string | null;
    };
}

interface StatusOption {
    value: string;
    label: string;
}

interface Summary {
    devices: number;
    online_devices: number;
    active_sessions: number;
    pending_transactions: number;
    open_incidents: number;
    successful_revenue: number;
}

interface StatusHistoryRow {
    id: number;
    from_status: string | null;
    to_status: string;
    reason: string;
    created_at: string | null;
    actor: {
        name: string;
        email: string;
    } | null;
}

interface RecentTransaction {
    id: number;
    reference: string;
    status: string;
    source: string;
    amount: number;
    currency: string | null;
    package: string | null;
    created_at: string | null;
}

interface RecentSession {
    id: number;
    package: string | null;
    status: string;
    transaction_reference: string | null;
    started_at: string | null;
    expires_at: string | null;
}

interface RecentIncident {
    id: number;
    title: string;
    status: string;
    severity: string;
    device_name: string | null;
    device_identifier: string | null;
    reporter_name: string | null;
    resolver_name: string | null;
    opened_at: string | null;
    resolved_at: string | null;
}

interface BranchShowProps {
    viewer: Viewer;
    branch: BranchDetail;
    status_options: StatusOption[];
    summary: Summary;
    status_history: StatusHistoryRow[];
    recent_transactions: RecentTransaction[];
    recent_sessions: RecentSession[];
    recent_incidents: RecentIncident[];
    notes: FollowUpNoteRow[];
}

const tone: Record<string, string> = {
    active: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
    maintenance: 'bg-amber-500/10 text-amber-700 dark:text-amber-300',
    inactive: 'bg-slate-500/10 text-slate-700 dark:text-slate-300',
    open: 'bg-amber-500/10 text-amber-700 dark:text-amber-300',
    resolved: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
    low: 'bg-slate-500/10 text-slate-700 dark:text-slate-300',
    medium: 'bg-sky-500/10 text-sky-700 dark:text-sky-300',
    high: 'bg-orange-500/10 text-orange-700 dark:text-orange-300',
    critical: 'bg-rose-500/10 text-rose-700 dark:text-rose-300',
    pending: 'bg-amber-500/10 text-amber-700 dark:text-amber-300',
    successful: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
    failed: 'bg-rose-500/10 text-rose-700 dark:text-rose-300',
    cancelled: 'bg-slate-500/10 text-slate-700 dark:text-slate-300',
    expired: 'bg-slate-500/10 text-slate-700 dark:text-slate-300',
    terminated: 'bg-rose-500/10 text-rose-700 dark:text-rose-300',
};

export default function BranchShow({
    viewer,
    branch,
    status_options,
    summary,
    status_history,
    recent_transactions,
    recent_sessions,
    recent_incidents,
    notes,
}: BranchShowProps) {
    const { flash } = usePage<SharedData>().props;
    const form = useForm({
        status: status_options.find((option) => option.value !== branch.status)?.value ?? branch.status,
        reason: '',
    });
    const noteForm = useForm({
        note: '',
    });
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Branches', href: '/branches' },
        { title: branch.name, href: `/branches/${branch.id}` },
    ];

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(route('branches.update-status', branch.id));
    };

    const submitNote = (event: FormEvent) => {
        event.preventDefault();
        noteForm.post(route('branches.notes.store', branch.id), {
            preserveScroll: true,
            onSuccess: () => noteForm.reset(),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={branch.name} />
            <div className="flex flex-1 flex-col gap-6 rounded-xl p-4">
                {flash?.success && (
                    <Alert className="border-emerald-500/25 bg-emerald-500/8 text-emerald-900 dark:text-emerald-100">
                        <CircleAlert className="size-4" />
                        <AlertTitle>Branch updated</AlertTitle>
                        <AlertDescription>{flash.success}</AlertDescription>
                    </Alert>
                )}

                {flash?.error && (
                    <Alert variant="destructive">
                        <CircleAlert className="size-4" />
                        <AlertTitle>Branch action issue</AlertTitle>
                        <AlertDescription>{flash.error}</AlertDescription>
                    </Alert>
                )}

                <div className="flex justify-start">
                    <Link
                        href={route('branches.index')}
                        className="border-border/70 bg-background text-foreground inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-medium"
                    >
                        <ArrowLeft className="size-4" />
                        Back to branches
                    </Link>
                </div>

                <OpsPageHeader
                    title={branch.name}
                    description="Branch detail connects status, device health, incidents, sessions, and revenue so operators can decide whether a site should remain live, go into maintenance, or return to service."
                    viewer={viewer}
                />

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <OpsStatCard label="Devices" value={summary.devices.toString()} hint="Routers and hotspot devices mapped to this branch." icon={Router} />
                    <OpsStatCard label="Online devices" value={summary.online_devices.toString()} hint="Hardware currently reporting healthy." icon={MapPinned} />
                    <OpsStatCard label="Active sessions" value={summary.active_sessions.toString()} hint="Customer access currently live at this site." icon={Activity} />
                    <OpsStatCard
                        label="Pending payments"
                        value={summary.pending_transactions.toString()}
                        hint="Transactions still waiting for confirmation."
                        icon={Clock3}
                    />
                    <OpsStatCard
                        label="Open incidents"
                        value={summary.open_incidents.toString()}
                        hint="Outstanding branch-linked operational issues."
                        icon={CircleAlert}
                    />
                </section>

                <section className="grid gap-4 xl:grid-cols-[1.05fr_0.95fr]">
                    <Card className="border-border/70">
                        <CardHeader>
                            <CardTitle>Branch summary</CardTitle>
                            <CardDescription>Operational identity, current status, and on-the-ground ownership.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex flex-wrap items-center gap-2">
                                <Badge variant="outline" className={tone[branch.status] ?? ''}>
                                    {branch.status}
                                </Badge>
                                <Badge variant="outline">{branch.code}</Badge>
                            </div>

                            <div className="border-border/60 rounded-2xl border px-4 py-4">
                                <p className="font-medium">Operational note</p>
                                <p className="text-muted-foreground mt-2 text-sm leading-6">{branch.status_note}</p>
                            </div>

                            <div className="grid gap-3 md:grid-cols-2">
                                {[
                                    ['Tenant', branch.tenant],
                                    ['Location', branch.location],
                                    ['Address', branch.address],
                                    ['Manager', branch.manager.name],
                                    ['Support contact', branch.manager.email],
                                    ['Successful revenue', formatMoney(summary.successful_revenue, branch.currency)],
                                ].map(([label, value]) => (
                                    <div key={label} className="bg-muted/45 rounded-xl px-3 py-3">
                                        <p className="text-muted-foreground text-xs uppercase">{label}</p>
                                        <p className="mt-2 font-medium">{value || 'Not available'}</p>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border-border/70">
                        <CardHeader>
                            <CardTitle>Change branch status</CardTitle>
                            <CardDescription>Move a branch into maintenance, reactivate it, or mark it inactive with an auditable reason.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <form onSubmit={submit} className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="status">New status</Label>
                                    <select
                                        id="status"
                                        value={form.data.status}
                                        onChange={(event) => form.setData('status', event.target.value)}
                                        className="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-hidden"
                                    >
                                        {status_options.map((option) => (
                                            <option key={option.value} value={option.value}>
                                                {option.label}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={form.errors.status} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="reason">Reason</Label>
                                    <textarea
                                        id="reason"
                                        value={form.data.reason}
                                        onChange={(event) => form.setData('reason', event.target.value)}
                                        placeholder="Explain why the branch status is changing."
                                        className="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex min-h-28 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-hidden"
                                    />
                                    <InputError message={form.errors.reason} />
                                </div>

                                <Button type="submit" disabled={form.processing} className="rounded-xl">
                                    Update branch status
                                </Button>
                            </form>

                            <div className="border-border/60 rounded-2xl border px-4 py-4">
                                <p className="font-medium">When to use this</p>
                                <p className="text-muted-foreground mt-2 text-sm leading-6">
                                    Use maintenance when the whole site is unstable, inactive when the branch is intentionally out of service, and active when the branch is ready for live customer traffic again.
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </section>

                <FollowUpNotesPanel
                    title="Operator follow-up notes"
                    description="Capture who picked this up, what was checked, and what still needs attention so branch work does not rely on memory."
                    notes={notes}
                    note={noteForm.data.note}
                    onNoteChange={(value) => noteForm.setData('note', value)}
                    onSubmit={submitNote}
                    error={noteForm.errors.note}
                    processing={noteForm.processing}
                    emptyMessage="No follow-up notes have been recorded for this branch yet."
                />

                <section className="grid gap-4 xl:grid-cols-[1fr_1fr]">
                    <Card className="border-border/70">
                        <CardHeader>
                            <CardTitle>Status history</CardTitle>
                            <CardDescription>Audit trail for branch-level operational changes.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {status_history.length === 0 && (
                                <div className="border-border/60 text-muted-foreground rounded-2xl border border-dashed px-4 py-8 text-center text-sm">
                                    No status changes have been recorded for this branch yet.
                                </div>
                            )}
                            {status_history.map((event) => (
                                <div key={event.id} className="border-border/60 rounded-2xl border px-4 py-4">
                                    <div className="flex flex-wrap items-center gap-2">
                                        {event.from_status && <Badge variant="outline">{event.from_status}</Badge>}
                                        <Badge variant="outline" className={tone[event.to_status] ?? ''}>
                                            {event.to_status}
                                        </Badge>
                                    </div>
                                    <p className="mt-3 text-sm leading-6">{event.reason}</p>
                                    <p className="text-muted-foreground mt-2 text-sm">
                                        {formatDateTime(event.created_at)} • {event.actor?.name || 'Unknown operator'}
                                        {event.actor?.email ? ` • ${event.actor.email}` : ''}
                                    </p>
                                </div>
                            ))}
                        </CardContent>
                    </Card>

                    <Card className="border-border/70">
                        <CardHeader>
                            <CardTitle>Recent incidents</CardTitle>
                            <CardDescription>Branch-linked device issues and whether they are still open.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {recent_incidents.length === 0 && (
                                <div className="border-border/60 text-muted-foreground rounded-2xl border border-dashed px-4 py-8 text-center text-sm">
                                    No incidents have been recorded for this branch yet.
                                </div>
                            )}
                            {recent_incidents.map((incident) => (
                                <div key={incident.id} className="border-border/60 rounded-2xl border px-4 py-4">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <p className="font-medium">{incident.title}</p>
                                        <Badge variant="outline" className={tone[incident.status] ?? ''}>
                                            {incident.status}
                                        </Badge>
                                        <Badge variant="outline" className={tone[incident.severity] ?? ''}>
                                            {incident.severity}
                                        </Badge>
                                    </div>
                                    <p className="text-muted-foreground mt-2 text-sm">
                                        {[incident.device_name, incident.device_identifier].filter(Boolean).join(' • ') || 'Device context unavailable'}
                                    </p>
                                    <p className="text-muted-foreground mt-2 text-sm">
                                        Opened {formatDateTime(incident.opened_at)}
                                        {incident.reporter_name ? ` • ${incident.reporter_name}` : ''}
                                        {incident.resolved_at ? ` • Resolved ${formatDateTime(incident.resolved_at)}` : ''}
                                    </p>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                </section>

                <section className="grid gap-4 xl:grid-cols-[1fr_1fr]">
                    <Card className="border-border/70">
                        <CardHeader>
                            <CardTitle>Recent sessions</CardTitle>
                            <CardDescription>Customer access activity currently tied to this branch.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {recent_sessions.length === 0 && (
                                <div className="border-border/60 text-muted-foreground rounded-2xl border border-dashed px-4 py-8 text-center text-sm">
                                    No recent sessions were found for this branch.
                                </div>
                            )}
                            {recent_sessions.map((session) => (
                                <div key={session.id} className="border-border/60 rounded-2xl border px-4 py-4">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <p className="font-medium">{session.package ?? 'Package unavailable'}</p>
                                        <Badge variant="outline" className={tone[session.status] ?? ''}>
                                            {session.status}
                                        </Badge>
                                    </div>
                                    <p className="text-muted-foreground mt-2 text-sm">
                                        {session.transaction_reference ? `Transaction ${session.transaction_reference}` : 'No transaction reference'}
                                    </p>
                                    <p className="text-muted-foreground mt-2 text-sm">
                                        Started {formatDateTime(session.started_at)} • Expires {formatDateTime(session.expires_at)}
                                    </p>
                                </div>
                            ))}
                        </CardContent>
                    </Card>

                    <Card className="border-border/70">
                        <CardHeader>
                            <CardTitle>Recent transactions</CardTitle>
                            <CardDescription>Commercial activity that may be affected by branch-wide maintenance or outages.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {recent_transactions.length === 0 && (
                                <div className="border-border/60 text-muted-foreground rounded-2xl border border-dashed px-4 py-8 text-center text-sm">
                                    No recent transactions were found for this branch.
                                </div>
                            )}
                            {recent_transactions.map((transaction) => (
                                <div key={transaction.id} className="border-border/60 rounded-2xl border px-4 py-4">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <p className="font-medium">{transaction.reference}</p>
                                        <Badge variant="outline" className={tone[transaction.status] ?? ''}>
                                            {transaction.status}
                                        </Badge>
                                    </div>
                                    <p className="text-muted-foreground mt-2 text-sm">
                                        {transaction.package ?? 'Package unavailable'} • {transaction.source.replaceAll('_', ' ')}
                                    </p>
                                    <p className="text-muted-foreground mt-2 text-sm">
                                        {formatMoney(transaction.amount, transaction.currency)} • {formatDateTime(transaction.created_at)}
                                    </p>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                </section>
            </div>
        </AppLayout>
    );
}

import InputError from '@/components/input-error';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { OpsPageHeader } from '@/components/ops/ops-page-header';
import { OpsStatCard } from '@/components/ops/ops-stat-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { formatDataLimit, formatDateTime, formatMinutes, formatMoney, formatSpeed } from '@/lib/formatters';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, CircleAlert, Clock3, DatabaseZap, ShieldCheck, Ticket, Wallet } from 'lucide-react';
import { FormEvent } from 'react';

interface Viewer {
    scope: 'platform' | 'tenant';
    name: string;
    role: string;
    currency?: string | null;
}

interface SessionDetail {
    id: number;
    status: string;
    status_note: string;
    tenant: string | null;
    branch: {
        name: string | null;
        code: string | null;
        location: string | null;
        address: string | null;
        manager_name: string | null;
        manager_email: string | null;
    };
    package: {
        name: string;
        description: string | null;
        price: number;
        currency: string | null;
        duration_minutes: number | null;
        data_limit_mb: number | null;
        speed_limit_kbps: number | null;
    } | null;
    voucher: {
        code: string;
        status: string;
        locked_mac_address: string | null;
        redeemed_at: string | null;
        expires_at: string | null;
    } | null;
    transaction: {
        id: number;
        reference: string;
        status: string;
        source: string;
        amount: number;
        gateway_fee: number;
        currency: string | null;
        provider_reference: string | null;
        created_at: string | null;
        confirmed_at: string | null;
        needs_follow_up: boolean;
    } | null;
    authorizer: {
        name: string;
        email: string;
    } | null;
    terminator: {
        name: string;
        email: string;
    } | null;
    mac_address: string;
    ip_address: string | null;
    duration_minutes: number | null;
    data_limit_mb: number | null;
    data_used_mb: number;
    usage_percentage: number | null;
    started_at: string | null;
    expires_at: string | null;
    ended_at: string | null;
    started_age_minutes: number | null;
    expires_in_minutes: number | null;
    expired_since_minutes: number | null;
    termination_reason: string | null;
    can_terminate: boolean;
}

interface SessionShowProps {
    viewer: Viewer;
    session: SessionDetail;
}

const tone: Record<string, string> = {
    active: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
    pending: 'bg-amber-500/10 text-amber-700 dark:text-amber-300',
    expired: 'bg-slate-500/10 text-slate-700 dark:text-slate-300',
    terminated: 'bg-rose-500/10 text-rose-700 dark:text-rose-300',
    successful: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
    failed: 'bg-rose-500/10 text-rose-700 dark:text-rose-300',
    cancelled: 'bg-slate-500/10 text-slate-700 dark:text-slate-300',
    mobile_money: 'bg-indigo-500/10 text-indigo-700 dark:text-indigo-300',
    voucher: 'bg-cyan-500/10 text-cyan-700 dark:text-cyan-300',
};

export default function SessionShow({ viewer, session }: SessionShowProps) {
    const { flash } = usePage<SharedData>().props;
    const terminateForm = useForm({
        termination_reason: '',
    });
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Sessions', href: '/sessions' },
        { title: `Session #${session.id}`, href: `/sessions/${session.id}` },
    ];

    const submitTermination = (event: FormEvent) => {
        event.preventDefault();
        terminateForm.post(route('sessions.terminate', session.id));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Session #${session.id}`} />
            <div className="flex flex-1 flex-col gap-6 rounded-xl p-4">
                {flash?.success && (
                    <Alert className="border-emerald-500/25 bg-emerald-500/8 text-emerald-900 dark:text-emerald-100">
                        <CircleAlert className="size-4" />
                        <AlertTitle>Session updated</AlertTitle>
                        <AlertDescription>{flash.success}</AlertDescription>
                    </Alert>
                )}

                {flash?.error && (
                    <Alert variant="destructive">
                        <CircleAlert className="size-4" />
                        <AlertTitle>Session action issue</AlertTitle>
                        <AlertDescription>{flash.error}</AlertDescription>
                    </Alert>
                )}

                <div className="flex justify-start">
                    <Link
                        href={route('sessions.index')}
                        className="border-border/70 bg-background text-foreground inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-medium"
                    >
                        <ArrowLeft className="size-4" />
                        Back to sessions
                    </Link>
                </div>

                <OpsPageHeader
                    title={`Session #${session.id}`}
                    description="Session detail connects customer access to its branch, package, voucher, and transaction evidence so operators can verify fulfilment instead of guessing from list rows."
                    viewer={viewer}
                />

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <OpsStatCard label="Data used" value={formatDataLimit(session.data_used_mb)} hint="Observed usage on this session." icon={DatabaseZap} />
                    <OpsStatCard
                        label="Duration policy"
                        value={formatMinutes(session.duration_minutes ?? session.package?.duration_minutes)}
                        hint="Configured access duration for this session."
                        icon={Clock3}
                    />
                    <OpsStatCard
                        label="Data cap"
                        value={formatDataLimit(session.data_limit_mb ?? session.package?.data_limit_mb)}
                        hint="Maximum data allowance if one exists."
                        icon={ShieldCheck}
                    />
                    <OpsStatCard
                        label="Voucher"
                        value={session.voucher?.code ?? 'No voucher'}
                        hint="Voucher evidence linked to this session."
                        icon={Ticket}
                    />
                    <OpsStatCard
                        label="Transaction"
                        value={session.transaction?.reference ?? 'No transaction'}
                        hint="Payment record connected to access fulfilment."
                        icon={Wallet}
                    />
                </section>

                <section className="grid gap-4 xl:grid-cols-[1.05fr_0.95fr]">
                    <Card className="border-border/70">
                        <CardHeader>
                            <CardTitle>Session summary</CardTitle>
                            <CardDescription>Access identity, timing, branch context, and status evidence.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex flex-wrap items-center gap-2">
                                <Badge variant="outline" className={tone[session.status] ?? ''}>
                                    {session.status}
                                </Badge>
                                {session.transaction && (
                                    <Badge variant="outline" className={tone[session.transaction.source] ?? ''}>
                                        {session.transaction.source.replaceAll('_', ' ')}
                                    </Badge>
                                )}
                                {session.usage_percentage !== null && <Badge variant="outline">{session.usage_percentage}% of cap used</Badge>}
                            </div>

                            <div className="border-border/60 rounded-2xl border px-4 py-4">
                                <p className="font-medium">Operational note</p>
                                <p className="text-muted-foreground mt-2 text-sm leading-6">{session.status_note}</p>
                            </div>

                            <div className="grid gap-3 md:grid-cols-2">
                                {[
                                    ['Tenant', session.tenant],
                                    ['Branch', session.branch.name],
                                    ['Location', session.branch.location],
                                    ['Address', session.branch.address],
                                    ['Package', session.package?.name],
                                    ['Started', formatDateTime(session.started_at)],
                                    ['Expires', formatDateTime(session.expires_at)],
                                    ['Ended', formatDateTime(session.ended_at)],
                                    ['Started age', session.started_age_minutes !== null ? `${session.started_age_minutes} min ago` : 'Not available'],
                                    [
                                        'Expiry state',
                                        session.expires_in_minutes !== null
                                            ? `${session.expires_in_minutes} min remaining`
                                            : session.expired_since_minutes !== null
                                              ? `Expired ${session.expired_since_minutes} min ago`
                                              : 'Not available',
                                    ],
                                    ['IP address', session.ip_address],
                                    ['MAC address', session.mac_address],
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
                            <CardTitle>Fulfilment evidence</CardTitle>
                            <CardDescription>What granted access, who authorized it, and whether the payment trail needs follow-up.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid gap-3 sm:grid-cols-2">
                                <div className="bg-muted/45 rounded-xl px-3 py-3">
                                    <p className="text-muted-foreground text-xs uppercase">Branch manager</p>
                                    <p className="mt-2 font-medium">{session.branch.manager_name || 'Not assigned'}</p>
                                    <p className="text-muted-foreground mt-1 text-sm">{session.branch.manager_email || 'No contact recorded'}</p>
                                </div>
                                <div className="bg-muted/45 rounded-xl px-3 py-3">
                                    <p className="text-muted-foreground text-xs uppercase">Authorized by</p>
                                    <p className="mt-2 font-medium">{session.authorizer?.name || 'System or unknown'}</p>
                                    <p className="text-muted-foreground mt-1 text-sm">{session.authorizer?.email || 'No operator recorded'}</p>
                                </div>
                            </div>

                            {session.termination_reason && (
                                <div className="border-border/60 rounded-2xl border px-4 py-4">
                                    <p className="font-medium">Termination record</p>
                                    <p className="text-muted-foreground mt-2 text-sm leading-6">{session.termination_reason}</p>
                                    <p className="text-muted-foreground mt-3 text-sm">
                                        Ended by {session.terminator?.name || 'Unknown operator'} • {session.terminator?.email || 'No contact recorded'}
                                    </p>
                                </div>
                            )}

                            {session.package && (
                                <div className="border-border/60 rounded-2xl border px-4 py-4">
                                    <p className="font-medium">{session.package.name}</p>
                                    <p className="text-muted-foreground mt-2 text-sm leading-6">
                                        {session.package.description || 'No package description recorded.'}
                                    </p>
                                    <div className="mt-4 grid gap-3 sm:grid-cols-2">
                                        <div className="bg-muted/45 rounded-xl px-3 py-3">
                                            <p className="text-muted-foreground text-xs uppercase">Price</p>
                                            <p className="mt-2 font-medium">{formatMoney(session.package.price, session.package.currency)}</p>
                                        </div>
                                        <div className="bg-muted/45 rounded-xl px-3 py-3">
                                            <p className="text-muted-foreground text-xs uppercase">Speed profile</p>
                                            <p className="mt-2 font-medium">{formatSpeed(session.package.speed_limit_kbps)}</p>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {session.voucher ? (
                                <div className="border-border/60 rounded-2xl border px-4 py-4">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <p className="font-medium">{session.voucher.code}</p>
                                        <Badge variant="outline">{session.voucher.status}</Badge>
                                    </div>
                                    <p className="text-muted-foreground mt-2 text-sm">
                                        Locked MAC: {session.voucher.locked_mac_address || 'Not locked'}
                                    </p>
                                    <p className="text-muted-foreground mt-2 text-sm">
                                        Redeemed {formatDateTime(session.voucher.redeemed_at)} • Expires {formatDateTime(session.voucher.expires_at)}
                                    </p>
                                </div>
                            ) : (
                                <div className="border-border/60 text-muted-foreground rounded-2xl border border-dashed px-4 py-8 text-center text-sm">
                                    This session was not created from a voucher.
                                </div>
                            )}

                            {session.transaction ? (
                                <div className="border-border/60 rounded-2xl border px-4 py-4">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <p className="font-medium">{session.transaction.reference}</p>
                                        <Badge variant="outline" className={tone[session.transaction.status] ?? ''}>
                                            {session.transaction.status}
                                        </Badge>
                                    </div>
                                    <p className="text-muted-foreground mt-2 text-sm">
                                        {formatMoney(session.transaction.amount, session.transaction.currency)} • {session.transaction.provider_reference || 'No provider reference'}
                                    </p>
                                    <p className="text-muted-foreground mt-2 text-sm">
                                        Created {formatDateTime(session.transaction.created_at)} • Confirmed {formatDateTime(session.transaction.confirmed_at)}
                                    </p>
                                    <div className="mt-4 flex flex-wrap gap-2">
                                        <Button asChild variant="outline" className="rounded-lg">
                                            <Link href={route('transactions.show', session.transaction.id)}>Open transaction</Link>
                                        </Button>
                                        {session.transaction.needs_follow_up && (
                                            <Badge variant="outline" className={tone.pending}>
                                                needs payment follow-up
                                            </Badge>
                                        )}
                                    </div>
                                </div>
                            ) : (
                                <div className="border-border/60 text-muted-foreground rounded-2xl border border-dashed px-4 py-8 text-center text-sm">
                                    No transaction is linked to this session.
                                </div>
                            )}

                            {session.can_terminate && (
                                <div className="border-border/60 rounded-2xl border px-4 py-4">
                                    <p className="font-medium">Terminate session</p>
                                    <p className="text-muted-foreground mt-2 text-sm leading-6">
                                        Use this when access should stop early due to customer request, branch intervention, or an operational issue that should not wait for expiry.
                                    </p>
                                    <form onSubmit={submitTermination} className="mt-4 space-y-4">
                                        <div className="space-y-2">
                                            <Label htmlFor="termination_reason">Reason</Label>
                                            <textarea
                                                id="termination_reason"
                                                value={terminateForm.data.termination_reason}
                                                onChange={(event) => terminateForm.setData('termination_reason', event.target.value)}
                                                placeholder="Explain why the session is being terminated early."
                                                className="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex min-h-28 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-hidden"
                                            />
                                            <InputError message={terminateForm.errors.termination_reason} />
                                        </div>
                                        <Button type="submit" variant="destructive" disabled={terminateForm.processing} className="rounded-xl">
                                            Terminate session
                                        </Button>
                                    </form>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </section>
            </div>
        </AppLayout>
    );
}
